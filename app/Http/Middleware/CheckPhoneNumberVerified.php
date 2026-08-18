<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class CheckPhoneNumberVerified
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
        if (Auth::user()) {
            $user = Auth::user();
            if ((!$user->phone || $user->phone == '' || $user->phone_verified != '1')) {
                return Redirect::guest(route('confirm-phone-number', $request->all()));
            }
        }
        return $next($request);
    }
}
