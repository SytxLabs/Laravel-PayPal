<?php

namespace SytxLabs\PayPal\Models\DTO\Subscription;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Address;
use SytxLabs\PayPal\Models\DTO\Name;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Subscriber implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('name', Name::class)]
    private ?Name $name = null;
    #[ArrayMappingAttribute('email_address')]
    private ?string $emailAddress = null;
    #[ArrayMappingAttribute('shipping_address.address', Address::class)]
    private ?Address $shippingAddress = null;

    public function getName(): ?Name
    {
        return $this->name;
    }

    public function setName(?Name $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(?string $emailAddress): self
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?Address $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->name)) {
            $json['name'] = $this->name;
        }
        if (isset($this->emailAddress)) {
            $json['email_address'] = $this->emailAddress;
        }
        if (isset($this->shippingAddress)) {
            $json['shipping_address'] = ['address' => $this->shippingAddress];
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
