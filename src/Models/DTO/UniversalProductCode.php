<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\UniversalProductCodeType;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class UniversalProductCode implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('type', UniversalProductCodeType::class)]
    private UniversalProductCodeType $type;
    #[ArrayMappingAttribute('code')]
    private string $code;

    public function __construct(UniversalProductCodeType $type, string $code)
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

    public static function fromArray(array $data): static
    {
        return new self(
            UniversalProductCodeType::from($data['type']),
            $data['code']
        );
    }
}
