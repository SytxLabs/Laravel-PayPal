<?php

namespace SytxLabs\PayPal\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use PaypalServerSdkLib\Models\Builders\OrderBuilder;
use PaypalServerSdkLib\Models\Order as PayPalOrder;
use SytxLabs\PayPal\Facades\Accessor\PayPalOrderFacadeAccessor;
use SytxLabs\PayPal\Facades\PayPal;

/**
 * @property string $order_id
 * @property ?string $intent
 * @property ?string $processing_instruction
 * @property ?string $status
 * @property ?array $links
 * @property ?string $request_id
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read PayPalOrder $payPalOrder {@see self::payPalOrder}
 * @property-read Model $orderable {@see self::orderable}
 */
class Order extends Model
{
    use HasTimestamps;

    protected $fillable = [
        'order_id',
        'intent',
        'processing_instruction',
        'status',
        'links',
        'request_id',
    ];

    protected $casts = [
        'links' => 'array',
    ];

    public function getTable(): string
    {
        if (PayPalOrderFacadeAccessor::getProvider() === null) {
            return 'sytxlabs_paypal_orders';
        }
        return PayPalOrderFacadeAccessor::getProvider()?->config['database']['order_table'] ?? 'sytxlabs_paypal_orders';
    }

    public function getConnectionName(): ?string
    {
        if (!PayPal::getProvider()) {
            return null;
        }
        return PayPal::getProvider()?->config['database']['connection'] ?? null;
    }

    public function orderable(): MorphTo
    {
        return $this->morphTo('orderable');
    }

    public function scopeOrderable(Builder $query, Model $model): Builder
    {
        return $query->where('orderable_type', $model->getMorphClass())
            ->where('orderable_id', $model->getKey());
    }

    public function payPalOrder(): Attribute
    {
        return new Attribute(
            fn () => OrderBuilder::init()
            ->id($this->order_id)
            ->intent($this->intent)
            ->processingInstruction($this->processing_instruction)
            ->status($this->status)
            ->links($this->links)
            ->createTime($this->created_at->format('c'))
            ->updateTime($this->updated_at->format('c'))
            ->build(),
            function (PayPalOrder $order) {
                $this->order_id = $order->getId();
                $this->intent = $order->getIntent();
                $this->processing_instruction = $order->getProcessingInstruction();
                $this->status = $order->getStatus();
                $this->links = $order->getLinks();
                $this->save();
            }
        );
    }
}
