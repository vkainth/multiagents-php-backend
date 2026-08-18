<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use App\Models\Places;
use App\Repository\StatsRepository;
use Illuminate\Support\Facades\Cache;

class TopRealtorController extends Controller
{
    protected StatsRepository $statsRepo;

    public function __construct(StatsRepository $statsRepo)
    {
        $this->statsRepo = $statsRepo;
    }

    protected function computeCondition($summary): array
    {
        $empty = [
            'label' => null, 'color' => '#888', 'class' => 'verdict-unknown',
            'absorption_rate' => 0, 'avg_dom' => 0, 'price_trend' => 0,
            'sold_30d' => 0, 'current_active' => 0, 'avg_sold_30d' => 0,
        ];
        if (!$summary) return $empty;

        $sold30  = (int)($summary->sold_30d      ?? 0);
        $active  = (int)($summary->current_active ?? 0);
        $avg30   = (int)($summary->avg_sold_30d   ?? 0);
        $avg90   = (int)($summary->avg_sold_90d   ?? 0);
        $dom     = (int)($summary->avg_dom_30d    ?? 0);

        if ($sold30 < 1) {
            return array_merge($empty, ['sold_30d' => $sold30, 'current_active' => $active, 'avg_sold_30d' => $avg30]);
        }

        $absorptionRate = $active > 0 ? round(($sold30 / $active) * 100, 1) : 0;
        $priceTrend     = ($avg90 > 0 && $avg30 > 0) ? round((($avg30 - $avg90) / $avg90) * 100, 1) : 0;

        $condition = \App\Helpers\MarketConditionHelper::classify($absorptionRate, $dom);
        $label = $condition['label'];
        $color = $condition['color'];
        $class = $condition['class'];

        return [
            'label'           => $label,
            'color'           => $color,
            'class'           => $class,
            'absorption_rate' => $absorptionRate,
            'avg_dom'         => $dom,
            'price_trend'     => $priceTrend,
            'sold_30d'        => $sold30,
            'current_active'  => $active,
            'avg_sold_30d'    => $avg30,
        ];
    }

    public function hub(): \Illuminate\View\View
    {
        $cities = Cache::remember('top_realtor_hub_cities', 7200, function () {
            return Places::where('type', 'city')
                ->where('stats_disabled', 0)
                ->orderBy('order')
                ->get();
        });

        return view('frontend.top_realtor_hub', [
            'cities' => $cities,
        ]);
    }

    public function city(string $citySlug): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $city = Helper::deslugPlace($citySlug);

        $cityRecord = Places::where('type', 'city')
            ->whereRaw('LOWER(place) = LOWER(?)', [$city])
            ->first();

        if (!$cityRecord) {
            abort(404);
        }

        $city = $cityRecord->place;

        $cacheKey = 'top_realtor_city_' . md5($city);
        $condition = Cache::remember($cacheKey, 3600, function () use ($city) {
            $summary = $this->statsRepo->get_market_summary($city, '');
            return $this->computeCondition($summary);
        });

        $subareas = Places::where('type', 'subarea')
            ->where('city', $city)
            ->where('stats_disabled', 0)
            ->orderBy('order')
            ->get();

        $team      = Helper::getTeamAgentsNew();
        $teamCount = $team->count();

        return view('frontend.top_realtor', [
            'city'        => $city,
            'citySlug'    => $citySlug,
            'subarea'     => null,
            'subareaSlug' => null,
            'condition'   => $condition,
            'subareas'    => $subareas,
            'team'        => $team,
            'teamCount'   => $teamCount,
        ]);
    }

    public function subarea(string $citySlug, string $subareaSlug): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
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

        $city    = $cityRecord->place;
        $subarea = $subareaRecord->place;

        $cacheKey = 'top_realtor_subarea_' . md5($city . '_' . $subarea);
        $condition = Cache::remember($cacheKey, 3600, function () use ($city, $subarea) {
            $summary = $this->statsRepo->get_market_summary($city, $subarea);
            return $this->computeCondition($summary);
        });

        $subareas = Places::where('type', 'subarea')
            ->where('city', $city)
            ->where('stats_disabled', 0)
            ->orderBy('order')
            ->get();

        $team      = Helper::getTeamAgentsNew();
        $teamCount = $team->count();

        return view('frontend.top_realtor', [
            'city'        => $city,
            'citySlug'    => $citySlug,
            'subarea'     => $subarea,
            'subareaSlug' => $subareaSlug,
            'condition'   => $condition,
            'subareas'    => $subareas,
            'team'        => $team,
            'teamCount'   => $teamCount,
        ]);
    }
}
