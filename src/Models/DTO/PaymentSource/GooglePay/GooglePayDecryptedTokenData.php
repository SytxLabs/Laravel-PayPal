<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\GooglePay;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalGooglePayAuthenticationMethod;

class GooglePayDecryptedTokenData implements JsonSerializable
{
    private ?string $messageId;
    private ?string $messageExpiration;
    private ?string $cryptogram;
    private ?string $eciIndicator;

    public function __construct(private PayPalGooglePayAuthenticationMethod $authenticationMethod)
    {
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
}
