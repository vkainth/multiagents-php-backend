<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;

class HandleReferral{
    
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

        //check if referral cookie needs to store

        $routesAllowed = [
           'building-detail-page',
           'getWeeklyStats',
           'listing-detail-page2'
        ];

        if(in_array($currentRoute, $routesAllowed) && !$user && $request->get('ref_by') != ''){
            Cookie::queue(Cookie::make('ref_by', $request->get('ref_by'), 2628000));
        }

        $routes = [
            'complete-profile'
        ];

        if(in_array($currentRoute, $routes) && $user && Cookie::get('ref_by')){
            Cookie::queue(Cookie::forget('ref_by'));
        }
        
        return $next($request);
    }


}