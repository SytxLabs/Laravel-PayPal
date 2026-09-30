<?php

namespace SytxLabs\PayPal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SytxLabs\PayPal\Enums\DTO\Subscription\SubscriptionStatus;
use SytxLabs\PayPal\Events\PayPalSubscriptionPaymentCompleted;
use SytxLabs\PayPal\Events\PayPalSubscriptionPaymentFailed;
use SytxLabs\PayPal\Events\PayPalSubscriptionPaymentRefunded;
use SytxLabs\PayPal\Events\PayPalWebhookReceived;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\Subscription\Subscription as PayPalSubscriptionDTO;
use SytxLabs\PayPal\Models\Subscription;
use SytxLabs\PayPal\Services\PayPalSubscription;
use Throwable;

class PayPalWebhookController
{
    /**
     * Events to dispatch once the database changes of the webhook are committed.
     *
     * @var object[]
     */
    private array $events = [];

    /**
     * @throws Throwable
     */
    public function __invoke(Request $request): JsonResponse
    {
        $service = app()->bound('paypal_subscription_client')
            ? app('paypal_subscription_client')
            : new PayPalSubscription();

        $body = $request->getContent();
        $verified = $service->verifyWebhookSignature($request->headers->all(), $body);
        if (!$verified) {
            return response()->json(['status' => 'invalid_signature'], 400);
        }

        $payload = json_decode($body, true) ?? [];
        $eventType = $payload['event_type'] ?? '';
        $resource = $payload['resource'] ?? [];
        $eventId = $payload['id'] ?? null;

        // Without the events table PayPal retries would be processed twice: fail closed, PayPal retries later.
        if (($service->config['webhook']['deduplicate'] ?? true) === true && !$service->hasWebhookEventStore()) {
            $service->log('PayPal webhook rejected: webhook event table missing or database disabled', ['event_id' => $eventId, 'event_type' => $eventType]);
            return response()->json(['status' => 'event_store_unavailable'], 503);
        }

        $subscription = null;
        $this->events = [];
        // Claim + database changes are one transaction: a failure rolls both back and PayPal's retry is processed again.
        $processed = $service->processWebhookEventOnce($eventId, $eventType, $resource['id'] ?? null, function () use (&$subscription, $service, $eventType, $resource, $payload) {
            $this->events = [];
            $subscription = $this->handle($service, $eventType, $resource, $payload);
        });
        if (!$processed) {
            return response()->json(['status' => 'duplicate']);
        }

        // Dispatched after commit. The event is already marked as processed, so a failing listener is not
        // retried by PayPal: use queued listeners for work that must not get lost.
        foreach ($this->events as $event) {
            event($event);
        }
        PayPalWebhookReceived::dispatch($eventType, $payload, $subscription?->fresh());

        return response()->json(['status' => 'ok']);
    }

    /**
     * @param  array<string,mixed>  $resource
     * @param  array<string,mixed>  $payload
     */
    private function handle(PayPalSubscription $service, string $eventType, array $resource, array $payload): ?Subscription
    {
        if (str_starts_with($eventType, 'BILLING.SUBSCRIPTION.')) {
            $subscription = $this->syncSubscription($service, $eventType, $resource, $payload);
            if ($eventType === 'BILLING.SUBSCRIPTION.PAYMENT.FAILED' && isset($resource['id'])) {
                $failedCount = $resource['billing_info']['failed_payments_count'] ?? null;
                $subscription = $service->recordFailedSubscriptionPayment($resource['id'], $failedCount) ?? $subscription;
                $this->events[] = new PayPalSubscriptionPaymentFailed($resource['id'], $subscription?->failed_payments_count ?? $failedCount, $payload, $subscription);
            }
            return $subscription;
        }

        $subscriptionId = $resource['billing_agreement_id'] ?? null;
        if ($subscriptionId === null) {
            return null;
        }
        $amount = self::saleAmount($resource);
        $saleId = $resource['sale_id'] ?? $resource['id'] ?? null;
        switch ($eventType) {
            case 'PAYMENT.SALE.COMPLETED':
                $subscription = $service->recordSubscriptionPayment($subscriptionId, $amount, $resource['create_time'] ?? null);
                $this->events[] = new PayPalSubscriptionPaymentCompleted($subscriptionId, $resource['id'] ?? null, $amount, $payload, $subscription);
                return $subscription;
            case 'PAYMENT.SALE.REFUNDED':
            case 'PAYMENT.SALE.REVERSED':
                $subscription = $service->loadSubscriptionFromDatabase($subscriptionId);
                $this->events[] = new PayPalSubscriptionPaymentRefunded($subscriptionId, $saleId, $amount, $eventType === 'PAYMENT.SALE.REVERSED', $payload, $subscription);
                return $subscription;
            default:
                return $service->loadSubscriptionFromDatabase($subscriptionId);
        }
    }

    /**
     * Sale resources (v1 payments) use {total, currency} instead of {value, currency_code}.
     *
     * @param  array<string,mixed>  $resource
     */
    private static function saleAmount(array $resource): ?Money
    {
        $amount = $resource['amount'] ?? null;
        $value = $amount['total'] ?? $amount['value'] ?? null;
        $currency = $amount['currency'] ?? $amount['currency_code'] ?? null;
        if ($value === null || $currency === null) {
            return null;
        }
        return new Money($currency, (string) $value);
    }

    /**
     * @param  array<string,mixed>  $resource
     * @param  array<string,mixed>  $payload
     */
    private function syncSubscription(PayPalSubscription $service, string $eventType, array $resource, array $payload): ?Subscription
    {
        if (($resource['id'] ?? null) === null) {
            return null;
        }
        $dto = PayPalSubscriptionDTO::fromArray($resource);
        // links of the webhook resource are API links, not the approval link we stored
        $dto->setLinks(null);
        // version of this state, used to ignore out-of-order deliveries
        $dto->setUpdateTime($dto->getUpdateTime() ?? $payload['create_time'] ?? null);
        $dto->setStatus($dto->getStatus() ?? match ($eventType) {
            'BILLING.SUBSCRIPTION.ACTIVATED', 'BILLING.SUBSCRIPTION.RE-ACTIVATED' => SubscriptionStatus::ACTIVE,
            'BILLING.SUBSCRIPTION.CANCELLED' => SubscriptionStatus::CANCELLED,
            'BILLING.SUBSCRIPTION.SUSPENDED' => SubscriptionStatus::SUSPENDED,
            'BILLING.SUBSCRIPTION.EXPIRED' => SubscriptionStatus::EXPIRED,
            default => null,
        });
        $saved = $service->saveSubscriptionToDatabase($dto);
        return $saved instanceof Subscription ? $saved : null;
    }
}
