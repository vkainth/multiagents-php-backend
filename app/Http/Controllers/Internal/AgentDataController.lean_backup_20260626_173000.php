<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentSettings;
use App\Models\AgentTestimonial;
use App\Models\Buildings;
use App\Models\Listings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\LeadPipeline;

class AgentDataController extends Controller
{

    public function __construct()
    {
        header_remove('X-Powered-By');
    }

    /**
     * GET /api-internal/regions
     * Returns a map of residencity.ca region slug → agent slug for all active agents.
     */
    public function regions(): JsonResponse
    {
        $rows = DB::table('agent_settings as s')
            ->join('agents as a', 'a.id', '=', 's.agent_id')
            ->where('a.status', 'active')
            ->whereNotNull('s.residencity_region')
            ->where('s.residencity_region', '!=', '')
            ->select('s.residencity_region', 'a.slug')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->residencity_region] = $row->slug;
        }

        return response()->json($map);
    }

    public function bySlug(string $slug): JsonResponse
    {
        $agent = Agent::with(['settings', 'features'])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (! $agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        return response()->json($this->format($agent));
    }

    public function byDomain(string $domain): JsonResponse
    {
        $domain = strtolower(trim($domain));
        if (str_starts_with($domain, 'www.')) {
            $domain = substr($domain, 4);
        }

        $settings = AgentSettings::where('custom_domain', $domain)->first();
        if (! $settings) {
            return response()->json(['error' => 'Agent not found for domain'], 404);
        }

        $agent = Agent::with(['settings', 'features'])
            ->where('id', $settings->agent_id)
            ->where('status', 'active')
            ->first();

        if (! $agent) {
            return response()->json(['error' => 'Agent inactive'], 404);
        }

        return response()->json($this->format($agent));
    }

    /**
     * Territory-scoped listings search.
     * Query params: status (Active|Sold), type, min_price, max_price, beds,
     *               baths, subarea, sort (newest|price_asc|price_desc|beds|dom),
     *               days_back, page, limit.
     */
    public function featuredListings(string $slug, Request $req): JsonResponse
    {
        $agent = Agent::with(['territories', 'settings'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        if (empty($cities)) {
            return response()->json(['data' => [], 'total' => 0, 'page' => 1, 'limit' => 24]);
        }

        // Restrict to the agent's configured subarea whitelist when present.
        $subareaWhitelist = null;
        if ($agent->settings) {
            $raw = $agent->settings->subarea_whitelist;
            if ($raw) {
                $decoded = is_array($raw) ? $raw : json_decode($raw, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $subareaWhitelist = $decoded;
                }
            }
        }

        $status   = $req->query('status', 'Active');
        $type     = $req->query('type');
        $subarea  = $req->query('subarea');
        $minPrice = (int) $req->query('min_price', 0);
        $maxPrice = (int) $req->query('max_price', 0);
        $beds     = (int) $req->query('beds', 0);
        $baths    = (float) $req->query('baths', 0);
        $sort     = $req->query('sort', 'newest');
        $daysBack = (int) $req->query('days_back', 0);
        $priceReduced = (int) $req->query('price_reduced', 0);
        $page     = max(1, (int) $req->query('page', 1));
        $limit    = min(250, max(1, (int) $req->query('limit', 24)));

        $priceCol = $status === 'Sold' ? 'soldprice_2' : 'listprice_2';

        $q = Listings::withoutGlobalScopes()
            ->whereIn('city', $cities)
            ->where('status', $status)
            ->select([
                'sysid', 'listingid', 'streetaddress', 'city', 'subarea',
                'status', 'listprice', 'listprice_2', 'soldprice', 'soldprice_2',
                'bedrooms', 'bathstotal', 'livingarea', 'livingarea_2', 'mainpicurl', 'thumbnailurl', 'slug',
                'type', 'home_style', 'dom', 'lat', 'lng',
                'yearbuilt', 'maintenance', 'sold_date', 'list_date',
                'original_price', 'prev_price',
                'lotsize', 'frontage', 'finished_levels',
            ]);

        if ($subareaWhitelist) {
            $q->whereIn('subarea', $subareaWhitelist);
        }

        if ($subarea)  $q->where('subarea', $subarea);
        if ($type)     $q->where('type', $type);
        if ($minPrice) $q->where($priceCol, '>=', $minPrice);
        if ($maxPrice) $q->where($priceCol, '<=', $maxPrice);
        if ($beds)     $q->where('bedrooms', '>=', $beds);
        if ($baths)    $q->where('bathstotal', '>=', $baths);
        if ($daysBack > 0) {
            $dateCol = $status === 'Sold' ? 'sold_date' : 'list_date';
            $q->where($dateCol, '>=', now()->subDays($daysBack)->format('Y-m-d'));
        }
        if ($priceReduced && $status === 'Active') {
            $q->whereColumn('original_price', '>', 'listprice_2')->where('listprice_2', '>', 0);
        }
        $month = $req->query('month'); // e.g. "2026-04" — filter sold listings to a calendar month
        if ($month && $status === 'Sold' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $q->whereRaw("DATE_FORMAT(sold_date, '%Y-%m') = ?", [$month]);
        }
        $year = (int) $req->query('year', 0);
        if ($year >= 2000 && $year <= 2100 && $status === 'Sold') {
            $q->whereYear('sold_date', $year);
        }

        $minYear = (int) $req->query('min_year', 0);
        if ($minYear) $q->where('yearbuilt', '>=', $minYear);

        $minLotSize = (int) $req->query('min_lot_size', 0);
        if ($minLotSize) $q->whereRaw('CAST(lotsize AS DECIMAL(15,2)) >= ?', [$minLotSize]);

        // Exclude listings with no photos - prevents blank cards in all grids.
        $q->whereRaw("(NULLIF(mainpicurl, '') IS NOT NULL OR NULLIF(thumbnailurl, '') IS NOT NULL)");

        // Sort by displayable columns. Supports both legacy single-token values
        // (newest/beds/dom/price_asc/price_desc) and explicit <field>_<dir> keys
        // emitted by the clickable list-table headers.
        $sqftSort = "CAST(REPLACE(COALESCE(NULLIF(livingarea_2, ''), livingarea, '0'), ',', '') AS DECIMAL(12,2))";
        switch ($sort) {
            case 'price_asc':  $q->orderBy($priceCol, 'asc'); break;
            case 'price_desc': $q->orderBy($priceCol, 'desc'); break;
            case 'beds':
            case 'beds_desc':  $q->orderBy('bedrooms', 'desc'); break;
            case 'beds_asc':   $q->orderBy('bedrooms', 'asc'); break;
            case 'baths_asc':  $q->orderBy('bathstotal', 'asc'); break;
            case 'baths_desc': $q->orderBy('bathstotal', 'desc'); break;
            case 'address_asc':  $q->orderBy('streetaddress', 'asc'); break;
            case 'address_desc': $q->orderBy('streetaddress', 'desc'); break;
            case 'type_asc':   $q->orderBy('type', 'asc'); break;
            case 'type_desc':  $q->orderBy('type', 'desc'); break;
            case 'sqft_asc':   $q->orderByRaw("$sqftSort asc"); break;
            case 'sqft_desc':  $q->orderByRaw("$sqftSort desc"); break;
            case 'lot_size_asc':  $q->orderByRaw('CAST(lotsize AS DECIMAL(15,2)) asc'); break;
            case 'lot_size_desc': $q->orderByRaw('CAST(lotsize AS DECIMAL(15,2)) desc'); break;
            case 'frontage_asc':  $q->orderByRaw('CAST(frontage AS DECIMAL(10,2)) asc'); break;
            case 'frontage_desc': $q->orderByRaw('CAST(frontage AS DECIMAL(10,2)) desc'); break;
            case 'levels_asc':  $q->orderByRaw('CAST(finished_levels AS DECIMAL(5,1)) asc'); break;
            case 'levels_desc': $q->orderByRaw('CAST(finished_levels AS DECIMAL(5,1)) desc'); break;
            case 'date_asc':   $q->orderBy('sold_date', 'asc'); break;
            case 'date_desc':  $q->orderBy('sold_date', 'desc'); break;
            case 'dom':
            case 'dom_asc':
                // Lowest days-on-market first. For Active, dom is derived from
                // list_date (newest listing = fewest days), so order by list_date desc.
                if ($status === 'Sold') $q->orderBy('dom', 'asc');
                else $q->orderBy('list_date', 'desc');
                break;
            case 'dom_desc':
                if ($status === 'Sold') $q->orderBy('dom', 'desc');
                else $q->orderBy('list_date', 'asc');
                break;
            case 'newest':
            default:
                $q->orderBy($status === 'Sold' ? 'sold_date' : 'list_date', 'desc');
                break;
        }

        $total = (clone $q)->count();

        $listings = $q->forPage($page, $limit)->get();

        return response()->json([
            'data'  => $listings->map(fn ($l) => [
                'id'         => $l->sysid,
                'mls_no'     => $l->listingid,
                'address'    => $l->streetaddress,
                'city'       => $l->city,
                'subarea'    => $l->subarea,
                'status'     => $l->status,
                'list_price' => (int) ($l->listprice_2 ?: $l->listprice),
                'original_price'   => ((int) $l->original_price > 0) ? (int) $l->original_price : null,
                'price_reduced'    => ($l->status === 'Active' && (int) $l->original_price > (int) ($l->listprice_2 ?: $l->listprice) && (int) ($l->listprice_2 ?: $l->listprice) > 0),
                'reduction_amount' => ($l->status === 'Active' && (int) $l->original_price > (int) ($l->listprice_2 ?: $l->listprice) && (int) ($l->listprice_2 ?: $l->listprice) > 0) ? ((int) $l->original_price - (int) ($l->listprice_2 ?: $l->listprice)) : 0,
                'sold_price' => ($l->soldprice_2 > 0) ? (int) $l->soldprice_2 : (($l->soldprice > 0) ? (int) $l->soldprice : null),
                'beds'       => (int) $l->bedrooms,
                'baths'      => (float) $l->bathstotal,
                'sqft'       => (int) str_replace(",", "", (string) ($l->livingarea_2 ?: $l->livingarea ?: "0")),
                'photo_url'  => $l->mainpicurl ?: $l->thumbnailurl ?: null,
                'type'       => $l->type,
                'style'      => $l->home_style,
                'slug'       => $l->slug,
                'dom'        => ($l->status === 'Active' && !empty($l->list_date) && $l->list_date !== '0000-00-00')
                    ? (int) floor((now()->timestamp - strtotime($l->list_date)) / 86400)
                    : ((isset($l->dom) && $l->dom) ? (int) $l->dom : null),
                'sold_date'  => ($l->sold_date && $l->sold_date !== '0000-00-00') ? (string) $l->sold_date : null,
                'year_built' => $l->yearbuilt ? (int) $l->yearbuilt : null,
                'strata_fee' => ($l->maintenance > 0) ? (float) $l->maintenance : null,
                'lot_size'   => ($l->lotsize > 0) ? (float) $l->lotsize : null,
                'frontage'   => ($l->frontage > 0) ? (float) $l->frontage : null,
                'levels'     => ($l->finished_levels > 0) ? (int) $l->finished_levels : null,
                'latitude'   => (isset($l->lat) && $l->lat != 0) ? (float) $l->lat : null,
                'longitude'  => (isset($l->lng) && $l->lng != 0) ? (float) $l->lng : null,
            ]),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Single listing detail by slug.
     */
    public function listingDetail(string $slug, string $listingSlug): JsonResponse
    {
        $listing = Listings::withoutGlobalScopes()
            ->where('slug', $listingSlug)
            ->orWhere('listingid', $listingSlug)
            ->first();

        if (! $listing) return response()->json(['error' => 'Listing not found'], 404);

        // Photos
        $photos = [];
        try {
            $photoModels = $listing->photos()->orderBy('id')->limit(60)->get();
            $photos = $photoModels->map(fn($p) =>
                'https://media.pixilinkserver.com/' . ltrim(str_replace('images', '', $p->directory . $p->name), '/')
            )->filter()->values()->toArray();
        } catch (\Throwable $e) { $photos = []; }
        if (empty($photos) && $listing->mainpicurl) $photos = [$listing->mainpicurl];

        // Feature / amenity parsing
        $parseList = static function (?string $raw): array {
            if (!$raw) return [];
            $cleaned = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return array_values(array_filter(array_map('trim', preg_split('/[;,]/', $cleaned))));
        };
        $features  = $parseList($listing->features);
        $amenities = $parseList($listing->amenity);

        // Open house (next upcoming event)
        $openHouse = null;
        try {
            $oh = DB::connection('mysql_mlsr')
                ->table('open_house')
                ->where('mls', $listing->listingid)
                ->where('date', '>=', now()->format('Y-m-d'))
                ->orderBy('date')
                ->orderBy('start')
                ->first();
            if ($oh) {
                $openHouse = [
                    'date'    => $oh->date,
                    'start'   => $oh->start,
                    'finish'  => $oh->finish,
                    'remarks' => $oh->remarks ?? null,
                ];
            }
        } catch (\Throwable $e) {}

        // Linked building
        $linkedBuilding = null;
        try {
            if ($listing->strata_no) {
                $b = Buildings::withoutGlobalScopes()
                    ->where('strata_no', $listing->strata_no)
                    ->whereNotNull('strata_no')
                    ->where('strata_no', '!=', '')
                    ->select(['id', 'name', 'slug', 'city', 'subarea', 'yearbuilt'])
                    ->first();
                if ($b) {
                    $linkedBuilding = [
                        'id'         => (string) $b->id,
                        'name'       => $b->name,
                        'slug'       => $b->slug,
                        'city'       => $b->city,
                        'subarea'    => $b->subarea,
                        'year_built' => $b->yearbuilt ? (int) $b->yearbuilt : null,
                    ];
                }
            }
        } catch (\Throwable $e) {}

        // mlsr_listing (floor area + rooms + bath detail)
        $mlsr = null;
        try { $mlsr = $listing->mlsr_listing; } catch (\Throwable $e) {}

        $floorArea = null;
        if ($mlsr) {
            $fa = array_filter([
                'main'       => ($mlsr->main_floor_area_2 ?? null) ? (int) $mlsr->main_floor_area_2 : null,
                'above'      => ($mlsr->above_main_area   ?? null) ? (int) $mlsr->above_main_area   : null,
                'below'      => ($mlsr->below_main_area   ?? null) ? (int) $mlsr->below_main_area   : null,
                'basement'   => ($mlsr->basement_area     ?? null) ? (int) $mlsr->basement_area     : null,
                'unfinished' => ($mlsr->unfinished_area   ?? null) ? (int) $mlsr->unfinished_area   : null,
                'total'      => ($mlsr->livingarea_2      ?? null) ? (int) $mlsr->livingarea_2      : null,
            ], fn($v) => $v !== null);
            $floorArea = !empty($fa) ? $fa : null;
        }

        $rooms = [];
        if ($mlsr) {
            for ($i = 1; $i <= 28; $i++) {
                $level = $mlsr->{"room{$i}_level"} ?? null;
                if (!$level) continue;
                $rooms[] = [
                    'level' => $level,
                    'type'  => $mlsr->{"room{$i}_type"}  ?? null,
                    'dim1'  => $mlsr->{"room{$i}_dim1"}  ?? null,
                    'dim2'  => $mlsr->{"room{$i}_dim2"}  ?? null,
                ];
            }
        }

        $bathsDetail = [];
        if ($mlsr) {
            for ($i = 1; $i <= 8; $i++) {
                $level = $mlsr->{"bath{$i}_level"} ?? null;
                if (!$level) continue;
                $bathsDetail[] = [
                    'level'   => $level,
                    'ensuite' => $mlsr->{"bath{$i}_ensuite"} ?? null,
                    'pieces'  => $mlsr->{"bath{$i}_pieces"}  ?? null,
                ];
            }
        }

        // DOM
        $dom = null;
        if ($listing->status === 'Active' && ($listing->list_date ?? null)) {
            $dom = (int) floor((now()->timestamp - strtotime($listing->list_date)) / 86400);
        } elseif (isset($listing->dom) && $listing->dom) {
            $dom = (int) $listing->dom;
        }

        return response()->json([
            'id'               => $listing->sysid,
            'mls_no'           => $listing->listingid,
            'address'          => $listing->streetaddress,
            'city'             => $listing->city,
            'subarea'          => $listing->subarea,
            'status'           => $listing->status,
            'list_price'       => (int) $listing->listprice_2,
            'sold_price'       => $listing->soldprice_2 ? (int) $listing->soldprice_2 : null,
            'original_price'   => ((int) $listing->original_price > 0) ? (int) $listing->original_price : null,
            'price_reduced'    => ((int) $listing->original_price > (int) $listing->listprice_2 && (int) $listing->listprice_2 > 0),
            'beds'             => (int) $listing->bedrooms,
            'baths'            => (float) $listing->bathstotal,
            'sqft'             => (int) str_replace(",", "", (string) ($listing->livingarea_2 ?: $listing->livingarea ?: "0")),
            'photo_url'        => $listing->mainpicurl ?: null,
            'photos'           => $photos,
            'type'             => $listing->type,
            'style'            => $listing->home_style,
            'slug'             => $listing->slug,
            'description'      => $listing->publicremarks ?? $listing->remarks ?? null,
            'year_built'       => isset($listing->yearbuilt) ? (int) $listing->yearbuilt : null,
            'strata_fee'       => isset($listing->maintenance) && $listing->maintenance > 0 ? (float) $listing->maintenance : null,
            'latitude'         => isset($listing->lat) && $listing->lat != 0 ? (float) $listing->lat : null,
            'longitude'        => isset($listing->lng) && $listing->lng != 0 ? (float) $listing->lng : null,
            'dom'              => $dom,
            'lot_size'         => isset($listing->lotsize) && $listing->lotsize ? (float) $listing->lotsize : null,
            'parking'          => $listing->parking ?: null,
            'basement'         => $listing->basement ?: null,
            'tax_amount'       => isset($listing->taxamount) && $listing->taxamount > 0 ? (float) $listing->taxamount : null,
            'tax_year'         => isset($listing->taxyear) && $listing->taxyear ? (int) $listing->taxyear : null,
            'sold_date'        => ($listing->sold_date && $listing->sold_date !== '0000-00-00') ? $listing->sold_date : null,
            'virtual_tour'     => $listing->virtualtoururl ?: null,
            'features'         => $features,
            'amenities'        => $amenities,
            'open_house'       => $openHouse,
            'building'         => $linkedBuilding,
            'heating'              => $listing->heating ?: null,
            'kitchens'             => $listing->kitchens ? (int) $listing->kitchens : null,
            'roof'                 => $mlsr?->roof ?: ($listing->roof ?? null) ?: null,
            'strata_no'            => $listing->strata_no ?: null,
            'postal_code'          => $listing->postalcode ?: null,
            'complex'              => $listing->complex ? ucwords(strtolower($listing->complex)) : null,
            'reno_year'            => ($mlsr?->reno_year ?? null) ? (int) $mlsr->reno_year : null,
            'units_in_development' => ($mlsr?->units_in_development ?? null) ? (int) $mlsr->units_in_development : null,
            'units_in_strata'      => ($mlsr?->units_in_strata ?? null) ? (int) $mlsr->units_in_strata : null,
            'reoffice'             => $listing->reoffice ?: null,
            'frontage'             => $listing->frontage ? (float) $listing->frontage : null,
            'depth'                => $listing->depth ? (float) $listing->depth : null,
            'garage_size'          => $listing->garage_size ?: null,
            'floor_area'           => $floorArea,
            'rooms'                => $rooms,
            'baths_detail'         => $bathsDetail,
            'similar_active'         => [],
            'similar_sold'           => [],
            'neighbourhood'          => null,
            'building_active'        => [],
            'building_solds_summary' => null,
            'price_history'          => [],
            'listing_history'        => [],
        ]);
    }

    /**
     * Supplemental listing data loaded client-side to keep listingDetail fast.
     * Heavy sub-queries: similar listings, neighbourhood stats, price/listing
     * history, building active units, building solds summary.
     */
    public function listingSupplemental(string $slug, string $listingSlug): JsonResponse
    {
        $listing = Listings::withoutGlobalScopes()
            ->where('slug', $listingSlug)
            ->orWhere('listingid', $listingSlug)
            ->first();

        if (! $listing) return response()->json(['error' => 'Listing not found'], 404);

        // Building solds summary (last 90 days, same strata_no)
        $buildingSoldsSummary = null;
        try {
            if ($listing->strata_no) {
                $bSolds = Listings::withoutGlobalScopes()
                    ->where('status', 'Sold')
                    ->where('strata_no', $listing->strata_no)
                    ->where('sold_date', '>=', now()->subDays(90)->format('Y-m-d'))
                    ->where('soldprice_2', '>', 0)
                    ->selectRaw('COUNT(*) as cnt, AVG(soldprice_2) as avg_sold_price')
                    ->first();
                if ($bSolds && (int) $bSolds->cnt > 0) {
                    $buildingSoldsSummary = [
                        'count'          => (int) $bSolds->cnt,
                        'avg_sold_price' => (int) round($bSolds->avg_sold_price),
                    ];
                }
            }
        } catch (\Throwable $e) {}

        // Other active listings in same building (strata_no + street_number)
        $buildingActive = [];
        try {
            if ($listing->strata_no) {
                $bq = Listings::withoutGlobalScopes()
                    ->where('status', 'Active')
                    ->where('strata_no', $listing->strata_no)
                    ->when($listing->street_number, fn ($q) => $q->where('street_number', $listing->street_number))
                    ->select([
                        'sysid', 'listingid', 'streetaddress', 'city', 'subarea',
                        'status', 'listprice', 'listprice_2', 'bedrooms', 'bathstotal',
                        'livingarea', 'livingarea_2', 'mainpicurl', 'slug', 'type', 'home_style',
                        'dom', 'list_date', 'maintenance', 'taxamount', 'taxyear', 'reoffice',
                        'original_price', 'prev_price',
                    ])
                    ->orderBy('listprice_2', 'asc')
                    ->limit(50)
                    ->get();
                $buildingActive = $bq->map(function ($l) {
                    $computedDom = ($l->list_date && $l->list_date !== '0000-00-00')
                        ? (int) floor((now()->timestamp - strtotime($l->list_date)) / 86400)
                        : (isset($l->dom) && $l->dom ? (int) $l->dom : null);
                    return [
                        'id'              => $l->sysid,
                        'mls_no'          => $l->listingid,
                        'address'         => $l->streetaddress,
                        'city'            => $l->city,
                        'subarea'         => $l->subarea,
                        'status'          => $l->status,
                        'list_price'      => (int) ($l->listprice_2 ?: $l->listprice),
                        'original_price'  => ((int) $l->original_price > 0) ? (int) $l->original_price : null,
                        'price_reduced'   => ((int) $l->original_price > (int) ($l->listprice_2 ?: $l->listprice) && (int) ($l->listprice_2 ?: $l->listprice) > 0),
                        'sold_price'      => null,
                        'beds'            => (int) $l->bedrooms,
                        'baths'           => (float) $l->bathstotal,
                        'sqft'            => (int) str_replace(',', '', (string) ($l->livingarea_2 ?: $l->livingarea ?: '0')),
                        'photo_url'       => $l->mainpicurl ?: null,
                        'type'            => $l->type,
                        'style'           => $l->home_style,
                        'slug'            => $l->slug,
                        'dom'             => $computedDom,
                        'list_date'       => $l->list_date ?: null,
                        'strata_fee'      => ($l->maintenance > 0) ? (float) $l->maintenance : null,
                        'tax_amount'      => (isset($l->taxamount) && $l->taxamount > 0) ? (float) $l->taxamount : null,
                        'tax_year'        => (isset($l->taxyear) && $l->taxyear) ? (int) $l->taxyear : null,
                        'listed_by'       => $l->reoffice ?: null,
                    ];
                })->toArray();
            }
        } catch (\Throwable $e) {}

        // Similar active listings (same subarea + type)
        $similarActive = [];
        try {
            $similarActive = Listings::withoutGlobalScopes()
                ->where('status', 'Active')
                ->where('subarea', $listing->subarea)
                ->when($listing->type, fn ($q) => $q->where('type', $listing->type))
                ->where('sysid', '!=', $listing->sysid)
                ->select([
                    'sysid', 'listingid', 'streetaddress', 'city', 'subarea',
                    'status', 'listprice', 'listprice_2', 'bedrooms', 'bathstotal',
                    'livingarea', 'livingarea_2', 'mainpicurl', 'slug', 'type', 'home_style',
                    'list_date', 'maintenance', 'taxamount', 'reoffice',
                ])
                ->orderByDesc('sysid')
                ->limit(6)
                ->get()
                ->map(fn ($l) => [
                    'id'         => $l->sysid,
                    'mls_no'     => $l->listingid,
                    'address'    => $l->streetaddress,
                    'city'       => $l->city,
                    'subarea'    => $l->subarea,
                    'status'     => $l->status,
                    'list_price' => (int) $l->listprice_2,
                    'sold_price' => null,
                    'beds'       => (int) $l->bedrooms,
                    'baths'      => (float) $l->bathstotal,
                    'sqft'       => (int) str_replace(",", "", (string) ($l->livingarea_2 ?: $l->livingarea ?: "0")),
                    'photo_url'  => $l->mainpicurl ?: $l->thumbnailurl ?: null,
                    'type'       => $l->type,
                    'style'      => $l->home_style,
                    'slug'       => $l->slug,
                    'dom'        => ($l->list_date && $l->list_date !== '0000-00-00')
                        ? (int) floor((time() - strtotime($l->list_date)) / 86400)
                        : null,
                    'list_date'  => $l->list_date ?: null,
                    'strata_fee' => $l->maintenance > 0 ? (float) $l->maintenance : null,
                    'tax_amount' => $l->taxamount > 0 ? (float) $l->taxamount : null,
                    'listed_by'  => $l->reoffice ?: null,
                ])
                ->toArray();
        } catch (\Throwable $e) {}

        // Similar sold listings (same subarea + type, last 90 days)
        $similarSold = [];
        try {
            $similarSold = Listings::withoutGlobalScopes()
                ->where('status', 'Sold')
                ->where('subarea', $listing->subarea)
                ->when($listing->type, fn ($q) => $q->where('type', $listing->type))
                ->where('sysid', '!=', $listing->sysid)
                ->where('sold_date', '>=', now()->subDays(90)->format('Y-m-d'))
                ->select([
                    'sysid', 'listingid', 'streetaddress', 'city', 'subarea',
                    'status', 'listprice', 'listprice_2', 'soldprice_2', 'bedrooms', 'bathstotal',
                    'livingarea', 'livingarea_2', 'mainpicurl', 'slug', 'type', 'home_style', 'sold_date',
                    'dom', 'maintenance', 'taxamount', 'reoffice',
                ])
                ->orderByDesc('sold_date')
                ->limit(6)
                ->get()
                ->map(fn ($l) => [
                    'id'         => $l->sysid,
                    'mls_no'     => $l->listingid,
                    'address'    => $l->streetaddress,
                    'city'       => $l->city,
                    'subarea'    => $l->subarea,
                    'status'     => $l->status,
                    'list_price' => (int) $l->listprice_2,
                    'sold_price' => $l->soldprice_2 ? (int) $l->soldprice_2 : null,
                    'beds'       => (int) $l->bedrooms,
                    'baths'      => (float) $l->bathstotal,
                    'sqft'       => (int) str_replace(",", "", (string) ($l->livingarea_2 ?: $l->livingarea ?: "0")),
                    'photo_url'  => $l->mainpicurl ?: $l->thumbnailurl ?: null,
                    'type'       => $l->type,
                    'style'      => $l->home_style,
                    'slug'       => $l->slug,
                    'dom'        => $l->dom ? (int) $l->dom : null,
                    'strata_fee' => $l->maintenance > 0 ? (float) $l->maintenance : null,
                    'tax_amount' => $l->taxamount > 0 ? (float) $l->taxamount : null,
                    'listed_by'  => $l->reoffice ?: null,
                ])
                ->toArray();
        } catch (\Throwable $e) {}

        // Neighbourhood stats (subarea, last 30 days sold window)
        $neighbourhood = null;
        try {
            if ($listing->subarea) {
                $active = Listings::withoutGlobalScopes()
                    ->where('subarea', $listing->subarea)
                    ->where('status', 'Active')
                    ->selectRaw('COUNT(*) as active_count, AVG(listprice_2) as avg_list_price')
                    ->first();
                $sold = Listings::withoutGlobalScopes()
                    ->where('subarea', $listing->subarea)
                    ->where('status', 'Sold')
                    ->where('sold_date', '>=', now()->subDays(30)->format('Y-m-d'))
                    ->whereNotNull('soldprice_2')
                    ->where('soldprice_2', '>', 0)
                    ->selectRaw('COUNT(*) as sold_count, AVG(soldprice_2) as avg_sold_price, AVG(DATEDIFF(sold_date, list_date)) as avg_dom, AVG(soldprice_2 / NULLIF(listprice_2, 0)) * 100 as sale_to_list')
                    ->first();
                $activeCount = (int) ($active?->active_count ?? 0);
                $sold30d     = (int) ($sold?->sold_count ?? 0);
                $absorption  = $sold30d > 0 ? round($activeCount / $sold30d, 2) : 9.9;
                $marketType  = match (true) {
                    $absorption <= 2.5  => 'strong-sellers',
                    $absorption <= 5.0  => 'sellers',
                    $absorption <= 8.33 => 'balanced',
                    default          => 'buyers',
                };
                $neighbourhood = [
                    'subarea'         => $listing->subarea,
                    'city'            => $listing->city,
                    'active'          => $activeCount,
                    'active_count'    => $activeCount,
                    'sold_30d'        => $sold30d,
                    'avg_list_price'  => $active?->avg_list_price ? (int) round($active->avg_list_price) : null,
                    'avg_sold_price'  => $sold?->avg_sold_price ? (int) round($sold->avg_sold_price) : null,
                    'avg_dom'         => $sold?->avg_dom ? (int) round($sold->avg_dom) : null,
                    'sale_to_list'    => $sold?->sale_to_list ? round($sold->sale_to_list, 1) : null,
                    'absorption_rate' => (float) $absorption,
                    'market_type'     => $marketType,
                ];
            }
        } catch (\Throwable $e) {}

        // Price & listing history
        $priceHistory = [];
        try {
            $changes = $listing->get_price_history();
            $listprice = $listing->listprice;
            foreach ($changes as $pc) {
                $priceHistory[] = [
                    'date'   => $pc->time_changed,
                    'mls'    => $listing->listingid,
                    'status' => 'Price Updated',
                    'price'  => (int) $pc->price,
                ];
                $listprice = $pc->price + abs($pc->change);
            }
            if ($listing->list_date) {
                $priceHistory[] = [
                    'date'   => $listing->list_date,
                    'mls'    => $listing->listingid,
                    'status' => 'Active',
                    'price'  => (int) $listprice,
                ];
            }
        } catch (\Throwable $e) {}

        if (in_array($listing->status, ['Sold', 'Terminated', 'Expired']) && !empty($priceHistory)) {
            array_unshift($priceHistory, [
                'date'   => $listing->status === 'Sold' ? ($listing->sold_date ?? $listing->last_modified) : $listing->last_modified,
                'mls'    => $listing->listingid,
                'status' => $listing->status,
                'price'  => $listing->status === 'Sold' ? (int) $listing->soldprice : (int) $listing->listprice,
            ]);
        }

        $listingHistory = [];
        try {
            foreach ($listing->getHistory() as $h) {
                $isSoldH = $h->status === 'Sold';
                $listingHistory[] = [
                    'date'   => $isSoldH ? ($h->sold_date ?? $h->last_modified) : $h->last_modified,
                    'mls'    => $h->listingid,
                    'status' => $h->status,
                    'price'  => (int) ($isSoldH ? ($h->soldprice_2 ?? $h->soldprice ?? 0) : ($h->listprice_2 ?? $h->listprice ?? 0)),
                ];
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'building_solds_summary' => $buildingSoldsSummary,
            'building_active'        => $buildingActive,
            'similar_active'         => $similarActive,
            'similar_sold'           => $similarSold,
            'neighbourhood'          => $neighbourhood,
            'price_history'          => $priceHistory,
            'listing_history'        => $listingHistory,
        ]);
    }

    /**
     * Agent media items (headshot, gallery).
     * Returns a JSON array of media items for the given agent slug.
     * Falls back to the agent photo_path field if no dedicated media table exists.
     */
    public function media(string $slug, \Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $agent = \App\Models\Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json([], 200);

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('agent_media')) {
                $collection = $req->query('collection');
                $q = \Illuminate\Support\Facades\DB::table('agent_media')
                    ->where('agent_id', $agent->id);
                if ($collection) $q->where('collection', $collection);
                $rows = $q->orderBy('sort_order')->orderBy('id')->get();
                if ($rows->isNotEmpty()) {
                    return response()->json($rows->map(function($m) use ($agent) { return [
                        'id'            => $m->id,
                        'type'          => $m->type ?? 'headshot',
                        'collection'    => $m->collection ?? 'profile',
                        'url'           => $m->url ?? null,
                        'thumbnail_url' => $m->thumbnail_url ?? null,
                        'caption'       => $m->caption ?? $agent->name,
                        'alt'           => $m->alt ?? $agent->name,
                    ]; })->values());
                }
            }
        } catch (\Throwable $e) {}

        // Fallback: use agent photo_path (either /storage/ or absolute HTTPS URL)
        if ($agent->photo_path && (str_starts_with($agent->photo_path, '/storage/') || str_starts_with($agent->photo_path, 'http'))) {
            return response()->json([[
                'id'            => 1,
                'type'          => 'headshot',
                'collection'    => 'profile',
                'url'           => $agent->photo_path,
                'thumbnail_url' => null,
                'caption'       => $agent->name,
                'alt'           => $agent->name,
            ]]);
        }

        return response()->json([]);
    }

    /**
     * Agent awards - returns records if the table exists, empty array otherwise.
     */
    public function awards(string $slug): JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json([], 200);

        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('agent_awards')) {
                return response()->json([], 200);
            }
            $awards = DB::table('agent_awards')
                ->where('agent_id', $agent->id)
                ->orderBy('year', 'desc')
                ->get();
            return response()->json($awards->map(fn($a) => [
                'id'          => $a->id,
                'title'       => $a->title ?? null,
                'year'        => $a->year ?? null,
                'description' => $a->description ?? null,
                'image_url'   => $a->image_url ?? null,
            ])->values());
        } catch (\Throwable $e) {
            return response()->json([], 200);
        }
    }

    public function territories(string $slug): JsonResponse
    {
        $agent = Agent::with(['territories'])->where('slug', $slug)->first();
        if (! $agent) return response()->json([], 200);
        try {
            return response()->json($agent->territories->map(function($t) {
                return [
                    'id'      => $t->id,
                    'city'    => $t->city ?? null,
                    'subarea' => $t->subarea ?? null,
                ];
            })->values());
        } catch (\Throwable $e) {
            return response()->json([], 200);
        }
    }

        public function featuredBuildings(string $slug, Request $req = null): JsonResponse
    {
        $req = $req ?? request();
        $agent = Agent::with(['territories'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        if (empty($cities)) return response()->json([]);

        $subarea = $req->query('subarea');
        $page    = max(1, (int) $req->query('page', 1));
        $limit   = min(1000, max(1, (int) $req->query('limit', 6)));

        $fbSettingsRow = \Illuminate\Support\Facades\DB::table('agent_settings')
            ->where('agent_id', $agent->id)->first();
        $fbWhitelist = ($fbSettingsRow && $fbSettingsRow->subarea_whitelist)
            ? json_decode($fbSettingsRow->subarea_whitelist, true) : null;

        $q = Buildings::whereIn('city', $cities)
            ->when(!empty($fbWhitelist), function ($query) use ($fbWhitelist) {
                $query->whereIn('subarea', $fbWhitelist);
            })
            ->select([
                'id', 'name', 'slug', 'city', 'subarea',
                'yearbuilt', 'units_in_development', 'strata_no', 'levels',
                'street_no', 'street_name', 'street_type',
                'postalcode', 'status_sync', 'title_to_land', 'construction',
            ]);

        if ($subarea) $q->where('subarea', $subarea);

        $buildings = $q->orderByDesc('yearbuilt')
            ->limit(2000)
            ->get();

        $subareaKey = $subarea ? preg_replace('/[^a-z0-9]/', '_', strtolower($subarea)) : 'all';
        $cacheKey   = 'agent_featured_buildings_' . $agent->id . '_' . $subareaKey
                      . '_' . date('YmdH');

        $mapped = Cache::remember($cacheKey, 3600, function () use ($buildings) {
            return $buildings->map(function (Buildings $b) {
            $photoUrl = null;
            try { $photoUrl = $b->main_image() ?: null; } catch (\Throwable $e) {}
            if ($photoUrl && !str_starts_with($photoUrl, 'https://')) { $photoUrl = null; }

            $activeCount = 0;
            $min = null;
            $max = null;

            // Skip DB queries for pre-construction / no strata (avoids cross-province junk)
            if ($b->strata_no && trim($b->strata_no) !== '' && (int) ($b->yearbuilt ?? 0) <= (int) date('Y')) {
                try {
                    $agg = Listings::withoutGlobalScopes()
                        ->where('status', 'Active')
                        ->where('strata_no', $b->strata_no)
                        ->when($b->street_no, fn ($q) => $q->where('street_number', $b->street_no))
                        ->selectRaw('COUNT(*) as c, MIN(listprice_2) as mn, MAX(listprice_2) as mx')
                        ->first();
                    $activeCount = (int) ($agg->c ?? 0);
                    $min = ($agg->mn ?? null) ? (int) $agg->mn : null;
                    $max = ($agg->mx ?? null) ? (int) $agg->mx : null;
                } catch (\Throwable $e) {}
            }

            $streetRaw = trim(preg_replace('/\s+/', ' ',
                ($b->street_no ?? '') . ' ' . ($b->street_name ?? '') . ' ' . ($b->street_type ?? '')));
            $address = $streetRaw !== '' ? ucwords(strtolower($streetRaw)) : null;
            if ($address && $b->city) $address .= ', ' . ucwords(strtolower($b->city));

            return [
                'id'              => (string) $b->id,
                'name'            => $b->name,
                'slug'            => $b->slug,
                'city'            => $b->city,
                'subarea'         => $b->subarea,
                'year_built'      => $b->yearbuilt ? (int) $b->yearbuilt : null,
                'units'           => $b->units_in_development ? (int) $b->units_in_development : null,
                'levels'          => $b->levels ? (int) $b->levels : null,
                'strata_no'       => $b->strata_no ?: null,
                'street_no'       => $b->street_no ?: null,
                'street_name'     => $b->street_name ?: null,
                'street_type'     => $b->street_type ?: null,
                'address'         => $address,
                'postal_code'     => $b->postalcode ? strtoupper(trim($b->postalcode)) : null,
                'status'          => $b->status_sync ? ucwords(strtolower(trim($b->status_sync))) : null,
                'title_to_land'   => $b->title_to_land ? ucwords(strtolower(trim($b->title_to_land))) : null,
                'photo_url'       => $photoUrl,
                'min_price'       => $min,
                'max_price'       => $max,
                'active_listings' => $activeCount,
                'construction'    => $b->construction ? ucwords(strtolower(trim($b->construction))) : null,
            ];
            })->sortByDesc('active_listings')->values()->all();
        });

        $sliced = array_slice($mapped, ($page - 1) * $limit, $limit);
        return response()->json($sliced);
    }

    /**
     * Single building detail by slug.
     */
    public function buildingDetail(string $slug, string $buildingSlug): JsonResponse
    {
        $building = Buildings::withoutGlobalScopes()
            ->where('slug', $buildingSlug)
            ->where(function ($q) {
                $q->whereNull('board')->orWhere('board', '!=', 'Victoria Real Estate Board');
            })
            ->first();
        if (! $building) return response()->json(['error' => 'Building not found'], 404);

        // Pre-construction buildings (no strata_no, or yearbuilt in future) must not
        // query MLS by strata_no — it would pull wrong cross-province listings.
        $isPreConstruction = empty($building->strata_no)
            || trim($building->strata_no) === ''
            || (int) ($building->yearbuilt ?? 0) > (int) date('Y');

        // ── Photos ───────────────────────────────────────────────────────
        $photoUrl = null;
        try { $photoUrl = $building->main_image() ?: null; } catch (\Throwable $e) {}
        if ($photoUrl && !str_starts_with($photoUrl, 'https://')) { $photoUrl = null; }

        $photos = [];
        try {
            $buildingPhotos = $building->photos()->limit(12)->get();
            foreach ($buildingPhotos as $p) {
                if (!empty($p->image_name)) {
                    $photos[] = 'https://media.pixilinkserver.com/upload/house/images/' . $p->image_name;
                }
            }
        } catch (\Throwable $e) {}
        if (empty($photos) && $photoUrl) {
            $photos[] = $photoUrl;
        }
        $photos = array_values(array_filter($photos, fn($u) => str_starts_with($u, 'https://')));

        // ── Active listings (skip if pre-construction — prevents cross-province junk)
        $listingFormatter = function ($l) {
            $listDate = (($l->list_date ?? null) && $l->list_date !== '0000-00-00') ? (string) $l->list_date : null;
            $soldDate = (($l->sold_date ?? null) && $l->sold_date !== '0000-00-00') ? (string) $l->sold_date : null;
            // DOM is computed from list_date (the stored `dom` column is stale/0 in this DB).
            // Active: list_date → today. Sold: list_date → sold_date. Fallback to stored dom.
            $dom = null;
            if ($listDate) {
                $end = $soldDate ? strtotime($soldDate) : now()->timestamp;
                $dom = (int) floor(($end - strtotime($listDate)) / 86400);
                if ($dom < 0) { $dom = null; }
            } elseif (isset($l->dom) && $l->dom) {
                $dom = (int) $l->dom;
            }
            return [
                'id'         => $l->sysid,
                'mls_no'     => $l->listingid,
                'address'    => $l->streetaddress,
                'status'     => $l->status,
                'list_price' => (int) ($l->listprice_2 ?: $l->listprice),
                'sold_price' => (($l->soldprice_2 ?? 0) > 0) ? (int) $l->soldprice_2 : null,
                'beds'       => (int) $l->bedrooms,
                'baths'      => (float) $l->bathstotal,
                'sqft'       => (int) str_replace(",", "", (string) ($l->livingarea_2 ?: $l->livingarea ?: "0")),
                'photo_url'  => $l->mainpicurl ?: $l->thumbnailurl ?: null,
                'type'       => $l->type,
                'style'      => $l->home_style,
                'slug'       => $l->slug,
                'dom'        => $dom,
                'list_date'  => $listDate,
                'sold_date'  => $soldDate,
                'tax_amount' => (($l->taxamount ?? 0) > 0) ? (float) $l->taxamount : null,
                'listed_by'  => $l->reoffice ?: null,
                'year_built' => ($l->yearbuilt ?? null) ? (int) $l->yearbuilt : null,
                'strata_fee' => (($l->maintenance ?? 0) > 0) ? (float) $l->maintenance : null,
                'latitude'   => (isset($l->lat) && $l->lat != 0) ? (float) $l->lat : null,
                'longitude'  => (isset($l->lng) && $l->lng != 0) ? (float) $l->lng : null,
            ];
        };

        if ($isPreConstruction) {
            $activeListings = collect([]);
            $recentSold     = collect([]);
        } else {
            $activeListings = Listings::withoutGlobalScopes()
                ->where('status', 'Active')
                ->where('strata_no', $building->strata_no)
                ->select([
                    'sysid', 'listingid', 'streetaddress', 'status', 'listprice', 'listprice_2',
                    'bedrooms', 'bathstotal', 'livingarea', 'livingarea_2', 'mainpicurl', 'slug',
                    'type', 'home_style', 'dom', 'lat', 'lng', 'yearbuilt', 'maintenance',
                    'list_date', 'taxamount', 'reoffice',
                ])
                ->orderBy('listprice_2')
                ->limit(40)
                ->get()
                ->map($listingFormatter);

            // ── Recent sold listings (12 months) ─────────────────────────────
            $recentSold = Listings::withoutGlobalScopes()
                ->where('status', 'Sold')
                ->where('strata_no', $building->strata_no)
                ->whereNotNull('soldprice_2')
                ->where('soldprice_2', '>', 0)
                ->where('sold_date', '>=', now()->subMonths(12)->format('Y-m-d'))
                ->select([
                    'sysid', 'listingid', 'streetaddress', 'status', 'listprice', 'listprice_2',
                    'soldprice_2', 'bedrooms', 'bathstotal', 'livingarea', 'livingarea_2',
                    'mainpicurl', 'slug', 'type', 'home_style', 'sold_date',
                    'dom', 'lat', 'lng', 'yearbuilt', 'maintenance',
                    'list_date', 'taxamount', 'reoffice',
                ])
                ->orderByDesc('sold_date')
                ->limit(20)
                ->get()
                ->map($listingFormatter);
        }

        // ── Parse amenities and maintenance fee includes ──────────────────
        $parseList = static function (?string $raw): array {
            if (!$raw) return [];
            return array_values(array_filter(array_map('trim', preg_split('/[;,]/', $raw))));
        };
        $amenities             = $parseList($building->amenities ?? '');
        $maintenanceFeeIncludes = $parseList($building->maint_fees_inc ?? '');

        // ── Building stats (sold in last 12 months, same strata) ─────────
        $buildingStats = null;
        try {
            if (! $isPreConstruction) {
                $stats = Listings::withoutGlobalScopes()
                    ->where('status', 'Sold')
                    ->where('strata_no', $building->strata_no)
                    ->whereNotNull('soldprice_2')
                    ->where('soldprice_2', '>', 0)
                    ->where('sold_date', '>=', now()->subMonths(12)->format('Y-m-d'))
                    ->selectRaw('
                        COUNT(*) as sold_count,
                        ROUND(AVG(soldprice_2)) as avg_sold_price,
                        ROUND(MAX(soldprice_2)) as expensive_sold,
                        ROUND(AVG(DATEDIFF(sold_date, list_date))) as avg_dom,
                        ROUND(AVG(soldprice_2 / NULLIF(livingarea, 0))) as avg_per_sqft
                    ')
                    ->first();
                if ($stats && $stats->sold_count > 0) {
                    $sold6m = (int) Listings::withoutGlobalScopes()
                        ->where('status', 'Sold')
                        ->where('strata_no', $building->strata_no)
                        ->whereNotNull('soldprice_2')
                        ->where('soldprice_2', '>', 0)
                        ->where('sold_date', '>=', now()->subMonths(6)->format('Y-m-d'))
                        ->count();
                    $buildingStats = [
                        'sold_count'     => (int) $stats->sold_count,
                        'sold_count_6m'  => $sold6m,
                        'avg_sold_price' => $stats->avg_sold_price ? (int) $stats->avg_sold_price : null,
                        'expensive_sold' => $stats->expensive_sold ? (int) $stats->expensive_sold : null,
                        'avg_dom'        => $stats->avg_dom ? (int) $stats->avg_dom : null,
                        'avg_per_sqft'   => $stats->avg_per_sqft ? (int) $stats->avg_per_sqft : null,
                    ];
                }
            }
        } catch (\Throwable $e) {}

        // ── Sibling buildings (same strata_no) ───────────────────────────
        $siblingBuildings = [];
        try {
            if (! $isPreConstruction && $building->strata_no) {
                $siblings = Buildings::withoutGlobalScopes()
                    ->whereNotNull('strata_no')
                    ->where('strata_no', '!=', '')
                    ->where('strata_no', $building->strata_no)
                    ->where('id', '!=', $building->id)
                    ->select(['id', 'name', 'slug', 'city', 'subarea', 'yearbuilt', 'units_in_development',
                              'street_no', 'street_name', 'street_type', 'strata_no'])
                    ->limit(8)
                    ->get();
                // Count active listings per sibling in one query
                $sibStrata = $siblings->pluck('strata_no')->filter()->unique()->values()->all();
                $sibActiveMap = [];
                if (count($sibStrata)) {
                    $rows = Listings::withoutGlobalScopes()
                        ->where('status', 'Active')
                        ->whereIn('strata_no', $sibStrata)
                        ->selectRaw('strata_no, COUNT(*) as cnt')
                        ->groupBy('strata_no')
                        ->get();
                    foreach ($rows as $r) $sibActiveMap[$r->strata_no] = (int) $r->cnt;
                }
                $siblingBuildings = $siblings->map(fn ($b) => [
                    'id'                    => (string) $b->id,
                    'name'                  => $b->name,
                    'slug'                  => $b->slug,
                    'address'               => trim(($b->street_no ?? '') . ' ' . ($b->street_name ?? '') . ' ' . ($b->street_type ?? '')),
                    'year_built'            => $b->yearbuilt ? (int) $b->yearbuilt : null,
                    'units'                 => $b->units_in_development ? (int) $b->units_in_development : null,
                    'active_listings_count' => $sibActiveMap[$b->strata_no] ?? 0,
                ])->values()->toArray();
            }
        } catch (\Throwable $e) {}

        // ── Nearby buildings (same subarea or city) ──────────────────────
        $nearbyBuildings = [];
        try {
            $nbQuery = Buildings::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('id', '!=', $building->id)
                ->where(function ($q) use ($building) {
                    if ($building->subarea) {
                        $q->where('subarea', $building->subarea);
                    } else {
                        $q->where('city', $building->city);
                    }
                })
                ->whereNotNull('slug')->where('slug', '!=', '')
                ->whereNotNull('strata_no')->where('strata_no', '!=', '')
                ->select(['id', 'name', 'slug', 'street_no', 'street_name', 'street_type',
                          'city', 'subarea', 'yearbuilt', 'levels', 'strata_no', 'units_in_development'])
                ->orderByDesc('yearbuilt')
                ->limit(12)
                ->get();

            $nbStrata = $nbQuery->pluck('strata_no')->filter()->unique()->values()->all();
            $nbActiveMap = [];
            if (count($nbStrata)) {
                $rows = Listings::withoutGlobalScopes()
                    ->where('status', 'Active')
                    ->whereIn('strata_no', $nbStrata)
                    ->selectRaw('strata_no, COUNT(*) as cnt')
                    ->groupBy('strata_no')
                    ->get();
                foreach ($rows as $r) $nbActiveMap[$r->strata_no] = (int) $r->cnt;
            }

            $nearbyBuildings = $nbQuery->map(fn ($nb) => [
                'id'                    => (string) $nb->id,
                'name'                  => $nb->name,
                'slug'                  => $nb->slug,
                'address'               => trim(($nb->street_no ?? '') . ' ' . ($nb->street_name ?? '') . ' ' . ($nb->street_type ?? '')),
                'year_built'            => $nb->yearbuilt ? (int) $nb->yearbuilt : null,
                'levels'                => $nb->levels ? (int) $nb->levels : null,
                'active_listings_count' => $nbActiveMap[$nb->strata_no] ?? 0,
            ])->values()->toArray();
        } catch (\Throwable $e) {}

        // ── BCN cache extras (walk scores, developer, suite sizes) ────────
        $walkScore    = null;
        $transitScore = null;
        $bikeScore    = null;
        $developer    = null;
        $suiteSizes   = null;
        try {
            $bcnExtra     = $building->bcnInfoCached;
            $walkRaw      = data_get($bcnExtra, 'api_data.data.building.walk_score');
            $transitRaw   = data_get($bcnExtra, 'api_data.data.building.transit_score');
            $bikeRaw      = data_get($bcnExtra, 'api_data.data.building.bike_score');
            if ($walkRaw !== null)    $walkScore    = (int) $walkRaw;
            if ($transitRaw !== null) $transitScore = (int) $transitRaw;
            if ($bikeRaw !== null)    $bikeScore    = (int) $bikeRaw;
            $developer  = data_get($bcnExtra, 'api_data.data.building.developer') ?: null;
            $suiteSizes = data_get($bcnExtra, 'api_data.data.building.suite_sizes') ?: null;
        } catch (\Throwable $e) {}

        // Agent sold count (last 24 months, same strata)
        $agentSoldCount = 0;
        try {
            if (! $isPreConstruction && $building->strata_no) {
                $agentForMls = Agent::where('slug', $slug)->first();
                if ($agentForMls) {
                    $mlsIds = DB::table('agent_mls_ids')
                        ->where('agent_id', $agentForMls->id)
                        ->pluck('mls_id')
                        ->toArray();
                    if (! empty($mlsIds)) {
                        $agentSoldCount = (int) Listings::withoutGlobalScopes()
                            ->where('status', 'Sold')
                            ->where('strata_no', $building->strata_no)
                            ->whereIn('agent_id', $mlsIds)
                            ->where('sold_date', '>=', now()->subMonths(24)->format('Y-m-d'))
                            ->count();
                    }
                }
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'id'                       => (string) $building->id,
            'name'                     => $building->name,
            'slug'                     => $building->slug,
            'city'                     => $building->city,
            'subarea'                  => $building->subarea,
            'year_built'               => $building->yearbuilt ? (int) $building->yearbuilt : null,
            'units'                    => $building->units_in_development ? (int) $building->units_in_development : null,
            'units_in_strata'          => $building->units_in_strata ? (int) $building->units_in_strata : null,
            'strata_no'                => $building->strata_no,
            'complex_name'             => $building->complex ?: null,
            'photo_url'                => $photoUrl,
            'photos'                   => $photos,
            'description'              => (function () use ($building) {
                try {
                    if (!empty($building->description) && strlen(trim($building->description)) > 10) return trim($building->description);
                } catch (\Throwable $e) {}
                try {
                    $bcn = $building->bcnInfoCached;
                    $desc = data_get($bcn, 'api_data.data.building.building_description');
                    if ($desc && strlen(trim($desc)) > 10) return trim($desc);
                } catch (\Throwable $e) {}
                return null;
            })(),
            'tagline'                  => !empty($building->tagline) ? trim($building->tagline) : null,
            'neighbourhood_context'    => !empty($building->neighbourhood_context) ? trim($building->neighbourhood_context) : null,
            'meta_description'         => !empty($building->meta_description) ? trim($building->meta_description) : null,
            'faq_json'                 => !empty($building->faq_json) ? $building->faq_json : null,
            'amenities'                => $amenities,
            'features'                 => (function () use ($building) {
                try {
                    $bcn = $building->bcnInfoCached;
                    $raw = data_get($bcn, 'api_data.data.building.features');
                    if (is_array($raw)) return array_values(array_filter(array_map('strval', $raw)));
                } catch (\Throwable $e) {}
                return [];
            })(),
            'features_data'            => (function () use ($building) {
                // Priority 1: AI-generated structured features
                if (!empty($building->ai_features_json)) {
                    try {
                        $parsed = json_decode($building->ai_features_json, true);
                        if (is_array($parsed) && isset($parsed['type'])) {
                            return $parsed;
                        }
                    } catch (\Throwable $e) {}
                }
                // Priority 2: BCN raw features with HTML-structure detection
                try {
                    $bcn = $building->bcnInfoCached;
                    $raw = data_get($bcn, 'api_data.data.building.features');
                    if (!is_array($raw) || empty($raw)) return null;
                    $items = array_values(array_filter(array_map(fn($s) => trim(strval($s)), $raw)));
                    if (empty($items)) return null;
                    $hasSections = !empty(array_filter($items, fn($i) => stripos($i, '<strong>') !== false));
                    if ($hasSections) {
                        $sections = [];
                        $currentTitle = null;
                        $currentItems = [];
                        foreach ($items as $item) {
                            if (preg_match('/<strong>(.*?)<\/strong>/is', $item, $m)) {
                                if ($currentTitle !== null && !empty($currentItems)) {
                                    $sections[] = ['title' => $currentTitle, 'items' => $currentItems];
                                }
                                $currentTitle = html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                $currentItems = [];
                                $rest = preg_replace('/<strong>.*?<\/strong>/is', '', $item);
                                $lines = preg_split('/<p[^>]*>|<\/p>|<br[^>]*>/i', $rest);
                                foreach ($lines as $line) {
                                    $clean = trim(html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                                    if ($clean !== '') $currentItems[] = $clean;
                                }
                            } else {
                                $clean = trim(html_entity_decode(strip_tags($item), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                                if ($clean !== '') $currentItems[] = $clean;
                            }
                        }
                        if ($currentTitle !== null && !empty($currentItems)) {
                            $sections[] = ['title' => $currentTitle, 'items' => $currentItems];
                        }
                        if (!empty($sections)) {
                            return ['type' => 'sections', 'sections' => $sections];
                        }
                    }
                    $cleanTags = array_values(array_filter(array_map(
                        fn($i) => trim(html_entity_decode(strip_tags($i), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                        $items
                    )));
                    if (!empty($cleanTags)) {
                        return ['type' => 'tags', 'items' => $cleanTags];
                    }
                } catch (\Throwable $e) {}
                return null;
            })(),
            'maintenance_fee_includes' => $maintenanceFeeIncludes,
            'bylaw_restrictions'       => $building->bylaw_restrictions ?: null,
            'no_pets'                  => (bool) ($building->no_pets ?? false),
            'dogs_allowed'             => (bool) ($building->dogs ?? false),
            'cats_allowed'             => (bool) ($building->cats ?? false),
            'levels'                   => isset($building->levels) && $building->levels ? (int) $building->levels : null,
            'construction'             => $building->construction ?: null,
            'mgmt_name'                => $building->mgmt_name ?: null,
            'latitude'                 => (isset($building->latitude) && $building->latitude != 0) ? (float) $building->latitude : null,
            'longitude'                => (isset($building->longitude) && $building->longitude != 0) ? (float) $building->longitude : null,
            'address'                  => trim(($building->street_no ?? '') . ' ' . ($building->street_name ?? '') . ' ' . ($building->street_type ?? '') . ', ' . ($building->city ?? '')),
            'stats'                    => $buildingStats,
            'faqs'                     => (function () use ($building, $buildingStats, $activeListings) {
                $faqs = [];
                $name = $building->name;
                if ($building->units) {
                    $faqs[] = ['q' => "How many units are in {$name}?",
                               'a' => "{$name} has {$building->units} residential unit" . ($building->units !== 1 ? 's' : '') .
                                     ($building->yearbuilt ? " built in {$building->yearbuilt}" : '') . '.'];
                }
                if ($building->yearbuilt) {
                    $faqs[] = ['q' => "When was {$name} built?",
                               'a' => "{$name} was built in {$building->yearbuilt}" .
                                     ($building->construction ? ". It is a " . strtolower($building->construction) . " construction building." : '.')];
                }
                if ($buildingStats && ($buildingStats['avg_sold_price'] ?? null)) {
                    $avg = '$' . number_format($buildingStats['avg_sold_price']);
                    $faqs[] = ['q' => "What is the average sold price at {$name}?",
                               'a' => "Recent units at {$name} have sold for an average of {$avg}" .
                                     ($buildingStats['avg_dom'] ? ", typically in {$buildingStats['avg_dom']} days on market" : '') . '.'];
                }
                if ($building->mgmt_name) {
                    $faqs[] = ['q' => "Who manages {$name}?",
                               'a' => "{$building->mgmt_name} manages the strata corporation at {$name}."];
                }
                $activeCount = count($activeListings);
                if ($activeCount > 0) {
                    $faqs[] = ['q' => "Are there any units for sale at {$name}?",
                               'a' => "Yes, there " . ($activeCount === 1 ? 'is' : 'are') . " currently {$activeCount} active listing" .
                                     ($activeCount !== 1 ? 's' : '') . " at {$name}."];
                } else {
                    $faqs[] = ['q' => "Are there any units for sale at {$name}?",
                               'a' => "There are no active listings at {$name} right now. Contact us to be notified when a unit comes to market."];
                }
                if (!empty($building->bylaw_restrictions)) {
                    $faqs[] = ['q' => "What are the rental restrictions at {$name}?",
                               'a' => $building->bylaw_restrictions];
                }
                if (!empty($building->strata_no)) {
                    $faqs[] = ['q' => "What is the strata plan number for {$name}?",
                               'a' => "The strata plan number for {$name} is {$building->strata_no}."];
                }
                return $faqs;
            })(),
            'active_listings'          => $activeListings,
            'recent_sold'              => $recentSold,
            'sibling_buildings'        => $siblingBuildings,
            'nearby_buildings'         => $nearbyBuildings,
            'walk_score'               => $walkScore,
            'transit_score'            => $transitScore,
            'bike_score'               => $bikeScore,
            'developer'                => $developer,
            'suite_sizes'              => $suiteSizes,
            'agent_sold_count'         => $agentSoldCount,
        ]);
    }

    /**
     * Market stats for agent's territory.
     */
    public function marketStats(string $slug): JsonResponse
    {
        $agent = Agent::with(['territories'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        if (empty($cities)) return response()->json([
            'active_count' => 0, 'avg_list_price' => null,
            'sold_last_30_days' => 0, 'avg_sold_price' => null, 'avg_dom' => null,
        ]);

        $msSettingsRow = \Illuminate\Support\Facades\DB::table('agent_settings')
            ->where('agent_id', $agent->id)->first();
        $msWhitelist = ($msSettingsRow && $msSettingsRow->subarea_whitelist)
            ? json_decode($msSettingsRow->subarea_whitelist, true) : null;

        try {
            $activeStats = Listings::withoutGlobalScopes()
                ->whereIn('city', $cities)
                ->when(!empty($msWhitelist), fn ($q) => $q->whereIn('subarea', $msWhitelist))
                ->where(fn ($q) => $q->whereNotIn('type', ['Land', 'Mobile'])->orWhereNull('type'))
                ->where('status', 'Active')
                ->selectRaw('COUNT(*) as active_count, AVG(listprice) as avg_list_price')
                ->first();
        } catch (\Exception $e) {
            $activeStats = null;
        }

        try {
            $soldStats = Listings::withoutGlobalScopes()
                ->whereIn('city', $cities)
                ->when(!empty($msWhitelist), fn ($q) => $q->whereIn('subarea', $msWhitelist))
                ->where(fn ($q) => $q->whereNotIn('type', ['Land', 'Mobile'])->orWhereNull('type'))
                ->where('status', 'Sold')
                ->where('sold_date', '>=', now()->subDays(30)->format('Y-m-d'))
                ->selectRaw('COUNT(*) as sold_count, AVG(soldprice) as avg_sold_price')
                ->first();
        } catch (\Exception $e) {
            $soldStats = null;
        }

        return response()->json([
            'active_count'      => (int) ($activeStats?->active_count ?? 0),
            'avg_list_price'    => $activeStats?->avg_list_price ? (int) round($activeStats->avg_list_price) : null,
            'sold_last_30_days' => (int) ($soldStats?->sold_count ?? 0),
            'avg_sold_price'    => $soldStats?->avg_sold_price ? (int) round($soldStats->avg_sold_price) : null,
            'avg_dom'           => null,
        ]);
    }

    /**
     * Agent testimonials (from agent_testimonials table).
     */
    public function testimonials(string $slug): JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $rows = AgentTestimonial::where('agent_id', $agent->id)
            ->where('visible', true)
            ->orderByDesc('date')
            ->limit(8)
            ->get();

        return response()->json($rows->map(fn ($t) => [
            'id'     => $t->id,
            'name'   => $t->author_name,
            'text'   => $t->body,
            'rating' => (int) $t->rating,
            'source' => $t->source ?? 'Google',
            'date'   => $t->date,
        ]));
    }

    /**
     * Accept a contact / lead form submission.
     * Single ingestion point for all form types: w1, w2, w3, w4, contact, ask, market_subscribe.
     * Normalises field variants, saves to agent_leads (with notes), sends a rich notification email.
     */
    public function contact(string $slug, Request $req): JsonResponse
    {
        // Rate-limit: 5 submissions per 10 minutes per IP
        $rateLimitKey = 'contact:' . $req->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json(
                ['error' => 'Too many requests. Please try again in ' . ceil($seconds / 60) . ' minute(s).'],
                429
            );
        }
        RateLimiter::hit($rateLimitKey, 600); // 600-second window

        $agent = Agent::with(['settings'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $data = $req->validate([
            'name'             => 'nullable|string|max:120',
            'first_name'       => 'nullable|string|max:60',
            'last_name'        => 'nullable|string|max:60',
            'phone'            => 'nullable|string|max:30',
            'email'            => 'nullable|email|max:120',
            'message'          => 'nullable|string|max:2000',
            'listing_address'  => 'nullable|string|max:300',
            'property_address' => 'nullable|string|max:300',
            'form_type'        => 'nullable|string|max:40',
            'source_url'       => 'nullable|string|max:500',
            'notes'            => 'nullable|string|max:5000',
            'agree'            => 'nullable|boolean',
        ]);

        // Normalise property address — accept either field name.
        $propertyAddress = $data['property_address'] ?? $data['listing_address'] ?? null;

        // Split name into first/last for admin display.
        $nameParts = explode(' ', trim($data['name'] ?? ''), 2);
        $firstName = !empty($data['first_name']) ? $data['first_name'] : ($nameParts[0] ?: null);
        $lastName  = !empty($data['last_name'])  ? $data['last_name']  : ($nameParts[1] ?? null);

        // Persist lead. Use the actual form_type so each submission is categorised correctly.
        // Append listing/property address to message when provided.
        $leadMessage = $data['message'] ?? null;
        if ($propertyAddress) {
            $leadMessage = ($leadMessage ? $leadMessage . "\n" : '') . 'Property: ' . $propertyAddress;
        }

        \Illuminate\Support\Facades\DB::table('agent_leads')->insert([
            'agent_id'   => $agent->id,
            'form_type'  => $data['form_type'] ?? 'contact',
            'name'       => $data['name'] ?? '',
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $data['email'] ?? null,
            'phone'      => $data['phone'] ?? null,
            'message'    => $leadMessage,
            'source_url' => $data['source_url'] ?? null,
            'ip_hash'    => hash('sha256', $req->ip() ?? ''),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send notification email.
        $notifyEmail = $agent->settings?->notification_email ?: $agent->email;
        if ($notifyEmail) {
            try {
                $formType  = $data['form_type'] ?? 'contact';
                $typeLabel = match($formType) {
                    'w1'               => 'W1 Showing',
                    'w2'               => 'W2 Home Eval',
                    'w3'               => 'W3 Pre-qual',
                    'w4'               => 'W4 Quick Contact',
                    'contact'          => 'Contact Form',
                    'ask'              => 'Market Ask',
                    'market_subscribe' => 'Market Subscribe',
                    default            => ucfirst($formType),
                };
                $siteDomain  = $agent->settings?->custom_domain ?? ($slug . '.pixilink.com');
                $nameDisplay = $data['name'] ?? '(no name)';
                $subjectName = $data['name'] ?? ($data['phone'] ?? 'Unknown');

                $notesBlock = '';
                if (!empty($data['notes'])) {
                    $parsed = json_decode($data['notes'], true);
                    if (is_array($parsed)) {
                        $notesBlock = "\nForm Details:\n";
                        foreach ($parsed as $k => $v) {
                            if ($v !== '' && $v !== null) {
                                $notesBlock .= '  ' . ucwords(str_replace('_', ' ', $k)) . ": {$v}\n";
                            }
                        }
                    }
                }

                $body = "New lead [{$typeLabel}] from {$siteDomain}\n"
                    . str_repeat('-', 44) . "\n"
                    . "Name:     {$nameDisplay}\n"
                    . "Phone:    " . ($data['phone'] ?? '—') . "\n"
                    . "Email:    " . ($data['email'] ?? '—') . "\n"
                    . "Property: " . ($propertyAddress ?? '—') . "\n"
                    . "Message:  " . ($data['message'] ?? '—') . "\n"
                    . "Source:   " . ($data['source_url'] ?? '—') . "\n"
                    . $notesBlock
                    . str_repeat('-', 44) . "\n"
                    . "View leads: https://website.pixilink.com/admin/agents/{$agent->id}/leads\n";

                \Illuminate\Support\Facades\Mail::raw(
                    $body,
                    fn ($m) => $m->to($notifyEmail)->subject("[{$typeLabel}] New Lead — {$subjectName}")
                );
            } catch (\Throwable $mailErr) {
                \Illuminate\Support\Facades\Log::warning('Contact mail failed', ['err' => $mailErr->getMessage()]);
            }
        }

        // CRM push -- fire after email, failures never block the response.
        $pipelineData = [
            'name'             => $data['name'] ?? '',
            'first_name'       => $firstName ?? null,
            'last_name'        => $lastName  ?? null,
            'email'            => $data['email']   ?? null,
            'phone'            => $data['phone']   ?? null,
            'form_type'        => $data['form_type'] ?? 'contact',
            'message'          => $data['message'] ?? null,
            'property_address' => $propertyAddress ?? null,
            'source_url'       => $data['source_url'] ?? null,
        ];
        LeadPipeline::pushToFollowUpBoss($agent, $pipelineData);
        LeadPipeline::pushToGoHighLevel($agent, $pipelineData);

        return response()->json(['success' => true]);
    }


    /**
     * Neighbourhood summary list — subareas with active listings in agent's territory cities.
     */
    public function neighbourhoods(string $slug): JsonResponse
    {
        $agent = Agent::with(['territories', 'settings'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        if (empty($cities)) return response()->json([]);

        // If the agent has a subarea_whitelist configured, use it to restrict results to
        // only the specific MLS subarea values the agent actually covers.
        $subareaWhitelist = null;
        if ($agent->settings) {
            $raw = $agent->settings->subarea_whitelist;
            if ($raw) {
                $decoded = is_array($raw) ? $raw : json_decode($raw, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $subareaWhitelist = $decoded;
                }
            }
        }

        $q = Listings::withoutGlobalScopes()
            ->where('status', 'Active')
            ->whereIn('city', $cities)
            ->whereNotNull('subarea')
            ->where('subarea', '!=', '');

        if ($subareaWhitelist) {
            $q->whereIn('subarea', $subareaWhitelist);
        }

        $results = $q->selectRaw('subarea, MAX(city) as city, COUNT(*) as active_count')
            ->groupBy('subarea')
            ->orderByDesc('active_count')
            ->get();

        // Sold stats for the last 30 days, grouped by subarea
        $subareas = $results->pluck('subarea')->filter()->unique()->values()->toArray();
        $soldStats = [];
        if (!empty($subareas)) {
            $rows = Listings::withoutGlobalScopes()
                ->where('status', 'Sold')
                ->whereIn('subarea', $subareas)
                ->where('sold_date', '>=', now()->subDays(30)->format('Y-m-d'))
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->selectRaw('subarea, COUNT(*) as sold_count, AVG(soldprice_2) as avg_sold_price, AVG(DATEDIFF(sold_date, list_date)) as avg_dom, AVG(soldprice_2/NULLIF(livingarea,0)) as avg_per_sqft')
                ->groupBy('subarea')
                ->get();
            foreach ($rows as $row) {
                $soldStats[$row->subarea] = $row;
            }
        }

        $descriptions = self::neighbourhoodDescriptions();

        // Override with AI-generated description if available in agent_ai_pages
        $aiDescription = null;
        try {
            $aiDescription = \Illuminate\Support\Facades\DB::connection('mysql_mlsr')
                ->table('agent_ai_pages')
                ->where('agent_id', $agent->id)
                ->where('page_type', 'neighbourhood_description')
                ->where('subarea', $primarySubarea)
                ->orderByDesc('generated_at')
                ->value('content');
        } catch (\Throwable $e) {
            // AI description lookup failed silently -- fall back to static
        }

        // Reverse map: DB subarea string → canonical URL slug
        // This ensures subareas like "Ocean Park Surrey" resolve to "ocean-park"
        // (matching the $slugMap keys used in neighbourhoodDetail / landing pages).
        $reverseSlugMap = [
            'South Surrey White Rock' => 'south-surrey-white-rock',
            'South Surrey'            => 'south-surrey-white-rock',
            'White Rock'              => 'white-rock',
            'Cloverdale BC'           => 'cloverdale',
            'Cloverdale'              => 'cloverdale',
            'Morgan Creek'            => 'morgan-creek',
            'Grandview Surrey'        => 'grandview-surrey',
            'Grandview Heights'       => 'grandview-heights',
            'Ocean Park Surrey'       => 'ocean-park',
            'Ocean Park'              => 'ocean-park',
            'Semiahmoo'               => 'semiahmoo',
            'Fleetwood Tynehead'      => 'fleetwood',
            'Fleetwood'               => 'fleetwood',
            'King George Corridor'    => 'king-george-corridor',
            'Pacific Douglas'         => 'pacific-douglas',
            'Crescent Bch Ocean Pk.'  => 'crescent-bch-ocean-pk',
            'Sunnyside Park Surrey'   => 'sunnyside-park-surrey',
            'Elgin Chantrell'         => 'elgin-chantrell',
            'Hazelmere'               => 'hazelmere',
            'Whalley'                 => 'whalley',
            'East Newton'             => 'east-newton',
            'Fraser Heights'          => 'fraser-heights',
        ];

        return response()->json($results->map(function ($r) use ($soldStats, $descriptions, $reverseSlugMap) {
            $sold = $soldStats[$r->subarea] ?? null;
            $activeCount = (int) $r->active_count;
            $sold30d     = $sold ? (int) $sold->sold_count : 0;
            $avgSold     = $sold && $sold->avg_sold_price > 0 ? (int) round($sold->avg_sold_price) : 0;
            $avgDom      = $sold && $sold->avg_dom > 0 ? (int) round($sold->avg_dom) : 0;
            $avgPerSqft  = $sold && $sold->avg_per_sqft > 0 ? round((float)$sold->avg_per_sqft, 2) : null;
            $absorption  = $sold30d > 0 ? round($activeCount / $sold30d, 2) : 9.9;
            $marketType  = match (true) {
                $absorption <= 2.5  => 'strong-sellers',
                $absorption <= 5.0  => 'sellers',
                $absorption <= 8.33 => 'balanced',
                default          => 'buyers',
            };
            return [
                'name'            => $r->subarea,
                'city'            => $r->city,
                'subarea'         => $r->subarea,
                'slug'            => $reverseSlugMap[$r->subarea] ?? preg_replace('/[^a-z0-9]+/', '-', strtolower($r->subarea)),
                'active_count'    => $activeCount,
                'sold_30d'        => $sold30d,
                'avg_sold_price'  => $avgSold,
                'avg_dom'         => $avgDom,
                'avg_per_sqft'    => $avgPerSqft,
                'absorption_rate' => (float) $absorption,
                'market_type'     => $marketType,
                'description'     => $descriptions[$r->subarea] ?? null,
            ];
        }));
    }

    /**
     * Neighbourhood detail — market widget, monthly trend, active + sold listings, description.
     */
    public function neighbourhoodDetail(string $slug, string $subareaSlug): JsonResponse
    {
        $agent = Agent::with(['territories', 'settings'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        // Slug → possible DB subarea name(s)
        $slugMap = [
            'south-surrey-white-rock' => ['South Surrey White Rock', 'South Surrey'],
            'white-rock'              => ['White Rock'],
            'cloverdale'              => ['Cloverdale BC', 'Cloverdale'],
            'morgan-creek'            => ['Morgan Creek'],
            'grandview'               => ['Grandview Surrey', 'Grandview Heights'],
            'grandview-surrey'        => ['Grandview Surrey'],
            'grandview-heights'       => ['Grandview Heights'],
            'ocean-park'              => ['Ocean Park Surrey', 'Ocean Park'],
            'semiahmoo'               => ['Semiahmoo'],
            'fleetwood'               => ['Fleetwood Tynehead', 'Fleetwood'],
            'king-george-corridor'    => ['King George Corridor'],
            'pacific-douglas'         => ['Pacific Douglas'],
            'crescent-bch-ocean-pk'   => ['Crescent Bch Ocean Pk.'],
                'crescent-beach'          => ['Crescent Bch Ocean Pk.'],
            'sunnyside-park-surrey'   => ['Sunnyside Park Surrey'],
            'elgin-chantrell'         => ['Elgin Chantrell'],
            'hazelmere'               => ['Hazelmere'],
            'whalley'                 => ['Whalley'],
            'east-newton'             => ['East Newton'],
            'fraser-heights'          => ['Fraser Heights'],
            // ── Tri-Cities city-level slugs ──────────────────────────────────
            'coquitlam'                       => ['Burke Mountain', 'Canyon Springs', 'Cape Horn', 'Central Coquitlam', 'Chineside', 'Coquitlam East', 'Coquitlam West', 'Eagle Ridge CQ', 'Harbour Chines', 'Harbour Place', 'Hockaday', 'Maillardville', 'Meadow Brook', 'New Horizons', 'North Coquitlam', 'Park Ridge Estates', 'Ranch Park', 'River Springs', 'Scott Creek', 'Summitt View', 'Upper Eagle Ridge', 'Westwood Plateau'],
            'port-moody'                      => ['Anmore', 'Barber Street', 'Belcarra', 'College Park PM', 'Glenayre', 'Heritage Mountain', 'Heritage Woods PM', 'Mountain Meadows', 'North Shore Pt Moody', 'Port Moody Centre'],
            'port-coquitlam'                  => ['Birchland Manor', 'Central Pt Coquitlam', 'Citadel PQ', 'Glenwood PQ', 'Lincoln Park PQ', 'Lower Mary Hill', 'Mary Hill', 'Oxford Heights', 'Riverwood', 'Woodland Acres PQ'],
        ];

        $querySubareas = $slugMap[$subareaSlug] ?? [];
        if (empty($querySubareas)) {
            // Reverse the slug: dashes → spaces, title-case
            $querySubareas = [ucwords(str_replace('-', ' ', $subareaSlug))];
        }

        // Authorise: if agent has a subarea_whitelist, restrict to it.
        if ($agent->settings) {
            $raw = $agent->settings->subarea_whitelist;
            if ($raw) {
                $wl = is_array($raw) ? $raw : json_decode($raw, true);
                if (is_array($wl) && count($wl) > 0) {
                    $querySubareas = array_values(array_intersect($querySubareas, $wl));
                }
            }
        }
        if (empty($querySubareas)) {
            return response()->json(['error' => 'Neighbourhood not found'], 404);
        }
        $primarySubarea = $querySubareas[0];

        // Determine display name + city from a live listing
        $sample = Listings::withoutGlobalScopes()
            ->whereIn('subarea', $querySubareas)
            ->select(['city', 'subarea'])
            ->first();

        // Hardcoded fallback: city + display name by slug,
        // so subareas with zero MLS rows still render a page.
        $cityNameMap = [
            'south-surrey-white-rock' => ['Surrey',     'South Surrey White Rock'],
            'white-rock'              => ['White Rock', 'White Rock'],
            'cloverdale'              => ['Surrey',     'Cloverdale'],
            'morgan-creek'            => ['Surrey',     'Morgan Creek'],
            'grandview'               => ['Surrey',     'Grandview Surrey'],
            'grandview-surrey'        => ['Surrey',     'Grandview Surrey'],
            'grandview-heights'       => ['Surrey',     'Grandview Heights'],
            'ocean-park'              => ['Surrey',     'Ocean Park Surrey'],
            'semiahmoo'               => ['Surrey',     'Semiahmoo'],
            'fleetwood'               => ['Surrey',     'Fleetwood Tynehead'],
            'king-george-corridor'    => ['Surrey',     'King George Corridor'],
            'pacific-douglas'         => ['Surrey',     'Pacific Douglas'],
            'crescent-bch-ocean-pk'   => ['Surrey',     'Crescent Bch Ocean Pk.'],
            'crescent-beach'          => ['Surrey',     'Crescent Bch Ocean Pk.'],
            'sunnyside-park-surrey'   => ['Surrey',     'Sunnyside Park Surrey'],
            'elgin-chantrell'         => ['Surrey',     'Elgin Chantrell'],
            'hazelmere'               => ['Surrey',     'Hazelmere'],
            'whalley'                 => ['Surrey',     'Whalley'],
            'east-newton'             => ['Surrey',     'East Newton'],
            'fraser-heights'          => ['Surrey',     'Fraser Heights'],
            'coquitlam'               => ['Coquitlam',    'Coquitlam'],
            'port-moody'              => ['Port Moody',   'Port Moody'],
            'port-coquitlam'          => ['Port Coquitlam', 'Port Coquitlam'],
            // ── Tri-Cities city-level slugs ──────────────────────────────────
            'coquitlam'                       => ['Coquitlam',     'Coquitlam'],
            'port-moody'                      => ['Port Moody',    'Port Moody'],
            'port-coquitlam'                  => ['Port Coquitlam','Port Coquitlam'],
        ];

        if (isset($cityNameMap[$subareaSlug])) {
            // City-level slug: always use the canonical name, never a sub-area from a live listing
            [$displayCity, $displayName] = $cityNameMap[$subareaSlug];
        } elseif ($sample) {
            $displayCity = $sample->city;
            $displayName = $sample->subarea;
        } else {
            $displayCity = 'Surrey';
            $displayName = $primarySubarea;
        }

        // ── Widget (last 30 days) ──────────────────────────────────────────
        $activeStats = Listings::withoutGlobalScopes()
            ->whereIn('subarea', $querySubareas)
            ->where('status', 'Active')
            ->selectRaw('COUNT(*) as active_count, AVG(listprice_2) as avg_list_price')
            ->first();

        $soldStats = Listings::withoutGlobalScopes()
            ->whereIn('subarea', $querySubareas)
            ->where('status', 'Sold')
            ->where('sold_date', '>=', now()->subDays(30)->format('Y-m-d'))
            ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
            ->selectRaw('COUNT(*) as sold_count, AVG(soldprice_2) as avg_sold_price, AVG(DATEDIFF(sold_date, list_date)) as avg_dom')
            ->first();

        $activeCount = (int) ($activeStats?->active_count ?? 0);
        $sold30d     = (int) ($soldStats?->sold_count ?? 0);
        $avgSold     = $soldStats?->avg_sold_price ? (int) round($soldStats->avg_sold_price) : 0;
        $avgDom      = $soldStats?->avg_dom ? (int) round($soldStats->avg_dom) : 0;
        $avgList     = $activeStats?->avg_list_price ? (int) round($activeStats->avg_list_price) : 0;
        $absorption  = $sold30d > 0 ? round($activeCount / $sold30d, 2) : 9.9;
        $marketType  = match (true) {
            $absorption <= 2.5  => 'strong-sellers',
            $absorption <= 5.0  => 'sellers',
            $absorption <= 8.33 => 'balanced',
            default          => 'buyers',
        };

        $widget = [
            'subarea'         => $displayName,
            'city'            => $displayCity,
            'active'          => $activeCount,
            'sold_30d'        => $sold30d,
            'avg_sold_price'  => $avgSold,
            'avg_list_price'  => $avgList,
            'avg_dom'         => $avgDom,
            'absorption_rate' => (float) $absorption,
            'market_type'     => $marketType,
        ];

        // ── Monthly trend (last 12 months) ────────────────────────────────
        $trend = Listings::withoutGlobalScopes()
            ->whereIn('subarea', $querySubareas)
            ->where('status', 'Sold')
            ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
            ->whereNotNull('sold_date')
            ->where('sold_date', '>=', now()->subMonths(12)->format('Y-m-d'))
            ->selectRaw("DATE_FORMAT(sold_date,'%Y-%m') as month, COUNT(*) as sold, AVG(soldprice_2) as avg_price, AVG(DATEDIFF(sold_date,list_date)) as avg_dom, AVG(soldprice_2/NULLIF(livingarea,0)) as avg_ppsf")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // ── Active listings (up to 12) ─────────────────────────────────────
        $activeListings = Listings::withoutGlobalScopes()
            ->whereIn('subarea', $querySubareas)
            ->where('status', 'Active')
            ->select(['sysid','listingid','streetaddress','city','subarea','status','listprice_2','bedrooms','bathstotal','livingarea','livingarea_2','mainpicurl','thumbnailurl','slug','type','home_style'])
            ->whereRaw("(NULLIF(mainpicurl, '') IS NOT NULL OR NULLIF(thumbnailurl, '') IS NOT NULL)")
            ->orderByDesc('sysid')
            ->limit(12)
            ->get()
            ->map(fn ($l) => [
                'id'         => $l->sysid,
                'mls_no'     => $l->listingid,
                'address'    => $l->streetaddress,
                'city'       => $l->city,
                'subarea'    => $l->subarea,
                'status'     => $l->status,
                'list_price' => (int) $l->listprice_2,
                'sold_price' => null,
                'beds'       => (int) $l->bedrooms,
                'baths'      => (float) $l->bathstotal,
                'sqft'       => (int) str_replace(",", "", (string) ($l->livingarea_2 ?: $l->livingarea ?: "0")),
                'photo_url'  => $l->mainpicurl ?: $l->thumbnailurl ?: null,
                'type'       => $l->type,
                'style'      => $l->home_style,
                'slug'       => $l->slug,
                'dom'        => null,
            ]);

        // ── Recent sold (up to 20) ─────────────────────────────────────────
        $recentSold = Listings::withoutGlobalScopes()
            ->whereIn('subarea', $querySubareas)
            ->where('status', 'Sold')
            ->whereNotNull('sold_date')
            ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
            ->select(['sysid','listingid','streetaddress','city','subarea','status','listprice_2','soldprice_2','bedrooms','bathstotal','livingarea','livingarea_2','mainpicurl','thumbnailurl','slug','type','home_style','sold_date'])
            ->whereRaw("(NULLIF(mainpicurl, '') IS NOT NULL OR NULLIF(thumbnailurl, '') IS NOT NULL)")
            ->orderByDesc('sold_date')
            ->limit(20)
            ->get()
            ->map(fn ($l) => [
                'id'         => $l->sysid,
                'mls_no'     => $l->listingid,
                'address'    => $l->streetaddress,
                'city'       => $l->city,
                'subarea'    => $l->subarea,
                'status'     => $l->status,
                'list_price' => (int) $l->listprice_2,
                'sold_price' => $l->soldprice_2 ? (int) $l->soldprice_2 : null,
                'beds'       => (int) $l->bedrooms,
                'baths'      => (float) $l->bathstotal,
                'sqft'       => (int) str_replace(",", "", (string) ($l->livingarea_2 ?: $l->livingarea ?: "0")),
                'photo_url'  => $l->mainpicurl ?: $l->thumbnailurl ?: null,
                'type'       => $l->type,
                'style'      => $l->home_style,
                'slug'       => $l->slug,
                'dom'        => null,
                'sold_date'  => $l->sold_date,
            ]);

        $descriptions = self::neighbourhoodDescriptions();

        // ── Per-type breakdown (Apartment / Townhouse / House) ─────────────
        $typeGroups = [
            ['label' => 'Apartment', 'types' => ['Apartment', 'Apartment/Condo']],
            ['label' => 'Townhouse', 'types' => ['Townhouse', 'Townhouse/Multi-Family', 'Row House (Non-Strata)']],
            ['label' => 'House',     'types' => ['House', 'Detached', 'House/Single Family', 'Single Family Detached']],
        ];

        $listingMapper = fn ($l) => [
            'id'         => $l->sysid,
            'mls_no'     => $l->listingid,
            'address'    => $l->streetaddress,
            'city'       => $l->city,
            'subarea'    => $l->subarea,
            'status'     => $l->status,
            'list_price' => (int) $l->listprice_2,
            'sold_price' => isset($l->soldprice_2) && $l->soldprice_2 > 0 ? (int) $l->soldprice_2 : null,
            'beds'       => (int) $l->bedrooms,
            'baths'      => (float) $l->bathstotal,
            'sqft'       => (int) str_replace(",", "", (string) ($l->livingarea_2 ?: $l->livingarea ?: "0")),
            'photo_url'  => $l->mainpicurl ?: $l->thumbnailurl ?: null,
            'type'       => $l->type,
            'style'      => $l->home_style,
            'slug'       => $l->slug,
            'dom'        => null,
            'sold_date'  => $l->sold_date ?? null,
        ];

        $byType = [];
        foreach ($typeGroups as $group) {
            $typeActiveStats = Listings::withoutGlobalScopes()
                ->whereIn('subarea', $querySubareas)
                ->whereIn('type', $group['types'])
                ->where('status', 'Active')
                ->selectRaw('COUNT(*) as active_count, AVG(listprice_2) as avg_list_price')
                ->first();

            $typeSoldStats = Listings::withoutGlobalScopes()
                ->whereIn('subarea', $querySubareas)
                ->whereIn('type', $group['types'])
                ->where('status', 'Sold')
                ->where('sold_date', '>=', now()->subDays(30)->format('Y-m-d'))
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->selectRaw('COUNT(*) as sold_count, AVG(soldprice_2) as avg_sold_price, AVG(DATEDIFF(sold_date, list_date)) as avg_dom')
                ->first();

            $tActive = (int) ($typeActiveStats?->active_count ?? 0);
            $tSold   = (int) ($typeSoldStats?->sold_count ?? 0);
            if ($tActive === 0 && $tSold === 0) continue;

            $tAvgSold   = $typeSoldStats?->avg_sold_price > 0 ? (int) round($typeSoldStats->avg_sold_price) : 0;
            $tAvgDom    = $typeSoldStats?->avg_dom > 0 ? (int) round($typeSoldStats->avg_dom) : 0;
            $tAvgList   = $typeActiveStats?->avg_list_price > 0 ? (int) round($typeActiveStats->avg_list_price) : 0;
            $tAbsorb    = $tSold > 0 ? round($tActive / $tSold, 2) : 9.9;
            $tMarket    = match (true) {
                $tAbsorb <= 2.5  => 'strong-sellers',
                $tAbsorb <= 5.0  => 'sellers',
                $tAbsorb <= 8.33 => 'balanced',
                default       => 'buyers',
            };

            $tActiveListings = Listings::withoutGlobalScopes()
                ->whereIn('subarea', $querySubareas)
                ->whereIn('type', $group['types'])
                ->where('status', 'Active')
                ->select(['sysid','listingid','streetaddress','city','subarea','status','listprice_2','bedrooms','bathstotal','livingarea','livingarea_2','mainpicurl','thumbnailurl','slug','type','home_style'])
                ->whereRaw("(NULLIF(mainpicurl, '') IS NOT NULL OR NULLIF(thumbnailurl, '') IS NOT NULL)")
                ->orderByDesc('sysid')
                ->limit(4)
                ->get()
                ->map(fn ($l) => ($listingMapper)($l));

            $tSoldListings = Listings::withoutGlobalScopes()
                ->whereIn('subarea', $querySubareas)
                ->whereIn('type', $group['types'])
                ->where('status', 'Sold')
                ->whereNotNull('sold_date')
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->select(['sysid','listingid','streetaddress','city','subarea','status','listprice_2','soldprice_2','bedrooms','bathstotal','livingarea','livingarea_2','mainpicurl','thumbnailurl','slug','type','home_style','sold_date'])
                ->whereRaw("(NULLIF(mainpicurl, '') IS NOT NULL OR NULLIF(thumbnailurl, '') IS NOT NULL)")
                ->orderByDesc('sold_date')
                ->limit(4)
                ->get()
                ->map(fn ($l) => ($listingMapper)($l));

            $byType[] = [
                'type'   => $group['label'],
                'widget' => [
                    'active'          => $tActive,
                    'sold_30d'        => $tSold,
                    'avg_sold_price'  => $tAvgSold,
                    'avg_list_price'  => $tAvgList,
                    'avg_dom'         => $tAvgDom,
                    'absorption_rate' => (float) $tAbsorb,
                    'market_type'     => $tMarket,
                ],
                'active'      => $tActiveListings->values(),
                'recent_sold' => $tSoldListings->values(),
            ];
        }


        // ── Neighbourhood Pulse (90-day analysis) ─────────────────────────
        $pulse = null;
        try {
            $pulseTypeGroups = [
                ['label' => 'House',     'types' => ['House', 'Detached', 'House/Single Family', 'Single Family Detached']],
                ['label' => 'Townhouse', 'types' => ['Townhouse', 'Townhouse/Multi-Family', 'Row House (Non-Strata)']],
                ['label' => 'Apartment', 'types' => ['Apartment', 'Apartment/Condo']],
            ];
            $pulseByType = [];
            foreach ($pulseTypeGroups as $pg) {
                $ps = Listings::withoutGlobalScopes()
                    ->whereIn('subarea', $querySubareas)
                    ->whereIn('type', $pg['types'])
                    ->where('status', 'Sold')
                    ->where('sold_date', '>=', now()->subDays(90)->format('Y-m-d'))
                    ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                    ->selectRaw('COUNT(*) as cnt, AVG(soldprice_2) as avg_price, AVG(DATEDIFF(sold_date,list_date)) as avg_dom, AVG(soldprice_2/NULLIF(livingarea_2,0)) as avg_ppsf')
                    ->first();
                $cnt = (int) ($ps?->cnt ?? 0);
                if ($cnt === 0) continue;
                $pulseByType[] = [
                    'type'               => $pg['label'],
                    'count_90d'          => $cnt,
                    'avg_sold_price_90d' => $ps->avg_price   ? (int) round($ps->avg_price)   : 0,
                    'avg_dom_90d'        => $ps->avg_dom     ? (int) round($ps->avg_dom)      : 0,
                    'avg_ppsf_90d'       => $ps->avg_ppsf    ? round($ps->avg_ppsf, 2)        : null,
                ];
            }

            // Housing age buckets from 90-day solds
            $ageRaw = Listings::withoutGlobalScopes()
                ->whereIn('subarea', $querySubareas)
                ->where('status', 'Sold')
                ->where('sold_date', '>=', now()->subDays(90)->format('Y-m-d'))
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->whereNotNull('yearbuilt')->where('yearbuilt', '>', 0)
                ->selectRaw('SUM(yearbuilt >= 2015) as new_cnt, SUM(yearbuilt BETWEEN 2000 AND 2014) as mid_cnt, SUM(yearbuilt < 2000) as est_cnt, COUNT(*) as total')
                ->first();
            $ageTotal  = max(1, (int) ($ageRaw?->total ?? 0));
            $ageBuckets = [
                'new'         => (int) ($ageRaw?->new_cnt ?? 0),
                'mid'         => (int) ($ageRaw?->mid_cnt ?? 0),
                'established' => (int) ($ageRaw?->est_cnt ?? 0),
                'new_pct'         => $ageTotal > 0 ? round(100 * (int)($ageRaw?->new_cnt ?? 0) / $ageTotal) : 0,
                'mid_pct'         => $ageTotal > 0 ? round(100 * (int)($ageRaw?->mid_cnt ?? 0) / $ageTotal) : 0,
                'established_pct' => $ageTotal > 0 ? round(100 * (int)($ageRaw?->est_cnt ?? 0) / $ageTotal) : 0,
            ];

            // Activity score (1-10)
            $score = 5;
            if ($absorption <= 2.0)       $score += 3;
            elseif ($absorption <= 4.0)   $score += 2;
            elseif ($absorption <= 6.0)   $score += 1;
            elseif ($absorption >= 10.0)  $score -= 2;
            elseif ($absorption >= 8.0)   $score -= 1;
            if ($avgDom > 60)             $score -= 2;
            elseif ($avgDom > 30)         $score -= 1;
            elseif ($avgDom <= 14)        $score += 1;
            if ($sold30d > 10)            $score += 1;
            elseif ($sold30d <= 2)        $score -= 1;
            $score = max(1, min(10, $score));
            $actLabel = match (true) {
                $score >= 8 => 'High Demand',
                $score >= 6 => 'Active',
                $score >= 4 => 'Moderate',
                default     => 'Quiet',
            };

            $pulse = [
                'activity_score' => $score,
                'activity_label' => $actLabel,
                'by_type'        => $pulseByType,
                'age_buckets'    => $ageBuckets,
            ];
        } catch (\Throwable $pulseErr) {
            \Log::warning('neighbourhoodDetail pulse failed: ' . $pulseErr->getMessage());
        }

        return response()->json([
            'name'          => $displayName,
            'city'          => $displayCity,
            'subarea'       => $displayName,
            'description'   => $aiDescription ?? $descriptions[$displayName] ?? null,
            'widget'        => $widget,
            'pulse'         => $pulse,
            'by_type'       => $byType,
            'monthly_trend' => $trend->map(fn ($p) => [
                'month'     => $p->month,
                'sold'      => (int) $p->sold,
                'active'    => $activeByMonth[$p->month] ?? null,
                'avg_price' => (int) round($p->avg_price),
                'avg_dom'   => (int) round($p->avg_dom),
                'avg_ppsf'  => $p->avg_ppsf ? round($p->avg_ppsf, 2) : null,
            ])->values(),
            'active'        => $activeListings->values(),
            'recent_sold'   => $recentSold->values(),
        ]);
    }

    /**
     * Neighbourhood sold listings (paginated, up to 50).
     */
    public function neighbourhoodSold(string $slug, string $subareaSlug): JsonResponse
    {
        $agent = Agent::with(['territories', 'settings'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $slugMap = [
            'south-surrey-white-rock' => ['South Surrey White Rock', 'South Surrey'],
            'white-rock' => ['White Rock'], 'cloverdale' => ['Cloverdale BC','Cloverdale'],
            'morgan-creek' => ['Morgan Creek'], 'grandview' => ['Grandview Surrey','Grandview Heights'],
            'ocean-park' => ['Ocean Park Surrey','Ocean Park'], 'semiahmoo' => ['Semiahmoo'],
            'crescent-bch-ocean-pk' => ['Crescent Bch Ocean Pk.'],
                'crescent-beach'        => ['Crescent Bch Ocean Pk.'],
            'sunnyside-park-surrey' => ['Sunnyside Park Surrey'],
            'elgin-chantrell' => ['Elgin Chantrell'],
            'hazelmere' => ['Hazelmere'],
        ];
        $querySubareas = $slugMap[$subareaSlug] ?? [ucwords(str_replace('-', ' ', $subareaSlug))];

        // Authorise: restrict to agent's subarea whitelist when set.
        if ($agent->settings) {
            $raw = $agent->settings->subarea_whitelist;
            if ($raw) {
                $wl = is_array($raw) ? $raw : json_decode($raw, true);
                if (is_array($wl) && count($wl) > 0) {
                    $querySubareas = array_values(array_intersect($querySubareas, $wl));
                }
            }
        }
        if (empty($querySubareas)) return response()->json([]);

        $rows = Listings::withoutGlobalScopes()
            ->whereIn('subarea', $querySubareas)
            ->where('status', 'Sold')
            ->whereNotNull('sold_date')
            ->select(['sysid','listingid','streetaddress','city','subarea','status','listprice_2','soldprice_2','bedrooms','bathstotal','livingarea','livingarea_2','mainpicurl','thumbnailurl','slug','type','sold_date'])
            ->whereRaw("(NULLIF(mainpicurl, '') IS NOT NULL OR NULLIF(thumbnailurl, '') IS NOT NULL)")
            ->orderByDesc('sold_date')
            ->limit(50)
            ->get();

        return response()->json($rows->map(fn ($l) => [
            'id' => $l->sysid, 'mls_no' => $l->listingid, 'address' => $l->streetaddress,
            'city' => $l->city, 'subarea' => $l->subarea, 'status' => $l->status,
            'list_price' => (int) $l->listprice_2, 'sold_price' => $l->soldprice_2 ? (int) $l->soldprice_2 : null,
            'beds' => (int) $l->bedrooms, 'baths' => (float) $l->bathstotal, 'sqft' => (int) str_replace(",", "", (string) ($l->livingarea_2 ?: $l->livingarea ?: "0")),
            'photo_url' => $l->mainpicurl ?: $l->thumbnailurl ?: null, 'type' => $l->type, 'style' => null,
            'slug' => $l->slug, 'dom' => null, 'sold_date' => $l->sold_date,
        ]));
    }

    /**
     * Neighbourhood monthly reports (12-month trend for archive pages).
     */
    public function neighbourhoodReports(string $slug, string $subareaSlug): JsonResponse
    {
        $agent = Agent::with(['territories', 'settings'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $slugMap = [
            'south-surrey-white-rock' => ['South Surrey White Rock', 'South Surrey'],
            'white-rock' => ['White Rock'], 'cloverdale' => ['Cloverdale BC','Cloverdale'],
            'morgan-creek' => ['Morgan Creek'], 'grandview' => ['Grandview Surrey','Grandview Heights'],
            'ocean-park' => ['Ocean Park Surrey','Ocean Park'], 'semiahmoo' => ['Semiahmoo'],
        ];
        $querySubareas = $slugMap[$subareaSlug] ?? [ucwords(str_replace('-', ' ', $subareaSlug))];

        // Authorise: restrict to agent's subarea whitelist when set.
        if ($agent->settings) {
            $raw = $agent->settings->subarea_whitelist;
            if ($raw) {
                $wl = is_array($raw) ? $raw : json_decode($raw, true);
                if (is_array($wl) && count($wl) > 0) {
                    $querySubareas = array_values(array_intersect($querySubareas, $wl));
                }
            }
        }
        if (empty($querySubareas)) return response()->json([]);

        $trend = Listings::withoutGlobalScopes()
            ->whereIn('subarea', $querySubareas)
            ->where('status', 'Sold')
            ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
            ->whereNotNull('sold_date')
            ->where('sold_date', '>=', now()->subMonths(36)->format('Y-m-d'))
            ->selectRaw("DATE_FORMAT(sold_date,'%Y-%m') as month, COUNT(*) as sold, AVG(soldprice_2) as avg_price, AVG(DATEDIFF(sold_date,list_date)) as avg_dom, AVG(soldprice_2/NULLIF(livingarea,0)) as avg_ppsf")
            ->groupBy('month')->orderBy('month')->get();

        // ── Active-count per trend month (months-of-inventory badge) ──────
        $activeByMonth = [];
        if ($trend->isNotEmpty()) {
            $months = $trend->pluck('month')->toArray();
            $cases = implode(', ', array_map(fn ($m) =>
                "SUM(CASE WHEN list_date IS NOT NULL AND list_date <= LAST_DAY(CONCAT('{$m}','-01'))"
                . " AND (sold_date IS NULL OR sold_date > LAST_DAY(CONCAT('{$m}','-01'))) THEN 1 ELSE 0 END) AS `m_{$m}`",
                $months
            ));
            try {
                $activeRow = Listings::withoutGlobalScopes()
                    ->whereIn('subarea', $querySubareas)
                    ->selectRaw($cases)->first();
                if ($activeRow) {
                    foreach ($months as $m) {
                        $activeByMonth[$m] = (int) ($activeRow->{"m_{$m}"} ?? 0);
                    }
                }
            } catch (\Throwable $e) {}
        }

        return response()->json($trend->map(fn ($p) => [
            'month' => $p->month, 'sold' => (int) $p->sold,
            'active' => isset($activeByMonth[$p->month]) ? (int) $activeByMonth[$p->month] : null,
            'avg_price' => (int) round($p->avg_price), 'avg_dom' => (int) round($p->avg_dom),
            'avg_ppsf' => $p->avg_ppsf ? round($p->avg_ppsf, 2) : null,
        ])->values());
    }

    /**
     * Market report — overall + by_type + 12-month trend; optional ?subarea= filter.
     */
    public function marketReport(string $slug, Request $req): JsonResponse
    {
        $agent = Agent::with(['territories', 'settings'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities  = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        $subarea = $req->query('subarea');

        if (empty($cities)) {
            return response()->json(['overall' => ['active'=>0,'sold_30d'=>0,'avg_sold_price'=>0,'avg_dom'=>0,'absorption_rate'=>9.9,'market_type'=>'balanced'], 'by_type'=>[], 'monthly_trend'=>[]]);
        }

        // Derive whitelist unconditionally so it can gate both the default and explicit-subarea paths.
        $subareaWhitelist = null;
        if ($agent->settings) {
            $raw = $agent->settings->subarea_whitelist;
            if ($raw) {
                $decoded = is_array($raw) ? $raw : json_decode($raw, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $subareaWhitelist = $decoded;
                }
            }
        }
        // If an explicit subarea filter is given, validate it against the whitelist.
        if ($subarea && $subareaWhitelist && ! in_array($subarea, $subareaWhitelist)) {
            $subarea = null; // silently drop invalid subarea; report falls back to whitelist scope
        }

        // Base query builder helper
        $base = fn () => Listings::withoutGlobalScopes()
            ->whereIn('city', $cities)
            ->where(fn ($q) => $q->whereNotIn('type', ['Land', 'Mobile'])->orWhereNull('type'))
            ->when($subarea, fn ($q) => $q->where('subarea', $subarea))
            ->when(! $subarea && $subareaWhitelist, fn ($q) => $q->whereIn('subarea', $subareaWhitelist));

        // ── Overall ───────────────────────────────────────────────────────
        $activeRow = $base()->where('status', 'Active')
            ->selectRaw('COUNT(*) as c')->first();
        $soldRow   = $base()->where('status', 'Sold')
            ->where('sold_date', '>=', now()->subDays(30)->format('Y-m-d'))
            ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
            ->selectRaw('COUNT(*) as c, AVG(soldprice_2) as avg_price, AVG(DATEDIFF(sold_date,list_date)) as avg_dom')
            ->first();

        $activeCount = (int) ($activeRow?->c ?? 0);
        $sold30d     = (int) ($soldRow?->c ?? 0);
        $avgSold     = $soldRow?->avg_price ? (int) round($soldRow->avg_price) : 0;
        $avgDom      = $soldRow?->avg_dom ? (int) round($soldRow->avg_dom) : 0;
        $absorption  = $sold30d > 0 ? round($activeCount / $sold30d, 2) : 9.9;
        $mtype = fn ($a) => match (true) {
            $a <= 2.5 => 'strong-sellers', $a <= 5.0 => 'sellers', $a <= 8.33 => 'balanced', default => 'buyers',
        };

        $overall = ['active'=>$activeCount,'sold_30d'=>$sold30d,'avg_sold_price'=>$avgSold,'avg_dom'=>$avgDom,'absorption_rate'=>(float)$absorption,'market_type'=>$mtype($absorption)];

        // ── By type (two grouped queries, keyed) ──────────────────────────
        $typeActive = $base()->where('status','Active')->whereNotNull('type')->where('type','!=','')
            ->selectRaw('type, COUNT(*) as c')->groupBy('type')->get()->keyBy('type');
        $typeSold   = $base()->where('status','Sold')
            ->where('sold_date','>=',now()->subDays(30)->format('Y-m-d'))
            ->whereNotNull('soldprice_2')->where('soldprice_2','>',0)
            ->whereNotNull('type')->where('type','!=','')
            ->selectRaw('type, COUNT(*) as c, AVG(soldprice_2) as avg_price, AVG(DATEDIFF(sold_date,list_date)) as avg_dom')
            ->groupBy('type')->get()->keyBy('type');

        $byType = $typeActive->map(function ($row) use ($typeSold, $mtype) {
            $s  = $typeSold->get($row->type);
            $a  = (int) $row->c;
            $sd = (int) ($s?->c ?? 0);
            $ab = $sd > 0 ? round($a / $sd, 2) : 9.9;
            return ['type'=>$row->type,'active'=>$a,'sold_30d'=>$sd,'avg_sold_price'=>$s?->avg_price?(int)round($s->avg_price):0,'avg_dom'=>$s?->avg_dom?(int)round($s->avg_dom):0,'absorption_rate'=>(float)$ab,'market_type'=>$mtype($ab)];
        })->values()->toArray();

        // ── Monthly trend (12 months) ─────────────────────────────────────
        $trend = $base()->where('status','Sold')
            ->whereNotNull('soldprice_2')->where('soldprice_2','>',0)
            ->whereNotNull('sold_date')
            ->where('sold_date','>=',now()->subMonths(36)->format('Y-m-d'))
            ->selectRaw("DATE_FORMAT(sold_date,'%Y-%m') as month, COUNT(*) as sold, AVG(soldprice_2) as avg_price, AVG(DATEDIFF(sold_date,list_date)) as avg_dom, AVG(soldprice_2/NULLIF(livingarea,0)) as avg_ppsf")
            ->groupBy('month')->orderBy('month')->get();

        // ── Per-month active inventory (expiration_date-aware) ────────────
        $activeByMonth = [];
        try {
            $allListings = $base()
                ->whereIn('status', ['Active', 'Sold', 'Expired', 'Terminated', 'Cancel Protected', 'Hold all Action'])
                ->whereNotNull('list_date')
                ->where('list_date', '!=', '0000-00-00')
                ->select(['list_date', 'sold_date', 'expiration_date'])
                ->get();
            foreach ($trend as $p) {
                $monthEnd = date('Y-m-t', strtotime($p->month . '-01'));
                $cnt = 0;
                foreach ($allListings as $l) {
                    if ($l->list_date > $monthEnd) continue;
                    if ($l->sold_date && $l->sold_date !== '0000-00-00' && $l->sold_date <= $monthEnd) continue;
                    if ($l->expiration_date && $l->expiration_date !== '0000-00-00' && $l->expiration_date <= $monthEnd) continue;
                    $cnt++;
                }
                $activeByMonth[$p->month] = $cnt;
            }
        } catch (\Throwable $e) {}

        // ── Monthly trend by type (36 months, avg sold price per type) ─────
        // Powers the per-type lines (Apartment / Townhouse / House) on the
        // "3-Year Price Trend" chart. Buckets mirror the by-type breakdown above.
        $typeBucketCase =
            "CASE "
            . "WHEN type IN ('Apartment','Apartment/Condo') THEN 'apartment' "
            . "WHEN type IN ('Townhouse','Townhouse/Multi-Family','Row House (Non-Strata)') THEN 'townhouse' "
            . "WHEN type IN ('House','Detached','House/Single Family','Single Family Detached') THEN 'house' "
            . "WHEN type IN ('Duplex','Half Duplex') THEN 'duplex' "
            . "ELSE NULL END";

        $byTypeTrendRows = $base()->where('status','Sold')
            ->whereNotNull('soldprice_2')->where('soldprice_2','>',0)
            ->whereNotNull('sold_date')
            ->where('sold_date','>=',now()->subMonths(36)->format('Y-m-d'))
            ->whereNotNull('type')->where('type','!=','')
            ->selectRaw("DATE_FORMAT(sold_date,'%Y-%m') as month, {$typeBucketCase} as bucket, AVG(soldprice_2) as avg_price, COUNT(*) as sold_count, AVG(DATEDIFF(sold_date,list_date)) as avg_dom, AVG(soldprice_2/NULLIF(livingarea,0)) as avg_ppsf")
            ->groupBy('month','bucket')->orderBy('month')->get();

        $byTypeMonths = [];
        foreach ($byTypeTrendRows as $r) {
            if (! $r->bucket) continue; // skip types outside the three canonical buckets
            if (! isset($byTypeMonths[$r->month])) {
                $byTypeMonths[$r->month] = ['month' => $r->month, 'apartment' => null, 'townhouse' => null, 'house' => null, 'duplex' => null, 'apartment_sold' => null, 'townhouse_sold' => null, 'house_sold' => null, 'duplex_sold' => null, 'apartment_dom' => null, 'townhouse_dom' => null, 'house_dom' => null, 'duplex_dom' => null, 'apartment_ppsf' => null, 'townhouse_ppsf' => null, 'house_ppsf' => null, 'duplex_ppsf' => null];
            }
            $byTypeMonths[$r->month][$r->bucket] = $r->avg_price ? (int) round($r->avg_price) : null;
            $byTypeMonths[$r->month][$r->bucket . '_sold'] = $r->sold_count ? (int) $r->sold_count : null;
            $byTypeMonths[$r->month][$r->bucket . '_dom'] = $r->avg_dom ? (int) round($r->avg_dom) : null;
            $byTypeMonths[$r->month][$r->bucket . '_ppsf'] = $r->avg_ppsf ? (int) round($r->avg_ppsf) : null;
        }
        ksort($byTypeMonths);
        $monthlyTrendByType = array_values($byTypeMonths);
        return response()->json([
            'overall'       => $overall,
            'by_type'       => $byType,
            'monthly_trend_by_type' => $monthlyTrendByType,
            'monthly_trend' => $trend->map(fn ($p) => [
                'month'     => $p->month,
                'sold'      => (int) $p->sold,
                'active'    => isset($activeByMonth[$p->month]) ? (int) $activeByMonth[$p->month] : null,
                'avg_price' => (int) round($p->avg_price),
                'avg_dom'   => (int) round($p->avg_dom),
                'avg_ppsf'  => $p->avg_ppsf ? round($p->avg_ppsf, 2) : null,
            ])->values(),
        ]);
    }


    /**
     * GET /api-internal/agent/{slug}/ai-pages?type=lifestyle_seo|school_catchment|amenities
     * Returns AI-generated pages for the agent, filtered by type.
     */
    public function aiPages(string $slug, \Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $type = $req->query('type');
        $query = \Illuminate\Support\Facades\DB::table('agent_ai_pages')
            ->where('agent_id', $agent->id);

        if ($type) $query->where('page_type', $type);

        $rows = $query->orderByDesc('generated_at')->get();

        return response()->json($rows->map(fn ($r) => [
            'id'               => (int) $r->id,
            'page_type'        => $r->page_type,
            'slug'             => $r->slug,
            'title'            => $r->title,
            'content'          => $r->content,
            'meta_description' => $r->meta_description,
            'subarea'          => $r->subarea ?? null,
            'generated_at'     => $r->generated_at,
        ])->values());
    }

    /**
     * GET /api-internal/agent/{slug}/ai-pages/{pageSlug}
     * Returns a single AI-generated page including full content.
     */
    public function aiPage(string $slug, string $pageSlug): \Illuminate\Http\JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $page = \Illuminate\Support\Facades\DB::table('agent_ai_pages')
            ->where('agent_id', $agent->id)
            ->where('slug', $pageSlug)
            ->first();

        if (! $page) return response()->json(['error' => 'Page not found'], 404);

        return response()->json([
            'id'               => (int) $page->id,
            'page_type'        => $page->page_type,
            'slug'             => $page->slug,
            'title'            => $page->title,
            'content'          => $page->content,
            'meta_description' => $page->meta_description,
            'subarea'          => $page->subarea ?? null,
            'generated_at'     => $page->generated_at,
        ]);
    }
    /**
     * Hardcoded neighbourhood descriptions keyed by canonical DB subarea name.
     */
    private static function neighbourhoodDescriptions(): array
    {
        return [
            'South Surrey White Rock' => 'South Surrey and White Rock form one of Greater Vancouver\'s most desirable communities, combining ocean views with quiet, tree-lined streets. The housing mix ranges from luxury detached homes in gated enclaves to modern townhouses and condominiums. Buyers are drawn by top-rated schools, the White Rock Promenade and pier, and easy access to the US border. The area offers a suburban feel with strong walkability near the beach and Morgan Crossing shopping.',
            'South Surrey'            => 'South Surrey offers a compelling mix of established neighbourhoods and master-planned communities spread across the southern end of Surrey. Families are drawn by large lots, top-ranking schools including Earl Marriott and Elgin Park Secondary, and direct access to Crescent Beach. The area\'s housing ranges from gated estate homes to newer townhouse developments, with shopping anchored by Morgan Crossing and Grandview Corners.',
            'White Rock'              => 'White Rock is a charming seaside city famous for its two-kilometre promenade, sandy beach, and iconic white rock landmark. The community skews slightly older and attracts buyers seeking a walkable, resort-like lifestyle close to the ocean. Housing ranges from heritage character homes to modern condominiums with ocean views. Marine Drive restaurants and boutiques add to the village atmosphere that makes White Rock genuinely unique in Metro Vancouver.',
            'Cloverdale BC'           => 'Cloverdale is a heritage-rich neighbourhood in southeast Surrey with a small-town feel and strong community spirit. Known for its annual rodeo and exhibition grounds, excellent schools, and more affordable detached home prices than the rest of South Surrey, the area attracts families seeking larger lots and a quieter pace. Quick highway access to the Fraser Valley makes Cloverdale a practical choice for commuters and young families alike.',
            'Morgan Creek'            => 'Morgan Creek is an upscale, master-planned community nestled around an 18-hole golf course in South Surrey. Known for its winding, landscaped streets and executive detached homes, it attracts buyers who value privacy, natural beauty, and proximity to top South Surrey amenities. Strata townhouses and luxury estates share the landscape with the golf course, and the nearby Pacific Academy makes Morgan Creek especially popular with families.',
            'Grandview Surrey'        => 'Grandview Heights is South Surrey\'s fastest-growing neighbourhood, built around Grandview Corners — a major retail hub anchored by big-box stores and boutique dining. The area offers modern detached homes and townhouses on generous lots, with Grandview Heights Secondary and numerous parks nearby. Families choose Grandview for new construction quality, walkable amenities, and the neighbourhood\'s energetic, community-focused character.',
            'Grandview Heights'       => 'Grandview Heights is South Surrey\'s fastest-growing neighbourhood, built around Grandview Corners — a major retail hub anchored by big-box stores and boutique dining. The area offers modern detached homes and townhouses on generous lots, with Grandview Heights Secondary and numerous parks nearby. Families choose Grandview for new construction quality, walkable amenities, and the neighbourhood\'s energetic, community-focused character.',
            'Ocean Park Surrey'       => 'Ocean Park is a quiet, established neighbourhood on South Surrey\'s west side, prized for its proximity to Crescent Beach, walking trails through Elgin Heritage Park, and a relaxed coastal atmosphere. Housing is predominantly detached, often on larger lots with mature trees. Buyers are typically drawn by the area\'s peaceful character, direct beach access, and tight-knit community spirit.',
            'Semiahmoo'               => 'Semiahmoo sits at the southern tip of South Surrey near the US border crossing, offering a mix of established and newer developments. The Semiahmoo Secondary catchment and proximity to White Rock Beach make it popular with families and retirees alike. Housing includes detached homes on full-sized lots as well as newer strata developments, with convenient access to Peace Arch Hospital and the Peace Arch border crossing.',
            'Coquitlam'               => 'Coquitlam is the largest city in the Tri-Cities and one of Metro Vancouver\'s fastest-growing communities, offering a diverse range of housing from high-rise condominiums in North Coquitlam\'s Town Centre to detached homes and townhouses in established neighbourhoods like Maillardville, Burke Mountain, and Canyon Springs. The Evergreen SkyTrain extension has transformed North Coquitlam into a transit-connected urban hub, while Coquitlam Centre mall and a growing downtown core provide excellent shopping and dining. Families are drawn by well-regarded schools, Mundy Park, and an extensive trail network, alongside a broad spectrum of price points that remain more accessible than Vancouver proper.',
            'Port Moody'              => 'Port Moody is a compact, arts-focused city at the eastern end of Burrard Inlet, renowned for its scenic waterfront, the Rocky Point Park pier, and a walkable craft-brewery district along St. Johns Street. The Evergreen SkyTrain line has positioned Port Moody as one of Metro Vancouver\'s most accessible waterfront communities, with Moody Centre and Inlet Centre stations providing fast connections to Coquitlam and downtown Vancouver. Housing ranges from established character homes in Port Moody Centre to newer townhouses in Moody Centre and the Heritage Woods hillside community. Buyers are drawn by the city\'s small-town feel, award-winning schools, and year-round access to Burrard Inlet recreation.',
            'Port Coquitlam'          => 'Port Coquitlam — known locally as PoCo — is a welcoming, family-oriented city in the eastern Tri-Cities, offering some of the most affordable single-family home prices in Metro Vancouver\'s inner suburbs. The city sits along the Pitt River and Coquitlam River confluence, giving residents easy access to the Traboulay PoCo Trail, a 25-kilometre loop encircling the city through parks and riverside greenways. West Coast Express commuter rail connects downtown Port Coquitlam to Vancouver in under 50 minutes, making PoCo a practical choice for commuters who want space and value. Newer townhouse developments along the Shaughnessy Street corridor have brought modern housing options to a market historically dominated by detached homes.',
        ];
    }



    /**
     * Admin: territory buildings for a specific agent (up to 300).
     * Protected by VerifyAdminSecret middleware (X-Admin-Secret header).
     */
    public function adminAgentBuildings(Request $req, int $agentId): JsonResponse
    {
        $agent = Agent::with(['territories'])->where('id', $agentId)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        if (empty($cities)) return response()->json(['buildings' => [], 'total' => 0, 'page' => 1, 'limit' => 300]);

        $limit = min(300, max(1, (int) $req->query('limit', 300)));
        $page  = max(1, (int) $req->query('page', 1));

        $abSettingsRow = \Illuminate\Support\Facades\DB::table('agent_settings')
            ->where('agent_id', $agent->id)->first();
        $abWhitelist = ($abSettingsRow && $abSettingsRow->subarea_whitelist)
            ? json_decode($abSettingsRow->subarea_whitelist, true) : null;

        $q = Buildings::whereIn('city', $cities)
            ->when(!empty($abWhitelist), function ($query) use ($abWhitelist) {
                $query->whereIn('subarea', $abWhitelist);
            })
            ->when($req->query('missing_only'), function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('tagline')->orWhere('tagline', '');
                })->where(function ($q) {
                    $q->whereNull('description')->orWhere('description', '');
                });
            })
            ->when($req->query('missing_features_only'), function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('ai_features_json')->orWhere('ai_features_json', '');
                });
            })
            ->select([
                'id', 'name', 'slug', 'city', 'subarea',
                'yearbuilt', 'units_in_development', 'strata_no', 'levels',
            ]);

        $tagFilter = array_filter((array) $req->query('tags', []));
        $hasAmenityTagsCol = !empty(\Illuminate\Support\Facades\DB::connection('mysql_mlsr')->select("SHOW COLUMNS FROM buildings LIKE 'amenity_tags'"));
        if (!empty($tagFilter) && $hasAmenityTagsCol) {
            foreach ($tagFilter as $tag) {
                $q->where('amenity_tags', 'LIKE', '%"' . addslashes($tag) . '"%');
            }
        }

        $total = (clone $q)->count();

        $buildings = $q->orderByDesc('yearbuilt')
            ->forPage($page, $limit)
            ->get();

        return response()->json([
            'buildings' => $buildings->map(function (Buildings $b) {
                $photoUrl = null;
                try { $photoUrl = $b->main_image() ?: null; } catch (\Throwable $e) {}
            if ($photoUrl && !str_starts_with($photoUrl, 'https://')) { $photoUrl = null; }

                $activeCount = 0;
                $min = null;
                $max = null;

                if ($b->strata_no && trim($b->strata_no) !== '' && (int) ($b->yearbuilt ?? 0) <= (int) date('Y')) {
                    try {
                        $agg = Listings::withoutGlobalScopes()
                            ->where('status', 'Active')
                            ->where('strata_no', $b->strata_no)
                            ->selectRaw('COUNT(*) as c, MIN(listprice_2) as mn, MAX(listprice_2) as mx')
                            ->first();
                        $activeCount = (int) ($agg->c ?? 0);
                        $min = ($agg->mn ?? null) ? (int) $agg->mn : null;
                        $max = ($agg->mx ?? null) ? (int) $agg->mx : null;
                    } catch (\Throwable $e) {}
                }

                return [
                    'id'              => (string) $b->id,
                    'name'            => $b->name,
                    'slug'            => $b->slug,
                    'city'            => $b->city,
                    'subarea'         => $b->subarea,
                    'year_built'      => $b->yearbuilt ? (int) $b->yearbuilt : null,
                    'units'           => $b->units_in_development ? (int) $b->units_in_development : null,
                    'levels'          => $b->levels ? (int) $b->levels : null,
                    'strata_no'       => $b->strata_no ?: null,
                    'photo_url'       => $photoUrl,
                    'min_price'       => $min,
                    'max_price'       => $max,
                    'active_listings' => $activeCount,
                    'amenity_tags'    => $b->amenity_tags ? json_decode($b->amenity_tags, true) : [],
                ];
            })->values(),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Market breakdown - by bedroom, bathroom, decade (age), lot size, levels.
     * GET /api-internal/agent/{slug}/market-breakdown?subarea=
     */
    public function marketBreakdown(string $slug, Request $req): JsonResponse
    {
        $agent = Agent::with(['territories', 'settings'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities  = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        $subarea = $req->query('subarea');

        if (empty($cities)) {
            return response()->json([
                'by_bedroom'  => [], 'by_bathroom' => [],
                'by_decade'   => [], 'by_lot_size' => [], 'by_levels'  => [],
            ]);
        }

        $subareaWhitelist = null;
        if ($agent->settings) {
            $raw = $agent->settings->subarea_whitelist;
            if ($raw) {
                $decoded = is_array($raw) ? $raw : json_decode($raw, true);
                if (is_array($decoded) && count($decoded) > 0) $subareaWhitelist = $decoded;
            }
        }
        if ($subarea && $subareaWhitelist && ! in_array($subarea, $subareaWhitelist)) {
            $subarea = null;
        }

        $base30 = fn () => Listings::withoutGlobalScopes()
            ->whereIn('city', $cities)
            ->where('status', 'Sold')
            ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
            ->where('sold_date', '>=', now()->subDays(30)->format('Y-m-d'))
            ->when($subarea, fn ($q) => $q->where('subarea', $subarea))
            ->when(! $subarea && $subareaWhitelist, fn ($q) => $q->whereIn('subarea', $subareaWhitelist));

        $base90 = fn () => Listings::withoutGlobalScopes()
            ->whereIn('city', $cities)
            ->where('status', 'Sold')
            ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
            ->where('sold_date', '>=', now()->subDays(90)->format('Y-m-d'))
            ->when($subarea, fn ($q) => $q->where('subarea', $subarea))
            ->when(! $subarea && $subareaWhitelist, fn ($q) => $q->whereIn('subarea', $subareaWhitelist));

        // By Bedroom
        $byBedroom = [];
        try {
            $rows = $base30()
                ->whereNotNull('bedrooms')->where('bedrooms', '>', 0)->where('bedrooms', '<=', 10)
                ->selectRaw('bedrooms as beds, COUNT(*) as sold_30d, ROUND(AVG(soldprice_2)) as avg_sold_price, ROUND(AVG(DATEDIFF(sold_date, list_date))) as avg_dom')
                ->groupBy('bedrooms')->orderBy('bedrooms')->get();
            foreach ($rows as $r) {
                $byBedroom[] = ['beds' => (int)$r->beds, 'avg_sold_price' => $r->avg_sold_price ? (int)$r->avg_sold_price : 0, 'sold_30d' => (int)$r->sold_30d, 'avg_dom' => $r->avg_dom ? (int)$r->avg_dom : 0];
            }
        } catch (\Throwable $e) {}

        // By Bathroom
        $byBathroom = [];
        try {
            $rows = $base30()
                ->whereNotNull('bathstotal')->where('bathstotal', '>', 0)->where('bathstotal', '<=', 8)
                ->selectRaw('ROUND(bathstotal) as baths, COUNT(*) as sold_30d, ROUND(AVG(soldprice_2)) as avg_sold_price, ROUND(AVG(DATEDIFF(sold_date, list_date))) as avg_dom')
                ->groupBy(DB::raw('ROUND(bathstotal)'))
                ->orderBy(DB::raw('ROUND(bathstotal)'))
                ->get();
            foreach ($rows as $r) {
                $byBathroom[] = ['baths' => (int)$r->baths, 'avg_sold_price' => $r->avg_sold_price ? (int)$r->avg_sold_price : 0, 'sold_30d' => (int)$r->sold_30d, 'avg_dom' => $r->avg_dom ? (int)$r->avg_dom : 0];
            }
        } catch (\Throwable $e) {}

        // By Decade
        $byDecade = [];
        try {
            $rows = $base30()
                ->whereNotNull('yearbuilt')->where('yearbuilt', '>', 1900)->where('yearbuilt', '<=', (int)date('Y') + 2)
                ->selectRaw("CASE WHEN yearbuilt < 1980 THEN 'Pre-1980' WHEN yearbuilt < 1990 THEN '1980s' WHEN yearbuilt < 2000 THEN '1990s' WHEN yearbuilt < 2010 THEN '2000s' WHEN yearbuilt < 2020 THEN '2010s' ELSE '2020+' END as decade, COUNT(*) as sold_30d, ROUND(AVG(soldprice_2)) as avg_sold_price, ROUND(AVG(DATEDIFF(sold_date, list_date))) as avg_dom")
                ->groupBy('decade')->get()->keyBy('decade');
            $order = ['Pre-1980', '1980s', '1990s', '2000s', '2010s', '2020+'];
            foreach ($order as $dec) {
                if (isset($rows[$dec])) {
                    $r = $rows[$dec];
                    $byDecade[] = ['decade' => $dec, 'avg_sold_price' => $r->avg_sold_price ? (int)$r->avg_sold_price : 0, 'sold_30d' => (int)$r->sold_30d, 'avg_dom' => $r->avg_dom ? (int)$r->avg_dom : 0];
                }
            }
        } catch (\Throwable $e) {}

        // By Lot Size (detached houses, last 90 days)
        $byLotSize = [];
        try {
            $houseTypes = ['House', 'Detached', 'House/Single Family', 'Single Family Detached'];
            $rows = $base30()
                ->whereIn('type', $houseTypes)
                ->whereNotNull('lotsize')->where('lotsize', '>', 0)
                ->selectRaw("CASE WHEN lotsize < 4000 THEN 'Under 4,000 sqft' WHEN lotsize < 6000 THEN '4,000-6,000 sqft' WHEN lotsize < 8000 THEN '6,000-8,000 sqft' ELSE '8,000+ sqft' END as band, COUNT(*) as sold_30d, ROUND(AVG(soldprice_2)) as avg_sold_price, ROUND(AVG(DATEDIFF(sold_date, list_date))) as avg_dom")
                ->groupBy('band')->get()->keyBy('band');
            $bands = ['Under 4,000 sqft', '4,000-6,000 sqft', '6,000-8,000 sqft', '8,000+ sqft'];
            foreach ($bands as $band) {
                if (isset($rows[$band])) {
                    $r = $rows[$band];
                    $byLotSize[] = ['band' => $band, 'avg_sold_price' => $r->avg_sold_price ? (int)$r->avg_sold_price : 0, 'sold_30d' => (int)$r->sold_30d, 'avg_dom' => $r->avg_dom ? (int)$r->avg_dom : 0];
                }
            }
        } catch (\Throwable $e) {}

        // By Levels / Storeys (detached houses, last 90 days)
        $byLevels = [];
        try {
            $houseTypes = ['House', 'Detached', 'House/Single Family', 'Single Family Detached'];
            $rows = $base30()
                ->whereIn('type', $houseTypes)
                ->whereNotNull('finished_levels')->where('finished_levels', '>', 0)->where('finished_levels', '<=', 4)
                ->selectRaw("CASE WHEN finished_levels = 1 THEN '1 Storey' WHEN finished_levels = 2 THEN '2 Storey' WHEN finished_levels = 3 THEN '3 Storey' ELSE '4+ Storey' END as levels, COUNT(*) as sold_30d, ROUND(AVG(soldprice_2)) as avg_sold_price, ROUND(AVG(DATEDIFF(sold_date, list_date))) as avg_dom")
                ->groupBy('levels')->get()->keyBy('levels');
            $levelOrder = ['1 Storey', '2 Storey', '3 Storey', '4+ Storey'];
            foreach ($levelOrder as $lv) {
                if (isset($rows[$lv])) {
                    $r = $rows[$lv];
                    $byLevels[] = ['levels' => $lv, 'avg_sold_price' => $r->avg_sold_price ? (int)$r->avg_sold_price : 0, 'sold_30d' => (int)$r->sold_30d, 'avg_dom' => $r->avg_dom ? (int)$r->avg_dom : 0];
                }
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'by_bedroom'  => $byBedroom,
            'by_bathroom' => $byBathroom,
            'by_decade'   => $byDecade,
            'by_lot_size' => $byLotSize,
            'by_levels'   => $byLevels,
        ]);
    }

    /**
     * Team members stored as JSON in agent_settings.team_members.
     */
    public function getTeam(string $slug): JsonResponse
    {
        $agent = Agent::with(['settings'])->where('slug', $slug)->where('status', 'active')->first();
        if (! $agent) return response()->json([]);

        $settings = $agent->settings;
        if (! $settings || ! $settings->team_members) return response()->json([]);

        $members = is_array($settings->team_members)
            ? $settings->team_members
            : json_decode($settings->team_members, true);
        if (! is_array($members)) return response()->json([]);

        return response()->json(array_values(array_map(function ($m, $i) {
            return [
                'id'        => $i + 1,
                'name'      => $m['name']    ?? '',
                'role'      => $m['title']   ?? 'Agent',
                'photo_url' => $m['photo']   ?? null,
                'bio'       => $m['bio']     ?? null,
                'phone'     => $m['phone']   ?? null,
                'email'     => $m['email']   ?? null,
                'license'   => $m['license'] ?? null,
            ];
        }, $members, array_keys($members))));
    }


    private function format(Agent $agent): array
    {
        $settings = $agent->settings;

        return [
            'id'               => $agent->id,
            'slug'             => $agent->slug,
            'name'             => $agent->name,
            'brokerage'        => $agent->brokerage,
            'phone'            => $agent->phone,
            'email'            => $agent->email,
            'bio'              => $agent->bio,
            'photo_path'       => $agent->photo_path,
            'headshot_path'    => $agent->headshot_path,
            'logo_path'        => $agent->logo_path,
            'license_number'   => $agent->license_number,
            'theme_slug'       => $agent->theme_slug,
            'theme_color'      => $agent->theme_color,
            'primary_bg_color' => $agent->primary_bg_color ?? '#1a1a1a',
            'brand_text_color' => $agent->brand_text_color ?? '#ffffff',
            'status'           => $agent->status,
            'photo_focal_x'    => (int) ($settings?->photo_focal_x ?? 50),
            'photo_focal_y'    => (int) ($settings?->photo_focal_y ?? 15),
            'settings'         => $settings ? [
                'custom_domain'        => $settings->custom_domain,
                'ga4_id'               => $settings->ga4_id,
                'fb_pixel_id'          => $settings->fb_pixel_id,
                'intro_video_url'      => $settings->intro_video_url,
                'social_links'         => $settings->social_links,
                'featured_listing_ids' => $settings->featured_listing_ids,
                'notification_email'   => $settings->notification_email,
                'notification_phone'   => $settings->notification_phone,
                'fub_enabled'          => (bool) $settings->fub_enabled,
                'ghl_enabled'          => (bool) ($settings->ghl_enabled ?? false),
                'ghl_api_key'          => $settings->ghl_api_key ?? null,
                'lead_routing'         => $settings->lead_routing,
                'seo_noindex'          => (bool) ($settings->seo_noindex ?? false),
                'subarea_whitelist'    => $settings->subarea_whitelist ?? null,
                'team_members'         => $settings->team_members
                    ? (is_array($settings->team_members)
                        ? $settings->team_members
                        : json_decode($settings->team_members, true))
                    : null,
                'hero_stats'           => $settings->hero_stats
                    ? (is_array($settings->hero_stats)
                        ? $settings->hero_stats
                        : json_decode($settings->hero_stats, true))
                    : null,
                'favicon_url'          => $settings->favicon_url ?? null,
            ] : null,
            'features' => collect($agent->features ?? [])->mapWithKeys(function ($f) {
                return [$f->feature_key => (bool) ($f->enabled ?? false)];
            })->toArray(),
        ];
    }




    // -- Agent Portal: Integrations (admin-secret protected) ------------------

    public function agentPortalIntegrationsGet(int $id): \Illuminate\Http\JsonResponse
    {
        $agent    = \App\Models\Agent::with('settings')->find($id);
        $settings = $agent?->settings;
        return response()->json([
            'ga4_id'      => $settings?->ga4_id,
            'fub_enabled' => (bool) ($settings?->fub_enabled ?? false),
            'ghl_enabled' => (bool) ($settings?->ghl_enabled ?? false),
        ]);
    }

    public function agentPortalIntegrationsUpdate(\Illuminate\Http\Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $agent = \App\Models\Agent::with('settings')->find($id);
        if (! $agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }
        $settings = $agent->settings()->firstOrCreate(['agent_id' => $id]);
        $data = $request->validate([
            'ga4_id'      => ['nullable', 'string', 'max:30'],
            'fub_enabled' => 'nullable|boolean',
            'fub_api_key' => 'nullable|string|max:200',
            'ghl_enabled' => 'nullable|boolean',
            'ghl_api_key' => 'nullable|string|max:200',
        ]);
        $settings->ga4_id      = $data['ga4_id'] ?? null;
        $settings->fub_enabled = (bool) ($data['fub_enabled'] ?? false);
        $settings->ghl_enabled = (bool) ($data['ghl_enabled'] ?? false);
        if (! empty($data['fub_api_key'])) { $settings->fub_api_key = $data['fub_api_key']; }
        if (! empty($data['ghl_api_key'])) { $settings->ghl_api_key = $data['ghl_api_key']; }
        $settings->save();
        return response()->json([
            'ga4_id'      => $settings->ga4_id,
            'fub_enabled' => (bool) $settings->fub_enabled,
            'ghl_enabled' => (bool) $settings->ghl_enabled,
        ]);
    }

    // ── Favourites — Bearer-token auth ──────────────────────────────────────

    private function getUserFromRequest(\Illuminate\Http\Request $request): ?object
    {
        $header = $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return null;
        }
        $plain  = substr($header, 7);
        $hashed = hash('sha256', $plain);
        $row = \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->table('user_tokens')
            ->where('token', $hashed)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();
        return $row;
    }

    public function getFavourites(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $token = $this->getUserFromRequest($request);
        if (! $token) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }
        $rows = \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->table('favorite_listings')
            ->where('userid', $token->user_id)
            ->where('deleted', 0)
            ->orderByDesc('created_at')
            ->pluck('listingid')
            ->toArray();
        return response()->json(['mls_nos' => array_values($rows)]);
    }

    public function addFavourite(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $token = $this->getUserFromRequest($request);
        if (! $token) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }
        $mlsNo = trim($request->input('mls_no', ''));
        if (! $mlsNo) {
            return response()->json(['error' => 'mls_no is required.'], 422);
        }
        $existing = \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->table('favorite_listings')
            ->where('userid', $token->user_id)
            ->where('listingid', $mlsNo)
            ->first();
        if ($existing) {
            \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->table('favorite_listings')
                ->where('id', $existing->id)
                ->update(['deleted' => 0]);
        } else {
            \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->table('favorite_listings')->insert([
                'userid'     => $token->user_id,
                'listingid'  => $mlsNo,
                'deleted'    => 0,
                'created_at' => now(),
                'ip'         => $request->ip(),
            ]);
        }
        return response()->json(['ok' => true]);
    }

    public function removeFavourite(\Illuminate\Http\Request $request, string $mlsNo): \Illuminate\Http\JsonResponse
    {
        $token = $this->getUserFromRequest($request);
        if (! $token) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }
        \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->table('favorite_listings')
            ->where('userid', $token->user_id)
            ->where('listingid', $mlsNo)
            ->update(['deleted' => 1]);
        return response()->json(['ok' => true]);
    }

    public function recordSoldGateEvent(\Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $event = $req->input("event");
        if (!in_array($event, ["register", "login"])) {
            return response()->json(["error" => "Invalid event"], 422);
        }
        \Illuminate\Support\Facades\DB::table("sold_gate_events")->insert([
            "event_type" => $event,
            "agent_slug"  => $req->input("agent_slug"),
            "mls"         => $req->input("mls"),
            "subarea"     => $req->input("subarea"),
            "created_at"  => now(),
        ]);
        return response()->json(["ok" => true]);
    }

    public function soldGateStats(\Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $days  = max(1, min(365, (int) $req->input("days", 30)));
        $since = now()->subDays($days);

        $totals = \Illuminate\Support\Facades\DB::table("sold_gate_events")
            ->where("created_at", ">=", $since)
            ->selectRaw("event_type, COUNT(*) as cnt")
            ->groupBy("event_type")
            ->pluck("cnt", "event_type");

        $rows = \Illuminate\Support\Facades\DB::table("sold_gate_events")
            ->where("created_at", ">=", $since)
            ->whereNotNull("agent_slug")
            ->selectRaw("agent_slug, event_type, COUNT(*) as cnt")
            ->groupBy("agent_slug", "event_type")
            ->get();

        $byAgent = [];
        foreach ($rows as $row) {
            $s = $row->agent_slug;
            if (!isset($byAgent[$s])) {
                $byAgent[$s] = ["slug" => $s, "register" => 0, "login" => 0];
            }
            $byAgent[$s][$row->event_type] = (int) $row->cnt;
        }

        return response()->json([
            "period_days"    => $days,
            "total_register" => (int) ($totals["register"] ?? 0),
            "total_login"    => (int) ($totals["login"] ?? 0),
            "by_agent"       => array_values($byAgent),
        ]);
    }


    public function soldGateStatsByDay(\Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $days  = max(1, min(90, (int) $req->input("days", 30)));
        $since = now()->subDays($days)->startOfDay();

        $rows = \Illuminate\Support\Facades\DB::table("sold_gate_events")
            ->where("created_at", ">=", $since)
            ->selectRaw("DATE(created_at) as day, event_type, COUNT(*) as cnt")
            ->groupBy("day", "event_type")
            ->orderBy("day")
            ->get();

        $byDay = [];
        foreach ($rows as $row) {
            $d = $row->day;
            if (!isset($byDay[$d])) {
                $byDay[$d] = ["day" => $d, "register" => 0, "login" => 0];
            }
            $byDay[$d][$row->event_type] = (int) $row->cnt;
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->format("Y-m-d");
            $result[] = $byDay[$d] ?? ["day" => $d, "register" => 0, "login" => 0];
        }

        return response()->json([
            "period_days" => $days,
            "daily"       => $result,
        ]);
    }


    /**
     * Admin global buildings list - all buildings, paginated.
     * GET /api-internal/admin/buildings
     */
    public function adminBuildingsList(Request $req): JsonResponse
    {
        $page    = max(1, (int) $req->query('page', 1));
        $limit   = min(100, max(1, (int) $req->query('limit', 50)));
        $city    = $req->query('city');
        $subarea = $req->query('subarea');

        $q = Buildings::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->select([
                'id', 'name', 'slug', 'city', 'subarea',
                'yearbuilt', 'units_in_development', 'strata_no', 'levels',
            ]);

        if ($city)    $q->where('city', $city);
        if ($subarea) $q->where('subarea', $subarea);

        if ($req->query('missing_only')) {
            $q->where(function ($qb) {
                $qb->whereNull('tagline')->orWhere('tagline', '');
            })->where(function ($qb) {
                $qb->whereNull('description')->orWhere('description', '');
            });
        }

        if ($req->query('missing_features_only')) {
            $q->where(function ($qb) {
                $qb->whereNull('ai_features_json')->orWhere('ai_features_json', '');
            });
        }

        $tagFilter = array_filter((array) $req->query('tags', []));
        $hasAmenityTagsCol2 = !empty(\Illuminate\Support\Facades\DB::connection('mysql_mlsr')->select("SHOW COLUMNS FROM buildings LIKE 'amenity_tags'"));
        if (!empty($tagFilter) && $hasAmenityTagsCol2) {
            foreach ($tagFilter as $tag) {
                $q->where('amenity_tags', 'LIKE', '%"' . addslashes($tag) . '"%');
            }
        }

        $total     = (clone $q)->count();
        $buildings = $q->orderByDesc('yearbuilt')->forPage($page, $limit)->get();

        return response()->json([
            'buildings' => $buildings->map(function (Buildings $b) {
                $photoUrl = null;
                try { $photoUrl = $b->main_image() ?: null; } catch (\Throwable $e) {}
            if ($photoUrl && !str_starts_with($photoUrl, 'https://')) { $photoUrl = null; }

                $activeCount = 0; $min = null; $max = null;
                if ($b->strata_no && trim($b->strata_no) !== ''
                    && (int)($b->yearbuilt ?? 0) <= (int) date('Y')) {
                    try {
                        $agg = Listings::withoutGlobalScopes()
                            ->where('status', 'Active')
                            ->where('strata_no', $b->strata_no)
                            ->selectRaw('COUNT(*) as c, MIN(listprice_2) as mn, MAX(listprice_2) as mx')
                            ->first();
                        $activeCount = (int)($agg->c ?? 0);
                        $min = ($agg->mn ?? null) ? (int)$agg->mn : null;
                        $max = ($agg->mx ?? null) ? (int)$agg->mx : null;
                    } catch (\Throwable $e) {}
                }
                return [
                    'id'              => (string) $b->id,
                    'name'            => $b->name,
                    'slug'            => $b->slug,
                    'city'            => $b->city,
                    'subarea'         => $b->subarea,
                    'year_built'      => $b->yearbuilt ? (int)$b->yearbuilt : null,
                    'units'           => $b->units_in_development ? (int)$b->units_in_development : null,
                    'levels'          => $b->levels ? (int)$b->levels : null,
                    'strata_no'       => $b->strata_no ?: null,
                    'photo_url'       => $photoUrl,
                    'min_price'       => $min,
                    'max_price'       => $max,
                    'active_listings' => $activeCount,
                    'amenity_tags'    => $b->amenity_tags ? json_decode($b->amenity_tags, true) : [],
                ];
            }),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * POST /api-internal/admin/buildings/{id}/tags
     * Save amenity tags for a building.
     * Allowed: air_conditioning, panel_fridge, gas_appliances, electric_appliances.
     */
    public function adminSaveBuildingTags(Request $req, int $id): JsonResponse
    {
        $allowed = ['air_conditioning', 'panel_fridge', 'gas_appliances', 'electric_appliances'];
        $tags = array_values(array_filter((array) $req->input('tags', []), function ($t) use ($allowed) {
            return in_array($t, $allowed, true);
        }));

        if (!\Illuminate\Support\Facades\Schema::hasColumn('buildings', 'amenity_tags')) {
            \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->statement('ALTER TABLE buildings ADD COLUMN amenity_tags LONGTEXT NULL');
        }

        $exists = \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->table('buildings')->where('id', $id)->exists();
        if (!$exists) {
            return response()->json(['error' => 'Building not found'], 404);
        }

        \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->table('buildings')->where('id', $id)->update([
            'amenity_tags' => json_encode($tags),
        ]);

        return response()->json(['ok' => true, 'tags' => $tags]);
    }

    /**
     * GET /api-internal/agent/{slug}/ai-content/area-intro
     * Returns the agent-level area intro AI page (subarea = '_area').
     */
    public function areaIntro(string $slug): \Illuminate\Http\JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $page = \Illuminate\Support\Facades\DB::table('agent_ai_pages')
            ->where('agent_id', $agent->id)
            ->where('page_type', 'area_intro')
            ->orderByDesc('generated_at')
            ->first();

        if (! $page) return response()->json(null);

        return response()->json([
            'content'          => $page->content,
            'title'            => $page->title,
            'meta_description' => $page->meta_description,
            'generated_at'     => $page->generated_at,
        ]);
    }

    /**
     * GET /api-internal/agent/{slug}/neighbourhood-ai-content?subareas[]=x&subareas[]=y
     * Returns buyer_personas and lifestyle_seo keyed by subarea name.
     */
    public function neighbourhoodAiContent(string $slug, \Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $subareas = $req->query('subareas', []);
        if (empty($subareas)) return response()->json([]);

        $rows = \Illuminate\Support\Facades\DB::table('agent_ai_pages')
            ->where('agent_id', $agent->id)
            ->whereIn('page_type', ['buyer_personas', 'lifestyle_seo'])
            ->whereIn('subarea', $subareas)
            ->orderByDesc('generated_at')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $sub = $row->subarea;
            if (! isset($result[$sub])) $result[$sub] = [];
            // Only keep the most recent per type (already ordered desc)
            if (! isset($result[$sub][$row->page_type])) {
                $result[$sub][$row->page_type] = [
                    'content'          => $row->content,
                    'title'            => $row->title,
                    'meta_description' => $row->meta_description,
                    'generated_at'     => $row->generated_at,
                ];
            }
        }

        return response()->json($result);
    }

    /**
     * POST /api-internal/admin/agents/{id}/upload-photo
     * Accepts a multipart file, saves to storage/app/public/agents/, updates photo_path.
     * Protected by VerifyAdminSecret middleware.
     */
    public function uploadAgentPhoto(Request $req, int $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (! $agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        if (! $req->hasFile('photo')) {
            return response()->json(['error' => 'No file uploaded'], 422);
        }

        $file = $req->file('photo');
        $ext  = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            return response()->json(['error' => 'Unsupported file type. Use jpg, png, or webp.'], 422);
        }

        $filename = 'agent_' . $id . '_' . uniqid() . '.' . $ext;
        $file->storeAs('agents', $filename, 'public');

        $publicUrl = config('app.url') . '/storage/agents/' . $filename;
        $agent->update(['photo_path' => $publicUrl]);

        return response()->json(['photo_url' => $publicUrl]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    // LANDING PAGES
    // ─────────────────────────────────────────────────────────────────────────

    private function formatLandingPage(object $p): array
    {
        return [
            'id'                 => $p->id,
            'agent_id'           => $p->agent_id,
            'city_slug'          => $p->city_slug,
            'city_display_name'  => $p->city_display_name,
            'area_slug'          => $p->area_slug,
            'area_display_name'  => $p->area_display_name,
            'province'           => $p->province,
            'respond_time_label' => $p->respond_time_label,
            'award_badges'       => $p->award_badges ? json_decode($p->award_badges, true) : [],
            'stat_years_exp'     => $p->stat_years_exp,
            'stat_sold_volume'   => $p->stat_sold_volume,
            'stat_team_size'     => $p->stat_team_size,
            'stat_award_label'   => $p->stat_award_label,
            'value_prop_cards'   => $p->value_prop_cards ? json_decode($p->value_prop_cards, true) : [],
            'testimonials'       => $p->testimonials ? json_decode($p->testimonials, true) : [],
            'meta_title'         => $p->meta_title,
            'meta_description'   => $p->meta_description,
            'created_at'         => $p->created_at,
            'updated_at'         => $p->updated_at,
        ];
    }

    /**
     * GET /api-internal/agent/{slug}/landing-pages
     */
    public function landingPagesList(string $slug): JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $pages = \Illuminate\Support\Facades\DB::table('agent_landing_pages')
            ->where('agent_id', $agent->id)
            ->orderBy('city_display_name')
            ->orderBy('area_display_name')
            ->get();

        return response()->json($pages->map(fn ($p) => $this->formatLandingPage($p)));
    }

    /**
     * GET /api-internal/agent/{slug}/landing-pages/{citySlug}
     */
    public function landingPageByCity(string $slug, string $citySlug): JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $page = \Illuminate\Support\Facades\DB::table('agent_landing_pages')
            ->where('agent_id', $agent->id)
            ->where('city_slug', $citySlug)
            ->whereNull('area_slug')
            ->first();

        if (! $page) return response()->json(['error' => 'Landing page not found'], 404);
        return response()->json($this->formatLandingPage($page));
    }

    /**
     * GET /api-internal/agent/{slug}/landing-pages/{citySlug}/{areaSlug}
     */
    public function landingPageByArea(string $slug, string $citySlug, string $areaSlug): JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $page = \Illuminate\Support\Facades\DB::table('agent_landing_pages')
            ->where('agent_id', $agent->id)
            ->where('city_slug', $citySlug)
            ->where('area_slug', $areaSlug)
            ->first();

        if (! $page) return response()->json(['error' => 'Landing page not found'], 404);
        return response()->json($this->formatLandingPage($page));
    }

    /**
     * GET /api-internal/admin/agents/{id}/landing-pages
     */

    /**
     * POST /api-internal/admin/test-ghl-push
     * Fire a dummy contact at GHL to verify the API key works.
     */
    public function testGhlPush(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        if ($request->header('X-Admin-Secret') !== config('app.admin_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $agentId  = (int) $request->input('agent_id');
        $settings = \App\Models\AgentSettings::where('agent_id', $agentId)->first();

        if (! $settings
            || ! $settings->ghl_enabled
            || empty($settings->ghl_api_key)
            || empty($settings->getRawOriginal('ghl_location_id'))) {
            return response()->json(['ok' => false, 'reason' => 'not_configured']);
        }

        $apiKey     = $settings->ghl_api_key;
        $locationId = $settings->ghl_location_id;
        $payload = json_encode([
            'firstName'  => 'Pixilink',
            'lastName'   => 'Test',
            'email'      => 'test+ghl@pixilink.com',
            'locationId' => $locationId,
            'tags'       => ['website-lead', 'test'],
            'source'     => 'Pixilink Test',
        ]);

        $ch = curl_init('https://services.leadconnectorhq.com/contacts/');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Version: 2021-07-28',
            ],
        ]);
        $body       = curl_exec($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr    = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return response()->json(['ok' => false, 'reason' => 'curl_error', 'body' => $curlErr]);
        }

        $decoded = json_decode($body, true);
        $contactId = $decoded['contact']['id'] ?? $decoded['id'] ?? null;
        $ok      = $httpStatus >= 200 && $httpStatus < 300 && ! empty($contactId);
        return response()->json(['ok' => $ok, 'status' => $httpStatus, 'body' => $decoded ?? $body]);
    }

    public function adminLandingPagesList(Request $req, int $agentId): JsonResponse
    {
        if ($req->header('X-Admin-Secret') !== config('app.admin_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $pages = \Illuminate\Support\Facades\DB::table('agent_landing_pages')
            ->where('agent_id', $agentId)
            ->orderBy('city_display_name')
            ->orderBy('area_display_name')
            ->get();

        return response()->json($pages->map(fn ($p) => $this->formatLandingPage($p)));
    }

    /**
     * POST /api-internal/admin/agents/{id}/landing-pages
     */
    public function adminLandingPagesCreate(Request $req, int $agentId): JsonResponse
    {
        if ($req->header('X-Admin-Secret') !== config('app.admin_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $req->all();
        $id = \Illuminate\Support\Facades\DB::table('agent_landing_pages')->insertGetId([
            'agent_id'           => $agentId,
            'city_slug'          => $data['city_slug'] ?? '',
            'city_display_name'  => $data['city_display_name'] ?? '',
            'area_slug'          => isset($data['area_slug']) && $data['area_slug'] !== '' ? $data['area_slug'] : null,
            'area_display_name'  => $data['area_display_name'] ?? '',
            'province'           => $data['province'] ?? 'BC',
            'respond_time_label' => $data['respond_time_label'] ?? '15 min',
            'award_badges'       => json_encode($data['award_badges'] ?? []),
            'stat_years_exp'     => $data['stat_years_exp'] ?? null,
            'stat_sold_volume'   => $data['stat_sold_volume'] ?? null,
            'stat_team_size'     => $data['stat_team_size'] ?? null,
            'stat_award_label'   => $data['stat_award_label'] ?? null,
            'value_prop_cards'   => json_encode($data['value_prop_cards'] ?? []),
            'testimonials'       => json_encode($data['testimonials'] ?? []),
            'meta_title'         => $data['meta_title'] ?? null,
            'meta_description'   => $data['meta_description'] ?? null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $page = \Illuminate\Support\Facades\DB::table('agent_landing_pages')->where('id', $id)->first();
        return response()->json($this->formatLandingPage($page), 201);
    }

    /**
     * PUT /api-internal/admin/agents/{id}/landing-pages/{pageId}
     */
    public function adminLandingPagesUpdate(Request $req, int $agentId, int $pageId): JsonResponse
    {
        if ($req->header('X-Admin-Secret') !== config('app.admin_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $page = \Illuminate\Support\Facades\DB::table('agent_landing_pages')
            ->where('id', $pageId)->where('agent_id', $agentId)->first();
        if (! $page) return response()->json(['error' => 'Not found'], 404);

        $data   = $req->all();
        $update = ['updated_at' => now()];
        $fields = [
            'city_slug', 'city_display_name', 'province', 'respond_time_label',
            'stat_years_exp', 'stat_sold_volume', 'stat_team_size', 'stat_award_label',
            'meta_title', 'meta_description', 'area_display_name',
        ];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) $update[$f] = $data[$f];
        }
        if (array_key_exists('area_slug', $data)) {
            $update['area_slug'] = ($data['area_slug'] !== '') ? $data['area_slug'] : null;
        }
        if (array_key_exists('award_badges', $data))    $update['award_badges']    = json_encode($data['award_badges']);
        if (array_key_exists('value_prop_cards', $data))$update['value_prop_cards']= json_encode($data['value_prop_cards']);
        if (array_key_exists('testimonials', $data))    $update['testimonials']    = json_encode($data['testimonials']);

        \Illuminate\Support\Facades\DB::table('agent_landing_pages')->where('id', $pageId)->update($update);
        $updated = \Illuminate\Support\Facades\DB::table('agent_landing_pages')->where('id', $pageId)->first();
        return response()->json($this->formatLandingPage($updated));
    }

    /**
     * DELETE /api-internal/admin/agents/{id}/landing-pages/{pageId}
     */
    public function adminLandingPagesDelete(Request $req, int $agentId, int $pageId): JsonResponse
    {
        if ($req->header('X-Admin-Secret') !== config('app.admin_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $deleted = \Illuminate\Support\Facades\DB::table('agent_landing_pages')
            ->where('id', $pageId)->where('agent_id', $agentId)->delete();

        if (! $deleted) return response()->json(['error' => 'Not found'], 404);
        return response()->json(['ok' => true]);
    }

    /**
     * POST /api-internal/admin/buildings/{id}/description
     * Saves AI-generated content for a building.
     * Auto-migrates required columns the first time it runs.
     */
    public function saveBuildingDescription(Request $req, string $id): JsonResponse
    {
        // Idempotent column migration
        $columns = [
            'tagline'               => 'ALTER TABLE buildings ADD COLUMN tagline VARCHAR(120) NULL DEFAULT NULL',
            'neighbourhood_context' => 'ALTER TABLE buildings ADD COLUMN neighbourhood_context TEXT NULL DEFAULT NULL',
            'meta_description'      => 'ALTER TABLE buildings ADD COLUMN meta_description VARCHAR(220) NULL DEFAULT NULL',
            'faq_json'              => 'ALTER TABLE buildings ADD COLUMN faq_json LONGTEXT NULL DEFAULT NULL',
            'description'           => 'ALTER TABLE buildings ADD COLUMN description TEXT NULL DEFAULT NULL',
        ];
        foreach ($columns as $col => $ddl) {
            try {
                $exists = \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->select("SHOW COLUMNS FROM buildings LIKE '{$col}'");
                if (empty($exists)) \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->statement($ddl);
            } catch (\Throwable $e) {}
        }

        $building = Buildings::withoutGlobalScopes()->find($id);
        if (!$building) return response()->json(['error' => 'Building not found'], 404);

        $data = [];
        if ($req->has('tagline'))               $data['tagline']               = $req->input('tagline');
        if ($req->has('description'))           $data['description']           = $req->input('description');
        if ($req->has('neighbourhood_context')) $data['neighbourhood_context'] = $req->input('neighbourhood_context');
        if ($req->has('meta_description'))      $data['meta_description']      = $req->input('meta_description');
        if ($req->has('faq_json'))              $data['faq_json']              = $req->input('faq_json');

        if (!empty($data)) {
            \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->table('buildings')->where('id', $id)->update($data);
        }

        return response()->json(['success' => true, 'id' => $id]);
    }


    // ── Platform Settings (global noindex toggle) ────────────────────────────

    public function getPlatformSettings(): \Illuminate\Http\JsonResponse
    {
        $row = \Illuminate\Support\Facades\DB::table('platform_settings')
            ->where('key', 'global_noindex')
            ->first();
        $globalNoindex = $row ? ((bool) (int) $row->value) : false;
        return response()->json(['global_noindex' => $globalNoindex]);
    }

    public function updatePlatformSettings(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $globalNoindex = $request->boolean('global_noindex', false);
        \Illuminate\Support\Facades\DB::table('platform_settings')
            ->updateOrInsert(
                ['key' => 'global_noindex'],
                ['value' => $globalNoindex ? '1' : '0', 'updated_at' => now()]
            );
        return response()->json(['ok' => true, 'global_noindex' => $globalNoindex]);
    }


    /**
     * Admin: save AI-generated features JSON for a building.
     * POST /api-internal/admin/buildings/{id}/features
     */
    public function saveBuildingFeatures(Request $req, string $id): JsonResponse
    {
        // Idempotent column migration
        try {
            $exists = \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->select("SHOW COLUMNS FROM buildings LIKE 'ai_features_json'");
            if (empty($exists)) {
                \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->statement('ALTER TABLE buildings ADD COLUMN ai_features_json LONGTEXT NULL DEFAULT NULL');
            }
        } catch (\Throwable $e) {}

        $building = Buildings::withoutGlobalScopes()->find($id);
        if (!$building) return response()->json(['error' => 'Building not found'], 404);

        $featuresJson = $req->input('features_json');
        if ($featuresJson !== null) {
            \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->table('buildings')->where('id', $id)->update(['ai_features_json' => $featuresJson]);
        }

        return response()->json(['success' => true, 'id' => $id]);
    }

}
