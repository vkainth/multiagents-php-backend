<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Repository\StatsRepository;
use App\Models\Places;
use App\Models\Buildings;
use App\Models\Listings;
use App\Helpers\Helper;

class NeighbourhoodController extends Controller
{
    protected $statsRepo;

    public function __construct(StatsRepository $statsRepo)
    {
        $this->statsRepo = $statsRepo;
    }

    protected function genCacheKey()
    {
        $args = func_get_args();
        return preg_replace('/\s+/', '_', join('_', $args));
    }

    protected function computeMarketCondition($summary)
    {
        $empty = [
            'label' => null, 'color' => '#888', 'class' => 'verdict-unknown',
            'absorption_rate' => 0, 'avg_dom' => 0, 'price_trend' => 0,
            'sold_30d' => 0, 'current_active' => 0, 'avg_sold_30d' => 0, 'avg_sold_90d' => 0,
        ];
        if (!$summary || !($summary->current_active ?? 0)) return $empty;

        $absorptionRate = ($summary->sold_30d / $summary->current_active) * 100;
        $avgDom         = (int)($summary->avg_dom_30d ?? 0);
        $priceTrend     = ($summary->avg_sold_90d && $summary->avg_sold_30d && $summary->avg_sold_90d > 0)
            ? (($summary->avg_sold_30d - $summary->avg_sold_90d) / $summary->avg_sold_90d) * 100
            : 0;

        $condition = \App\Helpers\MarketConditionHelper::classify($absorptionRate, $avgDom);

        return [
            'label'           => $condition['label'],
            'color'           => $condition['color'],
            'class'           => $condition['class'],
            'absorption_rate' => round($absorptionRate, 1),
            'avg_dom'         => $avgDom,
            'price_trend'     => round($priceTrend, 1),
            'sold_30d'        => (int)($summary->sold_30d       ?? 0),
            'current_active'  => (int)($summary->current_active ?? 0),
            'avg_sold_30d'    => (int)($summary->avg_sold_30d   ?? 0),
            'avg_sold_90d'    => (int)($summary->avg_sold_90d   ?? 0),
        ];
    }

    public function index()
    {
        $cacheKey = 'neighbourhood_hub_v2';
        $data = Cache::remember($cacheKey, 86400, function () {
            $cities = Places::where('type', 'city')
                ->where('stats_disabled', 0)
                ->where('stats_subareas_disabled', 0)
                ->orderBy('order')
                ->get();

            $allStats  = $this->statsRepo->get_all_cities_market_summary();
            $statsMap  = collect($allStats)->keyBy('city');

            $cityStats = [];
            foreach ($cities as $c) {
                $cityStats[$c->place] = $this->computeMarketCondition($statsMap->get($c->place));
            }

            return compact('cities', 'cityStats');
        });

        return view('frontend.neighbourhood_hub', $data);
    }

    public function cityHub($citySlug)
    {
        $city = Helper::deslugPlace($citySlug);

        $cityRecord = Places::where('type', 'city')
            ->whereRaw('LOWER(place) = LOWER(?)', [$city])
            ->first();

        if (!$cityRecord) {
            abort(404);
        }

        $city = $cityRecord->place;

        $cacheKey = $this->genCacheKey('neighbourhood_city_v2', $city);
        $data = Cache::remember($cacheKey, 86400, function () use ($city, $citySlug, $cityRecord) {
            $subareas = Places::where('type', 'subarea')
                ->where('city', $city)
                ->where('stats_disabled', 0)
                ->orderBy('order')
                ->get();

            $overallSummary   = $this->statsRepo->get_market_summary($city);
            $overallCondition = $this->computeMarketCondition($overallSummary);

            $batchRows  = $this->statsRepo->get_market_summary_batch($city);
            $batchMap   = collect($batchRows)->keyBy('subarea');

            $subareaStats = [];
            foreach ($subareas as $sa) {
                $subareaStats[$sa->place] = $this->computeMarketCondition($batchMap->get($sa->place));
            }

            $subareas = $subareas->sortByDesc(function ($sa) use ($subareaStats) {
                return $subareaStats[$sa->place]['absorption_rate'] ?? 0;
            })->values();

            return compact('city', 'citySlug', 'cityRecord', 'subareas', 'overallCondition', 'subareaStats');
        });

        return view('frontend.neighbourhood_city', $data);
    }

    public function guide($citySlug, $subareaSlug)
    {
        $city    = Helper::deslugPlace($citySlug);
        $subarea = Helper::deslugPlace($subareaSlug);

        $cityRecord = Places::where('type', 'city')
            ->whereRaw('LOWER(place) = LOWER(?)', [$city])
            ->first();

        $subareaRecord = Places::where('type', 'subarea')
            ->whereRaw('LOWER(place) = LOWER(?)', [$subarea])
            ->whereRaw('LOWER(city) = LOWER(?)', [$city])
            ->first();

        if (!$cityRecord || !$subareaRecord) {
            abort(404);
        }

        $city        = $cityRecord->place;
        $subarea     = $subareaRecord->place;
        $description = $subareaRecord->description ?? null;

        $cacheKey = $this->genCacheKey('neighbourhood_guide', $city, $subarea);
        $data = Cache::remember($cacheKey, 86400, function () use ($city, $citySlug, $subarea, $subareaSlug, $cityRecord) {
            $condoSummary     = $this->statsRepo->get_market_summary($city, $subarea, 'Apartment');
            $houseSummary     = $this->statsRepo->get_market_summary($city, $subarea, 'House');
            $townhouseSummary = $this->statsRepo->get_market_summary($city, $subarea, 'Townhouse');
            $allSummary       = $this->statsRepo->get_market_summary($city, $subarea);

            $condoCondition     = $this->computeMarketCondition($condoSummary);
            $houseCondition     = $this->computeMarketCondition($houseSummary);
            $townhouseCondition = $this->computeMarketCondition($townhouseSummary);
            $allCondition       = $this->computeMarketCondition($allSummary);

            $topBuildings = Buildings::where('city', $city)
                ->where('subarea', $subarea)
                ->orderBy('units_in_strata', 'desc')
                ->limit(6)
                ->get();

            $condoListings = Listings::where('status', 'Active')
                ->where('city', $city)
                ->where('subarea', $subarea)
                ->where('type', 'Apartment')
                ->orderBy('list_date', 'desc')
                ->limit(4)
                ->get();

            $houseListings = Listings::where('status', 'Active')
                ->where('city', $city)
                ->where('subarea', $subarea)
                ->whereIn('type', ['House', 'Townhouse'])
                ->orderBy('list_date', 'desc')
                ->limit(4)
                ->get();

            $recentSold = Listings::where('status', 'Sold')
                ->where('city', $city)
                ->where('subarea', $subarea)
                ->whereIn('type', ['Apartment', 'House', 'Townhouse'])
                ->where('sold_date', '>=', now()->subDays(60))
                ->orderBy('sold_date', 'desc')
                ->limit(8)
                ->get();

            $priceSeries = $this->statsRepo->get_avg_price_monthly($city, $subarea);

            $nearbySubareas = Places::where('type', 'subarea')
                ->where('city', $city)
                ->where('place', '!=', $subarea)
                ->where('stats_disabled', 0)
                ->orderBy('order')
                ->limit(3)
                ->get();

            $nearbyStats = [];
            foreach ($nearbySubareas as $ns) {
                $sm = $this->statsRepo->get_market_summary($city, $ns->place);
                $nearbyStats[$ns->place] = $this->computeMarketCondition($sm);
            }

            $buildingYears = $topBuildings->pluck('yearbuilt')->filter()->values();
            $avgYear = $buildingYears->count() ? (int)round($buildingYears->avg()) : null;

            $buildingCount = Buildings::where('city', $city)->where('subarea', $subarea)->count();

            $avgLat = $topBuildings->pluck('latitude')->filter()->avg();
            $avgLng = $topBuildings->pluck('longitude')->filter()->avg();

            return compact(
                'city', 'citySlug', 'subarea', 'subareaSlug',
                'condoCondition', 'houseCondition', 'townhouseCondition', 'allCondition',
                'topBuildings', 'condoListings', 'houseListings', 'recentSold', 'priceSeries',
                'nearbySubareas', 'nearbyStats', 'avgYear', 'buildingCount', 'avgLat', 'avgLng'
            );
        });

        $data['description'] = $description;

        return view('frontend.neighbourhood_guide', $data);
    }
}
