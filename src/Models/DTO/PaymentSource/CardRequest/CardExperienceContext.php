<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest;

use JsonSerializable;
use stdClass;

class CardExperienceContext implements JsonSerializable
{
    private ?string $returnUrl;
    private ?string $cancelUrl;

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(?string $returnUrl): self
    {
        $this->returnUrl = $returnUrl;
        return $this;
    }

    public function getCancelUrl(): ?string
    {
        return $this->cancelUrl;
    }

    public function setCancelUrl(?string $cancelUrl): self
    {
        $this->cancelUrl = $cancelUrl;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->returnUrl)) {
            $json['return_url'] = $this->returnUrl;
        }
        if (isset($this->cancelUrl)) {
            $json['cancel_url'] = $this->cancelUrl;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
