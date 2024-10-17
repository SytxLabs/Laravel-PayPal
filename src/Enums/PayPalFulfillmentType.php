<?php

namespace SytxLabs\PayPal\Enums;

use PaypalServerSDKLib\Models\FullfillmentType;

enum PayPalFulfillmentType: string
{
    case Shipping = FullfillmentType::SHIPPING;
    case PickupInPerson = FullfillmentType::PICKUP_IN_PERSON;
    case PickupInStore = FullfillmentType::PICKUP_IN_STORE;
    case PickupFromPerson = FullfillmentType::PICKUP_FROM_PERSON;
    case Digital = '';
}
