<?php

require_once __DIR__ . '/vendor/autoload.php';

use SytxLabs\PayPal\Enums\PayPalItemCategory;
use SytxLabs\PayPal\Models\DTO\Payee;
use SytxLabs\PayPal\Models\DTO\Product;

$config = [
    'mode' => 'sandbox',

    'client_id' => null,
    'client_secret' => null,

    'success_route' => 'http://localhost/success',

];

$paypal = new \SytxLabs\PayPal\Services\PayPalOrder($config);

$paypal->addProduct(Product::fromArray([
    'name' => 'Test',
    'unitPrice' => 5.0,
    'quantity' => 1,
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
//
// $paypal->approveOrderRedirect();

// After approving the order, you can get the order id from the url and use it to capture the order
$paypal->getOrderFormId('8A105823UT346431A');

$paypal->captureOrder();

$paypal->getOrderStatus();
