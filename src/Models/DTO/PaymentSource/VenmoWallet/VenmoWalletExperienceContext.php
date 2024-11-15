<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\VenmoWallet;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalShippingPreference;

class VenmoWalletExperienceContext implements JsonSerializable
{
    private ?string $brandName;
    private ?PayPalShippingPreference $shippingPreference = PayPalShippingPreference::GET_FROM_FILE;

    public function getBrandName(): ?string
    {
        return $this->brandName;
    }

    public function setBrandName(?string $brandName): self
    {
        $this->brandName = $brandName;
        return $this;
    }

    public function getShippingPreference(): ?PayPalShippingPreference
    {
        return $this->shippingPreference;
    }

    public function setShippingPreference(?PayPalShippingPreference $shippingPreference): self
    {
        $this->shippingPreference = $shippingPreference;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->brandName)) {
            $json['brand_name'] = $this->brandName;
        }
        if (isset($this->shippingPreference)) {
            $json['shipping_preference'] = $this->shippingPreference->value;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
