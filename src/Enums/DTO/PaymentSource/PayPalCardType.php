<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalCardType: string
{
    case CREDIT = 'CREDIT';
    case DEBIT = 'DEBIT';
    case PREPAID = 'PREPAID';
    case STORE = 'STORE';
    case UNKNOWN = 'UNKNOWN';
}
