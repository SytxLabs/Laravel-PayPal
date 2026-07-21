<?php

/** @noinspection PhpUnused */

namespace SytxLabs\PayPal\Services;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Collection;
use RuntimeException;
use SytxLabs\PayPal\Enums\DTO\LinkHTTPMethod;
use SytxLabs\PayPal\Enums\DTO\Subscription\IntervalUnit;
use SytxLabs\PayPal\Enums\DTO\Subscription\SubscriptionStatus;
use SytxLabs\PayPal\Enums\DTO\Subscription\TenureType;
use SytxLabs\PayPal\Exception\CreateCatalogProductException;
use SytxLabs\PayPal\Exception\CreatePlanException;
use SytxLabs\PayPal\Exception\CreateSubscriptionException;
use SytxLabs\PayPal\Models\DTO\LinkDescription;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Product;
use SytxLabs\PayPal\Models\DTO\Subscription\BillingCycle;
use SytxLabs\PayPal\Models\DTO\Subscription\CatalogProduct;
use SytxLabs\PayPal\Models\DTO\Subscription\Frequency;
use SytxLabs\PayPal\Models\DTO\Subscription\PaymentPreferences;
use SytxLabs\PayPal\Models\DTO\Subscription\Plan;
use SytxLabs\PayPal\Models\DTO\Subscription\PricingScheme;
use SytxLabs\PayPal\Models\DTO\Subscription\Subscriber;
use SytxLabs\PayPal\Models\DTO\Subscription\Subscription;
use SytxLabs\PayPal\Models\DTO\Subscription\SubscriptionApplicationContext;
use SytxLabs\PayPal\Models\Subscription as SubscriptionModel;
use SytxLabs\PayPal\Services\Traits\PayPalSubscriptionSave;

class PayPalSubscription extends PayPal
{
    use PayPalSubscriptionSave;

    private ?PendingRequest $controller = null;
    private Collection $oneTimeProducts;

    private ?CatalogProduct $catalogProduct = null;
    private ?string $productId = null;
    private ?Plan $plan = null;
    private ?string $planId = null;
    private ?Subscriber $subscriber = null;
    private ?SubscriptionApplicationContext $applicationContext = null;
    private ?string $customId = null;
    private ?string $quantity = null;

    private ?string $payPalRequestId = null;
    private ?Subscription $subscription = null;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->oneTimeProducts = new Collection();
    }

    public function build(): self
    {
        parent::build();
        $this->controller = $this->getClient();
        if ($this->controller === null) {
            throw new RuntimeException('PayPal client not found');
        }
        $this->controller->baseUrl($this->mode->getPayPalEnvironmentURL());
        return $this;
    }

    public function setCatalogProduct(?CatalogProduct $catalogProduct): self
    {
        $this->catalogProduct = $catalogProduct;
        return $this;
    }

    public function setProductId(?string $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    public function setPlan(?Plan $plan): self
    {
        $this->plan = $plan;
        return $this;
    }

    public function setPlanId(?string $planId): self
    {
        $this->planId = $planId;
        return $this;
    }

    /**
     * Convenience: set the recurring price of the plan with a single REGULAR billing cycle.
     */
    public function setRecurringPrice(Money $price, IntervalUnit $intervalUnit, int $intervalCount = 1, int $totalCycles = 0): self
    {
        $this->plan ??= new Plan();
        $this->plan->addBillingCycle(
            (new BillingCycle())
                ->setTenureType(TenureType::REGULAR)
                ->setSequence((count($this->plan->getBillingCycles() ?? [])) + 1)
                ->setTotalCycles($totalCycles)
                ->setFrequency(new Frequency($intervalUnit, $intervalCount))
                ->setPricingScheme((new PricingScheme())->setFixedPrice($price))
        );
        return $this;
    }

    public function addOneTimeProduct(Product $product): self
    {
        $this->oneTimeProducts->push($product);
        return $this;
    }

    public function setSubscriber(?Subscriber $subscriber): self
    {
        $this->subscriber = $subscriber;
        return $this;
    }

    public function setApplicationContext(?SubscriptionApplicationContext $applicationContext): self
    {
        $this->applicationContext = $applicationContext;
        return $this;
    }

    public function setCustomId(?string $customId): self
    {
        $this->customId = $customId;
        return $this;
    }

    public function setQuantity(?string $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    private function getApplicationContext(): SubscriptionApplicationContext
    {
        if ($this->applicationContext === null) {
            $this->applicationContext = new SubscriptionApplicationContext();
            if (function_exists('config') && app()->bound('config')) {
                $this->applicationContext->setBrandName(config('app.name'));
            }
        }
        if (($this->config['success_route'] ?? null) !== null && $this->applicationContext->getReturnUrl() === null) {
            $this->applicationContext->setReturnUrl($this->config['success_route']);
        }
        if (($this->config['cancel_route'] ?? null) !== null && $this->applicationContext->getCancelUrl() === null) {
            $this->applicationContext->setCancelUrl($this->config['cancel_route']);
        }
        return $this->applicationContext;
    }

    /**
     * Sum of the one-time products, charged once as the plan's setup_fee.
     */
    public function getOneTimeTotal(): float
    {
        return (float) $this->oneTimeProducts->sum(static fn (Product $item) => (($item->totalPrice ?? ($item->unitPrice * $item->quantity)) + ($item->tax ?? 0) + ($item->shipping ?? 0)) - (($item->shippingDiscount ?? 0) + ($item->discount ?? 0)));
    }

    private function resolveCurrency(): string
    {
        $cycle = ($this->plan?->getBillingCycles() ?? [])[0] ?? null;
        return $cycle?->getPricingScheme()?->getFixedPrice()?->getCurrencyCode()
            ?? $this->oneTimeProducts->first()?->currencyCode
            ?? $this->currency
            ?? 'USD';
    }

    /**
     * @throws CreateCatalogProductException|RuntimeException|Exception
     */
    public function createCatalogProduct(): self
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        if ($this->catalogProduct === null) {
            throw new RuntimeException('No catalog product set');
        }
        $this->payPalRequestId ??= $this->generateRequestId();
        $apiResponse = $client
            ->withHeader('PayPal-Request-Id', $this->payPalRequestId)
            ->withHeader('Prefer', 'return=representation')
            ->post('v1/catalogs/products', $this->catalogProduct);
        $result = $apiResponse->json();
        if (($result['id'] ?? null) === null || !in_array($apiResponse->getStatusCode(), [200, 201])) {
            $this->log('CreateCatalogProductException: ' . ($apiResponse->getReasonPhrase() ?? 'An error occurred'), [
                'response' => $apiResponse->body(),
                'product' => $this->catalogProduct,
            ]);
            throw new CreateCatalogProductException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred', $apiResponse);
        }
        $this->catalogProduct->setId($result['id']);
        $this->productId = $result['id'];
        return $this;
    }

    /**
     * @throws CreatePlanException|CreateCatalogProductException|RuntimeException|Exception
     */
    public function createPlan(): self
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        if ($this->plan === null || empty($this->plan->getBillingCycles())) {
            throw new RuntimeException('No plan with billing cycles set. Use setPlan() or setRecurringPrice().');
        }
        if ($this->productId === null) {
            $this->createCatalogProduct();
        }
        $this->plan->setProductId($this->productId);
        if ($this->plan->getName() === null) {
            $this->plan->setName($this->catalogProduct?->getName() ?? 'Subscription Plan');
        }

        // One-time payment collected together with the subscription approval as setup_fee.
        $oneTimeTotal = $this->getOneTimeTotal();
        if ($oneTimeTotal > 0) {
            $currency = $this->resolveCurrency();
            $preferences = $this->plan->getPaymentPreferences() ?? new PaymentPreferences();
            if ($preferences->getSetupFee() === null) {
                $preferences->setSetupFee(new Money($currency, number_format($oneTimeTotal, 2, '.', '')));
            }
            $this->plan->setPaymentPreferences($preferences);
        }

        $this->payPalRequestId ??= $this->generateRequestId();
        $apiResponse = $client
            ->withHeader('PayPal-Request-Id', $this->payPalRequestId)
            ->withHeader('Prefer', 'return=representation')
            ->post('v1/billing/plans', $this->plan);
        $result = $apiResponse->json();
        if (($result['id'] ?? null) === null || !in_array($apiResponse->getStatusCode(), [200, 201])) {
            $this->log('CreatePlanException: ' . ($apiResponse->getReasonPhrase() ?? 'An error occurred'), [
                'response' => $apiResponse->body(),
                'plan' => $this->plan,
            ]);
            throw new CreatePlanException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred', $apiResponse);
        }
        $this->plan->setId($result['id']);
        $this->planId = $result['id'];
        return $this;
    }

    /**
     * Orchestrates catalog product -> plan (with setup_fee) -> subscription.
     * Only the final subscription create yields an approve link, so the user
     * approves exactly once for both the one-time payment and the subscription.
     *
     * @throws CreateSubscriptionException|CreatePlanException|CreateCatalogProductException|RuntimeException|Exception
     */
    public function createSubscription(): self
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        if ($this->planId === null) {
            $this->createPlan();
        }
        if ($this->planId === null) {
            throw new RuntimeException('No plan id available for subscription');
        }

        $subscription = (new Subscription())
            ->setPlanId($this->planId)
            ->setSubscriber($this->subscriber)
            ->setApplicationContext($this->getApplicationContext())
            ->setCustomId($this->customId)
            ->setQuantity($this->quantity);

        $this->payPalRequestId ??= $this->generateRequestId();
        $apiResponse = $client
            ->withHeader('PayPal-Request-Id', $this->payPalRequestId)
            ->withHeader('Prefer', 'return=representation')
            ->post('v1/billing/subscriptions', $subscription);
        $result = $apiResponse->json();
        if (($result['id'] ?? null) === null || !in_array($apiResponse->getStatusCode(), [200, 201])) {
            $this->log('CreateSubscriptionException: ' . ($apiResponse->getReasonPhrase() ?? 'An error occurred'), [
                'response' => $apiResponse->body(),
                'request_id' => $this->payPalRequestId,
                'subscription' => $subscription,
            ]);
            throw new CreateSubscriptionException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred', $apiResponse);
        }
        $subscription->setId($result['id']);
        $subscription->setStatus(SubscriptionStatus::tryFrom($result['status'] ?? ''));
        $links = [];
        foreach ($result['links'] ?? [] as $link) {
            $links[] = (new LinkDescription($link['href'], $link['rel']))->setMethod(LinkHTTPMethod::tryFrom($link['method'] ?? ''));
        }
        $subscription->setLinks($links);
        $subscription->setCreateTime($result['create_time'] ?? null);
        $this->subscription = $subscription;
        $this->saveSubscriptionToDatabase($this->subscription, $this->payPalRequestId, $this->productId);
        return $this;
    }

    private function generateRequestId(): string
    {
        // min: 1, max: 100
        return substr(md5(uniqid('paypal', true)), 0, 100);
    }

    public function setSubscription(Subscription|SubscriptionModel $subscription): self
    {
        if ($subscription instanceof SubscriptionModel) {
            $this->payPalRequestId = $subscription->request_id;
            $subscription = $subscription->payPalSubscription;
        }
        $this->subscription = $subscription;
        return $this;
    }

    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function getSubscriptionFromId(string $id): Subscription
    {
        $dbSubscription = $this->loadSubscriptionFromDatabase($id);
        if ($dbSubscription !== null) {
            $this->subscription = $dbSubscription->payPalSubscription;
            $this->payPalRequestId = $dbSubscription->request_id;
            return $this->subscription;
        }
        $this->subscription = (new Subscription())->setId($id);
        return $this->subscription;
    }

    /**
     * @throws Exception
     */
    public function getSubscriptionFromPayPal(?string $id = null): Subscription
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        $id ??= $this->subscription?->getId();
        if ($id === null) {
            throw new RuntimeException('Subscription not found');
        }
        $apiResponse = $client->get('v1/billing/subscriptions/' . $id);
        if (!in_array($apiResponse->getStatusCode(), [200, 201])) {
            $this->log('Failed to get subscription from PayPal', [
                'response' => $apiResponse->body(),
                'subscription_id' => $id,
            ]);
            throw new RuntimeException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred');
        }
        $this->subscription = Subscription::fromArray($apiResponse->json());
        $this->saveSubscriptionToDatabase($this->subscription, $this->payPalRequestId, $this->productId);
        return $this->subscription;
    }

    /**
     * @throws RuntimeException
     */
    public function approveSubscriptionRedirect(): RedirectResponse
    {
        $approveLink = $this->getApproveSubscriptionRoute();
        if (app()->bound(ResponseFactory::class)) {
            return response()->redirectTo($approveLink);
        }
        header('Location: ' . $approveLink);
        exit;
    }

    /**
     * @throws RuntimeException
     */
    public function getApproveSubscriptionRoute(): string
    {
        if ($this->subscription === null) {
            throw new RuntimeException('Subscription not found');
        }
        if ($this->subscription->getLinks() === null) {
            throw new RuntimeException('No links found for subscription');
        }
        $approveLink = array_values(array_filter($this->subscription->getLinks(), static fn (LinkDescription $link) => strtolower($link->getRel()) === 'approve'))[0] ?? null;
        if ($approveLink === null) {
            throw new RuntimeException('No approve link found for subscription');
        }
        return $approveLink->getHref();
    }

    /**
     * @throws Exception
     */
    public function activate(?string $reason = null): self
    {
        return $this->lifecycleAction('activate', $reason);
    }

    /**
     * @throws Exception
     */
    public function suspend(?string $reason = null): self
    {
        return $this->lifecycleAction('suspend', $reason);
    }

    /**
     * @throws Exception
     */
    public function cancel(?string $reason = null): self
    {
        return $this->lifecycleAction('cancel', $reason);
    }

    /**
     * @throws Exception
     */
    private function lifecycleAction(string $action, ?string $reason): self
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        if ($this->subscription === null || $this->subscription->getId() === null) {
            throw new RuntimeException('Subscription not found');
        }
        $apiResponse = $client->post('v1/billing/subscriptions/' . $this->subscription->getId() . '/' . $action, [
            'reason' => $reason ?? ucfirst($action) . ' by merchant',
        ]);
        if (!in_array($apiResponse->getStatusCode(), [200, 201, 204])) {
            $this->log('Failed to ' . $action . ' subscription', [
                'response' => $apiResponse->body(),
                'subscription_id' => $this->subscription->getId(),
            ]);
            throw new RuntimeException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred');
        }
        $this->getSubscriptionFromPayPal();
        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function getSubscriptionStatus(): SubscriptionStatus
    {
        if ($this->subscription === null) {
            throw new RuntimeException('Subscription not found');
        }
        return $this->subscription->getStatus() ?? SubscriptionStatus::APPROVAL_PENDING;
    }

    /**
     * Verify an incoming PayPal webhook signature.
     *
     * @param  array<string,string>  $headers  request headers (case-insensitive)
     *
     * @throws Exception
     */
    public function verifyWebhookSignature(array $headers, array|string $body): bool
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        $webhookId = $this->config['webhook_id'] ?? null;
        if (empty($webhookId)) {
            throw new RuntimeException('PayPal webhook_id is not configured');
        }
        $lower = [];
        foreach ($headers as $key => $value) {
            $lower[strtolower($key)] = is_array($value) ? ($value[0] ?? '') : $value;
        }
        $event = is_string($body) ? json_decode($body, true) : $body;
        $apiResponse = $client->post('v1/notifications/verify-webhook-signature', [
            'auth_algo' => $lower['paypal-auth-algo'] ?? null,
            'cert_url' => $lower['paypal-cert-url'] ?? null,
            'transmission_id' => $lower['paypal-transmission-id'] ?? null,
            'transmission_sig' => $lower['paypal-transmission-sig'] ?? null,
            'transmission_time' => $lower['paypal-transmission-time'] ?? null,
            'webhook_id' => $webhookId,
            'webhook_event' => $event,
        ]);
        if (!in_array($apiResponse->getStatusCode(), [200, 201])) {
            $this->log('Failed to verify webhook signature', ['response' => $apiResponse->body()]);
            return false;
        }
        return ($apiResponse->json()['verification_status'] ?? null) === 'SUCCESS';
    }
}
