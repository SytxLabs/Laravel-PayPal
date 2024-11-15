<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\BLIKPayment;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalShippingPreference;

class BLIKExperienceContext implements JsonSerializable
{
    private ?string $brandName;
    private ?string $locale;
    private ?PayPalShippingPreference $shippingPreference;
    private ?string $returnUrl;
    private ?string $cancelUrl;
    private ?string $consumerIp;
    private ?string $consumerUserAgent;

    public function getBrandName(): ?string
    {
        return $this->brandName;
    }

    public function setBrandName(?string $brandName): void
    {
        $this->brandName = $brandName;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function getShippingPreference(): ?PayPalShippingPreference
    {
        return $this->shippingPreference;
    }

    public function setShippingPreference(?PayPalShippingPreference $shippingPreference): self
    {
        $this->shippingPreference = $shippingPreference;
        return $this;
    }

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

    public function getConsumerIp(): ?string
    {
        return $this->consumerIp;
    }

    public function setConsumerIp(?string $consumerIp): self
    {
        $this->consumerIp = $consumerIp;
        return $this;
    }

    public function getConsumerUserAgent(): ?string
    {
        return $this->consumerUserAgent;
    }

    public function setConsumerUserAgent(?string $consumerUserAgent): self
    {
        $this->consumerUserAgent = $consumerUserAgent;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->brandName)) {
            $json['brand_name'] = $this->brandName;
        }
        if (isset($this->locale)) {
            $json['locale'] = $this->locale;
        }
        if (isset($this->shippingPreference)) {
            $json['shipping_preference'] = $this->shippingPreference->value;
        }
        if (isset($this->returnUrl)) {
            $json['return_url'] = $this->returnUrl;
        }
        if (isset($this->cancelUrl)) {
            $json['cancel_url'] = $this->cancelUrl;
        }
        if (isset($this->consumerIp)) {
            $json['consumer_ip'] = $this->consumerIp;
        }
        if (isset($this->consumerUserAgent)) {
            $json['consumer_user_agent'] = $this->consumerUserAgent;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
