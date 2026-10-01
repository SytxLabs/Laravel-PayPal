<?php

namespace SytxLabs\PayPal\Facades\Accessor;

use SytxLabs\PayPal\Services\PayPalSubscription as PayPalSubscriptionClient;

class PayPalSubscriptionFacadeAccessor
{
    public static ?PayPalSubscriptionClient $provider = null;

    public static function getProvider(): ?PayPalSubscriptionClient
    {
        return self::$provider;
    }

    public static function setProvider(array $config = []): PayPalSubscriptionClient
    {
        self::$provider = new PayPalSubscriptionClient($config);
        return self::$provider;
    }
}
