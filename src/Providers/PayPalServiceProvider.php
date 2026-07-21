<?php

namespace SytxLabs\PayPal\Providers;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use SytxLabs\PayPal\Facades\PayPal;
use SytxLabs\PayPal\Facades\PayPalOrder;
use SytxLabs\PayPal\Services\PayPalSubscription;

class PayPalServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/paypal.php' => config_path('paypal.php'),
            ], 'sytxlabs-paypal-config');

            if (method_exists($this, 'publishesMigrations')) {
                $this->publishesMigrations([
                    __DIR__ . '/../../database/migrations' => database_path('migrations'),
                ], 'sytxlabs-paypal-migrations');
            } else {
                $this->publishes([
                    __DIR__ . '/../../database/migrations' => database_path('migrations'),
                ], 'sytxlabs-paypal-migrations');
            }
            AboutCommand::add('SytxLabs Laravel Paypal Package', static fn () => ['Version' => '1.0.0', 'Author' => 'SytxLabs']);
        }

        if ((bool) config('paypal.webhook.route_enabled', false) === true) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/webhooks.php');
        }
    }

    public function register(): void
    {
        $this->registerPayPal();

        $this->mergeConfigFrom(__DIR__ . '/../../config/paypal.php', 'paypal');
    }

    private function registerPayPal(): void
    {
        $this->app->singleton('paypal_client', static function () {
            return new PayPal();
        });
        $this->app->singleton('paypal_order_client', static function () {
            return new PayPalOrder();
        });
        $this->app->singleton('paypal_subscription_client', static function () {
            return new PayPalSubscription();
        });
    }
}
