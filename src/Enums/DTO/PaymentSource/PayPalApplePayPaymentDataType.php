<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalApplePayPaymentDataType: string
{
    case ENUM_3DSECURE = '3DSECURE';
    case EMV = 'EMV';
}
