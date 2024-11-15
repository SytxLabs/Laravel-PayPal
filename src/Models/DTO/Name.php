<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use stdClass;

class Name implements JsonSerializable
{
    public function __construct(private ?string $givenName = null, private ?string $surname = null)
    {
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(?string $givenName): self
    {
        $this->givenName = $givenName;
        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): self
    {
        $this->surname = $surname;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->givenName)) {
            $json['given_name'] = $this->givenName;
        }
        if (isset($this->surname)) {
            $json['surname'] = $this->surname;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['given_name'] ?? null,
            $data['surname'] ?? null
        );
    }
}
