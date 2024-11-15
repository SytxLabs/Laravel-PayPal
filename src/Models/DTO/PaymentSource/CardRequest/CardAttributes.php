<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest;

use JsonSerializable;
use PaypalServerSDKLib\Models\VaultInstructionBase;
use stdClass;
use SytxLabs\PayPal\Models\DTO\PaymentSource\CustomerInformation;

class CardAttributes implements JsonSerializable
{
    private ?CustomerInformation $customer;
    private ?VaultInstructionBase $vault;
    private ?CardVerification $verification;

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

    public function getVerification(): ?CardVerification
    {
        return $this->verification;
    }

    public function setVerification(?CardVerification $verification): self
    {
        $this->verification = $verification;
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
        if (isset($this->verification)) {
            $json['verification'] = $this->verification;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
