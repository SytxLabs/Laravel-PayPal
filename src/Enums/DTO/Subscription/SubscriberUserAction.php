<?php

namespace SytxLabs\PayPal\Enums\DTO\Subscription;

enum SubscriberUserAction: string
{
    case SUBSCRIBE_NOW = 'SUBSCRIBE_NOW';
    case CONTINUE = 'CONTINUE';
}
