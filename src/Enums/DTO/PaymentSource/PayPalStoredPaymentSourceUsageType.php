<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalStoredPaymentSourceUsageType: string
{
    case FIRST = 'FIRST';
    case SUBSEQUENT = 'SUBSEQUENT';
    case DERIVED = 'DERIVED';
}
