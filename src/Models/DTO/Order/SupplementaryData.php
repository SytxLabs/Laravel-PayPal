<?php

namespace SytxLabs\PayPal\Models\DTO\Order;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Order\SupplementaryData\CardSupplementaryData;

class SupplementaryData implements JsonSerializable
{
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
