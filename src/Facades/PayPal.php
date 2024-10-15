<?php

namespace SytxLabs\PayPal\Facades;

use Illuminate\Support\Facades\Facade;
use SytxLabs\PayPal\PayPalFacadeAccessor;

class PayPal extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PayPalFacadeAccessor::class;
    }
}
