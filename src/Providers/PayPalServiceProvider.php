<?php

namespace SytxLabs\PayPal\Providers;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use SytxLabs\PayPal\Services\PayPal;

class PayPalServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/paypal.php' => config_path('paypal.php'),
            ], 'paypal');

            $this->publishesMigrations([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'paypal-migrations');

            AboutCommand::add('SytxLabs Laravel Paypal Package', static fn () => ['Version' => '1.0.0', 'Author' => 'SytxLabs']);
        }
    }

    public function register(): void
    {
        $this->registerPayPal();

        $this->mergeConfigFrom(__DIR__ . '/../config/paypal.php', 'paypal');
    }

    private function registerPayPal(): void
    {
        $this->app->singleton('paypal_client', static function () {
            return new PayPal();
        });
    }
}
