<?php

use Illuminate\Support\Facades\Http;
use SytxLabs\PayPal\Enums\DTO\Subscription\SubscriptionStatus;
use SytxLabs\PayPal\Models\Subscription;

function fakePayPalForCommand(array $extra = []): void
{
    Http::fake(array_merge([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'A-TOKEN', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's'], 200),
    ], $extra));
}

function seedCommandSubscriptions(): void
{
    Subscription::query()->create(['subscription_id' => 'I-A', 'plan_id' => 'P-1', 'custom_id' => 'user-1', 'status' => SubscriptionStatus::ACTIVE, 'next_billing_time' => now()->subHours(2)]);
    Subscription::query()->create(['subscription_id' => 'I-B', 'plan_id' => 'P-2', 'custom_id' => 'user-2', 'status' => SubscriptionStatus::ACTIVE, 'next_billing_time' => now()->subHour()]);
    Subscription::query()->create(['subscription_id' => 'I-C', 'plan_id' => 'P-1', 'custom_id' => 'user-3', 'status' => SubscriptionStatus::SUSPENDED, 'failed_payments_count' => 3, 'next_billing_time' => now()->subHour()]);
}

function fakeDueSubscriptions(): void
{
    $response = static fn (string $id, string $status = 'ACTIVE') => Http::response(['id' => $id, 'plan_id' => 'P-1', 'status' => $status], 200);
    fakePayPalForCommand([
        '*/v1/billing/subscriptions/I-A' => $response('I-A'),
        '*/v1/billing/subscriptions/I-B' => $response('I-B'),
        '*/v1/billing/subscriptions/I-C' => $response('I-C', 'SUSPENDED'),
    ]);
}

function fetchedSubscriptionIds(): array
{
    return Http::recorded()
        ->map(fn ($pair) => $pair[0]->url())
        ->filter(fn ($url) => str_contains($url, '/v1/billing/subscriptions/'))
        ->map(fn ($url) => substr($url, strrpos($url, '/') + 1))
        ->values()
        ->all();
}

describe('scheduler filters', function () {
    it('filters due subscriptions by status, plan and custom id', function () {
        seedCommandSubscriptions();

        fakeDueSubscriptions();
        $this->artisan('paypal:subscription', ['--status' => 'suspended'])->assertSuccessful();
        expect(fetchedSubscriptionIds())->toBe(['I-C']);

        fakeDueSubscriptions();
        $this->artisan('paypal:subscription', ['--plan' => 'P-2'])->assertSuccessful();
        expect(fetchedSubscriptionIds())->toBe(['I-B']);

        fakeDueSubscriptions();
        $this->artisan('paypal:subscription', ['--custom-id' => 'user-1'])->assertSuccessful();
        expect(fetchedSubscriptionIds())->toBe(['I-A']);
    });

    it('fetches the longest overdue first and respects the limit', function () {
        seedCommandSubscriptions();
        fakeDueSubscriptions();

        $this->artisan('paypal:subscription', ['--limit' => 1])->assertSuccessful();

        expect(fetchedSubscriptionIds())->toBe(['I-A']);
    });

    it('rejects an unknown status', function () {
        $this->artisan('paypal:subscription', ['--status' => 'FOO'])
            ->expectsOutputToContain('Unknown status FOO')
            ->assertFailed();
    });
});

describe('scheduler run', function () {
    it('fetches only active subscriptions whose billing time has passed', function () {
        Subscription::query()->create(['subscription_id' => 'I-DUE', 'plan_id' => 'P-1', 'status' => SubscriptionStatus::ACTIVE, 'next_billing_time' => now()->subHour()]);
        Subscription::query()->create(['subscription_id' => 'I-LATER', 'plan_id' => 'P-1', 'status' => SubscriptionStatus::ACTIVE, 'next_billing_time' => now()->addDay()]);
        Subscription::query()->create(['subscription_id' => 'I-CANCELLED', 'plan_id' => 'P-1', 'status' => SubscriptionStatus::CANCELLED, 'next_billing_time' => now()->subDay()]);
        fakePayPalForCommand(['*/v1/billing/subscriptions/I-DUE' => Http::response([
            'id' => 'I-DUE',
            'plan_id' => 'P-1',
            'status' => 'ACTIVE',
            'billing_info' => [
                'last_payment' => ['amount' => ['currency_code' => 'EUR', 'value' => '9.99'], 'time' => '2026-09-30T08:00:00Z'],
                'next_billing_time' => '2026-10-30T08:00:00Z',
                'failed_payments_count' => 0,
            ],
        ], 200)]);

        $this->artisan('paypal:subscription')
            ->expectsOutputToContain('I-DUE')
            ->doesntExpectOutputToContain('I-LATER')
            ->doesntExpectOutputToContain('I-CANCELLED')
            ->assertSuccessful();

        $row = Subscription::query()->firstWhere('subscription_id', 'I-DUE');
        expect($row->last_payment_amount)->toBe('9.99')
            ->and($row->next_billing_time->toIso8601ZuluString())->toBe('2026-10-30T08:00:00Z');
        Http::assertSentCount(2); // token + I-DUE only
    });

    it('reports when nothing is due', function () {
        Subscription::query()->create(['subscription_id' => 'I-LATER', 'plan_id' => 'P-1', 'status' => SubscriptionStatus::ACTIVE, 'next_billing_time' => now()->addDay()]);

        $this->artisan('paypal:subscription')
            ->expectsOutput('No subscriptions due.')
            ->assertSuccessful();
    });

    it('fails when a due subscription cannot be fetched', function () {
        Subscription::query()->create(['subscription_id' => 'I-DUE', 'plan_id' => 'P-1', 'status' => SubscriptionStatus::ACTIVE, 'next_billing_time' => now()->subHour()]);
        fakePayPalForCommand(['*/v1/billing/subscriptions/I-DUE' => Http::response(['name' => 'INTERNAL_SERVER_ERROR'], 500)]);

        $this->artisan('paypal:subscription')
            ->expectsOutputToContain('Could not refresh I-DUE')
            ->assertFailed();
    });
});

describe('show', function () {
    it('shows a stored subscription', function () {
        seedCommandSubscriptions();

        $this->artisan('paypal:subscription', ['id' => 'I-C'])
            ->expectsOutputToContain('SUSPENDED')
            ->expectsOutputToContain('user-3')
            ->expectsOutputToContain('Stored data')
            ->assertSuccessful();
    });

    it('shows the live state from PayPal', function () {
        fakePayPalForCommand(['*/v1/billing/subscriptions/I-LIVE' => Http::response([
            'id' => 'I-LIVE',
            'plan_id' => 'P-9',
            'status' => 'ACTIVE',
            'subscriber' => ['email_address' => 'kunde@example.com'],
            'billing_info' => [
                'outstanding_balance' => ['currency_code' => 'EUR', 'value' => '0.00'],
                'last_payment' => ['amount' => ['currency_code' => 'EUR', 'value' => '9.99'], 'time' => '2026-09-01T10:00:00Z'],
                'next_billing_time' => '2026-10-01T10:00:00Z',
                'failed_payments_count' => 0,
            ],
        ], 200)]);

        $this->artisan('paypal:subscription', ['id' => 'I-LIVE', '--refresh' => true])
            ->expectsOutputToContain('P-9')
            ->expectsOutputToContain('kunde@example.com')
            ->expectsOutputToContain('2026-10-01T10:00:00Z')
            ->expectsOutputToContain('9.99 EUR')
            ->doesntExpectOutputToContain('Stored data')
            ->assertSuccessful();

        expect(Subscription::query()->where('subscription_id', 'I-LIVE')->exists())->toBeTrue();
    });

    it('fails for an unknown subscription', function () {
        $this->artisan('paypal:subscription', ['id' => 'I-NOPE'])
            ->expectsOutputToContain('Subscription I-NOPE not found')
            ->assertFailed();
    });

    it('fails when PayPal cannot be reached', function () {
        fakePayPalForCommand(['*/v1/billing/subscriptions/I-X' => Http::response(['name' => 'RESOURCE_NOT_FOUND'], 404)]);

        $this->artisan('paypal:subscription', ['id' => 'I-X', '--refresh' => true])
            ->expectsOutputToContain('Could not fetch subscription from PayPal')
            ->assertFailed();
    });
});
