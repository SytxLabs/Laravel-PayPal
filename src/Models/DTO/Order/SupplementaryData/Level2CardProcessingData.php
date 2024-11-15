<?php

namespace SytxLabs\PayPal\Models\DTO\Order\SupplementaryData;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Money;

class Level2CardProcessingData implements JsonSerializable
{
    private ?string $invoiceId;
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
