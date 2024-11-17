<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\PayPalProcessingInstruction;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class ConfirmOrder implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('payment_source', PaymentSource::class)]
    private PaymentSource $paymentSource;
    #[ArrayMappingAttribute('processing_instruction', PayPalProcessingInstruction::class)]
    private ?PayPalProcessingInstruction $processingInstruction = PayPalProcessingInstruction::NO_INSTRUCTION;
    #[ArrayMappingAttribute('application_context', ApplicationContext::class)]
    private ?ApplicationContext $applicationContext;

    public function __construct(PaymentSource $paymentSource)
    {
        $this->paymentSource = $paymentSource;
    }

    public function getPaymentSource(): PaymentSource
    {
        return $this->paymentSource;
    }

    public function setPaymentSource(PaymentSource $paymentSource): self
    {
        $this->paymentSource = $paymentSource;
        return $this;
    }

    public function getProcessingInstruction(): ?string
    {
        return $this->processingInstruction;
    }

    public function setProcessingInstruction(?string $processingInstruction): self
    {
        $this->processingInstruction = $processingInstruction;
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

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array
    {
        $json = ['payment_source' => $this->paymentSource];
        if (isset($this->processingInstruction)) {
            $json['processing_instruction'] = $this->processingInstruction->value;
        }
        if (isset($this->applicationContext)) {
            $json['application_context'] = $this->applicationContext;
        }

        return $json;
    }

    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new self(PaymentSource::fromArray($data['payment_source'])), $data);
    }
}
