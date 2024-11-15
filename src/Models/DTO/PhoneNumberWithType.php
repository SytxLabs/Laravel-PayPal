<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\PayPalPhoneType;

class PhoneNumberWithType implements JsonSerializable
{
    private ?PayPalPhoneType $phoneType;
    private PhoneNumber $phoneNumber;

    public function __construct(PhoneNumber $phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getPhoneType(): ?PayPalPhoneType
    {
        return $this->phoneType;
    }

    public function setPhoneType(?PayPalPhoneType $phoneType): self
    {
        $this->phoneType = $phoneType;
        return $this;
    }

    public function getPhoneNumber(): PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(PhoneNumber $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array
    {
        $json = ['phone_number' => $this->phoneNumber];
        if (isset($this->phoneType)) {
            $json['phone_type'] = $this->phoneType;
        }
        return $json;
    }
}
