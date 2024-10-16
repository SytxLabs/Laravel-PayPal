<?php

namespace SytxLabs\PayPal\Services\Traits;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use PaypalServerSDKLib\Models\Order as PayPalOrder;
use SytxLabs\PayPal\Models\Order;

trait PayPalOrderSave
{
    use PayPalConfig;

    protected function getConnection(): ?Connection
    {
        if (!app()->bound('db')) {
            return null;
        }
        return DB::connection($this->config['oauth_database_connection'] ?? null);
    }

    protected function tableExists(): bool
    {
        return $this->getConnection()?->getSchemaBuilder()->hasTable('sytxlabs_paypal_orders') ?? false;
    }

    public function saveOrderToDatabase(PayPalOrder $order): Order|PayPalOrder
    {
        if (!$this->tableExists()) {
            return $order;
        }
        return Order::query()->updateOrCreate(['order_id' => $order->getId()], [
            'intent' => $order->getIntent(),
            'processing_instruction' => $order->getProcessingInstruction(),
            'status' => $order->getStatus(),
            'links' => $order->getLinks(),
        ]);
    }

    public function loadOrderFromDatabase(string $id): ?PayPalOrder
    {
        if (!$this->tableExists()) {
            return null;
        }
        return Order::query()->firstWhere('order_id', $id)?->payPalOrder;
    }
}
