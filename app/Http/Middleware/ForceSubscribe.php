<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use App\Models\UserPropertyViews;
use App\Models\Listings;

class ForceSubscribe{
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
        $listingFeatured = false;

        if($currentRoute == 'listing-detail-page2'){
            $slug = $request->route('slug');
            $listing = Listings::where('slug', $slug)->first();
            if($listing){
                $listingFeatured = $listing->is_featured();
            }
        }

        if($user && !$listingFeatured){
            if(!$user->isPremiumMember()){
                $date180d = date('Y-m-d H:i:s', strtotime('-180 days'));
                $viewBefore180 = UserPropertyViews::where('userid', $user->id)->where('created_at', '<=', $date180d)->first();
                if($viewBefore180){
                    $totalViews = UserPropertyViews::where('userid', $user->id)->count();
                    if($totalViews >= 200){
                        return redirect(route('subscription_pricing_table', ['requiredSubscription'=>true]));
                    }
                }
            }
        }
        return $next($request);
    }
}