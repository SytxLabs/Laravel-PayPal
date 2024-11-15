<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalExperienceUserAction: string
{
    case CONTINUE = 'CONTINUE';
    case PAY_NOW = 'PAY_NOW';
}
