<?php

/** @noinspection PhpUnused */

namespace SytxLabs\PayPal\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PaypalServerSdkLib\Controllers\OrdersController;
use PaypalServerSdkLib\Models\Builders\AmountBreakdownBuilder;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSdkLib\Models\Builders\ConfirmOrderRequestBuilder;
use PaypalServerSdkLib\Models\Builders\ItemBuilder;
use PaypalServerSdkLib\Models\Builders\MoneyBuilder;
use PaypalServerSdkLib\Models\Builders\OrderApplicationContextBuilder;
use PaypalServerSdkLib\Models\Builders\OrderAuthorizeRequestBuilder;
use PaypalServerSdkLib\Models\Builders\OrderBuilder;
use PaypalServerSdkLib\Models\Builders\OrderCaptureRequestBuilder;
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\Builders\OrderTrackerRequestBuilder;
use PaypalServerSdkLib\Models\Builders\PayerBuilder;
use PaypalServerSdkLib\Models\Builders\PaymentInstructionBuilder;
use PaypalServerSdkLib\Models\Builders\PaymentSourceBuilder;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\LinkDescription;
use PaypalServerSdkLib\Models\Order;
use PaypalServerSdkLib\Models\OrderCaptureRequest;
use PaypalServerSdkLib\Models\Payer;
use PaypalServerSdkLib\Models\PaymentInstruction;
use PaypalServerSdkLib\Models\PaymentSource;
use RuntimeException;
use SytxLabs\PayPal\Enums\PayPalCheckoutPaymentIntent;
use SytxLabs\PayPal\Enums\PayPalOrderCompletionType;
use SytxLabs\PayPal\Exception\CaptureOrderException;
use SytxLabs\PayPal\Exception\CreateOrderException;
use SytxLabs\PayPal\Models\Order as OrderModel;
use SytxLabs\PayPal\Models\Product;
use SytxLabs\PayPal\Services\Traits\PayPalOrderSave;

class PayPalOrder extends PayPal
{
    use PayPalOrderSave;

    private ?OrdersController $controller = null;
    private PayPalCheckoutPaymentIntent $intent;
    private Collection $items;
    private ?PaymentSource $paymentSource = null;
    private ?OrderApplicationContextBuilder $applicationContext = null;
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
        $this->controller = $this->getClient()?->getOrdersController();
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

    private function getApplicationContext(): OrderApplicationContextBuilder
    {
        if ($this->applicationContext === null) {
            $this->applicationContext = OrderApplicationContextBuilder::init();
            if (function_exists('config') && app()->bound('config')) {
                $this->applicationContext = $this->applicationContext
                    ->brandName(config('app.name'))
                    ->landingPage(config('app.url'));
            };
        }
        return $this->applicationContext;
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

    public function getGroupedProducts(): Collection
    {
        return $this->items->groupBy(
            static fn (Product $item) => $item->payee?->getEmailAddress() !== null ? $item->payee->getEmailAddress() . '_' . ($item->payee?->getMerchantId() ?? '') : ''
        );
    }

    /**
     * @throws RuntimeException|CreateOrderException
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
        if (($this->config['success_route'] ?? null) !== null && $applicationContext->build()->getReturnUrl() === null) {
            $applicationContext = $applicationContext->returnUrl($this->config['success_route']);
        }
        if (($this->config['cancel_route'] ?? null) !== null && $applicationContext->build()->getCancelUrl() === null) {
            $applicationContext = $applicationContext->cancelUrl($this->config['cancel_route']);
        }
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
                $items[] = ItemBuilder::init($item->name, MoneyBuilder::init($code, $item->unitPrice . '')->build(), $item->quantity . '')
                    ->imageUrl($item->imageUrl)
                    ->sku($item->sku)
                    ->description($item->description)
                    ->category($item->category?->value)
                    ->url($item->url)
                    ->upc($item->upc)
                    ->tax(MoneyBuilder::init($code, ($item->tax ?? 0) . '')->build())
                    ->build();
            }
            $purchaseUnits[] = PurchaseUnitRequestBuilder::init(
                AmountWithBreakdownBuilder::init($currencyCode, $sortedItems->sum(static fn (Product $item) => (($item->totalPrice ?? ($item->unitPrice * $item->quantity)) + $item->tax + $item->shipping) - ($item->shippingDiscount + $item->discount)) . '')->breakdown(
                    AmountBreakdownBuilder::init()
                        ->itemTotal(MoneyBuilder::init($currencyCode, $sortedItems->sum(static fn (Product $item) => $item->totalPrice ?? ($item->unitPrice * $item->quantity)) . '')->build())
                        ->taxTotal(MoneyBuilder::init($currencyCode, ($sortedItems->sum(static fn (Product $item) => $item->tax)) . '')->build())
                        ->shipping(MoneyBuilder::init($currencyCode, ($sortedItems->sum(static fn (Product $item) => $item->shipping)) . '')->build())
                        ->shippingDiscount(MoneyBuilder::init($currencyCode, ($sortedItems->sum(static fn (Product $item) => $item->shippingDiscount)) . '')->build())
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
            'body' => OrderRequestBuilder::init($this->intent->value, $purchaseUnits)
                    ->paymentSource($this->paymentSource)
                    ->applicationContext($applicationContext->build())
                    ->payer($this->payer)
                    ->build(),
            'payPalRequestId' => $this->payPalRequestId,
        ]);
        if ($apiResponse->isError() || !($apiResponse->getResult() instanceof Order)) {
            throw new CreateOrderException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred', $apiResponse);
        }
        $this->order = $apiResponse->getResult();
        $this->order->setIntent($this->intent->value);
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
        $this->order = OrderBuilder::init()->id($id)->build();
        return $this->order;
    }

    public function getOrderFromPayPal(?string $id = null): Order
    {
        $client = $this->controller ?? $this->build()->controller;
        if ($client === null) {
            throw new RuntimeException('PayPal client not found');
        }
        $apiResponse = $client->ordersGet([
            'id' => $id ?? $this->order->getId(),
        ]);
        if ($apiResponse->isError() || !($apiResponse->getResult() instanceof Order)) {
            throw new RuntimeException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred');
        }
        $this->order = $apiResponse->getResult();
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
        $paymentSource = PaymentSourceBuilder::init()
            ->card($paymentSourceResponse->getCard())
            ->paypal($paymentSourceResponse->getPaypal())
            ->bancontact($paymentSourceResponse->getBancontact())
            ->blik($paymentSourceResponse->getBlik())
            ->eps($paymentSourceResponse->getEps())
            ->giropay($paymentSourceResponse->getGiropay())
            ->ideal($paymentSourceResponse->getIdeal())
            ->mybank($paymentSourceResponse->getMybank())
            ->p24($paymentSourceResponse->getP24())
            ->sofort($paymentSourceResponse->getSofort())
            ->trustly($paymentSourceResponse->getTrustly())
            ->applePay($paymentSourceResponse->getApplePay())
            ->googlePay($paymentSourceResponse->getGooglePay())
            ->venmo($paymentSourceResponse->getVenmo())
            ->build();
        $applicationContext = $this->getApplicationContext();
        if (($this->config['success_route'] ?? null) !== null && $applicationContext->build()->getReturnUrl() === null) {
            $applicationContext = $applicationContext->returnUrl($this->config['success_route']);
        }
        if (($this->config['cancel_route'] ?? null) !== null && $applicationContext->build()->getCancelUrl() === null) {
            $applicationContext = $applicationContext->cancelUrl($this->config['cancel_route']);
        }
        $apiResponse = $client->ordersConfirm([
            'id' => $this->order->getId(),
            'body' => ConfirmOrderRequestBuilder::init($paymentSource)
                ->applicationContext($applicationContext)
                ->processingInstruction($this->order->getProcessingInstruction())
                ->build()
        ]);
        dd($apiResponse);
        if ($apiResponse->isError()) {
            throw new RuntimeException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred');
        }
        $this->order = $apiResponse->getResult();
        $this->saveOrderToDatabase($this->order, $this->payPalRequestId);
        return $this;
    }

    /**
     * @throws CaptureOrderException|RuntimeException
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
        $order = $this->getOrderFromPayPal();

        dd(Order)
        $data = [
            'id' => $this->order->getId(),
            //'paypalRequestId' => $this->payPalRequestId ?? $this->generateRequestId(),
        ];

//        if (($this->order->getIntent() ?? $this->intent->value) !== PayPalCheckoutPaymentIntent::AUTHORIZE->value) {
//            $data['body'] = OrderCaptureRequestBuilder::init()->build();
//        } else {
//            $data['body'] = OrderAuthorizeRequestBuilder::init()->build();
//        }

        $apiResponse = ($this->order->getIntent() ?? $this->intent->value) !== PayPalCheckoutPaymentIntent::AUTHORIZE->value ?
            $client->ordersCapture($data) :
            $client->ordersAuthorize($data);
        if ($apiResponse->isError()) {
            Log::error('CaptureOrderException: ' . $apiResponse->getReasonPhrase() ?? 'An error occurred', [
                'response' => $apiResponse->getResult(),
                'request' => $data,
                'request_id' => $this->payPalRequestId,
                'order' => $this->order,
                'intent' => $this->intent,
                'apiRequest' => $apiResponse->getRequest()
            ]);
            throw new CaptureOrderException($apiResponse->getReasonPhrase() ?? $apiResponse->getBody() ?? 'An error occurred', $apiResponse);
        }
        $this->order = $apiResponse->getResult();
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
        $this->saveOrderToDatabase($this->order, $this->payPalRequestId);
        return $this;
    }
}
