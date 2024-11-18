<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalExperienceLandingPage;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalExperienceUserAction;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalShippingPreference;
use SytxLabs\PayPal\Models\DTO\PaymentSource\StoredPaymentSource;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class ApplicationContext implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('brand_name')]
    private ?string $brandName = null;
    #[ArrayMappingAttribute('locale')]
    private ?string $locale = null;
    #[ArrayMappingAttribute('landing_page', PayPalExperienceLandingPage::class)]
    private ?PayPalExperienceLandingPage $landingPage = PayPalExperienceLandingPage::NO_PREFERENCE;
    #[ArrayMappingAttribute('shipping_preference', PayPalShippingPreference::class)]
    private ?PayPalShippingPreference $shippingPreference = PayPalShippingPreference::GET_FROM_FILE;
    #[ArrayMappingAttribute('user_action', PayPalExperienceUserAction::class)]
    private ?PayPalExperienceUserAction $userAction = PayPalExperienceUserAction::CONTINUE;
    #[ArrayMappingAttribute('payment_method', PaymentMethodPreference::class)]
    private ?PaymentMethodPreference $paymentMethod = null;
    #[ArrayMappingAttribute('return_url')]
    private ?string $returnUrl = null;
    #[ArrayMappingAttribute('cancel_url')]
    private ?string $cancelUrl = null;
    #[ArrayMappingAttribute('stored_payment_source', StoredPaymentSource::class)]
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

    public function getLandingPage(): ?PayPalExperienceLandingPage
    {
        return $this->landingPage;
    }

    public function setLandingPage(?PayPalExperienceLandingPage $landingPage): self
    {
        $this->landingPage = $landingPage;
        return $this;
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

    public function getUserAction(): ?PayPalExperienceUserAction
    {
        return $this->userAction;
    }

    public function setUserAction(?PayPalExperienceUserAction $userAction): self
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
