<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\VenmoWallet;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class VenmoWalletAdditionalAttributes implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('customer', VenmoWalletCustomerInformation::class)]
    private ?VenmoWalletCustomerInformation $customer;
    #[ArrayMappingAttribute('vault', VenmoWalletVaultAttributes::class)]
    private ?VenmoWalletVaultAttributes $vault;

    public function getCustomer(): ?VenmoWalletCustomerInformation
    {
        return $this->customer;
    }

    public function setCustomer(?VenmoWalletCustomerInformation $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getVault(): ?VenmoWalletVaultAttributes
    {
        return $this->vault;
    }

    public function setVault(?VenmoWalletVaultAttributes $vault): self
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
