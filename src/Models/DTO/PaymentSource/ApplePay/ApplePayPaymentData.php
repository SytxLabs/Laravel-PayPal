<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\ApplePay;

use JsonSerializable;
use stdClass;

class ApplePayPaymentData implements JsonSerializable
{
    private ?string $cryptogram;
    private ?string $eciIndicator;
    private ?string $emvData;
    private ?string $pin;

    public function getCryptogram(): ?string
    {
        return $this->cryptogram;
    }

    public function setCryptogram(?string $cryptogram): void
    {
        $this->cryptogram = $cryptogram;
    }

    public function getEciIndicator(): ?string
    {
        return $this->eciIndicator;
    }

    public function setEciIndicator(?string $eciIndicator): void
    {
        $this->eciIndicator = $eciIndicator;
    }

    public function getEmvData(): ?string
    {
        return $this->emvData;
    }

    public function setEmvData(?string $emvData): void
    {
        $this->emvData = $emvData;
    }

    public function getPin(): ?string
    {
        return $this->pin;
    }

    public function setPin(?string $pin): void
    {
        $this->pin = $pin;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->cryptogram)) {
            $json['cryptogram'] = $this->cryptogram;
        }
        if (isset($this->eciIndicator)) {
            $json['eci_indicator'] = $this->eciIndicator;
        }
        if (isset($this->emvData)) {
            $json['emv_data'] = $this->emvData;
        }
        if (isset($this->pin)) {
            $json['pin'] = $this->pin;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
