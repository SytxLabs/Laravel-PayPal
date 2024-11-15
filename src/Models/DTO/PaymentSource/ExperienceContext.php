<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalShippingPreference;

class ExperienceContext implements JsonSerializable
{
    private ?string $brandName;
    private ?string $locale;
    private ?PayPalShippingPreference $shippingPreference = PayPalShippingPreference::GET_FROM_FILE;
    private ?string $returnUrl;
    private ?string $cancelUrl;

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

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
