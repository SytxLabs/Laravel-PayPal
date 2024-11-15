<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalPaymentTokenCustomerType: string
{
    case CONSUMER = 'CONSUMER';
    case BUSINESS = 'BUSINESS';
}
