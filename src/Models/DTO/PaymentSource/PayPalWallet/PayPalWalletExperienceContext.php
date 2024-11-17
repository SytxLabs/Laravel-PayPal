<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\PayPalWallet;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalExperienceLandingPage;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalExperienceUserAction;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalPayeePaymentMethodPreference;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalShippingPreference;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class PayPalWalletExperienceContext implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('brand_name')]
    private ?string $brandName;
    #[ArrayMappingAttribute('locale')]
    private ?string $locale;
    #[ArrayMappingAttribute('shipping_preference', PayPalShippingPreference::class)]
    private ?PayPalShippingPreference $shippingPreference = PayPalShippingPreference::GET_FROM_FILE;
    #[ArrayMappingAttribute('return_url')]
    private ?string $returnUrl;
    #[ArrayMappingAttribute('cancel_url')]
    private ?string $cancelUrl;
    #[ArrayMappingAttribute('landing_page', PayPalExperienceLandingPage::class)]
    private ?PayPalExperienceLandingPage $landingPage = PayPalExperienceLandingPage::NO_PREFERENCE;
    #[ArrayMappingAttribute('user_action', PayPalExperienceUserAction::class)]
    private ?PayPalExperienceUserAction $userAction = PayPalExperienceUserAction::CONTINUE;
    #[ArrayMappingAttribute('payment_method_preference', PayPalPayeePaymentMethodPreference::class)]
    private ?PayPalPayeePaymentMethodPreference $paymentMethodPreference = PayPalPayeePaymentMethodPreference::UNRESTRICTED;

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

    /**
     * Sets Locale.
     * The [language tag](https://tools.ietf.org/html/bcp47#section-2) for the language in which to
     * localize the error-related strings, such as messages, issues, and suggested actions. The tag is made
     * up of the [ISO 639-2 language code](https://www.loc.gov/standards/iso639-2/php/code_list.php), the
     * optional [ISO-15924 script tag](https://www.unicode.org/iso15924/codelists.html), and the [ISO-3166
     * alpha-2 country code](/api/rest/reference/country-codes/) or [M49 region code](https://unstats.un.
     * org/unsd/methodology/m49/).
     *
     * @maps locale
     */
    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;
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

    public function getLandingPage(): ?PayPalExperienceLandingPage
    {
        return $this->landingPage;
    }

    public function setLandingPage(?PayPalExperienceLandingPage $landingPage): self
    {
        $this->landingPage = $landingPage;
        return $this;
    }

    public function getUserAction(): ?PayPalExperienceUserAction
    {
        return $this->userAction;
    }

    public function setUserAction(?PayPalExperienceLandingPage $userAction): self
    {
        $this->userAction = $userAction;
        return $this;
    }

    public function getPaymentMethodPreference(): ?PayPalPayeePaymentMethodPreference
    {
        return $this->paymentMethodPreference;
    }

    public function setPaymentMethodPreference(?PayPalPayeePaymentMethodPreference $paymentMethodPreference): self
    {
        $this->paymentMethodPreference = $paymentMethodPreference;
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
        if (isset($this->landingPage)) {
            $json['landing_page'] = $this->landingPage->value;
        }
        if (isset($this->userAction)) {
            $json['user_action'] = $this->userAction->value;
        }
        if (isset($this->paymentMethodPreference)) {
            $json['payment_method_preference'] = $this->paymentMethodPreference->value;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
