<?php

namespace SytxLabs\PayPal\Enums;

use PaypalServerSDKLib\Environment;

enum PayPalModeType: string
{
    case Sandbox = 'sandbox';
    case Live = 'live';

    public function getPayPalEnvironment(): string
    {
        return match ($this) {
            self::Sandbox => Environment::SANDBOX,
            self::Live => Environment::PRODUCTION,
        };
    }
}
