<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Agent;
use App\Models\AgentFeature;
use App\Models\AgentLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminInternalController extends Controller
{
    private const TERRITORY_CITIES = [
        'Vancouver', 'Burnaby', 'Richmond', 'Surrey', 'Coquitlam', 'Port Coquitlam',
        'Port Moody', 'New Westminster', 'North Vancouver', 'West Vancouver', 'Langley',
        'Abbotsford', 'Chilliwack', 'Mission', 'Maple Ridge', 'Pitt Meadows',
        'Delta', 'White Rock', 'South Surrey White Rock', 'Cloverdale',
        'Squamish', 'Whistler',
    ];

    private const CITY_SUBAREAS = [
        'Vancouver' => [
            'Downtown VW','Kitsilano','Yaletown','Mount Pleasant VE','West End VW',
            'Collingwood VE','Fairview VW','University VW','Grandview Woodland',
            'False Creek','Cambie','South Marine','Marpole','Renfrew VE',
            'Fraser VE','Coal Harbour','Knight','Dunbar','Killarney VE',
            'Point Grey','Victoria VE','Hastings','Main','Kerrisdale',
            'South Vancouver','Renfrew Heights','South Granville','Strathcona',
            'Downtown VE','Hastings Sunrise','Quilchena','Shaughnessy','South Cambie',
            'Oakridge VW','Fraserview VE','Champlain Heights','S.W. Marine',
            'Mount Pleasant VW','Arbutus','Southlands',
        ],
        'Burnaby' => [
            'Metrotown','Brentwood Park','Highgate','Edmonds BE','South Slope',
            'Simon Fraser Univer.','Forest Glen BS','Sullivan Heights','Government Road',
            'Capitol Hill BN','Central BN','Central Park BS','Vancouver Heights',
            'East Burnaby','Willingdon Heights','Sperling-Duthie','Parkcrest',
            'Burnaby Lake','The Crest','Upper Deer Lake','Montecito','Cariboo',
            'Burnaby Hospital','Simon Fraser Hills','Forest Hills BN','Westridge BN',
            'Oaklands','Greentree Village','Garden Village','Suncrest',
            'Deer Lake Place','Big Bend','Buckingham Heights','Deer Lake',
        ],
        'Richmond' => [
            'Brighouse','West Cambie','Brighouse South','McLennan North','Steveston South',
            'Riverdale RI','Steveston North','Granville','Ironwood','Hamilton RI',
            'Woodwards','Broadmoor','Boyd Park','Seafair','Saunders',
            'East Cambie','Bridgeport RI','South Arm','Terra Nova','Garden City',
            'Lackner','Quilchena RI','McNair','Steveston Village','Westwind',
            'East Richmond','McLennan','Sea Island','Gilmore','Neilsen Grove',
        ],
        'Surrey' => [
            'Whalley','Cloverdale BC','Fleetwood Tynehead','Grandview Surrey','Clayton',
            'Guildford','King George Corridor','Sullivan Station','East Newton','West Newton',
            'Queen Mary Park Surrey','Sunnyside Park Surrey','Panorama Ridge',
            'Bear Creek Green Timbers','Fraser Heights','Crescent Bch Ocean Pk.',
            'Morgan Creek','Bolivar Heights','Elgin Chantrell','Pacific Douglas',
            'Cedar Hills','Royal Heights','Bridgeview','White Rock','Hazelmere',
            'Port Kells','Serpentine','Scottsdale','Grandview Heights','Ocean Park Surrey',
            'Semiahmoo',
        ],
        'Coquitlam' => [
            'Coquitlam West','North Coquitlam','Burke Mountain','Westwood Plateau',
            'Central Coquitlam','Maillardville','New Horizons','Coquitlam East',
            'Canyon Springs','Ranch Park','Eagle Ridge CQ','Cape Horn','Scott Creek',
            'Harbour Chines','Upper Eagle Ridge','Meadow Brook','River Springs',
            'Chineside','Harbour Place','Hockaday','Park Ridge Estates',
        ],
        'Port Coquitlam' => [
            'Central Pt Coquitlam','Glenwood PQ','Riverwood','Citadel PQ','Mary Hill',
            'Lincoln Park PQ','Oxford Heights','Woodland Acres PQ','Lower Mary Hill',
            'Birchland Manor',
        ],
        'Port Moody' => [
            'Port Moody Centre','North Shore Pt Moody','College Park PM','Heritage Woods PM',
            'Heritage Mountain','Barber Street','Glenayre','Anmore','Mountain Meadows',
        ],
        'New Westminster' => [
            'Uptown NW','Downtown NW','Queensborough','Quay','Fraserview NW',
            'Sapperton','GlenBrooke North','The Heights NW','West End NW',
            'Queens Park','Moody Park','Connaught Heights',
        ],
        'North Vancouver' => [
            'Lower Lonsdale','Central Lonsdale','Lynn Valley','Lynnmour','Pemberton NV',
            'Upper Lonsdale','Roche Point','Canyon Heights NV','Mosquito Creek',
            'Edgemont','Northlands','Westlynn','Deep Cove','Blueridge NV',
            'Boulevard','Seymour NV','Norgate','Indian River','Queensbury',
            'Upper Delbrook','Harbourside','Pemberton Heights','Calverhall',
            'Forest Hills NV','Capilano NV',
        ],
        'West Vancouver' => [
            'Ambleside','Dundarave','British Properties','Park Royal','Caulfeild',
            'Cypress Park Estates','Eagle Harbour','Horseshoe Bay WV','Sentinel Hill',
            'Panorama Village','Glenmore','Cedardale','Chartwell','Bayridge',
            'Queens','Altamont','Upper Caulfeild','Gleneagles','Westmount WV',
            'Whitby Estates','West Bay','Whytecliff','Olde Caulfeild','Rockridge',
        ],
        'Langley' => [
            'Willoughby Heights','Langley City','Walnut Grove','Aldergrove Langley',
            'Brookswood Langley','Murrayville','Salmon River','Fort Langley',
            'Campbell Valley','Otter District','County Line Glen Valley',
        ],
        'Abbotsford' => [
            'Central Abbotsford','Abbotsford West','Abbotsford East','Poplar',
            'Aberdeen','Bradner','Matsqui','Sumas Mountain','Sumas Prairie',
        ],
        'Chilliwack' => [
            'Promontory','Vedder S Watson-Promontory','Sardis South','Chilliwack E Young-Yale',
            'Chilliwack W Young-Well','Chilliwack Proper East','Chilliwack Proper West',
            'Eastern Hillsides','Sardis East Vedder Rd','Garrison Crossing',
            'Sardis West Vedder Rd','Fairfield Island','Chilliwack Mountain',
            'Chilliwack Downtown','Little Mountain','Ryder Lake',
        ],
        'Mission' => [
            'Mission BC','Hatzic','Mission-West','Lake Errock','Dewdney Deroche',
            'Stave Falls','Durieu','Hemlock',
        ],
        'Maple Ridge' => [
            'East Central','West Central','Cottonwood MR','Albion','Silver Valley',
            'Southwest Maple Ridge','Northwest Maple Ridge','Thornhill MR',
            'Websters Corners','Whonnock',
        ],
        'Pitt Meadows' => [
            'Central Meadows','South Meadows','Mid Meadows','North Meadows PI','West Meadows',
        ],
        'Delta' => [
            'Nordel','Scottsdale','Annieville','Sunshine Hills Woods','Tsawwassen Central',
            'Neilsen Grove','Hawthorne','Cliff Drive','Ladner Elementary','Pebble Hill',
            'Beach Grove','Delta Manor','Holly','Tsawwassen North','Boundary Beach',
            'Tsawwassen East','English Bluff','Port Guichon','Ladner Rural',
        ],
        'White Rock' => [
            'White Rock','King George Corridor','Grandview Surrey','Sunnyside Park Surrey',
            'Pacific Douglas','Crescent Bch Ocean Pk.','Morgan Creek','Elgin Chantrell','Hazelmere',
        ],
        'South Surrey White Rock' => [
            'White Rock','King George Corridor','Grandview Surrey','Sunnyside Park Surrey',
            'Pacific Douglas','Crescent Bch Ocean Pk.','Morgan Creek','Elgin Chantrell',
            'Hazelmere','Ocean Park Surrey','Grandview Heights','Semiahmoo',
        ],
        'Cloverdale' => ['Cloverdale','Cloverdale BC'],
        'Squamish' => [
            'Downtown SQ','Garibaldi Estates','Tantalus','Garibaldi Highlands','Valleycliffe',
            'Brackendale','Northyards','Dentville','Brennan Center','Hospital Hill','Plateau',
        ],
        'Whistler' => [
            'Whistler Village','Benchlands','Whistler Creek','Nordic','Alpine Meadows',
            'Bayshores','Blueberry Hill','Whistler Cay Heights','Emerald Estates',
            'Alta Vista','Green Lake Estates','Brio','White Gold','Cheakamus Crossing','Rainbow',
        ],
    ];

    /**
     * POST /api-internal/admin/auth
     * Validate email + password against admins table, return admin info.
     */
    public function auth(Request $req): JsonResponse
    {
        $data = $req->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $data['email'])->first();

        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $admin->update(['last_login_at' => now()]);

        return response()->json([
            'id'    => $admin->id,
            'name'  => $admin->name,
            'email' => $admin->email,
        ]);
    }

    /**
     * GET /api-internal/admin/agents
     */
    public function agentsList(): JsonResponse
    {
        $agents = Agent::with(['settings', 'territories'])
            ->orderBy('name')
            ->get()
            ->map(fn ($a) => $this->formatAgent($a, false));

        return response()->json($agents);
    }

    /**
     * GET /api-internal/admin/agents/{id}
     */
    public function agentGet(int $id): JsonResponse
    {
        $agent = Agent::with(['settings', 'territories', 'mls_ids', 'features'])->find($id);
        if (! $agent) return response()->json(['error' => 'Not found'], 404);
        return response()->json($this->formatAgent($agent, true));
    }

    /**
     * POST /api-internal/admin/agents
     */
    public function agentCreate(Request $req): JsonResponse
    {
        $data = $req->validate([
            'name'               => 'required|string|max:100',
            'slug'               => 'nullable|string|max:60|unique:agents,slug',
            'brokerage'          => 'nullable|string|max:100',
            'email'              => 'required|email|unique:agents,email',
            'phone'              => 'nullable|string|max:30',
            'bio'                => 'nullable|string|max:2000',
            'theme_slug'         => 'nullable|string|max:40',
            'theme_color'        => 'nullable|string|max:10',
            'primary_bg_color'   => 'nullable|string|max:10',
            'license_number'     => 'nullable|string|max:40',
            'headshot_path'      => 'nullable|string|max:500',
            'status'             => 'nullable|in:active,inactive,suspended',
            'mls_ids'            => 'nullable|array',
            'mls_ids.*'          => 'string|max:50',
            'territories'        => 'nullable|array',
            'territories.*'      => 'string|max:80',
            'custom_domain'      => 'nullable|string|max:100',
            'ga4_id'             => 'nullable|string|max:30',
            'fb_pixel_id'        => 'nullable|string|max:30',
            'fub_enabled'        => 'nullable|boolean',
            'fub_api_key'        => 'nullable|string|max:200',
            'ghl_enabled'        => 'nullable|boolean',
            'ghl_api_key'        => 'nullable|string|max:200',
            'lofty_enabled'      => 'nullable|boolean',
            'lofty_api_key'      => 'nullable|string|max:600',
            'notification_email' => 'nullable|email|max:150',
            'notification_phone' => 'nullable|string|max:30',
            'social_links'       => 'nullable|array',
            'subarea_whitelist'  => 'nullable|array',
            'subarea_whitelist.*'=> 'string|max:100',
            'residencity_region' => 'nullable|string|max:80',
            'seo_noindex'        => 'nullable|boolean',
            'features'           => 'nullable|array',
            'hero_stats'         => 'nullable|string|max:8000',
            'favicon_url'        => 'nullable|string|max:500',
            'guide_name'         => 'nullable|string|max:150',
        ]);

        $agent = DB::transaction(function () use ($data) {
            $slug = $data['slug']
                ?? (Str::slug($data['name']) . '-' . Str::lower(Str::random(4)));

            $agent = Agent::create([
                'name'             => $data['name'],
                'slug'             => $slug,
                'brokerage'        => $data['brokerage'] ?? null,
                'email'            => $data['email'],
                'phone'            => $data['phone'] ?? null,
                'bio'              => $data['bio'] ?? null,
                'theme_slug'       => $data['theme_slug'] ?? 'classic-dark',
                'theme_color'      => $data['theme_color'] ?? '#c9a96e',
                'primary_bg_color' => $data['primary_bg_color'] ?? '#1a1a1a',
                'license_number'   => $data['license_number'] ?? null,
                'headshot_path'    => $data['headshot_path'] ?? null,
                'status'           => $data['status'] ?? 'active',
                'password'         => Hash::make(Str::password(16)),
            ]);

            $agent->settings()->create([
                'custom_domain'      => $data['custom_domain'] ?? null,
                'notification_email' => $data['notification_email'] ?? $data['email'],
                'notification_phone' => $data['notification_phone'] ?? null,
                'ga4_id'             => $data['ga4_id'] ?? null,
                'fb_pixel_id'        => $data['fb_pixel_id'] ?? null,
                'fub_enabled'        => (bool) ($data['fub_enabled'] ?? false),
                'fub_api_key'        => $data['fub_api_key'] ?? null,
                'social_links'       => $data['social_links'] ?? null,
                'residencity_region' => $data['residencity_region'] ?? null,
            ]);

            foreach ($data['mls_ids'] ?? [] as $mlsId) {
                $agent->mls_ids()->create(['mls_id' => $mlsId]);
            }

            foreach ($data['territories'] ?? [] as $city) {
                $agent->territories()->create(['city' => $city]);
            }

            foreach ($data['features'] ?? [] as $key => $enabled) {
                if (array_key_exists($key, AgentFeature::FEATURES)) {
                    $agent->features()->updateOrCreate(
                        ['feature_key' => $key],
                        ['enabled' => (bool) $enabled]
                    );
                }
            }

            return $agent;
        });

        return response()->json(
            $this->formatAgent($agent->fresh(['settings', 'territories', 'mls_ids', 'features']), true),
            201
        );
    }

    /**
     * PUT /api-internal/admin/agents/{id}
     */
    public function agentUpdate(Request $req, int $id): JsonResponse
    {
        $agent = Agent::with(['settings', 'territories', 'mls_ids', 'features'])->find($id);
        if (! $agent) return response()->json(['error' => 'Not found'], 404);

        $data = $req->validate([
            'name'               => 'sometimes|required|string|max:100',
            'slug'               => 'sometimes|string|max:60|unique:agents,slug,' . $id,
            'brokerage'          => 'nullable|string|max:100',
            'email'              => 'sometimes|required|email|unique:agents,email,' . $id,
            'phone'              => 'nullable|string|max:30',
            'bio'                => 'nullable|string|max:2000',
            'theme_slug'         => 'nullable|string|max:40',
            'theme_color'        => 'nullable|string|max:10',
            'primary_bg_color'   => 'nullable|string|max:10',
            'license_number'     => 'nullable|string|max:40',
            'headshot_path'      => 'nullable|string|max:500',
            'status'             => 'nullable|in:active,inactive,suspended',
            'mls_ids'            => 'nullable|array',
            'mls_ids.*'          => 'string|max:50',
            'territories'        => 'nullable|array',
            'territories.*'      => 'string|max:80',
            'custom_domain'      => 'nullable|string|max:100',
            'ga4_id'             => 'nullable|string|max:30',
            'fb_pixel_id'        => 'nullable|string|max:30',
            'fub_enabled'        => 'nullable|boolean',
            'fub_api_key'        => 'nullable|string|max:200',
            'notification_email' => 'nullable|email|max:150',
            'notification_phone' => 'nullable|string|max:30',
            'social_links'       => 'nullable|array',
            'subarea_whitelist'  => 'nullable|array',
            'subarea_whitelist.*'=> 'string|max:100',
            'residencity_region' => 'nullable|string|max:80',
            'seo_noindex'        => 'nullable|boolean',
            'photo_focal_x'      => 'nullable|integer|min:0|max:100',
            'photo_focal_y'      => 'nullable|integer|min:0|max:100',
            'favicon_url'        => 'nullable|string|max:500',
            'hero_stats'              => 'nullable|string|max:8000',
            'area_expertise'          => 'nullable|string|max:32000',
            'guide_name'              => 'nullable|string|max:150',
            'achievements'            => 'nullable|string|max:8000',
            'co_agent_achievements'   => 'nullable|string|max:8000',
            'disable_sticky_bar'      => 'nullable|boolean',
            'features'                => 'nullable|array',
        ]);

        DB::transaction(function () use ($agent, $data) {
            $agentKeys = ['name', 'slug', 'brokerage', 'email', 'phone', 'bio',
                          'theme_slug', 'theme_color', 'primary_bg_color', 'license_number', 'headshot_path', 'status'];
            $agentFields = array_intersect_key($data, array_flip($agentKeys));
            if (! empty($agentFields)) {
                $agent->update($agentFields);
            }

            $settingsKeys = ['custom_domain', 'notification_email', 'notification_phone',
                             'ga4_id', 'fb_pixel_id', 'fub_enabled', 'ghl_enabled', 'social_links',
                             'subarea_whitelist', 'seo_noindex',
                             'photo_focal_x', 'photo_focal_y', 'residencity_region', 'hero_stats', 'area_expertise', 'favicon_url', 'guide_name',
                             'achievements', 'co_agent_achievements', 'disable_sticky_bar'];
            $settingsFields = array_intersect_key($data, array_flip($settingsKeys));
            if (! empty($settingsFields) || ! empty($data['fub_api_key']) || ! empty($data['ghl_api_key']) || ! empty($data['lofty_api_key'])) {
                $settings = $agent->settings ?? $agent->settings()->make(['agent_id' => $agent->id]);
                $settings->fill($settingsFields);
                if (! empty($data['fub_api_key'])) {
                    $settings->fub_api_key = $data['fub_api_key'];
                }
                if (array_key_exists('ghl_enabled', $data)) {
                    $settings->ghl_enabled = (bool) $data['ghl_enabled'];
                }
                if (! empty($data['ghl_api_key'])) {
                    $settings->ghl_api_key = $data['ghl_api_key'];
                }
                if (array_key_exists('lofty_enabled', $data)) {
                    $settings->lofty_enabled = (bool) $data['lofty_enabled'];
                }
                if (! empty($data['lofty_api_key'])) {
                    $settings->lofty_api_key = $data['lofty_api_key'];
                }
                $settings->agent_id = $agent->id;
                $settings->save();
            }

            if (array_key_exists('mls_ids', $data)) {
                $agent->mls_ids()->delete();
                foreach ($data['mls_ids'] ?? [] as $mlsId) {
                    $agent->mls_ids()->create(['mls_id' => $mlsId]);
                }
            }

            if (array_key_exists('territories', $data)) {
                $agent->territories()->delete();
                foreach ($data['territories'] ?? [] as $city) {
                    $agent->territories()->create(['city' => $city]);
                }
            }

            foreach ($data['features'] ?? [] as $key => $enabled) {
                if (array_key_exists($key, AgentFeature::FEATURES)) {
                    $agent->features()->updateOrCreate(
                        ['feature_key' => $key],
                        ['enabled' => (bool) $enabled]
                    );
                }
            }
        });

        return response()->json(
            $this->formatAgent($agent->fresh(['settings', 'territories', 'mls_ids', 'features']), true)
        );
    }

    /**
     * DELETE /api-internal/admin/agents/{id}
     * Soft-deletes by suspending.
     */
    public function agentDelete(int $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (! $agent) return response()->json(['error' => 'Not found'], 404);
        $agent->update(['status' => 'suspended']);
        return response()->json(['success' => true]);
    }

    /**
     * GET /api-internal/admin/agents/{id}/leads
     */
    public function agentLeads(int $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (! $agent) return response()->json(['error' => 'Not found'], 404);

        $hasUserId = \Illuminate\Support\Facades\Schema::hasColumn('agent_leads', 'user_id');

        $leads = AgentLead::where('agent_id', $id)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->map(fn ($l) => [
                'id'               => $l->id,
                'user_id'          => $hasUserId ? ($l->user_id ?? null) : null,
                'name'             => $l->name ?? trim(($l->first_name ?? '') . ' ' . ($l->last_name ?? '')),
                'email'            => $l->email,
                'phone'            => $l->phone,
                'message'          => $l->message,
                'form_type'        => $l->form_type,
                'form_type_label'  => $l->formTypeLabel(),
                'notes'            => $l->notes ?? null,
                'property_address' => $l->property_address ?? $l->listing_slug,
                'offer_context'    => $l->offer_context,
                'contacted_at'     => $l->contacted_at?->toIso8601String(),
                'created_at'       => $l->created_at?->toIso8601String(),
                'source_url'       => $l->source_url ?? null,
                'listing_slug'     => $l->listing_slug ?? null,
            ]);

        return response()->json($leads);
    }

    /**
     * PUT /api-internal/admin/agents/{id}/features
     */
    public function agentFeatures(Request $req, int $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (! $agent) return response()->json(['error' => 'Not found'], 404);

        $data = $req->validate(['features' => 'required|array']);

        foreach ($data['features'] as $key => $enabled) {
            if (array_key_exists($key, AgentFeature::FEATURES)) {
                $agent->features()->updateOrCreate(
                    ['feature_key' => $key],
                    ['enabled' => (bool) $enabled]
                );
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * GET /api-internal/admin/territory-cities
     */
    public function territoryCities(): JsonResponse
    {
        return response()->json([
            'cities'   => self::TERRITORY_CITIES,
            'subareas' => self::CITY_SUBAREAS,
        ]);
    }

    private function formatAgent(Agent $agent, bool $full = false): array
    {
        $settings = $agent->settings;

        $base = [
            'id'            => $agent->id,
            'name'          => $agent->name,
            'slug'          => $agent->slug,
            'brokerage'     => $agent->brokerage,
            'email'         => $agent->email,
            'phone'         => $agent->phone,
            'status'        => $agent->status,
            'theme_slug'    => $agent->theme_slug,
            'theme_color'   => $agent->theme_color,
            'custom_domain' => $settings?->custom_domain,
            'territories'   => $agent->territories?->pluck('city')->toArray() ?? [],
        ];

        if (! $full) return $base;

        return array_merge($base, [
            'bio'              => $agent->bio,
            'license_number'   => $agent->license_number,
            'photo_path'       => $agent->photo_path,
            'headshot_path'    => $agent->headshot_path,
            'logo_path'        => $agent->logo_path,
            'primary_bg_color' => $agent->primary_bg_color,
            'mls_ids'          => $agent->mls_ids?->pluck('mls_id')->toArray() ?? [],
            'settings'         => $settings ? [
                'custom_domain'      => $settings->custom_domain,
                'notification_email' => $settings->notification_email,
                'notification_phone' => $settings->notification_phone,
                'ga4_id'             => $settings->ga4_id,
                'fb_pixel_id'        => $settings->fb_pixel_id,
                'fub_enabled'        => (bool) $settings->fub_enabled,
                'ghl_enabled'        => (bool) ($settings->ghl_enabled ?? false),
                'ghl_api_key_set'     => ! empty($settings->getRawOriginal('ghl_api_key')),
                'ghl_location_id_set' => ! empty($settings->getRawOriginal('ghl_location_id')),
                'lofty_enabled'       => (bool) ($settings->lofty_enabled ?? false),
                'lofty_api_key_set'   => ! empty($settings->getRawOriginal('lofty_api_key')),
                'social_links'       => $settings->social_links ?? [],
                'lead_routing'       => $settings->lead_routing,
                'intro_video_url'    => $settings->intro_video_url,
                'seo_noindex'        => (bool) ($settings->seo_noindex ?? false),
                'subarea_whitelist'  => $settings->subarea_whitelist ?? null,
                'photo_focal_x'      => (int) ($settings->photo_focal_x ?? 50),
                'photo_focal_y'      => (int) ($settings->photo_focal_y ?? 15),
                'residencity_region' => $settings->residencity_region ?? null,
                'favicon_url'        => $settings->favicon_url ?? null,
                'guide_name'              => $settings->guide_name ?? null,
                'hero_stats'              => $settings->hero_stats ?? null,
                'area_expertise'          => $settings->area_expertise
                    ? (is_array($settings->area_expertise)
                        ? $settings->area_expertise
                        : json_decode($settings->area_expertise, true))
                    : null,
                'achievements'            => $settings->achievements
                    ? (is_array($settings->achievements)
                        ? $settings->achievements
                        : json_decode($settings->achievements, true))
                    : null,
                'co_agent_achievements'   => $settings->co_agent_achievements
                    ? (is_array($settings->co_agent_achievements)
                        ? $settings->co_agent_achievements
                        : json_decode($settings->co_agent_achievements, true))
                    : null,
                'team_members'            => $settings->team_members
                    ? (is_array($settings->team_members)
                        ? $settings->team_members
                        : json_decode($settings->team_members, true))
                    : null,
            ] : null,
            'features' => collect(AgentFeature::FEATURES)
                ->mapWithKeys(function ($label, $key) use ($agent) {
                    $f = $agent->features?->firstWhere('feature_key', $key);
                    return [$key => (bool) ($f?->enabled ?? false)];
                }),
        ]);
    }


    /**
     * GET /api-internal/admin/platform-summary
     * Returns platform-wide counts: active agent sites and total leads.
     */

    /**
     * POST /api-internal/admin/agents/{id}/ai-pages
     * Bulk-save AI-generated pages for an agent (upsert by agent_id+page_type+slug).
     * Body: { pages: [{ page_type, slug, title, content, meta_description, subarea? }] }
     */
    public function saveAiPages(int $id): \Illuminate\Http\JsonResponse
    {
        $agent = \App\Models\Agent::find($id);
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $pages = request()->input('pages', []);
        if (empty($pages)) return response()->json(['error' => 'No pages provided'], 422);

        $now = now();
        foreach ($pages as $p) {
            \Illuminate\Support\Facades\DB::table('agent_ai_pages')->updateOrInsert(
                ['agent_id' => $agent->id, 'page_type' => $p['page_type'], 'slug' => $p['slug']],
                [
                    'title'            => $p['title'],
                    'content'          => $p['content'],
                    'meta_description' => $p['meta_description'] ?? null,
                    'subarea'          => $p['subarea'] ?? null,
                    'generated_at'     => $now,
                    'updated_at'       => $now,
                    'created_at'       => $now,
                ]
            );
        }

        $saved = \Illuminate\Support\Facades\DB::table('agent_ai_pages')
            ->where('agent_id', $agent->id)
            ->whereIn('slug', array_column($pages, 'slug'))
            ->get();

        return response()->json($saved->map(fn ($r) => [
            'id'               => (int) $r->id,
            'page_type'        => $r->page_type,
            'slug'             => $r->slug,
            'title'            => $r->title,
            'meta_description' => $r->meta_description,
            'subarea'          => $r->subarea ?? null,
            'generated_at'     => $r->generated_at,
        ])->values());
    }

    /**
     * GET /api-internal/admin/agents/{id}/ai-pages?type=...
     * Returns all saved AI pages for an agent (admin view).
     */
    public function listAiPages(int $id): \Illuminate\Http\JsonResponse
    {
        $agent = \App\Models\Agent::find($id);
        if (! $agent) return response()->json(['error' => 'Agent not found'], 404);

        $type = request()->query('type');
        $query = \Illuminate\Support\Facades\DB::table('agent_ai_pages')
            ->where('agent_id', $agent->id);
        if ($type) $query->where('page_type', $type);

        $rows = $query->orderByDesc('generated_at')->get();

        return response()->json($rows->map(fn ($r) => [
            'id'               => (int) $r->id,
            'page_type'        => $r->page_type,
            'slug'             => $r->slug,
            'title'            => $r->title,
            'meta_description' => $r->meta_description,
            'subarea'          => $r->subarea ?? null,
            'generated_at'     => $r->generated_at,
        ])->values());
    }
    public function platformSummary(): \Illuminate\Http\JsonResponse
    {
        $activeAgents = \App\Models\Agent::where("status", "active")->count();
        $totalLeads   = \App\Models\AgentLead::count();
        $leads30      = \App\Models\AgentLead::where("created_at", ">=", now()->subDays(30))->count();

        return response()->json([
            "active_agent_sites" => (int) $activeAgents,
            "total_leads"        => (int) $totalLeads,
            "leads_last_30_days" => (int) $leads30,
        ]);
    }

    /**
     * GET /api-internal/admin/leads
     * All agents leads overview (for super-admin dashboard)
     */
    public function allLeads(\Illuminate\Http\Request $req): JsonResponse
    {
        $agentId  = $req->input("agent_id");
        $formType = $req->input("form_type");
        $from     = $req->input("from", now()->subDays(30)->toDateString());
        $to       = $req->input("to", now()->toDateString());

        $query = \App\Models\AgentLead::with("agent:id,name,slug")
            ->whereBetween("created_at", [$from . " 00:00:00", $to . " 23:59:59"])
            ->orderByDesc("created_at")
            ->limit(500);

        if ($agentId) $query->where("agent_id", (int) $agentId);
        if ($formType) $query->where("form_type", $formType);

        $leads = $query->get()->map(fn ($l) => [
            "id"               => $l->id,
            "agent_id"         => $l->agent_id,
            "agent_name"       => $l->agent?->name ?? "Unknown",
            "agent_slug"       => $l->agent?->slug ?? "",
            "name"             => !empty($l->name) ? $l->name : trim(($l->first_name ?? "") . " " . ($l->last_name ?? "")),
            "email"            => $l->email,
            "phone"            => $l->phone,
            "message"          => $l->message,
            "form_type"        => $l->form_type,
            "form_type_label"  => $l->formTypeLabel(),
            "notes"            => $l->notes ?? null,
            "property_address" => $l->property_address ?? $l->listing_slug,
            "offer_context"    => $l->offer_context,
            "contacted_at"     => $l->contacted_at?->toIso8601String(),
            "created_at"       => $l->created_at?->toIso8601String(),
            "source_url"       => $l->source_url ?? null,
            "listing_slug"     => $l->listing_slug ?? null,
        ]);

        $byAgent = \App\Models\AgentLead::selectRaw("agent_id, count(*) as total")
            ->whereBetween("created_at", [$from . " 00:00:00", $to . " 23:59:59"])
            ->groupBy("agent_id")
            ->with("agent:id,name")
            ->get()
            ->map(fn ($r) => ["agent_id" => $r->agent_id, "agent_name" => $r->agent?->name ?? "Unknown", "total" => (int) $r->total])
            ->sortByDesc("total")
            ->values();

        return response()->json(["leads" => $leads, "by_agent" => $byAgent, "from" => $from, "to" => $to]);
    }

    // =========================================================
    // Agent Portal endpoints (called by Next.js /agent-portal/*)
    // =========================================================

    /**
     * POST /api-internal/agent-portal/auth
     */
    public function agentPortalAuth(\Illuminate\Http\Request $req): JsonResponse
    {
        $email    = $req->input("email");
        $password = $req->input("password");

        if (!$email || !$password) {
            return response()->json(["error" => "Email and password required"], 422);
        }

        $agent = Agent::where("email", $email)->where("status", "active")->first();

        if (!$agent || !password_verify($password, $agent->password ?? "")) {
            return response()->json(["error" => "Invalid credentials"], 401);
        }

        $settings = \Illuminate\Support\Facades\DB::table("agent_settings")
            ->where("agent_id", $agent->id)->first();

        return response()->json([
            "id"          => $agent->id,
            "name"        => $agent->name,
            "email"       => $agent->email,
            "slug"        => $agent->slug,
            "theme_color" => $agent->theme_color ?? null,
            "theme_slug"  => $agent->theme_slug ?? null,
            "domain"      => $settings->custom_domain ?? null,
        ]);
    }

    /**
     * GET /api-internal/agent-portal/{id}/dashboard
     */
    public function agentPortalDashboard(int $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (!$agent) return response()->json(["error" => "Not found"], 404);

        $settings = \Illuminate\Support\Facades\DB::table("agent_settings")
            ->where("agent_id", $id)->first();

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        $leadsThisMonth = \App\Models\AgentLead::where("agent_id", $id)
            ->where("created_at", ">=", $startOfMonth)->count();

        $recentLeads = \App\Models\AgentLead::where("agent_id", $id)
            ->orderByDesc("created_at")->limit(5)->get()
            ->map(fn ($l) => [
                "id"              => $l->id,
                "name"            => !empty($l->name) ? $l->name : trim(($l->first_name ?? "") . " " . ($l->last_name ?? "")),
                "phone"           => $l->phone,
                "email"           => $l->email,
                "type"            => $l->form_type ?? "contact",
                "form_type_label" => $l->formTypeLabel(),
                "source"          => $l->source_url,
                "listing_slug"    => $l->listing_slug ?? null,
                "page_views"      => 0,
                "saved_searches"  => 0,
                "avg_price"       => null,
                "last_viewed"     => null,
                "last_viewed_type"=> null,
                "last_login"      => null,
                "created_at"      => $l->created_at?->toIso8601String(),
                "contacted"       => !is_null($l->contacted_at),
                "verified"        => (bool) $l->sms_verified,
            ]);

        return response()->json([
            "leads_this_month"    => $leadsThisMonth,
            "page_views_30d"      => 0,
            "active_listings"     => 0,
            "open_houses_this_week" => 0,
            "recent_leads"        => $recentLeads,
            "site_domain"         => $settings->custom_domain ?? null,
            "site_mode"           => ($settings && !empty($settings->custom_domain)) ? "live" : "demo",
            "subscription_plan"   => null,
            "subscription_status" => null,
            "next_payment_date"   => null,
            "monthly_amount"      => null,
        ]);
    }

    /**
     * GET /api-internal/agent-portal/{id}/leads
     */
    public function agentPortalLeads(int $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (!$agent) return response()->json(["error" => "Not found"], 404);

        $hasUserId = \Illuminate\Support\Facades\Schema::hasColumn('agent_leads', 'user_id');

        $leads = \App\Models\AgentLead::where("agent_id", $id)
            ->orderByDesc("created_at")->limit(200)->get()
            ->map(fn ($l) => [
                "id"              => $l->id,
                "user_id"         => $hasUserId ? ($l->user_id ?? null) : null,
                "name"            => !empty($l->name) ? $l->name : trim(($l->first_name ?? "") . " " . ($l->last_name ?? "")),
                "phone"           => $l->phone,
                "email"           => $l->email,
                "type"            => $l->form_type ?? "contact",
                "form_type_label" => $l->formTypeLabel(),
                "source"          => $l->source_url,
                "listing_slug"    => $l->listing_slug ?? null,
                "offer_context"   => $l->offer_context,
                "page_views"      => 0,
                "saved_searches"  => 0,
                "avg_price"       => null,
                "last_viewed"     => null,
                "last_viewed_type"=> null,
                "last_login"      => null,
                "created_at"      => $l->created_at?->toIso8601String(),
                "contacted"       => !is_null($l->contacted_at),
                "verified"        => (bool) $l->sms_verified,
            ]);

        return response()->json($leads);
    }

    /**
     * GET /api-internal/agent-portal/{id}/profile
     */
    public function agentPortalProfile(int $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (!$agent) return response()->json(["error" => "Not found"], 404);

        $settings = \Illuminate\Support\Facades\DB::table("agent_settings")
            ->where("agent_id", $id)->first();

        return response()->json([
            "name"            => $agent->name,
            "title"           => $agent->title ?? null,
            "brokerage"       => $agent->brokerage ?? null,
            "phone"           => $agent->phone ?? null,
            "email"           => $agent->email,
            "bio"             => $agent->bio ?? null,
            "photo_path"      => $agent->photo_path ?? null,
            "intro_video_url" => $settings->intro_video_url ?? null,
            "social_links"    => $settings ? json_decode($settings->social_links ?? "{}", true) ?? [] : [],
        ]);
    }

    /**
     * PUT /api-internal/agent-portal/{id}/profile
     */
    public function agentPortalProfileUpdate(\Illuminate\Http\Request $req, int $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (!$agent) return response()->json(["error" => "Not found"], 404);

        $allowed = ["name", "title", "brokerage", "phone", "bio"];
        $data = array_intersect_key($req->all(), array_flip($allowed));
        if (!empty($data)) $agent->update($data);

        return response()->json(["success" => true]);
    }

    /**
     * GET /api-internal/agent-portal/{id}/team
     */
    public function agentPortalTeam(int $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (!$agent) return response()->json(["error" => "Not found"], 404);

        $team = \Illuminate\Support\Facades\DB::table("agent_team_members")
            ->where("agent_id", $id)->orderBy("sort_order")->get();

        return response()->json($team);
    }

    /**
     * GET /api-internal/agent-portal/{id}/featured-listings
     */
    public function agentPortalFeaturedListings(int $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (!$agent) return response()->json(["error" => "Not found"], 404);

        $featured = \Illuminate\Support\Facades\DB::table("agent_featured_listings")
            ->where("agent_id", $id)->orderBy("sort_order")->get();

        return response()->json($featured);
    }

    /**
     * GET /api-internal/agent-portal/{id}/settings
     */
    public function agentPortalSettings(int $id): JsonResponse
    {
        $agent = Agent::find($id);
        if (!$agent) return response()->json(["error" => "Not found"], 404);

        $settings = \Illuminate\Support\Facades\DB::table("agent_settings")
            ->where("agent_id", $id)->first();

        return response()->json([
            "custom_domain"      => $settings->custom_domain ?? null,
            "notification_email" => $settings->notification_email ?? null,
            "notification_phone" => $settings->notification_phone ?? null,
            "ga4_id"             => $settings->ga4_id ?? null,
            "fb_pixel_id"        => $settings->fb_pixel_id ?? null,
        ]);
    }


    // Agent Portal: Integrations
    public function agentPortalIntegrationsGet(int $id): \Illuminate\Http\JsonResponse
    {
        $agent    = \App\Models\Agent::with('settings')->find($id);
        $settings = $agent?->settings;
        return response()->json([
            'ga4_id'        => $settings?->ga4_id,
            'fub_enabled'   => (bool) ($settings?->fub_enabled   ?? false),
            'ghl_enabled'   => (bool) ($settings?->ghl_enabled   ?? false),
            'lofty_enabled' => (bool) ($settings?->lofty_enabled ?? false),
        ]);
    }
    public function agentPortalIntegrationsUpdate(\Illuminate\Http\Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $agent = \App\Models\Agent::with('settings')->find($id);
        if (! $agent) { return response()->json(['error' => 'Agent not found'], 404); }
        $settings = $agent->settings()->firstOrCreate(['agent_id' => $id]);
        $data = $request->validate([
            'ga4_id'      => ['nullable', 'string', 'max:30'],
            'fub_enabled' => 'nullable|boolean',
            'fub_api_key' => 'nullable|string|max:200',
            'ghl_enabled' => 'nullable|boolean',
            'ghl_api_key'      => 'nullable|string|max:200',
            'ghl_location_id'  => 'nullable|string|max:64',
            'lofty_enabled'    => 'nullable|boolean',
            'lofty_api_key'    => 'nullable|string|max:600',
        ]);
        $settings->ga4_id        = $data['ga4_id'] ?? null;
        $settings->fub_enabled   = (bool) ($data['fub_enabled']   ?? false);
        $settings->ghl_enabled   = (bool) ($data['ghl_enabled']   ?? false);
        $settings->lofty_enabled = (bool) ($data['lofty_enabled'] ?? false);
        if (! empty($data['fub_api_key']))   { $settings->fub_api_key   = $data['fub_api_key'];   }
        if (! empty($data['ghl_api_key']))   { $settings->ghl_api_key   = $data['ghl_api_key'];   }
        if (! empty($data['ghl_location_id'])) { $settings->ghl_location_id = $data['ghl_location_id']; }
        if (! empty($data['lofty_api_key'])) { $settings->lofty_api_key = $data['lofty_api_key']; }
        $settings->save();
        return response()->json([
            'ga4_id'        => $settings->ga4_id,
            'fub_enabled'   => (bool) $settings->fub_enabled,
            'ghl_enabled'   => (bool) $settings->ghl_enabled,
            'lofty_enabled' => (bool) $settings->lofty_enabled,
        ]);
    }

    /**
     * GET /api-internal/admin/users
     * All registered Pixilink users across agent sites.
     * Excludes legacy Firebase bccondosandhomes.com users (uid IS NOT NULL).
     */
    public function adminUsers(\Illuminate\Http\Request $req): \Illuminate\Http\JsonResponse
    {
        $agentId = $req->query('agent_id');
        $search  = $req->query('search');

        $query = \Illuminate\Support\Facades\DB::table('users')
            ->join('agents', 'users.agent_id', '=', 'agents.id')
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.phone',
                'users.agent_id',
                'agents.name as agent_name',
                'agents.slug as agent_slug',
                'users.email_verified_at',
                'users.phone_verified_at',
                'users.google_id',
                'users.created_at'
            )
            ->where(function ($q) { $q->whereNull('users.uid')->orWhere('users.uid', ''); })
            ->orderByDesc('users.created_at')
            ->limit(500);

        if ($agentId) {
            $query->where('users.agent_id', (int) $agentId);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.email', 'like', '%' . $search . '%')
                  ->orWhere('users.first_name', 'like', '%' . $search . '%')
                  ->orWhere('users.last_name', 'like', '%' . $search . '%')
                  ->orWhere('users.phone', 'like', '%' . $search . '%');
            });
        }

        $users = $query->get()->map(function ($u) {
            return [
                'id'             => $u->id,
                'name'           => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                'first_name'     => $u->first_name,
                'last_name'      => $u->last_name,
                'email'          => $u->email,
                'phone'          => $u->phone,
                'agent_id'       => $u->agent_id,
                'agent_name'     => $u->agent_name,
                'agent_slug'     => $u->agent_slug,
                'email_verified' => !is_null($u->email_verified_at),
                'phone_verified' => !is_null($u->phone_verified_at),
                'google_linked'  => !is_null($u->google_id),
                'created_at'     => $u->created_at,
            ];
        });

        $byAgent = \Illuminate\Support\Facades\DB::table('users')
            ->join('agents', 'users.agent_id', '=', 'agents.id')
            ->where(function ($q) { $q->whereNull('users.uid')->orWhere('users.uid', ''); })
            ->selectRaw('users.agent_id, agents.name as agent_name, agents.slug as agent_slug, count(*) as total')
            ->groupBy('users.agent_id', 'agents.name', 'agents.slug')
            ->orderByDesc('total')
            ->get()
            ->map(function ($r) {
                return [
                    'agent_id'   => $r->agent_id,
                    'agent_name' => $r->agent_name,
                    'agent_slug' => $r->agent_slug,
                    'total'      => (int) $r->total,
                ];
            });

        return response()->json([
            'users'    => $users,
            'by_agent' => $byAgent,
            'total'    => $users->count(),
        ]);
    }

    /**
     * GET /api-internal/admin/agents/{id}/users
     * Registered users scoped to a specific agent site.
     * Excludes legacy Firebase bccondosandhomes.com users (uid IS NOT NULL).
     */
    public function agentUsers(int $id): \Illuminate\Http\JsonResponse
    {
        $agent = \App\Models\Agent::find($id);
        if (!$agent) return response()->json(['error' => 'Not found'], 404);

        $users = \Illuminate\Support\Facades\DB::table('users')
            ->where('agent_id', $id)
            ->where(function ($q) { $q->whereNull('uid')->orWhere('uid', ''); })
            ->orderByDesc('created_at')
            ->limit(500)
            ->get()
            ->map(function ($u) {
                return [
                    'id'             => $u->id,
                    'name'           => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                    'first_name'     => $u->first_name,
                    'last_name'      => $u->last_name,
                    'email'          => $u->email,
                    'phone'          => $u->phone,
                    'email_verified' => !is_null($u->email_verified_at),
                    'phone_verified' => !is_null($u->phone_verified_at),
                    'google_linked'  => !is_null($u->google_id),
                    'created_at'     => $u->created_at,
                ];
            });

        return response()->json([
            'agent_id'   => $id,
            'agent_name' => $agent->name,
            'agent_slug' => $agent->slug,
            'users'      => $users,
            'total'      => $users->count(),
        ]);
    }

}
