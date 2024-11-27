<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use InvalidArgumentException;
use JsonSerializable;
use SytxLabs\PayPal\Models\DTO\PaymentSource\BLIKPayment\BLIKExperienceContext;
use SytxLabs\PayPal\Models\DTO\PaymentSource\BLIKPayment\BLIKOneClickPayment;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class BLIKPayment implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute('name')]
    private string $name;
    #[ArrayMappingAttribute('country_code')]
    private string $countryCode;
    #[ArrayMappingAttribute('email')]
    private ?string $email;
    #[ArrayMappingAttribute('experience_context', BLIKExperienceContext::class)]
    private ?BLIKExperienceContext $experienceContext;
    #[ArrayMappingAttribute('level_0')]
    private ?string $level0;
    #[ArrayMappingAttribute('one_click', BLIKOneClickPayment::class)]
    private ?BLIKOneClickPayment $oneClick;

    public function __construct(string $name, string $countryCode)
    {
        $this->name = $name;
        $this->countryCode = $countryCode;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        if (
            $email !== null
            && (strlen($email) > 250 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)
        ) {
            throw new InvalidArgumentException('Invalid email address');
        }
        $this->email = $email;
        return $this;
    }

    public function getExperienceContext(): ?BLIKExperienceContext
    {
        return $this->experienceContext;
    }

    public function setExperienceContext(?BLIKExperienceContext $experienceContext): self
    {
        $this->experienceContext = $experienceContext;
        return $this;
    }

    public function getLevel0(): ?string
    {
        return $this->level0;
    }

    public function setLevel0(?string $authToken): self
    {
        $this->level0 = $authToken;
        return $this;
    }

    public function getOneClick(): ?BLIKOneClickPayment
    {
        return $this->oneClick;
    }

    public function setOneClick(?BLIKOneClickPayment $oneClick): self
    {
        $this->oneClick = $oneClick;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array
    {
        $json = [
            'name' => $this->name,
            'country_code' => $this->countryCode,
        ];
        if (isset($this->email)) {
            $json['email'] = $this->email;
        }
        if (isset($this->experienceContext)) {
            $json['experience_context'] = $this->experienceContext;
        }
        if (isset($this->level0)) {
            $json['level_0']['auth_code'] = $this->level0;
        }
        if (isset($this->oneClick)) {
            $json['one_click'] = $this->oneClick;
        }

        return $json;
    }

    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new self($data['name'], $data['country_code']), $data);
    }
}
