<?php

namespace SytxLabs\PayPal\Enums\DTO\Subscription;

enum ShippingPreference: string
{
    case NO_SHIPPING = 'NO_SHIPPING';
    case GET_FROM_FILE = 'GET_FROM_FILE';
    case SET_PROVIDED_ADDRESS = 'SET_PROVIDED_ADDRESS';
}
