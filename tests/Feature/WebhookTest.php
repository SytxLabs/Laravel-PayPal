<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use SytxLabs\PayPal\Enums\DTO\Subscription\SubscriptionStatus;
use SytxLabs\PayPal\Events\PayPalWebhookReceived;
use SytxLabs\PayPal\Models\Subscription;
use SytxLabs\PayPal\Services\PayPalSubscription;

function fakeWebhookVerify(string $status = 'SUCCESS'): void
{
    Http::fake([
        '*/v1/oauth2/token' => Http::response([
            'access_token' => 'A-TOKEN', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's',
        ], 200),
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
    Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/notifications/verify-webhook-signature')
        && ($request->data()['webhook_id'] ?? null) === 'WH-TEST');
});

it('accepts a valid webhook, updates the subscription and dispatches an event', function () {
    fakeWebhookVerify('SUCCESS');
    Event::fake([PayPalWebhookReceived::class]);

    $payload = [
        'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
        'resource' => [
            'id' => 'I-100',
            'plan_id' => 'P-1',
            'status' => 'ACTIVE',
        ],
    ];

    $response = $this->postJson('paypal/webhook', $payload, webhookHeaders());

    $response->assertOk()->assertJson(['status' => 'ok']);

    $row = Subscription::query()->firstWhere('subscription_id', 'I-100');
    expect($row)->not->toBeNull()
        ->and($row->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($row->plan_id)->toBe('P-1');

    Event::assertDispatched(PayPalWebhookReceived::class, fn ($e) => $e->eventType === 'BILLING.SUBSCRIPTION.ACTIVATED');
});

it('rejects a webhook with an invalid signature', function () {
    fakeWebhookVerify('FAILURE');

    $response = $this->postJson('paypal/webhook', ['event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED', 'resource' => ['id' => 'I-200']], webhookHeaders());

    $response->assertStatus(400)->assertJson(['status' => 'invalid_signature']);
    expect(Subscription::query()->where('subscription_id', 'I-200')->exists())->toBeFalse();
});
