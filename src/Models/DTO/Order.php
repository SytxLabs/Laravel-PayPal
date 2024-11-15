<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PayPalProcessingInstruction;
use SytxLabs\PayPal\Enums\PayPalCheckoutPaymentIntent;
use SytxLabs\PayPal\Enums\PayPalOrderCompletionType;
use SytxLabs\PayPal\Models\DTO\Order\PurchaseUnit;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class Order implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('create_time')]
    private ?string $createTime = null;
    #[ArrayMappingAttribute('update_time')]
    private ?string $updateTime = null;
    #[ArrayMappingAttribute('id')]
    private ?string $id = null;
    #[ArrayMappingAttribute('payment_source', PaymentSource::class)]
    private ?PaymentSource $paymentSource = null;
    #[ArrayMappingAttribute('intent', PayPalCheckoutPaymentIntent::class)]
    private ?PayPalCheckoutPaymentIntent $intent = null;
    #[ArrayMappingAttribute('processing_instruction', PayPalProcessingInstruction::class)]
    private ?PayPalProcessingInstruction $processingInstruction = PayPalProcessingInstruction::NO_INSTRUCTION;
    #[ArrayMappingAttribute('payer', Payer::class)]
    private ?Payer $payer = null;

    /**
     * @var PurchaseUnit[]|null
     */
    #[ArrayMappingAttribute('purchase_units', PurchaseUnit::class, true)]
    private ?array $purchaseUnits = null;
    #[ArrayMappingAttribute('status', PayPalOrderCompletionType::class)]
    private ?PayPalOrderCompletionType $status = null;
    #[ArrayMappingAttribute('application_context', ApplicationContext::class)]
    private ?ApplicationContext $applicationContext = null;

    /**
     * @var LinkDescription[]|null
     */
    #[ArrayMappingAttribute('links', LinkDescription::class, true)]
    private ?array $links = null;

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

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getPaymentSource(): ?PaymentSource
    {
        return $this->paymentSource;
    }

    public function setPaymentSource(?PaymentSource $paymentSource): self
    {
        $this->paymentSource = $paymentSource;
        return $this;
    }

    public function getIntent(): ?PayPalCheckoutPaymentIntent
    {
        return $this->intent;
    }

    public function setIntent(?PayPalCheckoutPaymentIntent $intent): self
    {
        $this->intent = $intent;
        return $this;
    }

    public function getProcessingInstruction(): ?PayPalProcessingInstruction
    {
        return $this->processingInstruction;
    }

    public function setProcessingInstruction(?PayPalProcessingInstruction $processingInstruction): self
    {
        $this->processingInstruction = $processingInstruction;
        return $this;
    }

    public function getPayer(): ?Payer
    {
        return $this->payer;
    }

    public function setPayer(?Payer $payer): self
    {
        $this->payer = $payer;
        return $this;
    }

    public function getPurchaseUnits(): ?array
    {
        return $this->purchaseUnits;
    }

    public function setPurchaseUnits(?array $purchaseUnits): self
    {
        $this->purchaseUnits = $purchaseUnits;
        return $this;
    }

    public function getStatus(): ?PayPalOrderCompletionType
    {
        return $this->status;
    }

    public function setStatus(?PayPalOrderCompletionType $status): self
    {
        $this->status = $status;
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

    public function getApplicationContext(): ?ApplicationContext
    {
        return $this->applicationContext;
    }

    public function setApplicationContext(?ApplicationContext $applicationContext): self
    {
        $this->applicationContext = $applicationContext;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->createTime)) {
            $json['create_time'] = $this->createTime;
        }
        if (isset($this->updateTime)) {
            $json['update_time'] = $this->updateTime;
        }
        if (isset($this->id)) {
            $json['id'] = $this->id;
        }
        if (isset($this->paymentSource)) {
            $json['payment_source'] = $this->paymentSource;
        }
        if (isset($this->intent)) {
            $json['intent'] = $this->intent->value;
        }
        if (isset($this->processingInstruction)) {
            $json['processing_instruction'] = $this->processingInstruction->value;
        }
        if (isset($this->payer)) {
            $json['payer'] = $this->payer;
        }
        if (isset($this->purchaseUnits)) {
            $json['purchase_units'] = $this->purchaseUnits;
        }
        if (isset($this->status)) {
            $json['status'] = $this->status->value;
        }
        if (isset($this->links)) {
            $json['links'] = $this->links;
        }
        if (isset($this->applicationContext)) {
            $json['application_context'] = $this->applicationContext;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
