<?php

namespace SytxLabs\PayPal\Models\DTO;

use InvalidArgumentException;
use JsonSerializable;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Address implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('address_line_1')]
    private ?string $addressLine1;
    #[ArrayMappingAttribute('address_line_2')]
    private ?string $addressLine2;
    #[ArrayMappingAttribute('admin_area_2')]
    private ?string $adminArea2;
    #[ArrayMappingAttribute('admin_area_1')]
    private ?string $adminArea1;
    #[ArrayMappingAttribute('postal_code')]
    private ?string $postalCode;
    #[ArrayMappingAttribute('country_code')]
    private string $countryCode;

    public function __construct(string $countryCode)
    {
        $this->countryCode = $countryCode;
        if (strlen($this->countryCode) !== 2) {
            throw new InvalidArgumentException('Country code must be a 2-character ISO 3166-1 code');
        }
    }

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(?string $addressLine1): self
    {
        $this->addressLine1 = $addressLine1;
        return $this;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(?string $addressLine2): self
    {
        $this->addressLine2 = $addressLine2;
        return $this;
    }

    public function getAdminArea2(): ?string
    {
        return $this->adminArea2;
    }

    public function setAdminArea2(?string $adminArea2): self
    {
        $this->adminArea2 = $adminArea2;
        return $this;
    }

    public function getAdminArea1(): ?string
    {
        return $this->adminArea1;
    }

    public function setAdminArea1(?string $adminArea1): self
    {
        $this->adminArea1 = $adminArea1;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        if (strlen($countryCode) !== 2) {
            throw new InvalidArgumentException('Country code must be a 2-character ISO 3166-1 code');
        }
        $this->countryCode = $countryCode;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = ['country_code' => $this->countryCode];
        if (isset($this->addressLine1)) {
            $json['address_line_1'] = $this->addressLine1;
        }
        if (isset($this->addressLine2)) {
            $json['address_line_2'] = $this->addressLine2;
        }
        if (isset($this->adminArea2)) {
            $json['admin_area_2'] = $this->adminArea2;
        }
        if (isset($this->adminArea1)) {
            $json['admin_area_1'] = $this->adminArea1;
        }
        if (isset($this->postalCode)) {
            $json['postal_code'] = $this->postalCode;
        }
        return $json;
    }

    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new self($data['country_code']), $data);
    }
}
