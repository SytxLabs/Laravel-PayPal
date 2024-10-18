<?php

namespace SytxLabs\PayPal\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PaypalServerSDKLib\Controllers\OrdersController;
use PaypalServerSDKLib\Models\Builders\AmountBreakdownBuilder;
use PaypalServerSDKLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSDKLib\Models\Builders\ItemBuilder;
use PaypalServerSDKLib\Models\Builders\MoneyBuilder;
use PaypalServerSDKLib\Models\Builders\OrderApplicationContextBuilder;
use PaypalServerSDKLib\Models\Builders\OrderBuilder;
use PaypalServerSDKLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSDKLib\Models\Builders\OrderTrackerRequestBuilder;
use PaypalServerSDKLib\Models\Builders\PayerBuilder;
use PaypalServerSDKLib\Models\Builders\PaymentInstructionBuilder;
use PaypalServerSDKLib\Models\Builders\PaymentSourceBuilder;
use PaypalServerSDKLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSDKLib\Models\CheckoutPaymentIntent;
use PaypalServerSDKLib\Models\LinkDescription;
use PaypalServerSDKLib\Models\Order;
use PaypalServerSDKLib\Models\Payer;
use PaypalServerSDKLib\Models\PaymentInstruction;
use PaypalServerSDKLib\Models\PaymentSource;
use RuntimeException;
use SytxLabs\PayPal\Enums\PayPalOrderCompletionType;
use SytxLabs\PayPal\Models\Order as OrderModel;
use SytxLabs\PayPal\Models\Product;
use SytxLabs\PayPal\Services\Traits\PayPalOrderSave;

class PayPalOrder extends PayPal
{
    use PayPalOrderSave;

    private ?OrdersController $controller = null;
    private string $intent = CheckoutPaymentIntent::CAPTURE;
    private Collection $items;
    private ?PaymentSource $paymentSource = null;
    private ?OrderApplicationContextBuilder $applicationContext = null;
    private ?Payer $payer = null;
    private ?PaymentInstruction $platformInstruction = null;

    private ?string $payPalRequestId = null;
    private ?Order $order = null;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->items = new Collection();
    }

    public function build(): self
    {
        parent::build();
        $this->controller = $this->getClient()?->getOrdersController();
        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function setIntent(string $intent): self
    {
        $intent = strtoupper($intent);
        if (!in_array($intent, [CheckoutPaymentIntent::AUTHORIZE, CheckoutPaymentIntent::CAPTURE], true)) {
            throw new RuntimeException('Invalid intent provided');
        }
        $this->intent = $intent;
        return $this;
    }

    public function addProduct(Product $product): self
    {
        $this->items->push($product);
        return $this;
    }

    public function setPaymentSource(?PaymentSourceBuilder $paymentSource): self
    {
        $this->paymentSource = $paymentSource?->build();
        return $this;
    }

    public function setApplicationContext(?OrderApplicationContextBuilder $applicationContext): self
    {
        $this->applicationContext = $applicationContext;
        return $this;
    }

    public function setPayer(?PayerBuilder $payer): self
    {
        $this->payer = $payer?->build();
        return $this;
    }

    public function setPlatformFee(?PaymentInstructionBuilder $platformInstruction): self
    {
        $this->platformInstruction = $platformInstruction?->build();
        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function createOrder(): self
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        $applicationContext = $this->applicationContext;
        if ($applicationContext === null) {
            $applicationContext = OrderApplicationContextBuilder::init();
            if (function_exists('config') && app()->bound('config')) {
                $applicationContext = $applicationContext
                    ->brandName(config('app.name'))
                    ->landingPage(config('app.url'));
            }
        }
        if ($this->items->count() < 1) {
            throw new RuntimeException('No items added to the order');
        }
        if ($applicationContext->build()->getReturnUrl() === null && ($this->config['success_route'] ?? null) !== null) {
            $applicationContext = $applicationContext->returnUrl($this->config['success_route']);
        }
        if ($applicationContext->build()->getCancelUrl() === null && ($this->config['cancel_route'] ?? null) !== null) {
            $applicationContext = $applicationContext->cancelUrl($this->config['cancel_route']);
        }
        $grouped = $this->items->groupBy(static fn (Product $item) => $item->payee?->getEmailAddress() !== null ? $item->payee->getEmailAddress() . '_' . ($item->payee?->getMerchantId() ?? '') : '');
        if ($grouped->count() > 10) {
            throw new RuntimeException('Maximum of 10 payees allowed per order');
        }
        $purchaseUnits = [];
        foreach ($grouped as $sortedItems) {
            $currencyCode = $this->currency ?? $sortedItems->first()?->currencyCode ?? 'USD';
            $items = [];
            $payee = $sortedItems->first()?->payee;
            foreach ($sortedItems as $item) {
                $items[] = ItemBuilder::init($item->name, MoneyBuilder::init($item->currencyCode ?? $this->currency, $item->unitPrice . '')->build(), $item->quantity . '')
                    ->imageUrl($item->imageUrl)
                    ->sku($item->sku)
                    ->description($item->description)
                    ->category($item->category?->value)
                    ->url($item->url)
                    ->upc($item->upc)
                    ->tax(MoneyBuilder::init($item->currencyCode ?? $this->currency, ($item->tax ?? 0) . '')->build())
                    ->build();
            }
            $purchaseUnits[] = PurchaseUnitRequestBuilder::init(
                AmountWithBreakdownBuilder::init($currencyCode, $sortedItems->sum(static fn (Product $item) => (($item->totalPrice ?? ($item->unitPrice * $item->quantity)) + $item->tax + $item->shipping) - ($item->shippingDiscount + $item->discount)) . '')->breakdown(
                    AmountBreakdownBuilder::init()
                        ->itemTotal(MoneyBuilder::init($currencyCode, $sortedItems->sum(static fn (Product $item) => $item->totalPrice ?? ($item->unitPrice * $item->quantity)) . '')->build())
                        ->taxTotal(MoneyBuilder::init($currencyCode, $sortedItems->sum(static fn (Product $item) => $item->tax) . '')->build())
                        ->shipping(MoneyBuilder::init($currencyCode, $sortedItems->sum(static fn (Product $item) => $item->shipping) . '')->build())
                        ->shippingDiscount(MoneyBuilder::init($currencyCode, $sortedItems->sum(static fn (Product $item) => $item->shippingDiscount) . '')->build())
                        ->discount(MoneyBuilder::init($currencyCode, $sortedItems->sum(static fn (Product $item) => $item->discount) . '')->build())
                    ->build()
                )->build()
            )
                ->payee($payee)
                ->paymentInstruction($this->platformInstruction)
                ->shipping($payee?->shippingDetail?->build())
                ->referenceId($payee?->referenceId ?? Str::random())
                ->items($items)
                ->build();
        }

        $this->payPalRequestId ??= $this->generateRequestId();
        $apiResponse = $client->ordersCreate([
            'body' => OrderRequestBuilder::init($this->intent, $purchaseUnits)
                    ->paymentSource($this->paymentSource)
                    ->applicationContext($applicationContext->build())
                    ->payer($this->payer)
                    ->build(),
            'payPalRequestId' => $this->payPalRequestId,
        ]);
        if ($apiResponse->isError() || !($apiResponse->getResult() instanceof Order)) {
            throw new RuntimeException($apiResponse->getReasonPhrase() ?? 'An error occurred');
        }
        $this->order = $apiResponse->getResult();
        $this->order->setIntent($this->intent);
        $this->saveOrderToDatabase($this->order);
        return $this;
    }

    private function generateRequestId(): string
    {
        return md5(uniqid('paypal', true));
    }

    public function setOrder(Order|OrderModel $order): self
    {
        if ($order instanceof OrderModel) {
            $order = $order->payPalOrder;
        }
        $this->order = $order;
        return $this;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getOrderFormId(string $id): Order
    {
        $order = $this->loadOrderFromDatabase($id) ?? OrderBuilder::init()->id($id)->build();
        $this->order = $order;
        return $order;
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

    public function captureOrder(): self
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        if ($this->order === null) {
            throw new RuntimeException('Order not found');
        }
        if (($this->order->getIntent() ?? $this->intent) !== CheckoutPaymentIntent::AUTHORIZE) {
            $apiResponse = $client->ordersCapture([
                'id' => $this->order->getId(),
                'payPalRequestId' => $this->payPalRequestId,
            ]);
        } else {
            $apiResponse = $client->ordersAuthorize([
                'id' => $this->order->getId(),
                'payPalRequestId' => $this->payPalRequestId,
            ]);
        }
        if ($apiResponse->isError()) {
            throw new RuntimeException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred');
        }
        $this->order = $apiResponse->getResult();
        $this->saveOrderToDatabase($this->order);
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
        return PayPalOrderCompletionType::tryFrom(strtoupper($this->order->getStatus() ?? '')) ?? PayPalOrderCompletionType::UNKNOWN;
    }

    public function createTracking(OrderTrackerRequestBuilder $builder): self
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        if ($this->order === null) {
            throw new RuntimeException('Order not found');
        }
        $apiResponse = $client->ordersTrackCreate([
            'id' => $this->order->getId(),
            'body' => $builder->build(),
        ]);
        if ($apiResponse->isError()) {
            throw new RuntimeException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred');
        }
        $this->order = $apiResponse->getResult();
        $this->saveOrderToDatabase($this->order);
        return $this;
    }
}
