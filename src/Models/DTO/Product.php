<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use SytxLabs\PayPal\Enums\PayPalItemCategory;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Product implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('name')]
    public string $name;
    #[ArrayMappingAttribute('unitPrice')]
    public float $unitPrice;
    #[ArrayMappingAttribute('currencyCode')]
    public ?string $currencyCode = null;
    #[ArrayMappingAttribute('totalPrice')]
    public ?float $totalPrice = null;
    #[ArrayMappingAttribute('tax')]
    public ?float $tax = null;
    #[ArrayMappingAttribute('quantity')]
    public int $quantity = 1;
    #[ArrayMappingAttribute('sku')]
    public ?string $sku = null;
    #[ArrayMappingAttribute('description')]
    public ?string $description = null;
    #[ArrayMappingAttribute('url')]
    public ?string $url = null;
    #[ArrayMappingAttribute('category', PayPalItemCategory::class)]
    public ?PayPalItemCategory $category = null;
    #[ArrayMappingAttribute('imageUrl')]
    public ?string $imageUrl = null;
    #[ArrayMappingAttribute('upc', UniversalProductCode::class)]
    public ?UniversalProductCode $upc = null;
    #[ArrayMappingAttribute('payee', Payee::class)]
    public ?Payee $payee = null;
    #[ArrayMappingAttribute('shipping')]
    public ?float $shipping = null;
    #[ArrayMappingAttribute('discount')]
    public ?float $discount = null;
    #[ArrayMappingAttribute('shippingDiscount')]
    public ?float $shippingDiscount = null;

    public function __construct(string $name, float $unitPrice, int $quantity, ?string $currencyCode = null)
    {
        $this->name = $name;
        $this->unitPrice = $unitPrice;
        $this->quantity = $quantity;
        $this->currencyCode = $currencyCode;
    }

    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new self($data['name'], $data['unitPrice'], $data['quantity'], $data['currencyCode'] ?? null), $data);
    }

    public function setTotalPrice(?float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function setTax(?float $tax): self
    {
        $this->tax = $tax;
        return $this;
    }

    public function setSku(?string $sku): self
    {
        $this->sku = $sku;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setCategory(?PayPalItemCategory $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        if ($imageUrl === null) {
            $this->imageUrl = null;
            return $this;
        }
        if (strlen($imageUrl) > 2048) {
            return $this;
        }
        if (!preg_match('/^(https:\/\/)([\/|.\w\s-])*\.(?:jpg|gif|png|jpeg|JPG|GIF|PNG|JPEG)$/', $imageUrl)) {
            return $this;
        }
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function setUpc(?UniversalProductCode $upc): self
    {
        $this->upc = $upc;
        return $this;
    }

    public function setPayee(?Payee $payee): self
    {
        $this->payee = $payee;
        return $this;
    }

    public function setShipping(?float $shipping): self
    {
        $this->shipping = $shipping;
        return $this;
    }

    public function setDiscount(?float $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    public function setShippingDiscount(?float $shippingDiscount): self
    {
        $this->shippingDiscount = $shippingDiscount;
        return $this;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'unitPrice' => $this->unitPrice,
            'currencyCode' => $this->currencyCode,
            'totalPrice' => $this->totalPrice ?? ($this->unitPrice * $this->quantity),
            'tax' => $this->tax,
            'quantity' => $this->quantity,
            'sku' => $this->sku,
            'description' => $this->description,
            'url' => $this->url,
            'category' => $this->category,
            'imageUrl' => $this->imageUrl,
            'upc' => $this->upc,
            'payee' => $this->payee,
            'shipping' => $this->shipping,
            'discount' => $this->discount,
            'shippingDiscount' => $this->shippingDiscount,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
