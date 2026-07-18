<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Captured at config:cache time — env() returns null in a cached-config
    // deploy, which would silently disable webhook signature verification.
    'stripe' => [
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],


    // Runtime fallback SMS gateway for OTP delivery (used only when no admin
    // SMS gateway is configured). Read via config() so it works under
    // `php artisan config:cache` — env() is null there.
    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM_NUMBER'),
    ],

    // Test-only predictable OTP for the phone-login path. When 'phone' is a
    // non-empty number, that ONE number always receives the fixed 'code'
    // (no SMS is sent), so the owner can log into the customer app while
    // testing without a live SMS gateway. Every other number keeps the secure
    // random OTP. Read via config() so it survives `php artisan config:cache`.
    //
    // Fail-closed in production: under APP_ENV=production the feature is OFF
    // regardless of 'phone' unless 'allow_production' is explicitly true
    // (VITO_TEST_OTP_ALLOW_PRODUCTION=true). Unset that flag before a public
    // launch. Outside production, disable with VITO_TEST_OTP_PHONE= (empty).
    'vito_test_otp' => [
        'phone'            => env('VITO_TEST_OTP_PHONE', '+18885550000'),
        'code'             => env('VITO_TEST_OTP_CODE', '123456'),
        'allow_production' => env('VITO_TEST_OTP_ALLOW_PRODUCTION', false),
    ],

];
