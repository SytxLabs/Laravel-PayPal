<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;

class PhoneNumber implements JsonSerializable
{
    private ?string $countryCode;
    private string $nationalNumber;

    public function __construct(string $nationalNumber, ?string $countryCode = null)
    {
        $this->nationalNumber = $nationalNumber;
        $this->countryCode = $countryCode;
    }

    public function getNationalNumber(): string
    {
        return $this->nationalNumber;
    }

    public function setNationalNumber(string $nationalNumber): self
    {
        $this->nationalNumber = $nationalNumber;
        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array
    {
        $json = ['national_number' => $this->nationalNumber];
        if (isset($this->countryCode)) {
            $json['country_code'] = $this->countryCode;
        }
        return $json;
    }
}
