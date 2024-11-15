<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalPaymentInitiator;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalStoredPaymentSourcePaymentType;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalStoredPaymentSourceUsageType;

class CardStoredCredential implements JsonSerializable
{
    private PayPalPaymentInitiator $paymentInitiator;
    private PayPalStoredPaymentSourcePaymentType $paymentType;
    private ?PayPalStoredPaymentSourceUsageType $usage = PayPalStoredPaymentSourceUsageType::DERIVED;
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
}
