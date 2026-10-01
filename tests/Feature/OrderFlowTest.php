<?php

use Illuminate\Support\Facades\Http;
use SytxLabs\PayPal\Enums\PayPalCheckoutPaymentIntent;
use SytxLabs\PayPal\Enums\PayPalOrderCompletionType;
use SytxLabs\PayPal\Models\DTO\Product;
use SytxLabs\PayPal\Models\Order;
use SytxLabs\PayPal\Services\PayPalOrder;

function fakePayPalOrderHappyPath(): void
{
    Http::preventStrayRequests();
    Http::fake([
        '*/v1/oauth2/token' => Http::response([
            'access_token' => 'A-TOKEN',
            'token_type' => 'Bearer',
            'expires_in' => 3200,
            'scope' => 'https://uri.paypal.com/services/checkout/orders',
        ], 200),
        '*/v2/checkout/orders*' => Http::response([
            'id' => 'ORDER-1',
            'status' => 'CREATED',
            'intent' => 'CAPTURE',
            'create_time' => '2024-01-01T00:00:00Z',
            'links' => [
                ['href' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1', 'rel' => 'approve', 'method' => 'GET'],
                ['href' => 'https://api-m.sandbox.paypal.com/v2/checkout/orders/ORDER-1', 'rel' => 'self', 'method' => 'GET'],
            ],
        ], 201),
    ]);
}

it('creates an order and returns id and approve link', function () {
    fakePayPalOrderHappyPath();

    $order = (new PayPalOrder())->setIntent(PayPalCheckoutPaymentIntent::CAPTURE)->addProduct(new Product('Widget', 19.99, 2, 'EUR'))->createOrder();
    expect($order->getOrder()->getId())->toBe('ORDER-1')
        ->and($order->getOrderStatus())->toBe(PayPalOrderCompletionType::CREATED)
        ->and($order->getApproveOrderRoute())->toBe('https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1');
});

it('sends the product total as a purchase unit amount', function () {
    fakePayPalOrderHappyPath();
    (new PayPalOrder())->addProduct(new Product('Widget', 19.99, 2, 'EUR'))->createOrder();
    Http::assertSent(function ($request) {
        if (!str_contains($request->url(), '/v2/checkout/orders') || $request->method() !== 'POST') {
            return false;
        }
        $data = json_decode($request->body(), true);
        $unit = $data['purchase_units'][0] ?? [];
        return ($data['intent'] ?? null) === 'CAPTURE' && ($unit['amount']['currency_code'] ?? null) === 'EUR' && ($unit['amount']['value'] ?? null) === '39.98';
    });
});

it('multiplies the unit tax by the quantity like PayPal does', function () {
    fakePayPalOrderHappyPath();
    (new PayPalOrder())->addProduct((new Product('Widget', 15.0, 2, 'EUR'))->setTax(2.85)->setShipping(19.98))->createOrder();
    Http::assertSent(function ($request) {
        if (!str_contains($request->url(), '/v2/checkout/orders') || $request->method() !== 'POST') {
            return false;
        }
        $unit = json_decode($request->body(), true)['purchase_units'][0] ?? [];
        $breakdown = $unit['amount']['breakdown'] ?? [];
        return ($unit['items'][0]['tax']['value'] ?? null) === '2.85'
            && ($breakdown['item_total']['value'] ?? null) === '30.00'
            && ($breakdown['tax_total']['value'] ?? null) === '5.70'
            && ($unit['amount']['value'] ?? null) === '55.68';
    });
});

it('persists the order to the database', function () {
    fakePayPalOrderHappyPath();
    (new PayPalOrder())->addProduct(new Product('Widget', 19.99, 2, 'EUR'))->createOrder();
    $row = Order::query()->firstWhere('order_id', 'ORDER-1');
    expect($row)->not->toBeNull()->and($row->status)->toBe(PayPalOrderCompletionType::CREATED)->and($row->intent)->toBe(PayPalCheckoutPaymentIntent::CAPTURE);
});

it('throws when creating an order with no items', function () {
    fakePayPalOrderHappyPath();
    (new PayPalOrder())->createOrder();
})->throws(RuntimeException::class, 'No items added to the order');

it('captures an approved order and marks it completed', function () {
    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'A-TOKEN', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's'], 200),
        '*/v2/checkout/orders/ORDER-1/capture' => Http::response(['id' => 'ORDER-1', 'status' => 'COMPLETED', 'intent' => 'CAPTURE'], 201),
        '*/v2/checkout/orders/ORDER-1' => Http::response(['id' => 'ORDER-1', 'status' => 'APPROVED', 'intent' => 'CAPTURE'], 200),
    ]);
    $service = new PayPalOrder();
    $service->getOrderFormId('ORDER-1');
    $service->captureOrder();
    expect($service->getOrderStatus())->toBe(PayPalOrderCompletionType::COMPLETED)
        ->and(Order::query()->firstWhere('order_id', 'ORDER-1')->status)->toBe(PayPalOrderCompletionType::COMPLETED);
    Http::assertSent(fn ($request) => str_contains($request->url(), '/v2/checkout/orders/ORDER-1/capture') && $request->method() === 'POST');
});
