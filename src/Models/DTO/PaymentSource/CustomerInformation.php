<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\PhoneNumberWithType;

class CustomerInformation implements JsonSerializable
{
    private ?string $id;
    private ?string $emailAddress;
    private ?PhoneNumberWithType $phone;
    private ?string $merchantCustomerId;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(?string $emailAddress): self
    {
        if (
            $emailAddress !== null &&
            (strlen($emailAddress) > 250 || preg_match('/^[\w-]+(?:\.[\w-]+)*@(?:[\w-]+\.)+[a-zA-Z]{2,7}$/', $emailAddress) !== 1)
        ) {
            throw new InvalidArgumentException('Invalid email address');
        }
        $this->emailAddress = $emailAddress;
        return $this;
    }

    public function getPhone(): ?PhoneNumberWithType
    {
        return $this->phone;
    }

    public function setPhone(?PhoneNumberWithType $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getMerchantCustomerId(): ?string
    {
        return $this->merchantCustomerId;
    }

    public function setMerchantCustomerId(?string $merchantCustomerId): self
    {
        $this->merchantCustomerId = $merchantCustomerId;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->id)) {
            $json['id'] = $this->id;
        }
        if (isset($this->emailAddress)) {
            $json['email_address'] = $this->emailAddress;
        }
        if (isset($this->phone)) {
            $json['phone'] = $this->phone;
        }
        if (isset($this->merchantCustomerId)) {
            $json['merchant_customer_id'] = $this->merchantCustomerId;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
