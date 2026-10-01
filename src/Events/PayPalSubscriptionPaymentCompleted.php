<?php

namespace SytxLabs\PayPal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\Subscription;

/**
 * A recurring subscription payment was received (PAYMENT.SALE.COMPLETED).
 */
class PayPalSubscriptionPaymentCompleted
{
    use Dispatchable;
    use SerializesModels;

    /** @param  array<string,mixed>  $payload  full webhook event payload */
    public function __construct(public readonly string $subscriptionId, public readonly ?string $saleId, public readonly ?Money $amount, public readonly array $payload, public readonly ?Subscription $subscription = null)
    {
    }
}
