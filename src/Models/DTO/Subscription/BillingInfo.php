<?php

namespace SytxLabs\PayPal\Models\DTO\Subscription;

use JsonSerializable;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class BillingInfo implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('outstanding_balance', Money::class)]
    private ?Money $outstandingBalance = null;
    #[ArrayMappingAttribute('last_payment', LastPayment::class)]
    private ?LastPayment $lastPayment = null;
    #[ArrayMappingAttribute('next_billing_time')]
    private ?string $nextBillingTime = null;
    #[ArrayMappingAttribute('final_payment_time')]
    private ?string $finalPaymentTime = null;
    #[ArrayMappingAttribute('failed_payments_count')]
    private ?int $failedPaymentsCount = null;

    public function getOutstandingBalance(): ?Money
    {
        return $this->outstandingBalance;
    }

    public function setOutstandingBalance(?Money $outstandingBalance): self
    {
        $this->outstandingBalance = $outstandingBalance;
        return $this;
    }

    public function getLastPayment(): ?LastPayment
    {
        return $this->lastPayment;
    }

    public function setLastPayment(?LastPayment $lastPayment): self
    {
        $this->lastPayment = $lastPayment;
        return $this;
    }

    public function getNextBillingTime(): ?string
    {
        return $this->nextBillingTime;
    }

    public function setNextBillingTime(?string $nextBillingTime): self
    {
        $this->nextBillingTime = $nextBillingTime;
        return $this;
    }

    public function getFinalPaymentTime(): ?string
    {
        return $this->finalPaymentTime;
    }

    public function setFinalPaymentTime(?string $finalPaymentTime): self
    {
        $this->finalPaymentTime = $finalPaymentTime;
        return $this;
    }

    public function getFailedPaymentsCount(): ?int
    {
        return $this->failedPaymentsCount;
    }

    public function setFailedPaymentsCount(?int $failedPaymentsCount): self
    {
        $this->failedPaymentsCount = $failedPaymentsCount;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = [];
        if (isset($this->outstandingBalance)) {
            $json['outstanding_balance'] = $this->outstandingBalance;
        }
        if (isset($this->lastPayment)) {
            $json['last_payment'] = $this->lastPayment;
        }
        if (isset($this->nextBillingTime)) {
            $json['next_billing_time'] = $this->nextBillingTime;
        }
        if (isset($this->finalPaymentTime)) {
            $json['final_payment_time'] = $this->finalPaymentTime;
        }
        if (isset($this->failedPaymentsCount)) {
            $json['failed_payments_count'] = $this->failedPaymentsCount;
        }
        return $json;
    }
}
