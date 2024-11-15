<?php

namespace SytxLabs\PayPal\Models\DTO;

use InvalidArgumentException;
use JsonSerializable;

class Money implements JsonSerializable
{
    public function __construct(private string $currencyCode, private string $value)
    {
        if (strlen($this->currencyCode) !== 3) {
            throw new InvalidArgumentException('Currency code must be a three-character ISO-4217 currency code');
        }
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        if (strlen($currencyCode) !== 3) {
            throw new InvalidArgumentException('Currency code must be a three-character ISO-4217 currency code');
        }
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

    public function jsonSerialize(): array
    {
        return [
            'currency_code' => $this->currencyCode,
            'value' => $this->value,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['currency_code'], $data['value']);
    }
}
