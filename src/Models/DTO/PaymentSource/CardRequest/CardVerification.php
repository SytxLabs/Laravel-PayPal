<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalCardVerificationMethod;

class CardVerification implements JsonSerializable
{
    public function __construct(private ?PayPalCardVerificationMethod $method = PayPalCardVerificationMethod::SCA_WHEN_REQUIRED)
    {
    }

    public function getMethod(): ?PayPalCardVerificationMethod
    {
        return $this->method;
    }

    public function setMethod(?PayPalCardVerificationMethod $method): void
    {
        $this->method = $method;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->method)) {
            $json['method'] = $this->method->value;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
