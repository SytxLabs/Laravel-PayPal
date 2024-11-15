<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\PaymentSource\ApplePay\ApplePayAttributes;
use SytxLabs\PayPal\Models\DTO\PaymentSource\ApplePay\ApplePayDecryptedTokenData;
use SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest\CardStoredCredential;
use SytxLabs\PayPal\Models\DTO\PhoneNumber;

class ApplePay implements JsonSerializable
{
    private ?string $id;
    private ?string $name;
    private ?string $emailAddress;
    private ?PhoneNumber $phoneNumber;
    private ?ApplePayDecryptedTokenData $decryptedToken;
    private ?CardStoredCredential $storedCredential;
    private ?string $vaultId;
    private ?ApplePayAttributes $attributes;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

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
        if (
            $emailAddress !== null
            && (strlen($emailAddress) > 250 || preg_match('/^[\w-]+(?:\.[\w-]+)*@(?:[\w-]+\.)+[a-zA-Z]{2,7}$/', $emailAddress) !== 1)
        ) {
            throw new InvalidArgumentException('Invalid email address');
        }
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

    public function getDecryptedToken(): ?ApplePayDecryptedTokenData
    {
        return $this->decryptedToken;
    }

    public function setDecryptedToken(?ApplePayDecryptedTokenData $decryptedToken): self
    {
        $this->decryptedToken = $decryptedToken;
        return $this;
    }

    public function getStoredCredential(): ?CardStoredCredential
    {
        return $this->storedCredential;
    }

    public function setStoredCredential(?CardStoredCredential $storedCredential): self
    {
        $this->storedCredential = $storedCredential;
        return $this;
    }

    public function getVaultId(): ?string
    {
        return $this->vaultId;
    }

    public function setVaultId(?string $vaultId): self
    {
        $this->vaultId = $vaultId;
        return $this;
    }

    public function getAttributes(): ?ApplePayAttributes
    {
        return $this->attributes;
    }

    public function setAttributes(?ApplePayAttributes $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->id)) {
            $json['id'] = $this->id;
        }
        if (isset($this->name)) {
            $json['name'] = $this->name;
        }
        if (isset($this->emailAddress)) {
            $json['email_address'] = $this->emailAddress;
        }
        if (isset($this->phoneNumber)) {
            $json['phone_number'] = $this->phoneNumber;
        }
        if (isset($this->decryptedToken)) {
            $json['decrypted_token'] = $this->decryptedToken;
        }
        if (isset($this->storedCredential)) {
            $json['stored_credential'] = $this->storedCredential;
        }
        if (isset($this->vaultId)) {
            $json['vault_id'] = $this->vaultId;
        }
        if (isset($this->attributes)) {
            $json['attributes'] = $this->attributes;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
