<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentMlsId;
use App\Models\AgentSettings;
use App\Models\AgentTerritory;
use App\Models\AdminAuditLog;
use App\Models\AgentLead;
use App\Models\AgentPageView;
use App\Notifications\AgentWelcomeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentsController extends Controller
{
    private const ACCENT_SWATCHES = [
        '#c9a96e', '#2563eb', '#16a34a', '#dc2626', '#7c3aed', '#0f172a',
    ];

    private const TERRITORY_CITIES = [
        'Vancouver', 'Burnaby', 'Richmond', 'Surrey', 'Coquitlam', 'Port Coquitlam',
        'Port Moody', 'New Westminster', 'North Vancouver', 'West Vancouver', 'Langley',
        'Abbotsford', 'Chilliwack', 'Mission', 'Maple Ridge', 'Pitt Meadows',
        'Delta', 'White Rock', 'South Surrey White Rock', 'Cloverdale',
        'Squamish', 'Whistler',
    ];

    public const LEAD_TYPES = [
        'w1'           => 'Showing Request',
        'w2'           => 'Home Evaluation',
        'w3'           => 'Mortgage Pre-Qual',
        'contact'      => 'Contact / General',
        'registration' => 'New Registration',
    ];

    public function index()
    {
        $now        = now();
        $monthStart = $now->copy()->startOfMonth();

        $agents = Agent::with(['settings', 'territories'])
            ->orderBy('name')
            ->get()
            ->map(function (Agent $agent) use ($monthStart) {
                $agent->leads_this_month = AgentLead::where('agent_id', $agent->id)
                    ->where('created_at', '>=', $monthStart)
                    ->count();

                $agent->views_this_month = AgentPageView::where('agent_id', $agent->id)
                    ->where('date', '>=', $monthStart->toDateString())
                    ->sum('count');

                return $agent;
            });

        return view('admin.agents.index', compact('agents'));
    }

    public function create()
    {
        $swatches        = self::ACCENT_SWATCHES;
        $territoryCities = self::TERRITORY_CITIES;

        return view('admin.agents.create', compact('swatches', 'territoryCities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                                    => 'required|string|max:100',
            'brokerage'                               => 'nullable|string|max:100',
            'email'                                   => 'required|email|unique:agents,email',
            'phone'                                   => 'nullable|string|max:30',
            'mls_id'                                  => 'nullable|string|max:50',
            'custom_domain'                           => 'nullable|string|max:100',
            'notification_email'                      => 'nullable|email|max:150',
            'notification_phone'                      => 'nullable|string|max:30',
            'territories'                             => 'nullable|array',
            'territories.*'                           => 'string|max:80',
            'theme_color'                             => 'nullable|string|max:10',
            'social_links'                            => 'nullable|array',
            'ga4_id'                                  => ['nullable', 'string', 'max:30', 'regex:/^G-[A-Z0-9]+$/'],
            'fb_pixel_id'                             => ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'fub_enabled'                             => 'nullable|boolean',
            'fub_api_key'                             => 'nullable|string|max:200',
            'lead_routing'                            => 'nullable|array',
            'lead_routing.*'                          => 'nullable|email',
            'notification_prefs'                      => 'nullable|array',
            'notification_prefs.*.email'              => 'nullable|boolean',
            'notification_prefs.*.sms'                => 'nullable|boolean',
            'notification_prefs.*.email_override'     => 'nullable|email|max:150',
            'licensed_since'                          => 'nullable|integer|min:1950|max:2030',
            'languages'                               => 'nullable|string|max:255',
            'faqs_json'                               => 'nullable|string',
        ]);

        $tempPassword = Str::password(12, true, true, false);
        $slug         = Str::slug($data['name']) . '-' . Str::lower(Str::random(4));

        $agent = DB::transaction(function () use ($data, $tempPassword, $slug) {
            $agent = Agent::create([
                'name'        => $data['name'],
                'slug'        => $slug,
                'brokerage'   => $data['brokerage'] ?? null,
                'email'       => $data['email'],
                'phone'       => $data['phone'] ?? null,
                'theme_color' => $data['theme_color'] ?? '#c9a96e',
                'password'    => Hash::make($tempPassword),
                'status'      => 'active',
            ]);

            $agent->settings()->create([
                'custom_domain'      => $data['custom_domain'] ?? null,
                'notification_email' => $data['notification_email'] ?? $data['email'],
                'notification_phone' => $data['notification_phone'] ?? null,
                'ga4_id'             => $data['ga4_id'] ?? null,
                'fb_pixel_id'        => $data['fb_pixel_id'] ?? null,
                'fub_enabled'        => (bool) ($data['fub_enabled'] ?? false),
                'fub_api_key'        => $data['fub_api_key'] ?? null,
                'lead_routing'       => $data['lead_routing'] ?? null,
                'social_links'       => $data['social_links'] ?? null,
                'notification_prefs' => $this->buildNotifPrefs($data['notification_prefs'] ?? []),
                'licensed_since'     => isset($data['licensed_since']) ? (int) $data['licensed_since'] : null,
                'languages'          => $data['languages'] ?? null,
                'faqs_json'          => $data['faqs_json'] ?? null,
                'site_config'        => $data['site_config'] ?? null,
                'ghl_enabled'        => (bool) ($data['ghl_enabled'] ?? false),
                'ghl_source_label'   => $data['ghl_source_label'] ?? null,
                'rankmyagent_url'    => $data['rankmyagent_url'] ?? null,
            ]);

            if (!empty($data['mls_id'])) {
                $agent->mls_ids()->create(['mls_id' => $data['mls_id']]);
            }

            foreach ($data['territories'] ?? [] as $city) {
                $agent->territories()->create(['city' => $city]);
            }

            return $agent;
        });

        AdminAuditLog::record('agent_created', $agent->id, ['name' => $agent->name, 'email' => $agent->email]);

        try {
            $agent->notify(new AgentWelcomeNotification(
                $tempPassword,
                url('/agent-portal/login')
            ));
        } catch (\Throwable) {
        }

        return redirect()->route('admin.agents.edit', $agent)
            ->with('success', "Agent {$agent->name} created. Welcome email sent to {$agent->email}.");
    }

    public function edit(Agent $agent)
    {
        $agent->load(['settings', 'territories', 'mls_ids']);
        $swatches        = self::ACCENT_SWATCHES;
        $territoryCities = self::TERRITORY_CITIES;
        $leadTypes       = self::LEAD_TYPES;

        return view('admin.agents.edit', compact('agent', 'swatches', 'territoryCities', 'leadTypes'));
    }

    public function update(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'name'                                    => 'required|string|max:100',
            'brokerage'                               => 'nullable|string|max:100',
            'email'                                   => 'required|email|unique:agents,email,' . $agent->id,
            'phone'                                   => 'nullable|string|max:30',
            'mls_id'                                  => 'nullable|string|max:50',
            'custom_domain'                           => 'nullable|string|max:100',
            'notification_email'                      => 'nullable|email|max:150',
            'notification_phone'                      => 'nullable|string|max:30',
            'territories'                             => 'nullable|array',
            'territories.*'                           => 'string|max:80',
            'theme_color'                             => 'nullable|string|max:10',
            'social_links'                            => 'nullable|array',
            'ga4_id'                                  => ['nullable', 'string', 'max:30', 'regex:/^G-[A-Z0-9]+$/'],
            'fb_pixel_id'                             => ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'fub_enabled'                             => 'nullable|boolean',
            'fub_api_key'                             => 'nullable|string|max:200',
            'lead_routing'                            => 'nullable|array',
            'lead_routing.*'                          => 'nullable|email',
            'seo_noindex'                             => 'nullable|boolean',
            'notification_prefs'                      => 'nullable|array',
            'notification_prefs.*.email'              => 'nullable|boolean',
            'notification_prefs.*.sms'                => 'nullable|boolean',
            'notification_prefs.*.email_override'     => 'nullable|email|max:150',
            'licensed_since'                          => 'nullable|integer|min:1950|max:2030',
            'languages'                               => 'nullable|string|max:255',
            'faqs_json'                               => 'nullable|string',
            'site_config'                             => 'nullable|string',
            'ghl_enabled'                             => 'nullable|boolean',
            'ghl_api_key'                             => 'nullable|string|max:200',
            'ghl_source_label'                        => 'nullable|string|max:100',
            'rankmyagent_url'                         => 'nullable|url|max:300',
        ]);

        DB::transaction(function () use ($agent, $data) {
            $agent->update([
                'name'        => $data['name'],
                'brokerage'   => $data['brokerage'] ?? null,
                'email'       => $data['email'],
                'phone'       => $data['phone'] ?? null,
                'theme_color' => $data['theme_color'] ?? $agent->theme_color,
            ]);

            $settings = $agent->settings ?? $agent->settings()->make();
            $settings->fill([
                'custom_domain'      => $data['custom_domain'] ?? null,
                'notification_email' => $data['notification_email'] ?? $data['email'],
                'notification_phone' => $data['notification_phone'] ?? null,
                'ga4_id'             => $data['ga4_id'] ?? null,
                'fb_pixel_id'        => $data['fb_pixel_id'] ?? null,
                'fub_enabled'        => (bool) ($data['fub_enabled'] ?? false),
                'lead_routing'       => $data['lead_routing'] ?? null,
                'social_links'       => $data['social_links'] ?? null,
                'seo_noindex'        => (bool) ($data['seo_noindex'] ?? true),
                'notification_prefs' => $this->buildNotifPrefs($data['notification_prefs'] ?? []),
                'licensed_since'     => isset($data['licensed_since']) ? (int) $data['licensed_since'] : null,
                'languages'          => $data['languages'] ?? null,
                'faqs_json'          => $data['faqs_json'] ?? null,
                'site_config'        => $data['site_config'] ?? null,
                'ghl_enabled'        => (bool) ($data['ghl_enabled'] ?? false),
                'ghl_source_label'   => $data['ghl_source_label'] ?? null,
                'rankmyagent_url'    => $data['rankmyagent_url'] ?? null,
            ]);
            if (!empty($data['fub_api_key'])) {
                $settings->fub_api_key = $data['fub_api_key'];
            }
            if (!empty($data['ghl_api_key'])) {
                $settings->ghl_api_key = $data['ghl_api_key'];
            }
            $settings->agent_id = $agent->id;
            $settings->save();

            // Sync agent_faqs table from faqs_json blob (primary source for API)
            if (array_key_exists('faqs_json', $data) && \Illuminate\Support\Facades\Schema::hasTable('agent_faqs')) {
                DB::table('agent_faqs')->where('agent_id', $agent->id)->delete();
                $faqItems = json_decode($data['faqs_json'] ?? '[]', true);
                if (is_array($faqItems)) {
                    foreach ($faqItems as $i => $item) {
                        $q = $item['q'] ?? $item['question'] ?? null;
                        $a = $item['a'] ?? $item['answer'] ?? null;
                        if (!empty($q) || !empty($a)) {
                            DB::table('agent_faqs')->insert([
                                'agent_id'   => $agent->id,
                                'question'   => $q,
                                'answer'     => $a,
                                'sort_order' => (int) $i,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            if (array_key_exists('mls_id', $data)) {
                $agent->mls_ids()->delete();
                if (!empty($data['mls_id'])) {
                    $agent->mls_ids()->create(['mls_id' => $data['mls_id']]);
                }
            }

            $agent->territories()->delete();
            foreach ($data['territories'] ?? [] as $city) {
                $agent->territories()->create(['city' => $city]);
            }
        });

        AdminAuditLog::record('agent_updated', $agent->id, ['name' => $agent->name]);

        return redirect()->route('admin.agents.edit', $agent)->with('success', 'Agent updated successfully.');
    }

    public function suspend(Agent $agent)
    {
        $agent->update(['status' => 'suspended']);
        AdminAuditLog::record('agent_suspended', $agent->id, ['name' => $agent->name]);

        return redirect()->route('admin.agents.edit', $agent)->with('success', "{$agent->name} has been suspended.");
    }

    public function uploadPhoto(Request $request, Agent $agent)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $file = $request->file('photo');
        $ext  = strtolower($file->getClientOriginalExtension());
        $name = 'agent-' . $agent->id . '-' . time() . '.' . $ext;
        $dest = public_path('frontend/images/teamagents');

        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $oldPath = $agent->photo_path ?? '';
        if ($oldPath && str_starts_with($oldPath, 'frontend/images/teamagents/agent-')) {
            $oldFile = public_path($oldPath);
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }

        $file->move($dest, $name);
        $relativePath = 'frontend/images/teamagents/' . $name;

        $agent->update(['photo_path' => $relativePath]);

        AdminAuditLog::record('agent_photo_updated', $agent->id, ['photo_path' => $relativePath]);

        return redirect()->route('admin.agents.edit', $agent)->with('success', 'Headshot updated successfully.');
    }

    public function reactivate(Agent $agent)
    {
        $agent->update(['status' => 'active']);
        AdminAuditLog::record('agent_reactivated', $agent->id, ['name' => $agent->name]);

        return redirect()->route('admin.agents.edit', $agent)->with('success', "{$agent->name} has been reactivated.");
    }

    protected function buildNotifPrefs(array $raw): array
    {
        $prefs = [];

        foreach (array_keys(self::LEAD_TYPES) as $type) {
            $prefs[$type] = [
                'email'          => (bool) ($raw[$type]['email'] ?? 1),
                'sms'            => (bool) ($raw[$type]['sms'] ?? 0),
                'email_override' => trim($raw[$type]['email_override'] ?? ''),
            ];
        }

        return $prefs;
    }
}
