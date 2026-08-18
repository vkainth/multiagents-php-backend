<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

class CheckAgentId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        $currentRoute = Route::currentRouteName();
        $routesAllowed = [
            'landing',
            'handleAuth',
            'login.with.agent',
            'invalid.agent',
            'verify-email'

        ];
        if (!in_array($currentRoute, $routesAllowed)) {
            if ($user && (!$user->agent || $user->agent<=0)) {
                if($user->register_as == 'AGENT'){
                    return redirect(route('agent_next_step'));
                }
                else{
                    Log::info('User without agent id '.$currentRoute.'. ', [$user]);
                    return redirect()->route('invalid.agent');
                }
            }
        }
        return $next($request);
    }
}
