<?php

use Illuminate\Support\Facades\Http;
use SytxLabs\PayPal\Enums\DTO\Subscription\CatalogProductType;
use SytxLabs\PayPal\Enums\DTO\Subscription\IntervalUnit;
use SytxLabs\PayPal\Enums\DTO\Subscription\SubscriptionStatus;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Product;
use SytxLabs\PayPal\Models\DTO\Subscription\CatalogProduct;
use SytxLabs\PayPal\Models\DTO\Subscription\Subscriber;
use SytxLabs\PayPal\Models\Subscription;
use SytxLabs\PayPal\Services\PayPalSubscription;

function fakePayPalHappyPath(): void
{
    Http::fake([
        '*/v1/oauth2/token' => Http::response([
            'access_token' => 'A-TOKEN',
            'token_type' => 'Bearer',
            'expires_in' => 3200,
            'scope' => 'https://uri.paypal.com/services/subscriptions',
        ], 200),
        '*/v1/catalogs/products' => Http::response(['id' => 'PROD-1', 'name' => 'Pro Service'], 201),
        '*/v1/billing/plans' => Http::response(['id' => 'P-1', 'status' => 'ACTIVE'], 201),
        '*/v1/billing/subscriptions' => Http::response([
            'id' => 'I-1',
            'status' => 'APPROVAL_PENDING',
            'create_time' => '2024-01-01T00:00:00Z',
            'links' => [
                ['href' => 'https://www.sandbox.paypal.com/approve?token=I-1', 'rel' => 'approve', 'method' => 'GET'],
                ['href' => 'https://api-m.sandbox.paypal.com/v1/billing/subscriptions/I-1', 'rel' => 'self', 'method' => 'GET'],
            ],
        ], 201),
    ]);
}

it('creates product -> plan -> subscription and returns a single approve link', function () {
    fakePayPalHappyPath();

    $sub = (new PayPalSubscription())
        ->setCatalogProduct((new CatalogProduct())->setName('Pro Service')->setType(CatalogProductType::SERVICE))
        ->setRecurringPrice(new Money('EUR', '9.99'), IntervalUnit::MONTH, 1)
        ->addOneTimeProduct(new Product('Setup', 49.00, 1, 'EUR'))
        ->addOneTimeProduct(new Product('Hardware', 20.00, 1, 'EUR'))
        ->setSubscriber((new Subscriber())->setEmailAddress('kunde@example.com'))
        ->createSubscription();

    expect($sub->getSubscription()->getId())->toBe('I-1')
        ->and($sub->getSubscription()->getStatus())->toBe(SubscriptionStatus::APPROVAL_PENDING)
        ->and($sub->getApproveSubscriptionRoute())->toBe('https://www.sandbox.paypal.com/approve?token=I-1');
});

it('sends the sum of one-time products as the plan setup_fee', function () {
    fakePayPalHappyPath();

    (new PayPalSubscription())
        ->setCatalogProduct((new CatalogProduct())->setName('Pro Service'))
        ->setRecurringPrice(new Money('EUR', '9.99'), IntervalUnit::MONTH, 1)
        ->addOneTimeProduct(new Product('Setup', 49.00, 1, 'EUR'))
        ->addOneTimeProduct(new Product('Hardware', 20.00, 1, 'EUR'))
        ->createSubscription();

    Http::assertSent(function ($request) {
        if (!str_contains($request->url(), '/v1/billing/plans')) {
            return false;
        }
        $data = json_decode($request->body(), true);
        return ($data['payment_preferences']['setup_fee']['value'] ?? null) === '69.00'
            && ($data['payment_preferences']['setup_fee']['currency_code'] ?? null) === 'EUR'
            && ($data['product_id'] ?? null) === 'PROD-1';
    });
});

it('persists the subscription to the database', function () {
    fakePayPalHappyPath();

    (new PayPalSubscription())
        ->setCatalogProduct((new CatalogProduct())->setName('Pro Service'))
        ->setRecurringPrice(new Money('EUR', '9.99'), IntervalUnit::MONTH, 1)
        ->addOneTimeProduct(new Product('Setup', 49.00, 1, 'EUR'))
        ->createSubscription();

    $row = Subscription::query()->firstWhere('subscription_id', 'I-1');
    expect($row)->not->toBeNull()
        ->and($row->plan_id)->toBe('P-1')
        ->and($row->product_id)->toBe('PROD-1')
        ->and($row->status)->toBe(SubscriptionStatus::APPROVAL_PENDING);
});

it('reuses an existing plan id and skips product/plan creation', function () {
    Http::fake([
        '*/v1/oauth2/token' => Http::response([
            'access_token' => 'A-TOKEN', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's',
        ], 200),
        '*/v1/billing/subscriptions' => Http::response([
            'id' => 'I-2', 'status' => 'APPROVAL_PENDING', 'create_time' => '2024-01-01T00:00:00Z',
            'links' => [['href' => 'https://approve/I-2', 'rel' => 'approve', 'method' => 'GET']],
        ], 201),
    ]);

    (new PayPalSubscription())
        ->setPlanId('P-EXISTING')
        ->setSubscriber((new Subscriber())->setEmailAddress('kunde@example.com'))
        ->createSubscription();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/v1/catalogs/products'));
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/v1/billing/plans'));
    Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/billing/subscriptions')
        && (json_decode($request->body(), true)['plan_id'] ?? null) === 'P-EXISTING');
});
