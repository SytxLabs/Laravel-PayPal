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
        if (!app()->bound('db') || $this->config['database']['enabled'] !== true) {
            return null;
        }
        return DB::connection($this->config['database']['connection'] ?? null);
    }

    protected function tableName(): string
    {
        return $this->config['database']['order_table'] ?? 'sytxlabs_paypal_orders';
    }

    protected function tableExists(): bool
    {
        return $this->getConnection()?->getSchemaBuilder()->hasTable($this->tableName()) ?? false;
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

    public function loadOrderFromDatabase(string $id): ?Order
    {
        if (!$this->tableExists()) {
            return null;
        }
        return Order::query()->firstWhere('order_id', $id);
    }
}
