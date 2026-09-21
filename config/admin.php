<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legacy Blade admin
    |--------------------------------------------------------------------------
    |
    | The Laravel/Blade admin at website.pixilink.com/admin is RETIRED. The Next.js
    | admin served on each agent domain is the real one.
    |
    | Why it was switched off rather than left running: both authenticate the SAME
    | admins table, so it was a second front door to the same account — a second login
    | to keep hardened, a second place for a permission bug to hide, and duplicated
    | screens (agents, leads, analytics, billing, feature flags, invoices) certain to
    | drift apart over time.
    |
    | Disabling is done by not registering the routes at all, so every /admin/* path on
    | that host returns 404 and no controller is reachable. The api-internal/admin/*
    | endpoints are NOT affected — those are the JSON API the Next.js admin depends on,
    | and they are guarded separately by VerifyAdminSecret.
    |
    | Set LEGACY_ADMIN_ENABLED=true to bring it back temporarily; the code is untouched.
    |
    */
    'legacy_enabled' => env('LEGACY_ADMIN_ENABLED', false),

];
