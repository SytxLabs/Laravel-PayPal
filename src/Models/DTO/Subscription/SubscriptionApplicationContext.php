<?php

namespace SytxLabs\PayPal\Models\DTO\Subscription;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\Subscription\ShippingPreference;
use SytxLabs\PayPal\Enums\DTO\Subscription\SubscriberUserAction;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class SubscriptionApplicationContext implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('brand_name')]
    private ?string $brandName = null;
    #[ArrayMappingAttribute('locale')]
    private ?string $locale = null;
    #[ArrayMappingAttribute('shipping_preference', ShippingPreference::class)]
    private ?ShippingPreference $shippingPreference = ShippingPreference::NO_SHIPPING;
    #[ArrayMappingAttribute('user_action', SubscriberUserAction::class)]
    private ?SubscriberUserAction $userAction = SubscriberUserAction::SUBSCRIBE_NOW;
    #[ArrayMappingAttribute('return_url')]
    private ?string $returnUrl = null;
    #[ArrayMappingAttribute('cancel_url')]
    private ?string $cancelUrl = null;

    public function getBrandName(): ?string
    {
        return $this->brandName;
    }

    public function setBrandName(?string $brandName): self
    {
        $this->brandName = $brandName;
        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    public function getShippingPreference(): ?ShippingPreference
    {
        return $this->shippingPreference;
    }

    public function setShippingPreference(?ShippingPreference $shippingPreference): self
    {
        $this->shippingPreference = $shippingPreference;
        return $this;
    }

    public function getUserAction(): ?SubscriberUserAction
    {
        return $this->userAction;
    }

    public function setUserAction(?SubscriberUserAction $userAction): self
    {
        $this->userAction = $userAction;
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
        if (isset($this->userAction)) {
            $json['user_action'] = $this->userAction->value;
        }
        if (isset($this->returnUrl)) {
            $json['return_url'] = $this->returnUrl;
        }
        if (isset($this->cancelUrl)) {
            $json['cancel_url'] = $this->cancelUrl;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
