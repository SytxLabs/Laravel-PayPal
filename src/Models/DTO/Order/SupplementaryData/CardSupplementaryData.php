<?php

namespace SytxLabs\PayPal\Models\DTO\Order\SupplementaryData;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class CardSupplementaryData implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute(key: 'level_2', class: Level2CardProcessingData::class)]
    private ?Level2CardProcessingData $level2;
    #[ArrayMappingAttribute(key: 'level_3', class: Level3CardProcessingData::class)]
    private ?Level3CardProcessingData $level3;

    public function getLevel2(): ?Level2CardProcessingData
    {
        return $this->level2;
    }

    public function setLevel2(?Level2CardProcessingData $level2): self
    {
        $this->level2 = $level2;
        return $this;
    }

    public function getLevel3(): ?Level3CardProcessingData
    {
        return $this->level3;
    }

    public function setLevel3(?Level3CardProcessingData $level3): self
    {
        $this->level3 = $level3;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->level2)) {
            $json['level_2'] = $this->level2;
        }
        if (isset($this->level3)) {
            $json['level_3'] = $this->level3;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
