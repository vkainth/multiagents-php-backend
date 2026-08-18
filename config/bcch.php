<?php

return [

    'buildings' => [
        'bcn_info_api_url' => env('BCN_BLDG_INFO_API_URL','https://www.bccondosandhomes.com/api_building/public/'),
        'bcn_info_sync_period' => env('BCN_BLDG_INFO_SYNC_PERIOD','10 days'),
    ],

    'contact' => [
        'phone' => env('BCCH_CONTACT_PHONE', '604.265.7975'),
        'email' => env('BCCH_CONTACT_EMAIL','info@bccondosandhomes.com'),
        'address' => [
            'line1'=>'Re/Max Crest Realty',
            'line2'=>'300 - 1195 W Broadway, Vancouver, BC V6H 3X5',
        ],
    ],

    'misc' => [
        'log_max_stacks_count' => env('LOG_MAX_STACKS_COUNT',4),
    ],

    'alert_api_key'      => env('ALERT_API_KEY'),
    'alert_api_key_test' => env('ALERT_API_KEY_TEST', ''),
    'alert_webhook_url'  => env('ALERT_WEBHOOK_URL', 'https://admin.bccondosandhomes.com/webhooks/bcch-alerts'),
    'alert_webhook_secret' => env('ALERT_WEBHOOK_SECRET', ''),

];
