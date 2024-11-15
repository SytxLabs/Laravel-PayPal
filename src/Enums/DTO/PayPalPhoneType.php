<?php

namespace SytxLabs\PayPal\Enums\DTO;

enum PayPalPhoneType: string
{
    case FAX = 'FAX';
    case HOME = 'HOME';
    case MOBILE = 'MOBILE';
    case OTHER = 'OTHER';
    case PAGER = 'PAGER';
}
