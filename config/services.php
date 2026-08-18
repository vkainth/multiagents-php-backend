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
    // Also from: BCCH-v1:
    as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    
    
    // ----- as from BCCH-v1 [BEGINS] ---------
    
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],
    
    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],
    
    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],
    
    'stripe' => [
        'model' => env('AUTH_FIREBASE_MODEL', App\Models\Auth\FirebaseUser::class),
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    // ----- as from BCCH-v1 [ENDS] ---------

    'followupboss' => [
        'api_key' => env('FUB_API_KEY'),
    ],

    'google' => [
        'api_key'       => env('GOOGLE_API_KEY'),
        'place_id'      => env('GOOGLE_PLACE_ID'),
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', 'https://website.pixilink.com/api-internal/auth/google/callback'),
    ],

    'apple' => [
        'client_id'        => env('APPLE_CLIENT_ID'),
        'team_id'          => env('APPLE_TEAM_ID'),
        'key_id'           => env('APPLE_KEY_ID'),
        'private_key_path' => env('APPLE_PRIVATE_KEY_PATH', '/home/websitemanager/bcchv2/storage/apple_auth_key.p8'),
        'redirect'         => env('APPLE_REDIRECT_URI', 'https://website.pixilink.com/api-internal/auth/apple/callback'),
    ],

    'google_places' => [
        'api_key' => env('GOOGLE_PLACES_API_KEY'),
    ],


    'twilio' => [
        'sid'        => env('TWILIO_SID'),
        'token'      => env('TWILIO_TOKEN'),
        'verify_sid' => env('TWILIO_VERIFY_SID'),
    ],

    'walkscore' => [
        'api_key' => env('WALKSCORE_API_KEY'),
    ],

];
