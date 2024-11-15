<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource\VenmoWallet;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;

class VenmoWalletCustomerInformation implements JsonSerializable
{
    private ?string $id;
    private ?string $emailAddress;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(?string $emailAddress): self
    {
        if (
            $emailAddress !== null
            && (strlen($emailAddress) > 250 || preg_match('/^[\w-]+(?:\.[\w-]+)*@(?:[\w-]+\.)+[a-zA-Z]{2,7}$/', $emailAddress) !== 1)
        ) {
            throw new InvalidArgumentException('Invalid email address');
        }
        $this->emailAddress = $emailAddress;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->id)) {
            $json['id'] = $this->id;
        }
        if (isset($this->emailAddress)) {
            $json['email_address'] = $this->emailAddress;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
