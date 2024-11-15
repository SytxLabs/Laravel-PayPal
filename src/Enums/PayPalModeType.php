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
}
