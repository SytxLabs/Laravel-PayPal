<?php

namespace SytxLabs\PayPal\Enums\DTO\Subscription;

enum PlanStatus: string
{
    case CREATED = 'CREATED';
    case INACTIVE = 'INACTIVE';
    case ACTIVE = 'ACTIVE';
}
