<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Repository\FirebaseRepository;
use App\Models\Auth\FirebaseUser;

class ApiAuth
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
        $errorResponse = [
            'success' => false,
            'message' => 'Not Authorized'
        ];

        if (!$user) {
            return response()->json($errorResponse);
        } else {
            if (!$user->activated) {
                $errorResponse['message'] = "Inactive User";
                return response()->json($errorResponse);
            }
            if ($user->first == '' || $user->last == '' || $user->email == '') {
                $errorResponse['message'] = "Incomplete User Profile";
                return response()->json($errorResponse);
            }
        }

        return $next($request);
    }
}
