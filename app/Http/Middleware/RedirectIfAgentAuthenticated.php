<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAgentAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('agent')->check()) {
            return redirect()->route('agent-portal.dashboard');
        }

        return $next($request);
    }
}
