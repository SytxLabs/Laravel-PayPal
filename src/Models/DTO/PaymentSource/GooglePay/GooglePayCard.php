<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\GooglePay;

use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalCardBrand;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalCardType;
use SytxLabs\PayPal\Models\DTO\Address;

class GooglePayCard implements \JsonSerializable
{
    private ?string $name;
    private ?PayPalCardType $type;
    private ?PayPalCardBrand $brand;
    private ?Address $billingAddress;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): ?PayPalCardType
    {
        return $this->type;
    }

    public function setType(?PayPalCardType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getBrand(): ?PayPalCardBrand
    {
        return $this->brand;
    }

    public function setBrand(?PayPalCardBrand $brand): self
    {
        $this->brand = $brand;
        return $this;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?Address $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->name)) {
            $json['name'] = $this->name;
        }
        if (isset($this->type)) {
            $json['type'] = $this->type->value;
        }
        if (isset($this->brand)) {
            $json['brand'] = $this->brand->value;
        }
        if (isset($this->billingAddress)) {
            $json['billing_address'] = $this->billingAddress;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
