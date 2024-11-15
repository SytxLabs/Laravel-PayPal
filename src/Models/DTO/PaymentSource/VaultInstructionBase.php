<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use JsonSerializable;
use stdClass;

class VaultInstructionBase implements JsonSerializable
{
    public function __construct(private bool $storeInVault = false)
    {
    }

    public function getStoreInVault(): bool
    {
        return $this->storeInVault;
    }

    public function setStoreInVault(bool $storeInVault): self
    {
        $this->storeInVault = $storeInVault;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if ($this->storeInVault) {
            $json['store_in_vault'] = 'ON_SUCCESS';
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
