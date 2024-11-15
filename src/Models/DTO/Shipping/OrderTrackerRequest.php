<?php

namespace SytxLabs\PayPal\Models\DTO\Shipping;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\ShipmentCarrier;

class OrderTrackerRequest implements JsonSerializable
{
    private ?string $trackingNumber;
    private ?ShipmentCarrier $carrier;
    private ?string $carrierNameOther;
    private string $captureId;
    private ?bool $notifyPayer = false;

    /**
     * @var OrderTrackerItem[]|null
     */
    private ?array $items;

    public function __construct(string $captureId)
    {
        $this->captureId = $captureId;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): self
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
    }

    public function getCarrier(): ?ShipmentCarrier
    {
        return $this->carrier;
    }

    public function setCarrier(?ShipmentCarrier $carrier): self
    {
        $this->carrier = $carrier;
        return $this;
    }

    public function getCarrierNameOther(): ?string
    {
        return $this->carrierNameOther;
    }

    public function setCarrierNameOther(?string $carrierNameOther): self
    {
        $this->carrierNameOther = $carrierNameOther;
        return $this;
    }

    public function getCaptureId(): string
    {
        return $this->captureId;
    }

    public function setCaptureId(string $captureId): self
    {
        $this->captureId = $captureId;
        return $this;
    }

    public function getNotifyPayer(): ?bool
    {
        return $this->notifyPayer;
    }

    public function setNotifyPayer(?bool $notifyPayer): self
    {
        $this->notifyPayer = $notifyPayer;
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
     * @param  OrderTrackerItem[]|null  $items
     */
    public function setItems(?array $items): self
    {
        $this->items = $items;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = ['capture_id' => $this->captureId];
        if (isset($this->trackingNumber)) {
            $json['tracking_number'] = $this->trackingNumber;
        }
        if (isset($this->carrier)) {
            $json['carrier'] = $this->carrier->value;
        }
        if (isset($this->carrierNameOther)) {
            $json['carrier_name_other'] = $this->carrierNameOther;
        }
        if (isset($this->notifyPayer)) {
            $json['notify_payer'] = $this->notifyPayer;
        }
        if (isset($this->items)) {
            $json['items'] = $this->items;
        }

        return $json;
    }
}
