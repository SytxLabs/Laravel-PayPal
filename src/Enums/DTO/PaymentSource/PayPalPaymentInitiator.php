<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalPaymentInitiator: string
{
    case CUSTOMER = 'CUSTOMER';
    case MERCHANT = 'MERCHANT';
}
