<?php

namespace SytxLabs\PayPal\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use SytxLabs\PayPal\Enums\DTO\Subscription\SubscriptionStatus;
use SytxLabs\PayPal\Facades\Accessor\PayPalSubscriptionFacadeAccessor;
use SytxLabs\PayPal\Facades\PayPal;
use SytxLabs\PayPal\Models\DTO\Subscription\Subscription as PayPalSubscription;

/**
 * @property string $subscription_id
 * @property ?string $plan_id
 * @property ?string $product_id
 * @property ?SubscriptionStatus $status
 * @property ?string $custom_id
 * @property ?array $links
 * @property ?string $request_id
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read PayPalSubscription $payPalSubscription {@see self::payPalSubscription}
 * @property-read Model $subscribable {@see self::subscribable}
 */
class Subscription extends Model
{
    use HasTimestamps;

    protected $fillable = [
        'subscription_id',
        'plan_id',
        'product_id',
        'status',
        'custom_id',
        'links',
        'request_id',
    ];

    protected $casts = [
        'links' => 'array',
        'status' => SubscriptionStatus::class,
    ];

    public function getTable(): string
    {
        $configFile = function_exists('config') && app()->bound('config') ? config('paypal') : [];
        if (PayPalSubscriptionFacadeAccessor::getProvider() === null) {
            return $configFile['database']['subscription_table'] ?? 'sytxlabs_paypal_subscriptions';
        }
        return PayPalSubscriptionFacadeAccessor::getProvider()?->config['database']['subscription_table'] ?? $configFile['database']['subscription_table'] ?? 'sytxlabs_paypal_subscriptions';
    }

    public function getConnectionName(): ?string
    {
        if (!PayPal::getProvider()) {
            return null;
        }
        return PayPal::getProvider()?->config['database']['connection'] ?? null;
    }

    public function subscribable(): MorphTo
    {
        return $this->morphTo('subscribable');
    }

    /** @noinspection PhpUnused */
    public function scopeSubscribable(Builder $query, Model $model): Builder
    {
        return $query->where('subscribable_type', $model->getMorphClass())
            ->where('subscribable_id', $model->getKey());
    }

    public function payPalSubscription(): Attribute
    {
        return new Attribute(
            fn () => (new PayPalSubscription())
                ->setId($this->subscription_id)
                ->setPlanId($this->plan_id)
                ->setCustomId($this->custom_id)
                ->setStatus($this->status)
                ->setLinks($this->links)
                ->setCreateTime($this->created_at->format('c'))
                ->setUpdateTime($this->updated_at->format('c')),
            function (PayPalSubscription $subscription) {
                $this->subscription_id = $subscription->getId();
                $this->plan_id = $subscription->getPlanId();
                $this->custom_id = $subscription->getCustomId();
                $this->status = $subscription->getStatus();
                $this->links = $subscription->getLinks();
                $this->save();
            }
        );
    }
}
