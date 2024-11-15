<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\ApplePay;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalCardBrand;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalCardType;
use SytxLabs\PayPal\Models\DTO\Address;

class ApplePayTokenizedCard implements JsonSerializable
{
    private ?string $name;
    private ?string $number;
    private ?string $expiry;
    private ?PayPalCardBrand $cardType;
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

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function getExpiry(): ?string
    {
        return $this->expiry;
    }

    /**
     * The year and month, in ISO-8601 `YYYY-MM` date format.
     */
    public function setExpiry(?string $expiry): self
    {
        if ($expiry !== null && !preg_match('/^\d{4}-\d{2}$/', $expiry)) {
            throw new InvalidArgumentException('Invalid expiry date');
        }
        $this->expiry = $expiry;
        return $this;
    }

    public function getCardType(): ?PayPalCardBrand
    {
        return $this->cardType;
    }

    public function setCardType(?PayPalCardBrand $cardType): self
    {
        $this->cardType = $cardType;
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

    public function setBillingAddress(?Address $billingAddress): self
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->name)) {
            $json['name'] = $this->name;
        }
        if (isset($this->number)) {
            $json['number'] = $this->number;
        }
        if (isset($this->expiry)) {
            $json['expiry'] = $this->expiry;
        }
        if (isset($this->cardType)) {
            $json['card_type'] = $this->cardType->value;
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
