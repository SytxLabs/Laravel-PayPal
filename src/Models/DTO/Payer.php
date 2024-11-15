<?php

namespace SytxLabs\PayPal\Models\DTO;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Payer implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute('email_address')]
    private ?string $emailAddress;
    #[ArrayMappingAttribute('payer_id')]
    private ?string $payerId;
    #[ArrayMappingAttribute('name', Name::class)]
    private ?Name $name;
    #[ArrayMappingAttribute('phone', PhoneNumberWithType::class)]
    private ?PhoneNumberWithType $phone;
    #[ArrayMappingAttribute('birth_date')]
    private ?string $birthDate;
    #[ArrayMappingAttribute('tax_info', TaxInfo::class)]
    private ?TaxInfo $taxInfo;
    #[ArrayMappingAttribute('address', Address::class)]
    private ?Address $address;

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(?string $emailAddress): self
    {
        if (
            $emailAddress !== null
            && (strlen($emailAddress) > 250
                || preg_match(
                    '/^[\w-]+(?:\.[\w-]+)*@(?:[\w-]+\.)+[a-zA-Z]{2,7}$/',
                    $emailAddress
                ) !== 1)
        ) {
            throw new InvalidArgumentException('Invalid email address');
        }
        $this->emailAddress = $emailAddress;
        return $this;
    }

    public function getPayerId(): ?string
    {
        return $this->payerId;
    }

    public function setPayerId(?string $payerId): self
    {
        $this->payerId = $payerId;
        return $this;
    }

    public function getName(): ?Name
    {
        return $this->name;
    }

    public function setName(?Name $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPhone(): ?PhoneNumberWithType
    {
        return $this->phone;
    }

    public function setPhone(?PhoneNumberWithType $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    public function setBirthDate(?string $birthDate): self
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getTaxInfo(): ?TaxInfo
    {
        return $this->taxInfo;
    }

    public function setTaxInfo(?TaxInfo $taxInfo): self
    {
        $this->taxInfo = $taxInfo;
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
        if (isset($this->emailAddress)) {
            $json['email_address'] = $this->emailAddress;
        }
        if (isset($this->payerId)) {
            $json['payer_id'] = $this->payerId;
        }
        if (isset($this->name)) {
            $json['name'] = $this->name;
        }
        if (isset($this->phone)) {
            $json['phone'] = $this->phone;
        }
        if (isset($this->birthDate)) {
            $json['birth_date'] = $this->birthDate;
        }
        if (isset($this->taxInfo)) {
            $json['tax_info'] = $this->taxInfo;
        }
        if (isset($this->address)) {
            $json['address'] = $this->address;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
