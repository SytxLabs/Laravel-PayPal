<?php

namespace SytxLabs\PayPal\Services\Traits;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Subscription\Subscription as PayPalSubscription;
use SytxLabs\PayPal\Models\Subscription;

trait PayPalSubscriptionSave
{
    use PayPalConfig;

    /** @var array<string,bool>|null */
    private ?array $subscriptionColumns = null;

    /**
     * Fields that describe PayPal's remote state. They are skipped when an update is older than the stored state.
     *
     * @var string[]
     */
    private static array $remoteStateFields = [
        'plan_id',
        'status',
        'quantity',
        'next_billing_time',
        'last_payment_time',
        'last_payment_amount',
        'last_payment_currency',
        'failed_payments_count',
        'paypal_update_time',
    ];

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

    /**
     * PayPal sends UTC timestamps. Convert them to the application timezone, which is how Eloquent
     * writes and reads datetime columns, so stored values and comparisons share one convention.
     */
    protected function parsePayPalTime(?string $time): ?Carbon
    {
        if ($time === null || $time === '') {
            return null;
        }
        return Carbon::parse($time)->setTimezone(config('app.timezone', date_default_timezone_get()));
    }

    public function saveSubscriptionToDatabase(PayPalSubscription $subscription, ?string $requestId = null, ?string $productId = null, ?Model $subscribable = null): PayPalSubscription|Subscription
    {
        if (!$this->subscriptionTableExists() || $subscription->getId() === null) {
            return $subscription;
        }
        $billingInfo = $subscription->getBillingInfo();
        $lastPayment = $billingInfo?->getLastPayment();
        $version = $this->parsePayPalTime($subscription->getUpdateTime());
        $data = [
            'plan_id' => $subscription->getPlanId(),
            'status' => $subscription->getStatus(),
            'custom_id' => $subscription->getCustomId(),
            'quantity' => $subscription->getQuantity(),
            'links' => $subscription->getLinks(),
            'next_billing_time' => $this->parsePayPalTime($billingInfo?->getNextBillingTime()),
            'last_payment_time' => $this->parsePayPalTime($lastPayment?->getTime()),
            'last_payment_amount' => $lastPayment?->getAmount()?->getValue(),
            'last_payment_currency' => $lastPayment?->getAmount()?->getCurrencyCode(),
            'failed_payments_count' => $billingInfo?->getFailedPaymentsCount(),
            'paypal_update_time' => $version,
            'product_id' => $productId,
            'request_id' => $requestId,
            'subscribable_type' => $subscribable?->getMorphClass(),
            'subscribable_id' => $subscribable?->getKey(),
        ];
        // Partial updates (e.g. webhooks) must not wipe already stored values.
        $data = array_filter($data, static fn ($value) => $value !== null);

        return $this->writeSubscription($subscription->getId(), static function (?Subscription $existing) use ($data, $version) {
            // Out-of-order delivery: an older PayPal state must not overwrite a newer one (e.g. ACTIVE after CANCELLED).
            if ($version !== null && $existing?->paypal_update_time !== null && $version->lt($existing->paypal_update_time)) {
                return array_diff_key($data, array_flip(self::$remoteStateFields));
            }
            return $data;
        });
    }

    /**
     * Insert or update a subscription row under a row lock. $build receives the current row (or null)
     * and returns the attributes to write. A concurrent insert of the same subscription id is retried as update.
     *
     * @param  callable(?Subscription): array<string,mixed>  $build
     */
    protected function writeSubscription(string $subscriptionId, callable $build): Subscription
    {
        $write = fn () => Subscription::query()->getConnection()->transaction(function () use ($subscriptionId, $build) {
            $existing = Subscription::query()->where('subscription_id', $subscriptionId)->lockForUpdate()->first();
            $data = $this->onlyExistingSubscriptionColumns($build($existing));
            if ($existing !== null) {
                $existing->fill($data)->save();
                return $existing;
            }
            return Subscription::query()->create(['subscription_id' => $subscriptionId] + $data);
        });
        try {
            return $write();
        } catch (QueryException $e) {
            // unique index on subscription_id: another request created the row first
            if (!Subscription::query()->where('subscription_id', $subscriptionId)->exists()) {
                throw $e;
            }
            return $write();
        }
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
     * A payment older than the stored one is ignored.
     */
    public function recordSubscriptionPayment(string $subscriptionId, ?Money $amount, ?string $time): ?Subscription
    {
        if ($this->loadSubscriptionFromDatabase($subscriptionId) === null) {
            return null;
        }
        $paidAt = $this->parsePayPalTime($time);
        return $this->writeSubscription($subscriptionId, static function (?Subscription $existing) use ($amount, $paidAt) {
            if ($paidAt !== null && $existing?->last_payment_time !== null && $paidAt->lt($existing->last_payment_time)) {
                return [];
            }
            return array_filter([
                'last_payment_time' => $paidAt,
                'last_payment_amount' => $amount?->getValue(),
                'last_payment_currency' => $amount?->getCurrencyCode(),
                'failed_payments_count' => 0,
            ], static fn ($value) => $value !== null);
        });
    }

    /**
     * Store a failed recurring payment (BILLING.SUBSCRIPTION.PAYMENT.FAILED) on the subscription.
     * PayPal's own counter wins when present, otherwise the stored counter is incremented.
     * An event older than the stored update time or last payment is ignored.
     */
    public function recordFailedSubscriptionPayment(string $subscriptionId, ?int $failedPaymentsCount = null, ?string $time = null): ?Subscription
    {
        if ($this->loadSubscriptionFromDatabase($subscriptionId) === null) {
            return null;
        }
        $failedAt = $this->parsePayPalTime($time);
        return $this->writeSubscription($subscriptionId, static function (?Subscription $existing) use ($failedPaymentsCount, $failedAt) {
            if ($failedAt !== null && (
                ($existing?->paypal_update_time !== null && $failedAt->lt($existing->paypal_update_time))
                || ($existing?->last_payment_time !== null && $failedAt->lt($existing->last_payment_time))
            )) {
                return [];
            }
            return ['failed_payments_count' => $failedPaymentsCount ?? (($existing?->failed_payments_count ?? 0) + 1)];
        });
    }

    protected function webhookEventTableExists(): bool
    {
        return $this->getConnection()?->getSchemaBuilder()->hasTable($this->webhookEventTableName()) ?? false;
    }

    /**
     * Whether processed webhook event ids can be stored (database enabled and events table migrated).
     */
    public function hasWebhookEventStore(): bool
    {
        return $this->webhookEventTableExists();
    }

    /**
     * Run $handler once per webhook event id. The event claim and all database changes of $handler share
     * one transaction: a failure or crash rolls both back, so PayPal's retry is processed again, and a
     * retry of a committed event is reported as duplicate.
     *
     * @return bool false when the event id was already processed
     */
    public function processWebhookEventOnce(?string $eventId, string $eventType, ?string $resourceId, callable $handler): bool
    {
        $connection = $this->getConnection();
        if ($connection === null) {
            $handler();
            return true;
        }
        $claim = $eventId !== null && $this->webhookEventTableExists();
        try {
            $connection->transaction(function () use ($connection, $claim, $eventId, $eventType, $resourceId, $handler) {
                if ($claim) {
                    // unique event_id: a concurrent delivery of the same event waits here and then fails
                    $connection->table($this->webhookEventTableName())->insert([
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'resource_id' => $resourceId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $handler();
            });
        } catch (QueryException $e) {
            if ($claim && $connection->table($this->webhookEventTableName())->where('event_id', $eventId)->exists()) {
                return false;
            }
            throw $e;
        }
        return true;
    }
}
