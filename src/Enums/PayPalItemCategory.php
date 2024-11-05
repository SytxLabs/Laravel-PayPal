<?php

namespace SytxLabs\PayPal\Enums;

enum PayPalItemCategory: string
{
    case DIGITAL_GOODS = 'DIGITAL_GOODS';
    case PHYSICAL_GOODS = 'PHYSICAL_GOODS';
    case DONATION = 'DONATION';
}
