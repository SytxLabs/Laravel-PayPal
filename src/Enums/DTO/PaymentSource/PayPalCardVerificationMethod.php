<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalCardVerificationMethod: string
{
    case SCA_ALWAYS = 'SCA_ALWAYS';
    case SCA_WHEN_REQUIRED = 'SCA_WHEN_REQUIRED';
    case ENUM_3D_SECURE = '3D_SECURE';
    case AVS_CVV = 'AVS_CVV';
}
