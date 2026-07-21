<?php

namespace SytxLabs\PayPal\Enums\DTO\Subscription;

enum IntervalUnit: string
{
    case DAY = 'DAY';
    case WEEK = 'WEEK';
    case MONTH = 'MONTH';
    case YEAR = 'YEAR';
}
