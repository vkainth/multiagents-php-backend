<?php

namespace App\Http\Middleware;

use Closure;
use App\Repository\FirebaseRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;

class CheckEmailVerified
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

        if ($user && !$user->activated) {
            $firebaseRepo = new FirebaseRepository();
            $verified =  $firebaseRepo->checkVerification($user->uid);
            if ($verified) {
                $user->activated = 1;
                $user->save();
            } else {
                if ($currentRoute != 'verify-email' && $currentRoute != 'logout') {
                    return Redirect::guest(route('verify-email', $request->all()));
                }
            }
        }
        return $next($request);
    }
}
