<?php

namespace SytxLabs\PayPal\Models\DTO\Order\SupplementaryData;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Address;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Order\Item;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Level3CardProcessingData implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('shipping_amount', class: Money::class)]
    private ?Money $shippingAmount;
    #[ArrayMappingAttribute('duty_amount', class: Money::class)]
    private ?Money $dutyAmount;
    #[ArrayMappingAttribute('discount_amount', class: Money::class)]
    private ?Money $discountAmount;
    #[ArrayMappingAttribute('shipping_address', class: Address::class)]
    private ?Address $shippingAddress;
    #[ArrayMappingAttribute('ships_from_postal_code')]
    private ?string $shipsFromPostalCode;

    /**
     * @var Item[]|null
     */
    #[ArrayMappingAttribute('line_items', class: Item::class, isArray: true)]
    private ?array $lineItems;

    public function getShippingAmount(): ?Money
    {
        return $this->shippingAmount;
    }

    public function setShippingAmount(?Money $shippingAmount): self
    {
        $this->shippingAmount = $shippingAmount;
        return $this;
    }

    public function getDutyAmount(): ?Money
    {
        return $this->dutyAmount;
    }

    public function setDutyAmount(?Money $dutyAmount): self
    {
        $this->dutyAmount = $dutyAmount;
        return $this;
    }

    public function getDiscountAmount(): ?Money
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(?Money $discountAmount): self
    {
        $this->discountAmount = $discountAmount;
        return $this;
    }

    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?Address $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    public function getShipsFromPostalCode(): ?string
    {
        return $this->shipsFromPostalCode;
    }

    public function setShipsFromPostalCode(?string $shipsFromPostalCode): self
    {
        $this->shipsFromPostalCode = $shipsFromPostalCode;
        return $this;
    }

    /**
     * @return Item[]|null
     */
    public function getLineItems(): ?array
    {
        return $this->lineItems;
    }

    /**
     * @param Item[]|null $lineItems
     */
    public function setLineItems(?array $lineItems): self
    {
        $this->lineItems = $lineItems;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->shippingAmount)) {
            $json['shipping_amount'] = $this->shippingAmount;
        }
        if (isset($this->dutyAmount)) {
            $json['duty_amount'] = $this->dutyAmount;
        }
        if (isset($this->discountAmount)) {
            $json['discount_amount'] = $this->discountAmount;
        }
        if (isset($this->shippingAddress)) {
            $json['shipping_address'] = $this->shippingAddress;
        }
        if (isset($this->shipsFromPostalCode)) {
            $json['ships_from_postal_code'] = $this->shipsFromPostalCode;
        }
        if (isset($this->lineItems)) {
            $json['line_items'] = $this->lineItems;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
