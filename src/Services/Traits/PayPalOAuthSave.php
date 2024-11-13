<?php

namespace SytxLabs\PayPal\Services\Traits;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PaypalServerSDKLib\Models\OAuthToken;

trait PayPalOAuthSave
{
    use PayPalConfig;

    protected function getConnection(): ?Connection
    {
        if (($this->config['database']['enabled'] ?? false) !== true || !app()->bound('db')) {
            return null;
        }
        return DB::connection($this->config['database']['connection'] ?? null);
    }

    protected function oAuthTableName(): string
    {
        return $this->config['database']['oauth_table'] ?? 'sytxlabs_paypal_oauth_tokens';
    }

    protected function oAuthTableExists(): bool
    {
        return $this->getConnection()?->getSchemaBuilder()->hasTable($this->oAuthTableName()) ?? false;
    }

    protected function oAuthTable(): ?Builder
    {
        return $this->getConnection()?->table($this->oAuthTableName());
    }

    public function saveOAuthToken(OAuthToken $token): void
    {
        if (!$this->oAuthTableExists() || $this->oAuthTable() === null) {
            return;
        }
        if ($this->oAuthTable()->count() > 0) {
            $this->oAuthTable()->delete();
        }
        $this->oAuthTable()->insert([
            'access_token' => $token->getAccessToken(),
            'token_type' => $token->getTokenType(),
            'expiry' => $token->getExpiry(),
            'scope' => $token->getScope(),
            'refresh_token' => $token->getRefreshToken(),
            'id_token' => $token->getIdToken(),
        ]);
    }

    public function loadTokenFromDatabase(): ?OAuthToken
    {
        if (!$this->oAuthTableExists() || $this->oAuthTable() === null) {
            return null;
        }
        $token = $this->oAuthTable()->first();
        if ($token === null) {
            return null;
        }
        $oAuth = new OAuthToken($token->access_token, $token->token_type);
        $oAuth->setExpiry($token->expiry);
        $oAuth->setScope($token->scope);
        $oAuth->setRefreshToken($token->refresh_token);
        $oAuth->setIdToken($token->id_token);
        if ($oAuth->getExpiry() < time()) {
            return null;
        }
        return $oAuth;
    }
}
