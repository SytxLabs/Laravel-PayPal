<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest;

use InvalidArgumentException;
use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalECIFlag;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class NetworkToken implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('cryptogram')]
    private ?string $cryptogram;
    #[ArrayMappingAttribute('eci_flag', PayPalECIFlag::class)]
    private ?PayPalECIFlag $eciFlag;
    #[ArrayMappingAttribute('token_requestor_id')]
    private ?string $tokenRequestorId;
    #[ArrayMappingAttribute('number')]
    private string $number;
    #[ArrayMappingAttribute('expiry')]
    private string $expiry;

    public function __construct(string $number, string $expiry)
    {
        $this->number = $number;
        $this->expiry = $expiry;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function getExpiry(): string
    {
        return $this->expiry;
    }

    public function setExpiry(string $expiry): self
    {
        if (strlen($expiry) !== 7) {
            throw new InvalidArgumentException('Expiry must be in the format YYYY-MM');
        }
        $this->expiry = $expiry;
        return $this;
    }

    public function getCryptogram(): ?string
    {
        return $this->cryptogram;
    }

    public function setCryptogram(?string $cryptogram): self
    {
        $this->cryptogram = $cryptogram;
        return $this;
    }

    public function getEciFlag(): ?PayPalECIFlag
    {
        return $this->eciFlag;
    }

    public function setEciFlag(?PayPalECIFlag $eciFlag): self
    {
        $this->eciFlag = $eciFlag;
        return $this;
    }

    public function getTokenRequestorId(): ?string
    {
        return $this->tokenRequestorId;
    }

    public function setTokenRequestorId(?string $tokenRequestorId): self
    {
        $this->tokenRequestorId = $tokenRequestorId;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = [
            'number' => $this->number,
            'expiry' => $this->expiry,
        ];
        if (isset($this->cryptogram)) {
            $json['cryptogram'] = $this->cryptogram;
        }
        if (isset($this->eciFlag)) {
            $json['eci_flag'] = $this->eciFlag->value;
        }
        if (isset($this->tokenRequestorId)) {
            $json['token_requestor_id'] = $this->tokenRequestorId;
        }

        return $json;
    }

    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new static($data['number'], $data['expiry']), $data);
    }
}
