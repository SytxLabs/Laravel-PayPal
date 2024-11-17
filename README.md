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

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

