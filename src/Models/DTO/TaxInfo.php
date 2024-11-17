<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\PayPalTaxIdType;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class TaxInfo implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('tax_id')]
    private string $taxId;
    #[ArrayMappingAttribute('tax_id_type', PayPalTaxIdType::class)]
    private PayPalTaxIdType $taxIdType;

    public function __construct(string $taxId, PayPalTaxIdType $taxIdType)
    {
        $this->taxId = $taxId;
        $this->taxIdType = $taxIdType;
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
