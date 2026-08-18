<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use App\Models\Agent;
use App\Models\AgentSettings;
use App\Helpers\AgentContext;
use Symfony\Component\HttpFoundation\Response;

class ResolveAgent
{
    /**
     * Resolve which agent "owns" this request and bind it to AgentContext.
     *
     * Runs as a global web middleware on every request.  When the same request
     * also passes through the /agent/{agentSlug} route group the guard at the
     * top short-circuits so resolution only happens once.
     *
     * Priority:
     *  1. Production mode (AGENT_ROUTING_MODE=domain): match HTTP Host against
     *     agent_settings.custom_domain (cached 10 min per domain).
     *  2. Path-prefix fallback (dev/staging): {agentSlug} route parameter.
     *
     * When no agent is detected the site behaves exactly as before —
     * AgentContext::current() returns null and no Blade variables are added.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Guard: skip if a previous middleware invocation already resolved an agent.
        if (AgentContext::hasAgent()) {
            return $next($request);
        }

        $agent = null;

        $mode = config('bcch.agent_routing_mode', env('AGENT_ROUTING_MODE', 'path'));

        if ($mode === 'domain') {
            $agent = $this->resolveByDomain($request->getHost());
        }

        if ($agent === null) {
            $agent = $this->resolveByPathSlug($request);
        }

        if ($agent !== null) {
            AgentContext::set($agent);
            $request->attributes->set('agent', $agent);

            View::share('agent', $agent);
            View::share('agentTheme', $agent->theme_slug);
            View::share('agentThemeColor', $agent->theme_color);
        }

        // Check for suspended agent accessed via custom domain — return 403.
        if ($agent === null && $mode === 'domain') {
            $suspendedId = $this->findSuspendedByDomain($request->getHost());
            if ($suspendedId) {
                return response()->view('errors.agent-suspended', [], 403);
            }
        }

        return $next($request);
    }

    protected function findSuspendedByDomain(string $host): bool
    {
        return \App\Models\AgentSettings::where('custom_domain', strtolower(trim($host)))
            ->whereHas('agent', fn ($q) => $q->where('status', 'suspended'))
            ->exists();
    }

    protected function resolveByDomain(string $host): ?Agent
    {
        $cacheKey = 'agent_domain_' . md5(strtolower(trim($host)));

        $agentId = Cache::remember($cacheKey, 600, function () use ($host) {
            $settings = AgentSettings::where('custom_domain', strtolower(trim($host)))->first();
            return $settings?->agent_id;
        });

        if (!$agentId) {
            return null;
        }

        return Agent::where('id', $agentId)->where('status', 'active')->first();
    }

    protected function resolveByPathSlug(Request $request): ?Agent
    {
        $slug = $request->route('agentSlug');
        if (empty($slug)) {
            return null;
        }

        return Agent::where('slug', $slug)->where('status', 'active')->first();
    }
}
