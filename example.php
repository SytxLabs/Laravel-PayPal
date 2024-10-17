<?php

require_once __DIR__ . '/vendor/autoload.php';

use SytxLabs\PayPal\Enums\PayPalItemCategory;
use SytxLabs\PayPal\Models\Payee;
use SytxLabs\PayPal\Models\Product;
use SytxLabs\PayPal\Services\PayPalOrder;

$config = [
    'mode' => 'sandbox',

    'client_id' => null,
    'client_secret' => null,
];

$paypal = new PayPalOrder($config);

$paypal->addProduct(Product::fromArray([
    'name' => 'Test',
    'unitPrice' => 5.0,
    'quantity' => 19,
    'currencyCode' => 'USD',
    'description' => 'test',
    'url' => 'http://localhost/product/2',
    'category' => PayPalItemCategory::DIGITAL_GOODS,
    'imageUrl' => null,
    'upc' => null,
    'payee' => new Payee('test@test.de', null, 'test'),
    'shipping' => null,
]));

// $paypal->createOrder();

// $paypal->approveOrderRedirect();

// After approving the order, you can get the order id from the url and use it to capture the order
// $paypal->getOrderFormId('5PA48148L4468233S');

// $paypal->captureOrder();

// $paypal->getOrderStatus();
