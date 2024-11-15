<?php

namespace SytxLabs\PayPal\Models\DTO\Shipping;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\UniversalProductCode;

class OrderTrackerItem implements JsonSerializable
{
    private ?string $name;
    private ?string $quantity;
    private ?string $sku;
    private ?string $url;
    private ?string $imageUrl;
    private ?UniversalProductCode $upc;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(?string $quantity): self
    {
        $this->quantity = $quantity;
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

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->name)) {
            $json['name'] = $this->name;
        }
        if (isset($this->quantity)) {
            $json['quantity'] = $this->quantity;
        }
        if (isset($this->sku)) {
            $json['sku'] = $this->sku;
        }
        if (isset($this->url)) {
            $json['url'] = $this->url;
        }
        if (isset($this->imageUrl)) {
            $json['image_url'] = $this->imageUrl;
        }
        if (isset($this->upc)) {
            $json['upc'] = $this->upc;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
