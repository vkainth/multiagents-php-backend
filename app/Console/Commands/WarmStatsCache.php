<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Repository\StatsRepository;
use App\Models\Places;
use App\Models\Listings;
use App\Models\Buildings;
use App\Helpers\Helper;

class WarmStatsCache extends Command
{
    protected $signature   = 'stats:warm {--force : Force refresh by bypassing existing cached entries}';
    protected $description = 'Pre-warm neighbourhood and house market stats cache for all cities and subareas';

    public function __construct(private StatsRepository $statsRepo)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $ttl   = 86400;

        $this->info('[stats:warm] Starting cache warm-up...');

        $this->warmNeighbourhoodHub($force, $ttl);
        $this->warmHouseHub($force, $ttl);

        $cities = Places::where('type', 'city')
            ->where('stats_disabled', 0)
            ->orderBy('order')
            ->get();

        foreach ($cities as $cityRecord) {
            $city     = $cityRecord->place;
            $citySlug = Helper::enslugPlace($city);
            $this->line("  city: {$city}");

            $this->warmNeighbourhoodCity($city, $citySlug, $cityRecord, $force, $ttl);
            $this->warmHouseCity($city, $citySlug, $cityRecord, $force, $ttl);

            $subareas = Places::where('type', 'subarea')
                ->where('city', $city)
                ->where('stats_disabled', 0)
                ->orderBy('order')
                ->get();

            foreach ($subareas as $subareaRecord) {
                $subarea     = $subareaRecord->place;
                $subareaSlug = Helper::enslugPlace($subarea);
                $this->line("    subarea: {$subarea}");

                $this->warmNeighbourhoodGuide($city, $citySlug, $subarea, $subareaSlug, $cityRecord, $subareaRecord, $force, $ttl);
                $this->warmHouseSubarea($city, $citySlug, $subarea, $subareaSlug, $cityRecord, $subareaRecord, $force, $ttl);
            }
        }

        $this->info('[stats:warm] Cache warm-up complete.');
        return 0;
    }

    private function cacheKey(string ...$parts): string
    {
        return preg_replace('/\s+/', '_', implode('_', $parts));
    }

    private function warmNeighbourhoodHub(bool $force, int $ttl): void
    {
        $key = 'neighbourhood_hub_v2';
        if (!$force && Cache::has($key)) {
            $this->line('  neighbourhood hub: already cached, skipping');
            return;
        }

        $cities   = Places::where('type', 'city')->where('stats_disabled', 0)->where('stats_subareas_disabled', 0)->orderBy('order')->get();
        $allStats = $this->statsRepo->get_all_cities_market_summary();
        $statsMap = collect($allStats)->keyBy('city');

        $cityStats = [];
        foreach ($cities as $c) {
            $cityStats[$c->place] = $this->computeMarketCondition($statsMap->get($c->place));
        }

        Cache::put($key, compact('cities', 'cityStats'), $ttl);
        $this->line('  neighbourhood hub: warmed');
    }

    private function warmHouseHub(bool $force, int $ttl): void
    {
        $key = 'house_hub_v2';
        if (!$force && Cache::has($key)) {
            $this->line('  house hub: already cached, skipping');
            return;
        }

        $cities   = Places::where('type', 'city')->where('stats_disabled', 0)->orderBy('order')->get();
        $allStats = $this->statsRepo->get_all_cities_house_summary();
        $statsMap = collect($allStats)->keyBy('city');

        $cityStats = [];
        foreach ($cities as $c) {
            $cityStats[$c->place] = $this->computeHouseCondition($statsMap->get($c->place) ?: null);
        }

        $recentListings = Listings::with('aphoto')
            ->where('status', 'Active')
            ->whereIn('type', ['House', 'Duplex', 'Fourplex', 'Triplex'])
            ->orderBy('list_date', 'desc')
            ->limit(6)
            ->get();

        Cache::put($key, compact('cities', 'cityStats', 'recentListings'), $ttl);
        $this->line('  house hub: warmed');
    }

    private function warmNeighbourhoodCity(string $city, string $citySlug, $cityRecord, bool $force, int $ttl): void
    {
        $key = $this->cacheKey('neighbourhood_city_v2', $city);
        if (!$force && Cache::has($key)) {
            $this->line("    neighbourhood city {$city}: already cached, skipping");
            return;
        }

        $subareas = Places::where('type', 'subarea')->where('city', $city)->where('stats_disabled', 0)->orderBy('order')->get();

        $overallSummary   = $this->statsRepo->get_market_summary($city);
        $overallCondition = $this->computeMarketCondition($overallSummary);

        $batchRows = $this->statsRepo->get_market_summary_batch($city);
        $batchMap  = collect($batchRows)->keyBy('subarea');

        $subareaStats = [];
        foreach ($subareas as $sa) {
            $subareaStats[$sa->place] = $this->computeMarketCondition($batchMap->get($sa->place));
        }

        $subareas = $subareas->sortByDesc(fn($sa) => $subareaStats[$sa->place]['absorption_rate'] ?? 0)->values();

        Cache::put($key, compact('city', 'citySlug', 'cityRecord', 'subareas', 'overallCondition', 'subareaStats'), $ttl);
    }

    private function warmHouseCity(string $city, string $citySlug, $cityRecord, bool $force, int $ttl): void
    {
        $key = $this->cacheKey('house_city_v2', $city);
        if (!$force && Cache::has($key)) {
            $this->line("    house city {$city}: already cached, skipping");
            return;
        }

        $overallSummary = $this->statsRepo->get_house_market_summary($city);
        $overallCond    = $this->computeHouseCondition($overallSummary);

        $subareaRows = $this->statsRepo->get_house_subarea_breakdown($city);
        $subareas    = Places::where('type', 'subarea')->where('city', $city)->where('stats_disabled', 0)->orderBy('order')->get()->keyBy('place');

        $subareaStats = [];
        foreach ($subareaRows as $row) {
            $subareaStats[$row->subarea] = $this->computeHouseCondition($row);
        }

        $houseRow   = $this->statsRepo->get_house_avg_price_monthly($city);
        $priceRange = $this->statsRepo->get_house_sold_price_range('90 DAY', $city);

        $recentListings = Listings::with('aphoto')
            ->where('status', 'Active')
            ->where('city', $city)
            ->whereIn('type', ['House', 'Duplex', 'Fourplex', 'Triplex'])
            ->orderBy('list_date', 'desc')
            ->limit(6)
            ->get();

        $editorial = null;

        Cache::put($key, compact(
            'city', 'citySlug', 'cityRecord', 'overallCond',
            'subareas', 'subareaStats', 'houseRow', 'priceRange',
            'recentListings', 'editorial'
        ), $ttl);
    }

    private function warmNeighbourhoodGuide(string $city, string $citySlug, string $subarea, string $subareaSlug, $cityRecord, $subareaRecord, bool $force, int $ttl): void
    {
        $key = $this->cacheKey('neighbourhood_guide', $city, $subarea);
        if (!$force && Cache::has($key)) return;

        $condoSummary     = $this->statsRepo->get_market_summary($city, $subarea, 'Apartment');
        $houseSummary     = $this->statsRepo->get_market_summary($city, $subarea, 'House');
        $townhouseSummary = $this->statsRepo->get_market_summary($city, $subarea, 'Townhouse');
        $allSummary       = $this->statsRepo->get_market_summary($city, $subarea);

        $condoCondition     = $this->computeMarketCondition($condoSummary);
        $houseCondition     = $this->computeMarketCondition($houseSummary);
        $townhouseCondition = $this->computeMarketCondition($townhouseSummary);
        $allCondition       = $this->computeMarketCondition($allSummary);

        $topBuildings = Buildings::where('city', $city)->where('subarea', $subarea)->orderBy('units_in_strata', 'desc')->limit(6)->get();

        $condoListings = Listings::with('aphoto')
            ->where('status', 'Active')->where('city', $city)->where('subarea', $subarea)->where('type', 'Apartment')
            ->orderBy('list_date', 'desc')->limit(4)->get();

        $houseListings = Listings::with('aphoto')
            ->where('status', 'Active')->where('city', $city)->where('subarea', $subarea)->whereIn('type', ['House', 'Townhouse'])
            ->orderBy('list_date', 'desc')->limit(4)->get();

        $priceSeries = $this->statsRepo->get_avg_price_monthly($city, $subarea);

        $nearbySubareas = Places::where('type', 'subarea')->where('city', $city)->where('place', '!=', $subarea)
            ->where('stats_disabled', 0)->orderBy('order')->limit(3)->get();

        $nearbyBatch  = $this->statsRepo->get_market_summary_batch($city);
        $nearbyBatchMap = collect($nearbyBatch)->keyBy('subarea');
        $nearbyStats  = [];
        foreach ($nearbySubareas as $ns) {
            $nearbyStats[$ns->place] = $this->computeMarketCondition($nearbyBatchMap->get($ns->place));
        }

        $buildingYears = $topBuildings->pluck('yearbuilt')->filter()->values();
        $avgYear       = $buildingYears->count() ? (int) round($buildingYears->avg()) : null;
        $buildingCount = Buildings::where('city', $city)->where('subarea', $subarea)->count();
        $avgLat        = $topBuildings->pluck('latitude')->filter()->avg();
        $avgLng        = $topBuildings->pluck('longitude')->filter()->avg();

        Cache::put($key, compact(
            'city', 'citySlug', 'subarea', 'subareaSlug',
            'condoCondition', 'houseCondition', 'townhouseCondition', 'allCondition',
            'topBuildings', 'condoListings', 'houseListings', 'priceSeries',
            'nearbySubareas', 'nearbyStats', 'avgYear', 'buildingCount', 'avgLat', 'avgLng'
        ), $ttl);
    }

    private function warmHouseSubarea(string $city, string $citySlug, string $subarea, string $subareaSlug, $cityRecord, $subareaRecord, bool $force, int $ttl): void
    {
        $key = $this->cacheKey('house_subarea_v2', $city, $subarea);
        if (!$force && Cache::has($key)) return;

        $summary  = $this->statsRepo->get_house_market_summary($city, $subarea);
        $cond     = $this->computeHouseCondition($summary);
        $houseRow = $this->statsRepo->get_house_avg_price_monthly($city, $subarea);

        $recentListings = Listings::with('aphoto')
            ->where('status', 'Active')->where('city', $city)->where('subarea', $subarea)
            ->whereIn('type', ['House', 'Duplex', 'Fourplex', 'Triplex'])
            ->orderBy('list_date', 'desc')->limit(6)->get();

        $nearbySubareas = Places::where('type', 'subarea')->where('city', $city)->where('place', '!=', $subarea)
            ->where('stats_disabled', 0)->orderBy('order')->limit(6)->get();

        $editorial = null;

        Cache::put($key, compact(
            'city', 'citySlug', 'subarea', 'subareaSlug',
            'cityRecord', 'subareaRecord', 'cond',
            'houseRow', 'recentListings', 'nearbySubareas', 'editorial'
        ), $ttl);
    }

    private function computeMarketCondition($summary): array
    {
        $empty = [
            'label' => null, 'color' => '#888', 'class' => 'verdict-unknown',
            'absorption_rate' => 0, 'avg_dom' => 0, 'price_trend' => 0,
            'sold_30d' => 0, 'current_active' => 0, 'avg_sold_30d' => 0, 'avg_sold_90d' => 0,
        ];
        if (!$summary || !($summary->current_active ?? 0)) return $empty;

        $absorptionRate = ($summary->sold_30d / $summary->current_active) * 100;
        $avgDom         = (int) ($summary->avg_dom_30d ?? 0);
        $priceTrend     = ($summary->avg_sold_90d && $summary->avg_sold_30d && $summary->avg_sold_90d > 0)
            ? (($summary->avg_sold_30d - $summary->avg_sold_90d) / $summary->avg_sold_90d) * 100
            : 0;

        if ($absorptionRate > 25 && $avgDom > 0 && $avgDom < 25) {
            $label = "Strong Seller's Market"; $color = '#c0392b'; $class = 'verdict-red';
        } elseif ($absorptionRate >= 20) {
            $label = "Seller's Market";        $color = '#e67e22'; $class = 'verdict-orange';
        } elseif ($absorptionRate >= 12) {
            $label = "Balanced Market";        $color = '#f39c12'; $class = 'verdict-yellow';
        } else {
            $label = "Buyer's Market";         $color = '#2980b9'; $class = 'verdict-blue';
        }

        return [
            'label'           => $label,
            'color'           => $color,
            'class'           => $class,
            'absorption_rate' => round($absorptionRate, 1),
            'avg_dom'         => $avgDom,
            'price_trend'     => round($priceTrend, 1),
            'sold_30d'        => (int) ($summary->sold_30d       ?? 0),
            'current_active'  => (int) ($summary->current_active ?? 0),
            'avg_sold_30d'    => (int) ($summary->avg_sold_30d   ?? 0),
            'avg_sold_90d'    => (int) ($summary->avg_sold_90d   ?? 0),
        ];
    }

    private function computeHouseCondition($summary): array
    {
        $empty = [
            'label' => null, 'color' => '#888', 'class' => 'verdict-unknown',
            'absorption_rate' => 0, 'avg_dom' => 0, 'price_trend' => 0,
            'sold_30d' => 0, 'sold_90d' => 0, 'current_active' => 0,
            'avg_sold_30d' => 0, 'avg_sold_90d' => 0, 'insufficient_data' => false,
        ];
        if (!$summary) return array_merge($empty, ['insufficient_data' => true]);

        $sold30  = (int) ($summary->sold_30d      ?? 0);
        $sold90  = (int) ($summary->sold_90d       ?? 0);
        $active  = (int) ($summary->current_active ?? 0);
        $avg30   = (int) ($summary->avg_sold_30d   ?? 0);
        $avg90   = (int) ($summary->avg_sold_90d   ?? 0);
        $dom     = (int) ($summary->avg_dom_30d    ?? 0);

        if ($sold90 < 3) {
            return array_merge($empty, [
                'insufficient_data' => true,
                'sold_30d'          => $sold30,
                'sold_90d'          => $sold90,
                'current_active'    => $active,
                'avg_sold_30d'      => $avg30,
            ]);
        }

        $absorptionRate = $active > 0 ? round(($sold30 / $active) * 100, 1) : 0;
        $priceTrend     = ($avg90 > 0 && $avg30 > 0)
            ? round((($avg30 - $avg90) / $avg90) * 100, 1) : 0;

        if ($absorptionRate > 25 && $dom > 0 && $dom < 25) {
            $label = "Strong Seller's Market"; $color = '#c0392b'; $class = 'verdict-red';
        } elseif ($absorptionRate >= 20) {
            $label = "Seller's Market";        $color = '#e67e22'; $class = 'verdict-orange';
        } elseif ($absorptionRate >= 12) {
            $label = "Balanced Market";        $color = '#f39c12'; $class = 'verdict-yellow';
        } else {
            $label = "Buyer's Market";         $color = '#2980b9'; $class = 'verdict-blue';
        }

        return [
            'label'             => $label,
            'color'             => $color,
            'class'             => $class,
            'absorption_rate'   => $absorptionRate,
            'avg_dom'           => $dom,
            'price_trend'       => $priceTrend,
            'sold_30d'          => $sold30,
            'sold_90d'          => $sold90,
            'current_active'    => $active,
            'avg_sold_30d'      => $avg30,
            'avg_sold_90d'      => $avg90,
            'insufficient_data' => false,
        ];
    }
}
