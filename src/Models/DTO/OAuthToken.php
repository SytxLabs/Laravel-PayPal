<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class OAuthToken implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('access_token')]
    private string $accessToken;
    #[ArrayMappingAttribute('token_type')]
    private string $tokenType;
    #[ArrayMappingAttribute('expires_in')]
    private ?int $expiresIn;
    #[ArrayMappingAttribute('scope')]
    private ?string $scope;
    #[ArrayMappingAttribute('expiry')]
    private ?int $expiry;
    #[ArrayMappingAttribute('refresh_token')]
    private ?string $refreshToken;
    #[ArrayMappingAttribute('id_token')]
    private ?string $idToken;

    public function __construct(string $accessToken, string $tokenType)
    {
        $this->accessToken = $accessToken;
        $this->tokenType = $tokenType;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function setTokenType(string $tokenType): self
    {
        $this->tokenType = $tokenType;
        return $this;
    }

    public function getExpiresIn(): ?int
    {
        return $this->expiresIn;
    }

    public function setExpiresIn(?int $expiresIn): self
    {
        $this->expiresIn = $expiresIn;
        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): self
    {
        $this->scope = $scope;
        return $this;
    }

    public function getExpiry(): ?int
    {
        return $this->expiry;
    }

    public function setExpiry(?int $expiry): self
    {
        $this->expiry = $expiry;
        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function getIdToken(): ?string
    {
        return $this->idToken;
    }

    public function setIdToken(?string $idToken): self
    {
        $this->idToken = $idToken;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array
    {
        $json = [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
        ];
        if (isset($this->expiresIn)) {
            $json['expires_in'] = $this->expiresIn;
        }
        if (isset($this->scope)) {
            $json['scope'] = $this->scope;
        }
        if (isset($this->expiry)) {
            $json['expiry'] = $this->expiry;
        }
        if (isset($this->refreshToken)) {
            $json['refresh_token'] = $this->refreshToken;
        }
        if (isset($this->idToken)) {
            $json['id_token'] = $this->idToken;
        }

        return $json;
    }

    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new self($data['access_token'], $data['token_type']), $data);
    }
}
