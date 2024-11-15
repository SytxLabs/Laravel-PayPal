<?php

namespace SytxLabs\PayPal\Models\DTO\Order;

use JsonSerializable;

class AmountWithBreakdown implements JsonSerializable
{
    private ?AmountBreakdown $breakdown;

    public function __construct(private string $currencyCode, private string $value)
    {
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getBreakdown(): ?AmountBreakdown
    {
        return $this->breakdown;
    }

    public function setBreakdown(?AmountBreakdown $breakdown): self
    {
        $this->breakdown = $breakdown;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array
    {
        $json = [
            'currency_code' => $this->currencyCode,
            'value' => $this->value,
        ];
        if (isset($this->breakdown)) {
            $json['breakdown'] = $this->breakdown;
        }

        return $json;
    }

    public static function fromArray(array $data): ?self
    {
        if (!isset($data['currency_code'], $data['value'])) {
            return null;
        }
        $amountWithBreakdown = new self($data['currency_code'], $data['value']);
        if (isset($data['breakdown'])) {
            $amountWithBreakdown->setBreakdown(AmountBreakdown::fromArray($data['breakdown']));
        }
        return $amountWithBreakdown;
    }
}
