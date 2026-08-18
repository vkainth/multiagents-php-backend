<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectToMapPage
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
        $params = $request->all();
        $filter = [
            'city',
            'area',
            'subarea',
            'postalarea',
            'postalcode',
            'address',
            'status'
        ];
        if (count(array_intersect_key(array_flip($filter), $params)) > 0 ) {
            return $next($request);
        }
        else{
            $user = Auth::user();
            $loginWithAgent = $user->loginWithAgent()->first();
            $p2 = [
                'agentId'=>$loginWithAgent->vow_username
            ];
            $params2 = array_merge($p2,$params);
            return redirect(route('mapPage', $params2));
        }
        return $next($request);
    }
}
