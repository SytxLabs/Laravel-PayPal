<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

class IDEALPayment extends GeneralPayment
{
    private ?string $bic;

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): self
    {
        $this->bic = $bic;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = parent::jsonSerialize();
        if (isset($this->bic)) {
            $json['bic'] = $this->bic;
        }
        return $json;
    }
}
