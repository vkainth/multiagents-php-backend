<?php

namespace App\Http\Controllers\AgentPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    protected array $themePresets = [
        '#c9a96e', '#1a73e8', '#e53935', '#2e7d32', '#6a1b9a', '#212121',
    ];

    // w1/w2/w3/contact have active notification pipelines (email + SMS).
    // 'registration' prefs are stored and respected once a registration
    // notification endpoint is wired up; currently no notification fires on sign-up.
    public const LEAD_TYPES = [
        'w1'           => 'Showing Request',
        'w2'           => 'Home Evaluation',
        'w3'           => 'Mortgage Pre-Qual',
        'contact'      => 'Contact / General',
        'registration' => 'New Registration',
    ];

    public function index()
    {
        $agent    = Auth::guard('agent')->user()->load('settings', 'territories');
        $settings = $agent->settings;
        $leadTypes = self::LEAD_TYPES;

        return view('agent-portal.settings', compact('agent', 'settings', 'leadTypes'));
    }

    public function update(Request $request)
    {
        $agent = Auth::guard('agent')->user();

        $validated = $request->validate([
            'notification_email'                      => 'nullable|email|max:120',
            'notification_phone'                      => 'nullable|string|max:30',
            'theme_color'                             => 'nullable|string|max:10',
            'notification_prefs'                      => 'nullable|array',
            'notification_prefs.*.email'              => 'nullable|boolean',
            'notification_prefs.*.sms'                => 'nullable|boolean',
            'notification_prefs.*.email_override'     => 'nullable|email|max:150',
        ]);

        if (!empty($validated['theme_color'])) {
            $agent->update(['theme_color' => $validated['theme_color']]);
        }

        $settings = $agent->settings()->firstOrCreate(['agent_id' => $agent->id]);

        $socialLinks = $request->only([
            'instagram', 'facebook', 'linkedin', 'youtube', 'tiktok', 'twitter',
        ]);

        $notifPrefs = $this->buildNotifPrefs($request->input('notification_prefs', []));

        $settings->update([
            'notification_email' => $validated['notification_email'] ?? $settings->notification_email,
            'notification_phone' => $validated['notification_phone'] ?? $settings->notification_phone,
            'social_links'       => array_filter($socialLinks, fn($v) => !empty($v)),
            'notification_prefs' => $notifPrefs,
        ]);

        return back()->with('success', 'Settings saved successfully.');
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
