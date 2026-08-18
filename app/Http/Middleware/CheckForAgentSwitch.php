<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class CheckForAgentSwitch
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
        if(Auth::user()){
            $user = Auth::user();
            if(($user->agent != $user->login_with_agent) && $user->login_with_agent != config('constants.demo_agent_id')){
                return Redirect::guest(route('switch_agent', $request->all()));
                
            }
        }
        return $next($request);
    }
}
