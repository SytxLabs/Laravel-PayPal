<?php

use Illuminate\Support\Facades\Route;
use SytxLabs\PayPal\Http\Controllers\PayPalWebhookController;

Route::post(config('paypal.webhook.path', 'paypal/webhook'), PayPalWebhookController::class)
    ->name('sytxlabs.paypal.webhook');
