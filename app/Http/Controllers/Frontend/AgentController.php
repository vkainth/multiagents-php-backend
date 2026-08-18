<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Helpers\AgentContext;
use App\Models\Agent;
use App\Models\Listings;
use App\Models\Buildings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * AgentController
 *
 * Handles all agent white-label site pages. Every method:
 *   1. Resolves the agent (from AgentContext, already set by ResolveAgent middleware)
 *   2. Prepares page-specific data — scoped to the agent's territory via inAgentTerritory()
 *   3. Returns the correct theme view: themes.{theme_slug}.{page}
 *
 * All Listings/Buildings DB queries are wrapped in try/catch so the page
 * renders gracefully (with empty data) when the external MySQL DB is unavailable
 * (e.g. local dev using SQLite).
 */
class AgentController extends Controller
{
    protected function resolveAgent(?string $slug = null): Agent
    {
        $agent = AgentContext::current();

        if (!$agent && $slug) {
            $agent = Agent::where('slug', $slug)->where('status', 'active')->firstOrFail();
        }

        if (!$agent) {
            abort(404);
        }

        return $agent;
    }

    protected function view(Agent $agent, string $template, array $data = [])
    {
        $territories = $agent->territories()->get()->groupBy('city');

        $common = [
            'agent'            => $agent,
            'territories'      => $territories,
            'agentTheme'       => $agent->theme_slug ?? 'classic-dark',
            'agentThemeColor'  => $agent->theme_color ?? '#c9a96e',
            'testimonialCount' => $agent->testimonials()->count(),
        ];

        return view("themes.{$agent->theme_slug}.{$template}", array_merge($common, $data));
    }

    /**
     * Whether the primary Listings DB host (mysql_boards) is reachable.
     * Cached per-process so we only probe once per request.
     */
    private static ?bool $listingsDbReachable = null;

    protected function listingsDbReachable(): bool
    {
        if (self::$listingsDbReachable !== null) {
            return self::$listingsDbReachable;
        }

        // Read hosts from config; prefer the read-slave host since that's what
        // SELECT queries use on the mysql_boards connection.
        $host = config('database.connections.mysql_boards.read.host.0')
            ?? config('database.connections.mysql_boards.host', '127.0.0.1');
        $port = (int) config('database.connections.mysql_boards.port', 3306);

        $fp = @fsockopen($host, $port, $errno, $errstr, 1.0);
        if ($fp) {
            fclose($fp);
            self::$listingsDbReachable = true;
        } else {
            self::$listingsDbReachable = false;
        }

        return self::$listingsDbReachable;
    }

    /**
     * Safe wrapper: executes a listings query callback and returns the result,
     * or $default if the DB host is unreachable or any exception is thrown.
     * A 1-second fsockopen pre-check avoids the 30-second PDO connect hang
     * in local dev where the external MySQL host is not available.
     */
    protected function safeListings(callable $fn, mixed $default = null): mixed
    {
        if (!$this->listingsDbReachable()) {
            return $default;
        }

        try {
            return $fn();
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Compute territory-wide market stats (active count, sold 30d, avg sold price, avg DOM, list-to-sale).
     * Cached 30 minutes per agent. Returns zeroed array if DB unavailable.
     */
    protected function buildStatsBar(Agent $agent): array
    {
        $empty = [
            'active_count'   => 0,
            'sold_count'     => 0,
            'avg_sold_price' => 0,
            'avg_dom'        => 0.0,
            'list_to_sale'   => 0.0,
        ];

        return $this->safeListings(function () use ($agent, $empty) {
            $cacheKey = 'agent_stats_bar_' . $agent->id . '_' . date('YmdH');

            return Cache::remember($cacheKey, 1800, function () use ($agent, $empty) {
                try {
                    $baseActive = Listings::inAgentTerritory($agent)->where('status', 'Active');
                    $baseSold   = Listings::inAgentTerritory($agent)
                        ->where('status', 'Sold')
                        ->where('sold_date', '>=', Carbon::now()->subDays(30)->toDateString());

                    $activeCount = (clone $baseActive)->count();
                    $soldCount   = (clone $baseSold)->count();

                    $avgRow = (clone $baseSold)->toBase()
                        ->selectRaw('AVG(soldprice_2) as avg_sold, AVG(DATEDIFF(sold_date, list_date)) as avg_dom, AVG(soldprice_2/NULLIF(listprice_2,0))*100 as list_to_sale')
                        ->first();

                    return [
                        'active_count'   => $activeCount,
                        'sold_count'     => $soldCount,
                        'avg_sold_price' => (int) round($avgRow->avg_sold ?? 0),
                        'avg_dom'        => (float) round($avgRow->avg_dom ?? 0, 1),
                        'list_to_sale'   => (float) round($avgRow->list_to_sale ?? 0, 1),
                    ];
                } catch (\Throwable $e) {
                    return $empty;
                }
            });
        }, $empty);
    }

    /**
     * Per-city breakdown for market stats page.
     */
    protected function buildCityStats(Agent $agent): array
    {
        return $this->safeListings(function () use ($agent) {
            $territories = $agent->territories()->get()->groupBy('city');
            $cityStats   = [];

            foreach ($territories->keys() as $city) {
                try {
                    $activeCount = Listings::inAgentTerritory($agent)->where('status', 'Active')->where('city', $city)->count();
                    $soldBase    = Listings::inAgentTerritory($agent)->where('status', 'Sold')
                        ->where('city', $city)
                        ->where('sold_date', '>=', Carbon::now()->subDays(30)->toDateString());

                    $row = (clone $soldBase)->toBase()
                        ->selectRaw('COUNT(*) as cnt, AVG(soldprice_2) as avg_sold, AVG(DATEDIFF(sold_date,list_date)) as avg_dom, AVG(soldprice_2/NULLIF(livingarea_2,0)) as avg_ppsf')
                        ->first();

                    $cityStats[$city] = [
                        'active'         => $activeCount,
                        'sold'           => (int) ($row->cnt ?? 0),
                        'avg_sold_price' => (int) round($row->avg_sold ?? 0),
                        'avg_dom'        => (float) round($row->avg_dom ?? 0, 1),
                        'avg_ppsf'       => (int) round($row->avg_ppsf ?? 0),
                    ];
                } catch (\Throwable $e) {
                    $cityStats[$city] = [
                        'active' => 0, 'sold' => 0,
                        'avg_sold_price' => 0, 'avg_dom' => 0.0, 'avg_ppsf' => 0,
                    ];
                }
            }

            return $cityStats;
        }, []);
    }

    /**
     * Last 24 months of sold stats for the Monthly History section.
     * Always returns exactly 24 entries (newest-first), including zero-activity months.
     * Each element: [year, month, month_label, sold_count, avg_sold_price].
     */
    protected function buildMonthlyHistory(Agent $agent): array
    {
        // Build the canonical 24-month scaffold regardless of DB availability.
        $scaffold = [];
        $now = Carbon::now();
        for ($i = 1; $i <= 24; $i++) {
            $date = $now->copy()->subMonths($i)->startOfMonth();
            $key  = $date->format('Y-n');
            $scaffold[$key] = [
                'year'           => (int) $date->format('Y'),
                'month'          => (int) $date->format('n'),
                'month_label'    => $date->format('F Y'),
                'sold_count'     => 0,
                'avg_sold_price' => 0,
            ];
        }

        $filled = $this->safeListings(function () use ($agent, $scaffold) {
            $cacheKey = 'agent_monthly_history_' . $agent->id . '_' . date('YmdH');

            return Cache::remember($cacheKey, 1800, function () use ($agent, $scaffold) {
                try {
                    $cutoff = Carbon::now()->subMonths(24)->startOfMonth()->toDateString();

                    $rows = Listings::withoutGlobalScopes()
                        ->inAgentTerritory($agent)
                        ->where('status', 'Sold')
                        ->where('sold_date', '>=', $cutoff)
                        ->toBase()
                        ->selectRaw('YEAR(sold_date) as yr, MONTH(sold_date) as mo, COUNT(*) as sold_count, ROUND(AVG(soldprice_2)) as avg_sold')
                        ->groupByRaw('YEAR(sold_date), MONTH(sold_date)')
                        ->get();

                    $result = $scaffold;
                    foreach ($rows as $row) {
                        $key = $row->yr . '-' . $row->mo;
                        if (isset($result[$key])) {
                            $result[$key]['sold_count']     = (int) $row->sold_count;
                            $result[$key]['avg_sold_price'] = (int) ($row->avg_sold ?? 0);
                        }
                    }

                    return array_values($result);
                } catch (\Throwable $e) {
                    return array_values($scaffold);
                }
            });
        }, array_values($scaffold));

        return $filled;
    }

    /**
     * Property-type breakdown for a specific year+month.
     * Returns array keyed by bucket name (Condos, Townhouses, Houses), each with:
     *   sold_count, avg_sold_price, avg_ppsf, avg_dom.
     */
    protected function buildMonthlyReportByType(Agent $agent, int $year, int $month): array
    {
        $empty = ['sold_count' => 0, 'avg_sold_price' => 0, 'avg_ppsf' => 0, 'avg_dom' => 0.0];
        $buckets = [
            'Condos'     => $empty,
            'Townhouses' => $empty,
            'Houses'     => $empty,
        ];

        return $this->safeListings(function () use ($agent, $year, $month, $buckets, $empty) {
            $cacheKey = "agent_monthly_report_{$agent->id}_{$year}_{$month}";

            return Cache::remember($cacheKey, 3600, function () use ($agent, $year, $month, $buckets, $empty) {
                try {
                    $firstDay = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
                    $lastDay  = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

                    $rows = Listings::withoutGlobalScopes()
                        ->inAgentTerritory($agent)
                        ->where('status', 'Sold')
                        ->whereBetween('sold_date', [$firstDay, $lastDay])
                        ->toBase()
                        ->selectRaw('type, COUNT(*) as sold_count, ROUND(AVG(soldprice_2)) as avg_sold, ROUND(AVG(soldprice_2/NULLIF(livingarea_2,0))) as avg_ppsf, ROUND(AVG(DATEDIFF(sold_date,list_date)),1) as avg_dom')
                        ->groupBy('type')
                        ->get();

                    $condoTypes     = ['Apartment'];
                    $townhouseTypes = ['Townhouse', '1/2 Duplex', 'Duplex', 'Triplex', 'Fourplex'];
                    $houseTypes     = ['House', 'Detached', 'Other'];

                    $accum = [
                        'Condos'     => ['sold' => 0, 'price_sum' => 0, 'ppsf_sum' => 0, 'dom_sum' => 0, 'rows' => 0],
                        'Townhouses' => ['sold' => 0, 'price_sum' => 0, 'ppsf_sum' => 0, 'dom_sum' => 0, 'rows' => 0],
                        'Houses'     => ['sold' => 0, 'price_sum' => 0, 'ppsf_sum' => 0, 'dom_sum' => 0, 'rows' => 0],
                    ];

                    foreach ($rows as $row) {
                        $type = $row->type;
                        if (in_array($type, $condoTypes)) {
                            $bucket = 'Condos';
                        } elseif (in_array($type, $townhouseTypes)) {
                            $bucket = 'Townhouses';
                        } elseif (in_array($type, $houseTypes)) {
                            $bucket = 'Houses';
                        } else {
                            continue;
                        }
                        $accum[$bucket]['sold']      += (int) $row->sold_count;
                        $accum[$bucket]['price_sum'] += (int) ($row->avg_sold ?? 0) * (int) $row->sold_count;
                        $accum[$bucket]['ppsf_sum']  += (int) ($row->avg_ppsf ?? 0) * (int) $row->sold_count;
                        $accum[$bucket]['dom_sum']   += (float) ($row->avg_dom ?? 0) * (int) $row->sold_count;
                        $accum[$bucket]['rows']      += (int) $row->sold_count;
                    }

                    $result = [];
                    foreach ($accum as $bucket => $a) {
                        $n = $a['sold'];
                        $result[$bucket] = [
                            'sold_count'     => $n,
                            'avg_sold_price' => $n > 0 ? (int) round($a['price_sum'] / $n) : 0,
                            'avg_ppsf'       => $n > 0 ? (int) round($a['ppsf_sum'] / $n) : 0,
                            'avg_dom'        => $n > 0 ? (float) round($a['dom_sum'] / $n, 1) : 0.0,
                        ];
                    }

                    return $result;
                } catch (\Throwable $e) {
                    return $buckets;
                }
            });
        }, $buckets);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Pages
    // ──────────────────────────────────────────────────────────────────────────

    public function home(string $slug)
    {
        $agent       = $this->resolveAgent($slug);
        $statsBar    = $this->buildStatsBar($agent);
        $territories = $agent->territories()->get()->groupBy('city');

        // Featured: agent's own MLS listings first, then territory-scoped active
        $mlsIds = $agent->mls_ids()->pluck('mls_id');

        $featuredListings = $this->safeListings(function () use ($agent, $mlsIds) {
            if ($mlsIds->isNotEmpty()) {
                $listings = Listings::inAgentTerritory($agent)
                    ->where('status', 'Active')
                    ->whereIn('agent_id', $mlsIds)
                    ->orderByDesc('list_date')
                    ->limit(6)
                    ->get();
                if ($listings->isNotEmpty()) {
                    return $listings;
                }
            }
            return Listings::inAgentTerritory($agent)
                ->where('status', 'Active')
                ->orderByDesc('list_date')
                ->limit(6)
                ->get();
        }, collect());

        // If settings has explicit featured listing IDs, honour those
        $agentSettings = $agent->settings;
        if ($agentSettings && !empty($agentSettings->featured_listing_ids)) {
            $featured = $this->safeListings(
                fn () => Listings::whereIn('listingid', $agentSettings->featured_listing_ids)->get(),
                collect()
            );
            if ($featured->count() > 0) {
                $featuredListings = $featured;
            }
        }

        $recentSolds = $this->safeListings(
            fn () => Listings::inAgentTerritory($agent)
                ->where('status', 'Sold')
                ->orderByDesc('sold_date')
                ->limit(4)
                ->get(),
            collect()
        );

        $testimonials = $agent->testimonials()->limit(5)->get();

        // Per-city active listing counts for the area grid.
        // Single grouped query: withoutGlobalScopes avoids any global filter interference,
        // inAgentTerritory scopes to the agent's territory, groupBy city returns all counts
        // in one round-trip instead of one query per city.
        $cityKeys   = $territories->keys()->toArray();
        $areaCounts = array_fill_keys($cityKeys, 0);
        if (!empty($cityKeys)) {
            $cityRows = $this->safeListings(
                fn () => Listings::withoutGlobalScopes()
                    ->inAgentTerritory($agent)
                    ->where('status', 'Active')
                    ->whereIn('city', $cityKeys)
                    ->toBase()
                    ->selectRaw('city, COUNT(*) as cnt')
                    ->groupBy('city')
                    ->get(),
                collect()
            );
            foreach ($cityRows as $row) {
                if (isset($areaCounts[$row->city])) {
                    $areaCounts[$row->city] = (int) $row->cnt;
                }
            }
        }

        // Featured buildings sorted by active listing count (most active first)
        // Cached per agent + territory fingerprint + hour; auto-busts when territory list changes.
        $featuredBuildings = $this->safeListings(function () use ($agent) {
            $territoryKey = md5(
                $agent->territories
                    ->map(fn ($t) => strtolower($t->city) . '|' . strtolower($t->subarea ?? ''))
                    ->sort()->implode(',')
            );
            $cacheKey = 'agent_featured_buildings_' . $agent->id . '_' . $territoryKey . '_' . date('YmdH');
            return Cache::remember($cacheKey, 3600, function () use ($agent) {
                $boardsDb   = \Illuminate\Support\Facades\DB::connection('mysql_boards')->getDatabaseName();
                $mlsrDb     = \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->getDatabaseName();
                $listTable  = "`{$boardsDb}`.`listings`";
                $addrExpr   = "LOWER(TRIM(CONCAT(COALESCE(`{$mlsrDb}`.`buildings`.street_no,''), ' ', COALESCE(`{$mlsrDb}`.`buildings`.street_name,''))))";
                $activeSubq = "SELECT COUNT(*) FROM {$listTable} l WHERE l.status = 'Active' AND LOWER(TRIM(l.streetaddress)) LIKE CONCAT('%', {$addrExpr}, '%')";
                return Buildings::inAgentTerritory($agent)
                    ->selectRaw("`{$mlsrDb}`.`buildings`.*, ({$activeSubq}) as active_count")
                    ->orderByDesc('active_count')
                    ->orderByDesc('updated')
                    ->limit(12)
                    ->get();
            });
        }, collect());

        return $this->view($agent, 'homepage', compact(
            'statsBar', 'featuredListings', 'recentSolds', 'testimonials', 'areaCounts',
            'featuredBuildings'
        ));
    }

    public function search(string $slug, Request $request)
    {
        $agent  = $this->resolveAgent($slug);
        $status = $request->input('status', 'active');

        $listings = $this->safeListings(function () use ($agent, $request, $status) {
            $query = Listings::inAgentTerritory($agent);

            if ($status === 'sold') {
                $query->where('status', 'Sold');
            } else {
                $query->where('status', 'Active');
            }

            if ($request->filled('city')) {
                $query->where('city', $request->input('city'));
            }
            if ($request->filled('subarea')) {
                $query->where('subarea', $request->input('subarea'));
            }
            if ($request->filled('type')) {
                $type = $request->input('type');
                if ($type === 'House') {
                    $query->whereIn('type', ['House', 'Detached', 'Other', 'Mobile']);
                } elseif ($type === 'Townhouse') {
                    $query->whereIn('type', ['Townhouse', '1/2 Duplex', 'Duplex', 'Triplex', 'Fourplex']);
                } else {
                    $query->where('type', $type);
                }
            }
            if ($request->filled('beds')) {
                $beds = $request->input('beds');
                if (str_contains($beds, '+')) {
                    $query->where('bedrooms', '>=', (int) $beds);
                } else {
                    $query->where('bedrooms', (int) $beds);
                }
            }
            if ($request->filled('pricefrom')) {
                $query->where('listprice_2', '>=', (int) $request->input('pricefrom'));
            }
            if ($request->filled('priceto')) {
                $query->where('listprice_2', '<=', (int) $request->input('priceto'));
            }

            return $query->with('aphoto')
                ->orderByDesc($status === 'sold' ? 'sold_date' : 'list_date')
                ->paginate(24);
        }, new \Illuminate\Pagination\LengthAwarePaginator([], 0, 24));

        $territories = $agent->territories()->get()->groupBy('city');

        return $this->view($agent, 'search', compact('listings', 'territories'));
    }

    public function sold(string $slug, Request $request)
    {
        $request->merge(['status' => 'sold']);
        return $this->search($slug, $request);
    }

    public function buildings(string $slug)
    {
        $agent       = $this->resolveAgent($slug);
        $currentSort = request()->input('sort', 'default');
        if (!in_array($currentSort, ['default', 'popular'])) {
            $currentSort = 'default';
        }

        $buildings = $this->safeListings(function () use ($agent, $currentSort) {
            $boardsDb  = \Illuminate\Support\Facades\DB::connection('mysql_boards')->getDatabaseName();
            $mlsrDb    = \Illuminate\Support\Facades\DB::connection('mysql_mlsr')->getDatabaseName();
            $listTable = "`{$boardsDb}`.`listings`";
            // COALESCE ensures address match works even when street_no or street_name is NULL.
            $addrExpr  = "LOWER(TRIM(CONCAT(COALESCE(`{$mlsrDb}`.`buildings`.street_no,''), ' ', COALESCE(`{$mlsrDb}`.`buildings`.street_name,''))))";

            // Always-needed: count of Active listings matching this building's street address.
            $activeSubq = "SELECT COUNT(*) FROM {$listTable} l
                WHERE l.status = 'Active'
                AND LOWER(TRIM(l.streetaddress)) LIKE CONCAT('%', {$addrExpr}, '%')";

            $selectRaw = "`{$mlsrDb}`.`buildings`.*, ({$activeSubq}) as active_count";

            // Single popularity subquery: sold count * 10 minus capped avg DOM, in one table scan
            // using conditional aggregation (avoids two separate subqueries for sold_12mo & avg_dom).
            if ($currentSort === 'popular') {
                $popularitySubq = "SELECT GREATEST(COALESCE(
                        SUM(CASE WHEN l.status = 'Sold' AND l.sold_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 10 ELSE 0 END)
                        - LEAST(COALESCE(
                            AVG(CASE WHEN l.status = 'Sold' AND l.sold_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                                THEN DATEDIFF(l.sold_date, l.list_date) ELSE NULL END),
                            30), 90),
                        0), 0)
                    FROM {$listTable} l
                    WHERE LOWER(TRIM(l.streetaddress)) LIKE CONCAT('%', {$addrExpr}, '%')";
                $selectRaw .= ", ({$popularitySubq}) as popularity_score";
            }

            $query = Buildings::inAgentTerritory($agent)->selectRaw($selectRaw);

            if ($currentSort === 'popular') {
                $query->orderByRaw('popularity_score DESC')
                      ->orderByRaw('active_count DESC')
                      ->orderBy('name');
            } else {
                $query->orderByRaw('active_count DESC')
                      ->orderBy('name');
            }

            return $query->paginate(30);
        }, new \Illuminate\Pagination\LengthAwarePaginator([], 0, 30));

        $territories = $agent->territories()->get()->groupBy('city');
        $cityList    = $territories->keys()->implode(', ');
        $totalCount  = $buildings->total();

        // Pre-fetch one photo per building in a single query (avoids N+1 in grid view).
        // CondoImages uses mysql_pixi360 — a different connection from Buildings (mysql_mlsr),
        // so cross-connection eager-loading is not possible; we batch manually instead.
        $buildingImages = [];
        $strataIds = $buildings->pluck('strata_no')->filter()->unique()->values()->toArray();
        if (!empty($strataIds)) {
            try {
                $photos = \App\Models\CondoImages::whereIn('strata_idx', $strataIds)
                    ->where('strata_idx', '!=', '')
                    ->orderBy('strata_idx')
                    ->orderBy('order')
                    ->get(['strata_idx', 'image_name'])
                    ->groupBy('strata_idx')
                    ->map(fn ($group) => 'https://media.pixilinkserver.com/upload/house/images/' . $group->first()->image_name);
                $buildingImages = $photos->toArray();
            } catch (\Throwable $e) {
                $buildingImages = [];
            }
        }

        $metaTitle = 'Condos & Buildings in ' . $cityList . ' | ' . $agent->name;

        if ($totalCount > 0) {
            $metaDescription = 'Browse ' . $totalCount . ' condos and buildings in ' . $cityList
                . ' with ' . $agent->name . '. View photos, floor plans, and active listings.';
        } else {
            $metaDescription = 'Browse condos and buildings in ' . $cityList
                . ' with ' . $agent->name . '. View photos, floor plans, and active listings.';
        }

        return $this->view($agent, 'building-list', compact('buildings', 'buildingImages', 'metaTitle', 'metaDescription', 'currentSort'));
    }

    public function buildingDetail(string $slug, string $buildingSlug)
    {
        $agent    = $this->resolveAgent($slug);
        $building = $this->safeListings(function () use ($agent, $buildingSlug) {
            return Buildings::inAgentTerritory($agent)
                ->where('slug', $buildingSlug)
                ->firstOrFail();
        }, null);

        if (!$building) {
            abort(404);
        }

        $active_listings = $this->safeListings(fn () => $building->active_listings()->get(), collect());
        $sold_listings   = $this->safeListings(fn () => $building->sold_listings('1 Year')->get(), collect());
        $openHouseEvents = [];

        $total_active_listings = $active_listings->count();
        $total_soldlistings    = $sold_listings->count();

        $total_listprice     = $active_listings->sum('listprice_2');
        $total_price_sqft    = 0;
        $total_soldprice     = 0;
        $total_days_active   = 0;

        foreach ($active_listings as $l) {
            $ppSqft = $l->livingarea_2 > 0 ? $l->listprice_2 / $l->livingarea_2 : 0;
            $total_price_sqft += $ppSqft;
            $total_days_active += $l->active_days_on_market() ?? 0;
        }
        foreach ($sold_listings as $l) {
            $total_soldprice += $l->soldprice_2;
        }

        $avg_listing_price         = $total_active_listings > 0 ? $total_listprice / $total_active_listings : 0;
        $avg_price_sqft            = $total_active_listings > 0 ? $total_price_sqft / $total_active_listings : 0;
        $avg_soldprice             = $total_soldlistings > 0 ? $total_soldprice / $total_soldlistings : 0;
        $avg_days_on_market_active = $total_active_listings > 0 ? $total_days_active / $total_active_listings : 0;

        foreach ($active_listings as $listing) {
            if ($listing->open_house) {
                foreach (explode(',', $listing->open_house) as $oheStr) {
                    $openHouseEvents[] = [
                        'open_house'    => trim($oheStr),
                        'streetaddress' => $listing->streetaddress,
                        'listing_url'   => route('listing-detail-page2', ['slug' => $listing->slug]),
                    ];
                }
            }
        }

        return $this->view($agent, 'building-detail', compact(
            'building', 'active_listings', 'sold_listings', 'openHouseEvents',
            'total_active_listings', 'avg_listing_price', 'avg_price_sqft',
            'avg_soldprice', 'avg_days_on_market_active'
        ));
    }

    public function listingDetail(string $slug, string $listingSlug)
    {
        $agent   = $this->resolveAgent($slug);
        $listing = $this->safeListings(
            fn () => Listings::where('slug', $listingSlug)->firstOrFail(),
            null
        );

        if (!$listing) {
            abort(404);
        }

        $openHouseEvents = [];
        if ($listing->open_house) {
            foreach (explode(',', $listing->open_house) as $oheStr) {
                $openHouseEvents[] = [
                    'open_house'    => trim($oheStr),
                    'streetaddress' => $listing->streetaddress,
                    'listing_url'   => route('listing-detail-page2', ['slug' => $listing->slug]),
                ];
            }
        }

        return $this->view($agent, 'listing-detail', compact('listing', 'openHouseEvents'));
    }

    public function listingDetailSold(string $slug, string $listingSlug)
    {
        $agent   = $this->resolveAgent($slug);
        $listing = $this->safeListings(
            fn () => Listings::withoutGlobalScopes()->where('slug', $listingSlug)->where('status', 'Sold')->firstOrFail(),
            null
        );

        if (!$listing) {
            abort(404);
        }

        return $this->view($agent, 'listing-sold', compact('listing'));
    }

    public function neighbourhoodHub(string $slug)
    {
        $agent       = $this->resolveAgent($slug);
        $territories = $agent->territories()->get()->groupBy('city');
        $statsBar    = $this->buildStatsBar($agent);

        return $this->view($agent, 'neighbourhood', compact('territories', 'statsBar'));
    }

    public function neighbourhood(string $slug, string $citySlug, ?string $subareaSlug = null)
    {
        $agent       = $this->resolveAgent($slug);
        $territories = $agent->territories()->get()->groupBy('city');

        $city = $territories->keys()->first(fn ($c) => str_replace(' ', '-', strtolower($c)) === strtolower($citySlug));
        $city = $city ?? str_replace('-', ' ', ucwords($citySlug, '-'));

        $subarea = $subareaSlug ? str_replace('-', ' ', ucwords($subareaSlug, '-')) : null;

        $listings = $this->safeListings(function () use ($agent, $city, $subarea) {
            $query = Listings::inAgentTerritory($agent)->where('status', 'Active')->where('city', $city);
            if ($subarea) {
                $query->where('subarea', $subarea);
            }
            return $query->orderByDesc('list_date')->paginate(12);
        }, new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12));

        $statsBar = $this->buildStatsBar($agent);

        return $this->view($agent, 'neighbourhood', compact('city', 'subarea', 'listings', 'statsBar', 'territories'));
    }

    public function marketStats(string $slug)
    {
        $agent          = $this->resolveAgent($slug);
        $statsBar       = $this->buildStatsBar($agent);
        $cityStats      = $this->buildCityStats($agent);
        $monthlyHistory = $this->buildMonthlyHistory($agent);

        return $this->view($agent, 'market-stats', compact('statsBar', 'cityStats', 'monthlyHistory'));
    }

    public function monthlyReport(string $slug, string $year, string $month)
    {
        $agent = $this->resolveAgent($slug);

        $year  = (int) $year;
        $month = (int) $month;

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            abort(404);
        }

        $reportDate = Carbon::create($year, $month, 1);

        if ($reportDate->isFuture()) {
            abort(404);
        }
        if ($reportDate->lt(Carbon::now()->subMonths(36)->startOfMonth())) {
            abort(404);
        }

        $typeStats   = $this->buildMonthlyReportByType($agent, $year, $month);
        $monthLabel  = $reportDate->format('F Y');
        $territory   = $agent->territories()->get()->groupBy('city')->keys()->implode(', ');
        $pageTitle   = $territory . ' — ' . $monthLabel . ' Market Report';
        $metaDesc    = "Real estate market report for {$territory} — {$monthLabel}. Condos, townhouses, and houses: units sold, average prices, and days on market.";

        return $this->view($agent, 'market-stats-month', compact(
            'typeStats', 'monthLabel', 'territory', 'pageTitle', 'metaDesc', 'year', 'month'
        ));
    }

    public function marketReportHub(string $slug)
    {
        $agent = $this->resolveAgent($slug);
        return $this->view($agent, 'market-report-hub', ['reports' => []]);
    }

    public function marketReport(string $slug, ?string $period = null)
    {
        $agent = $this->resolveAgent($slug);
        // $period is the route segment (e.g. "june-2026"); derive a display title from it if present.
        $reportTitle = $period
            ? ucwords(str_replace('-', ' ', $period)) . ' Market Report'
            : date('F Y') . ' Market Report';
        return $this->view($agent, 'market-report', compact('reportTitle'));
    }

    public function propertyTypeHub(string $slug, string $type)
    {
        $agent    = $this->resolveAgent($slug);
        $statsBar = $this->buildStatsBar($agent);

        $typeMap = [
            'condos'      => 'Apartment', 'condo'       => 'Apartment',
            'townhouses'  => 'Townhouse', 'townhouse'   => 'Townhouse',
            'houses'      => 'House',     'house'       => 'House',     'detached' => 'House',
        ];
        $dbType = $typeMap[strtolower($type)] ?? 'Apartment';

        $listings = $this->safeListings(function () use ($agent, $dbType) {
            $query = Listings::inAgentTerritory($agent)->where('status', 'Active');
            if ($dbType === 'House') {
                $query->whereIn('type', ['House', 'Detached']);
            } elseif ($dbType === 'Townhouse') {
                $query->whereIn('type', ['Townhouse', '1/2 Duplex', 'Duplex']);
            } else {
                $query->where('type', $dbType);
            }
            return $query->orderByDesc('list_date')->paginate(18);
        }, new \Illuminate\Pagination\LengthAwarePaginator([], 0, 18));

        // For the houses hub, compute per-city card stats so the view can render richer content
        $houseCityCards = null;
        if (strtolower($type) === 'houses') {
            $houseCityCards = $this->buildHouseCityCards($agent);
        }

        // For the townhouses hub, compute per-city card stats
        $townhouseCityCards = null;
        if (strtolower($type) === 'townhouses') {
            $townhouseCityCards = $this->buildTownhouseCityCards($agent);
        }

        // Canonical URL for the hub page
        $agentDomain   = $agent->settings?->custom_domain;
        $baseUrl        = $agentDomain ? 'https://' . rtrim($agentDomain, '/') : url('/agent/' . $agent->slug);
        $hubCanonical   = $baseUrl . '/' . strtolower($type);

        return $this->view($agent, 'property-type-hub', compact('listings', 'statsBar', 'type', 'houseCityCards', 'townhouseCityCards', 'hubCanonical') + ['propertyType' => $dbType]);
    }

    public function houseCity(string $slug, string $citySlug)
    {
        $agent       = $this->resolveAgent($slug);
        $territories = $agent->territories()->get()->groupBy('city');

        // Match city slug to a territory city
        $city = $territories->keys()->first(fn($c) => \App\Helpers\Helper::enslugPlace($c) === $citySlug);
        if (!$city) {
            abort(404);
        }

        $cond = $this->buildHouseConditionForLocation($agent, $city);

        // Subarea breakdown — only subareas in agent territory for this city
        $cityTerritories = $territories->get($city);
        $subareaStats    = [];
        if ($cityTerritories) {
            foreach ($cityTerritories->where('subarea', '!=', '') as $t) {
                if ($t->subarea) {
                    $subareaStats[$t->subarea] = $this->buildHouseConditionForLocation($agent, $city, $t->subarea);
                }
            }
        }

        $recentListings = $this->safeListings(
            fn() => Listings::withoutGlobalScopes()->inAgentTerritory($agent)
                ->where('status', 'Active')
                ->where('city', $city)
                ->whereIn('type', ['House', 'Detached', 'Duplex', 'Triplex', 'Fourplex'])
                ->orderByDesc('list_date')
                ->limit(6)
                ->get(),
            collect()
        );

        $editorial = $this->buildHouseEditorial($city, $cond);

        $agentDomain = $agent->settings?->custom_domain;
        $baseUrl     = $agentDomain ? 'https://' . rtrim($agentDomain, '/') : url('/agent/' . $agent->slug);
        $canonical   = $baseUrl . '/houses/' . $citySlug;

        $metaTitle       = 'Houses for Sale in ' . $city . ' | ' . $agent->name;
        $metaDescription = $this->buildHouseCityMetaDesc($city, $cond, $agent->name);

        return $this->view($agent, 'houses-city', compact(
            'city', 'citySlug', 'cond', 'subareaStats', 'recentListings',
            'editorial', 'canonical', 'metaTitle', 'metaDescription', 'territories'
        ));
    }

    public function houseSubarea(string $slug, string $citySlug, string $subareaSlug)
    {
        $agent       = $this->resolveAgent($slug);
        $territories = $agent->territories()->get()->groupBy('city');

        $city = $territories->keys()->first(fn($c) => \App\Helpers\Helper::enslugPlace($c) === $citySlug);
        if (!$city) {
            abort(404);
        }

        $cityTerritories = $territories->get($city);
        $subarea = null;
        if ($cityTerritories) {
            $subarea = $cityTerritories->where('subarea', '!=', '')->first(
                fn($t) => \App\Helpers\Helper::enslugPlace($t->subarea) === $subareaSlug
            )?->subarea;
        }
        if (!$subarea) {
            abort(404);
        }

        $cond = $this->buildHouseConditionForLocation($agent, $city, $subarea);

        $recentListings = $this->safeListings(
            fn() => Listings::withoutGlobalScopes()->inAgentTerritory($agent)
                ->where('status', 'Active')
                ->where('city', $city)
                ->where('subarea', $subarea)
                ->whereIn('type', ['House', 'Detached', 'Duplex', 'Triplex', 'Fourplex'])
                ->orderByDesc('list_date')
                ->limit(6)
                ->get(),
            collect()
        );

        // Nearby subareas from agent territory (exclude current)
        $nearbySubareas = $cityTerritories
            ? $cityTerritories->where('subarea', '!=', '')->where('subarea', '!=', $subarea)->values()
            : collect();

        $editorial = $this->buildHouseEditorial($city, $cond, $subarea);

        $agentDomain = $agent->settings?->custom_domain;
        $baseUrl     = $agentDomain ? 'https://' . rtrim($agentDomain, '/') : url('/agent/' . $agent->slug);
        $canonical   = $baseUrl . '/houses/' . $citySlug . '/' . $subareaSlug;

        $metaTitle       = 'Houses for Sale in ' . $subarea . ', ' . $city . ' | ' . $agent->name;
        $metaDescription = $this->buildHouseSubareaMetaDesc($subarea, $city, $cond, $agent->name);

        return $this->view($agent, 'houses-subarea', compact(
            'city', 'citySlug', 'subarea', 'subareaSlug', 'cond', 'recentListings',
            'nearbySubareas', 'editorial', 'canonical', 'metaTitle', 'metaDescription', 'territories'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Houses market helpers
    // ──────────────────────────────────────────────────────────────────────────

    protected function buildHouseCityCards(Agent $agent): array
    {
        return $this->safeListings(function () use ($agent) {
            $territories = $agent->territories()->get()->groupBy('city');
            $cards = [];
            foreach ($territories->keys() as $city) {
                $cacheKey = 'agent_house_hub_card_' . $agent->id . '_' . md5($city) . '_' . date('YmdH');
                $cards[$city] = Cache::remember($cacheKey, 1800, function () use ($agent, $city) {
                    try {
                        $houseTypes = ['House', 'Detached'];
                        $active = Listings::withoutGlobalScopes()->inAgentTerritory($agent)
                            ->where('status', 'Active')->where('city', $city)
                            ->whereIn('type', $houseTypes)->count();
                        $avgRow = Listings::withoutGlobalScopes()->inAgentTerritory($agent)
                            ->where('status', 'Active')->where('city', $city)
                            ->whereIn('type', $houseTypes)->toBase()
                            ->selectRaw('AVG(listprice_2) as avg_list')->first();
                        $cond = $this->buildHouseConditionForLocation($agent, $city);
                        return [
                            'active'    => $active,
                            'avg_list'  => (int) round($avgRow->avg_list ?? 0),
                            'cond'      => $cond,
                        ];
                    } catch (\Throwable $e) {
                        return ['active' => 0, 'avg_list' => 0, 'cond' => ['label' => null, 'color' => '#888', 'insufficient_data' => true]];
                    }
                });
            }
            return $cards;
        }, []);
    }

    protected function buildHouseConditionForLocation(Agent $agent, string $city, ?string $subarea = null): array
    {
        $empty = [
            'label' => null, 'color' => '#888', 'class' => 'verdict-unknown',
            'absorption_rate' => 0, 'avg_dom' => 0, 'price_trend' => 0,
            'sold_30d' => 0, 'sold_90d' => 0, 'current_active' => 0,
            'avg_sold_30d' => 0, 'avg_sold_90d' => 0, 'insufficient_data' => true,
        ];

        return $this->safeListings(function () use ($agent, $city, $subarea, $empty) {
            $cacheKey = 'agent_house_cond_' . $agent->id . '_' . md5($city . '|' . ($subarea ?? '')) . '_' . date('YmdH');
            return Cache::remember($cacheKey, 1800, function () use ($agent, $city, $subarea, $empty) {
                try {
                    $houseTypes = ['House', 'Detached'];

                    $base = Listings::withoutGlobalScopes()->inAgentTerritory($agent)
                        ->where('city', $city)
                        ->whereIn('type', $houseTypes);
                    if ($subarea) {
                        $base->where('subarea', $subarea);
                    }

                    $active = (clone $base)->where('status', 'Active')->count();

                    $sold30Base = (clone $base)->where('status', 'Sold')
                        ->where('sold_date', '>=', Carbon::now()->subDays(30)->toDateString());
                    $sold90Base = (clone $base)->where('status', 'Sold')
                        ->where('sold_date', '>=', Carbon::now()->subDays(90)->toDateString());

                    $sold30 = $sold30Base->count();
                    $sold90 = $sold90Base->count();

                    if ($sold90 < 3) {
                        return array_merge($empty, [
                            'current_active' => $active,
                            'sold_30d' => $sold30,
                            'sold_90d' => $sold90,
                        ]);
                    }

                    $row30 = (clone $sold30Base)->toBase()
                        ->selectRaw('AVG(soldprice_2) as avg_sold, AVG(DATEDIFF(sold_date,list_date)) as avg_dom')
                        ->first();
                    $row90 = (clone $sold90Base)->toBase()
                        ->selectRaw('AVG(soldprice_2) as avg_sold')
                        ->first();

                    $avg30 = (int) round($row30->avg_sold ?? 0);
                    $avg90 = (int) round($row90->avg_sold ?? 0);
                    $dom   = (int) round($row30->avg_dom ?? 0);

                    $absorptionRate = $active > 0 ? round(($sold30 / $active) * 100, 1) : 0;
                    $priceTrend     = ($avg90 > 0 && $avg30 > 0) ? round((($avg30 - $avg90) / $avg90) * 100, 1) : 0;

                    $condition = \App\Helpers\MarketConditionHelper::classify($absorptionRate, $dom);

                    return [
                        'label'            => $condition['label'],
                        'color'            => $condition['color'],
                        'class'            => $condition['class'],
                        'absorption_rate'  => $absorptionRate,
                        'avg_dom'          => $dom,
                        'price_trend'      => $priceTrend,
                        'sold_30d'         => $sold30,
                        'sold_90d'         => $sold90,
                        'current_active'   => $active,
                        'avg_sold_30d'     => $avg30,
                        'avg_sold_90d'     => $avg90,
                        'insufficient_data' => false,
                    ];
                } catch (\Throwable $e) {
                    return $empty;
                }
            });
        }, $empty);
    }

    protected function buildHouseEditorial(string $city, array $cond, ?string $subarea = null): ?string
    {
        if (!$cond['label'] || $cond['insufficient_data']) return null;

        $loc    = $subarea ? "{$subarea}, {$city}" : $city;
        $price  = $cond['avg_sold_30d'] ? '$' . number_format($cond['avg_sold_30d']) : null;
        $dom    = $cond['avg_dom'];
        $sold   = $cond['sold_30d'];
        $active = $cond['current_active'];
        $absorb = $cond['absorption_rate'];
        $trend  = $cond['price_trend'];

        $verdict = match(true) {
            str_contains($cond['label'], 'Strong Seller') =>
                "Detached homes in <strong>{$loc}</strong> are in very high demand — moving fast, with multiple offers common.",
            str_contains($cond['label'], 'Seller') =>
                "The <strong>{$loc}</strong> house market continues to favour sellers, with more buyers than available single-family homes.",
            str_contains($cond['label'], 'Balanced') =>
                "The <strong>{$loc}</strong> house market is balanced, giving both buyers and sellers reasonable negotiating power.",
            default =>
                "Buyers currently have more choice in <strong>{$loc}</strong>'s detached home market, with homes taking longer to sell.",
        };

        $parts = [$verdict];

        if ($sold && $active) {
            $line = "In the past 30 days, <strong>{$sold} houses</strong> sold across {$loc}";
            if ($price) $line .= " at an average sold price of <strong>{$price}</strong>";
            $parts[] = $line . '.';
        }

        if ($dom) {
            $parts[] = "Properties are selling in an average of <strong>{$dom} days</strong> on the market.";
        }

        if ($absorb) {
            $parts[] = "The absorption rate is <strong>{$absorb}%</strong> — meaning {$absorb}% of active house inventory sells each month.";
        }

        if ($trend != 0) {
            $dir = $trend > 0 ? 'up' : 'down';
            $pct = abs($trend);
            $parts[] = "Average house prices in {$loc} are <strong>{$dir} {$pct}%</strong> compared to the 90-day average"
                . ($cond['avg_sold_90d'] ? ' of $' . number_format($cond['avg_sold_90d']) : '') . '.';
        }

        return implode(' ', $parts);
    }

    protected function buildHouseCityMetaDesc(string $city, array $cond, string $agentName): string
    {
        $desc = $cond['current_active']
            ? 'Browse ' . number_format($cond['current_active']) . ' houses for sale in ' . $city . ' with ' . $agentName . '.'
            : 'Browse houses for sale in ' . $city . ' with ' . $agentName . '.';
        if ($cond['avg_sold_30d']) $desc .= ' Avg sold price $' . number_format($cond['avg_sold_30d']) . '.';
        if ($cond['avg_dom']) $desc .= ' Avg ' . $cond['avg_dom'] . ' days on market.';
        if ($cond['label']) $desc .= ' Currently a ' . $cond['label'] . '.';
        return $desc;
    }

    protected function buildHouseSubareaMetaDesc(string $subarea, string $city, array $cond, string $agentName): string
    {
        $desc = $cond['current_active']
            ? 'Browse ' . number_format($cond['current_active']) . ' houses for sale in ' . $subarea . ', ' . $city . ' with ' . $agentName . '.'
            : 'Houses for sale in ' . $subarea . ', ' . $city . ' with ' . $agentName . '.';
        if ($cond['avg_sold_30d'] && !$cond['insufficient_data']) $desc .= ' Avg sold price $' . number_format($cond['avg_sold_30d']) . '.';
        if ($cond['label']) $desc .= ' ' . $cond['label'] . '.';
        return $desc;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Townhouses market pages
    // ──────────────────────────────────────────────────────────────────────────

    public function townhouseCity(string $slug, string $citySlug)
    {
        $agent       = $this->resolveAgent($slug);
        $territories = $agent->territories()->get()->groupBy('city');

        $city = $territories->keys()->first(fn($c) => \App\Helpers\Helper::enslugPlace($c) === $citySlug);
        if (!$city) {
            abort(404);
        }

        $cond = $this->buildTownhouseConditionForLocation($agent, $city);

        $cityTerritories = $territories->get($city);
        $subareaStats    = [];
        if ($cityTerritories) {
            foreach ($cityTerritories->where('subarea', '!=', '') as $t) {
                if ($t->subarea) {
                    $subareaStats[$t->subarea] = $this->buildTownhouseConditionForLocation($agent, $city, $t->subarea);
                }
            }
        }

        $recentListings = $this->safeListings(
            fn() => Listings::withoutGlobalScopes()->inAgentTerritory($agent)
                ->where('status', 'Active')
                ->where('city', $city)
                ->whereIn('type', ['Townhouse', '1/2 Duplex', 'Duplex'])
                ->orderByDesc('list_date')
                ->limit(6)
                ->get(),
            collect()
        );

        $editorial = $this->buildTownhouseEditorial($city, $cond);

        $agentDomain = $agent->settings?->custom_domain;
        $baseUrl     = $agentDomain ? 'https://' . rtrim($agentDomain, '/') : url('/agent/' . $agent->slug);
        $canonical   = $baseUrl . '/townhouses/' . $citySlug;

        $metaTitle       = 'Townhouses for Sale in ' . $city . ' | ' . $agent->name;
        $metaDescription = $this->buildTownhouseCityMetaDesc($city, $cond, $agent->name);

        return $this->view($agent, 'townhouses-city', compact(
            'city', 'citySlug', 'cond', 'subareaStats', 'recentListings',
            'editorial', 'canonical', 'metaTitle', 'metaDescription', 'territories'
        ));
    }

    public function townhouseSubarea(string $slug, string $citySlug, string $subareaSlug)
    {
        $agent       = $this->resolveAgent($slug);
        $territories = $agent->territories()->get()->groupBy('city');

        $city = $territories->keys()->first(fn($c) => \App\Helpers\Helper::enslugPlace($c) === $citySlug);
        if (!$city) {
            abort(404);
        }

        $cityTerritories = $territories->get($city);
        $subarea = null;
        if ($cityTerritories) {
            $subarea = $cityTerritories->where('subarea', '!=', '')->first(
                fn($t) => \App\Helpers\Helper::enslugPlace($t->subarea) === $subareaSlug
            )?->subarea;
        }
        if (!$subarea) {
            abort(404);
        }

        $cond = $this->buildTownhouseConditionForLocation($agent, $city, $subarea);

        $recentListings = $this->safeListings(
            fn() => Listings::withoutGlobalScopes()->inAgentTerritory($agent)
                ->where('status', 'Active')
                ->where('city', $city)
                ->where('subarea', $subarea)
                ->whereIn('type', ['Townhouse', '1/2 Duplex', 'Duplex'])
                ->orderByDesc('list_date')
                ->limit(6)
                ->get(),
            collect()
        );

        $nearbySubareas = $cityTerritories
            ? $cityTerritories->where('subarea', '!=', '')->where('subarea', '!=', $subarea)->values()
            : collect();

        $editorial = $this->buildTownhouseEditorial($city, $cond, $subarea);

        $agentDomain = $agent->settings?->custom_domain;
        $baseUrl     = $agentDomain ? 'https://' . rtrim($agentDomain, '/') : url('/agent/' . $agent->slug);
        $canonical   = $baseUrl . '/townhouses/' . $citySlug . '/' . $subareaSlug;

        $metaTitle       = 'Townhouses for Sale in ' . $subarea . ', ' . $city . ' | ' . $agent->name;
        $metaDescription = $this->buildTownhouseSubareaMetaDesc($subarea, $city, $cond, $agent->name);

        return $this->view($agent, 'townhouses-subarea', compact(
            'city', 'citySlug', 'subarea', 'subareaSlug', 'cond', 'recentListings',
            'nearbySubareas', 'editorial', 'canonical', 'metaTitle', 'metaDescription', 'territories'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Townhouses market helpers
    // ──────────────────────────────────────────────────────────────────────────

    protected function buildTownhouseCityCards(Agent $agent): array
    {
        return $this->safeListings(function () use ($agent) {
            $territories = $agent->territories()->get()->groupBy('city');
            $cards = [];
            foreach ($territories->keys() as $city) {
                $cacheKey = 'agent_th_hub_card_' . $agent->id . '_' . md5($city) . '_' . date('YmdH');
                $cards[$city] = Cache::remember($cacheKey, 1800, function () use ($agent, $city) {
                    try {
                        $thTypes = ['Townhouse', '1/2 Duplex', 'Duplex'];
                        $active = Listings::withoutGlobalScopes()->inAgentTerritory($agent)
                            ->where('status', 'Active')->where('city', $city)
                            ->whereIn('type', $thTypes)->count();
                        $avgRow = Listings::withoutGlobalScopes()->inAgentTerritory($agent)
                            ->where('status', 'Active')->where('city', $city)
                            ->whereIn('type', $thTypes)->toBase()
                            ->selectRaw('AVG(listprice_2) as avg_list')->first();
                        $cond = $this->buildTownhouseConditionForLocation($agent, $city);
                        return [
                            'active'   => $active,
                            'avg_list' => (int) round($avgRow->avg_list ?? 0),
                            'cond'     => $cond,
                        ];
                    } catch (\Throwable $e) {
                        return ['active' => 0, 'avg_list' => 0, 'cond' => ['label' => null, 'color' => '#888', 'insufficient_data' => true]];
                    }
                });
            }
            return $cards;
        }, []);
    }

    protected function buildTownhouseConditionForLocation(Agent $agent, string $city, ?string $subarea = null): array
    {
        $empty = [
            'label' => null, 'color' => '#888', 'class' => 'verdict-unknown',
            'absorption_rate' => 0, 'avg_dom' => 0, 'price_trend' => 0,
            'sold_30d' => 0, 'sold_90d' => 0, 'current_active' => 0,
            'avg_sold_30d' => 0, 'avg_sold_90d' => 0, 'insufficient_data' => true,
        ];

        return $this->safeListings(function () use ($agent, $city, $subarea, $empty) {
            $cacheKey = 'agent_th_cond_' . $agent->id . '_' . md5($city . '|' . ($subarea ?? '')) . '_' . date('YmdH');
            return Cache::remember($cacheKey, 1800, function () use ($agent, $city, $subarea, $empty) {
                try {
                    $thTypes = ['Townhouse', '1/2 Duplex', 'Duplex'];

                    $base = Listings::withoutGlobalScopes()->inAgentTerritory($agent)
                        ->where('city', $city)
                        ->whereIn('type', $thTypes);
                    if ($subarea) {
                        $base->where('subarea', $subarea);
                    }

                    $active = (clone $base)->where('status', 'Active')->count();

                    $sold30Base = (clone $base)->where('status', 'Sold')
                        ->where('sold_date', '>=', Carbon::now()->subDays(30)->toDateString());
                    $sold90Base = (clone $base)->where('status', 'Sold')
                        ->where('sold_date', '>=', Carbon::now()->subDays(90)->toDateString());

                    $sold30 = $sold30Base->count();
                    $sold90 = $sold90Base->count();

                    if ($sold90 < 3) {
                        return array_merge($empty, [
                            'current_active' => $active,
                            'sold_30d' => $sold30,
                            'sold_90d' => $sold90,
                        ]);
                    }

                    $row30 = (clone $sold30Base)->toBase()
                        ->selectRaw('AVG(soldprice_2) as avg_sold, AVG(DATEDIFF(sold_date,list_date)) as avg_dom')
                        ->first();
                    $row90 = (clone $sold90Base)->toBase()
                        ->selectRaw('AVG(soldprice_2) as avg_sold')
                        ->first();

                    $avg30 = (int) round($row30->avg_sold ?? 0);
                    $avg90 = (int) round($row90->avg_sold ?? 0);
                    $dom   = (int) round($row30->avg_dom ?? 0);

                    $absorptionRate = $active > 0 ? round(($sold30 / $active) * 100, 1) : 0;
                    $priceTrend     = ($avg90 > 0 && $avg30 > 0) ? round((($avg30 - $avg90) / $avg90) * 100, 1) : 0;

                    $condition = \App\Helpers\MarketConditionHelper::classify($absorptionRate, $dom);

                    return [
                        'label'            => $condition['label'],
                        'color'            => $condition['color'],
                        'class'            => $condition['class'],
                        'absorption_rate'  => $absorptionRate,
                        'avg_dom'          => $dom,
                        'price_trend'      => $priceTrend,
                        'sold_30d'         => $sold30,
                        'sold_90d'         => $sold90,
                        'current_active'   => $active,
                        'avg_sold_30d'     => $avg30,
                        'avg_sold_90d'     => $avg90,
                        'insufficient_data' => false,
                    ];
                } catch (\Throwable $e) {
                    return $empty;
                }
            });
        }, $empty);
    }

    protected function buildTownhouseEditorial(string $city, array $cond, ?string $subarea = null): ?string
    {
        if (!$cond['label'] || $cond['insufficient_data']) return null;

        $loc    = $subarea ? "{$subarea}, {$city}" : $city;
        $price  = $cond['avg_sold_30d'] ? '$' . number_format($cond['avg_sold_30d']) : null;
        $dom    = $cond['avg_dom'];
        $sold   = $cond['sold_30d'];
        $active = $cond['current_active'];
        $absorb = $cond['absorption_rate'];
        $trend  = $cond['price_trend'];

        $verdict = match(true) {
            str_contains($cond['label'], 'Strong Seller') =>
                "Townhouses in <strong>{$loc}</strong> are in very high demand — moving fast with limited inventory.",
            str_contains($cond['label'], 'Seller') =>
                "The <strong>{$loc}</strong> townhouse market continues to favour sellers, with more buyers than available homes.",
            str_contains($cond['label'], 'Balanced') =>
                "The <strong>{$loc}</strong> townhouse market is balanced, giving both buyers and sellers reasonable negotiating power.",
            default =>
                "Buyers currently have more choice in <strong>{$loc}</strong>'s townhouse market, with homes taking longer to sell.",
        };

        $parts = [$verdict];

        if ($sold && $active) {
            $line = "In the past 30 days, <strong>{$sold} townhouses</strong> sold across {$loc}";
            if ($price) $line .= " at an average sold price of <strong>{$price}</strong>";
            $parts[] = $line . '.';
        }

        if ($dom) {
            $parts[] = "Properties are selling in an average of <strong>{$dom} days</strong> on the market.";
        }

        if ($absorb) {
            $parts[] = "The absorption rate is <strong>{$absorb}%</strong> — meaning {$absorb}% of active townhouse inventory sells each month.";
        }

        if ($trend != 0) {
            $dir = $trend > 0 ? 'up' : 'down';
            $pct = abs($trend);
            $parts[] = "Average townhouse prices in {$loc} are <strong>{$dir} {$pct}%</strong> compared to the 90-day average"
                . ($cond['avg_sold_90d'] ? ' of $' . number_format($cond['avg_sold_90d']) : '') . '.';
        }

        return implode(' ', $parts);
    }

    protected function buildTownhouseCityMetaDesc(string $city, array $cond, string $agentName): string
    {
        $desc = $cond['current_active']
            ? 'Browse ' . number_format($cond['current_active']) . ' townhouses for sale in ' . $city . ' with ' . $agentName . '.'
            : 'Browse townhouses for sale in ' . $city . ' with ' . $agentName . '.';
        if ($cond['avg_sold_30d']) $desc .= ' Avg sold price $' . number_format($cond['avg_sold_30d']) . '.';
        if ($cond['avg_dom']) $desc .= ' Avg ' . $cond['avg_dom'] . ' days on market.';
        if ($cond['label']) $desc .= ' Currently a ' . $cond['label'] . '.';
        return $desc;
    }

    protected function buildTownhouseSubareaMetaDesc(string $subarea, string $city, array $cond, string $agentName): string
    {
        $desc = $cond['current_active']
            ? 'Browse ' . number_format($cond['current_active']) . ' townhouses for sale in ' . $subarea . ', ' . $city . ' with ' . $agentName . '.'
            : 'Townhouses for sale in ' . $subarea . ', ' . $city . ' with ' . $agentName . '.';
        if ($cond['avg_sold_30d'] && !$cond['insufficient_data']) $desc .= ' Avg sold price $' . number_format($cond['avg_sold_30d']) . '.';
        if ($cond['label']) $desc .= ' ' . $cond['label'] . '.';
        return $desc;
    }

    public function buyersGuide(string $slug)
    {
        $agent = $this->resolveAgent($slug);
        return $this->view($agent, 'buyers-guide');
    }

    public function sellersGuide(string $slug)
    {
        $agent = $this->resolveAgent($slug);
        return $this->view($agent, 'sellers-guide');
    }

    public function about(string $slug)
    {
        $agent        = $this->resolveAgent($slug);
        $testimonials = $agent->testimonials()->get();
        return $this->view($agent, 'about', compact('testimonials'));
    }

    public function homeEvaluation(string $slug)
    {
        $agent = $this->resolveAgent($slug);
        return $this->view($agent, 'home-evaluation');
    }

    public function contact(string $slug)
    {
        $agent        = $this->resolveAgent($slug);
        $testimonials = $agent->testimonials()->limit(3)->get();
        return $this->view($agent, 'about', compact('testimonials'));
    }

    public function schoolCatchment(string $slug, ?string $citySlug = null)
    {
        $agent = $this->resolveAgent($slug);
        $city  = $citySlug ? str_replace('-', ' ', ucwords($citySlug, '-')) : null;
        return $this->view($agent, 'school-catchment', compact('city'));
    }

    public function lifestyleHub(string $slug, string $lifestyle)
    {
        $agent = $this->resolveAgent($slug);

        $lifestyleLabel = match (strtolower($lifestyle)) {
            'pet-friendly'     => 'Pet-Friendly',
            'ev-charging'      => 'EV Charging',
            'rental-allowed'   => 'Rental-Allowed',
            'new-construction' => 'New Construction',
            default            => ucfirst(str_replace('-', ' ', $lifestyle)),
        };

        $listings = $this->safeListings(function () use ($agent, $lifestyle) {
            $query = Listings::inAgentTerritory($agent)->where('status', 'Active');
            if ($lifestyle === 'pet-friendly') {
                $query->where(fn ($q) => $q->where('no_pets', 0)->orWhere('cats', 1)->orWhere('dogs', 1));
            }
            return $query->orderByDesc('list_date')->paginate(18);
        }, new \Illuminate\Pagination\LengthAwarePaginator([], 0, 18));

        return $this->view($agent, 'lifestyle-hub', compact('listings', 'lifestyle', 'lifestyleLabel'));
    }

    public function openHouses(string $slug)
    {
        $agent = $this->resolveAgent($slug);

        $openHouses = $this->safeListings(function () use ($agent) {
            $listings = Listings::inAgentTerritory($agent)
                ->where('status', 'Active')
                ->whereNotNull('open_house')
                ->where('open_house', '!=', '')
                ->get();

            $result = [];
            foreach ($listings as $listing) {
                foreach (explode(',', $listing->open_house ?? '') as $oheStr) {
                    $result[] = [
                        'open_house'    => trim($oheStr),
                        'streetaddress' => $listing->streetaddress,
                        'listing_url'   => route('listing-detail-page2', ['slug' => $listing->slug]),
                    ];
                }
            }
            return $result;
        }, []);

        return $this->view($agent, 'open-houses', compact('openHouses'));
    }
}
