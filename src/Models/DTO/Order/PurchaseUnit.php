<?php

namespace SytxLabs\PayPal\Models\DTO\Order;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\Payee;
use SytxLabs\PayPal\Models\DTO\Shipping\ShippingWithTrackingDetails;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class PurchaseUnit implements JsonSerializable
{
    use FromArray;
    #[ArrayMappingAttribute(key: 'reference_id')]
    private ?string $referenceId;
    #[ArrayMappingAttribute(key: 'amount', class: AmountWithBreakdown::class)]
    private ?AmountWithBreakdown $amount;
    #[ArrayMappingAttribute(key: 'payee', class: Payee::class)]
    private ?Payee $payee;
    #[ArrayMappingAttribute(key: 'payment_instruction', class: PaymentInstruction::class)]
    private ?PaymentInstruction $paymentInstruction;
    #[ArrayMappingAttribute(key: 'description')]
    private ?string $description;
    #[ArrayMappingAttribute(key: 'custom_id')]
    private ?string $customId;
    #[ArrayMappingAttribute(key: 'invoice_id')]
    private ?string $invoiceId;
    #[ArrayMappingAttribute(key: 'id')]
    private ?string $id;
    #[ArrayMappingAttribute(key: 'soft_descriptor')]
    private ?string $softDescriptor;
    #[ArrayMappingAttribute(key: 'items', class: Item::class, isArray: true)]
    private ?array $items;
    #[ArrayMappingAttribute(key: 'shipping', class: ShippingWithTrackingDetails::class)]
    private ?ShippingWithTrackingDetails $shipping;
    #[ArrayMappingAttribute(key: 'supplementary_data', class: SupplementaryData::class)]
    private ?SupplementaryData $supplementaryData;
    #[ArrayMappingAttribute(key: 'most_recent_errors')]
    private array $mostRecentErrors = [];

    public function getReferenceId(): ?string
    {
        return $this->referenceId;
    }

    public function setReferenceId(?string $referenceId): self
    {
        $this->referenceId = $referenceId;
        return $this;
    }

    public function getAmount(): ?AmountWithBreakdown
    {
        return $this->amount;
    }

    public function setAmount(?AmountWithBreakdown $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getPayee(): ?Payee
    {
        return $this->payee;
    }

    public function setPayee(?Payee $payee): self
    {
        $this->payee = $payee;
        return $this;
    }

    public function getPaymentInstruction(): ?PaymentInstruction
    {
        return $this->paymentInstruction;
    }

    public function setPaymentInstruction(?PaymentInstruction $paymentInstruction): self
    {
        $this->paymentInstruction = $paymentInstruction;
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

    public function getCustomId(): ?string
    {
        return $this->customId;
    }

    public function setCustomId(?string $customId): self
    {
        $this->customId = $customId;
        return $this;
    }

    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(?string $invoiceId): self
    {
        $this->invoiceId = $invoiceId;
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

    public function getSoftDescriptor(): ?string
    {
        return $this->softDescriptor;
    }

    public function setSoftDescriptor(?string $softDescriptor): self
    {
        if ($softDescriptor !== null && strlen($softDescriptor) > 22) {
            throw new InvalidArgumentException('Soft descriptor must be 22 characters or less');
        }
        $this->softDescriptor = $softDescriptor;
        return $this;
    }

    /**
     * @return Item[]|null
     */
    public function getItems(): ?array
    {
        return $this->items;
    }

    /**
     * @param  Item[]|null  $items
     */
    public function setItems(?array $items): self
    {
        $this->items = $items;
        return $this;
    }

    public function getShipping(): ?ShippingWithTrackingDetails
    {
        return $this->shipping;
    }

    public function setShipping(?ShippingWithTrackingDetails $shipping): self
    {
        $this->shipping = $shipping;
        return $this;
    }

    public function getSupplementaryData(): ?SupplementaryData
    {
        return $this->supplementaryData;
    }

    public function setSupplementaryData(?SupplementaryData $supplementaryData): self
    {
        $this->supplementaryData = $supplementaryData;
        return $this;
    }

    public function getMostRecentErrors(): array
    {
        return $this->mostRecentErrors;
    }

    public function setMostRecentErrors(array $mostRecentErrors): self
    {
        $this->mostRecentErrors = $mostRecentErrors;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->referenceId)) {
            $json['reference_id'] = $this->referenceId;
        }
        if (isset($this->amount)) {
            $json['amount'] = $this->amount;
        }
        if (isset($this->payee)) {
            $json['payee'] = $this->payee;
        }
        if (isset($this->paymentInstruction)) {
            $json['payment_instruction'] = $this->paymentInstruction;
        }
        if (isset($this->description)) {
            $json['description'] = $this->description;
        }
        if (isset($this->customId)) {
            $json['custom_id'] = $this->customId;
        }
        if (isset($this->invoiceId)) {
            $json['invoice_id'] = $this->invoiceId;
        }
        if (isset($this->id)) {
            $json['id'] = $this->id;
        }
        if (isset($this->softDescriptor)) {
            $json['soft_descriptor'] = $this->softDescriptor;
        }
        if (isset($this->items)) {
            $json['items'] = $this->items;
        }
        if (isset($this->shipping)) {
            $json['shipping'] = $this->shipping;
        }
        if (isset($this->supplementaryData)) {
            $json['supplementary_data'] = $this->supplementaryData;
        }
        if (isset($this->mostRecentErrors)) {
            $json['most_recent_errors'] = $this->mostRecentErrors;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
