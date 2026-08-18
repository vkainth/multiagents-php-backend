<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Buildings;
use App\Models\Listings;
use App\Models\Places;
use App\Helpers\Helper;


class SitemapController extends Controller
{
    protected $connection_360 = 'mysql_pixi360';

    public function sitemap_index()
    {
        $sql = "select distinct postalarea, MAX(DATE(last_modified)) as last_modified from boards.listings where board in ('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board') and status = 'Active' group by postalarea";
        $active_postalareas =  DB::select(/*DB::raw*/($sql));

        $sql = "select distinct postalarea, MAX(DATE(last_modified)) as last_modified from boards.listings where board in ('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board') and status = 'Sold' group by postalarea";
        $sold_postalareas =  DB::select(/*DB::raw*/($sql));

        $response =  '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .=  '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        foreach ($active_postalareas as $postalarea) {
            if (trim($postalarea->postalarea??'')) {
                $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-' . urlencode($postalarea->postalarea) . '-active.xml</loc>';
                $response .= '<lastmod>' . urlencode($postalarea->last_modified) . '</lastmod>';
                $response .= '</sitemap>';
            }
        }

        foreach ($sold_postalareas as $postalarea) {
            if (trim($postalarea->postalarea??'')) {
                $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-' . urlencode($postalarea->postalarea) . '-sold.xml</loc>';
                $response .= '<lastmod>' . urlencode($postalarea->last_modified) . '</lastmod>';
                $response .= '</sitemap>';
            }
        }

        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-static.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-stats.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-reports.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-market-updates.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-neighbourhoods.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-top-realtor.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-houses.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-townhouses.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-multi-family.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-adv-search-listings.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-adv-search-listings-bedrooms.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-adv-search-listings-subarea-bedrooms.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-search-listings-city-type.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-buildings-city-landing.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-bedroom-landing-pages.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-filtered-search.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-school-catchments.xml</loc><lastmod>' . date('Y-m-d') . '</lastmod></sitemap>';
        $response .= '</sitemapindex>';
        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Cache-Control', 'post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex');
    }

    /**
     * sitemap_listings_active_index — sitemap index of all per-postal-area ACTIVE listing sitemaps.
     * Submitted separately to GSC so active-listing errors are isolated from sold-listing errors.
     * [added: 2026-05]
     */
    public function sitemap_listings_active_index()
    {
        $sql = "SELECT DISTINCT postalarea, MAX(DATE(last_modified)) AS last_modified
                FROM boards.listings
                WHERE board IN ('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board')
                  AND status = 'Active'
                GROUP BY postalarea";
        $postalareas = DB::select($sql);

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($postalareas as $row) {
            if (trim($row->postalarea ?? '')) {
                $response .= '<sitemap>'
                    . '<loc>https://www.bccondosandhomes.com/sitemap-' . urlencode($row->postalarea) . '-active.xml</loc>'
                    . '<lastmod>' . urlencode($row->last_modified) . '</lastmod>'
                    . '</sitemap>';
            }
        }

        $response .= '</sitemapindex>';
        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('X-Robots-Tag', 'noindex');
    }

    /**
     * sitemap_listings_sold_index — sitemap index of all per-postal-area SOLD listing sitemaps.
     * Submitted separately to GSC so sold-listing errors are isolated from active-listing errors.
     * [added: 2026-05]
     */
    public function sitemap_listings_sold_index()
    {
        $sql = "SELECT DISTINCT postalarea, MAX(DATE(last_modified)) AS last_modified
                FROM boards.listings
                WHERE board IN ('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board')
                  AND status = 'Sold'
                GROUP BY postalarea";
        $postalareas = DB::select($sql);

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($postalareas as $row) {
            if (trim($row->postalarea ?? '')) {
                $response .= '<sitemap>'
                    . '<loc>https://www.bccondosandhomes.com/sitemap-' . urlencode($row->postalarea) . '-sold.xml</loc>'
                    . '<lastmod>' . urlencode($row->last_modified) . '</lastmod>'
                    . '</sitemap>';
            }
        }

        $response .= '</sitemapindex>';
        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('X-Robots-Tag', 'noindex');
    }

    public function sitemap_active($postalarea)
    {
        $sql = "select slug from boards.listings where board in ('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board') and status = 'Active' and postalarea = '" . $postalarea . "'";
        $listings =  DB::select(/*DB::raw*/($sql));

        $response =  '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= ' <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        foreach ($listings as $listing) {
            $response .= '<url>
                            <loc>https://www.bccondosandhomes.com/listing/' . $listing->slug . '</loc>
                            <changefreq>daily</changefreq>
                            <priority>0.9</priority>
                          </url>';
        }

        $response .= '</urlset>';

        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Cache-Control', 'post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex');
    }

    public function sitemap_sold($postalarea)
    {
        $sql = "select slug from boards.listings where board in ('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board') and status = 'Sold' and postalarea = '" . $postalarea . "'";
        $listings =  DB::select(/*DB::raw*/($sql));

        $response =  '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= ' <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        foreach ($listings as $listing) {
            $response .= '<url>
                            <loc>https://www.bccondosandhomes.com/listing/' . $listing->slug . '</loc>
                            <changefreq>daily</changefreq>
                            <priority>0.9</priority>
                          </url>';
        }

        $response .= '</urlset>';

        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Cache-Control', 'post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex');
    }

    /**
     * sitemap_lastweek / sitemap_lastmonth — listings modified within N days.
     * For Active: filters by list_date. For Sold: filters by sold_date so recently
     * sold properties actually appear (list_date doesn't change on sale). [fixed: 2026-05]
     *
     * @param  string  $status  Active|Sold|null (all)
     * @param  int     $days    7 or 30
     */
    public function sitemap_lastweek($status=null, $days=7)
    {
        $boards = "'Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'";

        if (!empty($status) && strtolower($status) === 'sold') {
            // Use sold_date for sold listings — list_date never changes after the listing goes active
            $sql = "SELECT `slug`, DATE_FORMAT(`last_modified`, '%Y-%m-%dT%H:%i:%s') AS `last_modified`
                    FROM `boards`.`listings`
                    WHERE `board` IN ({$boards})
                      AND `status` = 'Sold'
                      AND DATE(`sold_date`) >= DATE( NOW() - INTERVAL {$days} DAY )";
        } elseif (!empty($status) && strtolower($status) === 'active') {
            $sql = "SELECT `slug`, DATE_FORMAT(`last_modified`, '%Y-%m-%dT%H:%i:%s') AS `last_modified`
                    FROM `boards`.`listings`
                    WHERE `board` IN ({$boards})
                      AND `status` = 'Active'
                      AND DATE(`list_date`) >= DATE( NOW() - INTERVAL {$days} DAY )";
        } else {
            $sql = "SELECT `slug`, DATE_FORMAT(`last_modified`, '%Y-%m-%dT%H:%i:%s') AS `last_modified`
                    FROM `boards`.`listings`
                    WHERE `board` IN ({$boards})
                      AND DATE(`list_date`) >= DATE( NOW() - INTERVAL {$days} DAY )";
        }

        $listings = DB::select($sql);

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        foreach ($listings as $listing) {
            $response .= '<url>'
                . '<loc>https://www.bccondosandhomes.com/listing/' . $listing->slug . '</loc>'
                . '<lastmod>' . $listing->last_modified . (date('I') ? '-07:00' : '-08:00') . '</lastmod>'
                . '<changefreq>hourly</changefreq>'
                . '<priority>0.9</priority>'
                . '</url>';
        }

        $response .= '</urlset>';

        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Cache-Control', 'post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex');
    }

    public function sitemap_lastweek_active()  { return $this->sitemap_lastweek('Active', 7); }
    public function sitemap_lastweek_sold()    { return $this->sitemap_lastweek('Sold',   7); }

    /**
     * sitemap_lastmonth — listings from the past 30 days.
     * These methods were referenced in routes but missing — caused 500 errors on every crawl. [added: 2026-05]
     */
    public function sitemap_lastmonth_active() { return $this->sitemap_lastweek('Active', 30); }
    public function sitemap_lastmonth_sold()   { return $this->sitemap_lastweek('Sold',   30); }

    public function sitemap_buildings()
    {
        // [fixed: Task#329] Previously queried pixilink_mlsr.buildings (mysql_pixi360), which holds
        // pre-Sep-2021 slugs that have since been updated in mysql_mlsr. BuildingController resolves
        // slugs via the Buildings Eloquent model (mysql_mlsr), so the sitemap must use the same source.
        // Victoria Board exclusion + soft-delete exclusion are handled automatically by
        // BuildingsGlobalFilterScope and SoftDeletes on the Buildings model.
        $buildings = Buildings::whereNotNull('slug')
            ->where('slug', '!=', '')
            ->where('slug', 'NOT LIKE', '0%')
            ->whereNotNull('strata_no')
            ->where('strata_no', '!=', '')
            ->get(['slug']);

        $response =  '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        foreach ($buildings as $building) {
            $response .= "\n".' <url> <loc>https://www.bccondosandhomes.com/building/'. $building->slug
            . '</loc> <changefreq>daily</changefreq> <priority>0.9</priority> </url>';
        }

        $response .= "\n".'</urlset>';

        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Cache-Control', 'post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex');
    }

    /**
     * sitemap_buildings_city buildings-sitemap-divided-by-cities [created:28-12-2021 ]
     * @return [type] [description]
     */
    public function sitemap_buildings_city($city=null)
    {

        $response =  '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= ' <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        if(false && empty(trim($city??''))){

            $sql = "select distinct trim(buildings.city) as `city` from pixilink_mlsr.buildings group by `city`"; //strata_no is not null and strata_no != ''";
            $groupedRecords = DB::connection($this->connection_360)->select(/*DB::raw*/($sql));

            foreach ($groupedRecords as $_item) {
                    $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-buildings/' . $this->seoUrl(trim($_item->city??'')) . '.xml</loc>';
                    $response .= '</sitemap>';
            }

        }else if(empty(trim($city??''))){
            $_config_mysqlMlsr_prevState = config()->get(['database.connections.mysql_mlsr.strict']);
            config()->set(['database.connections.mysql_mlsr.strict'=>false]);
            DB::reconnect();

            $sql = "select distinct trim(buildings.city) as `city` from pixilink_mlsr.buildings group by `city`"; //strata_no is not null and strata_no != ''";
            $groupedRecords = DB::connection($this->connection_360)->select(/*DB::raw*/($sql));

            $groupedRecords = Buildings::whereNotNull('city')->where('city','<>','')
            ->where('city','NOT LIKE','%fake%')->where('slug','NOT LIKE','aaa-%')->where('slug','NOT LIKE','123-4%') //[added:07-06-2022]
            ->groupBy('city')->get();

            $cities = array_keys($groupedRecords->toArray());

            foreach ($groupedRecords as $_item) {
                // $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-buildings/' . ( trim($_item->city??'')==''?'~':$this->seoUrl(trim($_item->city??'')) ) . '.xml</loc>';
                // $response .= '<count>' . $groupedRecords->count() . '</count>';

                $response .= "\n".'<sitemap><loc>'. route('sitemap-buildings-city',['city'=>( trim($_item->city??'')==''?'~':$this->seoUrl(trim($_item->city??'')) ) ]) .'</loc></sitemap>';
            }

            if($_config_mysqlMlsr_prevState){
                config()->set(['database.connections.mysql_mlsr.strict'=>true]);
                DB::reconnect();
            }

        }else{
            // [fixed: Task#329] Two bugs corrected:
            // 1. Was querying pixilink_mlsr.buildings (mysql_pixi360) which has stale pre-2021 slugs;
            //    BuildingController uses mysql_mlsr (Buildings model), so sitemap must match.
            // 2. `deleted_at IS NOT NULL` was inverted — selected only soft-deleted buildings;
            //    SoftDeletes on the Buildings model now auto-excludes deleted rows correctly.
            // '-' → '_' is intentional: SQL LIKE treats _ as single-char wildcard,
            // so 'north_vancouver' matches the DB value 'North Vancouver' (space).
            // '~' → '' collapses to an empty pattern, handled by the whereNull clause below.
            $_city = str_replace(['-','~'], ['_',''], $city);
            $buildings = Buildings::when($_city !== '', function ($q) use ($_city) {
                    $q->where('city', 'LIKE', $_city);
                }, function ($q) {
                    $q->where(function ($q2) { $q2->whereNull('city')->orWhere('city', ''); });
                })
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->where('slug', 'NOT LIKE', '%aaa-%')
                ->where('slug', 'NOT LIKE', '%123-%')
                ->get(['slug']);

            foreach ($buildings as $building) {    
                $response .= '<url> <loc>https://www.bccondosandhomes.com/building/' . $building->slug . '</loc> <changefreq>daily</changefreq> <priority>0.9</priority> </url>
                              ';                

            }

        }

        $response .= '</urlset>';

        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Cache-Control', 'post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex');
    }


    /**
     * sitemap_search_listings [created:03-04-2022] [updated:13-04-2022]
     * @param  [type]  $city    [description]
     * @return [type]           [description]
     */
    public function sitemap_search_listings($city=null, $subarea=null)
    {

        $response =  '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

        if(false && empty(trim($city??''))){

            $sql = "select distinct trim(buildings.city) as `city` from pixilink_mlsr.buildings group by `city`"; //strata_no is not null and strata_no != ''";
            $groupedRecords = DB::connection($this->connection_360)->select(/*DB::raw*/($sql));

            foreach ($groupedRecords as $_item) {
                    $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap/search-listings/' . $this->seoUrl(trim($_item->city??'')) . '.xml</loc>';
                    $response .= '</sitemap>';
            }

        }else if(empty(trim($city??'')) || empty(trim($subarea??''))){
            $_config_mysqlMlsr_prevState = config()->get(['database.connections.mysql_mlsr.strict']);
            config()->set(['database.connections.mysql_mlsr.strict'=>false]);
            DB::reconnect();

            $response =  '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

            if(empty(trim($subarea??''))){

                // $groupedRecords = Listings::whereNotNull('city')->whereNotNull('subarea')->groupBy(['city','subarea'])->get();

                $sql = "SELECT DISTINCT TRIM(l.city) as `city`, TRIM(l.subarea) as `subarea` FROM `pixilink_mlsr`.`mlsr_listings` `l` WHERE l.slug IS NOT NULL AND l.slug!='' AND (l.`status` = 'Active' OR l.`status`='Sold') GROUP BY `l`.`city`,`l`.`subarea`"; 
                $groupedRecords = DB::connection($this->connection_360)->select(/*DB::raw*/($sql));

                // $cities = array_keys($groupedRecords->toArray());

                foreach ($groupedRecords as $_item) {
                    $response .= "\n".'<sitemap><loc>'. route('sitemap-search-listings',['city'=>( trim($_item->city??'')==''?'~':$this->seoUrl(trim($_item->city??'')) ), 'subarea'=>( trim($_item->subarea??'')==''?'~':$this->seoUrl(trim($_item->subarea??'')) ) ]) .'</loc></sitemap>';
                }

            }else{

                $groupedRecords = Listings::whereNotNull('city')->whereNotNull('subarea')->groupBy('city')->get();

                $cities = array_keys($groupedRecords->toArray());

                foreach ($groupedRecords as $_item) {
                // $response .= '<sitemap><loc>https://www.bccondosandhomes.com/sitemap-buildings/' . ( trim($_item->city??'')==''?'~':$this->seoUrl(trim($_item->city??'')) ) . '.xml</loc>';
                // $response .= '<count>' . $groupedRecords->count() . '</count>';

                    $response .= "\n".'<sitemap><loc>'. str_replace('public/','', route('sitemap-search-listings',['city'=>( trim($_item->city??'')==''?'~':$this->seoUrl(trim($_item->city??'')) ) ]) ) .'</loc></sitemap>';
                }
            }

            $response .= "\n".'</sitemapindex>';

            if($_config_mysqlMlsr_prevState){
                config()->set(['database.connections.mysql_mlsr.strict'=>true]);
                DB::reconnect();
            }

        }else{
            
            $_city = str_replace(['-','~'], ['_',''], $city);
            $_subarea = str_replace(['-','~'], ['_',''], $subarea);
            $sql = "SELECT l.`slug`, l.`city`, l.`subarea`  FROM `pixilink_mlsr`.`mlsr_listings` `l` WHERE l.`city` LIKE '".$_city."' AND l.`subarea` LIKE '".$_subarea."' AND l.`status` IN ('Active','Sold') " ;
            // $sql .= ($_city==''?' OR l.city IS NULL':''); 

            $listings = DB::connection($this->connection_360) ->select(/*DB::raw*/($sql));
            

            foreach ($listings as $listing) {    
                $response .= '<url>
                                <loc>https://www.bccondosandhomes.com/search-listings/' . str_replace(['-',' '], ['~','-'], strtolower($listing->city)) .'/' . str_replace(['-',' '], ['~','-'], strtolower($listing->subarea)) . '</loc>
                                <changefreq>daily</changefreq>
                                <priority>0.9</priority>
                              </url>
                              ';

            }
             

            $response .= ' <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
            xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

            foreach(['House','Townhouse','Apartment'] as $_tmpfeaType){
                // foreach(['',/*'1+',*/'2+','3+'] as $_tmpfeaKitchens){
                    // foreach(['',/*'1+',*/'2+','3+'] as $_tmpfeaBaths){
                        // foreach(['',/*'1+',*/'2+','3+','5'] as $_tmpfeaBeds){
                            // foreach(['',/*'1+',*/'500000','1500000','2000000','3000000'] as $_tmpfeaPriceto){
                                /*if(request()->input('nocdata',false)){
                                    $response .= "\n".'<url> <loc>' . str_replace('public/','',
                                        route('adv_search_listings', ['city'=>\App\Helpers\Helper::enslugPlace($city),'subarea'=>\App\Helpers\Helper::enslugPlace($subarea),'type'=>strtolower($_tmpfeaType), 'beds'=>$_tmpfeaBeds, 'baths'=>$_tmpfeaBaths, 'kitchens'=>$_tmpfeaKitchens,'priceto'=>$_tmpfeaPriceto ]) 
                                        ) . '</loc> <changefreq>daily</changefreq> <priority>0.9</priority> </url> ';
                                }else{ }*/
                                // $response .= "\n".'<url> <loc><![CDATA[' . str_replace('public/','',
                                //     route('adv_search_listings', ['city'=>\App\Helpers\Helper::enslugPlace($city),'subarea'=>\App\Helpers\Helper::enslugPlace($subarea),'type'=>strtolower($_tmpfeaType), /*'beds'=>$_tmpfeaBeds, 'baths'=>$_tmpfeaBaths, 'kitchens'=>$_tmpfeaKitchens,'priceto'=>$_tmpfeaPriceto*/ ]) 
                                //     ) . ']]></loc> <changefreq>daily</changefreq> <priority>0.9</priority> </url> ';


                            // }
                        // }
                    // }
                // }
            }

            $response .= "\n".'</urlset>';

        }


        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Cache-Control', 'post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex');
    }

    public function sitemap_searchpages()
    {
        $sql = "select * from bccondosandhomes.mls_query";
        $searches =  DB::select(/*DB::raw*/($sql));

        $response =  '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= ' <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        foreach ($searches as $search) {
            $response .= '<url>
            <loc>https://www.bccondosandhomes.com/' . $search->slug . '-for-sale</loc>
            <changefreq>daily</changefreq>
            <priority>0.9</priority>
          </url>';
            $sql = "select group_concat(distinct(subarea)) as subareas from boards.listings where " . $search->query . " and status='Active' and `table` = 'mlsr_listings'";
            $res1 = DB::select(/*DB::raw*/($sql));
            if ($res1 && count($res1) > 0) {
                $_subareas = explode(",", $res1[0]->subareas);
                foreach ($_subareas as $_subarea) {
                    $response .= '<url>
                    <loc>https://www.bccondosandhomes.com/' . $search->slug . '-for-sale-' . $this->seoUrl(trim($_subarea??'')) . '</loc>
                    <changefreq>daily</changefreq>
                    <priority>0.9</priority>
                  </url>';
                }
            }
        }

        $response .= '</urlset>';

        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Cache-Control', 'post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex');
    }

    public function sitemap_reports()
    {
        $typeDbToSlug = ['Apartment' => 'condos', 'House' => 'houses', 'Townhouse' => 'townhouses'];
        $typeSlugs    = ['condos', 'houses', 'townhouses'];
        $today        = date('Y-m-d');
        $curY         = (int)date('Y');
        $curM         = (int)date('n');

        $cities = Places::where('type','city')->where('stats_disabled',0)->where('stats_subareas_disabled',0)->orderBy('order')->get();

        // Bulk query: available city+TYPE months — full history since 2010, same floor as archive
        // ($typeDbToSlug defined above is reused here)
        $cityNames     = $cities->pluck('place')->map(fn($p) => "'" . addslashes($p) . "'")->implode(',');
        $cityMonthsRaw = DB::connection('mysql_pixi360')->select("
            SELECT listings.city, listings.type AS db_type, YEAR(sold_date) AS yr, MONTH(sold_date) AS mo, COUNT(*) AS cnt
            FROM boards.listings
            WHERE status = 'Sold' AND sold_date IS NOT NULL AND sold_date > '2010-01-01'
              AND type IN('Apartment','House','Townhouse')
              AND city IN ({$cityNames})
            GROUP BY listings.city, listings.type, yr, mo
            HAVING cnt >= 3
            ORDER BY yr DESC, mo DESC
        ");

        // Bulk query: available subarea+TYPE months — full history since 2010
        $subareaMonthsRaw = DB::connection('mysql_pixi360')->select("
            SELECT listings.city, listings.subarea, listings.type AS db_type, YEAR(sold_date) AS yr, MONTH(sold_date) AS mo, COUNT(*) AS cnt
            FROM boards.listings
            WHERE status = 'Sold' AND sold_date IS NOT NULL AND sold_date > '2010-01-01'
              AND type IN('Apartment','House','Townhouse')
              AND city IN ({$cityNames})
              AND subarea IS NOT NULL AND subarea != ''
            GROUP BY listings.city, listings.subarea, listings.type, yr, mo
            HAVING cnt >= 3
            ORDER BY yr DESC, mo DESC
        ");

        // Index by city+typeSlug → months and city+subarea+typeSlug → months
        $cityMonths = [];
        foreach ($cityMonthsRaw as $row) {
            $tSlug = $typeDbToSlug[$row->db_type] ?? null;
            if ($tSlug) $cityMonths[$row->city][$tSlug][] = $row;
        }
        $saMonths = [];
        foreach ($subareaMonthsRaw as $row) {
            $tSlug = $typeDbToSlug[$row->db_type] ?? null;
            if ($tSlug) $saMonths[$row->city][$row->subarea][$tSlug][] = $row;
        }

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Hub
        $response .= '<url><loc>https://www.bccondosandhomes.com/market-report</loc><lastmod>' . $today . '</lastmod><changefreq>daily</changefreq><priority>0.9</priority></url>';

        foreach ($cities as $city) {
            $cName = $city->place;
            $cSlug = Helper::enslugPlace($cName);

            // City hub
            $response .= '<url><loc>https://www.bccondosandhomes.com/market-report/' . $cSlug . '</loc><lastmod>' . $today . '</lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>';

            // City+type archive + monthly report pages (only where actual type-specific data exists)
            foreach ($typeSlugs as $t) {
                $response .= '<url><loc>https://www.bccondosandhomes.com/market-report/' . $cSlug . '/' . $t . '</loc><lastmod>' . $today . '</lastmod><changefreq>monthly</changefreq><priority>0.6</priority></url>';
                $cityMths = $cityMonths[$cName][$t] ?? [];
                foreach ($cityMths as $row) {
                    $monthName  = strtolower(date('F', mktime(0,0,0,$row->mo,1,$row->yr)));
                    $mSlug      = "{$monthName}-{$row->yr}";
                    $isNow      = ((int)$row->yr === $curY && (int)$row->mo === $curM);
                    $changefreq = $isNow ? 'daily' : 'never';
                    $priority   = $isNow ? '0.8'   : '0.5';
                    $lastmod    = $isNow ? $today  : date('Y-m-d', mktime(0,0,0,$row->mo+1,1,$row->yr));
                    $response .= '<url><loc>https://www.bccondosandhomes.com/market-report/' . $cSlug . '/' . $t . '/' . $mSlug . '</loc><lastmod>' . $lastmod . '</lastmod><changefreq>' . $changefreq . '</changefreq><priority>' . $priority . '</priority></url>';
                }
            }

            // Subarea+type archive + monthly report pages (only where actual type-specific data exists)
            $subareas = Places::where('type','subarea')->where('city',$cName)->where('stats_disabled',0)->get();
            foreach ($subareas as $sa) {
                $saName = $sa->place;
                $saSlug = Helper::enslugPlace($saName);
                foreach ($typeSlugs as $t) {
                    $response .= '<url><loc>https://www.bccondosandhomes.com/market-report/' . $cSlug . '/' . $saSlug . '/' . $t . '</loc><lastmod>' . $today . '</lastmod><changefreq>monthly</changefreq><priority>0.6</priority></url>';
                    $saMths = $saMonths[$cName][$saName][$t] ?? [];
                    foreach ($saMths as $row) {
                        $monthName  = strtolower(date('F', mktime(0,0,0,$row->mo,1,$row->yr)));
                        $mSlug      = "{$monthName}-{$row->yr}";
                        $isNow      = ((int)$row->yr === $curY && (int)$row->mo === $curM);
                        $changefreq = $isNow ? 'daily' : 'never';
                        $priority   = $isNow ? '0.8'   : '0.5';
                        $lastmod    = $isNow ? $today  : date('Y-m-d', mktime(0,0,0,$row->mo+1,1,$row->yr));
                        $response .= '<url><loc>https://www.bccondosandhomes.com/market-report/' . $cSlug . '/' . $saSlug . '/' . $t . '/' . $mSlug . '</loc><lastmod>' . $lastmod . '</lastmod><changefreq>' . $changefreq . '</changefreq><priority>' . $priority . '</priority></url>';
                    }
                }
            }
        }

        $response .= '</urlset>';
        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    /**
     * sitemap_market_updates — monthly market update archive pages per city.
     * current-data pages (new-listings, price-reductions, sold-over-asking) are excluded.
     */
    public function sitemap_market_updates()
    {
        $today  = date('Y-m-d');
        $curY   = (int)date('Y');
        $curM   = (int)date('n');
        $cities = Places::where('type', 'city')->where('stats_disabled', 0)->where('stats_subareas_disabled', 0)->orderBy('order')->get();

        $cityNames = $cities->pluck('place')->map(fn($p) => "'" . addslashes($p) . "'")->implode(',');
        $monthsRaw = DB::connection('mysql_pixi360')->select("
            SELECT city, YEAR(sold_date) AS yr, MONTH(sold_date) AS mo, COUNT(*) AS cnt
            FROM boards.listings
            WHERE status = 'Sold' AND sold_date IS NOT NULL AND sold_date > '2010-01-01'
              AND city IN ({$cityNames})
            GROUP BY city, yr, mo
            HAVING cnt >= 3
            ORDER BY yr DESC, mo DESC
        ");

        $byCity = [];
        foreach ($monthsRaw as $row) {
            $byCity[$row->city][] = $row;
        }

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($cities as $city) {
            $cName = $city->place;
            $cSlug = Helper::enslugPlace($cName);

            $response .= '<url><loc>https://www.bccondosandhomes.com/market-update/' . $cSlug . '</loc><lastmod>' . $today . '</lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>';

            foreach ($byCity[$cName] ?? [] as $row) {
                $yr         = (int)$row->yr;
                $mo         = (int)$row->mo;
                $isNow      = ($yr === $curY && $mo === $curM);
                $changefreq = $isNow ? 'daily' : 'never';
                $priority   = $isNow ? '0.8'   : '0.5';
                $lastmod    = $isNow ? $today  : date('Y-m-d', mktime(0, 0, 0, $mo + 1, 1, $yr));
                $response .= '<url><loc>https://www.bccondosandhomes.com/market-update/' . $cSlug . '/' . $yr . '/' . $mo . '</loc><lastmod>' . $lastmod . '</lastmod><changefreq>' . $changefreq . '</changefreq><priority>' . $priority . '</priority></url>';
            }
        }

        $response .= '</urlset>';
        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    public function sitemap_static()
    {
        $today = date('Y-m-d');

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $response .= '<url><loc>https://www.bccondosandhomes.com/buyers-guide</loc><lastmod>' . $today . '</lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>';
        $response .= '<url><loc>https://www.bccondosandhomes.com/sellers-guide</loc><lastmod>' . $today . '</lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>';
        $response .= '<url><loc>https://www.bccondosandhomes.com/ssmuh-guide</loc><lastmod>' . $today . '</lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>';
        $response .= '<url><loc>https://www.bccondosandhomes.com/buying-a-duplex-bc</loc><lastmod>' . $today . '</lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>';
        $response .= '<url><loc>https://www.bccondosandhomes.com/buying-a-fourplex-bc</loc><lastmod>' . $today . '</lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>';
        $response .= '<url><loc>https://www.bccondosandhomes.com/reviews</loc><lastmod>' . $today . '</lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>';
        $response .= '<url><loc>https://www.bccondosandhomes.com/sell.html</loc><lastmod>' . $today . '</lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>';

        $response .= '</urlset>';
        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /**
     * sitemap_stats
     * Only includes city and subarea URLs that have at least one active listing.
     * [db-filter: Task#262]
     */
    public function sitemap_stats()
    {
        $types  = ['condos', 'houses', 'townhouses', 'duplexes'];
        $cities = Places::where('type', 'city')->where('stats_disabled', 0)->where('stats_subareas_disabled', 0)->orderBy('order')->get();
        $today  = date('Y-m-d');

        $cityList   = $cities->pluck('place')->values()->all();
        $cityHas    = [];
        $subareaHas = [];

        if (!empty($cityList)) {
            $placemarks = implode(',', array_fill(0, count($cityList), '?'));

            $rows = DB::select("
                SELECT TRIM(city) AS city, TRIM(subarea) AS subarea, COUNT(*) AS cnt
                FROM boards.listings
                WHERE status = 'Active'
                  AND city IN ({$placemarks})
                GROUP BY TRIM(city), TRIM(subarea)
                HAVING cnt > 0
            ", $cityList);

            foreach ($rows as $row) {
                $ck = strtolower(trim($row->city));
                $cityHas[$ck] = true;
                if ($row->subarea !== null && $row->subarea !== '') {
                    $sk = strtolower(trim($row->subarea));
                    $subareaHas[$ck][$sk] = true;
                }
            }
        }

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $response .= '<url><loc>https://www.bccondosandhomes.com/market-stats</loc><lastmod>' . $today . '</lastmod><changefreq>daily</changefreq><priority>0.9</priority></url>';

        foreach ($cities as $city) {
            $ck    = strtolower(trim($city->place));
            $cSlug = Helper::enslugPlace($city->place);

            if (!isset($cityHas[$ck])) continue;

            $response .= '<url><loc>https://www.bccondosandhomes.com/market-stats/' . $cSlug . '</loc><lastmod>' . $today . '</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>';
            foreach ($types as $t) {
                $response .= '<url><loc>https://www.bccondosandhomes.com/market-stats/' . $cSlug . '/' . $t . '</loc><lastmod>' . $today . '</lastmod><changefreq>daily</changefreq><priority>0.7</priority></url>';
            }

            $subareas = Places::where('type', 'subarea')->where('city', $city->place)->where('stats_disabled', 0)->get();
            foreach ($subareas as $sa) {
                $sk     = strtolower(trim($sa->place));
                $saSlug = Helper::enslugPlace($sa->place);

                if (!isset($subareaHas[$ck][$sk])) continue;

                $response .= '<url><loc>https://www.bccondosandhomes.com/market-stats/' . $cSlug . '/' . $saSlug . '</loc><lastmod>' . $today . '</lastmod><changefreq>daily</changefreq><priority>0.7</priority></url>';
                foreach ($types as $t) {
                    $response .= '<url><loc>https://www.bccondosandhomes.com/market-stats/' . $cSlug . '/' . $saSlug . '/' . $t . '</loc><lastmod>' . $today . '</lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>';
                }
            }
        }

        $response .= '</urlset>';
        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    /**
     * sitemap_houses
     * Only includes city and subarea URLs that have at least one active listing.
     * [db-filter: Task#261]
     */
    public function sitemap_houses()
    {
        $cities = Places::where('type', 'city')->where('stats_disabled', 0)->orderBy('order')->get();
        $today  = date('Y-m-d');

        $cityList   = $cities->pluck('place')->values()->all();

        $cityHas    = [];
        $subareaHas = [];

        if (!empty($cityList)) {
            $placemarks = implode(',', array_fill(0, count($cityList), '?'));

            $rows = DB::select("
                SELECT TRIM(city) AS city, TRIM(subarea) AS subarea, COUNT(*) AS cnt
                FROM boards.listings
                WHERE status = 'Active'
                  AND `table` = 'mlsr_listings'
                  AND city IN ({$placemarks})
                GROUP BY TRIM(city), TRIM(subarea)
                HAVING cnt > 0
            ", $cityList);

            foreach ($rows as $row) {
                $ck = strtolower(trim($row->city));
                $cityHas[$ck] = true;
                if ($row->subarea !== null && $row->subarea !== '') {
                    $sk = strtolower(trim($row->subarea));
                    $subareaHas[$ck][$sk] = true;
                }
            }
        }

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $response .= '<url><loc>https://www.bccondosandhomes.com/houses/</loc><lastmod>' . $today . '</lastmod><changefreq>daily</changefreq><priority>0.9</priority></url>';

        foreach ($cities as $city) {
            $ck    = strtolower(trim($city->place));
            $cSlug = Helper::enslugPlace($city->place);

            if (!isset($cityHas[$ck])) continue;

            $response .= '<url><loc>https://www.bccondosandhomes.com/houses/' . $cSlug . '/</loc><lastmod>' . $today . '</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>';

            $subareas = Places::where('type', 'subarea')->where('city', $city->place)->where('stats_disabled', 0)->orderBy('order')->get();
            foreach ($subareas as $sa) {
                $sk    = strtolower(trim($sa->place));
                $saSlug = Helper::enslugPlace($sa->place);
                if (!isset($subareaHas[$ck][$sk])) continue;
                $response .= '<url><loc>https://www.bccondosandhomes.com/houses/' . $cSlug . '/' . $saSlug . '/</loc><lastmod>' . $today . '</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>';
            }
        }

        $response .= '</urlset>';
        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    /**
     * sitemap_townhouses
     * Only includes city and subarea URLs that have at least one active Townhouse listing.
     */
    public function sitemap_townhouses()
    {
        $cities = Places::where('type', 'city')->where('stats_disabled', 0)->orderBy('order')->get();
        $today  = date('Y-m-d');

        $cityList   = $cities->pluck('place')->values()->all();

        $cityHas    = [];
        $subareaHas = [];

        if (!empty($cityList)) {
            $placemarks = implode(',', array_fill(0, count($cityList), '?'));

            $rows = DB::select("
                SELECT TRIM(city) AS city, TRIM(subarea) AS subarea, COUNT(*) AS cnt
                FROM boards.listings
                WHERE status = 'Active'
                  AND `type` = 'Townhouse'
                  AND `table` = 'mlsr_listings'
                  AND city IN ({$placemarks})
                GROUP BY TRIM(city), TRIM(subarea)
                HAVING cnt > 0
            ", $cityList);

            foreach ($rows as $row) {
                $ck = strtolower(trim($row->city));
                $cityHas[$ck] = true;
                if ($row->subarea !== null && $row->subarea !== '') {
                    $sk = strtolower(trim($row->subarea));
                    $subareaHas[$ck][$sk] = true;
                }
            }
        }

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $response .= '<url><loc>https://www.bccondosandhomes.com/townhouses/</loc><lastmod>' . $today . '</lastmod><changefreq>daily</changefreq><priority>0.9</priority></url>';

        foreach ($cities as $city) {
            $ck    = strtolower(trim($city->place));
            $cSlug = Helper::enslugPlace($city->place);

            if (!isset($cityHas[$ck])) continue;

            $response .= '<url><loc>https://www.bccondosandhomes.com/townhouses/' . $cSlug . '/</loc><lastmod>' . $today . '</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>';

            $subareas = Places::where('type', 'subarea')->where('city', $city->place)->where('stats_disabled', 0)->orderBy('order')->get();
            foreach ($subareas as $sa) {
                $sk    = strtolower(trim($sa->place));
                $saSlug = Helper::enslugPlace($sa->place);
                if (!isset($subareaHas[$ck][$sk])) continue;
                $response .= '<url><loc>https://www.bccondosandhomes.com/townhouses/' . $cSlug . '/' . $saSlug . '/</loc><lastmod>' . $today . '</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>';
            }
        }

        $response .= '</urlset>';
        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    /**
     * sitemap_multi_family
     * Only includes city and subarea URLs that have at least one active Duplex/Triplex/Fourplex listing.
     */
    public function sitemap_multi_family()
    {
        $cities = Places::where('type', 'city')->where('stats_disabled', 0)->orderBy('order')->get();
        $today  = date('Y-m-d');

        $cityList   = $cities->pluck('place')->values()->all();

        $cityHas    = [];
        $subareaHas = [];

        if (!empty($cityList)) {
            $placemarks = implode(',', array_fill(0, count($cityList), '?'));

            $rows = DB::select("
                SELECT TRIM(city) AS city, TRIM(subarea) AS subarea, COUNT(*) AS cnt
                FROM boards.listings
                WHERE status = 'Active'
                  AND `type` IN ('Duplex','Triplex','Fourplex')
                  AND `table` = 'mlsr_listings'
                  AND city IN ({$placemarks})
                GROUP BY TRIM(city), TRIM(subarea)
                HAVING cnt > 0
            ", $cityList);

            foreach ($rows as $row) {
                $ck = strtolower(trim($row->city));
                $cityHas[$ck] = true;
                if ($row->subarea !== null && $row->subarea !== '') {
                    $sk = strtolower(trim($row->subarea));
                    $subareaHas[$ck][$sk] = true;
                }
            }
        }

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $response .= '<url><loc>https://www.bccondosandhomes.com/multi-family/</loc><lastmod>' . $today . '</lastmod><changefreq>daily</changefreq><priority>0.9</priority></url>';

        foreach ($cities as $city) {
            $ck    = strtolower(trim($city->place));
            $cSlug = Helper::enslugPlace($city->place);

            if (!isset($cityHas[$ck])) continue;

            $response .= '<url><loc>https://www.bccondosandhomes.com/multi-family/' . $cSlug . '/</loc><lastmod>' . $today . '</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>';

            $subareas = Places::where('type', 'subarea')->where('city', $city->place)->where('stats_disabled', 0)->orderBy('order')->get();
            foreach ($subareas as $sa) {
                $sk    = strtolower(trim($sa->place));
                $saSlug = Helper::enslugPlace($sa->place);
                if (!isset($subareaHas[$ck][$sk])) continue;
                $response .= '<url><loc>https://www.bccondosandhomes.com/multi-family/' . $cSlug . '/' . $saSlug . '/</loc><lastmod>' . $today . '</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>';
            }
        }

        $response .= '</urlset>';
        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    /**
     * sitemap_neighbourhoods
     * Only includes city and subarea URLs that have at least one active listing.
     * [db-filter: Task#261]
     */
    public function sitemap_neighbourhoods()
    {
        $cities = Places::where('type', 'city')->where('stats_disabled', 0)->where('stats_subareas_disabled', 0)->orderBy('order')->get();
        $today  = date('Y-m-d');

        $cityList   = $cities->pluck('place')->values()->all();

        $cityHas    = [];
        $subareaHas = [];

        if (!empty($cityList)) {
            $placemarks = implode(',', array_fill(0, count($cityList), '?'));

            $rows = DB::select("
                SELECT TRIM(city) AS city, TRIM(subarea) AS subarea, COUNT(*) AS cnt
                FROM boards.listings
                WHERE status = 'Active'
                  AND `table` = 'mlsr_listings'
                  AND city IN ({$placemarks})
                GROUP BY TRIM(city), TRIM(subarea)
                HAVING cnt > 0
            ", $cityList);

            foreach ($rows as $row) {
                $ck = strtolower(trim($row->city));
                $cityHas[$ck] = true;
                if ($row->subarea !== null && $row->subarea !== '') {
                    $sk = strtolower(trim($row->subarea));
                    $subareaHas[$ck][$sk] = true;
                }
            }
        }

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $response .= '<url><loc>https://www.bccondosandhomes.com/neighbourhood/</loc><lastmod>' . $today . '</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>';

        foreach ($cities as $city) {
            $ck    = strtolower(trim($city->place));
            $cSlug = Helper::enslugPlace($city->place);

            if (!isset($cityHas[$ck])) continue;

            $response .= '<url><loc>https://www.bccondosandhomes.com/neighbourhood/' . $cSlug . '/</loc><lastmod>' . $today . '</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>';

            $subareas = Places::where('type', 'subarea')->where('city', $city->place)->where('stats_disabled', 0)->orderBy('order')->get();
            foreach ($subareas as $sa) {
                $sk     = strtolower(trim($sa->place));
                $saSlug = Helper::enslugPlace($sa->place);
                if (!isset($subareaHas[$ck][$sk])) continue;
                $response .= '<url><loc>https://www.bccondosandhomes.com/neighbourhood/' . $cSlug . '/' . $saSlug . '/</loc><lastmod>' . $today . '</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>';
            }
        }

        $response .= '</urlset>';
        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    /**
     * sitemap_buildings_city_landing
     * Covers the /buildings/ hub and /buildings/{city} landing pages.
     * [created: Task#244]
     */
    public function sitemap_buildings_city_landing()
    {
        $cities = Places::where('type', 'city')->where('stats_disabled', 0)->orderBy('order')->get();
        $today  = date('Y-m-d');

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $response .= '<url><loc>https://www.bccondosandhomes.com/buildings/</loc><lastmod>' . $today . '</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>';

        foreach ($cities as $city) {
            $cSlug = Helper::enslugPlace($city->place);
            $response .= '<url><loc>https://www.bccondosandhomes.com/buildings/' . $cSlug . '</loc><lastmod>' . $today . '</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>';
        }

        $response .= '</urlset>';
        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    public function sitemap_top_realtor()
    {
        $cities = Places::where('type', 'city')->where('stats_disabled', 0)->orderBy('order')->get();
        $today  = date('Y-m-d');

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $response .= '<url><loc>https://www.bccondosandhomes.com/top-realtor/</loc><lastmod>' . $today . '</lastmod><changefreq>weekly</changefreq><priority>0.9</priority></url>';

        foreach ($cities as $city) {
            $cSlug = Helper::enslugPlace($city->place);
            $response .= '<url><loc>https://www.bccondosandhomes.com/top-realtor/' . $cSlug . '/</loc><lastmod>' . $today . '</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>';

            $subareas = Places::where('type', 'subarea')->where('city', $city->place)->where('stats_disabled', 0)->orderBy('order')->get();
            foreach ($subareas as $sa) {
                $saSlug = Helper::enslugPlace($sa->place);
                $response .= '<url><loc>https://www.bccondosandhomes.com/top-realtor/' . $cSlug . '/' . $saSlug . '/</loc><lastmod>' . $today . '</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>';
            }
        }

        $response .= '</urlset>';
        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    /**
     * sitemap_adv_search_listings_city_type_feature
     * Generates a urlset for the 3-segment city/type/feature search pages,
     * covering the most-searched cities × property types × price ranges + special features.
     * Only includes combinations that have at least one active listing in the database.
     * [created: Task#232] [db-filter: Task#258]
     */
    public function sitemap_adv_search_listings_city_type_feature()
    {
        $today = date('Y-m-d');

        $cities = Places::where('type', 'city')
            ->where('stats_disabled', 0)
            ->orderBy('order')
            ->get();

        $types = ['house', 'townhouse', 'apartment', 'duplex'];

        $features = [
            'under-500k',
            '500k-to-1m',
            '1m-to-2m',
            '2m-to-3m',
            'over-3m',
            'with-suite',
            'with-basement',
            'new-construction',
        ];

        $cityList    = $cities->pluck('place')->values()->all();
        $placemarks  = implode(',', array_fill(0, count($cityList), '?'));
        $curYear     = (int)date('Y');

        $rows = DB::select("
            SELECT
                TRIM(city) AS city,
                CASE WHEN LOWER(type) IN ('duplex','triplex','fourplex') THEN 'duplex' ELSE LOWER(type) END AS type,
                SUM(CASE WHEN listprice_2 < 500000 THEN 1 ELSE 0 END) AS under_500k,
                SUM(CASE WHEN listprice_2 >= 500000  AND listprice_2 <= 1000000 THEN 1 ELSE 0 END) AS btw_500k_1m,
                SUM(CASE WHEN listprice_2 > 1000000  AND listprice_2 <= 2000000 THEN 1 ELSE 0 END) AS btw_1m_2m,
                SUM(CASE WHEN listprice_2 > 2000000  AND listprice_2 <= 3000000 THEN 1 ELSE 0 END) AS btw_2m_3m,
                SUM(CASE WHEN listprice_2 > 3000000 THEN 1 ELSE 0 END) AS over_3m,
                SUM(CASE WHEN kitchens >= 3 THEN 1 ELSE 0 END) AS with_suite,
                SUM(CASE WHEN basement IS NOT NULL AND basement != '' THEN 1 ELSE 0 END) AS with_basement,
                SUM(CASE WHEN yearbuilt >= {$curYear} - 5 THEN 1 ELSE 0 END) AS new_construction
            FROM boards.listings
            WHERE status = 'Active'
              AND `table` = 'mlsr_listings'
              AND type IN ('House', 'Townhouse', 'Apartment', 'Duplex', 'Triplex', 'Fourplex')
              AND city IN ({$placemarks})
            GROUP BY TRIM(city), CASE WHEN LOWER(type) IN ('duplex','triplex','fourplex') THEN 'duplex' ELSE LOWER(type) END
        ", $cityList);

        $featureColMap = [
            'under-500k'       => 'under_500k',
            '500k-to-1m'       => 'btw_500k_1m',
            '1m-to-2m'         => 'btw_1m_2m',
            '2m-to-3m'         => 'btw_2m_3m',
            'over-3m'          => 'over_3m',
            'with-suite'       => 'with_suite',
            'with-basement'    => 'with_basement',
            'new-construction' => 'new_construction',
        ];

        $lookup = [];
        foreach ($rows as $row) {
            $cityKey = strtolower(trim($row->city));
            foreach ($featureColMap as $feat => $col) {
                $lookup[$cityKey][$row->type][$feat] = (int)($row->$col ?? 0);
            }
        }

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($cities as $city) {
            $citySlug = Helper::enslugPlace($city->place);
            $cityKey  = strtolower(trim($city->place));
            foreach ($types as $type) {
                foreach ($features as $feature) {
                    if (($lookup[$cityKey][$type][$feature] ?? 0) === 0) continue;
                    $url = route('adv_search_listings_city_type_feature', [
                        'city'    => $citySlug,
                        'type'    => $type,
                        'feature' => $feature,
                    ]);
                    $response .= '<url>';
                    $response .= '<loc>' . htmlspecialchars($url, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc>';
                    $response .= '<lastmod>' . $today . '</lastmod>';
                    $response .= '<changefreq>daily</changefreq>';
                    $response .= '<priority>0.7</priority>';
                    $response .= '</url>';
                }
            }
        }

        $response .= '</urlset>';

        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    /**
     * sitemap_adv_search_listings_city_type_bedroom
     * Generates a urlset for the city/type/N-bedroom search pages,
     * covering the most-searched cities × property types × 1–5 bedrooms.
     * Only includes combinations that have at least one active listing in the database.
     * [created: Task#238] [db-filter: Task#258]
     */
    public function sitemap_adv_search_listings_city_type_bedroom()
    {
        $today = date('Y-m-d');

        $cities = Places::where('type', 'city')
            ->where('stats_disabled', 0)
            ->orderBy('order')
            ->get();

        $types    = ['house', 'townhouse', 'apartment', 'duplex'];
        $bedrooms = [1, 2, 3, 4, 5];

        $cityList   = $cities->pluck('place')->values()->all();
        $placemarks = implode(',', array_fill(0, count($cityList), '?'));

        $rows = DB::select("
            SELECT TRIM(city) AS city, CASE WHEN LOWER(type) IN ('duplex','triplex','fourplex') THEN 'duplex' ELSE LOWER(type) END AS type, bedrooms, COUNT(*) AS cnt
            FROM boards.listings
            WHERE status = 'Active'
              AND `table` = 'mlsr_listings'
              AND type IN ('House', 'Townhouse', 'Apartment', 'Duplex', 'Triplex', 'Fourplex')
              AND city IN ({$placemarks})
              AND bedrooms IN (1, 2, 3, 4, 5)
            GROUP BY TRIM(city), CASE WHEN LOWER(type) IN ('duplex','triplex','fourplex') THEN 'duplex' ELSE LOWER(type) END, bedrooms
            HAVING cnt > 0
        ", $cityList);

        $activeSet = [];
        foreach ($rows as $row) {
            $cityKey = strtolower(trim($row->city));
            $activeSet[$cityKey][$row->type][(int)$row->bedrooms] = true;
        }

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($cities as $city) {
            $citySlug = Helper::enslugPlace($city->place);
            $cityKey  = strtolower(trim($city->place));
            foreach ($types as $type) {
                foreach ($bedrooms as $bed) {
                    if (!isset($activeSet[$cityKey][$type][$bed])) continue;
                    $url = route('adv_search_listings_city_type_feature', [
                        'city'    => $citySlug,
                        'type'    => $type,
                        'feature' => $bed . '-bedroom',
                    ]);
                    $response .= '<url>';
                    $response .= '<loc>' . htmlspecialchars($url, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc>';
                    $response .= '<lastmod>' . $today . '</lastmod>';
                    $response .= '<changefreq>daily</changefreq>';
                    $response .= '<priority>0.7</priority>';
                    $response .= '</url>';
                }
            }
        }

        $response .= '</urlset>';

        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    /**
     * sitemap_adv_search_listings_subarea_type_bedroom
     * Generates a urlset for the subarea/type/N-bedroom search pages,
     * covering active subareas × property types × 1–5 bedrooms.
     * Only includes combinations that have at least one active listing in the database.
     * [created: Task#246] [db-filter: Task#258]
     */
    public function sitemap_adv_search_listings_subarea_type_bedroom()
    {
        $today = date('Y-m-d');

        $cities = Places::where('type', 'city')
            ->where('stats_disabled', 0)
            ->orderBy('order')
            ->get();

        $types    = ['house', 'townhouse', 'apartment', 'duplex'];
        $bedrooms = [1, 2, 3, 4, 5];

        $cityList   = $cities->pluck('place')->values()->all();
        $placemarks = implode(',', array_fill(0, count($cityList), '?'));

        $rows = DB::select("
            SELECT TRIM(city) AS city, TRIM(subarea) AS subarea, CASE WHEN LOWER(type) IN ('duplex','triplex','fourplex') THEN 'duplex' ELSE LOWER(type) END AS type, bedrooms, COUNT(*) AS cnt
            FROM boards.listings
            WHERE status = 'Active'
              AND `table` = 'mlsr_listings'
              AND type IN ('House', 'Townhouse', 'Apartment', 'Duplex', 'Triplex', 'Fourplex')
              AND city IN ({$placemarks})
              AND subarea IS NOT NULL AND subarea != ''
              AND bedrooms IN (1, 2, 3, 4, 5)
            GROUP BY TRIM(city), TRIM(subarea), CASE WHEN LOWER(type) IN ('duplex','triplex','fourplex') THEN 'duplex' ELSE LOWER(type) END, bedrooms
            HAVING cnt > 0
        ", $cityList);

        $activeSet = [];
        foreach ($rows as $row) {
            $cityKey  = strtolower(trim($row->city));
            $saKey    = strtolower(trim($row->subarea));
            $activeSet[$cityKey][$saKey][$row->type][(int)$row->bedrooms] = true;
        }

        $allSubareas = Places::where('type', 'subarea')
            ->where('stats_disabled', 0)
            ->whereIn('city', $cities->pluck('place'))
            ->orderBy('order')
            ->get()
            ->groupBy('city');

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($cities as $city) {
            $citySlug  = Helper::enslugPlace($city->place);
            $cityKey   = strtolower($city->place);
            $subareas  = $allSubareas[$city->place] ?? collect();

            foreach ($subareas as $sa) {
                $saSlug  = Helper::enslugPlace($sa->place);
                $saKey   = strtolower($sa->place);
                foreach ($types as $type) {
                    foreach ($bedrooms as $bed) {
                        if (!isset($activeSet[$cityKey][$saKey][$type][$bed])) continue;
                        $url = route('adv_search_listings_feature', [
                            'city'    => $citySlug,
                            'subarea' => $saSlug,
                            'type'    => $type,
                            'feature' => $bed . '-bedroom',
                        ]);
                        $response .= '<url>';
                        $response .= '<loc>' . htmlspecialchars($url, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc>';
                        $response .= '<lastmod>' . $today . '</lastmod>';
                        $response .= '<changefreq>daily</changefreq>';
                        $response .= '<priority>0.6</priority>';
                        $response .= '</url>';
                    }
                }
            }
        }

        $response .= '</urlset>';

        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    /**
     * sitemap_search_listings_city_type
     * Generates a urlset for the city-only and city/type search listing pages:
     *   /search-listings/{city}
     *   /search-listings/{city}/{type}
     * Only includes cities and city/type pairs with at least one active listing.
     * [created: Task#240] [db-filter: Task#258] [duplex/triplex/fourplex: Task#449]
     */
    public function sitemap_search_listings_city_type()
    {
        $today = date('Y-m-d');

        $cities = Places::where('type', 'city')
            ->where('stats_disabled', 0)
            ->orderBy('order')
            ->get();

        $types = ['house', 'townhouse', 'apartment', 'duplex', 'triplex', 'fourplex'];

        $cityList   = $cities->pluck('place')->values()->all();
        $placemarks = implode(',', array_fill(0, count($cityList), '?'));

        $rows = DB::select("
            SELECT TRIM(city) AS city, LOWER(type) AS type, COUNT(*) AS cnt
            FROM boards.listings
            WHERE status = 'Active'
              AND `table` = 'mlsr_listings'
              AND type IN ('House', 'Townhouse', 'Apartment', 'Duplex', 'Triplex', 'Fourplex')
              AND city IN ({$placemarks})
            GROUP BY TRIM(city), LOWER(type)
            HAVING cnt > 0
        ", $cityList);

        $cityTypeActive  = [];
        $citiesWithAny   = [];
        foreach ($rows as $row) {
            $cityKey = strtolower(trim($row->city));
            $cityTypeActive[$cityKey][$row->type] = true;
            $citiesWithAny[$cityKey]              = true;
        }

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($cities as $city) {
            $citySlug = Helper::enslugPlace($city->place);
            $cityKey  = strtolower(trim($city->place));

            if (!isset($citiesWithAny[$cityKey])) continue;

            $response .= '<url>';
            $response .= '<loc>https://www.bccondosandhomes.com/search-listings/' . $citySlug . '</loc>';
            $response .= '<lastmod>' . $today . '</lastmod>';
            $response .= '<changefreq>daily</changefreq>';
            $response .= '<priority>0.8</priority>';
            $response .= '</url>';

            foreach ($types as $type) {
                if (!isset($cityTypeActive[$cityKey][$type])) continue;
                $response .= '<url>';
                $response .= '<loc>https://www.bccondosandhomes.com/search-listings/' . $citySlug . '/' . $type . '</loc>';
                $response .= '<lastmod>' . $today . '</lastmod>';
                $response .= '<changefreq>daily</changefreq>';
                $response .= '<priority>0.7</priority>';
                $response .= '</url>';
            }
        }

        $response .= '</urlset>';

        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }
    /**
     * sitemap_bedroom_landing_pages
     * Generates a urlset for /{N}-bedroom-{city-slug}-for-sale-{subarea-slug} pages.
     * Only includes city/subarea/bedroom combos with at least 5 active listings.
     * Covers 1–4 bedrooms. [created: Task#381]
     */
    public function sitemap_bedroom_landing_pages()
    {
        $today = date('Y-m-d');

        $cities = Places::where('type', 'city')
            ->where('stats_disabled', 0)
            ->orderBy('order')
            ->get();

        $bedrooms = [1, 2, 3, 4];

        $cityList   = $cities->pluck('place')->values()->all();
        $placemarks = implode(',', array_fill(0, count($cityList), '?'));

        $rows = DB::select("
            SELECT TRIM(city) AS city, TRIM(subarea) AS subarea, bedrooms, COUNT(*) AS cnt
            FROM boards.listings
            WHERE status = 'Active'
              AND `table` = 'mlsr_listings'
              AND city IN ({$placemarks})
              AND subarea IS NOT NULL AND subarea != ''
              AND bedrooms IN (1, 2, 3, 4)
            GROUP BY TRIM(city), TRIM(subarea), bedrooms
            HAVING cnt >= 5
        ", $cityList);

        $activeSet = [];
        foreach ($rows as $row) {
            $cityKey = strtolower(trim($row->city));
            $saKey   = strtolower(trim($row->subarea));
            $activeSet[$cityKey][$saKey][(int)$row->bedrooms] = true;
        }

        $allSubareas = Places::where('type', 'subarea')
            ->where('stats_disabled', 0)
            ->whereIn('city', $cities->pluck('place'))
            ->orderBy('order')
            ->get()
            ->groupBy('city');

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $urlCount = 0;
        $urlLimit = 49000;

        foreach ($cities as $city) {
            $citySlug = Helper::enslugPlace($city->place);
            $cityKey  = strtolower(trim($city->place));
            $subareas = $allSubareas[$city->place] ?? collect();

            foreach ($subareas as $sa) {
                $saSlug = Helper::enslugPlace($sa->place);
                $saKey  = strtolower(trim($sa->place));

                foreach ($bedrooms as $bed) {
                    if (!isset($activeSet[$cityKey][$saKey][$bed])) continue;
                    if ($urlCount >= $urlLimit) break 3;

                    $url = route('for_sale_listings_beds_subarea', [
                        'beds'    => $bed,
                        'slug'    => $citySlug,
                        'subarea' => $saSlug,
                    ]);

                    $response .= '<url>';
                    $response .= '<loc>' . htmlspecialchars($url, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc>';
                    $response .= '<lastmod>' . $today . '</lastmod>';
                    $response .= '<changefreq>weekly</changefreq>';
                    $response .= '<priority>0.6</priority>';
                    $response .= '</url>';
                    $urlCount++;
                }
            }
        }

        $response .= '</urlset>';

        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    public function seoUrl($string)
    {
        //Lower case everything
        $string = strtolower($string);
        //Make alphanumeric (removes all other characters)
        $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
        //Clean up multiple dashes or whitespaces
        $string = preg_replace("/[\s-]+/", " ", $string);
        //Convert whitespaces and underscore to dash
        $string = preg_replace("/[\s_]/", "-", $string);
        return $string;
    }

    /**
     * sitemap_filtered_search_pages
     * Generates a urlset for FilteredSearch SEO landing pages:
     *   /{city}-condos-for-sale             — hub pages
     *   /{N}-bedroom-condos-for-sale-{loc}  — bedroom pages (city + subarea level)
     *   /{type}-for-sale-{city}             — type pages
     *   /pet-friendly-condos-{city}         — lifestyle pages
     *   /ev-charging-condos-{city}
     *   /rental-allowed-condos-{city}
     *   /condos-near-{landmark}             — landmark pages
     * Only includes combos with at least 1 active listing.
     */
    public function sitemap_filtered_search_pages()
    {
        $today = date('Y-m-d');
        $base  = 'https://www.bccondosandhomes.com';

        $cities = Places::where('type', 'city')
            ->where('stats_disabled', 0)
            ->orderBy('order')
            ->get();

        $cityNames   = $cities->pluck('place')->values()->all();
        $placemarks  = implode(',', array_fill(0, count($cityNames), '?'));

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $urlCount = 0;
        $urlLimit = 49000;

        $addUrl = function (string $path) use (&$response, &$urlCount, $base, $today) {
            $response .= '<url>';
            $response .= '<loc>' . htmlspecialchars($base . $path, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc>';
            $response .= '<lastmod>' . $today . '</lastmod>';
            $response .= '<changefreq>weekly</changefreq>';
            $response .= '</url>';
            $urlCount++;
        };

        // --- Hub pages (city level) ---
        foreach ($cities as $city) {
            if ($urlCount >= $urlLimit) break;
            $condoCount = DB::table('boards.listings')
                ->where('status', 'Active')
                ->where('table', 'mlsr_listings')
                ->where('city', $city->place)
                ->whereIn('type', ['Apartment', 'Townhouse', 'Duplex', 'Fourplex', 'Triplex'])
                ->count();
            if ($condoCount < 1) continue;

            $citySlug = Helper::enslugPlace($city->place);
            $addUrl("/{$citySlug}-condos-for-sale");
        }

        // --- Bedroom pages ---
        if ($urlCount < $urlLimit) {
            $bedRows = DB::select("
                SELECT TRIM(city) AS city, TRIM(subarea) AS subarea, bedrooms, COUNT(*) AS cnt
                FROM boards.listings
                WHERE status = 'Active'
                  AND `table` = 'mlsr_listings'
                  AND city IN ({$placemarks})
                  AND type IN ('Apartment','Townhouse','Duplex','Fourplex','Triplex','1/2 Duplex')
                  AND bedrooms IN (1,2,3,4)
                GROUP BY TRIM(city), TRIM(subarea), bedrooms
                HAVING cnt >= 1
            ", $cityNames);

            // Index city slugs
            $citySlugMap = [];
            foreach ($cities as $c) {
                $citySlugMap[strtolower(trim($c->place))] = Helper::enslugPlace($c->place);
            }

            // Subareas
            $subareaSlugMap = [];
            $allSubareas = Places::where('type', 'subarea')
                ->where('stats_disabled', 0)
                ->whereIn('city', $cityNames)
                ->get();
            foreach ($allSubareas as $sa) {
                $subareaSlugMap[strtolower(trim($sa->place))] = Helper::enslugPlace($sa->place);
            }

            foreach ($bedRows as $row) {
                if ($urlCount >= $urlLimit) break;
                $cityKey    = strtolower(trim($row->city));
                $saKey      = strtolower(trim($row->subarea ?? ''));
                $citySlug   = $citySlugMap[$cityKey] ?? null;
                if (!$citySlug) continue;

                // City-level bedroom page
                $addUrl("/{$row->bedrooms}-bedroom-condos-for-sale-{$citySlug}");

                // Subarea-level bedroom page
                if ($saKey && isset($subareaSlugMap[$saKey])) {
                    if ($urlCount >= $urlLimit) break;
                    $saSlug = $subareaSlugMap[$saKey];
                    $addUrl("/{$row->bedrooms}-bedroom-condos-for-sale-{$saSlug}");
                }
            }
        }

        // --- Type pages ---
        $typeMap = [
            'townhouses'   => ['Townhouse', 'Duplex', 'Fourplex', 'Triplex', '1/2 Duplex'],
            'condos'       => ['Apartment'],
            'detached'     => ['House'],
            'duplexes'     => ['Duplex', '1/2 Duplex'],
        ];

        foreach ($cities as $city) {
            if ($urlCount >= $urlLimit) break;
            $citySlug = Helper::enslugPlace($city->place);

            foreach ($typeMap as $typeSlug => $dbTypes) {
                if ($urlCount >= $urlLimit) break;
                $tmpl     = implode(',', array_fill(0, count($dbTypes), '?'));
                $cnt = DB::table('boards.listings')
                    ->where('status', 'Active')
                    ->where('table', 'mlsr_listings')
                    ->where('city', $city->place)
                    ->whereIn('type', $dbTypes)
                    ->count();
                if ($cnt < 1) continue;
                $addUrl("/{$typeSlug}-for-sale-{$citySlug}");
            }
        }

        // --- Lifestyle pages ---
        foreach ($cities as $city) {
            if ($urlCount >= $urlLimit) break;
            $citySlug = Helper::enslugPlace($city->place);

            // Pet-friendly: buildings with no_pets=0 or dogs=1 or cats=1
            $petStratoNos = DB::table('mlsr.buildings')
                ->where('city', $city->place)
                ->where(function ($q) { $q->where('no_pets', 0)->orWhere('dogs', 1)->orWhere('cats', 1); })
                ->pluck('strata_no')->filter()->unique()->values()->all();
            if ($petStratoNos) {
                $petCnt = DB::table('boards.listings')
                    ->where('status', 'Active')->where('table', 'mlsr_listings')
                    ->where('city', $city->place)->whereIn('type', ['Apartment', 'Townhouse'])
                    ->whereIn('strata_no', $petStratoNos)->count();
                if ($petCnt > 0) $addUrl("/pet-friendly-condos-{$citySlug}");
            }

            if ($urlCount >= $urlLimit) break;

            // EV charging
            $evStratoNos = DB::table('mlsr.buildings')
                ->where('city', $city->place)
                ->where(function ($q) {
                    $q->where('amenities', 'LIKE', '%EV%')
                      ->orWhere('amenities', 'LIKE', '%charging station%')
                      ->orWhere('amenities', 'LIKE', '%electric vehicle%');
                })
                ->pluck('strata_no')->filter()->unique()->values()->all();
            if ($evStratoNos) {
                $evCnt = DB::table('boards.listings')
                    ->where('status', 'Active')->where('table', 'mlsr_listings')
                    ->where('city', $city->place)->whereIn('type', ['Apartment', 'Townhouse'])
                    ->whereIn('strata_no', $evStratoNos)->count();
                if ($evCnt > 0) $addUrl("/ev-charging-condos-{$citySlug}");
            }

            if ($urlCount >= $urlLimit) break;

            // Rental allowed (identical predicate set as FilteredSearchController::rentalAllowed)
            $rentalStratoNos = DB::table('mlsr.buildings')
                ->where('city', $city->place)
                ->where('bylaw_restrictions', 'NOT LIKE', '%rental restricted%')
                ->where('bylaw_restrictions', 'NOT LIKE', '%rental prohibited%')
                ->where('bylaw_restrictions', 'NOT LIKE', '%no rental%')
                ->where('bylaw_restrictions', 'NOT LIKE', '%rentals not allowed%')
                ->pluck('strata_no')->filter()->unique()->values()->all();
            if ($rentalStratoNos) {
                $rentalCnt = DB::table('boards.listings')
                    ->where('status', 'Active')->where('table', 'mlsr_listings')
                    ->where('city', $city->place)->whereIn('type', ['Apartment', 'Townhouse'])
                    ->whereIn('strata_no', $rentalStratoNos)->count();
                if ($rentalCnt > 0) $addUrl("/rental-allowed-condos-{$citySlug}");
            }
        }

        // --- Landmark pages (only include if listings exist within radius) ---
        foreach (config('landmarks.landmarks', []) as $lmk) {
            if ($urlCount >= $urlLimit) break;

            $lat    = (float)$lmk['lat'];
            $lng    = (float)$lmk['lng'];
            $radius = (float)($lmk['radius_km'] ?? 3);

            $distSql = "(6371 * acos(LEAST(1, cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))))";

            $lmkCnt = DB::table('boards.listings')
                ->where('status', 'Active')
                ->where('table', 'mlsr_listings')
                ->whereIn('type', ['Apartment', 'Townhouse', 'Duplex', 'Fourplex', 'Triplex', '1/2 Duplex'])
                ->whereNotNull('lat')->whereNotNull('lng')
                ->where('lat', '!=', 0)->where('lng', '!=', 0)
                ->whereRaw("{$distSql} <= ?", [$lat, $lng, $lat, $radius])
                ->count();

            if ($lmkCnt < 1) continue;
            $addUrl('/condos-near-' . $lmk['slug']);
        }

        $response .= '</urlset>';

        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200');
    }

    /**
     * sitemap_school_catchments — one URL per school catchment page + one hub per city.
     */
    public function sitemap_school_catchments()
    {
        $schools = \App\Models\School::where('is_public', true)
            ->orderBy('city')
            ->orderBy('name')
            ->get(['id', 'slug', 'city', 'updated_at']);

        $response  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $citiesDone = [];
        foreach ($schools as $school) {
            $citySlug = \App\Helpers\Helper::enslugPlace($school->city ?? '');

            if ($citySlug && !in_array($citySlug, $citiesDone)) {
                $citiesDone[] = $citySlug;
                $response .= '<url>'
                    . '<loc>https://www.bccondosandhomes.com/school-catchments/' . $citySlug . '</loc>'
                    . '<changefreq>weekly</changefreq>'
                    . '<priority>0.6</priority>'
                    . '</url>';
            }

            $lastmod = $school->updated_at ? date('Y-m-d', strtotime($school->updated_at)) : date('Y-m-d');
            $response .= '<url>'
                . '<loc>https://www.bccondosandhomes.com/school-catchment/' . urlencode($school->slug) . '</loc>'
                . '<lastmod>' . $lastmod . '</lastmod>'
                . '<changefreq>daily</changefreq>'
                . '<priority>0.7</priority>'
                . '</url>';
        }

        $response .= '</urlset>';

        return response($response)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=43200')
            ->header('X-Robots-Tag', 'noindex');
    }
}
