<?php

namespace SytxLabs\PayPal\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Collection;
use PaypalServerSDKLib\Controllers\OrdersController;
use PaypalServerSDKLib\Models\Builders\OrderApplicationContextBuilder;
use PaypalServerSDKLib\Models\Builders\OrderBuilder;
use PaypalServerSDKLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSDKLib\Models\Builders\PayerBuilder;
use PaypalServerSDKLib\Models\Builders\PaymentSourceBuilder;
use PaypalServerSDKLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSDKLib\Models\CheckoutPaymentIntent;
use PaypalServerSDKLib\Models\LinkDescription;
use PaypalServerSDKLib\Models\Order;
use PaypalServerSDKLib\Models\Payer;
use PaypalServerSDKLib\Models\PaymentSource;
use RuntimeException;
use SytxLabs\PayPal\Enums\PayPalOrderCompletionType;
use SytxLabs\PayPal\Models\Order as OrderModel;
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
        $this->controller = $this->getClient()->getOrdersController();
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

    public function addProduct(PurchaseUnitRequestBuilder $product): self
    {
        $this->items->push($product->build());
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

    /**
     * @throws RuntimeException
     */
    public function createOrder(): self
    {
        $client = $this->controller ?? $this->build()->controller;
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
        if ($this->items->count() > 10) {
            throw new RuntimeException('Maximum of 10 items allowed per order');
        }
        if (($this->config['success_route'] ?? null) !== null) {
            $applicationContext = $applicationContext->returnUrl($this->config['success_route']);
        }
        if (($this->config['cancel_route'] ?? null) !== null) {
            $applicationContext = $applicationContext->cancelUrl($this->config['cancel_route']);
        }
        $this->payPalRequestId ??= $this->generateRequestId();
        $apiResponse = $client->ordersCreate([
            'body' => OrderRequestBuilder::init($this->intent, $this->items->values()->toArray())
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

    /**
     * @throws RuntimeException
     */
    public function checkIfCompleted(): PayPalOrderCompletionType
    {
        $client = $this->controller ?? $this->build()->controller;
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
            throw new RuntimeException($apiResponse->getReasonPhrase() ?? 'An error occurred');
        }
        $this->order = $apiResponse->getResult();
        $this->saveOrderToDatabase($this->order);
        return PayPalOrderCompletionType::tryFrom(strtoupper($this->order->getStatus() ?? '')) ?? PayPalOrderCompletionType::UNKNOWN;
    }
}
