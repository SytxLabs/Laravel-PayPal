<?php

namespace SytxLabs\PayPal\Enums\DTO;

enum PayPalStandardEntryClassCode: string
{
    case TEL = 'TEL';
    case WEB = 'WEB';
    case CCD = 'CCD';
    case PPD = 'PPD';
}
