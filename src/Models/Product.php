<?php

namespace SytxLabs\PayPal\Models;

use PaypalServerSdkLib\Models\UniversalProductCode;
use SytxLabs\PayPal\Enums\PayPalItemCategory;

class Product
{
    public string $name;
    public float $unitPrice;
    public ?string $currencyCode = null;
    public ?float $totalPrice = null;
    public ?float $tax = null;
    public int $quantity = 1;
    public ?string $sku = null;
    public ?string $description = null;
    public ?string $url = null;
    public ?PayPalItemCategory $category = null;
    public ?string $imageUrl = null;
    public ?UniversalProductCode $upc = null;
    public ?Payee $payee = null;
    public ?float $shipping = null;
    public ?float $discount = null;
    public ?float $shippingDiscount = null;

    public function __construct(string $name, float $unitPrice, int $quantity, ?string $currencyCode = null)
    {
        $this->name = $name;
        $this->unitPrice = $unitPrice;
        $this->quantity = $quantity;
        $this->currencyCode = $currencyCode;
    }

    public static function fromArray(array $data): self
    {
        $product = new self($data['name'], $data['unitPrice'], $data['quantity'], $data['currencyCode'] ?? null);
        $product->setTax($data['tax'] ?? null);
        $product->setSku($data['sku'] ?? null);
        $product->setTotalPrice($data['totalPrice'] ?? null);
        $product->setDescription($data['description'] ?? null);
        $product->setUrl($data['url'] ?? null);
        $product->setCategory($data['category'] ?? null);
        $product->setImageUrl($data['imageUrl'] ?? null);
        $product->setUpc($data['upc'] ?? null);
        $product->setPayee($data['payee'] ?? null);
        $product->setShipping($data['shipping'] ?? null);
        $product->setDiscount($data['discount'] ?? null);
        $product->setShippingDiscount($data['shippingDiscount'] ?? null);
        return $product;
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
}
