<?php

/** @noinspection PhpUnused */

namespace SytxLabs\PayPal\Services;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use SytxLabs\PayPal\Enums\DTO\LinkHTTPMethod;
use SytxLabs\PayPal\Enums\PayPalCheckoutPaymentIntent;
use SytxLabs\PayPal\Enums\PayPalOrderCompletionType;
use SytxLabs\PayPal\Exception\CaptureOrderException;
use SytxLabs\PayPal\Exception\CreateOrderException;
use SytxLabs\PayPal\Models\DTO\ApplicationContext;
use SytxLabs\PayPal\Models\DTO\ConfirmOrder;
use SytxLabs\PayPal\Models\DTO\LinkDescription;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Order;
use SytxLabs\PayPal\Models\DTO\Order\PaymentInstruction;
use SytxLabs\PayPal\Models\DTO\Payer;
use SytxLabs\PayPal\Models\DTO\PaymentSource;
use SytxLabs\PayPal\Models\DTO\Product;
use SytxLabs\PayPal\Models\DTO\Shipping\OrderTrackerRequest;
use SytxLabs\PayPal\Models\Order as OrderModel;
use SytxLabs\PayPal\Services\Traits\PayPalOrderSave;

class PayPalOrder extends PayPal
{
    use PayPalOrderSave;

    private ?PendingRequest $controller = null;
    private PayPalCheckoutPaymentIntent $intent;
    private Collection $items;
    private ?PaymentSource $paymentSource = null;
    private ?ApplicationContext $applicationContext = null;
    private ?Payer $payer = null;
    private ?PaymentInstruction $platformInstruction = null;

    private ?string $payPalRequestId = null;
    private ?Order $order = null;

    public function __construct(array $config = [])
    {
        $this->intent = PayPalCheckoutPaymentIntent::CAPTURE;
        parent::__construct($config);
        $this->items = new Collection();
    }

    public function build(): self
    {
        parent::build();
        $this->controller = $this->getClient();
        if ($this->controller === null) {
            throw new RuntimeException('PayPal client not found');
        }
        $this->controller->baseUrl($this->mode->getPayPalEnvironmentURL() . '/v2/checkout/orders');
        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function setIntent(PayPalCheckoutPaymentIntent $intent): self
    {
        $this->intent = $intent;
        return $this;
    }

    public function addProduct(Product $product): self
    {
        $this->items->push($product);
        return $this;
    }

    public function setPaymentSource(?PaymentSource $paymentSource): self
    {
        $this->paymentSource = $paymentSource;
        return $this;
    }

    public function setApplicationContext(?ApplicationContext $applicationContext): self
    {
        $this->applicationContext = $applicationContext;
        return $this;
    }

    private function getApplicationContext(): ApplicationContext
    {
        if ($this->applicationContext === null) {
            $this->applicationContext = new ApplicationContext();
            if (function_exists('config') && app()->bound('config')) {
                $this->applicationContext = $this->applicationContext
                    ->setBrandName(config('app.name'))
                    ->setLandingPage(config('app.url'));
            }
        }
        if (($this->config['success_route'] ?? null) !== null && $this->applicationContext->getReturnUrl() === null) {
            $this->applicationContext = $this->applicationContext->setReturnUrl($this->config['success_route']);
        }
        if (($this->config['cancel_route'] ?? null) !== null && $this->applicationContext->getCancelUrl() === null) {
            $this->applicationContext = $this->applicationContext->setCancelUrl($this->config['cancel_route']);
        }
        return $this->applicationContext;
    }

    public function setPayer(?Payer $payer): self
    {
        $this->payer = $payer;
        return $this;
    }

    public function setPlatformFee(?PaymentInstruction $platformInstruction): self
    {
        $this->platformInstruction = $platformInstruction;
        return $this;
    }

    public function getGroupedProducts(): Collection
    {
        return $this->items->groupBy(
            static fn (Product $item) => $item->payee?->getEmailAddress() !== null ? $item->payee->getEmailAddress() . '_' . ($item->payee?->getMerchantId() ?? '') : ''
        );
    }

    /**
     * @throws RuntimeException|CreateOrderException|Exception
     */
    public function createOrder(): self
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        if ($this->items->count() < 1) {
            throw new RuntimeException('No items added to the order');
        }
        $applicationContext = $this->getApplicationContext();
        $grouped = $this->getGroupedProducts();
        if ($grouped->count() > 10) {
            throw new RuntimeException('Maximum of 10 payees allowed per order');
        }
        $purchaseUnits = [];
        foreach ($grouped as $sortedItems) {
            $currencyCode = $this->currency ?? $sortedItems->first()?->currencyCode ?? 'USD';
            $items = [];
            $payee = $sortedItems->first()?->payee;
            foreach ($sortedItems as $item) {
                $code = $item->currencyCode ?? $this->currency ?? 'USD';
                $items[] = (new Order\Item($item->name, new Money($code, $item->unitPrice . ''), $item->quantity . ''))
                    ->setImageUrl($item->imageUrl)
                    ->setSku($item->sku)
                    ->setDescription($item->description)
                    ->setCategory($item->category)
                    ->setUrl($item->url)
                    ->setUpc($item->upc)
                    ->setTax(new Money($code, ($item->tax ?? 0) . ''));
            }
            $purchaseUnits[] = (new Order\PurchaseUnit())->setAmount(
                (new Order\AmountWithBreakdown($currencyCode, $sortedItems->sum(static fn (Product $item) => (($item->totalPrice ?? ($item->unitPrice * $item->quantity)) + $item->tax + $item->shipping) - ($item->shippingDiscount + $item->discount)) . ''))->setBreakdown(
                    (new Order\AmountBreakdown())
                        ->setItemTotal(new Money($currencyCode, $sortedItems->sum(static fn (Product $item) => $item->totalPrice ?? ($item->unitPrice * $item->quantity)) . ''))
                        ->setTaxTotal(new Money($currencyCode, ($sortedItems->sum(static fn (Product $item) => $item->tax)) . ''))
                        ->setShipping(new Money($currencyCode, ($sortedItems->sum(static fn (Product $item) => $item->shipping)) . ''))
                        ->setShippingDiscount(new Money($currencyCode, ($sortedItems->sum(static fn (Product $item) => $item->shippingDiscount)) . ''))
                        ->setDiscount(new Money($currencyCode, $sortedItems->sum(static fn (Product $item) => $item->discount) . ''))
                )
            )->setPayee($payee)
                ->setPaymentInstruction($this->platformInstruction)
                ->setShipping($payee?->getShippingDetail()?->build())
                ->setReferenceId($payee?->referenceId ?? Str::random())
                ->setItems($items);
        }

        $this->payPalRequestId ??= $this->generateRequestId();
        $order = (new Order())->setIntent($this->intent)
            ->setPurchaseUnits($purchaseUnits)
            ->setPaymentSource($this->paymentSource)
            ->setApplicationContext($applicationContext)
            ->setPayer($this->payer);
        $apiResponse = $client
            ->withHeader('PayPal-Request-Id', $this->payPalRequestId)
            ->withHeader('Prefer', 'return=representation')
            ->post('', $order);
        $result = $apiResponse->json();
        if (($result['id'] ?? null) === null || !in_array($apiResponse->getStatusCode(), [200, 201])) {
            throw new CreateOrderException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred', $apiResponse);
        }
        $order->setId($result['id']);
        $order->setStatus(PayPalOrderCompletionType::tryFrom($result['status']));
        $links = [];
        foreach ($result['links'] as $link) {
            $links[] = (new LinkDescription($link['href'], $link['rel']))->setMethod(LinkHTTPMethod::tryFrom($link['method'] ?? ''));
        }
        $order->setLinks($links);
        $order->setCreateTime($result['create_time']);
        $this->order = $order;
        $this->saveOrderToDatabase($this->order, $this->payPalRequestId);
        return $this;
    }

    private function generateRequestId(): string
    {
        // min: 1, max: 100
        return substr(md5(uniqid('paypal', true)), 0, 100);
    }

    public function setOrder(Order|OrderModel $order): self
    {
        if ($order instanceof OrderModel) {
            $this->payPalRequestId = $order->request_id;
            $order = $order->payPalOrder;
        }
        $this->order = $order;
        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function getOrderFormId(string $id): Order
    {
        $dbOrder = $this->loadOrderFromDatabase($id);
        if ($dbOrder !== null) {
            $this->order = $dbOrder->payPalOrder;
            $this->payPalRequestId = $dbOrder->request_id;
            return $this->order;
        }
        $this->order = (new Order())->setId($id);
        return $this->order;
    }

    /**
     * @throws Exception
     */
    public function getOrderFromPayPal(?string $id = null): Order
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        $apiResponse = $client->get($id ?? $this->order->getId());
        if (!in_array($apiResponse->getStatusCode(), [200, 201])) {
            throw new RuntimeException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred');
        }
        $this->order = Order::fromArray($apiResponse->json());
        $this->saveOrderToDatabase($this->order, $this->payPalRequestId);
        return $this->order;
    }

    /**
     * @throws RuntimeException
     */
    public function approveOrderRedirect(): RedirectResponse
    {
        $approveLink = $this->getApproveOrderRoute();
        if (app()->bound(ResponseFactory::class)) {
            return response()->redirectTo($approveLink);
        }
        header('Location: ' . $approveLink);
        exit;
    }

    /**
     * @throws RuntimeException
     */
    public function getApproveOrderRoute(): string
    {
        if ($this->order === null) {
            throw new RuntimeException('Order not found');
        }
        if ($this->order->getLinks() === null) {
            throw new RuntimeException('No links found for order');
        }
        $approveLink = array_values(array_filter($this->order->getLinks(), static fn (LinkDescription $link) => strtolower($link->getRel()) === 'approve'))[0] ?? null;
        if ($approveLink === null) {
            throw new RuntimeException('No approve link found for order');
        }
        return $approveLink->getHref();
    }

    /**
     * @throws Exception
     */
    public function confirmOrder(): self
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        if ($this->order === null) {
            throw new RuntimeException('Order not found');
        }
        $paymentSourceResponse = $this->order->getPaymentSource();
        if ($paymentSourceResponse === null) {
            throw new RuntimeException('Payment source not found');
        }
        $apiResponse = $client
            ->withHeader('PayPal-Request-Id', $this->payPalRequestId)
            ->withHeader('Prefer', 'return=representation')
            ->post($this->order->getId() . '/confirm-payment-source', [
                'id' => $this->order->getId(),
                'body' => (new ConfirmOrder($paymentSourceResponse))->setProcessingInstruction($this->order->getProcessingInstruction()),
            ]);
        if (!in_array($apiResponse->getStatusCode(), [200, 201])) {
            throw new RuntimeException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred');
        }
        $this->order = Order::fromArray($apiResponse->json());
        $this->saveOrderToDatabase($this->order, $this->payPalRequestId);
        return $this;
    }

    /**
     * @throws CaptureOrderException|RuntimeException
     * @throws Exception
     */
    public function captureOrder(): self
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        if ($this->order === null) {
            throw new RuntimeException('Order not found');
        }
        $this->getOrderFromPayPal();
        if ($this->order->getStatus() === PayPalOrderCompletionType::COMPLETED) {
            return $this;
        }
        $client = $client->withHeader('Prefer', 'return=minimal');

        $data = [
            'id' => $this->order->getId(),
            'body' => $this->order->getPaymentSource(),
        ];

        $apiResponse = ($this->order->getIntent() ?? $this->intent)->value !== PayPalCheckoutPaymentIntent::AUTHORIZE->value ?
            $client->post($this->order->getId() . '/capture', $data) :
            $client->post($this->order->getId() . '/authorize', $data);
        if (!in_array($apiResponse->getStatusCode(), [200, 201])) {
            $this->log('CaptureOrderException: ' . $apiResponse->getReasonPhrase() ?? 'An error occurred', [
                'response' => $apiResponse->body(),
                'request_id' => $this->payPalRequestId,
                'order' => $this->order,
                'intent' => $this->intent,
            ]);
            throw new CaptureOrderException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred', $apiResponse);
        }
        $this->order = Order::fromArray($apiResponse->json());
        $this->saveOrderToDatabase($this->order, $this->payPalRequestId);
        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function getOrderStatus(): PayPalOrderCompletionType
    {
        if ($this->order === null) {
            throw new RuntimeException('Order not found');
        }
        return $this->order->getStatus() ?? PayPalOrderCompletionType::UNKNOWN;
    }

    /**
     * @throws Exception
     */
    public function createTracking(OrderTrackerRequest $builder): self
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        if ($this->order === null) {
            throw new RuntimeException('Order not found');
        }
        $apiResponse = $client->withHeader('PayPal-Request-Id', $this->payPalRequestId)
            ->withHeader('Prefer', 'return=representation')
            ->post($this->order->getId() . '/track', $builder);
        if (!in_array($apiResponse->getStatusCode(), [200, 201])) {
            throw new RuntimeException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred');
        }
        $this->order = Order::fromArray($apiResponse->json());
        $this->saveOrderToDatabase($this->order, $this->payPalRequestId);
        return $this;
    }
}
