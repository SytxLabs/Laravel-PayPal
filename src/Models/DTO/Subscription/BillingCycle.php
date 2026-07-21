<?php

namespace SytxLabs\PayPal\Models\DTO\Subscription;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\Subscription\TenureType;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class BillingCycle implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('frequency', Frequency::class)]
    private ?Frequency $frequency = null;
    #[ArrayMappingAttribute('tenure_type', TenureType::class)]
    private ?TenureType $tenureType = TenureType::REGULAR;
    #[ArrayMappingAttribute('sequence')]
    private ?int $sequence = 1;
    #[ArrayMappingAttribute('total_cycles')]
    private ?int $totalCycles = 0;
    #[ArrayMappingAttribute('pricing_scheme', PricingScheme::class)]
    private ?PricingScheme $pricingScheme = null;

    public function getFrequency(): ?Frequency
    {
        return $this->frequency;
    }

    public function setFrequency(?Frequency $frequency): self
    {
        $this->frequency = $frequency;
        return $this;
    }

    public function getTenureType(): ?TenureType
    {
        return $this->tenureType;
    }

    public function setTenureType(?TenureType $tenureType): self
    {
        $this->tenureType = $tenureType;
        return $this;
    }

    public function getSequence(): ?int
    {
        return $this->sequence;
    }

    public function setSequence(?int $sequence): self
    {
        $this->sequence = $sequence;
        return $this;
    }

    public function getTotalCycles(): ?int
    {
        return $this->totalCycles;
    }

    public function setTotalCycles(?int $totalCycles): self
    {
        $this->totalCycles = $totalCycles;
        return $this;
    }

    public function getPricingScheme(): ?PricingScheme
    {
        return $this->pricingScheme;
    }

    public function setPricingScheme(?PricingScheme $pricingScheme): self
    {
        $this->pricingScheme = $pricingScheme;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->frequency)) {
            $json['frequency'] = $this->frequency;
        }
        if (isset($this->tenureType)) {
            $json['tenure_type'] = $this->tenureType->value;
        }
        if (isset($this->sequence)) {
            $json['sequence'] = $this->sequence;
        }
        if (isset($this->totalCycles)) {
            $json['total_cycles'] = $this->totalCycles;
        }
        if (isset($this->pricingScheme)) {
            $json['pricing_scheme'] = $this->pricingScheme;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
