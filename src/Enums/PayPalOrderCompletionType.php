<?php

namespace SytxLabs\PayPal\Enums;

enum PayPalOrderCompletionType: string
{
    case CREATED = 'CREATED';
    case SAVED = 'SAVED';
    case APPROVED = 'APPROVED';
    case VOIDED = 'VOIDED';
    case COMPLETED = 'COMPLETED';
    case PAYER_ACTION_REQUIRED = 'PAYER_ACTION_REQUIRED';
    case UNKNOWN = 'UNKNOWN';
}
