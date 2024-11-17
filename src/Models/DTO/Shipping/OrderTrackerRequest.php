<?php

namespace SytxLabs\PayPal\Models\DTO\Shipping;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\ShipmentCarrier;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class OrderTrackerRequest implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('tracking_number')]
    private ?string $trackingNumber;
    #[ArrayMappingAttribute('carrier', ShipmentCarrier::class)]
    private ?ShipmentCarrier $carrier;
    #[ArrayMappingAttribute('carrier_name_other')]
    private ?string $carrierNameOther;
    #[ArrayMappingAttribute('capture_id')]
    private string $captureId;
    #[ArrayMappingAttribute('notify_payer')]
    private ?bool $notifyPayer = false;

    /**
     * @var OrderTrackerItem[]|null
     */
    #[ArrayMappingAttribute('items', OrderTrackerItem::class, true)]
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

    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new static($data['capture_id']), $data);
    }
}
