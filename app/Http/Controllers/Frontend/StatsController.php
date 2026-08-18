<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use App\Repository\StatsRepository;
use App\Repository\ListingRepository;
use App\Models\Auth\FirebaseUser;
use App\Models\PropertiesEmailed;
use App\Repository\EmailRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Repository\ActivityRepository;
use App\Models\Places;
use App\Models\Buildings;
use App\Models\Listings;
use App\Helpers\Helper;

class StatsController extends Controller
{

    protected $statsRepo;
    protected $listingRepo;
    protected $activityRepo;
    protected $cities = ['Vancouver', 'North Vancouver', 'Burnaby', 'Richmond', 'Delta'];

    public function __construct(StatsRepository $statsRepo, ListingRepository $listingRepo, ActivityRepository $activityRepo)
    {
        $this->statsRepo = $statsRepo;
        $this->listingRepo = $listingRepo;
        $this->activityRepo = $activityRepo;
    }

    protected function genCacheKeyStr(){
        $args = func_get_args();
        return preg_replace('/\s+/', '_', join('_',$args) );
    } 

    public function getWeeklyStats()
    {
        $request = request();

        if ($request->get('period')) {
            $period = $request->get('period');
        } else {
            $period = "week";
        }

        if ($period == "year") {
            $firstDay =  CarbonImmutable::now()->subYear();
            $lastDay = CarbonImmutable::now();
        } elseif ($period == "15days") {
            $firstDay =  CarbonImmutable::now()->subDays(15);
            $lastDay = CarbonImmutable::now();
        } elseif ($period == "month") {
            $firstDay =  CarbonImmutable::now()->subMonth();
            $lastDay = CarbonImmutable::now();
        } elseif ($period == "6months") {
            $firstDay =  CarbonImmutable::now()->subMonths(6);
            $lastDay = CarbonImmutable::now();
        } else {
            $firstDay =  CarbonImmutable::now()->subWeek();
            $lastDay = CarbonImmutable::now();
        }

        $lastYearFirst = $firstDay->subYear();
        $lastYearLast = $lastDay->subYear();

        $type = "city";
        $city = NULL;
        $subarea = false;
        $filters = $request->all();
        if ($request->get('city')) {
            $city = $request->get('city');
            $type = "subarea";
            // $cities = $this->listingRepo->getSubareasOfCity($city);
            // $this->cities = $cities->pluck('place')->toArray();
            $subarea = true;
        }
        $propertyType = array();
        if ($request->get('type')) {
            $propertyType = $request->get('type');
        }

        $stats = $this->statsRepo->getStats($this->cities, $type, $city, $firstDay, $lastDay, $lastYearFirst, $lastYearLast, $propertyType);

        // $currentlyForSale = $this->statsRepo->getCurrentlyForSale($this->cities, $type, $city, $propertyType);
        // $otherStats = $this->statsRepo->getOtherStats($this->cities, $type, $firstDay, $lastDay, $city, $propertyType);
        // $otherStatsLastYear = $this->statsRepo->getOtherStats($this->cities, $type, $lastYearFirst, $lastYearLast, $city, $propertyType);
        // $currentWeekRecords = $this->statsRepo->getCurrentWeekRecords($this->cities, $type, $firstDayOfWeek, $lastDay, $city);
        // foreach($otherStats as $city=>$otherStat){
        //     $otherStats[$city]['forSale'] = $currentlyForSale['total'][$city];
        //     $otherStats[$city]['avg_listed_price'] = $currentlyForSale['avgPrice'][$city];
        // }
        return view('frontend.weeklystats')->with([
            'stats' => $stats,
            'subarea' => $subarea,
            'filters' => $filters,
            'firstDay' => $firstDay,
            'lastDay' => $lastDay,
            // 'lastYearStats'=>$otherStatsLastYear,
            // 'currentWeekRecords'=>$currentWeekRecords,
            // 'subarea'=>$subarea,
            // 'filters'=>$filters
        ]);
    }

    public function getCityStats()
    {
        $request = request();
        if ($request->get('period')) {
            $period = $request->get('period');
        } else {
            $period = "7days";
        }

        if ($period == "7days") {
            $interval = "7 DAY";
        } elseif ($period == "15days") {
            $interval = "15 DAY";
        } elseif ($period == "30days") {
            $interval = "30 DAY";
        } elseif ($period == "60days") {
            $interval = "60 DAY";
        } else {
            $interval = "7 DAY";
        }

        $type = "city";
        $city = NULL;
        $subarea = false;
        $filters = $request->all();
        if ($request->get('city')) {
            $city = $request->get('city');
            $type = "subarea";
            $subarea = true;
        }

        $stats = $this->statsRepo->getStats2($interval);

        // print_r($stats);
        // exit;

        return view('frontend.stats')->with([

            'stats' => $stats,
            'filters' => $filters,
            'interval' => $interval
        ]);
    }


    public function getStats()
    {
        $request = request();
        $typeSlugMap = ['Apartment' => 'condos', 'House' => 'houses', 'Townhouse' => 'townhouses', 'Duplex' => 'duplexes'];
        $qCity    = $request->get('city', '');
        $qSubarea = $request->get('subarea', '');
        $qType    = $request->get('type', '');
        if ($qCity && $qCity !== 'Lower Mainland and Fraser Valley') {
            $path = '/market-stats/' . Helper::enslugPlace($qCity);
            if ($qSubarea && $qType && isset($typeSlugMap[$qType])) {
                $path .= '/' . Helper::enslugPlace($qSubarea) . '/' . $typeSlugMap[$qType];
            } elseif ($qSubarea) {
                $path .= '/' . Helper::enslugPlace($qSubarea);
            } elseif ($qType && isset($typeSlugMap[$qType])) {
                $path .= '/' . $typeSlugMap[$qType];
            }
            return redirect($path, 301);
        }
        if (!$qCity || $qCity === 'Lower Mainland and Fraser Valley') {
            return redirect('/market-stats', 301);
        }
        $user = Auth::user();
        $city = "";
        $flush = 0;
        $subarea = null;
        $ref = null;
        $subareas = null;
        $listingtype = '';
        if(in_array($request->get('type'), ['House', 'Townhouse', 'Apartment', 'Duplex'])){
            $listingtype = $request->get('type');
        }
        if ($request->get('city') && $request->get('city') != 'Lower Mainland and Fraser Valley') {
            $city = $request->get('city');
            $cityRecord = Places::where('type', 'city')->where('place', $city)->first();
            if (!$cityRecord->stats_subareas_disabled) {
                $subareas = Places::where('type', 'subarea')->where('city', $city)->where('stats_disabled', 0)->where('stats_subareas_disabled', 0)->orderBy('order')->get();
            }
        }
        if ($request->get('flush')) {
            $flush = $request->get('flush');
        }
        if ($request->get('subarea')) {
            $subarea = $request->get('subarea');
        }
        if ($request->get('ref')) {
            $ref = $request->get('ref');
        }
        $cities = Places::where('type', 'city')->where('stats_disabled', 0)->where('stats_subareas_disabled', 0)->orderBy('order')->get();
        $this->activityRepo->log_insights_activity('page_visit', 'Page Visit', $city, $subarea, "15 DAY", $ref);
        return view('frontend.stats')->with([
            'user' => $user,
            'city' => $city,
            'flush' => $flush,
            'cities' => $cities,
            'subarea' => $subarea,
            'subareas' => $subareas,
            'listingtype' => $listingtype 
        ]);
    }

    public function getStatsNew($citySlug = '', $subareaOrTypeSlug = '', $typeSlug = '')
    {
        $typeSlugMap = ['condos' => 'Apartment', 'houses' => 'House', 'townhouses' => 'Townhouse', 'duplexes' => 'Duplex'];

        $city        = $citySlug        ? Helper::deslugPlace($citySlug)        : '';
        $subarea     = '';
        $listingtype = '';

        if ($subareaOrTypeSlug) {
            if (isset($typeSlugMap[$subareaOrTypeSlug])) {
                $listingtype = $typeSlugMap[$subareaOrTypeSlug];
            } else {
                $subarea = Helper::deslugPlace($subareaOrTypeSlug);
            }
        }
        if ($typeSlug && isset($typeSlugMap[$typeSlug])) {
            $listingtype = $typeSlugMap[$typeSlug];
        }

        $cities   = Places::where('type', 'city')->where('stats_disabled', 0)->where('stats_subareas_disabled', 0)->orderBy('order')->get();
        $subareas = null;
        if ($city) {
            $cityRecord = Places::where('type', 'city')->where('place', $city)->first();
            if ($cityRecord && !$cityRecord->stats_subareas_disabled) {
                $subareas = Places::where('type', 'subarea')->where('city', $city)->where('stats_disabled', 0)->where('stats_subareas_disabled', 0)->orderBy('order')->get();
            }
        }

        $summaryKey    = $this->genCacheKeyStr('mkt_summary2', $city, $subarea, $listingtype);
        $marketSummary = Cache::remember($summaryKey, 1800, fn() => $this->statsRepo->get_market_summary($city, $subarea, $listingtype));

        $marketCondition = $this->computeMarketCondition($marketSummary);

        $absorptionMap = null;
        if ($city && !$subarea) {
            $absKey        = $this->genCacheKeyStr('abs_map2', $city);
            $absorptionMap = Cache::remember($absKey, 1800, fn() => $this->statsRepo->get_city_stats('30 DAY', $city));
        }

        $listingsKey    = $this->genCacheKeyStr('recent_lst2', $city, $subarea, $listingtype);
        $recentListings = Cache::remember($listingsKey, 900, function() use ($city, $subarea, $listingtype) {
            return Listings::withCount('photos')->with('photos')
                ->where('status', 'Active')
                ->when($city,        fn($q) => $q->where('city',    $city))
                ->when($subarea,     fn($q) => $q->where('subarea', $subarea))
                ->when($listingtype, fn($q) => $q->where('type',    $listingtype))
                ->orderBy('list_date', 'desc')
                ->limit(6)
                ->get();
        });

        $topBuildings = null;
        if ($city) {
            $bldgKey      = $this->genCacheKeyStr('top_bldg2', $city, $subarea);
            $topBuildings = Cache::remember($bldgKey, 3600, function() use ($city, $subarea) {
                return Buildings::where('city', $city)
                    ->when($subarea, fn($q) => $q->where('subarea', $subarea))
                    ->orderBy('units_in_strata', 'desc')
                    ->limit(6)
                    ->get();
            });
        }

        $listtypeSlug = $listingtype ? array_search($listingtype, $typeSlugMap) : '';
        $subareaSlug  = $subarea     ? Helper::enslugPlace($subarea)             : '';

        $this->activityRepo->log_insights_activity('page_visit', 'Market Stats New Page Visit', $city, $subarea, '30 DAY', null);

        return view('frontend.market_stats')->with([
            'city'            => $city,
            'citySlug'        => $citySlug,
            'subarea'         => $subarea,
            'subareaSlug'     => $subareaSlug,
            'listingtype'     => $listingtype,
            'listtypeSlug'    => $listtypeSlug,
            'cities'          => $cities,
            'subareas'        => $subareas,
            'marketSummary'   => $marketSummary,
            'marketCondition' => $marketCondition,
            'absorptionMap'   => $absorptionMap,
            'recentListings'  => $recentListings,
            'topBuildings'    => $topBuildings,
            'flush'           => 0,
        ]);
    }

    protected function computeMarketCondition($summary)
    {
        $empty = ['label' => null, 'color' => '#888', 'class' => 'verdict-unknown',
                  'absorption_rate' => 0, 'avg_dom' => 0, 'price_trend' => 0,
                  'sold_30d' => 0, 'current_active' => 0, 'avg_sold_30d' => 0, 'avg_sold_90d' => 0];
        if (!$summary || !($summary->current_active ?? 0)) return $empty;

        $absorptionRate = ($summary->sold_30d / $summary->current_active) * 100;
        $avgDom         = (int)($summary->avg_dom_30d ?? 0);
        $priceTrend     = ($summary->avg_sold_90d && $summary->avg_sold_30d && $summary->avg_sold_90d > 0)
            ? (($summary->avg_sold_30d - $summary->avg_sold_90d) / $summary->avg_sold_90d) * 100
            : 0;

        $condition = \App\Helpers\MarketConditionHelper::classify($absorptionRate, $avgDom);

        return [
            'label'          => $condition['label'],
            'color'          => $condition['color'],
            'class'          => $condition['class'],
            'absorption_rate'=> round($absorptionRate, 1),
            'avg_dom'        => $avgDom,
            'price_trend'    => round($priceTrend, 1),
            'sold_30d'       => (int)($summary->sold_30d    ?? 0),
            'current_active' => (int)($summary->current_active ?? 0),
            'avg_sold_30d'   => (int)($summary->avg_sold_30d ?? 0),
            'avg_sold_90d'   => (int)($summary->avg_sold_90d ?? 0),
        ];
    }

    public function getStatsJson()
    {
        $request = request();
        $type = NULL;
        $city = "";
        $subarea = null;
        $property_type = null;
        $stats_type = null;
        $flush = 0;
        $listingtype = '';
        $response = [
            'success' => false,
            'data' => ''
        ];
        $stats = NULL;
        $days = 7;

        if ($request->get('city')) {
            $city = $request->get('city');
        }
        if ($request->get('subarea')) {
            $subarea = $request->get('subarea');
        }
        if ($request->get('flush')) {
            $flush = $request->get('flush');
        }

        if ($request->get('period'))
            $period = $request->get('period');
        else
            $period = "days7";

        if ($period == "days7") {
            $interval = "7 DAY";
        } elseif ($period == "days15") {
            $interval = "15 DAY";
            $days = 15;
        } elseif ($period == "days30") {
            $interval = "30 DAY";
            $days = 30;
        } elseif ($period == "days60") {
            $interval = "60 DAY";
            $days = 60;
        }elseif ($period == "days90") {
            $interval = "90 DAY";
            $days = 90;
        }else {
            $interval = "7 DAY";
        }

        if ($request->get('stats_type')) {
            $stats_type = $request->get('stats_type');
        }
        
        if ($request->get('property_type')) {
            $property_type = $request->get('property_type');
        }
        
        if(in_array($request->get('listingtype'), ['House', 'Townhouse', 'Apartment'])){
            $listingtype = $request->get('listingtype');
        }
        
        if ($flush) {
            Cache::flush();
        }
        
        $type = $request->get('type','');

        if ($type && $type == "city_stats") {
            $_cachKeyName = $this->genCacheKeyStr($type, $interval . "_" . $city);

            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_city_stats($interval, $city);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
            if ($period != "days15") {
                $this->activityRepo->log_insights_activity('city_stats_table', 'Recent Sold Stats', $city, $subarea, $interval);
            }
        }elseif ($type == "city_active_sold") {
            $_cachKeyName = $this->genCacheKeyStr($type, $interval . "_" . $city."_".$listingtype);
            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_city_active_sold($interval, $city, $listingtype);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
            if ($period != "days15") {
                $this->activityRepo->log_insights_activity('sold_and_listed_graph', 'Sold, Listed Units', $city, $subarea, $interval);
            }
        }elseif ($type == "type_active_sold") {
            $_cachKeyName = $this->genCacheKeyStr($type, $interval . "_" . $city . "_" . $subarea);
            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_type_active_sold($interval, $city, $subarea);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
            if ($period != "days15") {
                $this->activityRepo->log_insights_activity('sold_market_share_graph', 'Units Sold by Property Type Pie', $city, $subarea, $interval);
            }
        }elseif ($type == "type_sold_monthly") {
            $_cachKeyName = $this->genCacheKeyStr($type, $interval . "_" . $city . "_" . $subarea);
            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_type_sold_monthly($city, $subarea);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
        }elseif ($type == "sold_beds") {
            $_cachKeyName = $this->genCacheKeyStr($type, $interval . "_" . $city . "_" . $subarea. "_" .$listingtype);
            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_sold_beds($interval, $city, $subarea, $listingtype);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
            if ($period != "days15") {
                $this->activityRepo->log_insights_activity('sold_vs_beds', 'Units Sold by Bedrooms', $city,  $subarea, $interval);
            }
        }elseif ($type == "three_year_sold") {
            $_cachKeyName = $this->genCacheKeyStr($type, $city, $listingtype);
            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_three_year_sold($city, $listingtype);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
            //  if($period != "days15"){
            //     $this->activityRepo->log_insights_activity('units_sold_24_month', 'Units Sold In Last 24 Months', $city, $interval);
            // }
        }elseif ($type == "sold_price_range") {
            $_cachKeyName = $this->genCacheKeyStr($type, $interval . "_" . $city . "_" . $subarea."_".$listingtype);
            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_sold_price_range($interval, $city, $subarea, $listingtype);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
            if ($period != "days15") {
                $this->activityRepo->log_insights_activity('sold_price_range', 'Units Sold by Price Range', $city, $subarea, $interval);
            }
        }elseif ($type == "city_type_sold") {
            $_cachKeyName = $this->genCacheKeyStr($type, $interval . "_" . $city);
            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_city_type_sold($interval, $city);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
            if ($period != "days15") {
                $this->activityRepo->log_insights_activity('house_townhouse_condos_bar_graph', 'Units Sold by Property Type Bar', $city, $subarea, $interval);
            }
            $response['success'] = true;
        }elseif ($type == "property_age_stats") {
            $_cachKeyName = $this->genCacheKeyStr($type, $interval . "_" . $city . "_" . $subarea."_".$listingtype);
            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_property_age_stats($interval, $city, $subarea, $listingtype);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
            if ($period != "days15") {
                $this->activityRepo->log_insights_activity('property_age_stats', 'Units Sold by Property Age', $city, $subarea, $interval);
            }
        }elseif ($type == "avg_dom_data") {
            $_cachKeyName = $this->genCacheKeyStr($type, $interval . "_" . $city);
            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_avg_days_on_market_stat($interval, $city);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
            if ($period != "days15") {
                $this->activityRepo->log_insights_activity('avg_dom_graph', 'Avg Days on Market', $city, $subarea, $interval);
            }
        }elseif ($type == "avg_price_monthly") {
            $_cachKeyName = $this->genCacheKeyStr($type, '1', $city . "_" . $subarea);
            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_avg_price_monthly($city, $subarea);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
        }elseif ($type == "avg_diff_monthly") {
            $_cachKeyName = $this->genCacheKeyStr($type, $city . "_" . $subarea);
            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_avg_diff_monthly($city, $subarea);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
        }elseif ($type == "sold_count_monthly") {
            $_cachKeyName = $this->genCacheKeyStr($type, '1', $city . "_" . $subarea);
            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_sold_count_monthly($city, $subarea);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
        }elseif ($type == "get_subarea_beds_sold_stats") {
            $_cachKeyName = $this->genCacheKeyStr($type, $city . "_" . $subarea . "_" . $property_type);
            if (!$flush && Cache::has($_cachKeyName) ) {
                $stats = Cache::get($_cachKeyName);
            } else {
                $stats = $this->statsRepo->get_subarea_beds_sold_stats($city, $subarea, $property_type);
                Cache::put($_cachKeyName, $stats, Carbon::now()->addHours(2));
            }
        }elseif ($type == 'city_stats_yearly') {
            $stats = array();
            $stats1 = array();
            if ($stats_type == 'units_sold') {
                $stats1 = $this->statsRepo->get_city_stats_yearly($city, $subarea, $listingtype);
            } elseif ($stats_type == 'avg_price') {
                $stats1 = $this->statsRepo->get_city_stats_yearly_price($city, $subarea, $listingtype);
            } elseif ($stats_type == 'avg_dom') {
                $stats1 = $this->statsRepo->get_city_stats_yearly_dom($city, $subarea, $listingtype);
            }
            $titles = array();
            if (count($stats1) > 0) {
                $row = $stats1[0];
                $titles = [
                    'minus_one' => $row->month_minus_one . "/" . substr($row->year_minus_one, -2),
                    'minus_two' => $row->month_minus_two . "/" . substr($row->year_minus_two, -2),
                    'minus_three' => $row->month_minus_three . "/" . substr($row->year_minus_three, -2),
                    'minus_four' => $row->month_minus_four . "/" . substr($row->year_minus_four, -2),
                    'minus_five' => $row->month_minus_five . "/" . substr($row->year_minus_five, -2),
                    'minus_six' => $row->month_minus_six . "/" . substr($row->year_minus_six, -2),
                    'minus_seven' => $row->month_minus_seven . "/" . substr($row->year_minus_seven, -2),
                    'minus_eight' => $row->month_minus_eight . "/" . substr($row->year_minus_eight, -2),
                    'minus_nine' => $row->month_minus_nine . "/" . substr($row->year_minus_nine, -2),
                    'minus_ten' => $row->month_minus_ten . "/" . substr($row->year_minus_ten, -2),
                    'minus_eleven' => $row->month_minus_eleven . "/" . substr($row->year_minus_eleven, -2),
                    'minus_twelve' => $row->month_minus_twelve . "/" . substr($row->year_minus_twelve, -2),

                    'one' => $row->month_one . "/" . substr($row->year_one, -2),
                    'two' => $row->month_two . "/" . substr($row->year_two, -2),
                    'three' => $row->month_three . "/" . substr($row->year_three, -2),
                    'four' => $row->month_four . "/" . substr($row->year_four, -2),
                    'five' => $row->month_five . "/" . substr($row->year_five, -2),
                    'six' => $row->month_six . "/" . substr($row->year_six, -2),
                    'seven' => $row->month_seven . "/" . substr($row->year_seven, -2),
                    'eight' => $row->month_eight . "/" . substr($row->year_eight, -2),
                    'nine' => $row->month_nine . "/" . substr($row->year_nine, -2),
                    'ten' => $row->month_ten . "/" . substr($row->year_ten, -2),
                    'eleven' => $row->month_eleven . "/" . substr($row->year_eleven, -2),
                    'twelve' => $row->month_twelve . "/" . substr($row->year_twelve, -2),
                ];
            }
            $stats = [
                'titles' => $titles,
                'data' => $stats1
            ];
        }

        /*
        // Previous-code-blocks (before generalization & optimization):
        if ($type == "get_subarea_beds_sold_stats") {
            if (!$flush && Cache::has('get_subarea_beds_sold_stats' . $city . "_" . $subarea . "_" . $property_type))) {
                $stats = Cache::get('get_subarea_beds_sold_stats' . $city . "_" . $subarea . "_" . $property_type));
            } else {
                $stats = $this->statsRepo->get_subarea_beds_sold_stats($city, $subarea, $property_type);
                Cache::put('get_subarea_beds_sold_stats' . $city . "_" . $subarea . "_" . $property_type), $stats, Carbon::now()->addHours(2));
            }
        }
        */
        
        $toDate = Carbon::now()->format('m/d/Y');
        $fromDate = Carbon::now()->subDays($days)->format('m/d/Y');

        if ($stats) {
            $response['success'] = true;
            $response['data'] = $stats;
            $response['fromDate'] = $fromDate;
            $response['toDate'] = $toDate;
        } else {
            $response['data'] = array();
            $response['fromDate'] = $fromDate;
            $response['toDate'] = $toDate;
        }

        return response()->json($response);
    }

    public function getBuildingStatsJson()
    {
        header('X-Robots-Tag: noindex, nofollow');
        $request = request();
        $stats = null;
        if ($request->get('id') && $request->get('period')) {
            $id = $request->get('id');
            $period = $request->get('period');
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
            $statsResponse = $building->get_stats($interval);
            if (count($statsResponse) > 0) {
                $stats = (array)$statsResponse[0];
                $stats['expensive_sold'] = "$" . $building->number_shorten($stats['expensive_sold']);
                $stats['avg_sold_price'] = "$" . $building->number_shorten($stats['avg_sold_price']);
                $stats['avg_per_sqft']  = money_format('%.0n', $stats['avg_per_sqft']);
                if (!$stats['avg_dom']) {
                    $stats['avg_dom'] = 0;
                }
            } else {
                $stats = null;
            }
            return response()->json($stats);
        }
    }
}
