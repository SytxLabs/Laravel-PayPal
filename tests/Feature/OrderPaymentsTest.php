<?php

use SytxLabs\PayPal\Support\OrderPayments;

function orderPaymentsOrder(?array $units = null, string $status = 'COMPLETED'): array
{
    return [
        'id' => 'ORDER-1',
        'status' => $status,
        'purchase_units' => $units ?? [[
            'reference_id' => 'default',
            'amount' => ['currency_code' => 'EUR', 'value' => '49.90'],
            'payments' => ['captures' => [['id' => 'CAP-1', 'status' => 'COMPLETED', 'final_capture' => true, 'amount' => ['currency_code' => 'EUR', 'value' => '49.90']]]],
        ]],
    ];
}

it('adds decimal strings without float noise', function (array $values, string $expected) {
    expect(OrderPayments::sum($values))->toBe($expected);
})->with([
    [['0.10', '0.20'], '0.30'],
    [['49.90'], '49.90'],
    [['30.00', '19.90'], '49.90'],
    [['0.5', '0.25'], '0.75'],
    [['100', '0.01'], '100.01'],
    [['1000000.01', '2000000.02'], '3000000.03'],
    [['5', '7'], '12'],
    [['-0.50', '0.20'], '-0.30'],
    [['0.05', '-0.05'], '0.00'],
    [[], '0'],
]);

it('sums what the payer approved over all purchase units', function () {
    $order = orderPaymentsOrder([
        ['reference_id' => 'a', 'amount' => ['currency_code' => 'EUR', 'value' => '30.00']],
        ['reference_id' => 'b', 'amount' => ['currency_code' => 'EUR', 'value' => '19.90']],
    ], 'APPROVED');

    $approved = OrderPayments::approved($order);

    expect($approved->getValue())->toBe('49.90')->and($approved->getCurrencyCode())->toBe('EUR');
});

it('knows no approved amount for units without amount or with several currencies', function () {
    expect(OrderPayments::approved(['purchase_units' => [['reference_id' => 'a']]]))->toBeNull()
        ->and(OrderPayments::approved(['purchase_units' => []]))->toBeNull()
        ->and(OrderPayments::approved([]))->toBeNull()
        ->and(OrderPayments::approved(['purchase_units' => [
            ['amount' => ['currency_code' => 'EUR', 'value' => '1.00']],
            ['amount' => ['currency_code' => 'USD', 'value' => '1.00']],
        ]]))->toBeNull();
});

it('lists the captures of all units with the unit they belong to', function () {
    $order = orderPaymentsOrder([
        ['reference_id' => 'supplier_1', 'payments' => ['captures' => [['id' => 'C1', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'EUR', 'value' => '10.00']]]]],
        ['reference_id' => 'supplier_2', 'payments' => ['captures' => [
            ['id' => 'C2', 'status' => 'PENDING', 'amount' => ['currency_code' => 'EUR', 'value' => '5.00']],
            ['id' => 'C3', 'status' => 'COMPLETED', 'final_capture' => true, 'amount' => ['currency_code' => 'eur', 'value' => '2.50']],
        ]]],
    ]);

    $completed = OrderPayments::captures($order);
    $all = OrderPayments::captures($order, completedOnly: false);

    expect(array_column($completed, 'id'))->toBe(['C1', 'C3'])
        ->and(array_column($completed, 'reference_id'))->toBe(['supplier_1', 'supplier_2'])
        ->and($completed[1]['amount']->getCurrencyCode())->toBe('EUR')
        ->and($completed[1]['final_capture'])->toBeTrue()
        ->and(array_column($all, 'status'))->toBe(['COMPLETED', 'PENDING', 'COMPLETED']);
});

it('skips captures without id or amount', function () {
    $order = orderPaymentsOrder([['payments' => ['captures' => [['status' => 'COMPLETED'], ['id' => 'C1', 'status' => 'COMPLETED']]]]]);

    expect(OrderPayments::captures($order))->toBe([]);
});

it('counts as collected only completed captures of a completed order', function () {
    $collected = OrderPayments::collected(orderPaymentsOrder());

    expect($collected->getValue())->toBe('49.90')->and($collected->getCurrencyCode())->toBe('EUR');
});

it('collects the captures of several units', function () {
    $order = orderPaymentsOrder([
        ['payments' => ['captures' => [['id' => 'C1', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'EUR', 'value' => '30.00']]]]],
        ['payments' => ['captures' => [['id' => 'C2', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'EUR', 'value' => '19.90']]]]],
    ]);

    expect(OrderPayments::collected($order)->getValue())->toBe('49.90');
});

it('collects nothing while the order is not completed or the capture is pending', function () {
    expect(OrderPayments::collected(orderPaymentsOrder(status: 'APPROVED')))->toBeNull()
        ->and(OrderPayments::collected(orderPaymentsOrder(status: 'PAYER_ACTION_REQUIRED')))->toBeNull()
        ->and(OrderPayments::collected(orderPaymentsOrder([['payments' => ['captures' => [['id' => 'C1', 'status' => 'PENDING', 'amount' => ['currency_code' => 'EUR', 'value' => '1.00']]]]]])))->toBeNull()
        ->and(OrderPayments::collected(orderPaymentsOrder([['payments' => []]])))->toBeNull()
        ->and(OrderPayments::collected([]))->toBeNull();
});

it('collects nothing when the captures are in several currencies', function () {
    $order = orderPaymentsOrder([
        ['payments' => ['captures' => [['id' => 'C1', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'EUR', 'value' => '1.00']]]]],
        ['payments' => ['captures' => [['id' => 'C2', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'USD', 'value' => '1.00']]]]],
    ]);

    expect(OrderPayments::collected($order))->toBeNull();
});

it('finds the order of a capture through the related ids or the up link', function () {
    expect(OrderPayments::orderIdOf(['supplementary_data' => ['related_ids' => ['order_id' => 'ORDER-7']]]))->toBe('ORDER-7')
        ->and(OrderPayments::orderIdOf(['links' => [
            ['rel' => 'self', 'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/CAP-1'],
            ['rel' => 'up', 'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/ORDER-8'],
        ]]))->toBe('ORDER-8')
        ->and(OrderPayments::orderIdOf(['supplementary_data' => ['related_ids' => ['order_id' => 'ORDER-7']], 'links' => [['rel' => 'up', 'href' => 'https://api.paypal.com/v2/checkout/orders/OTHER']]]))->toBe('ORDER-7')
        ->and(OrderPayments::orderIdOf(['links' => [['rel' => 'up', 'href' => 'https://api.paypal.com/v2/payments/authorizations/AUTH-1']]]))->toBeNull()
        ->and(OrderPayments::orderIdOf([]))->toBeNull();
});
