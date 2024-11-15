<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use stdClass;

class AssuranceDetails implements JsonSerializable
{
    private ?bool $accountVerified = false;
    private ?bool $cardHolderAuthenticated = false;

    public function getAccountVerified(): ?bool
    {
        return $this->accountVerified;
    }

    public function setAccountVerified(?bool $accountVerified): self
    {
        $this->accountVerified = $accountVerified;
        return $this;
    }

    public function getCardHolderAuthenticated(): ?bool
    {
        return $this->cardHolderAuthenticated;
    }

    public function setCardHolderAuthenticated(?bool $cardHolderAuthenticated): self
    {
        $this->cardHolderAuthenticated = $cardHolderAuthenticated;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->accountVerified)) {
            $json['account_verified'] = $this->accountVerified;
        }
        if (isset($this->cardHolderAuthenticated)) {
            $json['card_holder_authenticated'] = $this->cardHolderAuthenticated;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
