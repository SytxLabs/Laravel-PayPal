<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\PayPalWallet;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalPaymentTokenCustomerType;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalPaymentTokenUsagePattern;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class PayPalWalletVaultInstruction implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute('store_in_vault')]
    private bool $storeInVault = false;
    #[ArrayMappingAttribute('description')]
    private ?string $description;
    #[ArrayMappingAttribute('usage_pattern')]
    private ?string $usagePattern;
    #[ArrayMappingAttribute('usage_type', PayPalPaymentTokenUsagePattern::class)]
    private PayPalPaymentTokenUsagePattern $usageType;
    #[ArrayMappingAttribute('customer_type', PayPalPaymentTokenCustomerType::class)]
    private ?PayPalPaymentTokenCustomerType $customerType = PayPalPaymentTokenCustomerType::CONSUMER;
    #[ArrayMappingAttribute('permit_multiple_payment_tokens')]
    private ?bool $permitMultiplePaymentTokens = false;

    public function __construct(PayPalPaymentTokenUsagePattern $usageType)
    {
        $this->usageType = $usageType;
    }

    public function getStoreInVault(): bool
    {
        return $this->storeInVault;
    }

    public function setStoreInVault(bool $storeInVault): self
    {
        $this->storeInVault = $storeInVault;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getUsagePattern(): ?string
    {
        return $this->usagePattern;
    }

    public function setUsagePattern(?string $usagePattern): self
    {
        $this->usagePattern = $usagePattern;
        return $this;
    }

    public function getUsageType(): PayPalPaymentTokenUsagePattern
    {
        return $this->usageType;
    }

    public function setUsageType(PayPalPaymentTokenUsagePattern $usageType): self
    {
        $this->usageType = $usageType;
        return $this;
    }

    public function getCustomerType(): ?PayPalPaymentTokenCustomerType
    {
        return $this->customerType;
    }

    public function setCustomerType(?PayPalPaymentTokenCustomerType $customerType): self
    {
        $this->customerType = $customerType;
        return $this;
    }

    public function getPermitMultiplePaymentTokens(): ?bool
    {
        return $this->permitMultiplePaymentTokens;
    }

    public function setPermitMultiplePaymentTokens(?bool $permitMultiplePaymentTokens): self
    {
        $this->permitMultiplePaymentTokens = $permitMultiplePaymentTokens;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = [
            'usage_type' => $this->usageType->value,
        ];
        if ($this->storeInVault) {
            $json['store_in_vault'] = 'ON_SUCCESS';
        }
        if (isset($this->description)) {
            $json['description'] = $this->description;
        }
        if (isset($this->usagePattern)) {
            $json['usage_pattern'] = $this->usagePattern;
        }
        if (isset($this->customerType)) {
            $json['customer_type'] = $this->customerType->value;
        }
        if (isset($this->permitMultiplePaymentTokens)) {
            $json['permit_multiple_payment_tokens'] = $this->permitMultiplePaymentTokens;
        }

        return $json;
    }

    public static function fromArray(array $data): static
    {
        if (isset($data['store_in_vault'])) {
            $data['store_in_vault'] = $data['store_in_vault'] === 'ON_SUCCESS';
        }
        return self::fromArrayInternal(new self(PayPalPaymentTokenUsagePattern::tryFrom($data['usage_type'])), $data);
    }
}
