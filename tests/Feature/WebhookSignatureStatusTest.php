<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SytxLabs\PayPal\Enums\PayPalWebhookSignatureStatus;
use SytxLabs\PayPal\Services\PayPal;
use SytxLabs\PayPal\Services\PayPalOrder;
use SytxLabs\PayPal\Services\PayPalSubscription;

function sigHeaders(array $override = []): array
{
    return $override + [
        'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        'PAYPAL-CERT-URL' => 'https://api.sandbox.paypal.com/v1/notifications/certs/CERT-1',
        'PAYPAL-TRANSMISSION-ID' => 'tx-1',
        'PAYPAL-TRANSMISSION-SIG' => 'c2lnbmF0dXJl',
        'PAYPAL-TRANSMISSION-TIME' => '2026-10-07T10:00:00Z',
    ];
}

function sigFake(int|string $outcome = 'SUCCESS'): void
{
    $verify = is_int($outcome) ? Http::response(['name' => 'ERROR'], $outcome) : Http::response(['verification_status' => $outcome], 200);
    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'A-TOKEN', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's'], 200),
        '*/v1/notifications/verify-webhook-signature' => $verify,
    ]);
}

function sigVerifyCalls(): int
{
    return Http::recorded(static fn (Request $request) => str_ends_with($request->url(), '/v1/notifications/verify-webhook-signature'))->count();
}

it('tells a confirmed signature from a wrong one and from a PayPal that could not decide', function (int|string $outcome, PayPalWebhookSignatureStatus $expected) {
    sigFake($outcome);

    $status = (new PayPalOrder())->webhookSignatureStatus(sigHeaders(), '{"id":"WH-1","event_type":"CHECKOUT.ORDER.APPROVED"}');

    expect($status)->toBe($expected);
})->with([
    ['SUCCESS', PayPalWebhookSignatureStatus::Valid],
    ['FAILURE', PayPalWebhookSignatureStatus::Invalid],
    [500, PayPalWebhookSignatureStatus::Unavailable],
    [404, PayPalWebhookSignatureStatus::Unavailable],
    [400, PayPalWebhookSignatureStatus::Unavailable],
]);

it('is unavailable when PayPal cannot be reached', function () {
    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'A-TOKEN', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's'], 200),
        '*/v1/notifications/verify-webhook-signature' => static fn () => throw new ConnectionException('timeout'),
    ]);

    expect((new PayPalOrder())->webhookSignatureStatus(sigHeaders(), '{"id":"WH-1"}'))->toBe(PayPalWebhookSignatureStatus::Unavailable);
});

it('is unavailable when no token can be had', function () {
    Http::fake(['*/v1/oauth2/token' => Http::response(['error' => 'invalid_client'], 401)]);

    expect((new PayPalOrder())->webhookSignatureStatus(sigHeaders(), '{"id":"WH-1"}'))->toBe(PayPalWebhookSignatureStatus::Unavailable);
});

it('is available to every service', function () {
    sigFake();

    foreach ([new PayPal(), new PayPalOrder(), new PayPalSubscription()] as $service) {
        expect($service->webhookSignatureStatus(sigHeaders(), '{"id":"WH-1"}'))->toBe(PayPalWebhookSignatureStatus::Valid);
    }
});

it('hands PayPal the event exactly as it was received', function () {
    sigFake();
    // an empty object, a slash, an escaped umlaut, odd spacing and a number: all of it changes when the event is decoded and encoded again
    $raw = '{ "id":"WH-1", "event_type":"PAYMENT.CAPTURE.COMPLETED", "summary":"Käse / 100%", "resource":{"links":[],"supplementary_data":{},"amount":{"value":"10.00"},"weight":1.0} }';

    (new PayPalOrder())->webhookSignatureStatus(sigHeaders(), $raw);

    $body = Http::recorded(static fn (Request $request) => str_ends_with($request->url(), '/verify-webhook-signature'))->first()[0]->body();
    expect($body)->toContain('"webhook_event":' . $raw . '}');
    $sent = json_decode($body, true);
    expect($sent['webhook_id'])->toBe('WH-TEST')
        ->and($sent['auth_algo'])->toBe('SHA256withRSA')
        ->and($sent['cert_url'])->toBe('https://api.sandbox.paypal.com/v1/notifications/certs/CERT-1')
        ->and($sent['transmission_id'])->toBe('tx-1')
        ->and($sent['transmission_sig'])->toBe('c2lnbmF0dXJl')
        ->and($sent['transmission_time'])->toBe('2026-10-07T10:00:00Z')
        ->and($sent['webhook_event']['id'])->toBe('WH-1');
});

it('sends an event given as array encoded without escaping slashes', function () {
    sigFake();

    $status = (new PayPalOrder())->webhookSignatureStatus(sigHeaders(), ['id' => 'WH-1', 'resource' => ['href' => 'https://x/y', 'name' => 'Käse']]);

    $body = Http::recorded(static fn (Request $request) => str_ends_with($request->url(), '/verify-webhook-signature'))->first()[0]->body();
    expect($status)->toBe(PayPalWebhookSignatureStatus::Valid)->and($body)->toContain('"href":"https://x/y"')->and($body)->toContain('Käse');
});

it('reads the headers case-insensitively and accepts the array form of a request', function () {
    sigFake();
    $headers = ['paypal-auth-algo' => ['SHA256withRSA'], 'Paypal-Cert-Url' => ['https://api-m.paypal.com/v1/notifications/certs/CERT-9'], 'PAYPAL-TRANSMISSION-ID' => ['tx-1'], 'paypal-transmission-sig' => ['c2ln'], 'Paypal-Transmission-Time' => ['2026-10-07T10:00:00Z']];

    expect((new PayPalOrder())->webhookSignatureStatus($headers, '{"id":"WH-1"}'))->toBe(PayPalWebhookSignatureStatus::Valid);
});

it('rejects without asking PayPal what does not look like a PayPal webhook', function (array $override, array|string $body) {
    sigFake();

    $status = (new PayPalOrder())->webhookSignatureStatus(sigHeaders($override), $body);

    expect($status)->toBe(PayPalWebhookSignatureStatus::Invalid)->and(sigVerifyCalls())->toBe(0);
})->with([
    'other algorithm' => [['PAYPAL-AUTH-ALGO' => 'MD5withRSA'], '{"id":"WH-1"}'],
    'certificate from another host' => [['PAYPAL-CERT-URL' => 'https://evil.example/v1/notifications/certs/CERT-1'], '{"id":"WH-1"}'],
    'paypal as a subdomain of another host' => [['PAYPAL-CERT-URL' => 'https://api.sandbox.paypal.com.evil.example/certs'], '{"id":"WH-1"}'],
    'paypal in the user part' => [['PAYPAL-CERT-URL' => 'https://api.paypal.com@evil.example/certs'], '{"id":"WH-1"}'],
    'plain http' => [['PAYPAL-CERT-URL' => 'http://api.paypal.com/v1/notifications/certs/CERT-1'], '{"id":"WH-1"}'],
    'oversized signature' => [['PAYPAL-TRANSMISSION-SIG' => str_repeat('a', 2000)], '{"id":"WH-1"}'],
    'oversized transmission id' => [['PAYPAL-TRANSMISSION-ID' => str_repeat('a', 200)], '{"id":"WH-1"}'],
    'oversized time' => [['PAYPAL-TRANSMISSION-TIME' => str_repeat('1', 60)], '{"id":"WH-1"}'],
    'empty header' => [['PAYPAL-TRANSMISSION-SIG' => ''], '{"id":"WH-1"}'],
    'body is not JSON' => [[], 'not json'],
    'body is a JSON scalar' => [[], '"text"'],
    'empty body' => [[], ''],
]);

it('rejects a request that lacks a header without asking PayPal', function (string $missing) {
    sigFake();
    $headers = sigHeaders();
    unset($headers[$missing]);

    expect((new PayPalOrder())->webhookSignatureStatus($headers, '{"id":"WH-1"}'))->toBe(PayPalWebhookSignatureStatus::Invalid)
        ->and(sigVerifyCalls())->toBe(0);
})->with(['PAYPAL-AUTH-ALGO', 'PAYPAL-CERT-URL', 'PAYPAL-TRANSMISSION-ID', 'PAYPAL-TRANSMISSION-SIG', 'PAYPAL-TRANSMISSION-TIME']);

it('throws while no webhook id is configured, and asks nobody', function () {
    sigFake();
    config()->set('paypal.webhook_id', null);

    try {
        (new PayPalOrder(['webhook_id' => null]))->webhookSignatureStatus(sigHeaders(), '{"id":"WH-1"}');
        $this->fail('A missing webhook id should throw.');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('PayPal webhook_id is not configured')->and(sigVerifyCalls())->toBe(0);
    }
});

it('repeats the check once with a new token when PayPal turned the old one down', function () {
    Http::fake([
        '*/v1/oauth2/token' => Http::sequence()->push(['access_token' => 'OLD', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's'])->push(['access_token' => 'NEW', 'token_type' => 'Bearer', 'expires_in' => 3200, 'scope' => 's']),
        '*/v1/notifications/verify-webhook-signature' => Http::sequence()->push(['name' => 'AUTHENTICATION_FAILURE'], 401)->push(['verification_status' => 'SUCCESS'], 200),
    ]);

    $status = (new PayPalOrder())->webhookSignatureStatus(sigHeaders(), '{"id":"WH-1"}');

    expect($status)->toBe(PayPalWebhookSignatureStatus::Valid)->and(sigVerifyCalls())->toBe(2);
});

it('leaves the subscription check as it was', function () {
    sigFake('SUCCESS');

    expect((new PayPalSubscription())->verifyWebhookSignature(sigHeaders(), '{"id":"WH-1"}'))->toBeTrue();
});
