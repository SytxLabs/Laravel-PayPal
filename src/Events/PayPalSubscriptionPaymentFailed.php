<?php

namespace SytxLabs\PayPal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SytxLabs\PayPal\Models\Subscription;

/**
 * A recurring subscription payment failed (BILLING.SUBSCRIPTION.PAYMENT.FAILED).
 */
class PayPalSubscriptionPaymentFailed
{
    use Dispatchable;
    use SerializesModels;

    /** @param  array<string,mixed>  $payload  full webhook event payload */
    public function __construct(public readonly string $subscriptionId, public readonly ?int $failedPaymentsCount, public readonly array $payload, public readonly ?Subscription $subscription = null)
    {
    }
}
