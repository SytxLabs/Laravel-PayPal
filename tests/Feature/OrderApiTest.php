<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use SytxLabs\PayPal\Enums\PayPalOrderCompletionType;
use SytxLabs\PayPal\Exception\CaptureOrderException;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Product;
use SytxLabs\PayPal\Models\Order;
use SytxLabs\PayPal\Services\PayPal;
use SytxLabs\PayPal\Services\PayPalOrder;
use SytxLabs\PayPal\Services\PayPalSubscription;
use SytxLabs\PayPal\Support\OrderPayments;

function apiToken(string $token = 'A-TOKEN'): mixed
{
    return Http::response(['access_token' => $token, 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's'], 200);
}

/** @return array<string,mixed> an order as the Orders API sends it after the capture */
function apiCapturedOrder(string $id = 'ORDER-1', string $value = '49.90'): array
{
    return [
        'id' => $id,
        'status' => 'COMPLETED',
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => 'default',
            'amount' => ['currency_code' => 'EUR', 'value' => $value],
            'payments' => ['captures' => [['id' => 'CAP-1', 'status' => 'COMPLETED', 'final_capture' => true, 'amount' => ['currency_code' => 'EUR', 'value' => $value]]]],
        ]],
        'links' => [['href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . $id, 'rel' => 'self', 'method' => 'GET']],
    ];
}

/** @return list<Request> */
function apiSent(string $urlEnd, ?string $method = null): array
{
    return Http::recorded(static fn (Request $request) => str_ends_with($request->url(), $urlEnd) && ($method === null || $request->method() === $method))->map(static fn (array $pair) => $pair[0])->values()->all();
}

it('gives a client for any REST call that is built on first use and relative to the API host', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/v2/payments/captures/CAP-1' => Http::response(['id' => 'CAP-1'], 200)]);

    $response = (new PayPalOrder())->api()->get('v2/payments/captures/CAP-1');

    expect($response->json('id'))->toBe('CAP-1');
    $sent = apiSent('/v2/payments/captures/CAP-1', 'GET')[0];
    expect($sent->url())->toBe('https://api-m.sandbox.paypal.com/v2/payments/captures/CAP-1')
        ->and($sent->header('Authorization'))->toBe(['Bearer A-TOKEN']);
});

it('works on every service, not only on orders', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/v1/anything' => Http::response([], 200)]);

    foreach ([new PayPal(), new PayPalOrder(), new PayPalSubscription()] as $service) {
        expect($service->api()->get('v1/anything')->status())->toBe(200);
    }
});

it('hands out copies, so headers of one call do not travel to the next', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/v1/anything' => Http::response([], 200)]);
    $service = new PayPalOrder();

    $service->api()->withHeader('PayPal-Request-Id', 'first')->get('v1/anything');
    $service->api()->get('v1/anything');

    $sent = apiSent('/v1/anything');
    expect($sent[0]->header('PayPal-Request-Id'))->toBe(['first'])->and($sent[1]->header('PayPal-Request-Id'))->toBe([]);
});

it('does not carry the headers of an order call into the REST calls', function () {
    Http::fake([
        '*/v1/oauth2/token' => apiToken(),
        '*/v2/checkout/orders/ORDER-1' => Http::response(apiCapturedOrder(), 200),
        '*/v2/checkout/orders*' => Http::response(['id' => 'ORDER-1', 'status' => 'CREATED', 'intent' => 'CAPTURE', 'create_time' => '2024-01-01T00:00:00Z', 'links' => [['href' => 'https://x/approve', 'rel' => 'approve', 'method' => 'GET']]], 201),
    ]);
    $service = (new PayPalOrder())->addProduct(new Product('Widget', 49.9, 1, 'EUR'));
    $service->createOrder();

    $service->readOrder('ORDER-1');

    $read = apiSent('/v2/checkout/orders/ORDER-1', 'GET')[0];
    expect($read->header('PayPal-Request-Id'))->toBe([])->and($read->header('Prefer'))->toBe([]);
});

it('reads an order as PayPal sent it', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/v2/checkout/orders/ORDER-1' => Http::response(apiCapturedOrder(), 200)]);

    $order = (new PayPalOrder())->readOrder('ORDER-1');

    expect($order['status'])->toBe('COMPLETED')
        ->and(OrderPayments::collected($order)->getValue())->toBe('49.90');
});

it('reads an order without touching the one the service holds or the database', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/v2/checkout/orders/ORDER-1' => Http::response(apiCapturedOrder(), 200)]);
    $service = new PayPalOrder();

    $service->readOrder('ORDER-1');

    expect($service->getOrder())->toBeNull()->and(Order::query()->count())->toBe(0);
});

it('fails loudly when an order cannot be read', function (int $status) {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/v2/checkout/orders/ORDER-9' => Http::response(['name' => 'RESOURCE_NOT_FOUND'], $status)]);

    (new PayPalOrder())->readOrder('ORDER-9');
})->with([404, 500])->throws(RuntimeException::class, 'PayPal order ORDER-9 could not be read');

it('encodes the order id it puts in the path', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*' => Http::response(['name' => 'RESOURCE_NOT_FOUND'], 404)]);

    try {
        (new PayPalOrder())->readOrder('../v1/oauth2/token');
    } catch (RuntimeException) {
    }

    expect(apiSent('/v2/checkout/orders/..%2Fv1%2Foauth2%2Ftoken', 'GET'))->toHaveCount(1);
});

it('captures an order by id with a request id derived from the order and returns the captured order', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/v2/checkout/orders/ORDER-1/capture' => Http::response(apiCapturedOrder(), 201)]);

    $order = (new PayPalOrder())->captureOrderById('ORDER-1');

    $sent = apiSent('/v2/checkout/orders/ORDER-1/capture', 'POST');
    expect($sent)->toHaveCount(1)
        ->and($sent[0]->header('PayPal-Request-Id'))->toBe(['capture-ORDER-1'])
        ->and($sent[0]->header('Prefer'))->toBe(['return=representation'])
        ->and($sent[0]->body())->toBe('{}')
        ->and($order['status'])->toBe('COMPLETED')
        ->and(OrderPayments::collected($order)->getValue())->toBe('49.90');
});

it('takes the request id of the caller', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/capture' => Http::response(apiCapturedOrder(), 201)]);

    (new PayPalOrder())->captureOrderById('ORDER-1', 'my-request');

    expect(apiSent('/capture', 'POST')[0]->header('PayPal-Request-Id'))->toBe(['my-request']);
});

it('keeps the stored order in step with the capture', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/capture' => Http::response(apiCapturedOrder(), 201)]);
    Order::query()->create(['order_id' => 'ORDER-1', 'status' => PayPalOrderCompletionType::APPROVED, 'request_id' => 'create-request']);

    (new PayPalOrder())->captureOrderById('ORDER-1');

    $row = Order::query()->firstWhere('order_id', 'ORDER-1');
    expect($row->status)->toBe(PayPalOrderCompletionType::COMPLETED)->and($row->request_id)->toBe('create-request');
});

it('captures an order that is not stored', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/capture' => Http::response(apiCapturedOrder(), 201)]);

    (new PayPalOrder())->captureOrderById('ORDER-1');

    expect(Order::query()->firstWhere('order_id', 'ORDER-1')->status)->toBe(PayPalOrderCompletionType::COMPLETED);
});

it('reads the order again when somebody captured it first', function () {
    Http::fake([
        '*/v1/oauth2/token' => apiToken(),
        '*/v2/checkout/orders/ORDER-1/capture' => Http::response(['name' => 'UNPROCESSABLE_ENTITY', 'details' => [['issue' => 'ORDER_ALREADY_CAPTURED']]], 422),
        '*/v2/checkout/orders/ORDER-1' => Http::response(apiCapturedOrder(), 200),
    ]);

    $order = (new PayPalOrder())->captureOrderById('ORDER-1');

    expect($order['status'])->toBe('COMPLETED')->and(apiSent('/v2/checkout/orders/ORDER-1', 'GET'))->toHaveCount(1);
});

it('fails with the PayPal answer when the capture is refused', function (int $status, string $body) {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/capture' => Http::response(json_decode($body, true), $status)]);
    Order::query()->create(['order_id' => 'ORDER-1', 'status' => PayPalOrderCompletionType::APPROVED]);

    try {
        (new PayPalOrder())->captureOrderById('ORDER-1');
        $this->fail('The capture should have been refused.');
    } catch (CaptureOrderException $e) {
        expect($e->getResponse())->toBeArray();
    }
    expect(Order::query()->firstWhere('order_id', 'ORDER-1')->status)->toBe(PayPalOrderCompletionType::APPROVED);
})->with([
    [422, '{"name":"UNPROCESSABLE_ENTITY","details":[{"issue":"ORDER_NOT_APPROVED"}]}'],
    [500, '{"name":"INTERNAL_SERVER_ERROR"}'],
    [400, '{"name":"INVALID_REQUEST"}'],
]);

it('does not mistake any other 422 for an order that is already captured', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/capture' => Http::response(['details' => [['issue' => 'PAYER_ACTION_REQUIRED']]], 422)]);

    (new PayPalOrder())->captureOrderById('ORDER-1');
})->throws(CaptureOrderException::class);

it('swaps a token PayPal turned down once and repeats the call', function () {
    Http::fake([
        '*/v1/oauth2/token' => Http::sequence()->push(['access_token' => 'OLD', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's'])->push(['access_token' => 'NEW', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's']),
        '*/capture' => Http::sequence()->push(['name' => 'AUTHENTICATION_FAILURE'], 401)->push(apiCapturedOrder(), 201),
    ]);

    $order = (new PayPalOrder())->captureOrderById('ORDER-1');

    $capture = apiSent('/capture', 'POST');
    expect($order['status'])->toBe('COMPLETED')
        ->and($capture)->toHaveCount(2)
        ->and($capture[0]->header('Authorization'))->toBe(['Bearer OLD'])
        ->and($capture[1]->header('Authorization'))->toBe(['Bearer NEW'])
        ->and(apiSent('/v1/oauth2/token', 'POST'))->toHaveCount(2)
        ->and(DB::table('sytxlabs_paypal_oauth_tokens')->pluck('access_token')->all())->toBe(['NEW']);
});

it('gives up when the new token is turned down as well', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/capture' => Http::response(['name' => 'AUTHENTICATION_FAILURE'], 401)]);

    try {
        (new PayPalOrder())->captureOrderById('ORDER-1');
        $this->fail('The capture should have failed.');
    } catch (CaptureOrderException) {
    }

    expect(apiSent('/capture', 'POST'))->toHaveCount(2);
});

it('uses a stored token before it asks for a new one', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/v2/checkout/orders/ORDER-1' => Http::response(apiCapturedOrder(), 200)]);

    (new PayPalOrder())->readOrder('ORDER-1');
    (new PayPalOrder())->readOrder('ORDER-1');

    expect(apiSent('/v1/oauth2/token', 'POST'))->toHaveCount(1);
});

it('drops the stored token', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/v2/checkout/orders/ORDER-1' => Http::response(apiCapturedOrder(), 200)]);
    $service = new PayPalOrder();
    $service->readOrder('ORDER-1');
    expect(DB::table('sytxlabs_paypal_oauth_tokens')->count())->toBe(1);

    $service->forgetOAuthToken();

    expect(DB::table('sytxlabs_paypal_oauth_tokens')->count())->toBe(0);
});

it('refunds a capture of an order in full', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/v2/payments/captures/CAP-1/refund' => Http::response(['id' => 'REF-1', 'status' => 'COMPLETED'], 201)]);

    $refund = (new PayPalOrder())->refundCapture('CAP-1');

    $sent = apiSent('/v2/payments/captures/CAP-1/refund', 'POST')[0];
    expect($refund['id'])->toBe('REF-1')
        ->and($sent->body())->toBe('{}')
        ->and($sent->header('PayPal-Request-Id')[0])->not->toBe('');
});

it('refunds a capture partly with note and invoice number and a request id that can be repeated', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/refund' => Http::response(['id' => 'REF-2', 'status' => 'COMPLETED'], 201)]);

    (new PayPalOrder())->refundCapture('CAP-2', new Money('EUR', '0.50'), 'Goodwill', 'INV-7', 'refund-key');
    (new PayPalOrder())->refundCapture('CAP-2', new Money('EUR', '0.50'), 'Goodwill', 'INV-7', 'refund-key');

    $sent = apiSent('/refund', 'POST');
    expect($sent)->toHaveCount(2)
        ->and($sent[0]->header('PayPal-Request-Id'))->toBe(['refund-key'])
        ->and($sent[1]->header('PayPal-Request-Id'))->toBe(['refund-key'])
        ->and(json_decode($sent[0]->body(), true))->toBe(['amount' => ['currency_code' => 'EUR', 'value' => '0.50'], 'note_to_payer' => 'Goodwill', 'invoice_id' => 'INV-7']);
});

it('does not send a new request id for each refund', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/refund' => Http::response(['id' => 'REF-3'], 201)]);

    (new PayPalOrder())->refundCapture('CAP-3');
    (new PayPalOrder())->refundCapture('CAP-3');

    $sent = apiSent('/refund', 'POST');
    expect($sent[0]->header('PayPal-Request-Id'))->not->toBe($sent[1]->header('PayPal-Request-Id'));
});

it('fails when PayPal refuses a refund', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/refund' => Http::response(['name' => 'UNPROCESSABLE_ENTITY'], 422)]);

    (new PayPalOrder())->refundCapture('CAP-4');
})->throws(RuntimeException::class, 'Failed to refund capture');

it('leaves the existing subscription refund as it was', function () {
    Http::fake(['*/v1/oauth2/token' => apiToken(), '*/refund' => Http::response(['name' => 'UNPROCESSABLE_ENTITY'], 422)]);

    (new PayPalSubscription())->refundTransaction('CAP-5');
})->throws(RuntimeException::class, 'Failed to refund transaction');
