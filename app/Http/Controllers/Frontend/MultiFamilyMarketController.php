<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Repository\StatsRepository;
use App\Models\Places;
use App\Models\Listings;
use App\Helpers\Helper;
use App\Helpers\FubAreaHelper;

class MultiFamilyMarketController extends Controller
{
    protected $statsRepo;

    const TYPES = ['Duplex', 'Triplex', 'Fourplex'];

    public function __construct(StatsRepository $statsRepo)
    {
        $this->statsRepo = $statsRepo;
    }

    protected function genCacheKey()
    {
        $args = func_get_args();
        return preg_replace('/\s+/', '_', join('_', $args));
    }

    protected function computeMultiFamilyCondition($summary)
    {
        $empty = [
            'label' => null, 'color' => '#888', 'class' => 'verdict-unknown',
            'absorption_rate' => 0, 'avg_dom' => 0, 'price_trend' => 0,
            'sold_30d' => 0, 'sold_90d' => 0, 'current_active' => 0,
            'avg_sold_30d' => 0, 'avg_sold_90d' => 0,
            'avg_list_price' => 0, 'avg_price_sqft' => 0,
            'insufficient_data' => false,
        ];
        if (!$summary) return array_merge($empty, ['insufficient_data' => true]);

        $sold30  = (int)($summary->sold_30d       ?? 0);
        $sold90  = (int)($summary->sold_90d        ?? 0);
        $active  = (int)($summary->current_active  ?? 0);
        $avg30   = (int)($summary->avg_sold_30d    ?? 0);
        $avg90   = (int)($summary->avg_sold_90d    ?? 0);
        $dom     = (int)($summary->avg_dom_30d     ?? 0);
        $avgList = (int)($summary->avg_list_price  ?? 0);
        $sqft    = (int)($summary->avg_price_sqft  ?? 0);

        if ($sold90 < 3) {
            return array_merge($empty, [
                'insufficient_data' => true,
                'sold_30d' => $sold30,
                'sold_90d' => $sold90,
                'current_active' => $active,
                'avg_sold_30d' => $avg30,
                'avg_list_price' => $avgList,
                'avg_price_sqft' => $sqft,
            ]);
        }

        $absorptionRate = $active > 0 ? round(($sold30 / $active) * 100, 1) : 0;
        $priceTrend     = ($avg90 > 0 && $avg30 > 0)
            ? round((($avg30 - $avg90) / $avg90) * 100, 1) : 0;

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
            'avg_list_price'   => $avgList,
            'avg_price_sqft'   => $sqft,
            'insufficient_data' => false,
        ];
    }

    /**
     * Fetch per-type stats and listings for city or subarea.
     * Returns ['Duplex' => cond[], 'Triplex' => cond[], 'Fourplex' => cond[]]
     * and     ['Duplex' => listings, 'Triplex' => listings, 'Fourplex' => listings]
     */
    protected function fetchTypeData($city, $subarea = '')
    {
        $typeStats    = [];
        $typeListings = [];

        foreach (self::TYPES as $type) {
            $summary = $this->statsRepo->get_multi_family_type_summary($type, $city, $subarea);
            $typeStats[$type] = $this->computeMultiFamilyCondition($summary);

            $query = Listings::with('aphoto')
                ->where('status', 'Active')
                ->where('type', $type)
                ->where('city', $city);

            if ($subarea) {
                $query->where('subarea', $subarea);
            }

            $typeListings[$type] = $query->orderBy('list_date', 'desc')->limit(3)->get();
        }

        return [$typeStats, $typeListings];
    }

    protected function buildEditorial($cityName, $cond, $subarea = '')
    {
        if (!$cond['label'] || $cond['insufficient_data']) return null;

        $loc    = $subarea ? "{$subarea}, {$cityName}" : $cityName;
        $price  = $cond['avg_sold_30d'] ? '$' . number_format($cond['avg_sold_30d']) : null;
        $dom    = $cond['avg_dom'];
        $sold   = $cond['sold_30d'];
        $active = $cond['current_active'];
        $absorb = $cond['absorption_rate'];
        $trend  = $cond['price_trend'];

        $verdictSentence = match(true) {
            str_contains($cond['label'], 'Strong Seller') =>
                "Multi-family properties in <strong>{$loc}</strong> are in very high demand — moving fast, with multiple offers common.",
            str_contains($cond['label'], "Seller") =>
                "The <strong>{$loc}</strong> multi-family market continues to favour sellers, with more buyers than available properties.",
            str_contains($cond['label'], "Balanced") =>
                "The <strong>{$loc}</strong> multi-family market is balanced, giving both buyers and sellers reasonable negotiating power.",
            default =>
                "Buyers currently have more choice in <strong>{$loc}</strong>'s multi-family market, with homes taking longer to sell.",
        };

        $parts = ["{$verdictSentence}"];

        if ($sold && $active) {
            $parts[] = "In the past 30 days, <strong>{$sold} multi-family properties</strong> sold across {$loc}";
            if ($price) $parts[count($parts)-1] .= " at an average sold price of <strong>{$price}</strong>";
            $parts[count($parts)-1] .= ".";
        }

        if ($dom) {
            $parts[] = "Multi-family properties are selling in an average of <strong>{$dom} days</strong> on the market.";
        }

        if ($absorb) {
            $parts[] = "The absorption rate is <strong>{$absorb}%</strong> — meaning {$absorb}% of active multi-family inventory sells each month.";
        }

        if ($trend != 0) {
            $dir = $trend > 0 ? 'up' : 'down';
            $pct = abs($trend);
            $parts[] = "Average multi-family prices in {$loc} are <strong>{$dir} {$pct}%</strong> compared to the 90-day average" . ($cond['avg_sold_90d'] ? " of $" . number_format($cond['avg_sold_90d']) : '') . ".";
        }

        if (str_contains($cond['label'], "Seller")) {
            $parts[] = "If you're looking to <strong>buy a duplex, triplex, or fourplex in {$loc}</strong>, budget for competitive situations and be prepared to act quickly on well-priced properties.";
        } elseif (str_contains($cond['label'], "Buyer")) {
            $parts[] = "If you're considering buying a multi-family property in {$loc}, you have more time to negotiate — though well-priced homes still attract attention.";
        }

        return implode(' ', $parts);
    }

    public function hub()
    {
        $cacheKey = 'multi_family_hub_v1';
        $data = Cache::remember($cacheKey, 86400, function () {
            $allCities = Places::where('type', 'city')
                ->where('stats_disabled', 0)
                ->orderBy('order')
                ->get();

            $allStats  = $this->statsRepo->get_all_cities_multi_family_summary();
            $statsMap  = collect($allStats)->keyBy('city');

            $cityStats  = [];
            $activeCities = [];

            foreach ($allCities as $c) {
                $cond = $this->computeMultiFamilyCondition($statsMap->get($c->place) ?: null);
                $cityStats[$c->place] = $cond;
                // Only include cities that have active listings or recent sales
                if ($cond['current_active'] > 0 || $cond['sold_30d'] > 0) {
                    $activeCities[] = $c;
                }
            }

            // Fall back to all cities if none have inventory (avoids empty page)
            $cities = count($activeCities) > 0
                ? collect($activeCities)
                : $allCities;

            $recentListings = Listings::with('aphoto')
                ->where('status', 'Active')
                ->whereIn('type', self::TYPES)
                ->orderBy('list_date', 'desc')
                ->limit(6)
                ->get();

            return compact('cities', 'cityStats', 'recentListings');
        });

        return view('frontend.multi_family_hub', $data);
    }

    public function city($citySlug)
    {
        $city = Helper::deslugPlace($citySlug);
        FubAreaHelper::saveToSession($city);

        $cityRecord = Places::where('type', 'city')
            ->where('place', $city)
            ->where('stats_disabled', 0)
            ->first();

        if (!$cityRecord) abort(404);

        $cacheKey = $this->genCacheKey('multi_family_city_v2', $city);
        $data = Cache::remember($cacheKey, 86400, function () use ($city, $citySlug, $cityRecord) {
            // Aggregate summary
            $overallSummary = $this->statsRepo->get_multi_family_market_summary($city);
            $overallCond    = $this->computeMultiFamilyCondition($overallSummary);

            // Per-type stats + listings
            [$typeStats, $typeListings] = $this->fetchTypeData($city);

            // Subarea breakdown
            $subareaRows = $this->statsRepo->get_multi_family_subarea_breakdown($city);
            $subareas = Places::where('type', 'subarea')
                ->where('city', $city)
                ->where('stats_disabled', 0)
                ->orderBy('order')
                ->get()
                ->keyBy('place');

            $subareaStats = [];
            foreach ($subareaRows as $row) {
                $subareaStats[$row->subarea] = $this->computeMultiFamilyCondition($row);
            }

            $multiFamilyRow = $this->statsRepo->get_multi_family_avg_price_monthly($city);
            $priceRange     = $this->statsRepo->get_multi_family_sold_price_range('90 DAY', $city);
            $editorial      = $this->buildEditorial($cityRecord->label ?? $city, $overallCond);

            return compact(
                'city', 'citySlug', 'cityRecord', 'overallCond',
                'typeStats', 'typeListings',
                'subareas', 'subareaStats', 'multiFamilyRow', 'priceRange',
                'editorial'
            );
        });

        return view('frontend.multi_family_city', $data);
    }

    public function subarea($citySlug, $subareaSlug)
    {
        $city    = Helper::deslugPlace($citySlug);
        $subarea = Helper::deslugPlace($subareaSlug);
        FubAreaHelper::saveToSession($city);

        $cityRecord = Places::where('type', 'city')
            ->where('place', $city)
            ->where('stats_disabled', 0)
            ->first();

        $subareaRecord = Places::where('type', 'subarea')
            ->where('place', $subarea)
            ->where('city', $city)
            ->where('stats_disabled', 0)
            ->first();

        if (!$cityRecord || !$subareaRecord) abort(404);

        $cacheKey = $this->genCacheKey('multi_family_subarea_v2', $city, $subarea);
        $data = Cache::remember($cacheKey, 86400, function () use ($city, $citySlug, $subarea, $subareaSlug, $cityRecord, $subareaRecord) {
            // Aggregate summary
            $summary = $this->statsRepo->get_multi_family_market_summary($city, $subarea);
            $cond    = $this->computeMultiFamilyCondition($summary);

            // Per-type stats + listings
            [$typeStats, $typeListings] = $this->fetchTypeData($city, $subarea);

            $multiFamilyRow = $this->statsRepo->get_multi_family_avg_price_monthly($city, $subarea);

            $nearbySubareas = Places::where('type', 'subarea')
                ->where('city', $city)
                ->where('place', '!=', $subarea)
                ->where('stats_disabled', 0)
                ->orderBy('order')
                ->limit(6)
                ->get();

            $editorial = $this->buildEditorial($cityRecord->label ?? $city, $cond, $subareaRecord->label ?? $subarea);

            return compact(
                'city', 'citySlug', 'subarea', 'subareaSlug',
                'cityRecord', 'subareaRecord', 'cond',
                'typeStats', 'typeListings',
                'multiFamilyRow', 'nearbySubareas', 'editorial'
            );
        });

        return view('frontend.multi_family_subarea', $data);
    }
}
