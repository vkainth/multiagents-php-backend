<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAdminSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('app.admin_api_secret');

        if (empty($secret)) {
            return response()->json(['error' => 'Admin API not configured'], 503);
        }

        if ($request->header('X-Admin-Secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
