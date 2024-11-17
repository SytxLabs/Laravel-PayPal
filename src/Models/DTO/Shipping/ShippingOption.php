<?php

namespace SytxLabs\PayPal\Models\DTO\Shipping;

use JsonSerializable;
use SytxLabs\PayPal\Enums\PayPalFulfillmentType;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class ShippingOption implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('type', PayPalFulfillmentType::class)]
    private ?PayPalFulfillmentType $type;
    #[ArrayMappingAttribute('amount', Money::class)]
    private ?Money $amount;
    #[ArrayMappingAttribute('id')]
    private string $id;
    #[ArrayMappingAttribute('label')]
    private string $label;
    #[ArrayMappingAttribute('selected')]
    private bool $selected;

    public function __construct(string $id, string $label, bool $selected)
    {
        $this->id = $id;
        $this->label = $label;
        $this->selected = $selected;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getType(): ?PayPalFulfillmentType
    {
        return $this->type;
    }

    public function setType(?PayPalFulfillmentType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getAmount(): ?Money
    {
        return $this->amount;
    }

    public function setAmount(?Money $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getSelected(): bool
    {
        return $this->selected;
    }

    public function setSelected(bool $selected): self
    {
        $this->selected = $selected;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = [
            'id' => $this->id,
            'label' => $this->label,
            'selected' => $this->selected,
        ];
        if (isset($this->type)) {
            $json['type'] = $this->type->value;
        }
        if (isset($this->amount)) {
            $json['amount'] = $this->amount;
        }
        return $json;
    }
}
