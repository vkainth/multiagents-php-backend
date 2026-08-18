<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gatekeeper for the /test surface and the dev route includes
 * (routes/dev/tester.php, prod_quick_devs.php, manage_team_agents.php).
 *
 * Access is granted on ANY of:
 *   1. a valid X-Admin-Secret header (same secret as VerifyAdminSecret), or
 *   2. a REMOTE_ADDR listed in bcch.dev.allowed_ips, or
 *   3. a session authenticated as a dev-dj-approve user (browser workflow).
 *
 * Otherwise 404 -- not 403 -- so the dev surface stops advertising itself.
 */
class RestrictDevRoutes
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->hasAdminSecret($request) || $this->isAllowedIp($request) || Gate::allows('dev-dj-approve')) {
            return $next($request);
        }

        abort(404);
    }

    private function hasAdminSecret(Request $request): bool
    {
        $secret = (string) config('app.admin_api_secret', '');
        $given  = (string) $request->header('X-Admin-Secret', '');

        return $secret !== '' && $given !== '' && hash_equals($secret, $given);
    }

    private function isAllowedIp(Request $request): bool
    {
        $allowed = array_filter((array) config('bcch.dev.allowed_ips', []));

        if (! $allowed) {
            return false;
        }

        // Deliberately REMOTE_ADDR, not $request->ip(): TrustProxies sets
        // $proxies = '*', so X-Forwarded-For is caller-controlled and cannot
        // be used for an allowlist until that is narrowed to the real hops.
        $ip = $request->server('REMOTE_ADDR');

        return $ip !== null && IpUtils::checkIp($ip, $allowed);
    }
}
