<?php

namespace SytxLabs\PayPal\Models\DTO\Shipping;

use JsonSerializable;
use PaypalServerSDKLib\Models\OrderTrackerItem;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\OrderTrackerStatus;
use SytxLabs\PayPal\Models\DTO\LinkDescription;

class OrderTrackerResponse implements JsonSerializable
{
    private ?string $id;
    private ?OrderTrackerStatus $status;
    /**
     * @var OrderTrackerItem[]|null
     */
    private ?array $items;
    private ?array $links;
    private ?string $createTime;
    private ?string $updateTime;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getStatus(): ?OrderTrackerStatus
    {
        return $this->status;
    }

    public function setStatus(?OrderTrackerStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return OrderTrackerItem[]|null
     */
    public function getItems(): ?array
    {
        return $this->items;
    }

    /**
     * @param OrderTrackerItem[]|null $items
     */
    public function setItems(?array $items): self
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return LinkDescription[]|null
     */
    public function getLinks(): ?array
    {
        return $this->links;
    }

    /**
     * @param LinkDescription[]|null $links
     */
    public function setLinks(?array $links): self
    {
        $this->links = $links;
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

    public function getUpdateTime(): ?string
    {
        return $this->updateTime;
    }

    public function setUpdateTime(?string $updateTime): self
    {
        $this->updateTime = $updateTime;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->id)) {
            $json['id'] = $this->id;
        }
        if (isset($this->status)) {
            $json['status'] = $this->status->value;
        }
        if (isset($this->items)) {
            $json['items'] = $this->items;
        }
        if (isset($this->links)) {
            $json['links'] = $this->links;
        }
        if (isset($this->createTime)) {
            $json['create_time'] = $this->createTime;
        }
        if (isset($this->updateTime)) {
            $json['update_time'] = $this->updateTime;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
