<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use App\Models\Scopes\ListingsGlobalFilterScope;
use App\Models\Buildings;
use App\Models\OpenHouse;
use App\Models\OfferlandPrice;
use App\Helpers\Helper; //[added:2022-09-01]

// #[AllowDynamicProperties]
#[ScopedBy([ListingsGlobalFilterScope::class])]
class Listings extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mysql_boards';
    protected $connection_360 = 'mysql_pixi360';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'listings';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['sysid', 'table', 'table_id', 'listingid', 'status', 'type', 'listingtype', 'reciprocity', 'postalcode', 'postalarea', 'streetaddress', 'province', 'board', 'muncipality', 'yearbuilt', 'yearbuilt_text', 'virtualtoururl', 'suite_no', 'street_dir', 'street_number', 'last_modified', 'list_date', 'listprice', 'listprice_2', 'street_name', 'street_type', 'bedrooms', 'remarks', 'frontage', 'depth', 'full_baths', 'half_baths', 'maintenance', 'features', 'amenity', 'site_influences', 'area', 'lotsize', 'lotsize_sqmtrs', 'lotsize_text', 'subarea', 'taxyear', 'taxamount', 'reoffice', 'reoffice2', 'reoffice3', 'reoffice_url', 'reoffice2_url', 'reoffice_phone', 'reoffice2_phone', 'agent_email', 'agent_name', 'agent_phone', 'agent_url', 'agent_id', 'agent2_email', 'agent2_name', 'agent2_phone', 'agent2_url', 'agent2_id', 'agent3_email', 'agent3_name', 'agent3_phone', 'agent3_url', 'agent3_id', 'home_style', 'city', 'livingarea', 'livingarea_2', 'livingarea_text', 'bathstotal', 'basement', 'parking', 'mainpicurl', 'thumbnailurl', 'class', 'lat', 'lng', 'geoCode', 'building_id', 'internal_building_id', 'updated', 'updated_timestamp', 'inserted', 'lastImgTrans', 'floorplan', 'displayOnInternet', 'displayAddress', 'complex', 'strata_no', 'strata_id', 'view', 'title_to_land', 'legal_description', 'slug', 'fusion_updated', 'fusion_local', 'fusion_remote', 'fusion_id', 'last_mod_hash', 'last_mod', 'open_house', 'expiration_date', 'original_price', 'sold_date', 'soldprice_2', 'soldprice', 'dom', 'cdom', 'region', 'tax_util_incl', 'garage_size', 'door_height', 'barn_size', 'shed_size', 'pool_size', 'prop_disc', 'gst_incl', 'legal_desc', 'commission', 'prev_price', 'notax_incl_grbg', 'notax_incl_water', 'notax_incl_dyking', 'notax_incl_sewer', 'notax_incl_other', 'soldoffice_short1', 'soldoffice1', 'soldoffice_short2', 'soldoffice2', 'soldoffice_short3', 'soldoffice3', 'bylaw_infr', 'perc_test_date', 'bldg_permit_appr', 'info_pckg_appr', 'dev_permit', 'perm_land_use', 'prop_in_lnd_res', 'prospectus', 'bldg_plans', 'perc_test_avail', 'sign_on_prop', 'front_dir_exp','kitchens','lotsize'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be casted to native types.
     // protected $casts = []; // casts as-function now:
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['last_modified', 'list_date', 'updated', 'inserted', 'fusion_updated', 'last_mod', 'expiration_date', 'sold_date', 'perc_test_date'];

    const CREATED_AT = 'inserted';
    const UPDATED_AT = 'last_modified';

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['address_well_formed','price_per_sqft'];



    /* // ----------- static-vars [memoization] [BEGIN] ------------- */
    protected static array $featured_activeMlsIds=[];
    protected static array $agentInfoCache = [];
    protected static array $toursCache=[];
    protected static array $tours_res2Cache=[];
    /* // ----------- static-vars [memoization] [ENDS] -------------- */


    /* // ----------- Scopes [BEGIN] ------------- */

    /**
     * Scope a query to select briefed-fields-only listings.
     * scopeBriefed - limit fields in select statement to reduce space+time complexity
     * converted: function-scopeBriefed($query): void
     * USAGE: Listings::/->briefed() ->.....
     */
    #[Scope]
    protected function briefed(Builder $query): void
    {
        $query->select(['sysid', 'table', 'table_id', 'listingid', 'status', 'status_2', 'type', 'listingtype', 'reciprocity', 'postalcode', 'postalarea', 'streetaddress', 'province', 'board', 'muncipality', 'yearbuilt', /*'yearbuilt_text',*/ 'virtualtoururl', 'suite_no', 'street_dir', 'street_number', 'last_modified', 'list_date', 'listprice', 'listprice_2', 'street_name', 'street_type', 'bedrooms', 'frontage', 'depth', /*'full_baths', 'half_baths',*/ 'maintenance', 'amenity', 'area', 'lotsize', /*'lotsize_sqmtrs', 'lotsize_text',*/ 'subarea', 'reoffice', /*'reoffice2', 'reoffice3', 'reoffice_url', 'reoffice2_url', 'reoffice_phone', 'reoffice2_phone',*/ 'home_style', 'city', 'livingarea', 'livingarea_2', /*'livingarea_text',*/ 'bathstotal', 'basement', 'parking', 'mainpicurl', 'thumbnailurl', 'class', 'lat', 'lng', 'geoCode', /*'building_id', 'internal_building_id',*/ 'updated', /*'updated_timestamp', */'inserted', /*'lastImgTrans',*/ 'floorplan', /*'displayOnInternet',*/ 'displayAddress', 'complex', 'strata_no', 'strata_id', 'view', 'title_to_land', 'slug', /*'last_mod_hash',*/ 'last_mod', /*'expiration_date', 'original_price',*/ 'sold_date', 'soldprice_2', 'soldprice', 'dom', 'cdom', 'region', 'tax_util_incl', 'garage_size', 'commission', /*'prev_price',*/ /*'bylaw_infr', 'perc_test_date',*/ 'bldg_permit_appr', /*'info_pckg_appr', 'dev_permit', 'perm_land_use', 'prop_in_lnd_res',*/ 'prospectus', /*'perc_test_avail',*/ 'sign_on_prop', 'front_dir_exp','kitchens','finished_levels','lotsize']);
    }

    /**
     * Scope a query to include listings with status "Active" or "Sold".
     * converted: pub-function-scopeActiveSold($query) [added:2022-05-12,converted:2025-05-29]
     * USAGE: Listings ::/-> activeSold() ->.....
     */
    #[Scope]
    protected function activeSold(Builder $query): void
    {
        $query->whereIn('status', ['Active', 'Sold']);
    }

    /**
     * Scope a query to include listings with status "Active".
     * converted: pub-function-scopeActive($query) [added:2022-05-12,converted:2025-05-29]
     * USAGE: Listings ::/-> active() ->.....
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', 'Active');
    }

    /**
     * Scope a query to include listings with status "Sold".
     * converted: pub-function-scopeSold($query) [added:2022-05-12,converted:2025-05-29]
     * USAGE: Listings ::/-> sold() ->.....
     */
    #[Scope]
    protected function sold(Builder $query): void
    {
        $query->where('status', 'Sold');
    }

    // // public function scopeGetAllCities($query)
    // // {
    // //     return $query->distinct('city')->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->where('status', 'Sold')->orderBy('city', 'DESC')->get(['city']);
    // // }
    // /**
    //  * Scope a query to get all distinct cities for sold listings in specific boards.
    //  * converted: function-scopeGetAllCities($query)
    //  * USAGE: Listings ::/-> getAllCities() ->.....
    //  * [Most-likely (non-DJ) ]
    //  */
    // #[Scope]
    // protected function getAllCities(Builder $query): void
    // {
    //     $query->distinct('city')
    //         ->whereIn('board', ['Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board' ])
    //         ->where('status', 'Sold')->orderBy('city', 'DESC')->get(['city']);
    // }

    /**
     * Scope a query to listings within an agent's territory (cities/subareas).
     * USAGE: Listings::inAgentTerritory($agent)->...
     */
    #[Scope]
    protected function inAgentTerritory(Builder $query, \App\Models\Agent $agent): void
    {
        $territories = $agent->territories()->get();
        if ($territories->isEmpty()) {
            return;
        }

        $query->where(function (Builder $q) use ($territories) {
            foreach ($territories as $territory) {
                $q->orWhere(function (Builder $inner) use ($territory) {
                    $inner->where('city', $territory->city);
                    if (!empty($territory->subarea)) {
                        $inner->where('subarea', $territory->subarea);
                    }
                });
            }
        });
    }

    /* // ----------- Scopes [ENDS] -------------- */


    /* // ----------- Relations [BEGIN] ------------- */

    public function photos(): HasMany
    {
        return $this->hasMany(\App\Models\Photos::class, 'sysid', 'sysid');
    }

    /**
     * aphoto [created:03-10-2022]
     * to only fetch-one-photo_(as Collection)
     * @return Collection [description]
     */
    public function aphoto()
    {
        return $this->hasOne(\App\Models\Photos::class, 'sysid', 'sysid');
    }

    public function public_photos()
    {
        $res = DB::select("select id from pixilink_360.vow_private_listings where listingid = ? ", [$this->listingid]);
        if (count($res) > 0) {
            return [$this->photos()->first()];
        } else {
            return $this->photos()->get();
        }
    }

    public function mlsr_listing(): HasOne
    {
        return $this->hasOne(\App\Models\MlsrListingsMaster::class, 'sysid', 'sysid');
    }

    /**
     * building mapped through pivot-(view/table)
     * @return HasOne Building
     */
    public function building(): HasOneThrough
    {
        return $this->hasOneThrough(
            \App\Models\Buildings::class,
            \App\Models\PivotBuildingListings::class,
            'l_listingid',
            'id', /*'import_id',*/
            'listingid',
            'b_slug', /*'b_import_id',*/
        )
        ->orderBy('intid')->orderByDesc('import_id')
        ->limit(1);
    }

    /**
     * Get all/latest related OfferlandPrice records/latest-record, sorted by id/estimated_date_time descending.
     */
    public function offerland_prices(): HasMany
    {
        return $this->hasMany(OfferlandPrice::class, 'ml_no', 'listingid')->orderByDesc('id');
    }

    public function offerland_price(): HasOne
    {
        return $this->hasOne(OfferlandPrice::class, 'ml_no', 'listingid')->ofMany('id', 'max');
    }

    /* // ----------- Relations [ENDS] -------------- */
    /* // ----------- Custom-Attributes [BEGINS] ------------ */

    /**
     * getPricePerSqftAttribute --defined an accessor with name-convention:"getFooAttribute" [added:2021-07-19]
     * @param  [type] $val [original value if in model]
     * @return [type]      [returns calculated: 'price_per_sqft' attribute with value]
     */
    public function getPricePerSqftAttribute()
    {
        return $this->pricePerSQFT();
    } //*/

    /*public function setPricePerSqftAttribute($newValue){
        $this->attributes['price_per_sqft']=$this->pricePerSQFT();
    }*/


    /* // ----------- Custom-Attributes [ENDS] -------------- */

    public function pricePerSQFT(): ?int
    {
        $price = 0;
        if ($this->livingarea_2 && $this->livingarea_2 > 0) {
            if ($this->status == "Sold") {
                if ($this->soldprice_2 && $this->soldprice_2 > 0) {
                    $calculated_price = $this->soldprice_2 / $this->livingarea_2;
                    $price = number_format((float)$calculated_price, 2, '.', '');
                }
            } else {
                if ($this->listprice_2 && $this->listprice_2 > 0) {
                    $calculated_price = $this->listprice_2 / $this->livingarea_2;
                    $price = number_format((float)$calculated_price, 2, '.', '');
                }
            }
        }
        $price = (int) round($price);
        return $price?:null; // returning null to skip in average-calculations
    }

    public function agent_bccondos_info(): object|false
    {
        $isSpecialCity = in_array($this->city, ['Surrey', 'Langley']) && !$this->is_featured();
        $cacheKey = $isSpecialCity ? "{$this->city}|0" : "other|{$this->agent_id}";

        if (isset(self::$agentInfoCache[$cacheKey])) {
            return self::$agentInfoCache[$cacheKey];
        }

        $db = DB::connection('mysql_boards');

        // if ($this->city === 'Surrey' && ! $this->is_featured()) {
        //     $result = $db->selectOne("SELECT * FROM bccondosandhomes.team_members WHERE id = 46"); // user: Bal Virk
        // } else
        // if ($this->city === 'Langley' && ! $this->is_featured()) {
        //     $result = $db->selectOne("SELECT * FROM bccondosandhomes.team_members WHERE id = 48"); // user: Brent Arnold
        // } else
        if ($this->agent_id) {
            $result = $db->selectOne("SELECT * FROM bccondosandhomes.team_members WHERE mlsid = ?", [$this->agent_id]);
        } else {
            $result = false;
        }

        return self::$agentInfoCache[$cacheKey] = $result ?: false;
    }


    /* public function agent_bccondos_info__old_non_DJ()
    {
        return Helper::listing_agentInfo(city: $this->city, agentId: $this->agent_id, isFeatured: $this->is_featured() ); // [Dj-Improved:2025-06-26]

        if ($this->city == "Surrey" && !$this->is_featured()) {
            $res = DB::select("select * from bccondosandhomes.team_members where id = '46'"); // user: Bal Virk
        } elseif ($this->city == "Langley" && !$this->is_featured()) {
            $res = DB::select("select * from bccondosandhomes.team_members where id = '48'"); // user: Brent Arnold
        } else {
            $res = DB::select("select * from bccondosandhomes.team_members where mlsid = ? ", [$this->agent_id]);
        }

        if (count($res) > 0) {
            return $res[0];
        }
        return false;
    } // */


    public function getType()
    {
        if ($this->type == '1/2 Duplex' || $this->type == 'Duplex' || $this->type == 'Triplex'  || $this->type == 'Fourplex') {
            return 'Townhouse';
        } elseif ($this->type == 'Other' || $this->type == 'Mobile') {
            return 'House';
        } else {
            return $this->type;
        }
    }

    public function getSoldPeriod()
    {
        if ($this->sold_date) {
            return $this->time_elapsed_string($this->sold_date);
        } else {
            return null;
        }
    }

    /**
     * Returns the canonical listing for this physical address.
     * Prefers Active status, then the oldest listing by insertion date.
     * Used to emit a consistent <link rel="canonical"> across relisted properties.
     *
     * Matching uses: suite_no + street_number + street_name + street_type + city
     * (+ street_dir when present) so that "123 Main St" and "123 Main Ave" are
     * never confused with one another.
     */
    public function getCanonicalListing(): self
    {
        $query = Listings::withoutGlobalScopes()
            ->select(['id', 'listingid', 'status', 'slug', 'inserted'])
            ->where('street_number', $this->street_number)
            ->where('street_name',   $this->street_name)
            ->where('street_type',   $this->street_type)
            ->where('city',          $this->city);

        // Include street direction only when this listing has one set,
        // so that listings without a direction don't shadow directional ones.
        if (!empty($this->street_dir)) {
            $query->where('street_dir', $this->street_dir);
        }

        // For condos/apartments, scope to the specific unit; for houses
        // match only records that also have no unit number.
        if ($this->suite_no) {
            $query->where('suite_no', $this->suite_no);
        } else {
            $query->where(function ($q) {
                $q->whereNull('suite_no')->orWhere('suite_no', '');
            });
        }

        $canonical = $query
            ->orderByRaw("CASE WHEN status = 'Active' THEN 0 ELSE 1 END ASC")
            ->orderBy('inserted', 'asc')
            ->orderBy('id', 'asc')
            ->first();

        return $canonical ?? $this;
    }

    public function getHistory()
    {
        $_thisCachedName = 'onceCalledAndCached_getHistory';
        if ($this->$_thisCachedName ?? false) {
            return $this->$_thisCachedName;
        }

        //return $this->where()
        if ($this->getType() == 'Apartment') {
            if ($this->suite_no) {
                $retrn = Listings::query()->briefed()->where('type', $this->type)->where('postalcode', $this->postalcode)->where('city', $this->city)->where('streetaddress', $this->streetaddress)->where('suite_no', $this->suite_no)->where('listingid', '!=', $this->listingid)->orderBy('last_modified', 'DESC')->get();
            } else {
                $retrn = array();
            }
        } else {
            $retrn = Listings::where('type', $this->type)->where('postalcode', $this->postalcode)->where('city', $this->city)->where('streetaddress', $this->streetaddress)->where('listingid', '!=', $this->listingid)->orderBy('last_modified', 'DESC')->get();
        }

        $_thisCachedName = $retrn;
        return $retrn;
    }

    public function getSoldHistory()
    {
        // $_thisCachedName = 'onceCalledAndCached_getSoldHistory2';
        // if($this->$_thisCachedName ?? false){
        //     return $this->$_thisCachedName;
        // }
        $retrn = Listings::where('type', $this->type)
        //->where(function ($query) { $query->whereNotNull('strata_no')->where('strata_no','!=',''); })
        ->where('city', $this->city)
        ->where('street_number', $this->street_number)
        ->where('strata_no', $this->strata_no)
        ->where('postalcode', $this->postalcode)
        ->where('listingid', '!=', $this->listingid)
        ->where('status', 'Sold')
        ->where('sold_date', '>=', date("Y-m-d", strtotime("-1 year", time())))
        ->orderBy('sold_date', 'DESC')->get();
        //$_thisCachedName = $retrn;
        return $retrn;

    }

    public function getFloorFromUnitNumber($unitNumber)
    {
        $unitStr = (string)$unitNumber;
        $length = strlen($unitStr);
        if ($length === 3) {
            // e.g. 709 -> floor = 7
            return (int)$unitStr[0];
        } elseif ($length === 4) {
            // e.g. 2212 -> floor = 22
            return (int)substr($unitStr, 0, 2);
        } else {
            // Fallback if unexpected unit format
            return (int)$unitStr;
        }
    }

    public function daysAgo($dateString)
    {
        // Assuming $dateString is in format 'm/d/Y'
        $saleDate = \DateTime::createFromFormat('Y-m-d', $dateString);
        $now = new \DateTime();
        $diff = $now->diff($saleDate);
        return (int)$diff->days;
    }

    /**
     * Predict price based on given input and sales data.
     *
     * @param int $unitNumber
     * @param int $beds
     * @param int $baths
     * @param int $sqft
     * @param array $salesData - array of associative arrays with keys:
     *    Date (m/d/Y), Bed, Bath, Sold Price, Sqft
     *
     */

    public function predictPrice($unitNumber, $beds, $baths, $sqft, $salesData)
    {
        $isTownhouse = (strtolower($this->getType()) === 'townhouse');
        $targetFloor = $isTownhouse ? 0 : $this->getFloorFromUnitNumber($unitNumber);

        $comps = [];
        foreach ($salesData as $sale) {

            // Check if the unit is a Penthouse (starts with PH)
            $addressParts = explode(' ', $sale['Address']);
            $firstToken = $addressParts[0] ?? '';
            if (stripos($firstToken, 'PH') === 0) {
                // If first token starts with PH, skip this comp
                continue;
            }

            $saleBeds = (int)$sale['Bed'];
            $saleBaths = (int)$sale['Bath'];
            $saleSqft = (int)$sale['Sqft'];

            $daysOld = $this->daysAgo($sale['Date']);
            $rawWeight = 1 - ($daysOld / 180);
            $timeWeight = $rawWeight < 0.1 ? 0.1 : $rawWeight;

            $diffBeds = abs($beds - $saleBeds);
            $diffBaths = abs($baths - $saleBaths);
            $similarityPenalty = 1 + 0.5 * ($diffBeds + $diffBaths);
            $similarityWeight = 1 / $similarityPenalty;

            // Extract and clean sale price
            $soldPricePart = explode(' (', $sale['Sold Price']);
            $cleanSoldPrice = preg_replace('/[^0-9]/', '', $soldPricePart[0]);
            if (!is_numeric($cleanSoldPrice) || $saleSqft <= 0) {
                continue; // skip invalid data
            }
            $salePrice = (float)$cleanSoldPrice;
            $pps = $salePrice / $saleSqft;

            // If townhouse, floor is irrelevant; otherwise, calculate floor.
            $floor = $isTownhouse ? 0 : $this->getFloorFromUnitNumber($this->extractUnitNumber($sale['Address']));

            $finalWeight = $timeWeight * $similarityWeight;

            $comps[] = [
                'floor' => $floor,
                'beds' => $saleBeds,
                'baths' => $saleBaths,
                'sqft' => $saleSqft,
                'price' => $salePrice,
                'pps' => $pps,
                'weight' => $finalWeight
            ];
        }

        if (empty($comps)) {
            return 0.0;
        }

        $totalWeight = 0;
        $weightedSumPPS = 0;
        $avgFloor = 0;
        $avgSqft = 0;

        foreach ($comps as $c) {
            $weightedSumPPS += $c['pps'] * $c['weight'];
            $avgFloor += $c['floor'] * $c['weight'];
            $avgSqft += $c['sqft'] * $c['weight'];
            $totalWeight += $c['weight'];
        }

        $avgPPS = $weightedSumPPS / $totalWeight;
        $avgFloor = $avgFloor / $totalWeight;
        $avgSqft = $avgSqft / $totalWeight;

        // Floor adjustment:
        // Skip if townhouse (no floor consideration)
        $floorAdjustmentFactor = 1.0;
        if (!$isTownhouse) {
            $floorDiff = $targetFloor - $avgFloor;
            $floorAdjustmentFactor = 1 + ($floorDiff * 0.005);
        }

        // Sqft adjustment
        $sqftDiff = $sqft - $avgSqft;
        $sqftAdjustmentFactor = 1.0;
        if ($beds >= 2) {
            $sqftAdjustmentFactor -= ($sqftDiff / 50) * 0.005;
            if ($sqftAdjustmentFactor < 0.8) {
                $sqftAdjustmentFactor = 0.8;
            }
        }

        $adjustedPPS = $avgPPS * $floorAdjustmentFactor * $sqftAdjustmentFactor;
        $predictedPrice = $adjustedPPS * $sqft;

        return round($predictedPrice, 0);
    }


    public function predictPrice_xxx($unitNumber, $beds, $baths, $sqft, $salesData)
    {
        $targetFloor = $this->getFloorFromUnitNumber($unitNumber);

        $comps = [];
        foreach ($salesData as $sale) {
            $saleBeds = (int)$sale['Bed'];
            $saleBaths = (int)$sale['Bath'];
            $saleSqft = (int)$sale['Sqft'];

            // Consider all comps, but weight them based on similarity
            $daysOld = $this->daysAgo($sale['Date']);
            $rawWeight = 1 - ($daysOld / 180);
            $timeWeight = $rawWeight < 0.1 ? 0.1 : $rawWeight;

            // Similarity adjustment based on bed/bath difference
            $diffBeds = abs($beds - $saleBeds);
            $diffBaths = abs($baths - $saleBaths);
            // The more different the bed/bath count, the lower the weight
            // For instance, each bed or bath difference reduces weight.
            // This is an arbitrary formula that can be tweaked.
            $similarityPenalty = 1 + 0.5 * ($diffBeds + $diffBaths);
            $similarityWeight = 1 / $similarityPenalty;

            // Extract sold price numeric value
            $soldPricePart = explode(' (', $sale['Sold Price']);
            $cleanSoldPrice = preg_replace('/[^0-9]/', '', $soldPricePart[0]);
            if (!is_numeric($cleanSoldPrice) || $saleSqft <= 0) {
                continue; // skip invalid data
            }

            $salePrice = (float)$cleanSoldPrice;
            $pps = $salePrice / $saleSqft;

            $floor = $this->getFloorFromUnitNumber($this->extractUnitNumber($sale['Address']));

            // Final weight combines time and similarity
            $finalWeight = $timeWeight * $similarityWeight;

            $comps[] = [
                'floor' => $floor,
                'beds' => $saleBeds,
                'baths' => $saleBaths,
                'sqft' => $saleSqft,
                'price' => $salePrice,
                'pps' => $pps,
                'weight' => $finalWeight
            ];
        }

        if (empty($comps)) {
            // No data found
            return 0.0;
        }

        $totalWeight = 0;
        $weightedSumPPS = 0;
        $avgFloor = 0;
        $avgSqft = 0;

        foreach ($comps as $c) {
            $weightedSumPPS += $c['pps'] * $c['weight'];
            $avgFloor += $c['floor'] * $c['weight'];
            $avgSqft += $c['sqft'] * $c['weight'];
            $totalWeight += $c['weight'];
        }

        $avgPPS = $weightedSumPPS / $totalWeight;
        $avgFloor = $avgFloor / $totalWeight;
        $avgSqft = $avgSqft / $totalWeight;

        // Floor adjustment
        // Assume each floor difference changes pps by 0.5% per floor
        $floorDiff = $targetFloor - $avgFloor;
        $floorAdjustmentFactor = 1 + ($floorDiff * 0.005);

        // Sqft adjustment
        // For 2+ bedrooms, larger units tend to have slightly lower pps.
        // Adjust based on bed count (more beds = larger unit discounting)
        $sqftDiff = $sqft - $avgSqft;
        $sqftAdjustmentFactor = 1.0;
        if ($beds >= 2) {
            // Reduce pps if much larger than average
            $sqftAdjustmentFactor -= ($sqftDiff / 50) * 0.005;
            if ($sqftAdjustmentFactor < 0.8) {
                $sqftAdjustmentFactor = 0.8;
            }
        }

        $adjustedPPS = $avgPPS * $floorAdjustmentFactor * $sqftAdjustmentFactor;
        $predictedPrice = $adjustedPPS * $sqft;

        return round($predictedPrice, 0);
    }

    // Helper function to extract unit number from address (assuming the format "XXXX 13615 Fraser Highway")
    public function extractUnitNumber($address)
    {
        // The address seems formatted like: "3403 13615 Fraser Highway"
        // Unit number appears to be the first token
        $parts = explode(' ', $address);
        return (int)$parts[0];
    }

    public function getPredictedPriceWithAvailableData($sold_history = [], $listingsonly = false)
    {
        $predictedPrice = 0;
        if ($this->getType() == 'Apartment' || $this->getType() == 'Townhouse') {
            // if(count($sold_history) > 1){
            $sold_data = [];
            $count = 0;
            foreach ($sold_history as $sold_listing) {

                $sold_data[$count]['unit'] = $sold_listing->suite_no;
                $sold_data[$count]['Date'] = $sold_listing->sold_date;
                $sold_data[$count]['Sold Price'] = $sold_listing->soldprice_2;
                $sold_data[$count]['Sqft'] = $sold_listing->livingarea_2;
                $sold_data[$count]['Address'] = $sold_listing->streetaddress;
                $sold_data[$count]['Asking Price'] = $sold_listing->listprice_2;
                $sold_data[$count]['propertyTax'] = $sold_listing->taxamount;
                $sold_data[$count]['taxYear'] = $sold_listing->taxyear;
                $sold_data[$count]['Bed'] = $sold_listing->bedrooms;
                $sold_data[$count]['Bath'] = $sold_listing->bathstotal;
                $count++;

            }
            if ($listingsonly) {
                return $sold_data;
            }
            $predictedPrice =  $this->predictPrice($this->suite_no, $this->bedrooms, $this->bathstotal, $this->livingarea_2, $sold_data);
            // }
        }
        return $predictedPrice;
    }

    public function getPredictedPrice($listingsonly = false)
    {
        $predictedPrice = 0;
        if ($this->getType() == 'Apartment' || $this->getType() == 'Townhouse') {
            $sold_history = $this->getSoldHistory();
            // if(count($sold_history) > 1){
            $sold_data = [];
            $count = 0;
            foreach ($sold_history as $sold_listing) {

                $sold_data[$count]['unit'] = $sold_listing->suite_no;
                $sold_data[$count]['Date'] = $sold_listing->sold_date;
                $sold_data[$count]['Sold Price'] = $sold_listing->soldprice_2;
                $sold_data[$count]['Sqft'] = $sold_listing->livingarea_2;
                $sold_data[$count]['Address'] = $sold_listing->streetaddress;
                $sold_data[$count]['Asking Price'] = $sold_listing->listprice_2;
                $sold_data[$count]['propertyTax'] = $sold_listing->taxamount;
                $sold_data[$count]['taxYear'] = $sold_listing->taxyear;
                $sold_data[$count]['Bed'] = $sold_listing->bedrooms;
                $sold_data[$count]['Bath'] = $sold_listing->bathstotal;
                $count++;

            }
            if ($listingsonly) {
                return $sold_data;
            }
            $predictedPrice = $this->predictPrice($this->suite_no, $this->bedrooms, $this->bathstotal, $this->livingarea_2, $sold_data);
            // }
        }
        return $predictedPrice;
    }

    public function getListingPeriod()
    {
        if ($this->list_date) {
            return $this->time_elapsed_string($this->list_date);
        } else {
            return null;
        }
    }

    public function showNeighbourhoodStatsButton()
    {
        $res = DB::select("select id from bccondosandhomes.places where place = ? and city = ?", [trim($this->subarea),trim($this->city)]);
        if (count($res) > 0) {
            return true;
        }
        return false;
    }

    // public function active_days_on_market__old_non_DJ()
    // {
    //     $days_on_market = null;
    //     if ($this->status == 'Active') {
    //         $listing_date = new \DateTime($this->list_date);
    //         $current_date = Carbon::now();
    //         if ($listing_date && $current_date) {
    //             $days_on_market = $current_date->diff($listing_date)->format("%a");
    //         }
    //     }
    //     return $days_on_market;
    // }

    // public function days_on_market__old_non_DJ()
    // {
    //     $days_on_market = null;
    //     if ($this->status == 'Sold') {
    //         $listing_date = new \DateTime($this->list_date);
    //         $sold_date = new \DateTime($this->sold_date);
    //         if ($listing_date && $sold_date) {
    //             $days_on_market = $sold_date->diff($listing_date)->format("%a");
    //         }
    //     }
    //     return $days_on_market;
    // }

    public function active_days_on_market(): ?int
    {
        return $this->status == 'Active' && $this->list_date
        ? (int) Carbon::parse($this->list_date)->diffInDays(today())
        : null;
    }

    public function days_on_market(): ?int
    {
        return $this->status == 'Sold' && $this->list_date && $this->sold_date
        ? (int) Carbon::parse($this->list_date)->diffInDays(Carbon::parse($this->sold_date))
        : null;
    }


    /**
     * [analogousDOM General-function to automatically return DOM-value --according-to-listing-status]
     * @return [number|null] [number if ('Active' OR 'Sold'), else: NULL ]
     * ADDED ON: 14-07-2021
     */
    public function analogousDOM()
    {
        if (strtolower($this->status) == 'active') {
            return $this->active_days_on_market();
        } elseif (strtolower($this->status) == 'sold') {
            return $this->days_on_market();
        }
        return null;
    }

    public function time_elapsed_string($datetime, $full = false)
    {
        return Carbon::parse($datetime)->diffForhumans();
    }


    public function getFloorPlan()
    {
        $floorPlan = "";
        $houseFloorPlan = $this->getHouseFloorplan($this->listingid);
        if ($houseFloorPlan) {
            $floorPlan = $this->getFloorplan2($houseFloorPlan);
        }
        return $floorPlan;
    }

    public function get_tours()
    {
        $_thisCachedName = 'onceCalledAndCached_getTours';
        if ($this->$_thisCachedName ?? false) {
            return $this->$_thisCachedName;
        }

        $tours = null;

        // $res = once(function () {
        //     return DB::connection($this->connection_360)
        //     ->select("select * from pixilink_360.houses where mls = ? and mls not in (select distinct listingid from pixilink_360.vow_private_listings)", [$this->listingid]);/*->setBindings([$this->listingid]);*/
        // },'listing_getTours_'.$this->listingid); /* onceFxnality[added:2025-05-29]*/
        
        if (! isset(self::$toursCache[$this->listingid])) {
            self::$toursCache[$this->listingid] = DB::connection($this->connection_360)
                ->select("SELECT * FROM pixilink_360.houses WHERE mls = ? AND mls NOT IN (SELECT DISTINCT listingid FROM pixilink_360.vow_private_listings )", [$this->listingid]);
        }
        $res = self::$toursCache[$this->listingid];
        if (count($res) > 0) {
            if ($row = (array) $res[0]) {
                $house_id = $row['house_id'];

                if (! isset(self::$tours_res2Cache[$house_id])) {
                    self::$tours_res2Cache[$house_id] = DB::connection($this->connection_360)
                    ->select("select * from pixilink_360.virtual_tour where active='y' and house_id = ? ", [$house_id]);
                }
                $res2 = self::$tours_res2Cache[$house_id];

                // $res2 = DB::connection($this->connection_360)->select("select * from pixilink_360.virtual_tour where active='y' and house_id = ? ", [$house_id]);/*->setBindings([$house_id]);*/
                if (count($res2) > 0) {
                    foreach ($res2 as $tour) {
                        //get_final_url
                        if ($tour->tour_type == 'video' && strpos($tour->video_url, 'vimeo.com') !== false) {
                            $tours[$tour->tour_type] = [
                                'tour_id' => $tour->virtual_tour_id,
                                'video_url' => $tour->video_url,
                                'vimeo_embed_url' => $this->get_final_url($tour)
                            ];
                        } elseif ($tour->tour_type == 'video' && strpos($tour->video_url, 'youtu') !== false) {
                            $tours[$tour->tour_type] = [
                                'tour_id' => $tour->virtual_tour_id,
                                'video_url' => $tour->video_url,
                                'youtube_embed_url' => $this->getYoutubeEmbedUrl($tour->video_url)
                            ];
                        } elseif ($tour->tour_type == 'matterport' && strpos($tour->video_url, 'models') !== false) {
                            $tours[$tour->tour_type] = [
                                'tour_id' => $tour->virtual_tour_id,
                                'video_url' => $tour->video_url
                            ];
                            $exp = explode('models/', $tour->video_url);
                            if (sizeof($exp) > 1) {
                                $matterport_id = $exp[1];
                                $matterport_url = "https://my.matterport.com/show/?m=".$matterport_id;
                                $tours[$tour->tour_type]['video_url'] = $matterport_url;
                            }
                        } else {
                            $tours[$tour->tour_type] = [
                                'tour_id' => $tour->virtual_tour_id,
                                'video_url' => $tour->video_url
                            ];
                        }
                    }
                }
            }
        }

        $_thisCachedName = $tours;
        return $tours;
    }

    public function getYoutubeEmbedUrl($url)
    {
        $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
        $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';

        $youtube_id = '';


        if (preg_match($longUrlRegex, $url, $matches)) {
            $youtube_id = $matches[count($matches) - 1];
        }

        if (preg_match($shortUrlRegex, $url, $matches)) {
            $youtube_id = $matches[count($matches) - 1];
        }

        if ($youtube_id) {
            return 'https://www.youtube.com/embed/' . $youtube_id . "?autoplay=0&fs=0&iv_load_policy=3&showinfo=0&rel=0&cc_load_policy=0&start=7";
        } else {
            return '';
        }
    }


    public function getHouseFloorplan($mlsId)
    {
        $_thisCachedName = 'onceCalledAndCached_getHouseFloorplan';
        if ($this->$_thisCachedName ?? false) {
            return $this->$_thisCachedName;
        }

        $houseFloorPlan = '';
        $res = once(function () use ($mlsId) {
            return DB::connection($this->connection_360)
            ->select("select * from pixilink_360.houses where mls = ? and mls not in (select distinct listingid from pixilink_360.vow_private_listings)", [$mlsId])/*->setBindings([$mlsId])*/;
        }); /* onceFxnality[added:2025-05-29]*/

        if (count($res) > 0) {
            if ($row = (array) $res[0]) {
                $houseFloorPlan = $row['floor_plan'];
            }
        }
        $_thisCachedName = $houseFloorPlan;
        return $houseFloorPlan;
    }

    public function getFloorplan2($floorplanURL)
    {
        $_thisCachedName = 'onceCalledAndCached_getFloorplan2';
        if ($this->$_thisCachedName ?? false) {
            return $this->$_thisCachedName;
        }

        $floorplan = '';
        if ($floorplanURL != '') {
            $floorplanURL = "https://pixilink.com/" . $floorplanURL;
            if (strpos($floorplanURL, 'files') > 0) {
                // extract item id
                preg_match('/^.*\/files\/([0-9]+)\/([0-9]+).*$/i', $floorplanURL, $matches); // building details
                if ($matches[1] > 0) {
                    // we have an ID - so load floorplan
                    $res = DB::connection($this->connection_360)
                        ->select("select * from pixilink_accounts.order_items where id = ?", [$matches[2]])/*->setBindings(['id'=>$matches[2]])*/;
                    if (count($res) > 0) {
                        if ($row = (array) $res[0]) {
                            if ($row['additional_file_2'] != '') {
                                $path_parts = pathinfo($row['additional_file_2']);
                                switch ($path_parts['extension']) {
                                    case 'jpg':
                                    case 'png':
                                        $floorplan = 'https://www.pixilink.com/files/' . $matches[1] . '/' . $path_parts['basename'];
                                        break;
                                    default:
                                        // not a supported image
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }

        $_thisCachedName = $floorplan;
        return $floorplan;
    }

    public function current_year_listings_from_agent()
    {
        $currentYear = Carbon::now()->format('Y');
        $first_day = Carbon::now()->startOfYear();
        $last_day = Carbon::now()->endOfYear();
        $listings = Listings::where('agent_id', $this->agent_id)->where('list_date', '>=', $first_day)->where('list_date', '<=', $last_day)->count();
        return [
            'year' => $currentYear,
            'listings' => $listings
        ];
    }

    public function last_year_listings_from_agent()
    {
        $lastYear = Carbon::now()->subYear()->format('Y');
        $first_day = Carbon::now()->subYear()->startOfYear();
        $last_day = Carbon::now()->subYear()->endOfYear();
        $listings = Listings::where('agent_id', $this->agent_id)->where('list_date', '>=', $first_day)->where('list_date', '<=', $last_day)->count();
        return [
            'year' => $lastYear,
            'listings' => $listings
        ];
    }

    public function get_building()
    {
        once(function () {
            $_thisCachedName = 'onceCalledAndCached_getBuilding';
            if ($this->$_thisCachedName ?? false) {
                return $this->$_thisCachedName;
            }

            $building = null;
            if (in_array($this->type, ['Apartment', 'Townhouse'])) {
                if ($this->strata_no) {
                    $building = Buildings::where('strata_no', $this->strata_no)
                    ->where('street_no', $this->street_number)
                    ->where('city', $this->city)
                    ->orderByRaw(" `strata_no` LIKE '".addslashes($this->strata_no)."' DESC ")
                    ->orderByRaw(" `street_name` LIKE '".addslashes($this->street_name)."' DESC ")
                    ->orderByRaw(" `subarea` LIKE '".addslashes($this->subarea)."' DESC ") // [added:03-04-2022][Updated:26-04-2022] (added all the orderByRaw-parts, updated: .+DESC)
                    ->first();

                    /*
                }
            } elseif ($this->type == 'Townhouse') {
                if ($this->strata_no) {
                    $building = Buildings::where('strata_no', $this->strata_no)
                    ->where('city', $this->city)
                    ->orderByRaw(" `strata_no` LIKE '".addslashes($this->strata_no)."' DESC ")
                    ->orderByRaw(" `street_name` LIKE '".addslashes($this->street_name)."' DESC ")
                    ->orderByRaw(" `subarea` LIKE '".addslashes($this->subarea)."' DESC ") // [added:03-04-2022][Updated:26-04-2022] (added all the orderByRaw-parts, updated: .+DESC)
                    ->first();
                    */
                }
            }

            if (!$building) {
                /*(block for-address-match if(empty-set for strata-match) added: 05-10-2021) */
                // $building = Buildings::where('strata_no', '!=', '') /*because-404-error if(empty-strata_no)*/ [Disabled:31-03-2022] bcoz: blank-strata-no were allowed sometime ago
                $building = Buildings::where('street_no', $this->street_number)
                // ->where('geo_address', 'like', '%'.$this->streetaddress.'%') // [added:07-04-2022]
                // ->where('street_name', $this->street_name) // [Disabled:31-03-2022] bcoz: restricted--fxnality
                ->where('city', $this->city)
                ->when(strlen($this->subarea ?? '') > 0, function ($query) {return $query->where('subarea', $this->subarea);})
                ->orderByRaw(" `strata_no` LIKE '".addslashes($this->strata_no)."' DESC ")
                ->orderByRaw(" `street_name` LIKE '".addslashes($this->street_name)."' DESC ")
                ->orderByRaw(" `subarea` LIKE '".addslashes($this->subarea)."' DESC ") // [added:03-04-2022][Updated:26-04-2022] (added all the orderByRaw-parts, updated: .+DESC)
                ->first();
            }

            /** * [added: 03-04-2022][Updated:26-04-2022] * But-disabled until verified its requirement */
            // $bld_canonicalSlug = $building->getCanonicalSlug();
            // if($bld_canonicalSlug && $bld_canonicalSlug != $building->slug){
            //     $building=Buildings::where('slug', $bld_canonicalSlug)->first();
            // }

            /* [added:2025-06-02] */
            if (!$building || $building?->count() <= 0) {
                $building = $this->building()->first();
            }
            // \Debugbar::info(['this_bldg'=>$this->building, 'bldg'=>$building,'bldg_count'=>$building?->count(), 'bldg_frst'=>$this->building()?->first()]);
            $this->$_thisCachedName = $building;
            return $building;
        }); /* onceFxnality[added:2025-05-29]*/
    }

    public function get_price_history()
    {
        $_thisCachedName = 'onceCalledAndCached_getPriceHistory';
        if ($this->$_thisCachedName ?? false) {
            return $this->$_thisCachedName;
        }

        $query = "select * from boards.price_history where `change` < 0 and time_changed >'" . $this->list_date . "' and listingid = '" . $this->listingid . "' order by time_changed desc";
        $price_history =  DB::connection($this->connection_360)
            ->select($query);

        $_thisCachedName = $price_history;
        return $price_history;
    }

    public function get_final_url($tour)
    {
        $final_url = "https://player.pixilink.com/" . $tour->virtual_tour_id;
        if ($tour->data) {
            $data_obj = json_decode($tour->data);
            $data = (array) $data_obj;
            if (($data['type'] ?? '') == 'video' && ($data['provider_name'] ?? '') == 'Vimeo' && !empty($data['video_id'])) {
                $video_id = $data['video_id'];
                $final_url = "https://player.vimeo.com/video/" . $video_id . "?title=0&byline=0&portrait=0#t=7s";
            }
        }
        return $final_url;
    }

    public function get_open_house()
    {
        $_thisCachedName = 'onceCalledAndCached_getOpenHouse';
        if ($this->$_thisCachedName ?? false) {
            return $this->$_thisCachedName;
        }
        $open_house = null;
        if ($this->status == 'Active') {
            $open_house = OpenHouse::where('mls', $this->listingid)->orderBy('created', 'desc')->first();
        }
        $this->$_thisCachedName = $open_house;
        return $open_house;
    }











    public function get_commission_details($specificParameter = null)
    {
        $_thisCachedName = 'onceCalledAndCached_getCommisionDetails'.str_replace(['  ',' ',' '], '', ($specificParameter ?? ''));
        if ($this->$_thisCachedName ?? false) {
            return $this->$_thisCachedName;
        }
        // return null; // Temporarily-disabled [06-July-2021] (Because need to create some legal documentation..)
        /**
         * [$discountPercentage -- to calculate offer price, until OfferLand-functionality]
         * @var float
         */
        $discountPercentage = 30; //1.5; //was-originally-create--to-test-with-discounted-prices--before-OfferlandCSVs-were-available
        $fixedRebate = 4999;
        $our_rebate = $fixedRebate;


        /**
         * [$sentence description]
         * @var string
         * eg: "3.255% ON THE FIRST $100,000.00 AND 1.1625% ON THE BALANCE"
         * eg: "3.25 % OF 1ST 100,000 AND 1.16% OF THE BALANCE + $15,000 BONUS"
         * eg: "3% ON THE 1ST 100K; 1.5% ON THE BALANCE. PHYSICAL INTRO MUST BE BY BUYER’S AGENT OR COMMISSION WON'T BE PAID & $500 WILL BE RECEIVED BY BUYER'S AGENT."
         * eg: "3.255% ON 1ST $100,000/1.1625% ON BALANCE"
         * eg: ""
         */
        $sentence = $this->commission;
        // $sentence = "3% ON THE 1ST 100k; 1.5% ON THE BALANCE. PHYSICAL INTRO MUST BE BY BUYER’S AGENT OR COMMISSION WON'T BE PAID & $500 WILL BE RECEIVED BY BUYER'S AGENT.";
        // $sentence = "3.255% ON 1ST $100,000/1.1625% ON BALANCE";
        // sentence eg: 3.255% ON THE FIRST $100,000 AND 1.1625% ON THE BALANCE
        $viewReadyDetailsDemo = [ 'asking_price' => 0,'offer_price' => '$758,000', 'commission_offer_by_lisitng_agent' => '$10,774', 'our_rebate' => '$6,774', 'your_price' => '$751,226', 'total_savings' => '$47,774', 'save_on_permonthmortgage' => '$185'];
        $viewReadyDetails = [
            'asking_price' => 0,
            // 'offer_price'=>0,
            // 'commission_offer_by_lisitng_agent'=>0,
            // 'our_rebate'=>$our_rebate,
            // 'your_price'=>0,
            // 'total_savings'=>0,
            'save_on_permonthmortgage' => 0,
        ];
        //

        $commissionDetails = array_merge(['sentence' => $sentence], $viewReadyDetails /*,$viewReadyDetailsDemo*/);

        $asking_price = str_replace(['$',','], '', ($this->status == 'Active') ? (!empty($this->listprice) ? $this->listprice : 0) : 0);
        $commissionDetails['asking_price'] = $asking_price;

        $offer_price = round((100 - $discountPercentage) * 0.01 * $this->listprice_2);

        // $offerlandPriceObj = OfferlandPrice::where('ml_no',$this->listingid)->orderByDesc('created_at')->first();
        $offerlandPriceObj = once(function () {
            return OfferlandPrice::where('ml_no', $this->listingid)->orderByDesc('created_at')->first();
        }); /* onceFxnality[added:2025-05-29]*/

        if (!empty($offerlandPriceObj) && !empty($offerlandPriceObj->offer_value)) {
            $offer_price = $offerlandPriceObj->offer_value;
            if (!empty($specificParameter) && ($specificParameter == 'offer_price' || $specificParameter == 'offerland_price')) {
                return $offer_price;
            }
        } else {
            // return null; // changed-this-block on-6-Aug-2021 to-use-list_price-if-NO-offer_price
            // $offer_price = $this->listprice_2; // already-same-as-discountPercentage-is-ZERO
        }

        $sentence = str_replace(['1ST','1st'], '', $sentence);

        // echo "1:".$sentence;
        // echo "<br/>";
        // preg_match_all('!\d*\.?\d+\ ?\%!', $sentence ,$matches2);
        preg_match_all('!\d*\.?\d+\ ?\%!', $sentence, $matches);
        // echo "2:";
        // var_dump($matches);
        // echo "<br/>";

        $matches = (!empty($matches[0])) ? $matches[0] : [0,0];

        // echo "3:";
        // var_dump($matches);
        // echo "<br/>";

        // $commissionDetails['sentence_without_percentages'] =  preg_replace('!\d*\.?\d+\%!','', $sentence);
        preg_match_all('!\$\d*\,?\d+\.*?\d+!', preg_replace('!\d*\.?\d+\%!', '', $sentence), $amounts);
        // echo "4:";
        // var_dump($amounts);
        // echo "<br/>";
        preg_match_all('!\$?\ ?[0-9]{1,3}(?:,?[0-9])*(?:\.[0-9]{1,2})?(K|k)?!', preg_replace('!\d*\.?\d+\ ?\%!', '', $sentence), $amounts);
        // echo "5:";
        // var_dump($amounts);
        // echo "<br/>";


        $first_onamount = intval((!empty($amounts[0][0])) ? str_replace(['$',','], '', str_replace(['K','k'], '000', $amounts[0][0])) : 0);
        // echo "6:";
        // var_dump($first_onamount);
        // echo "<br/>";

        if (strpos(strtoupper($sentence), 'BONUS') !== false) {
            $bonus_amount = intval((!empty($amounts[0][1])) ? str_replace(['$',','], '', str_replace(['K','k'], '000', $amounts[0][1])) : 0);
        } else {
            $bonus_amount = 0;
        }

        // echo "7:";
        // var_dump($bonus_amount);
        // echo "<br/>";

        /**
         * [$first_percentage removing ' %', '%' and getting the float-part]
         * @var [type]
         */
        $first_percentage = floatval((!empty($matches[0])) ? $matches[0] : 0);
        // echo "8:";
        // var_dump($first_percentage);
        // echo "<br/>";
        $balance_percentage = floatval((!empty($matches[1])) ? $matches[1] : 0);
        // echo "9:";
        // var_dump($balance_percentage);
        // echo "<br/>";
        $first_amount =  $first_percentage * 0.01 * (($offer_price > $first_onamount) ? $first_onamount : $offer_price) ;
        // echo "10:";
        // var_dump($first_amount);
        // echo "<br/>";
        $balance = ($offer_price  > $first_onamount) ? ($offer_price  - $first_onamount) : 0;
        // echo "11:";
        // var_dump($balance);
        // echo "<br/>";
        if ($first_amount == 0 && $balance_percentage == 0 && !empty($first_percentage)) {
            $balance_percentage = $first_percentage; // In case there is no-first-amt, no-balance-amount, only-single-percentage-value
        }
        // echo "12:";
        // var_dump($balance_percentage);
        // echo "<br/>";
        $balance_amount = $balance_percentage * 0.01 * $balance;
        // echo "13:";
        // var_dump($balance_amount);
        // echo "<br/>";
        $total_commission = $first_amount + $balance_amount;
        // echo "14:";
        // var_dump($total_commission);
        // echo "<br/>";
        $our_rebate = ($total_commission > $fixedRebate) ? ($total_commission - $fixedRebate) : 0;
        $total_price = $offer_price - $our_rebate;
        $your_price = $total_price ;//- $our_rebate;
        $total_savings = $this->listprice_2 - $your_price;

        // preg_match_all('!\d*\.?\d+\ ?\%!', $sentence ,$matches2);
        // $commissionDetails['test_matches2'] = $matches2;
        // $commissionDetails['test_amounts'] = $amounts;

        $commissionDetails['offer_price'] = $offer_price;
        $commissionDetails['offerland_price'] = $offer_price;
        $commissionDetails['our_rebate'] = $our_rebate;
        $commissionDetails['first_onamount'] = $first_onamount;
        $commissionDetails['bonus_amount'] = $bonus_amount;
        $commissionDetails['first_percentage'] = $first_percentage;
        $commissionDetails['balance_percentage'] = $balance_percentage;
        $commissionDetails['first_amount'] = $first_amount;
        $commissionDetails['balance_amount'] = $balance_amount;
        $commissionDetails['total_commission'] = $total_commission;
        $commissionDetails['commission_offer_by_lisitng_agent'] = $total_commission;
        $commissionDetails['total_price'] = $total_price;
        $commissionDetails['your_price'] = $your_price;
        $commissionDetails['asking_price_figure'] = $asking_price;
        $commissionDetails['total_savings'] = $total_savings;

        // $otherListingsCt = Listings::where('agent_id', $this->agent_id)->where('list_date', '>=', $first_day)->where('list_date', '<=', $last_day)->count();

        // $similarPriceListings = Listings::/*briefed()->*/where('city',$this->city)->where('status','Sold')->where('listprice_2', '>=', $this->listprice_2*0.90 )->where('listprice_2', '<=', $this->listprice_2*1.10 );

        /**
         * // Commented-slowing-down-queries [on:2025-05-29] [BEGINS] ---
         */
        // $similarPriceListings = once(function () {
        //     return Listings::query()->briefed()->where('city',$this->city)->where('status','Sold')->where('listprice_2', '>=', $this->listprice_2*0.90 )->where('listprice_2', '<=', $this->listprice_2*1.10 );
        // }); /* onceFxnality[added:2025-05-29]*/

        // $mostRecentSoldListing = $similarPriceListings->orderByDesc('sold_date')/*->orderBy('dom')*/->first();
        // $commissionDetails['most_recent_sold_listing'] = $mostRecentSoldListing;

        // // $commissionDetails['similar_ones'] = $similarPriceListings->get();
        // $commissionDetails['similar_ones_avg_dom'] = 0;
        // // $commissionDetails['similar_ones_avg_dom'] = (int) round($similarPriceListings->avg('dom')??0,0);
        // $commissionDetails['similar_ones_avg_dom'] = once(function () use($similarPriceListings) {
        //     return (int) round($similarPriceListings->avg('dom')??0,0);
        // }); /* onceFxnality[added:2025-05-29]*/
        // // Commented-slowing-down-queries [on:2025-05-29] [ENDS] ---

        $commissionDetails['first_amount_moneyformat'] = Helper::money_format('%.0n', $commissionDetails['first_amount']);

        if ($this->status == 'Active') {

            if (!empty($specificParameter) && array_key_exists($specificParameter, $commissionDetails)) {
                $this->$_thisCachedName = $commissionDetails[$specificParameter];
                return $commissionDetails[$specificParameter];
            }

            $this->$_thisCachedName = $commissionDetails;
            return $commissionDetails;
        } else {
            $this->$_thisCachedName = null;
            return null;
        }
    }


    public function addressWellFormed(): Attribute
    {
        return new Attribute(get: function () {
            $addressAsH1tag = ltrim((($this->suite_no) ? $this->suite_no. ' - ' : ''). Helper::properCasePlace($this->street_number.' '.ucwords(strtolower($this->street_name)).' '.ucwords(strtolower($this->street_type))), ' - ');
            $addressAsH1tag = trim($addressAsH1tag.', '.Helper::properCasePlace($this->city).($this->province == 'BC' ? '' : ', '.strtoupper($this->province)).', '.$this->postalcode, ', ');
            return $addressAsH1tag;
        });
    }

    /**
     * getFAQs [added:01-09-2022]
     * @return array of ([q,ans])s
     */
    public function getFAQs()
    {

        $_thisCachedName = 'onceCalledAndCached_getFAQs';
        if ($this->$_thisCachedName ?? false) {
            return $this->$_thisCachedName;
        }

        $faqs = [];
        // $faqs []= ['q'=>'','ans'=>$this->yearbuilt];

        $_homeWord = strtolower($this->getType());
        $_address = $this->addressWellFormed;//getAddressWellFormedAttribute();

        if (!empty($this->yearbuilt)) {
            $faqs [] = [
                'q' => "What year was this home built in?",
                'ans' => "{$_address} was built in ".$this->yearbuilt.'.'
            ];
        }

        if (!empty($this->list_date)) {
            $faqs [] = [
                'q' => "How long has this property been listed for?",
                'ans' => "{$_address} was listed on ".date("m/d/Y", strtotime($this->list_date))
                . ((!empty($this->reoffice)) ? " by {$this->reoffice}" : '').'.'
            ];
        }

        // * For House(s) only [BEGINS]:
        if ($this->getType() == 'House') {
            if ($this->amenity ?? false) {
                $faqs [] = [
                    'q' => 'Is this property air conditioned?',
                    'ans' => $_address.' does '.((substr_count(strtoupper($this->amenity.$this->features), 'AIR COND') > 0) ? '' : 'not') .' have an air conditioner'.'.'
                ];
            }
            // How wide is the lot at [address] {house} >> The width of [address] is 55 feet
            if (($this->frontage ?? false) && $this->frontage > 0) {
                $faqs [] = [
                    // 'q'=>'How wide is the lot at '.$_address.'?',
                    'q' => 'How wide is the lot?', //' at '.$_address.'?',
                    'ans' => 'The lot is '.$this->frontage.' feet wide.',
                    // 'ans'=> 'The width of '.$_address.' is '.$this->frontage.' feet.',
                ];
            }
            if ($this->depth ?? false) {
                $faqs [] = [
                    'q' => 'How deep is the lot?', //' at '.$_address.'?',
                    'ans' => 'The lot at '.$_address.' is '.$this->depth.' feet deep.',
                ];
            }
        }
        // * For House(s) only [ENDS]:

        if (($this->getType() != 'Condo') && ($this->basement ?? false)) {
            $faqs [] = [
                'q' => 'Is there a basement in this home?',
                'ans' => $this->basement.'.',
            ];
        }


        if ($this->getOpenHouseEventsArray() ?? false) {
            $faqs [] = [
                'q' => 'Is there an open house scheduled?',
                'ans' => 'Open house event(s) scheduled on: '.$this->open_house.'.',
            ];
        }

        $_ftvnd = $this->vancouver_detached();
        if ($_ftvnd) {
            /*
            More Details on [address]
            - Heating  Natural Gas (lfd_fuelheating_17)
            - Fireplace Natural Gas (lfd_fireplace...)
            // - Number of Fireplaces - (LM_Int1_2)
            - Floor Finish - Hardwood, Mixed
            - Air Conditioning - (look for keywords in LFD_features included to figure out if it has air conditioner)
            - Laundry - In Suite Laundry
            // - Parking Access - Lane, Rear LFD_ParkingAccess_14
            - Exterior Finish - Other,Stone,Stucco  Ldf_exeteriorFinish_11
            - Roof - Asphalt
            // - Construction Type - LFD_construction_10
            // - Foundation - (LM_Char10_18)
            - Water Supply - City/Municiple   LFD_Watersupply_8
            // - Connected Services - Electricity,Natural Gas,Sanitary Sewer,Storm Sewer,Water   (LFD_ServicesConnected_7)
            - Basement Details - Fully finished with Seperate Entry
            // - Garage - Yes (look for word garage in LFD_Parking_13)
            // - Parking Spots - (LM_Int2_7) Total 5
            // - Covered - (look for word single or double in LFD_Parking_13)  single put in 1 and double put in 2
            // - Zoning - LM_Char10_17
            // - View - LM_char100_3
            */

            if ($_ftvnd->LM_Int1_2 ?? false) {
                $faqs[] = [
                    'q' => 'Does this property have fireplaces?',
                    'ans' => 'There are '.$_ftvnd->LM_Int1_2.' fireplaces at '.$_address.'.'
                ];
            }
            if (/*$_ftvnd->LM_Char10_18??*/ false) {
                $faqs[] = [
                    'q' => 'Foundation?',
                    'ans' => ''.$_ftvnd->LM_Char10_18.'.'
                ];
            }
            if ($_ftvnd->LM_Char10_17 ?? false) {
                $faqs[] = [
                    'q' => 'What zoning is this property in?',
                    'ans' => $_address.' is located in '. (('RES' == $_ftvnd->LM_Char10_17) ? 'residential' : strtolower($_ftvnd->LM_Char10_17)).' zoning'.'.'
                ];
            }
            if ($_ftvnd->LM_char100_3 ?? false) {
                $faqs[] = [
                    'q' => 'Which direction is this property facing?',
                    'ans' => $_address.' is a '.strtolower($_ftvnd->LM_char100_3).' facing lot'.'.'
                ];
            }
            if ($_ftvnd->LM_Int2_7 ?? false) {
                $faqs[] = [
                    'q' => 'How many parking spots are there?',
                    'ans' => $_address.' has '.$_ftvnd->LM_Int2_7.' parking spots.'
                ];
            }

            if (strpos(strtoupper($_ftvnd->LFD_Parking_13 ?? ''), 'GARAGE') !== false) {
                $faqs[] = [
                    'q' => 'Is there a garage?',
                    // 'ans'=>$_address.' comes with a garage.'
                    'ans' => $_address.' comes with a '.(
                        ($_ftvnd->LFD_Parking_13 ?? false)
                        ? (array_search(trim(explode('GARAGE;', strtoupper($_ftvnd->LFD_Parking_13), 2)[1] ?? ''), ['NO*Zero','SINGLE','DOUBLE']).' car garage')
                        : 'garage'
                    ).'.'
                ];
                /*
                if($_ftvnd->LFD_Parking_13??false){
                    $faqs[]=[
                        'q'=>'How many are covered?',
                        'ans'=>''.(array_search(trim(explode('GARAGE;',strtoupper($_ftvnd->LFD_Parking_13),2)[1]??''),['NO*Zero','SINGLE','DOUBLE']))
                    ];
                }*/
            } elseif ($_ftvnd->LFD_Parking_13 ?? false) {
                $faqs[] = [
                    'q' => 'Is there a garage?',
                    'ans' => $_address.' does not come with a garage.'
                ];
            }

            if ($_ftvnd->LFD_ParkingAccess_14 ?? false) {
                $faqs[] = [
                    'q' => 'Where is access to the parking?',
                    'ans' => 'You can access the parking from '.str_replace(',', ' & ', $_ftvnd->LFD_ParkingAccess_14).' at '.$_address.'.'
                ];
            }

            if ($_ftvnd->Ldf_exeteriorFinish_11 ?? false) {
                $faqs[] = [
                    'q' => 'What is the exterior finish?',
                    'ans' => ''.$_ftvnd->Ldf_exeteriorFinish_11.'.'
                ];
            }
            if ($_ftvnd->LFD_ServicesConnected_7 ?? false) {
                $faqs[] = [
                    'q' => 'What all connected services are available?',
                    'ans' => $_address.' is connected to '.$_ftvnd->LFD_ServicesConnected_7.'.'
                ];
            }
            if ($_ftvnd->LFD_construction_10 ?? false) {
                $faqs[] = [
                    'q' => 'What is its construction type?',
                    'ans' => ''.$_ftvnd->LFD_construction_10.'.'
                ];
            }

            if ($this->mlsr_listing->bylaw_restrictions ?? false) {
                $petsString = '';
                $restriction = strtolower($this->mlsr_listing->bylaw_restrictions);
                $petsNrentals = false;
                $pets = 0;
                $rentals = 0;
                if (substr_count($restriction, 'pets not') > 0) {
                    $petsNrentals = true;
                    $pets -= 1;
                }
                if (substr_count($restriction, 'rentals not') > 0) {
                    $petsNrentals = true;
                    $rentals -= 1;
                }
                if (substr_count($restriction, 'pets all') > 0) {
                    $petsNrentals = true;
                    $pets += 1;
                }
                if (substr_count($restriction, 'rentals all') > 0) {
                    $petsNrentals = true;
                    $rentals += 1;
                }
                // ucwords(strtolower(str_replace([' Allowed,',','], ', ', $this->mlsr_listing->bylaw_restrictions) ))
                // // Only put in anything that is allowed if both are disallowed - then go No Pets or Rentals
                if ($petsNrentals) {
                    $t = $pets + $rentals;
                    // $titleArray []= '|';
                    $_petsNrentalsAns = ($t < 0) ? 'No Pets or Rentals' : ($t == 2 ? 'Pets & Rentals Allowed' : ($pets > 0 ? 'Pets Allowed' : ($rentals > 0 ? 'Rentals Allowed' : ''))) ;
                    // $titleArray []= $_petsNrentalsAns;
                    $faqs [] = ['q' => 'Are pets and rentals allowed', 'ans' => ''.ucfirst(strtolower($_petsNrentalsAns))];
                }
            }

        }

        $this->_thisCachedName = $faqs;
        return $faqs;
    }

    /**
     * vancouver_detached [added:08-09-2022]
     * @return [type] [description]
     */
    public function vancouver_detached()
    {
        // $_thisCachedName = 'onceCalledAndCached_vancouverDetached';
        // if($this->$_thisCachedName ?? false){
        //     return $this->$_thisCachedName;
        // }

        return once(function () {
            $elq = DB::connection('mysql_boards')
            ->table('vancouver_detached')
            ->where('listingid', $this->listingid);
            $_thisCachedName =  $elq->first();
            return $_thisCachedName;
        }); /* onceFxnality[added:2025-05-29]*/


        // $res = DB::select("SELECT * FROM  `boards`.`vancouver_detached` WHERE `listingid` = '".$this->listingid."'");
        // return $res->get();

    }

    /**
     * getOpenHouseEventsArray [added:08-09-2022]
     * @return [type] [description]
     */
    public function getOpenHouseEventsArray()
    {
        $_thisCachedName = 'onceCalledAndCached_getOpenHouseEventsArray';
        if ($this->$_thisCachedName ?? false) {
            return $this->$_thisCachedName;
        }

        $oheArray = [];
        if ($this->open_house) {
            foreach (explode(',', $this->open_house) as $_oheIdx => $_openHouseEvent) {
                // $_oheDates = explode('/',explode('&',explode('dates=', $addToCal)[1]?:'')[0]?:'');
                $_oheStrAr = explode(':', $_openHouseEvent, 2);
                if (count($_oheStrAr) > 1) {
                    $_oheStrTimes = explode('-', $_oheStrAr[1], 2);
                    $_oheDates = [
                        strtotime($_oheStrAr[0].' '.date('y').' '.$_oheStrTimes[0].(strtotime($_oheStrTimes[0].'pm') > strtotime(($_oheStrTimes[1] ?? '')) ? 'am' : 'pm')),
                        strtotime($_oheStrAr[0].' '.date('y').' '.($_oheStrTimes[1] ?? ''))
                    ];
                    $oheArray [] = $_oheDates;
                }
            }
        }

        $_thisCachedName = $oheArray;
        return $oheArray;
    }

    public function is_featured(): bool
    {
        if (empty(self::$featured_activeMlsIds)) {
            // self::$featured_activeMlsIds = collect(
            //     DB::select("SELECT mlsid FROM bccondosandhomes.team_members WHERE mls_active = 1 AND mlsid IS NOT NULL AND mlsid !='' ")
            // )->pluck('mlsid')->map(fn($id) => strtoupper(trim($id??'')))->toArray();
            self::$featured_activeMlsIds = DB::table('bccondosandhomes.team_members')->where("mls_active",'1')->whereNotNull('mlsid')->where('mlsid','!=','')->pluck('mlsid')
            ->map(fn($id) => strtoupper(trim($id??'')))->toArray();
        }
        return in_array(strtoupper(trim($this->agent_id??'')), self::$featured_activeMlsIds, true);
    }

    /**
     * getNearbySchools
     *
     * Returns public schools within $radiusKm kilometres of this listing's
     * lat/lng coordinates, sorted by distance ascending.
     *
     * Uses ST_Distance_Sphere on MySQL. Returns an empty collection on SQLite
     * (local dev) since SQLite has no spatial support.
     *
     * @param  float $radiusKm  Search radius in kilometres (default 1.5 km)
     * @return \Illuminate\Support\Collection  Collection of plain objects with
     *         school fields + `distance_km` (float, rounded to 2 dp)
     */
    public function getNearbySchools(float $radiusKm = 1.5): \Illuminate\Support\Collection
    {
        if (empty($this->lat) || empty($this->lng)) {
            return collect();
        }

        if (DB::getDriverName() === 'sqlite') {
            return collect();
        }

        $lat       = (float) $this->lat;
        $lng       = (float) $this->lng;
        $radiusM   = $radiusKm * 1000;

        return DB::table('schools')
            ->select([
                'id',
                'name',
                'slug',
                'school_type',
                'address',
                'city',
                'latitude',
                'longitude',
                'district_name',
                'district_id',
                DB::raw('ROUND(ST_Distance_Sphere(POINT(longitude, latitude), POINT(?, ?)) / 1000, 2) AS distance_km'),
            ])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->addBinding([$lng, $lat], 'select')
            ->havingRaw('distance_km <= ?', [$radiusKm])
            ->orderBy('distance_km')
            ->get();
    }

}
