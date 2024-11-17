<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalCardVerificationMethod;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class CardVerification implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute(key: 'method', class: PayPalCardVerificationMethod::class)]
    private ?PayPalCardVerificationMethod $method;

    public function __construct(?PayPalCardVerificationMethod $method = PayPalCardVerificationMethod::SCA_WHEN_REQUIRED)
    {
        $this->method = $method;
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
