<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalCardBrand;

class NetworkTransactionReference implements JsonSerializable
{
    private string $id;
    private ?string $date;
    private ?PayPalCardBrand $network;
    private ?string $acquirerReferenceNumber;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(?string $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getNetwork(): ?PayPalCardBrand
    {
        return $this->network;
    }

    public function setNetwork(?PayPalCardBrand $network): self
    {
        $this->network = $network;
        return $this;
    }

    public function getAcquirerReferenceNumber(): ?string
    {
        return $this->acquirerReferenceNumber;
    }

    public function setAcquirerReferenceNumber(?string $acquirerReferenceNumber): self
    {
        $this->acquirerReferenceNumber = $acquirerReferenceNumber;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = ['id' => $this->id];
        if (isset($this->date)) {
            $json['date'] = $this->date;
        }
        if (isset($this->network)) {
            $json['network'] = $this->network->value;
        }
        if (isset($this->acquirerReferenceNumber)) {
            $json['acquirer_reference_number'] = $this->acquirerReferenceNumber;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
