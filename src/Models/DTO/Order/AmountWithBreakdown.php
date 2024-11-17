<?php

namespace SytxLabs\PayPal\Models\DTO\Order;

use InvalidArgumentException;
use JsonSerializable;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class AmountWithBreakdown implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('breakdown', class: AmountBreakdown::class)]
    private ?AmountBreakdown $breakdown;
    #[ArrayMappingAttribute('currency_code')]
    private string $currencyCode;
    #[ArrayMappingAttribute('value')]
    private string $value;

    public function __construct(string $currencyCode, string $value)
    {
        $this->currencyCode = $currencyCode;
        $this->value = $value;
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

    public static function fromArray(array $data): static
    {
        if (!isset($data['currency_code'], $data['value'])) {
            throw new InvalidArgumentException('Currency code and value are required');
        }
        return self::fromArrayInternal(new self($data['currency_code'], $data['value']), $data);
    }
}
