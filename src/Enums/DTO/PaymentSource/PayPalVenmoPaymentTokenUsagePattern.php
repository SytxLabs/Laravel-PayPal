<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalVenmoPaymentTokenUsagePattern: string
{
    case IMMEDIATE = 'IMMEDIATE';
    case DEFERRED = 'DEFERRED';
    case RECURRING_PREPAID = 'RECURRING_PREPAID';
    case RECURRING_POSTPAID = 'RECURRING_POSTPAID';
    case THRESHOLD_PREPAID = 'THRESHOLD_PREPAID';
    case THRESHOLD_POSTPAID = 'THRESHOLD_POSTPAID';
}
