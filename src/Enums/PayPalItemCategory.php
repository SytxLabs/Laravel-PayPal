<?php

namespace SytxLabs\PayPal\Enums;

use PaypalServerSDKLib\Models\ItemCategory;

enum PayPalItemCategory: string
{
    case DIGITAL_GOODS = ItemCategory::DIGITAL_GOODS;
    case PHYSICAL_GOODS = ItemCategory::PHYSICAL_GOODS;
    case DONATION = ItemCategory::DONATION;
}
