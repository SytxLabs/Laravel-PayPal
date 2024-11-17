<?php

namespace SytxLabs\PayPal\Models\DTO\PaymentSource;

use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class IDEALPayment extends GeneralPayment
{
    use FromArray;

    #[ArrayMappingAttribute('bic')]
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
