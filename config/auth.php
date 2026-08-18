<?php

return [

    'defaults' => [
        'guard'     => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        // -------- as BCCH-v1 [BEGINS] -----------
        'web' => [
            'driver'   => 'session',
            'provider' => 'firebase',
        ],

        'api' => [
            'driver'   => 'token',
            'provider' => 'users',
        ],

        'firebase' => [
            'driver'   => 'session',
            'provider' => 'firebase',
        ],

        // Agent portal guard — separate session from public site
        'agent' => [
            'driver'   => 'session',
            'provider' => 'agents',
        ],

        // Staff admin guard — separate session from agent + public
        'admin' => [
            'driver'   => 'session',
            'provider' => 'admins',
        ],
        // -------- as BCCH-v1 [ENDS] -----------
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', App\Models\User::class),
        ],

        'firebase' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_FIREBASE_MODEL', App\Models\Auth\FirebaseUser::class),
        ],

        'agents' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Agent::class,
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Admin::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],

        'agents' => [
            'provider' => 'agents',
            'table'    => 'agent_password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
