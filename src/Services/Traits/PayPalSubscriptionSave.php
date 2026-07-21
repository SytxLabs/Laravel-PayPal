<?php

namespace SytxLabs\PayPal\Services\Traits;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use SytxLabs\PayPal\Models\DTO\Subscription\Subscription as PayPalSubscription;
use SytxLabs\PayPal\Models\Subscription;

trait PayPalSubscriptionSave
{
    use PayPalConfig;

    protected function getConnection(): ?Connection
    {
        if (($this->config['database']['enabled'] ?? false) !== true || !app()->bound('db')) {
            return null;
        }
        return DB::connection($this->config['database']['connection'] ?? null);
    }

    protected function subscriptionTableName(): string
    {
        return $this->config['database']['subscription_table'] ?? 'sytxlabs_paypal_subscriptions';
    }

    protected function subscriptionTableExists(): bool
    {
        return $this->getConnection()?->getSchemaBuilder()->hasTable($this->subscriptionTableName()) ?? false;
    }

    protected function subscriptionTableRequestIdExists(): bool
    {
        return $this->getConnection()?->getSchemaBuilder()->hasColumn($this->subscriptionTableName(), 'request_id') ?? false;
    }

    public function saveSubscriptionToDatabase(PayPalSubscription $subscription, ?string $requestId = null, ?string $productId = null): PayPalSubscription|Subscription
    {
        if (!$this->subscriptionTableExists()) {
            return $subscription;
        }
        $data = [
            'subscription_id' => $subscription->getId(),
            'plan_id' => $subscription->getPlanId(),
            'status' => $subscription->getStatus(),
            'custom_id' => $subscription->getCustomId(),
            'links' => $subscription->getLinks(),
        ];
        if ($productId !== null) {
            $data['product_id'] = $productId;
        }
        if ($requestId !== null && $this->subscriptionTableRequestIdExists()) {
            $data['request_id'] = $requestId;
        }
        return Subscription::query()->updateOrCreate(['subscription_id' => $subscription->getId()], $data);
    }

    public function loadSubscriptionFromDatabase(string $id): ?Subscription
    {
        if (!$this->subscriptionTableExists()) {
            return null;
        }
        return Subscription::query()->firstWhere('subscription_id', $id);
    }
}
