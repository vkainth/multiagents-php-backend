<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Shared service for computing aggregate SEO stats on for-sale subarea pages.
 * Used by both SearchListingsController (runtime cache-miss path) and
 * the seo:warm-for-sale-stats artisan command (scheduled pre-warming path).
 * [added:Task#382, expanded:Task#380]
 */
class ForSaleSeoStatsService
{
    /**
     * Run aggregate queries against boards.listings and return a stats array
     * with the shape expected by buildForSaleSeoData() and the FAQ schema builder.
     *
     * @param  string      $queryStr  Raw WHERE clause from bccondosandhomes.mls_query
     * @param  string|null $subarea   Subarea name (already ucwords-normalised by caller)
     * @param  mixed       $beds      Bedroom count filter, or false for no filter
     */
    public function compute(string $queryStr, ?string $subarea, $beds = false): array
    {
        $q = DB::connection('mysql_boards')->table('listings')
            ->whereRaw($queryStr)
            ->where('table', 'mlsr_listings')
            ->where('status', 'Active');

        if ($subarea) {
            $q = $q->where('subarea', $subarea);
        }
        if ($beds && is_numeric($beds)) {
            $q = $q->where('bedrooms', (int) $beds);
        }

        $activeCount = (clone $q)->count();

        $priceRow = (clone $q)
            ->where('listprice_2', '>', 0)
            ->selectRaw('MIN(listprice_2) as min_price, MAX(listprice_2) as max_price, AVG(listprice_2) as avg_price')
            ->first();

        $typeRows = (clone $q)
            ->whereIn('type', ['House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'])
            ->selectRaw('type, COUNT(*) as cnt')
            ->groupBy('type')
            ->get();

        $typeCounts = ['House' => 0, 'Apartment' => 0, 'Townhouse' => 0];
        foreach ($typeRows as $typeRow) {
            if (in_array($typeRow->type, ['Townhouse', 'Duplex', 'Fourplex', 'Triplex'])) {
                $typeCounts['Townhouse'] += (int) $typeRow->cnt;
            } elseif (isset($typeCounts[$typeRow->type])) {
                $typeCounts[$typeRow->type] += (int) $typeRow->cnt;
            }
        }

        // Median list price from active listings
        $activePrices = (clone $q)
            ->where('listprice_2', '>', 0)
            ->orderBy('listprice_2')
            ->limit(2000)
            ->pluck('listprice_2')
            ->map(fn($p) => (float) $p)
            ->values()
            ->toArray();
        $medianListPrice = $this->computeMedian($activePrices);

        // Avg DOM and sales count from last 90 days sold
        $ninetyDaysAgo = Carbon::now()->subDays(90);
        $soldBase = DB::connection('mysql_boards')->table('listings')
            ->whereRaw($queryStr)
            ->where('table', 'mlsr_listings')
            ->where('status', 'Sold')
            ->where('sold_date', '>=', $ninetyDaysAgo);
        if ($subarea) {
            $soldBase = $soldBase->where('subarea', $subarea);
        }
        if ($beds && is_numeric($beds)) {
            $soldBase = $soldBase->where('bedrooms', (int) $beds);
        }

        $salesCount = (clone $soldBase)->count();
        $domRow = (clone $soldBase)
            ->whereNotNull('list_date')
            ->whereNotNull('sold_date')
            ->selectRaw('AVG(DATEDIFF(sold_date, list_date)) as avg_dom')
            ->first();
        $avgDom = (int) round((float) ($domRow->avg_dom ?? 0));

        // Sales ratio and market type (90-day sold vs active)
        $salesRatio = ($activeCount > 0) ? round($salesCount / $activeCount * 100, 1) : 0;
        if ($salesRatio < 12)     { $marketType = "Buyer's Market"; }
        elseif ($salesRatio < 20) { $marketType = "Balanced Market"; }
        else                      { $marketType = "Seller's Market"; }

        return [
            'active_count'      => $activeCount,
            'min_price'         => (int) ($priceRow->min_price ?? 0),
            'max_price'         => (int) ($priceRow->max_price ?? 0),
            'avg_price'         => (int) round((float) ($priceRow->avg_price ?? 0)),
            'type_counts'       => $typeCounts,
            'median_list_price' => (int) round($medianListPrice),
            'avg_dom'           => $avgDom,
            'sales_count'       => $salesCount,
            'sales_ratio'       => $salesRatio,
            'market_type'       => $marketType,
            'avg_price_sqft'    => 0,
        ];
    }

    private function computeMedian(array $values): float
    {
        $cnt = count($values);
        if ($cnt === 0) {
            return 0.0;
        }
        sort($values);
        $mid = intdiv($cnt, 2);
        return ($cnt % 2 === 0)
            ? ($values[$mid - 1] + $values[$mid]) / 2.0
            : (float) $values[$mid];
    }
}
