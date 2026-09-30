<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use SytxLabs\PayPal\Enums\DTO\Subscription\SubscriptionStatus;
use SytxLabs\PayPal\Events\PayPalSubscriptionPaymentCompleted;
use SytxLabs\PayPal\Events\PayPalSubscriptionPaymentFailed;
use SytxLabs\PayPal\Events\PayPalSubscriptionPaymentRefunded;
use SytxLabs\PayPal\Events\PayPalWebhookReceived;
use SytxLabs\PayPal\Models\Subscription;
use SytxLabs\PayPal\Services\PayPalSubscription;

function fakeWebhookVerify(string $status = 'SUCCESS'): void
{
    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'A-TOKEN', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's'], 200),
        '*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => $status], 200),
    ]);
}

function webhookHeaders(): array
{
    return [
        'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        'PAYPAL-CERT-URL' => 'https://api.paypal.com/cert.pem',
        'PAYPAL-TRANSMISSION-ID' => 'tx-1',
        'PAYPAL-TRANSMISSION-SIG' => 'sig',
        'PAYPAL-TRANSMISSION-TIME' => '2024-01-01T00:00:00Z',
    ];
}

it('verifies a webhook signature via the PayPal API', function () {
    fakeWebhookVerify('SUCCESS');

    $ok = (new PayPalSubscription())->verifyWebhookSignature(webhookHeaders(), json_encode(['event_type' => 'X']));

    expect($ok)->toBeTrue();
    Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/notifications/verify-webhook-signature') && ($request->data()['webhook_id'] ?? null) === 'WH-TEST');
});

it('accepts a valid webhook, updates the subscription and dispatches an event', function () {
    fakeWebhookVerify('SUCCESS');
    Event::fake([PayPalWebhookReceived::class]);

    $payload = [
        'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
        'resource' => ['id' => 'I-100', 'plan_id' => 'P-1', 'status' => 'ACTIVE'],
    ];

    $response = $this->postJson('paypal/webhook', $payload, webhookHeaders());

    $response->assertOk()->assertJson(['status' => 'ok']);

    $row = Subscription::query()->firstWhere('subscription_id', 'I-100');
    expect($row)->not->toBeNull()->and($row->status)->toBe(SubscriptionStatus::ACTIVE)->and($row->plan_id)->toBe('P-1');
    Event::assertDispatched(PayPalWebhookReceived::class, fn ($e) => $e->eventType === 'BILLING.SUBSCRIPTION.ACTIVATED');
});

it('rejects a webhook with an invalid signature', function () {
    fakeWebhookVerify('FAILURE');
    $this->postJson('paypal/webhook', ['event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED', 'resource' => ['id' => 'I-200']], webhookHeaders())
        ->assertStatus(400)->assertJson(['status' => 'invalid_signature']);
    expect(Subscription::query()->where('subscription_id', 'I-200')->exists())->toBeFalse();
});

it('maps event types to a status when the resource has none', function (string $event, SubscriptionStatus $expected) {
    fakeWebhookVerify('SUCCESS');

    $this->postJson('paypal/webhook', ['event_type' => $event, 'resource' => ['id' => 'I-300']], webhookHeaders())->assertOk();

    expect(Subscription::query()->firstWhere('subscription_id', 'I-300')->status)->toBe($expected);
})->with([
    ['BILLING.SUBSCRIPTION.ACTIVATED', SubscriptionStatus::ACTIVE],
    ['BILLING.SUBSCRIPTION.CANCELLED', SubscriptionStatus::CANCELLED],
    ['BILLING.SUBSCRIPTION.SUSPENDED', SubscriptionStatus::SUSPENDED],
    ['BILLING.SUBSCRIPTION.EXPIRED', SubscriptionStatus::EXPIRED],
]);

it('keeps stored plan, custom id and links when a webhook omits them', function () {
    fakeWebhookVerify('SUCCESS');
    Subscription::query()->create([
        'subscription_id' => 'I-400',
        'plan_id' => 'P-KEEP',
        'custom_id' => 'user-1',
        'links' => [['href' => 'https://approve', 'rel' => 'approve']],
        'status' => SubscriptionStatus::ACTIVE,
    ]);

    $this->postJson('paypal/webhook', ['event_type' => 'BILLING.SUBSCRIPTION.CANCELLED', 'resource' => ['id' => 'I-400', 'status' => 'CANCELLED']], webhookHeaders())->assertOk();

    $row = Subscription::query()->firstWhere('subscription_id', 'I-400');
    expect($row->status)->toBe(SubscriptionStatus::CANCELLED)
        ->and($row->plan_id)->toBe('P-KEEP')
        ->and($row->custom_id)->toBe('user-1')
        ->and($row->links)->toHaveCount(1);
});

it('ignores non-subscription events but still dispatches the event', function () {
    fakeWebhookVerify('SUCCESS');
    Event::fake([PayPalWebhookReceived::class]);

    $this->postJson('paypal/webhook', ['event_type' => 'PAYMENT.CAPTURE.COMPLETED', 'resource' => ['id' => 'CAP-1']], webhookHeaders())->assertOk();

    expect(Subscription::query()->where('subscription_id', 'CAP-1')->exists())->toBeFalse();
    Event::assertDispatched(PayPalWebhookReceived::class, fn ($e) => $e->eventType === 'PAYMENT.CAPTURE.COMPLETED');
});

it('does not dispatch an event for an invalid signature', function () {
    fakeWebhookVerify('FAILURE');
    Event::fake([PayPalWebhookReceived::class]);

    $this->postJson('paypal/webhook', ['event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED', 'resource' => ['id' => 'I-500']], webhookHeaders())->assertStatus(400);

    Event::assertNotDispatched(PayPalWebhookReceived::class);
});

it('returns false when the verification call fails', function () {
    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'A-TOKEN', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's'], 200),
        '*/v1/notifications/verify-webhook-signature' => Http::response(['name' => 'ERR'], 500),
    ]);

    expect((new PayPalSubscription())->verifyWebhookSignature(webhookHeaders(), ['event_type' => 'X']))->toBeFalse();
});

it('throws when no webhook_id is configured', function () {
    config(['paypal.webhook_id' => null]);
    fakeWebhookVerify('SUCCESS');

    (new PayPalSubscription(config('paypal')))->verifyWebhookSignature(webhookHeaders(), ['event_type' => 'X']);
})->throws(RuntimeException::class, 'webhook_id');

it('stores a recurring payment and dispatches PaymentCompleted', function () {
    fakeWebhookVerify('SUCCESS');
    Event::fake([PayPalSubscriptionPaymentCompleted::class, PayPalWebhookReceived::class]);
    Subscription::query()->create(['subscription_id' => 'I-600', 'plan_id' => 'P-1', 'status' => SubscriptionStatus::ACTIVE, 'failed_payments_count' => 2]);

    $this->postJson('paypal/webhook', [
        'id' => 'WH-EVT-600',
        'event_type' => 'PAYMENT.SALE.COMPLETED',
        'resource' => [
            'id' => 'SALE-1',
            'billing_agreement_id' => 'I-600',
            'amount' => ['total' => '9.99', 'currency' => 'EUR'],
            'state' => 'completed',
            'create_time' => '2026-09-01T10:00:00Z',
        ],
    ], webhookHeaders())->assertOk();

    $row = Subscription::query()->firstWhere('subscription_id', 'I-600');
    expect($row->last_payment_amount)->toBe('9.99')
        ->and($row->last_payment_currency)->toBe('EUR')
        ->and($row->last_payment_time->toIso8601ZuluString())->toBe('2026-09-01T10:00:00Z')
        ->and($row->failed_payments_count)->toBe(0);
    Event::assertDispatched(PayPalSubscriptionPaymentCompleted::class, fn ($e) => $e->subscriptionId === 'I-600' && $e->saleId === 'SALE-1' && $e->amount->getValue() === '9.99' && $e->subscription?->is($row));
    Event::assertDispatched(PayPalWebhookReceived::class, fn ($e) => $e->subscription?->subscription_id === 'I-600');
});

it('counts failed payments and dispatches PaymentFailed', function () {
    fakeWebhookVerify('SUCCESS');
    Event::fake([PayPalSubscriptionPaymentFailed::class]);
    Subscription::query()->create(['subscription_id' => 'I-700', 'plan_id' => 'P-1', 'status' => SubscriptionStatus::ACTIVE]);

    $this->postJson('paypal/webhook', [
        'id' => 'WH-EVT-700',
        'event_type' => 'BILLING.SUBSCRIPTION.PAYMENT.FAILED',
        'resource' => ['id' => 'I-700', 'status' => 'ACTIVE'],
    ], webhookHeaders())->assertOk();

    expect(Subscription::query()->firstWhere('subscription_id', 'I-700')->failed_payments_count)->toBe(1);
    Event::assertDispatched(PayPalSubscriptionPaymentFailed::class, fn ($e) => $e->subscriptionId === 'I-700' && $e->failedPaymentsCount === 1);
});

it('prefers the failed payment count reported by PayPal', function () {
    fakeWebhookVerify('SUCCESS');
    Subscription::query()->create(['subscription_id' => 'I-710', 'plan_id' => 'P-1', 'status' => SubscriptionStatus::ACTIVE, 'failed_payments_count' => 1]);

    $this->postJson('paypal/webhook', [
        'id' => 'WH-EVT-710',
        'event_type' => 'BILLING.SUBSCRIPTION.PAYMENT.FAILED',
        'resource' => ['id' => 'I-710', 'status' => 'SUSPENDED', 'billing_info' => ['failed_payments_count' => 3]],
    ], webhookHeaders())->assertOk();

    $row = Subscription::query()->firstWhere('subscription_id', 'I-710');
    expect($row->failed_payments_count)->toBe(3)->and($row->status)->toBe(SubscriptionStatus::SUSPENDED);
});

it('dispatches PaymentRefunded for refunds and reversals', function (string $eventType, bool $reversed) {
    fakeWebhookVerify('SUCCESS');
    Event::fake([PayPalSubscriptionPaymentRefunded::class]);

    $this->postJson('paypal/webhook', [
        'id' => 'WH-' . $eventType,
        'event_type' => $eventType,
        'resource' => ['id' => 'REF-1', 'sale_id' => 'SALE-1', 'billing_agreement_id' => 'I-800', 'amount' => ['total' => '9.99', 'currency' => 'EUR']],
    ], webhookHeaders())->assertOk();

    Event::assertDispatched(PayPalSubscriptionPaymentRefunded::class, fn ($e) => $e->subscriptionId === 'I-800' && $e->saleId === 'SALE-1' && $e->reversed === $reversed && $e->amount->getCurrencyCode() === 'EUR');
})->with([
    ['PAYMENT.SALE.REFUNDED', false],
    ['PAYMENT.SALE.REVERSED', true],
]);

it('stores billing info delivered with subscription events', function () {
    fakeWebhookVerify('SUCCESS');

    $this->postJson('paypal/webhook', [
        'id' => 'WH-EVT-900',
        'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
        'resource' => [
            'id' => 'I-900',
            'plan_id' => 'P-1',
            'status' => 'ACTIVE',
            'quantity' => '2',
            'billing_info' => ['next_billing_time' => '2026-10-01T10:00:00Z', 'failed_payments_count' => 0],
            'links' => [['href' => 'https://api/self', 'rel' => 'self', 'method' => 'GET']],
        ],
    ], webhookHeaders())->assertOk();

    $row = Subscription::query()->firstWhere('subscription_id', 'I-900');
    expect($row->next_billing_time->toIso8601ZuluString())->toBe('2026-10-01T10:00:00Z')
        ->and($row->quantity)->toBe('2')
        ->and($row->links)->toBeNull();
});

it('processes each webhook event id only once', function () {
    fakeWebhookVerify('SUCCESS');
    Event::fake([PayPalWebhookReceived::class]);
    $payload = ['id' => 'WH-DUP', 'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED', 'resource' => ['id' => 'I-1000', 'status' => 'ACTIVE']];

    $this->postJson('paypal/webhook', $payload, webhookHeaders())->assertOk()->assertJson(['status' => 'ok']);
    $this->postJson('paypal/webhook', $payload, webhookHeaders())->assertOk()->assertJson(['status' => 'duplicate']);

    Event::assertDispatchedTimes(PayPalWebhookReceived::class, 1);
});

it('rolls back claim and changes when processing fails, so the PayPal retry is processed', function () {
    fakeWebhookVerify('SUCCESS');
    Subscription::query()->create(['subscription_id' => 'I-1100', 'plan_id' => 'P-1', 'status' => SubscriptionStatus::ACTIVE]);
    $service = new class extends PayPalSubscription
    {
        public bool $fail = true;

        public function recordFailedSubscriptionPayment(string $subscriptionId, ?int $failedPaymentsCount = null): ?Subscription
        {
            if ($this->fail) {
                throw new RuntimeException('database down');
            }
            return parent::recordFailedSubscriptionPayment($subscriptionId, $failedPaymentsCount);
        }
    };
    app()->instance('paypal_subscription_client', $service);
    $payload = ['id' => 'WH-FAIL', 'event_type' => 'BILLING.SUBSCRIPTION.PAYMENT.FAILED', 'resource' => ['id' => 'I-1100', 'status' => 'SUSPENDED']];

    $this->withoutExceptionHandling();
    expect(fn () => $this->postJson('paypal/webhook', $payload, webhookHeaders()))->toThrow(RuntimeException::class, 'database down');

    // the status sync before the failure was rolled back together with the claim
    expect(DB::table('sytxlabs_paypal_webhook_events')->where('event_id', 'WH-FAIL')->exists())->toBeFalse()
        ->and(Subscription::query()->firstWhere('subscription_id', 'I-1100')->status)->toBe(SubscriptionStatus::ACTIVE);

    $service->fail = false;
    $this->postJson('paypal/webhook', $payload, webhookHeaders())->assertOk()->assertJson(['status' => 'ok']);

    $row = Subscription::query()->firstWhere('subscription_id', 'I-1100');
    expect($row->status)->toBe(SubscriptionStatus::SUSPENDED)->and($row->failed_payments_count)->toBe(1);
});

it('does not repeat database changes when a listener fails', function () {
    fakeWebhookVerify('SUCCESS');
    Subscription::query()->create(['subscription_id' => 'I-1200', 'plan_id' => 'P-1', 'status' => SubscriptionStatus::ACTIVE]);
    Event::listen(PayPalSubscriptionPaymentFailed::class, static function () {
        throw new RuntimeException('listener failed');
    });
    $payload = ['id' => 'WH-LISTENER', 'event_type' => 'BILLING.SUBSCRIPTION.PAYMENT.FAILED', 'resource' => ['id' => 'I-1200', 'status' => 'ACTIVE']];

    $this->postJson('paypal/webhook', $payload, webhookHeaders())->assertStatus(500);
    $this->postJson('paypal/webhook', $payload, webhookHeaders())->assertOk()->assertJson(['status' => 'duplicate']);

    expect(Subscription::query()->firstWhere('subscription_id', 'I-1200')->failed_payments_count)->toBe(1);
});

it('ignores an older state delivered after a newer one', function () {
    fakeWebhookVerify('SUCCESS');

    $this->postJson('paypal/webhook', [
        'id' => 'WH-CANCEL',
        'event_type' => 'BILLING.SUBSCRIPTION.CANCELLED',
        'resource' => ['id' => 'I-1300', 'plan_id' => 'P-1', 'status' => 'CANCELLED', 'update_time' => '2026-09-30T10:05:00Z'],
    ], webhookHeaders())->assertOk();
    $this->postJson('paypal/webhook', [
        'id' => 'WH-ACTIVATE-LATE',
        'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
        'resource' => ['id' => 'I-1300', 'plan_id' => 'P-1', 'status' => 'ACTIVE', 'update_time' => '2026-09-30T10:00:00Z'],
    ], webhookHeaders())->assertOk();

    $row = Subscription::query()->firstWhere('subscription_id', 'I-1300');
    expect($row->status)->toBe(SubscriptionStatus::CANCELLED)
        ->and($row->isActive())->toBeFalse()
        ->and($row->paypal_update_time->toIso8601ZuluString())->toBe('2026-09-30T10:05:00Z');
});

it('uses the event time as version when the resource has no update_time', function () {
    fakeWebhookVerify('SUCCESS');

    $this->postJson('paypal/webhook', [
        'id' => 'WH-SUSPEND',
        'create_time' => '2026-09-30T11:00:00Z',
        'event_type' => 'BILLING.SUBSCRIPTION.SUSPENDED',
        'resource' => ['id' => 'I-1400', 'status' => 'SUSPENDED'],
    ], webhookHeaders())->assertOk();
    $this->postJson('paypal/webhook', [
        'id' => 'WH-ACTIVATE-OLD',
        'create_time' => '2026-09-30T09:00:00Z',
        'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
        'resource' => ['id' => 'I-1400', 'status' => 'ACTIVE'],
    ], webhookHeaders())->assertOk();

    expect(Subscription::query()->firstWhere('subscription_id', 'I-1400')->status)->toBe(SubscriptionStatus::SUSPENDED);
});

it('ignores an older sale than the stored last payment', function () {
    fakeWebhookVerify('SUCCESS');
    Subscription::query()->create(['subscription_id' => 'I-1500', 'status' => SubscriptionStatus::ACTIVE, 'last_payment_time' => '2026-09-01 10:00:00', 'last_payment_amount' => '9.99', 'last_payment_currency' => 'EUR']);

    $this->postJson('paypal/webhook', [
        'id' => 'WH-OLD-SALE',
        'event_type' => 'PAYMENT.SALE.COMPLETED',
        'resource' => ['id' => 'SALE-OLD', 'billing_agreement_id' => 'I-1500', 'amount' => ['total' => '4.99', 'currency' => 'EUR'], 'create_time' => '2026-08-01T10:00:00Z'],
    ], webhookHeaders())->assertOk();

    expect(Subscription::query()->firstWhere('subscription_id', 'I-1500')->last_payment_amount)->toBe('9.99');
});

it('rejects webhooks with 503 while the event table is missing', function () {
    fakeWebhookVerify('SUCCESS');
    Schema::drop('sytxlabs_paypal_webhook_events');
    $payload = ['id' => 'WH-NOSTORE', 'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED', 'resource' => ['id' => 'I-1600', 'status' => 'ACTIVE']];

    $this->postJson('paypal/webhook', $payload, webhookHeaders())->assertStatus(503)->assertJson(['status' => 'event_store_unavailable']);
    expect(Subscription::query()->where('subscription_id', 'I-1600')->exists())->toBeFalse();
});

it('accepts webhooks without event table when deduplication is disabled', function () {
    fakeWebhookVerify('SUCCESS');
    Schema::drop('sytxlabs_paypal_webhook_events');
    config(['paypal.webhook.deduplicate' => false]);

    $this->postJson('paypal/webhook', ['id' => 'WH-NODEDUP', 'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED', 'resource' => ['id' => 'I-1700', 'status' => 'ACTIVE']], webhookHeaders())
        ->assertOk()->assertJson(['status' => 'ok']);
});
