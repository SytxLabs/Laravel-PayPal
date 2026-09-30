<?php

use Illuminate\Support\Facades\Http;
use SytxLabs\PayPal\Enums\DTO\Subscription\IntervalUnit;
use SytxLabs\PayPal\Enums\DTO\Subscription\SubscriptionStatus;
use SytxLabs\PayPal\Exception\CreateCatalogProductException;
use SytxLabs\PayPal\Exception\CreatePlanException;
use SytxLabs\PayPal\Exception\CreateSubscriptionException;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Product;
use SytxLabs\PayPal\Models\DTO\Subscription\CatalogProduct;
use SytxLabs\PayPal\Models\DTO\Subscription\RecurringPrice;
use SytxLabs\PayPal\Models\DTO\Subscription\Subscriber;
use SytxLabs\PayPal\Models\DTO\Subscription\Subscription as DTOSubscription;
use SytxLabs\PayPal\Models\Subscription;
use SytxLabs\PayPal\Services\PayPalSubscription;

function fakePayPal(array $extra = []): void
{
    Http::fake(array_merge([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'A-TOKEN', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's'], 200),
    ], $extra));
}

function approvedSubscriptionResponse(string $id = 'I-1')
{
    return Http::response([
        'id' => $id,
        'status' => 'APPROVAL_PENDING',
        'create_time' => '2024-01-01T00:00:00Z',
        'links' => [['href' => "https://www.sandbox.paypal.com/approve?token=$id", 'rel' => 'approve', 'method' => 'GET']],
    ], 201);
}

describe('validation and errors', function () {
    it('throws when creating a plan without billing cycles', function () {
        fakePayPal();

        (new PayPalSubscription())->setCatalogProduct((new CatalogProduct())->setName('Pro'))->createPlan();
    })->throws(RuntimeException::class, 'No plan with billing cycles');

    it('throws when creating a catalog product without one set', function () {
        fakePayPal();

        (new PayPalSubscription())->createCatalogProduct();
    })->throws(RuntimeException::class, 'No catalog product set');

    it('throws CreateCatalogProductException when PayPal rejects the product', function () {
        fakePayPal(['*/v1/catalogs/products' => Http::response(['name' => 'INVALID_REQUEST'], 400)]);

        (new PayPalSubscription())->setCatalogProduct((new CatalogProduct())->setName('Pro'))->createCatalogProduct();
    })->throws(CreateCatalogProductException::class);

    it('throws CreatePlanException when PayPal rejects the plan', function () {
        fakePayPal(['*/v1/billing/plans' => Http::response(['name' => 'INVALID_REQUEST'], 422)]);

        (new PayPalSubscription())
            ->setProductId('PROD-1')
            ->setRecurringPrice(new Money('EUR', '9.99'), IntervalUnit::MONTH)
            ->createPlan();
    })->throws(CreatePlanException::class);

    it('throws CreateSubscriptionException and stores nothing when PayPal rejects the subscription', function () {
        fakePayPal(['*/v1/billing/subscriptions' => Http::response(['name' => 'INVALID_REQUEST'], 400)]);

        try {
            (new PayPalSubscription())->setPlanId('P-1')->createSubscription();
            $this->fail('Expected CreateSubscriptionException');
        } catch (CreateSubscriptionException $e) {
            expect($e->getResponse()['status'])->toBe(400);
        }

        expect(Subscription::query()->count())->toBe(0);
    });

    it('throws when reading the approve link before a subscription exists', function () {
        (new PayPalSubscription())->getApproveSubscriptionRoute();
    })->throws(RuntimeException::class, 'Subscription not found');

    it('throws when the subscription has no approve link', function () {
        fakePayPal(['*/v1/billing/subscriptions' => Http::response([
            'id' => 'I-5',
            'status' => 'APPROVAL_PENDING',
            'links' => [['href' => 'https://self', 'rel' => 'self', 'method' => 'GET']],
        ], 201)]);

        (new PayPalSubscription())->setPlanId('P-1')->createSubscription()->getApproveSubscriptionRoute();
    })->throws(RuntimeException::class, 'No approve link');
});

describe('creation payload', function () {
    it('sends plan id, subscriber, custom id, return urls and a request id', function () {
        fakePayPal(['*/v1/billing/subscriptions' => approvedSubscriptionResponse()]);

        (new PayPalSubscription())
            ->setPlanId('P-9')
            ->setCustomId('user-42')
            ->setSubscriber((new Subscriber())->setEmailAddress('kunde@example.com'))
            ->createSubscription();

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/v1/billing/subscriptions')) {
                return false;
            }
            $data = json_decode($request->body(), true);
            return $data['plan_id'] === 'P-9'
                && $data['custom_id'] === 'user-42'
                && $data['subscriber']['email_address'] === 'kunde@example.com'
                && $data['application_context']['return_url'] === 'https://example.com/success'
                && $data['application_context']['cancel_url'] === 'https://example.com/cancel'
                && $request->hasHeader('PayPal-Request-Id');
        });
    });

    it('omits the setup fee when no one-time products are set', function () {
        fakePayPal([
            '*/v1/billing/plans' => Http::response(['id' => 'P-1', 'status' => 'ACTIVE'], 201),
            '*/v1/billing/subscriptions' => approvedSubscriptionResponse(),
        ]);

        (new PayPalSubscription())
            ->setProductId('PROD-1')
            ->setRecurringPrice(new Money('EUR', '9.99'), IntervalUnit::MONTH)
            ->createSubscription();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/billing/plans') && !isset(json_decode($request->body(), true)['payment_preferences']['setup_fee']));
    });

    it('sums one-time products and lets setOneTimeProduct replace them', function () {
        $service = (new PayPalSubscription())
            ->addOneTimeProduct(new Product('A', 10.00, 2, 'EUR'))
            ->addOneTimeProduct(new Product('B', 5.50, 1, 'EUR'));

        expect($service->getOneTimeTotal())->toBe(25.5);
        expect($service->setOneTimeProduct(new Product('C', 3.00, 1, 'EUR'))->getOneTimeTotal())->toBe(3.0);
    });

    it('replaces cycles on setRecurringPrice and appends on addRecurringPrice', function () {
        fakePayPal([
            '*/v1/billing/plans' => Http::response(['id' => 'P-1', 'status' => 'ACTIVE'], 201),
            '*/v1/billing/subscriptions' => approvedSubscriptionResponse(),
        ]);

        (new PayPalSubscription())
            ->setProductId('PROD-1')
            ->setRecurringPrice(new Money('EUR', '1.00'), IntervalUnit::WEEK)
            ->setRecurringPrice(new Money('EUR', '9.99'), IntervalUnit::MONTH)
            ->addRecurringPrice(new RecurringPrice(new Money('EUR', '99.00'), IntervalUnit::YEAR))
            ->createSubscription();

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/v1/billing/plans')) {
                return false;
            }
            $cycles = json_decode($request->body(), true)['billing_cycles'] ?? [];
            return count($cycles) === 2 && $cycles[0]['frequency']['interval_unit'] === 'MONTH' && $cycles[1]['sequence'] === 2;
        });
    });
});

describe('loading and status', function () {
    it('defaults the status to APPROVAL_PENDING and reports the stored one', function () {
        $service = (new PayPalSubscription())->setSubscription((new DTOSubscription())->setId('I-1'));
        expect($service->getSubscriptionStatus())->toBe(SubscriptionStatus::APPROVAL_PENDING);

        $service->setSubscription((new DTOSubscription())->setId('I-1')->setStatus(SubscriptionStatus::ACTIVE));
        expect($service->getSubscriptionStatus())->toBe(SubscriptionStatus::ACTIVE);
    });

    it('throws for the status without a subscription', function () {
        (new PayPalSubscription())->getSubscriptionStatus();
    })->throws(RuntimeException::class, 'Subscription not found');

    it('loads a subscription from the database and falls back to a bare id', function () {
        Subscription::query()->create(['subscription_id' => 'I-DB', 'plan_id' => 'P-DB', 'status' => SubscriptionStatus::ACTIVE, 'request_id' => 'req-1']);

        $service = new PayPalSubscription();
        $found = $service->getSubscriptionFromId('I-DB');
        expect($found->getPlanId())->toBe('P-DB')->and($found->getStatus())->toBe(SubscriptionStatus::ACTIVE);

        $bare = $service->getSubscriptionFromId('I-UNKNOWN');
        expect($bare->getId())->toBe('I-UNKNOWN')->and($bare->getPlanId())->toBeNull();
    });

    it('accepts a subscription model in setSubscription', function () {
        $model = Subscription::query()->create(['subscription_id' => 'I-M', 'plan_id' => 'P-M', 'status' => SubscriptionStatus::SUSPENDED]);

        $service = (new PayPalSubscription())->setSubscription($model);

        expect($service->getSubscription()->getId())->toBe('I-M')->and($service->getSubscriptionStatus())->toBe(SubscriptionStatus::SUSPENDED);
    });

    it('refreshes a subscription from PayPal and updates the database', function () {
        Subscription::query()->create(['subscription_id' => 'I-R', 'plan_id' => 'P-R', 'status' => SubscriptionStatus::APPROVAL_PENDING]);
        fakePayPal(['*/v1/billing/subscriptions/I-R' => Http::response(['id' => 'I-R', 'plan_id' => 'P-R', 'status' => 'ACTIVE'], 200)]);

        $sub = (new PayPalSubscription())->getSubscriptionFromPayPal('I-R');

        expect($sub->getStatus())->toBe(SubscriptionStatus::ACTIVE)
            ->and(Subscription::query()->firstWhere('subscription_id', 'I-R')->status)->toBe(SubscriptionStatus::ACTIVE);
    });

    it('throws when PayPal does not know the subscription', function () {
        fakePayPal(['*/v1/billing/subscriptions/I-X' => Http::response(['name' => 'RESOURCE_NOT_FOUND'], 404)]);

        (new PayPalSubscription())->getSubscriptionFromPayPal('I-X');
    })->throws(RuntimeException::class);

    it('throws when refreshing without any subscription id', function () {
        fakePayPal();

        (new PayPalSubscription())->getSubscriptionFromPayPal();
    })->throws(RuntimeException::class, 'Subscription not found');
});

describe('lifecycle actions', function () {
    it('runs the action and syncs the new status', function (string $action, string $paypalStatus, SubscriptionStatus $expected) {
        Subscription::query()->create(['subscription_id' => 'I-L', 'plan_id' => 'P-L', 'status' => SubscriptionStatus::ACTIVE]);
        fakePayPal([
            "*/v1/billing/subscriptions/I-L/$action" => Http::response('', 204),
            '*/v1/billing/subscriptions/I-L' => Http::response(['id' => 'I-L', 'plan_id' => 'P-L', 'status' => $paypalStatus], 200),
        ]);

        $service = new PayPalSubscription();
        $service->getSubscriptionFromId('I-L');
        $service->{$action}('because');

        expect($service->getSubscriptionStatus())->toBe($expected)
            ->and(Subscription::query()->firstWhere('subscription_id', 'I-L')->status)->toBe($expected);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), "/I-L/$action") && ($request->data()['reason'] ?? null) === 'because');
    })->with([
        'suspend' => ['suspend', 'SUSPENDED', SubscriptionStatus::SUSPENDED],
        'cancel' => ['cancel', 'CANCELLED', SubscriptionStatus::CANCELLED],
        'activate' => ['activate', 'ACTIVE', SubscriptionStatus::ACTIVE],
    ]);

    it('uses a default reason', function () {
        fakePayPal([
            '*/v1/billing/subscriptions/I-L/cancel' => Http::response('', 204),
            '*/v1/billing/subscriptions/I-L' => Http::response(['id' => 'I-L', 'status' => 'CANCELLED'], 200),
        ]);

        (new PayPalSubscription())->setSubscription((new DTOSubscription())->setId('I-L'))->cancel();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/I-L/cancel') && ($request->data()['reason'] ?? null) === 'Cancel by merchant');
    });

    it('throws when PayPal rejects the action', function () {
        fakePayPal(['*/v1/billing/subscriptions/I-L/cancel' => Http::response(['name' => 'SUBSCRIPTION_STATUS_INVALID'], 422)]);

        (new PayPalSubscription())->setSubscription((new DTOSubscription())->setId('I-L'))->cancel();
    })->throws(RuntimeException::class);

    it('throws without a subscription', function () {
        fakePayPal();

        (new PayPalSubscription())->suspend();
    })->throws(RuntimeException::class, 'Subscription not found');
});
