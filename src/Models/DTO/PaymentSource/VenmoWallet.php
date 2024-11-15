<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\PaymentSource\VenmoWallet\VenmoWalletAdditionalAttributes;
use SytxLabs\PayPal\Models\DTO\PaymentSource\VenmoWallet\VenmoWalletExperienceContext;

class VenmoWallet implements JsonSerializable
{
    private ?string $vaultId;
    private ?string $emailAddress;
    private ?VenmoWalletExperienceContext $experienceContext;
    private ?VenmoWalletAdditionalAttributes $attributes;

    public function getVaultId(): ?string
    {
        return $this->vaultId;
    }

    public function setVaultId(?string $vaultId): self
    {
        $this->vaultId = $vaultId;
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

    public function getExperienceContext(): ?VenmoWalletExperienceContext
    {
        return $this->experienceContext;
    }

    public function setExperienceContext(?VenmoWalletExperienceContext $experienceContext): self
    {
        $this->experienceContext = $experienceContext;
        return $this;
    }

    public function getAttributes(): ?VenmoWalletAdditionalAttributes
    {
        return $this->attributes;
    }

    public function setAttributes(?VenmoWalletAdditionalAttributes $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->vaultId)) {
            $json['vault_id'] = $this->vaultId;
        }
        if (isset($this->emailAddress)) {
            $json['email_address'] = $this->emailAddress;
        }
        if (isset($this->experienceContext)) {
            $json['experience_context'] = $this->experienceContext;
        }
        if (isset($this->attributes)) {
            $json['attributes'] = $this->attributes;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
