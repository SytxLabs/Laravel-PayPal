<?php

namespace SytxLabs\PayPal\Enums\DTO\Subscription;

enum CatalogProductType: string
{
    case PHYSICAL = 'PHYSICAL';
    case DIGITAL = 'DIGITAL';
    case SERVICE = 'SERVICE';
}
