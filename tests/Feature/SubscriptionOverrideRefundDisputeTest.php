<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use SytxLabs\PayPal\Events\PayPalDisputeReceived;
use SytxLabs\PayPal\Events\PayPalWebhookReceived;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Services\PayPalSubscription;

function overrideTestFake(array $extra = []): void
{
    Http::fake(array_merge([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'A-TOKEN', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's'], 200),
    ], $extra));
}

/** The JSON body as PayPal receives it (request->data() would keep DTO objects). */
function overrideTestBody(Illuminate\Http\Client\Request $request): array
{
    return json_decode($request->body(), true) ?? [];
}

function overrideTestSubscription(string $id = 'I-1', array $extra = []): array
{
    return array_merge(['id' => $id, 'plan_id' => 'P-1', 'status' => 'ACTIVE', 'quantity' => '1'], $extra);
}

function overrideTestWebhookHeaders(): array
{
    return [
        'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        'PAYPAL-CERT-URL' => 'https://api.paypal.com/cert.pem',
        'PAYPAL-TRANSMISSION-ID' => 'tx-1',
        'PAYPAL-TRANSMISSION-SIG' => 'sig',
        'PAYPAL-TRANSMISSION-TIME' => '2024-01-01T00:00:00Z',
    ];
}

describe('delayed start and per-subscription plan override', function () {
    it('sends start_time and the plan override when creating the subscription', function () {
        overrideTestFake(['*/v1/billing/subscriptions' => Http::response(['id' => 'I-NEW', 'status' => 'APPROVAL_PENDING', 'links' => []], 201)]);

        (new PayPalSubscription())
            ->setPlanId('P-1')
            ->setStartTime(new DateTimeImmutable('2026-11-01 12:00:00', new DateTimeZone('Europe/Berlin')))
            ->overrideBillingCycle(2, new Money('EUR', '0.80'))
            ->createSubscription();

        Http::assertSent(function ($request) {
            if (!str_ends_with($request->url(), '/v1/billing/subscriptions')) {
                return false;
            }
            $data = overrideTestBody($request);
            return $data['plan_id'] === 'P-1'
                && $data['start_time'] === '2026-11-01T11:00:00Z'
                && $data['plan']['billing_cycles'][0]['sequence'] === 2
                && $data['plan']['billing_cycles'][0]['pricing_scheme']['fixed_price']['value'] === '0.80';
        });
    });

    it('does not send start_time or plan when they are not set', function () {
        overrideTestFake(['*/v1/billing/subscriptions' => Http::response(['id' => 'I-NEW', 'status' => 'APPROVAL_PENDING', 'links' => []], 201)]);

        (new PayPalSubscription())->setPlanId('P-1')->createSubscription();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/billing/subscriptions') && !array_key_exists('start_time', overrideTestBody($request)) && !array_key_exists('plan', overrideTestBody($request)));
    });

    it('replaces an override of the same billing cycle sequence and keeps other cycles', function () {
        overrideTestFake(['*/v1/billing/subscriptions' => Http::response(['id' => 'I-NEW', 'status' => 'APPROVAL_PENDING', 'links' => []], 201)]);

        (new PayPalSubscription())
            ->setPlanId('P-1')
            ->overrideBillingCycle(1, new Money('EUR', '0.50'), 3)
            ->overrideBillingCycle(2, new Money('EUR', '1.00'))
            ->overrideBillingCycle(1, new Money('EUR', '0.40'), 2)
            ->createSubscription();

        Http::assertSent(function ($request) {
            if (!str_ends_with($request->url(), '/v1/billing/subscriptions')) {
                return false;
            }
            $cycles = collect(overrideTestBody($request)['plan']['billing_cycles'])->keyBy('sequence');
            return $cycles->count() === 2
                && $cycles[1]['pricing_scheme']['fixed_price']['value'] === '0.40'
                && $cycles[1]['total_cycles'] === 2
                && $cycles[2]['pricing_scheme']['fixed_price']['value'] === '1.00';
        });
    });

    it('rejects an override without price and cycles', function () {
        (new PayPalSubscription())->overrideBillingCycle(2);
    })->throws(RuntimeException::class);

    it('accepts the start time as string', function () {
        overrideTestFake(['*/v1/billing/subscriptions' => Http::response(['id' => 'I-NEW', 'status' => 'APPROVAL_PENDING', 'links' => []], 201)]);

        (new PayPalSubscription())->setPlanId('P-1')->setStartTime('2027-01-01T00:00:00Z')->createSubscription();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/billing/subscriptions') && overrideTestBody($request)['start_time'] === '2027-01-01T00:00:00Z');
    });
});

describe('patching a subscription', function () {
    it('sends JSON-patch operations and refreshes the subscription', function () {
        overrideTestFake(['*/v1/billing/subscriptions/I-1' => Http::sequence()->push('', 204)->push(overrideTestSubscription('I-1'), 200)]);

        $service = (new PayPalSubscription())->setSubscription((new SytxLabs\PayPal\Models\DTO\Subscription\Subscription())->setId('I-1'));
        $service->patchSubscription([['op' => 'replace', 'path' => '/custom_id', 'value' => 'abc']]);

        Http::assertSent(fn ($request) => $request->method() === 'PATCH' && str_ends_with($request->url(), '/v1/billing/subscriptions/I-1') && overrideTestBody($request) === [['op' => 'replace', 'path' => '/custom_id', 'value' => 'abc']]);
        Http::assertSent(fn ($request) => $request->method() === 'GET' && str_ends_with($request->url(), '/v1/billing/subscriptions/I-1'));
    });

    it('builds the cycle price path with the sequence selector', function () {
        overrideTestFake(['*/v1/billing/subscriptions/I-1' => Http::sequence()->push('', 204)->push(overrideTestSubscription('I-1'), 200)]);

        (new PayPalSubscription())->setSubscription((new SytxLabs\PayPal\Models\DTO\Subscription\Subscription())->setId('I-1'))
            ->overrideSubscriptionCyclePrice(2, new Money('EUR', '1.30'));

        Http::assertSent(function ($request) {
            if ($request->method() !== 'PATCH') {
                return false;
            }
            $op = overrideTestBody($request)[0];
            return $op['op'] === 'replace'
                && $op['path'] === '/plan/billing_cycles/@sequence==2/pricing_scheme/fixed_price'
                && $op['value']['value'] === '1.30' && $op['value']['currency_code'] === 'EUR';
        });
    });

    it('builds the total cycles path and the start time path', function () {
        overrideTestFake(['*/v1/billing/subscriptions/I-1' => Http::sequence()->push('', 204)->push(overrideTestSubscription('I-1'), 200)->push('', 204)->push(overrideTestSubscription('I-1'), 200)]);

        $service = (new PayPalSubscription())->setSubscription((new SytxLabs\PayPal\Models\DTO\Subscription\Subscription())->setId('I-1'));
        $service->overrideSubscriptionCycleTotal(1, 3);
        $service->changeStartTime(new DateTimeImmutable('2027-02-01 00:00:00', new DateTimeZone('UTC')));

        Http::assertSent(fn ($request) => $request->method() === 'PATCH' && overrideTestBody($request)[0]['path'] === '/plan/billing_cycles/@sequence==1/total_cycles' && overrideTestBody($request)[0]['value'] === 3);
        Http::assertSent(fn ($request) => $request->method() === 'PATCH' && overrideTestBody($request)[0]['path'] === '/start_time' && overrideTestBody($request)[0]['value'] === '2027-02-01T00:00:00Z');
    });

    it('refuses empty patches and fails loudly when PayPal rejects the patch', function () {
        overrideTestFake(['*/v1/billing/subscriptions/I-1' => Http::response(['name' => 'UNPROCESSABLE_ENTITY'], 422)]);
        $service = (new PayPalSubscription())->setSubscription((new SytxLabs\PayPal\Models\DTO\Subscription\Subscription())->setId('I-1'));

        expect(fn () => $service->patchSubscription([]))->toThrow(RuntimeException::class, 'No patch operations');
        expect(fn () => $service->patchSubscription([['op' => 'replace', 'path' => '/custom_id', 'value' => 'x']]))->toThrow(RuntimeException::class, 'Failed to patch subscription');
    });
});

describe('refunding a transaction', function () {
    it('sends an empty JSON object for a full refund', function () {
        overrideTestFake(['*/v2/payments/captures/CAP-1/refund' => Http::response(['id' => 'REF-1', 'status' => 'COMPLETED'], 201)]);

        $refund = (new PayPalSubscription())->refundTransaction('CAP-1');

        expect($refund['id'])->toBe('REF-1')->and($refund['status'])->toBe('COMPLETED');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v2/payments/captures/CAP-1/refund') && $request->body() === '{}' && $request->hasHeader('PayPal-Request-Id'));
    });

    it('sends amount, note and invoice id for a partial refund and reuses the request id', function () {
        overrideTestFake(['*/v2/payments/captures/CAP-2/refund' => Http::response(['id' => 'REF-2', 'status' => 'COMPLETED'], 201)]);

        (new PayPalSubscription())->refundTransaction('CAP-2', new Money('EUR', '0.50'), 'Goodwill', 'INV-7', 'req-fixed-1');

        Http::assertSent(function ($request) {
            $data = overrideTestBody($request);
            return str_ends_with($request->url(), '/v2/payments/captures/CAP-2/refund')
                && $data['amount']['value'] === '0.50' && $data['amount']['currency_code'] === 'EUR'
                && $data['note_to_payer'] === 'Goodwill' && $data['invoice_id'] === 'INV-7'
                && $request->header('PayPal-Request-Id')[0] === 'req-fixed-1';
        });
    });

    it('throws when PayPal rejects the refund', function () {
        overrideTestFake(['*/v2/payments/captures/CAP-3/refund' => Http::response(['name' => 'RESOURCE_NOT_FOUND'], 404)]);

        (new PayPalSubscription())->refundTransaction('CAP-3');
    })->throws(RuntimeException::class, 'Failed to refund transaction');
});

describe('dispute webhooks', function () {
    $disputeBody = static fn (string $type = 'CUSTOMER.DISPUTE.CREATED', array $resource = []): array => [
        'id' => 'WH-EVT-' . $type,
        'event_type' => $type,
        'create_time' => '2026-10-07T10:00:00Z',
        'resource' => array_merge([
            'dispute_id' => 'PP-D-100',
            'status' => 'OPEN',
            'reason' => 'UNAUTHORISED',
            'dispute_life_cycle_stage' => 'CHARGEBACK',
            'dispute_amount' => ['currency_code' => 'EUR', 'value' => '10.00'],
            'disputed_transactions' => [['seller_transaction_id' => 'TX-1', 'buyer_transaction_id' => 'B-1'], ['seller_transaction_id' => 'TX-2']],
        ], $resource),
    ];

    it('dispatches a dispute event with the mapped fields', function () use ($disputeBody) {
        overrideTestFake(['*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200)]);
        Event::fake([PayPalDisputeReceived::class, PayPalWebhookReceived::class]);

        $this->postJson('paypal/webhook', $disputeBody(), overrideTestWebhookHeaders())->assertOk()->assertJson(['status' => 'ok']);

        Event::assertDispatched(PayPalDisputeReceived::class, function (PayPalDisputeReceived $event) {
            return $event->eventType === 'CUSTOMER.DISPUTE.CREATED'
                && $event->disputeId === 'PP-D-100'
                && $event->status === 'OPEN'
                && $event->reason === 'UNAUTHORISED'
                && $event->lifeCycleStage === 'CHARGEBACK'
                && $event->isChargeback()
                && !$event->isResolved()
                && $event->amount?->getValue() === '10.00' && $event->amount?->getCurrencyCode() === 'EUR'
                && $event->transactionIds === ['TX-1', 'TX-2']
                && $event->outcomeCode === null;
        });
        Event::assertDispatched(PayPalWebhookReceived::class, fn ($event) => $event->eventType === 'CUSTOMER.DISPUTE.CREATED' && $event->subscription === null);
    });

    it('reports the outcome of a resolved dispute', function () use ($disputeBody) {
        overrideTestFake(['*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200)]);
        Event::fake([PayPalDisputeReceived::class, PayPalWebhookReceived::class]);

        $this->postJson('paypal/webhook', $disputeBody('CUSTOMER.DISPUTE.RESOLVED', ['status' => 'RESOLVED', 'dispute_outcome' => ['outcome_code' => 'RESOLVED_BUYER_FAVOUR']]), overrideTestWebhookHeaders())->assertOk();

        Event::assertDispatched(PayPalDisputeReceived::class, fn (PayPalDisputeReceived $event) => $event->isResolved() && $event->outcomeCode === 'RESOLVED_BUYER_FAVOUR');
    });

    it('processes a dispute event id only once and stores the dispute id as resource id', function () use ($disputeBody) {
        overrideTestFake(['*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200)]);
        Event::fake([PayPalDisputeReceived::class, PayPalWebhookReceived::class]);
        $body = $disputeBody();

        $this->postJson('paypal/webhook', $body, overrideTestWebhookHeaders())->assertOk()->assertJson(['status' => 'ok']);
        $this->postJson('paypal/webhook', $body, overrideTestWebhookHeaders())->assertOk()->assertJson(['status' => 'duplicate']);

        Event::assertDispatchedTimes(PayPalDisputeReceived::class, 1);
        expect(DB::table(config('paypal.database.webhook_event_table'))->where('event_id', $body['id'])->value('resource_id'))->toBe('PP-D-100');
    });

    it('tolerates a dispute without amount and transactions', function () {
        overrideTestFake(['*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200)]);
        Event::fake([PayPalDisputeReceived::class, PayPalWebhookReceived::class]);

        $this->postJson('paypal/webhook', ['id' => 'WH-EVT-X', 'event_type' => 'CUSTOMER.DISPUTE.UPDATED', 'create_time' => '2026-10-07T10:00:00Z', 'resource' => ['dispute_id' => 'PP-D-200']], overrideTestWebhookHeaders())->assertOk();

        Event::assertDispatched(PayPalDisputeReceived::class, fn (PayPalDisputeReceived $event) => $event->amount === null && $event->transactionIds === [] && $event->disputeId === 'PP-D-200');
    });
});
