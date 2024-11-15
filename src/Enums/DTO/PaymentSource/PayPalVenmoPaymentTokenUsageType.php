<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalVenmoPaymentTokenUsageType: string
{
    case MERCHANT = 'MERCHANT';
    case PLATFORM = 'PLATFORM';
}
