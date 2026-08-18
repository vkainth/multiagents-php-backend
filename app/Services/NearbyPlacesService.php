<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\School;
use App\Models\SchoolCatchment;
use App\Models\TransitStop;

class NearbyPlacesService
{
    const DEFAULT_RADIUS = 1500;
    const CACHE_TTL_DAYS = 7;
    const WALK_SPEED_MPM = 80;
    const MAX_POIS_PER_TAB = 6;

    private static array $googleTypeMap = [
        'schools'   => ['school'],
        'parks'     => ['park', 'gym'],
        'transit'   => ['transit_station', 'bus_station', 'subway_station', 'light_rail_station'],
        'groceries' => ['grocery_or_supermarket', 'supermarket', 'convenience_store', 'cafe'],
    ];

    private static array $overpassTagMap = [
        'schools'   => ['["amenity"~"school|college|university|kindergarten"]'],
        'parks'     => ['["leisure"~"park|playground|sports_centre|fitness_centre|recreation_ground"]'],
        'transit'   => ['["public_transport"~"stop_position|platform"]', '["highway"="bus_stop"]'],
        'groceries' => ['["shop"~"supermarket|convenience|greengrocer|bakery|deli"]', '["amenity"~"cafe|fast_food"]'],
    ];

    /**
     * Fetch all POI tabs + optional Walk Score, cached by slug + radius.
     *
     * @param string $cacheSlug  building slug or neighbourhood slug for cache key
     */
    public function getAll(float $lat, float $lng, int $radius = self::DEFAULT_RADIUS, string $cacheSlug = ''): array
    {
        $cacheKey = $this->cacheKey($lat, $lng, $radius, $cacheSlug);

        return Cache::remember($cacheKey, now()->addDays(self::CACHE_TTL_DAYS), function () use ($lat, $lng, $radius) {
            $results = [];
            foreach (array_keys(self::$googleTypeMap) as $tab) {
                $results[$tab] = $this->fetchTab($tab, $lat, $lng, $radius);
            }
            return $results;
        });
    }

    /**
     * Fetch Walk Score data (score + transit + bike) for a lat/lng.
     * Returns null if API key is not configured or request fails.
     */
    public function getWalkScore(float $lat, float $lng, string $address = ''): ?array
    {
        $apiKey = config('services.walkscore.api_key');
        if (!$apiKey) {
            return null;
        }

        $cacheKey = 'walkscore_' . round($lat, 4) . '_' . round($lng, 4);

        return Cache::remember($cacheKey, now()->addDays(self::CACHE_TTL_DAYS), function () use ($lat, $lng, $address, $apiKey) {
            try {
                $resp = Http::timeout(5)->get('https://api.walkscore.com/score', [
                    'format'   => 'json',
                    'address'  => $address ?: "{$lat},{$lng}",
                    'lat'      => $lat,
                    'lon'      => $lng,
                    'transit'  => 1,
                    'bike'     => 1,
                    'wsapikey' => $apiKey,
                ]);

                if (!$resp->successful()) {
                    return null;
                }
                $data = $resp->json();
                if (($data['status'] ?? 0) !== 1) {
                    return null;
                }
                return [
                    'walk'    => [
                        'score'       => $data['walkscore']    ?? null,
                        'description' => $data['description']  ?? null,
                    ],
                    'transit' => [
                        'score'       => $data['transit']['score']       ?? null,
                        'description' => $data['transit']['description'] ?? null,
                    ],
                    'bike'    => [
                        'score'       => $data['bike']['score']          ?? null,
                        'description' => $data['bike']['description']    ?? null,
                    ],
                ];
            } catch (\Throwable $e) {
                Log::warning('WalkScore fetch error', ['err' => $e->getMessage()]);
                return null;
            }
        });
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function fetchTab(string $tab, float $lat, float $lng, int $radius): array
    {
        $apiKey = config('services.google_places.api_key');
        $pois   = [];

        if ($tab === 'schools') {
            $catchmentSchools = $this->fetchCatchmentSchools($lat, $lng);
            $pois             = $catchmentSchools;
        }

        if ($tab === 'transit') {
            $fromDb = $this->fetchTransitFromDb($lat, $lng, $radius);
            if (!empty($fromDb)) {
                return $this->topN(array_merge($pois, $fromDb));
            }
        }

        $apiPois = [];
        if ($apiKey) {
            $apiPois = $this->fetchFromGoogle($tab, $lat, $lng, $radius, $apiKey);
        }
        if (empty($apiPois)) {
            $apiPois = $this->fetchFromOverpass($tab, $lat, $lng, $radius);
        }

        $combined = array_merge($pois, $apiPois);
        return $this->topN($combined, $pois);
    }

    /**
     * Query school_catchments to find elementary/secondary catchment schools
     * that contain this lat/lng point, then return them as pinned results.
     */
    private function fetchCatchmentSchools(float $lat, float $lng): array
    {
        $result = [];
        try {
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                // Use spatial query on polygon_geom.
                // CONCAT builds the WKT string so the float params bind correctly
                // (placeholders inside string literals are not parameterized by PDO).
                $rows = DB::select("
                    SELECT s.name, s.latitude, s.longitude, sc.level
                    FROM school_catchments sc
                    JOIN schools s ON s.id = sc.school_id
                    WHERE sc.polygon_geom IS NOT NULL
                      AND sc.level IN ('Elementary', 'Secondary')
                      AND ST_Contains(sc.polygon_geom, ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326))
                    LIMIT 4
                ", [$lng, $lat]);
            } else {
                // SQLite fallback — use bounding-box on school lat/lng (no spatial index)
                $delta = 0.05;
                $rows  = DB::table('school_catchments as sc')
                    ->join('schools as s', 's.id', '=', 'sc.school_id')
                    ->whereIn('sc.level', ['Elementary', 'Secondary'])
                    ->whereBetween('s.latitude',  [$lat - $delta, $lat + $delta])
                    ->whereBetween('s.longitude', [$lng - $delta, $lng + $delta])
                    ->select('s.name', 's.latitude', 's.longitude', 'sc.level')
                    ->limit(4)
                    ->get()
                    ->toArray();
            }

            foreach ($rows as $row) {
                $sLat  = (float)$row->latitude;
                $sLng  = (float)$row->longitude;
                $dist  = ($sLat && $sLng) ? $this->haversine($lat, $lng, $sLat, $sLng) : 0;
                $result[] = [
                    'name'      => $row->name . ' (' . $row->level . ')',
                    'distance'  => (int) $dist,
                    'walk_time' => $this->walkTime($dist),
                    'type'      => 'catchment',
                    'pinned'    => true,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('NearbyPlaces catchment query error', ['err' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Query transit_stops table (populated by translink:import-gtfs) for stops
     * within radius, including route numbers.
     */
    private function fetchTransitFromDb(float $lat, float $lng, int $radius): array
    {
        try {
            if (!$this->transitStopsTableHasData()) {
                return [];
            }
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                // CONCAT builds the WKT string so float params bind correctly.
                $rows = DB::select("
                    SELECT stop_name, latitude, longitude, routes,
                           ST_Distance_Sphere(location, ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326)) AS dist_m
                    FROM transit_stops
                    HAVING dist_m <= ?
                    ORDER BY dist_m
                    LIMIT ?
                ", [$lng, $lat, $radius, self::MAX_POIS_PER_TAB]);
            } else {
                $delta = $radius / 111000;
                $rows  = DB::table('transit_stops')
                    ->whereBetween('latitude',  [$lat - $delta, $lat + $delta])
                    ->whereBetween('longitude', [$lng - $delta, $lng + $delta])
                    ->select('stop_name', 'latitude', 'longitude', 'routes')
                    ->limit(self::MAX_POIS_PER_TAB * 3)
                    ->get()
                    ->toArray();

                usort($rows, fn($a, $b) =>
                    $this->haversine($lat, $lng, (float)$a->latitude, (float)$a->longitude)
                    <=>
                    $this->haversine($lat, $lng, (float)$b->latitude, (float)$b->longitude)
                );
                $rows = array_slice($rows, 0, self::MAX_POIS_PER_TAB);
            }

            $result = [];
            foreach ($rows as $row) {
                $sLat   = (float)$row->latitude;
                $sLng   = (float)$row->longitude;
                $dist   = isset($row->dist_m) ? (float)$row->dist_m : $this->haversine($lat, $lng, $sLat, $sLng);
                $routes = [];
                if (!empty($row->routes)) {
                    $routes = is_array($row->routes) ? $row->routes : json_decode($row->routes, true) ?? [];
                }
                $routeLabel = !empty($routes) ? implode(', ', array_slice($routes, 0, 6)) : '';
                $name = $row->stop_name . ($routeLabel ? " [{$routeLabel}]" : '');
                $result[] = [
                    'name'      => $name,
                    'distance'  => (int) $dist,
                    'walk_time' => $this->walkTime($dist),
                    'type'      => 'transit_db',
                    'routes'    => $routes,
                ];
            }
            return $result;
        } catch (\Throwable $e) {
            Log::warning('NearbyPlaces transit DB error', ['err' => $e->getMessage()]);
            return [];
        }
    }

    private function transitStopsTableHasData(): bool
    {
        try {
            return DB::table('transit_stops')->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function fetchFromGoogle(string $tab, float $lat, float $lng, int $radius, string $apiKey): array
    {
        $types     = self::$googleTypeMap[$tab];
        $collected = [];

        foreach ($types as $type) {
            try {
                $resp = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
                    'location' => "{$lat},{$lng}",
                    'radius'   => $radius,
                    'type'     => $type,
                    'key'      => $apiKey,
                ]);

                if (!$resp->successful()) {
                    continue;
                }
                $data = $resp->json();
                if (($data['status'] ?? '') === 'REQUEST_DENIED') {
                    break;
                }
                foreach ($data['results'] ?? [] as $place) {
                    $pLat = $place['geometry']['location']['lat'] ?? null;
                    $pLng = $place['geometry']['location']['lng'] ?? null;
                    if (!$pLat || !$pLng) {
                        continue;
                    }
                    $dist = $this->haversine($lat, $lng, $pLat, $pLng);
                    if ($dist > $radius) {
                        continue;
                    }
                    $collected[] = [
                        'name'      => $place['name'] ?? 'Unknown',
                        'distance'  => (int) $dist,
                        'walk_time' => $this->walkTime($dist),
                        'type'      => $type,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('NearbyPlaces Google error', ['tab' => $tab, 'type' => $type, 'err' => $e->getMessage()]);
            }

            if (count($collected) >= self::MAX_POIS_PER_TAB * 2) {
                break;
            }
        }

        return $collected;
    }

    private function fetchFromOverpass(string $tab, float $lat, float $lng, int $radius): array
    {
        $tags      = self::$overpassTagMap[$tab];
        $collected = [];

        foreach ($tags as $tag) {
            try {
                $query = "[out:json][timeout:8];(node{$tag}(around:{$radius},{$lat},{$lng}););out body 20;";
                $resp  = Http::timeout(10)
                    ->withHeaders(['User-Agent' => 'BCCondosAndHomes/1.0'])
                    ->post('https://overpass-api.de/api/interpreter', ['data' => $query]);

                if (!$resp->successful()) {
                    continue;
                }
                foreach ($resp->json()['elements'] ?? [] as $el) {
                    $eLat = (float)($el['lat'] ?? 0);
                    $eLng = (float)($el['lon'] ?? 0);
                    if (!$eLat || !$eLng) {
                        continue;
                    }
                    $name = $el['tags']['name'] ?? ($el['tags']['operator'] ?? null);
                    if (!$name) {
                        continue;
                    }
                    $dist = $this->haversine($lat, $lng, $eLat, $eLng);
                    $collected[] = [
                        'name'      => $name,
                        'distance'  => (int) $dist,
                        'walk_time' => $this->walkTime($dist),
                        'type'      => $tab,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('NearbyPlaces Overpass error', ['tab' => $tab, 'err' => $e->getMessage()]);
            }

            if (count($collected) >= self::MAX_POIS_PER_TAB * 2) {
                break;
            }
        }

        return $collected;
    }

    /**
     * Sort combined list; keep pinned items at the top, then sort by distance.
     * Returns at most MAX_POIS_PER_TAB entries.
     */
    private function topN(array $pois, array $pinned = []): array
    {
        $pinnedItems  = array_filter($pois, fn($p) => !empty($p['pinned']));
        $regularItems = array_filter($pois, fn($p) =>  empty($p['pinned']));

        usort($regularItems, fn($a, $b) => $a['distance'] <=> $b['distance']);

        $combined = array_values(array_merge(array_values($pinnedItems), array_values($regularItems)));
        return array_slice($combined, 0, self::MAX_POIS_PER_TAB);
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R  = 6371000;
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lng2 - $lng1);
        $a  = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function walkTime(float $distMeters): int
    {
        return (int) max(1, round($distMeters / self::WALK_SPEED_MPM));
    }

    /**
     * Cache key includes slug when provided (per spec: cached per building/neighbourhood slug).
     */
    private function cacheKey(float $lat, float $lng, int $radius, string $cacheSlug = ''): string
    {
        if ($cacheSlug) {
            return "nearby_pois_slug_{$cacheSlug}_{$radius}";
        }
        $rLat = round($lat, 4);
        $rLng = round($lng, 4);
        return "nearby_pois_{$rLat}_{$rLng}_{$radius}";
    }

    public function flushCache(float $lat, float $lng, int $radius = self::DEFAULT_RADIUS, string $cacheSlug = ''): void
    {
        Cache::forget($this->cacheKey($lat, $lng, $radius, $cacheSlug));
    }
}
