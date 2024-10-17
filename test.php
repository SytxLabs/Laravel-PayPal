<?php

require_once __DIR__ . '/vendor/autoload.php';

use PaypalServerSDKLib\Models\Builders\AmountBreakdownBuilder;
use PaypalServerSDKLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSDKLib\Models\Builders\ItemBuilder;
use PaypalServerSDKLib\Models\Builders\MoneyBuilder;
use PaypalServerSDKLib\Models\Builders\OrderBuilder;
use PaypalServerSDKLib\Models\Builders\PayeeBuilder;
use PaypalServerSDKLib\Models\Builders\PurchaseUnitRequestBuilder;
use SytxLabs\PayPal\Services\PayPalOrder;

$config = [
    'mode' => 'sandbox',

    'client_id' => 'AfudkxWpc3PoqRzR8mXT-bXhbIN0Ow2bqwkPxplYjRKGp6fi894bTFEPvH5hVpoqise-MixeWE9Yod5Y',
    'client_secret' => 'ENz9eMdRqiPyjmAXs7FhDBpAxFhyVDptw6xAIm7eijmVWxZUUa_IpejOXVZsl-yguLbvS-IRUO4BmfX-',
    'success_route' => 'http://cms.localhost/test/success',
    'cancel_route' => 'http://cms.localhost/test/cancel',
];

$paypal = new PayPalOrder($config);
$paypal->addProduct(PurchaseUnitRequestBuilder::init(
    AmountWithBreakdownBuilder::init('USD', '100.00')->breakdown(
        AmountBreakdownBuilder::init()->itemTotal(MoneyBuilder::init('USD', '95.0')->build())
            ->taxTotal(MoneyBuilder::init('USD', '5.0')->build())
            ->build()
    )->build()
)->items([
    ItemBuilder::init('Test', MoneyBuilder::init('USD', '5.0')->build(), 19)
        ->description('test')
        ->url('http://wilma.localhost/product/2/WiLMa%25C2%25AE-Marketingportal%2BBrosch%25C3%25BCre%2BProduct2Print')
        ->category('PHYSICAL_GOODS')
        ->build()
])->payee(PayeeBuilder::init()->emailAddress('test@test.de')->build()));

//$paypal->createOrder();

$paypal->setOrder(OrderBuilder::init()->id('18S67192AM197972Y')->build());

//$paypal->approveOrderRedirect();

// $paypal->getOrderFormId('5PA48148L4468233S');

$paypal->checkIfCompleted();
