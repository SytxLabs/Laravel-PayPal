<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\BLIKPayment;

use InvalidArgumentException;
use JsonSerializable;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class BLIKOneClickPayment implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute('auth_code')]
    private ?string $authCode;
    #[ArrayMappingAttribute('consumer_reference')]
    private string $consumerReference;
    #[ArrayMappingAttribute('alias_label')]
    private ?string $aliasLabel;
    #[ArrayMappingAttribute('alias_key')]
    private ?string $aliasKey;

    public function __construct(string $consumerReference)
    {
        $this->consumerReference = $consumerReference;
    }

    public function getAuthCode(): ?string
    {
        return $this->authCode;
    }

    public function setAuthCode(?string $authCode): self
    {
        if ($authCode !== null && strlen($authCode) !== 6) {
            throw new InvalidArgumentException('The auth code must be a 6-digit code');
        }
        $this->authCode = $authCode;
        return $this;
    }

    public function getConsumerReference(): string
    {
        return $this->consumerReference;
    }

    public function setConsumerReference(string $consumerReference): self
    {
        $this->consumerReference = $consumerReference;
        return $this;
    }

    public function getAliasLabel(): ?string
    {
        return $this->aliasLabel;
    }

    public function setAliasLabel(?string $aliasLabel): self
    {
        $this->aliasLabel = $aliasLabel;
        return $this;
    }

    public function getAliasKey(): ?string
    {
        return $this->aliasKey;
    }

    public function setAliasKey(?string $aliasKey): self
    {
        $this->aliasKey = $aliasKey;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array
    {
        $json = ['consumer_reference' => $this->consumerReference];
        if (isset($this->authCode)) {
            $json['auth_code'] = $this->authCode;
        }
        if (isset($this->aliasLabel)) {
            $json['alias_label'] = $this->aliasLabel;
        }
        if (isset($this->aliasKey)) {
            $json['alias_key'] = $this->aliasKey;
        }

        return $json;
    }

    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new self($data['consumer_reference']), $data);
    }
}
