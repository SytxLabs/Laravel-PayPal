<?php

namespace SytxLabs\PayPal\Models\DTO\Order;

use JsonSerializable;
use SytxLabs\PayPal\Enums\PayPalItemCategory;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;
use SytxLabs\PayPal\Models\DTO\UniversalProductCode;

class Item implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute(key: 'name')]
    private string $name;
    #[ArrayMappingAttribute(key: 'unit_amount', class: Money::class)]
    private Money $unitAmount;
    #[ArrayMappingAttribute(key: 'tax', class: Money::class)]
    private ?Money $tax = null;
    #[ArrayMappingAttribute(key: 'quantity')]
    private string $quantity;
    #[ArrayMappingAttribute(key: 'description')]
    private ?string $description = null;
    #[ArrayMappingAttribute(key: 'sku')]
    private ?string $sku = null;
    #[ArrayMappingAttribute(key: 'url')]
    private ?string $url = null;
    #[ArrayMappingAttribute(key: 'category', class: PayPalItemCategory::class)]
    private ?PayPalItemCategory $category = null;
    #[ArrayMappingAttribute(key: 'image_url')]
    private ?string $imageUrl = null;
    #[ArrayMappingAttribute(key: 'upc', class: UniversalProductCode::class)]
    private ?UniversalProductCode $upc = null;

    public function __construct(string $name, Money $unitAmount, string $quantity)
    {
        $this->name = $name;
        $this->unitAmount = $unitAmount;
        $this->quantity = $quantity;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getUnitAmount(): Money
    {
        return $this->unitAmount;
    }

    public function setUnitAmount(Money $unitAmount): self
    {
        $this->unitAmount = $unitAmount;
        return $this;
    }

    public function getTax(): ?Money
    {
        return $this->tax;
    }

    public function setTax(?Money $tax): self
    {
        $this->tax = $tax;
        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): self
    {
        $this->sku = $sku;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getCategory(): ?PayPalItemCategory
    {
        return $this->category;
    }

    public function setCategory(?PayPalItemCategory $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getUpc(): ?UniversalProductCode
    {
        return $this->upc;
    }

    public function setUpc(?UniversalProductCode $upc): self
    {
        $this->upc = $upc;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = [
            'name' => $this->name,
            'unit_amount' => $this->unitAmount,
            'quantity' => $this->quantity,
        ];
        if (isset($this->tax)) {
            $json['tax'] = $this->tax;
        }
        if (isset($this->description)) {
            $json['description'] = $this->description;
        }
        if (isset($this->sku)) {
            $json['sku'] = $this->sku;
        }
        if (isset($this->url)) {
            $json['url'] = $this->url;
        }
        if (isset($this->category)) {
            $json['category'] = $this->category->value;
        }
        if (isset($this->imageUrl)) {
            $json['image_url'] = $this->imageUrl;
        }
        if (isset($this->upc)) {
            $json['upc'] = $this->upc;
        }
        return $json;
    }

    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new self($data['name'], Money::fromArray($data['unit_amount']), $data['quantity']), $data);
    }
}
