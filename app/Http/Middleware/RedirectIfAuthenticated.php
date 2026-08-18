<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Carbon;
use Illuminate\Filesystem\Cache;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $user = Auth::user();
        $currentRoute = Route::currentRouteName();


        $routesAllowed = [
            'landing',
            'handleAuth',
            'privacy-policy',
            'terms-and-conditions',
            'rebgv-terms-and-conditions'
        ];

        $routesAllowed2 = [
            'landing',
            'handleAuth'
        ];

        if ($user && in_array($currentRoute, $routesAllowed2)) {
            return redirect()->intended(route('landing', $request->all()));
        } else if (!$user && !in_array($currentRoute, $routesAllowed)) {
            if($currentRoute == "subscription_pricing_table"){
                return redirect(route('login.with.agent'));
            }
            else{
                session(['url.intended' => $request->fullUrl()]);
                return redirect()->route('login.with.agent'/*,['redirect' => $request->fullUrl()]*/);
                // return redirect(route('landing'));
            }
        }
        return $next($request);
    }
}
