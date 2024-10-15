<?php

namespace SytxLabs\PayPal\Services;

use PaypalServerSDKLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSDKLib\Authentication\ClientCredentialsAuthManager;
use PaypalServerSDKLib\Logging\LoggingConfigurationBuilder;
use PaypalServerSDKLib\Logging\RequestLoggingConfigurationBuilder;
use PaypalServerSDKLib\Logging\ResponseLoggingConfigurationBuilder;
use PaypalServerSDKLib\Models\OAuthToken;
use PaypalServerSDKLib\PaypalServerSDKClient;
use PaypalServerSDKLib\PaypalServerSDKClientBuilder;
use Psr\Log\LogLevel;
use SytxLabs\PayPal\Services\Traits\PayPalConfig;
use SytxLabs\PayPal\Services\Traits\PayPalOAuthSave;

class PayPal
{
    use PayPalConfig;
    use PayPalOAuthSave;

    private ?PaypalServerSDKClient $client;

    public function build(): self
    {
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
            ->environment($this->mode->getPayPalEnvironment())
            ->loggingConfiguration(
                LoggingConfigurationBuilder::init()
                    ->level(LogLevel::INFO)
                    ->requestConfiguration(RequestLoggingConfigurationBuilder::init()->body(true))
                    ->responseConfiguration(ResponseLoggingConfigurationBuilder::init()->headers(true))
            );
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
}
