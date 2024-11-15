<?php

namespace SytxLabs\PayPal\Services\Traits;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use SytxLabs\PayPal\Models\DTO\Order as PayPalOrder;
use SytxLabs\PayPal\Models\Order;

trait PayPalOrderSave
{
    use PayPalConfig;

    protected function getConnection(): ?Connection
    {
        if (($this->config['database']['enabled'] ?? false) !== true || !app()->bound('db')) {
            return null;
        }
        return DB::connection($this->config['database']['connection'] ?? null);
    }

    protected function orderTableName(): string
    {
        return $this->config['database']['order_table'] ?? 'sytxlabs_paypal_orders';
    }

    protected function orderTableExists(): bool
    {
        return $this->getConnection()?->getSchemaBuilder()->hasTable($this->orderTableName()) ?? false;
    }

    protected function orderTableRequestIdExists(): bool
    {
        return $this->getConnection()?->getSchemaBuilder()->hasColumn($this->orderTableName(), 'request_id') ?? false;
    }

    public function saveOrderToDatabase(PayPalOrder $order, ?string $requestId = null): Order|PayPalOrder
    {
        if (!$this->orderTableExists()) {
            return $order;
        }
        $data = [
            'order_id' => $order->getId(),
            'intent' => $order->getIntent(),
            'processing_instruction' => $order->getProcessingInstruction(),
            'status' => $order->getStatus(),
            'links' => $order->getLinks(),
        ];
        if ($requestId !== null && $this->orderTableRequestIdExists()) {
            $data['request_id'] = $requestId;
        }
        return Order::query()->updateOrCreate(['order_id' => $order->getId()], $data);
    }

    public function loadOrderFromDatabase(string $id): ?Order
    {
        if (!$this->orderTableExists()) {
            return null;
        }
        return Order::query()->firstWhere('order_id', $id);
    }
}
