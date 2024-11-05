<?php

namespace SytxLabs\PayPal\Enums;

enum PayPalCheckoutPaymentIntent: string
{
    case CAPTURE = 'CAPTURE';
    case AUTHORIZE = 'AUTHORIZE';
}
