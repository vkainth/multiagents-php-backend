<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use App\Models\Auth\FirebaseUser;

class CheckProfileCompletion
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
        if ($user) {
            $currentRoute = Route::currentRouteName();
            $routesAllowed = [
                'handleAuth',
                'login.with.agent',
                'invalid.agent',
                'step2',
                'complete-profile',
                'verify-email',
                'subscription_pricing_table',
                'stripe-manage-subscription',
                'subscription-confirmation',
                'recall-history',
            ];
            if (!in_array($currentRoute, $routesAllowed)) {
                // Redirect users with incomplete profiles to finish sign-up
                if ($user->first == '' || $user->last == '' || $user->email == '' ||  $user->agreedToTerms == '' ||  $user->agreePrivacyNotice == '' ||  $user->agreeDisclosure == '' || $user->agreedToTerms == null ||  $user->agreePrivacyNotice == null ||  $user->agreeDisclosure == null) {
                    Log::info('User with incomplete profile ' . $currentRoute . '. ', [$user]);
                    return redirect(route('step2') . '?' . http_build_query(['redirect' => $request->fullUrl()]));
                }

                // Phone deduplication: if this user shares a phone with an older account,
                // copy that account's (earlier, possibly expired) trial_end_date over.
                // This blocks trial resets via new email sign-ups with the same phone.
                if($user->phone_verified){
                    if($user->trial_end_date){
                        $origUser = FirebaseUser::where('phone', $user->phone)->where('phone_verified', '1')->where('id', '!=', $user->id)->where('phone', '!=', '')->whereNotNull('phone')->where('trial_end_date', '<', $user->trial_end_date)->orderBy('trial_end_date', 'asc')->where('trial_end_date', '!=', $user->trial_end_date)->first();
                        if($origUser){
                            $user->trial_start_date = $origUser->trial_start_date;
                            $user->trial_end_date = $origUser->trial_end_date;
                            $user->save();
                        }
                    }
                }

            }
        }

        return $next($request);
    }
}
