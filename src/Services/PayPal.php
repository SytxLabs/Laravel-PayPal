<?php

namespace SytxLabs\PayPal\Services;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SytxLabs\PayPal\Models\DTO\OAuthToken;
use SytxLabs\PayPal\Services\Traits\PayPalConfig;
use SytxLabs\PayPal\Services\Traits\PayPalOAuthSave;

class PayPal
{
    use PayPalConfig;
    use PayPalOAuthSave;

    private ?PendingRequest $client = null;

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
}
