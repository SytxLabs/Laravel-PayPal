<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalStoredPaymentSourcePaymentType: string
{
    case ONE_TIME = 'ONE_TIME';
    case RECURRING = 'RECURRING';
    case UNSCHEDULED = 'UNSCHEDULED';
}
