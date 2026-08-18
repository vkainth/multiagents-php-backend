<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repository\ListingRepository;
// use App\Models\CityButtonClicks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Listings;
use App\Models\Places;
use App\Services\ForSaleSeoStatsService;
use App\Helpers\FubAreaHelper;
use App\Helpers\Helper;
use Carbon\Carbon;

class SearchListingsController extends Controller
{
    // [created:12-05-2022] (for clarity in logic/routes... )

    protected $listingRepo;
    protected ForSaleSeoStatsService $seoStatsService;

    public function __construct(ListingRepository $listingRepo, ForSaleSeoStatsService $seoStatsService)
    {
        $this->listingRepo     = $listingRepo;
        $this->seoStatsService = $seoStatsService;
    }

    private function computeValueSignalStats($listingsQuery): array
    {
        // toBase() gets the raw query builder, then null-out columns so the existing
        // SELECT * and photos subquery don't get mixed with our aggregates.
        $sqftBase = (clone $listingsQuery)->toBase();
        $sqftBase->columns = null;
        $sqftRow = $sqftBase
            ->where('listprice_2', '>', 0)
            ->where('livingarea_2', '>', 0)
            ->selectRaw('AVG(listprice_2 / livingarea_2) as avg_ppsf, COUNT(*) as cnt')
            ->first();

        // Intentional: require at least 2 listings with valid sqft before computing stats.
        // Fewer than 2 means a meaningful "below average" comparison is not possible,
        // so both stats are returned as 0 — which suppresses the value signal for all cards.
        if (!$sqftRow || (int)$sqftRow->cnt < 2) {
            return ['avgPricePerSqft' => 0, 'medianListPrice' => 0];
        }

        $avgPricePerSqft = (float)$sqftRow->avg_ppsf;

        // Median list price: capped at 2000 rows to bound memory, consistent with other stat queries.
        $priceBase = (clone $listingsQuery)->toBase();
        $priceBase->columns = null;
        $sortedPrices = $priceBase
            ->where('listprice_2', '>', 0)
            ->orderBy('listprice_2')
            ->limit(2000)
            ->pluck('listprice_2')
            ->map(fn($p) => (float)$p)
            ->values();

        $count = $sortedPrices->count();
        $medianListPrice = ($count % 2 === 0)
            ? ($sortedPrices[intval($count / 2) - 1] + $sortedPrices[intval($count / 2)]) / 2
            : $sortedPrices[intval($count / 2)];

        return [
            'avgPricePerSqft' => (int)round($avgPricePerSqft),
            'medianListPrice' => (int)round($medianListPrice),
        ];
    }

    public function seoUrl($string)
    {
        //Lower case everything
        $string = strtolower($string);
        //Make alphanumeric (removes all other characters)
        $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
        //Clean up multiple dashes or whitespaces
        $string = preg_replace("/[\s-]+/", " ", $string);
        //Convert whitespaces and underscore to dash
        $string = preg_replace("/[\s_]/", "-", $string);
        return $string;
    }


    public function get_place_for_sale($slug, $subarea = false, $beds=false, Request $request=null)
    {
        $request = $request??request();
        $res = Cache::remember('mls_query_v1_' . $slug, 3600, function () use ($slug) {
            return DB::select("select * from bccondosandhomes.mls_query where slug = '" . $slug . "'");
        });

        if ($res) {
            $per_page = 200;
            $max_results = 500;
            $page = 1;
            $max_pages = ceil($max_results / $per_page);
            $properties_sent = 0;

            if ($subarea) {
                $subarea = str_replace(array('-', '~'), array(' ', '-'), stripslashes($subarea));
                $subarea = ucwords($subarea);
            }

            if ($request->input('page') > 0) {
                $page = $request->input('page');
                $properties_sent = ($page - 1) * $per_page;
            }

            $orderByField = 'list_date'; // 'last_modified'; // [updated:03-10-2022] changed-lastModified2listDate
            $orderByOrder = 'desc';

            $arrayMapViewSelect2Field = ['default'=>'last_modified','listdate'=>'list_date','listprice_2'=>'listprice_2','livingarea_2'=>'livingarea_2'];

            if (!empty($request->input('sort_by')) ) {
                $orderByTemp = explode('|',$request->input('sort_by'));
                $orderByField = $orderByTemp[0];
                if(!empty($orderByTemp[0])){
                    $orderByField = $orderByTemp[0];
                }
                if(!empty($orderByTemp[1]) && strtolower($orderByTemp[1])=='desc'){
                    $orderByOrder = 'desc';
                }else{
                    $orderByOrder = 'asc';
                }
                $orderByField = $arrayMapViewSelect2Field[$orderByField];
            }

            // if ($page > $max_pages) {
            //     $listings = array();
            // } else {
            $response = $res[0];

            $params = $request->except(['_token', '_']);
            ksort($params);
            $listingsCacheKey = 'srch_v1_' . $slug . '_' . ($subarea ?: '') . '_' . md5(json_encode($params));

            [$listings, $subareas] = Cache::remember($listingsCacheKey, 600, function () use ($slug, $subarea, $response, $request, $orderByField, $orderByOrder, $per_page) {
                $subareas = [];
                if ($subarea) {
                    $listings = Listings::with('aphoto')->withCount('photos')->where('table', 'mlsr_listings')->whereRaw($response->query)->where('subarea', $subarea);
                } else {
                    $listings = Listings::with('aphoto')->withCount('photos')->where('table', 'mlsr_listings')->whereRaw($response->query);
                }

                /*Using-generalized-function-for-listings-filters*/
                $listings = $this->filterListingsWithRequestParams($listings, $request);

            /* GENERAL-code for mapped-input-args--with--table-fields */
            
            // $plusArgs=['beds'=>'bedrooms','baths'=>'bathstotal','kitchens'=>'kitchens'];
            // // $plusArgs=['kitchens'=>'kitchens'];
            // $_plus_suffixes = ['-or-more','+' /*,'plus'*/];
            // foreach($plusArgs as $_arg=>$_mappedField){
            //     if(!empty($request->input($_arg))){
            //         foreach ($_plus_suffixes as $_plus_suffix) {
            //             if( substr($request->input($_arg), 0-(strlen($_plus_suffix)) )==$_plus_suffix ){
            //             // for-usable with args like: beds=2plus... AsWellAs beds=2+
            //             // $_val = explode('+', $request->input($_arg) )[0];
            //                 $_val = explode($_plus_suffix, $request->input($_arg) )[0];
            //                 $listings = $listings->where($_mappedField,'>=', $_val );
            //             }else{
            //                 $_val = $request->input($_arg);
            //                 $listings = $listings->where($_mappedField,'=', $_val );
            //             }
            //         }
            //     }
            // }
            // // 

            // if(!empty($request->route('beds'))){
            //     if(intval($request->route('beds')).'' ==$request->route('beds')){
            //         $listings = $listings->where('bedrooms','=', $request->route('beds') );
            //     }else{
            //         $listings = $listings->where('bedrooms','>=', intval($request->route('beds'))  );
            //     }
            // }

            // if(!empty($request->input('pricefrom'))){
            //     $listings = $listings->where('listprice_2','>=', $request->input('pricefrom') );
            // }

            // if(!empty($request->input('priceto'))){
            //     $listings = $listings->where('listprice_2','<=', $request->input('priceto') );
            // }

            // if(!empty($request->input('sqftfrom'))){
            //     $listings = $listings->where('livingarea_2','>=', $request->input('sqftfrom') );
            // }

            // if(!empty($request->input('sqftto'))){
            //     $listings = $listings->where('livingarea_2','<=', $request->input('sqftto') );
            // }

            // if(!empty($request->input('filter_subareas')) && is_array($request->input('filter_subareas'))){
            //     $listings = $listings->whereIn('subarea', $request->input('filter_subareas') );
            // }

            // if(!empty($request->input('filter_types')) && is_array($request->input('filter_types'))){
            //     $_filter_TownHouseTypesArray =  array('Townhouse', 'Duplex', 'Fourplex', 'Triplex');
            //     if(in_array('Townhouse', $request->input('filter_types')) ){
            //         $listings = $listings->whereIn('type', array_merge($_filter_TownHouseTypesArray, $request->input('filter_types')) );
            //     }else{
            //         $listings = $listings->whereIn('type', $request->input('filter_types') );
            //     }

            // }

            // if(!empty($request->input('built_btw')) && !empty($request->input('built_btw')[0]) && !empty($request->input('built_btw')[1]) ){
            //     if(! ($request->input('built_btw')[0]==1900  && $request->input('built_btw')[1]==1900) )
            //     $listings = $listings->where('yearbuilt','>=', min($request->input('built_btw')) )->where('yearbuilt','<=', max($request->input('built_btw')) );
            // }

            // if(strtolower($request->input('lststatus',''))=='sold' || strtolower($request->input('listing_status',''))=='sold'  ){

            //     $listings = $listings->where('status','Sold');

            //     if(!empty($request->input('dom'))){
            //         $listings = $listings->whereRaw(' DATE_SUB(`sold_date`,INTERVAL '.substr(str_replace('_', ' ', $request->input('dom')),0,-1) .' ) <= `list_date` ' );
            //     }
            //     if(!empty($request->input('soldwithin'))){
            //         $listings = $listings->whereRaw(' DATE_SUB(NOW(),INTERVAL '.substr(str_replace('_', ' ', $request->input('soldwithin')),0,-1) .' ) <= `list_date` ' );
            //     }

            //     // Put following block to @else after-publish
            // }elseif((strtolower($request->input('lststatus',''))=='active' || strtolower($request->input('listing_status',''))=='active') || (Auth::user() && substr(Auth::user()->email,-12)=='pixilink.com') ){

            //     $listings = $listings->where('status','Active');

            //     if(!empty($request->input('dom'))){
            //         $listings = $listings->whereRaw(' DATE_SUB(NOW(),INTERVAL '.substr(str_replace('_', ' ', $request->input('dom')),0,-1) .' ) <= `list_date` ' );
            //     }

            // }else{                
            //     $listings = $listings->where(function ($q) {
            //         $q->where('status', 'Active')->orWhere('status', 'Sold');
            //     });
            // }



                $listings = $listings->orderBy($orderByField, $orderByOrder)->paginate($per_page);

                $subareasSql = "select subarea, COUNT(*) as cnt from boards.listings where " . $response->query . " and (status='Active' OR status = 'Sold') and `table` = 'mlsr_listings' and subarea is not null and subarea != '' group by subarea order by subarea";
                $res1 = DB::select($subareasSql);
                if ($res1 && count($res1) > 0) {
                    foreach ($res1 as $_row) {
                        $area['link'] = "https://www.bccondosandhomes.com/" . $slug . "-for-sale-" . $this->seoUrl(trim($_row->subarea));
                        $area['subarea'] = $_row->subarea;
                        $area['listings_count'] = $_row->cnt;
                        $subareas[] = $area;
                    }
                }

                return [$listings, $subareas];
            });

            // Compute SEO stats and data for subarea pages [added:Task#371]
            $seoStats = [];
            $seoData  = [];
            if ($subarea) {
                $_statsCacheKey = 'for_sale_seo_v1_' . $slug . '_' . md5($subarea . ($beds ?: ''));
                $seoStats = Cache::remember($_statsCacheKey, 1800, function () use ($response, $subarea, $beds) {
                    return $this->computeForSaleSeoStats($response->query, $subarea, $beds);
                });
                $seoData = $this->buildForSaleSeoData($res[0], $subarea, $beds, $seoStats, (string)($request->route('type', '')));
            }

            // FUB: track search page view for phone-verified logged-in users
            $fubCity = $res[0]->city ?? null;
            FubAreaHelper::saveToSession($fubCity);
            $fubSearchLabel = ucwords(str_replace('-', ' ', $slug));
            if ($subarea) {
                $fubSearchLabel .= ' — ' . $subarea;
            }
            FubAreaHelper::pushSearchEvent(
                $fubSearchLabel . ' for sale',
                request()->fullUrl(),
                $fubCity
            );

            return view('frontend.for_sale_listings_ng')->with([
                'listings'        => $listings,
                'place'           => $res[0],
                'subarea'         => $subarea,
                'subareas'        => $subareas,
                'seoStats'        => $seoStats,
                'seoData'         => $seoData,
                'marketStats'     => [],
                'listings_count'  => $listings->total(),
                'avgPricePerSqft' => 0,
                'medianListPrice' => 0,
                'minPrice'        => (int)($seoStats['min_price'] ?? 0),
                'maxPrice'        => (int)($seoStats['max_price'] ?? 0),
                'city'            => null,
            ]);
        } else {
            abort(404);
        }
    }

    public function getBladeReadyData_for_sale_listings ($slug, $subarea = false, $beds=false, Request $request=null)
    {
        $request = $request??request();
        $sql = "select * from bccondosandhomes.mls_query where slug = '" . $slug . "'";
        $res =  DB::select(/*DB::raw*/($sql));

        if ($res) {
            $per_page = (int) $request->input('per_page',200);
            $max_results = 500;
            $page = 1;
            $max_pages = ceil($max_results / $per_page);
            $properties_sent = 0;

            if ($subarea) {
                $subarea = str_replace(array('-', '~'), array(' ', '-'), stripslashes($subarea));
                $subarea = ucwords($subarea);
            }

            if ( intval($request->input('page',0)) > 0) {
                $page = $request->input('page');
                $properties_sent = ($page - 1) * $per_page;
            }

            $orderByField = 'list_date'; // 'last_modified'; // [updated:03-10-2022] changed-lastModified2listDate
            $orderByOrder = 'desc';

            $arrayMapViewSelect2Field = ['default'=>'last_modified','listdate'=>'list_date','listprice_2'=>'listprice_2','livingarea_2'=>'livingarea_2'];

            if (!empty($request->input('sort_by')) ) {
                $orderByTemp = explode('|',$request->input('sort_by'));
                $orderByField = $orderByTemp[0];
                if(!empty($orderByTemp[0])){
                    $orderByField = $orderByTemp[0];
                }
                if(!empty($orderByTemp[1]) && strtolower($orderByTemp[1])=='desc'){
                    $orderByOrder = 'desc';
                }else{
                    $orderByOrder = 'asc';
                }
                $orderByField = $arrayMapViewSelect2Field[$orderByField];
            }

            // if ($page > $max_pages) {
            //     $listings = array();
            // } else {
            $response = $res[0];
            $subareas = array();
            if ($subarea) {
                $listings = Listings::with('aphoto')->withCount('photos')->where('table', 'mlsr_listings')->whereRaw($response->query)->where('subarea', $subarea);
                // ->where(function ($q) {
                //     $q->where('status', 'Active')->orWhere('status', 'Sold');
            } else {
                $listings = Listings::with('aphoto')->withCount('photos')->where('table', 'mlsr_listings')->whereRaw($response->query);
                // ->where(function ($q) {
                //     $q->where('status', 'Active')->orWhere('status', 'Sold');
                // })->orderBy($orderByField, $orderByOrder)->paginate($per_page);
            }

            /*Using-generalized-function-for-listings-filters*/
            $listings = $this->filterListingsWithRequestParams($listings,$request);


            // /* GENERAL-code for mapped-input-args--with--table-fields */
            
            // // $plusArgs=['kitchens'=>'kitchens'];
            // $plusArgs = ['beds'=>'bedrooms','baths'=>'bathstotal','kitchens'=>'kitchens','frontage'=>'frontage','levels'=>'finished_levels'];
            // $_plus_suffixes = ['-or-more','+' /*,'plus'*/];

            // foreach($plusArgs as $_arg=>$_mappedField){

            //     if( ($request->input($_arg,'')!='') && is_numeric( $request->input($_arg)) ){
            //         $_val = $request->input($_arg);
            //         $listings = $listings->where($_mappedField,'=', $_val );
            //     }else{
            //         foreach ($_plus_suffixes as $_plus_suffix) {
            //             if( substr($request->input($_arg), 0-(strlen($_plus_suffix)) )==$_plus_suffix ){
            //                 // for-usable with args like: beds=2plus... AsWellAs beds=2+
            //                 $_val = explode($_plus_suffix, $request->input($_arg) )[0];
            //                 $listings = $listings->where($_mappedField,'>=', $_val );
            //             }else{}
            //         }
            //     }
            // }
            // // 

            // if(!empty($request->route('beds'))){
            //     if(intval($request->route('beds'))==$request->route('beds')){
            //         $listings = $listings->where('bedrooms','=', $request->route('beds') );
            //     }else{
            //         $listings = $listings->where('bedrooms','>=', str_replace($_plus_suffix,'', $request->route('beds') ) );
            //     }
            // }

            // if(!empty($request->input('pricefrom'))){
            //     $listings = $listings->where('listprice_2','>=', $request->input('pricefrom') );
            //     /* Other-logic--for-sold:: Check Listing-Model*/
            // }

            // if(!empty($request->input('priceto'))){
            //     $listings = $listings->where('listprice_2','<=', $request->input('priceto') );
            // }

            // if(!empty($request->input('sqftfrom'))){
            //     $listings = $listings->where('livingarea_2','>=', $request->input('sqftfrom') );
            // }

            // if(!empty($request->input('sqftto'))){
            //     $listings = $listings->where('livingarea_2','<=', $request->input('sqftto') );
            // }

            // if(!empty($request->input('subareas')) && is_array($request->input('subareas'))){
            //     $listings = $listings->whereIn('subarea', $request->input('subareas') );
            // }
            // if(!empty($request->input('filter_subareas')) && is_array($request->input('filter_subareas'))){
            //     $listings = $listings->whereIn('subarea', $request->input('filter_subareas') );
            // }

            // if(!empty($request->input('types')) && is_array($request->input('types'))){
            //     $_filter_TownHouseTypesArray =  array('Townhouse', 'Duplex', 'Fourplex', 'Triplex');
            //     if(in_array('Townhouse', $request->input('types')) ){
            //         $listings = $listings->whereIn('type', array_merge($_filter_TownHouseTypesArray, $request->input('types')) );
            //     }else{
            //         $listings = $listings->whereIn('type', $request->input('types') );
            //     }
            // }
            // if(!empty($request->input('filter_types')) && is_array($request->input('filter_types'))){
            //     $_filter_TownHouseTypesArray =  array('Townhouse', 'Duplex', 'Fourplex', 'Triplex');
            //     if(in_array('Townhouse', $request->input('filter_types')) ){
            //         $listings = $listings->whereIn('type', array_merge($_filter_TownHouseTypesArray, $request->input('filter_types')) );
            //     }else{
            //         $listings = $listings->whereIn('type', $request->input('filter_types') );
            //     }
            // }

            // if(!empty($request->input('built_btw')) && !empty($request->input('built_btw')[0]) && !empty($request->input('built_btw')[1]) ){
            //     if(! ($request->input('built_btw')[0]==1900  && $request->input('built_btw')[1]==1900) )
            //     $listings = $listings->where('yearbuilt','>=', min($request->input('built_btw')) )->where('yearbuilt','<=', max($request->input('built_btw')) );
            // }

            // if(strtolower($request->input('lststatus',''))=='sold' || strtolower($request->input('listing_status',''))=='sold'  ){

            //     $listings = $listings->where('status','Sold');

            //     if(!empty($request->input('dom'))){
            //         $listings = $listings->whereRaw(' DATE_SUB(`sold_date`,INTERVAL '.substr(str_replace('_', ' ', $request->input('dom')),0,-1) .' ) <= `list_date` ' );
            //     }
            //     if(!empty($request->input('soldwithin'))){
            //         $listings = $listings->whereRaw(' DATE_SUB(NOW(),INTERVAL '.substr(str_replace('_', ' ', $request->input('soldwithin')),0,-1) .' ) <= `list_date` ' );
            //     }

            //     // Put following block to @else after-publish
            // }elseif((strtolower($request->input('lststatus',''))=='active' || strtolower($request->input('listing_status',''))=='active') || (Auth::user() && substr(Auth::user()->email,-12)=='pixilink.com') ){

            //     $listings = $listings->where('status','Active');

            //     if(!empty($request->input('dom'))){
            //         $listings = $listings->whereRaw(' DATE_SUB(NOW(),INTERVAL '.substr(str_replace('_', ' ', $request->input('dom')),0,-1) .' ) <= `list_date` ' );
            //     }

            // }else{                
            //     $listings = $listings->where(function ($q) {
            //         $q->where('status', 'Active')->orWhere('status', 'Sold');
            //     });
            // }



            $_valueSignalStats = $this->computeValueSignalStats($listings);

            $listings = $listings->orderBy($orderByField, $orderByOrder)->paginate($per_page);

            $subareas = [];
            $sql = "select subarea, COUNT(*) as cnt from boards.listings where " . $response->query . " and (status='Active' OR status = 'Sold') and `table` = 'mlsr_listings' and subarea is not null and subarea != '' group by subarea order by subarea";
            $res1 = DB::select($sql);
            if ($res1 && count($res1) > 0) {
                foreach ($res1 as $_row) {
                    $area['link'] = "https://www.bccondosandhomes.com/" . $slug . "-for-sale-" . $this->seoUrl(trim($_row->subarea));
                    $area['subarea'] = $_row->subarea;
                    $area['listings_count'] = $_row->cnt;
                    $subareas[] = $area;
                }
            }

            // if ($page == $max_pages) {
            //     $remainig_properties = $max_results - $properties_sent;
            //     $listings_count = count($listings);
            //     for ($i = 0; $i < $listings_count; $i++) {
            //         if (($i + 1) > $remainig_properties) {
            //             unset($listings[$i]);
            //         }
            //     }
            // }
            //}
            
            $_bladeReadyAssocArray = [
                'listings' => $listings,
                'listings_count' => $listings->total(),
                'place' => $res[0],
                'subarea' => $subarea,
                'subareas' => $subareas,
                'avgPricePerSqft' => $_valueSignalStats['avgPricePerSqft'],
                'medianListPrice' => $_valueSignalStats['medianListPrice'],
            ];

            return $_bladeReadyAssocArray;

            // Hot to use this data : 

            // $listings_without_undesired_data = $listings->makeHidden(['location','table']); // location-value invalid string ->fails-json-encoding

            // return response()->json($listings_without_undesired_data);

            // return response(json_encode($listings_without_undesired_data,JSON_INVALID_UTF8_SUBSTITUTE))->header('Content-Type', 'application/json');
            // // config(['app.debug'=>true]);
            // // mb_convert_encoding($listings, 'UTF-8', 'UTF-8');
            // return response(json_encode($_bladeReadyAssocArray,JSON_INVALID_UTF8_SUBSTITUTE))->header('Content-Type', 'application/json');

            // return view('frontend.for_sale_listings')->with([
            //     'listings' => $listings,
            //     'place' => $res[0],
            //     'subarea' => $subarea,
            //     'subareas' => $subareas
            // ]);


        } else {
            abort(404);
        }
    }


    public function filterListingsWithRequestParams($listings=null, Request $request=null){
        $request = $request??request(); 
        if(empty($listings)){
            $listings = Listings::with('aphoto')->withCount('photos')->where('table', 'mlsr_listings');
            /*->whereRaw($response->query); // ->where('subarea', $subarea);*/
        }

        if(!empty($request->route('city'))){
            $listings = $listings->where('city', str_replace(array('-', '~'), array(' ', '-'), stripslashes($request->route('city')) ) );
        }
        if(!empty($request->route('subarea'))){
            $_routeSubareaRaw  = Helper::deslugPlace(stripslashes($request->route('subarea')));
            $_routeSubareaSlug = strtolower(str_replace(' ', '-', $_routeSubareaRaw));
            $_knownTypeSlugsFLWRP = ['house','houses','apartment','apartments','condo','condos','townhouse','townhouses','mobile','mobiles','land','lands','duplex','duplexes','triplex','triplexes','fourplex','fourplexes'];
            if(in_array($_routeSubareaSlug, $_knownTypeSlugsFLWRP, true) && empty($request->route('type'))){
                // The subarea slot actually holds a property-type slug (e.g. /search-listings/surrey/duplex).
                // Apply it as a type filter instead of a subarea filter.
                $_typeArrFLWRP = $this->getTypeArrayFromRouteSlug($_routeSubareaSlug);
                if(!empty($_typeArrFLWRP)){
                    $listings = $listings->whereIn('type', $_typeArrFLWRP);
                }
            } else {
                $listings = $listings->where('subarea', $_routeSubareaRaw);
            }
        }

        // Apply type filter from route segment (e.g. /search-listings/burnaby/metrotown/apartment)
        $_routeType = $request->route('type');
        if(!empty($_routeType) && is_string($_routeType)){
            $_routeTypeArray = $this->getTypeArrayFromRouteSlug($_routeType);
            if(!empty($_routeTypeArray)){
                $listings = $listings->whereIn('type', $_routeTypeArray);
            }
        }
        // Apply feature filter from route segment (e.g. /search-listings/burnaby/metrotown/house/with-suite)
        $_routeFeature = $request->route('feature');
        if(!empty($_routeFeature) && is_string($_routeFeature)){
            if($_routeFeature === 'with-suite'){
                $listings = $listings->where('kitchens', '>=', 3);
            }elseif($_routeFeature === 'with-basement'){
                $listings = $listings->whereNotNull('basement')->where('basement', '!=', '');
            }elseif($_routeFeature === 'new-construction'){
                $listings = $listings->where('yearbuilt', '>=', (int)date('Y') - 5);
            }elseif(preg_match('/^(\d+)-bedroom$/', $_routeFeature, $_bm)){
                $listings = $listings->where('bedrooms', (int)$_bm[1]);
            }elseif($this->isPriceSlug($_routeFeature)){
                $_priceRange = $this->parsePriceSlug($_routeFeature);
                if(!empty($_priceRange)){
                    if($_priceRange['from'] > 0) $listings = $listings->where('listprice_2', '>=', $_priceRange['from']);
                    if($_priceRange['to'] > 0)   $listings = $listings->where('listprice_2', '<=', $_priceRange['to']);
                }
            }
        }

        /* GENERAL-code for mapped-input-args--with--table-fields */
            
        /* $plusArgs=['kitchens'=>'kitchens']; */
        $plusArgs = ['beds'=>'bedrooms','baths'=>'bathstotal','kitchens'=>'kitchens','frontage'=>'frontage','levels'=>'finished_levels'];
        $_plus_suffixes = ['-or-more','+']; // /*,'plus'*/];

        foreach($plusArgs as $_arg=>$_mappedField){

            if( ($request->input($_arg,'')!='') && is_numeric( $request->input($_arg)) ){
                $_val = $request->input($_arg);
                $listings = $listings->where($_mappedField,'=', $_val );
            }else{
                foreach ($_plus_suffixes as $_plus_suffix) {
                    if( substr($request->input($_arg,'')??'', 0-(strlen($_plus_suffix)) )==$_plus_suffix ){
                            // for-usable with args like: beds=2plus... AsWellAs beds=2+
                        $_val = explode($_plus_suffix, $request->input($_arg) )[0];
                        $listings = $listings->where($_mappedField,'>=', $_val );
                    }else{}
                }
            }
        }

        // if(!empty($city)){
        //     $listings = $listings->where('city', $city );
        // }

        if(!empty($request->input('city'))){
            $listings = $listings->where('city', $request->input('city') );
        }

        if(!empty($request->route('beds'))){
            if(intval($request->route('beds'))==$request->route('beds')){
                $listings = $listings->where('bedrooms','=', $request->route('beds') );
            }else{
                $listings = $listings->where('bedrooms','>=', intval($request->route('beds')) );
            }
        }

        if(!empty($request->input('pricefrom'))){
            $listings = $listings->where('listprice_2','>=', $request->input('pricefrom') );
            /* Other-logic--for-sold:: Check Listing-Model*/
        }

        if(!empty($request->input('priceto'))){
            $listings = $listings->where('listprice_2','<=', $request->input('priceto') );
        }

        if(!empty($request->input('sqftfrom'))){
            $listings = $listings->where('livingarea_2','>=', $request->input('sqftfrom') );
        }

        if(!empty($request->input('sqftto'))){
            $listings = $listings->where('livingarea_2','<=', $request->input('sqftto') );
        }

        if(!empty($request->input('subareas')) && is_array($request->input('subareas'))){
            $listings = $listings->whereIn('subarea', $request->input('subareas') );
        }
        if(!empty($request->input('filter_subareas')) && is_array($request->input('filter_subareas'))){
            $listings = $listings->whereIn('subarea', $request->input('filter_subareas') );
        }

        if(!empty($request->input('types')) && is_array($request->input('types'))){
            $_filter_TownHouseTypesArray =  array('Townhouse', 'Duplex', 'Fourplex', 'Triplex');
            if(in_array('Townhouse', $request->input('types')) ){
                $listings = $listings->whereIn('type', array_merge($_filter_TownHouseTypesArray, $request->input('types')) );
            }else{
                $listings = $listings->whereIn('type', $request->input('types') );
            }
        }
        if(!empty($request->input('filter_types')) && is_array($request->input('filter_types'))){
            $_filter_TownHouseTypesArray =  array('Townhouse', 'Duplex', 'Fourplex', 'Triplex');
            if(in_array('Townhouse', $request->input('filter_types')) ){
                $listings = $listings->whereIn('type', array_merge($_filter_TownHouseTypesArray, $request->input('filter_types')) );
            }else{
                $listings = $listings->whereIn('type', $request->input('filter_types') );
            }
        }

        if(!empty($request->input('built_btw')) && !empty($request->input('built_btw')[0]) && !empty($request->input('built_btw')[1]) ){
            if(! ($request->input('built_btw')[0]==1900  && $request->input('built_btw')[1]==1900) )
                $listings = $listings->where('yearbuilt','>=', min($request->input('built_btw')) )->where('yearbuilt','<=', max($request->input('built_btw')) );
        }

        if(strtolower($request->input('listing_status',''))=='sold' || strtolower($request->input('lststatus',''))=='sold'  ){

            $listings = $listings->where('status','Sold');

            if(!empty($request->input('dom'))){
                $listings = $listings->whereRaw(' DATE_SUB(`sold_date`,INTERVAL '.substr(str_replace('_', ' ', $request->input('dom')),0,-1) .' ) <= `list_date` ' );
            }
            if(!empty($request->input('soldwithin'))){
                $listings = $listings->whereRaw(' DATE_SUB(NOW(),INTERVAL '.substr(str_replace('_', ' ', $request->input('soldwithin')),0,-1) .' ) <= `list_date` ' );
            }

        }elseif( strtolower($request->input('listing_status','')=='active' || strtolower($request->input('lststatus',''))=='active') ){

            $listings = $listings->where('status','Active');

            if(!empty($request->input('dom'))){
                $listings = $listings->whereRaw(' DATE_SUB(NOW(),INTERVAL '.substr(str_replace('_', ' ', $request->input('dom')),0,-1) .' ) <= `list_date` ' );
            }

        }else{                
            $listings = $listings->where(function ($q) {
                $q->where('status', 'Active')->orWhere('status', 'Sold');
            });
        }

        return $listings;
    }


    /**
     * [getApiReadyData_for_sale_listings description]
     * @param  [type]  $slug    [description]
     * @param  boolean $subarea [description]
     * @param  boolean $beds    [description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function getApiReadyData_for_sale_listings($slug, $subarea = false, $beds=false, Request $request=null){
        $request = $request??request();

        $_apiReadyAssocArray = $this->getBladeReadyData_for_sale_listings($slug, $subarea, $beds,$request);

        $listings = $_apiReadyAssocArray['listings'];

        $listing_exclude_fields_list = ['location','table'];
        
        if(false && !Auth::user()){
            // $listing_exclude_fields_list = array_merge($listing_exclude_fields_list,['soldprice','soldprice_2',/*'listprice','listprice_2'*/]);
            foreach ($listings as $listing) {
                $listing->fill(['soldprice'=>'login-required','soldprice_2'=>'login-required',
                    'pricePerSqft'=>'pp',
                    // 'pricePerSqft2'=>$listing->price_per_sqft,
                ]);
            }
        }

        // $listings->selectRaw("'nothing' as `soldprice`");

        $listings_without_undesired_data = $listings->makeHidden($listing_exclude_fields_list); 
        // location-value invalid string ->fails-json-encoding

        $_apiReadyAssocArray['listings']->data = $listings_without_undesired_data;
        
        if($_apiReadyAssocArray['place']->query){
            unset($_apiReadyAssocArray['place']->query); 
        }



        return $_apiReadyAssocArray;
        // return response()->json($listings_without_undesired_data);
    }

    public function get_api_for_sale($slug, $subarea = false, $beds=false, Request $request=null){
        $request = $request??request();
        
        return $this->getApiReadyData_for_sale_listings($slug, $subarea, $beds,$request); 
        // return response()->json($this->getApiReadyData_for_sale_listings($slug, $subarea, $beds,$request));

    }

    /**
     * [get_place_for_sale_with_beds last-edited-and-enabled:17-Aug-2021]
     * USAGE: eg: 2-bedroom-vancouver-condos-for-sale
     * @param  boolean $beds    [description]
     * @param  [type]  $slug    [description]
     * @param  boolean $subarea [description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function get_place_for_sale_with_beds($beds=false, $slug='', $subarea = false, Request $request=null)
    {
        $request = $request??request();
        // Reject non-numeric/non-standard beds values (e.g. 'bedsplaceholder' JS template artifacts) [added:Task#402]
        if ($beds && !preg_match('/^(\d+(-or-more|\+)?|studio)$/i', (string)$beds)) {
            abort(404);
        }
        // just adjusting the parameters' order and processing:
        return $this->get_place_for_sale($slug, $subarea, $beds,$request);
    }

    public function render_for_sale_slugFilteredListings($slug=false, $subarea = false, $beds=false, Request $request=null){
        $request = $request??request();
        if(!$slug){
            // config(['app.debug'=>true]);
            $_bladeReadyAssocArray = [
                'listings' => [],
                'place' => null,
                'subarea' => null,
                'subareas' => []
            ];
            $_bladeReadyAssocArray = $this->get_api_adv_search_listings_per_city_subarea(false, $subarea, $beds, $request);
        }else{
            $_bladeReadyAssocArray = $this->getBladeReadyData_for_sale_listings($slug, $subarea, $beds, $request);
        }
        // Save area tag to session for all visitors (including guests) so the
        // FUB registration push includes the tag if they sign up from this page.
        FubAreaHelper::saveToSession($slug);

        if(Auth::user()){
            return response(view('frontend.search_listings_ng')->with($_bladeReadyAssocArray))
                ->header('Cache-Control', 'no-store, private');
        }
        else{
            //return redirect(route('subscription_pricing_table'));
            return view('frontend.search_listings_static')->with($_bladeReadyAssocArray);
        }
        //return view('frontend.search_listings_ng')->with($_bladeReadyAssocArray);
    }

    /**
     * [render_for_sale_slugAndBedsFilteredListings created:17-08-2021]
     * @param  boolean $beds    [description]
     * @param  boolean $slug    [description]
     * @param  boolean $subarea [description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function render_for_sale_slugAndBedsFilteredListings($beds=false, $slug=false, $subarea = false, Request $request=null){
        $request = $request??request();
        // just adjusting the parameters' order and processing:
        return $this->render_for_sale_slugFilteredListings($slug,$subarea,$beds,$request);
    }

    public function render_adv_search_listings($city=false, $subarea = false, $beds=false, Request $request=null){
        $request = $request??request();

        // 301-redirect plain ?type= scalar param to clean route-segment URL [Task#447]
        $_scalarType = $request->query('type');
        if (is_string($_scalarType) && $_scalarType !== '') {
            $_typeSlugMap = [
                'house'     => 'house',     'houses'     => 'house',
                'apartment' => 'apartment', 'apartments' => 'apartment',
                'condo'     => 'apartment', 'condos'     => 'apartment',
                'townhouse' => 'townhouse', 'townhouses' => 'townhouse',
                'duplex'    => 'duplex',    'duplexes'   => 'duplex',
                'triplex'   => 'triplex',   'triplexes'  => 'triplex',
                'fourplex'  => 'fourplex',  'fourplexes' => 'fourplex',
            ];
            $_mappedSlug = $_typeSlugMap[strtolower(trim($_scalarType))] ?? null;
            if ($_mappedSlug) {
                // Build clean URL: /search-listings/{city}[/{subarea}]/{type}
                $_segments = array_filter([$city, $subarea, $_mappedSlug], fn($v) => $v !== false && $v !== '');
                $_cleanPath = '/search-listings/' . implode('/', array_values($_segments));
                // Preserve other query params (beds, baths, etc.) but drop 'type'
                $_otherParams = $request->except('type');
                if (!empty($_otherParams)) {
                    $_cleanPath .= '?' . http_build_query($_otherParams);
                }
                return redirect($_cleanPath, 301);
            }
        }

        $_bladeReadyAssocArray = $this->get_api_adv_search_listings_per_city_subarea($city, $subarea, $beds, $request);

        // Save area tag to session for all visitors (including guests) so the
        // FUB registration push includes the tag if they sign up from this page.
        FubAreaHelper::saveToSession($city);

        // FUB: track search page view for phone-verified logged-in users
        $fubSearchMsg = $city ? ucwords($city) : 'BC';
        if ($subarea && $subarea !== false) {
            $fubSearchMsg .= ' — ' . ucwords(str_replace('-', ' ', (string)$subarea));
        }
        $fubType = $request->route('type');
        if ($fubType) {
            $fubSearchMsg .= ' ' . ucwords(str_replace('-', ' ', $fubType));
        }
        FubAreaHelper::pushSearchEvent(
            $fubSearchMsg . ' listings search',
            request()->fullUrl(),
            $city ?: null
        );

        if(Auth::user()){
            return response(view('frontend.search_listings_ng')->with($_bladeReadyAssocArray))
                ->header('Cache-Control', 'no-store, private');
        }
        else{
            //return redirect(route('subscription_pricing_table'));
            return view('frontend.search_listings_static')->with($_bladeReadyAssocArray);
        }
    }

    // public function render_adv_search_listings_2($city=false, $subarea = false, $beds=false, Request $request=null){
    //     $request = $request??request();
    //     $_bladeReadyAssocArray = $this->get_api_adv_search_listings_per_city_subarea($city, $subarea, $beds, $request);
    //     return view('frontend.search_listings_static')->with($_bladeReadyAssocArray);
    //     //print_r($_bladeReadyAssocArray);
    //     //exit;
    // }


    /**
     * get_api_properties_per_city_subarea function-almost-copy-of getApiReadyData_for_sale_listings
     * @return array collection-of-Listings, place, subarea
     */
    public function get_api_adv_search_listings_per_city_subarea($city=false, $subarea = false, $beds=false, Request $request=null)
    {
        $request = $request??request();
        // $sql = "select * from bccondosandhomes.mls_query where slug = '" . $slug . "'";
        $res =  true;// DB::select(/*DB::raw*/($sql));
        
        if ($request->routeIs('api*') && $request->input('_token',false)!= csrf_token())
        {
            $res = false;
            throw new \Illuminate\Session\TokenMismatchException;
        }

        if ($res) {
            $per_page = Auth::check()
                ? (int) $request->input('per_page', 200)
                : min((int) $request->input('per_page', 50), 50);
            $max_results = 500;
            $page = 1;
            $max_pages = ceil($max_results / $per_page);
            $properties_sent = 0;

            if ($city) {
                $city = Helper::deslugPlace(stripslashes($city));
            }
            if ($subarea) {
                $subarea = Helper::deslugPlace(stripslashes($subarea));
            }

            if ($request->input('page') > 0) {
                $page = $request->input('page');
                $properties_sent = ($page - 1) * $per_page;
            }

            $orderByField = 'list_date'; // 'last_modified'; // [updated:03-10-2022] changed-lastModified2listDate
            $orderByOrder = 'desc';

            $arrayMapViewSelect2Field = ['default'=>'last_modified','listdate'=>'list_date','listprice_2'=>'listprice_2','livingarea_2'=>'livingarea_2'];

            if (!empty($request->input('sort_by')) ) {
                $orderByTemp = explode('|',$request->input('sort_by'));
                $orderByField = $orderByTemp[0];
                if(!empty($orderByTemp[0])){
                    $orderByField = $orderByTemp[0];
                }
                if(!empty($orderByTemp[1]) && strtolower($orderByTemp[1])=='desc'){
                    $orderByOrder = 'desc';
                }else{
                    $orderByOrder = 'asc';
                }
                $orderByField = $arrayMapViewSelect2Field[$orderByField];
            }

            $listings = Listings::with('aphoto')->withCount('photos')->where('table', 'mlsr_listings');//->whereRaw($response->query);

            /*Using-generalized-function-for-listings-filters*/
            $listings = $this->filterListingsWithRequestParams($listings,$request);

            // /* GENERAL-code for mapped-input-args--with--table-fields */
            // $listings = Listings::with('aphoto')->withCount('photos')->where('table', 'mlsr_listings');//->whereRaw($response->query);
            //     // ->where('subarea', $subarea);
            
            // // $plusArgs=['kitchens'=>'kitchens'];
            // $plusArgs = ['beds'=>'bedrooms','baths'=>'bathstotal','kitchens'=>'kitchens','frontage'=>'frontage','levels'=>'finished_levels'];
            // $_plus_suffixes = ['-or-more','+' /*,'plus'*/];

            // foreach($plusArgs as $_arg=>$_mappedField){

            //     if( ($request->input($_arg,'')!='') && is_numeric( $request->input($_arg)) ){
            //         $_val = $request->input($_arg);
            //         $listings = $listings->where($_mappedField,'=', $_val );
            //     }else{
            //         foreach ($_plus_suffixes as $_plus_suffix) {
            //             if( substr($request->input($_arg), 0-(strlen($_plus_suffix)) )==$_plus_suffix ){
            //                 // for-usable with args like: beds=2plus... AsWellAs beds=2+
            //                 $_val = explode($_plus_suffix, $request->input($_arg) )[0];
            //                 $listings = $listings->where($_mappedField,'>=', $_val );
            //             }else{}
            //         }
            //     }
            // }
            // // 

            // if($city){
            //     $listings = $listings->where('city', $city );
            // }
            // if(!empty($request->input('city'))){
            //     $listings = $listings->where('city', $request->input('city') );
            // }

            // if(!empty($request->route('beds'))){
            //     if(intval($request->route('beds'))==$request->route('beds')){
            //         $listings = $listings->where('bedrooms','=', $request->route('beds') );
            //     }else{
            //         $listings = $listings->where('bedrooms','>=', str_replace($_plus_suffix,'', $request->route('beds') ) );
            //     }
            // }

            // if(!empty($request->input('pricefrom'))){
            //     $listings = $listings->where('listprice_2','>=', $request->input('pricefrom') );
            //     /* Other-logic--for-sold:: Check Listing-Model*/
            // }

            // if(!empty($request->input('priceto'))){
            //     $listings = $listings->where('listprice_2','<=', $request->input('priceto') );
            // }

            // if(!empty($request->input('sqftfrom'))){
            //     $listings = $listings->where('livingarea_2','>=', $request->input('sqftfrom') );
            // }

            // if(!empty($request->input('sqftto'))){
            //     $listings = $listings->where('livingarea_2','<=', $request->input('sqftto') );
            // }

            // if(!empty($request->input('subareas')) && is_array($request->input('subareas'))){
            //     $listings = $listings->whereIn('subarea', $request->input('subareas') );
            // }
            // if(!empty($request->input('filter_subareas')) && is_array($request->input('filter_subareas'))){
            //     $listings = $listings->whereIn('subarea', $request->input('filter_subareas') );
            // }

            // if(!empty($request->input('types')) && is_array($request->input('types'))){
            //     $_filter_TownHouseTypesArray =  array('Townhouse', 'Duplex', 'Fourplex', 'Triplex');
            //     if(in_array('Townhouse', $request->input('types')) ){
            //         $listings = $listings->whereIn('type', array_merge($_filter_TownHouseTypesArray, $request->input('types')) );
            //     }else{
            //         $listings = $listings->whereIn('type', $request->input('types') );
            //     }
            // }
            // if(!empty($request->input('filter_types')) && is_array($request->input('filter_types'))){
            //     $_filter_TownHouseTypesArray =  array('Townhouse', 'Duplex', 'Fourplex', 'Triplex');
            //     if(in_array('Townhouse', $request->input('filter_types')) ){
            //         $listings = $listings->whereIn('type', array_merge($_filter_TownHouseTypesArray, $request->input('filter_types')) );
            //     }else{
            //         $listings = $listings->whereIn('type', $request->input('filter_types') );
            //     }
            // }

            // if(!empty($request->input('built_btw')) && !empty($request->input('built_btw')[0]) && !empty($request->input('built_btw')[1]) ){
            //     if(! ($request->input('built_btw')[0]==1900  && $request->input('built_btw')[1]==1900) )
            //     $listings = $listings->where('yearbuilt','>=', min($request->input('built_btw')) )->where('yearbuilt','<=', max($request->input('built_btw')) );
            // }

            // if(strtolower($request->input('listing_status',''))=='sold' || strtolower($request->input('lststatus',''))=='sold'  ){

            //     $listings = $listings->where('status','Sold');

            //     if(!empty($request->input('dom'))){
            //         $listings = $listings->whereRaw(' DATE_SUB(`sold_date`,INTERVAL '.substr(str_replace('_', ' ', $request->input('dom')),0,-1) .' ) <= `list_date` ' );
            //     }
            //     if(!empty($request->input('soldwithin'))){
            //         $listings = $listings->whereRaw(' DATE_SUB(NOW(),INTERVAL '.substr(str_replace('_', ' ', $request->input('soldwithin')),0,-1) .' ) <= `list_date` ' );
            //     }

            //     // Put following block to @else after-publish
            // }elseif((strtolower($request->input('listing_status',''))=='active' || strtolower($request->input('lststatus',''))=='active') || (Auth::user() && substr(Auth::user()->email,-12)=='pixilink.com') ){

            //     $listings = $listings->where('status','Active');

            //     if(!empty($request->input('dom'))){
            //         $listings = $listings->whereRaw(' DATE_SUB(NOW(),INTERVAL '.substr(str_replace('_', ' ', $request->input('dom')),0,-1) .' ) <= `list_date` ' );
            //     }

            // }else{                
            //     $listings = $listings->where(function ($q) {
            //         $q->where('status', 'Active')->orWhere('status', 'Sold');
            //     });
            // }
            


            $_valueSignalStats = $this->computeValueSignalStats($listings);

            $listings = $listings->orderBy($orderByField, $orderByOrder)->paginate($per_page);

            $subareas = [];
            $_subareasCacheKey = 'sl_subareas_' . md5($city);
            $_subareaRows = Cache::remember($_subareasCacheKey, 3600, function() use ($city) {
                $sql = "select subarea, COUNT(*) as cnt from boards.listings where city = '" . addslashes($city) . "' and status='Active' and `table` = 'mlsr_listings' and subarea is not null and subarea != '' group by subarea order by subarea LIMIT 100";
                return DB::select($sql);
            });
            if ($_subareaRows && count($_subareaRows) > 0) {
                foreach ($_subareaRows as $_row) {
                    $area['link'] = "https://www.bccondosandhomes.com/search-listings/".\App\Helpers\Helper::enslugPlace($city)."/". $this->seoUrl(trim($_row->subarea));
                    $area['subarea'] = $_row->subarea;
                    $area['listings_count'] = $_row->cnt;
                    $subareas[] = $area;
                }
            }

            // if ($page == $max_pages) {
            //     $remainig_properties = $max_results - $properties_sent;
            //     $listings_count = count($listings);
            //     for ($i = 0; $i < $listings_count; $i++) {
            //         if (($i + 1) > $remainig_properties) {
            //             unset($listings[$i]);
            //         }
            //     }
            // }
            //}

            // $listings = $listings->makeHidden(['location']);

            
            $listing_exclude_fields_list = ['location'/*,'table'*/]; // location-value invalid string ->fails-json-encoding

            $listings->getCollection()->transform(
                function ($item) use ($listing_exclude_fields_list) {
                    foreach($listing_exclude_fields_list as $_exField){
                        unset($item->$_exField);
                    }
                    if(!Auth::user()){
                        $item->fill(['soldprice'=>'login-required', 'soldprice_2'=>'login-required', 'pricePerSqft'=>'-', ]);
                        // 'pricePerSqft2'=>$listing->price_per_sqft,
                    }
                    return $item;
                }
            );

            /**
             * Following-block included in above-code-block
             */
            // if(!Auth::user()){
            //     // $listing_exclude_fields_list = array_merge($listing_exclude_fields_list,['soldprice','soldprice_2',/*'listprice','listprice_2'*/]);
            //     foreach ($listings as $listing) {
            //         $listing->fill(['soldprice'=>'login-required','soldprice_2'=>'login-required',
            //             'pricePerSqft'=>'-',
            //         // 'pricePerSqft2'=>$listing->price_per_sqft,
            //         ]);
            //     }
            // }

            // $listings_without_undesired_data = $listings->getCollection()->makeHidden($listing_exclude_fields_list);
            // $listings->data = $listings_without_undesired_data; 

            \Debugbar::debug($listings, clone($listings)->take(1));

            $_bladeReadyAssocArray = [
                'listings' => $listings,
                'listings_count' => $listings->total(),
                'place' => '', //$res[0],
                'subarea' => '', //$subarea,
                'subareas' => $subareas,
                'avgPricePerSqft' => $_valueSignalStats['avgPricePerSqft'],
                'medianListPrice' => $_valueSignalStats['medianListPrice'],
            ];

            // Compute market stats and SEO data (only when city is specified to avoid full-table scans)
            $_bladeReadyAssocArray['marketStats'] = [];
            $_bladeReadyAssocArray['seoData'] = [];
            try {
                $_typeSlug  = (string)($request->route('type') ?: '');
                $_feature   = (string)($request->route('feature') ?: '');
                $_cityStr = (string)($city ?: '');
                $_subareaStr = (string)($subarea ?: '');

                // Safeguard: detect mis-routing where the catch-all consumed {city}/{type}/{feature}
                // as {city}/{subarea}/{type} (happens when route cache is stale or regenerated).
                // Symptom: $_subareaStr holds a type slug (e.g. "House") and $_feature is empty
                // while $_typeSlug holds a feature slug (e.g. "new-construction").
                $_knownTypeSlugs   = ['house','houses','apartment','apartments','condo','condos','townhouse','townhouses','mobile','mobiles','land','lands','duplex','duplexes','triplex','triplexes','fourplex','fourplexes'];
                $_knownFeaturePat  = '/^(with-suite|with-basement|new-construction|\d+-bedroom|under-\d+[km]|over-\d+[km]|\d+[km]-to-\d+[km])$/';
                $_subareaAsSlug    = strtolower(str_replace([' ', '~'], ['-', '-'], $_subareaStr));
                // Case 0: 3-segment route {city}/{type}/{feature} causes Laravel to positionally
                // inject {type} into $subarea (the 2nd untyped param with no name match) and
                // {feature} into $beds. Symptom: $_subareaStr and $_typeSlug hold the same
                // type slug (e.g. both "house"). Clear the spurious $subarea.
                if($_subareaAsSlug !== '' && in_array($_subareaAsSlug, $_knownTypeSlugs, true) && $_subareaAsSlug === strtolower($_typeSlug)){
                    $_subareaStr    = '';
                    $_subareaAsSlug = '';
                }
                if($_feature === '' && in_array($_subareaAsSlug, $_knownTypeSlugs, true)){
                    if(preg_match($_knownFeaturePat, $_typeSlug)){
                        // Mis-routing: {city}/{type}/{feature} consumed as {city}/{subarea}/{type}
                        $_feature    = $_typeSlug;
                        $_typeSlug   = $_subareaAsSlug;
                        $_subareaStr = '';
                    } elseif($_typeSlug === ''){
                        // URL is {city}/{type} with the type slug landing in the subarea slot
                        // e.g. /search-listings/surrey/duplex
                        $_typeSlug   = $_subareaAsSlug;
                        $_subareaStr = '';
                    }
                }
                // Case 2: price slug in subarea slot + type slug in type slot
                // e.g. /search-listings/new-westminster/1m-to-2m/house (generated by old ?type= redirect)
                if($_feature === '' && $this->isPriceSlug($_subareaAsSlug) && in_array(strtolower($_typeSlug), $_knownTypeSlugs, true)){
                    $_feature    = $_subareaAsSlug;
                    $_subareaStr = '';
                }
                // Case 3: price slug in subarea slot, no type slug
                // e.g. /search-listings/new-westminster/1m-to-2m (catch-all with only 2 segments)
                if($_feature === '' && $this->isPriceSlug($_subareaAsSlug) && $_typeSlug === ''){
                    $_feature    = $_subareaAsSlug;
                    $_subareaStr = '';
                }

                $_bedsFilter = 0;
                if(preg_match('/^(\d+)-bedroom$/', $_feature, $_bm2)){
                    $_bedsFilter = (int)$_bm2[1];
                }
                if($_cityStr || $_subareaStr){
                    if(!Auth::check()){
                        $_statsCacheKey = 'sl_mkt_' . md5("{$_cityStr}|{$_subareaStr}|{$_typeSlug}|{$_feature}|{$_bedsFilter}");
                        $_cachedStats = Cache::remember($_statsCacheKey, 1800, function() use ($_cityStr, $_subareaStr, $_typeSlug, $_feature, $_bedsFilter){
                            return $this->computeMarketStats($_cityStr, $_subareaStr, $_typeSlug, $_feature, $_bedsFilter);
                        });
                        $_bladeReadyAssocArray['marketStats'] = $_cachedStats;
                        $_bladeReadyAssocArray['seoData'] = $this->buildSeoData(
                            $_cityStr, $_subareaStr, $_typeSlug, $_feature, $_bedsFilter, $_cachedStats
                        );
                    } else {
                        $_bladeReadyAssocArray['marketStats'] = $this->computeMarketStats(
                            $_cityStr, $_subareaStr, $_typeSlug, $_feature, $_bedsFilter
                        );
                        $_bladeReadyAssocArray['seoData'] = $this->buildSeoData(
                            $_cityStr, $_subareaStr, $_typeSlug, $_feature, $_bedsFilter,
                            $_bladeReadyAssocArray['marketStats']
                        );
                    }
                }
            } catch(\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('SearchListings seoData error: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
            }

            $_bladeReadyAssocArray['minPrice'] = (int)($_bladeReadyAssocArray['marketStats']['min_price'] ?? 0);
            $_bladeReadyAssocArray['maxPrice'] = (int)($_bladeReadyAssocArray['marketStats']['max_price'] ?? 0);

            return $_bladeReadyAssocArray;

        } else {
            abort(404);
        }
    }

    // =========================================================
    // MARKET STATS & SEO DATA HELPERS  [added:Task#8]
    // =========================================================

    /**
     * Map URL type slug to DB type array.
     * e.g. 'house' => ['House'], 'apartment' => ['Apartment'], 'townhouse' => ['Townhouse','Duplex','Fourplex','Triplex']
     */
    protected function getTypeArrayFromRouteSlug(string $typeSlug): array
    {
        $t = strtolower(trim($typeSlug));
        if(in_array($t, ['house','houses','detached'])){ return ['House']; }
        if(in_array($t, ['apartment','apartments','condo','condos','condominium'])){ return ['Apartment']; }
        if(in_array($t, ['townhouse','townhouses'])){ return ['Townhouse','Duplex','Fourplex','Triplex']; }
        if(in_array($t, ['duplex','duplexes'])){ return ['Duplex']; }
        if(in_array($t, ['triplex','triplexes'])){ return ['Triplex']; }
        if(in_array($t, ['fourplex','fourplexes'])){ return ['Fourplex']; }
        return [];
    }

    /** Human-readable label from type slug. */
    protected function getTypeLabel(string $typeSlug, bool $plural = true): string
    {
        $t = strtolower(trim($typeSlug));
        $map = [
            'house'=>['house','houses'],'houses'=>['house','houses'],'detached'=>['house','houses'],
            'apartment'=>['condo','condos'],'apartments'=>['condo','condos'],
            'condo'=>['condo','condos'],'condos'=>['condo','condos'],
            'townhouse'=>['townhouse','townhouses'],'townhouses'=>['townhouse','townhouses'],
            'duplex'=>['duplex','duplexes'],'duplexes'=>['duplex','duplexes'],
            'triplex'=>['triplex','triplexes'],'triplexes'=>['triplex','triplexes'],
            'fourplex'=>['fourplex','fourplexes'],'fourplexes'=>['fourplex','fourplexes'],
        ];
        $pair = $map[$t] ?? ['home','homes'];
        return $plural ? $pair[1] : $pair[0];
    }

    /** Format as $1,250,000 */
    protected function formatMoney(float $amount): string
    {
        if($amount <= 0) return 'N/A';
        return '$'.number_format((int)round($amount), 0, '.', ',');
    }

    /** Compute median from array of numbers. */
    protected function computeMedian(array $values): float
    {
        if(empty($values)) return 0.0;
        sort($values);
        $count = count($values);
        $mid = (int)floor($count / 2);
        return ($count % 2 === 0) ? ($values[$mid-1] + $values[$mid]) / 2.0 : (float)$values[$mid];
    }

    /**
     * Build base Eloquent query for stats (no photos, no pagination, city/subarea/type/feature/beds applied).
     */
    protected function buildBaseStatsQuery(string $city, string $subarea, array $types, string $feature, int $bedsFilter)
    {
        $q = DB::connection('mysql_boards')->table('listings')->where('table','mlsr_listings');
        if($city)   { $q = $q->where('city', $city); }
        if($subarea){ $q = $q->where('subarea', $subarea); }
        if(!empty($types)){ $q = $q->whereIn('type', $types); }
        if($bedsFilter > 0){ $q = $q->where('bedrooms', $bedsFilter); }
        if($feature === 'with-suite')       { $q = $q->where('kitchens','>=',3); }
        elseif($feature === 'with-basement'){ $q = $q->whereNotNull('basement')->where('basement','!=',''); }
        elseif($feature === 'new-construction'){ $q = $q->where('yearbuilt','>=',(int)date('Y')-5); }
        elseif($this->isPriceSlug($feature)){
            $_pr = $this->parsePriceSlug($feature);
            if(!empty($_pr)){
                if($_pr['from'] > 0) $q = $q->where('listprice_2', '>=', $_pr['from']);
                if($_pr['to'] > 0)   $q = $q->where('listprice_2', '<=', $_pr['to']);
            }
        }
        return $q;
    }

    /** Get price band definitions for a type slug. */
    protected function getPriceBandDefs(string $typeSlug): array
    {
        $t = strtolower($typeSlug);
        if(in_array($t,['house','houses','detached'])){
            return [
                [0,600000,'Under $600K'],[600001,800000,'$600K–$800K'],
                [800001,1000000,'$800K–$1M'],[1000001,1250000,'$1M–$1.25M'],
                [1250001,1500000,'$1.25M–$1.5M'],[1500001,1750000,'$1.5M–$1.75M'],
                [1750001,2000000,'$1.75M–$2M'],[2000001,2500000,'$2M–$2.5M'],
                [2500001,3000000,'$2.5M–$3M'],[3000001,PHP_INT_MAX,'Over $3M'],
            ];
        }
        if(in_array($t,['townhouse','townhouses'])){
            return [
                [0,400000,'Under $400K'],[400001,500000,'$400K–$500K'],
                [500001,600000,'$500K–$600K'],[600001,700000,'$600K–$700K'],
                [700001,800000,'$700K–$800K'],[800001,900000,'$800K–$900K'],
                [900001,1000000,'$900K–$1M'],[1000001,1250000,'$1M–$1.25M'],
                [1250001,1500000,'$1.25M–$1.5M'],[1500001,PHP_INT_MAX,'Over $1.5M'],
            ];
        }
        // Apartment / default
        return [
            [0,300000,'Under $300K'],[300001,400000,'$300K–$400K'],
            [400001,500000,'$400K–$500K'],[500001,600000,'$500K–$600K'],
            [600001,700000,'$600K–$700K'],[700001,800000,'$700K–$800K'],
            [800001,900000,'$800K–$900K'],[900001,1000000,'$900K–$1M'],
            [1000001,1250000,'$1M–$1.25M'],[1250001,1500000,'$1.25M–$1.5M'],
            [1500001,2000000,'$1.5M–$2M'],[2000001,PHP_INT_MAX,'Over $2M'],
        ];
    }

    /** Compute price band breakdown (inventory vs sales vs ratio). */
    protected function computePriceBands($baseQuery, string $typeSlug, array $activePrices = []): array
    {
        $bandDefs = $this->getPriceBandDefs($typeSlug);
        $soldPrices = (clone $baseQuery)
            ->where('status','Sold')
            ->where('sold_date','>=',Carbon::now()->subDays(30))
            ->where('soldprice_2','>',0)
            ->limit(2000)->pluck('soldprice_2')->toArray();

        $result = []; $maxRatio = 0;
        foreach($bandDefs as [$min,$max,$label]){
            $inventory = 0;
            foreach($activePrices as $p){ if($p>=$min && ($max===PHP_INT_MAX || $p<=$max)) $inventory++; }
            $sales = 0;
            foreach($soldPrices as $p){ if($p>=$min && ($max===PHP_INT_MAX || $p<=$max)) $sales++; }
            if($inventory > 0 || $sales > 0){
                $ratio = ($inventory > 0) ? (int)round($sales/$inventory*100) : ($sales > 0 ? 100 : 0);
                $maxRatio = max($maxRatio, $ratio);
                $result[] = ['label'=>$label,'inventory'=>$inventory,'sales'=>$sales,'ratio'=>$ratio,'min'=>$min,'max'=>$max,'is_best'=>false];
            }
        }
        foreach($result as &$row){ $row['is_best'] = ($maxRatio > 0 && $row['ratio'] === $maxRatio); }
        return $result;
    }

    /** Compute bedroom breakdown with links. */
    protected function computeBedroomBreakdown($baseQuery, string $city, string $subarea, string $typeSlug): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $citySlug = strtolower(str_replace(' ','-',$city));
        $subareaSlug = strtolower(str_replace([' ','-'],['-','~'],$subarea));
        $typeSlugClean = strtolower($typeSlug ?: 'house');

        $activeRows = (clone $baseQuery)->where('status','Active')
            ->where('bedrooms','>=',0)->where('bedrooms','<=',8)
            ->selectRaw('bedrooms, COUNT(*) as cnt')->groupBy('bedrooms')->orderBy('bedrooms')
            ->get()->keyBy('bedrooms');
        $soldRows = (clone $baseQuery)->where('status','Sold')->where('sold_date','>=',$thirtyDaysAgo)
            ->where('bedrooms','>=',0)->where('bedrooms','<=',8)
            ->selectRaw('bedrooms, COUNT(*) as cnt')->groupBy('bedrooms')->orderBy('bedrooms')
            ->get()->keyBy('bedrooms');

        $result = [];
        for($b = 0; $b <= 7; $b++){
            $inv = (int)($activeRows->get($b)?->cnt ?? 0);
            $sls = (int)($soldRows->get($b)?->cnt ?? 0);
            if($inv > 0 || $sls > 0){
                $ratio = ($inv > 0) ? (int)round($sls/$inv*100) : 0;
                $label = ($b === 0) ? 'Studio/0 Bed' : "{$b} Bedroom";
                $link = null;
                if($city && $b > 0){
                    $link = "/search-listings/{$citySlug}".($subarea?"/{$subareaSlug}":'')."/{$typeSlugClean}/{$b}-bedroom";
                }
                $result[] = ['label'=>$label,'beds'=>$b,'inventory'=>$inv,'sales'=>$sls,'ratio'=>$ratio,'link'=>$link];
            }
        }
        return $result;
    }

    /** Compute subarea breakdown (city-level pages only). */
    protected function computeSubareaBreakdown($baseQuery, string $city): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $citySlug = strtolower(str_replace(' ','-',$city));

        $activeRows = (clone $baseQuery)->where('status','Active')
            ->whereNotNull('subarea')->where('subarea','!=','')
            ->selectRaw('subarea, COUNT(*) as cnt')->groupBy('subarea')->orderByDesc('cnt')->limit(20)
            ->get()->keyBy('subarea');
        $soldRows = (clone $baseQuery)->where('status','Sold')->where('sold_date','>=',$thirtyDaysAgo)
            ->whereNotNull('subarea')->where('subarea','!=','')
            ->selectRaw('subarea, COUNT(*) as cnt')->groupBy('subarea')
            ->get()->keyBy('subarea');

        $result = [];
        foreach($activeRows as $subareaName => $row){
            $inv = (int)$row->cnt;
            $sls = (int)($soldRows->get($subareaName)?->cnt ?? 0);
            $ratio = ($inv > 0) ? (int)round($sls/$inv*100) : 0;
            $subareaSlug = strtolower(str_replace(' ','-',$subareaName));
            $result[] = ['subarea'=>$subareaName,'inventory'=>$inv,'sales'=>$sls,'ratio'=>$ratio,'link'=>"/search-listings/{$citySlug}/{$subareaSlug}"];
        }
        return $result;
    }

    /**
     * Compute all market stats for the given city/subarea/type/feature/beds scope.
     */
    protected function computeMarketStats(string $city, string $subarea, string $typeSlug, string $feature, int $bedsFilter): array
    {
        $types = $this->getTypeArrayFromRouteSlug($typeSlug);
        $baseQuery = $this->buildBaseStatsQuery($city, $subarea, $types, $feature, $bedsFilter);

        $activeQuery = (clone $baseQuery)->where('status','Active');
        $activeCount = $activeQuery->count();

        $activePrices = (clone $activeQuery)->where('listprice_2','>',0)->orderBy('listprice_2')
            ->limit(2000)->pluck('listprice_2')->toArray();
        $medianListPrice = $this->computeMedian($activePrices);

        $avgData = (clone $activeQuery)->where('livingarea_2','>',0)->where('listprice_2','>',0)
            ->selectRaw('AVG(listprice_2/livingarea_2) as avg_ppsf')->first();
        $avgPriceSqft = (float)($avgData->avg_ppsf ?? 0);

        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $soldQuery = (clone $baseQuery)->where('status','Sold')->where('sold_date','>=',$thirtyDaysAgo);
        $domData = (clone $soldQuery)->whereNotNull('list_date')->whereNotNull('sold_date')->selectRaw('AVG(DATEDIFF(sold_date, list_date)) as avg_dom')->first();
        $avgDom = (int)round((float)($domData->avg_dom ?? 0));
        $salesCount = $soldQuery->count();
        $salesRatio = ($activeCount > 0) ? round($salesCount/$activeCount*100, 1) : 0;

        if($salesRatio < 12)    { $marketType = "Buyer's Market"; $marketColor = '#1565C0'; }
        elseif($salesRatio < 20){ $marketType = "Balanced Market"; $marketColor = '#E65100'; }
        else                    { $marketType = "Seller's Market"; $marketColor = '#B71C1C'; }

        // Auth-gated sold data
        $medianSoldPrice = 0; $saleToListRatio = 0;
        $prevMonthSalesCount = 0; $salesVariance = null;
        if(Auth::check()){
            $soldPrices = (clone $soldQuery)->where('soldprice_2','>',0)->orderBy('soldprice_2')
                ->limit(2000)->pluck('soldprice_2')->toArray();
            $medianSoldPrice = $this->computeMedian($soldPrices);
            $ratioVal = (clone $soldQuery)->where('soldprice_2','>',0)->where('listprice_2','>',0)
                ->selectRaw('AVG(soldprice_2/listprice_2*100) as avg_ratio')->value('avg_ratio');
            $saleToListRatio = round((float)$ratioVal, 1);
            $prevMonthSalesCount = (clone $baseQuery)->where('status','Sold')
                ->where('sold_date','>=',Carbon::now()->subDays(60))
                ->where('sold_date','<',$thirtyDaysAgo)->count();
            if($prevMonthSalesCount > 0){
                $salesVariance = (int)round(($salesCount - $prevMonthSalesCount)/$prevMonthSalesCount*100);
            }
        }

        $priceBands       = $this->computePriceBands($baseQuery, $typeSlug, $activePrices);
        $bedroomBreakdown = $this->computeBedroomBreakdown($baseQuery, $city, $subarea, $typeSlug);
        $subareaBreakdown = [];
        if($city && !$subarea && $activeCount >= 5){
            $subareaBreakdown = $this->computeSubareaBreakdown($baseQuery, $city);
        }

        $priceRangeRow = (clone $activeQuery)->where('listprice_2', '>', 0)
            ->selectRaw('MIN(listprice_2) as min_price, MAX(listprice_2) as max_price')
            ->first();
        $minPrice = (int)($priceRangeRow->min_price ?? 0);
        $maxPrice = (int)($priceRangeRow->max_price ?? 0);

        return [
            'active_count'       => $activeCount,
            'sales_count'        => $salesCount,
            'sales_ratio'        => $salesRatio,
            'market_type'        => $marketType,
            'market_color'       => $marketColor,
            'median_list_price'  => $medianListPrice,
            'avg_price_sqft'     => (int)round($avgPriceSqft),
            'avg_dom'            => $avgDom,
            'median_sold_price'  => $medianSoldPrice,
            'sale_to_list_ratio' => $saleToListRatio,
            'prev_month_sales'   => $prevMonthSalesCount,
            'sales_variance'     => $salesVariance,
            'price_bands'        => $priceBands,
            'bedroom_breakdown'  => $bedroomBreakdown,
            'subarea_breakdown'  => $subareaBreakdown,
            'min_price'          => $minPrice,
            'max_price'          => $maxPrice,
        ];
    }

    /**
     * Build rule-based market summary paragraph.
     */
    protected function buildMarketSummaryText(string $city, string $subarea, string $typeLabel, string $feature, int $bedsFilter, array $stats): string
    {
        if(empty($stats['active_count']) && empty($stats['sales_count'])) return '';
        $areaLabel = $subarea ? rtrim("{$subarea}, {$city}", ', ') : ($city ?: 'BC');
        $prefix = '';
        $suffix = '';
        if($bedsFilter > 0) $prefix = "{$bedsFilter}-bedroom ";
        elseif($feature === 'with-suite') $prefix = 'suite-equipped ';
        elseif($feature === 'new-construction') $prefix = 'new construction ';
        elseif($this->isPriceSlug($feature)){
            $_pr = $this->parsePriceSlug($feature);
            if(!empty($_pr['natural'])) $suffix = ' priced '.strtolower($_pr['natural']);
            elseif(!empty($_pr['label'])) $suffix = ' priced '.strtolower($_pr['label']);
        }

        $parts = [];
        // Market type sentence
        if(!empty($stats['market_type']) && $stats['active_count'] > 0){
            $ratio = $stats['sales_ratio'] ?? 0;
            $s = "The {$prefix}{$typeLabel}{$suffix} market in {$areaLabel} is currently a <strong>{$stats['market_type']}</strong>";
            if($ratio > 0){
                $s .= " with a {$ratio}% sales ratio — {$stats['sales_count']} homes sold against {$stats['active_count']} active listings in the past 30 days";
            }
            $parts[] = $s.".";
        }
        // Price
        if(!empty($stats['median_list_price'])){
            $s = "The median asking price is <strong>".$this->formatMoney($stats['median_list_price'])."</strong>";
            if(!empty($stats['avg_price_sqft'])){ $s .= " (".$this->formatMoney($stats['avg_price_sqft'])."/sqft)"; }
            $parts[] = $s.".";
        }
        // DOM
        if(!empty($stats['avg_dom'])){
            $dom = $stats['avg_dom'];
            $speed = $dom <= 7 ? 'very quickly' : ($dom <= 14 ? 'quickly' : ($dom <= 30 ? 'within a month on average' : 'slowly'));
            $parts[] = "Properties are selling {$speed}, averaging <strong>{$dom} days</strong> on the market.";
        }
        // Sale-to-list (auth-gated)
        if(Auth::check() && !empty($stats['sale_to_list_ratio'])){
            $str = $stats['sale_to_list_ratio'];
            $diff = round(abs($str - 100), 1);
            if($str >= 100) $parts[] = "Homes are selling at <strong>{$str}%</strong> of asking price — on average {$diff}% above list, signalling strong buyer demand.";
            else $parts[] = "Homes are selling at <strong>{$str}%</strong> of asking price — buyers are averaging {$diff}% below list.";
        }
        // Month-over-month (auth-gated)
        if(Auth::check() && $stats['sales_variance'] !== null && $stats['sales_variance'] !== 0){
            $v = $stats['sales_variance'];
            $dir = $v > 0 ? 'up' : 'down';
            $parts[] = "Sales activity is <strong>".abs($v)."%</strong> {$dir} compared to the previous 30 days.";
        }
        return implode(' ', $parts);
    }

    /** Build rule-based FAQ list. */
    protected function buildFaqs(string $city, string $subarea, string $typeLabel, string $feature, int $bedsFilter, array $stats, array $reportUrls = []): array
    {
        $areaLabel = $subarea ? rtrim("{$subarea}, {$city}", ', ') : ($city ?: 'BC');
        $prefix = $bedsFilter > 0 ? "{$bedsFilter}-bedroom " : '';
        $priceSuffix = '';
        $isLuxuryFaq = false;
        if($this->isPriceSlug($feature)){
            $_pr = $this->parsePriceSlug($feature);
            if(!empty($_pr['natural'])) $priceSuffix = ' priced '.strtolower($_pr['natural']);
            elseif(!empty($_pr['label'])) $priceSuffix = ' priced '.strtolower($_pr['label']);
            $isLuxuryFaq = !empty($_pr['from']) && $_pr['from'] >= 3000000;
        }
        $faqs = [];

        // Helper closure to append a market report inline link to an answer string
        $appendReportLink = function(string $answer) use ($reportUrls): string {
            if(!empty($reportUrls['type_url']) && !empty($reportUrls['type_anchor'])){
                return $answer." See the full <a href=\"{$reportUrls['type_url']}\">{$reportUrls['type_anchor']}</a> for monthly trends.";
            }
            if(!empty($reportUrls['city_url']) && !empty($reportUrls['city_anchor'])){
                return $answer." See the full <a href=\"{$reportUrls['city_url']}\">{$reportUrls['city_anchor']}</a> for monthly trends.";
            }
            return $answer;
        };

        if(!empty($stats['active_count'])){
            $count = $stats['active_count'];
            $faqs[] = ['q'=>"How many {$prefix}{$typeLabel}{$priceSuffix} are for sale in {$areaLabel}?",
                'a'=>"There are currently <strong>{$count} {$prefix}{$typeLabel}</strong>{$priceSuffix} listed for sale in {$areaLabel}. Listings are updated daily from the MLS® database."];
        }
        if(!empty($stats['market_type']) && $stats['sales_ratio'] > 0){
            $mt = $stats['market_type']; $ratio = $stats['sales_ratio'];
            if($mt === "Buyer's Market"){
                $exp = "With a {$ratio}% sales ratio, there are more {$typeLabel} available than buyers are purchasing, giving buyers more time and negotiating power.";
            }elseif($mt === "Seller's Market"){
                $exp = "With a {$ratio}% sales ratio, demand is outpacing supply — {$typeLabel} are moving quickly and sellers have the upper hand in negotiations.";
            }else{
                $exp = "With a {$ratio}% sales ratio, supply and demand are roughly balanced — both buyers and sellers have reasonable negotiating positions.";
            }
            $ans = "It is currently a <strong>{$mt}</strong>. {$exp}";
            $ans = $appendReportLink($ans);
            $faqs[] = ['q'=>"Is it a buyer's or seller's market for {$prefix}{$typeLabel} in {$areaLabel}?",'a'=>$ans];
        }
        if(!empty($stats['median_list_price'])){
            $price = $this->formatMoney($stats['median_list_price']);
            $a = "The median asking price for {$prefix}{$typeLabel} in {$areaLabel} is <strong>{$price}</strong>.";
            if(!empty($stats['avg_price_sqft'])) $a .= " The average price per sqft is <strong>".$this->formatMoney($stats['avg_price_sqft'])."</strong>.";
            $a = $appendReportLink($a);
            $faqs[] = ['q'=>"What is the median price of {$prefix}{$typeLabel} in {$areaLabel}?",'a'=>$a];
        }
        if(!empty($stats['avg_dom'])){
            $dom = $stats['avg_dom'];
            $ctx = $dom <= 14 ? "This is a fast-moving market — act quickly when you find a property you like." : ($dom <= 30 ? "Buyers have a few weeks to view comparables before making an offer." : "Buyers have time to view multiple properties and negotiate.");
            $faqs[] = ['q'=>"How long do {$prefix}{$typeLabel} take to sell in {$areaLabel}?",'a'=>"{$prefix}".ucfirst($typeLabel)." in {$areaLabel} are averaging <strong>{$dom} days</strong> on market before selling. {$ctx}"];
        }
        if($feature === 'with-suite'){
            $faqs[] = ['q'=>"What qualifies a {$typeLabel} as having a suite in {$areaLabel}?",
                'a'=>"In the Lower Mainland, a home is typically considered to have a suite when it has 3 or more kitchens — indicating an in-law suite or mortgage helper. Suite-equipped homes command a premium but can generate rental income to offset mortgage costs."];
        }elseif($feature === 'with-basement'){
            $faqs[] = ['q'=>"Are basements common in {$areaLabel} {$typeLabel}?",
                'a'=>"Many {$typeLabel} in {$areaLabel} include finished or partially finished basements that can serve as extra living space, recreation rooms, or potential rental suites. Always verify basement ceiling height, permits, and legal suite status."];
        }elseif($feature === 'new-construction'){
            $faqs[] = ['q'=>"What are the benefits of buying new construction {$typeLabel} in {$areaLabel}?",
                'a'=>"New construction {$typeLabel} (built within the last 5 years) in {$areaLabel} offer modern layouts, energy-efficient systems, Travelers/National Home Warranty coverage, and the latest building code standards. They typically carry a premium over resale but require no immediate renovation costs."];
        }elseif($isLuxuryFaq && !empty($stats['active_count'])){
            $count = $stats['active_count'];
            $faqs[] = ['q'=>"What qualifies as a luxury {$typeLabel} in {$areaLabel}?",
                'a'=>"In {$areaLabel}, luxury {$typeLabel} are generally considered to be properties priced above $3 million. These homes typically feature premium finishes, larger lot sizes, high-end appliances, and desirable locations. There are currently <strong>{$count}</strong> luxury {$typeLabel} listed for sale in {$areaLabel}."];
            $faqs[] = ['q'=>"Do luxury {$typeLabel} in {$areaLabel} negotiate on price?",
                'a'=>"The luxury segment in {$areaLabel} tends to have fewer competing buyers, which can give purchasers more negotiating room compared to the entry-level market. That said, well-priced luxury {$typeLabel} in prime locations still sell quickly. Working with an experienced luxury real estate agent familiar with {$areaLabel} is essential to getting the best outcome."];
        }elseif($priceSuffix && !empty($stats['active_count'])){
            $faqs[] = ['q'=>"Are there good {$typeLabel}{$priceSuffix} in {$areaLabel}?",
                'a'=>"Yes — there are currently <strong>{$stats['active_count']}</strong> {$typeLabel}{$priceSuffix} available in {$areaLabel}. Use the filters above to narrow by bedrooms, bathrooms, or features like suites and basements."];
        }
        return $faqs;
    }

    /**
     * Build SEO data (title, description, H1, canonical, market summary, FAQs).
     */
    protected function buildSeoData(string $city, string $subarea, string $typeSlug, string $feature, int $bedsFilter, array $stats): array
    {
        $typeLabel = $this->getTypeLabel($typeSlug, true);
        // Decode raw route slugs to proper display names (e.g. "new-westminster" → "New Westminster")
        $cityDisplay    = $city    ? \App\Helpers\Helper::deslugPlace($city)    : '';
        $subareaDisplay = $subarea ? \App\Helpers\Helper::deslugPlace($subarea) : '';
        $areaLabel = $subareaDisplay
            ? rtrim("{$subareaDisplay}, {$cityDisplay}", ', ')
            : ($cityDisplay ?: 'BC');

        $priceLabel = '';
        $priceNatural = '';
        $isLuxury = false;
        if($this->isPriceSlug($feature)){
            $_pr = $this->parsePriceSlug($feature);
            $priceLabel   = !empty($_pr['label'])   ? $_pr['label']   : '';
            $priceNatural = !empty($_pr['natural'])  ? $_pr['natural'] : $priceLabel;
            $isLuxury     = !empty($_pr['from']) && $_pr['from'] >= 3000000;
        }

        // City-first format for better local SEO; price range spelled out naturally.
        // Luxury label injected when price floor is $3M+.
        $luxuryAdj = $isLuxury ? 'Luxury ' : '';
        if($bedsFilter > 0)                    $h1 = "{$areaLabel} {$bedsFilter}-Bedroom ".ucfirst($typeLabel)." for Sale";
        elseif($feature === 'with-suite')      $h1 = "{$areaLabel} ".ucfirst($typeLabel)." with Suite for Sale";
        elseif($feature === 'with-basement')   $h1 = "{$areaLabel} ".ucfirst($typeLabel)." with Basement for Sale";
        elseif($feature === 'new-construction')$h1 = "{$areaLabel} New Construction ".ucfirst($typeLabel)." for Sale";
        elseif($priceNatural)                  $h1 = "{$areaLabel} {$luxuryAdj}".ucfirst($typeLabel)." for Sale {$priceNatural}";
        elseif($typeSlug && !$subarea)         $h1 = ucfirst($typeLabel)." for Sale in {$areaLabel}";
        elseif($typeSlug)                      $h1 = "{$areaLabel} ".ucfirst($typeLabel)." for Sale";
        else                                   $h1 = "Homes for Sale in {$areaLabel} | MLS® Listings";

        $count = $stats['active_count'] ?? 0;
        $luxuryMetaAdj = $isLuxury ? 'luxury ' : '';
        $metaDesc = "Find {$count} {$luxuryMetaAdj}".($count!==1?$typeLabel:rtrim($typeLabel,'s'))." for sale in {$areaLabel}";
        if($priceNatural) $metaDesc .= " priced {$priceNatural}";
        $metaDesc .= ".";
        $_minP = (int)($stats['min_price'] ?? 0);
        $_maxP = (int)($stats['max_price'] ?? 0);
        if($_minP > 0 && $_maxP > 0 && !$priceNatural){
            $metaDesc .= " Prices from ".$this->formatMoney($_minP)." to ".$this->formatMoney($_maxP).".";
            // Bedroom pages: surface median list price for that bed count [Task#371]
            if($bedsFilter > 0 && !empty($stats['median_list_price'])){
                $metaDesc .= " Median: ".$this->formatMoney($stats['median_list_price']).".";
            }
        } elseif(!empty($stats['median_list_price'])){
            $metaDesc .= " Median price: ".$this->formatMoney($stats['median_list_price']).".";
        }
        $metaDesc .= " View sold history, floor plans, photos and market stats. Updated daily.";
        // Cap meta description at 155 chars [Task#371 — consistency with buildForSaleSeoData]
        if(mb_strlen($metaDesc) > 155){
            $_trunc = mb_substr($metaDesc, 0, 155);
            $_ls    = mb_strrpos($_trunc, ' ');
            $metaDesc = rtrim($_ls !== false ? mb_substr($_trunc, 0, $_ls) : $_trunc, '.!?,') . '…';
        }

        $isPlainHomesForSale = !$typeSlug && !$feature && $bedsFilter === 0;
        if($isPlainHomesForSale && $subarea){
            // Consistent count-first format aligned with task#371 requirement
            $seoTitle = "{$subareaDisplay}, {$cityDisplay} Homes for Sale | {$count} Active MLS® Listings | Hani & Les";
        } elseif($city && $typeSlug && !$subarea && !$feature && $bedsFilter === 0){
            // City-level type page (e.g. /search-listings/tsawwassen/triplex) [Task#447]
            $seoTitle = ucfirst($typeLabel)." for Sale in {$cityDisplay} | {$count} Active MLS® Listings | Hani & Les";
        } elseif($subarea && $typeSlug && !$feature && $bedsFilter === 0){
            // Type-specific subarea page — include live count in title [Task#371]
            $seoTitle = "{$subareaDisplay} ".ucfirst($typeLabel)." for Sale in {$cityDisplay} | {$count} Active MLS® Listings | Hani & Les";
        } elseif($subarea && $bedsFilter > 0 && $typeSlug){
            // Bedroom+type+subarea page — include live count in title [Task#371]
            $seoTitle = "{$bedsFilter}-Bedroom ".ucfirst($typeLabel)." for Sale in {$subareaDisplay}, {$cityDisplay} | {$count} Active MLS® Listings | Hani & Les";
        } else {
            $seoTitle = $h1.' | Hani & Les | BC Condos And Homes';
        }

        // ---- Intro paragraph ----
        $introParts = [];
        if($count > 0 && ($city || $subarea)){
            // Build a natural-language listing description with bedroom/feature context
            $pricePart = $priceNatural ? " priced {$priceNatural}" : '';
            if($bedsFilter > 0){
                // e.g. "47 3-bedroom condos"
                $listingDesc = "{$count} {$bedsFilter}-bedroom {$typeLabel}";
            } elseif($feature === 'new-construction'){
                // e.g. "12 new construction houses"
                $listingDesc = "{$count} new construction {$typeLabel}";
            } elseif($feature === 'with-suite'){
                // e.g. "32 condos with suite"
                $listingDesc = "{$count} {$typeLabel} with suite";
            } elseif($feature === 'with-basement'){
                // e.g. "18 houses with basement"
                $listingDesc = "{$count} {$typeLabel} with basement";
            } else {
                $listingDesc = "{$count} {$typeLabel}";
            }
            $introParts[] = "There are currently <strong>{$listingDesc}</strong> for sale in {$areaLabel}{$pricePart}.";
            $_introMinP = (int)($stats['min_price'] ?? 0);
            $_introMaxP = (int)($stats['max_price'] ?? 0);
            if($_introMinP > 0 && $_introMaxP > 0 && !$priceNatural){
                // Price range (min→max) for crawlable context [Task#371]
                $introParts[] = "Prices range from <strong>".$this->formatMoney($_introMinP)."</strong> to <strong>".$this->formatMoney($_introMaxP)."</strong>.";
                if($bedsFilter > 0 && !empty($stats['median_list_price'])){
                    $introParts[] = "The average asking price for {$bedsFilter}-bedroom {$typeLabel} is <strong>".$this->formatMoney($stats['median_list_price'])."</strong>.";
                }
            } elseif(!empty($stats['median_list_price'])){
                $medStr = $this->formatMoney($stats['median_list_price']);
                $sqftStr = !empty($stats['avg_price_sqft']) ? ', averaging '.$this->formatMoney($stats['avg_price_sqft']).'/sqft' : '';
                $introParts[] = "The median asking price is <strong>{$medStr}</strong>{$sqftStr}.";
            }
            $introParts[] = "Browse all MLS® listings below, complete with sold history, floor plans, and daily updates.";
        }
        $introParagraph = implode(' ', $introParts);

        // ---- DB-driven SEO content overlay (city+type+feature) ----
        // Fallback priority: exact (city+subarea+type+feature) → city+type+feature → city+feature → city+type
        if($city && $feature && !$this->isPriceSlug($feature)){
            $seoRow = \Illuminate\Support\Facades\Cache::remember(
                'seo_content_' . md5("{$city}|{$subarea}|{$typeSlug}|{$feature}"),
                3600,
                function () use ($city, $subarea, $typeSlug, $feature) {
                    $db = \Illuminate\Support\Facades\DB::connection('mysql_boards')
                        ->table('search_listings_seo_content');
                    // 1. Exact match
                    $row = (clone $db)
                        ->whereRaw('LOWER(city) = LOWER(?)', [$city])
                        ->whereRaw('LOWER(subarea) = LOWER(?)', [$subarea])
                        ->whereRaw('LOWER(type_slug) = LOWER(?)', [$typeSlug])
                        ->whereRaw('LOWER(feature_slug) = LOWER(?)', [$feature])
                        ->first();
                    if($row) return $row;
                    // 2. city+type+feature (ignore subarea)
                    $row = (clone $db)
                        ->where('subarea', '')
                        ->whereRaw('LOWER(city) = LOWER(?)', [$city])
                        ->whereRaw('LOWER(type_slug) = LOWER(?)', [$typeSlug])
                        ->whereRaw('LOWER(feature_slug) = LOWER(?)', [$feature])
                        ->first();
                    if($row) return $row;
                    // 3. city+feature (any type)
                    $row = (clone $db)
                        ->where('type_slug', '')
                        ->where('subarea', '')
                        ->whereRaw('LOWER(city) = LOWER(?)', [$city])
                        ->whereRaw('LOWER(feature_slug) = LOWER(?)', [$feature])
                        ->first();
                    if($row) return $row;
                    // 4. city+type (any feature)
                    $row = (clone $db)
                        ->where('feature_slug', '')
                        ->where('subarea', '')
                        ->whereRaw('LOWER(city) = LOWER(?)', [$city])
                        ->whereRaw('LOWER(type_slug) = LOWER(?)', [$typeSlug])
                        ->first();
                    return $row ?: null;
                }
            );
            if($seoRow){
                $seoPrefix = '';
                if(!empty($seoRow->intro_text)) $seoPrefix = trim($seoRow->intro_text) . ' ';
                $seoSuffix = '';
                if(!empty($seoRow->local_facts))     $seoSuffix .= ' ' . trim($seoRow->local_facts);
                if(!empty($seoRow->rental_estimate)) $seoSuffix .= ' ' . trim($seoRow->rental_estimate);
                $introParagraph = $seoPrefix . $introParagraph . $seoSuffix;
            }
        }

        // ---- Market report URL slugs ----
        $citySlug = $city ? \App\Helpers\Helper::enslugPlace($city) : '';
        $typeReportSlug = '';
        $_t = strtolower(trim($typeSlug));
        if(in_array($_t, ['house','houses','detached']))                              $typeReportSlug = 'houses';
        elseif(in_array($_t, ['apartment','apartments','condo','condos','condominium'])) $typeReportSlug = 'condos';
        elseif(in_array($_t, ['townhouse','townhouses']))                             $typeReportSlug = 'townhouses';

        // ---- Report URLs passed to FAQ builder ----
        $reportUrls = [];
        if($citySlug){
            if($typeReportSlug){
                $reportUrls['type_url']    = "/market-report/{$citySlug}/{$typeReportSlug}";
                $reportUrls['type_anchor'] = "{$cityDisplay} ".ucfirst($typeReportSlug)." market report";
            }
            $reportUrls['city_url']    = "/market-report/{$citySlug}";
            $reportUrls['city_anchor'] = "{$cityDisplay} real estate market report";
        }

        // ---- Related links block ----
        $relatedLinks = [];
        if($citySlug){
            $mrLinks = [];
            if($typeReportSlug){
                $mrLinks[] = ['url'=>"/market-report/{$citySlug}/{$typeReportSlug}", 'label'=>"{$cityDisplay} ".ucfirst($typeReportSlug)." Market Report"];
            }
            $mrLinks[] = ['url'=>"/market-report/{$citySlug}", 'label'=>"{$cityDisplay} Real Estate Market Report"];
            $relatedLinks['market_reports'] = $mrLinks;

            $relatedLinks['neighbourhood_hub'] = ['url'=>"/neighbourhood/{$citySlug}", 'label'=>"{$cityDisplay} Neighbourhood Guide"];

            // Subarea guide pages — only on city-level pages (not subarea pages)
            // Use subarea_breakdown (sorted by active listing count desc) as the activity source,
            // then confirm each subarea has a live neighbourhood guide (places record, stats_disabled=0).
            // On subarea pages, show a direct link to that subarea's own guide if it exists.
            if(!$subarea){
                $ngLinks = [];
                $activeSubareas = !empty($stats['subarea_breakdown']) ? $stats['subarea_breakdown'] : [];
                if(!empty($activeSubareas)){
                    // Build a lookup set of guide-enabled subareas from places table
                    $cacheKey = 'neighbourhood_subareas_' . md5(strtolower($city));
                    $guideSubareaNames = Cache::remember($cacheKey, 86400, function () use ($city) {
                        return Places::where('type','subarea')
                            ->where('city', $city)
                            ->where('stats_disabled', 0)
                            ->pluck('place')
                            ->map(fn($p) => strtolower($p))
                            ->flip()
                            ->toArray();
                    });
                    foreach($activeSubareas as $saRow){
                        $saName = $saRow['subarea'] ?? '';
                        if(!$saName) continue;
                        if(!isset($guideSubareaNames[strtolower($saName)])) continue;
                        $saSlug = \App\Helpers\Helper::enslugPlace($saName);
                        $ngLinks[] = ['url'=>"/neighbourhood/{$citySlug}/{$saSlug}", 'label'=>"{$saName} Neighbourhood Guide"];
                        if(count($ngLinks) >= 5) break;
                    }
                } else {
                    // Fallback when subarea_breakdown not yet populated (low listing count pages)
                    $cacheKey = 'neighbourhood_subareas_fallback_' . md5(strtolower($city));
                    $saRecords = Cache::remember($cacheKey, 86400, function () use ($city) {
                        return Places::where('type','subarea')
                            ->where('city', $city)
                            ->where('stats_disabled', 0)
                            ->orderBy('order')
                            ->limit(5)
                            ->get();
                    });
                    foreach($saRecords as $sa){
                        $saSlug = \App\Helpers\Helper::enslugPlace($sa->place);
                        $ngLinks[] = ['url'=>"/neighbourhood/{$citySlug}/{$saSlug}", 'label'=>"{$sa->place} Neighbourhood Guide"];
                    }
                }
                $relatedLinks['neighbourhood_guides'] = $ngLinks;
            } else {
                // On a subarea page: link directly to that subarea's own neighbourhood guide
                // only when a Places record exists with stats_disabled = 0.
                $subareaRecord = Places::where('type', 'subarea')
                    ->where('city', $city)
                    ->where('place', $subarea)
                    ->first();
                if($subareaRecord && $subareaRecord->stats_disabled == 0){
                    $saSlug = \App\Helpers\Helper::enslugPlace($subarea);
                    $relatedLinks['neighbourhood_subarea_guide'] = [
                        'url'   => "/neighbourhood/{$citySlug}/{$saSlug}",
                        'label' => "{$subarea} Neighbourhood Guide",
                    ];
                }
            }
        }

        // ---- Inventory-gated cross-type links (same price, other types) ----
        // ---- AND same-type/different-price links — all checked via DB count ----
        $crossTypeLinks = [];
        $crossPriceLinks = [];
        if($citySlug && $typeSlug && $city){
            // Resolve DB type values for the current route slug
            $_t = strtolower(trim($typeSlug));
            if(in_array($_t,['house','houses','detached']))                                 $currentTypes=['House'];
            elseif(in_array($_t,['apartment','apartments','condo','condos','condominium'])) $currentTypes=['Apartment'];
            elseif(in_array($_t,['townhouse','townhouses']))                               $currentTypes=['Townhouse','Duplex','Fourplex','Triplex'];
            else                                                                            $currentTypes=[];
            // Standard price slugs to check for same-type links
            $priceSlugsToCheck = [
                'under-500k' => 'Under $500K',
                'under-800k' => 'Under $800K',
                'under-1m'   => 'Under $1M',
                '1m-to-2m'  => '$1M–$2M',
                'over-2m'   => 'Over $2M',
                '2m-to-3m'  => '$2M–$3M',
                'over-3m'   => 'Luxury ($3M+)',
            ];
            // Cross-type map
            $allTypeMap = [
                'house'     => ['label'=>'Houses',    'types'=>['House']],
                'apartment' => ['label'=>'Condos',    'types'=>['Apartment']],
                'townhouse' => ['label'=>'Townhouses','types'=>['Townhouse','Duplex','Fourplex','Triplex']],
            ];
            $currentTypeKey = '';
            if(in_array($_t,['house','houses','detached']))                                $currentTypeKey='house';
            elseif(in_array($_t,['apartment','apartments','condo','condos','condominium']))$currentTypeKey='apartment';
            elseif(in_array($_t,['townhouse','townhouses']))                               $currentTypeKey='townhouse';

            // Helper to count active listings matching city+types+price range
            $countActive = function(array $types, string $priceSlug) use ($city): int {
                $_pr = $this->parsePriceSlug($priceSlug);
                if(empty($_pr)) return 0;
                $q = DB::connection('mysql_boards')->table('listings')
                    ->where('table','mlsr_listings')
                    ->where('city', $city)
                    ->where('status','Active')
                    ->whereIn('type', $types);
                if(!empty($_pr['from'])) $q->where('listprice_2','>=',$_pr['from']);
                if(!empty($_pr['to']))   $q->where('listprice_2','<=',$_pr['to']);
                return (int)$q->count();
            };

            if($this->isPriceSlug($feature)){
                // Same-type / different-price links (check inventory for each price slug)
                foreach($priceSlugsToCheck as $pSlug => $pLabel){
                    if($pSlug === $feature) continue;
                    if($countActive($currentTypes, $pSlug) > 0){
                        $crossPriceLinks[] = [
                            'url'   => "/search-listings/{$citySlug}/{$_t}/{$pSlug}",
                            'label' => $city.' '.ucfirst($typeLabel).' '.$pLabel,
                        ];
                    }
                }
                // Cross-type / same-price links
                foreach($allTypeMap as $tKey => $tDef){
                    if($tKey === $currentTypeKey) continue;
                    if($countActive($tDef['types'], $feature) > 0){
                        $crossTypeLinks[] = [
                            'url'   => "/search-listings/{$citySlug}/{$tKey}/{$feature}",
                            'label' => $city.' '.$tDef['label'].($priceLabel ? ' '.$priceLabel : ''),
                        ];
                    }
                }
            }
        }

        return [
            'h1_text'           => $h1,
            'seo_title'         => $seoTitle,
            'meta_desc'         => $metaDesc,
            'intro_paragraph'   => $introParagraph,
            'market_summary'    => $this->buildMarketSummaryText($city, $subarea, $typeLabel, $feature, $bedsFilter, $stats),
            'faqs'              => $this->buildFaqs($city, $subarea, $typeLabel, $feature, $bedsFilter, $stats, $reportUrls),
            'related_links'     => $relatedLinks,
            'cross_type_links'  => $crossTypeLinks,
            'cross_price_links' => $crossPriceLinks,
            'area_label'        => $areaLabel,
            'type_label'        => $typeLabel,
        ];
    }

    /**
     * Compute aggregate stats for slug-based for-sale pages (/{slug}-for-sale-{subarea}).
     * Delegates to ForSaleSeoStatsService so the warm command shares the same implementation. [added:Task#371, refactored:Task#382]
     */
    protected function computeForSaleSeoStats(string $queryStr, ?string $subarea, $beds = false): array
    {
        return $this->seoStatsService->compute($queryStr, $subarea, $beds);
    }

    /**
     * Build SEO title, meta description, intro paragraph, and cross-link slugs
     * for slug-based for-sale pages (/{slug}-for-sale-{subarea} and /{beds}-bedroom-{slug}-for-sale-{subarea}). [added:Task#371]
     */
    protected function buildForSaleSeoData($place, string $subarea, $beds, array $stats, string $typeSlug = ''): array
    {
        $city       = trim($place->menu_title ?? '');
        $count      = (int)($stats['active_count'] ?? 0);
        $minP       = (int)($stats['min_price'] ?? 0);
        $maxP       = (int)($stats['max_price'] ?? 0);
        $avgP       = (int)($stats['avg_price'] ?? 0);
        $typeCounts = $stats['type_counts'] ?? ['House' => 0, 'Apartment' => 0, 'Townhouse' => 0];

        // Type breakdown sentence
        $typeParts = [];
        if (!empty($typeCounts['House']))     $typeParts[] = number_format($typeCounts['House'])     . ' ' . \Illuminate\Support\Str::plural('house',     $typeCounts['House']);
        if (!empty($typeCounts['Apartment'])) $typeParts[] = number_format($typeCounts['Apartment']) . ' ' . \Illuminate\Support\Str::plural('condo',     $typeCounts['Apartment']);
        if (!empty($typeCounts['Townhouse'])) $typeParts[] = number_format($typeCounts['Townhouse']) . ' ' . \Illuminate\Support\Str::plural('townhouse', $typeCounts['Townhouse']);
        $typeBreakdown = implode(', ', $typeParts);

        // Price range string
        $priceRange = ($minP > 0 && $maxP > 0) ? ('$' . number_format($minP) . ' to $' . number_format($maxP)) : '';

        // Resolve type label: prefer explicit $typeSlug, fall back to dominant-type inference [Task#447]
        if ($typeSlug !== '') {
            $dominantType    = $this->getTypeLabel($typeSlug, true);   // e.g. 'triplexes'
            $dominantTypeCap = ucfirst($dominantType);                 // e.g. 'Triplexes'
        } else {
            // Infer dominant type from type_counts (used for bedroom-only pages with no URL type segment)
            $tcTotal = array_sum($typeCounts);
            $dominantType = 'homes'; // generic fallback
            if ($tcTotal > 0) {
                $threshold = $tcTotal * 0.6;
                if ($typeCounts['Apartment'] >= $threshold)     $dominantType = 'condos';
                elseif ($typeCounts['House'] >= $threshold)     $dominantType = 'houses';
                elseif ($typeCounts['Townhouse'] >= $threshold) $dominantType = 'townhouses';
            }
            $dominantTypeCap = ucfirst($dominantType);
        }

        // SEO title & H1
        if ($typeSlug !== '' && !($beds && is_numeric($beds))) {
            // Type-filtered page (e.g. /triplex): type-first format with live count [Task#447]
            $seoTitle = "{$dominantTypeCap} for Sale in {$subarea}, {$city} | {$count} Active MLS® Listings | Hani & Les";
            $h1       = "{$dominantTypeCap} for Sale in {$subarea}, {$city}";
        } elseif ($beds && is_numeric($beds)) {
            $seoTitle = "{$beds}-Bedroom {$dominantTypeCap} for Sale in {$subarea}, {$city} | {$count} Active MLS® Listings | Hani & Les";
            $h1       = "{$beds}-Bedroom {$dominantTypeCap} for Sale in {$subarea}, {$city}";
        } else {
            $seoTitle = "{$subarea}, {$city} Homes for Sale | {$count} Active MLS® Listings | Hani & Les";
            $h1       = "{$subarea}, {$city} Homes for Sale";
        }

        // Meta description (capped at 155 chars)
        $metaDesc = "Browse {$count} " . ($beds && is_numeric($beds) ? "{$beds}-bedroom {$dominantType}" : $dominantType) . " for sale in {$subarea}, {$city}";
        if ($typeBreakdown && !($beds && is_numeric($beds)) && $typeSlug === '') $metaDesc .= " — {$typeBreakdown}";
        if ($priceRange)    $metaDesc .= ". Price range: {$priceRange}";
        if ($beds && is_numeric($beds) && $avgP > 0) $metaDesc .= ". Avg price \$" . number_format($avgP);
        $metaDesc .= ". Updated daily from MLS®.";
        if (mb_strlen($metaDesc) > 155) {
            $trunc    = mb_substr($metaDesc, 0, 155);
            $ls       = mb_strrpos($trunc, ' ');
            $metaDesc = rtrim($ls !== false ? mb_substr($trunc, 0, $ls) : $trunc, '.!?,') . '…';
        }

        // Server-rendered intro paragraph (crawlable, visible to Google bots)
        $introNoun = ($beds && is_numeric($beds)) ? "{$beds}-bedroom {$dominantType}" : $dominantType;
        $introParts = ["There are currently <strong>{$count} {$introNoun}</strong> for sale in {$subarea}, {$city}."];
        if ($typeBreakdown && !($beds && is_numeric($beds)) && $typeSlug === '') $introParts[] = "This includes {$typeBreakdown}.";
        if ($priceRange)                                     $introParts[] = "Prices range from {$priceRange}.";
        if ($beds && is_numeric($beds) && $avgP > 0)         $introParts[] = "The average asking price for {$beds}-bedroom {$dominantType} is \$" . number_format($avgP) . ".";
        $introParts[] = "Updated daily from MLS®.";
        $introParagraph = implode(' ', $introParts);

        // Gate neighbourhood guide cross-link behind a Places record check [Task#371]
        $citySlug    = \App\Helpers\Helper::enslugPlace($city);
        $subareaSlug = \App\Helpers\Helper::enslugPlace($subarea);
        $guideExists = Cache::remember(
            'guide_exists_' . md5($city . '|' . $subarea),
            86400,
            function () use ($city, $subarea) {
                return Places::where('type', 'subarea')
                    ->where('city', $city)
                    ->where('place', $subarea)
                    ->where('stats_disabled', 0)
                    ->exists();
            }
        );
        if (!$guideExists) {
            $citySlug    = null;
            $subareaSlug = null;
        }

        // Build FAQs using the shared buildFaqs helper
        // Use explicit typeSlug label when available, otherwise infer from dominant type [Task#447]
        if ($typeSlug !== '') {
            $typeLabel = $this->getTypeLabel($typeSlug, true);
        } else {
            $typeLabel  = 'homes';
            $tcTotal = array_sum($typeCounts);
            if ($tcTotal > 0) {
                $threshold = $tcTotal * 0.6;
                if ($typeCounts['Apartment'] >= $threshold)     $typeLabel = 'condos';
                elseif ($typeCounts['House'] >= $threshold)     $typeLabel = 'houses';
                elseif ($typeCounts['Townhouse'] >= $threshold) $typeLabel = 'townhouses';
            }
        }
        $bedsInt = ($beds && is_numeric($beds)) ? (int)$beds : 0;
        $faqs = $this->buildFaqs($city, $subarea, $typeLabel, '', $bedsInt, $stats);

        return [
            'h1_text'         => $h1,
            'seo_title'       => $seoTitle,
            'meta_desc'       => $metaDesc,
            'intro_paragraph' => $introParagraph,
            'city_slug'       => $citySlug,
            'subarea_slug'    => $subareaSlug,
            'price_range'     => $priceRange,
            'type_breakdown'  => $typeBreakdown,
            'type_counts'     => $typeCounts,
            'faqs'            => $faqs,
        ];
    }

    protected function parsePriceSlug(string $slug): array
    {
        if(preg_match('/^under-(\d+)(k|m)$/i', $slug, $m)){
            $val = $m[2]==='m'||$m[2]==='M' ? (int)$m[1]*1000000 : (int)$m[1]*1000;
            $compact = $this->formatCompactPrice($val);
            return ['from'=>0,'to'=>$val,'label'=>'Under '.$compact,'natural'=>'Under '.$compact];
        }
        if(preg_match('/^over-(\d+)(k|m)$/i', $slug, $m)){
            $val = $m[2]==='m'||$m[2]==='M' ? (int)$m[1]*1000000 : (int)$m[1]*1000;
            $compact = $this->formatCompactPrice($val);
            return ['from'=>$val,'to'=>0,'label'=>'Over '.$compact,'natural'=>'Over '.$compact];
        }
        if(preg_match('/^(\d+)(k|m)-to-(\d+)(k|m)$/i', $slug, $m)){
            $from = $m[2]==='m'||$m[2]==='M' ? (int)$m[1]*1000000 : (int)$m[1]*1000;
            $to   = $m[4]==='m'||$m[4]==='M' ? (int)$m[3]*1000000 : (int)$m[3]*1000;
            if($from > $to) { $tmp = $from; $from = $to; $to = $tmp; }
            $cFrom = $this->formatCompactPrice($from);
            $cTo   = $this->formatCompactPrice($to);
            return ['from'=>$from,'to'=>$to,'label'=>$cFrom.' – '.$cTo,'natural'=>'Between '.$cFrom.' and '.$cTo];
        }
        return [];
    }

    protected function formatCompactPrice(int $val): string
    {
        if($val >= 1000000 && $val % 1000000 === 0) return '$'.($val/1000000).'M';
        if($val >= 1000000) return '$'.number_format($val/1000000,1).'M';
        if($val >= 1000 && $val % 1000 === 0) return '$'.($val/1000).'K';
        return '$'.number_format($val);
    }

    protected function isPriceSlug(string $feature): bool
    {
        return (bool)preg_match('/^(under|over)-\d+[km]$|^\d+[km]-to-\d+[km]$/i', $feature);
    }

}
