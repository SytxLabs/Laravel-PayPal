<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\UniversalProductCodeType;

class UniversalProductCode implements JsonSerializable
{
    public function __construct(private UniversalProductCodeType $type, private string $code)
    {
    }

    public function getType(): UniversalProductCodeType
    {
        return $this->type;
    }

    public function setType(UniversalProductCodeType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type->value,
            'code' => $this->code,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            UniversalProductCodeType::from($data['type']),
            $data['code']
        );
    }
}
