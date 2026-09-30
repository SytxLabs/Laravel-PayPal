<?php

namespace SytxLabs\PayPal\Models\DTO\Subscription;

use JsonSerializable;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class LastPayment implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('amount', Money::class)]
    private ?Money $amount = null;
    #[ArrayMappingAttribute('time')]
    private ?string $time = null;

    public function getAmount(): ?Money
    {
        return $this->amount;
    }

    public function setAmount(?Money $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function setTime(?string $time): self
    {
        $this->time = $time;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = [];
        if (isset($this->amount)) {
            $json['amount'] = $this->amount;
        }
        if (isset($this->time)) {
            $json['time'] = $this->time;
        }
        return $json;
    }
}
