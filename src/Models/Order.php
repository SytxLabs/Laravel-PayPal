<?php

namespace SytxLabs\PayPal\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
 *
 * @property-read PayPalOrder $payPalOrder {@see self::payPalOrder}
 * @property-read Model $orderable {@see self::orderable}
 */
class Order extends Model
{
    protected $fillable = [
        'order_id',
        'intent',
        'processing_instruction',
        'status',
        'links',
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

    public function payPalOrder(): Attribute
    {
        return new Attribute(
            fn () => OrderBuilder::init()
            ->id($this->order_id)
            ->intent($this->intent)
            ->processingInstruction($this->processing_instruction)
            ->status($this->status)
            ->links($this->links)
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
