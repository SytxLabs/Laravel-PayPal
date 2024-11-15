<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalExperienceLandingPage: string
{
    case LOGIN = 'LOGIN';
    case GUEST_CHECKOUT = 'GUEST_CHECKOUT';
    case NO_PREFERENCE = 'NO_PREFERENCE';
}
