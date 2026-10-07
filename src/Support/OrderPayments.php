<?php

namespace SytxLabs\PayPal\Support;

use SytxLabs\PayPal\Models\DTO\Money;

/**
 * Reads what an order of the Orders API (`GET /v2/checkout/orders/{id}`, as array) says about money: what the payer approved, what PayPal really collected, and which order a capture belongs to. Works on the array PayPal sent, so nothing depends on how much of it the DTOs map.
 */
final class OrderPayments
{
    /**
     * What the payer approved: the sum of the amounts of all purchase units.
     *
     * @param  array<string,mixed>  $order
     * @return Money|null null when a unit names no amount or the units use several currencies
     */
    public static function approved(array $order): ?Money
    {
        $values = [];
        $currency = null;
        foreach ($order['purchase_units'] ?? [] as $unit) {
            $amount = $unit['amount'] ?? null;
            if (!isset($amount['value'], $amount['currency_code'])) {
                return null;
            }
            $code = strtoupper((string) $amount['currency_code']);
            if ($currency !== null && $currency !== $code) {
                return null;
            }
            $currency = $code;
            $values[] = (string) $amount['value'];
        }
        return $currency === null ? null : new Money($currency, self::sum($values));
    }

    /**
     * The captures of all purchase units.
     *
     * @param  array<string,mixed>  $order
     * @return list<array{id: string, status: string, reference_id: ?string, amount: Money, final_capture: bool}>
     */
    public static function captures(array $order, bool $completedOnly = true): array
    {
        $captures = [];
        foreach ($order['purchase_units'] ?? [] as $unit) {
            foreach ($unit['payments']['captures'] ?? [] as $capture) {
                if (!isset($capture['id'], $capture['amount']['value'], $capture['amount']['currency_code'])) {
                    continue;
                }
                $status = (string) ($capture['status'] ?? '');
                if ($completedOnly && $status !== 'COMPLETED') {
                    continue;
                }
                $captures[] = [
                    'id' => (string) $capture['id'],
                    'status' => $status,
                    'reference_id' => isset($unit['reference_id']) ? (string) $unit['reference_id'] : null,
                    'amount' => new Money(strtoupper((string) $capture['amount']['currency_code']), (string) $capture['amount']['value']),
                    'final_capture' => (bool) ($capture['final_capture'] ?? false),
                ];
            }
        }
        return $captures;
    }

    /**
     * What PayPal collected. Only an order that is COMPLETED counts, and only its COMPLETED captures - a pending capture is not money yet.
     *
     * @param  array<string,mixed>  $order
     * @return Money|null null when nothing is collected (yet) or the captures use several currencies
     */
    public static function collected(array $order): ?Money
    {
        if (($order['status'] ?? null) !== 'COMPLETED') {
            return null;
        }
        $captures = self::captures($order);
        if ($captures === []) {
            return null;
        }
        $currency = $captures[0]['amount']->getCurrencyCode();
        foreach ($captures as $capture) {
            if ($capture['amount']->getCurrencyCode() !== $currency) {
                return null;
            }
        }
        return new Money($currency, self::sum(array_map(static fn (array $capture) => $capture['amount']->getValue(), $captures)));
    }

    /**
     * The order a capture, refund or authorization resource of a webhook belongs to: `supplementary_data.related_ids.order_id`, otherwise the `up` link of a capture (`…/v2/checkout/orders/{id}`).
     *
     * @param  array<string,mixed>  $resource
     */
    public static function orderIdOf(array $resource): ?string
    {
        $id = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
        if (is_string($id) && $id !== '') {
            return $id;
        }
        foreach ($resource['links'] ?? [] as $link) {
            if (($link['rel'] ?? null) === 'up' && preg_match('#/v2/checkout/orders/([A-Za-z0-9-]+)$#', (string) ($link['href'] ?? ''), $matches) === 1) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Adds decimal strings without going through floats ("0.10" + "0.20" = "0.30"), keeping the longest number of decimals.
     *
     * @param  list<string>  $values
     */
    public static function sum(array $values): string
    {
        $scale = 0;
        foreach ($values as $value) {
            $scale = max($scale, strlen(explode('.', $value)[1] ?? ''));
        }
        $total = 0;
        foreach ($values as $value) {
            [$whole, $fraction] = array_pad(explode('.', ltrim($value, '+'), 2), 2, '');
            $negative = str_starts_with($whole, '-');
            $units = (int) ltrim($whole, '-') * (10 ** $scale) + (int) str_pad($fraction, $scale, '0');
            $total += $negative ? -$units : $units;
        }
        $negative = $total < 0;
        $digits = str_pad((string) abs($total), $scale + 1, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '') . ($scale === 0 ? $digits : substr($digits, 0, -$scale) . '.' . substr($digits, -$scale));
    }
}
