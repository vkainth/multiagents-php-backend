<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('agent')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('agent-portal.login')
                ->with('intended', $request->fullUrl());
        }

        $agent = Auth::guard('agent')->user();

        if (!$agent || !$agent->isActive()) {
            Auth::guard('agent')->logout();
            return redirect()->route('agent-portal.login')
                ->withErrors(['email' => 'Your account is suspended. Please contact support.']);
        }

        // Share variables used by agent-portal layout
        $agentSiteUrl = $this->resolveSiteUrl($request, $agent);

        View::share('portalAgent', $agent);
        View::share('agentSiteUrl', $agentSiteUrl);

        return $next($request);
    }

    protected function resolveSiteUrl(Request $request, $agent): string
    {
        // In domain mode the agent's site is the same host at '/'
        $mode = config('bcch.agent_routing_mode', env('AGENT_ROUTING_MODE', 'path'));

        if ($mode === 'domain') {
            $scheme = $request->getScheme();
            $host   = $request->getHost();
            return "{$scheme}://{$host}/";
        }

        // Path mode: /agent/{slug}/
        return url("/agent/{$agent->slug}/");
    }
}
