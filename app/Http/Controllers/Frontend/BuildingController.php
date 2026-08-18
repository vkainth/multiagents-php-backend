<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Buildings;
// use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Repository\ActivityRepository;
use Illuminate\Support\Facades\Cache;
use App\Helpers\Helper;
use App\Helpers\FubAreaHelper;
use App\Models\UserListing;

class BuildingController extends Controller
{
    //

    protected $connection_360 = 'mysql_pixi360';

    public function __construct(){
        try {
            if(\Browser::isBot()){
                \Debugbar::disable();
            }
        } catch (\Throwable $e) {
            try { \Debugbar::disable(); } catch (\Throwable $e2) {}
        }
    }

    public function getBuilding($slug, $useNewSlugFld=false, Request $request=null)
    {
        $request = $request??request();
        if($request->input('bid',false)){
            $building = Buildings::where('id',$request->input('bid','none-default'))->where('slug',$slug)->first();
            return $building; //[added:16-08-2022]
        }
        if($useNewSlugFld){
            $building = Buildings::where($useNewSlugFld, $slug)
            /*->whereNotNull('strata_no')->where('strata_no','!=','')*/
            /*->where('board','!=', 'Victoria Real Estate Board') // covered in BuildingsGlobalFilterScope*/
            // ->where('city', '!=','Victoria') // /* [Disabled:2025-06-12] */
            ->firstOrFail();
            $slug = $building->slug;
        }else{
            $building = Buildings::where(function ($query) use ($slug) {$query->where('slug', $slug)->orWhere('slug_map', $slug);})
            /*->whereNotNull('strata_no')->where('strata_no','!=','')*/
            /*->where('board','!=', 'Victoria Real Estate Board') // covered in BuildingsGlobalFilterScope*/
            // ->where('city', '!=','Victoria') // /* [Disabled:2025-06-12] */
            ->orderBy('intid')->orderByDesc('updated')
            ->firstOrFail();
            if(!$building){
                return null;
            }
        }
        return $building;
    }


    public function getSlugForOlderUrls($slug, $useNewSlugFld=false, Request $request=null)
    {
        $request = $request??request();
        // $building = Buildings::where('slug', $slug)->whereNotNull('strata_no')->where('strata_no','!=','')->first();
        
        /**
         *  New-block added-on-6-Aug-2021--to-use-slug-2 
         *  Block modified: 1-sep-2021 to-use: slug3/slug4...etc.
         *  Block modified: 8-sep-2021 to-use: updated-slug, redirect if-$slug==slugtill7sep2021
         *  Block modified: 20-dec-2021 strata_no-check==NULL/Blank disabled, manually set to '--' in proceeding
         */
        if($useNewSlugFld){
            
            $building = Buildings::where($useNewSlugFld, $slug)/*->whereNotNull('strata_no')->where('strata_no','!=','')*/->first();
            return $building->slug;

        }else{

            $building = Buildings::where('slug', $slug)->orWhere('slug_map', $slug)
            /*->whereNotNull('strata_no')->where('strata_no','!=','')*/
            ->orderBy('intid')->orderByDesc('updated')
            ->first();

            if(!$building){
                /* REDIRECT to new slug-url, if the old-url might-be supplied*/
                $building = Buildings::where('slugtill7sep2021', $slug)->orWhere('slugtill28dec2021',$slug)/*->whereNotNull('strata_no')->where('strata_no','!=','')*/->first();
                if($building){
                    return $building->slug;
                    // return redirect()->route('building-detail-page', ['slug' => $building->slug], 301)/*->status(301)*/;
                }else{
                    $_oldToNewSlugB = DB::connection('mysql_mlsr')->table('zjt_bldgs_slugchanges')
                    ->where('slugtill7sep2021', $slug)
                    ->orWhere('slugtill28dec2021', $slug)
                    ->orWhere('slug_map', $slug)
                    ->orWhere('slug', $slug)
                    ->first(); //->pluck('slug_map'); //->value('slug');
                    if($_oldToNewSlugB){
                        return $_oldToNewSlugB->slug;
                        // return redirect()->route('building-detail-page', ['slug' => $_oldToNewSlugB->slug], 301)/*->status(301)*/;
                    }
                }
            }
        }

        return $building->slug ?? null;
        // return $building;

    }

    /**
     * getBuildingDetailPageReadyData [created:12-05-2022] (separated from rendering-view for scalability/api-mode-support in future/clarity)
     * @param  [type]  $slug          [description]
     * @param  boolean $useNewSlugFld [description]
     * @return [type]                 [description]
     * @param  Request $request       [description]
     */
    public function getBuildingDetailPageReadyData($slug, $useNewSlugFld=false, Request $request=null)
    {
        $request = $request??request();

        // $building = Buildings::where('slug', $slug)->whereNotNull('strata_no')->where('strata_no','!=','')->first();
        
        $building = $this->getBuilding($slug, $useNewSlugFld, $request);


        if ( empty($building) || !$building) {
            return null;
            // abort(404);
        }

        /**
         *  Block modified: 20-dec-2021 strata_no-check==NULL/Blank disabled.
         */
        if (empty($building->strata_no)) {
            // abort(404);
            // $building->strata_no='--'; // Manually set blank_[strata_no] to '--' to avoid 500 error [Updated:20-12-2021] // [disabled:2024-08-13]
        }

        $buildingPhotos = $building->photos()->get()->toArray();
        $period = "1year";// "2year";
        $interval = "1 Year";//"6 month"; //"2 Year";
        $beds = 'all';
        $active_listings = $building->active_listings()->get();
        $sold_listings = $building->sold_listings($interval)->get();
        
        /**
         * block [added:20-04-2022]
         * if(no-lisitngs) -> extend period+interval
         */
        if(/*count*/($sold_listings->count())<=0){
            $period = "2year"; $interval = "2 Year"; $sold_listings = $building->sold_listings($interval)->get();
        }

        $other_buildings = $building->other_buildings_in_complex();
        $statsData = $building->get_stats($interval);
        $presale_listings = $building->pre_sale_listings();
        $stats = null;
        if (count($statsData) > 0) {
            $stats = $statsData[0];
        }
        $total_listprice = 0;
        $total_area = 0;
        $total_soldprice = 0;
        $avgprice_sqlft = 0;
        $total_listarea = 0;
        $total_active_listings = /*count*/($active_listings->count());
        $avg_listing_price = 0;
        $avg_price_sqft = 0;
        $total_price_sqft = 0;
        $avg_area = 0;
        $maxBeds = 0;
        $total_soldarea = 0;
        $total_soldpricesqft = 0;
        $total_soldlistings = /*count*/($sold_listings->count());
        $avg_soldprice = 0;
        $avg_soldarea = 0;
        $avg_soldpricesqft = 0;
        $total_days_on_market_active = 0;
        $avg_days_on_market_active = 0;
        $total_days_on_market_sold = 0;
        $avg_days_on_market_sold = 0;
        $total_listprice_sold = 0;
        $avg_sale_to_list_ratio = 0;
        $isTownhouse = 0;
        $isPenthouse = 0;
        $maxBedsSold = 0;
        $isTownhouseSold = 0;
        $isPenthouseSold = 0;
        $min_price = 0;
        $max_price = 0;
        $ccnt = 0;
        foreach ($active_listings as $listing) {
            if($ccnt == 0){
                $min_price = $listing->listprice_2;
                $ccnt++;
            }
            $total_listprice = $total_listprice + $listing->listprice_2;
            $total_area = $total_area + $listing->livingarea_2;
            $total_listarea = $total_listarea + $listing->livingarea_2;
            $price_per_sqft = ($listing->livingarea_2!=0)?($listing->listprice_2 / $listing->livingarea_2):null;
            $total_price_sqft = $total_price_sqft + $price_per_sqft;
            if ($listing->bedrooms > $maxBeds) {
                $maxBeds = $listing->bedrooms;
            }
            if ($listing->listprice_2 > $max_price) {
                $max_price = $listing->listprice_2;
            }
            if ($listing->listprice_2 < $min_price) {
                $min_price = $listing->listprice_2;
            }
            if ($listing->type == 'Townhouse') {
                $isTownhouse = 1;
            }
            if (substr_count($listing->home_style, 'Penthouse') > 0) {
                $isPenthouse = 1;
            }
            $total_days_on_market_active = $total_days_on_market_active + $listing->active_days_on_market();
            
        }

        foreach ($sold_listings as $listing) {
            $total_soldprice = $total_soldprice + $listing->soldprice_2;
            $total_area = $total_area + $listing->livingarea_2;
            $total_soldarea = $total_soldarea + $listing->livingarea_2;
            $price_per_sqft = ($listing->livingarea_2!=0)?($listing->soldprice_2 / $listing->livingarea_2):null;
            $total_soldpricesqft = $total_soldpricesqft + $price_per_sqft;
            $total_days_on_market_sold = $total_days_on_market_sold + $listing->days_on_market();
            if ($listing->listprice_2 > 0) {
                $total_listprice_sold = $total_listprice_sold + $listing->listprice_2;
            }
            if ($listing->bedrooms > $maxBedsSold) {
                $maxBedsSold = $listing->bedrooms;
            }
            if ($listing->type == 'Townhouse') {
                $isTownhouseSold = 1;
            }
            if (substr_count($listing->home_style, 'Penthouse') > 0) {
                $isPenthouseSold = 1;
            }
        }

        $total_price = $total_listprice + $total_soldprice;

        if ($total_price > 0 && $total_area > 0) {
            $avgprice_sqlft = $total_price / $total_area;
        }

        if ($total_listprice > 0 && $total_active_listings > 0) {
            $avg_listing_price = $total_listprice / $total_active_listings;
        }

        if ($total_price_sqft > 0 && $total_active_listings > 0) {
            $avg_price_sqft = $total_price_sqft / $total_active_listings;
        }
        if ($total_listarea > 0 && $total_active_listings > 0) {
            $avg_area = $total_listarea / $total_active_listings;
        }

        if ($total_soldprice > 0 && $total_soldlistings > 0) {
            $avg_soldprice = $total_soldprice / $total_soldlistings;
        }

        if ($total_soldarea > 0 && $total_soldlistings > 0) {
            $avg_soldarea = $total_soldarea / $total_soldlistings;
        }

        if ($total_soldpricesqft > 0 && $total_soldlistings > 0) {
            $avg_soldpricesqft = $total_soldpricesqft / $total_soldlistings;
        }

        if ($total_days_on_market_active > 0 && $total_active_listings > 0) {
            $avg_days_on_market_active = $total_days_on_market_active / $total_active_listings;
        }

        if ($total_days_on_market_sold > 0 && $total_soldlistings > 0) {
            $avg_days_on_market_sold = $total_days_on_market_sold / $total_soldlistings;
        }

        if ($total_soldprice > 0 && $total_listprice_sold > 0) {
            $avg_sale_to_list_ratio = round($total_soldprice / $total_listprice_sold * 100);
        }

        // Buyers: distinct registered users who viewed/favourited this building or its listings
        $building_listing_ids = $active_listings->pluck('listingid')
            ->merge($sold_listings->pluck('listingid'))
            ->filter()->unique()->values();

        $building_buyers_count = Cache::remember(
            'bldg_buyers_count_' . $building->import_id,
            3600, // 1 hour in seconds; count changes slowly, no need for real-time accuracy
            function () use ($building, $building_listing_ids) {
                // 1. Users who viewed this building page directly (all time)
                $buyerIds = \App\Models\UserBuildingViews::where('building_id', $building->import_id)
                    ->pluck('userid');

                // 2. Users who viewed individual listings in this building (all time)
                if ($building_listing_ids->isNotEmpty()) {
                    $buyerIds = $buyerIds->merge(
                        \App\Models\UserPropertyViews::whereIn('mls', $building_listing_ids)
                            ->pluck('userid')
                    );
                }

                // 3. Users who favourited any listing in this building (all time)
                if ($building_listing_ids->isNotEmpty()) {
                    $buyerIds = $buyerIds->merge(
                        \App\Models\FavoriteListings::withoutGlobalScopes()
                            ->whereIn('listingid', $building_listing_ids)
                            ->pluck('userid')
                    );
                }

                return $buyerIds->filter()->unique()->count();
            }
        );

        $building_additional_information = null;

        /**
         * getting the last saved api-response
         */
        $_bcnInfoCached = $building->bcnInfoCached;
        $building_additional_information = json_decode(json_encode($_bcnInfoCached?->api_data),true);
        \Debugbar::addMessage(["LastSync"=>$_bcnInfoCached->last_synced,'_bcnInfoCached'=>$_bcnInfoCached,'_bcnInfoCached->api_data'=>$_bcnInfoCached?->api_data], 'bcnInfo_cached');


        /**
         * check $server_up == 'y' >> moved to checking if the cache(d) obj-fetch fails > only then perform DB-query
         */
        if (false /*$server_up == 'y' &&  $building->strata_no && !empty($building->strata_no)*/ ) {
            /* (to avoid-500 errors for-seldom-filegetcontents-failures:when unable to open_stream - try-catch added [on:06-10-2021]) */
            try{
                /* added-on: 10-09-2021 [enable after testing] (to-fetch-records on condos.id-based instead of strata-based)*/
                /*
                // \Config(['app.debug'=>true]);
                $_bcnmapped = $this->getBcnBcchSlugLinkerRecordForBcchSlug($slug);
                if($_bcnmapped && $_bcnmapped->bcn_gp_id){
                    $building_additional_information = file_get_contents('https://www.bccondosandhomes.com/api_building/public/index.php?task=trybcnwithid&condoid=' . $_bcnmapped->bcn_gp_id, 0, stream_context_create(["http" => ["timeout" => 2]]));
                }else{
                    $building_additional_information = file_get_contents('https://www.bccondosandhomes.com/api_building/public/index.php?strata=' . $building->strata_no, 0, stream_context_create(["http" => ["timeout" => 2]]));
                }
                */

               
                $_cacheKeyName = '_buildingBcnApi__'.date("Ymd").'_strata-'.urlencode(trim($building->strata_no)).'_streetnum-'.urlencode(trim($building->street_no?:'')).'_city-'.urlencode(Helper::enslugPlace($building->city?:'')).'_bcnId1-'.($building->bcc_id??'');
                $cachedBldAdtnlInfo = Cache::get($_cacheKeyName);
                
                if(empty($cachedBldAdtnlInfo)){

                    $_apiURL = 'https://www.bccondosandhomes.com/api_building/public/?strata=' .urlencode(trim($building->strata_no?:'--'))
                    .(
                        empty(trim(trim($building->strata_no,'-')))
                        ? ('&task=trybcnwithid&condoid='.urlencode(trim($building->bcc_id?:'')))
                        : ('&streetnum='.urlencode(trim($building->street_no?:''))) 
                    )
                    .'&city='.urlencode(trim($building->city?:'')).'&bcn_id='.urlencode(trim($building->bcc_id?:''))
                    .'&refreshtoken='.date("Ymd"); /* [date("Ymdhis") > every-second fresh-fetch | bloats-cache]*/


                    $sql = 'select up from bccondosandhomes.api_server_status where server = "bccondos.net"';
                    $res =  DB::select($sql);
                    $server_up = ($res && count($res))?($res[0]->up):'n';
                    
                    if($server_up=='y'){
                        
                        $building_additional_information = file_get_contents($_apiURL, 0, stream_context_create(["http" => ["timeout" => 2]]));

                        Cache::put($_cacheKeyName, $building_additional_information, 60*24);

                    }

                    \Debugbar::addMessage(['apiFetch'=>($server_up=='y'?'Yes':'No'),"apiUrl"=> $_apiURL], 'bcnInfo_apiURL');

                }else{
                    $building_additional_information = $cachedBldAdtnlInfo;
                    \Debugbar::addMessage('Using cached bcn-api Info!', 'bcnInfo_apiURL');
                }

            }catch(\Exception $exception){
                // report($exception);
                \Illuminate\Support\Facades\Log::error( $exception->getMessage().' at '.$exception->getFile() .':'. $exception->getLine() .' [custom-compressed-without-stacktrace].' );
                $building_additional_information = null; 
                // value returned will be ===FALSE (in file-get-cntnts fxn in try-block) , still setting value back to null
            }

            if ($building_additional_information /*&& is_string($building_additional_information)*/) {
                $building_additional_information = json_decode($building_additional_information, true);
            }
        }

        $ref = $request->get('ref');
        if (Auth::user()) {
            $activityRepository = new ActivityRepository();
            $activityRepository->logActivity('building_view', null, null, $building->import_id, null, $ref);
        }

        $subarea_slug = '';

        $sql = 'select slug from mls_query where city like "' . $building->city . '" and type = "Apartment" limit 1';
        $res =  DB::select($sql);
        if ($res && count($res)) {
            $sl = $res[0]->slug;
            $subarea_slug = $sl . "-for-sale-" . strtolower(str_replace(' ', '-', $building->subarea??''));
        }

        /**
         * block - for open_house events + extracted date-times [added:22-04-2022]
         * [updated: SEO fix] filter future-only, deduplicate, sort, cap at 20
         */
        $openHouseEvents = [];
        $_oheSeen = [];
        foreach($active_listings as $_listing) if($_listing->open_house){
            foreach(explode(',', ($_listing->open_house??null)) as $_oheIdx => $_openHouseEvent){
                $_oheStrAr = explode(':',$_openHouseEvent,2);
                $_oheStrTimes = explode('-',($_oheStrAr[1]??''),2);
                $_oheDates = [
                    strtotime($_oheStrAr[0].' '.date('y').' '.$_oheStrTimes[0].(strtotime($_oheStrTimes[0].'pm')>strtotime(($_oheStrTimes[1]??''))?'am':'pm')),
                    strtotime($_oheStrAr[0].' '.date('y').' '.($_oheStrTimes[1]??''))
                ];

                if(($_oheDates[1] ?? $_oheDates[0] ?? 0) < time()) continue;

                $_oheKey = $_listing->streetaddress . '|' . $_openHouseEvent;
                if(isset($_oheSeen[$_oheKey])) continue;
                $_oheSeen[$_oheKey] = true;

                $openHouseEvents []= [ 
                    'dates'=>$_oheDates, 
                    'listing_url'=>route('listing-detail-page2',['slug'=>$_listing->slug]),
                    'open_house'=> $_openHouseEvent,
                    'streetaddress'=> $_listing->streetaddress,
                    'jsonld'=>[
                        "@context"=> "https://schema.org/", "@type"=> "Event",
                        "startDate"=> date('c',($_oheDates[0]??'')),
                        "endDate"=> date('c',($_oheDates[1]??'')), 
                        "url"=>route('listing-detail-page2',['slug'=>$_listing->slug]),
                        "image"=> ($_listing->mainpicurl?:'https://www.bccondosandhomes.com/assets/img/no-image-800-600.png'),
                        "name"=> "Open House ".$_openHouseEvent,
                        "description"=> "Open House",
                        "eventAttendanceMode"=> "https://schema.org/OfflineEventAttendanceMode",
                        "eventStatus"=> "https://schema.org/EventScheduled", 
                        "performer"=> ["@type"=> "Person", "name"=> $_listing->agent_name],
                        "organizer"=> ["@type"=> "Organization", "name"=> $_listing->reoffice,"url"=> ($_listing->reoffice_url?:url('/')) ],
                        "offers"=>[
                                "@type"=> "Offer", 
                                "price"=> $_listing->listprice_2, "priceCurrency"=> "CAD", "validFrom"=>date('Y-m-d'),
                                "url"=> route('listing-detail-page2',['slug'=>$_listing->slug]),
                                "availability"=> "https://schema.org/".(strtolower($_listing->status)=='active')?'InStock':'SoldOut'
                            ],
                            "location"=>[
                                "@type"=> "Place",
                                "name"=> $_listing->streetaddress.', '.$_listing->city.', '.$_listing->province.' '.$_listing->postalcode,
                                "geo"=> [
                                    "@type"=> "GeoCoordinates",
                                    "latitude"=> $_listing->lat,
                                    "longitude"=> $_listing->lng
                                ],
                                "address"=>[
                                    "@type"=> "PostalAddress",
                                    "streetAddress"=> $_listing->streetaddress,
                                    "addressLocality"=> $_listing->city,
                                    "addressRegion"=> $_listing->province,
                                    "postalCode"=> $_listing->postalcode,
                                    "addressCountry"=> "Canada"
                                ]
                            ]
                    ]
                ];

            }
        }
        usort($openHouseEvents, function($a, $b) { return ($a['dates'][0] ?? 0) - ($b['dates'][0] ?? 0); });
        $openHouseEvents = array_slice($openHouseEvents, 0, 20);

        $user_listings = UserListing::where('building_id', $building->id)->where('active', 'y')->where('status', 'published')->get();

        // return view('frontend.building')->with([
        $_buildingDetailPageReadyData =  ([
            'building' => $building,
            'photos' => $buildingPhotos,
            'period' => $period,
            'beds' => $beds,
            'active_listings' => $active_listings,
            'sold_listings' => $sold_listings,
            'other_buildings' => $other_buildings,
            'avgprice_sqft' => $avgprice_sqlft,
            'avg_listing_price' => $avg_listing_price,
            'avg_price_sqft' => $avg_price_sqft,
            'avg_active_area' => $avg_area,
            'avg_soldprice' => $avg_soldprice,
            'avg_soldarea' => $avg_soldarea,
            'avg_soldpricesqft' => $avg_soldpricesqft,
            'avg_days_on_market_active' => $avg_days_on_market_active,
            'avg_days_on_market_sold' => $avg_days_on_market_sold,
            'stats' => $stats,
            'maxBeds' => $maxBeds,
            'isTownhouse' => $isTownhouse,
            'isPenthouse' => $isPenthouse,
            'maxBedsSold' => $maxBedsSold,
            'isTownhouseSold' => $isTownhouseSold,
            'isPenthouseSold' => $isPenthouseSold,
            'building_additional_information' => $building_additional_information,
            'presale_listings' => $presale_listings,
            'total_active_listings' => $total_active_listings,
            'total_soldlistings' => $total_soldlistings,
            'interval' => $interval,
            'subarea_slug' => $subarea_slug,
            'openHouseEvents' => $openHouseEvents,
            'canonical_slug' => $building->getCanonicalSlug(),
            'nearbyBuildings' => $this->getNearbyBuildings($building),
            'min_price' =>$min_price,
            'max_price'=>$max_price,
            'user_listings' => $user_listings,
            'avg_sale_to_list_ratio' => $avg_sale_to_list_ratio,
            'building_buyers_count' => $building_buyers_count,
        ]);

        return $_buildingDetailPageReadyData;
    }

    /**
     * showBuildingDetailPage previously-written main function for building-pages 
     * (seperated from getBlg..PageReadyData for scalability/api-mode-support in future/clarity, seperated on:12-05-2022)
     * @param  String  $slug          [description]
     * @param  boolean $useNewSlugFld [description]
     * @param  Request $request       [description] // removed/movedToLast:2024-10-21 
     * @return View                 [description]
     */
    public function showBuildingDetailPage($slug, $useNewSlugFld=false, Request $request=null)
    {
        $request = $request??request();

        $cacheKey = 'bldg_page_v2_' . $slug;
        $_buildingDetailPageReadyData = Cache::get($cacheKey);

        if (is_null($_buildingDetailPageReadyData)) {

            $_buildingDetailPageReadyData = $this->getBuildingDetailPageReadyData($slug, $useNewSlugFld, $request);
            // try{}catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){abort(410,'Sorry! The page no longer exists.'); }

            if(!$_buildingDetailPageReadyData){

                $currentSlug = $this->getSlugForOlderUrls($slug, $useNewSlugFld, $request);

                if($currentSlug){
                    /*@file_put_contents(storage_path('/logs/oldUrls_beingVisited.log'),  'https://www.bccondosandhomes.com/building/'.$slug." ?-> ".$currentSlug."\n", FILE_APPEND ); //  */
                    return redirect()->route('building-detail-page', ['slug' => $currentSlug], 301); /*->status(301)*/
                }
                abort(404);
                return null;
            }

            /* if(
                ($_buildingDetailPageReadyData['building']->board??'') == 'Victoria Real Estate Board'
                ||
                ($_buildingDetailPageReadyData['building']->city??'') == 'Victoria'
            ){
                abort(404);
            } */ // /*[Disabled:2025-06-12] */

            $_buildingDetailPageReadyData['bccondos_agents'] = array();
            $_buildingDetailPageReadyData['bccondos_agents_detail'] = array();

            $res = Cache::remember('bldg_team_members_v1', 3600, function() {
                return DB::select("select * from bccondosandhomes.team_members where mls_active = '1' and mlsid is not null and mlsid != ''");
            });
            foreach($res as $ag) {
                $_buildingDetailPageReadyData['bccondos_agents'][] = $ag->mlsid;
                $_buildingDetailPageReadyData['bccondos_agents_detail'][] = $ag;
            }

            $_buildingDetailPageReadyData['featured_listings'] = array();
            $_buildingDetailPageReadyData['featured_mlsids'] = array();

            $latest_listing = null;
            $latest_listing2 = null;
            $count = 0;
            foreach($_buildingDetailPageReadyData['active_listings'] as $_listing){
                if($_listing->listprice_2 >= 500000){
                    if($count == 0){ $latest_listing = $_listing; }
                    if($count == 1){ $latest_listing2 = $_listing; }
                    $count++;
                }
                if(in_array($_listing->agent_id, $_buildingDetailPageReadyData['bccondos_agents']) || in_array($_listing->agent2_id, $_buildingDetailPageReadyData['bccondos_agents']) || in_array($_listing->agent3_id, $_buildingDetailPageReadyData['bccondos_agents'])){
                    $_buildingDetailPageReadyData['featured_listings'][] = $_listing;
                    $_buildingDetailPageReadyData['featured_mlsids'][] = $_listing->listingid;
                }
            }

            if(count($_buildingDetailPageReadyData['featured_listings']) == 0){
                if($latest_listing != null){ $_buildingDetailPageReadyData['featured_listings'][] = $latest_listing; }
                if($latest_listing2 != null && count($_buildingDetailPageReadyData['active_listings']) >= 10){
                    $_buildingDetailPageReadyData['featured_listings'][] = $latest_listing2;
                }
            }

            // Resolve presale_listings to a Collection before serializing
            if ($_buildingDetailPageReadyData['presale_listings'] instanceof \Illuminate\Database\Eloquent\Builder
                || $_buildingDetailPageReadyData['presale_listings'] instanceof \Illuminate\Database\Query\Builder) {
                $_buildingDetailPageReadyData['presale_listings'] = $_buildingDetailPageReadyData['presale_listings']->get();
            }

            // Strip Builder objects cached as model attributes (onceCalledAndCached_*) — they contain Closures
            $building = $_buildingDetailPageReadyData['building'];
            foreach (array_keys($building->getAttributes()) as $attrKey) {
                $attrVal = $building->getAttributes()[$attrKey];
                if ($attrVal instanceof \Illuminate\Database\Eloquent\Builder
                    || $attrVal instanceof \Illuminate\Database\Query\Builder) {
                    $building->offsetUnset($attrKey);
                }
            }

            Cache::put($cacheKey, $_buildingDetailPageReadyData, 600);
        }

        // Save area tag to session for any visitor (including guests) so the
        // FUB registration push includes the tag when they sign up from this page.
        FubAreaHelper::saveToSession($_buildingDetailPageReadyData['building']->city ?? null);

        // Guests: fetch default sold listings for server-side rendering so Google
        // can index the data in the initial HTML (no AJAX needed for guests).
        if (!\Auth::check()) {
            try {
                $guestBuilding = $_buildingDetailPageReadyData['building'];
                $_buildingDetailPageReadyData['guestSoldListings'] = $guestBuilding
                    ->sold_listings('1 YEAR', 'all')
                    ->get();
            } catch (\Throwable $e) {
                $_buildingDetailPageReadyData['guestSoldListings'] = collect();
            }
        }

        return view('frontend.building')->with( $_buildingDetailPageReadyData );
    }


    public function getSoldListings(Request $request=null)
    {
        $authUser = Auth::user();
        $noCacheHeaders = [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma'        => 'no-cache',
        ];

        $request = $request??request();
        if (!$request->get('id') || !$request->get('period') || !$request->get('beds')) {
            return;
        }
        header('X-Robots-Tag: noindex, nofollow');
        $id = $request->get('id');
        $period = $request->get('period');
        $beds = $request->get('beds');
        $bedsWhere = null;
        if ($beds == 'beds1') {
            $bedsWhere = array('=', '1');
        } elseif ($beds == 'beds2') {
            $bedsWhere = array('=', '2');
        } elseif ($beds == 'beds3') {
            $bedsWhere = array('=', '3');
        } elseif ($beds == 'beds3p') {
            $bedsWhere = array('>', '3');
        } else {
            $bedsWhere = $beds;
        }

        $interval = "6 MONTH";

        if ($period == "30day") {
            $interval = "30 DAY";
        } elseif ($period == "90day") {
            $interval = "90 DAY";
        } elseif ($period == "6month") {
            $interval = "6 MONTH";
        } elseif ($period == "1year") {
            $interval = "1 YEAR";
        } elseif ($period == "2year") {
            $interval = "2 YEAR";
        }
        $building = Buildings::where('import_id', $id)->first();
        $listings = $building->sold_listings($interval, $bedsWhere)->get();
        $total_soldarea = 0;
        $total_soldpricesqft = 0;
        $total_soldprice = 0;
        $total_soldlistings = count($listings);
        $avg_soldprice = 0;
        $avg_soldarea = 0;
        $avg_soldpricesqft = 0;
        $total_days_on_market_sold = 0;
        $avg_days_on_market_sold = 0;
        $maxBedsSold = 0;
        $isTownhouseSold = 0;
        $isPenthouseSold = 0;

        // naive-echo-response replaced [djLogic]:
        $resp4mview = view('frontend.components.recent_sold_table_tbody_tr',array_merge(['sold_listings'=>$listings,'building'=>$building],compact('avg_soldprice','avg_soldarea','avg_soldpricesqft','avg_days_on_market_sold')));
        return response($resp4mview, 200, $noCacheHeaders);

    }

    public function getActiveListings(Request $request=null)
    {
        $request = $request??request();
        if (!$request->get('id') || !$request->get('beds')) {
            return;
        }
        header('X-Robots-Tag: noindex, nofollow');
        $id = $request->get('id');
        $beds = $request->get('beds');
        $bedsWhere = null;
        if ($beds == 'beds1') {
            $bedsWhere = array('=', '1');
        } elseif ($beds == 'beds2') {
            $bedsWhere = array('=', '2');
        } elseif ($beds == 'beds3') {
            $bedsWhere = array('=', '3');
        } elseif ($beds == 'beds3p') {
            $bedsWhere = array('>', '3');
        } else {
            $bedsWhere = $beds;
        }

        $bccondos_agents = array();
        $bccondos_agents_detail = array();
        
        $res = DB::select("select * from bccondosandhomes.team_members where mls_active = '1' and mlsid is not null and mlsid != ''");
        foreach($res as $ag) {
            $bccondos_agents[] = $ag->mlsid;
            $bccondos_agents_detail[] = $ag;
        }

        $building = Buildings::where('import_id', $id)->first();
        $listings = $building->active_listings($bedsWhere)->get();

        // naive-echo-response replaced [djLogic]:
        $resp4mview = view('frontend.components.active_listings_table_tbody',array_merge(['active_listings'=>$listings,'building'=>$building], compact('bccondos_agents') ));
        return $resp4mview;
    }

    public function showAllBuildings(Request $request=null)
    {

        $sql = "select buildings.*, building_matterports.matterport_url, building_matterports.id from pixilink_mlsr.buildings
        join pixilink_360.building_matterports on (buildings.strata_no like building_matterports.strata_no and buildings.street_no = building_matterports.street_no)  where buildings.slug is not null and buildings.slug!='' group by CONCAT(buildings.strata_no, buildings.street_no)";

        $buildings = DB::connection($this->connection_360)
            ->select($sql);

        // $buildingsGrouped_by_titleToLand = $this->getBuildingsGrouped_by_titleToLand();

        // return view('frontend.all_buildings')->with([
        return view('frontend.city_buildings')->with([
            'buildings' => $buildings,
            'city' => null,
            'buildingsGrouped_by_titleToLand' => $this->getBuildingsGrouped_by_titleToLand(),
            // 'cityBuildingsGrouped_by_titleToLand' =>  $this->getBuildingsGrouped_by_titleToLand($city),
        ]);
    }



    /**
     * getBuildingsGrouped_by_titleToLand new-functionality--for-filtered-views below-functions-added-July-2021
     * @param  [type] $city [description]
     * @return [type]       [description]
     */
    public function getBuildingsGrouped_by_titleToLand($city=null, Request $request=null)
    {
        $sql = "SELECT b.title_to_land, COUNT(*) AS `count` FROM pixilink_mlsr.buildings `b` WHERE b.title_to_land IS NOT NULL AND b.title_to_land !='' ";
        if(!empty($city)){
            $sql .= " AND b.city='{$city}' ";
        }
        $sql .= " GROUP BY b.title_to_land ";

        $buildingsGrouped_by_titleToLand = DB::connection($this->connection_360)->select($sql);
        return $buildingsGrouped_by_titleToLand;
    }

    public function city_buildings($city, $subarea=null, Request $request=null)
    {
        // \Config::set(['app.debug'=>true]);
        $city = str_replace(array('-', '~'), array(' ', '-'), stripslashes($city??''));
        $city = trim(ucwords($city));

        $sql = "select buildings.* from pixilink_mlsr.buildings where city = '" . $city . "' "." AND buildings.strata_no IS NOT NULL AND buildings.strata_no!='' ";
        // strata-no-blank added on [09-09-2021], because strata-blank-urls show 404 error.
        if(!empty( trim($subarea) )){
            $subarea = str_replace('-', '_', trim($subarea??'') );
            $sql .= " AND `subarea` LIKE '". $subarea ."' ";
        }
        if(request()->input('filter_titletoland','')!=''){
            $sql .= " AND `title_to_land`='".urldecode(request()->input('filter_titletoland',''))."'  ";
        }
        $sql .= " group by CONCAT(buildings.strata_no, buildings.street_no)";

        $buildings = DB::connection($this->connection_360)->select($sql);

        
        // $buildings = DB::table('buildings')->select('*')->where('city',$city);
        // \Config::set(['app.debug'=>true]);
        // $buildings = Buildings::where('city',$city)->whereNotNull('strata_no')->where('strata_no','!=','');
        // if(!empty( trim($subarea) )){
        //     $buildings = $buildings->where('subarea','LIKE', str_replace('-', '_', trim($subarea??'') ) );
        // }
        // if(request()->input('filter_titletoland','')!=''){
        //     $buildings = $buildings->where('title_to_land',urldecode(request()->input('filter_titletoland','')) );
        // }
        // $buildings = $buildings->groupBy('strata_no')/*->groupBy('street_no')->get()*/;
        

         
        /*// Filter-for--sub-category-id 
        if(request()->input('subcatid','')!=''){
            $buildings = $buildings->where('subcatid',request()->input('subcatid'));
        }*/
        if(request()->input('filter_titletoland','')!=''){
            // $buildings = DB::connection($this->connection_360)->select($sql)->where('city' , $request->route('city'));
            // ->where('buildings.title_to_land','=' , request()->input('filter_titletoland'));
            // dd($buildings);
        }

        $subareas = [];
        
        if(!empty($city)){
            $subareas = Buildings::where('city',trim($city))->whereNotNull('subarea')->where('subarea','!=','')->groupBy('subarea')->select('subarea')->selectRaw('COUNT(`subarea`) AS `subarea_count`')->orderByDesc('subarea_count')->take(50)->get(); //->toArray();
        }

        $data_array = [
                'buildings' => $buildings,
                'city' => $city,
                'subareas'=>$subareas,
                'buildingsGrouped_by_titleToLand' => $this->getBuildingsGrouped_by_titleToLand(),
                'cityBuildingsGrouped_by_titleToLand' =>  $this->getBuildingsGrouped_by_titleToLand($city),
            ];

        // FUB: track buildings-by-city page view for phone-verified logged-in users
        $fubBldgMsg = $city ? $city . ' buildings browse' : 'Buildings browse';
        FubAreaHelper::pushSearchEvent($fubBldgMsg, request()->fullUrl(), $city ?: null);

        return view('frontend.city_buildings')->with($data_array);

    }








    public function building_redirect($strata_no, Request $request=null)
    {
        $request = $request??request();

        $building = Buildings::where('strata_no', $strata_no);
        if($request->get('street_no')){
            $building = $building->where('street_no', $request->get('street_no'));
        }
        $building = $building->first();

        if($request->get('street_no') && !$building){
            $building = Buildings::where('strata_no', $strata_no)->first();
        }

        if ($building && $building->slug) {
            return redirect("https://www.bccondosandhomes.com/building/" . $building->slug);
        }
        return redirect("https://www.bccondosandhomes.com/");
    }

    public function get_building_url($strata_no, Request $request=null)
    {
        $building = Buildings::where('strata_no', $strata_no)->select('slug')->first();
        
        /**
         * [modified:19-05-2022] To eliminate SEO(s) from indexing [Avoid (Mobile-View)Text-too small/meta-viewport errors]
         */
        $responseTxt = "https://www.bccondosandhomes.com/building/" . ($building->slug ??'') ;
        return response($responseTxt)->header('X-Robots-Tag','noindex');
    }

    /**
     * getBuildingWithBccNetCondoIds useful-to-get-same_complex-buildings  created: 13-Aug-2021
     * @param  int|string|array $ids condos.id(s)-in-buildings.bcc_id(s)
     * @return collection      [colleciton of buildihngs]
     */
    public function getBuildingsWithBccNetCondoIds($ids)
    {
        $buildings = Buildings::whereIn('bcc_id',$ids)->get();
        return $buildings;
    }

    /**
     * getBcnBcchSlugLinkerRecordForBcchSlug  to-help api_building fetch bcn_gp_id-based instead of strataNo-based created: 10-09-2021
     * @param  string $slug the-bcch_slug
     * @return mixed       whole-table-row
     */
    public function getBcnBcchSlugLinkerRecordForBcchSlug($slug)
    {
        $sql = "SELECT * FROM pixilink_mlsr.bcn_bcch_sluglinker WHERE `bcn_count_id`='1' AND `bcch_gp_slug`='$slug' ORDER BY `updated_on` DESC LIMIT 1 ";
        $record = DB::connection('mysql_mlsr')->select($sql); //->first();
        return $record;
    }


    /**
     * getNearbyBuildings [created:03-05-2022] [updated:05-05-2022]
     * @param  Building $building [description]
     * @param  integer  $count    [description]
     * @return Array|null         [description]
     */
    public static function getNearbyBuildings($building, $count=30)
    {
        $latitude = (float)($building->latitude ?? 0.0);
        $longitude = (float)($building->longitude ?? 0.0);
        if(!$latitude || !$longitude){
            return null;
        }
        try{
            $buildings = Buildings::whereNotNull('slug')
            ->where('slug','!=',$building->slug)
            ->where('postalcode','!=','V0V 0V0')
            // ->where('postalcode','!=','')
            /* ->where('board','!=', 'Victoria Real Estate Board')
            ->where('city', '!=','Victoria') */ // /* [Disabled:2025-06-12] */
            ->select()
            ->addSelect(DB::raw(" SQRT(POW((latitude-" . $latitude . "),2)+POW((longitude-" . $longitude . "),2)) AS `distance`"))
            ->whereRaw("((`postalcode` = '" .$building->postalcode. "' ) OR ((`latitude` BETWEEN " . ($latitude - 0.0027) . " AND " . ($latitude + 0.0027) . ") and (`longitude` BETWEEN " . ($longitude - 0.0056) . " AND " . ($longitude + 0.0056) . ")) ) ")
            ->orderBy('distance')
            ->take($count);

            $buildings = $buildings->get()->unique('slug');
        }catch(Exception $exPtn){}

        return $buildings??null;
    }

    public function get_building_doc(Request $request=null)
    {
        $request = $request??request();

        $user = Auth::user();
        if($user && $user->isPremiumMember()){
            $doc_key = $request->get('doc_key');
            $url = urldecode(Helper::decryptURL($doc_key));
            $name = "document.pdf";
            $content = file_get_contents($url);
            header('Content-Type: application/pdf');
            header('Content-Length: '.strlen( $content ));
            header('Content-disposition: inline; filename="' . $name . '"');
            header('Cache-Control: public, must-revalidate, max-age=0');
            header('Pragma: public');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
            echo $content; 
        }
        else{
            return abort(403);
        }
    }

}
