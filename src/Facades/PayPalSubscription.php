<?php

namespace SytxLabs\PayPal\Facades;

use Illuminate\Support\Facades\Facade;
use SytxLabs\PayPal\Facades\Accessor\PayPalSubscriptionFacadeAccessor;

class PayPalSubscription extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PayPalSubscriptionFacadeAccessor::class;
    }
}
