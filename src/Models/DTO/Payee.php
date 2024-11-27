<?php

namespace SytxLabs\PayPal\Models\DTO;

use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Payee implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('email_address')]
    private ?string $email;
    #[ArrayMappingAttribute('merchant_id')]
    private ?string $merchantId;
    #[ArrayMappingAttribute('reference_id')]
    private ?string $referenceId;
    #[ArrayMappingAttribute('shipping_detail', ShippingDetail::class)]
    private ?ShippingDetail $shippingDetail;

    public function __construct(?string $email = null, ?string $merchantId = null, ?string $referenceId = null, ?ShippingDetail $shippingDetail = null)
    {
        $this->email = $email;
        $this->merchantId = $merchantId;
        $this->referenceId = $referenceId;
        $this->shippingDetail = $shippingDetail;
        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid email address');
        }
        if ($merchantId !== null && preg_match('/^[2-9A-HJ-NP-Z]/', $merchantId) !== 1) {
            throw new InvalidArgumentException('Invalid merchant ID');
        }

        $this->referenceId = Str::snake(Str::lower($this->referenceId));
    }

    public function getEmailAddress(): ?string
    {
        return $this->email;
    }

    public function setEmailAddress(?string $emailAddress): void
    {
        if ($emailAddress !== null && filter_var($emailAddress, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid email address');
        }
        $this->email = $emailAddress;
    }

    public function getMerchantId(): ?string
    {
        return $this->merchantId;
    }

    public function setMerchantId(?string $merchantId): self
    {
        if ($merchantId !== null && preg_match('/^[2-9A-HJ-NP-Z]/', $merchantId) !== 1) {
            throw new InvalidArgumentException('Invalid merchant ID');
        }
        $this->merchantId = $merchantId;
        return $this;
    }

    public function getReferenceId(): ?string
    {
        return $this->referenceId;
    }

    public function setReferenceId(?string $referenceId): self
    {
        $this->referenceId = Str::snake(Str::lower($referenceId));
        return $this;
    }

    public function getShippingDetail(): ?ShippingDetail
    {
        return $this->shippingDetail;
    }

    public function setShippingDetail(?ShippingDetail $shippingDetail): self
    {
        $this->shippingDetail = $shippingDetail;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->email)) {
            $json['email_address'] = $this->email;
        }
        if (isset($this->merchantId)) {
            $json['merchant_id'] = $this->merchantId;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
