<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\AssuranceDetails;
use SytxLabs\PayPal\Models\DTO\PaymentSource\GooglePay\GooglePayCard;
use SytxLabs\PayPal\Models\DTO\PaymentSource\GooglePay\GooglePayCardAttributes;
use SytxLabs\PayPal\Models\DTO\PaymentSource\GooglePay\GooglePayDecryptedTokenData;
use SytxLabs\PayPal\Models\DTO\PhoneNumber;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class GooglePay implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('name')]
    private ?string $name;
    #[ArrayMappingAttribute('email_address')]
    private ?string $emailAddress;
    #[ArrayMappingAttribute('phone_number', PhoneNumber::class)]
    private ?PhoneNumber $phoneNumber;
    #[ArrayMappingAttribute('card', GooglePayCard::class)]
    private ?GooglePayCard $card;
    #[ArrayMappingAttribute('decrypted_token', GooglePayDecryptedTokenData::class)]
    private ?GooglePayDecryptedTokenData $decryptedToken;
    #[ArrayMappingAttribute('assurance_details', AssuranceDetails::class)]
    private ?AssuranceDetails $assuranceDetails;
    #[ArrayMappingAttribute('attributes', GooglePayCardAttributes::class)]
    private ?GooglePayCardAttributes $attributes;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(?string $emailAddress): self
    {
        $this->emailAddress = $emailAddress;
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

    public function getCard(): ?GooglePayCard
    {
        return $this->card;
    }

    public function setCard(?GooglePayCard $card): self
    {
        $this->card = $card;
        return $this;
    }

    public function getDecryptedToken(): ?GooglePayDecryptedTokenData
    {
        return $this->decryptedToken;
    }

    public function setDecryptedToken(?GooglePayDecryptedTokenData $decryptedToken): self
    {
        $this->decryptedToken = $decryptedToken;
        return $this;
    }

    public function getAssuranceDetails(): ?AssuranceDetails
    {
        return $this->assuranceDetails;
    }

    public function setAssuranceDetails(?AssuranceDetails $assuranceDetails): self
    {
        $this->assuranceDetails = $assuranceDetails;
        return $this;
    }

    public function getAttributes(): ?GooglePayCardAttributes
    {
        return $this->attributes;
    }

    public function setAttributes(?GooglePayCardAttributes $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->name)) {
            $json['name'] = $this->name;
        }
        if (isset($this->emailAddress)) {
            $json['email_address'] = $this->emailAddress;
        }
        if (isset($this->phoneNumber)) {
            $json['phone_number'] = $this->phoneNumber;
        }
        if (isset($this->card)) {
            $json['card'] = $this->card;
        }
        if (isset($this->decryptedToken)) {
            $json['decrypted_token'] = $this->decryptedToken;
        }
        if (isset($this->assuranceDetails)) {
            $json['assurance_details'] = $this->assuranceDetails;
        }
        if (isset($this->attributes)) {
            $json['attributes'] = $this->attributes;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
