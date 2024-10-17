<?php

return [
    'oauth_database_connection' => env('PAYPAL_DB_CONNECTION'), // Database connection to use for storing OAuth tokens or null for default database connection or file storage
    'mode' => env('PAYPAL_MODE', 'sandbox'), // Can only be 'sandbox' Or 'live'. If empty or invalid, 'live' will be used.
    'sandbox' => [
        'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID', ''), // to get your credentials, see https://developer.paypal.com/docs/api/overview/#get-credentials
        'client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET', ''),
        'app_id' => 'APP-80W284485P519543T',
    ],
    'live' => [
        'client_id' => env('PAYPAL_LIVE_CLIENT_ID', ''),
        'client_secret' => env('PAYPAL_LIVE_CLIENT_SECRET', ''),
        'app_id' => env('PAYPAL_LIVE_APP_ID', ''),
    ],
    'retry' => [
        'enabled' => env('PAYPAL_RETRY_ENABLED'), // Enable failed request retry default null
        'attempts' => env('PAYPAL_RETRY_ATTEMPTS', 3),
    ],
    'timeout' => env('PAYPAL_TIMEOUT'),

    'currency' => env('PAYPAL_CURRENCY', 'USD'), // https://developer.paypal.com/api/rest/reference/currency-codes/ for full list of currency codes

    'success_route' => '', // Redirect route on successful payment as example route('sytxlabs.paypal.success')
    'cancel_route' => '', // Redirect route on canceled payment as example route('sytxlabs.paypal.cancel')

    'logging' => [
        'enabled' => env('PAYPAL_LOGGING_ENABLED', true), // Enable logging
        'channel' => env('PAYPAL_LOGGING_CHANNEL', config('logging.default', 'stack')), // Logging channel to use
        'level' => env('PAYPAL_LOGGING_LEVEL', 'info'), // Logging level
    ],
];
