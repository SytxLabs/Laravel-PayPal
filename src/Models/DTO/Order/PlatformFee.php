<?php

namespace SytxLabs\PayPal\Models\DTO\Order;

use JsonSerializable;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Payee;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class PlatformFee implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute(key: 'payee', class: Payee::class)]
    private ?Payee $payee = null;
    #[ArrayMappingAttribute(key: 'amount', class: Money::class)]
    private Money $amount;

    public function __construct(Money $amount)
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

    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new static(Money::fromArray($data['amount'])), $data);
    }
}
