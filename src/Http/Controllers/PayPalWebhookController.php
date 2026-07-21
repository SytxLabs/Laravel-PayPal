<?php

namespace SytxLabs\PayPal\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SytxLabs\PayPal\Enums\DTO\Subscription\SubscriptionStatus;
use SytxLabs\PayPal\Events\PayPalWebhookReceived;
use SytxLabs\PayPal\Models\DTO\Subscription\Subscription as PayPalSubscriptionDTO;
use SytxLabs\PayPal\Services\PayPalSubscription;

class PayPalWebhookController
{
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

        $this->syncSubscription($service, $eventType, $payload['resource'] ?? []);

        PayPalWebhookReceived::dispatch($eventType, $payload);

        return response()->json(['status' => 'ok']);
    }

    /**
     * @param  array<string,mixed>  $resource
     */
    private function syncSubscription(PayPalSubscription $service, string $eventType, array $resource): void
    {
        $subscriptionId = $resource['id'] ?? null;
        if ($subscriptionId === null || !str_starts_with($eventType, 'BILLING.SUBSCRIPTION.')) {
            return;
        }
        $status = SubscriptionStatus::tryFrom($resource['status'] ?? '') ?? match ($eventType) {
            'BILLING.SUBSCRIPTION.ACTIVATED' => SubscriptionStatus::ACTIVE,
            'BILLING.SUBSCRIPTION.CANCELLED' => SubscriptionStatus::CANCELLED,
            'BILLING.SUBSCRIPTION.SUSPENDED' => SubscriptionStatus::SUSPENDED,
            'BILLING.SUBSCRIPTION.EXPIRED' => SubscriptionStatus::EXPIRED,
            default => null,
        };
        $dto = (new PayPalSubscriptionDTO())
            ->setId($subscriptionId)
            ->setPlanId($resource['plan_id'] ?? null)
            ->setCustomId($resource['custom_id'] ?? null)
            ->setStatus($status);
        $service->saveSubscriptionToDatabase($dto);
    }
}
