# PayPal for Laravel

[![MIT Licensed](https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Check code style](https://github.com/SytxLabs/Laravel-PayPal/actions/workflows/code-style.yml/badge.svg?style=flat-square)](https://github.com/SytxLabs/Laravel-PayPal/actions/workflows/code-style.yml)
[![Latest Version on Packagist](https://poser.pugx.org/sytxlabs/laravel-paypal/v/stable?format=flat-square)](https://packagist.org/packages/sytxlabs/laravel-paypal)
[![Total Downloads](https://poser.pugx.org/sytxlabs/laravel-paypal/downloads?format=flat-square)](https://packagist.org/packages/sytxlabs/laravel-paypal)

This package adds a simple way to integrate PayPal payments into your Laravel application.

## Prerequisites

* A configured Laravel database connection
* PHP 8.2 or higher
* Laravel 10.0 or higher

## Installation

```sh
composer require sytxlabs/laravel-paypal
```

## Using
- [GuzzleHttp](https://packagist.org/packages/guzzlehttp/guzzle)
- [PayPal API Reference](https://developer.paypal.com/docs/api/overview/)
- [PayPal Account](https://developer.paypal.com/docs/api-basics/sandbox/accounts/)

## Configuration

```sh
php artisan vendor:publish --tag="sytxlabs-paypal-config"
```
the configuration file is located at `config/paypal.php`

## Optional Database

```sh
php artisan vendor:publish --tag="sytxlabs-paypal-migrations"
php artisan migrate
```

## Usage

### Create a new PayPal Order
```php
use SytxLabs\PayPal\PayPalOrder;

$paypalOrder = new PayPalOrder();

$paypalOrder->addProduct(new Product('Product 1', 10.00, 1));

$paypalOrder->createOrder();
```

### Redirect to PayPal
```php
$paypalOrder->approveOrderRedirect();
```
or get the approval link
```php
$paypalOrder->getApprovalLink();
```

### Capture the payment
```php
$paypalOrder->captureOrder();
```

### Check the payment status
```php
$paypalOrder->captureOrder()->getOrderStatus();
```

### Orders without the browser: read, capture, refund, verify a webhook

For a server that books payments on its own - from a webhook, a cron job that reconciles open orders - the order service has
stateless calls that work on ids and return what PayPal sent. They fetch their token like every other call, use the stored one
while it is valid and swap it once when PayPal rejects it.

```php
$orders = new PayPalOrder();

// the order as PayPal sent it (purchase units, payments, captures …)
$order = $orders->readOrder($orderId);

// capture; safe to call twice and from two places at once: the request id is derived from the order
// ("capture-{id}"), and an order PayPal reports as already captured is simply read again.
$order = $orders->captureOrderById($orderId);

// refund a capture of an order (full without amount); pass a request id of your own to repeat a call safely
$refund = $orders->refundCapture($captureId, new Money('EUR', '5.00'), 'Goodwill', 'INV-7', requestId: 'refund-INV-7');
```

`SytxLabs\PayPal\Support\OrderPayments` reads the money out of such an order, so you book what PayPal collected and not what a
browser or a payload claims:

```php
use SytxLabs\PayPal\Support\OrderPayments;

OrderPayments::approved($order);   // Money: what the payer approved (sum of the purchase units), null if unclear
OrderPayments::collected($order);  // Money: only a COMPLETED order, only its COMPLETED captures; null otherwise
OrderPayments::captures($order);   // [['id', 'status', 'reference_id' (purchase unit), 'amount', 'final_capture'], …]
OrderPayments::orderIdOf($webhookResource); // the order a capture/refund resource of a webhook belongs to
OrderPayments::sum(['0.10', '0.20']);       // "0.30" - decimal strings, no float noise
```

Any other REST call goes through `api()` (a client for the API host, built on first use, a copy per call):

```php
$response = $orders->api()->get('v2/payments/captures/' . $captureId);
```

`api()` and the calls above exist on `PayPal`, `PayPalOrder` and `PayPalSubscription`.

#### Verifying a webhook without subscriptions

`webhookSignatureStatus()` (every service, needs `webhook_id`) separates a **wrong** signature from a PayPal that **could not decide**,
which an application answers differently - 400, or 503 so that PayPal delivers the event again:

```php
use SytxLabs\PayPal\Enums\PayPalWebhookSignatureStatus;

$status = $orders->webhookSignatureStatus($request->headers->all(), $request->getContent());

return match ($status) {
    PayPalWebhookSignatureStatus::Valid => $handle(),
    PayPalWebhookSignatureStatus::Invalid => response('', 400),
    PayPalWebhookSignatureStatus::Unavailable => response('', 503),
};
```

Pass the **raw body** (`getContent()`): it is handed to PayPal byte for byte, because PayPal signed these bytes and a decoded and
re-encoded array can differ (empty objects, escapes, number formats). Requests that do not look like PayPal's - another algorithm
than `SHA256withRSA`, a certificate URL that is not on `api[-m][.sandbox].paypal.com`, a missing header, a body that is not JSON - are
`Invalid` without a call to PayPal.

## PayPal Subscriptions

Subscriptions let you charge a recurring amount. To combine a **one-time payment** with
the start of a subscription in a **single PayPal approval**, add the one-time products via
`addOneTimeProduct()` — their sum becomes the plan's `setup_fee`, which PayPal charges once
when the subscriber approves. Only the subscription create step produces an approve link,
so the user approves exactly once for both the one-time amount and the recurring plan.

The service orchestrates the full chain automatically: it creates the catalog product,
then the billing plan (with the `setup_fee`), then the subscription.

```php
use SytxLabs\PayPal\Services\PayPalSubscription;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Product;
use SytxLabs\PayPal\Models\DTO\Subscription\CatalogProduct;
use SytxLabs\PayPal\Models\DTO\Subscription\Subscriber;
use SytxLabs\PayPal\Enums\DTO\Subscription\CatalogProductType;
use SytxLabs\PayPal\Enums\DTO\Subscription\IntervalUnit;

$subscription = (new PayPalSubscription())
    ->setCatalogProduct((new CatalogProduct())->setName('Pro Service')->setType(CatalogProductType::SERVICE))
    ->setRecurringPrice(new Money('EUR', '9.99'), IntervalUnit::MONTH, 1) // recurring plan
    ->addOneTimeProduct(new Product('Setup / Onboarding', 49.00, 1, 'EUR')) // one-time -> setup_fee
    ->addOneTimeProduct(new Product('Hardware', 20.00, 1, 'EUR'))           // summed into setup_fee
    ->setSubscriber((new Subscriber())->setEmailAddress('customer@example.com'))
    ->createSubscription();

return $subscription->approveSubscriptionRedirect(); // single user approval
```

If you already have a PayPal plan, skip product/plan creation with `->setPlanId('P-XXX')`.

#### Multiple recurring prices

A plan can carry several billing cycles (e.g. a trial followed by the regular
price, or tiered pricing). Add them with `RecurringPrice` value objects; the
billing-cycle `sequence` is assigned automatically in insertion order.

```php
use SytxLabs\PayPal\Models\DTO\Subscription\RecurringPrice;
use SytxLabs\PayPal\Enums\DTO\Subscription\IntervalUnit;
use SytxLabs\PayPal\Enums\DTO\Subscription\TenureType;

$subscription = (new PayPalSubscription())
    ->setCatalogProduct((new CatalogProduct())->setName('Pro Service'))
    ->addRecurringPrices([
        new RecurringPrice(new Money('EUR', '0.00'), IntervalUnit::MONTH, 1, 1, TenureType::TRIAL), // 1-month free trial
        new RecurringPrice(new Money('EUR', '9.99'), IntervalUnit::MONTH),                          // regular monthly price
    ])
    ->setSubscriber((new Subscriber())->setEmailAddress('customer@example.com'))
    ->createSubscription();
```

`addRecurringPrice(RecurringPrice $price)` adds one, `addRecurringPrices([...])` adds many.
`setRecurringPrice(...)` is the single-value shorthand; it replaces previously set cycles.

### Link the subscription to a model
```php
(new PayPalSubscription())
    ->setPlanId('P-XXX')
    ->setSubscribable($user) // stored in the `subscribable` morph columns
    ->createSubscription();

\SytxLabs\PayPal\Models\Subscription::query()->subscribable($user)->get();
```

### Handle the return from PayPal
PayPal redirects the buyer to `success_route` with `?subscription_id=I-XXX`. Fetch and store the
current state there:
```php
public function success(Request $request)
{
    $subscription = (new PayPalSubscription())->handleApprovalReturn($request);
    // $subscription->getStatus() === SubscriptionStatus::ACTIVE
}
```

### Manage the subscription
```php
$subscription->getSubscriptionFromPayPal('I-XXX'); // also stores billing info (next billing, last payment, failed payments)
$subscription->getSubscriptionStatus();
$subscription->suspend('Customer request');
$subscription->activate();
$subscription->cancel('No longer needed');

// change plan / quantity; returns true if the buyer has to approve the change
if ($subscription->revise(planId: 'P-NEW', quantity: '2')) {
    return $subscription->approveSubscriptionRedirect();
}

$subscription->captureOutstandingBalance(new Money('EUR', '19.98'), 'Missed payments');
$subscription->listTransactions(now()->subMonth(), now());
```

### Delayed start, per-subscription prices, refunds
```php
// start at a later time: a setup fee is still charged at approval, regular billing starts at start_time
(new PayPalSubscription())->setPlanId('P-XXX')->setStartTime(now()->addDays(30))->createSubscription();

// price of one billing cycle for this subscription only (customer specific price, grandfathering)
(new PayPalSubscription())->setPlanId('P-XXX')
    ->overrideBillingCycle(2, new Money('EUR', '0.80'))      // cycle sequence 2: new fixed price
    ->overrideBillingCycle(1, new Money('EUR', '0.50'), 3)   // cycle sequence 1: price and number of cycles
    ->createSubscription();

// change a running subscription (PATCH): price or number of cycles of one billing cycle, start time (while in the future), raw operations
$subscription->overrideSubscriptionCyclePrice(2, new Money('EUR', '1.30'));
$subscription->overrideSubscriptionCycleTotal(1, 3);
$subscription->changeStartTime(now()->addDays(10));
$subscription->patchSubscription([['op' => 'replace', 'path' => '/custom_id', 'value' => 'customer-7']]);

// refund a captured payment by its capture id (full refund without amount)
$subscription->refundTransaction($captureId, new Money('EUR', '0.50'), 'Goodwill');
```

PayPal limits: at most **2 trial cycles** and exactly **one regular cycle** per plan (a cycle may span several periods via
`total_cycles`). A price change by PATCH does not affect billing cycles within the next 10 days (PayPal-funded subscriptions);
once a subscription is overridden, later plan changes do not affect it. There is no API to change the funding source of a
PayPal-funded subscription: create a new subscription with `setStartTime()` at the end of the paid period and cancel the old
one before its next billing time.

### Scheduler command
PayPal charges subscriptions on its own. `paypal:subscription` fetches every active subscription
whose next billing time has passed (longest overdue first) from PayPal and stores the result
(last payment, next billing time, failed payments, status) – a fallback for missed webhooks.

Add it to your scheduler:
```php
// routes/console.php (Laravel 11+) or app/Console/Kernel.php
Schedule::command('paypal:subscription')->hourly()->withoutOverlapping();
```

```bash
php artisan paypal:subscription                          # fetch all due active subscriptions
php artisan paypal:subscription --status=SUSPENDED       # due subscriptions with another status
php artisan paypal:subscription --plan=P-XXX --custom-id=user-1 --limit=100
php artisan paypal:subscription I-XXX                    # show one subscription from the database
php artisan paypal:subscription I-XXX --refresh          # show it live from PayPal (and store it)
```

The command exits with a failure code when a subscription could not be fetched from PayPal.

### Manage plans
```php
$service = new PayPalSubscription();
$service->listPlans('PROD-XXX');
$service->getPlanFromPayPal('P-XXX');
$service->updatePlanPricing([2 => new Money('EUR', '12.99')], 'P-XXX'); // key = billing cycle sequence
$service->deactivatePlan('P-XXX');
$service->activatePlan('P-XXX');
```

### Webhooks

Set `PAYPAL_WEBHOOK_ID` and enable the built-in route with
`PAYPAL_WEBHOOK_ROUTE_ENABLED=true` (path via `PAYPAL_WEBHOOK_PATH`, default
`paypal/webhook`). Incoming webhooks are signature-verified against PayPal and every event id
is handled only once (PayPal retries are answered with `{"status": "duplicate"}`).

| Event                                 | Stored                                       | Dispatched                           |
|---------------------------------------|----------------------------------------------|--------------------------------------|
| `BILLING.SUBSCRIPTION.*`              | status, plan, quantity, billing info         | `PayPalWebhookReceived`              |
| `BILLING.SUBSCRIPTION.PAYMENT.FAILED` | `failed_payments_count`                      | `PayPalSubscriptionPaymentFailed`    |
| `PAYMENT.SALE.COMPLETED`              | last payment, resets `failed_payments_count` | `PayPalSubscriptionPaymentCompleted` |
| `PAYMENT.SALE.REFUNDED` / `REVERSED`  | –                                            | `PayPalSubscriptionPaymentRefunded`  |
| `CUSTOMER.DISPUTE.CREATED` / `UPDATED` / `RESOLVED` | –                              | `PayPalDisputeReceived` (dispute id, status, reason, life cycle stage, outcome code, amount, disputed transaction ids) |

`PayPalWebhookReceived` is dispatched for every event and carries the stored subscription
(`$event->subscription`) when one matches. All events live in `SytxLabs\PayPal\Events`.

Processing guarantees:

- **Once per event id:** the event claim and all database changes run in one transaction. A failure
  or crash rolls both back and PayPal's retry is processed again; a retry of a committed event is
  answered with `duplicate`.
- **Events after commit:** the listed events are dispatched after the transaction. The event is
  already marked as processed then, so a failing listener is *not* retried by PayPal and a duplicate
  delivery does not dispatch it again. Queued listeners (`ShouldQueue`) can retry their work, but only
  once the job was enqueued: a crash or enqueue failure after commit can still lose the event.
- **No out-of-order regressions:** every stored state remembers PayPal's `update_time`
  (`paypal_update_time`). An older webhook or API response does not overwrite a newer status, e.g.
  a delayed `ACTIVATED` after `CANCELLED`. Older sales do not replace the stored last payment.
- **Event table required:** while the `sytxlabs_paypal_webhook_events` table is missing (or the
  database is disabled) webhooks are rejected with `503`, so PayPal retries them later instead of
  being processed twice. Set `PAYPAL_WEBHOOK_DEDUPLICATE=false` to accept webhooks without it.

Processed event ids are stored in the `sytxlabs_paypal_webhook_events` table (configurable via
`database.webhook_event_table`). Publish and run the migrations after updating:
```bash
php artisan vendor:publish --tag=sytxlabs-paypal-migrations
php artisan migrate
```

### Error handling
Failed API calls throw `CreateCatalogProductException`, `CreatePlanException`,
`CreateSubscriptionException` or a `RuntimeException`, regardless of whether logging is enabled.
With `PAYPAL_LOGGING_ENABLED=true` the PayPal response is logged as well.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

