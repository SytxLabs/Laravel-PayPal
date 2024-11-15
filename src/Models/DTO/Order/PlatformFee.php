<?php

namespace SytxLabs\PayPal\Models\DTO\Order;

use JsonSerializable;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Payee;

class PlatformFee implements JsonSerializable
{
    private ?Payee $payee;

    public function __construct(private Money $amount)
    {
        $this->amount = $amount;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function setAmount(Money $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getPayee(): ?Payee
    {
        return $this->payee;
    }

    public function setPayee(?Payee $payee): self
    {
        $this->payee = $payee;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array
    {
        $json = [
            'amount' => $this->amount,
        ];
        if (isset($this->payee)) {
            $json['payee'] = $this->payee;
        }

        return $json;
    }
}
