<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Address;
use SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest\CardAttributes;
use SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest\CardExperienceContext;
use SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest\CardStoredCredential;
use SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest\NetworkToken;

class Card implements JsonSerializable
{
    private ?string $name;
    private ?string $number;
    private ?string $expiry;
    private ?string $securityCode;
    private ?Address $billingAddress;
    private ?CardAttributes $attributes;
    private ?string $vaultId;
    private ?string $singleUseToken;
    private ?CardStoredCredential $storedCredential;
    private ?NetworkToken $networkToken;
    private ?CardExperienceContext $experienceContext;

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
     * Sets Expiry.
     * The year and month, in ISO-8601 `YYYY-MM` date format. See [Internet date and time format](https:
     * //tools.ietf.org/html/rfc3339#section-5.6).
     *
     * @maps expiry
     */
    public function setExpiry(?string $expiry): self
    {
        if ($expiry !== null && strlen($expiry) !== 7) {
            throw new InvalidArgumentException('Expiry must be in the format YYYY-MM');
        }
        $this->expiry = $expiry;
        return $this;
    }

    public function getSecurityCode(): ?string
    {
        return $this->securityCode;
    }

    public function setSecurityCode(?string $securityCode): self
    {
        $this->securityCode = $securityCode;
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

    public function getAttributes(): ?CardAttributes
    {
        return $this->attributes;
    }

    public function setAttributes(?CardAttributes $attributes): self
    {
        $this->attributes = $attributes;
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

    public function getSingleUseToken(): ?string
    {
        return $this->singleUseToken;
    }

    public function setSingleUseToken(?string $singleUseToken): self
    {
        $this->singleUseToken = $singleUseToken;
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

    public function getNetworkToken(): ?NetworkToken
    {
        return $this->networkToken;
    }

    public function setNetworkToken(?NetworkToken $networkToken): self
    {
        $this->networkToken = $networkToken;
        return $this;
    }

    public function getExperienceContext(): ?CardExperienceContext
    {
        return $this->experienceContext;
    }

    public function setExperienceContext(?CardExperienceContext $experienceContext): self
    {
        $this->experienceContext = $experienceContext;
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
        if (isset($this->securityCode)) {
            $json['security_code'] = $this->securityCode;
        }
        if (isset($this->billingAddress)) {
            $json['billing_address'] = $this->billingAddress;
        }
        if (isset($this->attributes)) {
            $json['attributes'] = $this->attributes;
        }
        if (isset($this->vaultId)) {
            $json['vault_id'] = $this->vaultId;
        }
        if (isset($this->singleUseToken)) {
            $json['single_use_token'] = $this->singleUseToken;
        }
        if (isset($this->storedCredential)) {
            $json['stored_credential'] = $this->storedCredential;
        }
        if (isset($this->networkToken)) {
            $json['network_token'] = $this->networkToken;
        }
        if (isset($this->experienceContext)) {
            $json['experience_context'] = $this->experienceContext;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
