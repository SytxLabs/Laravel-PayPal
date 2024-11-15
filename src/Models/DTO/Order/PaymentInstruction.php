<?php

namespace SytxLabs\PayPal\Models\DTO\Order;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\DisbursementMode;

class PaymentInstruction implements JsonSerializable
{
    /**
     * @var PlatformFee[]|null
     */
    private ?array $platformFees;
    private ?DisbursementMode $disbursementMode = DisbursementMode::INSTANT;
    private ?string $payeePricingTierId;
    private ?string $payeeReceivableFxRateId;

    /**
     * @return PlatformFee[]|null
     */
    public function getPlatformFees(): ?array
    {
        return $this->platformFees;
    }

    public function setPlatformFees(?array $platformFees): self
    {
        $this->platformFees = $platformFees;
        return $this;
    }

    public function getDisbursementMode(): ?DisbursementMode
    {
        return $this->disbursementMode;
    }

    public function setDisbursementMode(?DisbursementMode $disbursementMode): self
    {
        $this->disbursementMode = $disbursementMode;
        return $this;
    }

    public function getPayeePricingTierId(): ?string
    {
        return $this->payeePricingTierId;
    }

    public function setPayeePricingTierId(?string $payeePricingTierId): self
    {
        $this->payeePricingTierId = $payeePricingTierId;
        return $this;
    }

    public function getPayeeReceivableFxRateId(): ?string
    {
        return $this->payeeReceivableFxRateId;
    }

    public function setPayeeReceivableFxRateId(?string $payeeReceivableFxRateId): self
    {
        $this->payeeReceivableFxRateId = $payeeReceivableFxRateId;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->platformFees)) {
            $json['platform_fees'] = $this->platformFees;
        }
        if (isset($this->disbursementMode)) {
            $json['disbursement_mode'] = $this->disbursementMode->value;
        }
        if (isset($this->payeePricingTierId)) {
            $json['payee_pricing_tier_id'] = $this->payeePricingTierId;
        }
        if (isset($this->payeeReceivableFxRateId)) {
            $json['payee_receivable_fx_rate_id'] = $this->payeeReceivableFxRateId;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
