<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace SytxLabs\PayPal\Models\DTO\Traits;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ArrayMappingAttribute
{
    public function __construct(public string $key, public ?string $class = null, public bool $isArray = false)
    {
    }
}
