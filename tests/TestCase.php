<?php

namespace SytxLabs\PayPal\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SytxLabs\PayPal\Providers\PayPalServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PayPalServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('paypal', [
            'mode' => 'sandbox',
            'sandbox' => [
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
                'app_id' => 'APP-80W284485P519543T',
            ],
            'live' => [
                'client_id' => 'live-client-id',
                'client_secret' => 'live-client-secret',
                'app_id' => '',
            ],
            'currency' => 'EUR',
            'success_route' => 'https://example.com/success',
            'cancel_route' => 'https://example.com/cancel',
            'webhook_id' => 'WH-TEST',
            'webhook' => [
                'route_enabled' => true,
                'path' => 'paypal/webhook',
            ],
            'logging' => [
                'enabled' => false,
                'channel' => 'null',
                'level' => 'info',
            ],
            'database' => [
                'enabled' => true,
                'connection' => null,
                'oauth_table' => 'sytxlabs_paypal_oauth_tokens',
                'order_table' => 'sytxlabs_paypal_orders',
                'subscription_table' => 'sytxlabs_paypal_subscriptions',
            ],
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
