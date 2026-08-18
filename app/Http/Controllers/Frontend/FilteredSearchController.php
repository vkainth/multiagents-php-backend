<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Listings;
use App\Models\Buildings;
use App\Models\Places;
use App\Helpers\Helper;

class FilteredSearchController extends Controller
{
    const MIN_LISTINGS = 1;
    const CACHE_TTL    = 3600;

    protected array $typeMap = [
        'townhouses'   => ['Townhouse', 'Duplex', 'Fourplex', 'Triplex', '1/2 Duplex'],
        'condos'       => ['Apartment'],
        'apartments'   => ['Apartment'],
        'detached'     => ['House'],
        'houses'       => ['House'],
        'duplexes'     => ['Duplex', '1/2 Duplex'],
        'multi-family' => ['House', 'Duplex', 'Fourplex', 'Triplex', '1/2 Duplex'],
    ];

    protected array $typeLabels = [
        'townhouses'   => 'Townhouses',
        'condos'       => 'Condos',
        'apartments'   => 'Apartments',
        'detached'     => 'Detached Homes',
        'houses'       => 'Houses',
        'duplexes'     => 'Duplexes',
        'multi-family' => 'Multi-Family Homes',
    ];

    /**
     * Resolve a location slug to city + optional subarea.
     */
    protected function resolveLocation(string $slug): array
    {
        $name = Helper::deslugPlace($slug);

        $cityRecord = Places::where('type', 'city')
            ->where('place', $name)
            ->where('stats_disabled', 0)
            ->first();

        if ($cityRecord) {
            return [
                'city'           => $name,
                'subarea'        => null,
                'location_label' => $cityRecord->label ?? $name,
                'city_slug'      => Helper::enslugPlace($name),
                'subarea_slug'   => null,
                'city_record'    => $cityRecord,
            ];
        }

        $subareaRecord = Places::where('type', 'subarea')
            ->where('place', $name)
            ->where('stats_disabled', 0)
            ->first();

        if ($subareaRecord) {
            $parentCity = Places::where('type', 'city')
                ->where('place', $subareaRecord->city)
                ->first();
            return [
                'city'           => $subareaRecord->city,
                'subarea'        => $name,
                'location_label' => ($subareaRecord->label ?? $name),
                'city_slug'      => Helper::enslugPlace($subareaRecord->city),
                'subarea_slug'   => Helper::enslugPlace($name),
                'city_record'    => $parentCity,
            ];
        }

        return [];
    }

    /**
     * Base active-listings query for a city (and optionally a subarea).
     */
    protected function baseQuery(string $city, ?string $subarea): \Illuminate\Database\Eloquent\Builder
    {
        $q = Listings::with('aphoto')
            ->where('table', 'mlsr_listings')
            ->where('status', 'Active')
            ->where('city', $city);

        if ($subarea) {
            $q->where('subarea', $subarea);
        }

        return $q;
    }

    /**
     * Aggregate count / avg_price / avg_dom from an Eloquent builder.
     */
    protected function computeStats(\Illuminate\Database\Eloquent\Builder $q): array
    {
        $base          = (clone $q)->toBase();
        $base->columns = null;
        $base->orders  = null;
        $base->limit   = null;
        $base->offset  = null;

        // Clear stale bindings for the clauses we just nulled to prevent
        // binding-count mismatches (e.g. orderByRaw adds bindings that would
        // remain even after orders is set to null).
        $base->bindings['order']  = [];
        $base->bindings['select'] = [];

        $row = $base->selectRaw(
            'COUNT(*) as cnt, AVG(listprice_2) as avg_price, AVG(dom) as avg_dom'
        )->first();

        return [
            'count'     => (int)($row->cnt ?? 0),
            'avg_price' => (int)round($row->avg_price ?? 0),
            'avg_dom'   => (int)round($row->avg_dom ?? 0),
        ];
    }

    /**
     * Build the auto-generated market summary paragraph.
     */
    protected function buildSummary(string $locationLabel, array $stats, string $filterPhrase): string
    {
        if (!$stats['count']) {
            return "There are currently no active {$filterPhrase} in {$locationLabel}.";
        }

        $cnt      = number_format($stats['count']);
        $price    = $stats['avg_price'] ? '$' . number_format($stats['avg_price']) : null;
        $dom      = $stats['avg_dom'];
        $singular = $stats['count'] === 1;

        $parts   = [];
        $parts[] = "There " . ($singular ? 'is' : 'are') . " currently <strong>{$cnt} {$filterPhrase}</strong>"
            . " in <strong>{$locationLabel}</strong>.";

        if ($price) {
            $parts[] = "The average asking price is <strong>{$price}</strong>.";
        }
        if ($dom) {
            $parts[] = "Properties are spending an average of <strong>{$dom} days</strong> on the market.";
        }
        $parts[] = "Data is updated daily from MLS® records.";

        return implode(' ', $parts);
    }

    // -------------------------------------------------------------------------
    // Public route handlers
    // -------------------------------------------------------------------------

    /**
     * Bedroom-filtered page.
     * Route: /{beds}-bedroom-condos-for-sale-{location}
     */
    public function bedroom(string $beds, string $location)
    {
        $bedsInt = (int)$beds;
        if ($bedsInt < 1 || $bedsInt > 9) abort(404);

        $loc = $this->resolveLocation($location);
        if (!$loc) abort(404);

        $city    = $loc['city'];
        $subarea = $loc['subarea'];

        $condoTypes = ['Apartment', 'Townhouse', 'Duplex', 'Fourplex', 'Triplex', '1/2 Duplex'];

        $statsKey = 'fsearch_bed_stats_v1_' . $bedsInt . '_' . $location;
        $stats = Cache::remember($statsKey, self::CACHE_TTL, function () use ($bedsInt, $city, $subarea, $condoTypes) {
            return $this->computeStats(
                $this->baseQuery($city, $subarea)
                    ->where('bedrooms', $bedsInt)
                    ->whereIn('type', $condoTypes)
            );
        });

        if ($stats['count'] < self::MIN_LISTINGS) abort(404);

        $listings = $this->baseQuery($city, $subarea)
            ->where('bedrooms', $bedsInt)
            ->whereIn('type', $condoTypes)
            ->orderBy('list_date', 'desc')
            ->paginate(24);

        $bedsLabel = match($bedsInt) {
            1 => 'One-Bedroom', 2 => 'Two-Bedroom',  3 => 'Three-Bedroom',
            4 => 'Four-Bedroom', 5 => 'Five-Bedroom', 6 => 'Six-Bedroom',
            default => "{$bedsInt}-Bedroom"
        };

        $locationLabel = $loc['location_label'];
        $filterPhrase  = "{$bedsInt} bedroom condos for sale";
        $h1            = "{$bedsLabel} Condos for Sale in {$locationLabel}";
        $filterType    = 'bedroom';
        $summary       = $this->buildSummary($locationLabel, $stats, $filterPhrase);

        $relatedBedLinks = [];
        foreach ([1, 2, 3, 4] as $b) {
            if ($b === $bedsInt) continue;
            $cnt = Cache::remember("fsearch_bed_cnt_v1_{$b}_{$location}", self::CACHE_TTL, function () use ($b, $city, $subarea, $condoTypes) {
                return $this->baseQuery($city, $subarea)->where('bedrooms', $b)->whereIn('type', $condoTypes)->count();
            });
            if ($cnt > 0) {
                $relatedBedLinks[] = [
                    'label' => "{$b}-Bedroom Condos (" . number_format($cnt) . ")",
                    'url'   => "/{$b}-bedroom-condos-for-sale-{$location}",
                ];
            }
        }

        $canonicalUrl = "https://www.bccondosandhomes.com/{$beds}-bedroom-condos-for-sale-{$location}";

        return view('frontend.filtered_search', compact(
            'listings', 'stats', 'summary', 'loc', 'h1', 'filterType', 'filterPhrase',
            'relatedBedLinks', 'location', 'bedsInt', 'bedsLabel', 'locationLabel', 'canonicalUrl'
        ));
    }

    /**
     * Property-type filtered page.
     * Route: /{type}-for-sale-{city}
     */
    public function typeCity(string $type, string $city)
    {
        $typeSlug = strtolower($type);
        $dbTypes  = $this->typeMap[$typeSlug] ?? null;
        if (!$dbTypes) abort(404);

        $loc = $this->resolveLocation($city);
        if (!$loc) abort(404);

        $cityName  = $loc['city'];
        $subarea   = $loc['subarea'];
        $typeLabel = $this->typeLabels[$typeSlug] ?? ucwords(str_replace('-', ' ', $typeSlug));

        $statsKey = 'fsearch_type_stats_v1_' . $typeSlug . '_' . $city;
        $stats = Cache::remember($statsKey, self::CACHE_TTL, function () use ($cityName, $subarea, $dbTypes) {
            return $this->computeStats(
                $this->baseQuery($cityName, $subarea)->whereIn('type', $dbTypes)
            );
        });

        if ($stats['count'] < self::MIN_LISTINGS) abort(404);

        $listings = $this->baseQuery($cityName, $subarea)
            ->whereIn('type', $dbTypes)
            ->orderBy('list_date', 'desc')
            ->paginate(24);

        $locationLabel = $loc['location_label'];
        $filterPhrase  = strtolower($typeLabel) . ' for sale';
        $h1            = "{$typeLabel} for Sale in {$locationLabel}";
        $filterType    = 'type';
        $summary       = $this->buildSummary($locationLabel, $stats, $filterPhrase);

        $relatedTypeLinks = [];
        foreach ($this->typeMap as $tSlug => $tTypes) {
            if ($tSlug === $typeSlug) continue;
            $cnt = Cache::remember("fsearch_type_cnt_v1_{$tSlug}_{$city}", self::CACHE_TTL, function () use ($cityName, $subarea, $tTypes) {
                return $this->baseQuery($cityName, $subarea)->whereIn('type', $tTypes)->count();
            });
            if ($cnt > 0) {
                $relatedTypeLinks[] = [
                    'label' => ($this->typeLabels[$tSlug] ?? ucwords(str_replace('-', ' ', $tSlug))) . " (" . number_format($cnt) . ")",
                    'url'   => "/{$tSlug}-for-sale-{$city}",
                ];
            }
        }

        $canonicalUrl = "https://www.bccondosandhomes.com/{$typeSlug}-for-sale-{$city}";

        return view('frontend.filtered_search', compact(
            'listings', 'stats', 'summary', 'loc', 'h1', 'filterType', 'filterPhrase',
            'relatedTypeLinks', 'city', 'typeSlug', 'typeLabel', 'locationLabel', 'canonicalUrl'
        ));
    }

    /**
     * Pet-friendly condos page.
     * Route: /pet-friendly-condos-{city}
     */
    public function petFriendly(string $city)
    {
        return $this->lifestylePage($city, 'pet-friendly', 'Pet-Friendly Condos for Sale', function (string $cityName) {
            return Buildings::where('city', $cityName)
                ->where(function ($q) {
                    $q->where('no_pets', 0)
                      ->orWhere('dogs', 1)
                      ->orWhere('cats', 1);
                })
                ->pluck('strata_no')
                ->filter()
                ->unique()
                ->values()
                ->all();
        });
    }

    /**
     * EV charging condos page.
     * Route: /ev-charging-condos-{city}
     */
    public function evCharging(string $city)
    {
        return $this->lifestylePage($city, 'ev-charging', 'Condos with EV Charging for Sale', function (string $cityName) {
            return Buildings::where('city', $cityName)
                ->where(function ($q) {
                    $q->where('amenities', 'LIKE', '%EV%')
                      ->orWhere('amenities', 'LIKE', '%electric vehicle%')
                      ->orWhere('amenities', 'LIKE', '%charging station%')
                      ->orWhere('amenities', 'LIKE', '%EV Charging%');
                })
                ->pluck('strata_no')
                ->filter()
                ->unique()
                ->values()
                ->all();
        });
    }

    /**
     * Rental-allowed condos page.
     * Route: /rental-allowed-condos-{city}
     */
    public function rentalAllowed(string $city)
    {
        return $this->lifestylePage($city, 'rental-allowed', 'Rental-Allowed Condos for Sale', function (string $cityName) {
            return Buildings::where('city', $cityName)
                ->where('bylaw_restrictions', 'NOT LIKE', '%rental restricted%')
                ->where('bylaw_restrictions', 'NOT LIKE', '%rental prohibited%')
                ->where('bylaw_restrictions', 'NOT LIKE', '%no rental%')
                ->where('bylaw_restrictions', 'NOT LIKE', '%rentals not allowed%')
                ->pluck('strata_no')
                ->filter()
                ->unique()
                ->values()
                ->all();
        });
    }

    /**
     * Shared lifestyle page handler.
     */
    protected function lifestylePage(string $citySlug, string $filterKey, string $filterTitle, callable $stratoNoFn)
    {
        $loc = $this->resolveLocation($citySlug);
        if (!$loc) abort(404);

        $cityName      = $loc['city'];
        $locationLabel = $loc['location_label'];
        $subarea       = $loc['subarea'];

        $strataKey = 'fsearch_life_strata_v1_' . $filterKey . '_' . $citySlug;
        $stratoNos = Cache::remember($strataKey, self::CACHE_TTL, fn () => $stratoNoFn($cityName));

        if (empty($stratoNos)) abort(404);

        $q = Listings::with('aphoto')
            ->where('table', 'mlsr_listings')
            ->where('status', 'Active')
            ->where('city', $cityName)
            ->whereIn('type', ['Apartment', 'Townhouse'])
            ->whereIn('strata_no', $stratoNos);

        if ($subarea) $q->where('subarea', $subarea);

        $statsKey = 'fsearch_life_stats_v1_' . $filterKey . '_' . $citySlug;
        $stats = Cache::remember($statsKey, self::CACHE_TTL, fn () => $this->computeStats($q));

        if ($stats['count'] < self::MIN_LISTINGS) abort(404);

        $listings     = (clone $q)->orderBy('list_date', 'desc')->paginate(24);
        $filterPhrase = strtolower($filterTitle);
        $h1           = "{$filterTitle} in {$locationLabel}";
        $filterType   = 'lifestyle';
        $summary      = $this->buildSummary($locationLabel, $stats, $filterPhrase);

        $lifestyleLinks = [];
        foreach ([
            'pet-friendly'   => 'Pet-Friendly Condos',
            'ev-charging'    => 'Condos with EV Charging',
            'rental-allowed' => 'Rental-Allowed Condos',
        ] as $lf => $lbl) {
            if ($lf === $filterKey) continue;
            $lifestyleLinks[] = [
                'label' => $lbl,
                'url'   => "/{$lf}-condos-{$citySlug}",
            ];
        }

        $canonicalUrl = "https://www.bccondosandhomes.com/{$filterKey}-condos-{$citySlug}";

        return view('frontend.filtered_search', compact(
            'listings', 'stats', 'summary', 'loc', 'h1', 'filterType', 'filterPhrase',
            'filterTitle', 'locationLabel', 'lifestyleLinks', 'citySlug', 'canonicalUrl'
        ));
    }

    /**
     * Near-landmark page.
     * Route: /condos-near-{landmark}
     */
    public function landmark(string $slug)
    {
        $landmarks = config('landmarks.landmarks', []);
        $lmk       = collect($landmarks)->firstWhere('slug', $slug);
        if (!$lmk) abort(404);

        $lat    = (float)$lmk['lat'];
        $lng    = (float)$lmk['lng'];
        $radius = (float)($lmk['radius_km'] ?? 3);

        $distSql = "(6371 * acos(LEAST(1, cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))))";

        $q = Listings::with('aphoto')
            ->where('table', 'mlsr_listings')
            ->where('status', 'Active')
            ->whereIn('type', ['Apartment', 'Townhouse', 'Duplex', 'Fourplex', 'Triplex', '1/2 Duplex'])
            ->whereNotNull('lat')->whereNotNull('lng')
            ->where('lat', '!=', 0)->where('lng', '!=', 0)
            ->whereRaw("{$distSql} <= ?", [$lat, $lng, $lat, $radius])
            ->orderByRaw("{$distSql} ASC", [$lat, $lng, $lat]);

        $statsKey = 'fsearch_lmk_stats_v1_' . $slug;
        $stats = Cache::remember($statsKey, self::CACHE_TTL, fn () => $this->computeStats($q));

        if ($stats['count'] < self::MIN_LISTINGS) abort(404);

        $listings      = (clone $q)->paginate(24);
        $locationLabel = $lmk['name'];
        $h1            = "Condos for Sale Near {$locationLabel}";
        $filterType    = 'landmark';
        $filterPhrase  = "condos near {$locationLabel}";

        $summaryParts = ["There are currently <strong>" . number_format($stats['count']) . " condos for sale</strong>"
            . " within <strong>{$radius} km</strong> of <strong>{$locationLabel}</strong>."];
        if ($stats['avg_price']) {
            $summaryParts[] = "Average asking price: <strong>\$" . number_format($stats['avg_price']) . "</strong>.";
        }
        if ($stats['avg_dom']) {
            $summaryParts[] = "Average days on market: <strong>{$stats['avg_dom']}</strong>.";
        }
        $summary = implode(' ', $summaryParts);

        $nearbyLandmarks = collect($landmarks)
            ->where('slug', '!=', $slug)
            ->map(fn ($l) => ['label' => $l['name'], 'url' => '/condos-near-' . $l['slug']])
            ->take(5)
            ->values()
            ->all();

        $loc = [
            'city'           => $lmk['city'] ?? null,
            'subarea'        => null,
            'location_label' => $locationLabel,
            'city_slug'      => $lmk['city'] ? Helper::enslugPlace($lmk['city']) : null,
            'subarea_slug'   => null,
        ];

        $canonicalUrl = "https://www.bccondosandhomes.com/condos-near-{$slug}";

        return view('frontend.filtered_search', compact(
            'listings', 'stats', 'summary', 'loc', 'h1', 'filterType', 'filterPhrase',
            'locationLabel', 'lmk', 'nearbyLandmarks', 'canonicalUrl'
        ));
    }

    /**
     * City-level condos hub page.
     * Route: /{city}-condos-for-sale
     */
    public function hub(string $city)
    {
        $loc = $this->resolveLocation($city);
        if (!$loc || $loc['subarea']) abort(404);

        $cityName      = $loc['city'];
        $locationLabel = $loc['location_label'];
        $citySlug      = $loc['city_slug'];

        $cacheKey = 'fsearch_hub_v1_' . $city;
        $hubData  = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cityName, $city, $citySlug) {
            $condoTypes = ['Apartment', 'Townhouse', 'Duplex', 'Fourplex', 'Triplex', '1/2 Duplex'];

            $totalActive = Listings::where('table', 'mlsr_listings')
                ->where('status', 'Active')
                ->where('city', $cityName)
                ->whereIn('type', $condoTypes)
                ->count();

            if (!$totalActive) return null;

            $avgPrice = (int)round(
                (float)(Listings::where('table', 'mlsr_listings')
                    ->where('status', 'Active')->where('city', $cityName)
                    ->whereIn('type', $condoTypes)->where('listprice_2', '>', 0)
                    ->avg('listprice_2') ?? 0)
            );

            $bedroomVariants = [];
            foreach ([1, 2, 3, 4] as $b) {
                $cnt = Listings::where('table', 'mlsr_listings')->where('status', 'Active')
                    ->where('city', $cityName)->where('bedrooms', $b)->whereIn('type', $condoTypes)->count();
                if ($cnt > 0) $bedroomVariants[$b] = $cnt;
            }

            $typeVariants = [];
            $typeMap = [
                'townhouses'   => ['Townhouse', 'Duplex', 'Fourplex', 'Triplex', '1/2 Duplex'],
                'condos'       => ['Apartment'],
                'detached'     => ['House'],
                'duplexes'     => ['Duplex', '1/2 Duplex'],
                'multi-family' => ['House', 'Duplex', 'Fourplex', 'Triplex', '1/2 Duplex'],
            ];
            $typeLabels = [
                'townhouses' => 'Townhouses', 'condos' => 'Condos', 'detached' => 'Detached Homes',
                'duplexes' => 'Duplexes', 'multi-family' => 'Multi-Family',
            ];
            foreach ($typeMap as $tSlug => $tTypes) {
                $cnt = Listings::where('table', 'mlsr_listings')->where('status', 'Active')
                    ->where('city', $cityName)->whereIn('type', $tTypes)->count();
                if ($cnt > 0) $typeVariants[$tSlug] = ['count' => $cnt, 'label' => $typeLabels[$tSlug] ?? ucwords($tSlug)];
            }

            $lifestyleVariants = [];
            $petStratoNos = Buildings::where('city', $cityName)
                ->where(function ($q) { $q->where('no_pets', 0)->orWhere('dogs', 1)->orWhere('cats', 1); })
                ->pluck('strata_no')->filter()->unique()->values()->all();
            if ($petStratoNos) {
                $cnt = Listings::where('table', 'mlsr_listings')->where('status', 'Active')
                    ->where('city', $cityName)->whereIn('type', ['Apartment', 'Townhouse'])->whereIn('strata_no', $petStratoNos)->count();
                if ($cnt > 0) $lifestyleVariants['pet-friendly'] = ['label' => 'Pet-Friendly Condos', 'count' => $cnt, 'url' => "/pet-friendly-condos-{$city}"];
            }

            $evStratoNos = Buildings::where('city', $cityName)
                ->where(function ($q) {
                    $q->where('amenities', 'LIKE', '%EV%')
                      ->orWhere('amenities', 'LIKE', '%charging station%')
                      ->orWhere('amenities', 'LIKE', '%electric vehicle%');
                })
                ->pluck('strata_no')->filter()->unique()->values()->all();
            if ($evStratoNos) {
                $cnt = Listings::where('table', 'mlsr_listings')->where('status', 'Active')
                    ->where('city', $cityName)->whereIn('type', ['Apartment', 'Townhouse'])->whereIn('strata_no', $evStratoNos)->count();
                if ($cnt > 0) $lifestyleVariants['ev-charging'] = ['label' => 'Condos with EV Charging', 'count' => $cnt, 'url' => "/ev-charging-condos-{$city}"];
            }

            $rentalStratoNos = Buildings::where('city', $cityName)
                ->where('bylaw_restrictions', 'NOT LIKE', '%rental restricted%')
                ->where('bylaw_restrictions', 'NOT LIKE', '%rental prohibited%')
                ->where('bylaw_restrictions', 'NOT LIKE', '%no rental%')
                ->where('bylaw_restrictions', 'NOT LIKE', '%rentals not allowed%')
                ->pluck('strata_no')->filter()->unique()->values()->all();
            if ($rentalStratoNos) {
                $cnt = Listings::where('table', 'mlsr_listings')->where('status', 'Active')
                    ->where('city', $cityName)->whereIn('type', ['Apartment', 'Townhouse'])->whereIn('strata_no', $rentalStratoNos)->count();
                if ($cnt > 0) $lifestyleVariants['rental-allowed'] = ['label' => 'Rental-Allowed Condos', 'count' => $cnt, 'url' => "/rental-allowed-condos-{$city}"];
            }

            $allLandmarks = collect(config('landmarks.landmarks', []))
                ->filter(fn ($l) => strtolower($l['city'] ?? '') === strtolower($cityName))
                ->values()->all();

            return compact('totalActive', 'avgPrice', 'bedroomVariants', 'typeVariants', 'lifestyleVariants', 'allLandmarks');
        });

        if (!$hubData) abort(404);

        $filterType   = 'hub';
        $h1           = "Condos for Sale in {$locationLabel}";
        $canonicalUrl = "https://www.bccondosandhomes.com/{$city}-condos-for-sale";

        return view('frontend.filtered_search', array_merge(
            compact('loc', 'h1', 'filterType', 'locationLabel', 'citySlug', 'city', 'canonicalUrl'),
            $hubData
        ));
    }
}
