<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayMongo API
    |--------------------------------------------------------------------------
    | Keys are managed via environment variables. Keep PAYMONGO_SECRET_KEY
    | server-side only; the hosted checkout (V2 checkout sessions) does not
    | require exposing the public key to the client.
    */

    'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com'),

    'secret_key' => env('PAYMONGO_SECRET_KEY', ''),

    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET', ''),

    /**
     * Payment methods offered on the hosted checkout page.
     * Must be enabled on the PayMongo account.
     */
    'payment_method_types' => ['qrph'],

];
