<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalExperienceLandingPage;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalExperienceUserAction;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalShippingPreference;
use SytxLabs\PayPal\Models\DTO\PaymentSource\StoredPaymentSource;

class ApplicationContext implements JsonSerializable
{
    private ?string $brandName = null;
    private ?string $locale = null;
    private ?PayPalExperienceLandingPage $landingPage = PayPalExperienceLandingPage::NO_PREFERENCE;
    private ?PayPalShippingPreference $shippingPreference = PayPalShippingPreference::GET_FROM_FILE;
    private ?PayPalExperienceUserAction $userAction = PayPalExperienceUserAction::CONTINUE;
    private ?PaymentMethodPreference $paymentMethod = null;
    private ?string $returnUrl = null;
    private ?string $cancelUrl = null;
    private ?StoredPaymentSource $storedPaymentSource = null;

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

    public function getLandingPage(): ?string
    {
        return $this->landingPage;
    }

    public function setLandingPage(?string $landingPage): self
    {
        $this->landingPage = $landingPage;
        return $this;
    }

    public function getShippingPreference(): ?string
    {
        return $this->shippingPreference;
    }

    public function setShippingPreference(?string $shippingPreference): self
    {
        $this->shippingPreference = $shippingPreference;
        return $this;
    }

    public function getUserAction(): ?string
    {
        return $this->userAction;
    }

    public function setUserAction(?string $userAction): self
    {
        $this->userAction = $userAction;
        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethodPreference
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?PaymentMethodPreference $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
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

    public function getStoredPaymentSource(): ?StoredPaymentSource
    {
        return $this->storedPaymentSource;
    }

    public function setStoredPaymentSource(?StoredPaymentSource $storedPaymentSource): self
    {
        $this->storedPaymentSource = $storedPaymentSource;
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
        if (isset($this->landingPage)) {
            $json['landing_page'] = $this->landingPage->value;
        }
        if (isset($this->shippingPreference)) {
            $json['shipping_preference'] = $this->shippingPreference->value;
        }
        if (isset($this->userAction)) {
            $json['user_action'] = $this->userAction->value;
        }
        if (isset($this->paymentMethod)) {
            $json['payment_method'] = $this->paymentMethod;
        }
        if (isset($this->returnUrl)) {
            $json['return_url'] = $this->returnUrl;
        }
        if (isset($this->cancelUrl)) {
            $json['cancel_url'] = $this->cancelUrl;
        }
        if (isset($this->storedPaymentSource)) {
            $json['stored_payment_source'] = $this->storedPaymentSource;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
