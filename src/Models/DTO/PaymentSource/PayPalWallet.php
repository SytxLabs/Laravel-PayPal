<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Address;
use SytxLabs\PayPal\Models\DTO\Name;
use SytxLabs\PayPal\Models\DTO\PaymentSource\PayPalWallet\PayPalWalletAttributes;
use SytxLabs\PayPal\Models\DTO\PaymentSource\PayPalWallet\PayPalWalletExperienceContext;
use SytxLabs\PayPal\Models\DTO\PhoneNumberWithType;
use SytxLabs\PayPal\Models\DTO\TaxInfo;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class PayPalWallet implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute('vault_id')]
    private ?string $vaultId;
    #[ArrayMappingAttribute('email_address')]
    private ?string $emailAddress;
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
    #[ArrayMappingAttribute('attributes', PayPalWalletAttributes::class)]
    private ?PayPalWalletAttributes $attributes;
    #[ArrayMappingAttribute('experience_context', PayPalWalletExperienceContext::class)]
    private ?PayPalWalletExperienceContext $experienceContext;
    #[ArrayMappingAttribute('billing_agreement_id')]
    private ?string $billingAgreementId;

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

    /**
     * Sets Email Address.
     * The internationalized email address.<blockquote><strong>Note:</strong> Up to 64 characters are
     * allowed before and 255 characters are allowed after the <code>@</code> sign. However, the generally
     * accepted maximum length for an email address is 254 characters. The pattern verifies that an
     * unquoted <code>@</code> sign exists.</blockquote>
     *
     * @maps email_address
     */
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

    /**
     * Date Time of Birth. The datetime of Birth (yyyy-mm-ddThh.mm.ss+Z).
     */
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

    public function getAttributes(): ?PayPalWalletAttributes
    {
        return $this->attributes;
    }

    public function setAttributes(?PayPalWalletAttributes $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function getExperienceContext(): ?PayPalWalletExperienceContext
    {
        return $this->experienceContext;
    }

    public function setExperienceContext(?PayPalWalletExperienceContext $experienceContext): self
    {
        $this->experienceContext = $experienceContext;
        return $this;
    }

    public function getBillingAgreementId(): ?string
    {
        return $this->billingAgreementId;
    }

    public function setBillingAgreementId(?string $billingAgreementId): self
    {
        $this->billingAgreementId = $billingAgreementId;
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
        if (isset($this->attributes)) {
            $json['attributes'] = $this->attributes;
        }
        if (isset($this->experienceContext)) {
            $json['experience_context'] = $this->experienceContext;
        }
        if (isset($this->billingAgreementId)) {
            $json['billing_agreement_id'] = $this->billingAgreementId;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
