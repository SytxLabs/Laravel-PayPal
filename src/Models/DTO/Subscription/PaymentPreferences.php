<?php

namespace SytxLabs\PayPal\Models\DTO\Subscription;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\Subscription\SetupFeeFailureAction;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class PaymentPreferences implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('auto_bill_outstanding')]
    private ?bool $autoBillOutstanding = true;
    #[ArrayMappingAttribute('setup_fee', Money::class)]
    private ?Money $setupFee = null;
    #[ArrayMappingAttribute('setup_fee_failure_action', SetupFeeFailureAction::class)]
    private ?SetupFeeFailureAction $setupFeeFailureAction = SetupFeeFailureAction::CANCEL;
    #[ArrayMappingAttribute('payment_failure_threshold')]
    private ?int $paymentFailureThreshold = null;

    public function getAutoBillOutstanding(): ?bool
    {
        return $this->autoBillOutstanding;
    }

    public function setAutoBillOutstanding(?bool $autoBillOutstanding): self
    {
        $this->autoBillOutstanding = $autoBillOutstanding;
        return $this;
    }

    public function getSetupFee(): ?Money
    {
        return $this->setupFee;
    }

    public function setSetupFee(?Money $setupFee): self
    {
        $this->setupFee = $setupFee;
        return $this;
    }

    public function getSetupFeeFailureAction(): ?SetupFeeFailureAction
    {
        return $this->setupFeeFailureAction;
    }

    public function setSetupFeeFailureAction(?SetupFeeFailureAction $setupFeeFailureAction): self
    {
        $this->setupFeeFailureAction = $setupFeeFailureAction;
        return $this;
    }

    public function getPaymentFailureThreshold(): ?int
    {
        return $this->paymentFailureThreshold;
    }

    public function setPaymentFailureThreshold(?int $paymentFailureThreshold): self
    {
        $this->paymentFailureThreshold = $paymentFailureThreshold;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->autoBillOutstanding)) {
            $json['auto_bill_outstanding'] = $this->autoBillOutstanding;
        }
        if (isset($this->setupFee)) {
            $json['setup_fee'] = $this->setupFee;
        }
        if (isset($this->setupFeeFailureAction)) {
            $json['setup_fee_failure_action'] = $this->setupFeeFailureAction->value;
        }
        if (isset($this->paymentFailureThreshold)) {
            $json['payment_failure_threshold'] = $this->paymentFailureThreshold;
        }
        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
