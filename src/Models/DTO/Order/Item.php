<?php

namespace SytxLabs\PayPal\Models\DTO\Order;

use JsonSerializable;
use SytxLabs\PayPal\Enums\PayPalItemCategory;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\UniversalProductCode;

class Item implements JsonSerializable
{
    private string $name;
    private Money $unitAmount;
    private ?Money $tax;
    private string $quantity;
    private ?string $description;
    private ?string $sku;
    private ?string $url;
    private ?PayPalItemCategory $category;
    private ?string $imageUrl;
    private ?UniversalProductCode $upc;

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

    public static function fromArray(array $data): self
    {
        $item = new self($data['name'], Money::fromArray($data['unit_amount']), $data['quantity']);
        if (isset($data['tax'])) {
            $item->setTax(Money::fromArray($data['tax']));
        }
        if (isset($data['description'])) {
            $item->setDescription($data['description']);
        }
        if (isset($data['sku'])) {
            $item->setSku($data['sku']);
        }
        if (isset($data['url'])) {
            $item->setUrl($data['url']);
        }
        if (isset($data['category'])) {
            $item->setCategory(PayPalItemCategory::tryFrom($data['category']));
        }
        if (isset($data['image_url'])) {
            $item->setImageUrl($data['image_url']);
        }
        if (isset($data['upc'])) {
            $item->setUpc(UniversalProductCode::fromArray($data['upc']));
        }
        return $item;
    }
}
