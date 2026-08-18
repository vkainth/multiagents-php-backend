<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class CheckVowAccessOfAgent
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
        if($user){
            $currentRoute = Route::currentRouteName();
            $routesToCheck = [
                'dashboard',
            ];
            if (in_array($currentRoute, $routesToCheck)) {
                $agent = $user->agent()->first();
                if($request->get('status')){
                   if($request->get('status') == 'Sold' && !$agent->isSoldAllowed()){
                        $args = $request->all();
                        $args['status'] = 'Active';
                        return redirect()->route('dashboard',$args)->with('message', config('constants.no_sold_access_message'));
                    }
                }
            }
       }
        return $next($request);
    }
}
