<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repository\MarketReportRepository;
use App\Models\Places;
use App\Models\Listings;
use App\Models\Buildings;
use App\Helpers\Helper;
use App\Helpers\FubAreaHelper;
use App\Helpers\AgentContext;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MarketReportController extends Controller
{
    protected MarketReportRepository $repo;

    public function __construct(MarketReportRepository $repo)
    {
        $this->repo = $repo;
    }

    private static array $typeSlugMap = ['condos' => 'Apartment', 'houses' => 'House', 'townhouses' => 'Townhouse'];
    private static array $monthNames  = [
        'january'=>1,'february'=>2,'march'=>3,'april'=>4,'may'=>5,'june'=>6,
        'july'=>7,'august'=>8,'september'=>9,'october'=>10,'november'=>11,'december'=>12,
    ];

    private function isTypeSlug(string $s): bool { return isset(self::$typeSlugMap[$s]); }

    private function canonicalBase(): string
    {
        $agent = AgentContext::current();
        if ($agent) {
            $domain = $agent->settings?->custom_domain ?? null;
            if ($domain) {
                return 'https://' . rtrim($domain, '/');
            }
        }
        return 'https://www.bccondosandhomes.com';
    }

    private function parseMonthSlug(string $s): ?array
    {
        $parts = explode('-', $s, 2);
        if (count($parts) !== 2) return null;
        [$mName, $yr] = $parts;
        $m = self::$monthNames[$mName] ?? null;
        $y = (int)$yr;
        if (!$m || $y < 2010 || $y > 2100) return null;
        return [$y, $m];
    }

    private function computeCondition(int $countSold, int $activeAtStart, int $avgDom, int $countListed = 0): array
    {
        // Absorption rate = sold / active inventory at start of month × 100 (industry standard)
        // Falls back to sold/listed ratio when active_at_start is unavailable
        $divisor    = $activeAtStart > 0 ? $activeAtStart : $countListed;
        $absorption = ($divisor > 0) ? round($countSold / $divisor * 100, 1) : 0;
        $condition  = \App\Helpers\MarketConditionHelper::classify($absorption, $avgDom);
        $label = $condition['label'];
        $color = $condition['color'];
        $class = $condition['class'];
        return compact('label', 'color', 'class', 'absorption');
    }

    private function cacheKey(string ...$parts): string
    {
        // Use explicit readable keys: report-{city}-{subarea}-{type}-{year}-{month}
        // Parts are already normalized slugs/values; sanitize to safe chars
        $safe = array_map(fn($p) => preg_replace('/[^a-z0-9\-_]/', '_', strtolower($p)), $parts);
        return 'report-' . implode('-', $safe);
    }

    private function isCurrentMonth(int $year, int $month): bool
    {
        return $year === (int)date('Y') && $month === (int)date('n');
    }

    private function cacheReport(string $key, callable $cb, int $year, int $month): mixed
    {
        if ($this->isCurrentMonth($year, $month)) {
            return Cache::remember($key, 21600, $cb);
        }
        return Cache::rememberForever($key, $cb);
    }

    private function typeLabel(string $type): string
    {
        return match($type) { 'Apartment'=>'Condo', 'House'=>'House', 'Townhouse'=>'Townhouse', default=>'Property' };
    }

    private function getPlaceData(string $citySlug, string $subareaSlug = ''): array
    {
        $city    = $citySlug    ? Helper::deslugPlace($citySlug)    : '';
        $subarea = $subareaSlug ? Helper::deslugPlace($subareaSlug) : '';
        return [$city, $subarea];
    }

    public function hub(): \Illuminate\View\View
    {
        $now        = Carbon::now();
        $year       = $now->year;
        $month      = $now->month;
        $monthLabel = $now->format('F Y');

        $cKey     = $this->cacheKey('all_cities', $year, $month);
        $snapshot = Cache::remember($cKey, 3600, fn() => $this->repo->getAllCitiesSnapshot($year, $month));
        $cities   = Places::where('type','city')->where('stats_disabled',0)->orderBy('order')->get();

        return view('frontend.market_report_hub', [
            'mode'          => 'main',
            'year'          => $year,
            'month'         => $month,
            'monthLabel'    => $monthLabel,
            'snapshot'      => $snapshot,
            'cities'        => $cities,
            'city'          => '', 'citySlug' => '', 'subarea' => '', 'subareaSlug' => '',
            'canonicalBase' => $this->canonicalBase(),
        ]);
    }

    public function cityHub(string $citySlug): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        [$city] = $this->getPlaceData($citySlug);
        $now        = Carbon::now();
        $year       = $now->year;
        $month      = $now->month;
        $monthLabel = $now->format('F Y');

        $cKey     = $this->cacheKey('city_snap', $city, $year, $month);
        $snapshot = Cache::remember($cKey, 3600, fn() => $this->repo->getCityMonthSnapshot($city, $year, $month));
        $subareas = Places::where('type','subarea')->where('city',$city)->where('stats_disabled',0)->orderBy('order')->get();

        // If no subareas found for this "city", check if the slug is actually a known subarea
        if ($subareas->isEmpty() && empty($snapshot)) {
            $placeRow = Places::where('type', 'subarea')->where('place', $city)->whereNotNull('city')->first();
            if ($placeRow) {
                $parentCitySlug = Helper::enslugPlace($placeRow->city);
                return redirect('/market-report/' . $parentCitySlug . '/' . $citySlug, 301);
            }
        }

        return view('frontend.market_report_hub', [
            'mode'          => 'city',
            'year'          => $year,
            'month'         => $month,
            'monthLabel'    => $monthLabel,
            'snapshot'      => $snapshot,
            'subareas'      => $subareas,
            'city'          => $city,
            'citySlug'      => $citySlug,
            'subarea'       => '',
            'subareaSlug'   => '',
            'canonicalBase' => $this->canonicalBase(),
        ]);
    }

    public function areaOrTypeArchive(string $citySlug, string $subareaOrTypeSlug): \Illuminate\View\View
    {
        [$city] = $this->getPlaceData($citySlug);
        $subarea     = '';
        $subareaSlug = '';
        $type        = '';
        $typeSlug    = '';

        if ($this->isTypeSlug($subareaOrTypeSlug)) {
            $type     = self::$typeSlugMap[$subareaOrTypeSlug];
            $typeSlug = $subareaOrTypeSlug;
            // subareaSlug stays empty — city+type archive
        } else {
            $subarea     = Helper::deslugPlace($subareaOrTypeSlug);
            $subareaSlug = $subareaOrTypeSlug;
        }

        return $this->renderArchive($city, $citySlug, $subarea, $subareaSlug, $type, $typeSlug);
    }

    public function archiveOrReport(string $citySlug, string $subareaOrTypeSlug, string $typeOrMonthSlug): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        [$city] = $this->getPlaceData($citySlug);
        $subarea     = '';
        $subareaSlug = '';
        $type        = '';
        $typeSlug    = '';

        if ($this->isTypeSlug($subareaOrTypeSlug)) {
            // Pattern: /market-report/{city}/{type}/{month}
            $type     = self::$typeSlugMap[$subareaOrTypeSlug];
            $typeSlug = $subareaOrTypeSlug;
            $monthParsed = $this->parseMonthSlug($typeOrMonthSlug);
            if ($monthParsed) {
                [$year, $month] = $monthParsed;
                return $this->renderMonthlyReport($city, $citySlug, $subarea, $subareaSlug, $type, $typeSlug, $year, $month, $typeOrMonthSlug);
            }
            // fallthrough: /market-report/{city}/{type}/{unknown} → treat as archive
            return $this->renderArchive($city, $citySlug, $subarea, $subareaSlug, $type, $typeSlug);
        } else {
            // Pattern: /market-report/{city}/{subarea}/{type|month}
            $subarea     = Helper::deslugPlace($subareaOrTypeSlug);
            $subareaSlug = $subareaOrTypeSlug;
            if ($this->isTypeSlug($typeOrMonthSlug)) {
                $type     = self::$typeSlugMap[$typeOrMonthSlug];
                $typeSlug = $typeOrMonthSlug;
                return $this->renderArchive($city, $citySlug, $subarea, $subareaSlug, $type, $typeSlug);
            }
            // Pattern: /market-report/{city}/{subarea}/{monthSlug} — all-types monthly report
            $monthParsed = $this->parseMonthSlug($typeOrMonthSlug);
            if ($monthParsed) {
                [$year, $month] = $monthParsed;
                // Render monthly report for all types (type = '' queries all residential types in the repository)
                return $this->renderMonthlyReport($city, $citySlug, $subarea, $subareaSlug, '', '', $year, $month, $typeOrMonthSlug);
            }
            return $this->renderArchive($city, $citySlug, $subarea, $subareaSlug, $type, $typeSlug);
        }
    }

    public function monthlyReport(string $citySlug, string $subareaSlug, string $typeSlug, string $monthSlug): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        if (!isset(self::$typeSlugMap[$typeSlug])) {
            return redirect('/market-report/' . $citySlug . '/' . $subareaSlug, 301);
        }
        [$city, $subarea] = $this->getPlaceData($citySlug, $subareaSlug);
        FubAreaHelper::saveToSession($city);
        $type        = self::$typeSlugMap[$typeSlug];
        $monthParsed = $this->parseMonthSlug($monthSlug);
        if (!$monthParsed) {
            return redirect('/market-report/' . $citySlug . '/' . $subareaSlug . '/' . $typeSlug, 301);
        }
        [$year, $month] = $monthParsed;
        return $this->renderMonthlyReport($city, $citySlug, $subarea, $subareaSlug, $type, $typeSlug, $year, $month, $monthSlug);
    }

    private function renderArchive(
        string $city, string $citySlug, string $subarea, string $subareaSlug,
        string $type, string $typeSlug, string $noDataMonth = ''
    ): \Illuminate\View\View {
        $cKey   = $this->cacheKey('avail', $city, $subarea, $type);
        $months = Cache::remember($cKey, 3600, fn() => $this->repo->getAvailableMonths($city, $subarea, $type));

        $enriched = [];
        foreach ($months as $row) {
            $mKey  = $this->cacheKey('mth', $city, $subarea, $type, $row->yr, $row->mo);
            $data  = $this->cacheReport($mKey, fn() => $this->repo->getMonthlyReport($city, $subarea, $type, (int)$row->yr, (int)$row->mo), (int)$row->yr, (int)$row->mo);
            $cond  = ($data && (int)($data->count_sold ?? 0) > 0)
                ? $this->computeCondition((int)$data->count_sold, (int)($data->active_at_start ?? 0), (int)($data->avg_dom ?? 0), (int)($data->count_listed ?? 0))
                : ['label'=>'','color'=>'#aaa','class'=>'','absorption'=>0];
            $monthName = strtolower(date('F', mktime(0,0,0,$row->mo,1,$row->yr)));
            $enriched[] = (object)[
                'yr'              => $row->yr,
                'mo'              => $row->mo,
                'cnt'             => $row->cnt,
                'label'           => date('F Y', mktime(0,0,0,$row->mo,1,$row->yr)),
                'slug'            => "{$monthName}-{$row->yr}",
                'count_sold'      => (int)($data->count_sold      ?? 0),
                'avg_price'       => (int)($data->avg_sold_price   ?? 0),
                'avg_dom'         => (int)($data->avg_dom          ?? 0),
                'active_at_start' => (int)($data->active_at_start  ?? 0),
                'condition'       => $cond,
            ];
        }

        $typeLabel = $type ? $this->typeLabel($type) : 'Real Estate';

        // Fetch place description (subarea first, then city, then fallback)
        // Note: image_url column not yet in production — query description only
        $placeDescription = null;
        $placeImageUrl    = null;
        if ($subarea) {
            $placeDescription = Places::where('type', 'subarea')
                ->where('place', $subarea)
                ->when($city, fn($q) => $q->where('city', $city))
                ->value('description') ?: null;
        }
        if (!$placeDescription && $city) {
            $placeDescription = Places::where('type', 'city')
                ->where('place', $city)
                ->value('description') ?: null;
        }
        if (!$placeDescription && ($city || $subarea)) {
            $areaName  = $subarea ?: $city;
            $typeSuffix = ($typeLabel && $typeLabel !== 'Real Estate') ? strtolower($typeLabel) : 'real estate';
            $placeDescription = "{$areaName} is an active {$typeSuffix} market in British Columbia's Lower Mainland. "
                . "Browse monthly reports below to track sold prices, days on market, and market conditions for {$areaName} based on MLS® data.";
        }

        return view('frontend.market_report_archive', [
            'city'             => $city,
            'citySlug'         => $citySlug,
            'subarea'          => $subarea,
            'subareaSlug'      => $subareaSlug,
            'type'             => $type,
            'typeSlug'         => $typeSlug,
            'typeLabel'        => $typeLabel,
            'months'           => $enriched,
            'noDataMonth'      => $noDataMonth,
            'placeDescription' => $placeDescription,
            'placeImageUrl'    => $placeImageUrl,
            'canonicalBase'    => $this->canonicalBase(),
        ]);
    }

    private function renderMonthlyReport(
        string $city, string $citySlug, string $subarea, string $subareaSlug,
        string $type, string $typeSlug, int $year, int $month, string $monthSlug
    ): \Illuminate\View\View|\Illuminate\Http\RedirectResponse {
        // Validate month availability before fetching full report data
        $availKey    = $this->cacheKey('avail', $city, $subarea, $type);
        $availMonths = Cache::remember($availKey, 3600, fn() => $this->repo->getAvailableMonths($city, $subarea, $type));
        $monthExists = false;
        foreach ($availMonths as $am) {
            if ((int)$am->yr === $year && (int)$am->mo === $month) { $monthExists = true; break; }
        }
        if (!$monthExists) {
            // Month has no sufficient data — 301 redirect to the archive
            $archivePath = '/market-report'
                . ($citySlug    ? '/'.$citySlug    : '')
                . ($subareaSlug ? '/'.$subareaSlug : '')
                . ($typeSlug    ? '/'.$typeSlug    : '');
            return redirect($archivePath, 301);
        }

        $cKey   = $this->cacheKey('mth', $city, $subarea, $type, $year, $month);
        $report = $this->cacheReport($cKey, fn() => $this->repo->getMonthlyReport($city, $subarea, $type, $year, $month), $year, $month);

        // Extra guard: if data still empty after availability check, redirect
        if (!$report || (int)($report->count_sold ?? 0) === 0) {
            $archivePath = '/market-report'
                . ($citySlug    ? '/'.$citySlug    : '')
                . ($subareaSlug ? '/'.$subareaSlug : '')
                . ($typeSlug    ? '/'.$typeSlug    : '');
            return redirect($archivePath, 301);
        }

        $prevY = $month === 1 ? $year - 1 : $year;
        $prevM = $month === 1 ? 12 : $month - 1;
        $prevKey    = $this->cacheKey('mth', $city, $subarea, $type, $prevY, $prevM);
        $prevReport = $this->cacheReport($prevKey, fn() => $this->repo->getMonthlyReport($city, $subarea, $type, $prevY, $prevM), $prevY, $prevM);

        $yoyY    = $year - 1;
        $yoyKey  = $this->cacheKey('mth', $city, $subarea, $type, $yoyY, $month);
        $yoyReport = $this->cacheReport($yoyKey, fn() => $this->repo->getMonthlyReport($city, $subarea, $type, $yoyY, $month), $yoyY, $month);

        $chartKey  = $this->cacheKey('chart36', $city, $subarea, $type, $year, $month);
        $chartData = $this->cacheReport($chartKey, fn() => $this->repo->getTrailing12MonthsSeries($city, $subarea, $type, $year, $month), $year, $month);

        // $availMonths already fetched above for month-availability check
        $prevSlug = null; $nextSlug = null;
        $foundIdx = null;
        foreach ($availMonths as $i => $am) {
            if ((int)$am->yr === $year && (int)$am->mo === $month) { $foundIdx = $i; break; }
        }
        if ($foundIdx !== null) {
            if (isset($availMonths[$foundIdx + 1])) {
                $pm      = $availMonths[$foundIdx + 1];
                $prevSlug = strtolower(date('F', mktime(0,0,0,$pm->mo,1,$pm->yr))) . '-' . $pm->yr;
            }
            if ($foundIdx > 0) {
                $nm      = $availMonths[$foundIdx - 1];
                $nextSlug = strtolower(date('F', mktime(0,0,0,$nm->mo,1,$nm->yr))) . '-' . $nm->yr;
            }
        }

        $condition = $this->computeCondition(
            (int)($report->count_sold      ?? 0),
            (int)($report->active_at_start ?? 0),
            (int)($report->avg_dom         ?? 0),
            (int)($report->count_listed    ?? 0)
        );

        $typeLabel  = $type ? $this->typeLabel($type) : 'Real Estate';
        $monthLabel = date('F Y', mktime(0,0,0,$month,1,$year));
        $prevLabel  = date('F Y', mktime(0,0,0,$prevM,1,$prevY));

        $soldListings = Cache::remember(
            $this->cacheKey('sold_lst', $city, $subarea, $type),
            3600,
            function() use ($city, $subarea, $type) {
                return Listings::briefed()->with('photos')
                    ->sold()
                    ->when($city,    fn($q) => $q->where('city',    $city))
                    ->when($subarea, fn($q) => $q->where('subarea', $subarea))
                    ->when($type,    fn($q) => $q->where('type',    $type))
                    ->orderBy('sold_date', 'desc')
                    ->limit(6)
                    ->get();
            }
        );

        $activeListings = Cache::remember(
            $this->cacheKey('act_lst', $city, $subarea, $type),
            900,
            function() use ($city, $subarea, $type) {
                return Listings::briefed()->with('photos')
                    ->active()
                    ->when($city,    fn($q) => $q->where('city',    $city))
                    ->when($subarea, fn($q) => $q->where('subarea', $subarea))
                    ->when($type,    fn($q) => $q->where('type',    $type))
                    ->orderBy('last_modified', 'desc')
                    ->limit(6)
                    ->get();
            }
        );

        $topBuildings = null;
        if ($type === 'Apartment' && $city) {
            $topBuildings = Cache::remember(
                $this->cacheKey('top_bldg', $city, $subarea),
                3600,
                function() use ($city, $subarea) {
                    return Buildings::where('city', $city)
                        ->when($subarea, fn($q) => $q->where('subarea', $subarea))
                        ->orderBy('units_in_strata', 'desc')
                        ->limit(6)
                        ->get();
                }
            );
        }

        // Fetch place description for the intro paragraph (subarea first, then city, then fallback)
        // Note: image_url column not yet in production — query description only
        $placeDescription = null;
        $placeImageUrl    = null;
        if ($subarea) {
            $placeDescription = Places::where('type', 'subarea')
                ->where('place', $subarea)
                ->when($city, fn($q) => $q->where('city', $city))
                ->value('description') ?: null;
        }
        if (!$placeDescription && $city) {
            $placeDescription = Places::where('type', 'city')
                ->where('place', $city)
                ->value('description') ?: null;
        }
        // Generate a short fallback when no description exists but a location is present
        if (!$placeDescription && ($city || $subarea)) {
            $areaName  = $subarea ?: $city;
            $typePart  = ($typeLabel && $typeLabel !== 'Real Estate') ? strtolower($typeLabel) : 'real estate';
            $placeDescription = "{$areaName} is an active {$typePart} market in British Columbia's Lower Mainland. "
                . "The report below covers sold activity, pricing trends, and market conditions for {$areaName} based on MLS® data.";
        }

        return view('frontend.market_report', [
            'city'        => $city,
            'citySlug'    => $citySlug,
            'subarea'     => $subarea,
            'subareaSlug' => $subareaSlug,
            'type'        => $type,
            'typeSlug'    => $typeSlug,
            'typeLabel'   => $typeLabel,
            'year'        => $year,
            'month'       => $month,
            'monthSlug'   => $monthSlug,
            'monthLabel'  => $monthLabel,
            'report'      => $report,
            'prevReport'  => $prevReport,
            'yoyReport'   => $yoyReport,
            'chartData'   => $chartData,
            'condition'   => $condition,
            'prevSlug'      => $prevSlug,
            'nextSlug'      => $nextSlug,
            'prevY'         => $prevY,
            'prevM'         => $prevM,
            'prevLabel'     => $prevLabel,
            'soldListings'     => $soldListings,
            'activeListings'   => $activeListings,
            'topBuildings'     => $topBuildings,
            'placeDescription' => $placeDescription,
            'placeImageUrl'    => $placeImageUrl,
            'canonicalBase'    => $this->canonicalBase(),
        ]);
    }
}
