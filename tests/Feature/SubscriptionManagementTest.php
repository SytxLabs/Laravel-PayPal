<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use SytxLabs\PayPal\Enums\DTO\Subscription\IntervalUnit;
use SytxLabs\PayPal\Enums\DTO\Subscription\PlanStatus;
use SytxLabs\PayPal\Enums\DTO\Subscription\SubscriptionStatus;
use SytxLabs\PayPal\Exception\CreatePlanException;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Subscription\Subscription as DTOSubscription;
use SytxLabs\PayPal\Models\Subscription;
use SytxLabs\PayPal\Services\PayPalSubscription;

class SubscriptionManagementTest extends Model
{
    protected $table = 'fake_subscribers';
    protected $guarded = [];
    public $timestamps = false;
}

function fakePayPalApi(array $extra = []): void
{
    Http::fake(array_merge([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'A-TOKEN', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's'], 200),
    ], $extra));
}

function paypalSubscriptionResource(string $id, string $status = 'ACTIVE', array $extra = []): array
{
    return array_merge([
        'id' => $id,
        'plan_id' => 'P-1',
        'status' => $status,
        'quantity' => '1',
        'billing_info' => [
            'outstanding_balance' => ['currency_code' => 'EUR', 'value' => '0.00'],
            'last_payment' => ['amount' => ['currency_code' => 'EUR', 'value' => '9.99'], 'time' => '2026-09-01T10:00:00Z'],
            'next_billing_time' => '2026-10-01T10:00:00Z',
            'failed_payments_count' => 0,
        ],
    ], $extra);
}

describe('subscribable', function () {
    it('links the stored subscription to a model', function () {
        fakePayPalApi(['*/v1/billing/subscriptions' => Http::response(['id' => 'I-SUB', 'status' => 'APPROVAL_PENDING', 'links' => []], 201)]);
        $user = (new FakeSubscriber())->forceFill(['id' => 7]);

        (new PayPalSubscription())->setPlanId('P-1')->setSubscribable($user)->createSubscription();

        $row = Subscription::query()->firstWhere('subscription_id', 'I-SUB');
        expect($row->subscribable_type)->toBe(FakeSubscriber::class)
            ->and((string) $row->subscribable_id)->toBe('7')
            ->and(Subscription::query()->subscribable($user)->pluck('subscription_id')->all())->toBe(['I-SUB']);
    });
});

describe('billing info', function () {
    it('stores billing info from PayPal', function () {
        fakePayPalApi(['*/v1/billing/subscriptions/I-B' => Http::response(paypalSubscriptionResource('I-B'), 200)]);

        $dto = (new PayPalSubscription())->getSubscriptionFromPayPal('I-B');

        expect($dto->getBillingInfo()->getNextBillingTime())->toBe('2026-10-01T10:00:00Z')
            ->and($dto->getBillingInfo()->getLastPayment()->getAmount()->getValue())->toBe('9.99');

        $row = Subscription::query()->firstWhere('subscription_id', 'I-B');
        expect($row->next_billing_time->toIso8601ZuluString())->toBe('2026-10-01T10:00:00Z')
            ->and($row->last_payment_time->toIso8601ZuluString())->toBe('2026-09-01T10:00:00Z')
            ->and($row->last_payment_amount)->toBe('9.99')
            ->and($row->last_payment_currency)->toBe('EUR')
            ->and($row->failed_payments_count)->toBe(0)
            ->and($row->quantity)->toBe('1')
            ->and($row->isActive())->toBeTrue();
    });

    it('rebuilds the approve link from a stored subscription', function () {
        fakePayPalApi(['*/v1/billing/subscriptions' => Http::response([
            'id' => 'I-LINK',
            'status' => 'APPROVAL_PENDING',
            'links' => [['href' => 'https://approve/I-LINK', 'rel' => 'approve', 'method' => 'GET']],
        ], 201)]);
        (new PayPalSubscription())->setPlanId('P-1')->createSubscription();

        $service = new PayPalSubscription();
        $service->getSubscriptionFromId('I-LINK');

        expect($service->getApproveSubscriptionRoute())->toBe('https://approve/I-LINK');
    });
});

describe('approval return', function () {
    it('fetches and stores the subscription from the return request', function () {
        Subscription::query()->create(['subscription_id' => 'I-RET', 'plan_id' => 'P-1', 'status' => SubscriptionStatus::APPROVAL_PENDING, 'request_id' => 'req-9']);
        fakePayPalApi(['*/v1/billing/subscriptions/I-RET' => Http::response(paypalSubscriptionResource('I-RET'), 200)]);

        $request = Request::create('/paypal/success', 'GET', ['subscription_id' => 'I-RET', 'ba_token' => 'BA-1', 'token' => 'T-1']);
        $sub = (new PayPalSubscription())->handleApprovalReturn($request);

        $row = Subscription::query()->firstWhere('subscription_id', 'I-RET');
        expect($sub->getStatus())->toBe(SubscriptionStatus::ACTIVE)
            ->and($row->status)->toBe(SubscriptionStatus::ACTIVE)
            ->and($row->request_id)->toBe('req-9');
    });

    it('accepts a plain array', function () {
        fakePayPalApi(['*/v1/billing/subscriptions/I-ARR' => Http::response(paypalSubscriptionResource('I-ARR'), 200)]);

        expect((new PayPalSubscription())->handleApprovalReturn(['subscription_id' => 'I-ARR'])->getId())->toBe('I-ARR');
    });

    it('throws without a subscription id', function () {
        (new PayPalSubscription())->handleApprovalReturn(Request::create('/paypal/success'));
    })->throws(RuntimeException::class, 'No subscription_id');
});

describe('revise', function () {
    it('changes the plan and stores the new approve link', function () {
        Subscription::query()->create(['subscription_id' => 'I-REV', 'plan_id' => 'P-OLD', 'status' => SubscriptionStatus::ACTIVE]);
        fakePayPalApi(['*/v1/billing/subscriptions/I-REV/revise' => Http::response([
            'plan_id' => 'P-NEW',
            'links' => [['href' => 'https://approve/revise', 'rel' => 'approve', 'method' => 'GET']],
        ], 200)]);

        $service = new PayPalSubscription();
        $service->getSubscriptionFromId('I-REV');
        $needsApproval = $service->revise('P-NEW', '2');

        expect($needsApproval)->toBeTrue()
            ->and($service->getApproveSubscriptionRoute())->toBe('https://approve/revise')
            ->and(Subscription::query()->firstWhere('subscription_id', 'I-REV')->plan_id)->toBe('P-OLD');
        Http::assertSent(function ($request) {
            if (!str_ends_with($request->url(), '/I-REV/revise')) {
                return false;
            }
            $data = json_decode($request->body(), true);
            return $data['plan_id'] === 'P-NEW' && $data['quantity'] === '2' && isset($data['application_context']['return_url']);
        });
    });

    it('reports no approval when PayPal applies the change directly', function () {
        fakePayPalApi(['*/v1/billing/subscriptions/I-REV/revise' => Http::response(['quantity' => '3', 'links' => []], 200)]);

        expect((new PayPalSubscription())->setSubscription((new DTOSubscription())->setId('I-REV'))->revise(quantity: '3'))->toBeFalse();
    });

    it('throws without anything to revise', function () {
        fakePayPalApi();

        (new PayPalSubscription())->setSubscription((new DTOSubscription())->setId('I-REV'))->revise();
    })->throws(RuntimeException::class, 'Nothing to revise');

    it('throws when PayPal rejects the revision', function () {
        fakePayPalApi(['*/v1/billing/subscriptions/I-REV/revise' => Http::response(['name' => 'UNPROCESSABLE_ENTITY'], 422)]);

        (new PayPalSubscription())->setSubscription((new DTOSubscription())->setId('I-REV'))->revise('P-NEW');
    })->throws(RuntimeException::class, 'Failed to revise subscription');
});

describe('outstanding balance and transactions', function () {
    it('captures the outstanding balance', function () {
        fakePayPalApi(['*/v1/billing/subscriptions/I-CAP/capture' => Http::response(['id' => 'TX-1', 'status' => 'COMPLETED'], 202)]);

        $result = (new PayPalSubscription())->setSubscription((new DTOSubscription())->setId('I-CAP'))->captureOutstandingBalance(new Money('EUR', '19.98'), 'Missed payments');

        expect($result['id'])->toBe('TX-1');
        Http::assertSent(function ($request) {
            $data = json_decode($request->body(), true);
            return str_ends_with($request->url(), '/I-CAP/capture')
                && $data['capture_type'] === 'OUTSTANDING_BALANCE'
                && $data['note'] === 'Missed payments'
                && $data['amount'] === ['currency_code' => 'EUR', 'value' => '19.98']
                && $request->hasHeader('PayPal-Request-Id');
        });
    });

    it('throws when the capture fails', function () {
        fakePayPalApi(['*/v1/billing/subscriptions/I-CAP/capture' => Http::response(['name' => 'ERR'], 422)]);

        (new PayPalSubscription())->setSubscription((new DTOSubscription())->setId('I-CAP'))->captureOutstandingBalance(new Money('EUR', '1.00'));
    })->throws(RuntimeException::class, 'Failed to capture outstanding balance');

    it('lists transactions in the given range', function () {
        fakePayPalApi(['*/v1/billing/subscriptions/I-TX/transactions*' => Http::response(['transactions' => [
            ['id' => 'TX-1', 'status' => 'COMPLETED'],
            ['id' => 'TX-2', 'status' => 'COMPLETED'],
        ]], 200)]);

        $transactions = (new PayPalSubscription())
            ->setSubscription((new DTOSubscription())->setId('I-TX'))
            ->listTransactions(Carbon::parse('2026-01-01 00:00:00', 'UTC'), '2026-09-30T00:00:00Z');

        expect(array_column($transactions, 'id'))->toBe(['TX-1', 'TX-2']);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/I-TX/transactions')
            && $request->data()['start_time'] === '2026-01-01T00:00:00Z'
            && $request->data()['end_time'] === '2026-09-30T00:00:00Z');
    });

    it('requires a subscription', function () {
        fakePayPalApi();

        (new PayPalSubscription())->listTransactions('2026-01-01T00:00:00Z');
    })->throws(RuntimeException::class, 'Subscription not found');
});

describe('plans', function () {
    it('fetches a plan', function () {
        fakePayPalApi(['*/v1/billing/plans/P-7' => Http::response([
            'id' => 'P-7',
            'product_id' => 'PROD-1',
            'name' => 'Pro',
            'status' => 'ACTIVE',
            'billing_cycles' => [[
                'frequency' => ['interval_unit' => 'MONTH', 'interval_count' => 1],
                'tenure_type' => 'REGULAR',
                'sequence' => 1,
                'total_cycles' => 0,
                'pricing_scheme' => ['fixed_price' => ['currency_code' => 'EUR', 'value' => '9.99']],
            ]],
        ], 200)]);

        $service = new PayPalSubscription();
        $plan = $service->getPlanFromPayPal('P-7');

        expect($plan->getId())->toBe('P-7')
            ->and($plan->getStatus())->toBe(PlanStatus::ACTIVE)
            ->and($plan->getBillingCycles()[0]->getPricingScheme()->getFixedPrice()->getValue())->toBe('9.99')
            ->and($service->getPlanId())->toBe('P-7');
    });

    it('lists plans of a product', function () {
        fakePayPalApi(['*/v1/billing/plans?*' => Http::response(['plans' => [['id' => 'P-1', 'name' => 'A'], ['id' => 'P-2', 'name' => 'B']]], 200)]);

        $plans = (new PayPalSubscription())->listPlans('PROD-1');

        expect(array_map(static fn ($plan) => $plan->getId(), $plans))->toBe(['P-1', 'P-2']);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/billing/plans') && ($request->data()['product_id'] ?? null) === 'PROD-1');
    });

    it('activates and deactivates a plan', function (string $action) {
        fakePayPalApi(["*/v1/billing/plans/P-1/$action" => Http::response('', 204)]);

        (new PayPalSubscription())->setPlanId('P-1')->{$action . 'Plan'}();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), "/v1/billing/plans/P-1/$action") && $request->method() === 'POST');
    })->with(['activate', 'deactivate']);

    it('updates plan pricing per billing cycle', function () {
        fakePayPalApi(['*/v1/billing/plans/P-1/update-pricing-schemes' => Http::response('', 204)]);

        (new PayPalSubscription())->updatePlanPricing([2 => new Money('EUR', '12.99')], 'P-1');

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/update-pricing-schemes')
            && json_decode($request->body(), true)['pricing_schemes'] === [['billing_cycle_sequence' => 2, 'pricing_scheme' => ['fixed_price' => ['currency_code' => 'EUR', 'value' => '12.99']]]]);
    });

    it('throws without a plan id', function () {
        fakePayPalApi();

        (new PayPalSubscription())->deactivatePlan();
    })->throws(RuntimeException::class, 'No plan id');

    it('throws when PayPal rejects a plan action', function () {
        fakePayPalApi(['*/v1/billing/plans/P-1/deactivate' => Http::response(['name' => 'ERR'], 422)]);

        (new PayPalSubscription())->deactivatePlan('P-1');
    })->throws(RuntimeException::class, 'Failed to deactivate plan');
});

describe('logging', function () {
    it('throws the typed exception when logging is disabled', function () {
        fakePayPalApi(['*/v1/billing/plans' => Http::response(['name' => 'INVALID_REQUEST'], 400)]);

        (new PayPalSubscription())->setProductId('PROD-1')->setRecurringPrice(new Money('EUR', '9.99'), IntervalUnit::MONTH)->createPlan();
    })->throws(CreatePlanException::class);

    it('logs failures when logging is enabled', function () {
        $logger = Mockery::spy(LoggerInterface::class);
        Log::shouldReceive('channel')->with('stack')->andReturn($logger);
        fakePayPalApi(['*/v1/billing/plans' => Http::response(['name' => 'INVALID_REQUEST'], 400)]);

        $config = array_replace_recursive(config('paypal'), ['logging' => ['enabled' => true, 'channel' => 'stack', 'level' => 'error']]);
        try {
            (new PayPalSubscription($config))->setProductId('PROD-1')->setRecurringPrice(new Money('EUR', '9.99'), IntervalUnit::MONTH)->createPlan();
        } catch (CreatePlanException) {
        }

        $logger->shouldHaveReceived('error')->withArgs(fn ($message, $context) => str_starts_with($message, 'CreatePlanException') && isset($context['response']));
    });

    it('throws when no OAuth token can be obtained', function () {
        Http::fake(['*/v1/oauth2/token' => Http::response(['error' => 'invalid_client'], 401)]);

        (new PayPalSubscription())->setPlanId('P-1')->createSubscription();
    })->throws(RuntimeException::class, 'Failed to get OAuth token');
});
