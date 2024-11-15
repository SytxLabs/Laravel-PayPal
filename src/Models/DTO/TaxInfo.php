<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\PayPalTaxIdType;

class TaxInfo implements JsonSerializable
{
    public function __construct(private string $taxId, private PayPalTaxIdType $taxIdType)
    {
    }

    public function getTaxId(): string
    {
        return $this->taxId;
    }

    public function setTaxId(string $taxId): self
    {
        $this->taxId = $taxId;
        return $this;
    }

    public function getTaxIdType(): PayPalTaxIdType
    {
        return $this->taxIdType;
    }

    public function setTaxIdType(PayPalTaxIdType $taxIdType): self
    {
        $this->taxIdType = $taxIdType;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'tax_id' => $this->taxId,
            'tax_id_type' => $this->taxIdType->value,
        ];
    }
}
