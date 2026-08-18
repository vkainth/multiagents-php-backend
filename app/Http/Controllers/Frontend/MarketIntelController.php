<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Listings;
use App\Models\Places;
use App\Models\ListingAlert;
use App\Repository\MarketReportRepository;
use App\Helpers\Helper;
use App\Helpers\MarketConditionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MarketIntelController extends Controller
{
    protected MarketReportRepository $repo;

    public function __construct(MarketReportRepository $repo)
    {
        $this->repo = $repo;
    }

    private function cacheKey(string ...$parts): string
    {
        $safe = array_map(fn($p) => preg_replace('/[^a-z0-9\-_]/', '_', strtolower($p)), $parts);
        return 'mintel-' . implode('-', $safe);
    }

    // ───────────────────────── Monthly update archive ───────────────────────────

    public function monthlyUpdateArchive(string $citySlug): \Illuminate\View\View
    {
        $city     = Helper::deslugPlace($citySlug);
        $now      = Carbon::now();

        $availKey = $this->cacheKey('avail', $city);
        $months   = Cache::remember($availKey, 3600, fn() => $this->repo->getAvailableMonths($city, '', ''));

        $enriched = [];
        foreach ($months as $row) {
            $yr = (int)$row->yr;
            $mo = (int)$row->mo;
            $repKey = $this->cacheKey('mth', $city, $yr, $mo);
            $ttl    = ($yr === $now->year && $mo === $now->month) ? 21600 : PHP_INT_MAX;
            $data   = Cache::remember($repKey, $ttl, fn() => $this->repo->getMonthlyReport($city, '', '', $yr, $mo));
            $enriched[] = (object)[
                'yr'         => $yr,
                'mo'         => $mo,
                'label'      => date('F Y', mktime(0, 0, 0, $mo, 1, $yr)),
                'count_sold' => (int)($data->count_sold    ?? 0),
                'avg_price'  => (int)($data->avg_sold_price ?? 0),
                'avg_dom'    => (int)($data->avg_dom        ?? 0),
            ];
        }

        $metaTitle = "{$city} Real Estate Market Updates | Monthly Reports | Hani & Les";
        $metaDesc  = "Monthly real estate market update archive for {$city}, BC. Browse sold data, average prices, and market conditions by month.";
        $canonical = "https://www.bccondosandhomes.com/market-update/{$citySlug}";

        return view('frontend.market_update_archive', compact(
            'city', 'citySlug', 'months', 'enriched',
            'metaTitle', 'metaDesc', 'canonical'
        ));
    }

    // ───────────────────────── Monthly update page ───────────────────────────

    public function monthlyUpdate(string $citySlug, int $year, int $month): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        if ($year < 2010 || $year > 2100 || $month < 1 || $month > 12) {
            return redirect("/market-update/{$citySlug}", 301);
        }

        $city = Helper::deslugPlace($citySlug);
        $now  = Carbon::now();

        $availKey    = $this->cacheKey('avail', $city);
        $availMonths = Cache::remember($availKey, 3600, fn() => $this->repo->getAvailableMonths($city, '', ''));

        $monthExists = false;
        foreach ($availMonths as $am) {
            if ((int)$am->yr === $year && (int)$am->mo === $month) { $monthExists = true; break; }
        }
        if (!$monthExists) {
            return redirect("/market-update/{$citySlug}", 301);
        }

        $ttl    = ($year === $now->year && $month === $now->month) ? 21600 : PHP_INT_MAX;
        $repKey = $this->cacheKey('mth', $city, $year, $month);
        $report = Cache::remember($repKey, $ttl, fn() => $this->repo->getMonthlyReport($city, '', '', $year, $month));

        if (!$report || !(int)($report->count_sold ?? 0)) {
            return redirect("/market-update/{$citySlug}", 301);
        }

        // Previous month
        $prevY = $month === 1 ? $year - 1 : $year;
        $prevM = $month === 1 ? 12 : $month - 1;
        $prevRepKey = $this->cacheKey('mth', $city, $prevY, $prevM);
        $prevTtl    = ($prevY === $now->year && $prevM === $now->month) ? 21600 : PHP_INT_MAX;
        $prevReport = Cache::remember($prevRepKey, $prevTtl, fn() => $this->repo->getMonthlyReport($city, '', '', $prevY, $prevM));

        // YoY
        $yoyY      = $year - 1;
        $yoyRepKey = $this->cacheKey('mth', $city, $yoyY, $month);
        $yoyTtl    = PHP_INT_MAX;
        $yoyReport = Cache::remember($yoyRepKey, $yoyTtl, fn() => $this->repo->getMonthlyReport($city, '', '', $yoyY, $month));

        // Prev/Next navigation
        $prevSlug = null; $nextSlug = null;
        $foundIdx = null;
        $availArr = is_array($availMonths) ? $availMonths : $availMonths;
        foreach ($availArr as $i => $am) {
            if ((int)$am->yr === $year && (int)$am->mo === $month) { $foundIdx = $i; break; }
        }
        if ($foundIdx !== null) {
            $availArrVals = array_values((array)$availArr);
            if (isset($availArrVals[$foundIdx + 1])) {
                $pm = $availArrVals[$foundIdx + 1];
                $prevSlug = (int)$pm->yr . '/' . (int)$pm->mo;
            }
            if ($foundIdx > 0) {
                $nm = $availArrVals[$foundIdx - 1];
                $nextSlug = (int)$nm->yr . '/' . (int)$nm->mo;
            }
        }

        // Market condition
        $countSold   = (int)($report->count_sold     ?? 0);
        $activeStart = (int)($report->active_at_start ?? 0);
        $avgDom      = (int)($report->avg_dom         ?? 0);
        $countListed = (int)($report->count_listed    ?? 0);
        $divisor     = $activeStart > 0 ? $activeStart : $countListed;
        $absorption  = ($divisor > 0) ? round($countSold / $divisor * 100, 1) : 0;
        $condition   = MarketConditionHelper::classify($absorption, $avgDom);
        $condition['absorption'] = $absorption;

        $monthLabel = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        $prevLabel  = date('F Y', mktime(0, 0, 0, $prevM, 1, $prevY));

        $metaTitle = "{$city} Real Estate Market Update – {$monthLabel} | Hani & Les";
        $_soldStr  = $countSold ? number_format($countSold) . ' units sold' : 'Sold activity';
        $_avgP     = (int)($report->avg_sold_price ?? 0);
        $metaDesc  = "The {$monthLabel} real estate market update for {$city}, BC. {$_soldStr}"
            . ($_avgP ? ", avg sold price \${$_avgP}." : '.')
            . " Market condition: {$condition['label']}.";
        if (strlen($metaDesc) > 160) {
            $cut = substr($metaDesc, 0, 157);
            $metaDesc = rtrim(substr($cut, 0, strrpos($cut, ' ')), '.,;:') . '...';
        }
        $canonical = "https://www.bccondosandhomes.com/market-update/{$citySlug}/{$year}/{$month}";

        return view('frontend.market_update', compact(
            'city', 'citySlug', 'year', 'month', 'monthLabel', 'prevLabel',
            'report', 'prevReport', 'yoyReport', 'condition',
            'countSold', 'activeStart', 'avgDom', 'countListed', 'absorption',
            'prevSlug', 'nextSlug', 'prevY', 'prevM',
            'metaTitle', 'metaDesc', 'canonical'
        ));
    }

    // ───────────────────────── New listings this week ───────────────────────────

    public function newListings(string $citySlug): \Illuminate\View\View
    {
        $city = Helper::deslugPlace($citySlug);

        $cKey    = $this->cacheKey('new_lst', $city);
        $listings = Cache::remember($cKey, 3600, function() use ($city) {
            return Listings::briefed()->with('photos')
                ->active()
                ->where('city', $city)
                ->where('inserted', '>=', Carbon::now()->subDays(7))
                ->orderBy('inserted', 'desc')
                ->limit(48)
                ->get();
        });

        $countKey = $this->cacheKey('new_lst_cnt', $city);
        $totalCount = Cache::remember($countKey, 3600, function() use ($city) {
            return Listings::active()
                ->where('city', $city)
                ->where('inserted', '>=', Carbon::now()->subDays(7))
                ->count();
        });

        $metaTitle = "New Listings This Week in {$city}, BC | MLS® | Hani & Les";
        $metaDesc  = "Browse the latest MLS® listings added in {$city} in the last 7 days. Updated daily. {$totalCount} new " . ($totalCount === 1 ? 'listing' : 'listings') . " found.";
        $canonical = "https://www.bccondosandhomes.com/new-listings/{$citySlug}";

        return view('frontend.new_listings', compact(
            'city', 'citySlug', 'listings', 'totalCount',
            'metaTitle', 'metaDesc', 'canonical'
        ));
    }

    // ───────────────────────── Price reductions ───────────────────────────

    public function priceReductions(string $citySlug): \Illuminate\View\View
    {
        $city = Helper::deslugPlace($citySlug);

        $cKey    = $this->cacheKey('price_red', $city);
        $listings = Cache::remember($cKey, 3600, function() use ($city) {
            return Listings::briefed()
                ->addSelect(['prev_price'])
                ->with('photos')
                ->active()
                ->where('city', $city)
                ->whereColumn('prev_price', '>', 'listprice_2')
                ->where('prev_price', '>', 0)
                ->where('listprice_2', '>', 0)
                ->where('last_modified', '>=', Carbon::now()->subDays(14))
                ->orderBy('last_modified', 'desc')
                ->limit(48)
                ->get();
        });

        $metaTitle = "Price Reductions in {$city}, BC | Recent Price Drops | Hani & Les";
        $metaDesc  = "See the latest price reductions on MLS® listings in {$city}, BC in the last 14 days. Find homes with recent price drops.";
        $canonical = "https://www.bccondosandhomes.com/price-reductions/{$citySlug}";

        return view('frontend.price_reductions', compact(
            'city', 'citySlug', 'listings',
            'metaTitle', 'metaDesc', 'canonical'
        ));
    }

    // ───────────────────────── Sold over asking ───────────────────────────

    public function soldOverAsking(string $citySlug): \Illuminate\View\View
    {
        $city    = Helper::deslugPlace($citySlug);
        $isGuest = !Auth::check();

        $cKey    = $this->cacheKey('sold_oask', $city);
        $listings = Cache::remember($cKey, 3600, function() use ($city) {
            return Listings::briefed()->with('photos')
                ->sold()
                ->where('city', $city)
                ->whereColumn('soldprice_2', '>', 'listprice_2')
                ->where('listprice_2', '>', 0)
                ->where('soldprice_2', '>', 0)
                ->where('sold_date', '>=', Carbon::now()->subDays(90))
                ->orderBy('sold_date', 'desc')
                ->limit(48)
                ->get();
        });

        $metaTitle = "Sold Over Asking in {$city}, BC – Last 90 Days | Hani & Les";
        $metaDesc  = "Homes sold above the asking price in {$city}, BC in the last 90 days. Includes days on market and % over asking. Sign in to see sold prices.";
        $canonical = "https://www.bccondosandhomes.com/sold-over-asking/{$citySlug}";

        return view('frontend.sold_over_asking', compact(
            'city', 'citySlug', 'listings', 'isGuest',
            'metaTitle', 'metaDesc', 'canonical'
        ));
    }

    // ───────────────────────── Email alert signup ───────────────────────────

    public function storeAlert(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'email'   => 'required|email|max:191',
            'city'    => 'nullable|string|max:100',
            'subarea' => 'nullable|string|max:100',
            'type'    => 'nullable|string|max:50',
            'source'  => 'nullable|string|max:100',
        ]);

        $ipHash = $request->ip() ? hash('sha256', $request->ip()) : null;

        // Deduplicate: skip if same email+city was stored in the last 24h
        $exists = ListingAlert::where('email', $validated['email'])
            ->where('city', $validated['city'] ?? null)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->exists();

        if (!$exists) {
            ListingAlert::create(array_merge($validated, ['ip_hash' => $ipHash]));
        }

        return response()->json(['ok' => true]);
    }
}
