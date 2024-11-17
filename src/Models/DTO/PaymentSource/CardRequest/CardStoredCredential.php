<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalPaymentInitiator;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalStoredPaymentSourcePaymentType;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalStoredPaymentSourceUsageType;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class CardStoredCredential implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute('payment_initiator', PayPalPaymentInitiator::class)]
    private PayPalPaymentInitiator $paymentInitiator;
    #[ArrayMappingAttribute('payment_type', PayPalStoredPaymentSourcePaymentType::class)]
    private PayPalStoredPaymentSourcePaymentType $paymentType;
    #[ArrayMappingAttribute('usage', PayPalStoredPaymentSourceUsageType::class)]
    private ?PayPalStoredPaymentSourceUsageType $usage = PayPalStoredPaymentSourceUsageType::DERIVED;
    #[ArrayMappingAttribute('previous_network_transaction_reference', NetworkTransactionReference::class)]
    private ?NetworkTransactionReference $previousNetworkTransactionReference;

    public function __construct(
        PayPalPaymentInitiator $paymentInitiator,
        PayPalStoredPaymentSourcePaymentType $paymentType
    ) {
        $this->paymentInitiator = $paymentInitiator;
        $this->paymentType = $paymentType;
    }

    public function getPaymentInitiator(): PayPalPaymentInitiator
    {
        return $this->paymentInitiator;
    }

    public function setPaymentInitiator(PayPalPaymentInitiator $paymentInitiator): self
    {
        $this->paymentInitiator = $paymentInitiator;
        return $this;
    }

    public function getPaymentType(): PayPalStoredPaymentSourcePaymentType
    {
        return $this->paymentType;
    }

    public function setPaymentType(PayPalStoredPaymentSourcePaymentType $paymentType): self
    {
        $this->paymentType = $paymentType;
        return $this;
    }

    public function getUsage(): ?PayPalStoredPaymentSourceUsageType
    {
        return $this->usage;
    }

    public function setUsage(?PayPalStoredPaymentSourceUsageType $usage): self
    {
        $this->usage = $usage;
        return $this;
    }

    public function getPreviousNetworkTransactionReference(): ?NetworkTransactionReference
    {
        return $this->previousNetworkTransactionReference;
    }

    public function setPreviousNetworkTransactionReference(
        ?NetworkTransactionReference $previousNetworkTransactionReference
    ): self {
        $this->previousNetworkTransactionReference = $previousNetworkTransactionReference;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [
            'payment_initiator' => $this->paymentInitiator->value,
            'payment_type' => $this->paymentType->value,
        ];
        if (isset($this->usage)) {
            $json['usage'] = $this->usage->value;
        }
        if (isset($this->previousNetworkTransactionReference)) {
            $json['previous_network_transaction_reference'] = $this->previousNetworkTransactionReference;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }

    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new self(
            PayPalPaymentInitiator::tryFrom($data['payment_initiator']),
            PayPalStoredPaymentSourcePaymentType::tryFrom($data['payment_type'])
        ), $data);
    }
}
