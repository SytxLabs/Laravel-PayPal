<?php

namespace SytxLabs\PayPal\Models\DTO\Subscription;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\Subscription\IntervalUnit;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Frequency implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('interval_unit', IntervalUnit::class)]
    private ?IntervalUnit $intervalUnit = null;
    #[ArrayMappingAttribute('interval_count')]
    private ?int $intervalCount = 1;

    public function __construct(?IntervalUnit $intervalUnit = null, ?int $intervalCount = 1)
    {
        $this->intervalUnit = $intervalUnit;
        $this->intervalCount = $intervalCount;
    }

    public function getIntervalUnit(): ?IntervalUnit
    {
        return $this->intervalUnit;
    }

    public function setIntervalUnit(?IntervalUnit $intervalUnit): self
    {
        $this->intervalUnit = $intervalUnit;
        return $this;
    }

    public function getIntervalCount(): ?int
    {
        return $this->intervalCount;
    }

    public function setIntervalCount(?int $intervalCount): self
    {
        $this->intervalCount = $intervalCount;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->intervalUnit)) {
            $json['interval_unit'] = $this->intervalUnit->value;
        }
        if (isset($this->intervalCount)) {
            $json['interval_count'] = $this->intervalCount;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
