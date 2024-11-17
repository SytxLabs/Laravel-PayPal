<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\ApplePay;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\PaymentSource\CustomerInformation;
use SytxLabs\PayPal\Models\DTO\PaymentSource\VaultInstructionBase;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class ApplePayAttributes implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute('customer', CustomerInformation::class)]
    private ?CustomerInformation $customer;
    #[ArrayMappingAttribute('vault', VaultInstructionBase::class)]
    private ?VaultInstructionBase $vault;

    public function getCustomer(): ?CustomerInformation
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerInformation $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getVault(): ?VaultInstructionBase
    {
        return $this->vault;
    }

    public function setVault(?VaultInstructionBase $vault): self
    {
        $this->vault = $vault;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->customer)) {
            $json['customer'] = $this->customer;
        }
        if (isset($this->vault)) {
            $json['vault'] = $this->vault;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
