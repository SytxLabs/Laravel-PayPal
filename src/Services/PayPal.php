<?php

namespace SytxLabs\PayPal\Services;

use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthManager;
use PaypalServerSdkLib\Logging\LoggingConfigurationBuilder;
use PaypalServerSdkLib\Logging\RequestLoggingConfigurationBuilder;
use PaypalServerSdkLib\Logging\ResponseLoggingConfigurationBuilder;
use PaypalServerSdkLib\Models\OAuthToken;
use PaypalServerSdkLib\PaypalServerSDKClient;
use PaypalServerSdkLib\PaypalServerSDKClientBuilder;
use Psr\Log\LogLevel;
use SytxLabs\PayPal\Services\Traits\PayPalConfig;
use SytxLabs\PayPal\Services\Traits\PayPalOAuthSave;

class PayPal
{
    use PayPalConfig;
    use PayPalOAuthSave;

    private ?PaypalServerSDKClient $client = null;

    public function build(): self
    {
        $loggingConfig = null;

        if (isset($this->config['logging']['enabled']) && ($this->config['logging']['enabled'] ?? false)) {
            $loggingConfig =
                LoggingConfigurationBuilder::init()
                    ->level($this->config['logging']['level'] ?? LogLevel::INFO)
                    ->requestConfiguration(RequestLoggingConfigurationBuilder::init()->body(true))
                    ->responseConfiguration(ResponseLoggingConfigurationBuilder::init()->headers(true));
        }

        $client = PaypalServerSDKClientBuilder::init()
            ->clientCredentialsAuthCredentials(
                ClientCredentialsAuthCredentialsBuilder::init(
                    $this->config['client_id'],
                    $this->config['client_secret']
                )->oAuthTokenProvider(
                    function (?OAuthToken $lastOAuthToken, ClientCredentialsAuthManager $authManager): OAuthToken {
                        return $this->loadTokenFromDatabase() ?? $authManager->fetchToken();
                    }
                )->oAuthOnTokenUpdate(function (OAuthToken $oAuthToken) {
                    $this->saveOAuthToken($oAuthToken);
                })
            )
            ->environment($this->mode->getPayPalEnvironment());
        if ($loggingConfig !== null) {
            $client->loggingConfiguration($loggingConfig);
        }
        if (isset($this->config['timeout']) && is_numeric($this->config['timeout'])) {
            $client->timeout($this->config['timeout']);
        }
        if (isset($this->config['retry']['enabled']) && is_bool($this->config['retry']['enabled'])) {
            $client->enableRetries($this->config['retry']['enabled']);
            $client->numberOfRetries($this->config['retry']['attempts'] ?? 3);
        }

        $this->client = $client->build();
        return $this;
    }

    public function getClient(): ?PaypalServerSDKClient
    {
        return $this->client;
    }
}
