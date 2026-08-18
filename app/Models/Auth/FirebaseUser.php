<?php

namespace App\Models\Auth;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use App\Models\Listings;
use App\Models\PropertiesEmailed;
use App\Models\PropertyViewStatDaily;
use Illuminate\Support\Facades\DB;
use App\Models\UserPropertyViews;
use App\Models\ShowingRequests;
use Laravel\Cashier\Billable;
use Stripe\Subscription as StripeSubscription;
use App\Models\Subscription;
use Stripe\Customer as StripeCustomer;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FirebaseUser extends Authenticatable
{
    use Notifiable;
    use Billable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uid', 'first', 'last', 'phone_country_code', 'phone', 'phone_verified', 'email', 'verificationSent', 'agreedToTerms', 'last_login', 'profile_image', 'signup_country', 'signup_ip', 'role', 'ref', 'agent_notified', 'property_suggestion_emails', 'new_feature_notifications', 'incomplete_signup_emails', 'phone_verification_sid',  'user_agent', 'device', 'ref_details', 'stripe_id', 'card_brand', 'card_last_four', 'trial_ends_at', 'trial_start_date', 'trial_end_date', 'client_type', 'work_with_realtor', 'stripe_sync_subscriptions'
    ];   

    public function isProfileCompleted()
    {
        $user = $this;
        if (!$user->first || !$user->last || !$user->phone || !$user->email) {
            return false;
        } else {
            return true;
        }
    }


    
    /* --------- RELATIONS [BEGIN] ---------------- */
     
    public function searches()
    {
        return $this->hasMany(\App\Models\UserSearches::class, 'userid', 'id');
    }

    public function property_views()
    {
        return $this->hasMany(\App\Models\UserPropertyViews::class, 'userid', 'id');
    }

    public function saved_searches()
    {
        return $this->hasMany(\App\Models\SavedSearches::class, 'userid', 'id');
    }

    public function building_follows()
    {
        return $this->hasMany(\App\Models\BuildingFollow::class, 'userid', 'id');
    }

    public function favorites()
    {
        return $this->hasMany(\App\Models\FavoriteListings::class, 'userid', 'id');
    }
    
    public function latest_favorites(): HasOne
    {
        return $this->hasOne(\App\Models\FavoriteListings::class, 'userid', 'id')->latestOfMany();
    }

    public function recent_searches()
    {
        return $this->hasMany(\App\Models\UserSearches::class, 'userid', 'id')->orderBy('updated_at', 'desc')->take(20);
    }

    public function recent_property_views()
    {
        return $this->hasMany(\App\Models\PropertyViewStatDaily::class, 'userid', 'id')->orderBy('updated_at', 'desc')->take(20);
    }

    public function stripeSubscription(): HasOne
    {
        return $this->hasOne(\App\Models\Subscription::class, 'user_stripe_id', 'stripe_id')->latestOfMany(); 
        /* //same-as: orderBy('id','desc')->first()*/
    }

    /* --------- RELATIONS [END] ---------------- */



    public function get_client_ip()
    {

        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public function total_properties_viewed()
    {
        return UserPropertyViews::where('userid', $this->id)->count();
    }

    public function total_showing_requests()
    {
        return ShowingRequests::where('userid', $this->id)->count();
    }


    public function get_properties_suggestion($images = false, $messageId = NULL)
    {
        $suggestedListings = NULL;
        $recentPropertyViews = $this->recent_property_views()->get();
        $recentSearches = $this->recent_searches()->get();
        $soldSearched = 0;
        $activeSearched = 0;
        if (count($recentPropertyViews) > 0 || count($recentSearches) > 0) {
            $min_prices_viewed = NULL;
            $max_prices_viewed = NULL;
            $last_viewed = NULL;
            $citiesViewed = NULL;
            $subareaViewed = NULL;
            $last_searched = NULL;
            $min_prices_searched = NULL;
            $max_prices_searched = NULL;
            $cities_searched = NULL;
            $subarea_searched = NULL;
            $min_price_viewed = NULL;
            $max_price_viewed = NULL;
            $min_price_searched = NULL;
            $max_price_searched = NULL;
            $typeSearched = array();
            $area_searched = NULL;
            if (count($recentPropertyViews) > 0) {
                $citiesViewed = $recentPropertyViews->pluck('city')->toArray();
                $subareaViewed = $recentPropertyViews->pluck('area')->toArray();
                $daysViewed = $recentPropertyViews->pluck('day')->toArray();
                $monthsViewed = $recentPropertyViews->pluck('month')->toArray();
                $yearsViewed = $recentPropertyViews->pluck('year')->toArray();
                $min_prices_viewed = $recentPropertyViews->pluck('min_price')->toArray();
                $max_prices_viewed = $recentPropertyViews->pluck('max_price')->toArray();
                $end_year = max($yearsViewed);
                $end_month = PropertyViewStatDaily::where('userid', $this->id)->where('year', $end_year)->max('month');
                $end_day =   PropertyViewStatDaily::where('userid', $this->id)->where('year', $end_year)->where('month', $end_month)->max('day');
                $last_viewed = Carbon::createFromDate($end_year, $end_month, $end_day);
                $min_price_viewed = min($min_prices_viewed);
                $max_price_viewed = max($max_prices_viewed);
                $statusViewed = $recentPropertyViews->pluck('status')->toArray();
                if (in_array('Active', $statusViewed)) {
                    $activeSearched = 1;
                }
                if (in_array('Sold', $statusViewed)) {
                    $soldSearched = 1;
                }
            }
            if (count($recentSearches) > 0) {
                $count = 0;
                foreach ($recentSearches as $recentSearch) {
                    if ($count == 0) {
                        $last_search_date_db = $recentSearch->created_at;
                    }
                    if ($recentSearch->status == "Active") {
                        $activeSearched = 1;
                    }
                    if ($recentSearch->status == "Sold") {
                        $soldSearched = 1;
                    }

                    $searchRecord = json_decode($recentSearch->data);
                    if (property_exists($searchRecord, 'status') && $searchRecord->status) {
                        if ($searchRecord->status == "Active") {
                            $activeSearched = 1;
                        }
                        if ($searchRecord->status == "Sold") {
                            $soldSearched = 1;
                        }
                    }
                    if (property_exists($searchRecord, 'cities') && $searchRecord->cities) {
                        $searched_city = explode(";", $searchRecord->cities);
                        if ($cities_searched == NULL) {
                            $cities_searched = array();
                        }
                        $cities_searched = array_merge($cities_searched, $searched_city);
                    }
                    if (property_exists($searchRecord, 'areas') && $searchRecord->areas) {
                        $searched_areas = explode(";", $searchRecord->areas);
                        if ($area_searched == NULL) {
                            $area_searched = array();
                        }
                        $area_searched = array_merge($area_searched, $searched_areas);
                    }
                    if (property_exists($searchRecord, 'subareas') && $searchRecord->subareas) {
                        $searched_subareas = explode(";", $searchRecord->subareas);
                        if ($subarea_searched == NULL) {
                            $subarea_searched = array();
                        }
                        $subarea_searched = array_merge($subarea_searched, $searched_subareas);
                    }
                    if (property_exists($searchRecord, 'min_price') && $searchRecord->min_price) {
                        if ($min_prices_searched == NULL) {
                            $min_prices_searched = array();
                        }
                        $min_prices_searched[] = $searchRecord->min_price;
                        $min_price_searched = min($min_prices_searched);
                    }
                    if (property_exists($searchRecord, 'max_price') && $searchRecord->max_price) {
                        if ($max_prices_searched == NULL) {
                            $max_prices_searched = array();
                        }
                        $max_prices_searched[] = $searchRecord->max_price;
                        $max_price_searched = max($max_prices_searched);
                    }
                    if (property_exists($searchRecord, 'type') && $searchRecord->type) {
                        $typeSearched = array_merge($typeSearched, $searchRecord->type);
                    }
                    $count++;
                }
                $last_searched = Carbon::createFromFormat('Y-m-d H:i:s', $last_search_date_db);
            }
            if ($last_searched && $last_viewed) {
                if ($last_searched > $last_viewed) {
                    $last_activity = $last_searched;
                    $last_activity_type = "search";
                } else {
                    $last_activity = $last_viewed;
                    $last_activity_type = "view";
                }
            } elseif ($last_searched) {
                $last_activity = $last_searched;
                $last_activity_type = "search";
            } else {
                $last_activity = $last_viewed;
                $last_activity_type = "view";
            }
            if ($cities_searched && $citiesViewed) {
                $allcities = array_merge($cities_searched, $citiesViewed);
            } elseif ($cities_searched) {
                $allcities = $cities_searched;
            } else {
                $allcities = $citiesViewed;
            }

            if ($subarea_searched && $subareaViewed) {
                $allsubareas = array_merge($subarea_searched, $subareaViewed);
            } elseif ($subarea_searched) {
                $allsubareas = $subarea_searched;
            } else {
                $allsubareas = $subareaViewed;
            }

            if ($min_price_viewed && $max_price_viewed && $min_price_viewed != $max_price_viewed) {
                $min_price = $min_price_viewed;
                $max_price = $max_price_viewed;
            } elseif ($min_price_searched && $max_price_searched) {
                $min_price = $min_price_searched;
                $max_price = $max_price_searched;
            } else {
                $min_price = 0;
                $max_price = 10000000;
            }
            $city = NULL;
            $subarea = NULL;
            if ($allcities) {
                $c = array_count_values($allcities);
                $city = array_search(max($c), $c);
            }

            if ($allsubareas) {
                $c = array_count_values($allsubareas);
                $subarea = array_search(max($c), $c);
            }
            $type = array('House', 'Townhouse', 'Apartment');
            $listingAlreadySentQuery = PropertiesEmailed::where('userid', $this->id);
            if ($messageId) {
                $listingAlreadySentQuery = $listingAlreadySentQuery->where('messageId', '!=', $messageId);
            }
            $listingAlreadySentQuery = $listingAlreadySentQuery->get()->pluck('mls')->toArray();
            $listingAlreadySent = $this->explode_inner($listingAlreadySentQuery);
            if (strtotime($last_activity) < strtotime('-3 days')) {
                if ($activeSearched) {

                    $suggestedListingsActive = Listings::where('listprice_2', '>=', $min_price)->where('listprice_2', '<=', $max_price)->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->where('list_date', '>', $last_activity)->where('list_date', '>', DB::raw('DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)'))->orderBy('list_date', 'desc');
                    if ($subarea) {
                        $suggestedListingsActive->whereIn('subarea', array_unique($allsubareas));
                    }
                    if ($allcities) {
                        $suggestedListingsActive->whereIn('city', array_unique($allcities));
                    }
                    if ($area_searched) {
                        $suggestedListingsActive->whereIn('area', array_unique($area_searched));
                    }
                    if (count($typeSearched) > 0) {
                        $suggestedListingsActive->whereIn('type', array_unique($typeSearched));
                    } else {
                        $suggestedListingsActive->whereIn('type', array('House', 'Townhouse', 'Apartment'));
                    }
                    if ($images) {
                        $suggestedListingsActive->with('photos');
                    }

                    if (count($listingAlreadySent) > 0) {
                        $suggestedListingsActive->whereNotIn('listingid', $listingAlreadySent);
                    }

                    $suggestedListingsActive->withCount('photos')->having('photos_count', '>', 0);

                    $suggestedListings['active'] = $suggestedListingsActive->where('status', 'Active')->whereNotIn('listingid', function ($subquery) {
                        $subquery->select('mls')->from('pixilinkvow.user_property_views')->where('userid', $this->id);
                    })->take(6)->get();
                } else {
                    $suggestedListings['active'] = array();
                }

                if ($soldSearched) {
                    $suggestedListingsSold = Listings::where('soldprice_2', '>=', $min_price)->where('soldprice_2', '<=', $max_price)->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->where('sold_date', '>', $last_activity)->where('sold_date', '>', DB::raw('DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)'))->orderBy('sold_date', 'desc');
                    if ($subarea) {
                        $suggestedListingsSold->whereIn('subarea', array_unique($allsubareas));
                    }
                    if ($allcities) {
                        $suggestedListingsSold->whereIn('city', array_unique($allcities));
                    }
                    if ($area_searched) {
                        $suggestedListingsSold->whereIn('area', array_unique($area_searched));
                    }
                    if (count($typeSearched) > 0) {
                        $suggestedListingsSold->whereIn('type', array_unique($typeSearched));
                    } else {
                        $suggestedListingsSold->whereIn('type', array('House', 'Townhouse', 'Apartment'));
                    }
                    if ($images) {
                        $suggestedListingsSold->with('photos');
                    }

                    if (count($listingAlreadySent) > 0) {
                        $suggestedListingsSold->whereNotIn('listingid', $listingAlreadySent);
                    }

                    $suggestedListingsSold->withCount('photos')->having('photos_count', '>', 0);

                    $suggestedListings['sold'] = $suggestedListingsSold->where('status', 'Sold')->whereNotIn('listingid', function ($subquery) {
                        $subquery->select('mls')->from('pixilinkvow.user_property_views')->where('userid', $this->id);
                    })->take(6)->get();
                } else {
                    $suggestedListings['sold'] = array();
                }
            }
        }
        return $suggestedListings;
    }

    public function hasActivePremiumSubscription(){
        $hasActivePremiumSubscription = false;
        $user = $this;
        if($user->subscribed('premium')){
            $hasActivePremiumSubscription = true;
        } 
        return $hasActivePremiumSubscription;
    }

    public function hasActiveFreeSubscription(){

        $hasActiveFreeSubscription = false;
        $user = $this;
        if($user->subscribed('free')){
            $subscription = Subscription::where('firebase_user_id', $user->id)
                ->where('name', 'free')
                ->orderBy('id', 'desc')->first();
            $subscription_stripe_id = $subscription->stripe_id;
            if($subscription_stripe_id){
                $stripe_subscription =  StripeSubscription::retrieve($subscription_stripe_id/*, $this->stripe_id*/);
                $s_subscription = json_decode(json_encode($stripe_subscription), true);
                if(array_key_exists('status', $s_subscription)){
                    if($s_subscription['status'] == 'trialing' || $s_subscription['status'] == 'active' || $s_subscription['status'] == 'past_due' || $s_subscription=='unpaid'){
                        $hasActiveFreeSubscription = true;
                    }
                }
            }
            
        } 
        return $hasActiveFreeSubscription;

    }

    public function isOnTrial(){
        
        $user = $this;
        $ontrial = false;
        $today = date('Y-m-d');
        $trialend = date('Y-m-d');

        if(!$user->trial_start_date && $user->phone_verified){
            $user->trial_start_date = $today;
            $user->trial_end_date = $trialend;
            $user->save();
        }

        if($user->trial_end_date){
            if($user->trial_end_date >= $today && !$user->subscribed('premium')){
                $ontrial = true;
            }
        }

        return $ontrial;
    }

    public function syncSubscriptions(){
        $user = $this;
        $syncRequired = false;
        $today = date('Y-m-d');

        if($user->stripe_id){
            if(!$user->stripe_sync_subscriptions){
                $syncRequired = true;
            }
            elseif($user->stripe_sync_subscriptions && $user->stripe_sync_subscriptions < $today){
                $syncRequired = true;
            }
        }
       
        if($syncRequired){

            $processSync = true;

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/subscriptions?customer='.$user->stripe_id);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_USERPWD, $this->stripe_id . ':' . '');
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            
            $headers = array();
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result_json = curl_exec($ch);
            $result = array();

            if (curl_errno($ch)) {
                $processSync = false;
            }
            else{
                $result = json_decode($result_json, true);
            }

            curl_close($ch);

            

            if($result && $processSync){
                $premium_plans = [
                    'price_1TLkLSJMQ9rLXPTO4pFf1SXa', 'price_1TLkM9JMQ9rLXPTOpfrxPIiO', 'price_1TLkKZJMQ9rLXPTOnjyDTBlj', // current prices
                    'price_1OCnKOJMQ9rLXPTOdgrcrTXq', 'price_1OCnKOJMQ9rLXPTOshfJOKPN', 'price_1OCnKOJMQ9rLXPTO0xQwS6YD', // legacy prices
                ];
                if(array_key_exists('data', $result) && count($result['data'])){
                    foreach($result['data'] as $subscription){
                        if(array_key_exists('plan', $subscription) && array_key_exists('id', $subscription['plan']) && in_array($subscription['plan']['id'], $premium_plans)){
                            if($subscription['id']){
                                $subscriptionDb = Subscription::where('firebase_user_id', $user->id)->where('stripe_id', $subscription['id'])->first();
                                //if(!$subscriptionDb){
                                    $stripe_subscription = StripeSubscription::retrieve($subscription['id']/*, $this->stripe_id*/);
                                    if(!$subscriptionDb){
                                        $db_subscription = new Subscription();
                                        $db_subscription->firebase_user_id = $user->id;
                                        $db_subscription->stripe_id = $subscription['id'];
                                        $db_subscription->user_stripe_id = $user->stripe_id;
                                    }
                                    else{
                                        $db_subscription = $subscriptionDb;
                                    }
                                    $db_subscription->name = "premium";

                                    $customer_detail = StripeCustomer::retrieve($user->stripe_id, $this->stripe_id);
                                    $db_subscription->user_stripe_email = $customer_detail->email;

                                    if (isset($stripe_subscription->quantity)) {
                                        $db_subscription->quantity = $stripe_subscription->quantity;
                                    }
                        
                                    if (isset($stripe_subscription->plan->id)) {
                                        $db_subscription->stripe_plan = $stripe_subscription->plan->id;
                                    }
                        
                                    // Trial ending date...
                                    if (isset($stripe_subscription->trial_end)) {
                                        $db_subscription->trial_ends_at = Carbon::createFromTimestamp($stripe_subscription->trial_end);
                                    }
                        
                                      //ending date...
                                    if (isset($stripe_subscription->current_period_end)) {
                                        $db_subscription->ends_at = Carbon::createFromTimestamp($stripe_subscription->current_period_end);
                                    }
                        
                                    $db_subscription->save();
                                //}
                            }
                        }
                    }
                }
            }

            $user->stripe_sync_subscriptions = $today;
            $user->save();
        }
    }

    protected $isPremiumMember_val = null;
    public function isPremiumMember(){
        if(!is_null($this->isPremiumMember_val)){
            return $this->isPremiumMember_val;
        }
        $isPremiumMember = false;
        $user = $this;
        $userEmail = $user->email;

        
        $today = date('Y-m-d');
        $trialend = date('Y-m-d');
            
        if(!$user->trial_start_date && $user->phone_verified){
            // Safety-net dedup: check phone-based trial history before granting a fresh trial.
            // Primary dedup happens at OTP verification time; this catches any edge cases.
            $phoneOrigUser = null;
            if ($user->phone && $user->phone !== '') {
                $phoneOrigUser = FirebaseUser::where('phone', $user->phone)
                    ->where('phone_verified', '1')
                    ->where('id', '!=', $user->id)
                    ->whereNotNull('phone')
                    ->where('phone', '!=', '')
                    ->whereNotNull('trial_end_date')
                    ->orderBy('trial_end_date', 'asc')
                    ->first();
            }
            if ($phoneOrigUser) {
                $user->trial_start_date = $phoneOrigUser->trial_start_date;
                $user->trial_end_date   = $phoneOrigUser->trial_end_date;
            } else {
                $user->trial_start_date = $today;
                $user->trial_end_date   = $trialend;
            }
            $user->save();
        }

        $emailParts = explode('@', $userEmail);
        //$emailParts[1] == 'pixilink.com' || 
        if(in_array($emailParts[1], ['bccondosandhomes.com','bccondos.net','6717000.com'])){
            $this->isPremiumMember_val = true;
            return true;
        }
        //else

        if($user->trial_end_date >= $today){
            $this->isPremiumMember_val = true;
            return true;
        }

        $this->syncSubscriptions();

        $subscription = Subscription::where('firebase_user_id', $user->id)
                ->where('name', 'premium')
                ->orderBy('id', 'desc')->first();
        $today = date('Y-m-d');

        if($subscription && $subscription->stripe_confirmed < $today){
            $subscription_stripe_id = $subscription->stripe_id;
                if($subscription_stripe_id){
                    $stripe_subscription =  StripeSubscription::retrieve( $subscription_stripe_id /*, $this->stripe_id*/);
                    $s_subscription = json_decode(json_encode($stripe_subscription), true);
                    if(array_key_exists('status', $s_subscription)){
                        if($s_subscription['status'] == 'trialing' || $s_subscription['status'] == 'active'){
                            $isPremiumMember = true;
                        }
                    }

                    // Trial ending date...
                    if (isset($stripe_subscription->trial_end)) {
                        $subscription->trial_ends_at = Carbon::createFromTimestamp($stripe_subscription->trial_end);
                    }

                    //ending date...
                    if (isset($stripe_subscription->current_period_end)) {
                        $subscription->ends_at = Carbon::createFromTimestamp($stripe_subscription->current_period_end);
                    }
                    

                    $subscription->stripe_confirmed = date('Y-m-d');
                    $subscription->save();
                }
        }

        if($user->subscribed('premium')){
            $isPremiumMember = true;
        }
        $this->isPremiumMember_val = $isPremiumMember;
        return $isPremiumMember;
    }

    public function getUserSubscriptionPlan(){
        $plan = "Free Member";
        if(!$this->stripe_id){
            $plan = '';
        } elseif($this->hasActivePremiumSubscription()){
            $plan = 'Premium Member';
        }
        return $plan;
    }

    public function getUserSubscriptionStatus(){
        $status = '';
        if($this->stripe_id){
            if($this->hasActivePremiumSubscription()){
                $status = 'Active';
            }
            else{
                $status = 'Inactive';
            }
        }
        return $status;
    }

    /**
     * $subscriptionObj to stop re-quering db on every-call to getSubscriptionPlanDetail()
     * @var null|subscriptin_model
     */
    protected $subscriptionObj = null;
    public function getSubscriptionPlanDetail(){
        if($this->subscriptionObj!=null){
            return $this->subscriptionObj;
        }
        $sql = "select * from bccondosandhomes.subscriptions where firebase_user_id = ".$this->id." order by id desc";
        $res = DB::select(/*DB::raw*/($sql));
        if(count($res)){
            $this->subscriptionObj = $res[0];
            return $res[0];
        }else {
            $this->subscriptionObj = false;
        }
        return false;
    }

    public function getSubscriptionPlanInterval(){
        $interval = '';
        $subscription = $this->getSubscriptionPlanDetail();
        if($subscription){
            if(in_array($subscription->stripe_plan, ['price_1TLkLSJMQ9rLXPTO4pFf1SXa', 'price_1OCnKOJMQ9rLXPTOdgrcrTXq'])){
                $interval = 'Year';
            }
            elseif(in_array($subscription->stripe_plan, ['price_1TLkM9JMQ9rLXPTOpfrxPIiO', 'price_1OCnKOJMQ9rLXPTOshfJOKPN'])){
                $interval = "Month";
            }
            elseif(in_array($subscription->stripe_plan, ['price_1TLkKZJMQ9rLXPTOnjyDTBlj', 'price_1OCnKOJMQ9rLXPTO0xQwS6YD'])){
                $interval = "Week";
            }
        }
        return $interval;
    }

    public function getSubscriptionPlanPrice(){
        $price = NULL;
        $subscription = $this->getSubscriptionPlanDetail();
        if($subscription){
            if(in_array($subscription->stripe_plan, ['price_1TLkLSJMQ9rLXPTO4pFf1SXa', 'price_1OCnKOJMQ9rLXPTOdgrcrTXq'])){
                $price = '140';
            }
            elseif(in_array($subscription->stripe_plan, ['price_1TLkM9JMQ9rLXPTOpfrxPIiO', 'price_1OCnKOJMQ9rLXPTOshfJOKPN'])){
                $price = "14";
            }
            elseif(in_array($subscription->stripe_plan, ['price_1TLkKZJMQ9rLXPTOnjyDTBlj', 'price_1OCnKOJMQ9rLXPTO0xQwS6YD'])){
                $price = "7";
            }
        }
        return $price;
    }

    public function explode_inner($array = array())
    {
        $response = array();
        $array2 = array();
        foreach ($array as $item) {
            $array2[] = explode(",", $item);
        }
        if (count($array2) > 0) {
            $response = array_unique(array_merge(...$array2));
        }
        return $response;
    }
}
