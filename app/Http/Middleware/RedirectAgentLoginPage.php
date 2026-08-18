<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;

class RedirectAgentLoginPage
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
        // if($user){
        //     return Redirect::intended(route('dashboard', app('request')->request->all()));
        // }
        // else{
        //     return Redirect::guest(route('agent_login'));
        // }
        if(!$user && $currentRoute == 'vow_activation'){
            $request->session()->put('redirect_vow_activation', 1);
            return redirect(route('agent_login'));
        }
        elseif(!$user){
            return redirect(route('agent_login'));
        }
        return $next($request);
    }
}
