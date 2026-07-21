<?php

namespace SytxLabs\PayPal\Models\DTO\Subscription;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\Subscription\PlanStatus;
use SytxLabs\PayPal\Models\DTO\LinkDescription;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Plan implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('id')]
    private ?string $id = null;
    #[ArrayMappingAttribute('product_id')]
    private ?string $productId = null;
    #[ArrayMappingAttribute('name')]
    private ?string $name = null;
    #[ArrayMappingAttribute('description')]
    private ?string $description = null;
    #[ArrayMappingAttribute('status', PlanStatus::class)]
    private ?PlanStatus $status = PlanStatus::ACTIVE;

    /**
     * @var BillingCycle[]|null
     */
    #[ArrayMappingAttribute('billing_cycles', BillingCycle::class, true)]
    private ?array $billingCycles = null;
    #[ArrayMappingAttribute('payment_preferences', PaymentPreferences::class)]
    private ?PaymentPreferences $paymentPreferences = null;
    #[ArrayMappingAttribute('taxes', Taxes::class)]
    private ?Taxes $taxes = null;
    #[ArrayMappingAttribute('quantity_supported')]
    private ?bool $quantitySupported = null;
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

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): self
    {
        $this->productId = $productId;
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

    public function getStatus(): ?PlanStatus
    {
        return $this->status;
    }

    public function setStatus(?PlanStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return BillingCycle[]|null
     */
    public function getBillingCycles(): ?array
    {
        return $this->billingCycles;
    }

    /**
     * @param  BillingCycle[]|null  $billingCycles
     */
    public function setBillingCycles(?array $billingCycles): self
    {
        $this->billingCycles = $billingCycles;
        return $this;
    }

    public function addBillingCycle(BillingCycle $billingCycle): self
    {
        $this->billingCycles ??= [];
        $this->billingCycles[] = $billingCycle;
        return $this;
    }

    public function getPaymentPreferences(): ?PaymentPreferences
    {
        return $this->paymentPreferences;
    }

    public function setPaymentPreferences(?PaymentPreferences $paymentPreferences): self
    {
        $this->paymentPreferences = $paymentPreferences;
        return $this;
    }

    public function getTaxes(): ?Taxes
    {
        return $this->taxes;
    }

    public function setTaxes(?Taxes $taxes): self
    {
        $this->taxes = $taxes;
        return $this;
    }

    public function getQuantitySupported(): ?bool
    {
        return $this->quantitySupported;
    }

    public function setQuantitySupported(?bool $quantitySupported): self
    {
        $this->quantitySupported = $quantitySupported;
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
        if (isset($this->productId)) {
            $json['product_id'] = $this->productId;
        }
        if (isset($this->name)) {
            $json['name'] = $this->name;
        }
        if (isset($this->description)) {
            $json['description'] = $this->description;
        }
        if (isset($this->status)) {
            $json['status'] = $this->status->value;
        }
        if (isset($this->billingCycles)) {
            $json['billing_cycles'] = $this->billingCycles;
        }
        if (isset($this->paymentPreferences)) {
            $json['payment_preferences'] = $this->paymentPreferences;
        }
        if (isset($this->taxes)) {
            $json['taxes'] = $this->taxes;
        }
        if (isset($this->quantitySupported)) {
            $json['quantity_supported'] = $this->quantitySupported;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
