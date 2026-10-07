<?php

namespace SytxLabs\PayPal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use SytxLabs\PayPal\Models\DTO\Money;

/**
 * A dispute or chargeback changed (CUSTOMER.DISPUTE.CREATED / .UPDATED / .RESOLVED).
 *
 * Disputes reference payments by the merchant's transaction ids ({@see self::$transactionIds}); map them to your own records.
 * Values follow the PayPal Customer Disputes API: status (OPEN, WAITING_FOR_BUYER_RESPONSE, WAITING_FOR_SELLER_RESPONSE, UNDER_REVIEW, RESOLVED, OTHER),
 * life cycle stage (INQUIRY, CHARGEBACK, PRE_ARBITRATION, ARBITRATION) and, once resolved, an outcome code (e.g. RESOLVED_BUYER_FAVOUR).
 */
class PayPalDisputeReceived
{
    use Dispatchable;

    /**
     * @param  string[]  $transactionIds  seller_transaction_id of every disputed transaction
     * @param  array<string,mixed>  $payload  full webhook event payload
     */
    public function __construct(
        public readonly string $eventType,
        public readonly ?string $disputeId,
        public readonly ?string $status,
        public readonly ?string $reason,
        public readonly ?string $lifeCycleStage,
        public readonly ?string $outcomeCode,
        public readonly ?Money $amount,
        public readonly array $transactionIds,
        public readonly array $payload,
    ) {
    }

    public function isChargeback(): bool
    {
        return $this->lifeCycleStage === 'CHARGEBACK';
    }

    public function isResolved(): bool
    {
        return $this->status === 'RESOLVED' || $this->eventType === 'CUSTOMER.DISPUTE.RESOLVED';
    }
}
