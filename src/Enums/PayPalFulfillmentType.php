<?php

namespace SytxLabs\PayPal\Enums;

enum PayPalFulfillmentType: string
{
    case Shipping = 'SHIPPING';
    case PickupInPerson = 'PICKUP_IN_PERSON';
    case PickupInStore = 'PICKUP_IN_STORE';
    case PickupFromPerson = 'PICKUP_FROM_PERSON';
    case Digital = '';
}
