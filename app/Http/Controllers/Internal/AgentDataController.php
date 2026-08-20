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
            ->whereIn('status', ['active', 'suspended'])
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
            ->whereIn('status', ['active', 'suspended'])
            ->first();

        if (! $agent) {
            return response()->json(['error' => 'Agent inactive'], 404);
        }

        return response()->json($this->format($agent));
    }

    /**
     * GET /api-internal/cities
     * Returns a sorted list of all distinct active MLS cities (province-wide, no agent scoping).
     * Used by the search page city dropdown when all_search=1 is active.
     */
    public function cities(): JsonResponse
    {
        $rows = DB::connection('mysql_mlsr')
            ->table('mlsr_listings_master')
            ->where('status', 'Active')
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return response()->json($rows->values());
    }

    /**
     * Territory-scoped listings search. Pass all_search=1 to skip territory scoping
     * and search all of BC.
     * Query params: status (Active|Sold), type, min_price, max_price, beds,
     *               baths, subarea, city, sort (newest|price_asc|price_desc|beds|dom),
     *               days_back, page, limit, all_search.
     */
    public function featuredListings(string $slug, Request $req): JsonResponse
    {
        $allSearch = (bool) $req->query('all_search', false);

        $agent = Agent::with(['territories', 'settings'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        if (! $allSearch) {
            $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
            if (empty($cities)) {
                return response()->json(['data' => [], 'total' => 0, 'page' => 1, 'limit' => 24]);
            }
        }

        // Restrict to the agent's configured subarea whitelist when present (territory-scoped only).
        $subareaWhitelist = null;
        if (! $allSearch && $agent->settings) {
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
        $city     = $req->query('city');
        $minPrice = (int) $req->query('min_price', 0);
        $maxPrice = (int) $req->query('max_price', 0);
        $beds     = (int) $req->query('beds', 0);
        $baths    = (float) $req->query('baths', 0);
        $sort     = $req->query('sort', 'newest');
        $daysBack = (int) $req->query('days_back', 0);
        $priceReduced = (int) $req->query('price_reduced', 0);
        // Free-text search over address and MLS number. The showcase search page has
        // always shipped a keyword box, but there was no server-side param for it, so it
        // filtered only the 40 rows already on screen out of 50k+ — searching an MLS
        // number returned "No homes match" for a listing that plainly exists.
        $keyword  = trim((string) $req->query('keyword', ''));
        $page     = max(1, (int) $req->query('page', 1));
        $limit    = min(250, max(1, (int) $req->query('limit', 24)));

        $priceCol = $status === 'Sold' ? 'soldprice_2' : 'listprice_2';

        $q = Listings::withoutGlobalScopes()
            ->where('status', $status);
        if (! $allSearch) {
            $q->whereIn('city', $cities);
        }
        $q
            ->select([
                'sysid', 'listingid', 'streetaddress', 'city', 'subarea',
                'status', 'listprice', 'listprice_2', 'soldprice', 'soldprice_2',
                'bedrooms', 'bathstotal', 'livingarea', 'livingarea_2', 'mainpicurl', 'thumbnailurl', 'slug',
                'type', 'home_style', 'dom', 'lat', 'lng',
                'yearbuilt', 'maintenance', 'sold_date', 'list_date',
                'original_price', 'prev_price',
                'lotsize', 'frontage', 'finished_levels',
                'basement', 'kitchens', 'remarks',
            ]);

        if ($subareaWhitelist) {
            $q->whereIn('subarea', $subareaWhitelist);
        }

        if ($city)     $q->where('city', $city);
        if ($keyword !== '') {
            // Escape LIKE wildcards so a user typing % or _ searches literally rather
            // than matching everything.
            $kw = '%' . addcslashes($keyword, '%_\\\\') . '%';
            $q->where(function ($w) use ($kw) {
                $w->where('streetaddress', 'like', $kw)
                  ->orWhere('listingid', 'like', $kw)
                  ->orWhere('city', 'like', $kw)
                  ->orWhere('subarea', 'like', $kw);
            });
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
        $maxYear = (int) $req->query('max_year', 0);
        if ($maxYear) $q->where('yearbuilt', '<=', $maxYear);

        $minLotSize = (int) $req->query('min_lot_size', 0);
        if ($minLotSize) $q->whereRaw('CAST(lotsize AS DECIMAL(15,2)) >= ?', [$minLotSize]);

        // Suite filters (active listings only)
        $withSuite    = (int) $req->query('with_suite', 0);
        $twoSuites    = (int) $req->query('two_suites', 0);
        $coachHome    = (int) $req->query('coach_home', 0);
        $lanewayHouse = (int) $req->query('laneway_house', 0);
        $legalSuite   = (int) $req->query('legal_suite', 0);

        // Suite filters use a pre-computed flags table (bccondosandhomes.mlsr_suite_flags)
        // instead of LIKE/OR on remarks/basement. The flags table is a cross-DB mirror:
        // bccondosandhomes user has no ALTER privilege on pixilink_mlsr, so the boolean
        // flags live in bccondosandhomes and are refreshed every 2h by a cron job.
        // MySQL optimises the subquery to an eq_ref nested-loop join (~35ms cached).
        if ($withSuite && $status === 'Active') {
            $q->whereIn('sysid', function ($sub) {
                $sub->select('sysid')->from('bccondosandhomes.mlsr_suite_flags')->where('has_suite', 1);
            });
        }
        if ($twoSuites && $status === 'Active') {
            $q->where('kitchens', '>=', 3);
        }
        if ($coachHome && $status === 'Active') {
            $q->whereIn('sysid', function ($sub) {
                $sub->select('sysid')->from('bccondosandhomes.mlsr_suite_flags')->where('has_coach_home', 1);
            });
        }
        if ($lanewayHouse && $status === 'Active') {
            $q->whereIn('sysid', function ($sub) {
                $sub->select('sysid')->from('bccondosandhomes.mlsr_suite_flags')->where('has_laneway', 1);
            });
        }
        if ($legalSuite && $status === 'Active') {
            $q->whereIn('sysid', function ($sub) {
                $sub->select('sysid')->from('bccondosandhomes.mlsr_suite_flags')->where('has_legal_suite', 1);
            });
        }

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

        $suiteFilterActive = $status === 'Active' && ($withSuite || $twoSuites || $coachHome || $lanewayHouse || $legalSuite);

        $buildPayload = function () use ($q, $page, $limit) {
            $total = (clone $q)->count();

            $listings = $q->forPage($page, $limit)->get();

            return [
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
                    'photo_url'  => (str_replace('http://', 'https://', $l->mainpicurl ?: $l->thumbnailurl ?: '') ?: null),
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
                    'basement'   => $l->basement ?: null,
                    'kitchens'   => $l->kitchens ? (int) $l->kitchens : null,
                    'rental_income_hint' => (preg_match('/\\$\\s*([\\d,]+)\\s*(?:\/\\s*(?:mo(?:nth)?)|per month|monthly)/i', $l->remarks ?? '', $rMatch) ? '$'.$rMatch[1].'/mo' : null),
                ])->values()->toArray(),
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
            ];
        };

        if ($suiteFilterActive) {
            // Suite / coach-home / laneway-house / legal-suite filters scan the
            // unindexed `remarks` column across ~1.6M rows in mlsr_listings_master
            // (measured 5-6.3s), which exceeds the Next.js frontend's default
            // 4s fetch timeout and triggers its hardcoded FALLBACK_LISTINGS data
            // (mixed property types, null photo_url — the reported wrong-type /
            // broken-image bug on the suite/coach-home landing pages). Cache the
            // slow suite-filtered result so repeat requests are fast; same
            // pattern as marketReport/marketStats caching.
            $cacheKey = 'listings_suite_v2_' . $slug . '_' . md5((string) $req->getQueryString());
            $payload = Cache::remember($cacheKey, 1800, $buildPayload);
        } else {
            $payload = $buildPayload();
        }

        return response()->json($payload);
    }

    /**
     * Listings personally listed by this agent (by agent_id / MLS agent code).
     * Returns same shape as featuredListings so the Next.js api.ts can handle it identically.
     * GET /api-internal/agent/{slug}/own-listings?status=Active|Sold&limit=N
     */
    public function ownListings(string $slug, Request $req): JsonResponse
    {
        try {
            $agent = DB::table('agents')->where('slug', $slug)->first();
            if (!$agent) return response()->json(['data' => [], 'total' => 0]);

            $mlsIds = DB::table('agent_mls_ids')
                ->where('agent_id', $agent->id)
                ->pluck('mls_id')
                ->toArray();

            if (empty($mlsIds)) return response()->json(['data' => [], 'total' => 0]);

            $status = in_array($req->query('status'), ['Active', 'Sold']) ? $req->query('status') : 'Active';
            $limit  = min(100, max(1, (int) $req->query('limit', 24)));

            $q = Listings::withoutGlobalScopes()
                ->whereIn('agent_id', $mlsIds)
                ->where('status', $status)
                ->select([
                    'sysid', 'listingid', 'streetaddress', 'city', 'subarea',
                    'status', 'listprice', 'listprice_2', 'soldprice', 'soldprice_2',
                    'bedrooms', 'bathstotal', 'livingarea', 'livingarea_2',
                    'mainpicurl', 'thumbnailurl', 'slug',
                    'type', 'home_style', 'dom', 'lat', 'lng',
                    'yearbuilt', 'maintenance', 'sold_date', 'list_date',
                    'original_price', 'lotsize', 'frontage', 'finished_levels',
                ])
                ->orderBy($status === 'Sold' ? 'sold_date' : 'list_date', 'desc');

            $total    = (clone $q)->count();
            $listings = $q->limit($limit)->get();

            return response()->json([
                'data'  => $listings->map(fn ($l) => [
                    'id'               => $l->sysid,
                    'mls_no'           => $l->listingid,
                    'address'          => $l->streetaddress,
                    'city'             => $l->city,
                    'subarea'          => $l->subarea,
                    'status'           => $l->status,
                    'list_price'       => (int) ($l->listprice_2 ?: $l->listprice),
                    'original_price'   => ((int) $l->original_price > 0) ? (int) $l->original_price : null,
                    'price_reduced'    => ($l->status === 'Active' && (int) $l->original_price > (int) ($l->listprice_2 ?: $l->listprice) && (int) ($l->listprice_2 ?: $l->listprice) > 0),
                    'reduction_amount' => ($l->status === 'Active' && (int) $l->original_price > (int) ($l->listprice_2 ?: $l->listprice) && (int) ($l->listprice_2 ?: $l->listprice) > 0) ? ((int) $l->original_price - (int) ($l->listprice_2 ?: $l->listprice)) : 0,
                    'sold_price'       => ($l->soldprice_2 > 0) ? (int) $l->soldprice_2 : (($l->soldprice > 0) ? (int) $l->soldprice : null),
                    'beds'             => (int) $l->bedrooms,
                    'baths'            => (float) $l->bathstotal,
                    'sqft'             => (int) str_replace(",", "", (string) ($l->livingarea_2 ?: $l->livingarea ?: "0")),
                    'photo_url'        => (str_replace('http://', 'https://', $l->mainpicurl ?: $l->thumbnailurl ?: '') ?: null),
                    'type'             => $l->type,
                    'style'            => $l->home_style,
                    'slug'             => $l->slug,
                    'dom'              => ($l->status === 'Active' && !empty($l->list_date) && $l->list_date !== '0000-00-00')
                        ? (int) floor((now()->timestamp - strtotime($l->list_date)) / 86400)
                        : ((isset($l->dom) && $l->dom) ? (int) $l->dom : null),
                    'sold_date'        => ($l->sold_date && $l->sold_date !== '0000-00-00') ? (string) $l->sold_date : null,
                    'list_date'        => ($l->list_date && $l->list_date !== '0000-00-00') ? (string) $l->list_date : null,
                    'year_built'       => $l->yearbuilt ? (int) $l->yearbuilt : null,
                    'strata_fee'       => ($l->maintenance > 0) ? (float) $l->maintenance : null,
                    'lot_size'         => ($l->lotsize > 0) ? (float) $l->lotsize : null,
                    'frontage'         => ($l->frontage > 0) ? (float) $l->frontage : null,
                    'levels'           => ($l->finished_levels > 0) ? (int) $l->finished_levels : null,
                    'latitude'         => (isset($l->lat) && $l->lat != 0) ? (float) $l->lat : null,
                    'longitude'        => (isset($l->lng) && $l->lng != 0) ? (float) $l->lng : null,
                ]),
                'total' => $total,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['data' => [], 'total' => 0]);
        }
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


        // Suite detection
        $basement = $listing->basement ?? '';
        $remarks = $listing->publicremarks ?? $listing->remarks ?? '';
        $kitchens = (int) ($listing->kitchens ?? 0);
        $hasSuiteByEntry = (stripos($basement, 'Separate Entry') !== false || stripos($basement, 'Exterior Entry') !== false);
        $hasWokKitchen = false;
        if ($mlsr) {
            for ($ri = 1; $ri <= 28; $ri++) {
                $rt = $mlsr->{"room{$ri}_type"} ?? null;
                if ($rt && stripos($rt, 'wok') !== false) { $hasWokKitchen = true; break; }
            }
        }
        $hasSuiteByKitchens = ($kitchens >= 2 && !$hasWokKitchen);
        $hasSuite = $hasSuiteByEntry || $hasSuiteByKitchens;
        $suiteCount = ($kitchens >= 3) ? 2 : ($hasSuite ? 1 : 0);
        $legalSuite = (stripos($remarks, 'legal suite') !== false);
        $suiteLabel = null;
        if ($hasSuite) {
            if (stripos($remarks, 'laneway') !== false) {
                $suiteLabel = 'Laneway House';
            } elseif (stripos($remarks, 'coach home') !== false || stripos($remarks, 'coach house') !== false) {
                $suiteLabel = 'Coach Home';
            } elseif ($legalSuite) {
                $suiteLabel = 'Legal Basement Suite';
            } elseif (stripos($basement, 'Exterior Entry') !== false) {
                $suiteLabel = 'Exterior Entry Suite';
            } elseif (stripos($basement, 'Separate Entry') !== false) {
                $suiteLabel = 'Separate Entry Basement Suite';
            } elseif (stripos($remarks, 'in-law') !== false || stripos($remarks, 'in law') !== false) {
                $suiteLabel = 'In-Law Suite';
            } else {
                $suiteLabel = 'Secondary Suite';
            }
        }
        $rentalIncome = null;
        if (preg_match('/\$(\d{1,2},?\d{3})\s*\/?(?:mo|month)/i', $remarks, $rm)) {
            $rentalIncome = '$' . $rm[1] . '/mo';
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
            'photo_url'        => (str_replace('http://', 'https://', $listing->mainpicurl ?: '') ?: null),
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
            'has_suite'          => $hasSuite,
            'suite_count'        => $suiteCount,
            'suite_label'        => $suiteLabel,
            'legal_suite'        => $legalSuite,
            'rental_income_hint' => $rentalIncome,
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
                        'photo_url'       => (str_replace('http://', 'https://', $l->mainpicurl ?: '') ?: null),
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
                    'photo_url'  => (str_replace('http://', 'https://', $l->mainpicurl ?: $l->thumbnailurl ?: '') ?: null),
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
                    'photo_url'  => (str_replace('http://', 'https://', $l->mainpicurl ?: $l->thumbnailurl ?: '') ?: null),
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
     * Returns the most recently sold unit in the same building (same strata_no)
     * as the subject listing, excluding the subject itself.
     * Returns null (HTTP 200) when listing has no strata_no or no sold history.
     * Never exposes a sold price - price gating is enforced on the frontend.
     *
     * Route: GET /api-internal/agent/{slug}/listing/{listingSlug}/building-last-sold
     */
    public function buildingLastSold(string $slug, string $listingSlug): JsonResponse
    {
        $listing = Listings::withoutGlobalScopes()
            ->where('slug', $listingSlug)
            ->orWhere('listingid', $listingSlug)
            ->first(['listingid', 'strata_no', 'streetaddress']);

        if (! $listing || ! $listing->strata_no || trim($listing->strata_no) === '') {
            return response()->json(null);
        }

        $lastSold = Listings::withoutGlobalScopes()
            ->where('status', 'Sold')
            ->where('strata_no', $listing->strata_no)
            ->where('listingid', '!=', $listing->listingid)
            ->whereNotNull('sold_date')
            ->where('sold_date', '!=', '0000-00-00')
            ->orderBy('sold_date', 'desc')
            ->first(['listingid', 'streetaddress', 'sold_date']);

        if (! $lastSold) {
            return response()->json(null);
        }

        // Extract unit number from address (e.g. "1405 12345 104 Ave" -> "1405")
        $unit = null;
        if ($lastSold->streetaddress) {
            $parts = explode(' ', trim($lastSold->streetaddress));
            if (count($parts) > 1 && ctype_digit($parts[0])) {
                $unit = $parts[0];
            }
        }

        // Look up building name from buildings table
        $buildingName = null;
        try {
            $b = Buildings::withoutGlobalScopes()
                ->where('strata_no', $listing->strata_no)
                ->value('name');
            $buildingName = $b ?: null;
        } catch (\Throwable $e) {}

        return response()->json([
            'unit'          => $unit,
            'sold_date'     => $lastSold->sold_date,
            'building_name' => $buildingName,
            'mls_num'       => $lastSold->listingid,
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

    public function faqs(string $slug): JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json([], 200);

        try {
            // agent_faqs table is the primary source (ordered by sort_order)
            if (\Illuminate\Support\Facades\Schema::hasTable('agent_faqs')) {
                $faqs = \Illuminate\Support\Facades\DB::table('agent_faqs')
                    ->where('agent_id', $agent->id)
                    ->orderBy('sort_order')->orderBy('id')
                    ->get();
                if ($faqs->count() > 0) {
                    return response()->json($faqs->map(fn($f) => [
                        'id'       => $f->id,
                        'question' => $f->question ?? null,
                        'answer'   => $f->answer ?? null,
                    ])->values());
                }
            }

            // Fallback: faqs_json blob in agent_settings (admin-managed via blade form)
            $settings = \Illuminate\Support\Facades\DB::table('agent_settings')
                ->where('agent_id', $agent->id)
                ->first();
            if ($settings && !empty($settings->faqs_json)) {
                $decoded = json_decode($settings->faqs_json, true);
                if (is_array($decoded)) {
                    $out = [];
                    foreach ($decoded as $i => $item) {
                        if (!empty($item['q']) || !empty($item['a'])) {
                            $out[] = [
                                'id'       => $i + 1,
                                'question' => $item['q'] ?? $item['question'] ?? null,
                                'answer'   => $item['a'] ?? $item['answer'] ?? null,
                            ];
                        }
                    }
                    return response()->json($out);
                }
            }

            return response()->json([], 200);
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
        $limit   = min(5000, max(1, (int) $req->query("limit", 6)));

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
                'photo_url'  => (str_replace('http://', 'https://', $l->mainpicurl ?: $l->thumbnailurl ?: '') ?: null),
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
            'agent_take'               => (function () use ($building) {
                try {
                    $row = \Illuminate\Support\Facades\DB::table('building_agent_takes')->where('building_id', $building->id)->first();
                } catch (\Throwable $e) {
                    return null;
                }
                if (!$row) return null;
                $fields = [
                    'desirability'      => $row->desirability ?? null,
                    'buyer_profile'     => $row->buyer_profile ?? null,
                    'common_problems'   => $row->common_problems ?? null,
                    'value_take'        => $row->value_take ?? null,
                    'best_floorplans'   => $row->best_floorplans ?? null,
                    'view_preference'   => $row->view_preference ?? null,
                    'noise_notes'       => $row->noise_notes ?? null,
                    'rental_pet_appeal' => $row->rental_pet_appeal ?? null,
                ];
                $fields = array_map(function ($v) { return (!empty($v) && trim((string) $v) !== '') ? trim((string) $v) : null; }, $fields);
                $hasAny = count(array_filter($fields, function ($v) { return $v !== null; })) > 0;
                return $hasAny ? $fields : null;
            })(),
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

        $cacheKey = 'mkt_stats_v1_' . $slug;
        $result = \Illuminate\Support\Facades\Cache::remember($cacheKey, 1800, function () use ($cities, $msWhitelist) {
            $activeStats = null; $soldStats = null;
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

            return [
                'active_count'      => (int) ($activeStats ? ($activeStats->active_count ?? 0) : 0),
                'avg_list_price'    => ($activeStats && $activeStats->avg_list_price) ? (int) round($activeStats->avg_list_price) : null,
                'sold_last_30_days' => (int) ($soldStats ? ($soldStats->sold_count ?? 0) : 0),
                'avg_sold_price'    => ($soldStats && $soldStats->avg_sold_price) ? (int) round($soldStats->avg_sold_price) : null,
                'avg_dom'           => null,
            ];
        });
        return response()->json($result);
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
     * Anti-spam: IP rate limit (5/10 min), per-phone/email rate limit (3/30 min),
     *            honeypot field (website_url), phone digit sanity check.
     * Backup email to hello@suburbia.ca fires whenever lead has phone or email (Lofty failsafe).
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

        // Per-phone/email rate limiting: 3 submissions per 30 minutes per unique phone or email.
        $rawPhone = trim($req->input('phone', ''));
        $rawEmail = trim($req->input('email', ''));
        if ($rawPhone) {
            $phoneKey = 'contact_phone:' . hash('sha256', preg_replace('/\D/', '', $rawPhone));
            if (RateLimiter::tooManyAttempts($phoneKey, 3)) {
                \Illuminate\Support\Facades\Log::warning('Contact rate-limited by phone', ['agent' => $slug, 'ip' => $req->ip()]);
                return response()->json(['error' => 'Too many requests from this phone. Please try again later.'], 429);
            }
            RateLimiter::hit($phoneKey, 1800);
        }
        if ($rawEmail) {
            $emailKey = 'contact_email:' . hash('sha256', strtolower($rawEmail));
            if (RateLimiter::tooManyAttempts($emailKey, 3)) {
                \Illuminate\Support\Facades\Log::warning('Contact rate-limited by email', ['agent' => $slug, 'ip' => $req->ip()]);
                return response()->json(['error' => 'Too many requests from this email. Please try again later.'], 429);
            }
            RateLimiter::hit($emailKey, 1800);
        }

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
            // The building / neighbourhood / school the offer was scoped to.
            // LeadOfferCapture has always sent this; it was absent from this validator,
            // so Laravel stripped it and every agent received "someone wants weekly
            // deals" with no area attached. The intent is the point of the lead.
            'offer_context'    => 'nullable|string|max:150',
            'source_url'       => 'nullable|string|max:500',
            'notes'            => 'nullable|string|max:5000',
            'agree'            => 'nullable|boolean',
            'website_url'      => 'nullable|string|max:200',
            'source_type'      => 'nullable|string|max:20',
            'listing_id'       => 'nullable|string|max:40',
            'building_slug'    => 'nullable|string|max:100',
            'subarea'          => 'nullable|string|max:120',
            'property_type'    => 'nullable|string|max:60',
            'min_beds'         => 'nullable|integer',
            'min_price'        => 'nullable|integer',
            'max_price'        => 'nullable|integer',
        ]);

        // Honeypot: bots fill all visible-looking fields; humans never see this hidden field.
        if (!empty($data['website_url'])) {
            \Illuminate\Support\Facades\Log::warning('Contact honeypot triggered', ['agent' => $slug, 'ip' => $req->ip()]);
            return response()->json(['success' => true]); // silent reject
        }

        // Phone sanity check: must have >=7 digits and must not be all the same digit.
        if (!empty($data['phone'])) {
            $digits = preg_replace('/\D/', '', $data['phone']);
            if (strlen($digits) < 7 || strlen(count_chars($digits, 3)) === 1) {
                \Illuminate\Support\Facades\Log::warning('Contact invalid phone rejected', ['agent' => $slug, 'phone' => $data['phone']]);
                return response()->json(['error' => 'Please provide a valid phone number.'], 422);
            }
        }

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
        // Fold the offer context into message rather than adding a column: every CRM
        // mapping already forwards message, so the intent reaches FUB/GHL/Lofty and the
        // agent email with no downstream change.
        $offerContext = $data['offer_context'] ?? null;
        if ($offerContext) {
            $leadMessage = ($leadMessage ? $leadMessage . "\n" : '') . 'Interested in: ' . $offerContext;
        }

        \Illuminate\Support\Facades\DB::table('agent_leads')->insert([
            'agent_id'   => $agent->id,
            'form_type'  => $data['form_type'] ?? 'contact',
            'name'       => $data['name'] ?? '',
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $data['email'] ?? null,
            'phone'      => $data['phone'] ?? null,
            'message'       => $leadMessage,
            'offer_context' => $offerContext,
            'source_url'    => $data['source_url'] ?? null,
            'ip_hash'    => hash('sha256', $req->ip() ?? ''),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Build notification email body (shared by agent notification + platform backup).
        $contactFormType = $data['form_type'] ?? 'contact';
        $notifyEmail = $agent->settings?->notification_email ?: $agent->email;

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

        $ctxSrcLabel = (static function ($d) {
            $sType = $d['source_type'] ?? null;
            $lid   = $d['listing_id']   ?? null;
            $bslug = $d['building_slug'] ?? null;
            $area  = $d['subarea']       ?? null;
            $ptype = $d['property_type'] ?? null;
            $beds  = isset($d['min_beds'])  && $d['min_beds']  ? (int) $d['min_beds']  : null;
            $minP  = isset($d['min_price']) && $d['min_price'] ? (int) $d['min_price'] : null;
            $maxP  = isset($d['max_price']) && $d['max_price'] ? (int) $d['max_price'] : null;
            $sUrl  = $d['source_url'] ?? null;
            if ($sType === 'listing' && $lid)    return "Listing page: {$lid}";
            if ($sType === 'building' && $bslug) return 'Building page: ' . ucwords(str_replace('-', ' ', $bslug));
            if ($sType === 'search') {
                $pricePart = null;
                if ($minP || $maxP) {
                    $pricePart = ($minP ? '$' . number_format((int) ($minP / 1000)) . 'k' : '')
                        . ($minP && $maxP ? "\xe2\x80\x93" : '')
                        . ($maxP ? '$' . number_format((int) ($maxP / 1000)) . 'k' : '');
                }
                $parts = array_filter([$area, $beds ? "{$beds}+ bed" : null, $ptype, $pricePart]);
                return $parts ? implode(" \xc2\xb7 ", $parts) : ($sUrl ? (@parse_url($sUrl)['path'] ?? $sUrl) : "\xe2\x80\x94");
            }
            if ($sUrl) {
                $parsed = @parse_url($sUrl);
                return $parsed['path'] ?? $sUrl;
            }
            return "\xe2\x80\x94";
        })($data);
        $body = "New lead [{$typeLabel}] from {$siteDomain}\n"
            . str_repeat('-', 44) . "\n"
            . "Name:     {$nameDisplay}\n"
            . "Phone:    " . ($data['phone'] ?? "\xe2\x80\x94") . "\n"
            . "Email:    " . ($data['email'] ?? "\xe2\x80\x94") . "\n"
            . "Property: " . ($propertyAddress ?? "\xe2\x80\x94") . "\n"
            . "Message:  " . ($leadMessage ?? "\xe2\x80\x94") . "\n"
            . "Source:   {$ctxSrcLabel}\n"
            . $notesBlock
            . str_repeat('-', 44) . "\n"
            . "View leads: website.pixilink.com/admin/agents/{$agent->id}/leads\n";

        // Agent notification email (non-blocking — lead is already saved).
        if ($notifyEmail) {
            try {
                \Illuminate\Support\Facades\Mail::raw(
                    $body,
                    fn ($m) => $m->to($notifyEmail)->subject("[{$typeLabel}] New Lead \xe2\x80\x94 {$subjectName}")
                );
            } catch (\Throwable $mailErr) {
                \Illuminate\Support\Facades\Log::warning('Contact mail failed', ['err' => $mailErr->getMessage()]);
            }
        }

        // Platform backup email — fires whenever lead has phone or email (Lofty failsafe).
        if (!empty($data['phone']) || !empty($data['email'])) {
            try {
                \Illuminate\Support\Facades\Mail::raw(
                    $body,
                    fn ($m) => $m->to('hello@suburbia.ca')->subject("[{$typeLabel}] New Lead \xe2\x80\x94 {$subjectName}")
                );
            } catch (\Throwable $backupMailErr) {
                \Illuminate\Support\Facades\Log::warning('Contact backup mail failed', ['err' => $backupMailErr->getMessage()]);
            }
        }

        // SMS notification if enabled for this lead type.
        if ($agent->settings?->getNotifPref($contactFormType, 'sms') ?? false) {
            $smsPhone = $agent->settings?->notification_phone;
            if ($smsPhone && config('services.twilio.sid') && config('services.twilio.token')) {
                try {
                    $smsClient = new \Twilio\Rest\Client(
                        config('services.twilio.sid'),
                        config('services.twilio.token')
                    );
                    $smsBody = "New " . ucfirst($contactFormType) . " lead from "
                        . ($data['name'] ?? 'visitor') . ". Reply STOP to opt out.";
                    $smsClient->messages->create($smsPhone, [
                        'from' => config('services.twilio.from'),
                        'body' => $smsBody,
                    ]);
                } catch (\Throwable $smsErr) {
                    \Illuminate\Support\Facades\Log::warning('Contact SMS failed', ['err' => $smsErr->getMessage()]);
                }
            }
        }

        // CRM push — fire after email/SMS, failures never block the response.
        $pipelineData = [
            'name'             => $data['name'] ?? '',
            'first_name'       => $firstName ?? null,
            'last_name'        => $lastName  ?? null,
            'email'            => $data['email']   ?? null,
            'phone'            => $data['phone']   ?? null,
            'form_type'        => $data['form_type'] ?? 'contact',
            'message'          => $leadMessage ?? null,
            'property_address' => $propertyAddress ?? null,
            'source_url'       => $data['source_url'] ?? null,
        ];
        LeadPipeline::pushToFollowUpBoss($agent, $pipelineData);
        LeadPipeline::pushToGoHighLevel($agent, $pipelineData);
        LeadPipeline::pushToLofty($agent, $pipelineData);

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
            // -- Burnaby subareas ------------------------------------------------
            'Simon Fraser Univer.'    => 'simon-fraser-univer',
            'Capitol Hill BN'         => 'capitol-hill-bn',
            'Edmonds BE'              => 'edmonds-be',
            'Westridge BN'            => 'westridge-bn',
            'Forest Glen BS'          => 'forest-glen-bs',
            'Central Park BS'         => 'central-park-bs',
            'Central BN'              => 'central-bn',
            'Forest Hills BN'         => 'forest-hills-bn',
            'Sperling-Duthie'         => 'sperling-duthie',
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
            // -- Burnaby subareas ------------------------------------------------
            'simon-fraser-univer'     => ['Simon Fraser Univer.'],
            'capitol-hill-bn'         => ['Capitol Hill BN'],
            'edmonds-be'              => ['Edmonds BE'],
            'westridge-bn'            => ['Westridge BN'],
            'forest-glen-bs'          => ['Forest Glen BS'],
            'central-park-bs'         => ['Central Park BS'],
            'central-bn'              => ['Central BN'],
            'forest-hills-bn'         => ['Forest Hills BN'],
            'sperling-duthie'         => ['Sperling-Duthie'],
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
            // -- Burnaby subareas ------------------------------------------------
            'simon-fraser-univer'     => ['Burnaby',    'Simon Fraser Univer.'],
            'capitol-hill-bn'         => ['Burnaby',    'Capitol Hill BN'],
            'edmonds-be'              => ['Burnaby',    'Edmonds BE'],
            'westridge-bn'            => ['Burnaby',    'Westridge BN'],
            'forest-glen-bs'          => ['Burnaby',    'Forest Glen BS'],
            'central-park-bs'         => ['Burnaby',    'Central Park BS'],
            'central-bn'              => ['Burnaby',    'Central BN'],
            'forest-hills-bn'         => ['Burnaby',    'Forest Hills BN'],
            'sperling-duthie'         => ['Burnaby',    'Sperling-Duthie'],
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
                'photo_url'  => (str_replace('http://', 'https://', $l->mainpicurl ?: $l->thumbnailurl ?: '') ?: null),
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
                'photo_url'  => (str_replace('http://', 'https://', $l->mainpicurl ?: $l->thumbnailurl ?: '') ?: null),
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
            'photo_url'  => (str_replace('http://', 'https://', $l->mainpicurl ?: $l->thumbnailurl ?: '') ?: null),
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


        // Neighbourhood Content (lifestyle narrative + weekly pulse)
        $lifestyleBody    = null;
        $pulseBlurb       = null;
        $pulseGeneratedAt = null;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('neighbourhood_content')) {
                $nc = DB::table('neighbourhood_content')
                    ->where('agent_id', $agent->id)
                    ->where('subarea', $primarySubarea)
                    ->first();
                if ($nc) {
                    $lifestyleBody    = $nc->lifestyle_body ?: null;
                    $pulseBlurb       = $nc->pulse_body ?: null;
                    $pulseGeneratedAt = ($nc->pulse_generated_at && $nc->pulse_generated_at !== '0000-00-00 00:00:00') ? $nc->pulse_generated_at : null;
                }
            }
        } catch (\Throwable $ncErr) {
            \Log::warning('neighbourhoodDetail content fetch failed: ' . $ncErr->getMessage());
        }

        // Active listings new per month (for monthly_trend active column)
        $activeByMonth = [];
        try {
            $activeMonthly = Listings::withoutGlobalScopes()
                ->whereIn('subarea', $querySubareas)
                ->where('status', 'Active')
                ->whereNotNull('list_date')
                ->where('list_date', '>=', now()->subMonths(12)->format('Y-m-d'))
                ->selectRaw("DATE_FORMAT(list_date,'%Y-%m') as month, COUNT(*) as cnt")
                ->groupBy('month')
                ->get();
            foreach ($activeMonthly as $am) {
                $activeByMonth[$am->month] = (int) $am->cnt;
            }
        } catch (\Throwable $e) { /* non-fatal */ }

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
            'lifestyle_body'     => $lifestyleBody,
            'pulse_body'         => $pulseBlurb,
            'pulse_generated_at' => $pulseGeneratedAt,
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
            // -- Burnaby subareas ------------------------------------------------
            'simon-fraser-univer' => ['Simon Fraser Univer.'],
            'capitol-hill-bn' => ['Capitol Hill BN'],
            'edmonds-be' => ['Edmonds BE'],
            'westridge-bn' => ['Westridge BN'],
            'forest-glen-bs' => ['Forest Glen BS'],
            'central-park-bs' => ['Central Park BS'],
            'central-bn' => ['Central BN'],
            'forest-hills-bn' => ['Forest Hills BN'],
            'sperling-duthie' => ['Sperling-Duthie'],
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
     * Price-reduction / sold-price narrative for filtered listing pages.
     * Finds ONE real qualifying listing (never fabricated) — prefers a Sold listing
     * whose original list price was above its sold price, falls back to an Active
     * listing with a live price reduction. Reduction count comes from real
     * boards.price_history rows (Listings::get_price_history(), change < 0), so if
     * that history can't be verified we return null rather than guess a count.
     */
    public function priceStory(string $slug, \Illuminate\Http\Request $req): JsonResponse
    {
        $agent = Agent::with(['territories', 'settings'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(null);

        $subareaSlug = $req->query('subarea');
        $slugMap = [
            'south-surrey-white-rock' => ['South Surrey White Rock', 'South Surrey'],
            'white-rock' => ['White Rock'], 'cloverdale' => ['Cloverdale BC','Cloverdale'],
            'morgan-creek' => ['Morgan Creek'], 'grandview' => ['Grandview Surrey','Grandview Heights'],
            'ocean-park' => ['Ocean Park Surrey','Ocean Park'], 'semiahmoo' => ['Semiahmoo'],
            'crescent-bch-ocean-pk' => ['Crescent Bch Ocean Pk.'],
            'crescent-beach'        => ['Crescent Bch Ocean Pk.'],
            'sunnyside-park-surrey' => ['Sunnyside Park Surrey'],
            'sunnyside-park' => ['Sunnyside Park Surrey'],
            'elgin-chantrell' => ['Elgin Chantrell'],
            'hazelmere' => ['Hazelmere'],
            'king-george-corridor' => ['King George Corridor'],
            'pacific-douglas' => ['Pacific Douglas'],
            'rosemary-heights' => ['Rosemary Hgts'],
            'fleetwood-tynehead' => ['Fleetwood Tynehead'],
            'clayton' => ['Clayton'],
            'brookswood' => ['Brookswood Langley'],
            'south-surrey' => ['South Surrey White Rock'],
            // -- Burnaby subareas ------------------------------------------------
            'simon-fraser-univer' => ['Simon Fraser Univer.'],
            'simon-fraser-univ' => ['Simon Fraser Univer.'],
            'capitol-hill-bn' => ['Capitol Hill BN'],
            'edmonds-be' => ['Edmonds BE'],
            'edmonds' => ['Edmonds BE'],
            'westridge-bn' => ['Westridge BN'],
            'forest-glen-bs' => ['Forest Glen BS'],
            'central-park-bs' => ['Central Park BS'],
            'central-bn' => ['Central BN'],
            'forest-hills-bn' => ['Forest Hills BN'],
            'sperling-duthie' => ['Sperling-Duthie'],
            'metrotown' => ['Metrotown'],
            'brentwood-park' => ['Brentwood Park'],
            'highgate' => ['Highgate'],
            'south-slope' => ['South Slope'],
        ];
        $querySubareas = null;
        if ($subareaSlug) {
            $querySubareas = $slugMap[$subareaSlug] ?? [ucwords(str_replace('-', ' ', $subareaSlug))];
        }

        // Authorise: restrict to agent's subarea whitelist when set.
        $whitelist = null;
        if ($agent->settings) {
            $raw = $agent->settings->subarea_whitelist;
            if ($raw) {
                $wl = is_array($raw) ? $raw : json_decode($raw, true);
                if (is_array($wl) && count($wl) > 0) $whitelist = $wl;
            }
        }
        if ($querySubareas && $whitelist) {
            $querySubareas = array_values(array_intersect($querySubareas, $whitelist));
            if (empty($querySubareas)) return response()->json(null);
        } elseif (! $querySubareas && $whitelist) {
            $querySubareas = $whitelist;
        }

        $buildBase = function () use ($querySubareas, $agent) {
            $q = Listings::withoutGlobalScopes();
            if ($querySubareas) {
                $q->whereIn('subarea', $querySubareas);
            } else {
                $cities = $agent->territories->pluck('city')->filter()->unique()->values()->all();
                if (! empty($cities)) $q->whereIn('city', $cities);
            }
            return $q;
        };

        $findWithHistory = function ($listings, bool $isSold) {
            foreach ($listings as $listing) {
                $reductionCount = 0;
                try {
                    $reductionCount = count($listing->get_price_history());
                } catch (\Throwable $e) {
                    $reductionCount = 0;
                }
                if ($reductionCount < 1) continue;

                $originalPrice = (int) $listing->original_price;
                $finalPrice = $isSold ? (int) $listing->soldprice_2 : (int) $listing->listprice_2;
                if ($originalPrice <= 0 || $finalPrice <= 0 || $finalPrice >= $originalPrice) continue;

                return [
                    'mls_no'          => $listing->listingid,
                    'slug'            => $listing->slug,
                    'address'         => $listing->streetaddress,
                    'subarea'         => $listing->subarea,
                    'status'          => $isSold ? 'Sold' : 'Active',
                    'original_price'  => $originalPrice,
                    'reduction_count' => $reductionCount,
                    'final_price'     => $finalPrice,
                ];
            }
            return null;
        };

        // Prefer a Sold listing with a verifiable reduction; fall back to Active.
        $soldCandidates = $buildBase()
            ->where('status', 'Sold')
            ->whereNotNull('sold_date')
            ->whereColumn('original_price', '>', 'soldprice_2')
            ->where('soldprice_2', '>', 0)
            ->orderByDesc('sold_date')
            ->limit(60)
            ->get();

        $story = $findWithHistory($soldCandidates, true);

        if (! $story) {
            $activeCandidates = $buildBase()
                ->where('status', 'Active')
                ->whereColumn('original_price', '>', 'listprice_2')
                ->where('listprice_2', '>', 0)
                ->orderByDesc('list_date')
                ->limit(60)
                ->get();
            $story = $findWithHistory($activeCandidates, false);
        }

        return response()->json($story);
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
            // -- Burnaby subareas ------------------------------------------------
            'simon-fraser-univer' => ['Simon Fraser Univer.'],
            'capitol-hill-bn' => ['Capitol Hill BN'],
            'edmonds-be' => ['Edmonds BE'],
            'westridge-bn' => ['Westridge BN'],
            'forest-glen-bs' => ['Forest Glen BS'],
            'central-park-bs' => ['Central Park BS'],
            'central-bn' => ['Central BN'],
            'forest-hills-bn' => ['Forest Hills BN'],
            'sperling-duthie' => ['Sperling-Duthie'],
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

        $reportCacheKey = 'mkt_report_v1_' . $slug . '_' . ($subarea ?? '');
        $result = \Illuminate\Support\Facades\Cache::remember($reportCacheKey, 1800, function () use ($cities, $subarea, $subareaWhitelist) {
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
        return [
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
            ])->values()->all(),
        ];
        });
        return response()->json($result);
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

        $q = $this->agentBuildingsScope($cities, $abWhitelist)
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
        if (!empty($tagFilter)) {
            $matchIds = $this->aiTagMatchIds('building_ai_tags', 'building_id', $tagFilter);
            $q->whereIn('id', $matchIds);
        }

        $total = (clone $q)->count();

        $buildings = $q->orderByDesc('yearbuilt')
            ->forPage($page, $limit)
            ->get();

        $amenityTagsMap = $this->fetchAiTagsMap('building_ai_tags', 'building_id', $buildings->pluck('id')->map(fn ($v) => (string) $v)->toArray());

        return response()->json([
            'buildings' => $buildings->map(function (Buildings $b) use ($amenityTagsMap) {
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
                    'amenity_tags'    => $amenityTagsMap[(string) $b->id] ?? [],
                ];
            })->values(),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Persona tags offered by the batch-generate UI. Kept in sync with
     * PERSONA_TAG_LABELS in
     * pixilink-web/src/app/admin/buildings/batch-generate/page.tsx.
     *
     * Amenity tags (air_conditioning, panel_fridge, gas_appliances,
     * electric_appliances) are deliberately excluded: the UI does not treat a
     * building as tagged on the strength of those alone, so counting them here
     * would report work as done that the queue still offers.
     */
    private const BUILDING_PERSONA_TAGS = [
        'elevator', 'one-level-living', 'age-55-plus', 'low-strata-fee',
        'small-complex', 'pet-friendly', 'luxury-finishes', 'custom-millwork',
        'spa-ensuite', 'high-end-renovation', 'designer-kitchen',
        'high-end-appliances', 'sub-zero', 'wolf', 'viking', 'miele',
        'thermador', 'fisher-paykel', 'bosch',
    ];

    /**
     * The set of buildings an agent's admin tools operate on: every building in
     * the agent's territory cities, narrowed by the subarea whitelist when one
     * is configured.
     *
     * Shared with adminAgentBuildingsStats() so the coverage numbers cannot
     * drift away from the queue they describe.
     */
    private function agentBuildingsScope(array $cities, ?array $subareaWhitelist)
    {
        return Buildings::whereIn('city', $cities)
            ->when(!empty($subareaWhitelist), function ($query) use ($subareaWhitelist) {
                $query->whereIn('subarea', $subareaWhitelist);
            });
    }

    /**
     * Generation coverage for an agent's buildings.
     * GET /api-internal/admin/agents/{id}/buildings/stats
     *
     * Answers "how many in total, how many generated, how many left" for each
     * mode the batch-generate page offers. COUNT queries only -- no row
     * hydration and none of the per-building listing aggregates that make
     * adminAgentBuildings() expensive -- so it is cheap enough to refetch after
     * every run.
     *
     * The "generated" definitions mirror the missing_only and
     * missing_features_only filters in adminAgentBuildings() exactly, so
     * `remaining` always equals the number of rows that endpoint would return.
     */
    public function adminAgentBuildingsStats(Request $req, int $agentId): JsonResponse
    {
        $agent = Agent::with(['territories'])->where('id', $agentId)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $empty  = ['generated' => 0, 'remaining' => 0];
        $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        if (empty($cities)) {
            return response()->json([
                'total' => 0, 'cities' => [], 'subarea_whitelist_count' => 0,
                'description' => $empty, 'features' => $empty, 'tags' => $empty,
            ]);
        }

        $settingsRow = \Illuminate\Support\Facades\DB::table('agent_settings')
            ->where('agent_id', $agent->id)->first();
        $whitelist = ($settingsRow && $settingsRow->subarea_whitelist)
            ? json_decode($settingsRow->subarea_whitelist, true) : null;

        $total = $this->agentBuildingsScope($cities, $whitelist)->count();

        // Missing a description == tagline AND description both blank, matching
        // the missing_only filter in adminAgentBuildings().
        $missingDescription = $this->agentBuildingsScope($cities, $whitelist)
            ->where(function ($q) { $q->whereNull('tagline')->orWhere('tagline', ''); })
            ->where(function ($q) { $q->whereNull('description')->orWhere('description', ''); })
            ->count();

        $missingFeatures = $this->agentBuildingsScope($cities, $whitelist)
            ->where(function ($q) { $q->whereNull('ai_features_json')->orWhere('ai_features_json', ''); })
            ->count();

        // Tags live in a JSON array inside a longtext column, so they are
        // counted in PHP. Only buildings carrying at least one persona tag count
        // as generated -- a row holding only amenity tags, or an empty array,
        // is still outstanding work.
        $ids = $this->agentBuildingsScope($cities, $whitelist)
            ->pluck('id')->map(fn ($v) => (string) $v)->toArray();
        $tagged = 0;
        foreach ($this->fetchAiTagsMap('building_ai_tags', 'building_id', $ids) as $tags) {
            if (array_intersect($tags, self::BUILDING_PERSONA_TAGS)) {
                $tagged++;
            }
        }

        return response()->json([
            'total'                   => $total,
            'cities'                  => $cities,
            'subarea_whitelist_count' => is_array($whitelist) ? count($whitelist) : 0,
            'description' => ['generated' => $total - $missingDescription, 'remaining' => $missingDescription],
            'features'    => ['generated' => $total - $missingFeatures,    'remaining' => $missingFeatures],
            'tags'        => ['generated' => $tagged,                      'remaining' => $total - $tagged],
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
                'lofty_enabled'        => (bool) ($settings->lofty_enabled ?? false),
                'ghl_api_key'          => $settings->ghl_api_key ?? null,
                'lead_routing'         => $settings->lead_routing,
                'seo_noindex'          => (bool) ($settings->seo_noindex ?? false),
                'subarea_whitelist'    => $settings->subarea_whitelist ?? null,
                'team_members'         => $settings->team_members
                    ? (is_array($settings->team_members)
                        ? $settings->team_members
                        : json_decode($settings->team_members, true))
                    : null,
                'achievements'         => $settings->achievements
                    ? (is_array($settings->achievements)
                        ? $settings->achievements
                        : json_decode($settings->achievements, true))
                    : null,
                'co_agent_achievements' => $settings->co_agent_achievements
                    ? (is_array($settings->co_agent_achievements)
                        ? $settings->co_agent_achievements
                        : json_decode($settings->co_agent_achievements, true))
                    : null,
                'hero_stats'           => $settings->hero_stats
                    ? (is_array($settings->hero_stats)
                        ? $settings->hero_stats
                        : json_decode($settings->hero_stats, true))
                    : null,
                'favicon_url'          => $settings->favicon_url ?? null,
                'guide_name'           => $settings->guide_name ?? null,
                'licensed_since'       => $settings->licensed_since ? (int) $settings->licensed_since : null,
                'languages'            => $settings->languages ?? null,
                'site_config'          => $settings->site_config
                    ? (is_array($settings->site_config)
                        ? $settings->site_config
                        : json_decode($settings->site_config, true))
                    : null,
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
            'fub_enabled'   => (bool) ($settings?->fub_enabled   ?? false),
            'ghl_enabled'   => (bool) ($settings?->ghl_enabled   ?? false),
            'lofty_enabled' => (bool) ($settings?->lofty_enabled ?? false),
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
            'ghl_enabled'   => 'nullable|boolean',
            'ghl_api_key'   => 'nullable|string|max:200',
            'lofty_enabled' => 'nullable|boolean',
            'lofty_api_key' => 'nullable|string|max:600',
        ]);
        $settings->ga4_id      = $data['ga4_id'] ?? null;
        $settings->fub_enabled = (bool) ($data['fub_enabled'] ?? false);
        $settings->ghl_enabled   = (bool) ($data['ghl_enabled']   ?? false);
        $settings->lofty_enabled = (bool) ($data['lofty_enabled'] ?? false);
        if (! empty($data['fub_api_key']))   { $settings->fub_api_key   = $data['fub_api_key'];   }
        if (! empty($data['ghl_api_key']))   { $settings->ghl_api_key   = $data['ghl_api_key'];   }
        if (! empty($data['lofty_api_key'])) { $settings->lofty_api_key = $data['lofty_api_key']; }
        $settings->save();
        return response()->json([
            'ga4_id'      => $settings->ga4_id,
            'fub_enabled'   => (bool) $settings->fub_enabled,
            'ghl_enabled'   => (bool) $settings->ghl_enabled,
            'lofty_enabled' => (bool) $settings->lofty_enabled,
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
        $row = \Illuminate\Support\Facades\DB::table('user_tokens')
            ->where('token', $hashed)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();
        return $row;
    }

    /**
     * Listing alerts. The frontend has always had a "Get price alerts" bell on every active
     * listing (ListingAlertButton.client.tsx) posting to these routes — but the routes did
     * not exist, so every call 404'd and the user's intent to watch that listing reached
     * nobody.
     *
     * The platform does not send alerts; the agent does that in their own CRM. So the honest
     * implementation is to record the INTENT as a lead, with the MLS attached, and let the
     * normal lead pipeline deliver it. Stored as an agent_lead rather than a new table so the
     * agent sees it alongside every other lead with no extra UI.
     */
    private function listingAlertLeadQuery(int $userId, string $agentId, string $mls)
    {
        return \Illuminate\Support\Facades\DB::table('agent_leads')
            ->where('agent_id', $agentId)
            ->where('form_type', 'listing_alert')
            ->where('user_id', $userId)
            ->where('listing_slug', $mls);
    }

    public function getListingAlert(\Illuminate\Http\Request $request, string $slug, string $mls): \Illuminate\Http\JsonResponse
    {
        $token = $this->getUserFromRequest($request);
        if (! $token) return response()->json(['subscribed' => false]);
        $agent = \App\Models\Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['subscribed' => false]);

        return response()->json([
            'subscribed' => $this->listingAlertLeadQuery($token->user_id, $agent->id, $mls)->exists(),
        ]);
    }

    public function addListingAlert(\Illuminate\Http\Request $request, string $slug): \Illuminate\Http\JsonResponse
    {
        $token = $this->getUserFromRequest($request);
        if (! $token) return response()->json(['error' => 'Unauthorized.'], 401);

        $data = $request->validate([
            'mls_num' => 'required|string|max:40',
            'address' => 'nullable|string|max:300',
        ]);

        $agent = \App\Models\Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found.'], 404);

        $u = \Illuminate\Support\Facades\DB::table('users')->where('id', $token->user_id)->first();
        if (! $u) return response()->json(['error' => 'Unauthorized.'], 401);

        // Idempotent: the bell can be toggled, and one lead per listing is the useful signal.
        if ($this->listingAlertLeadQuery($token->user_id, $agent->id, $data['mls_num'])->exists()) {
            return response()->json(['subscribed' => true]);
        }

        $message = 'Wants price alerts for MLS ' . $data['mls_num']
            . (! empty($data['address']) ? ' — ' . $data['address'] : '');

        \Illuminate\Support\Facades\DB::table('agent_leads')->insert([
            'agent_id'    => $agent->id,
            'form_type'   => 'listing_alert',
            'name'        => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
            'first_name'  => $u->first_name ?? null,
            'last_name'   => $u->last_name ?? null,
            'email'       => $u->email ?? null,
            'phone'       => $u->phone ?? null,
            'message'     => $message,
            'listing_slug' => $data['mls_num'],
            'user_id'      => $token->user_id,
            'ip_hash'     => hash('sha256', $request->ip() ?? ''),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $pipelineData = [
            'first_name' => $u->first_name ?? null,
            'last_name'  => $u->last_name ?? null,
            'email'      => $u->email ?? null,
            'phone'      => $u->phone ?? null,
            'message'    => $message,
            'form_type'  => 'listing_alert',
        ];
        \App\Services\LeadPipeline::pushToFollowUpBoss($agent, $pipelineData);
        \App\Services\LeadPipeline::pushToGoHighLevel($agent, $pipelineData);
        \App\Services\LeadPipeline::pushToLofty($agent, $pipelineData);

        return response()->json(['subscribed' => true]);
    }

    public function removeListingAlert(\Illuminate\Http\Request $request, string $slug, string $mls): \Illuminate\Http\JsonResponse
    {
        $token = $this->getUserFromRequest($request);
        if (! $token) return response()->json(['error' => 'Unauthorized.'], 401);
        $agent = \App\Models\Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found.'], 404);

        $this->listingAlertLeadQuery($token->user_id, $agent->id, $mls)->delete();
        return response()->json(['subscribed' => false]);
    }

    public function getFavourites(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $token = $this->getUserFromRequest($request);
        if (! $token) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }
        // Email verification gate: block accounts that registered but have not yet verified.
        $favUser = \Illuminate\Support\Facades\DB::table('users')->where('id', $token->user_id)->first();
        if (! $favUser || ! $favUser->email_verified_at) {
            return response()->json(['error' => 'Please verify your email address before saving favourites.'], 403);
        }
        $rows = \Illuminate\Support\Facades\DB::table('favorite_listings')
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
        // Email verification gate: block accounts that registered but have not yet verified.
        $favUser = \Illuminate\Support\Facades\DB::table('users')->where('id', $token->user_id)->first();
        if (! $favUser || ! $favUser->email_verified_at) {
            return response()->json(['error' => 'Please verify your email address before saving favourites.'], 403);
        }
        $mlsNo = trim($request->input('mls_no', ''));
        if (! $mlsNo) {
            return response()->json(['error' => 'mls_no is required.'], 422);
        }
        $existing = \Illuminate\Support\Facades\DB::table('favorite_listings')
            ->where('userid', $token->user_id)
            ->where('listingid', $mlsNo)
            ->first();
        if ($existing) {
            \Illuminate\Support\Facades\DB::table('favorite_listings')
                ->where('id', $existing->id)
                ->update(['deleted' => 0]);
        } else {
            \Illuminate\Support\Facades\DB::table('favorite_listings')->insert([
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
        // Email verification gate: block accounts that registered but have not yet verified.
        $favUser = \Illuminate\Support\Facades\DB::table('users')->where('id', $token->user_id)->first();
        if (! $favUser || ! $favUser->email_verified_at) {
            return response()->json(['error' => 'Please verify your email address before saving favourites.'], 403);
        }
        \Illuminate\Support\Facades\DB::table('favorite_listings')
            ->where('userid', $token->user_id)
            ->where('listingid', $mlsNo)
            ->update(['deleted' => 1]);
        return response()->json(['ok' => true]);
    }

    // ── Property view tracking — Bearer-token auth ──────────────────────────

    /**
     * POST /api-internal/user/property-view
     * Record (or increment) a logged-in user's view of a listing or building page.
     * One row per (user_id, listing_id) and (user_id, building_slug);
     * view_count increments at most once per calendar day.
     */
    public function recordPropertyView(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $token = $this->getUserFromRequest($request);
        if (! $token) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $listingId    = trim($request->input('listing_id', ''));
        $buildingSlug = trim($request->input('building_slug', ''));
        $addressLabel = trim($request->input('address_label', ''));

        if (! $listingId && ! $buildingSlug) {
            return response()->json(['error' => 'listing_id or building_slug required.'], 422);
        }
        if (! $addressLabel) {
            return response()->json(['error' => 'address_label required.'], 422);
        }

        try {
            $userId = $token->user_id;
            $today  = now()->toDateString();

            // Derive agent_id server-side from the user's registered agent affiliation.
            // Never trust client-supplied agent_id — it could be spoofed and would break
            // agent-portal scoping (portal queries ?agent_id=<session.id>).
            $agentId = \Illuminate\Support\Facades\DB::table('users')
                ->where('id', $userId)
                ->value('agent_id') ?: null;

            $label = mb_substr($addressLabel, 0, 255);

            if ($listingId) {
                // Atomic upsert: unique key on (user_id, listing_id) enforces one row per user+listing.
                // ON DUPLICATE KEY increments view_count at most once per calendar day.
                \Illuminate\Support\Facades\DB::statement(
                    'INSERT INTO lead_property_views
                        (agent_id, user_id, listing_id, building_slug, address_label, view_count, first_viewed_at, last_viewed_at)
                     VALUES (?, ?, ?, NULL, ?, 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE
                        view_count     = IF(DATE(last_viewed_at) < CURDATE(), view_count + 1, view_count),
                        last_viewed_at = IF(DATE(last_viewed_at) < CURDATE(), NOW(), last_viewed_at)',
                    [$agentId, $userId, $listingId, $label]
                );
            } else {
                \Illuminate\Support\Facades\DB::statement(
                    'INSERT INTO lead_property_views
                        (agent_id, user_id, listing_id, building_slug, address_label, view_count, first_viewed_at, last_viewed_at)
                     VALUES (?, ?, NULL, ?, ?, 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE
                        view_count     = IF(DATE(last_viewed_at) < CURDATE(), view_count + 1, view_count),
                        last_viewed_at = IF(DATE(last_viewed_at) < CURDATE(), NOW(), last_viewed_at)',
                    [$agentId, $userId, $buildingSlug, $label]
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('recordPropertyView error user=' . ($token->user_id ?? '?') . ': ' . $e->getMessage());
            return response()->json(['error' => 'Server error.'], 500);
        }

        // ── CRM push gate ─────────────────────────────────────────────────────
        // Fire at most once per (user, property) per 24 h, guarded by last_crm_push_at.
        // All errors are caught — CRM failure must never affect the 200 response.
        try {
            $hasLcpCol = \Illuminate\Support\Facades\Schema::hasColumn('lead_property_views', 'last_crm_push_at');
            if ($hasLcpCol && $agentId) {
                $viewQ = \Illuminate\Support\Facades\DB::table('lead_property_views')
                    ->where('user_id', $userId);
                if ($listingId) {
                    $viewQ->where('listing_id', $listingId);
                } else {
                    $viewQ->where('building_slug', $buildingSlug);
                }
                $viewRow = $viewQ->first();

                $shouldPush = $viewRow && (
                    is_null($viewRow->last_crm_push_at) ||
                    now()->diffInHours($viewRow->last_crm_push_at) >= 24
                );

                if ($shouldPush) {
                    $userRow    = \Illuminate\Support\Facades\DB::table('users')->where('id', $userId)->first();
                    $agentModel = \App\Models\Agent::with('settings')->find($agentId);

                    if ($userRow && $agentModel) {
                        LeadPipeline::pushPropertyViewEvent($agentModel, [
                            'email'         => $userRow->email ?? null,
                            'name'          => $userRow->name ?? trim(($userRow->first_name ?? '') . ' ' . ($userRow->last_name ?? '')),
                            'first_name'    => $userRow->first_name ?? '',
                            'last_name'     => $userRow->last_name ?? '',
                            'listing_id'    => $listingId ?: null,
                            'building_slug' => $buildingSlug ?: null,
                            'address_label' => $label,
                            'view_count'    => (int) ($viewRow->view_count ?? 1),
                        ]);

                        // Stamp the time so we skip for the next 24 h
                        $viewQ2 = \Illuminate\Support\Facades\DB::table('lead_property_views')
                            ->where('user_id', $userId);
                        if ($listingId) {
                            $viewQ2->where('listing_id', $listingId);
                        } else {
                            $viewQ2->where('building_slug', $buildingSlug);
                        }
                        $viewQ2->update(['last_crm_push_at' => now()]);
                    }
                }
            }
        } catch (\Throwable $crmErr) {
            \Illuminate\Support\Facades\Log::warning('recordPropertyView CRM push error: ' . $crmErr->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    /**
     * GET /api-internal/admin/leads/{userId}/property-views
     * Return property view history for a user.
     * Optional ?agent_id= to scope to a specific agent site.
     * Protected by VerifyAdminSecret middleware (applied to admin route group).
     */
    public function getLeadPropertyViews(\Illuminate\Http\Request $req, int $userId): \Illuminate\Http\JsonResponse
    {
        try {
            $q = \Illuminate\Support\Facades\DB::table('lead_property_views')
                ->where('user_id', $userId);
            if ($req->filled('agent_id')) {
                $agentId = (int) $req->query('agent_id');

                // Ownership gate: this user must be a registered lead for this agent.
                // Without this check an agent could enumerate view history for arbitrary user IDs.
                $hasUidCol = \Illuminate\Support\Facades\Schema::hasColumn('agent_leads', 'user_id');
                if ($hasUidCol) {
                    $isOwnLead = \Illuminate\Support\Facades\DB::table('agent_leads')
                        ->where('agent_id', $agentId)
                        ->where('user_id', $userId)
                        ->exists();
                    if (! $isOwnLead) {
                        return response()->json(['error' => 'Forbidden'], 403);
                    }
                }

                $q->where('agent_id', $agentId);
            }
            $views = $q->orderByDesc('last_viewed_at')
                ->limit(50)
                ->get()
                ->map(fn ($v) => [
                    'listing_id'      => $v->listing_id,
                    'building_slug'   => $v->building_slug,
                    'address_label'   => $v->address_label,
                    'view_count'      => (int) $v->view_count,
                    'first_viewed_at' => $v->first_viewed_at,
                    'last_viewed_at'  => $v->last_viewed_at,
                ]);
            return response()->json($views);
        } catch (\Throwable $e) {
            return response()->json([]);
        }
    }

    /** Allowed sold-gate event types. Must stay <= 20 chars (column width). */
    /**
     * Allowed funnel event types. Must stay <= 20 chars (sold_gate_events.event_type
     * is varchar(20)).
     *
     * The table is named for the sold gate but the shape is generic
     * (event_type / agent_slug / mls / subarea / created_at), so it also carries the
     * form-engagement events behind the daily funnel report. Reused rather than adding
     * a table: same columns, same endpoint, same admin aggregation.
     *
     *   register / login          - converted at the gate
     *   prompt_impression/dismiss - saw the gate prompt (the denominator)
     *   form_start                - began typing into a lead or registration form
     *   form_abandon              - left with something typed and nothing submitted
     *   otp_failed                - wrong verification code
     *   phone_invalid             - Twilio rejected the number outright
     */
    private const SOLD_GATE_EVENTS = [
        "register", "login", "prompt_impression", "prompt_dismiss",
        "form_start", "form_abandon", "otp_failed", "phone_invalid",
    ];

    public function recordSoldGateEvent(\Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $event = $req->input("event");
        // register/login are the conversions; prompt_impression/prompt_dismiss are the
        // denominator. Without an impression event a gate's conversion rate cannot be
        // computed at all, which is why the admin page reads "Requires page view
        // tracking". NB: sold_gate_events.event_type is varchar(20) - keep names short.
        if (!in_array($event, self::SOLD_GATE_EVENTS, true)) {
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
                $byAgent[$s] = ["slug" => $s, "register" => 0, "login" => 0, "prompt_impression" => 0, "prompt_dismiss" => 0];
            }
            $byAgent[$s][$row->event_type] = (int) $row->cnt;
        }

        return response()->json([
            "period_days"      => $days,
            "total_register"   => (int) ($totals["register"] ?? 0),
            "total_login"      => (int) ($totals["login"] ?? 0),
            "total_impression" => (int) ($totals["prompt_impression"] ?? 0),
            "total_dismiss"    => (int) ($totals["prompt_dismiss"] ?? 0),
            "by_agent"         => array_values($byAgent),
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
                $byDay[$d] = ["day" => $d, "register" => 0, "login" => 0, "prompt_impression" => 0, "prompt_dismiss" => 0];
            }
            $byDay[$d][$row->event_type] = (int) $row->cnt;
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->format("Y-m-d");
            $result[] = $byDay[$d] ?? ["day" => $d, "register" => 0, "login" => 0, "prompt_impression" => 0, "prompt_dismiss" => 0];
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
        if (!empty($tagFilter)) {
            $matchIds = $this->aiTagMatchIds('building_ai_tags', 'building_id', $tagFilter);
            $q->whereIn('id', $matchIds);
        }

        $total     = (clone $q)->count();
        $buildings = $q->orderByDesc('yearbuilt')->forPage($page, $limit)->get();

        $amenityTagsMap = $this->fetchAiTagsMap('building_ai_tags', 'building_id', $buildings->pluck('id')->map(fn ($v) => (string) $v)->toArray());

        return response()->json([
            'buildings' => $buildings->map(function (Buildings $b) use ($amenityTagsMap) {
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
                    'amenity_tags'    => $amenityTagsMap[(string) $b->id] ?? [],
                ];
            }),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Lazily create a persona AI-tag lookup table in the default (bccondosandhomes) DB connection.
     * NOTE: the app's DB user only has SELECT on `boards` and SELECT/INSERT/UPDATE/INDEX (no ALTER)
     * on `pixilink_mlsr` — so tags for listings/buildings cannot live as a column on those tables.
     * They are stored here instead and merged in-app by id.
     */
    private function ensureAiTagsTable(string $table, string $idColumn): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
            return;
        }
        \Illuminate\Support\Facades\Schema::create($table, function ($t) use ($idColumn) {
            if ($idColumn === 'building_id') {
                $t->string('building_id', 100)->primary();
            } else {
                $t->unsignedBigInteger('listing_id')->primary();
            }
            $t->longText('tags')->nullable();
            $t->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Fetch a map of id => decoded tags array for the given ids from an ai-tags lookup table.
     */
    private function fetchAiTagsMap(string $table, string $idColumn, array $ids): array
    {
        if (empty($ids) || !\Illuminate\Support\Facades\Schema::hasTable($table)) {
            return [];
        }
        $rows = \Illuminate\Support\Facades\DB::table($table)->whereIn($idColumn, $ids)->get([$idColumn, 'tags']);
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row->{$idColumn}] = $row->tags ? (json_decode($row->tags, true) ?: []) : [];
        }
        return $map;
    }

    /**
     * Return ids from an ai-tags lookup table whose tags match ANY of the given tags.
     */
    private function aiTagMatchIds(string $table, string $idColumn, array $tags): array
    {
        if (empty($tags) || !\Illuminate\Support\Facades\Schema::hasTable($table)) {
            return [];
        }
        return \Illuminate\Support\Facades\DB::table($table)
            ->where(function ($qb) use ($tags) {
                foreach ($tags as $t) {
                    $qb->orWhere('tags', 'LIKE', '%"' . addslashes($t) . '"%');
                }
            })
            ->pluck($idColumn)->toArray();
    }

    /**
     * POST /api-internal/admin/buildings/{id}/tags
     * Save amenity tags for a building.
     * Allowed: air_conditioning, panel_fridge, gas_appliances, electric_appliances.
     */
    public function adminSaveBuildingTags(Request $req, string $id): JsonResponse
    {
        $allowed = [
            'air_conditioning', 'panel_fridge', 'gas_appliances', 'electric_appliances',
            // Downsizers persona
            'elevator', 'one-level-living', 'age-55-plus', 'low-strata-fee', 'small-complex', 'pet-friendly',
            // Luxury Finishes persona
            'luxury-finishes', 'custom-millwork', 'spa-ensuite', 'high-end-renovation', 'designer-kitchen',
            // High-End Appliances persona
            'high-end-appliances', 'sub-zero', 'wolf', 'viking', 'miele', 'thermador', 'fisher-paykel', 'bosch',
        ];
        $tags = array_values(array_filter((array) $req->input('tags', []), function ($t) use ($allowed) {
            return in_array($t, $allowed, true);
        }));

        $exists = \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->table('buildings')->where('id', $id)->exists();
        if (!$exists) {
            return response()->json(['error' => 'Building not found'], 404);
        }

        $this->ensureAiTagsTable('building_ai_tags', 'building_id');
        \Illuminate\Support\Facades\DB::table('building_ai_tags')->updateOrInsert(
            ['building_id' => (string) $id],
            ['tags' => json_encode($tags), 'updated_at' => now()]
        );

        return response()->json(['ok' => true, 'tags' => $tags]);
    }

    /**
     * Persona/tag taxonomy shared by buildings.amenity_tags and listings.ai_tags.
     * Buildings additionally keep 4 legacy appliance-checkbox tags (not persona-scoped).
     */
    private function personaTagAllowlist(): array
    {
        return [
            'elevator', 'one-level-living', 'age-55-plus', 'low-strata-fee', 'small-complex', 'pet-friendly',
            'luxury-finishes', 'custom-millwork', 'spa-ensuite', 'high-end-renovation', 'designer-kitchen',
            'high-end-appliances', 'sub-zero', 'wolf', 'viking', 'miele', 'thermador', 'fisher-paykel', 'bosch',
        ];
    }

    private function personaGroups(): array
    {
        return [
            'downsizer-homes' => ['elevator', 'one-level-living', 'age-55-plus', 'low-strata-fee', 'small-complex', 'pet-friendly'],
            'luxury-finishes-homes' => ['luxury-finishes', 'custom-millwork', 'spa-ensuite', 'high-end-renovation', 'designer-kitchen'],
            'high-end-appliance-homes' => ['high-end-appliances', 'sub-zero', 'wolf', 'viking', 'miele', 'thermador', 'fisher-paykel', 'bosch'],
        ];
    }

    /**
     * POST /api-internal/admin/listings/{id}/tags
     * Save AI-derived persona tags for a listing (house or condo).
     */
    public function adminSaveListingTags(Request $req, int $id): JsonResponse
    {
        $allowed = $this->personaTagAllowlist();
        $tags = array_values(array_filter((array) $req->input('tags', []), function ($t) use ($allowed) {
            return in_array($t, $allowed, true);
        }));

        $exists = \Illuminate\Support\Facades\DB::connection('mysql_boards')->table('listings')->where('id', $id)->exists();
        if (!$exists) {
            return response()->json(['error' => 'Listing not found'], 404);
        }

        $this->ensureAiTagsTable('listing_ai_tags', 'listing_id');
        \Illuminate\Support\Facades\DB::table('listing_ai_tags')->updateOrInsert(
            ['listing_id' => $id],
            ['tags' => json_encode($tags), 'updated_at' => now()]
        );

        return response()->json(['ok' => true, 'tags' => $tags]);
    }

    /**
     * Shared listing-list query builder used by adminAgentListings / adminListingsList.
     * ai_tags are stored in the separate `listing_ai_tags` table (see ensureAiTagsTable) and
     * merged in by id after the page is fetched; use aiTagMatchIds for tag-filtering.
     */
    private function adminListingsQuery(Request $req)
    {
        $q = Listings::withoutGlobalScopes()
            ->where('status', 'Active')
            ->select([
                'id', 'sysid', 'listingid', 'streetaddress', 'city', 'subarea',
                'listingtype', 'bedrooms', 'bathstotal', 'listprice_2', 'strata_no',
                'remarks', 'features', 'amenity', 'mainpicurl', 'slug',
            ]);

        if ($city = $req->query('city'))    $q->where('city', $city);
        if ($subarea = $req->query('subarea')) $q->where('subarea', $subarea);

        if ($req->query('missing_only')) {
            $taggedIds = \Illuminate\Support\Facades\Schema::hasTable('listing_ai_tags')
                ? \Illuminate\Support\Facades\DB::table('listing_ai_tags')
                    ->whereNotNull('tags')->where('tags', '!=', '')->where('tags', '!=', '[]')
                    ->pluck('listing_id')->toArray()
                : [];
            if (!empty($taggedIds)) {
                $q->whereNotIn('id', $taggedIds);
            }
        }

        $tagFilter = array_filter((array) $req->query('tags', []));
        if (!empty($tagFilter)) {
            $matchIds = $this->aiTagMatchIds('listing_ai_tags', 'listing_id', $tagFilter);
            $q->whereIn('id', $matchIds);
        }

        return $q;
    }

    private function formatAdminListingRow($l, array $tagsMap = []): array
    {
        return [
            'id'          => (string) $l->id,
            'listingid'   => $l->listingid,
            'address'     => $l->streetaddress,
            'city'        => $l->city,
            'subarea'     => $l->subarea,
            'type'        => $l->listingtype,
            'bedrooms'    => $l->bedrooms !== null ? (int) $l->bedrooms : null,
            'baths'       => $l->bathstotal !== null ? (float) $l->bathstotal : null,
            'price'       => $l->listprice_2 ? (int) $l->listprice_2 : null,
            'strata_no'   => $l->strata_no ?: null,
            'remarks'     => $l->remarks,
            'features'    => $l->features,
            'amenity'     => $l->amenity,
            'photo_url'   => $l->mainpicurl ?: null,
            'slug'        => $l->slug,
            'ai_tags'     => $tagsMap[(string) $l->id] ?? [],
        ];
    }

    /**
     * Admin: global listings list, all agents/territories, paginated.
     * GET /api-internal/admin/listings
     */
    public function adminListingsList(Request $req): JsonResponse
    {
        $page  = max(1, (int) $req->query('page', 1));
        $limit = min(200, max(1, (int) $req->query('limit', 50)));

        $q = $this->adminListingsQuery($req);

        $total    = (clone $q)->count();
        $listings = $q->orderByDesc('list_date')->forPage($page, $limit)->get();
        $tagsMap  = $this->fetchAiTagsMap('listing_ai_tags', 'listing_id', $listings->pluck('id')->map(fn ($v) => (string) $v)->toArray());

        return response()->json([
            'listings' => $listings->map(fn ($l) => $this->formatAdminListingRow($l, $tagsMap)),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Admin: territory listings for a specific agent, paginated.
     * GET /api-internal/admin/agents/{agentId}/listings
     */
    public function adminAgentListings(Request $req, int $agentId): JsonResponse
    {
        $agent = Agent::with(['territories'])->where('id', $agentId)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        if (empty($cities)) return response()->json(['listings' => [], 'total' => 0, 'page' => 1, 'limit' => 100]);

        $limit = min(200, max(1, (int) $req->query('limit', 100)));
        $page  = max(1, (int) $req->query('page', 1));

        $abSettingsRow = \Illuminate\Support\Facades\DB::table('agent_settings')
            ->where('agent_id', $agent->id)->first();
        $abWhitelist = ($abSettingsRow && $abSettingsRow->subarea_whitelist)
            ? json_decode($abSettingsRow->subarea_whitelist, true) : null;

        $q = $this->adminListingsQuery($req);
        $q->whereIn('city', $cities);
        if (!empty($abWhitelist)) $q->whereIn('subarea', $abWhitelist);

        $total = (clone $q)->count();
        $listings = $q->orderByDesc('list_date')->forPage($page, $limit)->get();
        $tagsMap  = $this->fetchAiTagsMap('listing_ai_tags', 'listing_id', $listings->pluck('id')->map(fn ($v) => (string) $v)->toArray());

        return response()->json([
            'listings' => $listings->map(fn ($l) => $this->formatAdminListingRow($l, $tagsMap))->values(),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * GET /api-internal/agent/{slug}/persona/{persona}
     * Territory-scoped, tag-matched listings for a persona (downsizer-homes, luxury-finishes-homes, high-end-appliance-homes).
     * Qualification: listing.ai_tags matches ANY persona tag, OR listing's building (via strata_no) has amenity_tags matching ANY persona tag.
     * Optional ?subarea= to scope to a single area (used by area sub-pages); omit for the agent-wide hub.
     */
    public function personaListings(string $slug, string $persona, Request $req): JsonResponse
    {
        $groups = $this->personaGroups();
        if (!isset($groups[$persona])) {
            return response()->json(['error' => 'Unknown persona'], 404);
        }
        $tags = $groups[$persona];

        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities = \Illuminate\Support\Facades\DB::table('agent_territories')
            ->where('agent_id', $agent->id)->pluck('city')->filter()->unique()->values()->toArray();
        if (empty($cities)) {
            return response()->json(['listings' => [], 'areas' => [], 'total' => 0]);
        }

        $abSettingsRow = \Illuminate\Support\Facades\DB::table('agent_settings')
            ->where('agent_id', $agent->id)->first();
        $abWhitelist = ($abSettingsRow && $abSettingsRow->subarea_whitelist)
            ? json_decode($abSettingsRow->subarea_whitelist, true) : null;

        $qualifyingListingIds = $this->aiTagMatchIds('listing_ai_tags', 'listing_id', $tags);

        $qualifyingStratas = [];
        $qualifyingBuildingIds = $this->aiTagMatchIds('building_ai_tags', 'building_id', $tags);
        if (!empty($qualifyingBuildingIds)) {
            $qualifyingStratas = Buildings::withoutGlobalScopes()->whereNull('deleted_at')
                ->whereIn('id', $qualifyingBuildingIds)
                ->whereIn('city', $cities)
                ->whereNotNull('strata_no')->where('strata_no', '!=', '')
                ->pluck('strata_no')->filter()->unique()->values()->toArray();
        }

        if (empty($qualifyingListingIds) && empty($qualifyingStratas)) {
            return response()->json(['persona' => $persona, 'tags' => $tags, 'listings' => [], 'areas' => [], 'total' => 0]);
        }

        $subarea = $req->query('subarea');

        $q = Listings::withoutGlobalScopes()
            ->where('status', 'Active')
            ->whereIn('city', $cities)
            ->when(!empty($abWhitelist), fn ($qb) => $qb->whereIn('subarea', $abWhitelist))
            ->when($subarea, fn ($qb) => $qb->where('subarea', $subarea))
            ->where(function ($qb) use ($qualifyingListingIds, $qualifyingStratas) {
                if (!empty($qualifyingListingIds)) {
                    $qb->orWhereIn('id', $qualifyingListingIds);
                }
                if (!empty($qualifyingStratas)) {
                    $qb->orWhereIn('strata_no', $qualifyingStratas);
                }
            })
            ->select([
                'id', 'sysid', 'listingid', 'streetaddress', 'city', 'subarea',
                'listingtype', 'bedrooms', 'bathstotal', 'listprice_2', 'livingarea_2',
                'strata_no', 'mainpicurl', 'slug',
            ]);

        $total = (clone $q)->count();
        $listings = $q->orderByDesc('list_date')->limit(200)->get();

        $areaQ = Listings::withoutGlobalScopes()
            ->where('status', 'Active')
            ->whereIn('city', $cities)
            ->when(!empty($abWhitelist), fn ($qb) => $qb->whereIn('subarea', $abWhitelist))
            ->where(function ($qb) use ($qualifyingListingIds, $qualifyingStratas) {
                if (!empty($qualifyingListingIds)) {
                    $qb->orWhereIn('id', $qualifyingListingIds);
                }
                if (!empty($qualifyingStratas)) {
                    $qb->orWhereIn('strata_no', $qualifyingStratas);
                }
            })
            ->select('subarea', \Illuminate\Support\Facades\DB::raw('COUNT(*) as c'))
            ->whereNotNull('subarea')->where('subarea', '!=', '')
            ->groupBy('subarea')
            ->orderByDesc('c')
            ->get();

        $tagsMap = $this->fetchAiTagsMap('listing_ai_tags', 'listing_id', $listings->pluck('id')->map(fn ($v) => (string) $v)->toArray());

        return response()->json([
            'persona'  => $persona,
            'tags'     => $tags,
            'listings' => $listings->map(function ($l) use ($tagsMap) {
                return [
                    'id'          => (string) $l->id,
                    'listingid'   => $l->listingid,
                    'address'     => $l->streetaddress,
                    'city'        => $l->city,
                    'subarea'     => $l->subarea,
                    'type'        => $l->listingtype,
                    'bedrooms'    => $l->bedrooms !== null ? (int) $l->bedrooms : null,
                    'baths'       => $l->bathstotal !== null ? (float) $l->bathstotal : null,
                    'price'       => $l->listprice_2 ? (int) $l->listprice_2 : null,
                    'sqft'        => $l->livingarea_2 ? (int) $l->livingarea_2 : null,
                    'photo_url'   => $l->mainpicurl ?: null,
                    'slug'        => $l->slug ?: $l->listingid,
                    'ai_tags'     => $tagsMap[(string) $l->id] ?? [],
                ];
            })->values(),
            'areas' => $areaQ->map(fn ($r) => ['subarea' => $r->subarea, 'count' => (int) $r->c])->values(),
            'total' => $total,
        ]);
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

    /**
     * Admin: fetch agent's take (commentary) on a building for editing.
     * GET /api-internal/admin/buildings/{id}/commentary
     */
    public function getBuildingCommentary(string $id): JsonResponse
    {
        $building = Buildings::withoutGlobalScopes()->find($id);
        if (!$building) return response()->json(['error' => 'Building not found'], 404);

        $data = [
            'agent_take_desirability'      => null,
            'agent_take_buyer_profile'     => null,
            'agent_take_common_problems'   => null,
            'agent_take_value_take'        => null,
            'agent_take_best_floorplans'   => null,
            'agent_take_view_preference'   => null,
            'agent_take_noise_notes'       => null,
            'agent_take_rental_pet_appeal' => null,
        ];

        try {
            $row = \Illuminate\Support\Facades\DB::table('building_agent_takes')->where('building_id', $id)->first();
            if ($row) {
                $data['agent_take_desirability']      = $row->desirability;
                $data['agent_take_buyer_profile']     = $row->buyer_profile;
                $data['agent_take_common_problems']   = $row->common_problems;
                $data['agent_take_value_take']        = $row->value_take;
                $data['agent_take_best_floorplans']   = $row->best_floorplans;
                $data['agent_take_view_preference']   = $row->view_preference;
                $data['agent_take_noise_notes']       = $row->noise_notes;
                $data['agent_take_rental_pet_appeal'] = $row->rental_pet_appeal;
            }
        } catch (\Throwable $e) {}

        return response()->json($data);
    }

    /**
     * Admin: save agent's take (commentary) on a building.
     * Stored in `building_agent_takes` on the default (bccondosandhomes) connection —
     * NOT on pixilink_mlsr.buildings, because the app DB user has no ALTER privilege
     * on that database (owned by a different cPanel account). See memory:
     * building-amenity-tags.md for the prior incident that surfaced this constraint.
     * POST /api-internal/admin/buildings/{id}/commentary
     */
    public function saveBuildingCommentary(Request $req, string $id): JsonResponse
    {
        try {
            \Illuminate\Support\Facades\DB::statement("CREATE TABLE IF NOT EXISTS building_agent_takes (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                building_id VARCHAR(255) NOT NULL,
                desirability TEXT NULL DEFAULT NULL,
                buyer_profile TEXT NULL DEFAULT NULL,
                common_problems TEXT NULL DEFAULT NULL,
                value_take TEXT NULL DEFAULT NULL,
                best_floorplans TEXT NULL DEFAULT NULL,
                view_preference TEXT NULL DEFAULT NULL,
                noise_notes TEXT NULL DEFAULT NULL,
                rental_pet_appeal TEXT NULL DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                UNIQUE KEY building_agent_takes_building_id_unique (building_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {}

        $building = Buildings::withoutGlobalScopes()->find($id);
        if (!$building) return response()->json(['error' => 'Building not found'], 404);

        $fieldMap = [
            'agent_take_desirability'      => 'desirability',
            'agent_take_buyer_profile'     => 'buyer_profile',
            'agent_take_common_problems'   => 'common_problems',
            'agent_take_value_take'        => 'value_take',
            'agent_take_best_floorplans'   => 'best_floorplans',
            'agent_take_view_preference'   => 'view_preference',
            'agent_take_noise_notes'       => 'noise_notes',
            'agent_take_rental_pet_appeal' => 'rental_pet_appeal',
        ];

        $data = [];
        foreach ($fieldMap as $inputKey => $col) {
            if ($req->has($inputKey)) {
                $val = $req->input($inputKey);
                $data[$col] = ($val === null || trim((string) $val) === '') ? null : trim((string) $val);
            }
        }

        if (!empty($data)) {
            $data['building_id'] = $id;
            $data['updated_at'] = now();
            try {
                $existing = \Illuminate\Support\Facades\DB::table('building_agent_takes')->where('building_id', $id)->first();
                if ($existing) {
                    \Illuminate\Support\Facades\DB::table('building_agent_takes')->where('building_id', $id)->update($data);
                } else {
                    $data['created_at'] = now();
                    \Illuminate\Support\Facades\DB::table('building_agent_takes')->insert($data);
                }
            } catch (\Throwable $e) {
                return response()->json(['error' => 'Save failed: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['success' => true, 'id' => $id]);
    }


    // ─────────────────────────────────────────────────────────────────
    // RESIDENCITY METRO ENDPOINTS
    // ─────────────────────────────────────────────────────────────────

    private const RESIDENCITY_SUBAREA_CITY_MAP = [
        // Vancouver
        'downtown vw' => 'Vancouver', 'kitsilano' => 'Vancouver', 'yaletown' => 'Vancouver',
        'mount pleasant ve' => 'Vancouver', 'west end vw' => 'Vancouver', 'collingwood ve' => 'Vancouver',
        'fairview vw' => 'Vancouver', 'university vw' => 'Vancouver', 'grandview woodland' => 'Vancouver',
        'false creek' => 'Vancouver', 'cambie' => 'Vancouver', 'south marine' => 'Vancouver',
        'marpole' => 'Vancouver', 'renfrew ve' => 'Vancouver', 'fraser ve' => 'Vancouver',
        'coal harbour' => 'Vancouver', 'knight' => 'Vancouver', 'dunbar' => 'Vancouver',
        'killarney ve' => 'Vancouver', 'point grey' => 'Vancouver', 'victoria ve' => 'Vancouver',
        'hastings' => 'Vancouver', 'main' => 'Vancouver', 'kerrisdale' => 'Vancouver',
        'south vancouver' => 'Vancouver', 'renfrew heights' => 'Vancouver', 'south granville' => 'Vancouver',
        'strathcona' => 'Vancouver', 'downtown ve' => 'Vancouver', 'hastings sunrise' => 'Vancouver',
        'quilchena' => 'Vancouver', 'shaughnessy' => 'Vancouver', 'south cambie' => 'Vancouver',
        'oakridge vw' => 'Vancouver', 'fraserview ve' => 'Vancouver', 'champlain heights' => 'Vancouver',
        's.w. marine' => 'Vancouver', 'mount pleasant vw' => 'Vancouver', 'arbutus' => 'Vancouver',
        'southlands' => 'Vancouver',
        // Burnaby
        'metrotown' => 'Burnaby', 'brentwood park' => 'Burnaby', 'highgate' => 'Burnaby',
        'edmonds be' => 'Burnaby', 'south slope' => 'Burnaby', 'simon fraser univer.' => 'Burnaby',
        'forest glen bs' => 'Burnaby', 'sullivan heights' => 'Burnaby', 'government road' => 'Burnaby',
        'capitol hill bn' => 'Burnaby', 'central bn' => 'Burnaby', 'central park bs' => 'Burnaby',
        'vancouver heights' => 'Burnaby', 'east burnaby' => 'Burnaby', 'willingdon heights' => 'Burnaby',
        'sperling-duthie' => 'Burnaby', 'parkcrest' => 'Burnaby', 'burnaby lake' => 'Burnaby',
        'the crest' => 'Burnaby', 'upper deer lake' => 'Burnaby', 'montecito' => 'Burnaby',
        'cariboo' => 'Burnaby', 'burnaby hospital' => 'Burnaby', 'simon fraser hills' => 'Burnaby',
        'forest hills bn' => 'Burnaby', 'westridge bn' => 'Burnaby', 'oaklands' => 'Burnaby',
        'greentree village' => 'Burnaby', 'garden village' => 'Burnaby', 'suncrest' => 'Burnaby',
        'deer lake place' => 'Burnaby', 'big bend' => 'Burnaby', 'buckingham heights' => 'Burnaby',
        'deer lake' => 'Burnaby',
        // Richmond
        'brighouse' => 'Richmond', 'west cambie' => 'Richmond', 'brighouse south' => 'Richmond',
        'mclennan north' => 'Richmond', 'steveston south' => 'Richmond', 'riverdale ri' => 'Richmond',
        'steveston north' => 'Richmond', 'granville' => 'Richmond', 'ironwood' => 'Richmond',
        'hamilton ri' => 'Richmond', 'woodwards' => 'Richmond', 'broadmoor' => 'Richmond',
        'boyd park' => 'Richmond', 'seafair' => 'Richmond', 'saunders' => 'Richmond',
        'east cambie' => 'Richmond', 'bridgeport ri' => 'Richmond', 'south arm' => 'Richmond',
        'terra nova' => 'Richmond', 'garden city' => 'Richmond', 'lackner' => 'Richmond',
        'quilchena ri' => 'Richmond', 'mcnair' => 'Richmond', 'steveston village' => 'Richmond',
        'westwind' => 'Richmond', 'east richmond' => 'Richmond', 'mclennan' => 'Richmond',
        'sea island' => 'Richmond', 'gilmore' => 'Richmond', 'neilsen grove' => 'Richmond',
        // Surrey (incl. South Surrey / White Rock overlaps default to Surrey unless overridden below)
        'whalley' => 'Surrey', 'cloverdale bc' => 'Surrey', 'fleetwood tynehead' => 'Surrey',
        'grandview surrey' => 'Surrey', 'clayton' => 'Surrey', 'guildford' => 'Surrey',
        'king george corridor' => 'Surrey', 'sullivan station' => 'Surrey', 'east newton' => 'Surrey',
        'west newton' => 'Surrey', 'queen mary park surrey' => 'Surrey', 'sunnyside park surrey' => 'Surrey',
        'panorama ridge' => 'Surrey', 'bear creek green timbers' => 'Surrey', 'fraser heights' => 'Surrey',
        'crescent bch ocean pk.' => 'Surrey', 'morgan creek' => 'Surrey', 'bolivar heights' => 'Surrey',
        'elgin chantrell' => 'Surrey', 'pacific douglas' => 'Surrey', 'cedar hills' => 'Surrey',
        'royal heights' => 'Surrey', 'bridgeview' => 'Surrey', 'white rock' => 'South Surrey',
        'hazelmere' => 'Surrey', 'port kells' => 'Surrey', 'serpentine' => 'Surrey',
        'scottsdale' => 'Surrey', 'grandview heights' => 'South Surrey', 'ocean park surrey' => 'South Surrey',
        'semiahmoo' => 'South Surrey', 'cloverdale' => 'Surrey',
        // Coquitlam / Tri-Cities
        'coquitlam west' => 'Coquitlam', 'north coquitlam' => 'Coquitlam', 'burke mountain' => 'Coquitlam',
        'westwood plateau' => 'Coquitlam', 'central coquitlam' => 'Coquitlam', 'maillardville' => 'Coquitlam',
        'new horizons' => 'Coquitlam', 'coquitlam east' => 'Coquitlam', 'canyon springs' => 'Coquitlam',
        'ranch park' => 'Coquitlam', 'eagle ridge cq' => 'Coquitlam', 'cape horn' => 'Coquitlam',
        'scott creek' => 'Coquitlam', 'harbour chines' => 'Coquitlam', 'upper eagle ridge' => 'Coquitlam',
        'meadow brook' => 'Coquitlam', 'river springs' => 'Coquitlam', 'chineside' => 'Coquitlam',
        'harbour place' => 'Coquitlam', 'hockaday' => 'Coquitlam', 'park ridge estates' => 'Coquitlam',
        'central pt coquitlam' => 'Port Coquitlam', 'glenwood pq' => 'Port Coquitlam', 'riverwood' => 'Port Coquitlam',
        'citadel pq' => 'Port Coquitlam', 'mary hill' => 'Port Coquitlam', 'lincoln park pq' => 'Port Coquitlam',
        'oxford heights' => 'Port Coquitlam', 'woodland acres pq' => 'Port Coquitlam', 'lower mary hill' => 'Port Coquitlam',
        'birchland manor' => 'Port Coquitlam',
        'port moody centre' => 'Port Moody', 'north shore pt moody' => 'Port Moody', 'college park pm' => 'Port Moody',
        'heritage woods pm' => 'Port Moody', 'heritage mountain' => 'Port Moody', 'barber street' => 'Port Moody',
        'glenayre' => 'Port Moody', 'anmore' => 'Port Moody', 'mountain meadows' => 'Port Moody',
        // New Westminster
        'uptown nw' => 'New Westminster', 'downtown nw' => 'New Westminster', 'queensborough' => 'New Westminster',
        'quay' => 'New Westminster', 'fraserview nw' => 'New Westminster', 'sapperton' => 'New Westminster',
        'glenbrooke north' => 'New Westminster', 'the heights nw' => 'New Westminster', 'west end nw' => 'New Westminster',
        'queens park' => 'New Westminster', 'moody park' => 'New Westminster', 'connaught heights' => 'New Westminster',
        // North Vancouver
        'lower lonsdale' => 'North Vancouver', 'central lonsdale' => 'North Vancouver', 'lynn valley' => 'North Vancouver',
        'lynnmour' => 'North Vancouver', 'pemberton nv' => 'North Vancouver', 'upper lonsdale' => 'North Vancouver',
        'roche point' => 'North Vancouver', 'canyon heights nv' => 'North Vancouver', 'mosquito creek' => 'North Vancouver',
        'edgemont' => 'North Vancouver', 'northlands' => 'North Vancouver', 'westlynn' => 'North Vancouver',
        'deep cove' => 'North Vancouver', 'blueridge nv' => 'North Vancouver', 'boulevard' => 'North Vancouver',
        'seymour nv' => 'North Vancouver', 'norgate' => 'North Vancouver', 'indian river' => 'North Vancouver',
        'queensbury' => 'North Vancouver', 'upper delbrook' => 'North Vancouver', 'harbourside' => 'North Vancouver',
        'pemberton heights' => 'North Vancouver', 'calverhall' => 'North Vancouver', 'forest hills nv' => 'North Vancouver',
        'capilano nv' => 'North Vancouver',
        // West Vancouver
        'ambleside' => 'West Vancouver', 'dundarave' => 'West Vancouver', 'british properties' => 'West Vancouver',
        'park royal' => 'West Vancouver', 'caulfeild' => 'West Vancouver', 'cypress park estates' => 'West Vancouver',
        'eagle harbour' => 'West Vancouver', 'horseshoe bay wv' => 'West Vancouver', 'sentinel hill' => 'West Vancouver',
        'panorama village' => 'West Vancouver', 'glenmore' => 'West Vancouver', 'cedardale' => 'West Vancouver',
        'chartwell' => 'West Vancouver', 'bayridge' => 'West Vancouver', 'queens' => 'West Vancouver',
        'altamont' => 'West Vancouver', 'upper caulfeild' => 'West Vancouver', 'gleneagles' => 'West Vancouver',
        'westmount wv' => 'West Vancouver', 'whitby estates' => 'West Vancouver', 'west bay' => 'West Vancouver',
        'whytecliff' => 'West Vancouver', 'olde caulfeild' => 'West Vancouver', 'rockridge' => 'West Vancouver',
        // Langley
        'willoughby heights' => 'Langley', 'langley city' => 'Langley', 'walnut grove' => 'Langley',
        'aldergrove langley' => 'Langley', 'brookswood langley' => 'Langley', 'murrayville' => 'Langley',
        'salmon river' => 'Langley', 'fort langley' => 'Langley', 'campbell valley' => 'Langley',
        'otter district' => 'Langley', 'county line glen valley' => 'Langley',
        // Abbotsford
        'central abbotsford' => 'Abbotsford', 'abbotsford west' => 'Abbotsford', 'abbotsford east' => 'Abbotsford',
        'poplar' => 'Abbotsford', 'aberdeen' => 'Abbotsford', 'bradner' => 'Abbotsford',
        'matsqui' => 'Abbotsford', 'sumas mountain' => 'Abbotsford', 'sumas prairie' => 'Abbotsford',
        // Chilliwack / Mission
        'promontory' => 'Chilliwack', 'vedder s watson-promontory' => 'Chilliwack', 'sardis south' => 'Chilliwack',
        'chilliwack e young-yale' => 'Chilliwack', 'chilliwack w young-well' => 'Chilliwack',
        'chilliwack proper east' => 'Chilliwack', 'chilliwack proper west' => 'Chilliwack',
        'eastern hillsides' => 'Chilliwack', 'sardis east vedder rd' => 'Chilliwack', 'garrison crossing' => 'Chilliwack',
        'sardis west vedder rd' => 'Chilliwack', 'fairfield island' => 'Chilliwack', 'chilliwack mountain' => 'Chilliwack',
        'chilliwack downtown' => 'Chilliwack', 'little mountain' => 'Chilliwack', 'ryder lake' => 'Chilliwack',
        'mission bc' => 'Mission', 'hatzic' => 'Mission', 'mission-west' => 'Mission', 'lake errock' => 'Mission',
        'dewdney deroche' => 'Mission', 'stave falls' => 'Mission', 'durieu' => 'Mission', 'hemlock' => 'Mission',
        // Maple Ridge / Pitt Meadows
        'east central' => 'Maple Ridge', 'west central' => 'Maple Ridge', 'cottonwood mr' => 'Maple Ridge',
        'albion' => 'Maple Ridge', 'silver valley' => 'Maple Ridge', 'southwest maple ridge' => 'Maple Ridge',
        'northwest maple ridge' => 'Maple Ridge', 'thornhill mr' => 'Maple Ridge', 'websters corners' => 'Maple Ridge',
        'whonnock' => 'Maple Ridge',
        'central meadows' => 'Pitt Meadows', 'south meadows' => 'Pitt Meadows', 'mid meadows' => 'Pitt Meadows',
        'north meadows pi' => 'Pitt Meadows', 'west meadows' => 'Pitt Meadows',
        // Delta
        'nordel' => 'Delta', 'annieville' => 'Delta', 'sunshine hills woods' => 'Delta',
        'tsawwassen central' => 'Delta', 'neilsen grove delta' => 'Delta', 'hawthorne' => 'Delta',
        'cliff drive' => 'Delta', 'ladner elementary' => 'Delta', 'pebble hill' => 'Delta',
        'beach grove' => 'Delta', 'delta manor' => 'Delta', 'holly' => 'Delta', 'tsawwassen north' => 'Delta',
        'boundary beach' => 'Delta', 'tsawwassen east' => 'Delta', 'english bluff' => 'Delta',
        'port guichon' => 'Delta', 'ladner rural' => 'Delta',
        // Squamish / Whistler
        'downtown sq' => 'Squamish', 'garibaldi estates' => 'Squamish', 'tantalus' => 'Squamish',
        'garibaldi highlands' => 'Squamish', 'valleycliffe' => 'Squamish', 'brackendale' => 'Squamish',
        'northyards' => 'Squamish', 'dentville' => 'Squamish', 'brennan center' => 'Squamish',
        'hospital hill' => 'Squamish', 'plateau' => 'Squamish',
        'whistler village' => 'Whistler', 'benchlands' => 'Whistler', 'whistler creek' => 'Whistler',
        'nordic' => 'Whistler', 'alpine meadows' => 'Whistler', 'bayshores' => 'Whistler',
        'blueberry hill' => 'Whistler', 'whistler cay heights' => 'Whistler', 'emerald estates' => 'Whistler',
        'alta vista' => 'Whistler', 'green lake estates' => 'Whistler', 'brio' => 'Whistler',
        'white gold' => 'Whistler', 'cheakamus crossing' => 'Whistler', 'rainbow' => 'Whistler',
        // Audit additions — real neighbourhood name variants/typos found in a full DB scan
        // that were previously falling back to a (sometimes wrong) raw `city` value.
        'steveston villlage' => 'Richmond', 'simon fraser univer' => 'Burnaby',
        'north maple ridge' => 'Maple Ridge', 'thornhill' => 'Maple Ridge', 'northeast' => 'Maple Ridge',
        'squamish rural' => 'Squamish', 'upper squamish' => 'Squamish', 'university highlands' => 'Squamish',
        'business park' => 'Squamish', 'paradise valley' => 'Squamish', 'ring creek' => 'Squamish',
        'britannia beach' => 'Squamish',
        'function junction' => 'Whistler', 'nesters' => 'Whistler', 'spring creek' => 'Whistler',
        'spruce grove' => 'Whistler', 'wedgewoods' => 'Whistler', 'whistler cay estates' => 'Whistler',
        'westside' => 'Whistler', 'whistler' => 'Whistler',
        'north meadows' => 'Pitt Meadows',
        'belcarra' => 'Port Moody', 'ioco' => 'Port Moody',
        'brunette' => 'New Westminster', 'north arm' => 'New Westminster',
        'lake city industrial' => 'Burnaby',
        'east delta' => 'Delta', 'tilbury' => 'Delta', 'westham island' => 'Delta',
        'hastings east' => 'Vancouver', 'grandview ve' => 'Vancouver', 'false creek north' => 'Vancouver',
        'sw marine' => 'Vancouver', 'mackenzie heights' => 'Vancouver',
        'windsor park nv' => 'North Vancouver', 'woodlands-sunshine-cascade' => 'North Vancouver',
        'westlynn terrace' => 'North Vancouver', 'delbrook' => 'North Vancouver', 'dollarton' => 'North Vancouver',
        'braemar' => 'North Vancouver', 'capilano highlands' => 'North Vancouver', 'hamilton' => 'North Vancouver',
        'hamilton heights' => 'North Vancouver', 'grouse woods' => 'North Vancouver', 'indian arm' => 'North Vancouver',
        'princess park' => 'North Vancouver', 'seymour' => 'North Vancouver', 'tempe' => 'North Vancouver',
        'cypress' => 'West Vancouver', 'canterbury wv' => 'West Vancouver', 'caulfield' => 'West Vancouver',
        'chelsea park' => 'West Vancouver', 'deer ridge wv' => 'West Vancouver', 'eagleridge' => 'West Vancouver',
        'olde caulfield' => 'West Vancouver', 'sandy cove' => 'West Vancouver', 'upper caulfield' => 'West Vancouver',
        'westhill' => 'West Vancouver', 'passage island' => 'West Vancouver', 'lions bay' => 'West Vancouver',
    ];

    /**
     * Corrects a raw DB `city` value using the canonical subarea->city map.
     * Falls back to the raw city if the subarea isn't in the map, so unknown
     * / new subareas never disappear from results.
     */
    private function residencityCorrectCity(?string $subarea, ?string $rawCity): string
    {
        $key = strtolower(trim((string) $subarea));
        return self::RESIDENCITY_SUBAREA_CITY_MAP[$key] ?? ($rawCity ?: '');
    }

    private function residencityMetroCities(): array
    {
        return [
            'Surrey', 'White Rock', 'Langley', 'Delta', 'Abbotsford', 'Mission',
            'Burnaby', 'Coquitlam', 'Port Coquitlam', 'Port Moody',
            'New Westminster', 'Richmond', 'Vancouver',
            'North Vancouver', 'West Vancouver', 'Maple Ridge', 'Pitt Meadows',
        ];
    }

    public function residencityHeatmap(\Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $days = max(7, min(365, (int) $req->query('days', 60)));
        $data = \Illuminate\Support\Facades\Cache::remember('rc_hm_' . $days, 21600, function () use ($days) {
            $cities = $this->residencityMetroCities();
            try {
                $rows = \Illuminate\Support\Facades\DB::connection('mysql_mlsr')
                    ->table('mlsr_listings_master')
                    ->whereIn('city', $cities)
                    ->where('status', 'Sold')
                    ->where('sold_date', '>=', now()->subDays($days)->format('Y-m-d'))
                    ->whereNotNull('lat')->where('lat', '!=', '0')->where('lat', '!=', '')
                    ->whereNotNull('lng')->where('lng', '!=', '0')->where('lng', '!=', '')
                    ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                    ->select(['lat', 'lng', 'listingtype', 'bedrooms', 'yearbuilt', 'soldprice_2', 'subarea', 'city'])
                    ->limit(8000)
                    ->get();
                $out = [];
                foreach ($rows as $r) {
                    $out[] = [
                        'lat'    => (float) $r->lat,
                        'lng'    => (float) $r->lng,
                        'type'   => $r->listingtype ?: '',
                        'beds'   => $r->bedrooms ? (int) $r->bedrooms : null,
                        'year'   => $r->yearbuilt ? (int) $r->yearbuilt : null,
                        'price'  => (int) $r->soldprice_2,
                        'subarea'=> $r->subarea ?: '',
                        'city'   => $this->residencityCorrectCity($r->subarea, $r->city),
                    ];
                }
                return $out;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('rc_heatmap: ' . $e->getMessage());
                return [];
            }
        });
        return response()->json($data);
    }

    public function residencityRecentSold(\Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $limit = max(1, min(100, (int) $req->query('limit', 40)));
        $data = \Illuminate\Support\Facades\Cache::remember('rc_rs_' . $limit, 900, function () use ($limit) {
            $cities = $this->residencityMetroCities();
            try {
                $rows = \Illuminate\Support\Facades\DB::connection('mysql_mlsr')
                    ->table('mlsr_listings_master')
                    ->whereIn('city', $cities)
                    ->where('status', 'Sold')
                    ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                    ->where(function ($q) { $q->whereNotNull('subarea')->where('subarea', '!=', ''); })
                    ->orderBy('sold_date', 'desc')
                    ->select(['subarea', 'city', 'class', 'soldprice_2', 'sold_date', 'streetaddress', 'listprice_2', 'list_date'])
                    ->limit($limit)
                    ->get();
                $out = [];
                  foreach ($rows as $r) {
                      $dom = null;
                      if (!empty($r->list_date) && !empty($r->sold_date)) {
                          try { $dom = (int) ((strtotime($r->sold_date) - strtotime($r->list_date)) / 86400); } catch (\Throwable $e2) {}
                      }
                      $out[] = [
                          'subarea'    => $r->subarea,
                          'city'       => $this->residencityCorrectCity($r->subarea, $r->city),
                          'type'       => $r->class ?: 'Residential',
                          'price'      => (int) $r->soldprice_2,
                          'list_price' => (int) ($r->listprice_2 ?? 0),
                          'address'    => $r->streetaddress ?: null,
                          'dom'        => $dom,
                          'sold_date'  => $r->sold_date,
                      ];
                  }
                  return $out;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('rc_recent_sold: ' . $e->getMessage());
                return [];
            }
        });
        return response()->json($data);
    }

    public function residencityOverview(\Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $days = max(7, min(365, (int) $req->query('days', 60)));
        $data = \Illuminate\Support\Facades\Cache::remember('rc_ov_v3_' . $days, 1800, function () use ($days) {
            $cities = $this->residencityMetroCities();
            $since  = now()->subDays($days)->format('Y-m-d');
            $priorSince = now()->subDays($days * 2)->format('Y-m-d');
            $db     = \Illuminate\Support\Facades\DB::connection('mysql_mlsr');

            $ppsfExpr = "soldprice_2 / NULLIF(CAST(REPLACE(COALESCE(NULLIF(livingarea_2,'0'), livingarea, '0'), ',', '') AS DECIMAL(10,0)), 0)";

            $sold = null;
            try {
                $sold = $db->table('mlsr_listings_master')
                    ->whereIn('city', $cities)->where('status', 'Sold')
                    ->where('sold_date', '>=', $priorSince)
                    ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                    ->selectRaw("
                        SUM(CASE WHEN sold_date >= ? THEN 1 ELSE 0 END) as cnt_cur,
                        ROUND(AVG(CASE WHEN sold_date >= ? THEN soldprice_2 END)) as avg_price_cur,
                        ROUND(AVG(CASE WHEN sold_date >= ? THEN DATEDIFF(sold_date, list_date) END)) as avg_dom_cur,
                        ROUND(AVG(CASE WHEN sold_date < ? THEN DATEDIFF(sold_date, list_date) END)) as avg_dom_prior,
                        ROUND(AVG(CASE WHEN sold_date >= ? AND listprice_2 > 0 THEN soldprice_2 / listprice_2 * 100 END), 2) as stl_cur,
                        ROUND(AVG(CASE WHEN sold_date < ? AND listprice_2 > 0 THEN soldprice_2 / listprice_2 * 100 END), 2) as stl_prior,
                        ROUND(AVG(CASE WHEN sold_date >= ? AND (livingarea_2 > 0 OR livingarea > 0) THEN $ppsfExpr END)) as ppsf_cur,
                        ROUND(AVG(CASE WHEN sold_date < ? AND (livingarea_2 > 0 OR livingarea > 0) THEN $ppsfExpr END)) as ppsf_prior
                    ", [$since, $since, $since, $since, $since, $since, $since, $since])
                    ->first();
            } catch (\Throwable $e) {}

            $medianFor = function ($fromDate, $toDate = null) use ($db, $cities) {
                $q = $db->table('mlsr_listings_master')
                    ->whereIn('city', $cities)->where('status', 'Sold')
                    ->where('sold_date', '>=', $fromDate)
                    ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0);
                if ($toDate) { $q->where('sold_date', '<', $toDate); }
                $prices = [];
                try { $prices = $q->orderBy('soldprice_2')->pluck('soldprice_2')->map(fn($v) => (float) $v)->values()->all(); } catch (\Throwable $e) {}
                $n = count($prices);
                if ($n === 0) return null;
                $mid = intdiv($n, 2);
                return $n % 2 === 1 ? $prices[$mid] : ($prices[$mid - 1] + $prices[$mid]) / 2;
            };

            $ac = 0;
            try {
                $ac = $db->table('mlsr_listings_master')
                    ->whereIn('city', $cities)->where('status', 'Active')->count();
            } catch (\Throwable $e) {}

            $ytdSince = now()->startOfYear()->format('Y-m-d');
            $ytdCount = 0;
            try {
                $ytdCount = $db->table('mlsr_listings_master')
                    ->whereIn('city', $cities)->where('status', 'Sold')
                    ->where('sold_date', '>=', $ytdSince)
                    ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                    ->count();
            } catch (\Throwable $e) {}

            $last7Since = now()->subDays(7)->format('Y-m-d');
            $prior7Since = now()->subDays(14)->format('Y-m-d');
            $week = null; $newListingsWeek = null;
            try {
                $week = $db->table('mlsr_listings_master')
                    ->whereIn('city', $cities)->where('status', 'Sold')
                    ->where('sold_date', '>=', $prior7Since)
                    ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                    ->selectRaw("
                        SUM(CASE WHEN sold_date >= ? THEN 1 ELSE 0 END) as cnt_cur,
                        ROUND(AVG(CASE WHEN sold_date >= ? THEN soldprice_2 END)) as avg_price_cur,
                        SUM(CASE WHEN sold_date < ? THEN 1 ELSE 0 END) as cnt_prior,
                        ROUND(AVG(CASE WHEN sold_date < ? THEN soldprice_2 END)) as avg_price_prior
                    ", [$last7Since, $last7Since, $last7Since, $last7Since])
                    ->first();
            } catch (\Throwable $e) {}
            try {
                $newListingsWeek = $db->table('mlsr_listings_master')
                    ->whereIn('city', $cities)
                    ->whereNotNull('list_date')
                    ->where('list_date', '>=', $prior7Since)
                    ->selectRaw("
                        SUM(CASE WHEN list_date >= ? THEN 1 ELSE 0 END) as cnt_cur,
                        SUM(CASE WHEN list_date < ? THEN 1 ELSE 0 END) as cnt_prior
                    ", [$last7Since, $last7Since])
                    ->first();
            } catch (\Throwable $e) {}

            $change7dPct = null;
            if ($week && (float) ($week->avg_price_prior ?? 0) > 0 && (int) ($week->cnt_cur ?? 0) > 0 && (int) ($week->cnt_prior ?? 0) > 0) {
                $change7dPct = round((((float) $week->avg_price_cur - (float) $week->avg_price_prior) / (float) $week->avg_price_prior) * 100, 1);
            }
            $pctChange = function ($cur, $prior) {
                if ($cur === null || $prior === null || (float) $prior == 0) return null;
                return round((((float) $cur - (float) $prior) / (float) $prior) * 100, 1);
            };

            $median = $medianFor($since);
            $medianPrior = $medianFor($priorSince, $since);
            $newListings7d = (int) ($newListingsWeek->cnt_cur ?? 0);
            $newListingsPrior7d = (int) ($newListingsWeek->cnt_prior ?? 0);

            return [
                'sold_count'          => (int) ($sold->cnt_cur ?? 0),
                'active_count'        => (int) $ac,
                'avg_sold_price'      => (int) ($sold->avg_price_cur ?? 0),
                'median_price'        => $median !== null ? (int) round($median) : 0,
                'median_price_change_pct' => $pctChange($median, $medianPrior),
                'avg_dom'             => (int) ($sold->avg_dom_cur ?? 0),
                'avg_dom_change_pct'  => $pctChange($sold->avg_dom_cur ?? null, $sold->avg_dom_prior ?? null),
                'sold_to_list'        => (float) ($sold->stl_cur ?? 0),
                'sold_to_list_change_pct' => $pctChange($sold->stl_cur ?? null, $sold->stl_prior ?? null),
                'avg_ppsf'            => (int) ($sold->ppsf_cur ?? 0),
                'avg_ppsf_change_pct' => $pctChange($sold->ppsf_cur ?? null, $sold->ppsf_prior ?? null),
                'new_listings_7d'     => $newListings7d,
                'new_listings_change_pct' => $pctChange($newListings7d, $newListingsPrior7d),
                'ytd_sold_count'      => (int) $ytdCount,
                'price_change_7d_pct' => $change7dPct,
                'days'                => $days,
            ];
        });
        return response()->json($data);
    }
    public function residencityTrends(\Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $days = max(7, min(365, (int) $req->query('days', 60)));
        $data = \Illuminate\Support\Facades\Cache::remember('rc_tr_' . $days, 3600, function () use ($days) {
            $cities     = $this->residencityMetroCities();
            $since      = now()->subDays($days)->format('Y-m-d');
            $priorSince = now()->subDays($days * 2)->format('Y-m-d');
            $db         = \Illuminate\Support\Facades\DB::connection('mysql_mlsr');

            $base = function () use ($db, $cities) {
                return $db->table('mlsr_listings_master')
                    ->whereIn('city', $cities)->where('status', 'Sold')
                    ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                    ->whereNotNull('subarea')->where('subarea', '!=', '');
            };

            $ma = collect([]); $sp = collect([]); $pf = collect([]);
            $cp = collect([]); $pp = collect([]);
            $sl = collect([]); $ar = collect([]); $sr = collect([]); $sk = collect([]);

            try { $ma = $base()->where('sold_date', '>=', $since)->selectRaw('subarea, city, COUNT(*) as sold_count, ROUND(AVG(soldprice_2)) as avg_price')->groupBy('subarea', 'city')->orderByDesc('sold_count')->limit(30)->get(); } catch (\Throwable $e) {}
            try { $sp = $base()->where('sold_date', '>=', $since)->whereNotNull('list_date')->selectRaw('subarea, city, COUNT(*) as sold_count, ROUND(AVG(DATEDIFF(sold_date, list_date))) as avg_dom')->groupBy('subarea', 'city')->having('sold_count', '>=', 3)->orderBy('avg_dom')->limit(30)->get(); } catch (\Throwable $e) {}
            try { $pf = $base()->where('sold_date', '>=', $since)->where(function ($q) { $q->where('livingarea_2', '>', 0)->orWhere('livingarea', '>', 0); })->selectRaw("subarea, city, COUNT(*) as sold_count, ROUND(AVG(soldprice_2 / NULLIF(CAST(REPLACE(COALESCE(NULLIF(livingarea_2,'0'), livingarea, '0'), ',', '') AS DECIMAL(10,0)), 0))) as avg_ppsf")->groupBy('subarea', 'city')->having('sold_count', '>=', 3)->orderByDesc('avg_ppsf')->limit(30)->get(); } catch (\Throwable $e) {}
            try { $cp = $base()->where('sold_date', '>=', $since)->selectRaw('subarea, city, COUNT(*) as cnt, ROUND(AVG(soldprice_2)) as avg_price')->groupBy('subarea', 'city')->having('cnt', '>=', 3)->get()->keyBy('subarea'); } catch (\Throwable $e) {}
            try { $pp = $base()->where('sold_date', '>=', $priorSince)->where('sold_date', '<', $since)->selectRaw('subarea, city, COUNT(*) as cnt, ROUND(AVG(soldprice_2)) as avg_price')->groupBy('subarea', 'city')->having('cnt', '>=', 3)->get()->keyBy('subarea'); } catch (\Throwable $e) {}
            try { $sl = $base()->where('sold_date', '>=', $since)->whereNotNull('listprice_2')->where('listprice_2', '>', 0)->selectRaw('subarea, city, COUNT(*) as sold_count, ROUND(AVG(soldprice_2 / listprice_2 * 100), 2) as avg_stl')->groupBy('subarea', 'city')->having('sold_count', '>=', 3)->orderByDesc('avg_stl')->limit(30)->get(); } catch (\Throwable $e) {}
            try { $ar = $db->table('mlsr_listings_master')->whereIn('city', $cities)->where('status', 'Active')->selectRaw('subarea, city, COUNT(*) as active_count')->groupBy('subarea', 'city')->get()->keyBy('subarea'); } catch (\Throwable $e) {}
            try { $sr = $base()->where('sold_date', '>=', $since)->selectRaw('subarea, city, COUNT(*) as sold_count')->groupBy('subarea', 'city')->get()->keyBy('subarea'); } catch (\Throwable $e) {}
            try { $sk = $base()->where('sold_date', '>=', now()->subMonths(6)->startOfMonth()->format('Y-m-d'))->selectRaw("subarea, DATE_FORMAT(sold_date, '%Y-%m') as month, COUNT(*) as sold_count, ROUND(AVG(soldprice_2)) as avg_price")->groupBy('subarea', \Illuminate\Support\Facades\DB::raw("DATE_FORMAT(sold_date, '%Y-%m')"))->get(); } catch (\Throwable $e) {}
            $mt = collect([]);
            try {
                $mt = $base()->where('sold_date', '>=', now()->subMonths(24)->startOfMonth()->format('Y-m-d'))
                    ->where(function ($q) { $q->where('livingarea_2', '>', 0)->orWhere('livingarea', '>', 0); })
                    ->selectRaw("DATE_FORMAT(sold_date, '%Y-%m') as month, COUNT(*) as sold_count, ROUND(AVG(soldprice_2)) as avg_price, ROUND(AVG(DATEDIFF(sold_date, list_date))) as avg_dom, ROUND(AVG(soldprice_2 / NULLIF(CAST(REPLACE(COALESCE(NULLIF(livingarea_2,'0'), livingarea, '0'), ',', '') AS DECIMAL(10,0)), 0))) as avg_ppsf")
                    ->groupBy(\Illuminate\Support\Facades\DB::raw("DATE_FORMAT(sold_date, '%Y-%m')"))
                    ->orderBy('month')
                    ->get();
            } catch (\Throwable $e) {}

            $gains = []; $drops = [];
            foreach ($cp as $sub => $cur) {
                if (!isset($pp[$sub])) continue;
                $prior = $pp[$sub];
                if ((int) $prior->avg_price <= 0) continue;
                $pct = round(((int) $cur->avg_price - (int) $prior->avg_price) / (int) $prior->avg_price * 100, 1);
                $entry = ['subarea' => $sub, 'city' => $this->residencityCorrectCity($sub, $cur->city), 'pct_change' => $pct, 'avg_price' => (int) $cur->avg_price, 'prior_avg' => (int) $prior->avg_price, 'sold_count' => (int) $cur->cnt];
                if ($pct > 0) $gains[] = $entry;
                if ($pct < 0) $drops[] = $entry;
            }
            usort($gains, function ($a, $b) { return $b['pct_change'] <=> $a['pct_change']; });
            usort($drops, function ($a, $b) { return $a['pct_change'] <=> $b['pct_change']; });

            $inv = [];
            foreach ($ar as $sub => $ac) {
                if (!isset($sr[$sub])) continue;
                $rate = (int) $sr[$sub]->sold_count / ($days / 30.0);
                if ($rate <= 0) continue;
                $mo = round((int) $ac->active_count / $rate, 1);
                $inv[] = ['subarea' => $sub, 'city' => $this->residencityCorrectCity($sub, $ac->city), 'months_supply' => $mo, 'active_count' => (int) $ac->active_count, 'monthly_sold' => round($rate, 1), 'market_type' => $mo < 2 ? 'sellers' : ($mo < 4 ? 'balanced' : 'buyers')];
            }
            usort($inv, function ($a, $b) { return $a['months_supply'] <=> $b['months_supply']; });

            $spl = [];
            foreach ($sk as $row) {
                if (!isset($spl[$row->subarea])) $spl[$row->subarea] = [];
                $spl[$row->subarea][] = ['month' => $row->month, 'sold_count' => (int) $row->sold_count, 'avg_price' => (int) $row->avg_price];
            }

            $ml = function ($rows, $fn) { $o = []; foreach ($rows as $r) { $o[] = $fn($r); } return $o; };
            return [
                'most_active'      => $ml($ma, function ($r) { return ['subarea' => $r->subarea, 'city' => $this->residencityCorrectCity($r->subarea, $r->city), 'sold_count' => (int) $r->sold_count, 'avg_price' => (int) $r->avg_price]; }),
                'speed_of_market'  => $ml($sp, function ($r) { return ['subarea' => $r->subarea, 'city' => $this->residencityCorrectCity($r->subarea, $r->city), 'avg_dom' => (int) $r->avg_dom, 'sold_count' => (int) $r->sold_count]; }),
                'price_per_sqft'   => array_values(array_filter($ml($pf, function ($r) { return ['subarea' => $r->subarea, 'city' => $this->residencityCorrectCity($r->subarea, $r->city), 'avg_ppsf' => (int) ($r->avg_ppsf ?? 0), 'sold_count' => (int) $r->sold_count]; }), function ($r) { return $r['avg_ppsf'] > 0; })),
                'price_gains'      => array_slice($gains, 0, 10),
                'price_drops'      => array_slice($drops, 0, 10),
                'sold_to_list'     => $ml($sl, function ($r) { return ['subarea' => $r->subarea, 'city' => $this->residencityCorrectCity($r->subarea, $r->city), 'avg_stl' => (float) $r->avg_stl, 'sold_count' => (int) $r->sold_count]; }),
                'inventory_health' => array_slice($inv, 0, 20),
                'sparklines'       => $spl,
                'monthly_trend'    => $mt->map(function ($r) {
                    return [
                        'month'      => $r->month,
                        'sold'       => (int) $r->sold_count,
                        'avg_price'  => (int) $r->avg_price,
                        'avg_dom'    => (int) ($r->avg_dom ?? 0),
                        'avg_ppsf'   => (int) ($r->avg_ppsf ?? 0),
                    ];
                })->values()->all(),
                'days'             => $days,
            ];
        });
        return response()->json($data);
    }

    public function residencitySubscribe(\Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $name  = trim((string) $req->input('name', ''));
        $email = trim((string) $req->input('email', ''));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Valid email required'], 422);
        }
        try {
            \Illuminate\Support\Facades\DB::statement("CREATE TABLE IF NOT EXISTS residencity_subscribers (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NULL, email VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uk_email (email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {}
        try {
            \Illuminate\Support\Facades\DB::table('residencity_subscribers')->insertOrIgnore(['name' => $name ?: null, 'email' => $email, 'created_at' => now()]);
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Server error'], 500);
        }
    }


    public function ping(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['ok' => true, 'ts' => time()]);
    }

    public function topRealtor(\Illuminate\Http\Request $req, string $slug): \Illuminate\Http\JsonResponse
    {
        return response()->json(['slug' => $slug, 'areas' => []]);
    }

    /**
     * GET /api-internal/agent/{slug}/schools
     * School catchment hub: schools within the agent's territory cities, with live active-listing counts.
     */
    public function schoolCatchments(string $slug): JsonResponse
    {
        $agent = Agent::with(['territories'])->where('slug', $slug)->where('status', 'active')->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        $unrestricted = empty($cities);

        $cacheKey = 'school_catchments_hub_v1_' . $agent->id;

        $data = Cache::remember($cacheKey, 1800, function () use ($cities, $unrestricted) {
            $q = DB::table('schools')->where('is_public', true);
            if (! $unrestricted) {
                $q->whereIn(DB::raw('LOWER(city)'), array_map('strtolower', $cities));
            }
            $schools = $q->orderBy('school_type')->orderBy('name')->get();

            return $schools->map(function ($school) {
                $catchment = DB::table('school_catchments')->where('school_id', $school->id)->first();
                $count = $this->schoolActiveListingCount($school, $catchment);
                return [
                    'name'          => $school->name,
                    'slug'          => $school->slug,
                    'school_type'   => $school->school_type,
                    'city'          => $school->city,
                    'district_name' => $school->district_name,
                    'address'       => $school->address,
                    'latitude'      => $school->latitude ? (float) $school->latitude : null,
                    'longitude'     => $school->longitude ? (float) $school->longitude : null,
                    'active_count'  => $count,
                    'has_boundary'  => $catchment && $catchment->polygon_geojson ? true : false,
                ];
            })->values();
        });

        return response()->json($data);
    }

    /**
     * GET /api-internal/agent/{slug}/schools/{schoolSlug}
     * School catchment detail: active + recently sold listings within the catchment, plus averages.
     */
    public function schoolCatchmentDetail(string $slug, string $schoolSlug): JsonResponse
    {
        $agent = Agent::with(['territories'])->where('slug', $slug)->where('status', 'active')->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $school = DB::table('schools')->where('slug', $schoolSlug)->where('is_public', true)->first();
        if (! $school) return response()->json(['error' => 'School not found'], 404);

        $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        $unrestricted = empty($cities);
        if (! $unrestricted) {
            $cityMatch = false;
            foreach ($cities as $c) {
                if (strcasecmp(trim($c), trim($school->city ?? '')) === 0) { $cityMatch = true; break; }
            }
            if (! $cityMatch) return response()->json(['error' => 'School not found'], 404);
        }

        $catchment = DB::table('school_catchments')->where('school_id', $school->id)->first();

        $cacheKey = 'school_catchment_detail_v1_' . $agent->id . '_' . $school->id;

        $data = Cache::remember($cacheKey, 1800, function () use ($school, $catchment) {
            $activeListings = $this->schoolCatchmentListings('Active', $school, $catchment, 'list_date', null, 24);
            $soldListings   = $this->schoolCatchmentListings('Sold', $school, $catchment, 'sold_date', 6, 24);

            $avgListPrice = $activeListings->count() > 0
                ? (int) round($activeListings->avg('list_price'))
                : null;
            $avgSoldPrice = $soldListings->count() > 0
                ? (int) round($soldListings->avg('sold_price'))
                : null;
            $psfValues = $soldListings->filter(fn($l) => ($l['sqft'] ?? 0) > 0 && ($l['sold_price'] ?? 0) > 0)
                ->map(fn($l) => $l['sold_price'] / $l['sqft']);
            $avgSoldPsf = $psfValues->count() > 0 ? round($psfValues->avg(), 2) : null;

            return [
                'school' => [
                    'name'          => $school->name,
                    'slug'          => $school->slug,
                    'school_type'   => $school->school_type,
                    'city'          => $school->city,
                    'district_name' => $school->district_name,
                    'address'       => $school->address,
                    'latitude'      => $school->latitude ? (float) $school->latitude : null,
                    'longitude'     => $school->longitude ? (float) $school->longitude : null,
                ],
                'has_boundary'    => $catchment && $catchment->polygon_geojson ? true : false,
                'active'          => $activeListings->values(),
                'recent_sold'     => $soldListings->values(),
                'active_count'    => $activeListings->count(),
                'sold_count'      => $soldListings->count(),
                'avg_list_price'  => $avgListPrice,
                'avg_sold_price'  => $avgSoldPrice,
                'avg_sold_psf'    => $avgSoldPsf,
            ];
        });

        return response()->json($data);
    }

    // ── school catchment helpers ────────────────────────────────────────────

    protected function schoolCatchmentBbox($school, $catchment): array
    {
        if ($catchment && $catchment->polygon_geojson) {
            $geojson = json_decode($catchment->polygon_geojson, true);
            $type    = $geojson['type'] ?? '';
            if ($type === 'Polygon') {
                $ring = $geojson['coordinates'][0] ?? [];
            } elseif ($type === 'MultiPolygon') {
                $ring = $geojson['coordinates'][0][0] ?? [];
            } else {
                $ring = [];
            }
            if (! empty($ring)) {
                $lats = array_column($ring, 1);
                $lngs = array_column($ring, 0);
                return [
                    'min_lat' => min($lats), 'max_lat' => max($lats),
                    'min_lng' => min($lngs), 'max_lng' => max($lngs),
                ];
            }
        }

        $isSecondary = in_array($school->school_type, ['Secondary', 'Middle']);
        $dLat = $isSecondary ? 0.036 : 0.016;
        $dLng = $isSecondary ? 0.046 : 0.020;
        $lat  = (float) ($school->latitude ?? 49.05);
        $lng  = (float) ($school->longitude ?? -122.80);

        return [
            'min_lat' => $lat - $dLat, 'max_lat' => $lat + $dLat,
            'min_lng' => $lng - $dLng, 'max_lng' => $lng + $dLng,
        ];
    }

    protected function schoolActiveListingCount($school, $catchment): int
    {
        try {
            $bbox = $this->schoolCatchmentBbox($school, $catchment);
            $q = Listings::withoutGlobalScopes()->where('status', 'Active')
                ->whereBetween('lat', [$bbox['min_lat'], $bbox['max_lat']])
                ->whereBetween('lng', [$bbox['min_lng'], $bbox['max_lng']]);
            if ($catchment && $catchment->polygon_geojson) {
                $q->whereRaw('ST_Contains(ST_GeomFromGeoJSON(?, 1, 0), POINT(lng, lat))', [$catchment->polygon_geojson]);
            }
            return $q->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function schoolCatchmentListings(string $status, $school, $catchment, string $orderCol, ?int $soldMonths, int $limit)
    {
        try {
            $bbox = $this->schoolCatchmentBbox($school, $catchment);
            $q = Listings::withoutGlobalScopes()->where('status', $status)
                ->whereBetween('lat', [$bbox['min_lat'], $bbox['max_lat']])
                ->whereBetween('lng', [$bbox['min_lng'], $bbox['max_lng']]);
            if ($catchment && $catchment->polygon_geojson) {
                $q->whereRaw('ST_Contains(ST_GeomFromGeoJSON(?, 1, 0), POINT(lng, lat))', [$catchment->polygon_geojson]);
            }
            if ($soldMonths) {
                $cutoff = now()->subMonths($soldMonths)->toDateString();
                $q->where('sold_date', '>=', $cutoff);
            }
            $rows = $q->orderBy($orderCol, 'desc')->limit($limit)->get();

            return $rows->map(fn ($l) => [
                'id'         => $l->sysid,
                'mls_no'     => $l->listingid,
                'address'    => $l->streetaddress,
                'city'       => $l->city,
                'subarea'    => $l->subarea,
                'status'     => $l->status,
                'list_price' => (int) ($l->listprice_2 ?: $l->listprice),
                'sold_price' => ($l->soldprice_2 > 0) ? (int) $l->soldprice_2 : (($l->soldprice > 0) ? (int) $l->soldprice : null),
                'beds'       => (int) $l->bedrooms,
                'baths'      => (float) $l->bathstotal,
                'sqft'       => (int) str_replace(',', '', (string) ($l->livingarea_2 ?: $l->livingarea ?: '0')),
                'photo_url'  => $l->mainpicurl ?: $l->thumbnailurl ?: null,
                'type'       => $l->type,
                'style'      => $l->home_style,
                'slug'       => $l->slug,
                'dom'        => ($l->status === 'Active' && ! empty($l->list_date) && $l->list_date !== '0000-00-00')
                    ? (int) floor((now()->timestamp - strtotime($l->list_date)) / 86400)
                    : ((isset($l->dom) && $l->dom) ? (int) $l->dom : null),
                'sold_date'  => ($l->sold_date && $l->sold_date !== '0000-00-00') ? (string) $l->sold_date : null,
                'year_built' => $l->yearbuilt ? (int) $l->yearbuilt : null,
                'latitude'   => (isset($l->lat) && $l->lat != 0) ? (float) $l->lat : null,
                'longitude'  => (isset($l->lng) && $l->lng != 0) ? (float) $l->lng : null,
            ]);
        } catch (\Throwable $e) {
            return collect();
        }
    }

    // ────────────────────────────────────────────────────────────────
    // Area Comparison Pages (e.g. "South Surrey vs White Rock")
    // ────────────────────────────────────────────────────────────────

    private function formatAreaComparison($c): array
    {
        return [
            'id'                   => (int) $c->id,
            'agent_id'             => (int) $c->agent_id,
            'slug'                 => $c->slug,
            'title'                => $c->title,
            'intro'                => $c->intro,
            'area_a_subarea_slug'  => $c->area_a_subarea_slug,
            'area_a_label'         => $c->area_a_label,
            'area_a_buyer_profile' => $c->area_a_buyer_profile,
            'area_a_pros'          => $c->area_a_pros ? json_decode($c->area_a_pros, true) : [],
            'area_a_cons'          => $c->area_a_cons ? json_decode($c->area_a_cons, true) : [],
            'area_b_subarea_slug'  => $c->area_b_subarea_slug,
            'area_b_label'         => $c->area_b_label,
            'area_b_buyer_profile' => $c->area_b_buyer_profile,
            'area_b_pros'          => $c->area_b_pros ? json_decode($c->area_b_pros, true) : [],
            'area_b_cons'          => $c->area_b_cons ? json_decode($c->area_b_cons, true) : [],
            'verdict'              => $c->verdict,
            'status'               => $c->status,
            'meta_title'           => $c->meta_title,
            'meta_description'     => $c->meta_description,
            'created_at'           => $c->created_at,
            'updated_at'           => $c->updated_at,
        ];
    }

    /**
     * Builds one side of a comparison, enriching staff-authored copy with
     * live market stats pulled from the existing neighbourhoodDetail() logic.
     */
    private function buildComparisonSide(string $agentSlug, ?string $subareaSlug, ?string $label, ?string $buyerProfile, ?string $prosJson, ?string $consJson): array
    {
        $widget = null;
        $byType = [];
        $city   = null;
        if ($subareaSlug) {
            try {
                $detailResp = $this->neighbourhoodDetail($agentSlug, $subareaSlug);
                if ($detailResp->getStatusCode() === 200) {
                    $detail = json_decode($detailResp->getContent(), true);
                    $widget = $detail['widget'] ?? null;
                    $byType = $detail['by_type'] ?? [];
                    $city   = $detail['city'] ?? null;
                }
            } catch (\Throwable $e) {
                // Live stats optional -- fall back to null so the page still renders.
            }
        }

        return [
            'subarea_slug'  => $subareaSlug,
            'label'         => $label,
            'city'          => $city,
            'widget'        => $widget,
            'by_type'       => $byType,
            'buyer_profile' => $buyerProfile,
            'pros'          => $prosJson ? json_decode($prosJson, true) : [],
            'cons'          => $consJson ? json_decode($consJson, true) : [],
        ];
    }

    /**
     * GET /api-internal/agent/{slug}/area-comparisons
     */
    public function areaComparisonsList(string $slug): JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $rows = DB::table('agent_area_comparisons')
            ->where('agent_id', $agent->id)
            ->where('status', 'published')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($rows->map(fn ($c) => [
            'slug'             => $c->slug,
            'title'            => $c->title,
            'intro'            => $c->intro,
            'area_a_label'     => $c->area_a_label,
            'area_b_label'     => $c->area_b_label,
            'meta_title'       => $c->meta_title,
            'meta_description' => $c->meta_description,
        ]));
    }

    /**
     * GET /api-internal/agent/{slug}/area-comparisons/{comparisonSlug}
     */
    public function areaComparisonDetail(string $slug, string $comparisonSlug): JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $row = DB::table('agent_area_comparisons')
            ->where('agent_id', $agent->id)
            ->where('slug', $comparisonSlug)
            ->where('status', 'published')
            ->first();
        if (! $row) return response()->json(['error' => 'Comparison not found'], 404);

        return response()->json([
            'slug'             => $row->slug,
            'title'            => $row->title,
            'intro'            => $row->intro,
            'verdict'          => $row->verdict,
            'meta_title'       => $row->meta_title,
            'meta_description' => $row->meta_description,
            'area_a'           => $this->buildComparisonSide($slug, $row->area_a_subarea_slug, $row->area_a_label, $row->area_a_buyer_profile, $row->area_a_pros, $row->area_a_cons),
            'area_b'           => $this->buildComparisonSide($slug, $row->area_b_subarea_slug, $row->area_b_label, $row->area_b_buyer_profile, $row->area_b_pros, $row->area_b_cons),
        ]);
    }

    public function adminAreaComparisonsList(Request $req, int $agentId): JsonResponse
    {
        if ($req->header('X-Admin-Secret') !== config('app.admin_api_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $rows = DB::table('agent_area_comparisons')
            ->where('agent_id', $agentId)
            ->orderByDesc('updated_at')
            ->get();
        return response()->json($rows->map(fn ($c) => $this->formatAreaComparison($c)));
    }

    public function adminAreaComparisonsCreate(Request $req, int $agentId): JsonResponse
    {
        if ($req->header('X-Admin-Secret') !== config('app.admin_api_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $data = $req->all();
        if (empty($data['slug']) || empty($data['title'])) {
            return response()->json(['error' => 'slug and title are required'], 422);
        }
        $id = DB::table('agent_area_comparisons')->insertGetId([
            'agent_id'             => $agentId,
            'slug'                 => $data['slug'],
            'title'                => $data['title'],
            'intro'                => $data['intro'] ?? null,
            'area_a_subarea_slug'  => $data['area_a_subarea_slug'] ?? null,
            'area_a_label'         => $data['area_a_label'] ?? null,
            'area_a_buyer_profile' => $data['area_a_buyer_profile'] ?? null,
            'area_a_pros'          => json_encode($data['area_a_pros'] ?? []),
            'area_a_cons'          => json_encode($data['area_a_cons'] ?? []),
            'area_b_subarea_slug'  => $data['area_b_subarea_slug'] ?? null,
            'area_b_label'         => $data['area_b_label'] ?? null,
            'area_b_buyer_profile' => $data['area_b_buyer_profile'] ?? null,
            'area_b_pros'          => json_encode($data['area_b_pros'] ?? []),
            'area_b_cons'          => json_encode($data['area_b_cons'] ?? []),
            'verdict'              => $data['verdict'] ?? null,
            'status'               => $data['status'] ?? 'draft',
            'meta_title'           => $data['meta_title'] ?? null,
            'meta_description'     => $data['meta_description'] ?? null,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
        $row = DB::table('agent_area_comparisons')->where('id', $id)->first();
        return response()->json($this->formatAreaComparison($row), 201);
    }

    public function adminAreaComparisonsUpdate(Request $req, int $agentId, int $comparisonId): JsonResponse
    {
        if ($req->header('X-Admin-Secret') !== config('app.admin_api_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $row = DB::table('agent_area_comparisons')->where('id', $comparisonId)->where('agent_id', $agentId)->first();
        if (! $row) return response()->json(['error' => 'Not found'], 404);

        $data   = $req->all();
        $update = ['updated_at' => now()];
        $fields = [
            'slug', 'title', 'intro',
            'area_a_subarea_slug', 'area_a_label', 'area_a_buyer_profile',
            'area_b_subarea_slug', 'area_b_label', 'area_b_buyer_profile',
            'verdict', 'status', 'meta_title', 'meta_description',
        ];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) $update[$f] = $data[$f];
        }
        if (array_key_exists('area_a_pros', $data)) $update['area_a_pros'] = json_encode($data['area_a_pros']);
        if (array_key_exists('area_a_cons', $data)) $update['area_a_cons'] = json_encode($data['area_a_cons']);
        if (array_key_exists('area_b_pros', $data)) $update['area_b_pros'] = json_encode($data['area_b_pros']);
        if (array_key_exists('area_b_cons', $data)) $update['area_b_cons'] = json_encode($data['area_b_cons']);

        DB::table('agent_area_comparisons')->where('id', $comparisonId)->update($update);
        $updated = DB::table('agent_area_comparisons')->where('id', $comparisonId)->first();
        return response()->json($this->formatAreaComparison($updated));
    }

    public function adminAreaComparisonsDelete(Request $req, int $agentId, int $comparisonId): JsonResponse
    {
        if ($req->header('X-Admin-Secret') !== config('app.admin_api_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $deleted = DB::table('agent_area_comparisons')->where('id', $comparisonId)->where('agent_id', $agentId)->delete();
        if (! $deleted) return response()->json(['error' => 'Not found'], 404);
        return response()->json(['ok' => true]);
    }

    // ────────────────────────────────────────────────────────────────
    // "Best Of" Curated List Pages (e.g. "Best condo buildings in White Rock")
    // ────────────────────────────────────────────────────────────────

    private function formatBestOfList($l): array
    {
        return [
            'id'               => (int) $l->id,
            'agent_id'         => (int) $l->agent_id,
            'slug'             => $l->slug,
            'title'            => $l->title,
            'intro'            => $l->intro,
            'kind'             => $l->kind,
            'items'            => $l->items ? json_decode($l->items, true) : [],
            'status'           => $l->status,
            'meta_title'       => $l->meta_title,
            'meta_description' => $l->meta_description,
            'created_at'       => $l->created_at,
            'updated_at'       => $l->updated_at,
        ];
    }

    /**
     * GET /api-internal/agent/{slug}/best-of
     */
    public function bestOfListsList(string $slug): JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $rows = DB::table('agent_best_of_lists')
            ->where('agent_id', $agent->id)
            ->where('status', 'published')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($rows->map(function ($l) {
            $items = $l->items ? json_decode($l->items, true) : [];
            return [
                'slug'             => $l->slug,
                'title'            => $l->title,
                'intro'            => $l->intro,
                'kind'             => $l->kind,
                'item_count'       => is_array($items) ? count($items) : 0,
                'meta_title'       => $l->meta_title,
                'meta_description' => $l->meta_description,
            ];
        }));
    }

    /**
     * GET /api-internal/agent/{slug}/best-of/{listSlug}
     */
    public function bestOfListDetail(string $slug, string $listSlug): JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $row = DB::table('agent_best_of_lists')
            ->where('agent_id', $agent->id)
            ->where('slug', $listSlug)
            ->where('status', 'published')
            ->first();
        if (! $row) return response()->json(['error' => 'List not found'], 404);

        return response()->json($this->formatBestOfList($row));
    }

    public function adminBestOfListsList(Request $req, int $agentId): JsonResponse
    {
        if ($req->header('X-Admin-Secret') !== config('app.admin_api_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $rows = DB::table('agent_best_of_lists')
            ->where('agent_id', $agentId)
            ->orderByDesc('updated_at')
            ->get();
        return response()->json($rows->map(fn ($l) => $this->formatBestOfList($l)));
    }

    public function adminBestOfListsCreate(Request $req, int $agentId): JsonResponse
    {
        if ($req->header('X-Admin-Secret') !== config('app.admin_api_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $data = $req->all();
        if (empty($data['slug']) || empty($data['title'])) {
            return response()->json(['error' => 'slug and title are required'], 422);
        }
        $id = DB::table('agent_best_of_lists')->insertGetId([
            'agent_id'         => $agentId,
            'slug'             => $data['slug'],
            'title'            => $data['title'],
            'intro'            => $data['intro'] ?? null,
            'kind'             => $data['kind'] ?? 'building',
            'items'            => json_encode($data['items'] ?? []),
            'status'           => $data['status'] ?? 'draft',
            'meta_title'       => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
        $row = DB::table('agent_best_of_lists')->where('id', $id)->first();
        return response()->json($this->formatBestOfList($row), 201);
    }

    public function adminBestOfListsUpdate(Request $req, int $agentId, int $listId): JsonResponse
    {
        if ($req->header('X-Admin-Secret') !== config('app.admin_api_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $row = DB::table('agent_best_of_lists')->where('id', $listId)->where('agent_id', $agentId)->first();
        if (! $row) return response()->json(['error' => 'Not found'], 404);

        $data   = $req->all();
        $update = ['updated_at' => now()];
        $fields = ['slug', 'title', 'intro', 'kind', 'status', 'meta_title', 'meta_description'];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) $update[$f] = $data[$f];
        }
        if (array_key_exists('items', $data)) $update['items'] = json_encode($data['items']);

        DB::table('agent_best_of_lists')->where('id', $listId)->update($update);
        $updated = DB::table('agent_best_of_lists')->where('id', $listId)->first();
        return response()->json($this->formatBestOfList($updated));
    }

    public function adminBestOfListsDelete(Request $req, int $agentId, int $listId): JsonResponse
    {
        if ($req->header('X-Admin-Secret') !== config('app.admin_api_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $deleted = DB::table('agent_best_of_lists')->where('id', $listId)->where('agent_id', $agentId)->delete();
        if (! $deleted) return response()->json(['error' => 'Not found'], 404);
        return response()->json(['ok' => true]);
    }


    /**
     * GET /api-internal/admin/users
     * All registered site users across all agents (super-admin).
     * Optional query params: ?agent_id=, ?search=
     */
    public function adminUsers(\Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $agentId = $req->query('agent_id');
        $search  = $req->query('search');

        $query = \Illuminate\Support\Facades\DB::table('users')
            ->join('agents', 'users.agent_id', '=', 'agents.id')
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.phone',
                'users.agent_id',
                'agents.name as agent_name',
                'agents.slug as agent_slug',
                'users.email_verified_at',
                'users.phone_verified_at',
                'users.google_id',
                'users.created_at'
            )
            ->where(function ($q) { $q->whereNull('users.uid')->orWhere('users.uid', ''); })
            ->orderByDesc('users.created_at')
            ->limit(500);

        if ($agentId) {
            $query->where('users.agent_id', (int) $agentId);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.email', 'like', '%' . $search . '%')
                  ->orWhere('users.first_name', 'like', '%' . $search . '%')
                  ->orWhere('users.last_name', 'like', '%' . $search . '%')
                  ->orWhere('users.phone', 'like', '%' . $search . '%');
            });
        }

        $users = $query->get()->map(function ($u) {
            return [
                'id'             => $u->id,
                'name'           => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                'first_name'     => $u->first_name,
                'last_name'      => $u->last_name,
                'email'          => $u->email,
                'phone'          => $u->phone,
                'agent_id'       => $u->agent_id,
                'agent_name'     => $u->agent_name,
                'agent_slug'     => $u->agent_slug,
                'email_verified' => !is_null($u->email_verified_at),
                'phone_verified' => !is_null($u->phone_verified_at),
                'google_linked'  => !is_null($u->google_id),
                'created_at'     => $u->created_at,
            ];
        });

        $byAgent = \Illuminate\Support\Facades\DB::table('users')
            ->join('agents', 'users.agent_id', '=', 'agents.id')
            ->where(function ($q) { $q->whereNull('users.uid')->orWhere('users.uid', ''); })
            ->selectRaw('users.agent_id, agents.name as agent_name, agents.slug as agent_slug, count(*) as total')
            ->groupBy('users.agent_id', 'agents.name', 'agents.slug')
            ->orderByDesc('total')
            ->get()
            ->map(function ($r) {
                return [
                    'agent_id'   => $r->agent_id,
                    'agent_name' => $r->agent_name,
                    'agent_slug' => $r->agent_slug,
                    'total'      => (int) $r->total,
                ];
            });

        return response()->json([
            'users'    => $users,
            'by_agent' => $byAgent,
            'total'    => $users->count(),
        ]);
    }

    /**
     * GET /api-internal/admin/agents/{id}/users
     * Registered users scoped to a specific agent site.
     */
    public function agentUsers(int $id): \Illuminate\Http\JsonResponse
    {
        $agent = \App\Models\Agent::find($id);
        if (!$agent) return response()->json(['error' => 'Not found'], 404);

        $users = \Illuminate\Support\Facades\DB::table('users')
            ->where('agent_id', $id)
            ->where(function ($q) { $q->whereNull('uid')->orWhere('uid', ''); })
            ->orderByDesc('created_at')
            ->limit(500)
            ->get()
            ->map(function ($u) {
                return [
                    'id'             => $u->id,
                    'name'           => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                    'first_name'     => $u->first_name,
                    'last_name'      => $u->last_name,
                    'email'          => $u->email,
                    'phone'          => $u->phone,
                    'email_verified' => !is_null($u->email_verified_at),
                    'phone_verified' => !is_null($u->phone_verified_at),
                    'google_linked'  => !is_null($u->google_id),
                    'created_at'     => $u->created_at,
                ];
            });

        return response()->json([
            'agent_id'   => $id,
            'agent_name' => $agent->name,
            'agent_slug' => $agent->slug,
            'users'      => $users,
            'total'      => $users->count(),
        ]);
    }


    public function soldStats(Request $request, $slug)
    {
        $agent = Agent::where('slug', $slug)->firstOrFail();
        $mlsIds = DB::table('agent_mls_ids')->where('agent_id', $agent->id)->pluck('mls_id');
        if ($mlsIds->isEmpty()) {
            return response()->json(['sold_count' => 0, 'total_volume' => 0, 'avg_sale_to_list' => 0, 'best_sale_to_list' => 0, 'years' => 5, 'cities' => []]);
        }

        $cacheKey = 'sold_stats_v1_' . $slug;
        $data = Cache::remember($cacheKey, 10800, function () use ($mlsIds) {
            $cutoff = date('Y-m-d', strtotime('-5 years'));
            $cities = ['Port Coquitlam', 'Coquitlam', 'Port Moody'];
            $rows = DB::connection('mysql_mlsr')
                ->table('mlsr_listings_master')
                ->whereIn('agent_id', $mlsIds)
                ->where('status', 'Sold')
                ->whereIn('city', $cities)
                ->where('sold_date', '>=', $cutoff)
                ->where('soldprice_2', '>', 0)
                ->where('listprice_2', '>', 0)
                ->get(['soldprice_2', 'listprice_2', 'city']);

            if ($rows->isEmpty()) {
                return ['sold_count' => 0, 'total_volume' => 0, 'avg_sale_to_list' => 0, 'best_sale_to_list' => 0, 'years' => 5, 'cities' => []];
            }

            $ratios = $rows->map(function ($r) {
                return round($r->soldprice_2 / $r->listprice_2 * 100, 1);
            });

            $cityStats = [];
            foreach ($cities as $city) {
                $cityRows = $rows->filter(function ($r) use ($city) { return $r->city === $city; });
                if ($cityRows->isEmpty()) continue;
                $cityRatios = $cityRows->map(function ($r) {
                    return round($r->soldprice_2 / $r->listprice_2 * 100, 1);
                });
                $cityStats[] = [
                    'name'         => $city,
                    'sold_count'   => $cityRows->count(),
                    'total_volume' => (int) $cityRows->sum('soldprice_2'),
                    'avg_price'    => (int) round($cityRows->avg('soldprice_2')),
                    'avg_ratio'    => round($cityRatios->avg(), 1),
                ];
            }

            return [
                'sold_count'        => $rows->count(),
                'total_volume'      => (int) $rows->sum('soldprice_2'),
                'avg_sale_to_list'  => round($ratios->avg(), 1),
                'best_sale_to_list' => round($ratios->max(), 1),
                'years'             => 5,
                'cities'            => $cityStats,
            ];
        });

        return response()->json($data);
    }

    /**
     * GET /api-internal/market-board-report?board=gvr&city=Surrey&type=all
     * Returns market stats + 12-month trend for a real-estate board and optional city filter.
     * No agent context — board-wide data, cached 1 hour.
     */
    public function boardMarketReport(Request $request): \Illuminate\Http\JsonResponse
    {
        $board = strtolower($request->query('board', 'gvr'));
        $city  = $request->query('city', 'all');
        $type  = strtolower($request->query('type', 'all'));

        $boardMap = self::boardCityMap();
        if (! isset($boardMap[$board])) {
            return response()->json(['error' => 'Unknown board'], 400);
        }

        $boardLabel = $boardMap[$board]['label'];
        $allCities  = $boardMap[$board]['cities'];
        $cityFilter = ($city !== 'all') ? [$city] : $allCities;

        $typeFilters = [
            'apartment' => ['Apartment', 'Apartment/Condo'],
            'townhouse' => ['Townhouse', 'Townhouse/Multi-Family', 'Row House (Non-Strata)'],
            'house'     => ['House', 'Detached', 'House/Single Family', 'Single Family Detached'],
            'duplex'    => ['Duplex', 'Half Duplex'],
        ];

        $cacheKey = 'board_report_v2_' . $board . '_' . preg_replace('/[^a-z0-9]/', '_', strtolower($city)) . '_' . $type;
        $result = Cache::remember($cacheKey, 3600, function () use ($cityFilter, $type, $typeFilters, $board, $boardLabel, $city) {
            $cut30 = now()->subDays(30)->format('Y-m-d');
            $cut12 = now()->subMonths(12)->format('Y-m-d');

            $empty = [
                'board'       => $board,
                'board_label' => $boardLabel,
                'city'        => $city,
                'type'        => $type,
                'has_data'    => false,
                'overall'     => [
                    'active'            => 0,
                    'sold_30d'          => 0,
                    'median_sold_price' => 0,
                    'sale_to_list'      => 0.0,
                    'avg_dom'           => 0,
                    'absorption_rate'   => 9.9,
                    'market_type'       => 'balanced',
                ],
                'by_type'     => [],
                'monthly_trend'         => [],
                'monthly_trend_by_type' => [],
            ];

            $countCheck = DB::connection('mysql_mlsr')
                ->table('mlsr_listings_master')
                ->whereIn('city', $cityFilter)
                ->whereIn('status', ['Active', 'Sold'])
                ->selectRaw('COUNT(*) as c')
                ->first();
            if (! $countCheck || (int) $countCheck->c === 0) {
                return $empty;
            }

            $mtype = fn ($a) => match (true) {
                $a <= 2.5  => 'strong-sellers',
                $a <= 5.0  => 'sellers',
                $a <= 8.33 => 'balanced',
                default    => 'buyers',
            };

            $base = fn () => DB::connection('mysql_mlsr')
                ->table('mlsr_listings_master')
                ->whereIn('city', $cityFilter)
                ->where(fn ($q) => $q->whereNotIn('listingtype', ['Land', 'Mobile'])->orWhereNull('listingtype'))
                ->when(isset($typeFilters[$type]), fn ($q) => $q->whereIn('listingtype', $typeFilters[$type]));

            $activeCount = (int) $base()->where('status', 'Active')->selectRaw('COUNT(*) as c')->value('c');

            $soldRow = $base()->where('status', 'Sold')
                ->where('sold_date', '>=', $cut30)
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->selectRaw('COUNT(*) as c, AVG(DATEDIFF(sold_date,list_date)) as avg_dom, AVG(soldprice_2/NULLIF(listprice_2,0)*100) as sale_to_list')
                ->first();

            $sold30d    = (int) ($soldRow?->c ?? 0);
            $avgDom     = $soldRow?->avg_dom    ? (int) round($soldRow->avg_dom) : 0;
            $saleToList = $soldRow?->sale_to_list ? round((float) $soldRow->sale_to_list, 1) : 0.0;
            $absorption = $sold30d > 0 ? round($activeCount / $sold30d, 2) : 9.9;

            // Median sold price — fetch sorted array, pick middle
            $prices = $base()->where('status', 'Sold')
                ->where('sold_date', '>=', $cut30)
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->orderBy('soldprice_2')->pluck('soldprice_2')->toArray();
            $cnt = count($prices);
            $medianPrice = $cnt === 0 ? 0
                : ($cnt % 2 === 0
                    ? (int) round(($prices[$cnt/2 - 1] + $prices[$cnt/2]) / 2)
                    : (int) $prices[(int) floor($cnt/2)]);

            $overall = [
                'active'            => $activeCount,
                'sold_30d'          => $sold30d,
                'median_sold_price' => $medianPrice,
                'sale_to_list'      => $saleToList,
                'avg_dom'           => $avgDom,
                'absorption_rate'   => (float) $absorption,
                'market_type'       => $mtype($absorption),
            ];

            $typeActive = $base()->where('status', 'Active')
                ->whereNotNull('listingtype')->where('listingtype', '!=', '')
                ->selectRaw('listingtype as type, COUNT(*) as c')
                ->groupBy('listingtype')->get()->keyBy('type');
            $typeSold   = $base()->where('status', 'Sold')
                ->where('sold_date', '>=', now()->subDays(30)->format('Y-m-d'))
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->whereNotNull('listingtype')->where('listingtype', '!=', '')
                ->selectRaw('listingtype as type, COUNT(*) as c, AVG(soldprice_2) as avg_price, AVG(DATEDIFF(sold_date,list_date)) as avg_dom')
                ->groupBy('listingtype')->get()->keyBy('type');

            $byType = $typeActive->map(function ($row) use ($typeSold, $mtype) {
                $s  = $typeSold->get($row->type);
                $a  = (int) $row->c;
                $sd = (int) ($s?->c ?? 0);
                $ab = $sd > 0 ? round($a / $sd, 2) : 9.9;
                return [
                    'type'            => $row->type,
                    'active'          => $a,
                    'sold_30d'        => $sd,
                    'avg_sold_price'  => $s?->avg_price ? (int) round($s->avg_price) : 0,
                    'avg_dom'         => $s?->avg_dom   ? (int) round($s->avg_dom)   : 0,
                    'absorption_rate' => (float) $ab,
                    'market_type'     => $mtype($ab),
                ];
            })->values()->toArray();

            $trend = $base()->where('status', 'Sold')
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->whereNotNull('sold_date')
                ->where('sold_date', '>=', now()->subMonths(12)->format('Y-m-d'))
                ->selectRaw("DATE_FORMAT(sold_date,'%Y-%m') as month, COUNT(*) as sold, AVG(soldprice_2) as avg_price, AVG(DATEDIFF(sold_date,list_date)) as avg_dom, AVG(soldprice_2/NULLIF(livingarea,0)) as avg_ppsf")
                ->groupBy('month')->orderBy('month')->get();

            $typeBucketCase =
                "CASE "
                . "WHEN listingtype IN ('Apartment','Apartment/Condo') THEN 'apartment' "
                . "WHEN listingtype IN ('Townhouse','Townhouse/Multi-Family','Row House (Non-Strata)') THEN 'townhouse' "
                . "WHEN listingtype IN ('House','Detached','House/Single Family','Single Family Detached') THEN 'house' "
                . "WHEN listingtype IN ('Duplex','Half Duplex') THEN 'duplex' "
                . "ELSE NULL END";

            $byTypeTrendRows = $base()->where('status', 'Sold')
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->whereNotNull('sold_date')
                ->where('sold_date', '>=', now()->subMonths(12)->format('Y-m-d'))
                ->whereNotNull('listingtype')->where('listingtype', '!=', '')
                ->selectRaw("DATE_FORMAT(sold_date,'%Y-%m') as month, {$typeBucketCase} as bucket, AVG(soldprice_2) as avg_price, COUNT(*) as sold_count")
                ->groupBy('month', 'bucket')->orderBy('month')->get();

            $byTypeMonths = [];
            foreach ($byTypeTrendRows as $r) {
                if (! $r->bucket) continue;
                if (! isset($byTypeMonths[$r->month])) {
                    $byTypeMonths[$r->month] = ['month' => $r->month, 'apartment' => null, 'townhouse' => null, 'house' => null, 'duplex' => null];
                }
                $byTypeMonths[$r->month][$r->bucket] = $r->avg_price ? (int) round($r->avg_price) : null;
            }
            ksort($byTypeMonths);

            return [
                'board'       => $board,
                'board_label' => $boardLabel,
                'city'        => $city,
                'type'        => $type,
                'has_data'    => true,
                'overall'     => $overall,
                'by_type'     => $byType,
                'monthly_trend' => $trend->map(fn ($p) => [
                    'month'     => $p->month,
                    'sold'      => (int) $p->sold,
                    'avg_price' => (int) round($p->avg_price),
                    'avg_dom'   => (int) round($p->avg_dom),
                    'avg_ppsf'  => $p->avg_ppsf ? round($p->avg_ppsf, 2) : null,
                    'active'    => null,
                ])->values()->all(),
                'monthly_trend_by_type' => array_values($byTypeMonths),
            ];
        });

        return response()->json($result);
    }

    /**
     * GET /api-internal/market-board-cities?board=gvr
     * Returns cities with available listing data for the given board.
     */
    public function boardCities(Request $request): \Illuminate\Http\JsonResponse
    {
        $board = strtolower($request->query('board', 'gvr'));
        $boardMap = self::boardCityMap();
        if (! isset($boardMap[$board])) {
            return response()->json(['board' => $board, 'label' => '', 'cities' => []]);
        }

        $allCities = $boardMap[$board]['cities'];
        $cacheKey  = 'board_cities_v1_' . $board;

        $cities = Cache::remember($cacheKey, 3600, function () use ($allCities) {
            $rows = DB::connection('mysql_mlsr')
                ->table('mlsr_listings_master')
                ->whereIn('city', $allCities)
                ->whereIn('status', ['Active', 'Sold'])
                ->selectRaw('city, COUNT(*) as cnt')
                ->groupBy('city')
                ->orderByDesc('cnt')
                ->get();
            return $rows->pluck('city')->values()->toArray();
        });

        return response()->json([
            'board'  => $board,
            'label'  => $boardMap[$board]['label'],
            'cities' => $cities,
        ]);
    }

    /**
     * Static map of board → label + canonical city list.
     */
    private static function boardCityMap(): array
    {
        return [
            'gvr' => [
                'label'  => 'Greater Vancouver (GVR)',
                'cities' => [
                    'Surrey', 'White Rock', 'Burnaby', 'Vancouver', 'Richmond',
                    'North Vancouver', 'West Vancouver', 'Coquitlam', 'Port Coquitlam',
                    'Port Moody', 'New Westminster', 'Delta', 'Langley', 'Maple Ridge',
                    'Pitt Meadows', 'Lions Bay', 'Bowen Island',
                ],
            ],
            'fvreb' => [
                'label'  => 'Fraser Valley (FVREB)',
                'cities' => ['Abbotsford', 'Mission', 'Langley Township', 'Chilliwack'],
            ],
            'cadreb' => [
                'label'  => 'Chilliwack & District (CADREB)',
                'cities' => ['Chilliwack', 'Hope', 'Agassiz', 'Harrison Hot Springs'],
            ],
        ];
    }

    /**
     * GET /api-internal/agent/{slug}/news
     * Returns published articles for an agent, paginated.
     * Table: agent_articles (see ArticleGeneratorService)
     */
    public function news(\Illuminate\Http\Request $request, string $slug): \Illuminate\Http\JsonResponse
    {
        $agent = \Illuminate\Support\Facades\DB::table('agents')->where('slug', $slug)->first();
        if (!$agent) return response()->json(['posts' => [], 'total' => 0]);

        $page   = max(1, (int) $request->input('page', 1));
        $limit  = min(50, max(1, (int) $request->input('limit', 12)));
        $offset = ($page - 1) * $limit;

        if (!\Illuminate\Support\Facades\Schema::hasTable('agent_articles')) {
            return response()->json(['posts' => [], 'total' => 0]);
        }

        $q     = \Illuminate\Support\Facades\DB::table('agent_articles')
                    ->where('agent_id', $agent->id)
                    ->where('status', 'published')
                    ->orderByDesc('published_at');
        $total = $q->count();
        $rows  = (clone $q)->offset($offset)->limit($limit)->get();

        $posts = $rows->map(function ($r) {
            $tags = [];
            if (!empty($r->tags)) {
                $decoded = json_decode($r->tags, true);
                $tags = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $r->tags)));
            }
            return [
                'id'           => $r->id,
                'slug'         => $r->slug,
                'title'        => $r->title,
                'excerpt'      => $r->excerpt ?? null,
                'body'         => $r->body ?? null,
                'photo_url'    => $r->photo_url ?? null,
                'published_at' => $r->published_at,
                'category'     => $r->category ?? null,
                'tags'         => $tags,
            ];
        });

        return response()->json(['posts' => $posts, 'total' => $total]);
    }

    /**
     * GET /api-internal/agent/{slug}/news/{postSlug}
     * Returns a single published article by slug.
     */
    public function newsPost(string $slug, string $postSlug): \Illuminate\Http\JsonResponse
    {
        $agent = \Illuminate\Support\Facades\DB::table('agents')->where('slug', $slug)->first();
        if (!$agent) return response()->json(['message' => 'Not found'], 404);

        if (!\Illuminate\Support\Facades\Schema::hasTable('agent_articles')) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $row = \Illuminate\Support\Facades\DB::table('agent_articles')
                ->where('agent_id', $agent->id)
                ->where('slug', $postSlug)
                ->where('status', 'published')
                ->first();

        if (!$row) return response()->json(['message' => 'Not found'], 404);

        $tags = [];
        if (!empty($row->tags)) {
            $decoded = json_decode($row->tags, true);
            $tags = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $row->tags)));
        }

        return response()->json([
            'id'           => $row->id,
            'slug'         => $row->slug,
            'title'        => $row->title,
            'excerpt'      => $row->excerpt ?? null,
            'body'         => $row->body ?? null,
            'photo_url'    => $row->photo_url ?? null,
            'published_at' => $row->published_at,
            'category'     => $row->category ?? null,
            'tags'         => $tags,
        ]);
    }

    /**
     * GET /api-internal/agent/{slug}/pages
     * Returns agent CMS guide pages. Returns empty when table not provisioned.
     */
    /**
     * GET /api-internal/agent/{slug}/page/{pageSlug}
     *
     * The route (api-internal.php:74) has always pointed at ::page(), but the method did
     * not exist, so EVERY agent's every content page 500'd:
     *
     *   randy/tricity/sharene x sellers/buyers/about  ->  all HTTP 500
     *   "Call to undefined method AgentDataController::page()"
     *
     * getPage() in lib/api.ts catches the failure and falls back to FALLBACK_PAGES, which
     * is Randy's content — so Sharene's /sellers was titled "Sell Your Home in South Surrey
     * & White Rock | Randy Dyck" and described his Cloverdale service area. Every agent
     * without their own page row inherited his identity in title and meta description.
     *
     * Shaped identically to pages() below so the two cannot drift.
     */
    public function page(string $slug, string $pageSlug): \Illuminate\Http\JsonResponse
    {
        $agent = \Illuminate\Support\Facades\DB::table('agents')->where('slug', $slug)->first();
        if (! $agent) return response()->json(null);

        if (! \Illuminate\Support\Facades\Schema::hasTable('agent_pages')) {
            return response()->json(null);
        }

        $r = \Illuminate\Support\Facades\DB::table('agent_pages')
            ->where('agent_id', $agent->id)
            ->where('slug', $pageSlug)
            ->first();

        // 200 with null, not 404: the frontend treats a thrown/!ok response as "backend
        // broken" and substitutes fallback content. "This agent has no such page" is an
        // answer, and must not be mistaken for a failure.
        if (! $r) return response()->json(null);

        return response()->json([
            'slug'             => $r->slug,
            'title'            => $r->title ?? null,
            'subtitle'         => $r->subtitle ?? null,
            'hero_image_url'   => $r->hero_image_url ?? null,
            'body'             => $r->body ?? null,
            'blocks'           => json_decode($r->blocks ?? '[]', true) ?? [],
            'cta_label'        => $r->cta_label ?? null,
            'cta_url'          => $r->cta_url ?? null,
            'meta_title'       => $r->meta_title ?? null,
            'meta_description' => $r->meta_description ?? null,
        ]);
    }

    public function pages(string $slug): \Illuminate\Http\JsonResponse
    {
        $agent = \Illuminate\Support\Facades\DB::table('agents')->where('slug', $slug)->first();
        if (!$agent) return response()->json([]);

        if (!\Illuminate\Support\Facades\Schema::hasTable('agent_pages')) {
            return response()->json([]);
        }

        $rows = \Illuminate\Support\Facades\DB::table('agent_pages')
                    ->where('agent_id', $agent->id)
                    ->orderBy('sort_order')
                    ->get();

        return response()->json($rows->map(function ($r) {
            return [
                'slug'             => $r->slug,
                'title'            => $r->title ?? null,
                'subtitle'         => $r->subtitle ?? null,
                'hero_image_url'   => $r->hero_image_url ?? null,
                'body'             => $r->body ?? null,
                'blocks'           => json_decode($r->blocks ?? '[]', true) ?? [],
                'cta_label'        => $r->cta_label ?? null,
                'cta_url'          => $r->cta_url ?? null,
                'meta_title'       => $r->meta_title ?? null,
                'meta_description' => $r->meta_description ?? null,
            ];
        }));
    }



    /**
     * GET /api-internal/agent/{slug}/listing/{listingSlug}/building-compelling-sold
     * Finds the most dramatic recent sold nearby: $10K+ over asking OR sold in <=7 days.
     * Prefers same building; falls back to same subarea + property type.
     */
/**
     * GET /api-internal/agent/{slug}/listing/{listingSlug}/building-compelling-sold
     * Finds the most dramatic recent sold nearby: $10K+ over asking OR sold in <=7 days.
     * "Over asking" = soldprice_2 - listprice_2 (both numeric fields on sold listings).
     * Prefers same building (strata_no + street_number); falls back to same subarea + listingtype.
     * Recency window: sold within last 12 months.
     */
/**
     * GET /api-internal/agent/{slug}/listing/{listingSlug}/building-compelling-sold
     * Finds the most dramatic recent sold nearby: $10K+ over asking OR sold in <=7 days.
     * "Over asking" = soldprice_2 - listprice_2 (both numeric fields on sold listings).
     * Prefers same building (strata_no + street_number); falls back to same subarea + listingtype.
     * Recency window: sold within last 12 months.
     */
    public function buildingCompellingSold(string $slug, string $listingSlug): JsonResponse
    {
        $listing = Listings::withoutGlobalScopes()
            ->where('slug', $listingSlug)
            ->orWhere('listingid', $listingSlug)
            ->first(['listingid', 'strata_no', 'street_number', 'subarea', 'listingtype']);

        if (! $listing) {
            return response()->json(null);
        }

        $recencyCutoff = date('Y-m-d', strtotime('-12 months'));

        $compelling = null;

        // Scope 1: same building (strata_no + street_number)
        if ($listing->strata_no && trim($listing->strata_no) !== '') {
            $compelling = Listings::withoutGlobalScopes()
                ->where('status', 'Sold')
                ->where('strata_no', $listing->strata_no)
                ->where('street_number', $listing->street_number)
                ->where('listingid', '!=', $listing->listingid)
                ->whereNotNull('sold_date')
                ->where('sold_date', '!=', '0000-00-00')
                ->where('sold_date', '>=', $recencyCutoff)
                ->whereNotNull('list_date')
                ->where('list_date', '!=', '0000-00-00')
                ->where('soldprice_2', '>', 0)
                ->where('listprice_2', '>', 0)
                ->where(function ($q) {
                    $q->whereRaw('(soldprice_2 - listprice_2) >= 10000')
                      ->orWhereRaw('DATEDIFF(sold_date, list_date) <= 7');
                })
                ->orderByRaw('(soldprice_2 - listprice_2) DESC')
                ->first(['listingid', 'streetaddress', 'sold_date', 'soldprice_2', 'listprice_2', 'list_date', 'strata_no']);
        }

        // Scope 2: fallback to same subarea + property type
        if (! $compelling && $listing->subarea && $listing->listingtype) {
            $compelling = Listings::withoutGlobalScopes()
                ->where('status', 'Sold')
                ->where('subarea', $listing->subarea)
                ->where('listingtype', $listing->listingtype)
                ->where('listingid', '!=', $listing->listingid)
                ->whereNotNull('sold_date')
                ->where('sold_date', '!=', '0000-00-00')
                ->where('sold_date', '>=', $recencyCutoff)
                ->whereNotNull('list_date')
                ->where('list_date', '!=', '0000-00-00')
                ->where('soldprice_2', '>', 0)
                ->where('listprice_2', '>', 0)
                ->where(function ($q) {
                    $q->whereRaw('(soldprice_2 - listprice_2) >= 10000')
                      ->orWhereRaw('DATEDIFF(sold_date, list_date) <= 7');
                })
                ->orderByRaw('(soldprice_2 - listprice_2) DESC')
                ->first(['listingid', 'streetaddress', 'sold_date', 'soldprice_2', 'listprice_2', 'list_date', 'strata_no']);
        }

        if (! $compelling) {
            return response()->json(null);
        }

        // Extract unit number from address (e.g. "1405 12345 104 Ave" -> "1405")
        $unit = null;
        if ($compelling->streetaddress) {
            $parts = explode(' ', trim($compelling->streetaddress));
            if (count($parts) > 1 && ctype_digit($parts[0])) {
                $unit = $parts[0];
            }
        }

        // Look up building name using the sold listing's own strata_no
        // (may differ from subject listing when falling back to subarea scope)
        $buildingName = null;
        if ($compelling->strata_no && trim($compelling->strata_no) !== '') {
            try {
                $b = Buildings::withoutGlobalScopes()
                    ->where('strata_no', $compelling->strata_no)
                    ->value('name');
                $buildingName = $b ?: null;
            } catch (\Throwable $e) {}
        }

        // over_asking = sold price minus list price (positive = sold over asking)
        $overAsking = (int) ($compelling->soldprice_2 - $compelling->listprice_2);

        $daysOnMarket = null;
        if ($compelling->list_date && $compelling->sold_date) {
            try {
                $listed = new \DateTime($compelling->list_date);
                $sold   = new \DateTime($compelling->sold_date);
                $daysOnMarket = (int) $sold->diff($listed)->days;
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'mls_num'        => $compelling->listingid,
            'unit'           => $unit,
            'building_name'  => $buildingName,
            'sold_date'      => $compelling->sold_date,
            'over_asking'    => $overAsking,
            'days_on_market' => $daysOnMarket,
        ]);
    }



    /**
     * Restored from AgentDataController.php.bak.20260722162412 (lines 6268-6418).
     *
     * The frontend has always called this - getUnifiedSolds() in lib/api.ts hits
     * /api-internal/agent/{slug}/buyer-solds - but the method and its route were
     * dropped from the live controller, so every request 404'd. getUnifiedSolds
     * catches the failure and returns an empty set, so the "Recently Sold by"
     * gallery silently rendered nothing for every agent rather than erroring.
     */
    public function buyerSolds(\Illuminate\Http\Request $request, string $slug): \Illuminate\Http\JsonResponse
    {
        $agent = \App\Models\Agent::with(['settings', 'mls_ids', 'territories'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['items' => [], 'total_count' => 0, 'total_volume' => 0, 'page' => 1, 'limit' => 24]);

        $page  = max(1, (int) $request->query('page', 1));
        $limit = 24;

        $cacheKey = 'unified_solds_v1_' . $slug . '_p' . $page;
        $data = Cache::remember($cacheKey, 1800, function () use ($agent, $page, $limit) {
            $mlsIds  = DB::table('agent_mls_ids')->where('agent_id', $agent->id)->pluck('mls_id')->toArray();
            $cutoff  = date('Y-m-d', strtotime('-5 years'));
            $allItems = [];

            // Listing-side solds
            if (! empty($mlsIds)) {
                $rows = DB::connection('mysql_mlsr')
                    ->table('mlsr_listings_master')
                    ->whereIn('agent_id', $mlsIds)
                    ->where('status', 'Sold')
                    ->where('soldprice_2', '>', 0)
                    ->where('sold_date', '>=', $cutoff)
                    ->orderByDesc('sold_date')
                    ->limit(200)
                    ->get(['listingid', 'streetaddress', 'city', 'soldprice_2', 'sold_date',
                           'listingtype', 'bedrooms', 'bathstotal', 'livingarea', 'livingarea_2',
                           'mainpicurl', 'thumbnailurl']);

                foreach ($rows as $r) {
                    $allItems[] = [
                        'role'            => 'listing',
                        'mls_id'          => $r->listingid,
                        'address'         => $r->streetaddress,
                        'city'            => $r->city,
                        'sold_price'      => $r->soldprice_2 > 0 ? (int) $r->soldprice_2 : null,
                        'sold_date'       => $r->sold_date,
                        'type'            => $r->listingtype ?? null,
                        'beds'            => $r->bedrooms ? (int) $r->bedrooms : null,
                        'baths'           => $r->bathstotal ? (float) $r->bathstotal : null,
                        'sqft'            => (int) str_replace(',', '', (string) ($r->livingarea_2 ?: $r->livingarea ?: '0')) ?: null,
                        'photo_url'       => (str_replace('http://', 'https://', $r->mainpicurl ?: $r->thumbnailurl ?: '') ?: null),
                        'is_private_sale' => false,
                        '_sort'           => $r->sold_date ?? '1970-01-01',
                    ];
                }
            }

            // Buyer-represented solds
            $buyerRows = DB::table('agent_buyer_solds')
                ->where('agent_id', $agent->id)
                ->where('status', 'confirmed')
                ->get();

            $mlsLookupIds = $buyerRows->filter(function ($b) { return ! empty($b->mls_id) && ! $b->is_private_sale; })
                ->pluck('mls_id')->toArray();

            $mlsDetails = [];
            if (! empty($mlsLookupIds)) {
                $listings = DB::connection('mysql_mlsr')
                    ->table('mlsr_listings_master')
                    ->whereIn('listingid', $mlsLookupIds)
                    ->get(['listingid', 'streetaddress', 'city', 'soldprice_2', 'sold_date',
                           'listingtype', 'bedrooms', 'bathstotal', 'livingarea', 'livingarea_2',
                           'mainpicurl', 'thumbnailurl']);
                foreach ($listings as $l) {
                    $mlsDetails[$l->listingid] = $l;
                }
            }

            foreach ($buyerRows as $b) {
                if ($b->is_private_sale) {
                    $allItems[] = [
                        'role'            => 'buyer',
                        'mls_id'          => null,
                        'address'         => $b->address_raw,
                        'city'            => null,
                        'sold_price'      => null,
                        'sold_date'       => null,
                        'type'            => null,
                        'beds'            => null,
                        'baths'           => null,
                        'sqft'            => null,
                        'photo_url'       => null,
                        'is_private_sale' => true,
                        '_sort'           => $b->created_at ?? '1970-01-01',
                    ];
                } elseif (! empty($b->mls_id) && isset($mlsDetails[$b->mls_id])) {
                    $l = $mlsDetails[$b->mls_id];
                    $allItems[] = [
                        'role'            => 'buyer',
                        'mls_id'          => $l->listingid,
                        'address'         => $l->streetaddress,
                        'city'            => $l->city,
                        'sold_price'      => $l->soldprice_2 > 0 ? (int) $l->soldprice_2 : null,
                        'sold_date'       => $l->sold_date,
                        'type'            => $l->listingtype ?? null,
                        'beds'            => $l->bedrooms ? (int) $l->bedrooms : null,
                        'baths'           => $l->bathstotal ? (float) $l->bathstotal : null,
                        'sqft'            => (int) str_replace(',', '', (string) ($l->livingarea_2 ?: $l->livingarea ?: '0')) ?: null,
                        'photo_url'       => (str_replace('http://', 'https://', $l->mainpicurl ?: $l->thumbnailurl ?: '') ?: null),
                        'is_private_sale' => false,
                        '_sort'           => $l->sold_date ?? '1970-01-01',
                    ];
                } else {
                    $allItems[] = [
                        'role'            => 'buyer',
                        'mls_id'          => $b->mls_id,
                        'address'         => $b->address_raw,
                        'city'            => null,
                        'sold_price'      => null,
                        'sold_date'       => null,
                        'type'            => null,
                        'beds'            => null,
                        'baths'           => null,
                        'sqft'            => null,
                        'photo_url'       => null,
                        'is_private_sale' => false,
                        '_sort'           => $b->created_at ?? '1970-01-01',
                    ];
                }
            }

            // Sort by sold_date DESC, paginate
            usort($allItems, function ($a, $b) {
                return strcmp($b['_sort'], $a['_sort']);
            });

            $totalCount  = count($allItems);
            $soldItems   = array_filter($allItems, function ($i) { return $i['sold_price'] !== null; });
            $totalVolume = (int) array_sum(array_column(array_values($soldItems), 'sold_price'));

            return [
                'all'          => $allItems,
                'total_count'  => $totalCount,
                'total_volume' => $totalVolume,
            ];
        });

        $all    = $data['all'];
        $offset = ($page - 1) * $limit;
        $items  = array_slice($all, $offset, $limit);
        $items  = array_map(function ($i) { unset($i['_sort']); return $i; }, $items);

        return response()->json([
            'items'        => array_values($items),
            'total_count'  => $data['total_count'],
            'total_volume' => $data['total_volume'],
            'page'         => $page,
            'limit'        => $limit,
        ]);
    }
}
