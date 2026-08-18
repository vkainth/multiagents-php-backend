<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Auth\FirebaseUser;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Session;

class TrackHistoryURL {

     /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

     public function handle ($request, Closure $next){

        $allowed_routes = [
            'for_sale_listings_beds_subarea',
            'for_sale_listings',
            'for_sale_listings_subarea',
            'featured-listings',
            'our-solds',
            'getWeeklyStats',
            'show_favorite_listings',
            'listings-slugfiltered-subarea',
            'adv_search_listings'
        ];

        $listing_routes = [
            'building-detail-page',
            'listing-detail-page2',
        ];

        $subscription_referral_routes = [
            'subscription_pricing_table',
            'stripe-manage-subscription',
            'subscription-confirmation'
        ];

        $currentRoute = Route::currentRouteName();
        $params = $request->all();

        if(in_array($currentRoute, $listing_routes)){
            $slug = $request->route('slug');
            if($currentRoute == "building-detail-page"){
                $buildings_history = Session::get('buildings_history');
                if(is_array($buildings_history) && !in_array($slug, $buildings_history)){
                    if(count($buildings_history)>=5){
                        $array_size = count($buildings_history);
                        $values_to_remove = $array_size - 4;
                        $buildings_history = array_slice($buildings_history, $values_to_remove);
                    }
                    else{
                        array_push($buildings_history, $slug);
                    }
                }
                else{
                    $buildings_history = [$slug];
                }
                Session::put('buildings_history', $buildings_history);
                Session::put('history_url', route($currentRoute, ['slug'=>$slug]));
            }
            elseif($currentRoute == "listing-detail-page2"){
                $listings_history = Session::get('listings_history');
                if(is_array($listings_history) && !in_array($slug, $listings_history)){
                    if(count($listings_history)>=5){
                        $array_size = count($listings_history);
                        $values_to_remove = $array_size - 4;
                        $listings_history = array_slice($listings_history, $values_to_remove);
                    }
                    else{
                        array_push($listings_history, $slug);
                    }
                }
                else{
                    $listings_history = [$slug];
                }
                Session::put('listings_history', $listings_history);
                Session::put('history_url', route($currentRoute, ['slug'=>$slug]));
            }
        }
        elseif(in_array($currentRoute, $allowed_routes)){
            $route_info = [
                'route'=>$currentRoute,
                'args'=>$request->all()
            ];
            Session::put('history_route', $route_info);
        }
        elseif(in_array($currentRoute, $subscription_referral_routes)){
            $intended_url = redirect()->intended(route('landing'))->getTargetUrl();
            $last_route = app('router')->getRoutes()->match(app('request')->create(URL::previous()))->getName();
            if($intended_url != route('landing')){
                Session::put('history_url', $intended_url);
            }
            elseif(in_array($last_route, $allowed_routes) || in_array($last_route, $listing_routes) ){
                Session::put('history_url', URL::previous());  
            }
        }
        return $next($request);
     }
}