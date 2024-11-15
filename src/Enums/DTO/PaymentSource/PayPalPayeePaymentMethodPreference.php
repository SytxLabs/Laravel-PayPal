<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalPayeePaymentMethodPreference: string
{
    case UNRESTRICTED = 'UNRESTRICTED';
    case IMMEDIATE_PAYMENT_REQUIRED = 'IMMEDIATE_PAYMENT_REQUIRED';
}
