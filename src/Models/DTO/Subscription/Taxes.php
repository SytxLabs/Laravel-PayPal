<?php

namespace SytxLabs\PayPal\Models\DTO\Subscription;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Taxes implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('percentage')]
    private ?string $percentage = null;
    #[ArrayMappingAttribute('inclusive')]
    private ?bool $inclusive = null;

    public function __construct(?string $percentage = null, ?bool $inclusive = null)
    {
        $this->percentage = $percentage;
        $this->inclusive = $inclusive;
    }

    public function getPercentage(): ?string
    {
        return $this->percentage;
    }

    public function setPercentage(?string $percentage): self
    {
        $this->percentage = $percentage;
        return $this;
    }

    public function getInclusive(): ?bool
    {
        return $this->inclusive;
    }

    public function setInclusive(?bool $inclusive): self
    {
        $this->inclusive = $inclusive;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->percentage)) {
            $json['percentage'] = $this->percentage;
        }
        if (isset($this->inclusive)) {
            $json['inclusive'] = $this->inclusive;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
