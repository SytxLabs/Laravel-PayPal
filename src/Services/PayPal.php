<?php

namespace SytxLabs\PayPal\Services;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SytxLabs\PayPal\Enums\PayPalWebhookSignatureStatus;
use SytxLabs\PayPal\Models\DTO\Money;
use SytxLabs\PayPal\Models\DTO\OAuthToken;
use SytxLabs\PayPal\Services\Traits\PayPalConfig;
use SytxLabs\PayPal\Services\Traits\PayPalOAuthSave;
use Throwable;

class PayPal
{
    use PayPalConfig;
    use PayPalOAuthSave;

    private ?PendingRequest $client = null;

    /** The authorised client as {@see self::build()} made it, before any service put headers or another base URL on {@see self::$client}. */
    private ?PendingRequest $apiClient = null;

    private function buildNewClient(): PendingRequest
    {
        // Resolve through the container's HTTP client factory when available so that
        // Http::fake() can intercept requests (e.g. in tests); fall back otherwise.
        $pendingRequest = (function_exists('app') && app()->bound(Factory::class))
            ? app(Factory::class)->baseUrl($this->mode->getPayPalEnvironmentURL())
            : (new PendingRequest())->baseUrl($this->mode->getPayPalEnvironmentURL());
        $client = $pendingRequest->acceptJson();
        if (($this->config['timeout'] ?? null) !== null) {
            $client->timeout($this->config['timeout']);
        }
        if (($this->config['retry']['enabled'] ?? false) === true) {
            $client->retry($this->config['retry']['attempts'] ?? 1, $this->config['retry']['delay'] ?? 100);
        }
        return $client;
    }

    /**
     * @throws Exception
     */
    public function build(): self
    {
        $client = $this->buildNewClient();
        try {
            $oAuthToken = $this->loadTokenFromDatabase();
            if ($oAuthToken !== null) {
                $this->client = $client->withToken($oAuthToken->getAccessToken(), $oAuthToken->getTokenType());
                $this->apiClient = clone $this->client;
                return $this;
            }
            $response = $client
                ->withBasicAuth($this->config['client_id'], $this->config['client_secret'])
                ->asForm()
                ->post('/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);
            if ($response->getStatusCode() !== 200) {
                $this->log('Failed to get OAuth token', ['response' => $response->body()]);
                throw new RuntimeException('Failed to get OAuth token');
            }

            $body = $response->json();
            $oAuthToken = new OAuthToken($body['access_token'], $body['token_type']);
            $oAuthToken->setExpiry(time() + $body['expires_in'])
                ->setExpiresIn($body['expires_in'])
                ->setRefreshToken($body['refresh_token'] ?? null)
                ->setIdToken($body['id_token'] ?? null)
                ->setScope($body['scope']);
            $this->saveOAuthToken($oAuthToken);

            $this->client = $this->buildNewClient()->withToken($oAuthToken->getAccessToken(), $oAuthToken->getTokenType())->asJson();
            $this->apiClient = clone $this->client;
        } catch (RequestException $e) {
            $this->client = null;
            $this->log($e);
            throw new RuntimeException('Failed to get OAuth token: ' . $e->getMessage(), 0, $e);
        }
        return $this;
    }

    /**
     * Log a message when logging is enabled. Never throws, so callers can raise their own typed exception afterwards.
     */
    public function log(Exception|string $message, array $data = []): void
    {
        if (($this->config['logging']['enabled'] ?? false) !== true) {
            return;
        }
        $level = $this->config['logging']['level'] ?? 'info';
        $channel = Log::channel($this->config['logging']['channel'] ?? null);
        if ($message instanceof Exception) {
            $data['exception'] = $message;
            $message = $message->getMessage();
        }
        if (method_exists($channel, $level)) {
            $channel->$level($message, $data);
        } else {
            $channel->error('Invalid log level');
        }
    }

    public function getClient(): ?PendingRequest
    {
        return $this->client;
    }

    /**
     * A client for any PayPal REST call, relative to the API host (`v2/payments/captures/…`), built on first use. Unlike
     * {@see self::getClient()} it is never null, and it is a copy: headers set on it (PayPal-Request-Id, Prefer) do not stick
     * to the next call.
     *
     * @throws RuntimeException when no token could be fetched
     */
    public function api(): PendingRequest
    {
        if ($this->apiClient === null) {
            $this->build();
        }
        if ($this->apiClient === null) {
            throw new RuntimeException('PayPal client not found');
        }
        return (clone $this->apiClient)->baseUrl($this->mode->getPayPalEnvironmentURL());
    }

    /**
     * Runs a call made with {@see self::api()}; when PayPal turns the token down (revoked, or switched between sandbox and live) the
     * stored token is dropped and the call is made once more with a new one.
     *
     * @param  callable(PendingRequest): Response  $call
     */
    protected function callApi(callable $call): Response
    {
        $response = $call($this->api());
        if ($response->status() === 401) {
            $this->forgetOAuthToken();
            $this->client = null;
            $this->apiClient = null;
            $response = $call($this->api());
        }
        return $response;
    }

    protected function newRequestId(): string
    {
        // PayPal-Request-Id: 1 to 108 characters
        return bin2hex(random_bytes(16));
    }

    /**
     * Refund a captured payment of an order by its capture id (full refund without amount, partial with amount).
     *
     * @param  string|null  $requestId  PayPal-Request-Id to reuse so a retried call is idempotent; a new one is generated when omitted
     *
     * @return array<string,mixed> the PayPal refund (id, status, amount, ...)
     *
     * @throws RuntimeException|ConnectionException when PayPal refuses
     */
    public function refundCapture(string $captureId, ?Money $amount = null, ?string $note = null, ?string $invoiceId = null, ?string $requestId = null): array
    {
        $body = array_filter(['amount' => $amount, 'note_to_payer' => $note, 'invoice_id' => $invoiceId], static fn ($value) => $value !== null);
        $requestId ??= $this->newRequestId();
        $apiResponse = $this->callApi(static function (PendingRequest $client) use ($captureId, $body, $requestId): Response {
            $request = $client->withHeader('PayPal-Request-Id', $requestId)->withHeader('Prefer', 'return=representation');
            $path = 'v2/payments/captures/' . rawurlencode($captureId) . '/refund';
            // PayPal wants an empty JSON object for a full refund
            return $body === [] ? $request->withBody('{}', 'application/json')->post($path) : $request->post($path, $body);
        });
        if (!in_array($apiResponse->getStatusCode(), [200, 201], true)) {
            $this->log('Failed to refund capture', ['response' => $apiResponse->body(), 'capture_id' => $captureId]);
            throw new RuntimeException('Failed to refund capture: ' . ($apiResponse->getReasonPhrase() ?: 'An error occurred'));
        }
        return $apiResponse->json() ?? [];
    }

    /**
     * Asks PayPal whether the signature of a webhook is valid and tells a wrong signature from a PayPal that could not be asked
     * (a wrong signature is answered 400, an outage or an unknown webhook id 503 so PayPal delivers again). Available to every
     * service, so an application that only uses orders can verify its webhooks too.
     *
     * Requests that do not look like PayPal's (other algorithm, certificate from another host, missing headers, no JSON) are
     * {@see PayPalWebhookSignatureStatus::Invalid} without asking PayPal at all. The event goes to PayPal exactly as received:
     * pass the raw request body, a decoded array has to be encoded again and may not match the bytes PayPal signed.
     *
     * @param  array<string,string|array<int,string>>  $headers  request headers (case-insensitive)
     * @param  array<string,mixed>|string  $body
     *
     * @throws RuntimeException when no webhook_id is configured
     */
    public function webhookSignatureStatus(array $headers, array|string $body): PayPalWebhookSignatureStatus
    {
        $webhookId = $this->config['webhook_id'] ?? null;
        if (empty($webhookId)) {
            throw new RuntimeException('PayPal webhook_id is not configured');
        }
        $lower = [];
        foreach ($headers as $key => $value) {
            $lower[strtolower((string) $key)] = (string) (is_array($value) ? ($value[0] ?? '') : $value);
        }
        $transmission = [];
        foreach (['auth_algo' => 'paypal-auth-algo', 'cert_url' => 'paypal-cert-url', 'transmission_id' => 'paypal-transmission-id', 'transmission_sig' => 'paypal-transmission-sig', 'transmission_time' => 'paypal-transmission-time'] as $field => $header) {
            if (($lower[$header] ?? '') === '') {
                return PayPalWebhookSignatureStatus::Invalid;
            }
            $transmission[$field] = $lower[$header];
        }
        // PayPal fetches the certificate itself: only its own hosts and its one algorithm are worth asking about
        if ($transmission['auth_algo'] !== 'SHA256withRSA'
            || preg_match('#^https://api(-m)?\.(sandbox\.)?paypal\.com/#', $transmission['cert_url']) !== 1
            || strlen($transmission['transmission_sig']) > 1024
            || strlen($transmission['transmission_id']) > 128
            || strlen($transmission['transmission_time']) > 40) {
            return PayPalWebhookSignatureStatus::Invalid;
        }
        $rawEvent = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        if (!is_string($rawEvent) || !is_array(json_decode($rawEvent, true))) {
            return PayPalWebhookSignatureStatus::Invalid;
        }
        $request = '{' . implode(',', array_map(static fn (string $field, string $value) => json_encode($field) . ':' . json_encode($value, JSON_UNESCAPED_SLASHES), array_keys($transmission), $transmission))
            . ',"webhook_id":' . json_encode((string) $webhookId) . ',"webhook_event":' . $rawEvent . '}';

        try {
            $apiResponse = $this->callApi(static fn (PendingRequest $client) => $client->withBody($request, 'application/json')->post('v1/notifications/verify-webhook-signature'));
        } catch (Throwable $e) {
            $this->log('Failed to verify webhook signature', ['exception' => $e->getMessage()]);
            return PayPalWebhookSignatureStatus::Unavailable;
        }
        if (!in_array($apiResponse->getStatusCode(), [200, 201], true)) {
            $this->log('Failed to verify webhook signature', ['response' => $apiResponse->body()]);
            return PayPalWebhookSignatureStatus::Unavailable;
        }
        return ($apiResponse->json()['verification_status'] ?? null) === 'SUCCESS' ? PayPalWebhookSignatureStatus::Valid : PayPalWebhookSignatureStatus::Invalid;
    }
}
