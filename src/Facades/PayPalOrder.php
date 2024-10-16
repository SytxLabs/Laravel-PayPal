<?php

namespace SytxLabs\PayPal\Facades;

use Illuminate\Support\Facades\Facade;
use SytxLabs\PayPal\Facades\Accessor\PayPalOrderFacadeAccessor;

class PayPalOrder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PayPalOrderFacadeAccessor::class;
    }
}
