<?php

namespace SytxLabs\PayPal\Models\DTO\Subscription;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class PricingScheme implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('fixed_price', Money::class)]
    private ?Money $fixedPrice = null;
    #[ArrayMappingAttribute('version')]
    private ?int $version = null;

    public function __construct(?Money $fixedPrice = null)
    {
        $this->fixedPrice = $fixedPrice;
    }

    public function getFixedPrice(): ?Money
    {
        return $this->fixedPrice;
    }

    public function setFixedPrice(?Money $fixedPrice): self
    {
        $this->fixedPrice = $fixedPrice;
        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(?int $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->fixedPrice)) {
            $json['fixed_price'] = $this->fixedPrice;
        }
        if (isset($this->version)) {
            $json['version'] = $this->version;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
