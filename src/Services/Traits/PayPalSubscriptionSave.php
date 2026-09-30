<?php

namespace SytxLabs\PayPal\Services\Traits;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Subscription\Subscription as PayPalSubscription;
use SytxLabs\PayPal\Models\Subscription;

trait PayPalSubscriptionSave
{
    use PayPalConfig;

    /** @var array<string,bool>|null */
    private ?array $subscriptionColumns = null;

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

    protected function webhookEventTableName(): string
    {
        return $this->config['database']['webhook_event_table'] ?? 'sytxlabs_paypal_webhook_events';
    }

    protected function subscriptionTableExists(): bool
    {
        return $this->getConnection()?->getSchemaBuilder()->hasTable($this->subscriptionTableName()) ?? false;
    }

    /**
     * Only write columns that exist, so apps that have not run the newer migrations keep working.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    protected function onlyExistingSubscriptionColumns(array $data): array
    {
        $this->subscriptionColumns ??= array_fill_keys($this->getConnection()?->getSchemaBuilder()->getColumnListing($this->subscriptionTableName()) ?? [], true);
        return array_intersect_key($data, $this->subscriptionColumns);
    }

    public function saveSubscriptionToDatabase(PayPalSubscription $subscription, ?string $requestId = null, ?string $productId = null, ?Model $subscribable = null): PayPalSubscription|Subscription
    {
        if (!$this->subscriptionTableExists()) {
            return $subscription;
        }
        $billingInfo = $subscription->getBillingInfo();
        $lastPayment = $billingInfo?->getLastPayment();
        $data = [
            'subscription_id' => $subscription->getId(),
            'plan_id' => $subscription->getPlanId(),
            'status' => $subscription->getStatus(),
            'custom_id' => $subscription->getCustomId(),
            'quantity' => $subscription->getQuantity(),
            'links' => $subscription->getLinks(),
            'next_billing_time' => $billingInfo?->getNextBillingTime(),
            'last_payment_time' => $lastPayment?->getTime(),
            'last_payment_amount' => $lastPayment?->getAmount()?->getValue(),
            'last_payment_currency' => $lastPayment?->getAmount()?->getCurrencyCode(),
            'failed_payments_count' => $billingInfo?->getFailedPaymentsCount(),
            'product_id' => $productId,
            'request_id' => $requestId,
            'subscribable_type' => $subscribable?->getMorphClass(),
            'subscribable_id' => $subscribable?->getKey(),
        ];
        // Partial updates (e.g. webhooks) must not wipe already stored values.
        $data = $this->onlyExistingSubscriptionColumns(array_filter($data, static fn ($value) => $value !== null));
        return Subscription::query()->updateOrCreate(['subscription_id' => $subscription->getId()], $data);
    }

    public function loadSubscriptionFromDatabase(string $id): ?Subscription
    {
        if (!$this->subscriptionTableExists()) {
            return null;
        }
        return Subscription::query()->firstWhere('subscription_id', $id);
    }

    /**
     * Store a completed recurring payment (PAYMENT.SALE.COMPLETED) on the subscription.
     */
    public function recordSubscriptionPayment(string $subscriptionId, ?Money $amount, ?string $time): ?Subscription
    {
        $subscription = $this->loadSubscriptionFromDatabase($subscriptionId);
        if ($subscription === null) {
            return null;
        }
        $subscription->fill($this->onlyExistingSubscriptionColumns(array_filter([
            'last_payment_time' => $time,
            'last_payment_amount' => $amount?->getValue(),
            'last_payment_currency' => $amount?->getCurrencyCode(),
            'failed_payments_count' => 0,
        ], static fn ($value) => $value !== null)))->save();
        return $subscription;
    }

    /**
     * Store a failed recurring payment (BILLING.SUBSCRIPTION.PAYMENT.FAILED) on the subscription.
     * PayPal's own counter wins when present, otherwise the stored counter is incremented.
     */
    public function recordFailedSubscriptionPayment(string $subscriptionId, ?int $failedPaymentsCount = null): ?Subscription
    {
        $subscription = $this->loadSubscriptionFromDatabase($subscriptionId);
        if ($subscription === null) {
            return null;
        }
        $subscription->fill($this->onlyExistingSubscriptionColumns([
            'failed_payments_count' => $failedPaymentsCount ?? (($subscription->failed_payments_count ?? 0) + 1),
        ]))->save();
        return $subscription;
    }

    protected function webhookEventTableExists(): bool
    {
        return $this->getConnection()?->getSchemaBuilder()->hasTable($this->webhookEventTableName()) ?? false;
    }

    /**
     * Claim a webhook event id. Returns false when it was already processed (PayPal retry).
     * Without the events table every event is accepted.
     */
    public function claimWebhookEvent(?string $eventId, string $eventType, ?string $resourceId = null): bool
    {
        if ($eventId === null || !$this->webhookEventTableExists()) {
            return true;
        }
        try {
            $this->getConnection()?->table($this->webhookEventTableName())->insert([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'resource_id' => $resourceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            if ($this->getConnection()?->table($this->webhookEventTableName())->where('event_id', $eventId)->exists()) {
                return false;
            }
            throw $e;
        }
        return true;
    }

    /**
     * Release a claimed webhook event so a PayPal retry is processed again (e.g. after a failure).
     */
    public function releaseWebhookEvent(?string $eventId): void
    {
        if ($eventId === null || !$this->webhookEventTableExists()) {
            return;
        }
        $this->getConnection()?->table($this->webhookEventTableName())->where('event_id', $eventId)->delete();
    }
}
