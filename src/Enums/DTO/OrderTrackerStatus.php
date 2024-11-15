<?php

namespace SytxLabs\PayPal\Enums\DTO;

enum OrderTrackerStatus: string
{
    case CANCELLED = 'CANCELLED';
    case SHIPPED = 'SHIPPED';
}
