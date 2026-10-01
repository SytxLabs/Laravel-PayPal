<?php

namespace SytxLabs\PayPal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\Subscription;

/**
 * A subscription payment was refunded or reversed (PAYMENT.SALE.REFUNDED / PAYMENT.SALE.REVERSED).
 */
class PayPalSubscriptionPaymentRefunded
{
    use Dispatchable;
    use SerializesModels;

    /** @param  array<string,mixed>  $payload  full webhook event payload */
    public function __construct(public readonly string $subscriptionId, public readonly ?string $saleId, public readonly ?Money $amount, public readonly bool $reversed, public readonly array $payload, public readonly ?Subscription $subscription = null)
    {
    }
}
