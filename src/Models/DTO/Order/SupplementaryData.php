<?php

namespace SytxLabs\PayPal\Models\DTO\Order;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Order\SupplementaryData\CardSupplementaryData;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class SupplementaryData implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute(key: 'card', class: CardSupplementaryData::class)]
    private ?CardSupplementaryData $card;

    public function getCard(): ?CardSupplementaryData
    {
        return $this->card;
    }

    public function setCard(?CardSupplementaryData $card): self
    {
        $this->card = $card;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->card)) {
            $json['card'] = $this->card;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
