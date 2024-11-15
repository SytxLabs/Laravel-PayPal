<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalGooglePayAuthenticationMethod: string
{
    case PAN_ONLY = 'PAN_ONLY';
    case CRYPTOGRAM_3DS = 'CRYPTOGRAM_3DS';
}
