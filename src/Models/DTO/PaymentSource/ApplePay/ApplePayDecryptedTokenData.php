<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\ApplePay;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalApplePayPaymentDataType;
use SytxLabs\PayPal\Models\DTO\Money;

class ApplePayDecryptedTokenData implements JsonSerializable
{
    private ?Money $transactionAmount;
    private ApplePayTokenizedCard $tokenizedCard;
    private ?string $deviceManufacturerId;
    private ?PayPalApplePayPaymentDataType $paymentDataType;
    private ?ApplePayPaymentData $paymentData;

    public function __construct(ApplePayTokenizedCard $tokenizedCard)
    {
        $this->tokenizedCard = $tokenizedCard;
    }

    public function getTransactionAmount(): ?Money
    {
        return $this->transactionAmount;
    }

    public function setTransactionAmount(?Money $transactionAmount): self
    {
        $this->transactionAmount = $transactionAmount;
        return $this;
    }

    public function getTokenizedCard(): ApplePayTokenizedCard
    {
        return $this->tokenizedCard;
    }

    public function setTokenizedCard(ApplePayTokenizedCard $tokenizedCard): self
    {
        $this->tokenizedCard = $tokenizedCard;
        return $this;
    }

    public function getDeviceManufacturerId(): ?string
    {
        return $this->deviceManufacturerId;
    }

    public function setDeviceManufacturerId(?string $deviceManufacturerId): self
    {
        $this->deviceManufacturerId = $deviceManufacturerId;
        return $this;
    }

    public function getPaymentDataType(): ?PayPalApplePayPaymentDataType
    {
        return $this->paymentDataType;
    }

    public function setPaymentDataType(?PayPalApplePayPaymentDataType $paymentDataType): self
    {
        $this->paymentDataType = $paymentDataType;
        return $this;
    }

    public function getPaymentData(): ?ApplePayPaymentData
    {
        return $this->paymentData;
    }

    public function setPaymentData(?ApplePayPaymentData $paymentData): self
    {
        $this->paymentData = $paymentData;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = [
            'tokenized_card' => $this->tokenizedCard,
        ];
        if (isset($this->transactionAmount)) {
            $json['transaction_amount'] = $this->transactionAmount;
        }
        if (isset($this->deviceManufacturerId)) {
            $json['device_manufacturer_id'] = $this->deviceManufacturerId;
        }
        if (isset($this->paymentDataType)) {
            $json['payment_data_type'] = $this->paymentDataType->value;
        }
        if (isset($this->paymentData)) {
            $json['payment_data'] = $this->paymentData;
        }

        return $json;
    }
}
