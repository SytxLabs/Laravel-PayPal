<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\GooglePay;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest\CardVerification;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class GooglePayCardAttributes implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('verification', CardVerification::class)]
    private ?CardVerification $verification;

    public function __construct(?CardVerification $verification = null)
    {
        $this->verification = $verification;
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
