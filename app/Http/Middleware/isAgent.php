<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Agents;

class isAgent
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
        if($user && $user->role == "AGENT" && $user->agent_pixilink_id){
            $agent = Agents::find($user->agent_pixilink_id);
            if(!$agent){
                return redirect(route('dashboard'));
            }
        }
        else{
            return redirect(route('dashboard'));
        }

        return $next($request);
    }
}
