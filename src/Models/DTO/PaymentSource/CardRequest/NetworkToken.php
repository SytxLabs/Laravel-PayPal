<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\CardRequest;

use InvalidArgumentException;
use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalECIFlag;

class NetworkToken implements JsonSerializable
{
    private ?string $cryptogram;
    private ?PayPalECIFlag $eciFlag;
    private ?string $tokenRequestorId;

    public function __construct(private string $number, private string $expiry)
    {
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
}
