<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AlertApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        $key     = $request->header('X-Alert-Api-Key');
        $liveKey = config('bcch.alert_api_key');
        $testKey = config('bcch.alert_api_key_test');

        if (!$key || ($key !== $liveKey && $key !== $testKey)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Test mode: only active when authenticated with the dedicated test key.
        // Optionally callers may also pass ?test=1 alongside the test key for clarity,
        // but the live key can never unlock test fixtures regardless of query params.
        $isTest = ($key === $testKey && $testKey !== '');
        $request->attributes->set('alert_api_test_mode', $isTest);

        return $next($request);
    }
}
