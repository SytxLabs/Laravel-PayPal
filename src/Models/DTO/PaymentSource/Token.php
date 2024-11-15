<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use JsonSerializable;

class Token implements JsonSerializable
{
    public function __construct(private string $id)
    {
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
}
