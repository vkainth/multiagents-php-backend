<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware(\App\Http\Middleware\RestrictDevRoutes::class)->prefix('agents-special')->group(function () {

    // Returns viewer PII (names, emails, phone numbers) for a listing or
    // building. Access is enforced by RestrictDevRoutes on the group above;
    // the wildcard CORS header is gone so no third-party origin can read it.
    Route::get('/api/recent-visitors/{mode}/{slug}', function (Request $request, $mode, $slug=null) {

        $_retArrErr = ['status'=>'error','error'=>'failed!'];
        $_retArr = ['status'=>'success','view_count'=> 0, 'recent_users'=>[]];

        $_selectFields = ['first', 'last', 'phone_country_code', 'phone', 'email', 'profile_image', /* 'role', 'last_login', 'device', 'property_suggestion_emails', 'trial_start_date', 'trial_end_date', 'client_type', 'work_with_realtor'*/ ];

        if($mode=='listing'){
            $the_listing = App\Models\Listings::where('slug',$slug)->first();
            $building = $the_listing?->get_building();
            $_thisListingPropertyViews = App\Models\UserPropertyViews::where('mls',$the_listing?->listingid);

            $_thisListingViewers = App\Models\User::whereIn('id', $_thisListingPropertyViews?->pluck('userid'))->select($_selectFields)->get();
            $_retArr['this_listing_mls'] = $the_listing?->listingid;
            $_retArr['this_listing_viewers'] = $_thisListingViewers;
            $_retArr['recent_viewers'] = $_retArr['this_listing_viewers'];

        }else{
            $building = App\Models\Buildings::find($slug);
        }

        if(!$building){
            $_retArrErr['error']='No-Building!';
            // return response()->json($_retArrErr);
        }


        $buildingViews = App\Models\UserBuildingViews::where('building_id', $building?->import_id);
        
        $_buildingListings = array_unique([...$building?->active_listings()?->pluck('listingid')??[], ...$building?->sold_listings()?->pluck('listingid')??[]]);
        
        $propertyViews = App\Models\UserPropertyViews::whereIn('mls',$_buildingListings);

        $_listingsViewers = App\Models\User::whereIn('id', $propertyViews?->pluck('userid'))->select($_selectFields)->get();

        $_buildingViewers = App\Models\User::whereIn('id', $buildingViews?->pluck('userid'))->select($_selectFields)->get();

        $_totalUniqueViewers = App\Models\User::whereIn('id', [...$propertyViews?->pluck('userid'), ...$buildingViews?->pluck('userid')])->select($_selectFields)->get();

        $_buildingViewersCount = $_buildingViewers?->count();
        $_listingsViewersCount = $_listingsViewers?->count();

        $_retArr['listings_mls_ids'] = $_buildingListings;

        $_retArr['listings_viewers'] = $_listingsViewers;
        $_retArr['listings_viewers_count'] = $_listingsViewersCount;

        $_retArr['building_viewers'] = $_buildingViewers;
        $_retArr['building_view_count'] = $_buildingViewersCount;
        
        $_retArr['viewers_count_total'] = ($_buildingViewersCount + $_listingsViewersCount);

        $_retArr['recent_viewers'] = $_totalUniqueViewers?->count()?$_totalUniqueViewers:$_retArr['recent_viewers'];
        $_retArr['recent_viewers_unique_count'] = $_totalUniqueViewers?->count();

        return response()->json($_retArr);
    })->name('api-recent-visitors');

});


Route::get('/api/offerland-reqs', function (Request $request) {
    @header("Access-Control-Allow-Origin: *");

    $token = $request->header('X-Auth-Token');
    
    if (!auth()?->user()?->can('dev-dj-approve') && $token !== env('OFFERLAND-AUTH-TOKEN-202505-REQ','ofrld278syt3e45FbXa3ca30f73j')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    // $_data = App\Models\Listings::active()->pluck('mls','beds','baths');
    $_data = App\Models\Listings::active()->select(['listingid','bedrooms','bathstotal'])->simplePaginate(10000);

    return response()->json($_data);

});

