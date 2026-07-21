<?php

namespace SytxLabs\PayPal\Models\DTO\Subscription;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\Subscription\CatalogProductType;
use SytxLabs\PayPal\Models\DTO\LinkDescription;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class CatalogProduct implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('id')]
    private ?string $id = null;
    #[ArrayMappingAttribute('name')]
    private ?string $name = null;
    #[ArrayMappingAttribute('description')]
    private ?string $description = null;
    #[ArrayMappingAttribute('type', CatalogProductType::class)]
    private ?CatalogProductType $type = CatalogProductType::SERVICE;
    #[ArrayMappingAttribute('category')]
    private ?string $category = null;
    #[ArrayMappingAttribute('image_url')]
    private ?string $imageUrl = null;
    #[ArrayMappingAttribute('home_url')]
    private ?string $homeUrl = null;
    #[ArrayMappingAttribute('create_time')]
    private ?string $createTime = null;

    /**
     * @var LinkDescription[]|null
     */
    #[ArrayMappingAttribute('links', LinkDescription::class, true)]
    private ?array $links = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
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

    public function getType(): ?CatalogProductType
    {
        return $this->type;
    }

    public function setType(?CatalogProductType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
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

    public function getHomeUrl(): ?string
    {
        return $this->homeUrl;
    }

    public function setHomeUrl(?string $homeUrl): self
    {
        $this->homeUrl = $homeUrl;
        return $this;
    }

    public function getCreateTime(): ?string
    {
        return $this->createTime;
    }

    public function setCreateTime(?string $createTime): self
    {
        $this->createTime = $createTime;
        return $this;
    }

    public function getLinks(): ?array
    {
        return $this->links;
    }

    public function setLinks(?array $links): self
    {
        $this->links = $links;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->id)) {
            $json['id'] = $this->id;
        }
        if (isset($this->name)) {
            $json['name'] = $this->name;
        }
        if (isset($this->description)) {
            $json['description'] = $this->description;
        }
        if (isset($this->type)) {
            $json['type'] = $this->type->value;
        }
        if (isset($this->category)) {
            $json['category'] = $this->category;
        }
        if (isset($this->imageUrl)) {
            $json['image_url'] = $this->imageUrl;
        }
        if (isset($this->homeUrl)) {
            $json['home_url'] = $this->homeUrl;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
