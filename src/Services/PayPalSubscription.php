<?php

/** @noinspection PhpUnused */

namespace SytxLabs\PayPal\Services;

use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Carbon;
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
use SytxLabs\PayPal\Models\DTO\Subscription\CatalogProduct;
use SytxLabs\PayPal\Models\DTO\Subscription\PaymentPreferences;
use SytxLabs\PayPal\Models\DTO\Subscription\Plan;
use SytxLabs\PayPal\Models\DTO\Subscription\RecurringPrice;
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
    private ?Model $subscribable = null;

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

    public function getProductId(): ?string
    {
        return $this->productId;
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

    public function getPlanId(): ?string
    {
        return $this->planId;
    }

    /**
     * Convenience: append a recurring price to the plan as a billing cycle.
     * Repeated calls add further cycles (e.g., TRIAL then REGULAR, or tiered pricing).
     */
    public function setRecurringPrice(Money $price, IntervalUnit $intervalUnit, int $intervalCount = 1, int $totalCycles = 0, TenureType $tenureType = TenureType::REGULAR): self
    {
        $this->plan ??= new Plan();
        $this->plan->setBillingCycles(null);
        return $this->addRecurringPrice(new RecurringPrice($price, $intervalUnit, $intervalCount, $totalCycles, $tenureType));
    }

    /**
     * Append a single recurring price (billing cycle) to the plan.
     * The billing-cycle sequence is assigned automatically in insertion order.
     */
    public function addRecurringPrice(RecurringPrice $recurringPrice): self
    {
        $this->plan ??= new Plan();
        $this->plan->addBillingCycle($recurringPrice->toBillingCycle((count($this->plan->getBillingCycles() ?? [])) + 1));
        return $this;
    }

    /**
     * Append several recurring prices (billing cycles) to the plan in order.
     *
     * @param  RecurringPrice[]  $recurringPrices
     */
    public function addRecurringPrices(array $recurringPrices): self
    {
        foreach ($recurringPrices as $recurringPrice) {
            $this->addRecurringPrice($recurringPrice);
        }
        return $this;
    }

    public function setOneTimeProduct(Product $product): self
    {
        $this->oneTimeProducts = collect([$product]);
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

    /**
     * Link the stored subscription to a model (e.g. the subscribing user) via the `subscribable` morph.
     */
    public function setSubscribable(?Model $subscribable): self
    {
        $this->subscribable = $subscribable;
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
        return (($this->plan?->getBillingCycles() ?? [])[0] ?? null)?->getPricingScheme()?->getFixedPrice()?->getCurrencyCode() ?? $this->oneTimeProducts->first()?->currencyCode ?? $this->currency ?? 'USD';
    }

    /**
     * @throws RuntimeException|Exception
     */
    private function client(): PendingRequest
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        return clone $client;
    }

    /**
     * Log and throw when PayPal did not answer with one of the expected status codes.
     *
     * @param  int[]  $expected
     *
     * @throws RuntimeException
     */
    private function ensureSuccessful(Response $apiResponse, string $message, array $context = [], array $expected = [200, 201, 204]): Response
    {
        if (!in_array($apiResponse->getStatusCode(), $expected, true)) {
            $this->log($message, ['response' => $apiResponse->body()] + $context);
            throw new RuntimeException($message . ': ' . ($apiResponse->getReasonPhrase() ?: 'An error occurred'));
        }
        return $apiResponse;
    }

    /**
     * @throws RuntimeException
     */
    private function requireSubscriptionId(): string
    {
        $id = $this->subscription?->getId();
        if ($id === null) {
            throw new RuntimeException('Subscription not found');
        }
        return $id;
    }

    /**
     * @throws RuntimeException
     */
    private function requirePlanId(?string $planId): string
    {
        $planId ??= $this->planId ?? $this->subscription?->getPlanId();
        if ($planId === null) {
            throw new RuntimeException('No plan id available');
        }
        return $planId;
    }

    /**
     * @return LinkDescription[]
     */
    private static function parseLinks(array $links): array
    {
        return array_map(static fn (array $link) => (new LinkDescription($link['href'], $link['rel']))->setMethod(LinkHTTPMethod::tryFrom($link['method'] ?? '')), $links);
    }

    /**
     * @throws CreateCatalogProductException|RuntimeException|Exception
     */
    public function createCatalogProduct(): self
    {
        $client = $this->client();
        if ($this->catalogProduct === null) {
            throw new RuntimeException('No catalog product set');
        }
        $apiResponse = $client->withHeader('PayPal-Request-Id', $this->generateRequestId())->withHeader('Prefer', 'return=representation')->post('v1/catalogs/products', $this->catalogProduct);
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
        $client = $this->client();
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
            $preferences = $this->plan->getPaymentPreferences() ?? new PaymentPreferences();
            if ($preferences->getSetupFee() === null) {
                $preferences->setSetupFee(new Money($this->resolveCurrency(), number_format($oneTimeTotal, 2, '.', '')));
            }
            $this->plan->setPaymentPreferences($preferences);
        }

        $apiResponse = $client->withHeader('PayPal-Request-Id', $this->generateRequestId())->withHeader('Prefer', 'return=representation')->post('v1/billing/plans', $this->plan);
        $result = $apiResponse->json();
        if (($result['id'] ?? null) === null || !in_array($apiResponse->getStatusCode(), [200, 201])) {
            $this->log('CreatePlanException: ' . ($apiResponse->getReasonPhrase() ?? 'An error occurred'), ['response' => $apiResponse->body(), 'plan' => $this->plan]);
            throw new CreatePlanException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred', $apiResponse);
        }
        $this->plan->setId($result['id']);
        $this->planId = $result['id'];
        return $this;
    }

    /**
     * Orchestrates catalog product -> plan (with setup_fee) -> subscription.
     * Only the final subscription creation yields an approval link, so the user
     * approves exactly once for both the one-time payment and the subscription.
     *
     * @throws CreateSubscriptionException|CreatePlanException|CreateCatalogProductException|RuntimeException|Exception
     */
    public function createSubscription(): self
    {
        $client = $this->client();
        if ($this->planId === null) {
            $this->createPlan();
        }
        if ($this->planId === null) {
            throw new RuntimeException('No plan id available for subscription');
        }
        $subscription = (new Subscription())->setPlanId($this->planId)->setSubscriber($this->subscriber)->setApplicationContext($this->getApplicationContext())->setCustomId($this->customId)->setQuantity($this->quantity);
        $this->payPalRequestId = $this->generateRequestId();
        $apiResponse = $client->withHeader('PayPal-Request-Id', $this->payPalRequestId)->withHeader('Prefer', 'return=representation')->post('v1/billing/subscriptions', $subscription);
        $result = $apiResponse->json();
        if (($result['id'] ?? null) === null || !in_array($apiResponse->getStatusCode(), [200, 201])) {
            $this->log('CreateSubscriptionException: ' . ($apiResponse->getReasonPhrase() ?? 'An error occurred'), [
                'response' => $apiResponse->body(),
                'request_id' => $this->payPalRequestId,
                'subscription' => $subscription,
            ]);
            throw new CreateSubscriptionException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred', $apiResponse);
        }
        $subscription->setId($result['id'])->setStatus(SubscriptionStatus::tryFrom($result['status'] ?? ''));
        $this->subscription = $subscription->setLinks(self::parseLinks($result['links'] ?? []))->setCreateTime($result['create_time'] ?? null);
        $this->saveSubscriptionToDatabase($this->subscription, $this->payPalRequestId, $this->productId, $this->subscribable);
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
            $this->productId ??= $subscription->product_id;
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
            $this->setSubscription($dbSubscription);
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
        $client = $this->client();
        $id ??= $this->subscription?->getId();
        if ($id === null) {
            throw new RuntimeException('Subscription not found');
        }
        $apiResponse = $this->ensureSuccessful($client->get('v1/billing/subscriptions/' . $id), 'Failed to get subscription from PayPal', ['subscription_id' => $id], [200]);
        $this->subscription = Subscription::fromArray($apiResponse->json());
        $this->saveSubscriptionToDatabase($this->subscription, $this->payPalRequestId, $this->productId, $this->subscribable);
        return $this->subscription;
    }

    /**
     * Handle the buyer returning from PayPal to the success_route after approving.
     * PayPal appends `subscription_id` (and `ba_token`, `token`) to the return URL; the current
     * subscription state is fetched from PayPal and persisted.
     *
     * @throws Exception
     */
    public function handleApprovalReturn(array|Request|null $request = null): Subscription
    {
        if ($request === null && function_exists('request') && app()->bound('request')) {
            $request = request();
        }
        $subscriptionId = $request instanceof Request ? $request->query('subscription_id', $request->input('subscription_id')) : ($request['subscription_id'] ?? null);
        if (!is_string($subscriptionId) || $subscriptionId === '') {
            throw new RuntimeException('No subscription_id in approval return request');
        }
        $this->getSubscriptionFromId($subscriptionId);
        return $this->getSubscriptionFromPayPal($subscriptionId);
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
        $client = $this->client();
        $id = $this->requireSubscriptionId();
        $this->ensureSuccessful(
            $client->post('v1/billing/subscriptions/' . $id . '/' . $action, ['reason' => $reason ?? ucfirst($action) . ' by merchant']),
            'Failed to ' . $action . ' subscription',
            ['subscription_id' => $id],
        );
        $this->getSubscriptionFromPayPal();
        return $this;
    }

    /**
     * Change the plan and/or quantity of the subscription.
     * If PayPal requires the buyer to consent, the new approve link is available via getApproveSubscriptionRoute().
     * The stored plan id is only updated once PayPal reports the change (BILLING.SUBSCRIPTION.UPDATED webhook or a refresh).
     *
     * @return bool true when the buyer has to approve the change
     *
     * @throws Exception
     */
    public function revise(?string $planId = null, ?string $quantity = null, ?Money $shippingAmount = null): bool
    {
        $client = $this->client();
        $id = $this->requireSubscriptionId();
        if ($planId === null && $quantity === null && $shippingAmount === null) {
            throw new RuntimeException('Nothing to revise: pass a plan id, quantity or shipping amount');
        }
        $body = array_filter([
            'plan_id' => $planId,
            'quantity' => $quantity,
            'shipping_amount' => $shippingAmount,
            'application_context' => $this->getApplicationContext(),
        ], static fn ($value) => $value !== null);
        $apiResponse = $this->ensureSuccessful($client->post('v1/billing/subscriptions/' . $id . '/revise', $body), 'Failed to revise subscription', ['subscription_id' => $id], [200]);
        $links = self::parseLinks($apiResponse->json('links') ?? []);
        $needsApproval = array_filter($links, static fn (LinkDescription $link) => strtolower($link->getRel()) === 'approve') !== [];
        if ($needsApproval) {
            $this->subscription->setLinks($links);
            $this->saveSubscriptionToDatabase($this->subscription);
        }
        return $needsApproval;
    }

    /**
     * Capture the outstanding balance (e.g. after failed payments) of the subscription.
     *
     * @return array<string,mixed> the resulting PayPal transaction
     *
     * @throws Exception
     */
    public function captureOutstandingBalance(Money $amount, string $note = 'Outstanding balance'): array
    {
        $client = $this->client();
        $id = $this->requireSubscriptionId();
        $apiResponse = $this->ensureSuccessful($client->withHeader('PayPal-Request-Id', $this->generateRequestId())->post('v1/billing/subscriptions/' . $id . '/capture', [
            'note' => $note,
            'capture_type' => 'OUTSTANDING_BALANCE',
            'amount' => $amount,
        ]), 'Failed to capture outstanding balance', ['subscription_id' => $id], [200, 201, 202]);
        return $apiResponse->json() ?? [];
    }

    /**
     * List the payments of the subscription within a time range.
     *
     * @return array<int,array<string,mixed>>
     *
     * @throws Exception
     */
    public function listTransactions(DateTimeInterface|string $startTime, DateTimeInterface|string|null $endTime = null): array
    {
        $client = $this->client();
        $id = $this->requireSubscriptionId();
        $format = static fn (DateTimeInterface|string $time) => $time instanceof DateTimeInterface ? Carbon::instance($time)->utc()->format('Y-m-d\TH:i:s\Z') : $time;
        $apiResponse = $this->ensureSuccessful($client->get('v1/billing/subscriptions/' . $id . '/transactions', [
            'start_time' => $format($startTime),
            'end_time' => $format($endTime ?? Carbon::now()),
        ]), 'Failed to list subscription transactions', ['subscription_id' => $id], [200]);
        return $apiResponse->json('transactions') ?? [];
    }

    /**
     * @throws Exception
     */
    public function getPlanFromPayPal(?string $planId = null): Plan
    {
        $client = $this->client();
        $planId = $this->requirePlanId($planId);
        $apiResponse = $this->ensureSuccessful($client->get('v1/billing/plans/' . $planId), 'Failed to get plan from PayPal', ['plan_id' => $planId], [200]);
        $this->plan = Plan::fromArray($apiResponse->json());
        $this->planId = $this->plan->getId();
        return $this->plan;
    }

    /**
     * @return Plan[]
     *
     * @throws Exception
     */
    public function listPlans(?string $productId = null, int $page = 1, int $pageSize = 20): array
    {
        $client = $this->client();
        $query = array_filter(['product_id' => $productId ?? $this->productId, 'page' => $page, 'page_size' => $pageSize, 'total_required' => 'true'], static fn ($value) => $value !== null);
        $apiResponse = $this->ensureSuccessful($client->get('v1/billing/plans', $query), 'Failed to list plans', $query, [200]);
        return array_map(static fn (array $plan) => Plan::fromArray($plan), $apiResponse->json('plans') ?? []);
    }

    /**
     * @throws Exception
     */
    public function activatePlan(?string $planId = null): self
    {
        $planId = $this->requirePlanId($planId);
        $this->ensureSuccessful($this->client()->post('v1/billing/plans/' . $planId . '/activate'), 'Failed to activate plan', ['plan_id' => $planId]);
        return $this;
    }

    /**
     * New subscriptions can no longer use a deactivated plan; existing ones keep running.
     *
     * @throws Exception
     */
    public function deactivatePlan(?string $planId = null): self
    {
        $planId = $this->requirePlanId($planId);
        $this->ensureSuccessful($this->client()->post('v1/billing/plans/' . $planId . '/deactivate'), 'Failed to deactivate plan', ['plan_id' => $planId]);
        return $this;
    }

    /**
     * Change the price of billing cycles. Keys are the billing-cycle sequence (1-based).
     *
     * @param  array<int,Money>  $pricesBySequence
     *
     * @throws Exception
     */
    public function updatePlanPricing(array $pricesBySequence, ?string $planId = null): self
    {
        $planId = $this->requirePlanId($planId);
        if ($pricesBySequence === []) {
            throw new RuntimeException('No prices to update');
        }
        $schemes = [];
        foreach ($pricesBySequence as $sequence => $price) {
            $schemes[] = ['billing_cycle_sequence' => (int) $sequence, 'pricing_scheme' => ['fixed_price' => $price]];
        }
        $this->ensureSuccessful($this->client()->post('v1/billing/plans/' . $planId . '/update-pricing-schemes', ['pricing_schemes' => $schemes]), 'Failed to update plan pricing', ['plan_id' => $planId]);
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
        $client = $this->client();
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
