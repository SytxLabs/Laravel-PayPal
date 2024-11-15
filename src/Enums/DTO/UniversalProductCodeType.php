<?php

namespace SytxLabs\PayPal\Enums\DTO;

enum UniversalProductCodeType: string
{
    case UPCA = 'UPC-A';
    case UPCB = 'UPC-B';
    case UPCC = 'UPC-C';
    case UPCD = 'UPC-D';
    case UPCE = 'UPC-E';
    case UPC2 = 'UPC-2';
    case UPC5 = 'UPC-5';
}
