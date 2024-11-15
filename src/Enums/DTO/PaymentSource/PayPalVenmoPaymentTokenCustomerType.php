<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalVenmoPaymentTokenCustomerType: string
{
    case CONSUMER = 'CONSUMER';
    case BUSINESS = 'BUSINESS';
}
