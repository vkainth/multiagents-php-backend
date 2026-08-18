<?php

namespace App\Repository;

use Illuminate\Support\Facades\DB;

class MarketReportRepository
{
    protected $conn = 'mysql_pixi360';

    private function typeFilter(string $type, string $alias = 'boards.listings'): string
    {
        $col = "{$alias}.type";
        if ($type) return "{$col} = '" . addslashes($type) . "'";
        return "{$col} IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')";
    }

    private function placeFilter(string $city, string $subarea, string $alias = 'listings'): string
    {
        if ($subarea && $city) {
            return "{$alias}.subarea = '" . addslashes($subarea) . "' AND {$alias}.city = '" . addslashes($city) . "'";
        } elseif ($city) {
            return "{$alias}.city = '" . addslashes($city) . "'";
        }
        return "1=1";
    }

    private function monthRange(int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-d', mktime(0, 0, 0, $month + 1, 1, $year));
        return [$start, $end];
    }

    /**
     * Returns stats for a specific month:
     *   - count_sold         (units sold that month)
     *   - avg_sold_price
     *   - avg_dom
     *   - max_sold_price / min_sold_price
     *   - active_at_start    (inventory at start of month — denominator for absorption rate)
     *   - count_listed       (new listings that came on market during the month — for display)
     */
    public function getMonthlyReport(string $city, string $subarea, string $type, int $year, int $month): ?object
    {
        [$start, $end] = $this->monthRange($year, $month);

        // Outer query filters (uses main table alias)
        $outerPf = $this->placeFilter($city, $subarea, 'listings');
        $outerTf = $this->typeFilter($type, 'boards.listings');

        // Sub-query filters (uses alias la / lb)
        $cityFilter    = $city    ? "la.city = '"    . addslashes($city)    . "'" : '1=1';
        $subareaFilter = $subarea ? "la.subarea = '" . addslashes($subarea) . "'" : '1=1';
        $typeFilterSub = $type
            ? "la.type = '" . addslashes($type) . "'"
            : "la.type IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')";
        $placeSubA = $city ? "{$cityFilter}" . ($subarea ? " AND {$subareaFilter}" : '') : '1=1';

        $cityFilterB    = $city    ? "lb.city = '"    . addslashes($city)    . "'" : '1=1';
        $subareaFilterB = $subarea ? "lb.subarea = '" . addslashes($subarea) . "'" : '1=1';
        $typeFilterB    = $type
            ? "lb.type = '" . addslashes($type) . "'"
            : "lb.type IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')";
        $placeSubB = $city ? "{$cityFilterB}" . ($subarea ? " AND {$subareaFilterB}" : '') : '1=1';

        $activeAtStartSql = "(SELECT COUNT(*) FROM boards.listings la
             WHERE la.list_date < '{$start}'
               AND (la.sold_date IS NULL OR la.sold_date >= '{$start}')
               AND {$placeSubA} AND {$typeFilterSub})";

        $countListedSql = "(SELECT COUNT(*) FROM boards.listings lb
             WHERE lb.list_date >= '{$start}' AND lb.list_date < '{$end}'
               AND {$placeSubB} AND {$typeFilterB})";

        $sql = "SELECT
            COUNT(*) AS count_sold,
            ROUND(AVG(soldprice_2)) AS avg_sold_price,
            ROUND(AVG(DATEDIFF(sold_date, list_date))) AS avg_dom,
            MAX(soldprice_2) AS max_sold_price,
            MIN(soldprice_2) AS min_sold_price,
            {$activeAtStartSql} AS active_at_start,
            {$countListedSql}   AS count_listed
        FROM boards.listings
        WHERE listings.status = 'Sold'
          AND listings.sold_date >= '{$start}' AND listings.sold_date < '{$end}'
          AND {$outerPf} AND {$outerTf}";

        $rows = DB::connection($this->conn)->select($sql);
        return $rows ? $rows[0] : null;
    }

    public function getAvailableMonths(string $city, string $subarea, string $type): array
    {
        $pf = $this->placeFilter($city, $subarea);
        $tf = $this->typeFilter($type);

        $sql = "SELECT YEAR(sold_date) AS yr, MONTH(sold_date) AS mo, COUNT(*) AS cnt
        FROM boards.listings
        WHERE listings.status = 'Sold' AND sold_date IS NOT NULL AND sold_date > '2010-01-01'
          AND {$pf} AND {$tf}
        GROUP BY yr, mo
        HAVING cnt >= 3
        ORDER BY yr DESC, mo DESC";

        return DB::connection($this->conn)->select($sql);
    }

    public function getTrailing12MonthsSeries(string $city, string $subarea, string $type, int $endYear, int $endMonth): array
    {
        $pf = $this->placeFilter($city, $subarea);
        $tf = $this->typeFilter($type);

        $months = [];
        $y = $endYear; $m = $endMonth;
        for ($i = 0; $i < 36; $i++) {
            $months[] = [$y, $m];
            $m--;
            if ($m < 1) { $m = 12; $y--; }
        }
        $months = array_reverse($months);

        $allRanges  = array_map(fn($p) => $this->monthRange($p[0], $p[1]), $months);
        $rangeStart = $allRanges[0][0];
        $rangeEnd   = end($allRanges)[1];

        $cases = [];
        foreach ($months as $idx => [$my, $mm]) {
            [$s, $e] = $allRanges[$idx];
            $cases[] = "WHEN (sold_date >= '{$s}' AND sold_date < '{$e}') THEN '{$my}-{$mm}'";
        }

        $sql = "SELECT
            CASE " . implode(' ', $cases) . " ELSE NULL END AS period_key,
            COUNT(*) AS count_sold,
            ROUND(AVG(soldprice_2)) AS avg_sold_price,
            ROUND(AVG(DATEDIFF(sold_date, list_date))) AS avg_dom
        FROM boards.listings
        WHERE listings.status = 'Sold'
          AND sold_date >= '{$rangeStart}' AND sold_date < '{$rangeEnd}'
          AND {$pf} AND {$tf}
        GROUP BY period_key
        HAVING period_key IS NOT NULL
        ORDER BY period_key ASC";

        $rows = DB::connection($this->conn)->select($sql);
        $indexed = [];
        foreach ($rows as $row) { $indexed[$row->period_key] = $row; }

        $result = [];
        foreach ($months as [$my, $mm]) {
            $key   = "{$my}-{$mm}";
            $label = date('M Y', mktime(0, 0, 0, $mm, 1, $my));
            $result[] = (object)[
                'year'           => $my,
                'month'          => $mm,
                'label'          => $label,
                'count_sold'     => (int)($indexed[$key]->count_sold     ?? 0),
                'avg_sold_price' => (int)($indexed[$key]->avg_sold_price ?? 0),
                'avg_dom'        => (int)($indexed[$key]->avg_dom        ?? 0),
            ];
        }
        return $result;
    }

    public function getCityMonthSnapshot(string $city, int $year, int $month): array
    {
        [$start, $end] = $this->monthRange($year, $month);
        $cityEsc = addslashes($city);

        $sql = "SELECT
            listings.subarea AS area_name,
            COUNT(*) AS count_sold,
            ROUND(AVG(soldprice_2)) AS avg_sold_price,
            ROUND(AVG(DATEDIFF(sold_date, list_date))) AS avg_dom,
            (SELECT COUNT(*) FROM boards.listings la
             WHERE la.city = '{$cityEsc}'
               AND la.subarea = listings.subarea
               AND la.list_date < '{$start}'
               AND (la.sold_date IS NULL OR la.sold_date >= '{$start}')
               AND la.type IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')) AS active_at_start,
            (SELECT COUNT(*) FROM boards.listings lb
             WHERE lb.city = '{$cityEsc}'
               AND lb.subarea = listings.subarea
               AND lb.list_date >= '{$start}' AND lb.list_date < '{$end}'
               AND lb.type IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')) AS count_listed
        FROM boards.listings
        WHERE listings.status = 'Sold'
          AND listings.sold_date >= '{$start}' AND listings.sold_date < '{$end}'
          AND listings.city = '{$cityEsc}'
          AND listings.type IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')
          AND listings.subarea IS NOT NULL AND listings.subarea != ''
        GROUP BY listings.subarea
        HAVING count_sold >= 2
        ORDER BY count_sold DESC
        LIMIT 15";

        return DB::connection($this->conn)->select($sql);
    }

    public function getAllCitiesSnapshot(int $year, int $month): array
    {
        [$start, $end] = $this->monthRange($year, $month);

        $sql = "SELECT
            listings.city AS city_name,
            COUNT(*) AS count_sold,
            ROUND(AVG(soldprice_2)) AS avg_sold_price,
            ROUND(AVG(DATEDIFF(sold_date, list_date))) AS avg_dom,
            (SELECT COUNT(*) FROM boards.listings la
             WHERE la.city = listings.city
               AND la.list_date < '{$start}'
               AND (la.sold_date IS NULL OR la.sold_date >= '{$start}')
               AND la.type IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')) AS active_at_start,
            (SELECT COUNT(*) FROM boards.listings lb
             WHERE lb.city = listings.city
               AND lb.list_date >= '{$start}' AND lb.list_date < '{$end}'
               AND lb.type IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')) AS count_listed
        FROM boards.listings
        WHERE listings.status = 'Sold'
          AND listings.sold_date >= '{$start}' AND listings.sold_date < '{$end}'
          AND listings.type IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')
          AND listings.city IN (SELECT place FROM pixilinkvow.places WHERE type='city' AND stats_disabled=0)
        GROUP BY listings.city
        HAVING count_sold >= 3
        ORDER BY count_sold DESC";

        return DB::connection($this->conn)->select($sql);
    }

    public function getAvailableMonthsForCityType(string $city, string $type): array
    {
        $tf = $type
            ? "listings.type = '" . addslashes($type) . "'"
            : "listings.type IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')";
        $cityEsc = addslashes($city);

        $sql = "SELECT YEAR(sold_date) AS yr, MONTH(sold_date) AS mo, COUNT(*) AS cnt
        FROM boards.listings
        WHERE listings.status = 'Sold' AND sold_date IS NOT NULL AND sold_date > '2010-01-01'
          AND listings.city = '{$cityEsc}'
          AND {$tf}
        GROUP BY yr, mo
        HAVING cnt >= 5
        ORDER BY yr DESC, mo DESC
        LIMIT 24";

        return DB::connection($this->conn)->select($sql);
    }
}
