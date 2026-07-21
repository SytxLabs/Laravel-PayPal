<?php

namespace SytxLabs\PayPal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayPalWebhookReceived
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string,mixed>  $payload  full webhook event payload
     */
    public function __construct(
        public readonly string $eventType,
        public readonly array $payload,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function resource(): array
    {
        return $this->payload['resource'] ?? [];
    }
}
