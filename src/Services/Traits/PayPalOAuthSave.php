<?php

namespace SytxLabs\PayPal\Services\Traits;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PaypalServerSDKLib\Models\OAuthToken;

trait PayPalOAuthSave
{
    use PayPalConfig;

    protected function getConnection(): Connection
    {
        return DB::connection($this->config['oauth_database_connection'] ?? null);
    }

    protected function tableExists(): bool
    {
        return $this->getConnection()->getSchemaBuilder()->hasTable('sytxlabs_paypal_oauth_tokens');
    }

    protected function table(): Builder
    {
        return $this->getConnection()->table('sytxlabs_paypal_oauth_tokens');
    }

    public function saveOAuthToken(OAuthToken $token): void
    {
        if (!$this->tableExists()) {
            return;
        }
        if ($this->table()->count() > 0) {
            $this->table()->delete();
        }
        $this->table()->insert([
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
        if (!$this->tableExists()) {
            return null;
        }
        $token = $this->table()->first();
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