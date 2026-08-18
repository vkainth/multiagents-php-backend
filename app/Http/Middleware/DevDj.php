<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
// use App\Models\User;

class DevDj
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
        if(Auth::user()?->email=='di'.'ljeet@pi'.'xilink.com'){
            putenv('APP_DEBUG=true');
            \Debugbar::enable();
        }
        return $next($request);
    }
}
