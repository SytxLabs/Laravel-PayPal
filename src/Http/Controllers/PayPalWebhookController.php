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

        // PayPal re-delivers events it considers undelivered; handle every event id once.
        if (!$service->claimWebhookEvent($eventId, $eventType, $resource['id'] ?? null)) {
            return response()->json(['status' => 'duplicate']);
        }

        try {
            $subscription = $this->handle($service, $eventType, $resource, $payload);
            PayPalWebhookReceived::dispatch($eventType, $payload, $subscription);
        } catch (Throwable $e) {
            // let PayPal retry the event later
            $service->releaseWebhookEvent($eventId);
            throw $e;
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * @param  array<string,mixed>  $resource
     * @param  array<string,mixed>  $payload
     */
    private function handle(PayPalSubscription $service, string $eventType, array $resource, array $payload): ?Subscription
    {
        if (str_starts_with($eventType, 'BILLING.SUBSCRIPTION.')) {
            $subscription = $this->syncSubscription($service, $eventType, $resource);
            if ($eventType === 'BILLING.SUBSCRIPTION.PAYMENT.FAILED' && isset($resource['id'])) {
                $failedCount = $resource['billing_info']['failed_payments_count'] ?? null;
                $subscription = $service->recordFailedSubscriptionPayment($resource['id'], $failedCount) ?? $subscription;
                PayPalSubscriptionPaymentFailed::dispatch($resource['id'], $subscription?->failed_payments_count ?? $failedCount, $payload, $subscription);
            }
            return $subscription;
        }

        $subscriptionId = $resource['billing_agreement_id'] ?? null;
        if ($subscriptionId === null) {
            return null;
        }
        $amount = self::saleAmount($resource);
        return match ($eventType) {
            'PAYMENT.SALE.COMPLETED' => $this->paymentCompleted($service, $subscriptionId, $amount, $resource, $payload),
            'PAYMENT.SALE.REFUNDED', 'PAYMENT.SALE.REVERSED' => $this->paymentRefunded($service, $subscriptionId, $amount, $eventType === 'PAYMENT.SALE.REVERSED', $resource, $payload),
            default => $service->loadSubscriptionFromDatabase($subscriptionId),
        };
    }

    /**
     * @param  array<string,mixed>  $resource
     * @param  array<string,mixed>  $payload
     */
    private function paymentCompleted(PayPalSubscription $service, string $subscriptionId, ?Money $amount, array $resource, array $payload): ?Subscription
    {
        $subscription = $service->recordSubscriptionPayment($subscriptionId, $amount, $resource['create_time'] ?? null);
        PayPalSubscriptionPaymentCompleted::dispatch($subscriptionId, $resource['id'] ?? null, $amount, $payload, $subscription);
        return $subscription;
    }

    /**
     * @param  array<string,mixed>  $resource
     * @param  array<string,mixed>  $payload
     */
    private function paymentRefunded(PayPalSubscription $service, string $subscriptionId, ?Money $amount, bool $reversed, array $resource, array $payload): ?Subscription
    {
        $subscription = $service->loadSubscriptionFromDatabase($subscriptionId);
        PayPalSubscriptionPaymentRefunded::dispatch($subscriptionId, $resource['sale_id'] ?? $resource['id'] ?? null, $amount, $reversed, $payload, $subscription);
        return $subscription;
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
     */
    private function syncSubscription(PayPalSubscription $service, string $eventType, array $resource): ?Subscription
    {
        if (($resource['id'] ?? null) === null) {
            return null;
        }
        $dto = PayPalSubscriptionDTO::fromArray($resource);
        // links of the webhook resource are API links, not the approval link we stored
        $dto->setLinks(null);
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
