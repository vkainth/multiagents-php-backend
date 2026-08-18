<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe\Client as StripeClient;
use Illuminate\Support\Facades\Redirect;
use Stripe\Subscription as StripeSubscription;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use App\Models\Listings;
use App\Models\Buildings;

class SubscriptionController extends Controller
{
    protected static $stripeKey;
    protected $freeSubscriptionStripeId = "price_1OEdFCJMQ9rLXPTOZDWcVFzF";
    
    public static function getStripeKey()
    {
        if (static::$stripeKey) {
            return static::$stripeKey;
        }

        if ($key = getenv('STRIPE_SECRET')) {
            return $key;
        }

        return config('services.stripe.secret');
    }

    public function showPricingTable()
    {
        $user = Auth::user();
        if($user){
            $user->seen_subscription_plans = Carbon::now();
            $user->save();
        }
        if($user->stripe_id){
            return Redirect::to(route('stripe-manage-subscription'));
        }

        $expired_at = $user && $user->trial_end_date
            ? Carbon::parse($user->trial_end_date)->timestamp
            : null;

        $last_property = null;

        if(Session::has('listings_history') && is_array(Session::get('listings_history'))){
            $slugs = Session::get('listings_history');
            $lastSlug = end($slugs);
            $listing = Listings::where('slug', $lastSlug)->first();
            if($listing){
                $last_property = [
                    'name'    => $listing->name ?? ($listing->building_name ?? 'Property'),
                    'address' => $listing->address ?? ($listing->unit_number ?? ''),
                    'price'   => '$1,245,000',
                    'type'    => 'listing',
                ];
            }
        }

        if(!$last_property && Session::has('buildings_history') && is_array(Session::get('buildings_history'))){
            $slugs = Session::get('buildings_history');
            $lastSlug = end($slugs);
            $building = Buildings::where('slug', $lastSlug)->first();
            if($building){
                $last_property = [
                    'name'    => $building->name ?? 'Building',
                    'address' => $building->address ?? ($building->city ?? ''),
                    'price'   => '$1,245,000',
                    'type'    => 'building',
                ];
            }
        }

        return view('subscription_plans', [
            'expired_at'    => $expired_at,
            'last_property' => $last_property,
        ]);
    }

    public function manageSubscriptionPortal(){
        $request = request();
        $user = Auth::user();
        if($user->stripe_id){

            $hasActivePremiumSubscription = $user->hasActivePremiumSubscription();
            $hasActiveFreeSubscription = $user->hasActiveFreeSubscription();
            $alreadyCalled = $request->cookie('freesubscription_requested');
            
            if(!$hasActivePremiumSubscription && !$hasActiveFreeSubscription && !$alreadyCalled){

                cookie('freesubscription_requested', '1', 1);

                $data = array(
                    'customer'=>$user->stripe_id,
                    'items'=>array(
                        array(
                            'price'=>$this->freeSubscriptionStripeId
                        )                        
                    )
                );

                $sub = $this->stripe_curl('https://api.stripe.com/v1/subscriptions', $data);
                
            }

            $data = array(
                'customer' => $user->stripe_id,
                'return_url' => route('recall-history')
            );
    
            $portal_link = $this->stripe_curl("https://api.stripe.com/v1/billing_portal/sessions", $data);
            $response = json_decode($portal_link, true);
            if($response['url']){
                return Redirect::to($response['url']);
            }
        }
        else{
            return Redirect::to(route('subscription_pricing_table'));
        }
        
    }

    public function subscriptionConfirmation(){
        $recent_listings = [];
        $recent_buildings = [];
        $all_buildings = [];
        $building_slugs = [];

        if(Session::has('buildings_history') && is_array(Session::get('buildings_history'))){
            $all_buildings = Buildings::whereIn('slug', Session::get('buildings_history'))->get();
        }

        if(Session::has('listings_history') && is_array(Session::get('listings_history'))){
            $recent_listings = Listings::whereIn('slug', Session::get('listings_history'))->get();
        }

        foreach($all_buildings as $building){
            if(!in_array($building->slug, $building_slugs)){
                $recent_buildings[] = $building;
                $building_slugs[] = $building->slug;
            }
        }

        return view('subscription_confirmation')->with([
            'recent_buildings'=>$recent_buildings,
            'recent_listings'=>$recent_listings
        ]);
    }

    public function showNewPricingPage()
    {
        $user = Auth::user();
        return view('new_pricing', [
            'userEmail' => $user?->email ?? null,
        ]);
    }

    public function stripe_curl($url, $data){

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, $this->getStripeKey() . ':' . '');

        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return $result;

    }


}