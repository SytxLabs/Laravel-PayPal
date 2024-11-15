<?php

namespace SytxLabs\PayPal\Services;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
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
        $client = (new PendingRequest())->baseUrl($this->mode->getPayPalEnvironmentURL())->acceptJson();
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
                $this->log('Failed to get OAuth token');
                return $this;
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
        }
        return $this;
    }

    /**
     * @throws Exception
     */
    public function log(Exception|string $message, array $data = []): void
    {
        if (($this->config['logging']['enabled'] ?? false) === true) {
            $level = $this->config['logging']['level'];
            $channel = Log::channel($this->config['logging']['channel']);
            if (method_exists($channel, $level)) {
                $channel->$level($message, $data);
            } else {
                $channel->error('Invalid log level');
            }
        } else {
            throw $message instanceof Exception ? $message : new Exception($message);
        }
    }

    public function getClient(): ?PendingRequest
    {
        return $this->client;
    }
}
