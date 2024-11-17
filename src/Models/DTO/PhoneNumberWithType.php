<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\PayPalPhoneType;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class PhoneNumberWithType implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('phone_type', PayPalPhoneType::class)]
    private ?PayPalPhoneType $phoneType;
    #[ArrayMappingAttribute('phone_number', PhoneNumber::class)]
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

    public static function fromArray(array $data): static
    {
        return (new static(PhoneNumber::fromArray($data['phone_number'])))
            ->setPhoneType(isset($data['phone_type']) ? PayPalPhoneType::tryFrom($data['phone_type']) : null);
    }
}
