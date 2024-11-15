<?php

namespace SytxLabs\PayPal\Models\DTO\Shipping;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\PayPalFulfillmentType;
use SytxLabs\PayPal\Models\DTO\Address;
use SytxLabs\PayPal\Models\DTO\PhoneNumber;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class ShippingWithTrackingDetails implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute('name.full_name')]
    private ?string $name;
    #[ArrayMappingAttribute('phone_number', PhoneNumber::class)]
    private ?PhoneNumber $phoneNumber;
    #[ArrayMappingAttribute('type', PayPalFulfillmentType::class)]
    private ?PayPalFulfillmentType $type;

    /**
     * @var ShippingOption[]|null
     */
    #[ArrayMappingAttribute('options', ShippingOption::class, true)]
    private ?array $options;
    #[ArrayMappingAttribute('address', Address::class)]
    private ?Address $address;

    /**
     * @var OrderTrackerResponse[]|null
     */
    #[ArrayMappingAttribute('trackers', OrderTrackerResponse::class, true)]
    private ?array $trackers;

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
        return $this;
    }

    /**
     * @return ShippingOption[]|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * @param ShippingOption[]|null $options
     */
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

    /**
     * @return OrderTrackerResponse[]|null
     */
    public function getTrackers(): ?array
    {
        return $this->trackers;
    }

    /**
     * @param OrderTrackerResponse[]|null $trackers
     */
    public function setTrackers(?array $trackers): self
    {
        $this->trackers = $trackers;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->name)) {
            $json['name']['full_name'] = $this->name;
        }
        if (isset($this->phoneNumber)) {
            $json['phone_number'] = $this->phoneNumber;
        }
        if (isset($this->type)) {
            $json['type'] = $this->type;
        }
        if (isset($this->options)) {
            $json['options'] = $this->options;
        }
        if (isset($this->address)) {
            $json['address'] = $this->address;
        }
        if (isset($this->trackers)) {
            $json['trackers'] = $this->trackers;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
