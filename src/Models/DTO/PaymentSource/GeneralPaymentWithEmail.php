<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use InvalidArgumentException;
use JsonSerializable;

class GeneralPaymentWithEmail implements JsonSerializable
{
    private ?ExperienceContext $experienceContext;

    public function __construct(private string $name, private string $email, private string $countryCode)
    {
        if (strlen($this->email) > 250 || preg_match('/^[\w-]+(?:\.[\w-]+)*@(?:[\w-]+\.)+[a-zA-Z]{2,7}$/', $this->email) !== 1) {
            throw new InvalidArgumentException('Invalid email address');
        }
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        if (strlen($email) > 250 || preg_match('/^[\w-]+(?:\.[\w-]+)*@(?:[\w-]+\.)+[a-zA-Z]{2,7}$/', $email) !== 1) {
            throw new InvalidArgumentException('Invalid email address');
        }
        $this->email = $email;
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
            'email' => $this->email,
            'country_code' => $this->countryCode,
        ];
        if (isset($this->experienceContext)) {
            $json['experience_context'] = $this->experienceContext;
        }

        return $json;
    }
}
