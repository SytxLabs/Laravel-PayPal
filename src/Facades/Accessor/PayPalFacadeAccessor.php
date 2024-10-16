<?php

namespace SytxLabs\PayPal\Facades\Accessor;

use SytxLabs\PayPal\Services\PayPal as PayPalClient;

class PayPalFacadeAccessor
{
    public static ?PayPalClient $provider;

    public static function getProvider(): ?PayPalClient
    {
        return self::$provider;
    }

    public static function setProvider(array $config = []): ?PayPalClient
    {
        self::$provider = new PayPalClient($config);
        return self::getProvider();
    }
}
