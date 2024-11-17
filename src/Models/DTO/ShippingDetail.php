<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\PayPalFulfillmentType;
use SytxLabs\PayPal\Models\DTO\Shipping\ShippingOption;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class ShippingDetail implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('name.full_name')]
    private ?string $name;
    #[ArrayMappingAttribute('phone_number', PhoneNumber::class)]
    private ?PhoneNumber $phoneNumber = null;
    #[ArrayMappingAttribute('type', PayPalFulfillmentType::class)]
    private ?PayPalFulfillmentType $type = null;
    /**
     * @var ShippingOption[]|null $options
     */
    #[ArrayMappingAttribute('options', ShippingOption::class, true)]
    private ?array $options = null;
    #[ArrayMappingAttribute('address')]
    private ?Address $address = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?PhoneNumber $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getType(): ?PayPalFulfillmentType
    {
        return $this->type;
    }

    public function setType(?PayPalFulfillmentType $type): self
    {
        $this->type = $type;
        if ($this->type === PayPalFulfillmentType::Digital) {
            $this->type = null;
        }
        return $this;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->name)) {
            $json['name'] = $this->name;
        }
        if (isset($this->phoneNumber)) {
            $json['phone_number'] = $this->phoneNumber;
        }
        if (isset($this->type)) {
            $json['type'] = $this->type->value;
        }
        if (isset($this->options)) {
            $json['options'] = $this->options;
        }
        if (isset($this->address)) {
            $json['address'] = $this->address;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
