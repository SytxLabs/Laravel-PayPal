<?php

namespace SytxLabs\PayPal\Enums;

enum PayPalModeType: string
{
    case Sandbox = 'sandbox';
    case Live = 'live';

    public function getPayPalEnvironment(): string
    {
        return match ($this) {
            self::Sandbox => 'Sandbox',
            self::Live => 'Production',
        };
    }

    public function getPayPalEnvironmentURL(): string
    {
        return match ($this) {
            self::Sandbox => 'https://api-m.sandbox.paypal.com',
            self::Live => 'https://api-m.paypal.com',
        };
    }
}
