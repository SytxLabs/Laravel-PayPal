<?php

require_once __DIR__ . '/vendor/autoload.php';

use PaypalServerSDKLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSDKLib\Models\Builders\OrderBuilder;
use PaypalServerSDKLib\Models\Builders\PurchaseUnitRequestBuilder;
use SytxLabs\PayPal\Services\PayPalOrder;

$config = [
    'mode' => 'sandbox',

    'success_route' => 'http://cms.localhost/test/success',
    'cancel_route' => 'http://cms.localhost/test/cancel',
];

$paypal = new PayPalOrder($config);
$paypal->addProduct(PurchaseUnitRequestBuilder::init(
    AmountWithBreakdownBuilder::init('USD', '100.00')->build()
));

$paypal->createOrder();

$paypal->setOrder(OrderBuilder::init()->id('05429312R4818361H')->build());

// $paypal->approveOrderRedirect();

// $paypal->getOrderFormId('5PA48148L4468233S');

$paypal->checkIfCompleted();
