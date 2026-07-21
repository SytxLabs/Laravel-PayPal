<?php

namespace SytxLabs\PayPal\Enums\DTO\Subscription;

enum SetupFeeFailureAction: string
{
    case CONTINUE = 'CONTINUE';
    case CANCEL = 'CANCEL';
}
