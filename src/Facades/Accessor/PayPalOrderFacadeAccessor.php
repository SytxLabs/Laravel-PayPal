<?php

namespace SytxLabs\PayPal\Facades\Accessor;

use SytxLabs\PayPal\Services\PayPalOrder as PayPalOrderClient;

class PayPalOrderFacadeAccessor
{
    public static ?PayPalOrderClient $provider = null;

    public static function getProvider(): ?PayPalOrderClient
    {
        return self::$provider;
    }

    public static function setProvider(array $config = []): PayPalOrderClient
    {
        self::$provider = new PayPalOrderClient($config);
        return self::$provider;
    }
}
