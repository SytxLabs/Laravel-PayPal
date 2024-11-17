<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalCardBrand;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class NetworkTransactionReference implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('id')]
    private string $id;
    #[ArrayMappingAttribute('date')]
    private ?string $date;
    #[ArrayMappingAttribute('network', PayPalCardBrand::class)]
    private ?PayPalCardBrand $network;
    #[ArrayMappingAttribute('acquirer_reference_number')]
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

    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new self($data['id']), $data);
    }
}
