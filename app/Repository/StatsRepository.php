<?php

namespace App\Repository;

use App\Models\Listings;
use Illuminate\Support\Facades\DB;
use App\Models\Places;


class StatsRepository
{

    protected $connection_360 = 'mysql_pixi360';

    public function getCurrentlyForSale($cities, $type, $selectedcity = NULL, $propertyType = array())
    {
        $currentlyForSale = array();
        foreach ($cities as $city) {
            $query = Listings::where($type, $city)->where('status', 'Active');
            if ($selectedcity) {
                $query->where('city', $selectedcity);
            }
            if (count($propertyType) > 0) {
                $query->whereIn('type', $propertyType);
            }
            $currentlyForSale['avgPrice'][$city] = $query->avg('listprice_2');
            $currentlyForSale['total'][$city] = $query->count();
        }
        return $currentlyForSale;
    }

    public function getOtherStats($cities, $type, $firstDay, $lastDay, $selectedcity = NULL, $propertyType = array())
    {
        $data = array();
        foreach ($cities as $city) {
            $cityListingQuery = Listings::where($type, $city)->where('status', 'Sold')->where('sold_date', '>=', $firstDay)->where('sold_date', '<=', $lastDay);
            if ($selectedcity) {
                $cityListingQuery->where('city', $selectedcity);
            }
            if (count($propertyType) > 0) {
                $cityListingQuery->whereIn('type', $propertyType);
            }
            $cityListing = $cityListingQuery->get();
            $data[$city]['soldCount'] = count($cityListing);
            $total_price = 0;
            $total_days_on_market = 0;
            foreach ($cityListing as $listing) {
                $total_days_on_market = $total_days_on_market + $listing->days_on_market();
                $total_price = $total_price + $listing->soldprice_2;
            }
            if ($total_days_on_market > 0) {
                $avg_days_on_market = $total_days_on_market / $data[$city]['soldCount'];
            } else {
                $avg_days_on_market = 0;
            }
            if ($total_price > 0) {
                $avg_price = $total_price / $data[$city]['soldCount'];
            } else {
                $avg_price = 0;
            }
            $data[$city]['total_price'] = $total_price;
            $data[$city]['total_days_on_market'] = $total_days_on_market;
            $data[$city]['avg_days_on_market'] = $avg_days_on_market;
            $data[$city]['avg_price'] = $avg_price;
        }

        return $data;
    }

    public function getCurrentWeekRecords($cities, $type, $firstDayOfWeek, $lastDay, $selectedcity = NULL)
    {
        $currentWeekRecords = array();

        $newRecordQuery = Listings::whereIn($type, $cities)->where('list_date', '>=', $firstDayOfWeek)->where('list_date', '<=', $lastDay);
        $soldRecordQuery = Listings::whereIn($type, $cities)->where('sold_date', '>=', $firstDayOfWeek)->where('sold_date', '<=', $lastDay);
        $terminatedRecordQuery = Listings::whereIn($type, $cities)->where(function ($q) {
            $q->where('status', 'Expired')->orWhere('status', 'Terminated');
        })->where('updated', '>=', $firstDayOfWeek)->where('updated', '<=', $lastDay);

        if ($selectedcity) {
            $newRecordQuery->where('city', $selectedcity);
            $soldRecordQuery->where('city', $selectedcity);
            $terminatedRecordQuery->where('city', $selectedcity);
        }

        $currentWeekRecords['New'] = $newRecordQuery->count();
        $currentWeekRecords['Sold'] = $soldRecordQuery->count();
        $currentWeekRecords['Terminated'] = $terminatedRecordQuery->count();
        return $currentWeekRecords;
    }

    public function getStatsOLD($cities, $type, $city, $firstDay, $lastDay, $lastYearFirst, $lastYearLast, $propertyType)
    {
        $stats = NULL;

        $andQuery = '';
        $andQuery2 = '';

        if ($propertyType) {
            $andQuery = " and type IN ('" . implode("','", $propertyType) . "') ";
            $andQuery2 = " and boards.listings.type IN ('" . implode("','", $propertyType) . "') ";
        }

        if ($type == "city") {
            // $query = "SELECT place, round(avg(listprice_2)) AS avg_price, round(avg(soldprice_2)) as sold_price, SUM(status = 'active') AS listed_in_time_range, SUM(status = 'sold') AS sold, round(AVG(DATEDIFF(sold_date,list_date))) AS avg_datediff, last_year_sum, current_active_listings FROM pixilinkvow.places JOIN boards.listings ON listings.city = places.place JOIN (SELECT count(*) AS last_year_sum, subarea,city FROM boards.listings_master WHERE (list_date > '".$lastYearFirst."' AND list_date < '".$lastYearLast."') AND status = 'sold' AND city IN ('".implode("','", $cities)."') GROUP BY city) AS inner_query ON inner_query.city = places.place JOIN (SELECT count(*) AS current_active_listings, subarea,city FROM boards.listings_master WHERE status = 'active' AND city IN ('".implode("','", $cities)."') GROUP BY city) AS act_list_query ON act_list_query.city = places.place WHERE places.type = 'city' AND places.place IN ('".implode("','", $cities)."') AND(list_date > '".$firstDay."' AND list_date < '".$lastDay."') AND status IN ('active','sold') GROUP BY place ORDER BY label ASC";

            //city IN ('".implode("','", $cities)."')

            $query = "SELECT 
         place,
         current_active_listings,
         round(avg(if(`status`='active',`listprice_2`,null))) AS avg_price,
         SUM(`status` = 'active') AS listed_in_time_range,
         SUM(`status` = 'sold' and (sold_date > '" . $firstDay . "' AND sold_date < '" . $lastDay . "')) AS sold_in_time_range,
         round(AVG(DATEDIFF(sold_date,list_date))) AS avg_dom, 
         last_year_sold
         FROM pixilinkvow.places
         JOIN boards.listings ON listings.city = places.place
         JOIN (
             SELECT 
                 count(*) AS last_year_sold,
                 subarea,city 
             FROM boards.listings_master 
             WHERE
                 (`sold_date` > '" . $lastYearFirst . "' AND `sold_date` < '" . $lastYearLast . "') AND
                 `status` = 'sold'
            
                 " . $andQuery . "
             GROUP BY city
         ) AS inner_query ON inner_query.city = places.place
         JOIN (
             SELECT 
                 count(*) AS current_active_listings,
                 subarea,city 
             FROM boards.listings_master 
             WHERE
                 `status` = 'active' 
                 
                 " . $andQuery . "
             GROUP BY city
         ) AS curr_act_query ON curr_act_query.city = places.place
         WHERE
             `places`.`type` = 'city' AND
             ((list_date > '" . $firstDay . "' AND list_date < '" . $lastDay . "') OR (sold_date > '" . $firstDay . "' AND sold_date < '" . $lastDay . "')) AND
             `status` IN ('active','sold')
             " . $andQuery2 . "
         GROUP BY place
         ORDER BY `label` ASC";

            $stats =  DB::connection($this->connection_360)
                ->select($query);
        } else {
            // $query = "SELECT place, round(avg(listprice_2)) AS avg_price, round(avg(soldprice_2)) as sold_price, SUM(status = 'active') AS listed_in_time_range, SUM(status = 'sold') AS sold, round(AVG(DATEDIFF(sold_date,list_date))) AS avg_datediff,last_year_sum, current_active_listings FROM pixilinkvow.places JOIN boards.listings ON listings.subarea = places.place JOIN (SELECT count(*) AS last_year_sum, subarea FROM boards.listings_master WHERE (list_date > '".$lastYearFirst."' AND list_date < '".$lastYearLast."') AND status = 'sold' AND city = '".$city."' GROUP BY subarea) AS inner_query ON inner_query.subarea = places.place JOIN (SELECT count(*) AS current_active_listings, subarea FROM boards.listings_master WHERE status = 'active' AND city = '".$city."' GROUP BY subarea) AS act_list_query ON act_list_query.subarea = places.place WHERE places.type = 'subarea' AND places.city = 'Vancouver' AND (list_date > '".$firstDay."' AND list_date < '".$lastDay."') AND status IN ('active','sold') GROUP BY place ORDER BY label ASC";

            $query = "SELECT 
        place,
        current_active_listings,
        round(avg(if(`status`='active',`listprice_2`,null))) AS avg_price,
        SUM(`status` = 'active') AS listed_in_time_range,
        SUM(`status` = 'sold' and (sold_date > '" . $firstDay . "' AND sold_date < '" . $lastDay . "')) AS sold_in_time_range,
        round(AVG(DATEDIFF(sold_date,list_date))) AS avg_dom, 
        last_year_sold
        FROM pixilinkvow.places
        JOIN boards.listings ON listings.subarea = places.place
        JOIN (
            SELECT 
                count(*) AS last_year_sold,
                subarea 
            FROM boards.listings_master 
            WHERE
                (`sold_date` > '" . $lastYearFirst . "' AND `sold_date` < '" . $lastYearLast . "') AND
                `status` = 'sold' AND
                `city` = '" . $city . "' 
                " . $andQuery . "
            GROUP BY subarea
        ) AS inner_query ON inner_query.subarea = places.place
        JOIN (
            SELECT 
                count(*) AS current_active_listings,
                subarea 
            FROM boards.listings_master 
            WHERE
                `status` = 'active' AND
                `city` = '" . $city . "'
                " . $andQuery . "
            GROUP BY subarea
        ) AS curr_act_query ON curr_act_query.subarea = places.place
        WHERE
            `places`.`type` = 'subarea' AND
            `places`.`city` = '" . $city . "' AND
            ((list_date > '" . $firstDay . "' AND list_date < '" . $lastDay . "') OR (sold_date > '" . $firstDay . "' AND sold_date < '" . $lastDay . "')) AND
            `status` IN ('active','sold')
            " . $andQuery2 . "
        GROUP BY place
        ORDER BY `label` ASC";
            $stats =  DB::connection($this->connection_360)
                ->select($query);
        }
        return $stats;
    }


    public function getStats2($interval)
    {
        $query = "SELECT
        label as city_name, 
        current_active, 
        SUM(`status` = 'active') AS listed_by_filter, 
        listed_90, 
        SUM(`status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) sold_by_filter, 
        sold_90, 
        ROUND(AVG(IF(`status`='sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")),soldprice_2,null))) AS avg_sold_price_filter, 
        avg_sold_price_90, 
        ROUND(AVG(DATEDIFF(sold_date,list_date))) AS avg_dom_filter,
        avg_dom_90
    FROM pixilinkvow.places
        JOIN boards.listings ON listings.city = places.place
        /* query to get all currently active listings */
        JOIN (
            SELECT 
                count(*) AS current_active,
                city 
            FROM boards.listings 
            WHERE
                `status` = 'active'
            GROUP BY city
        ) AS curr_act_query ON curr_act_query.city = places.place
        /* query to get all data for 90 days columns */
        JOIN (
            SELECT 
                SUM(`status` = 'active') AS listed_90,
                SUM(`status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY))) AS sold_90,
                ROUND(AVG(IF(`status`='sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)),soldprice_2,null))) AS avg_sold_price_90,
                ROUND(AVG(DATEDIFF(sold_date,list_date))) AS avg_dom_90,
                city
            FROM boards.listings
            WHERE
                ((list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)) OR ((sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)))) AND              
                `status` IN ('active', 'sold')
                GROUP by city
        ) AS query_90 on query_90.city = places.place
    WHERE places.type = 'city' AND stats_disabled = 0 and
        ((list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")) OR ((sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")))) AND
        `status` IN ('active', 'sold')
    GROUP BY label
    ORDER BY label ASC";
        $stats =  DB::connection($this->connection_360)
            ->select($query);

        return $stats;
    }

    public function get_city_stats($interval, $city = "")
    {

        if ($city) {
            $query = "SELECT
                label as city_name, places.stats_subareas_disabled, place as place_name,
                SUM(`status` = 'active') AS current_active, 
                SUM( (list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS listed_by_filter, 
                SUM( (list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY))) AS listed_90,
                SUM(`status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) sold_by_filter, 
                SUM(`status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY))) AS sold_90, 
                ROUND(AVG(IF(`status`='sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")),soldprice_2,null))) AS avg_sold_price_filter, 
                ROUND(AVG(IF(`status`='sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)),soldprice_2,null))) AS avg_sold_price_90, 
                ROUND(AVG(IF(`status`='sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")),DATEDIFF(sold_date,list_date),null))) AS avg_dom_filter,
                ROUND(AVG(IF(`status`='sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)),DATEDIFF(sold_date,list_date),null))) AS avg_dom_90
            FROM pixilinkvow.places
                JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
            WHERE
                places.type = 'subarea' AND places.stats_disabled=0 and places.city='" . $city . "' and boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') and
                `status` IN ('active', 'sold')
            GROUP BY label
            ORDER BY `label` ASC";
        } else {
            $query = "SELECT
            label as city_name, places.stats_subareas_disabled, place as place_name,
            SUM(`status` = 'active') AS current_active, 
            SUM( (list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS listed_by_filter, 
            SUM( (list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY))) AS listed_90,
            SUM(`status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) sold_by_filter, 
            SUM(`status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY))) AS sold_90, 
            ROUND(AVG(IF(`status`='sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")),soldprice_2,null))) AS avg_sold_price_filter, 
            ROUND(AVG(IF(`status`='sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)),soldprice_2,null))) AS avg_sold_price_90, 
            ROUND(AVG(IF(`status`='sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")),DATEDIFF(sold_date,list_date),null))) AS avg_dom_filter,
            ROUND(AVG(IF(`status`='sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)),DATEDIFF(sold_date,list_date),null))) AS avg_dom_90
        FROM pixilinkvow.places
            JOIN boards.listings ON listings.city = places.place
        WHERE
            places.type = 'city' AND places.stats_disabled=0 and boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') and
            `status` IN ('active', 'sold')
        GROUP BY label
        ORDER BY `order` ASC";
        }

        $stats =  DB::connection($this->connection_360)
            ->select($query);
        return $stats;
    }

    public function get_all_cities_house_summary()
    {
        $query = "SELECT
            listings.city,
            SUM(`status` = 'active') AS current_active,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)) AS sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), soldprice_2, NULL))) AS avg_sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_30d
        FROM boards.listings
        INNER JOIN pixilinkvow.places ON places.place = listings.city AND places.type = 'city' AND places.stats_disabled = 0
        WHERE listings.type IN ('House','Duplex','Fourplex','Triplex')
            AND `status` IN ('active', 'sold')
        GROUP BY listings.city";

        return DB::connection($this->connection_360)->select($query) ?: [];
    }

    public function get_house_market_summary($city = '', $subarea = '')
    {
        $typeFilter = "boards.listings.type IN ('House','Duplex','Fourplex','Triplex')";

        if ($subarea && $city) {
            $placeFilter = "listings.subarea = '" . addslashes($subarea) . "' AND listings.city = '" . addslashes($city) . "'";
        } elseif ($city) {
            $placeFilter = "listings.city = '" . addslashes($city) . "'";
        } else {
            $placeFilter = "listings.city IS NOT NULL";
        }

        $query = "SELECT
            SUM(`status` = 'active') AS current_active,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)) AS sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), soldprice_2, NULL))) AS avg_sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_30d
        FROM boards.listings
        WHERE {$placeFilter}
            AND {$typeFilter}
            AND `status` IN ('active', 'sold')";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ? $results[0] : null;
    }

    public function get_house_subarea_breakdown($city)
    {
        $city = addslashes($city);
        $query = "SELECT
            listings.subarea,
            SUM(`status` = 'active') AS current_active,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)) AS sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), soldprice_2, NULL))) AS avg_sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_30d
        FROM boards.listings
        INNER JOIN pixilinkvow.places ON places.place = listings.subarea AND places.city = '{$city}' AND places.type = 'subarea' AND places.stats_disabled = 0
        WHERE listings.city = '{$city}'
            AND listings.type IN ('House','Duplex','Fourplex','Triplex')
            AND `status` IN ('active', 'sold')
        GROUP BY listings.subarea
        ORDER BY sold_30d DESC";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ?: [];
    }

    public function get_house_sold_price_range($interval, $city = '', $subarea = '')
    {
        $typeFilter = "type IN ('House','Duplex','Fourplex','Triplex')";

        if ($city && $subarea) {
            $placeWhere = "city = '" . addslashes($city) . "' AND subarea = '" . addslashes($subarea) . "'";
        } elseif ($city) {
            $placeWhere = "city = '" . addslashes($city) . "' AND subarea IN (SELECT place FROM pixilinkvow.places WHERE type='subarea' AND stats_disabled=0 AND city='" . addslashes($city) . "')";
        } else {
            $placeWhere = "city IS NOT NULL";
        }

        $query = "SELECT
            CASE
              WHEN soldprice_2 BETWEEN 0 AND 250000       THEN 'A_0-250,000'
              WHEN soldprice_2 BETWEEN 250000 AND 500000  THEN 'B_250,000-500,000'
              WHEN soldprice_2 BETWEEN 500000 AND 750000  THEN 'C_500,000-750,000'
              WHEN soldprice_2 BETWEEN 750000 AND 1000000 THEN 'D_750,000-1,000,000'
              WHEN soldprice_2 BETWEEN 1000000 AND 1500000 THEN 'E_1,000,000-1,500,000'
              WHEN soldprice_2 BETWEEN 1500000 AND 2000000 THEN 'F_1,500,000-2,000,000'
              WHEN soldprice_2 BETWEEN 2000000 AND 2500000 THEN 'G_2,000,000-2,500,000'
              WHEN soldprice_2 BETWEEN 2500000 AND 3000000 THEN 'H_2,500,000-3,000,000'
              WHEN soldprice_2 BETWEEN 3000000 AND 4000000 THEN 'I_3,000,000-4,000,000'
              WHEN soldprice_2 BETWEEN 4000000 AND 5000000 THEN 'J_4,000,000-5,000,000'
              WHEN soldprice_2 BETWEEN 5000000 AND 6000000 THEN 'K_5,000,000-6,000,000'
              WHEN soldprice_2 BETWEEN 6000000 AND 7000000 THEN 'L_6,000,000-7,000,000'
              ELSE 'M_7,000,000+'
            END AS `Range`,
            COUNT(id) AS `Count`
        FROM boards.listings
        WHERE `status` = 'sold'
            AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL {$interval})
            AND {$typeFilter}
            AND {$placeWhere}
        GROUP BY `Range`
        ORDER BY `Range` ASC";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ?: [];
    }

    public function get_house_avg_price_monthly($city = '', $subarea = '')
    {
        $typeFilter = "boards.listings_master.type IN ('House','Duplex','Fourplex','Triplex')";

        if ($city && $subarea) {
            $joinAndWhere = "JOIN boards.listings_master ON listings_master.subarea = places.place AND listings_master.city = '" . addslashes($city) . "'
                WHERE places.type = 'subarea' AND places.stats_disabled = 0 AND places.city = '" . addslashes($city) . "' AND places.place = '" . addslashes($subarea) . "'";
        } elseif ($city) {
            $joinAndWhere = "JOIN boards.listings_master ON listings_master.city = places.place
                WHERE places.type = 'city' AND places.stats_disabled = 0 AND places.place = '" . addslashes($city) . "'";
        } else {
            $joinAndWhere = "JOIN boards.listings_master ON 1=1 WHERE 1=1";
        }

        $query = "SELECT
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),soldprice_2,NULL))) AS avg_price_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),soldprice_2,NULL))) AS avg_price_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),soldprice_2,NULL))) AS avg_price_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),soldprice_2,NULL))) AS avg_price_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),soldprice_2,NULL))) AS avg_price_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),soldprice_2,NULL))) AS avg_price_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),soldprice_2,NULL))) AS avg_price_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),soldprice_2,NULL))) AS avg_price_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),soldprice_2,NULL))) AS avg_price_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),soldprice_2,NULL))) AS avg_price_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),soldprice_2,NULL))) AS avg_price_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            ROUND(AVG(IF(`status`='sold' AND sold_date >= DATE_FORMAT(CURDATE(),'%Y-%m-01'),soldprice_2,NULL))) AS avg_price_twelve,
            MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
        FROM pixilinkvow.places
        {$joinAndWhere}
            AND {$typeFilter}
            AND `status` = 'sold'
            AND sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH) AND CURDATE()";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ? $results[0] : null;
    }

    public function get_all_cities_townhouse_summary()
    {
        $query = "SELECT
            listings.city,
            SUM(`status` = 'active') AS current_active,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)) AS sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), soldprice_2, NULL))) AS avg_sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_30d
        FROM boards.listings
        INNER JOIN pixilinkvow.places ON places.place = listings.city AND places.type = 'city' AND places.stats_disabled = 0
        WHERE listings.type = 'Townhouse'
            AND `status` IN ('active', 'sold')
        GROUP BY listings.city";

        return DB::connection($this->connection_360)->select($query) ?: [];
    }

    public function get_townhouse_market_summary($city = '', $subarea = '')
    {
        $typeFilter = "boards.listings.type = 'Townhouse'";

        if ($subarea && $city) {
            $placeFilter = "listings.subarea = '" . addslashes($subarea) . "' AND listings.city = '" . addslashes($city) . "'";
        } elseif ($city) {
            $placeFilter = "listings.city = '" . addslashes($city) . "'";
        } else {
            $placeFilter = "listings.city IS NOT NULL";
        }

        $query = "SELECT
            SUM(`status` = 'active') AS current_active,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)) AS sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), soldprice_2, NULL))) AS avg_sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_30d
        FROM boards.listings
        WHERE {$placeFilter}
            AND {$typeFilter}
            AND `status` IN ('active', 'sold')";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ? $results[0] : null;
    }

    public function get_townhouse_subarea_breakdown($city)
    {
        $city = addslashes($city);
        $query = "SELECT
            listings.subarea,
            SUM(`status` = 'active') AS current_active,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)) AS sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), soldprice_2, NULL))) AS avg_sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_30d
        FROM boards.listings
        INNER JOIN pixilinkvow.places ON places.place = listings.subarea AND places.city = '{$city}' AND places.type = 'subarea' AND places.stats_disabled = 0
        WHERE listings.city = '{$city}'
            AND listings.type = 'Townhouse'
            AND `status` IN ('active', 'sold')
        GROUP BY listings.subarea
        ORDER BY sold_30d DESC";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ?: [];
    }

    public function get_townhouse_sold_price_range($interval, $city = '', $subarea = '')
    {
        $typeFilter = "type = 'Townhouse'";

        if ($city && $subarea) {
            $placeWhere = "city = '" . addslashes($city) . "' AND subarea = '" . addslashes($subarea) . "'";
        } elseif ($city) {
            $placeWhere = "city = '" . addslashes($city) . "' AND subarea IN (SELECT place FROM pixilinkvow.places WHERE type='subarea' AND stats_disabled=0 AND city='" . addslashes($city) . "')";
        } else {
            $placeWhere = "city IS NOT NULL";
        }

        $query = "SELECT
            CASE
              WHEN soldprice_2 BETWEEN 0 AND 250000       THEN 'A_0-250,000'
              WHEN soldprice_2 BETWEEN 250000 AND 500000  THEN 'B_250,000-500,000'
              WHEN soldprice_2 BETWEEN 500000 AND 750000  THEN 'C_500,000-750,000'
              WHEN soldprice_2 BETWEEN 750000 AND 1000000 THEN 'D_750,000-1,000,000'
              WHEN soldprice_2 BETWEEN 1000000 AND 1500000 THEN 'E_1,000,000-1,500,000'
              WHEN soldprice_2 BETWEEN 1500000 AND 2000000 THEN 'F_1,500,000-2,000,000'
              WHEN soldprice_2 BETWEEN 2000000 AND 2500000 THEN 'G_2,000,000-2,500,000'
              WHEN soldprice_2 BETWEEN 2500000 AND 3000000 THEN 'H_2,500,000-3,000,000'
              WHEN soldprice_2 BETWEEN 3000000 AND 4000000 THEN 'I_3,000,000-4,000,000'
              WHEN soldprice_2 BETWEEN 4000000 AND 5000000 THEN 'J_4,000,000-5,000,000'
              WHEN soldprice_2 BETWEEN 5000000 AND 6000000 THEN 'K_5,000,000-6,000,000'
              WHEN soldprice_2 BETWEEN 6000000 AND 7000000 THEN 'L_6,000,000-7,000,000'
              ELSE 'M_7,000,000+'
            END AS `Range`,
            COUNT(id) AS `Count`
        FROM boards.listings
        WHERE `status` = 'sold'
            AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL {$interval})
            AND {$typeFilter}
            AND {$placeWhere}
        GROUP BY `Range`
        ORDER BY `Range` ASC";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ?: [];
    }

    public function get_townhouse_avg_price_monthly($city = '', $subarea = '')
    {
        $typeFilter = "boards.listings_master.type = 'Townhouse'";

        if ($city && $subarea) {
            $joinAndWhere = "JOIN boards.listings_master ON listings_master.subarea = places.place AND listings_master.city = '" . addslashes($city) . "'
                WHERE places.type = 'subarea' AND places.stats_disabled = 0 AND places.city = '" . addslashes($city) . "' AND places.place = '" . addslashes($subarea) . "'";
        } elseif ($city) {
            $joinAndWhere = "JOIN boards.listings_master ON listings_master.city = places.place
                WHERE places.type = 'city' AND places.stats_disabled = 0 AND places.place = '" . addslashes($city) . "'";
        } else {
            $joinAndWhere = "JOIN boards.listings_master ON 1=1 WHERE 1=1";
        }

        $query = "SELECT
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),soldprice_2,NULL))) AS avg_price_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),soldprice_2,NULL))) AS avg_price_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),soldprice_2,NULL))) AS avg_price_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),soldprice_2,NULL))) AS avg_price_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),soldprice_2,NULL))) AS avg_price_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),soldprice_2,NULL))) AS avg_price_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),soldprice_2,NULL))) AS avg_price_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),soldprice_2,NULL))) AS avg_price_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),soldprice_2,NULL))) AS avg_price_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),soldprice_2,NULL))) AS avg_price_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),soldprice_2,NULL))) AS avg_price_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            ROUND(AVG(IF(`status`='sold' AND sold_date >= DATE_FORMAT(CURDATE(),'%Y-%m-01'),soldprice_2,NULL))) AS avg_price_twelve,
            MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
        FROM pixilinkvow.places
        {$joinAndWhere}
            AND {$typeFilter}
            AND `status` = 'sold'
            AND sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH) AND CURDATE()";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ? $results[0] : null;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Multi-Family (Duplex / Triplex / Fourplex) stats
    // ──────────────────────────────────────────────────────────────────────────

    public function get_all_cities_multi_family_summary()
    {
        $query = "SELECT
            listings.city,
            SUM(`status` = 'active') AS current_active,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)) AS sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), soldprice_2, NULL))) AS avg_sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_30d
        FROM boards.listings
        INNER JOIN pixilinkvow.places ON places.place = listings.city AND places.type = 'city' AND places.stats_disabled = 0
        WHERE listings.type IN ('Duplex','Triplex','Fourplex')
            AND `status` IN ('active', 'sold')
        GROUP BY listings.city";

        return DB::connection($this->connection_360)->select($query) ?: [];
    }

    public function get_multi_family_type_summary($type, $city = '', $subarea = '')
    {
        $type       = addslashes($type);
        $typeFilter = "boards.listings.type = '{$type}'";

        if ($subarea && $city) {
            $placeFilter = "listings.subarea = '" . addslashes($subarea) . "' AND listings.city = '" . addslashes($city) . "'";
        } elseif ($city) {
            $placeFilter = "listings.city = '" . addslashes($city) . "'";
        } else {
            $placeFilter = "listings.city IS NOT NULL";
        }

        $query = "SELECT
            SUM(`status` = 'active') AS current_active,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)) AS sold_90d,
            ROUND(AVG(IF(`status`='active', listprice_2, NULL))) AS avg_list_price,
            ROUND(AVG(IF(`status`='active' AND livingarea_2 > 0, listprice_2/livingarea_2, NULL))) AS avg_price_sqft,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), soldprice_2, NULL))) AS avg_sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_30d
        FROM boards.listings
        WHERE {$placeFilter}
            AND {$typeFilter}
            AND `status` IN ('active', 'sold')";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ? $results[0] : null;
    }

    public function get_multi_family_market_summary($city = '', $subarea = '')
    {
        $typeFilter = "boards.listings.type IN ('Duplex','Triplex','Fourplex')";

        if ($subarea && $city) {
            $placeFilter = "listings.subarea = '" . addslashes($subarea) . "' AND listings.city = '" . addslashes($city) . "'";
        } elseif ($city) {
            $placeFilter = "listings.city = '" . addslashes($city) . "'";
        } else {
            $placeFilter = "listings.city IS NOT NULL";
        }

        $query = "SELECT
            SUM(`status` = 'active') AS current_active,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)) AS sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), soldprice_2, NULL))) AS avg_sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_30d
        FROM boards.listings
        WHERE {$placeFilter}
            AND {$typeFilter}
            AND `status` IN ('active', 'sold')";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ? $results[0] : null;
    }

    public function get_multi_family_subarea_breakdown($city)
    {
        $city = addslashes($city);
        $query = "SELECT
            listings.subarea,
            SUM(`status` = 'active') AS current_active,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)) AS sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), soldprice_2, NULL))) AS avg_sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_30d
        FROM boards.listings
        INNER JOIN pixilinkvow.places ON places.place = listings.subarea AND places.city = '{$city}' AND places.type = 'subarea' AND places.stats_disabled = 0
        WHERE listings.city = '{$city}'
            AND listings.type IN ('Duplex','Triplex','Fourplex')
            AND `status` IN ('active', 'sold')
        GROUP BY listings.subarea
        ORDER BY sold_30d DESC";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ?: [];
    }

    public function get_multi_family_sold_price_range($interval, $city = '', $subarea = '')
    {
        $typeFilter = "type IN ('Duplex','Triplex','Fourplex')";

        if ($city && $subarea) {
            $placeWhere = "city = '" . addslashes($city) . "' AND subarea = '" . addslashes($subarea) . "'";
        } elseif ($city) {
            $placeWhere = "city = '" . addslashes($city) . "' AND subarea IN (SELECT place FROM pixilinkvow.places WHERE type='subarea' AND stats_disabled=0 AND city='" . addslashes($city) . "')";
        } else {
            $placeWhere = "city IS NOT NULL";
        }

        $query = "SELECT
            CASE
              WHEN soldprice_2 BETWEEN 0 AND 250000        THEN 'A_0-250,000'
              WHEN soldprice_2 BETWEEN 250000 AND 500000   THEN 'B_250,000-500,000'
              WHEN soldprice_2 BETWEEN 500000 AND 750000   THEN 'C_500,000-750,000'
              WHEN soldprice_2 BETWEEN 750000 AND 1000000  THEN 'D_750,000-1,000,000'
              WHEN soldprice_2 BETWEEN 1000000 AND 1500000 THEN 'E_1,000,000-1,500,000'
              WHEN soldprice_2 BETWEEN 1500000 AND 2000000 THEN 'F_1,500,000-2,000,000'
              WHEN soldprice_2 BETWEEN 2000000 AND 2500000 THEN 'G_2,000,000-2,500,000'
              WHEN soldprice_2 BETWEEN 2500000 AND 3000000 THEN 'H_2,500,000-3,000,000'
              WHEN soldprice_2 BETWEEN 3000000 AND 4000000 THEN 'I_3,000,000-4,000,000'
              WHEN soldprice_2 BETWEEN 4000000 AND 5000000 THEN 'J_4,000,000-5,000,000'
              WHEN soldprice_2 BETWEEN 5000000 AND 6000000 THEN 'K_5,000,000-6,000,000'
              WHEN soldprice_2 BETWEEN 6000000 AND 7000000 THEN 'L_6,000,000-7,000,000'
              ELSE 'M_7,000,000+'
            END AS `Range`,
            COUNT(id) AS `Count`
        FROM boards.listings
        WHERE `status` = 'sold'
            AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL {$interval})
            AND {$typeFilter}
            AND {$placeWhere}
        GROUP BY `Range`
        ORDER BY `Range` ASC";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ?: [];
    }

    public function get_multi_family_avg_price_monthly($city = '', $subarea = '')
    {
        $typeFilter = "boards.listings_master.type IN ('Duplex','Triplex','Fourplex')";

        if ($city && $subarea) {
            $joinAndWhere = "JOIN boards.listings_master ON listings_master.subarea = places.place AND listings_master.city = '" . addslashes($city) . "'
                WHERE places.type = 'subarea' AND places.stats_disabled = 0 AND places.city = '" . addslashes($city) . "' AND places.place = '" . addslashes($subarea) . "'";
        } elseif ($city) {
            $joinAndWhere = "JOIN boards.listings_master ON listings_master.city = places.place
                WHERE places.type = 'city' AND places.stats_disabled = 0 AND places.place = '" . addslashes($city) . "'";
        } else {
            $joinAndWhere = "JOIN boards.listings_master ON 1=1 WHERE 1=1";
        }

        $query = "SELECT
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),soldprice_2,NULL))) AS avg_price_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),soldprice_2,NULL))) AS avg_price_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),soldprice_2,NULL))) AS avg_price_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),soldprice_2,NULL))) AS avg_price_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),soldprice_2,NULL))) AS avg_price_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),soldprice_2,NULL))) AS avg_price_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),soldprice_2,NULL))) AS avg_price_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),soldprice_2,NULL))) AS avg_price_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),soldprice_2,NULL))) AS avg_price_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),soldprice_2,NULL))) AS avg_price_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),soldprice_2,NULL))) AS avg_price_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            ROUND(AVG(IF(`status`='sold' AND sold_date >= DATE_FORMAT(CURDATE(),'%Y-%m-01'),soldprice_2,NULL))) AS avg_price_twelve,
            MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
        FROM pixilinkvow.places
        {$joinAndWhere}
            AND {$typeFilter}
            AND `status` = 'sold'
            AND sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH) AND CURDATE()";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ? $results[0] : null;
    }

    public function get_market_summary($city = '', $subarea = '', $listingtype = '')
    {
        $typeFilter = $listingtype
            ? "boards.listings.type = '" . addslashes($listingtype) . "'"
            : "boards.listings.type IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')";

        if ($subarea && $city) {
            $placeFilter = "listings.subarea = '" . addslashes($subarea) . "' AND listings.city = '" . addslashes($city) . "'";
        } elseif ($city) {
            $placeFilter = "listings.city = '" . addslashes($city) . "'";
        } else {
            $placeFilter = "listings.city IS NOT NULL";
        }

        $query = "SELECT
            SUM(`status` = 'active') AS current_active,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), soldprice_2, NULL))) AS avg_sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_30d
        FROM boards.listings
        WHERE {$placeFilter}
            AND {$typeFilter}
            AND `status` IN ('active', 'sold')";

        $results = DB::connection($this->connection_360)->select($query);
        return $results ? $results[0] : null;
    }

    public function get_market_summary_batch($city)
    {
        $city = addslashes($city);
        $query = "SELECT
            listings.subarea,
            SUM(`status` = 'active') AS current_active,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), soldprice_2, NULL))) AS avg_sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_30d
        FROM boards.listings
        INNER JOIN pixilinkvow.places ON places.place = listings.subarea AND places.city = '{$city}' AND places.type = 'subarea' AND places.stats_disabled = 0
        WHERE listings.city = '{$city}'
            AND listings.type IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')
            AND `status` IN ('active', 'sold')
        GROUP BY listings.subarea";

        return DB::connection($this->connection_360)->select($query) ?: [];
    }

    public function get_all_cities_market_summary()
    {
        $query = "SELECT
            listings.city,
            SUM(`status` = 'active') AS current_active,
            SUM(`status` = 'sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) AS sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY), soldprice_2, NULL))) AS avg_sold_30d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), soldprice_2, NULL))) AS avg_sold_90d,
            ROUND(AVG(IF(`status`='sold' AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY), DATEDIFF(sold_date, list_date), NULL))) AS avg_dom_30d
        FROM boards.listings
        INNER JOIN pixilinkvow.places ON places.place = listings.city AND places.type = 'city' AND places.stats_disabled = 0 AND places.stats_subareas_disabled = 0
        WHERE listings.type IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')
            AND `status` IN ('active', 'sold')
        GROUP BY listings.city";

        return DB::connection($this->connection_360)->select($query) ?: [];
    }

    public function get_city_active_sold($interval, $city = "", $listingtype="")
    {
        if ($city) {
            $query = "SELECT
            label as city_name, 
            SUM((list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS listed_by_filter, 
            SUM(`status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) sold_by_filter,
            SUM(`status` = 'active') AS current_active
        FROM pixilinkvow.places
            JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
        WHERE
            places.type = 'subarea' AND places.stats_disabled=0 and places.city='" . $city . "' ";
            if($listingtype !=""){
                $query .= " and boards.listings.type IN('".$listingtype."') ";
            }
            else{
                $query .= " and boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
            }
            $query .= " and
            `status` IN ('active', 'sold')
        GROUP BY label
        ORDER BY label ASC";
        } else {
            $query = "SELECT
            label as city_name, 
            SUM((list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS listed_by_filter, 
            SUM(`status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) sold_by_filter,
            SUM(`status` = 'active') AS current_active
        FROM pixilinkvow.places
            JOIN boards.listings ON listings.city = places.place
        WHERE
            places.type = 'city' AND places.stats_disabled=0 ";
            if($listingtype !=""){
                $query .= " and boards.listings.type IN('".$listingtype."') ";
            }
            else{
                $query .= " and boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
            }
            $query .= " and
            `status` IN ('active', 'sold')
        GROUP BY label
        ORDER BY label ASC";
        }

        $stats =  DB::connection($this->connection_360)
            ->select($query);
        return $stats;
    }

    public function get_type_active_sold($interval, $city = "", $subarea = "")
    {
        if ($city && $subarea) {
            $query = "SELECT
                SUM( boards.listings.type = 'House' AND (list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS house_listed,
                SUM( boards.listings.type = 'House' AND `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS house_sold,
                SUM( boards.listings.type = 'Apartment' AND (list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS apartment_listed,
                SUM( boards.listings.type = 'Apartment' AND `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS apartment_sold,
                SUM( (boards.listings.type = 'Townhouse' || boards.listings.type = 'Duplex' || boards.listings.type = 'Fourplex' || boards.listings.type = 'Triplex') AND (list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS townhouse_listed,
                SUM( (boards.listings.type = 'Townhouse' || boards.listings.type = 'Duplex' || boards.listings.type = 'Fourplex' || boards.listings.type = 'Triplex' ) AND `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS townhouse_sold
            FROM pixilinkvow.places
                JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
            WHERE
                places.type = 'subarea' AND places.stats_disabled=0 and places.city='" . $city . "' and places.place='" . $subarea . "' and
                `status` IN ('active', 'sold')
        ";
        } elseif ($city) {
            $query = "SELECT
            SUM( boards.listings.type = 'House' AND (list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS house_listed,
            SUM( boards.listings.type = 'House' AND `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS house_sold,
            SUM( boards.listings.type = 'Apartment' AND (list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS apartment_listed,
            SUM( boards.listings.type = 'Apartment' AND `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS apartment_sold,
            SUM( (boards.listings.type = 'Townhouse' || boards.listings.type = 'Duplex' || boards.listings.type = 'Fourplex' || boards.listings.type = 'Triplex') AND (list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS townhouse_listed,
            SUM( (boards.listings.type = 'Townhouse' || boards.listings.type = 'Duplex' || boards.listings.type = 'Fourplex' || boards.listings.type = 'Triplex' ) AND `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS townhouse_sold
        FROM pixilinkvow.places
            JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
        WHERE
            places.type = 'subarea' AND places.stats_disabled=0 and places.city='" . $city . "' and
            `status` IN ('active', 'sold')
        ";
        } else {
            $query = "SELECT
            SUM( boards.listings.type = 'House' AND (list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS house_listed,
            SUM( boards.listings.type = 'House' AND `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS house_sold,
            SUM( boards.listings.type = 'Apartment' AND (list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS apartment_listed,
            SUM( boards.listings.type = 'Apartment' AND `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS apartment_sold,
            SUM( (boards.listings.type = 'Townhouse' || boards.listings.type = 'Duplex' || boards.listings.type = 'Fourplex' || boards.listings.type = 'Triplex') AND (list_date <= CURRENT_DATE() AND list_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS townhouse_listed,
            SUM( (boards.listings.type = 'Townhouse' || boards.listings.type = 'Duplex' || boards.listings.type = 'Fourplex' || boards.listings.type = 'Triplex') AND `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS townhouse_sold
        FROM pixilinkvow.places
            JOIN boards.listings ON listings.city = places.place
        WHERE
            places.type = 'city' AND places.stats_disabled=0 and
            `status` IN ('active', 'sold')
        ";
        }

        $stats =  DB::connection($this->connection_360)
            ->select($query);
        return $stats;
    }

    public function get_city_type_sold($interval, $city = "")
    {
        if ($city) {
            $query = "SELECT
            label as city_name, 
            SUM( boards.listings.type = 'House') as house,
            SUM( boards.listings.type = 'Apartment') as apartment,
            SUM( boards.listings.type = 'Townhouse' || boards.listings.type = 'Duplex' || boards.listings.type = 'Fourplex' || boards.listings.type = 'Triplex') as townhouse
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
        WHERE
            places.type = 'subarea' AND places.stats_disabled=0 and places.city='" . $city . "' and
            `status`='Sold'  and sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")
        GROUP BY label
        ORDER BY label ASC";
        } else {
            $query = "SELECT
            label as city_name, 
            SUM( boards.listings.type = 'House') as house,
            SUM( boards.listings.type = 'Apartment') as apartment,
            SUM( boards.listings.type = 'Townhouse' || boards.listings.type = 'Duplex' || boards.listings.type = 'Fourplex' || boards.listings.type = 'Triplex') as townhouse
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.city = places.place
        WHERE
            places.type = 'city' AND places.stats_disabled=0 and
            `status`='Sold'  and sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")
        GROUP BY label
        ORDER BY label ASC";
        }
        $stats =  DB::connection($this->connection_360)
            ->select($query);
        return $stats;
    }

    public function get_type_sold_monthly($city = "", $subarea = "")
    {
        if ($city && $subarea) {
            $query = "SELECT
            (CASE
                        WHEN boards.listings.type IN ('Townhouse','Duplex','Fourplex','Triplex') THEN 'Townhouse'
                        ELSE boards.listings.type
            END) type,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH))) AS sold_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH))) AS sold_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH))) AS sold_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH))) AS sold_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH))) AS sold_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH))) AS sold_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH))) AS sold_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH))) AS sold_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH))) AS sold_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH))) AS sold_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH))) AS sold_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH))) AS sold_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH))) AS sold_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH))) AS sold_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH))) AS sold_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH))) AS sold_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH))) AS sold_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH))) AS sold_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH))) AS sold_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH))) AS sold_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH))) AS sold_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH))) AS sold_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH))) AS sold_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH))) AS sold_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()))) AS sold_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0 AND places.city='" . $city . "' AND places.place='" . $subarea . "' and
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR) AND CURDATE()
            AND boards.listings.type IN('Apartment', 'House','Townhouse' ,'Duplex','Fourplex','Triplex')
        GROUP BY type";
        } elseif ($city) {
            $query = "SELECT
            (CASE
                        WHEN boards.listings.type IN ('Townhouse','Duplex','Fourplex','Triplex') THEN 'Townhouse'
                        ELSE boards.listings.type
            END) type,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH))) AS sold_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH))) AS sold_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH))) AS sold_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH))) AS sold_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH))) AS sold_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH))) AS sold_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH))) AS sold_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH))) AS sold_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH))) AS sold_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH))) AS sold_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH))) AS sold_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH))) AS sold_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH))) AS sold_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH))) AS sold_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH))) AS sold_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH))) AS sold_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH))) AS sold_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH))) AS sold_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH))) AS sold_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH))) AS sold_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH))) AS sold_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH))) AS sold_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH))) AS sold_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH))) AS sold_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()))) AS sold_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0 AND places.city='" . $city . "' AND
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR) AND CURDATE()
            AND boards.listings.type IN('Apartment', 'House','Townhouse' ,'Duplex','Fourplex','Triplex')
        GROUP BY type";
        } else {
            $query = "SELECT
            (CASE
                WHEN boards.listings.type IN ('Townhouse','Duplex','Fourplex','Triplex') THEN 'Townhouse'
                ELSE boards.listings.type
                END) type,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH))) AS sold_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH))) AS sold_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH))) AS sold_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH))) AS sold_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH))) AS sold_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH))) AS sold_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH))) AS sold_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH))) AS sold_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH))) AS sold_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH))) AS sold_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH))) AS sold_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH))) AS sold_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH))) AS sold_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH))) AS sold_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH))) AS sold_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH))) AS sold_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH))) AS sold_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH))) AS sold_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH))) AS sold_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH))) AS sold_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH))) AS sold_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH))) AS sold_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH))) AS sold_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH))) AS sold_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()))) AS sold_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.city = places.place
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'city' AND places.stats_disabled=0 AND
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR) AND CURDATE()
            AND boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex')
        GROUP BY type";
        }
        $stats =  DB::connection($this->connection_360)
            ->select($query);
        return $stats;
    }

    public function get_avg_price_monthly($city = "", $subarea = "")
    {
        if ($city && $subarea) {
            $query = "SELECT
            (CASE
                WHEN boards.listings_master.type IN ('Townhouse','Duplex','Fourplex','Triplex') THEN 'Townhouse'
                ELSE boards.listings_master.type
                END) type,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS year_thirdyear_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS month_thirdyear_twelve,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS year_thirdyear_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS month_thirdyear_eleven,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS year_thirdyear_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS month_thirdyear_ten,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS year_thirdyear_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS month_thirdyear_nine,
          
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS year_thirdyear_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS month_thirdyear_eight,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS year_thirdyear_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS month_thirdyear_seven,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS year_thirdyear_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS month_thirdyear_six,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS year_thirdyear_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS month_thirdyear_five,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS year_thirdyear_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS month_thirdyear_four,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS year_thirdyear_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS month_thirdyear_three,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS year_thirdyear_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS month_thirdyear_two,
                
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS year_thirdyear_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS month_thirdyear_one,



            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),soldprice_2,null))) AS avg_price_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),soldprice_2,null))) AS avg_price_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),soldprice_2,null))) AS avg_price_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),soldprice_2,null))) AS avg_price_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
          
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),soldprice_2,null))) AS avg_price_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),soldprice_2,null))) AS avg_price_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),soldprice_2,null))) AS avg_price_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),soldprice_2,null))) AS avg_price_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),soldprice_2,null))) AS avg_price_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),soldprice_2,null))) AS avg_price_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),soldprice_2,null))) AS avg_price_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
                
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),soldprice_2,null))) AS avg_price_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,


                        
                        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),soldprice_2,null))) AS avg_price_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),soldprice_2,null))) AS avg_price_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),soldprice_2,null))) AS avg_price_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),soldprice_2,null))) AS avg_price_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),soldprice_2,null))) AS avg_price_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),soldprice_2,null))) AS avg_price_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),soldprice_2,null))) AS avg_price_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),soldprice_2,null))) AS avg_price_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),soldprice_2,null))) AS avg_price_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),soldprice_2,null))) AS avg_price_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),soldprice_2,null))) AS avg_price_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),soldprice_2,null))) AS avg_price_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),soldprice_2,null))) AS avg_price_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings_master ON listings_master.subarea = places.place and listings_master.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0  AND places.city='" . $city . "' AND places.place='" . $subarea . "' and
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 3 YEAR) AND CURDATE()
            AND boards.listings_master.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex')
        GROUP BY type";
        } elseif ($city) {
            $query = "SELECT
            (CASE
                WHEN boards.listings_master.type IN ('Townhouse','Duplex','Fourplex','Triplex') THEN 'Townhouse'
                ELSE boards.listings_master.type
                END) type,


            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS year_thirdyear_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS month_thirdyear_twelve,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS year_thirdyear_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS month_thirdyear_eleven,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS year_thirdyear_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS month_thirdyear_ten,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS year_thirdyear_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS month_thirdyear_nine,
          
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS year_thirdyear_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS month_thirdyear_eight,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS year_thirdyear_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS month_thirdyear_seven,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS year_thirdyear_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS month_thirdyear_six,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS year_thirdyear_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS month_thirdyear_five,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS year_thirdyear_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS month_thirdyear_four,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS year_thirdyear_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS month_thirdyear_three,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS year_thirdyear_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS month_thirdyear_two,
                
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS year_thirdyear_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS month_thirdyear_one,


            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),soldprice_2,null))) AS avg_price_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),soldprice_2,null))) AS avg_price_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),soldprice_2,null))) AS avg_price_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),soldprice_2,null))) AS avg_price_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
          
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),soldprice_2,null))) AS avg_price_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),soldprice_2,null))) AS avg_price_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),soldprice_2,null))) AS avg_price_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),soldprice_2,null))) AS avg_price_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),soldprice_2,null))) AS avg_price_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),soldprice_2,null))) AS avg_price_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),soldprice_2,null))) AS avg_price_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
                
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),soldprice_2,null))) AS avg_price_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,


                        
                        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),soldprice_2,null))) AS avg_price_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),soldprice_2,null))) AS avg_price_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),soldprice_2,null))) AS avg_price_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),soldprice_2,null))) AS avg_price_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),soldprice_2,null))) AS avg_price_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),soldprice_2,null))) AS avg_price_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),soldprice_2,null))) AS avg_price_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),soldprice_2,null))) AS avg_price_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),soldprice_2,null))) AS avg_price_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),soldprice_2,null))) AS avg_price_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),soldprice_2,null))) AS avg_price_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),soldprice_2,null))) AS avg_price_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),soldprice_2,null))) AS avg_price_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings_master ON listings_master.subarea = places.place and listings_master.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0  AND places.city='" . $city . "' AND
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 3 YEAR) AND CURDATE()
            AND boards.listings_master.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex')
        GROUP BY type";
        } else {
            $query = "SELECT
            (CASE
                WHEN boards.listings_master.type IN ('Townhouse','Duplex','Fourplex','Triplex') THEN 'Townhouse'
                ELSE boards.listings_master.type
                END) type,


            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS year_thirdyear_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS month_thirdyear_twelve,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS year_thirdyear_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS month_thirdyear_eleven,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS year_thirdyear_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS month_thirdyear_ten,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS year_thirdyear_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS month_thirdyear_nine,
          
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS year_thirdyear_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS month_thirdyear_eight,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS year_thirdyear_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS month_thirdyear_seven,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS year_thirdyear_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS month_thirdyear_six,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS year_thirdyear_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS month_thirdyear_five,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS year_thirdyear_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS month_thirdyear_four,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS year_thirdyear_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS month_thirdyear_three,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS year_thirdyear_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS month_thirdyear_two,
                
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),soldprice_2,null))) AS avg_price_thirdyear_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS year_thirdyear_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS month_thirdyear_one,





            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),soldprice_2,null))) AS avg_price_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),soldprice_2,null))) AS avg_price_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),soldprice_2,null))) AS avg_price_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),soldprice_2,null))) AS avg_price_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
          
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),soldprice_2,null))) AS avg_price_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),soldprice_2,null))) AS avg_price_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),soldprice_2,null))) AS avg_price_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),soldprice_2,null))) AS avg_price_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),soldprice_2,null))) AS avg_price_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),soldprice_2,null))) AS avg_price_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
            
            
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),soldprice_2,null))) AS avg_price_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
                
                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),soldprice_2,null))) AS avg_price_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,


                        
                        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),soldprice_2,null))) AS avg_price_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),soldprice_2,null))) AS avg_price_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),soldprice_2,null))) AS avg_price_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),soldprice_2,null))) AS avg_price_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),soldprice_2,null))) AS avg_price_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),soldprice_2,null))) AS avg_price_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),soldprice_2,null))) AS avg_price_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),soldprice_2,null))) AS avg_price_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),soldprice_2,null))) AS avg_price_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),soldprice_2,null))) AS avg_price_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),soldprice_2,null))) AS avg_price_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),soldprice_2,null))) AS avg_price_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),soldprice_2,null))) AS avg_price_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings_master ON listings_master.city = places.place
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'city' AND places.stats_disabled=0 AND
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 3 YEAR) AND CURDATE()
            AND boards.listings_master.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex')
        GROUP BY type";
        }
        $stats =  DB::connection($this->connection_360)
            ->select($query);
        return $stats;
    }


    public function get_avg_diff_monthly($city = "", $subarea = ""){
        if($city && $subarea){
            $query = "SELECT
        (CASE
            WHEN boards.listings_master.type IN ('Townhouse','Duplex','Fourplex','Triplex') THEN 'Townhouse'
            ELSE boards.listings_master.type
        END) type,
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),listprice_2,null)))
        * 100)
        AS avg_price_thirdyear_twelve,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS year_thirdyear_twelve,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS month_thirdyear_twelve,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),soldprice_2,null)))
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_eleven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS year_thirdyear_eleven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS month_thirdyear_eleven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_ten,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS year_thirdyear_ten,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS month_thirdyear_ten,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_nine,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS year_thirdyear_nine,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS month_thirdyear_nine,
      
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_eight,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS year_thirdyear_eight,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS month_thirdyear_eight,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_seven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS year_thirdyear_seven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS month_thirdyear_seven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_six,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS year_thirdyear_six,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS month_thirdyear_six,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_five,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS year_thirdyear_five,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS month_thirdyear_five,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_four,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS year_thirdyear_four,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS month_thirdyear_four,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_three,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS year_thirdyear_three,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS month_thirdyear_three,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_two,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS year_thirdyear_two,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS month_thirdyear_two,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_one,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS year_thirdyear_one,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS month_thirdyear_one,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_thirteen,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_twelve,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_eleven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_ten,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
            
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_nine,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_eight,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_seven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_six,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_five,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_four,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_three,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_two,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,

        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_one,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_one,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_two,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_three,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_four,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_five,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_six,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_seven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_eight,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_nine,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_ten,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_eleven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),listprice_2,null)))
        *100)
        AS avg_price_twelve,
        YEAR(LAST_DAY(CURDATE())) AS year_twelve,
        MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
        
        FROM pixilinkvow.places
        JOIN boards.listings_master ON listings_master.subarea = places.place and listings_master.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0  AND places.city='" . $city . "' AND places.place='" . $subarea . "' and
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 3 YEAR) AND CURDATE()
            AND boards.listings_master.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex')
        GROUP BY type";
        }
        elseif($city){
            $query = "SELECT
        (CASE
            WHEN boards.listings_master.type IN ('Townhouse','Duplex','Fourplex','Triplex') THEN 'Townhouse'
            ELSE boards.listings_master.type
        END) type,
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),listprice_2,null)))
        * 100)
        AS avg_price_thirdyear_twelve,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS year_thirdyear_twelve,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS month_thirdyear_twelve,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),soldprice_2,null)))
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_eleven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS year_thirdyear_eleven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS month_thirdyear_eleven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_ten,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS year_thirdyear_ten,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS month_thirdyear_ten,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_nine,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS year_thirdyear_nine,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS month_thirdyear_nine,
      
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_eight,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS year_thirdyear_eight,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS month_thirdyear_eight,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_seven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS year_thirdyear_seven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS month_thirdyear_seven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_six,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS year_thirdyear_six,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS month_thirdyear_six,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_five,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS year_thirdyear_five,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS month_thirdyear_five,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_four,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS year_thirdyear_four,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS month_thirdyear_four,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_three,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS year_thirdyear_three,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS month_thirdyear_three,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_two,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS year_thirdyear_two,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS month_thirdyear_two,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_one,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS year_thirdyear_one,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS month_thirdyear_one,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_thirteen,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_twelve,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_eleven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_ten,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
            
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_nine,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_eight,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_seven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_six,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_five,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_four,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_three,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_two,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,

        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_one,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_one,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_two,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_three,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_four,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_five,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_six,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_seven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_eight,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_nine,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_ten,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_eleven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),listprice_2,null)))
        *100)
        AS avg_price_twelve,
        YEAR(LAST_DAY(CURDATE())) AS year_twelve,
        MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
        
        FROM pixilinkvow.places
        JOIN boards.listings_master ON listings_master.subarea = places.place and listings_master.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0  AND places.city='" . $city . "' AND
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 3 YEAR) AND CURDATE()
            AND boards.listings_master.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex')
        GROUP BY type";
        }
        else{
            $query = "SELECT
        (CASE
            WHEN boards.listings_master.type IN ('Townhouse','Duplex','Fourplex','Triplex') THEN 'Townhouse'
            ELSE boards.listings_master.type
        END) type,
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),listprice_2,null)))
        * 100)
        AS avg_price_thirdyear_twelve,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS year_thirdyear_twelve,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS month_thirdyear_twelve,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),soldprice_2,null)))
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_eleven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS year_thirdyear_eleven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS month_thirdyear_eleven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_ten,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS year_thirdyear_ten,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS month_thirdyear_ten,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_nine,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS year_thirdyear_nine,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS month_thirdyear_nine,
      
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_eight,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS year_thirdyear_eight,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS month_thirdyear_eight,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_seven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS year_thirdyear_seven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS month_thirdyear_seven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_six,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS year_thirdyear_six,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS month_thirdyear_six,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_five,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS year_thirdyear_five,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS month_thirdyear_five,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_four,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS year_thirdyear_four,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS month_thirdyear_four,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_three,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS year_thirdyear_three,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS month_thirdyear_three,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_two,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS year_thirdyear_two,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS month_thirdyear_two,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_thirdyear_one,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS year_thirdyear_one,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS month_thirdyear_one,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_thirteen,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_twelve,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_eleven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_ten,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
            
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_nine,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_eight,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_seven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_six,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_five,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_four,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_three,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_two,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,

        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_minus_one,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_one,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_two,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_three,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_four,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_five,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_six,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_seven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_eight,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_nine,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_ten,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),listprice_2,null)))
        *100)
        AS avg_price_eleven,
        YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
        MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
        
        ((
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),soldprice_2,null))) 
        -
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),listprice_2,null)))
        )/
        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),listprice_2,null)))
        *100)
        AS avg_price_twelve,
        YEAR(LAST_DAY(CURDATE())) AS year_twelve,
        MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
        
        FROM pixilinkvow.places
        JOIN boards.listings_master ON listings_master.city = places.place
    WHERE 
        `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
        places.type = 'city' AND places.stats_disabled=0 AND
        `status` = 'sold' AND
        sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 3 YEAR) AND CURDATE()
        AND boards.listings_master.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex')
    GROUP BY type";
        }

        $stats =  DB::connection($this->connection_360)
            ->select($query);
        return $stats;
    }



    public function get_sold_count_monthly($city = "", $subarea = "")
    {
        if ($city && $subarea) {
            $query = "SELECT
            (CASE
                WHEN boards.listings_master.type IN ('Townhouse','Duplex','Fourplex','Triplex') THEN 'Townhouse'
                ELSE boards.listings_master.type
                END) type,

            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),1,0)) AS sold_count_thirdyear_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS year_thirdyear_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS month_thirdyear_twelve,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),1,0)) AS sold_count_thirdyear_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS year_thirdyear_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS month_thirdyear_eleven,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),1,0)) AS sold_count_thirdyear_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS year_thirdyear_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS month_thirdyear_ten,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),1,0)) AS sold_count_thirdyear_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS year_thirdyear_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS month_thirdyear_nine,
          
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),1,0)) AS sold_count_thirdyear_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS year_thirdyear_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS month_thirdyear_eight,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),1,0)) AS sold_count_thirdyear_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS year_thirdyear_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS month_thirdyear_seven,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),1,0)) AS sold_count_thirdyear_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS year_thirdyear_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS month_thirdyear_six,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),1,0)) AS sold_count_thirdyear_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS year_thirdyear_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS month_thirdyear_five,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),1,0)) AS sold_count_thirdyear_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS year_thirdyear_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS month_thirdyear_four,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),1,0)) AS sold_count_thirdyear_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS year_thirdyear_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS month_thirdyear_three,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),1,0)) AS sold_count_thirdyear_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS year_thirdyear_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS month_thirdyear_two,
                
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),1,0)) AS sold_count_thirdyear_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS year_thirdyear_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS month_thirdyear_one,

            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),1,0)) AS sold_count_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),1,0)) AS sold_count_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),1,0)) AS sold_count_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),1,0)) AS sold_count_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
          
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),1,0)) AS sold_count_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),1,0)) AS sold_count_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),1,0)) AS sold_count_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),1,0)) AS sold_count_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),1,0)) AS sold_count_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),1,0)) AS sold_count_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),1,0)) AS sold_count_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
                
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),1,0)) AS sold_count_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,


                        
                        SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),1,0)) AS sold_count_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),1,0)) AS sold_count_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),1,0)) AS sold_count_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),1,0)) AS sold_count_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),1,0)) AS sold_count_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),1,0)) AS sold_count_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),1,0)) AS sold_count_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),1,0)) AS sold_count_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),1,0)) AS sold_count_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),1,0)) AS sold_count_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),1,0)) AS sold_count_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),1,0)) AS sold_count_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),1,0)) AS sold_count_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings_master ON listings_master.subarea = places.place and listings_master.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0  AND places.city='" . $city . "' AND places.place='" . $subarea . "' and
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 3 YEAR) AND CURDATE()
            AND boards.listings_master.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex')
        GROUP BY type";
        } elseif ($city) {
            $query = "SELECT
            (CASE
                WHEN boards.listings_master.type IN ('Townhouse','Duplex','Fourplex','Triplex') THEN 'Townhouse'
                ELSE boards.listings_master.type
                END) type,

            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),1,0)) AS sold_count_thirdyear_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS year_thirdyear_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS month_thirdyear_twelve,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),1,0)) AS sold_count_thirdyear_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS year_thirdyear_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS month_thirdyear_eleven,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),1,0)) AS sold_count_thirdyear_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS year_thirdyear_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS month_thirdyear_ten,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),1,0)) AS sold_count_thirdyear_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS year_thirdyear_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS month_thirdyear_nine,
          
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),1,0)) AS sold_count_thirdyear_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS year_thirdyear_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS month_thirdyear_eight,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),1,0)) AS sold_count_thirdyear_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS year_thirdyear_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS month_thirdyear_seven,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),1,0)) AS sold_count_thirdyear_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS year_thirdyear_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS month_thirdyear_six,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),1,0)) AS sold_count_thirdyear_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS year_thirdyear_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS month_thirdyear_five,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),1,0)) AS sold_count_thirdyear_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS year_thirdyear_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS month_thirdyear_four,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),1,0)) AS sold_count_thirdyear_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS year_thirdyear_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS month_thirdyear_three,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),1,0)) AS sold_count_thirdyear_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS year_thirdyear_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS month_thirdyear_two,
                
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),1,0)) AS sold_count_thirdyear_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS year_thirdyear_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS month_thirdyear_one,

            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),1,0)) AS sold_count_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),1,0)) AS sold_count_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),1,0)) AS sold_count_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),1,0)) AS sold_count_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
          
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),1,0)) AS sold_count_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),1,0)) AS sold_count_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),1,0)) AS sold_count_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),1,0)) AS sold_count_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),1,0)) AS sold_count_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),1,0)) AS sold_count_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),1,0)) AS sold_count_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
                
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),1,0)) AS sold_count_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,


                        
                        SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),1,0)) AS sold_count_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),1,0)) AS sold_count_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),1,0)) AS sold_count_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),1,0)) AS sold_count_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),1,0)) AS sold_count_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),1,0)) AS sold_count_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),1,0)) AS sold_count_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),1,0)) AS sold_count_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),1,0)) AS sold_count_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),1,0)) AS sold_count_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),1,0)) AS sold_count_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),1,0)) AS sold_count_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),1,0)) AS sold_count_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings_master ON listings_master.subarea = places.place and listings_master.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0  AND places.city='" . $city . "' AND
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 3 YEAR) AND CURDATE()
            AND boards.listings_master.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex')
        GROUP BY type";
        } else {
            $query = "SELECT
            (CASE
                WHEN boards.listings_master.type IN ('Townhouse','Duplex','Fourplex','Triplex') THEN 'Townhouse'
                ELSE boards.listings_master.type
                END) type,

            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 36 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 36 MONTH)),1,0)) AS sold_count_thirdyear_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS year_thirdyear_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 36 MONTH)) AS month_thirdyear_twelve,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 35 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 35 MONTH)),1,0)) AS sold_count_thirdyear_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS year_thirdyear_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 35 MONTH)) AS month_thirdyear_eleven,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 34 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 34 MONTH)),1,0)) AS sold_count_thirdyear_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS year_thirdyear_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 34 MONTH)) AS month_thirdyear_ten,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 33 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 33 MONTH)),1,0)) AS sold_count_thirdyear_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS year_thirdyear_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 33 MONTH)) AS month_thirdyear_nine,
          
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 32 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 32 MONTH)),1,0)) AS sold_count_thirdyear_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS year_thirdyear_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 32 MONTH)) AS month_thirdyear_eight,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 31 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 31 MONTH)),1,0)) AS sold_count_thirdyear_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS year_thirdyear_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 31 MONTH)) AS month_thirdyear_seven,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 30 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 30 MONTH)),1,0)) AS sold_count_thirdyear_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS year_thirdyear_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 30 MONTH)) AS month_thirdyear_six,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 29 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 29 MONTH)),1,0)) AS sold_count_thirdyear_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS year_thirdyear_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 29 MONTH)) AS month_thirdyear_five,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 28 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 28 MONTH)),1,0)) AS sold_count_thirdyear_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS year_thirdyear_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 28 MONTH)) AS month_thirdyear_four,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 27 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 27 MONTH)),1,0)) AS sold_count_thirdyear_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS year_thirdyear_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 27 MONTH)) AS month_thirdyear_three,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 26 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 26 MONTH)),1,0)) AS sold_count_thirdyear_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS year_thirdyear_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 26 MONTH)) AS month_thirdyear_two,
                
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 25 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 25 MONTH)),1,0)) AS sold_count_thirdyear_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS year_thirdyear_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 25 MONTH)) AS month_thirdyear_one,

            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),1,0)) AS sold_count_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),1,0)) AS sold_count_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),1,0)) AS sold_count_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),1,0)) AS sold_count_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
          
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),1,0)) AS sold_count_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),1,0)) AS sold_count_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),1,0)) AS sold_count_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),1,0)) AS sold_count_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),1,0)) AS sold_count_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),1,0)) AS sold_count_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
            
            
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),1,0)) AS sold_count_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
                
                SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),1,0)) AS sold_count_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,


                        
                        SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),1,0)) AS sold_count_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),1,0)) AS sold_count_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),1,0)) AS sold_count_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),1,0)) AS sold_count_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),1,0)) AS sold_count_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),1,0)) AS sold_count_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),1,0)) AS sold_count_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),1,0)) AS sold_count_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),1,0)) AS sold_count_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),1,0)) AS sold_count_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),1,0)) AS sold_count_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),1,0)) AS sold_count_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTHNAME(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            SUM(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),1,0)) AS sold_count_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTHNAME(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings_master ON listings_master.city = places.place
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'city' AND places.stats_disabled=0 AND
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 3 YEAR) AND CURDATE()
            AND boards.listings_master.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex')
        GROUP BY type";
        }
        $stats =  DB::connection($this->connection_360)
            ->select($query);
        return $stats;
    }






    public function get_sold_beds($interval, $city = "", $subarea = "", $listingtype="")
    {
        if ($city && $subarea) {
            $query = "SELECT
                SUM( `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS listings_sold,
                bedrooms
                FROM pixilinkvow.places
                JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
                WHERE
                places.type = 'subarea' AND places.stats_disabled=0 and places.city='" . $city . "' and places.place='" . $subarea . "' ";
                if($listingtype !=''){
                    $query .= " AND boards.listings.type IN('".$listingtype."') ";
                }
                else{
                    $query .= " AND boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
                }
                
                $query .= " and 
                `status`='Sold' and bedrooms is not null
                group by bedrooms order by `bedrooms`";

            $stats =  DB::connection($this->connection_360)
                ->select($query);
        } elseif ($city) {
            $query = "SELECT
                SUM( `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS listings_sold,
                bedrooms
                FROM pixilinkvow.places
                JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
                WHERE
                places.type = 'subarea' AND places.stats_disabled=0 and places.city='" . $city . "' ";
                if($listingtype !=''){
                    $query .= " AND boards.listings.type IN('".$listingtype."') ";
                }
                else{
                $query .= " AND boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
                }
                $query .= " and 
                `status`='Sold' and bedrooms is not null
                group by bedrooms order by `bedrooms`";

            $stats =  DB::connection($this->connection_360)
                ->select($query);
        } else {
            $query = "SELECT
                SUM( `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . "))) AS listings_sold,
                bedrooms
                FROM pixilinkvow.places
                JOIN boards.listings ON listings.city = places.place
                WHERE
                places.type = 'city' AND places.stats_disabled=0 ";
                if($listingtype !=''){
                    $query .= " AND boards.listings.type IN('".$listingtype."') ";
                }
                else{
                $query .= " and boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
                }
                $query .= " and 
                `status`='Sold' and bedrooms is not null
                group by bedrooms order by `bedrooms`";

            $stats =  DB::connection($this->connection_360)
                ->select($query);
        }
        return $stats;
    }

    public function get_three_year_sold($city = "", $listingtype = "")
    {
        if ($city) {

            $query = "SELECT
            label as city_name, 
            SUM(`status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(NOW(), INTERVAL 1 YEAR))) current_12_months_sold,
            CONCAT(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%d %b, %Y'), ' to ', DATE_FORMAT(CURRENT_DATE(), '%d %b, %Y')) as current_12_months,
            SUM(`status` = 'sold' AND (sold_date <= DATE_SUB(NOW(), INTERVAL 1 YEAR)  AND sold_date > DATE_SUB(NOW(), INTERVAL 2 YEAR))) last_12_months_sold,
            CONCAT(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 YEAR), '%d %b, %Y'), ' to ', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%d %b, %Y')) as last_12_months
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
            WHERE
            places.type = 'subarea' AND places.stats_disabled=0 and places.city='" . $city . "' ";
            if($listingtype != ""){
                $query .= " and boards.listings.type IN('".$listingtype."') ";
            }
            else{
                $query .= " and boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
            }
            $query .= "and
            `status` = 'Sold'
            GROUP BY label
            ORDER BY label ASC";
        } else {

            $query = "SELECT
            label as city_name, 
            SUM(`status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(NOW(), INTERVAL 1 YEAR))) current_12_months_sold,
            CONCAT(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%d %b, %Y'), ' to ', DATE_FORMAT(CURRENT_DATE(), '%d %b, %Y')) as current_12_months,
            SUM(`status` = 'sold' AND (sold_date <= DATE_SUB(NOW(), INTERVAL 1 YEAR)  AND sold_date > DATE_SUB(NOW(), INTERVAL 2 YEAR))) last_12_months_sold,
            CONCAT(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 YEAR), '%d %b, %Y'), ' to ', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%d %b, %Y')) as last_12_months
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.city = places.place
            WHERE
            places.type = 'city' AND places.stats_disabled=0 ";
            if($listingtype != ""){
                $query .= " and boards.listings.type IN('".$listingtype."') ";
            }
            else{
            $query .= " and boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
            }
            $query .= " and
            `status` = 'Sold'
            GROUP BY label
            ORDER BY label ASC";
        }

        $stats =  DB::connection($this->connection_360)
            ->select($query);
        return $stats;
    }

    public function get_sold_price_range($interval, $city = "", $subarea = "", $listingtype = "")
    {
        if ($city && $subarea) {
            $query = "select 
            case 
              when soldprice_2 between 0 and 250000 then 'A_0-250,000'
              when soldprice_2 between 250000 and 500000 then 'B_250,000-500,000'
              when soldprice_2 between 500000 and 750000 then 'C_500,000-750,000'
              when soldprice_2 between 750000 and 1000000 then 'D_750,000-1,000,000'
              when soldprice_2 between 1000000 and 1500000 then 'E_1,000,000-1,500,000'
              when soldprice_2 between 1500000 and 2000000 then 'F_1,500,000-2,000,000'
              when soldprice_2 between 2000000 and 2500000 then 'G_2,000,000-2,500,000'
              when soldprice_2 between 2500000 and 3000000 then 'H_2,500,000-3,000,000'
              when soldprice_2 between 3000000 and 4000000 then 'I_3,000,000-4,000,000'
              when soldprice_2 between 4000000 and 5000000 then 'J_4,000,000-5,000,000'
              when soldprice_2 between 5000000 and 6000000 then 'K_5,000,000-6,000,000'
              when soldprice_2 between 6000000 and 7000000 then 'L_6,000,000-7,000,000'
              else 'M_7,000,000+'
            end as `Range`,
            count(id) as `Count`
          from boards.listings where `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")) ";
          if($listingtype != ''){
              $query .= " and type IN('".$listingtype."') ";
          }
          else{
              $query .= " and type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
          }
          $query .= "and city = '" . $city . "' and subarea = '" . $subarea . "'
          group by `Range` order by `Range` asc";
        } elseif ($city) {
            $query = "select 
            case 
              when soldprice_2 between 0 and 250000 then 'A_0-250,000'
              when soldprice_2 between 250000 and 500000 then 'B_250,000-500,000'
              when soldprice_2 between 500000 and 750000 then 'C_500,000-750,000'
              when soldprice_2 between 750000 and 1000000 then 'D_750,000-1,000,000'
              when soldprice_2 between 1000000 and 1500000 then 'E_1,000,000-1,500,000'
              when soldprice_2 between 1500000 and 2000000 then 'F_1,500,000-2,000,000'
              when soldprice_2 between 2000000 and 2500000 then 'G_2,000,000-2,500,000'
              when soldprice_2 between 2500000 and 3000000 then 'H_2,500,000-3,000,000'
              when soldprice_2 between 3000000 and 4000000 then 'I_3,000,000-4,000,000'
              when soldprice_2 between 4000000 and 5000000 then 'J_4,000,000-5,000,000'
              when soldprice_2 between 5000000 and 6000000 then 'K_5,000,000-6,000,000'
              when soldprice_2 between 6000000 and 7000000 then 'L_6,000,000-7,000,000'
              else 'M_7,000,000+'
            end as `Range`,
            count(id) as `Count`
          from boards.listings where `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")) ";
          if($listingtype != ''){
              $query .= " and type IN('".$listingtype."') ";
          }
          else{
            $query .=" and type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
          }
          $query .=" and city = '" . $city . "' and subarea IN (select place from pixilinkvow.places where type='subarea' and stats_disabled = 0 and city='" . $city . "')
          group by `Range` order by `Range` asc";
        } else {
            $query = "select 
        case 
          when soldprice_2 between 0 and 250000 then 'A_0-250,000'
          when soldprice_2 between 250000 and 500000 then 'B_250,000-500,000'
          when soldprice_2 between 500000 and 750000 then 'C_500,000-750,000'
          when soldprice_2 between 750000 and 1000000 then 'D_750,000-1,000,000'
          when soldprice_2 between 1000000 and 1500000 then 'E_1,000,000-1,500,000'
          when soldprice_2 between 1500000 and 2000000 then 'F_1,500,000-2,000,000'
          when soldprice_2 between 2000000 and 2500000 then 'G_2,000,000-2,500,000'
          when soldprice_2 between 2500000 and 3000000 then 'H_2,500,000-3,000,000'
          when soldprice_2 between 3000000 and 4000000 then 'I_3,000,000-4,000,000'
          when soldprice_2 between 4000000 and 5000000 then 'J_4,000,000-5,000,000'
          when soldprice_2 between 5000000 and 6000000 then 'K_5,000,000-6,000,000'
          when soldprice_2 between 6000000 and 7000000 then 'L_6,000,000-7,000,000'
          else 'M_7,000,000+'
        end as `Range`,
        count(id) as `Count`
      from boards.listings where `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")) ";
      if($listingtype != ''){
            $query .= " and type IN('".$listingtype."') ";
        }
        else{
            $query .= " and type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
      }
      $query .= " and city IN (select place from pixilinkvow.places where type='city' and stats_disabled = 0)
      group by `Range` order by `Range` asc";
        }

        $stats =  DB::connection($this->connection_360)
            ->select($query);
        if (count($stats) > 0) {
            foreach ($stats as $stat) {
                $stat->Range = substr($stat->Range, 2);
            }
        }
        return $stats;
    }

    public function get_property_age_stats($interval, $city = "", $subarea = "", $listingtype="")
    {
        if ($city && $subarea) {
            $query = "select 
            case 
              when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 1 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(CURRENT_DATE()), UNSIGNED INTEGER) then 'A_0-1'
              when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 5 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 2 YEAR)), UNSIGNED INTEGER) then 'B_2-5'
              when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 10 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 6 YEAR)), UNSIGNED INTEGER) then 'C_6-10'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 15 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 11 YEAR)), UNSIGNED INTEGER) then 'D_11-15'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 20 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 16 YEAR)), UNSIGNED INTEGER) then 'E_16-20'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 30 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 21 YEAR)), UNSIGNED INTEGER) then 'F_21-30'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 40 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 31 YEAR)), UNSIGNED INTEGER) then 'F_31-40'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 50 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 41 YEAR)), UNSIGNED INTEGER) then 'F_41-50'
             else 'G_50+'
            end as `Range`,
            count(id) as `Count`
          from boards.listings where `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")) ";
          if($listingtype != ''){
              $query .= " and type IN('".$listingtype."') ";
          }
          else{
              $query .= " and type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
          }
          
          $query .= " and city = '" . $city . "' and subarea = '" . $subarea . "'
          group by `Range` order by `Range` asc";
        } elseif ($city) {
            $query = "select 
            case 
              when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 1 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(CURRENT_DATE()), UNSIGNED INTEGER) then 'A_0-1'
              when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 5 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 2 YEAR)), UNSIGNED INTEGER) then 'B_2-5'
              when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 10 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 6 YEAR)), UNSIGNED INTEGER) then 'C_6-10'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 15 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 11 YEAR)), UNSIGNED INTEGER) then 'D_11-15'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 20 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 16 YEAR)), UNSIGNED INTEGER) then 'E_16-20'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 30 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 21 YEAR)), UNSIGNED INTEGER) then 'F_21-30'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 40 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 31 YEAR)), UNSIGNED INTEGER) then 'F_31-40'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 50 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 41 YEAR)), UNSIGNED INTEGER) then 'F_41-50'
             else 'G_50+'
            end as `Range`,
            count(id) as `Count`
          from boards.listings where `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")) ";
          if($listingtype != ''){
              $query .= " and type IN('".$listingtype."') ";
          }
          else{
          $query .= " and type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
          }
          $query .= " and city = '" . $city . "' and subarea IN (select place from pixilinkvow.places where type='subarea' and stats_disabled = 0 and city='" . $city . "')
          group by `Range` order by `Range` asc";
        } else {
            $query = "select 
            case 
              when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 1 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(CURRENT_DATE()), UNSIGNED INTEGER) then 'A_0-1'
              when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 5 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 2 YEAR)), UNSIGNED INTEGER) then 'B_2-5'
              when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 10 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 6 YEAR)), UNSIGNED INTEGER) then 'C_6-10'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 15 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 11 YEAR)), UNSIGNED INTEGER) then 'D_11-15'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 20 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 16 YEAR)), UNSIGNED INTEGER) then 'E_16-20'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 30 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 21 YEAR)), UNSIGNED INTEGER) then 'F_21-30'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 40 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 31 YEAR)), UNSIGNED INTEGER) then 'F_31-40'
             when yearbuilt between CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 50 YEAR)), UNSIGNED INTEGER) and CONVERT(YEAR(DATE_SUB(NOW(), INTERVAL 41 YEAR)), UNSIGNED INTEGER) then 'F_41-50'
             else 'G_50+'
            end as `Range`,
            count(id) as `Count`
          from boards.listings where `status` = 'sold' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")) ";
          if($listingtype != ''){
              $query .= " and type IN('".$listingtype."') ";
          }
          else{
          $query .= " and type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
          }
          $query .= " and city IN (select place from pixilinkvow.places where type='city' and stats_disabled = 0)
          group by `Range` order by `Range` asc";
        }
        $stats =  DB::connection($this->connection_360)
            ->select($query);
        if (count($stats) > 0) {
            foreach ($stats as $stat) {
                $stat->Range = substr($stat->Range, 2);
            }
        }
        return $stats;
    }

    public function get_avg_days_on_market_stat($interval, $city = "")
    {
        if ($city) {
            $query = "SELECT
            label as city_name,
                ROUND(AVG(IF(listings.`type`='House' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")),DATEDIFF(sold_date,list_date),null))) AS avg_dom_house,
                ROUND(AVG(IF((listings.`type`='Townhouse' || listings.type = 'Duplex' || listings.type = 'Fourplex' || listings.type = 'Triplex' ) AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")),DATEDIFF(sold_date,list_date),null))) AS avg_dom_townhouse,
                ROUND(AVG(IF(listings.`type`='Apartment' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")),DATEDIFF(sold_date,list_date),null))) AS avg_dom_apartment

            FROM pixilinkvow.places
                JOIN boards.listings ON listings.subarea = places.place and listings.city='" . $city . "'
            WHERE
                places.type = 'subarea' AND places.stats_disabled=0 and places.city = '" . $city . "' and
                `status`='Sold'
            GROUP BY label
            ORDER BY `label` ASC";
        } else {
            $query = "SELECT
            label as city_name,
                ROUND(AVG(IF(listings.`type`='House' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")),DATEDIFF(sold_date,list_date),null))) AS avg_dom_house,
                ROUND(AVG(IF((listings.`type`='Townhouse' ||listings.type = 'Duplex' || listings.type = 'Fourplex' || listings.type = 'Triplex' ) AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")),DATEDIFF(sold_date,list_date),null))) AS avg_dom_townhouse,
                ROUND(AVG(IF(listings.`type`='Apartment' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")),DATEDIFF(sold_date,list_date),null))) AS avg_dom_apartment
            FROM pixilinkvow.places
                JOIN boards.listings ON listings.city = places.place
            WHERE
                places.type = 'city' AND places.stats_disabled=0 and
                `status`='Sold'
            GROUP BY label
            ORDER BY `label` ASC";
        }
        $stats =  DB::connection($this->connection_360)
            ->select($query);
        if (count($stats) > 0) {
            foreach ($stats as $stat) {
                if (!$stat->avg_dom_house) {
                    $stat->avg_dom_house = 0;
                }
                if (!$stat->avg_dom_townhouse) {
                    $stat->avg_dom_townhouse = 0;
                }
                if (!$stat->avg_dom_apartment) {
                    $stat->avg_dom_apartment = 0;
                }
            }
        }
        return $stats;
    }

    public function get_subarea_beds_sold_stats($city, $subarea, $type)
    {
        $query = "";
        if ($type == 'House') {
            $query = "SELECT 
            boards.listings.bedrooms,
            Current_date() as today,
            Date_sub(Current_date(), interval 1 month) current_month_start,
            Round(Avg(IF(sold_date <= Current_date() 
                                AND sold_date > Date_sub(Current_date(), interval 1 month), 
                                    soldprice_2, 
                                          NULL 
                             ))) AS current_month_sold, 
            Round(Avg(IF(sold_date <= Date_sub(Current_date(), interval 2 month)
                                AND sold_date > Date_sub(Current_date(), interval 3 month), 
                                    soldprice_2, 
                                          NULL 
                             ))) AS threemonthsago,
            Round(Avg(IF(sold_date <= Date_sub(Current_date(), interval 5 month)
                                AND sold_date > Date_sub(Current_date(), interval 6 month), 
                                    soldprice_2, 
                                          NULL 
                             ))) AS sixmonthsago,
             Round(Avg(IF(sold_date <= Date_sub(Current_date(), interval 11 month)
                                AND sold_date > Date_sub(Current_date(), interval 12 month), 
                                    soldprice_2, 
                                          NULL 
                             ))) AS yearago               
            FROM   pixilinkvow.places 
                   join boards.listings 
                     ON listings.subarea = places.place 
                        AND listings.city = '" . $city . "' 
            WHERE  places.TYPE = 'subarea' 
                   AND places.stats_disabled = 0 
                   AND places.city = '" . $city . "' 
                   AND places.place = '" . $subarea . "' 
                   AND boards.listings.TYPE = 'House'
                   AND boards.listings.status = 'Sold' 
                   AND boards.listings.bedrooms IS NOT NULL
                   AND (
                    (sold_date <= Current_date() AND sold_date > Date_sub(Current_date(), interval 1 month)) OR
                    (sold_date <= Date_sub(Current_date(), interval 2 month) AND sold_date > Date_sub(Current_date(), interval 3 month)) OR
                    (sold_date <= Date_sub(Current_date(), interval 5 month) AND sold_date > Date_sub(Current_date(), interval 6 month)) OR
                    (sold_date <= Date_sub(Current_date(), interval 11 month) AND sold_date > Date_sub(Current_date(), interval 12 month))
            )
            GROUP  BY boards.listings.bedrooms 
            ORDER  BY boards.listings.bedrooms";
        } elseif ($type == 'Townhouse') {
            $query = "SELECT 
            boards.listings.bedrooms,
            Current_date() as today,
            Date_sub(Current_date(), interval 1 month) current_month_start,
            Round(Avg(IF(sold_date <= Current_date() 
                                AND sold_date > Date_sub(Current_date(), interval 1 month), 
                                    soldprice_2, 
                                          NULL 
                             ))) AS current_month_sold, 
            Round(Avg(IF(sold_date <= Date_sub(Current_date(), interval 2 month)
                                AND sold_date > Date_sub(Current_date(), interval 3 month), 
                                    soldprice_2, 
                                          NULL 
                             ))) AS threemonthsago,
            Round(Avg(IF(sold_date <= Date_sub(Current_date(), interval 5 month)
                                AND sold_date > Date_sub(Current_date(), interval 6 month), 
                                    soldprice_2, 
                                          NULL 
                             ))) AS sixmonthsago,
             Round(Avg(IF(sold_date <= Date_sub(Current_date(), interval 11 month)
                                AND sold_date > Date_sub(Current_date(), interval 12 month), 
                                    soldprice_2, 
                                          NULL 
                             ))) AS yearago               
            FROM   pixilinkvow.places 
                   join boards.listings 
                     ON listings.subarea = places.place 
                        AND listings.city = '" . $city . "' 
            WHERE  places.TYPE = 'subarea' 
                   AND places.stats_disabled = 0 
                   AND places.city = '" . $city . "' 
                   AND places.place = '" . $subarea . "' 
                   AND boards.listings.TYPE IN ('Townhouse', 'Duplex', 
                                    'Fourplex', 'Triplex')
                   AND boards.listings.status = 'Sold' 
                   AND boards.listings.bedrooms IS NOT NULL
                   AND (
                    (sold_date <= Current_date() AND sold_date > Date_sub(Current_date(), interval 1 month)) OR
                    (sold_date <= Date_sub(Current_date(), interval 2 month) AND sold_date > Date_sub(Current_date(), interval 3 month)) OR
                    (sold_date <= Date_sub(Current_date(), interval 5 month) AND sold_date > Date_sub(Current_date(), interval 6 month)) OR
                    (sold_date <= Date_sub(Current_date(), interval 11 month) AND sold_date > Date_sub(Current_date(), interval 12 month))
            )
            GROUP  BY boards.listings.bedrooms 
            ORDER  BY boards.listings.bedrooms";
        } elseif ($type == 'Apartment') {
            $query = "SELECT 
            boards.listings.bedrooms,
            Current_date() as today,
            Date_sub(Current_date(), interval 1 month) current_month_start,
            Round(Avg(IF(sold_date <= Current_date() 
                                AND sold_date > Date_sub(Current_date(), interval 1 month), 
                                    soldprice_2, 
                                          NULL 
                             ))) AS current_month_sold, 
            Round(Avg(IF(sold_date <= Date_sub(Current_date(), interval 2 month)
                                AND sold_date > Date_sub(Current_date(), interval 3 month), 
                                    soldprice_2, 
                                          NULL 
                             ))) AS threemonthsago,
            Round(Avg(IF(sold_date <= Date_sub(Current_date(), interval 5 month)
                                AND sold_date > Date_sub(Current_date(), interval 6 month), 
                                    soldprice_2, 
                                          NULL 
                             ))) AS sixmonthsago,
             Round(Avg(IF(sold_date <= Date_sub(Current_date(), interval 11 month)
                                AND sold_date > Date_sub(Current_date(), interval 12 month), 
                                    soldprice_2, 
                                          NULL 
                             ))) AS yearago               
            FROM   pixilinkvow.places 
                   join boards.listings 
                     ON listings.subarea = places.place 
                        AND listings.city = '" . $city . "' 
            WHERE  places.TYPE = 'subarea' 
                   AND places.stats_disabled = 0 
                   AND places.city = '" . $city . "' 
                   AND places.place = '" . $subarea . "' 
                   AND boards.listings.TYPE = 'Apartment'
                   AND boards.listings.status = 'Sold' 
                   AND boards.listings.bedrooms IS NOT NULL
                   AND (
                    (sold_date <= Current_date() AND sold_date > Date_sub(Current_date(), interval 1 month)) OR
                    (sold_date <= Date_sub(Current_date(), interval 2 month) AND sold_date > Date_sub(Current_date(), interval 3 month)) OR
                    (sold_date <= Date_sub(Current_date(), interval 5 month) AND sold_date > Date_sub(Current_date(), interval 6 month)) OR
                    (sold_date <= Date_sub(Current_date(), interval 11 month) AND sold_date > Date_sub(Current_date(), interval 12 month))
                )
            GROUP  BY boards.listings.bedrooms 
            ORDER  BY boards.listings.bedrooms";
        }
        if ($query) {
            $stats =  DB::connection($this->connection_360)
                ->select($query);
            if (count($stats) > 0) {
                foreach ($stats as $stat) {
                    if ($stat->current_month_sold || $stat->threemonthsago  || $stat->sixmonthsago   || $stat->yearago) {
                        if ($stat->current_month_sold && $stat->current_month_sold > 0) {
                            $stat->current_month_sold = $this->number_shorten($stat->current_month_sold);
                        } else {
                            $stat->current_month_sold = 'n/a';
                        }
                        if ($stat->threemonthsago && $stat->threemonthsago > 0) {
                            $stat->threemonthsago = $this->number_shorten($stat->threemonthsago);
                        } else {
                            $stat->threemonthsago = 'n/a';
                        }
                        if ($stat->sixmonthsago && $stat->sixmonthsago > 0) {
                            $stat->sixmonthsago = $this->number_shorten($stat->sixmonthsago);
                        } else {
                            $stat->sixmonthsago = 'n/a';
                        }
                        if ($stat->yearago && $stat->yearago > 0) {
                            $stat->yearago = $this->number_shorten($stat->yearago);
                        } else {
                            $stat->yearago = 'n/a';
                        }
                    }
                    unset($stat);
                }
            }
            return $stats;
        } else {
            return null;
        }
    }

    public function get_city_stats_yearly($city, $subarea = null, $listingtype = "")
    {
        if ($city && $subarea) {
            $query = "SELECT
            listings.subarea as area,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH))) AS result_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
      
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH))) AS result_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH))) AS result_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH))) AS result_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH))) AS result_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH))) AS result_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH))) AS result_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
             SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH))) AS result_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH))) AS result_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH))) AS result_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,

            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH))) AS result_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH))) AS result_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH))) AS result_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH))) AS result_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH))) AS result_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH))) AS result_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH))) AS result_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH))) AS result_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH))) AS result_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH))) AS result_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH))) AS result_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH))) AS result_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH))) AS result_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH))) AS result_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()))) AS result_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTH(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0 AND places.city='" . $city . "' AND places.place='" . $subarea . "' and
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR) AND CURDATE() ";
            if($listingtype != ""){
                $query .= " AND boards.listings.type IN('".$listingtype."') ";
            }
            else{
                $query .= " AND boards.listings.type IN('Apartment', 'House','Townhouse' ,'Duplex','Fourplex','Triplex')";
            }
            
        } elseif ($city) {
            $query = "SELECT
            
            listings.subarea as area,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH))) AS result_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
      
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH))) AS result_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH))) AS result_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH))) AS result_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH))) AS result_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH))) AS result_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH))) AS result_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
             SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH))) AS result_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH))) AS result_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH))) AS result_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,

            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH))) AS result_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH))) AS result_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH))) AS result_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH))) AS result_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH))) AS result_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH))) AS result_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH))) AS result_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH))) AS result_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH))) AS result_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH))) AS result_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH))) AS result_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH))) AS result_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH))) AS result_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH))) AS result_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()))) AS result_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTH(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0 AND places.city='" . $city . "' AND
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR) AND CURDATE() ";
            if($listingtype != ""){
                $query .= " AND boards.listings.type IN('".$listingtype."') ";
            }
            else{
                $query .= " AND boards.listings.type IN('Apartment', 'House','Townhouse' ,'Duplex','Fourplex','Triplex') ";
            }
            $query .= " group by listings.subarea";
        } else {
            $query = "SELECT
            listings.city as area,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH))) AS result_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
      
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH))) AS result_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH))) AS result_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH))) AS result_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH))) AS result_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH))) AS result_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
              SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH))) AS result_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
             SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH))) AS result_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH))) AS result_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH))) AS result_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,

            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH))) AS result_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
            
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH))) AS result_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH))) AS result_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH))) AS result_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH))) AS result_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH))) AS result_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH))) AS result_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH))) AS result_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH))) AS result_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH))) AS result_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH))) AS result_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH))) AS result_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH))) AS result_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH))) AS result_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            SUM(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE()))) AS result_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTH(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.city = places.place
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'city' AND places.stats_disabled=0 AND
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR) AND CURDATE() ";
            if($listingtype != ""){
                $query .= " AND boards.listings.type IN('".$listingtype."') ";
            }
            else{
             $query .= " AND boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
            }
            $query .= " group by listings.city
            ";
        }
        $stats =  DB::connection($this->connection_360)
            ->select($query);
            // echo "<pre>";
            // print_r($stats);
            // exit;
        return $stats;
    }

    function get_city_stats_yearly_price($city, $subarea, $listingtype = "")
    {
        if ($city && $subarea) {
            $query = "SELECT
           listings.subarea as area,

           ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),soldprice_2,null))) AS result_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),soldprice_2,null))) AS result_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),soldprice_2,null))) AS result_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),soldprice_2,null))) AS result_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),soldprice_2,null))) AS result_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),soldprice_2,null))) AS result_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),soldprice_2,null))) AS result_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),soldprice_2,null))) AS result_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),soldprice_2,null))) AS result_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),soldprice_2,null))) AS result_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),soldprice_2,null))) AS result_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),soldprice_2,null))) AS result_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,
                        
                        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),soldprice_2,null))) AS result_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),soldprice_2,null))) AS result_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),soldprice_2,null))) AS result_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),soldprice_2,null))) AS result_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),soldprice_2,null))) AS result_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),soldprice_2,null))) AS result_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),soldprice_2,null))) AS result_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),soldprice_2,null))) AS result_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),soldprice_2,null))) AS result_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),soldprice_2,null))) AS result_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),soldprice_2,null))) AS result_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),soldprice_2,null))) AS result_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),soldprice_2,null))) AS result_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTH(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0  AND places.city='" . $city . "' AND places.place='" . $subarea . "' and
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR) AND CURDATE() ";
            if($listingtype != ""){
                $query .= " AND boards.listings.type IN('".$listingtype."') ";
            }
            else{
            $query .= " AND boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
            }
        } elseif ($city) {
            $query = "SELECT
            listings.subarea as area,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),soldprice_2,null))) AS result_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),soldprice_2,null))) AS result_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),soldprice_2,null))) AS result_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),soldprice_2,null))) AS result_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),soldprice_2,null))) AS result_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),soldprice_2,null))) AS result_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),soldprice_2,null))) AS result_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),soldprice_2,null))) AS result_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),soldprice_2,null))) AS result_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),soldprice_2,null))) AS result_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),soldprice_2,null))) AS result_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),soldprice_2,null))) AS result_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,
                        
                        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),soldprice_2,null))) AS result_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),soldprice_2,null))) AS result_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),soldprice_2,null))) AS result_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),soldprice_2,null))) AS result_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),soldprice_2,null))) AS result_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),soldprice_2,null))) AS result_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),soldprice_2,null))) AS result_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),soldprice_2,null))) AS result_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),soldprice_2,null))) AS result_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),soldprice_2,null))) AS result_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),soldprice_2,null))) AS result_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),soldprice_2,null))) AS result_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),soldprice_2,null))) AS result_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTH(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0  AND places.city='" . $city . "' AND
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR) AND CURDATE() ";
            if($listingtype != ""){
                $query .= " AND boards.listings.type IN('".$listingtype."') ";
            }
            else{
            $query .= " AND boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
            }
            $query .= " group by listings.subarea
        ";
        } else {
            $query = "SELECT
            listings.city as area,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),soldprice_2,null))) AS result_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),soldprice_2,null))) AS result_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),soldprice_2,null))) AS result_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),soldprice_2,null))) AS result_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),soldprice_2,null))) AS result_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),soldprice_2,null))) AS result_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),soldprice_2,null))) AS result_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),soldprice_2,null))) AS result_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),soldprice_2,null))) AS result_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),soldprice_2,null))) AS result_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),soldprice_2,null))) AS result_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,
            
                                ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),soldprice_2,null))) AS result_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,
                        
                        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),soldprice_2,null))) AS result_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),soldprice_2,null))) AS result_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),soldprice_2,null))) AS result_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),soldprice_2,null))) AS result_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),soldprice_2,null))) AS result_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),soldprice_2,null))) AS result_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),soldprice_2,null))) AS result_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),soldprice_2,null))) AS result_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),soldprice_2,null))) AS result_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),soldprice_2,null))) AS result_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),soldprice_2,null))) AS result_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),soldprice_2,null))) AS result_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),soldprice_2,null))) AS result_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTH(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.city = places.place
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'city' AND places.stats_disabled=0 AND
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR) AND CURDATE() ";
            if($listingtype != ""){
                $query .= " AND boards.listings.type IN('".$listingtype."') ";
            }
            else{
             $query .= " AND boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
            }
            $query .= " group by listings.city
        ";
        }
        $stats =  DB::connection($this->connection_360)
            ->select($query);
        foreach ($stats as $stat) {

            $stat->result_minus_one = $stat->result_minus_one ? number_format((int)$stat->result_minus_one) : 'N/A';
            $stat->result_minus_two = $stat->result_minus_two ? number_format((int)$stat->result_minus_two) : 'N/A';
            $stat->result_minus_three = $stat->result_minus_three ? number_format((int)$stat->result_minus_three) : 'N/A';
            $stat->result_minus_four = $stat->result_minus_four ? number_format((int)$stat->result_minus_four) : 'N/A';
            $stat->result_minus_five = $stat->result_minus_five ? number_format((int)$stat->result_minus_five) : 'N/A';
            $stat->result_minus_six = $stat->result_minus_six ? number_format((int)$stat->result_minus_six) : 'N/A';
            $stat->result_minus_seven = $stat->result_minus_seven ? number_format((int)$stat->result_minus_seven) : 'N/A';
            $stat->result_minus_eight = $stat->result_minus_eight ? number_format((int)$stat->result_minus_eight) : 'N/A';
            $stat->result_minus_nine = $stat->result_minus_nine ? number_format((int)$stat->result_minus_nine) : 'N/A';
            $stat->result_minus_ten = $stat->result_minus_ten ? number_format((int)$stat->result_minus_ten) : 'N/A';
            $stat->result_minus_eleven = $stat->result_minus_eleven ? number_format((int)$stat->result_minus_eleven) : 'N/A';
            $stat->result_minus_twelve = $stat->result_minus_twelve ? number_format((int)$stat->result_minus_twelve) : 'N/A';
            

            $stat->result_one = $stat->result_one ? number_format((int)$stat->result_one) : 'N/A';
            $stat->result_two = $stat->result_two ? number_format((int)$stat->result_two) : 'N/A';
            $stat->result_three = $stat->result_three ? number_format((int)$stat->result_three) : 'N/A';
            $stat->result_four = $stat->result_four ? number_format((int)$stat->result_four) : 'N/A';
            $stat->result_five = $stat->result_five ? number_format((int)$stat->result_five) : 'N/A';
            $stat->result_six = $stat->result_six ? number_format((int)$stat->result_six) : 'N/A';
            $stat->result_seven = $stat->result_seven ? number_format((int)$stat->result_seven) : 'N/A';
            $stat->result_eight = $stat->result_eight ? number_format((int)$stat->result_eight) : 'N/A';
            $stat->result_nine = $stat->result_nine ? number_format((int)$stat->result_nine) : 'N/A';
            $stat->result_ten = $stat->result_ten ? number_format((int)$stat->result_ten) : 'N/A';
            $stat->result_eleven = $stat->result_eleven ? number_format((int)$stat->result_eleven) : 'N/A';
            $stat->result_twelve = $stat->result_twelve ? number_format((int)$stat->result_twelve) : 'N/A';
        }
        return $stats;
    }

    function get_city_stats_yearly_dom($city, $subarea, $listingtype = "")
    {
        //ROUND(AVG(IF(listings.`type`='House' AND (sold_date <= CURRENT_DATE() AND sold_date > DATE_SUB(CURRENT_DATE(), INTERVAL " . $interval . ")),DATEDIFF(sold_date,list_date),null))) AS avg_dom_house,
        if ($city && $subarea) {
            $query = "SELECT
           listings.subarea as area,

           ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,
                        
                        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),DATEDIFF(sold_date,list_date),null))) AS result_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTH(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0  AND places.city='" . $city . "' AND places.place='" . $subarea . "' and
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR) AND CURDATE() ";
            if($listingtype != ""){
                $query .= " AND boards.listings.type IN('".$listingtype."')";
            }
            else{
                $query .= " AND boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex')";
            }
            
        } elseif ($city) {
            $query = "SELECT
            listings.subarea as area,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,
                        
                        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),DATEDIFF(sold_date,list_date),null))) AS result_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTH(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.subarea = places.place and listings.city = '" . $city . "'
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'subarea' AND places.stats_disabled=0  AND places.city='" . $city . "' AND
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR) AND CURDATE() ";
            if($listingtype != ""){
                $query .= " AND boards.listings.type IN('".$listingtype."')";
            }
            else{
                $query .= " AND boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
            }
                $query .= " group by listings.subarea";
        } else {
            $query = "SELECT
            listings.city as area,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 24 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 24 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_thirteen,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS year_minus_thirteen,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 24 MONTH)) AS month_minus_thirteen,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 23 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 23 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_twelve,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS year_minus_twelve,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 23 MONTH)) AS month_minus_twelve,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 22 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 22 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS year_minus_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 22 MONTH)) AS month_minus_eleven,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 21 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 21 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS year_minus_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 21 MONTH)) AS month_minus_ten,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 20 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 20 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS year_minus_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 20 MONTH)) AS month_minus_nine,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 19 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 19 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS year_minus_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 19 MONTH)) AS month_minus_eight,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 18 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 18 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS year_minus_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 18 MONTH)) AS month_minus_seven,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 17 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 17 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS year_minus_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 17 MONTH)) AS month_minus_six,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 16 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 16 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS year_minus_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 16 MONTH)) AS month_minus_five,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 15 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 15 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS year_minus_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 15 MONTH)) AS month_minus_four,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 14 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 14 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS year_minus_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 14 MONTH)) AS month_minus_three,


ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 13 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 13 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS year_minus_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 13 MONTH)) AS month_minus_two,
                        
                        ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 12 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_minus_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS year_minus_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 12 MONTH)) AS month_minus_one,

            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 11 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_one,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS year_one,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 11 MONTH)) AS month_one,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 10 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 10 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_two,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS year_two,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 10 MONTH)) AS month_two,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 9 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 9 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_three,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS year_three,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 9 MONTH)) AS month_three,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 8 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 8 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_four,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS year_four,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 8 MONTH)) AS month_four,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 7 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 7 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_five,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS year_five,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 7 MONTH)) AS month_five,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 6 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 6 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_six,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS year_six,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 6 MONTH)) AS month_six,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 5 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_seven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS year_seven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 5 MONTH)) AS month_seven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 4 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 4 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_eight,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS year_eight,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 4 MONTH)) AS month_eight,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 3 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 3 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_nine,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS year_nine,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 3 MONTH)) AS month_nine,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 2 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_ten,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS year_ten,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 2 MONTH)) AS month_ten,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH,'%Y-%m-01') AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)),DATEDIFF(sold_date,list_date),null))) AS result_eleven,
            YEAR(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS year_eleven,
            MONTH(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) AS month_eleven,
            
        
            ROUND(AVG(IF(`status`='sold' AND (sold_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())),DATEDIFF(sold_date,list_date),null))) AS result_twelve,
            YEAR(LAST_DAY(CURDATE())) AS year_twelve,
            MONTH(LAST_DAY(CURDATE())) AS month_twelve
            
            FROM pixilinkvow.places
            JOIN boards.listings ON listings.city = places.place
        WHERE 
            `table` IN ('mlsr_listings', 'vancouver_lotsland') AND
            places.type = 'city' AND places.stats_disabled=0 AND
            `status` = 'sold' AND
            sold_date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR) AND CURDATE() ";
            if($listingtype != ""){
                $query .= " AND boards.listings.type IN('".$listingtype."')";
            }
            else{
                $query .= " AND boards.listings.type IN('Apartment', 'House','Townhouse','Duplex','Fourplex','Triplex') ";
            }
            $query .= " group by listings.city";
        }
        $stats =  DB::connection($this->connection_360)
            ->select($query);
        foreach ($stats as $stat) {
            $stat->result_minus_one = $stat->result_minus_one ? $stat->result_minus_one : 'N/A';
            $stat->result_minus_two = $stat->result_minus_two ? $stat->result_minus_two : 'N/A';
            $stat->result_minus_three = $stat->result_minus_three ? $stat->result_minus_three : 'N/A';
            $stat->result_minus_four = $stat->result_minus_four ? $stat->result_minus_four : 'N/A';
            $stat->result_minus_five = $stat->result_minus_five ? $stat->result_minus_five : 'N/A';
            $stat->result_minus_six = $stat->result_minus_six ? $stat->result_minus_six : 'N/A';
            $stat->result_minus_seven = $stat->result_minus_seven ? $stat->result_minus_seven : 'N/A';
            $stat->result_minus_eight = $stat->result_minus_eight ? $stat->result_minus_eight : 'N/A';
            $stat->result_minus_nine = $stat->result_minus_nine ? $stat->result_minus_nine : 'N/A';
            $stat->result_minus_ten = $stat->result_minus_ten ? $stat->result_minus_ten : 'N/A';
            $stat->result_minus_eleven = $stat->result_minus_eleven ? $stat->result_minus_eleven : 'N/A';
            $stat->result_minus_twelve = $stat->result_minus_twelve ? $stat->result_minus_twelve : 'N/A';

            $stat->result_one = $stat->result_one ? $stat->result_one : 'N/A';
            $stat->result_two = $stat->result_two ? $stat->result_two : 'N/A';
            $stat->result_three = $stat->result_three ? $stat->result_three : 'N/A';
            $stat->result_four = $stat->result_four ? $stat->result_four : 'N/A';
            $stat->result_five = $stat->result_five ? $stat->result_five : 'N/A';
            $stat->result_six = $stat->result_six ? $stat->result_six : 'N/A';
            $stat->result_seven = $stat->result_seven ? $stat->result_seven : 'N/A';
            $stat->result_eight = $stat->result_eight ? $stat->result_eight : 'N/A';
            $stat->result_nine = $stat->result_nine ? $stat->result_nine : 'N/A';
            $stat->result_ten = $stat->result_ten ? $stat->result_ten : 'N/A';
            $stat->result_eleven = $stat->result_eleven ? $stat->result_eleven : 'N/A';
            $stat->result_twelve = $stat->result_twelve ? $stat->result_twelve : 'N/A';
        }
        return $stats;
    }

    public function number_shorten($number, $precision = 3, $divisors = null)
    {

        // Setup default $divisors if not provided
        if (!isset($divisors)) {
            $divisors = array(
                pow(1000, 0) => '', // 1000^0 == 1
                pow(1000, 1) => 'K', // Thousand
                pow(1000, 2) => 'M', // Million
                pow(1000, 3) => 'B', // Billion
                pow(1000, 4) => 'T', // Trillion
                pow(1000, 5) => 'Qa', // Quadrillion
                pow(1000, 6) => 'Qi', // Quintillion
            );
        }

        // Loop through each $divisor and find the
        // lowest amount that matches
        foreach ($divisors as $divisor => $shorthand) {
            if (abs($number) < ($divisor * 1000)) {
                // We found a match!
                break;
            }
        }

        // We found our match, or there were no matches.
        // Either way, use the last defined value for $divisor.
        if ($number == 0) {
            return 0;
        }
        if ($shorthand == 'K') {
            return number_format($number / $divisor, 0) . $shorthand;
        } elseif ($shorthand == 'M') {
            return number_format($number / $divisor, 2) . $shorthand;
        } else {
            return number_format($number / $divisor, $precision) . $shorthand;
        }
    }
}
