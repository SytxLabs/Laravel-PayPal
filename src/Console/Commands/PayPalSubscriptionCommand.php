<?php

namespace SytxLabs\PayPal\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use SytxLabs\PayPal\Enums\DTO\Subscription\SubscriptionStatus;
use SytxLabs\PayPal\Models\Subscription;
use SytxLabs\PayPal\Services\PayPalSubscription;
use Throwable;

class PayPalSubscriptionCommand extends Command
{
    protected $name = 'paypal:subscription';

    protected $description = 'Scheduler: fetch due PayPal subscriptions from PayPal. With an id: show that subscription';

    protected function getArguments(): array
    {
        return [
            ['id', InputArgument::OPTIONAL, 'PayPal subscription id (I-...) to show'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['refresh', null, InputOption::VALUE_NONE, 'With an id: fetch the current state from PayPal and store it'],
            ['status', null, InputOption::VALUE_REQUIRED, 'Only due subscriptions with this status (default: ACTIVE)'],
            ['plan', null, InputOption::VALUE_REQUIRED, 'Only due subscriptions of this plan id'],
            ['custom-id', null, InputOption::VALUE_REQUIRED, 'Only due subscriptions with this custom id'],
            ['limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of due subscriptions per run (default: unlimited)'],
        ];
    }

    public function handle(): int
    {
        $id = $this->argument('id');
        return $id === null ? $this->fetchDueSubscriptions() : $this->showSubscription($id);
    }

    private function service(): PayPalSubscription
    {
        return app()->bound('paypal_subscription_client') ? app('paypal_subscription_client') : new PayPalSubscription();
    }

    private function showSubscription(string $id): int
    {
        $service = $this->service();
        try {
            $dto = $this->option('refresh') ? $service->getSubscriptionFromPayPal($id) : null;
        } catch (Throwable $e) {
            $this->error('Could not fetch subscription from PayPal: ' . $e->getMessage());
            return self::FAILURE;
        }

        $row = $service->loadSubscriptionFromDatabase($id);
        if ($row === null && $dto === null) {
            $this->error("Subscription $id not found. Use --refresh to fetch it from PayPal.");
            return self::FAILURE;
        }

        $billingInfo = $dto?->getBillingInfo();
        $lastPayment = $billingInfo?->getLastPayment();
        $lastAmount = $lastPayment?->getAmount();
        $this->table(['Field', 'Value'], [
            ['Subscription', $id],
            ['Status', ($dto?->getStatus() ?? $row?->status)?->value ?? '-'],
            ['Plan', $dto?->getPlanId() ?? $row?->plan_id ?? '-'],
            ['Product', $row?->product_id ?? '-'],
            ['Custom id', $dto?->getCustomId() ?? $row?->custom_id ?? '-'],
            ['Quantity', $dto?->getQuantity() ?? $row?->quantity ?? '-'],
            ['Subscriber', $dto?->getSubscriber()?->getEmailAddress() ?? '-'],
            ['Linked model', $row?->subscribable_type !== null ? $row->subscribable_type . '#' . $row->subscribable_id : '-'],
            ['Next billing', $billingInfo?->getNextBillingTime() ?? $row?->next_billing_time?->toIso8601String() ?? '-'],
            ['Last payment', $lastAmount !== null
                ? $lastAmount->getValue() . ' ' . $lastAmount->getCurrencyCode() . ' @ ' . ($lastPayment->getTime() ?? '-')
                : ($row?->last_payment_amount !== null ? $row->last_payment_amount . ' ' . $row->last_payment_currency . ' @ ' . ($row->last_payment_time?->toIso8601String() ?? '-') : '-')],
            ['Outstanding balance', $billingInfo?->getOutstandingBalance() !== null ? $billingInfo->getOutstandingBalance()->getValue() . ' ' . $billingInfo->getOutstandingBalance()->getCurrencyCode() : '-'],
            ['Failed payments', (string) ($billingInfo?->getFailedPaymentsCount() ?? $row?->failed_payments_count ?? 0)],
            ['Created', $dto?->getCreateTime() ?? $row?->created_at?->toIso8601String() ?? '-'],
            ['Updated', $dto?->getUpdateTime() ?? $row?->updated_at?->toIso8601String() ?? '-'],
        ]);
        if ($dto === null) {
            $this->line('<comment>Stored data. Use --refresh for the live state from PayPal.</comment>');
        }
        return self::SUCCESS;
    }

    /**
     * Scheduler run: PayPal charges on its own, so every subscription whose billing date is reached
     * is fetched from PayPal to store the result (last payment, next billing time, status).
     */
    private function fetchDueSubscriptions(): int
    {
        $status = $this->option('status') ?? SubscriptionStatus::ACTIVE->value;
        if (SubscriptionStatus::tryFrom(strtoupper($status)) === null) {
            $this->error("Unknown status $status. Valid: " . implode(', ', array_column(SubscriptionStatus::cases(), 'value')));
            return self::FAILURE;
        }

        $query = Subscription::query()
            ->where('next_billing_time', '<=', now())
            ->where('status', strtoupper($status))
            ->oldest('next_billing_time');
        if ($this->option('limit') !== null) {
            $query->limit(max(1, (int) $this->option('limit')));
        }
        if ($this->option('plan') !== null) {
            $query->where('plan_id', $this->option('plan'));
        }
        if ($this->option('custom-id') !== null) {
            $query->where('custom_id', $this->option('custom-id'));
        }
        $subscriptions = $query->get();
        if ($subscriptions->isEmpty()) {
            $this->info('No subscriptions due.');
            return self::SUCCESS;
        }

        $service = $this->service();
        $failed = 0;
        foreach ($subscriptions as $subscription) {
            try {
                $service->getSubscriptionFromPayPal($subscription->subscription_id);
            } catch (Throwable $e) {
                $failed++;
                $this->warn("Could not refresh $subscription->subscription_id: " . $e->getMessage());
            }
        }
        $subscriptions = $subscriptions->map->fresh();

        $this->table(
            ['Subscription', 'Status', 'Plan', 'Custom id', 'Next billing', 'Last payment', 'Failed', 'Updated'],
            $subscriptions->map(static fn (Subscription $subscription) => [
                $subscription->subscription_id,
                $subscription->status?->value ?? '-',
                $subscription->plan_id ?? '-',
                $subscription->custom_id ?? '-',
                $subscription->next_billing_time?->toDateTimeString() ?? '-',
                $subscription->last_payment_amount !== null ? $subscription->last_payment_amount . ' ' . $subscription->last_payment_currency : '-',
                (string) ($subscription->failed_payments_count ?? 0),
                $subscription->updated_at?->toDateTimeString() ?? '-',
            ])->all(),
        );
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
