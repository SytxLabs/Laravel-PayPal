<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\GooglePay;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest\CardVerification;

class GooglePayCardAttributes implements JsonSerializable
{
    public function __construct(private ?CardVerification $verification = null)
    {
    }

    public function getVerification(): ?CardVerification
    {
        return $this->verification;
    }

    public function setVerification(?CardVerification $verification): void
    {
        $this->verification = $verification;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->verification)) {
            $json['verification'] = $this->verification;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
