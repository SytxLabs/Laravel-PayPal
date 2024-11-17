<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\GooglePay;

use InvalidArgumentException;
use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalGooglePayAuthenticationMethod;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class GooglePayDecryptedTokenData implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute(key: 'authentication_method', class: PayPalGooglePayAuthenticationMethod::class)]
    private PayPalGooglePayAuthenticationMethod $authenticationMethod;
    #[ArrayMappingAttribute(key: 'message_id')]
    private ?string $messageId;
    #[ArrayMappingAttribute(key: 'message_expiration')]
    private ?string $messageExpiration;
    #[ArrayMappingAttribute(key: 'cryptogram')]
    private ?string $cryptogram;
    #[ArrayMappingAttribute(key: 'eci_indicator')]
    private ?string $eciIndicator;

    public function __construct(PayPalGooglePayAuthenticationMethod $authenticationMethod)
    {
        $this->authenticationMethod = $authenticationMethod;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function setMessageId(?string $messageId): self
    {
        $this->messageId = $messageId;
        return $this;
    }

    public function getMessageExpiration(): ?string
    {
        return $this->messageExpiration;
    }

    public function setMessageExpiration(?string $messageExpiration): self
    {
        $this->messageExpiration = $messageExpiration;
        return $this;
    }

    public function getAuthenticationMethod(): PayPalGooglePayAuthenticationMethod
    {
        return $this->authenticationMethod;
    }

    public function setAuthenticationMethod(PayPalGooglePayAuthenticationMethod $authenticationMethod): self
    {
        $this->authenticationMethod = $authenticationMethod;
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

    public function getEciIndicator(): ?string
    {
        return $this->eciIndicator;
    }

    public function setEciIndicator(?string $eciIndicator): self
    {
        $this->eciIndicator = $eciIndicator;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = [
            'payment_method' => 'CARD',
            'authentication_method' => $this->authenticationMethod->value,
        ];
        if (isset($this->messageId)) {
            $json['message_id'] = $this->messageId;
        }
        if (isset($this->messageExpiration)) {
            $json['message_expiration'] = $this->messageExpiration;
        }
        if (isset($this->cryptogram)) {
            $json['cryptogram'] = $this->cryptogram;
        }
        if (isset($this->eciIndicator)) {
            $json['eci_indicator'] = $this->eciIndicator;
        }

        return $json;
    }

    public static function fromArray(array $data): static
    {
        if (!isset($data['authentication_method'])) {
            throw new InvalidArgumentException('Missing required field "authentication_method"');
        }
        return self::fromArrayInternal(new self(PayPalGooglePayAuthenticationMethod::tryFrom($data['authentication_method'])), $data);
    }
}
