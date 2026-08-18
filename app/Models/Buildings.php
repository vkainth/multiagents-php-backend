<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Listings;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Casts\Attribute; //lv-11
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\BuildingsGlobalFilterScope;
use App\Helpers\Helper;

// #[AllowDynamicProperties]
#[ScopedBy([BuildingsGlobalFilterScope::class])]
class Buildings extends Model
{
    use SoftDeletes;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mysql_mlsr';
    protected $connection_360 = 'mysql_pixi360';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'buildings';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'intid', 'import_id', 'name', 'complex', 'street_no', 'street_dir', 'street_name', 'street_type', 'city', 'postalcode', 'subarea', 'area', 'board', 'yearbuilt', 'levels', 'rain_screen', 'restricted_age', 'construction', 'roof', 'foundation', 'exterior_finish', 'bylaw_restrictions', 'amenities', 'maint_fees_inc', 'title_to_land', 'units_in_development', 'units_in_strata', 'min_suite', 'max_suite', 'sources', 'distinct_suites_found', 'strata_no', 'mgmt_name', 'mgmt_phone', 'no_pets', 'dogs', 'cats', 'home_style', 'loft', 'strataID', 'les_building_id', 'latitude', 'longitude', 'geo_type', 'geo_address', 'geo_street_number', 'geo_route', 'geo_locality', 'geo_response', 'inserted', 'updated', 'slug', 'mls_sources', 'pixilink_photos'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be casted to native types.
     * ['id' => 'string'] [added:18-08-2022]
     // protected $casts = ['pixilink_photos' => 'boolean','id' => 'string']; // casts as-function now:
     */
    protected function casts(): array
    {
        return ['pixilink_photos' => 'boolean','id' => 'string'];
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['inserted', 'updated'];
    const CREATED_AT = 'inserted';
    const UPDATED_AT = 'updated';


    public function photos()
    {
        return $this->hasMany(\App\Models\CondoImages::class, 'strata_idx', 'strata_no')->where('strata_idx','!=','')->orderBy('order'); 
        //[order-by added:26-04-2022] //[where-strt-not-blank added:20-07-2022]
    }

    /**
     * aphoto [added:30-09-2022][to fetch-just-once, instead of all, for performance improvement]
     * -- say on-mobile-view > listing-pages > with('aphoto')
     * @return string photo-url-location 
     */
    public function aphoto()
    {
        return $this->photos()->limit(1)->one();
        // return $this->hasMany('App\Models\CondoImages', 'strata_idx', 'strata_no')->where('strata_idx','!=','')->orderBy('order')->take(1); 
        //[added:30-09-2022] 
    }

    /**
     * main_image - returns the URL of the building's first photo, used on the homepage top-buildings cards.
     * Falls back to a generic condo image if no photo exists.
     */
    public function main_image(): string
    {
        if (!empty($this->strata_no)) {
            $photo = $this->photos()->first();
            if (!$photo) {
                $photo = \App\Models\CondoImages::where('strata_no', $this->strata_no)
                    ->where('strata_no', '!=', '')
                    ->orderBy('order')
                    ->first();
            }
            if ($photo && !empty($photo->image_name)) {
                return 'https://media.pixilinkserver.com/upload/house/images/' . $photo->image_name;
            }
        }
        return asset('frontend/images/apartment-condo-condominium-275484.jpg');
    }

    /**
     * bcnInfoCached Model for data cached for Bcn-API-fetched additional_info
     * @return HasOne relation
     */
    public function bcnInfoCached(): HasOne
    {
        return $this->hasOne(\App\Models\BcnBuildingInfoCached::class,'slug','slug');
    }
    public function getBcnInfoCachedAttribute()
    {
        return once(function () {
            $_bcnInfoCached = $this->bcnInfoCached()
            ->firstOrCreate(['slug'=>$this->slug],['sync_source'=>'bldg_model>bcnInfo'])
            ->notOlderThan(config('bcch.buildings.bcn_info_sync_period','10 days'))
            ;
            // if($_bcnInfoCached->wasRecentlyCreated){ /*$_bcnInfoCached->syncNow();*/ }

            if($_bcnInfoCached?->api_data?->data?->building?->id=='--' || data_get($_bcnInfoCached?->api_data, 'data.building.more_from_bccnet.bccnet_slug')=='wildwood-close'){
                $_bcnInfoCached->update(['api_data'=>null,'sync_source'=>'bldg:bad-record']);
            }

            return $_bcnInfoCached;
        });
    }

    /*public function all_listings(){
        return $this->hasMany(Listings::class);
    }*/

    /**
     * listings HasManyThrough mapped through pivot-(view/table)
     * @return Listings QueryBuilder object
     */
    public function listings(): HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Models\Listings::class, 
            \App\Models\PivotBuildingListings::class,
            'b_import_id', // Foreign key on the pivot table...
            'listingid', // Foreign key on the final table...
            'import_id', // Local key on the this-model's table...
            'l_listingid' // Local key on the pivot table...
        );
    }



    public function slug_duplicates()
    {
        return Buildings::where('slug',$this->slug)->where('id','!=',$this->id)->get();
        // [added: 16-08-2022] // for Dev-Team mainly
    }

    public function getId(){
        return $this->id; // [added:16-08-2022]
    }


    private function getQueryReadyAddress($address = null, $int = null){
        $address = trim($address??$this->geo_address??'');
        $address = str_replace('Street', 'St', $address);
        $address = str_replace('West', 'W', $address);
        $address = str_replace('W.', 'W', $address);
        $address = str_replace('Drive', 'Dr', $address);
        $address = str_replace('St.', 'St', $address);
        $address = str_replace('WY', 'Way', $address);
        $address = str_replace('Rd', 'Road', $address);
        $address = str_replace('Ave', 'Av', $address);
        $address = str_replace('TH', '', $address); // for-TownHouse

        return $address;
    }

    /**
     * matching_listings [created:20-04-2022]
     * @return Listings collection of Listing(s)
     */
    public function matching_listings(){
        // if(1){return $this->listings();}
        $listings  = Listings::query()
        ->where(function ($query) { $query->whereNotNull('strata_no')->where('strata_no','!=',''); })
        ->where('city', $this->city)
        ->where('street_number',$this->street_no) // [added:2022-04-03]
        ->where(function ($query) {
            if(!empty($this->strata_no)){
                $query->where('strata_no', $this->strata_no); // [Commented:2024-10-21] Because list_buildings-blade count didn't match with building-blade!
                $query->orWhere('postalarea',substr($this->postalcode,0,3)); // [added:2024-10-21 on-demand] +[in :only-when-strata_missing]
                // $query->orWhere('postalcode','LIKE',substr($this->postalcode,0,3).'%'); // [added:2024-10-21 on-demand] +[in :only-when-strata_missing]
            }elseif(!empty($this->postalcode)){
                $query->where('postalarea',substr($this->postalcode,0,3)); // [added:2024-10-21 on-demand] +[in :only-when-strata_missing]
                // $query->where('postalcode','LIKE',substr($this->postalcode,0,3).'%'); // [added:2024-10-21 on-demand] +[in :only-when-strata_missing]
            }
        })
        ->where(function ($query) {
            // $query->where('street_name', $this->street_name); // [2024-10-23]changed: for with/out ordinals:
            $_stNameNoOrdinals = preg_replace('/(\d)(st|nd|rd|th)/i', '$1',$this->street_name??'');
            $query->whereRaw("(street_name LIKE ? AND street_name REGEXP ?)", [$_stNameNoOrdinals.'%', $_stNameNoOrdinals.'(st|nd|rd|th)?$']);

            if(!empty($this->geo_address)){
                $query->orWhere('streetaddress', 'like', '%'.(implode('%', explode(',', $this->geo_address ))).'%');
            }
            // $_selectType = str_replace(['Street','West','W.','Drive','St.','WY','Rd','TH','Ave' ], ['St','W','W','Dr','St','Way','Road','','Av' ], $this->street_type);
        });
        return $listings;
    }

    public function active_listings($bedsWhere = null)
    {
        $_thisCachedName = 'onceCalledAndCached_activeListings'
        .str_replace(['  ',' ',' '], '', ($interval??''))
        .str_replace(['  ',' ',' '], '', ((empty($bedsWhere)?'': (is_array($bedsWhere)?implode('_',($bedsWhere??[])):'')) ) );
        if($this->$_thisCachedName ?? false){
            return $this->$_thisCachedName;
        }
        $year = date('Y');
        $listings = $this->matching_listings()
        ->where('status', 'Active')->where('yearbuilt', '<=', $year);
        if ($bedsWhere && !is_array($bedsWhere) && $bedsWhere == "TH") {
            $listings->where('type', 'Townhouse');
        } elseif ($bedsWhere && !is_array($bedsWhere) && $bedsWhere == "PH") {
            $listings->where('street_number', $this->street_no)/*->where('street_name', 'like', '%'.str_replace(['th','st','nd','rd'],'', strtolower($this->street_name)).'%')*/->where('type', 'Apartment')->where('home_style', 'like', '%Penthouse%');
        } elseif ($bedsWhere && is_array($bedsWhere) && count($bedsWhere) > 1) {
            $listings->where('bedrooms', $bedsWhere[0], $bedsWhere[1])->where(function ($q) {
                $q->where('type', 'Apartment')
                    ->orWhere('type', 'Townhouse');
            })->where('street_number', $this->street_no)/*->where('street_name', 'like', '%'.str_replace(['th','st','nd','rd'],'', strtolower($this->street_name)).'%')*/;
        }

        $listings = $listings->orderBy('list_date', 'DESC')/*->get()*/;
        $this->$_thisCachedName = $listings;
        return $listings;
    }

    public function sold_listings($interval = null, $bedsWhere = null)
    {
        $_thisCachedName = 'onceCalledAndCached_soldListings'
        .str_replace(['  ',' ',' '], '', ($interval??''))
        .str_replace(['  ',' ',' '], '', ((empty($bedsWhere)?'': (is_array($bedsWhere)?implode('_',($bedsWhere??[])):'')) ) );
        if($this->$_thisCachedName ?? false){
            return $this->$_thisCachedName;
        }

        $listings = $this->matching_listings()
        ->where('status', 'Sold')
        ;

        if ($interval) {
            $listings = $listings->where('sold_date', '>=', DB::raw('DATE_SUB(CURRENT_DATE(), INTERVAL ' . $interval . ')'));
        }
        if ($bedsWhere && !is_array($bedsWhere) && $bedsWhere == "TH") {
            $listings->where('type', 'Townhouse');
        } elseif ($bedsWhere && !is_array($bedsWhere) && $bedsWhere == "PH") {
            $listings->where('home_style', 'like', '%Penthouse%')->where('type', 'Apartment');
        } elseif ($bedsWhere && is_array($bedsWhere) && count($bedsWhere) > 0) {
            $listings->where('bedrooms', $bedsWhere[0], $bedsWhere[1]);
        }
        $listings = $listings->orderBy('sold_date', 'DESC')/*->get()*/;
        $this->$_thisCachedName = $listings;
        return $listings;
    }

    public function pre_sale_listings()
    {
        $_thisCachedName = 'onceCalledAndCached_soldListings'.'preSaleListings';
        if($this->$_thisCachedName ?? false){
            return $this->$_thisCachedName;
        }
        $year = date('Y');
        $maxyear = $year + 5;
        /* (for address-match (because some listings-mismatched strata_no) added: orWhere-street_name-comparison on:05-10-2021) */
        $listings = Listings::where('strata_no', $this->strata_no)->where('strata_no','!=','')/*->orWhere('street_name', $this->street_name)->where('street_number',$this->street_no) */ /*disabled-here, because it started-showing up in building-blade*/
        ->where('city', $this->city)->where('status', 'Active')->where('yearbuilt', '>', $year)->where('yearbuilt', '<=', $maxyear)->where(function ($query) {
            $query->where(function ($q) {
                $q->where('street_number', $this->street_no??'')/*->where('street_name', 'like', '%'.str_replace(['th','st','nd','rd'],'', strtolower($this->street_name)).'%')*/->where('type', 'Apartment');
            })->orWhere('type', 'Townhouse');
        });
        $listings = $listings->orderBy('list_date', 'DESC')/*->get()*/;
        $this->$_thisCachedName = $listings;
        return $listings;
    }

    public function other_buildings_in_complex()
    {
        return Buildings::strataNotEmpty()->where('strata_no', $this->strata_no)->where('id', '!=', $this->id)->where('slug','!=',$this->slug)->get();
    }

    public function address()
    {
        $address = $this->street_no;
        if ($this->steet_dir) {
            $address .= " " . $this->steet_dir;
        }
        $address .= " " . $this->street_name . " " . $this->street_type . ", " . $this->city;
        return ucwords(strtolower($address));
    }

    public function get_stats($interval)
    {
        if($this->onceCalledAndCached_getStats??false){
            return $this->onceCalledAndCached_getStats; // [added:30-09-2022]
        }
        $query = "select 
        ROUND(MAX(soldprice_2)) as expensive_sold, 
        ROUND(AVG(soldprice_2)) as avg_sold_price, 
        ROUND(AVG(DATEDIFF(sold_date,list_date))) as avg_dom,
        ROUND(AVG(soldprice_2/livingarea_2)) as avg_per_sqft
        from boards.listings 
        where 
        strata_no = '" . str_replace("'","\\'" ,$this->strata_no??'') . "' and
        status = 'Sold' and
        `street_number` = '" . str_replace("'","\\'" ,$this->street_no??'') . "' and 
        `street_name` = '" . str_replace("'","\\'" ,$this->street_name??'') . "' and 
        `street_type` = '" . str_replace("'","\\'" ,$this->street_type??'') . "' and 
        `city` = '" . str_replace("'","\\'" ,$this->city??'') . "' and 
        sold_date <= CURRENT_DATE() AND 
        sold_date >= DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")"; // (added str_replace bcoz some name were ..Lina's.. on:05-10-2021)

        $stats =  DB::connection($this->connection_360)->select($query);
        $this->onceCalledAndCached_getStats = $stats;  // [added:30-09-2022]
        return $stats;
    }

    function number_shorten($number, $precision = 3, $divisors = null)
    {

        // Setup default $divisors if not provided
        if (!isset($divisors)) {
            $divisors = array(
                pow(1000, 0) => '', // 1000^0 == 1
                pow(1000, 1) => 'K', // Thousand
                pow(1000, 2) => 'M', // Million
                pow(1000, 3) => 'B', // Billion
                pow(1000, 4) => 'T', // Trillion
                pow(1000, 5) => 'Qa', // Quadrillion
                pow(1000, 6) => 'Qi', // Quintillion
            );
        }

        // Loop through each $divisor and find the
        // lowest amount that matches
        foreach ($divisors as $divisor => $shorthand) {
            if (abs($number) < ($divisor * 1000)) {
                // We found a match!
                break;
            }
        }

        // We found our match, or there were no matches.
        // Either way, use the last defined value for $divisor.
        if ($number == 0) {
            return 0;
        }
        if ($shorthand == 'K') {
            return number_format($number / $divisor, 0) . $shorthand;
        } elseif ($shorthand == 'M') {
            return number_format($number / $divisor, 2) . $shorthand;
        } else {
            return number_format($number / $divisor, $precision) . $shorthand;
        }
    }

    public function matterport_url()
    {
        return false; // [Disabled on:24-01-2023]

        if($this->onceCalledAndCached_matterportUrl??false){
            return $this->onceCalledAndCached_matterportUrl;
        }
        $matterport = false;
        $sql = "select matterport_url from pixilink_360.building_matterports where strata_no like '" . $this->strata_no . "' and street_no = '" . $this->street_no . "'";
        $res =  DB::connection($this->connection_360)->select($sql);
        if (count($res) && (strpos($res[0]->matterport_url??'', 'http') === 0)) {
            $matterport = $res[0]->matterport_url;
        }

        $this->onceCalledAndCached_matterportUrl = $matterport;

        return $matterport;
    }

    /**
     * listingsQueryBuilderForAvgsClonedObj [created:30-09-2022]
     *  to return cloned (QueryBuilder) each time for seperate queries-to-be built on top of it
     * @return Illuminate\Database\Query\Builder  cloned Laravel-Eloquent-QueryBuilder
     */
    public function listingsQueryBuilderForAvgsClonedObj(){
        /* (for address-match (because some listings-mismatched strata_no) added: orWhere-street_name-comparison on:05-10-2021) */
        $queryBuilt = Listings::where('city', $this->city)
        ->when( $this->strata_no, function($q){return $q->where('strata_no',$this->strata_no);})
        ->when(!$this->strata_no, function($query) {
            // $query->where('strata_no', $this->strata_no);
            $query->where('street_name', $this->street_name)
            ->where('street_number',$this->street_no);
        })
        // ->where('status', 'Sold')
        ->where(function ($query) {
            $query->where(function ($q) {
                $q->where('street_number', $this->street_no)
                /*->where('street_name', 'like', '%'.str_replace(['th','st','nd','rd'],'', strtolower($this->street_name)).'%')*/
                ->where('type', 'Apartment')
                ;
            })->orWhere('type', 'Townhouse');
        });

        return clone $queryBuilt;
    }

    public function avg_strata_fee_for_duration($interval)
    {
        $_thisCachedName = 'onceCalledAndCached_avgStrataFeeForDuration'.str_replace(['  ',' ',' '], '', ($interval??''));
        if($this->$_thisCachedName ?? false){
            return $this->$_thisCachedName;
        }

        $queryBuilt = $this->matching_listings()->where('status', 'Sold');

        if ($interval) {
            $queryBuilt->where('sold_date', '>=', DB::raw('DATE_SUB(CURRENT_DATE(), INTERVAL ' . $interval . ')'));
        }

        $total_fee = clone $queryBuilt;
        $total_fee = $total_fee->sum('maintenance');
        $total_living_area = clone $queryBuilt;
        $total_living_area = $total_living_area->sum('livingarea_2');

        /**
         * $_result manuallyCach_and_signleLine_Result [added:30-09-2022]
         */
        $_result = ($total_living_area > 0) ? ($total_fee / $total_living_area) : 0;
        $this->$_thisCachedName = $_result;
        return $_result;
    }
    
    public function avg_strata_fee_for_duration_based_on_bedrooms($interval, $bedrooms)
    {
        $_thisCachedName = 'onceCalledAndCached_avgStrataFeeForDurationBedrooms'.str_replace(['  ',' ',' '], '', ($interval.$bedrooms??''));
        if($this->$_thisCachedName ?? false){
            return $this->$_thisCachedName;
        }

        $queryBuilt = $this->matching_listings()->where('status', 'Sold');

        if ($interval) {
            $queryBuilt->where('sold_date', '>=', DB::raw('DATE_SUB(CURRENT_DATE(), INTERVAL ' . $interval . ')'));
        }
        
        if ($bedrooms) {
            $queryBuilt->where('bedrooms', '=', $bedrooms);
        }

        $total_fee = clone $queryBuilt;
        $total_fee = $total_fee->sum('maintenance');
        $total_living_area = clone $queryBuilt;
        $total_living_area = $total_living_area->sum('livingarea_2');

        /**
         * $_result manuallyCach_and_signleLine_Result [added:30-09-2022]
         */
        $_result = ($total_living_area > 0) ? ($total_fee / $total_living_area) : 0;
        $this->$_thisCachedName = $_result;
        return $_result;
    }

    public function avg_strata_fee_int()
    {
        $avg_fee = $this->avg_strata_fee_for_duration('6 Month');
        if ($avg_fee) {
            return $avg_fee;
        } else {
            $avg_fee = $this->avg_strata_fee_for_duration("1 Year");
            return $avg_fee;
        }
    }
    
    public function avg_strata_fee_int_based_on_bedrooms($bedrooms)
    {
        $avg_fee = $this->avg_strata_fee_for_duration_based_on_bedrooms('6 Month', $bedrooms);
        if ($avg_fee) {
            return $avg_fee;
        } else {
            $avg_fee = $this->avg_strata_fee_for_duration_based_on_bedrooms("1 Year", $bedrooms);
            return $avg_fee;
        }
    }

    public function avg_strata_fee()
    {
        return "$" . number_format($this->avg_strata_fee_int(), 2);
    }
    
    public function avg_strata_fee_based_on_bedrooms($bedrooms)
    {
        return "$" . number_format($this->avg_strata_fee_int_based_on_bedrooms($bedrooms), 2);
    }

    public function avg_price_per_sqft_for_duration($interval)
    {
        $_thisCachedName = 'onceCalledAndCached_avgPricePerSqftForDuration'.str_replace(['  ',' ',' '], '', ($interval??''));
        if($this->$_thisCachedName ?? false){
            return $this->$_thisCachedName;
        }

        //$queryBuilt = $this->listingsQueryBuilderForAvgsClonedObj()->where('status', 'Sold');
        $queryBuilt = $this->matching_listings()->where('status', 'Sold');

        if ($interval) {
            $queryBuilt->where('sold_date', '>=', DB::raw('DATE_SUB(CURRENT_DATE(), INTERVAL ' . $interval . ')'));
        }

        $total_fee = clone $queryBuilt;
        $total_fee = $total_fee->sum('soldprice_2');
        $total_living_area = clone $queryBuilt;
        $total_living_area = $total_living_area->sum('livingarea_2');

        /**
         * $_result manuallyCach_and_signleLine_Result [added:30-09-2022]
         */
        $_result = ($total_living_area > 0) ? ($total_fee / $total_living_area) : 0;
        $this->$_thisCachedName = $_result;
        return $_result;
    }
    
    public function avg_price_per_sqft_for_duration_based_on_bedroom($interval, $bedrooms)
    {
        $_thisCachedName = 'onceCalledAndCached_avgPricePerSqftForDurationBedrooms'.str_replace(['  ',' ',' '], '', ($interval.$bedrooms??''));
        if($this->$_thisCachedName ?? false){
            return $this->$_thisCachedName;
        }

        $queryBuilt = $this->matching_listings()->where('status', 'Sold');

        if ($interval) {
            $queryBuilt->where('sold_date', '>=', DB::raw('DATE_SUB(CURRENT_DATE(), INTERVAL ' . $interval . ')'));
        }
        
        if($bedrooms){
            $queryBuilt->where('bedrooms', '=', $bedrooms);
        }

        $total_fee = clone $queryBuilt;
        $total_fee = $total_fee->sum('soldprice_2');
        $total_living_area = clone $queryBuilt;
        $total_living_area = $total_living_area->sum('livingarea_2');

        /**
         * $_result manuallyCach_and_signleLine_Result [added:30-09-2022]
         */
        $_result = ($total_living_area > 0) ? ($total_fee / $total_living_area) : 0;
        $this->$_thisCachedName = $_result;
        return $_result;
    }

    public function avg_price_per_sqft_int()
    {
        // [updated:30-09-2022] reduce-repeated-db-queries [38 to 32]
        $_thisCachedName = 'onceCalledAndCached_avgPricePerSqftInt';
        if($this->$_thisCachedName ?? false){
            return $this->$_thisCachedName;
        }
        $avg_fee = $this->avg_price_per_sqft_for_duration('6 Month');
        if ($avg_fee) {
        } else {
            $avg_fee = $this->avg_price_per_sqft_for_duration("1 Year");
        }
        $this->$_thisCachedName = $avg_fee;
        return $avg_fee;
    }
    
    public function avg_price_per_sqft_int_based_on_bedroom($bedrooms)
    {
        // [updated:30-09-2022] reduce-repeated-db-queries [38 to 32]
        $_thisCachedName = 'onceCalledAndCached_avgPricePerSqftIntBedrooms'.$bedrooms;
        if($this->$_thisCachedName ?? false){
            return $this->$_thisCachedName;
        }
        $avg_fee = $this->avg_price_per_sqft_for_duration_based_on_bedroom('6 Month', $bedrooms);
        if ($avg_fee) {
        } else {
            $avg_fee = $this->avg_price_per_sqft_for_duration_based_on_bedroom("1 Year", $bedrooms);
        }
        $this->$_thisCachedName = $avg_fee;
        return $avg_fee;
    }

    public function avg_price_per_sqft()
    {
        return "$" . number_format( $this->avg_price_per_sqft_int() , 2);
    }
    
    public function avg_price_per_sqft_based_on_bedroom($bedrooms)
    {
        return "$" . number_format( $this->avg_price_per_sqft_int_based_on_bedroom($bedrooms) , 0);
    }

    /**
     * [get_buildings_with_bccnet_condo_ids useful-to-get-same_complex-buildings  created: 13-Aug-2021]
     * @param  int|string|array $ids condos.id(s)-in-buildings.bcc_id(s)
     * @return collection      [colleciton of buildihngs]
     */
    public function get_buildings_with_bccnet_condo_ids($ids){
        if(!is_array($ids)){
            $ids = [$ids];
        }
        $buildings = $this->whereIn('bcc_id',$ids)/*->select('slug')*/->get();
        return $buildings;
    }


    /**
     * [get_reverse_bcn_slug ***Very-slow query because bcn.slug3 is always calculated dynamically (created:29-10-2021)]
     * @return [type] [description]
     */
    public function get_reverse_bcn_slug(){
        try{   
            $sql = "SELECT bcn.slug FROM pixilink_mlsr.vu_bcn_condos_clonned bcn WHERE bcn.slug3='".$this->slug."'; ";
            $res =  DB::select($sql);
            if(count($res)){
                return $res[0]->slug;
            }
        }catch(\Exception $exPtn){
            // dd($exPtn->getMessage());
            // sometimes fails the query 
        }
        return '';
    }


    /**
     * getCanonicalBuilding gets canonical-building OR null [created:2022-03-25, improved:2024-08-13]
     * @return [Buidling/null] object of canonical-building OR null
     */
    public function getCanonicalBuilding(){
        $canonicalBuildings = Buildings::strataNotEmpty()
        ->where('strata_no', $this->strata_no) /* even-if strata-null: '' passed, to not-match other-strata */
        ->where('street_no', $this->street_no)->where('city',$this->city)
        ->where(function ($query) {
            if(empty($this->strata_no) && !empty($this->geo_address)){
                $query->where('geo_address', 'like', '%'.(implode('%', explode(',', $this->geo_address))).'%');
            }
        })
        // ->where('slug', '!=', $this->slug)->where('id', '!=', $this->id) /* DISABLED: each in pair gives other as canonical*/
        ->orderBy('intid')->orderByDesc('updated')
        ->limit(1);
        return $canonicalBuildings;
    }

    
    /**
     * getCanonicalSlug gets slug of canonical-building OR null [created:25-03-2022]
     * @return [string/null] slug-string of canonical-building-records OR null
     */
    public function getCanonicalSlug(){
        $_thisCachedName = 'onceCalledAndCached_canonicalSlug';
        if($this->$_thisCachedName ?? false){
            return $this->$_thisCachedName;
        }
        $canonicalBuildings = $this->getCanonicalBuilding()->select('slug')->first();
        $_canonicalSlug = ($canonicalBuildings?->slug != $this->slug)?$canonicalBuildings?->slug:null;
        $this->$_thisCachedName = $_canonicalSlug;
        return ($_canonicalSlug);
    }

    /**
     * scopeStrataNotEmpty local-scope [created:2024-08-13]
     * @param  QueryBuilder $query  this-model's queryBuilder-query
     * @return QueryBuilder         this-model's queryBuilder-query
     * USAGE: Buildings::strataNotEmpty()->.....
     */
    public function scopeStrataNotEmpty($query): void
    {
        $query->whereNotNull('strata_no')->where('strata_no','!=','');
    }

    /**
     * scopeInAgentTerritory — filters buildings to those within an agent's territory.
     * USAGE: Buildings::inAgentTerritory($agent)->...
     */
    public function scopeInAgentTerritory($query, \App\Models\Agent $agent): void
    {
        $territories = $agent->territories()->get();
        if ($territories->isEmpty()) {
            return;
        }

        $query->where(function ($q) use ($territories) {
            foreach ($territories as $territory) {
                $q->orWhere(function ($inner) use ($territory) {
                    $inner->where('city', $territory->city);
                    if (!empty($territory->subarea)) {
                        $inner->where('subarea', $territory->subarea);
                    }
                });
            }
        });
    }

    /**
     * getSchoolCatchments
     *
     * Returns the elementary and/or secondary school(s) whose catchment
     * boundary polygon contains this building's lat/lng coordinates.
     *
     * Results are cached per building slug for 24 hours to avoid repeated
     * spatial queries on every page load.
     *
     * On MySQL the query uses ST_Contains on the stored MULTIPOLYGON geometry.
     * On SQLite (local dev) it falls back to returning an empty collection
     * because SQLite has no spatial function support.
     *
     * @return \Illuminate\Support\Collection  Collection of School models (each
     *         carrying a `pivot_level` attribute indicating Elementary/Secondary)
     */
    public function getSchoolCatchments(): \Illuminate\Support\Collection
    {
        if (empty($this->latitude) || empty($this->longitude)) {
            return collect();
        }

        $cacheKey = 'school_catchments_v1_' . $this->slug;

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400, function () {
            if (DB::getDriverName() === 'sqlite') {
                return collect();
            }

            $lat = (float) $this->latitude;
            $lng = (float) $this->longitude;

            $rows = DB::table('school_catchments as sc')
                ->join('schools as s', 's.id', '=', 'sc.school_id')
                ->select(
                    's.id',
                    's.name',
                    's.slug',
                    's.school_type',
                    's.address',
                    's.city',
                    's.latitude',
                    's.longitude',
                    's.district_name',
                    's.district_id',
                    'sc.level as pivot_level'
                )
                ->whereNotNull('sc.polygon_geom')
                ->whereRaw('ST_Contains(sc.polygon_geom, POINT(?, ?))', [$lng, $lat])
                ->get();

            return $rows->map(function ($row) {
                $school              = new \App\Models\School((array) $row);
                $school->id          = $row->id;
                $school->pivot_level = $row->pivot_level;
                return $school;
            });
        });
    }

    /**
     * flushSchoolCatchmentsCache
     * Call after re-importing catchment data to invalidate stale entries.
     */
    public function flushSchoolCatchmentsCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget('school_catchments_v1_' . $this->slug);
    }

}
