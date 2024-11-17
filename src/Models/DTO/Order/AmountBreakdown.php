<?php

namespace SytxLabs\PayPal\Models\DTO\Order;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class AmountBreakdown implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute(key: 'item_total', class: Money::class)]
    private ?Money $itemTotal;
    #[ArrayMappingAttribute(key: 'shipping', class: Money::class)]
    private ?Money $shipping;
    #[ArrayMappingAttribute(key: 'handling', class: Money::class)]
    private ?Money $handling;
    #[ArrayMappingAttribute(key: 'tax_total', class: Money::class)]
    private ?Money $taxTotal;
    #[ArrayMappingAttribute(key: 'insurance', class: Money::class)]
    private ?Money $insurance;
    #[ArrayMappingAttribute(key: 'shipping_discount', class: Money::class)]
    private ?Money $shippingDiscount;
    #[ArrayMappingAttribute(key: 'discount', class: Money::class)]
    private ?Money $discount;

    public function getItemTotal(): ?Money
    {
        return $this->itemTotal;
    }

    public function setItemTotal(?Money $itemTotal): self
    {
        $this->itemTotal = $itemTotal;
        return $this;
    }

    public function getShipping(): ?Money
    {
        return $this->shipping;
    }

    public function setShipping(?Money $shipping): self
    {
        $this->shipping = $shipping;
        return $this;
    }

    public function getHandling(): ?Money
    {
        return $this->handling;
    }

    public function setHandling(?Money $handling): self
    {
        $this->handling = $handling;
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

    public function getInsurance(): ?Money
    {
        return $this->insurance;
    }

    public function setInsurance(?Money $insurance): self
    {
        $this->insurance = $insurance;
        return $this;
    }

    public function getShippingDiscount(): ?Money
    {
        return $this->shippingDiscount;
    }

    public function setShippingDiscount(?Money $shippingDiscount): self
    {
        $this->shippingDiscount = $shippingDiscount;
        return $this;
    }

    public function getDiscount(): ?Money
    {
        return $this->discount;
    }

    public function setDiscount(?Money $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->itemTotal)) {
            $json['item_total'] = $this->itemTotal;
        }
        if (isset($this->shipping)) {
            $json['shipping'] = $this->shipping;
        }
        if (isset($this->handling)) {
            $json['handling'] = $this->handling;
        }
        if (isset($this->taxTotal)) {
            $json['tax_total'] = $this->taxTotal;
        }
        if (isset($this->insurance)) {
            $json['insurance'] = $this->insurance;
        }
        if (isset($this->shippingDiscount)) {
            $json['shipping_discount'] = $this->shippingDiscount;
        }
        if (isset($this->discount)) {
            $json['discount'] = $this->discount;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
