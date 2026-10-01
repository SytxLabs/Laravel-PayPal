<?php

namespace SytxLabs\PayPal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SytxLabs\PayPal\Models\Subscription;

class PayPalWebhookReceived
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string,mixed>  $payload  full webhook event payload
     * @param  Subscription|null  $subscription  stored subscription the event belongs to, if any
     */
    public function __construct(public readonly string $eventType, public readonly array $payload, public readonly ?Subscription $subscription = null)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function resource(): array
    {
        return $this->payload['resource'] ?? [];
    }
}
