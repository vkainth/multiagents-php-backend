<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolCatchment;
use App\Models\Listings;
use App\Helpers\Helper;
use App\Helpers\AgentContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class SchoolCatchmentController extends Controller
{
    /**
     * Hub page: /school-catchments/{citySlug}
     * Lists all public schools in a city with active listing counts.
     * On agent-scoped sites, only schools within the agent's territories are shown.
     */
    public function index(string $citySlug)
    {
        $city  = Helper::deslugPlace($citySlug);
        $agent = AgentContext::current();

        // On agent sites, enforce territory: the requested city must belong to this agent.
        if ($agent && !$this->cityInAgentTerritory($city, $agent)) {
            abort(404);
        }

        $cacheKey = 'school_catchments_hub_v2_' . $citySlug . ($agent ? '_agent_' . $agent->id : '');

        $data = Cache::remember($cacheKey, 3600, function () use ($city, $citySlug, $agent) {

            $schools = School::where('is_public', true)
                ->whereRaw('LOWER(city) = LOWER(?)', [$city])
                ->orderBy('school_type')
                ->orderBy('name')
                ->get();

            if ($schools->isEmpty()) {
                return null;
            }

            $schoolsWithCounts = $schools->map(function ($school) use ($agent) {
                $catchment = $school->catchments()->first();
                $count = $this->activeListingCount($catchment, $school, $agent);
                return [
                    'school'    => $school,
                    'count'     => $count,
                    'catchment' => $catchment,
                ];
            });

            return compact('schoolsWithCounts', 'city', 'citySlug');
        });

        if (!$data) {
            abort(404);
        }

        return view('frontend.school_catchments_hub', $data);
    }

    /**
     * Detail page: /school-catchment/{schoolSlug}
     * Shows one school's catchment with active + recent sold listings.
     * On agent-scoped sites, schools outside the agent's territory return 404.
     */
    public function show(string $schoolSlug)
    {
        $school = School::where('slug', $schoolSlug)->where('is_public', true)->firstOrFail();

        $agent = AgentContext::current();

        // Enforce agent territory: school's city must be covered by this agent.
        if ($agent && !$this->cityInAgentTerritory($school->city, $agent)) {
            abort(404);
        }

        $catchment = $school->catchments()->first();

        $activeListings = $this->queryListings('Active', $catchment, $school, $agent, 'list_date');
        $soldListings   = $this->queryListings('Sold',   $catchment, $school, $agent, 'sold_date', '6 Month');

        $user     = Auth::user();
        $isGuest  = !$user;
        $citySlug = Helper::enslugPlace($school->city ?? '');

        $avgListPrice = $activeListings->avg('listprice_2');
        $avgSoldPrice = $soldListings->avg('soldprice_2');
        $avgSoldPsf   = $soldListings->filter(fn($l) => ($l->livingarea_2 ?? 0) > 0)
            ->avg(fn($l) => $l->soldprice_2 / $l->livingarea_2);

        return view('frontend.school_catchment', compact(
            'school',
            'catchment',
            'activeListings',
            'soldListings',
            'user',
            'isGuest',
            'citySlug',
            'avgListPrice',
            'avgSoldPrice',
            'avgSoldPsf',
            'agent'
        ));
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /**
     * Returns true if the given city name belongs to any of the agent's territories.
     * When no agent is active (main site), always returns true.
     */
    protected function cityInAgentTerritory(string $city, $agent): bool
    {
        if (!$agent) {
            return true;
        }

        $territories = $agent->territories()->get();

        if ($territories->isEmpty()) {
            return true; // Agent with no territories set = unrestricted
        }

        foreach ($territories as $territory) {
            if (strcasecmp(trim($city), trim($territory->city)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Count active listings inside a school catchment.
     * Uses bbox as an index pre-filter, then ST_Contains for polygon accuracy.
     */
    protected function activeListingCount(?SchoolCatchment $catchment, School $school, $agent): int
    {
        try {
            $q = Listings::withoutGlobalScopes()->where('status', 'Active');
            $this->applyLocationFilter($q, $catchment, $school);
            if ($agent) {
                $q->inAgentTerritory($agent);
            }
            return $q->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Fetch listings inside a school catchment for a given status.
     * Uses bbox pre-filter + ST_Contains for polygon accuracy.
     */
    protected function queryListings(
        string $status,
        ?SchoolCatchment $catchment,
        School $school,
        $agent,
        string $orderCol,
        ?string $soldInterval = null
    ) {
        try {
            $q = Listings::withoutGlobalScopes()->where('status', $status);
            $this->applyLocationFilter($q, $catchment, $school);

            if ($agent) {
                $q->inAgentTerritory($agent);
            }

            if ($soldInterval) {
                // Use Carbon to compute the cutoff date — avoids MySQL-only DATE_SUB()
                [$amount, $unit] = explode(' ', trim($soldInterval), 2);
                $method = 'sub' . ucfirst(strtolower($unit)) . 's';
                $cutoff = Carbon::now()->{$method}((int)$amount)->toDateString();
                $q->where('sold_date', '>=', $cutoff);
            }

            return $q->orderBy($orderCol, 'DESC')->limit(50)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Apply location filter to a Listings query builder.
     *
     * Strategy:
     *   1. Always apply a bbox WHERE (uses lat/lng index — fast pre-filter).
     *   2. When a catchment polygon is available, add ST_Contains for accuracy.
     *      This eliminates homes that are inside the bounding box but outside
     *      the actual catchment boundary (important for irregular polygons).
     *   3. Falls back to bbox-only when no polygon is stored.
     */
    protected function applyLocationFilter(Builder $q, ?SchoolCatchment $catchment, School $school): void
    {
        $bbox = $this->getCatchmentBbox($catchment, $school);

        // Step 1: bbox pre-filter (leverages DB index on lat/lng)
        $q->whereBetween('lat', [$bbox['min_lat'], $bbox['max_lat']])
          ->whereBetween('lng', [$bbox['min_lng'], $bbox['max_lng']]);

        // Step 2: polygon refinement — ST_Contains(catchment, POINT(lng, lat))
        if ($catchment && $catchment->polygon_geojson) {
            $q->whereRaw(
                'ST_Contains(ST_GeomFromGeoJSON(?), POINT(lng, lat))',
                [$catchment->polygon_geojson]
            );
        }
    }

    /**
     * Extract lat/lng bounding box from the catchment polygon GeoJSON.
     * Used as an index-friendly pre-filter before ST_Contains.
     * Falls back to a radius box around the school coordinates when no polygon is stored.
     */
    protected function getCatchmentBbox(?SchoolCatchment $catchment, School $school): array
    {
        if ($catchment && $catchment->polygon_geojson) {
            $geojson = json_decode($catchment->polygon_geojson, true);
            $type    = $geojson['type'] ?? '';

            if ($type === 'Polygon') {
                $ring = $geojson['coordinates'][0] ?? [];
            } elseif ($type === 'MultiPolygon') {
                $ring = $geojson['coordinates'][0][0] ?? [];
            } else {
                $ring = [];
            }

            if (!empty($ring)) {
                $lats = array_column($ring, 1);
                $lngs = array_column($ring, 0);
                return [
                    'min_lat' => min($lats), 'max_lat' => max($lats),
                    'min_lng' => min($lngs), 'max_lng' => max($lngs),
                ];
            }
        }

        // Fallback: approximate radius box (~1.8 km elementary, ~4 km secondary)
        $isSecondary = in_array($school->school_type, ['Secondary', 'Middle']);
        $dLat = $isSecondary ? 0.036 : 0.016;
        $dLng = $isSecondary ? 0.046 : 0.020;
        $lat  = (float)($school->latitude  ?? 49.05);
        $lng  = (float)($school->longitude ?? -122.80);

        return [
            'min_lat' => $lat - $dLat,
            'max_lat' => $lat + $dLat,
            'min_lng' => $lng - $dLng,
            'max_lng' => $lng + $dLng,
        ];
    }
}
