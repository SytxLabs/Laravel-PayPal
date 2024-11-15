<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\VenmoWallet;

use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalVenmoPaymentTokenCustomerType;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalVenmoPaymentTokenUsagePattern;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalVenmoPaymentTokenUsageType;
use SytxLabs\PayPal\Models\DTO\PaymentSource\VaultInstructionBase;

class VenmoWalletVaultAttributes extends VaultInstructionBase
{
    private ?string $description;
    private ?PayPalVenmoPaymentTokenUsagePattern $usagePattern;
    private ?PayPalVenmoPaymentTokenCustomerType $customerType = PayPalVenmoPaymentTokenCustomerType::CONSUMER;
    private ?bool $permitMultiplePaymentTokens = false;

    public function __construct(
        bool $storeInVault = false,
        private PayPalVenmoPaymentTokenUsageType $usageType = PayPalVenmoPaymentTokenUsageType::MERCHANT
    ) {
        parent::__construct($storeInVault);
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

    public function getUsagePattern(): ?PayPalVenmoPaymentTokenUsagePattern
    {
        return $this->usagePattern;
    }

    public function setUsagePattern(?PayPalVenmoPaymentTokenUsagePattern $usagePattern): self
    {
        $this->usagePattern = $usagePattern;
        return $this;
    }

    public function getUsageType(): PayPalVenmoPaymentTokenUsageType
    {
        return $this->usageType;
    }

    public function setUsageType(PayPalVenmoPaymentTokenUsageType $usageType): self
    {
        $this->usageType = $usageType;
        return $this;
    }

    public function getCustomerType(): ?PayPalVenmoPaymentTokenCustomerType
    {
        return $this->customerType;
    }

    public function setCustomerType(?PayPalVenmoPaymentTokenCustomerType $customerType): self
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

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = parent::jsonSerialize($asArrayWhenEmpty);
        if (isset($this->description)) {
            $json['description'] = $this->description;
        }
        if (isset($this->usagePattern)) {
            $json['usage_pattern'] = $this->usagePattern->value;
        }
        $json['usage_type'] = $this->usageType->value;
        if (isset($this->customerType)) {
            $json['customer_type'] = $this->customerType->value;
        }
        if (isset($this->permitMultiplePaymentTokens)) {
            $json['permit_multiple_payment_tokens'] = $this->permitMultiplePaymentTokens;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
