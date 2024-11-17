<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use JsonSerializable;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Token implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('id')]
    private string $id;

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

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => 'BILLING_AGREEMENT',
        ];
    }

    public static function fromArray(array $data): static
    {
        return (new static($data['id']));
    }
}
