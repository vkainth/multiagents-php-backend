<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Buildings;
use App\Models\Listings;
use App\Models\UserChangesLogs;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Helper;
use App\Helpers\FubAreaHelper;
use Twilio\Rest\Client;
// use JsValidator;
// use Browser;

class TempDevCtrl2021 extends Controller
{

    protected $condos_clone = 'condos_25aug2021';
    protected $buildings_clone = 'buildings';//'blds25aug2021';
    protected $grouped_common_columns = " b.slug3 AS `bcch_slug3`, ct.slug3 AS `bcn_slug3`, COUNT(ct.slug3) AS `bcn_count_slug3`, COUNT(b.slug3) AS `bcch_count_slug3`, GROUP_CONCAT(ct.id) AS `bcn_gp_id`, GROUP_CONCAT(b.id) AS `bcch_gp_id`, GROUP_CONCAT(ct.slug) AS `bcn_gp_slug`, GROUP_CONCAT(b.slug) AS `bcch_gp_slug`, COUNT(ct.id) AS `bcn_count_id`, COUNT(b.id) AS `bcch_count_id`  "
    // .", GROUP_CONCAT(TRIM(b.strata_no)) AS `bcch_gp_strata_no`, GROUP_CONCAT(TRIM(ct.strata_no)) AS `bcn_gp_strata_no`, GROUP_CONCAT(DISTINCT TRIM(b.strata_no)) AS `bcch_gpd_strata_no`, GROUP_CONCAT(DISTINCT TRIM(ct.strata_no)) AS `bcn_gpd_strata_no`" // [Witout- TRIM(BOTH ',' FROM _field_ )] -to-mention-existance-of-blank-strata_values
    // .", GROUP_CONCAT(TRIM(b.strata_no)) AS `bcch_gp_strata_no`, GROUP_CONCAT(TRIM(ct.strata_no)) AS `bcn_gp_strata_no`, TRIM(BOTH ',' FROM GROUP_CONCAT(DISTINCT TRIM(b.strata_no))) AS `bcch_gpd_strata_no`, TRIM(BOTH ',' FROM GROUP_CONCAT(DISTINCT TRIM(ct.strata_no))) AS `bcn_gpd_strata_no`"
    ;


    protected $mapReqArray;

    protected $connection = 'mysql_mlsr';
    protected $connection_360 = 'mysql_pixi360';


    public function getReqArray($arg=null){

        $mapReqArray = [
            'report_bldgs_no_mlshistory_29july2022.json'=>[
                'description'=>'Report -All buildings without MLS-history (29-July-2022)',
                'sql'=>"SELECT  b.slug AS `bcch_slug`, b.strata_no, b.`name`, b.complex, b.street_no, b.street_dir, b.street_name, b.street_type, l.slug AS `listing_slug` FROM buildings b LEFT JOIN mlsr_listings l ON (l.street_number=b.street_no AND l.strata_no = b.strata_no)  WHERE l.id IS NULL ",
            ],
            'report_bldgs_23dec2021.json'=>[
                'description'=>'Report -All sitemaps-changes for buildings for new slugs (23-dec-2021)',
                'sql'=>"SELECT COUNT(b.id) AS cts,COUNT(DISTINCT b.slug_map) AS ct_gpd_slug_map, GROUP_CONCAT(DISTINCT b.strata_no) AS gdp_strata_no, GROUP_CONCAT(DISTINCT b.street_no) AS gpd_street_no,  GROUP_CONCAT(DISTINCT STR_TO_DATE( b.intid, '-%Y%m%d')) AS gpd_intid, GROUP_CONCAT(DISTINCT b.slug_map) AS `bcch_gpd_slug_map (NEW)`,GROUP_CONCAT(DISTINCT b.slug) AS `bcch_gpd_slug (Old)`, GROUP_CONCAT(b.id) AS bcch_gp_id FROM buildings b WHERE b.strata_no!='' AND b.slug_map!=b.slug GROUP BY b.slug_map ORDER BY ct_gpd_slug_map ASC,cts DESC LIMIT 20000;",
            ],
            'all_good_one2one.json'=>[
                'description'=>'All-Good PERFECTLY mapping ONE-TO-ONE (on-new-slug-schema basis)',
                'sql'=>"SELECT b.slug3 AS `slug3`, $this->grouped_common_columns  FROM `$this->condos_clone` `ct` RIGHT JOIN `$this->buildings_clone` `b` ON b.slug3=ct.slug3 GROUP BY ct.slug3,b.slug3 HAVING ct.slug3 IS NOT NULL AND b.slug3 IS NOT NULL AND (bcn_count_slug3=1 AND bcch_count_slug3=1)  ORDER BY bcn_count_slug3 DESC, bcch_count_slug3 DESC ;",
            ],

            'bcch_grouped_gt1.json'=>[
                'description'=>'Duplicate Condos: BCN -grouped-count>1 --Where slug3 grouped-count>1 for copyof_condos',
                'sql'=>"SELECT COUNT(*) AS `bcch_gp_count`, GROUP_CONCAT(DISTINCT `slug3`) AS `bcch_gp_slug3`, GROUP_CONCAT(`slug`) AS `bcch_gp_slugs`, GROUP_CONCAT(`id`) AS `bcch_gp_id` /*, GROUP_CONCAT( CONCAT('https://bccondosandhomes.com/building/',`slug`) ) AS `bcch_gp_slugs_links`*/ FROM `$this->buildings_clone` GROUP BY `slug3` HAVING `bcch_gp_count`>1 AND `bcch_gp_slug3` IS NOT NULL ORDER BY bcch_gp_count DESC; ",
            ],

            'bcch_deleted.json'=>[
                'description'=>'Deleted-buildings: Marked as DELETED in previous operations, but exist',
                'sql'=>"SELECT b.`*`, GROUP_CONCAT(slug3) AS `gp_slug3`, GROUP_CONCAT(DISTINCT deleted) AS `gp_deleted` FROM `$this->buildings_clone` b WHERE slug3 IS NOT NULL GROUP BY slug3 HAVING gp_deleted LIKE '%1%' ORDER BY slug3,deleted DESC, gp_deleted DESC ;" ,
            ],
            'bcn_notin_bcch.json'=>[
                'description'=>'Records in BCN-condos, but NOT mapping to any BCCH-buildings',
                'sql'=>"SELECT ct.slug3 AS `slug3`, $this->grouped_common_columns  FROM `$this->condos_clone` `ct` LEFT JOIN `$this->buildings_clone` `b` ON b.slug3=ct.slug3 GROUP BY ct.slug3,b.slug3 HAVING ct.slug3 IS NOT NULL AND b.slug3 IS NULL ORDER BY bcn_count_slug3 DESC, bcch_count_slug3 DESC ;" ,
            ],
            'bcch_notin_bcn.json'=>[
                'description'=>'Records in BCCH-buildings, but NOT mapping to any BCN-condos',
                'sql'=>"SELECT b.slug3 AS `slug3`, $this->grouped_common_columns  FROM `$this->condos_clone` `ct` RIGHT JOIN `$this->buildings_clone` `b` ON b.slug3=ct.slug3 GROUP BY ct.slug3,b.slug3 HAVING ct.slug3 IS NULL AND b.slug3 IS NOT NULL ORDER BY bcn_count_slug3 DESC, bcch_count_slug3 DESC ;" ,
            ],

            'joined_slug3_need_sync_strata.json'=>[
                'description'=>'','sql'=>"SELECT 'query-build-pending' AS `detials` "  ,
            ],

            'counts_report.json'=>[
                'description'=>'Short-counts-report combined', 
                'sql'=>"SELECT
                         (SELECT COUNT(DISTINCT ct.slug3) FROM `$this->condos_clone` `ct` ) AS `bcn_distinct_slug3_count`, 
                         (SELECT COUNT(*) FROM `$this->condos_clone` `ct` WHERE `slug3` IS NULL ) AS `bcn_NULL_slug3_count`, 
                         (SELECT COUNT(*) FROM `$this->condos_clone` `ct` ) AS `bcn_total`, 
                         (SELECT COUNT(DISTINCT b.slug3) FROM `$this->buildings_clone` `b` ) AS `bcch_distinct_slug3_count` ,
                         (SELECT COUNT(*) FROM `$this->buildings_clone` `b` WHERE `slug3` IS NULL ) AS `bcch_NULL_slug3_count`, 
                         (SELECT COUNT(*) FROM `$this->buildings_clone` `b` ) AS `bcch_total`,
                         'Counts-Report' AS `desc_01`;" , 
            ],
            
            'report_2024_12_duplicates.json'=>[
                'description'=>'Duplicates on same-slug (non-deleted)', 
                'sql'=>"SELECT b.slug, COUNT(b.id) AS `ct`, MIN(b.intid) AS `latest_intid`, MAX(b.inserted) AS `latest_inserted`, MAX(b.updated) AS `latest_updated`, MAX(b.bcc_id) AS `bcn_id`, b.id, b.import_id, b.bcc_id, b.strata_no, b.street_no, b.city, b.`name`, b.status_sync, GROUP_CONCAT(b.id) AS `bcch_gp_id`, GROUP_CONCAT( b.deleted_at) AS `deleted_ats` FROM buildings b WHERE b.deleted_at IS NULL GROUP BY b.slug HAVING ct<>1 ORDER BY ct DESC, latest_intid " ,
            ],

        ];

        if(empty($arg)){
            return $mapReqArray;
        }
        return $mapReqArray[$arg];
    }

    public function handleRequest($arg=null){

        config(['database.default'=>$this->connection ] ); 

        if(!empty($arg)){
            $mapReqArray = $this->getReqArray($arg);
        }else{
            $mapReqArray = $this->getReqArray('all_good_one2one.json');
        }

        $sql = $mapReqArray['sql'];

        try{

            $res =  DB::select($sql);  // $res =  DB::connection($this->connection)->select( DB::raw($sql));
            // $res = json_decode($res, true);

            if ($res && count($res)>=0) {

                // Remove-duplicate-words from slug3: 
                if(!empty($res[1]->gp_slugs3)){
                    foreach ($res as $row) {
                        $row->dupsmerged_slug3 = trim(preg_replace('!\-+!', '-', implode('-', array_unique(explode('-', $row->gp_slugs3) ) ) ),'-' );
                    }
                }

                return response()->json([
                    'description'=>$mapReqArray['description'] , 'total_count'=>count($res), 'rows'=>$res,
                    'sql'=>$mapReqArray['sql'],
                    /* 'mapReqArray'=>$mapReqArray*/ 
                ] );
            }

        }catch(Exception $expTn){}

        return response()->json(['description'=>'error', 'status'=>'error','error'=>'something-went-wrong' ,'rows'=>[] /*, 'sql'=>($mapReqArray['sql']?:'')*/ ]);
    }



    public function getReqModesJson(){
        $a = $this->getReqArray();
        $ret = [];
        foreach($a as $k => $v){
            $ret[] = ['term'=>$v['description']?:trim (str_replace(['.json','-','_'], ' ', $k)) ,'rqst'=>$k ];
        }
        return response()->json($ret);
    }


    /**
     * [redirectToListingDetailPage used-on-bcn to redirect to listing-detail-page using listing-id url, eg: bcch../listing-redirect/{listing-id} [added: 7-09-2021]]
     * @param  [type] $listingid [description]
     * @return [type]            [description]
     */
    public function redirectToListingDetailPage($listingid){
        $listing = Listings::where('listingid', $listingid)->firstOrFail();
        return redirect()->route('listing-detail-page2', ['slug' =>$listing->slug]);
    }

    /**
     * [listingDetailPageUrlForListingId used-on-bcn to get-direct-link with listing-slug, [added:10-09-2021] ]
     * @param  [type] $listingid [description]
     * @return [type]            [description]
     */
    public function listingDetailPageUrlForListingId($listingid){
        $listing = Listings::where('listingid', $listingid)->firstOrFail();
        return route('listing-detail-page2', ['slug'=>$listing->slug] );

    }

    /**
     * get_reverse_bcch2bcn_slug to-optimize-page-load support-ajax-on-demand request [created:12-11-2021]
     * @param  [type]  $slug          [description]
     * @param  boolean $useNewSlugFld [description]
     * @return [type]                 [description]
     */
    public function get_reverse_bcch2bcn_slug($slug=null, $useNewSlugFld=false)
    {
        if(empty($slug)){
            abort(404);
        }
        /**
         *  Block copied from main-show-building-page fxn
         */
        if($useNewSlugFld){
            $building = Buildings::where($useNewSlugFld, $slug)->whereNotNull('strata_no')->where('strata_no','!=','')->first();
            $slug = $building->slug;
        }else{
            $building = Buildings::where('slug', $slug)->whereNotNull('strata_no')->where('strata_no','!=','')->first();
            if(!$building){
                /* REDIRECT to new slug-url, if the old-url might-be supplied*/
                $building = Buildings::where('slugtill7sep2021', $slug)->whereNotNull('strata_no')->where('strata_no','!=','')->first();
            }
        }

        if($building){
            return $building->get_reverse_bcn_slug();
        }

    }












    /**
     * getBuildingsGrouped_by_titleToLand new-functionality--for-filtered-views below-functions-added-July-2021
     * @param  [type] $city [description]
     * @return [type]       [description]
     */
    public function getBuildingsGrouped_by_titleToLand($city=null){
        $cacheKey = 'bldgs_grouped_titletoland_' . ($city ? strtolower(str_replace(' ','_',$city)) : 'all');
        return Cache::remember($cacheKey, 3600, function() use ($city) {
            $sql = "SELECT b.title_to_land, COUNT(*) AS `count` FROM pixilink_mlsr.buildings `b` WHERE b.title_to_land IS NOT NULL AND b.title_to_land !='' ";
            $bindings = [];
            if(!empty($city)){
                $sql .= " AND b.city=? ";
                $bindings[] = $city;
            }
            $sql .= " GROUP BY b.title_to_land ";
            return DB::connection($this->connection_360)->select($sql, $bindings);
        });
    }

    /**
     * showBuildingsListPage list-buildings for [city>subarea](optional) [created:13-10-2021, updated:14-10-2021]
     *  -- to replace routes(all_buildings, city_buildings, city_buildings_subarea) , 
     *  -- move to BuildigController and replace with:showAllBuildings(), city_buildings() in routes.
     * @param  [type]  $city     [description]
     * @param  [type]  $subarea  [description]
     * @param  integer $pageSize [description]
     * @return [type]            [description]
     */
    public function showBuildingsListPage($city=null,$subarea=null,$pageSize=50){
        // In domain mode, an agent site's /buildings should show territory-filtered
        // buildings with agent-themed layout, not the main-site buildings dump.
        if (\App\Helpers\AgentContext::hasAgent()) {
            $agent = \App\Helpers\AgentContext::current();
            return app(\App\Http\Controllers\Frontend\AgentController::class)->buildings($agent->slug);
        }

        $request = request();
        // $request = Illuminate\Http\Request();
        $subareas = [];
        $cityBuildingsGrouped_by_titleToLand = [];

        if($request->hasAny(['city','subarea'])){
            return redirect()->route('city_buildings', ['city' => Helper::enslugPlace($request->city??$city),'subarea'=>Helper::enslugPlace($request->subarea??$subarea), ...request()->except(['city','subarea'])], 301);
        }

        $all_cities = Cache::remember('buildings_all_cities', 3600, function() {
            return Buildings::whereNotNull('city')->where('city','!=','')->selectRaw('DISTINCT `city` as `city`')->orderBy('city')->get()->pluck('city')->toArray();
        });

        $bldgs_groupByTitleToLand = Cache::remember('buildings_grouped_by_titletoland', 3600, function() {
            return Buildings::where('strata_no','!=','')->whereNotNull('title_to_land')->where('title_to_land','!=','')->selectRaw('DISTINCT title_to_land, COUNT(*) AS `count`')->groupBy('title_to_land')->get();
        });

        foreach ($bldgs_groupByTitleToLand as $key => $value) {
            $buildingsGrouped_by_titleToLand []= $value;
        }

        $buildings = Buildings::query();
        /*->where('strata_no','!=','now-allowed-blank-strata-too')*/
        $buildings = $buildings
        // ->whereNotIn('slug',['aaa-bbb','aaa-bbb-ccc-dddd','123-456-789'])->where('deleted_del','!=','1')
        ->select('id', 'name', 'slug', 'street_no', 'street_name', 'street_type', 'subarea', 'city', 'postalcode', 'levels', 'max_suite', 'status_sync', 'yearbuilt', 'title_to_land', 'strata_no', 'geo_address' )->where('street_no', '!=', '0'); //[Added:26-04-2022] for optimization eg: bldings/vancvr
        // ->orderByRaw(" `name` LIKE '0%', `name`='' ASC, `street_name` LIKE '0%',`strata_no` LIKE '', `postalcode` LIKE '' "); // [added:03-06-2022]
        //->orderByRaw(" `name` LIKE '0%', `name`='' ASC, `street_name` LIKE '0%',`strata_no` LIKE '' DESC, `postalcode` LIKE '' DESC "); // [added:2024-10-22]

        if($city){
            $city = Helper::deslugPlace($city);
            
            $buildings = $buildings->where('city',$city);

            if(!in_array(strtolower($city), array_map('strtolower', $all_cities))){
                abort(404);
            }

            $cityBuildingsGrouped_by_titleToLand =  $this->getBuildingsGrouped_by_titleToLand($city);
            $cityKey = strtolower(str_replace(' ','_',$city));
            $subareas = Cache::remember('buildings_subareas_'.$cityKey, 3600, function() use ($city) {
                return Buildings::where('city',trim($city))->whereNotNull('subarea')->where('subarea','!=','')->groupBy('subarea')->select('subarea')->selectRaw('COUNT(`subarea`) AS `subarea_count`')->orderByDesc('subarea_count')->take(50)->get();
            });

            if($subarea){
                
                $subarea = Helper::deslugPlace($subarea);
                $buildings = $buildings->whereNotNull('subarea')->where('subarea',$subarea);

                if(!$subareas->contains('subarea',$subarea)){
                    abort(404);
                }
                                
            }

        }

        // $buildings->withCount([
        //     'all_listings as active_listing_count' =>function($query2){
        //         $query2->where(function ($query) {
        //             $query->where('strata_no', $this->strata_no)->where('strata_no','!=','');
        //             $query->orWhere('street_name', $this->street_name)
        //             ->orWhere('streetaddress', 'like', '%'.(!empty($this->geo_address)?implode('%', explode(',', $this->geo_address)):'never-match-this-n2a7z7k2gf').'%');
        //            })
        //            ->where('street_number',$this->street_no) // [added:03-04-2022][Dis/ReEnabled:06-04-2022]
        //            ->where('city', $this->city)
        //            ->where('status', 'Active');
        //     },
        // ]);
        
        if($request->input('filter_titletoland','')!=''){
            $buildings = $buildings->where('title_to_land',urldecode(request()->input('filter_titletoland','')) );
        }

        if($request->input('sortby',false) && in_array(strtolower(explode('|',$request->input('sortby'))[0] ), ['name','street_name','levels','status_sync','title_to_land','yearbuilt'])){
            if(substr($request->input('sortby'),-5)=='|desc' ){
                $buildings = $buildings->orderByDesc(strtolower(explode('|',$request->input('sortby'))[0] ))
                ->orderBy(strtolower(explode('|',$request->input('sortby'))[0] ))
                ;
            }else{
                $buildings = $buildings->orderBy(strtolower(explode('|',$request->input('sortby'))[0] ));
                // $buildings = $buildings->orderBy(\DB::raw('-`'.strtolower(explode('|',$request->input('sortby'))[0] ).'`') ,'desc' );
            }
            // $buildings = $buildings->orderBy(\DB::raw(' NULLs last ') );
        }

        // $buildingsGrouped_by_titleToLand = $bldgs_groupByTitleToLand;
        $buildings_total = $buildings->count();
        $buildings = $buildings
        ->withCount([
            'listings' => function ($query) {
                $query->where('status', 'active'); /*Count only active listings*/
            }
        ])->orderByDesc('listings_count')
        // ->simplePaginate($pageSize) // -> [simplePagination on:21-06-2022]
        ->paginate($pageSize) // [LengthAwarePagination 20240806]
        ->appends($request->input()); //->take(50)->get();

        if(!$buildings->count() && request()->input('page',0)){
            return redirect()->route('city_buildings', ['city' =>Helper::enslugPlace($city),'subarea'=>Helper::enslugPlace($subarea), ...request()->except(['city','subarea','page'])], 301);
        }

        // dd($bldgs_groupByTitleToLand);
        // dd($buildingsGrouped_by_titleToLand);
        // return view('frontend.city_buildings14oct', [
        // FUB: track buildings-by-city page view for phone-verified logged-in users
        $fubBldgMsg = $city ? $city . ' buildings browse' : 'Buildings browse';
        FubAreaHelper::pushSearchEvent($fubBldgMsg, request()->fullUrl(), $city ?: null);

        // NOTE: This controller (TempDevCtrl2021) is the live route handler for /buildings/... via city_buildings route.
        //       BuildingController@showBuildingsListPage is not used for that route.
        return view('frontend.list_buildings', [
            'buildings'=>$buildings,
            'buildings_total'=>$buildings_total,
            'city'=>$city ,
            'subareas'=>$subareas,
            'buildingsGrouped_by_titleToLand'=>$buildingsGrouped_by_titleToLand,
            'cityBuildingsGrouped_by_titleToLand' =>  $cityBuildingsGrouped_by_titleToLand,
            'all_cities' => $all_cities,
         ] );
        // return view('frontend.city_buildings', compact($buildings,$city) );
    }

    /**
     * getBuildingsList list-buildings for [city>subarea](optional) [created:20-05-2022]
     *  -- to replace routes(all_buildings, city_buildings, city_buildings_subarea) , 
     *  -- move to BuildigController and replace with:showAllBuildings(), city_buildings() in routes.
     * @param  [type]  $city     [description]
     * @param  [type]  $subarea  [description]
     * @param  integer $pageSize [description]
     * @return [type]            [description]
     */
    public function getBuildingsList($city=null,$subarea=null,$pageSize=50){
        $request = request();
        $subareas = [];
        $cityBuildingsGrouped_by_titleToLand = [];


        $bldgs_groupByTitleToLand = Buildings::where('strata_no','!=','')->whereNotNull('title_to_land')->where('title_to_land','!=','')->selectRaw('DISTINCT title_to_land, COUNT(*) AS `count`')->groupBy('title_to_land')->get(); //->groupBy(['strata_no','street_no']) ; //->toSql();
        
        foreach ($bldgs_groupByTitleToLand as $key => $value) {
            $buildingsGrouped_by_titleToLand []= $value;
        }

        /**
         * $buildings [exclusion:VictoriaBoard:29-09-2022>(2024-excluded in globalScope)]
         * @var Buildings-Collection
         */
        $buildings = Buildings::query();
        /*->where('strata_no','!=','now-allowed-blank-strata-too')*/

        if($city){
            $city = Helper::deslugPlace($city);
            
            $buildings = $buildings->where('city',$city );

            $cityBuildingsGrouped_by_titleToLand =  $this->getBuildingsGrouped_by_titleToLand($city);
            $subareas = Buildings::where('city',trim($city))->whereNotNull('subarea')->where('subarea','!=','')->groupBy('subarea')->select('subarea')->selectRaw('COUNT(`subarea`) AS `subarea_count`')->orderByDesc('subarea_count')->take(50)->get(); //->toArray();

            if($subarea){
                $subarea = Helper::deslugPlace($subarea);
                $buildings = $buildings->whereNotNull('subarea')->where('subarea',$subarea);
            }

        }
        
        if($request->input('filter_titletoland','')!=''){
            $buildings = $buildings->where('title_to_land',urldecode(request()->input('filter_titletoland','')) );
        }

        if($request->input('sortby',false) && in_array(strtolower(explode('|',$request->input('sortby'))[0] ), ['name','street_name','levels','status_sync','title_to_land','yearbuilt'])){
            if(substr($request->input('sortby'),-5)=='|desc' ){
                $buildings = $buildings->orderByDesc(strtolower(explode('|',$request->input('sortby'))[0] ))
                ->orderBy(strtolower(explode('|',$request->input('sortby'))[0] ))
                ;
            }else{
                $buildings = $buildings->orderBy(strtolower(explode('|',$request->input('sortby'))[0] ));
                // $buildings = $buildings->orderBy(\DB::raw('-`'.strtolower(explode('|',$request->input('sortby'))[0] ).'`') ,'desc' );
            }
            // $buildings = $buildings->orderBy(\DB::raw(' NULLs last ') );
        }

        // $buildingsGrouped_by_titleToLand = $bldgs_groupByTitleToLand;
        $buildings = $buildings
        ->select('id', 'name', 'slug', 'street_no', 'street_name', 'street_type', 'subarea', 'city', 'postalcode', 'levels', 'max_suite', 'status_sync', 'yearbuilt', 'title_to_land' ) //[Added:26-04-2022] for optimization eg: bldings/vancvr
        ;// ->paginate($pageSize)->appends($request->input()); //->take(50)->get();

        // dd($bldgs_groupByTitleToLand);
        // dd($buildingsGrouped_by_titleToLand);
        // return view('frontend.city_buildings14oct', [
        // return view('frontend.list_buildings', [
        return  [
            'buildings'=>$buildings,
            'city'=>$city ,
            'subareas'=>$subareas,
            'buildingsGrouped_by_titleToLand'=>$buildingsGrouped_by_titleToLand,
            'cityBuildingsGrouped_by_titleToLand' =>  $cityBuildingsGrouped_by_titleToLand,
         ] ;
        // return view('frontend.city_buildings', compact($buildings,$city) );
    }

    public function showBuildingsGroupedBySlugListPage($city=null,$subarea=null,$pageSize=50){
        if(!Gate::allows('pixi-devs')){
            abort(403);
            return;
        }

        $_config_mysqlMlsr_prevState = config()->get(['database.connections.mysql_mlsr.strict']);
        config()->set(['database.connections.mysql_mlsr.strict'=>false]);
        DB::reconnect();


        $_readyData = $this->getBuildingsList($city, $subarea, $pageSize);

        $buildings = $_readyData['buildings'];
        
        $buildings = $buildings
        ->groupBy('slug')->addSelect( DB::raw('count(*) as gpd_total'))
        ->paginate($pageSize)->appends(request()->input()); 
        // ->take(50)->get();

        $_readyData['buildings'] = $buildings;

        if($_config_mysqlMlsr_prevState){
            config()->set(['database.connections.mysql_mlsr.strict'=>true]);
            DB::reconnect();
        }

        return view('frontend.list_buildings', $_readyData);            
    }

    /**
     * [redirectOldUrlsToNewCityBuildingsPage redirect-to -new-matching style of buildings-list]
     * @param  [type] $city    [description]
     * @param  [type] $subarea [description]
     * @return [type]          [description]
     */
    public function redirectOldUrlsToNewCityBuildingsPage($city=null,$subarea=null){
        return redirect()->route('city_buildings', ['city' => Helper::enslugPlace(Helper::deslugPlace($city)),'subarea'=>Helper::enslugPlace(Helper::deslugPlace($subarea))], 301);
    }

    public function whatsmyhomeworth(){
        // https://www.bccondosandhomes.com/listing/r2714705-3749-southwood-street
        return view('frontend.whatsmyhomeworth');
    }

    /**
     * renderShowStgChngSwitch [created:02-11-2022]
     * @return [type]           [description]
     */
    public function renderShowStgChngSwitch2(){
        if(!(Gate::allows('pixi-devs'))){
            abort(404);
        }
        

        // $request = request();
        // if($request->input('vsr5mLEs_Lfw29xfY',false)){
        //     Helpsers:Helper::setShowStaged('4444');
        //     // $request->session()->put('bcch_showStgdChngesv9xLvM','444');
        // }else{
        //     Helpsers:Helper::setShowStaged('2389');
        //     // $request->session()->put('bcch_showStgdChngesv9xLvM','5555');
        // }
        // $_isYes = $request->session()->get('bcch_showStgdChngesv9xLvM');
        /*
        $retString = '';
        $retString .= '<form action="" method="post" style="font-family:verdana; margin:auto;padding:20vh 20vw;text-align:center;">';
        $retString .= '<span style="color:'.($_isYes?'green':'').'">Value: </span>';
        $retString .= '';

        foreach(['true','false'] as $_status){
            $retString .= '<label style="background-color:'.(($_isYes==$_status)?'#ddd8':'').';padding:10px;cursor:pointer;border-radius:8px">'
            .'<input name="vsr5mLEs_Lfw29xfY" type="radio" value="'.$_status.'" onchange="this.form.submit()" '.(($_isYes && $_status)?'checked':'').' >'
            .(strtoupper($_status=='true'?'ON':'OFF'))
            .'</label';

        }
        $retString .= '</form>';
        echo $retString;
        */
       return view('frontend.user.dev_stgd_view');
    }

    /**
     * confirmPhoneNumber from Auth/LoginController
     * @return [type] [description]
     */
    public function confirmPhoneNumber()
    {
        $user = Auth::user();
        $agent = null;
        $agentId = null;
        if ($user->phone && $user->phone_verified) {
            // return Redirect::intended(route('dashboard', app('request')->request->all()));
        }
        $validation = [
            // 'phone' => 'required',
        ];
        if (true /*$user->loginWithAgent()*/) {
            // $agent = $user->loginWithAgent()->first();
            // if ($agent) {
            //     $agentId = $agent->agent_id;
            // }
        }
        // $validator = JsValidator::make($validation);
        // $next_url = Redirect::intended(route('dashboard', app('request')->request->all()))->getTargetUrl();
        $next_url = request()->get('redirect') ?: url('/mapsearch');
        return view('frontend.user.confirm_phone_number')->with([
            'user' => $user,
            'agent' => '', //$agent,
            'agentId' => '', //$agentId,
            'validator' => '', //$validator,
            'next_url' => $next_url,
        ]);
    }

    public function testPostConfirmPhoneNumber()
    {
        $request = request();
        $user = Auth::user();
        $action = "";
        $success = false;
        $sid    = config('services.twilio.sid');
        $token  = config('services.twilio.token');
        if ($request->get('action')) {
            $action = $request->get('action');
        }
        if ($action == 'change_number') {
            $validator = Validator::make($request->all(), [
                'number' => 'required|phone1|regex:/[0-9]{5,12}/',
                'country_code' => 'required'
            ]);
            if (!$validator->fails()) {
                $number = $request->post('number');
                $country_code = $request->post('country_code');
                if ($number != $user->phone || $country_code != $user->phone_country_code) {
                    $prev_number = $user->phone_country_code . $user->phone;
                    $user->phone = $number;
                    $user->phone_country_code = $country_code;
                    $user->phone_verified = 0;
                    $user->save();
                    UserChangesLogs::create([
                        'userid' => $user->id,
                        'role' => 'USER',
                        'activity_type' => 'update',
                        'activity' => $action,
                        'prev_value' => $prev_number,
                        'new_value' => $country_code . $number
                    ]);
                }
                $phone = $user->phone_country_code . $user->phone;
                $twilio = new Client($sid, $token);
                try {
                    $verification = $twilio->verify->v2->services("VA77c53188b66086a26419e366efc31688")
                        ->verifications
                        ->create($phone, "sms");
                    if ($verification->sid) {
                        $user->phone_verification_sid = $verification->sid;
                        $user->save();
                        $success = true;
                    }
                    $success = true;
                } catch (\Exception $e) {
                    \Debugbar::info($e);
                    $success = false;
                }
            }
        } elseif ($action == 'send_verification_code') {
            $validator = Validator::make($request->all(), [
                'number' => 'required|phone1|regex:/[0-9]{5,12}/',
                'country_code' => 'required'
            ]);
            if (!$validator->fails()) {
                $number = $request->post('number');
                $country_code = $request->post('country_code');
                //if ($number != $user->phone || $country_code != $user->phone_country_code) {
                $prev_number = $user->phone_country_code . $user->phone;
                $user->phone = $number;
                $user->phone_country_code = $country_code;
                $user->phone_verified = 0;
                $user->save();
                UserChangesLogs::create([
                    'userid' => $user->id,
                    'role' => 'USER',
                    'activity_type' => 'update',
                    'activity' => $action,
                    'prev_value' => $prev_number,
                    'new_value' => $country_code . $number
                ]);
                $phone = $user->phone_country_code . $user->phone;
                $twilio = new Client($sid, $token);
                try {
                    $verification = $twilio->verify->v2->services("VAb40c789d5dacd8e5dd558f1dca6b834c")
                        ->verifications
                        ->create($phone, "sms");
                    if ($verification->sid) {
                        $user->phone_verification_sid = $verification->sid;
                        $user->save();
                        $success = true;
                    }
                    \Debugbar::info($verification);
                } catch (\Exception $e) {
                    \Debugbar::info($e);
                    $success = false;
                }
                //}
            }else{
                \Debugbar::info($validator->errors());
            }
        } elseif ($action == 'verify_code') {
            $twilio = new Client($sid, $token);
            $code = $request->post('code');
            $verificationSid = $user->phone_verification_sid;
            try {
                $verification_check = $twilio->verify->v2->services("VAb40c789d5dacd8e5dd558f1dca6b834c")
                    ->verificationChecks
                    ->create($code, [
                        'verificationSid' => $verificationSid
                    ]);
                    \Debugbar::info($verification_check);

                if ($verification_check->sid) {
                    if ($verification_check->status == 'approved' && $verification_check->valid) {
                        $success = true;
                        $user->phone_verified = 1;
                        UserChangesLogs::create([
                            'userid' => $user->id,
                            'role' => 'USER',
                            'activity_type' => 'update',
                            'activity' => 'verify_number',
                            'prev_value' => '0',
                            'new_value' => '1'
                        ]);
                        //$user_agent = $user->agent1()->first();
                        //Mail::to($user_agent->email)->send(new AgentsUserSignup($user, true));
                        // if ($user->agent_notified == 0) {
                        //     //$user_agent = $user->loginWithAgent()->first();
                        //     if ($user_agent->fisherly_notification_newuser == 'y') {
                        //         $user->agent_notified = 1;
                        //         Mail::to($user_agent->email)->send(new AgentsUserSignup($user));
                        //     }
                        // } else {
                        //     if ($user_agent->fisherly_notification_newuser == 'y') {
                        //         Mail::to($user_agent->email)->send(new AgentsUserSignup($user, true));
                        //     }
                        // }
                        $user->save();
                    }
                }
            } catch (\Exception $e) {
                \Debugbar::info($e);
                $success = false;
            }
        }
        $response = [
            'success' => $success
        ];
        if(($validator??false) && $validator->fails() ){
            $response['errors']= $validator->errors();
        }
        return response()->json($response);
    }


    // --> moved to BuildngController [on 30-05-2022]
    // /**
    //  * getNearbyBuildings [created:03-05-2022] [updated:05-05-2022]
    //  * @param  Building $building [description]
    //  * @param  integer  $count    [description]
    //  * @return Array|null         [description]
    //  */
    // public static function getNearbyBuildings($building, $count=30){
    //     $latitude = $building->latitude;
    //     $longitude = $building->longitude;
    //     $buildings = Buildings::whereNotNull('slug')
    //     ->where('slug','!=',$building->slug)
    //     ->where('slug','!=','')
    //     ->where('postalcode','!=','V0V 0V0')
    //     // ->where('postalcode','!=','')
    //     ->select()
    //     ->addSelect(DB::raw(" SQRT(POW((latitude-" . $latitude . "),2)+POW((longitude-" . $longitude . "),2)) AS `distance`"))
    //     // ->addSelect(DB::raw(" DISTINCT `slug` AS `distinct_slug` "))
    //     ->whereRaw("((`postalcode` = '" .$building->postalcode. "' ) OR ((`latitude` BETWEEN " . ($latitude - 0.0027) . " AND " . ($latitude + 0.0027) . ") and (`longitude` BETWEEN " . ($longitude - 0.0056) . " AND " . ($longitude + 0.0056) . ")) ) ")
    //     ->orderBy('distance')
    //     ->take($count);

    //     return $buildings;
    // }





//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/// Alternate-APPROACH                                                                                                                                              //
/// [ ] DUPLICATE bcch-buildings, calling: bcch-b                                                                                                                   //
/// [ ] Dump bcn-condos, calling: bcn-ct                                                                                                                            //
/// [ ] Trim-unnecessary-fields                                                                                                                                     //
/// [ ] Create Mapable-slugs {calling:mslug} (eg slug2/slug3... ) ON [bcch-b,bcn-ct] with [name,street-(no,dir,address..|complex).. ]                               //
/// [ ] IF(count-mapable-slug==0 WHERE substr like '%oldslug-%' ) : mapable-slug = CONCAT('oldslug-', `slug`)                                                       //
///      ELSE {Other-string instead of "oldslug-"}                                                                                                                  //
/// [ ] IF-possible: reduce-mapable-slugs to no-repeating-words (like using PHP : implode('-', array_unique( explode-mslug('-') ) ))                                //
/// [ ] CREATE TABLE `mapping_bcn_bcch(_date)` [PRIMARY_AI(id|map_id), bcch_mslug, bcn_mslug, bcch_id, bcn_id, bcch_gp_ids, bcn_gp_ids, last_synced+timestamps.. ]  //
/// [ ] Add-UNIQUE index to mslug                                                                                                                                   //
///                                                                                                                                                                 //
/// [ ] DUPLICATE bcn->bcch-buildings-similar-table say:new-bcch-buildings [to replace-buildings]                                                                   //
/// [ ] MODIFY :  Rename new-bcch-id to `bcn_id(_date)`                                                                                                             //
/// [ ] ADD+FILL : `id` column with `id`(s) from mapping.                                                                                                           //
///                                                                                                                                                                 //
/// [ ] ***Now-syncable*** :  WHERE bcn.update_on>mapping_bcn_bcch.last_synced OR bcn.id>max(mapping_bcn_bcch.bcn_id)                                               //
///                                                                                                                                                                 //
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



####### POST-Complete-TODOS [STARTS] ##########
# [ ]   Remove-sql -from-json returned
# [ ]   Add authentication requirement where needed
# [ ]   Move functions to corresponding live-controllers
# [ ]   Update routes: web+api
# [ ]   Delete this controller
####### POST-Complete-TODOS [ENDS] ############


}