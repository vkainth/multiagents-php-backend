<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ServerLog {

    public function handle ($request, Closure $next){

        try {
            $clientIp = $request->ip();

            $blocked = Cache::remember('blocked_ips_list', 300, function () {
                return collect(DB::select('SELECT ip_address FROM bccondosandhomes.blocked_ips'))
                    ->pluck('ip_address')
                    ->flip()
                    ->all();
            });

            if (isset($blocked[$clientIp])) {
                abort(403, 'Access denied.');
            }
        } catch (\Exception $e) {
        }

        return $next($request);
    }
}
