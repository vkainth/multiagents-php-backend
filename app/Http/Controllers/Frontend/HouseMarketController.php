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

class HouseMarketController extends Controller
{
    protected $statsRepo;
    protected $houseTypes = ['House', 'Duplex', 'Fourplex', 'Triplex'];

    public function __construct(StatsRepository $statsRepo)
    {
        $this->statsRepo = $statsRepo;
    }

    protected function genCacheKey()
    {
        $args = func_get_args();
        return preg_replace('/\s+/', '_', join('_', $args));
    }

    protected function computeHouseCondition($summary)
    {
        $empty = [
            'label' => null, 'color' => '#888', 'class' => 'verdict-unknown',
            'absorption_rate' => 0, 'avg_dom' => 0, 'price_trend' => 0,
            'sold_30d' => 0, 'sold_90d' => 0, 'current_active' => 0,
            'avg_sold_30d' => 0, 'avg_sold_90d' => 0,
            'insufficient_data' => false,
        ];
        if (!$summary) return array_merge($empty, ['insufficient_data' => true]);

        $sold30  = (int)($summary->sold_30d       ?? 0);
        $sold90  = (int)($summary->sold_90d        ?? 0);
        $active  = (int)($summary->current_active  ?? 0);
        $avg30   = (int)($summary->avg_sold_30d    ?? 0);
        $avg90   = (int)($summary->avg_sold_90d    ?? 0);
        $dom     = (int)($summary->avg_dom_30d     ?? 0);

        if ($sold90 < 3) {
            return array_merge($empty, [
                'insufficient_data' => true,
                'sold_30d' => $sold30,
                'sold_90d' => $sold90,
                'current_active' => $active,
                'avg_sold_30d' => $avg30,
            ]);
        }

        $absorptionRate = $active > 0 ? round(($sold30 / $active) * 100, 1) : 0;
        $priceTrend     = ($avg90 > 0 && $avg30 > 0)
            ? round((($avg30 - $avg90) / $avg90) * 100, 1) : 0;

        $condition = \App\Helpers\MarketConditionHelper::classify($absorptionRate, $dom);
        $label = $condition['label'];
        $color = $condition['color'];
        $class = $condition['class'];

        return [
            'label'            => $label,
            'color'            => $color,
            'class'            => $class,
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
    }

    protected function buildEditorial($cityName, $cond, $subarea = '')
    {
        if (!$cond['label'] || $cond['insufficient_data']) return null;

        $loc   = $subarea ? "{$subarea}, {$cityName}" : $cityName;
        $price = $cond['avg_sold_30d'] ? '$' . number_format($cond['avg_sold_30d']) : null;
        $dom   = $cond['avg_dom'];
        $sold  = $cond['sold_30d'];
        $active = $cond['current_active'];
        $absorb = $cond['absorption_rate'];
        $trend  = $cond['price_trend'];

        $verdictSentence = match(true) {
            str_contains($cond['label'], 'Strong Seller') =>
                "Detached homes in <strong>{$loc}</strong> are in very high demand — moving fast, with multiple offers common.",
            str_contains($cond['label'], "Seller") =>
                "The <strong>{$loc}</strong> house market continues to favour sellers, with more buyers than available single-family homes.",
            str_contains($cond['label'], "Balanced") =>
                "The <strong>{$loc}</strong> house market is balanced, giving both buyers and sellers reasonable negotiating power.",
            default =>
                "Buyers currently have more choice in <strong>{$loc}</strong>'s detached home market, with homes taking longer to sell.",
        };

        $parts = ["{$verdictSentence}"];

        if ($sold && $active) {
            $parts[] = "In the past 30 days, <strong>{$sold} houses and detached homes</strong> sold across {$loc}";
            if ($price) $parts[count($parts)-1] .= " at an average sold price of <strong>{$price}</strong>";
            $parts[count($parts)-1] .= ".";
        }

        if ($dom) {
            $parts[] = "Properties are selling in an average of <strong>{$dom} days</strong> on the market.";
        }

        if ($absorb) {
            $parts[] = "The absorption rate is <strong>{$absorb}%</strong> — meaning {$absorb}% of the active house inventory sells each month.";
        }

        if ($trend != 0) {
            $dir = $trend > 0 ? 'up' : 'down';
            $pct = abs($trend);
            $parts[] = "Average house prices in {$loc} are <strong>{$dir} {$pct}%</strong> compared to the 90-day average" . ($cond['avg_sold_90d'] ? " of $" . number_format($cond['avg_sold_90d']) : '') . ".";
        }

        if (str_contains($cond['label'], "Seller")) {
            $parts[] = "If you're looking to <strong>buy a house in {$loc}</strong>, budget for competitive situations and be prepared to act quickly on well-priced detached homes.";
        } elseif (str_contains($cond['label'], "Buyer")) {
            $parts[] = "If you're considering buying a house in {$loc}, you have more time to negotiate — though well-priced single-family homes still attract attention.";
        }

        return implode(' ', $parts);
    }

    public function hub()
    {
        $cacheKey = 'house_hub_v3';
        $data = Cache::remember($cacheKey, 86400, function () {
            $cities = Places::where('type', 'city')
                ->where('stats_disabled', 0)
                ->orderBy('order')
                ->get();

            $allStats  = $this->statsRepo->get_all_cities_house_summary();
            $statsMap  = collect($allStats)->keyBy('city');

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

            return compact('cities', 'cityStats', 'recentListings');
        });

        return view('frontend.house_hub', $data);
    }

    public function city($citySlug)
    {
        $city = Helper::deslugPlace($citySlug);

        $cityRecord = Places::where('type', 'city')
            ->where('place', $city)
            ->where('stats_disabled', 0)
            ->first();

        if (!$cityRecord) abort(404);

        $cacheKey = $this->genCacheKey('house_city_v3', $city);
        $data = Cache::remember($cacheKey, 86400, function () use ($city, $citySlug, $cityRecord) {
            $overallSummary = $this->statsRepo->get_house_market_summary($city);
            $overallCond    = $this->computeHouseCondition($overallSummary);

            $subareaRows = $this->statsRepo->get_house_subarea_breakdown($city);

            $subareas = Places::where('type', 'subarea')
                ->where('city', $city)
                ->where('stats_disabled', 0)
                ->orderBy('order')
                ->get()
                ->keyBy('place');

            $subareaStats = [];
            foreach ($subareaRows as $row) {
                $subareaStats[$row->subarea] = $this->computeHouseCondition($row);
            }

            $houseRow = $this->statsRepo->get_house_avg_price_monthly($city);

            $priceRange = $this->statsRepo->get_house_sold_price_range('90 DAY', $city);

            $recentListings = Listings::with('aphoto')
                ->where('status', 'Active')
                ->where('city', $city)
                ->whereIn('type', ['House', 'Duplex', 'Fourplex', 'Triplex'])
                ->orderBy('list_date', 'desc')
                ->limit(6)
                ->get();

            $editorial = $this->buildEditorial($cityRecord->label ?? $city, $overallCond);

            return compact(
                'city', 'citySlug', 'cityRecord', 'overallCond',
                'subareas', 'subareaStats', 'houseRow', 'priceRange',
                'recentListings', 'editorial'
            );
        });

        return view('frontend.house_city', $data);
    }

    public function subarea($citySlug, $subareaSlug)
    {
        $city    = Helper::deslugPlace($citySlug);
        $subarea = Helper::deslugPlace($subareaSlug);

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

        $cacheKey = $this->genCacheKey('house_subarea_v3', $city, $subarea);
        $data = Cache::remember($cacheKey, 86400, function () use ($city, $citySlug, $subarea, $subareaSlug, $cityRecord, $subareaRecord) {
            $summary = $this->statsRepo->get_house_market_summary($city, $subarea);
            $cond    = $this->computeHouseCondition($summary);

            $houseRow = $this->statsRepo->get_house_avg_price_monthly($city, $subarea);

            $recentListings = Listings::with('aphoto')
                ->where('status', 'Active')
                ->where('city', $city)
                ->where('subarea', $subarea)
                ->whereIn('type', ['House', 'Duplex', 'Fourplex', 'Triplex'])
                ->orderBy('list_date', 'desc')
                ->limit(6)
                ->get();

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
                'houseRow', 'recentListings', 'nearbySubareas', 'editorial'
            );
        });

        return view('frontend.house_subarea', $data);
    }
}
