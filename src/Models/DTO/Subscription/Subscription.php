<?php

namespace SytxLabs\PayPal\Models\DTO\Subscription;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\Subscription\SubscriptionStatus;
use SytxLabs\PayPal\Models\DTO\LinkDescription;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Subscription implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('id')]
    private ?string $id = null;
    #[ArrayMappingAttribute('plan_id')]
    private ?string $planId = null;
    #[ArrayMappingAttribute('quantity')]
    private ?string $quantity = null;
    #[ArrayMappingAttribute('custom_id')]
    private ?string $customId = null;
    #[ArrayMappingAttribute('start_time')]
    private ?string $startTime = null;
    #[ArrayMappingAttribute('subscriber', Subscriber::class)]
    private ?Subscriber $subscriber = null;
    #[ArrayMappingAttribute('application_context', SubscriptionApplicationContext::class)]
    private ?SubscriptionApplicationContext $applicationContext = null;
    #[ArrayMappingAttribute('plan', Plan::class)]
    private ?Plan $plan = null;
    #[ArrayMappingAttribute('status', SubscriptionStatus::class)]
    private ?SubscriptionStatus $status = null;
    #[ArrayMappingAttribute('billing_info', BillingInfo::class)]
    private ?BillingInfo $billingInfo = null;
    #[ArrayMappingAttribute('create_time')]
    private ?string $createTime = null;
    #[ArrayMappingAttribute('update_time')]
    private ?string $updateTime = null;

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

    public function getPlanId(): ?string
    {
        return $this->planId;
    }

    public function setPlanId(?string $planId): self
    {
        $this->planId = $planId;
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

    public function getCustomId(): ?string
    {
        return $this->customId;
    }

    public function setCustomId(?string $customId): self
    {
        $this->customId = $customId;
        return $this;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function setStartTime(?string $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getSubscriber(): ?Subscriber
    {
        return $this->subscriber;
    }

    public function setSubscriber(?Subscriber $subscriber): self
    {
        $this->subscriber = $subscriber;
        return $this;
    }

    public function getApplicationContext(): ?SubscriptionApplicationContext
    {
        return $this->applicationContext;
    }

    public function setApplicationContext(?SubscriptionApplicationContext $applicationContext): self
    {
        $this->applicationContext = $applicationContext;
        return $this;
    }

    public function getPlan(): ?Plan
    {
        return $this->plan;
    }

    public function setPlan(?Plan $plan): self
    {
        $this->plan = $plan;
        return $this;
    }

    public function getStatus(): ?SubscriptionStatus
    {
        return $this->status;
    }

    public function setStatus(?SubscriptionStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getBillingInfo(): ?BillingInfo
    {
        return $this->billingInfo;
    }

    public function setBillingInfo(?BillingInfo $billingInfo): self
    {
        $this->billingInfo = $billingInfo;
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
        if (isset($this->planId)) {
            $json['plan_id'] = $this->planId;
        }
        if (isset($this->quantity)) {
            $json['quantity'] = $this->quantity;
        }
        if (isset($this->customId)) {
            $json['custom_id'] = $this->customId;
        }
        if (isset($this->startTime)) {
            $json['start_time'] = $this->startTime;
        }
        if (isset($this->subscriber)) {
            $json['subscriber'] = $this->subscriber;
        }
        if (isset($this->applicationContext)) {
            $json['application_context'] = $this->applicationContext;
        }
        if (isset($this->plan)) {
            $json['plan'] = $this->plan;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
