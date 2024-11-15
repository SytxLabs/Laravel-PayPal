<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\PayPalProcessingInstruction;

class ConfirmOrder implements JsonSerializable
{
    private PaymentSource $paymentSource;
    private ?PayPalProcessingInstruction $processingInstruction = PayPalProcessingInstruction::NO_INSTRUCTION;
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
}
