<?php

namespace SytxLabs\PayPal\Models\DTO;

use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonSerializable;
use stdClass;

class Payee implements JsonSerializable
{
    public function __construct(private ?string $email, private ?string $merchantId = null, private ?string $referenceId = null, private ?PayeeShippingDetail $shippingDetail = null)
    {
        if ($email !== null && preg_match('/^[\w-]+(?:\.[\w-]+)*@(?:[\w-]+\.)+[a-zA-Z]{2,7}$/', $email) !== 1) {
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
        if ($emailAddress !== null && preg_match('/^[\w-]+(?:\.[\w-]+)*@(?:[\w-]+\.)+[a-zA-Z]{2,7}$/', $emailAddress) !== 1) {
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

    public function getShippingDetail(): ?PayeeShippingDetail
    {
        return $this->shippingDetail;
    }

    public function setShippingDetail(?PayeeShippingDetail $shippingDetail): self
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

    public static function fromArray(array $data): self
    {
        $payee = new self($data['email_address'] ?? null);
        $payee->setMerchantId($data['merchant_id'] ?? null);
        return $payee;
    }
}
