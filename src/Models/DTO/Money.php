<?php

namespace SytxLabs\PayPal\Models\DTO;

use InvalidArgumentException;
use JsonSerializable;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Money implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('currency_code')]
    private string $currencyCode;
    #[ArrayMappingAttribute('value')]
    private string $value;

    public function __construct(string $currencyCode, string $value)
    {
        $this->currencyCode = $currencyCode;
        $this->value = $value;
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

    public static function fromArray(array $data): static
    {
        return new self($data['currency_code'], $data['value']);
    }
}
