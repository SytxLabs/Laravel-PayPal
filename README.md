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
`setRecurringPrice(...)` is the single-value shorthand (also additive when called repeatedly).

### Manage the subscription
```php
$subscription->getSubscriptionFromPayPal('I-XXX');
$subscription->getSubscriptionStatus();
$subscription->suspend('Customer request');
$subscription->activate();
$subscription->cancel('No longer needed');
```

### Webhooks

Set `PAYPAL_WEBHOOK_ID` and enable the built-in route with
`PAYPAL_WEBHOOK_ROUTE_ENABLED=true` (path via `PAYPAL_WEBHOOK_PATH`, default
`paypal/webhook`). Incoming webhooks are signature-verified against PayPal, subscription
status changes are persisted, and a `SytxLabs\PayPal\Events\PayPalWebhookReceived` event is
dispatched for you to listen on.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

