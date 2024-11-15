<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use JsonSerializable;

class GeneralPayment implements JsonSerializable
{
    private ?ExperienceContext $experienceContext;

    public function __construct(private string $name, private string $countryCode)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Sets Country Code.
     * The [two-character ISO 3166-1 code](/api/rest/reference/country-codes/) that identifies the country
     * or region.<blockquote><strong>Note:</strong> The country code for Great Britain is <code>GB</code>
     * and not <code>UK</code> as used in the top-level domain names for that country. Use the `C2` country
     * code for China worldwide for comparable uncontrolled price (CUP) method, bank card, and cross-border
     * transactions.</blockquote>
     *
     * @required
     * @maps country_code
     */
    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    public function getExperienceContext(): ?ExperienceContext
    {
        return $this->experienceContext;
    }

    public function setExperienceContext(?ExperienceContext $experienceContext): self
    {
        $this->experienceContext = $experienceContext;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = [
            'name' => $this->name,
            'country_code' => $this->countryCode,
        ];
        if (isset($this->experienceContext)) {
            $json['experience_context'] = $this->experienceContext;
        }

        return $json;
    }
}
