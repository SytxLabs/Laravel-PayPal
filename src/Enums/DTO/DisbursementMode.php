<?php

namespace SytxLabs\PayPal\Enums\DTO;

enum DisbursementMode: string
{
    case INSTANT = 'INSTANT';
    case DELAYED = 'DELAYED';
}
