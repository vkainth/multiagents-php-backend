<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Cookie;

class SetLoggedInCookie
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (Auth::check()) {
            $response->headers->setCookie(
                new Cookie(
                    'logged_in',  // name
                    '1',          // value
                    time() + 60 * 60 * 24 * 30, // expire: absolute Unix timestamp 30 days from now
                    '/',          // path
                    null,         // domain
                    true,         // secure
                    false,        // httpOnly: false so Varnish can read it
                    false,        // raw
                    Cookie::SAMESITE_LAX
                )
            );
        } elseif ($request->cookie('logged_in')) {
            $response->headers->clearCookie('logged_in', '/', null, true, false);
        }

        return $response;
    }
}
