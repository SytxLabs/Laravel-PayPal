<?php

namespace SytxLabs\PayPal\Enums\DTO\Subscription;

enum SubscriptionStatus: string
{
    case APPROVAL_PENDING = 'APPROVAL_PENDING';
    case APPROVED = 'APPROVED';
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case CANCELLED = 'CANCELLED';
    case EXPIRED = 'EXPIRED';
}
