<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentSettings;
use App\Models\AgentTestimonial;
use App\Models\Buildings;
use App\Models\Listings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentDataController extends Controller
{
    public function bySlug(string $slug): JsonResponse
    {
        $agent = Agent::with(['settings'])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (! $agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        return response()->json($this->format($agent));
    }

    public function byDomain(string $domain): JsonResponse
    {
        $domain = strtolower(trim($domain));
        if (str_starts_with($domain, 'www.')) {
            $domain = substr($domain, 4);
        }

        $settings = AgentSettings::where('custom_domain', $domain)->first();
        if (! $settings) {
            return response()->json(['error' => 'Agent not found for domain'], 404);
        }

        $agent = Agent::with(['settings'])
            ->where('id', $settings->agent_id)
            ->where('status', 'active')
            ->first();

        if (! $agent) {
            return response()->json(['error' => 'Agent inactive'], 404);
        }

        return response()->json($this->format($agent));
    }

    /**
     * Featured listings (active, 9 max) for agent's territory cities.
     * Accepts optional query params: status, type, min_price, max_price, beds, page.
     */
    public function featuredListings(string $slug, Request $req): JsonResponse
    {
        $agent = Agent::with(['territories'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        if (empty($cities)) {
            return response()->json([]);
        }

        $status   = $req->query('status', 'Active');
        $type     = $req->query('type');
        $minPrice = (int) $req->query('min_price', 0);
        $maxPrice = (int) $req->query('max_price', 0);
        $beds     = (int) $req->query('beds', 0);
        $page     = max(1, (int) $req->query('page', 1));
        $limit    = min(30, max(1, (int) $req->query('limit', 9)));

        $q = Listings::withoutGlobalScopes()
            ->whereIn('city', $cities)
            ->where('status', $status)
            ->select([
                'sysid', 'listingid', 'streetaddress', 'city', 'subarea',
                'status', 'listprice', 'soldprice', 'bedrooms', 'bathstotal',
                'livingarea', 'mainpicurl', 'slug', 'type', 'home_style',
                'list_date', 'dom',
            ]);

        if ($type)     $q->where('type', $type);
        if ($minPrice) $q->where('listprice', '>=', $minPrice);
        if ($maxPrice) $q->where('listprice', '<=', $maxPrice);
        if ($beds)     $q->where('bedrooms', '>=', $beds);

        $listings = $q->orderBy('sysid', 'desc')
            ->forPage($page, $limit)
            ->get();

        return response()->json($listings->map(fn ($l) => [
            'id'         => $l->sysid,
            'mls_no'     => $l->listingid,
            'address'    => $l->streetaddress,
            'city'       => $l->city,
            'subarea'    => $l->subarea,
            'status'     => $l->status,
            'list_price' => (int) $l->listprice,
            'sold_price' => $l->soldprice ? (int) $l->soldprice : null,
            'beds'       => (int) $l->bedrooms,
            'baths'      => (float) $l->bathstotal,
            'sqft'       => (int) $l->livingarea,
            'photo_url'  => $l->mainpicurl ?: null,
            'type'       => $l->type,
            'style'      => $l->home_style,
            'slug'       => $l->slug,
            'dom'        => ($l->status === 'Active' && !empty($l->list_date) && $l->list_date !== '0000-00-00') ? (int) floor((time() - strtotime($l->list_date)) / 86400) : ($l->dom !== null ? (int) $l->dom : null),
        ]));
    }

    /**
     * Single listing detail by slug.
     */
    public function listingDetail(string $slug, string $listingSlug): JsonResponse
    {
        $listing = Listings::withoutGlobalScopes()
            ->where('slug', $listingSlug)
            ->orWhere('listingid', $listingSlug)
            ->first();

        if (! $listing) return response()->json(['error' => 'Listing not found'], 404);

        $photos = [];
        try {
            $photos = $listing->photos()
                ->limit(60)
                ->get()
                ->map(fn ($p) => $this->photoUrl($p))
                ->filter()
                ->values()
                ->toArray();
        } catch (\Throwable $e) {}
        if (empty($photos) && $listing->mainpicurl) {
            $photos = [$listing->mainpicurl];
        }

        return response()->json([
            'id'            => $listing->sysid,
            'mls_no'        => $listing->listingid,
            'address'       => $listing->streetaddress,
            'city'          => $listing->city,
            'subarea'       => $listing->subarea,
            'status'        => $listing->status,
            'list_price'    => (int) $listing->listprice_2,
            'sold_price'    => $listing->soldprice ? (int) $listing->soldprice : null,
            'beds'          => (int) $listing->bedrooms,
            'baths'         => (float) $listing->bathstotal,
            'sqft'          => (int) $listing->livingarea,
            'photo_url'     => $listing->mainpicurl ?: null,
            'photos'        => $photos,
            'type'          => $listing->type,
            'style'         => $listing->home_style,
            'slug'          => $listing->slug,
            'description'   => $listing->publicremarks ?? $listing->remarks ?? null,
            'year_built'    => isset($listing->yearbuilt) ? (int) $listing->yearbuilt : null,
            'strata_fee'    => isset($listing->stratafeemonth) ? (float) $listing->stratafeemonth : null,
            'latitude'      => isset($listing->latitude) ? (float) $listing->latitude : null,
            'longitude'     => isset($listing->longitude) ? (float) $listing->longitude : null,
            'dom'           => null,
        ]);
    }

    /**
     * Featured buildings (6 max) for agent's territory cities.
     */
    public function featuredBuildings(string $slug): JsonResponse
    {
        $agent = Agent::with(['territories'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        if (empty($cities)) return response()->json([]);

        $fbSettingsRow = \Illuminate\Support\Facades\DB::table('agent_settings')
            ->where('agent_id', $agent->id)->first();
        $fbWhitelist = ($fbSettingsRow && $fbSettingsRow->subarea_whitelist)
            ? json_decode($fbSettingsRow->subarea_whitelist, true) : null;

        $buildings = Buildings::where(function ($query) use ($cities, $fbWhitelist) {
                foreach ($cities as $city) {
                    if ($city === 'Surrey' && !empty($fbWhitelist)) {
                        $query->orWhere(function ($q2) use ($city, $fbWhitelist) {
                            $q2->where('city', $city)->whereIn('subarea', $fbWhitelist);
                        });
                    } else {
                        $query->orWhere('city', $city);
                    }
                }
            })
            ->select([
                'id', 'name', 'slug', 'city', 'subarea',
                'yearbuilt', 'units_in_development', 'strata_no',
            ])
            ->orderByDesc('yearbuilt')
            ->get();

        return response()->json($buildings->map(function (Buildings $b) {
            $photoUrl = null;
            try { $photoUrl = $b->main_image() ?: null; } catch (\Throwable $e) {}

            $activeCount = 0;
            try {
                $activeCount = Listings::withoutGlobalScopes()
                    ->where('status', 'Active')
                    ->where('strata_no', $b->strata_no)
                    ->count();
            } catch (\Throwable $e) {}

            $minMax = Listings::withoutGlobalScopes()
                ->where('status', 'Active')
                ->where('strata_no', $b->strata_no)
                ->selectRaw('MIN(listprice) as min_price, MAX(listprice) as max_price')
                ->first();

            return [
                'id'              => (string) $b->id,
                'name'            => $b->name,
                'slug'            => $b->slug,
                'city'            => $b->city,
                'subarea'         => $b->subarea,
                'year_built'      => $b->yearbuilt ? (int) $b->yearbuilt : null,
                'units'           => $b->units_in_development ? (int) $b->units_in_development : null,
                'photo_url'       => $photoUrl,
                'min_price'       => $minMax?->min_price ? (int) $minMax->min_price : null,
                'max_price'       => $minMax?->max_price ? (int) $minMax->max_price : null,
                'active_listings' => $activeCount,
            ];
        }));
    }

    /**
     * Single building detail by slug.
     */
    public function buildingDetail(string $slug, string $buildingSlug): JsonResponse
    {
        $building = Buildings::where('slug', $buildingSlug)->first();
        if (! $building) return response()->json(['error' => 'Building not found'], 404);

        $photoUrl = null;
        try { $photoUrl = $building->main_image() ?: null; } catch (\Throwable $e) {}

        $photos = [];
        if ($photoUrl) $photos[] = $photoUrl;

        $activeListings = Listings::withoutGlobalScopes()
            ->where('status', 'Active')
            ->where('strata_no', $building->strata_no)
            ->select([
                'sysid', 'listingid', 'streetaddress', 'status', 'listprice_2',
                'bedrooms', 'bathstotal', 'livingarea_2', 'mainpicurl', 'slug', 'type',
                'maintenance', 'taxamount', 'taxyear', 'reoffice', 'list_date', 'dom',
            ])
            ->orderBy('listprice_2')
            ->limit(20)
            ->get()
            ->map(fn ($l) => [
                'id'         => $l->sysid,
                'mls_no'     => $l->listingid,
                'address'    => $l->streetaddress,
                'status'     => $l->status,
                'list_price' => (int) $l->listprice_2,
                'sold_price' => null,
                'beds'       => (int) $l->bedrooms,
                'baths'      => (float) $l->bathstotal,
                'sqft'       => (int) $l->livingarea_2,
                'photo_url'  => $l->mainpicurl ?: null,
                'type'       => $l->type,
                'style'      => null,
                'slug'       => $l->slug,
                'dom'        => (!empty($l->list_date) && $l->list_date !== '0000-00-00') ? (int) floor((time() - strtotime($l->list_date)) / 86400) : ($l->dom !== null ? (int) $l->dom : null),
                'list_date'  => $l->list_date ?: null,
                'strata_fee' => $l->maintenance !== null ? (float) $l->maintenance : null,
                'tax_amount' => $l->taxamount !== null ? (float) $l->taxamount : null,
                'tax_year'   => $l->taxyear ?: null,
                'listed_by'  => $l->reoffice ?: null,
            ]);

        $recentSold = Listings::withoutGlobalScopes()
            ->where('status', 'Sold')
            ->where('strata_no', $building->strata_no)
            ->whereNotNull('soldprice_2')
            ->select([
                'sysid', 'listingid', 'streetaddress', 'status', 'listprice_2', 'soldprice_2',
                'bedrooms', 'bathstotal', 'livingarea_2', 'slug',
                'maintenance', 'taxamount', 'taxyear', 'reoffice', 'sold_date', 'dom',
            ])
            ->orderByDesc('sold_date')
            ->limit(20)
            ->get()
            ->map(fn ($l) => [
                'id'         => $l->sysid,
                'mls_no'     => $l->listingid,
                'address'    => $l->streetaddress,
                'status'     => $l->status,
                'list_price' => (int) $l->listprice_2,
                'sold_price' => (int) $l->soldprice_2,
                'beds'       => (int) $l->bedrooms,
                'baths'      => (float) $l->bathstotal,
                'sqft'       => (int) $l->livingarea_2,
                'photo_url'  => null,
                'type'       => null,
                'style'      => null,
                'slug'       => $l->slug,
                'dom'        => $l->dom !== null ? (int) $l->dom : null,
                'sold_date'  => $l->sold_date ?: null,
                'strata_fee' => $l->maintenance !== null ? (float) $l->maintenance : null,
                'tax_amount' => $l->taxamount !== null ? (float) $l->taxamount : null,
                'tax_year'   => $l->taxyear ?: null,
                'listed_by'  => $l->reoffice ?: null,
            ]);

        $amenitiesRaw = $building->amenities ?? '';
        $amenities = array_filter(array_map('trim', explode(',', $amenitiesRaw)));

        return response()->json([
            'id'             => (string) $building->id,
            'name'           => $building->name,
            'slug'           => $building->slug,
            'city'           => $building->city,
            'subarea'        => $building->subarea,
            'year_built'     => $building->yearbuilt ? (int) $building->yearbuilt : null,
            'units'          => $building->units_in_development ? (int) $building->units_in_development : null,
            'strata_no'      => $building->strata_no,
            'photo_url'      => $photoUrl,
            'photos'         => $photos,
            'amenities'      => array_values($amenities),
            'no_pets'        => (bool) ($building->no_pets ?? false),
            'dogs_allowed'   => (bool) ($building->dogs ?? false),
            'cats_allowed'   => (bool) ($building->cats ?? false),
            'address'        => trim(($building->street_no ?? '') . ' ' . ($building->street_name ?? '') . ' ' . ($building->street_type ?? '') . ', ' . ($building->city ?? '')),
            'active_listings' => $activeListings,
            'recent_sold'    => $recentSold,
        ]);
    }

    /**
     * Market stats for agent's territory.
     */
    public function marketStats(string $slug): JsonResponse
    {
        $agent = Agent::with(['territories'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        if (empty($cities)) return response()->json([
            'active_count' => 0, 'avg_list_price' => null,
            'sold_last_30_days' => 0, 'avg_sold_price' => null, 'avg_dom' => null,
        ]);

        try {
            $activeStats = Listings::withoutGlobalScopes()
                ->whereIn('city', $cities)
                ->where('status', 'Active')
                ->selectRaw('COUNT(*) as active_count, AVG(listprice) as avg_list_price')
                ->first();
        } catch (\Exception $e) {
            $activeStats = null;
        }

        try {
            $soldStats = Listings::withoutGlobalScopes()
                ->whereIn('city', $cities)
                ->where('status', 'Sold')
                ->where('sold_date', '>=', now()->subDays(30)->format('Y-m-d'))
                ->selectRaw('COUNT(*) as sold_count, AVG(soldprice) as avg_sold_price')
                ->first();
        } catch (\Exception $e) {
            $soldStats = null;
        }

        return response()->json([
            'active_count'      => (int) ($activeStats?->active_count ?? 0),
            'avg_list_price'    => $activeStats?->avg_list_price ? (int) round($activeStats->avg_list_price) : null,
            'sold_last_30_days' => (int) ($soldStats?->sold_count ?? 0),
            'avg_sold_price'    => $soldStats?->avg_sold_price ? (int) round($soldStats->avg_sold_price) : null,
            'avg_dom'           => null,
        ]);
    }

    /**
     * Agent testimonials (from agent_testimonials table).
     */
    public function testimonials(string $slug): JsonResponse
    {
        $agent = Agent::where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $rows = AgentTestimonial::where('agent_id', $agent->id)
            ->where('visible', true)
            ->orderByDesc('date')
            ->limit(8)
            ->get();

        return response()->json($rows->map(fn ($t) => [
            'id'     => $t->id,
            'name'   => $t->author_name,
            'text'   => $t->body,
            'rating' => (int) $t->rating,
            'source' => $t->source ?? 'Google',
            'date'   => $t->date,
        ]));
    }

    /**
     * Accept a contact / lead form submission.
     */
    public function contact(string $slug, Request $req): JsonResponse
    {
        $agent = Agent::with(['settings'])->where('slug', $slug)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $data = $req->validate([
            'name'             => 'required|string|max:120',
            'phone'            => 'nullable|string|max:30',
            'email'            => 'nullable|email|max:120',
            'message'          => 'nullable|string|max:2000',
            'listing_address'  => 'nullable|string|max:200',
            'agree'            => 'nullable|boolean',
        ]);

        $notifyEmail = $agent->settings?->notification_email ?? $agent->email;

        if ($notifyEmail) {
            try {
                \Illuminate\Support\Facades\Mail::raw(
                    "New lead from southsurreywhiterock.com\n\n"
                    . "Name: {$data['name']}\n"
                    . "Phone: {$data['phone']}\n"
                    . "Email: " . ($data['email'] ?? 'not provided') . "\n"
                    . "Message: " . ($data['message'] ?? 'none') . "\n"
                    . "Property: " . ($data['listing_address'] ?? 'none') . "\n",
                    fn ($m) => $m->to($notifyEmail)->subject("New Lead — {$data['name']}")
                );
            } catch (\Throwable $e) {
                // Log but don't fail the request
                \Illuminate\Support\Facades\Log::warning('Contact mail failed', ['err' => $e->getMessage()]);
            }
        }

        return response()->json(['success' => true]);
    }

    private function photoUrl($p): ?string
    {
        if (empty($p->directory) && empty($p->name)) return null;
        $path = str_replace('images', '', ($p->directory ?? '') . ($p->name ?? ''));
        return 'https://media.pixilinkserver.com/' . ltrim($path, '/') . '?w=1200';
    }

    private function format(Agent $agent): array
    {
        $settings = $agent->settings;

        return [
            'id'               => $agent->id,
            'slug'             => $agent->slug,
            'name'             => $agent->name,
            'brokerage'        => $agent->brokerage,
            'phone'            => $agent->phone,
            'email'            => $agent->email,
            'bio'              => $agent->bio,
            'photo_path'       => $agent->photo_path,
            'logo_path'        => $agent->logo_path,
            'license_number'   => $agent->license_number,
            'theme_slug'       => $agent->theme_slug,
            'theme_color'      => $agent->theme_color,
            'primary_bg_color' => $agent->primary_bg_color ?? '#1a1a1a',
            'status'           => $agent->status,
            'settings'         => $settings ? [
                'custom_domain'        => $settings->custom_domain,
                'ga4_id'               => $settings->ga4_id,
                'fb_pixel_id'          => $settings->fb_pixel_id,
                'intro_video_url'      => $settings->intro_video_url,
                'social_links'         => $settings->social_links,
                'featured_listing_ids' => $settings->featured_listing_ids,
                'notification_email'   => $settings->notification_email,
                'notification_phone'   => $settings->notification_phone,
                'fub_enabled'          => (bool) $settings->fub_enabled,
                'lead_routing'         => $settings->lead_routing,
            ] : null,
        ];
    }

    /**
     * Admin: territory buildings for a specific agent (up to 200 per page).
     * Protected by VerifyAdminSecret middleware (X-Admin-Secret header).
     */
    public function adminAgentBuildings(Request $req, int $agentId): JsonResponse
    {
        $agent = Agent::with(['territories'])->where('id', $agentId)->first();
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $cities = $agent->territories->pluck('city')->filter()->unique()->values()->toArray();
        if (empty($cities)) return response()->json(['buildings' => [], 'total' => 0, 'page' => 1, 'limit' => 100]);

        $limit = min(500, max(1, (int) $req->query('limit', 100)));
        $page  = max(1, (int) $req->query('page', 1));

        $abSettingsRow = \Illuminate\Support\Facades\DB::table('agent_settings')
            ->where('agent_id', $agent->id)->first();
        $abWhitelist = ($abSettingsRow && $abSettingsRow->subarea_whitelist)
            ? json_decode($abSettingsRow->subarea_whitelist, true) : null;

        $q = Buildings::where(function ($query) use ($cities, $abWhitelist) {
                foreach ($cities as $city) {
                    if ($city === 'Surrey' && !empty($abWhitelist)) {
                        $query->orWhere(function ($q2) use ($city, $abWhitelist) {
                            $q2->where('city', $city)->whereIn('subarea', $abWhitelist);
                        });
                    } else {
                        $query->orWhere('city', $city);
                    }
                }
            })
            ->select([
                'id', 'name', 'slug', 'city', 'subarea',
                'yearbuilt', 'units_in_development', 'strata_no', 'levels',
            ]);

        $total = (clone $q)->count();

        $buildings = $q->orderByDesc('yearbuilt')
            ->forPage($page, $limit)
            ->get();

        return response()->json([
            'buildings' => $buildings->map(function (Buildings $b) {
                $photoUrl = null;
                try { $photoUrl = $b->main_image() ?: null; } catch (\Throwable $e) {}

                $activeCount = 0;
                $min = null;
                $max = null;

                if ($b->strata_no && trim($b->strata_no) !== '' && (int) ($b->yearbuilt ?? 0) <= (int) date('Y')) {
                    try {
                        $agg = Listings::withoutGlobalScopes()
                            ->where('status', 'Active')
                            ->where('strata_no', $b->strata_no)
                            ->selectRaw('COUNT(*) as c, MIN(listprice_2) as mn, MAX(listprice_2) as mx')
                            ->first();
                        $activeCount = (int) ($agg->c ?? 0);
                        $min = ($agg->mn ?? null) ? (int) $agg->mn : null;
                        $max = ($agg->mx ?? null) ? (int) $agg->mx : null;
                    } catch (\Throwable $e) {}
                }

                return [
                    'id'              => (string) $b->id,
                    'name'            => $b->name,
                    'slug'            => $b->slug,
                    'city'            => $b->city,
                    'subarea'         => $b->subarea,
                    'year_built'      => $b->yearbuilt ? (int) $b->yearbuilt : null,
                    'units'           => $b->units_in_development ? (int) $b->units_in_development : null,
                    'levels'          => $b->levels ? (int) $b->levels : null,
                    'strata_no'       => $b->strata_no ?: null,
                    'photo_url'       => $photoUrl,
                    'min_price'       => $min,
                    'max_price'       => $max,
                    'active_listings' => $activeCount,
                ];
            })->values(),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

}
