<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class VaultInstructionBase implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute('store_in_vault')]
    private bool $storeInVault = false;

    public function __construct(bool $storeInVault = false)
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

    public static function fromArray(array $data): static
    {
        if (isset($data['store_in_vault'])) {
            $storeInVault = $data['store_in_vault'] === 'ON_SUCCESS';
        } else {
            $storeInVault = false;
        }
        $data['store_in_vault'] = $storeInVault;
        return self::fromArrayInternal(new self(), $data);
    }
}
