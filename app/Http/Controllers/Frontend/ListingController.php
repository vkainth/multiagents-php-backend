<?php

namespace App\Http\Controllers\Frontend;

use App\Repository\ListingRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\FavoriteListings;
use Spatie\CalendarLinks\Link;
use Illuminate\Support\Carbon;
use App\Models\UserPropertyViews;
use App\Mail\NotifyAgentUserPropertyViews;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\EmailsSent;
use App\Models\UserAgents;
use App\Models\Agents;
use App\Models\Listings;
use JsValidator; // use Illuminate\Support\Facades\Validator as JsValidator; // use JsValidator;

use Illuminate\Support\Facades\Cache;
use App\Helpers\FubAreaHelper;


class ListingController extends Controller
{
    protected $listingRepo;
    public function __construct(ListingRepository $listingRepository)
    {
        $this->listingRepo  = $listingRepository;
    }

    public function showListingDetailPage($slug)
    {
        $request = request();
        $user = Auth::user();
        $ref = NULL;
        if ($request->get('ref')) {
            $ref = $request->get('ref');
        }
        $agent = Auth::user()->loginWithAgent()->first();
        if (!$agent) {
            $agent = Auth::user()->agent()->first();
        }
        $params1 = [
            'agentId' => $agent->vow_username,
            'slug' => $slug
        ];
        $params2 = $request->all();
        $params = array_merge($params1, $params2);
        return redirect(route('listing-detail-page2', $params));

        $listing = $this->listingRepo->getListingDetail($slug, $ref);
        if (!$listing) {
            abort(404);
        }

        if ($listing->status == 'Sold' && !$agent->isSoldAllowed() && $user->role != 'AGENT') {
            return redirect(route('dashboard'))->with('message', config('constants.no_sold_access_message'));
        }

        return view('frontend.listing_detail')->with([
            'listing' => $listing,
            'user' => $user
        ]);
    }

    public function showListingDetailPage2($agent_vowusername, $slug)
    {
        $request = request();
        $user = Auth::user();
        $ref = NULL;
        $favorite = false;
        if ($request->get('ref')) {
            $ref = $request->get('ref');
        }
        $agent = Auth::user()->loginWithAgent()->first();
        if (!$agent) {
            $agent = Auth::user()->agent()->first();
        }
        $listing = $this->listingRepo->getListingDetail($slug, $ref);
        if (!$listing) {
            abort(404);
        }

        if ($listing->status == 'Sold' && !$agent->isSoldAllowed() && $user->role != 'AGENT') {
            return redirect(route('dashboard'))->with('message', config('constants.no_sold_access_message'));
        }

        $checkFavorite = FavoriteListings::where('userid', $user->id)->where('listingid', $listing->listingid)->where('deleted', 0)->first();

        if ($checkFavorite) {
            $favorite = true;
        }

        $addToCal = null;

        if ($listing->open_house) {
            $open_house_detail = $listing->get_open_house();
            if ($open_house_detail) {

                $from = Carbon::parse($open_house_detail->start);
                $to = Carbon::parse($open_house_detail->finish);

                $link = Link::create('Openhouse: ' . $listing->streetaddress . ', ' . $listing->city, $from, $to);
                $addToCal = $link->google();
            }
        }

        if ($user->role != 'AGENT') {

            $view_count = $this->getPropertyViewCount($user, $listing->listingid);

            if ($view_count >= 5) {
                $mail = new NotifyAgentUserPropertyViews($user, $listing->listingid);
                $primaryAgentRecord = UserAgents::where('user_id', $user->id)->where('primary_agent', 'y')->first();
                if ($primaryAgentRecord && $primaryAgentRecord->agent()) {
                    $primaryAgent = $primaryAgentRecord->agent()->first();
                } else {
                    $primaryAgent = Agents::where('agent_id', $user->agent)->first();
                }
                $agent = $primaryAgent;
                if ($agent->agent_id != 4343) {  // temp to stop emailing to Cindy Stanley
                    Mail::to($agent->email)->queue($mail);
                    EmailsSent::create([
                        'userid' => $agent->agent_id,
                        'email' => $agent->email,
                        'user_role' => 'AGENT',
                        'email_type' => 'user_interest_alert',
                        'content' => $mail->render()
                    ]);

                    UserPropertyViews::where('userid', $user->id)->where('mls', $listing->listingid)->update([
                        'agent_notified' => 1,
                        'agent_notification_time' => Carbon::now()
                    ]);
                }
            }
        }

        return view('frontend.listing_detail')->with([
            'listing' => $listing,
            'user' => $user,
            'favorite' => $favorite,
            'addToCal' => $addToCal
        ]);
    }

    /**
     * Main function used id routes.web >> for listing-detail-page [since 2022]
     */
    public function showListingDetailPage3($slug)
    {
        $request = request();

        $listing = $this->listingRepo->getListingDetail($slug);

        // Detect bots early — used throughout this method to bypass auth gates and redirects
        $_isBot = false;
        try { $_isBot = \Browser::isBot(); } catch (\Throwable $e) {}

        if (!$listing) {
            /* block-added on:28-10-2021 [STARTS] */
            $_listing_notInActiveSold = Listings::where('slug',$slug)->first();
            if(!$_listing_notInActiveSold){
                /* block-added:08-12-2022 [STARTS] */
                $_listing_listingidInsteadOfSlug = Listings::where('listingid',$slug)->first();
                if($_listing_listingidInsteadOfSlug){
                    return redirect()->route('listing-detail-page2',['slug'=>$_listing_listingidInsteadOfSlug->slug]);
                }
                /* block-added:08-12-2022 [ENDS] */

                abort(404);
            }

            if( in_array($_listing_notInActiveSold->status, ['Terminated','Expired']) ){
                // Check for an active listing at the same address and redirect to it (301)
                // Bots bypass this redirect so Google sees the terminated/expired page content
                $activeQuery = Listings::where('status', 'Active')
                    ->where('street_number', $_listing_notInActiveSold->street_number)
                    ->where('street_name', $_listing_notInActiveSold->street_name)
                    ->where('city', $_listing_notInActiveSold->city);
                if ($_listing_notInActiveSold->suite_no) {
                    // Condo/unit: try exact suite first, then fall back to any active unit at same building address
                    $activeMatch = (clone $activeQuery)->where('suite_no', $_listing_notInActiveSold->suite_no)->first();
                    if (!$activeMatch) {
                        $activeMatch = $activeQuery->first();
                    }
                } else {
                    $activeMatch = $activeQuery->first();
                }
                if ($activeMatch && !$_isBot) {
                    return redirect()->route('listing-detail-page2', ['slug' => $activeMatch->slug], 301);
                }
                // No active listing found — show guest view without "Terminated"/"Expired" label
                // return view('frontend.error_style_page',['code'=>'Unavailable','message'=>'Listing '.$_listing_notInActiveSold->status.'!']);
                $listing = $_listing_notInActiveSold; // [re-enabled:2024-12-05]
                // response('Listing unavailable! ');//.$_listing_notInActiveSold->status , 200)->header('Content-Type', 'text/plain');
            }else{
                abort(404);
            }
            /* block-added on:28-10-2021 [ENDS] */
        }
        /*
        if($listing->status=='Sold' && empty(Auth::user())){
            return redirect('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]), 301);
        }
        */

        // Save area tag to session for any visitor (including guests) so the
        // FUB registration push includes the tag when they sign up from this page.
        FubAreaHelper::saveToSession($listing->city);

        $contact_us_validation = [
            'fullname'=> 'required|string|max:40',
            'emailaddress' => 'required|email|max:50',
            'phonenumber' => 'required',
            'message'=>'required',
            'agent-check-contactus' => 'required'
        ];

        $contact_us_validation_messages = [
            'fullname.required'=> 'Name is required',
            'emailaddress.required' => 'Email address is required',
            'emailaddress.email' => 'Invalid email address',
            'phonenumber.required' => 'Phone number is required',
            'agent-check-contactus.required' => 'This field is required',
            'message.required'=>'Message is required'
        ];

        $book_a_viewing_validation = [
            'firstname' => 'required|string|max:20',
            'lastname' => 'required|string|max:20',
            'emailaddress' => 'required|email|max:50',
            'phonenumber' => 'required',
            'agent-check' => 'required',
            'approved-check' => 'required',
            'dateone' => 'required',
            'timeone' => 'required'
        ];

        $book_a_viewing_validation_messages = [
            'firstname.required' => 'First Name is required',
            'lastname.required' => 'Last Name is required',
            'emailaddress.required' => 'Email address is required',
            'emailaddress.email' => 'Invalid email address',
            'phonenumber.required' => 'Phone number is required',
            'agent-check.required' => 'This field is required',
            'approved-check.required' => 'This field is required',
            'dateone.required' => 'Date is required',
            'timeone.required' => 'Time is required'
        ];

        $validator = JsValidator::make($book_a_viewing_validation, $book_a_viewing_validation_messages);
        $contactus_validator = JsValidator::make($contact_us_validation, $contact_us_validation_messages);

        $addToCal = null;

        if ($listing->open_house) {
            $open_house_detail = $listing->get_open_house();
            if ($open_house_detail) {

                $from = Carbon::parse($open_house_detail->start);
                $to = Carbon::parse($open_house_detail->finish);

                $link = Link::create('Openhouse: ' . $listing->streetaddress . ', ' . $listing->city, $from, $to);
                $addToCal = $link->google();
            }
        }

        $samecity_latest_active = array();
        $similar_active = array();
        $similar_sold = array();
        $samecity_similar_listings = array();
        $subarea_slug = '';
        $is_featured = false;

        $sql = "select mlsid from bccondosandhomes.team_members where mls_active = '1'";
        $res =  DB::select($sql);
        if ($res && count($res)) {
            foreach($res as $mls){
                if(strtolower(trim($mls->mlsid)) == strtolower(trim($listing->agent_id))){
                    $is_featured = true;
                }
            }
        }

        if ($listing->type == 'House' || $listing->type == 'Townhouse' || $listing->type == 'Apartment' || $listing->type == 'Duplex') {
            $samecity_latest_active = $this->get_samecity_latest_active_listings($listing);
            $similar_active = $this->get_similar_active_listings($listing);
            $similar_sold = $this->get_similar_sold_listings($listing);
            $samecity_similar_listings = $this->get_samecity_similar_listings($listing);
            $sql = 'select slug from mls_query where city = "' . $listing->city . '" and type = "' . $listing->type . '" limit 1';
            $res =  DB::select($sql);
            if ($res && count($res)) {
                $sl = $res[0]->slug;
                $subarea_slug = $sl . "-for-sale-" . strtolower(str_replace(' ', '-', $listing->subarea));
            }
        }

        $server_up = 'n';

        $sql = 'select up from bccondosandhomes.api_server_status where server = "bccondos.net"';
        $res =  DB::select($sql);
        if ($res && count($res)) {
            $server_up = $res[0]->up;
        }

        $house_description = '';

        $sql = "select * from pixilink_360.houses where mls= '".$listing->listingid."' limit 1";
        $res =  DB::select($sql);
        if ($res && count($res)) {
            $house_description = $res[0]->description;
        }

        $neighbourhoodData = [];
        $nhSellerStreak = 0;
        if($listing->subarea && $listing->city && $listing->type) {
            $nhType = $listing->getType();
            $hasNeighbourhoodStats = $listing->showNeighbourhoodStatsButton();

            // Cache all 3 DB queries together — TTL 1800s (matches market-stats page)
            $_nhDataCacheKey = 'nh_data_' . md5($listing->subarea . '_' . $listing->city . '_' . $listing->type);
            $_nhCached = Cache::remember($_nhDataCacheKey, 1800, function() use ($listing, $nhType) {
                $_nhActiveCount = Listings::where('table', 'mlsr_listings')
                    ->where('status', 'Active')
                    ->where('subarea', $listing->subarea)
                    ->where('city', $listing->city)
                    ->where('type', $listing->type)
                    ->count();

                $nhSql = "SELECT 
                    DATE_FORMAT(sold_date, '%Y-%m') as month_label,
                    COUNT(*) as sold_count,
                    ROUND(AVG(soldprice_2)) as avg_price,
                    ROUND(AVG(DATEDIFF(sold_date, list_date))) as avg_dom,
                    ROUND(AVG(CASE WHEN livingarea_2 > 0 THEN soldprice_2/livingarea_2 ELSE NULL END)) as avg_ppsf
                    FROM listings 
                    WHERE `table` = 'mlsr_listings' 
                    AND status = 'Sold' 
                    AND subarea = ? 
                    AND city = ? 
                    AND type = ? 
                    AND sold_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
                    AND soldprice_2 > 0 
                    GROUP BY DATE_FORMAT(sold_date, '%Y-%m') 
                    ORDER BY month_label ASC";
                $nhRes = DB::connection('mysql_boards')->select($nhSql, [$listing->subarea, $listing->city, $listing->type]);

                // Widget: all residential types (no type filter), outer WHERE bounded to 90 days for sold.
                // Matches get_market_summary() default scope → numbers align with /market-stats/ page.
                $nhWidgetSql = "SELECT
                    SUM(LOWER(`status`) = 'active') AS current_active,
                    SUM(LOWER(`status`) = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
                    ROUND(AVG(IF(LOWER(`status`)='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
                    ROUND(AVG(IF(LOWER(`status`)='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_90d
                    FROM listings
                    WHERE `table` = 'mlsr_listings'
                    AND subarea = ?
                    AND city = ?
                    AND type IN ('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')
                    AND (LOWER(`status`) = 'active'
                         OR (LOWER(`status`) = 'sold' AND sold_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)))";
                $nhWidgetRow = DB::connection('mysql_boards')->select($nhWidgetSql, [$listing->subarea, $listing->city]);
                $nhW         = !empty($nhWidgetRow) ? $nhWidgetRow[0] : null;
                $nhWActive   = (int)($nhW->current_active ?? 0);
                $nhWSold30d  = (int)($nhW->sold_30d      ?? 0);
                $nhWAvgDom   = (int)($nhW->avg_dom_90d   ?? 0);
                $nhWAvgPrice = (int)($nhW->avg_sold_30d  ?? 0);
                $nhWAbsRate  = $nhWActive > 0 && $nhWSold30d > 0
                    ? round(($nhWSold30d / $nhWActive) * 100, 1) : 0;
                $nhWMktType  = ($nhWAbsRate > 25 && $nhWAvgDom > 0 && $nhWAvgDom < 25) ? 'strong-sellers'
                    : ($nhWAbsRate >= 20 ? 'sellers'
                    : ($nhWAbsRate >= 12 ? 'balanced' : 'buyers'));

                $nhTotalSold = 0;
                $nhTotalPrice = 0;
                $nhTotalDom = 0;
                $nhTotalPpsf = 0;
                if($nhRes && count($nhRes)) {
                    foreach($nhRes as $row) {
                        $nhTotalSold += $row->sold_count;
                        $nhTotalPrice += ($row->avg_price * $row->sold_count);
                        if($row->avg_dom) { $nhTotalDom += ($row->avg_dom * $row->sold_count); }
                        if($row->avg_ppsf) { $nhTotalPpsf += ($row->avg_ppsf * $row->sold_count); }
                    }
                }

                $_nhData   = [];
                $_nhStreak = 0;
                if($nhTotalSold > 0 || $_nhActiveCount > 0) {
                    $_nhData = [
                        'months'     => $nhRes ?: [],
                        'total_sold' => $nhTotalSold,
                        'avg_price'  => $nhTotalSold > 0 ? round($nhTotalPrice / $nhTotalSold) : 0,
                        'avg_dom'    => $nhTotalSold > 0 ? round($nhTotalDom / $nhTotalSold) : 0,
                        'avg_ppsf'   => $nhTotalSold > 0 ? round($nhTotalPpsf / $nhTotalSold) : 0,
                        'active_count'   => $_nhActiveCount,
                        'has_stats_page' => false,
                        'subarea'        => $listing->subarea,
                        'city'           => $listing->city,
                        'type'           => $nhType,
                        'widget_active'    => $nhWActive,
                        'widget_sold_30d'  => $nhWSold30d,
                        'widget_avg_dom'   => $nhWAvgDom,
                        'widget_avg_price' => $nhWAvgPrice,
                        'widget_abs_rate'  => $nhWAbsRate,
                        'widget_mkt_type'  => $nhWMktType,
                    ];
                    if (!empty($nhRes) && $nhWActive > 0 && !empty($nhWMktType)) {
                        foreach (array_reverse((array) $nhRes) as $_mo) {
                            $_moAbs  = $nhWActive > 0 ? round(($_mo->sold_count / $nhWActive) * 100, 1) : 0;
                            $_moDom  = (int)($_mo->avg_dom ?? 0);
                            $_moType = ($_moAbs > 25 && $_moDom > 0 && $_moDom < 25) ? 'strong-sellers'
                                : ($_moAbs >= 20 ? 'sellers'
                                : ($_moAbs >= 12 ? 'balanced' : 'buyers'));
                            if ($_moType === $nhWMktType) { $_nhStreak++; } else { break; }
                        }
                    }
                }
                return ['data' => $_nhData, 'streak' => $_nhStreak];
            });
            $neighbourhoodData = $_nhCached['data'];
            $nhSellerStreak    = $_nhCached['streak'];
            // has_stats_page is listing-specific — set after cache to avoid cross-listing pollution
            if (!empty($neighbourhoodData)) {
                $neighbourhoodData['has_stats_page'] = $hasNeighbourhoodStats;
            }
        }

        // Count distinct registered buyers who viewed any listing or building in this subarea/city
        // TTL = 86400 (24 hrs): cumulative historical count — only grows, query is expensive cross-connection union
        $neighbourhoodBuyersCount = 0;
        if (!empty($neighbourhoodData)) {
            $_nhCacheKey = 'nh_buyers_' . md5($listing->subarea . '_' . $listing->city . '_' . $listing->type);
            $neighbourhoodBuyersCount = Cache::remember($_nhCacheKey, 86400, function () use ($listing) {
                // Step 1: listing IDs in this subarea/city/type from MLS connection
                $_nhListingIds = DB::connection('mysql_boards')
                    ->table('listings')
                    ->where('table', 'mlsr_listings')
                    ->where('subarea', $listing->subarea)
                    ->where('city', $listing->city)
                    ->where('type', $listing->type)
                    ->pluck('listingid')
                    ->toArray();

                // Step 2: building IDs in this subarea/city from MLSR connection
                $_nhBuildingIds = DB::connection('mysql_mlsr')
                    ->table('buildings')
                    ->where('subarea', $listing->subarea)
                    ->where('city', $listing->city)
                    ->pluck('id')
                    ->toArray();

                // Step 3: union distinct buyers across property views + building views
                if (!empty($_nhListingIds) && !empty($_nhBuildingIds)) {
                    $_mlsPh  = implode(',', array_fill(0, count($_nhListingIds), '?'));
                    $_bldPh  = implode(',', array_fill(0, count($_nhBuildingIds), '?'));
                    $_sql    = "SELECT COUNT(DISTINCT userid) as cnt FROM (
                        SELECT userid FROM user_property_views WHERE mls IN ({$_mlsPh})
                        UNION
                        SELECT userid FROM user_building_views WHERE building_id IN ({$_bldPh})
                    ) AS combined_buyers";
                    $_result = DB::select($_sql, array_merge($_nhListingIds, array_map('intval', $_nhBuildingIds)));
                    return (int) ($_result[0]->cnt ?? 0);
                }
                if (!empty($_nhListingIds)) {
                    return (int) DB::table('user_property_views')
                        ->whereIn('mls', $_nhListingIds)
                        ->distinct('userid')->count('userid');
                }
                if (!empty($_nhBuildingIds)) {
                    return (int) DB::table('user_building_views')
                        ->whereIn('building_id', $_nhBuildingIds)
                        ->distinct('userid')->count('userid');
                }
                return 0;
            });
        }

        /** deciding-view-blade [25-10-2022] */
        // $_isBot already set at top of method
        $_viewBlade = request()->input('expid','bad-default')=='239487982t3kjsydgfiuw32476dfsg'?'frontend.listing_detail_sold_guest':'frontend.listing_detail';
        if(in_array($listing->status,['Sold','Terminated','Expired']) && empty(Auth::user()) && !$is_featured && !$_isBot){
            $_viewBlade = 'frontend.listing_detail_sold_guest'; // [published:03-11-2022]
        }else{}

        if($listing->status=='Sold' && Auth::user() && !(Auth::user()->isPremiumMember()) && !$is_featured && !$_isBot){
            $currentUrl = request()->fullUrl();
            $_u = Auth::user();
            if($_u->phone_verified && $_u->agreedToTerms){
                return redirect(route('subscription_pricing_table', ['redirect' => $currentUrl]));
            }
            return redirect(url('/complete-profile') . '?' . http_build_query(['redirect' => $currentUrl]));
        }

        // Prevent server-side (LiteSpeed/Cloudflare) page caching for sold/expired/terminated
        // listings — page content varies by auth state, caching the full template for a premium
        // user's session would serve it to all unauthenticated visitors [added: 2025-04]
        if(in_array($listing->status,['Sold','Terminated','Expired'])){
            header('Cache-Control: no-store, no-cache, must-revalidate, private');
            header('X-LiteSpeed-Cache-Control: no-cache');
            header('Pragma: no-cache');
        }

        $canonicalListing = $listing->getCanonicalListing();
        $canonicalUrl = 'https://www.bccondosandhomes.com/listing/' . $canonicalListing->slug;

        if (Auth::user()) {
            $user = Auth::user();
            $ref = NULL;
            $favorite = false;
            if ($request->get('ref')) {
                $ref = $request->get('ref');
            }

            $checkFavorite = FavoriteListings::where('userid', $user->id)->where('listingid', $listing->listingid)->where('deleted', 0)->first();

            if ($checkFavorite) {
                $favorite = true;
                $favorite_tracked = $checkFavorite->tracked;
            }
            $userIsWatching = $checkFavorite
                ? (bool)(($checkFavorite->watch_price_drop ?? false) || ($checkFavorite->watch_sold ?? false))
                : false;

            $wwr_popup = false;

            $sql = "select * from bccondosandhomes.working_with_realtor where email like '".$user->email."' and (done != 1 OR done is null) limit 1";
            $res =  DB::select($sql);
            if ($res && count($res)) {
                $wwr_popup = 1;
            }

            return view($_viewBlade)->with([
                'listing' => $listing,
                'user' => $user,
                'favorite' => $favorite,
                'favorite_tracked' => $favorite_tracked??false,
                'userIsWatching' => $userIsWatching ?? false,
                'addToCal' => $addToCal,
                'validator' => $validator,
                'contactus_validator' =>$contactus_validator,
                'samecity_latest_active'=>$samecity_latest_active,
                'similar_active' => $similar_active,
                'similar_sold' => $similar_sold,
                'subarea_slug' => $subarea_slug,
                'server_up' => $server_up,
                'house_description'=>$house_description,
                'wwr_popup' =>$wwr_popup,
                'is_featured'=>$is_featured,
                'samecity_similar_listings' => $samecity_similar_listings,
                'is_authenticated' => true,
                'neighbourhoodData' => $neighbourhoodData,
                'neighbourhoodBuyersCount' => $neighbourhoodBuyersCount,
                'neighbourhoodSellerStreak' => $nhSellerStreak,
                'canonicalUrl' => $canonicalUrl,
            ]);
        } else {

            $is_authenticated = false;
            $user = false;
            if (Auth::user()) {
                $user = Auth::user();
                if ($user->role == 'AGENT') {
                    $is_authenticated = true;
                } elseif ($user->phone) {
                    $is_authenticated = true;
                }
            }



            // return view('frontend.listing_detail')->with([
            // following- conditional-view -for-testing-only[22-10-2021] (safe to delete after publishing(ie: replacing listing_detail with listing_detail_seo-code))
            return view($_viewBlade)->with([
                'listing' => $listing,
                'addToCal' => $addToCal,
                'is_authenticated' => $is_authenticated,
                'validator' => $validator,
                'contactus_validator' =>$contactus_validator,
                'samecity_latest_active'=>$samecity_latest_active,
                'similar_active' => $similar_active,
                'similar_sold' => $similar_sold,
                'subarea_slug' => $subarea_slug,
                'server_up' => $server_up,
                'house_description'=>$house_description,
                'wwr_popup'=>false,
                'is_featured'=>$is_featured,
                'neighbourhoodData' => $neighbourhoodData,
                'neighbourhoodBuyersCount' => $neighbourhoodBuyersCount,
                'neighbourhoodSellerStreak' => $nhSellerStreak,
                'canonicalUrl' => $canonicalUrl,
            ]);
        }
    }





    public function updateWwr(){
        if(Auth::user()){
            $user = Auth::user();
            $email = $user->email;
            $sql = "update bccondosandhomes.working_with_realtor set done = 1 where email = '".$email."'";
            DB::statement($sql);
        }
    }


    public function get_samecity_similar_listings($listing,$limit=10){
        return [];
        if(empty($listing)){
            return [];
        }

        $listings = array();

        $listings = Listings::/*with('aphoto')->*/where('table', 'mlsr_listings')->where('status', 'Active')
            ->where('city', $listing->city)
            ->where('bedrooms', $listing->bedrooms)
            ->where('bathstotal', $listing->bathstotal)
            ->where('listingid', '!=', $listing->listingid)
            ->where('type', $listing->type)
            ->where('yearbuilt','>=', ($listing->yearbuilt - 10))
            ->where('yearbuilt','<=', ($listing->yearbuilt + 10))
            ->orderBy('list_date','desc')->orderBy('inserted','desc')
            ->orderByRaw("ABS(yearbuilt - YEAR(CURDATE()))")
            ->limit($limit)->get();

        return $listings;

    }

    /**
     * get_samecity_latest_active_listings [created:14-01-2022]
     * @param  [type] $listing for-listing.city
     * @param  int $limit   max-number of expected-records
     * @return array          [array of listings]
     */
    public function get_samecity_latest_active_listings($listing,$limit=10)
    {
        return [];
        if(empty($listing)){
            return [];
        }
        $listingsCached = Cache::get( strtolower('sameCityLtstActv__'.$listing->city) );
        if(!empty($listingsCached)){
            // return $listingsCached;
        }
        $listings = Listings::/*with('aphoto')->*/where('table', 'mlsr_listings')->where('status', 'Active')
                    ->where('city', $listing->city)
                    ->where('subarea', $listing->subarea)
                    ->where('listingid', '!=', $listing->listingid)
                    ->orderBy('list_date','desc')->orderBy('inserted','desc')->limit($limit)->get();

        Cache::put( strtolower('sameCityLtstActv__'.$listing->city), $listings, 60*24 );
        return $listings;
    }

    public function get_similar_active_listings($listing)
    {
        return [];
        $listingprice = $listing->listprice_2;
        $diff = ($listingprice * 15) / 100;
        $min_price = $listingprice - $diff;
        $max_price = $listingprice + $diff;
        
        if($listing->type == "Apartment" || $listing->type =="Townhouse"){
             $min_price = $listingprice - 25000;
             $max_price = $listingprice + 25000;
        }
        
        if($listing->type == "Apartment" || $listing->type =="Townhouse"){
            $listingsCached = Cache::get( str_replace(' ','',strtolower('smlrLsActvs__'.$listing->city.$listing->type.$listing->subarea.$min_price.'_'.$max_price.'_'.$listing->bedrooms."_".$listing->bathstotal) ));
            if(!empty($listingsCached)){
                return $listingsCached
                ->random(min($listingsCached->count(),10))
                ->reject(function($i) use ($listing){return $i->listingid==$listing->listingid;})
                ;
            }
        }
        else{
            $listingsCached = Cache::get( str_replace(' ','',strtolower('smlrLsActvs__'.$listing->city.$listing->type.$listing->subarea.$min_price.'_'.$max_price) ));
            if(!empty($listingsCached)){
                return $listingsCached
                ->random(min($listingsCached->count(),10))
                ->reject(function($i) use ($listing){return $i->listingid==$listing->listingid;})
                ;
            }
        }
        
        

        if($listing->type == "Apartment" || $listing->type =="Townhouse"){
            $listings = Listings::/*with('aphoto')->*/where('table', 'mlsr_listings')->where('status', 'Active')->where('type', $listing->type)->where('city', $listing->city)->where('subarea', $listing->subarea)
            ->where('listprice_2', '>=', $min_price)->where('listprice_2', '<=', $max_price)->where('bedrooms', '=', ($listing->bedrooms))->where('bathstotal', '=', $listing->bathstotal)
            // ->where('listingid', '!=', $listing->listingid) // [disabled:30-06-2022 for caching]
            ->inRandomOrder()->limit(50)->get();

            Cache::put( str_replace(' ','',strtolower('smlrLsActvs__'.$listing->city.$listing->type.$listing->subarea.$min_price.'_'.$max_price.'_'.$listing->bedrooms."_".$listing->bathstotal)), $listings, 60*24 );
        }
        else{
            $listings = Listings::/*with('aphoto')->*/where('table', 'mlsr_listings')->where('status', 'Active')->where('type', $listing->type)->where('city', $listing->city)->where('subarea', $listing->subarea)
            ->where('listprice_2', '>=', $min_price)->where('listprice_2', '<=', $max_price)->where('bedrooms', '>=', ($listing->bedrooms))
            // ->where('listingid', '!=', $listing->listingid) // [disabled:30-06-2022 for caching]
            ->inRandomOrder()->limit(50)->get();

            Cache::put( str_replace(' ','',strtolower('smlrLsActvs__'.$listing->city.$listing->type.$listing->subarea.$min_price.'_'.$max_price)), $listings, 60*24 );
        }
        
        return $listings
        ->random(min(10,$listings->count())) // -($listing->status=='Active'?1:0))) // [random moved up updated:11-07-2022]
        ->reject(function($i) use ($listing){return $i->listingid==$listing->listingid;})
        ;
    }

    public function get_similar_sold_listings($listing)
    {
        return [];
        $listingsCached = Cache::get( str_replace(' ','',strtolower('smlrLsSlds__'.$listing->city.$listing->type.$listing->subarea) ));
        if(!empty($listingsCached)){
            return $listingsCached
            ->random(min($listingsCached->count(),10))
            ->reject(function($i) use ($listing){return $i->listingid==$listing->listingid;})
            ;
        }

        $listings = Listings::/*with('aphoto')->*/where('table', 'mlsr_listings')->where('status', 'Sold')->where('type', $listing->type)->where('city', $listing->city)->where('subarea', $listing->subarea)
            ->where('sold_date', '>', Carbon::now()->subMonths(6))
            // ->where('listingid', '!=', $listing->listingid) // [disabled:30-06-2022 for caching]
            ->orderBy('sold_date', 'desc')->limit(50)->get();
        
        Cache::put( str_replace(' ','',strtolower('smlrLsSlds__'.$listing->city.$listing->type.$listing->subarea)), $listings, 60*24 );
        return $listings
        ->random(min(10,$listings->count())) // -($listing->status=='Sold'?1:0))) // [random moved up updated:11-07-2022]
        ->reject(function($i) use ($listing){return $i->listingid==$listing->listingid;})
        ;
    }

    public function showListingDetailPage4($agent_vowusername, $slug)
    {
        $request = request();
        $listing = $this->listingRepo->getListingDetail($slug);
        if (!$listing) {
            abort(404);
        }

        $addToCal = null;

        if ($listing->open_house) {
            $open_house_detail = $listing->get_open_house();
            if ($open_house_detail) {

                $from = Carbon::parse($open_house_detail->start);
                $to = Carbon::parse($open_house_detail->finish);

                $link = Link::create('Openhouse: ' . $listing->streetaddress . ', ' . $listing->city, $from, $to);
                $addToCal = $link->google();
            }
        }

        if (Auth::user()) {
            $user = Auth::user();
            $ref = NULL;
            $favorite = false;
            if ($request->get('ref')) {
                $ref = $request->get('ref');
            }
            $agent = Auth::user()->loginWithAgent()->first();
            if (!$agent) {
                $agent = Auth::user()->agent()->first();
            }

            if ($listing->status == 'Sold' && !$agent->isSoldAllowed() && $user->role != 'AGENT') {
                return redirect(route('dashboard'))->with('message', config('constants.no_sold_access_message'));
            }

            $checkFavorite = FavoriteListings::where('userid', $user->id)->where('listingid', $listing->listingid)->where('deleted', 0)->first();

            if ($checkFavorite) {
                $favorite = true;
            }

            if ($user->role != 'AGENT') {

                $view_count = $this->getPropertyViewCount($user, $listing->listingid);

                if ($view_count >= 5) {
                    $mail = new NotifyAgentUserPropertyViews($user, $listing->listingid);
                    $primaryAgentRecord = UserAgents::where('user_id', $user->id)->where('primary_agent', 'y')->first();
                    if ($primaryAgentRecord && $primaryAgentRecord->agent()) {
                        $primaryAgent = $primaryAgentRecord->agent()->first();
                    } else {
                        $primaryAgent = Agents::where('agent_id', $user->agent)->first();
                    }
                    $agent = $primaryAgent;
                    if ($agent->agent_id != 4343) {  // temp to stop emailing to Cindy Stanley
                        Mail::to($agent->email)->queue($mail);
                        EmailsSent::create([
                            'userid' => $agent->agent_id,
                            'email' => $agent->email,
                            'user_role' => 'AGENT',
                            'email_type' => 'user_interest_alert',
                            'content' => $mail->render()
                        ]);

                        UserPropertyViews::where('userid', $user->id)->where('mls', $listing->listingid)->update([
                            'agent_notified' => 1,
                            'agent_notification_time' => Carbon::now()
                        ]);
                    }
                }
            }

            return view('frontend.listing_detail2')->with([
                'listing' => $listing,
                'user' => $user,
                'favorite' => $favorite,
                'addToCal' => $addToCal
            ]);
        } else {
            $agent = Agents::where('vow_username', $agent_vowusername)->where('fisherly_disable', 0)->where(function ($query) {
                $query->where(function ($q1) {
                    $q1->where("agent_id", config('constants.demo_agent_id'));
                })->orWhere(function ($q) {
                    $q
                        ->where('activated', 'Y')->where('suspended', 'n')
                        ->whereNotNull('mlsID')
                        ->where('mlsID', '!=', '')
                        ->whereIn('board', array(1, 9, 10));
                });
            })->first();

            if (!$agent) {
                abort(404);
            }

            if ($listing->status == 'Sold') {
                return redirect(route('login.with.agent', ['agentId' => $agent_vowusername, 'listingid' => $listing->listingid]));
            }

            $is_authenticated = false;
            $user = false;
            if (Auth::user()) {
                $user = Auth::user();
                // if ($user->role == 'AGENT') {
                //     $is_authenticated = true;
                // } elseif ($user->phone_verified) {
                //     $is_authenticated = true;
                // }
                if ($user->role == 'AGENT') {
                    $is_authenticated = true;
                } elseif ($user->phone) {
                    $is_authenticated = true;
                }
            }


            return view('frontend.listing_detail_public')->with([
                'listing' => $listing,
                'addToCal' => $addToCal,
                'agent' => $agent,
                'is_authenticated' => $is_authenticated
            ]);
        }
    }

    public function getPropertyViewCount($user, $listingid)
    {
        $agent_notified = UserPropertyViews::where('userid', $user->id)->where('mls', $listingid)->where('agent_notified', 1)->count();
        if ($agent_notified > 0) {
            return 0;
        } else {
            $query = "select FLOOR(UNIX_TIMESTAMP(created_at)/(15 * 60)) AS timekey FROM  user_property_views where userid = '" . $user->id . "' and mls='" . $listingid . "' GROUP BY timekey";
            $result = DB::select($query);
            return count($result);
        }
    }

    public function get_featured_listings()
    {

        //$sql = "select * from bccondosandhomes.team_members JOIN boards.listings ON bccondosandhomes.team_members.first + bccondosandhomes.team_members.last = boards.listings.agent_name where status = 'Active' and board IN ('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board')";

        //$sql = "select * from bccondosandhomes.team_members JOIN boards.listings ON bccondosandhomes.team_members.first + bccondosandhomes.team_members.last = boards.listings.agent_name where status = 'Active' and board IN ('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board') limit 50";

        //$listings = DB::select($sql);
        
        $mlsids = DB::table('bccondosandhomes.team_members')->select('mlsid')
            ->where("active",'1')
            ->whereNotNull('mlsid')->where('mlsid','!=','')
            ->pluck('mlsid')
            ->toArray()
            ;
        // $sql = "select mlsid from bccondosandhomes.team_members where mls_active = '1' and mlsid is not null and mlsid != ''";
        // $mlsids = collect(DB::select($sql))->pluck('mlsid')->toArray();
        
        // $mlsids1 = DB::select($sql);
        // $mlsids = array();
        // foreach($mlsids1 as $mlsid){
        //     $mlsids[] = $mlsid->mlsid;
        // }
        
        // $listings = Listings::with('aphoto')->where('table', 'mlsr_listings')->active()->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))
        // ->whereIn('agent_id', $mlsids)/*->orderBy('status','asc')*/->orderBy('list_date','desc')->get();
        
         $listings = Listings::with('aphoto')->where('table', 'mlsr_listings')->active()->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))
        ->whereIn('agent_id', $mlsids)->orderBy('list_date','desc')->get();
        
        

        return view('frontend.featured_listings')->with([
            'listings' => $listings
        ]);
    }

    public function get_oursolds_listings()
    {

        //$sql = "select * from bccondosandhomes.team_members JOIN boards.listings ON bccondosandhomes.team_members.first + bccondosandhomes.team_members.last = boards.listings.agent_name where status = 'Active' and board IN ('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board')";

        //$sql = "select * from bccondosandhomes.team_members JOIN boards.listings ON bccondosandhomes.team_members.first + bccondosandhomes.team_members.last = boards.listings.agent_name where status = 'Active' and board IN ('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board') limit 50";

        //$listings = DB::select($sql);
        
        $mlsids = DB::table('bccondosandhomes.team_members')->select('mlsid')
            ->where("active",'1')
            ->whereNotNull('mlsid')->where('mlsid','!=','')
            ->pluck('mlsid')
            ->toArray();

        $listings = Listings::with('aphoto')->where('table', 'mlsr_listings')->sold()->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('agent_id', $mlsids)/*->orderBy('status','asc')*/->orderBy('sold_date','desc')->get();

        return view('frontend.featured_listings')->with([
            'listings' => $listings
        ]);
    }
}
