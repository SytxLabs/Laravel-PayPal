<?php

namespace SytxLabs\PayPal\Models\DTO\Order\SupplementaryData;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Level2CardProcessingData implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('invoice_id')]
    private ?string $invoiceId;
    #[ArrayMappingAttribute('tax_total', Money::class)]
    private ?Money $taxTotal;

    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(?string $invoiceId): self
    {
        $this->invoiceId = $invoiceId;
        return $this;
    }

    public function getTaxTotal(): ?Money
    {
        return $this->taxTotal;
    }

    public function setTaxTotal(?Money $taxTotal): self
    {
        $this->taxTotal = $taxTotal;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->invoiceId)) {
            $json['invoice_id'] = $this->invoiceId;
        }
        if (isset($this->taxTotal)) {
            $json['tax_total'] = $this->taxTotal;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
