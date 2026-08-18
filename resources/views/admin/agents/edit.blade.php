@extends('admin.layouts.app')

@section('title', 'Edit — ' . $agent->name)
@section('page-title', $agent->name)

@section('content')

<div style="max-width:920px;">
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="{{ route('admin.agents.index') }}" class="ad-btn ad-btn--outline ad-btn--sm"><i class="fa-solid fa-arrow-left"></i></a>
        <div>
            <h2 style="font-size:18px;font-weight:700;">{{ $agent->name }}</h2>
            <div style="font-size:12px;color:#6b7280;margin-top:2px;">{{ $agent->brokerage ?? 'No brokerage set' }} &bull; {{ ucfirst($agent->status) }}</div>
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        @if($agent->settings?->custom_domain)
            <a href="https://{{ $agent->settings->custom_domain }}" target="_blank" class="ad-btn ad-btn--outline ad-btn--sm">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> View Site
            </a>
        @endif
        @if($agent->status === 'active')
            <form method="POST" action="{{ route('admin.agents.suspend', $agent) }}" onsubmit="return confirm('Suspend {{ addslashes($agent->name) }}? Their site will show a 403 until reactivated.')">
                @csrf @method('PATCH')
                <button type="submit" class="ad-btn ad-btn--danger ad-btn--sm"><i class="fa-solid fa-ban"></i> Suspend</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.agents.reactivate', $agent) }}">
                @csrf @method('PATCH')
                <button type="submit" class="ad-btn ad-btn--success ad-btn--sm"><i class="fa-solid fa-circle-check"></i> Reactivate</button>
            </form>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 200px;gap:18px;align-items:start;">

{{-- Main form --}}
<div>
<form method="POST" action="{{ route('admin.agents.update', $agent) }}">
@csrf @method('PATCH')

<div class="ad-tabs">
    <button type="button" class="ad-tab active" onclick="switchTab(0)"><i class="fa-solid fa-id-card" style="margin-right:6px;"></i>Identity &amp; Territory</button>
    <button type="button" class="ad-tab" onclick="switchTab(1)"><i class="fa-solid fa-palette" style="margin-right:6px;"></i>Site &amp; Branding</button>
    <button type="button" class="ad-tab" onclick="switchTab(2)"><i class="fa-solid fa-plug" style="margin-right:6px;"></i>Integrations &amp; Routing</button>
    <button type="button" class="ad-tab" onclick="switchTab(3)"><i class="fa-solid fa-comment-dots" style="margin-right:6px;"></i>Content &amp; FAQs</button>
</div>

{{-- TAB 1 --}}
<div class="ad-tab-panel active" id="tabPanel0">
<div class="ad-card">
    <div class="ad-form-row">
        <div class="ad-form-group">
            <label>Full Name <span style="color:#ef4444">*</span></label>
            <input type="text" name="name" class="ad-form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $agent->name) }}" required>
            @error('name')<div style="color:#ef4444;font-size:12px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
        <div class="ad-form-group">
            <label>Brokerage</label>
            <input type="text" name="brokerage" class="ad-form-control"
                value="{{ old('brokerage', $agent->brokerage) }}">
        </div>
    </div>
    <div class="ad-form-row">
        <div class="ad-form-group">
            <label>Email <span style="color:#ef4444">*</span></label>
            <input type="email" name="email" class="ad-form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $agent->email) }}" required>
            @error('email')<div style="color:#ef4444;font-size:12px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
        <div class="ad-form-group">
            <label>Phone</label>
            <input type="text" name="phone" class="ad-form-control"
                value="{{ old('phone', $agent->phone) }}">
        </div>
    </div>
    <div class="ad-form-row">
        <div class="ad-form-group">
            <label>MLS Agent ID</label>
            <input type="text" name="mls_id" class="ad-form-control"
                value="{{ old('mls_id', $agent->mls_ids->first()?->mls_id) }}">
            <div class="ad-form-help">Used to filter their listings.</div>
        </div>
        <div class="ad-form-group">
            <label>Custom Domain</label>
            <input type="text" name="custom_domain" class="ad-form-control"
                value="{{ old('custom_domain', $agent->settings?->custom_domain) }}" placeholder="randydyck.com">
        </div>
    </div>
    <div class="ad-form-row">
        <div class="ad-form-group">
            <label>Notification Email</label>
            <input type="email" name="notification_email" class="ad-form-control"
                value="{{ old('notification_email', $agent->settings?->notification_email) }}">
        </div>
        <div class="ad-form-group">
            <label>Notification Phone (SMS)</label>
            <input type="text" name="notification_phone" class="ad-form-control"
                value="{{ old('notification_phone', $agent->settings?->notification_phone) }}">
        </div>
    </div>
    <div class="ad-form-row">
        <div class="ad-form-group">
            <label>Licensed Since (Year)</label>
            <input type="number" name="licensed_since" class="ad-form-control"
                value="{{ old('licensed_since', $agent->settings?->licensed_since) }}"
                placeholder="1989" min="1950" max="2030">
            <div class="ad-form-help">Year the agent first got their real estate licence.</div>
        </div>
        <div class="ad-form-group">
            <label>Languages</label>
            <input type="text" name="languages" class="ad-form-control"
                value="{{ old('languages', $agent->settings?->languages) }}"
                placeholder="English, Mandarin, Punjabi">
            <div class="ad-form-help">Comma-separated languages spoken.</div>
        </div>
    </div>
    <div class="ad-form-group">
        <label>Territory Cities</label>
        @php $selectedCities = $agent->territories->pluck('city')->toArray(); @endphp
        <div style="display:flex;flex-wrap:wrap;gap:8px 16px;margin-top:6px;">
            @foreach($territoryCities as $city)
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:400;cursor:pointer;">
                <input type="checkbox" name="territories[]" value="{{ $city }}"
                    {{ in_array($city, old('territories', $selectedCities)) ? 'checked' : '' }}
                    style="width:14px;height:14px;accent-color:#2563eb;">
                {{ $city }}
            </label>
            @endforeach
        </div>
    </div>
</div>
</div>

{{-- TAB 2 --}}
<div class="ad-tab-panel" id="tabPanel1">
<div class="ad-card">
    <div class="ad-form-group">
        <label>Accent Colour</label>
        @php $currentColor = old('theme_color', $agent->theme_color ?? '#c9a96e'); @endphp
        <div class="ad-color-swatches" id="swatchContainer">
            @foreach($swatches as $hex)
            <div class="ad-swatch {{ $currentColor === $hex ? 'selected' : '' }}"
                style="background:{{ $hex }};"
                data-color="{{ $hex }}"
                title="{{ $hex }}"
                onclick="selectColor('{{ $hex }}')"></div>
            @endforeach
        </div>
        <input type="hidden" name="theme_color" id="themeColorInput" value="{{ $currentColor }}">
    </div>

    <div style="margin-top:20px;">
        <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:10px;">Social Links</label>
        @php $socialLinks = $agent->settings?->social_links ?? []; @endphp
        @foreach([
            'instagram' => ['Instagram', 'fa-instagram', 'fab'],
            'facebook'  => ['Facebook',  'fa-facebook',  'fab'],
            'linkedin'  => ['LinkedIn',  'fa-linkedin',  'fab'],
            'youtube'   => ['YouTube',   'fa-youtube',   'fab'],
            'tiktok'    => ['TikTok',    'fa-tiktok',    'fab'],
            'twitter'   => ['X/Twitter', 'fa-x-twitter', 'fab'],
        ] as $key => [$label, $icon, $prefix])
        <div class="ad-form-row" style="margin-bottom:12px;align-items:center;">
            <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;">
                <i class="{{ $prefix }} {{ $icon }}" style="width:18px;text-align:center;font-size:16px;"></i>
                {{ $label }}
            </div>
            <input type="text" name="social_links[{{ $key }}]" class="ad-form-control"
                value="{{ old('social_links.' . $key, $socialLinks[$key] ?? '') }}"
                placeholder="{{ in_array($key, ['instagram','tiktok','twitter']) ? '@handle' : 'profile URL or handle' }}">
        </div>
        @endforeach
    </div>
</div>
</div>

{{-- TAB 3 --}}
<div class="ad-tab-panel" id="tabPanel2">
<div class="ad-card">
    <div class="ad-form-row">
        <div class="ad-form-group">
            <label>Google Analytics 4 ID</label>
            <input type="text" name="ga4_id" class="ad-form-control @error('ga4_id') is-invalid @enderror"
                value="{{ old('ga4_id', $agent->settings?->ga4_id) }}" placeholder="G-XXXXXXXXXX">
            @error('ga4_id')<div style="color:#ef4444;font-size:12px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
        <div class="ad-form-group">
            <label>Facebook Pixel ID</label>
            <input type="text" name="fb_pixel_id" class="ad-form-control @error('fb_pixel_id') is-invalid @enderror"
                value="{{ old('fb_pixel_id', $agent->settings?->fb_pixel_id) }}" placeholder="Numeric ID">
            @error('fb_pixel_id')<div style="color:#ef4444;font-size:12px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="ad-form-group" style="padding:14px;background:#f8fafc;border-radius:8px;border:1px solid #e5e7eb;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div>
                <div style="font-size:13px;font-weight:600;">Follow Up Boss</div>
                <div style="font-size:12px;color:#6b7280;">Send leads to agent's FUB account</div>
            </div>
            <label class="ad-toggle" style="cursor:pointer;">
                <input type="hidden" name="fub_enabled" value="0">
                <input type="checkbox" name="fub_enabled" value="1" id="fubToggle"
                    {{ old('fub_enabled', $agent->settings?->fub_enabled) ? 'checked' : '' }}>
                <div class="ad-toggle__switch"></div>
            </label>
        </div>
        <div class="ad-form-group" style="margin-bottom:0;">
            <label>FUB API Key</label>
            <input type="password" name="fub_api_key" class="ad-form-control"
                value="" autocomplete="new-password" placeholder="Leave blank to keep existing">
        </div>
    </div>

    <div style="margin-top:20px;">
        <div style="font-size:13px;font-weight:600;margin-bottom:4px;">Lead Email Routing</div>
        <div style="font-size:12px;color:#6b7280;margin-bottom:12px;">Override which email receives each lead type. Per-type overrides in Notification Preferences below take priority over these.</div>
        @php $lr = $agent->settings?->effectiveLeadRouting() ?? []; @endphp
        @foreach([
            'w1_email' => ['W1 — Showing Request', 'Visitor requested a property showing'],
            'w2_email' => ['W2 — Home Evaluation', 'Visitor requested a market evaluation'],
            'w3_email' => ['W3 — Pre-qualification', 'Visitor requested mortgage pre-qual'],
        ] as $field => [$label, $desc])
        <div class="ad-form-group">
            <label>{{ $label }}</label>
            <input type="email" name="lead_routing[{{ $field }}]" class="ad-form-control"
                value="{{ old('lead_routing.' . $field, $lr[$field] ?? '') }}"
                placeholder="Defaults to notification email">
            <div class="ad-form-help">{{ $desc }}</div>
        </div>
        @endforeach
    </div>

    {{-- Notification Preferences --}}
    <div style="margin-top:24px;padding-top:20px;border-top:1px solid #e5e7eb;">
        <div style="font-size:13px;font-weight:600;margin-bottom:4px;">
            <i class="fa-solid fa-bell" style="margin-right:6px;color:#6b7280;"></i>Notification Preferences
        </div>
        <div style="font-size:12px;color:#6b7280;margin-bottom:14px;">
            Override the agent's channel settings per lead type. Email is on by default; SMS requires a notification phone number.
        </div>

        @php
            $notifPrefs = $agent->settings?->notification_prefs ?? [];
        @endphp

        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f9fafb;">
                    <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;border-bottom:1px solid #e5e7eb;width:36%;">Lead Type</th>
                    <th style="text-align:center;padding:8px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;border-bottom:1px solid #e5e7eb;width:12%;">Email</th>
                    <th style="text-align:center;padding:8px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;border-bottom:1px solid #e5e7eb;width:12%;">SMS</th>
                    <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Email Override</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leadTypes as $type => $label)
                @php
                    $emailOn  = (bool) ($notifPrefs[$type]['email'] ?? 1);
                    $smsOn    = (bool) ($notifPrefs[$type]['sms']   ?? 0);
                    $override = $notifPrefs[$type]['email_override'] ?? '';
                @endphp
                <tr>
                    <td style="padding:10px 12px;border-bottom:1px solid #f3f4f6;font-weight:500;color:#111827;">{{ $label }}</td>
                    <td style="padding:10px 12px;border-bottom:1px solid #f3f4f6;text-align:center;">
                        <label class="ad-toggle">
                            <input type="hidden"   name="notification_prefs[{{ $type }}][email]" value="0">
                            <input type="checkbox" name="notification_prefs[{{ $type }}][email]" value="1"
                                {{ $emailOn ? 'checked' : '' }}>
                            <div class="ad-toggle__switch"></div>
                        </label>
                    </td>
                    <td style="padding:10px 12px;border-bottom:1px solid #f3f4f6;text-align:center;">
                        <label class="ad-toggle">
                            <input type="hidden"   name="notification_prefs[{{ $type }}][sms]" value="0">
                            <input type="checkbox" name="notification_prefs[{{ $type }}][sms]" value="1"
                                {{ $smsOn ? 'checked' : '' }}>
                            <div class="ad-toggle__switch"></div>
                        </label>
                    </td>
                    <td style="padding:10px 12px;border-bottom:1px solid #f3f4f6;">
                        <input type="email"
                            name="notification_prefs[{{ $type }}][email_override]"
                            class="ad-form-control"
                            style="font-size:12.5px;padding:5px 9px;"
                            value="{{ old('notification_prefs.' . $type . '.email_override', $override) }}"
                            placeholder="Leave blank to use routing above">
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>
</div>

{{-- TAB 4: Content & FAQs --}}
<div class="ad-tab-panel" id="tabPanel3">
<div class="ad-card">

    <div class="ad-form-group">
        <label style="font-size:13px;font-weight:600;color:#374151;">Site Config JSON</label>
        <textarea name="site_config" id="siteConfigTextarea" class="ad-form-control" rows="4"
            placeholder='{"layout_preset":"showcase"}' style="font-family:monospace;font-size:12px;">{{ old('site_config', is_array($agent->settings?->site_config) ? json_encode($agent->settings?->site_config) : ($agent->settings?->site_config ?? '')) }}</textarea>
        <div class="ad-form-help">Raw JSON for layout presets and feature flags. Leave blank to use defaults.</div>
    </div>

    <div style="margin-top:28px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <label style="font-size:13px;font-weight:600;color:#374151;margin:0;">
                Agent FAQs
                <span style="font-size:11px;font-weight:400;color:#9ca3af;">(stored in agent_settings.faqs_json)</span>
            </label>
            <button type="button" onclick="faqAddRow()" class="ad-btn ad-btn--sm" style="background:#059669;color:#fff;border-color:#059669;font-size:11px;">+ Add FAQ</button>
        </div>
        <div id="faqRows" style="display:flex;flex-direction:column;gap:12px;"></div>
        <textarea name="faqs_json" id="faqsJsonTextarea" style="display:none;"
            rows="8">{{ old('faqs_json', $agent->settings?->faqs_json) }}</textarea>
        <div style="font-size:11px;color:#9ca3af;margin-top:8px;">
            Each FAQ appears as visible Q&amp;A on the agent site, indexable by AI engines.
        </div>
    </div>

</div>
</div>

<div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
    <button type="submit" class="ad-btn ad-btn--blue">
        <i class="fa-solid fa-floppy-disk"></i> Save Changes
    </button>
</div>

</form>
</div>

{{-- Integrations sidebar --}}
<div>
    {{-- Agent Photo Upload --}}
    <div class="ad-card" style="padding:16px;margin-bottom:12px;">
        <div style="font-size:13px;font-weight:600;margin-bottom:10px;"><i class="fa-solid fa-camera" style="margin-right:6px;color:#6b7280;"></i>Agent Photo</div>

        @if($agent->photo_path)
        <div style="margin-bottom:10px;text-align:center;">
            <img src="https://media.pixilinkserver.com/{{ ltrim($agent->photo_path, '/') }}?w=200"
                 alt="{{ $agent->name }}"
                 style="width:100%;max-width:160px;height:160px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;"
                 onerror="this.style.display='none'">
        </div>
        @else
        <div style="margin-bottom:10px;display:flex;align-items:center;justify-content:center;height:100px;background:#f3f4f6;border-radius:8px;border:1px dashed #d1d5db;">
            <span style="font-size:12px;color:#9ca3af;">No photo set</span>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.agents.upload-photo', $agent) }}" enctype="multipart/form-data">
            @csrf
            @if($errors->has('photo'))
                <div style="color:#ef4444;font-size:12px;margin-bottom:6px;">{{ $errors->first('photo') }}</div>
            @endif
            <label style="display:block;font-size:12px;font-weight:500;color:#374151;margin-bottom:4px;">
                {{ $agent->photo_path ? 'Replace headshot' : 'Upload headshot' }}
            </label>
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                   style="display:block;width:100%;font-size:12px;margin-bottom:8px;">
            <div style="font-size:11px;color:#9ca3af;margin-bottom:8px;">JPG, PNG or WebP &bull; max 5 MB</div>
            <button type="submit" class="ad-btn ad-btn--outline ad-btn--sm" style="width:100%;justify-content:center;">
                <i class="fa-solid fa-upload"></i> Upload
            </button>
        </form>
    </div>

    <div class="ad-card" style="padding:16px;">
        <div style="font-size:13px;font-weight:600;margin-bottom:12px;">Integrations</div>
        <div class="ad-integ-sidebar">
            <div class="ad-integ-row">
                <span class="ad-integ-row__label"><i class="fa-brands fa-google" style="margin-right:5px;color:#4285f4;"></i>GA4</span>
                @if($agent->settings?->ga4_id)
                    <span class="ad-badge ad-badge--on">Active</span>
                @else
                    <span style="color:#9ca3af;font-size:12px;">—</span>
                @endif
            </div>
            <div class="ad-integ-row">
                <span class="ad-integ-row__label"><i class="fa-brands fa-meta" style="margin-right:5px;color:#1877f2;"></i>FB Pixel</span>
                @if($agent->settings?->fb_pixel_id)
                    <span class="ad-badge ad-badge--on">Active</span>
                @else
                    <span style="color:#9ca3af;font-size:12px;">—</span>
                @endif
            </div>
            <div class="ad-integ-row">
                <span class="ad-integ-row__label"><i class="fa-solid fa-bolt" style="margin-right:5px;color:#f59e0b;"></i>FUB</span>
                @if($agent->settings?->fub_enabled && $agent->settings?->fub_api_key)
                    <span class="ad-badge ad-badge--on">Active</span>
                @else
                    <span style="color:#9ca3af;font-size:12px;">—</span>
                @endif
            </div>
        </div>
    </div>

    <div class="ad-card" style="padding:16px;margin-top:12px;">
        <div style="font-size:13px;font-weight:600;margin-bottom:8px;">Quick Info</div>
        <div style="font-size:12px;color:#6b7280;line-height:1.8;">
            <div><strong>Slug:</strong> {{ $agent->slug }}</div>
            <div><strong>Status:</strong>
                <span class="ad-badge ad-badge--{{ $agent->status }}" style="font-size:10px;">{{ ucfirst($agent->status) }}</span>
            </div>
            <div><strong>Last login:</strong><br>{{ $agent->last_login_at?->format('M j, Y g:ia') ?? 'Never' }}</div>
            <div><strong>Created:</strong><br>{{ $agent->created_at->format('M j, Y') }}</div>
        </div>
    </div>

    <a href="{{ route('admin.leads.index', ['agent_id' => $agent->id]) }}" class="ad-btn ad-btn--outline ad-btn--sm" style="width:100%;justify-content:center;margin-top:10px;">
        <i class="fa-solid fa-users"></i> View Leads
    </a>
</div>

</div>
</div>

@push('scripts')
<script>
function switchTab(idx) {
    document.querySelectorAll('.ad-tab').forEach((t, i) => t.classList.toggle('active', i === idx));
    document.querySelectorAll('.ad-tab-panel').forEach((p, i) => p.classList.toggle('active', i === idx));
}
function selectColor(hex) {
    document.getElementById('themeColorInput').value = hex;
    document.querySelectorAll('.ad-swatch').forEach(s => s.classList.toggle('selected', s.dataset.color === hex));
}
@if($errors->any())
(function(){
    const fields = {
        0: ['name','brokerage','email','phone','mls_id','custom_domain','notification_email','notification_phone','territories','licensed_since','languages'],
        1: ['theme_color','social_links'],
        2: ['ga4_id','fb_pixel_id','fub_api_key','lead_routing','notification_prefs'],
        3: ['site_config','faqs_json'],
    };
    const errNames = @json(array_keys($errors->toArray()));
    for (let tab = 0; tab <= 3; tab++) {
        if (errNames.some(n => fields[tab].some(f => n.startsWith(f)))) {
            switchTab(tab); break;
        }
    }
})();
@endif

// --- FAQ management ---
var faqData = [];
(function initFaqs() {
    var ta = document.getElementById('faqsJsonTextarea');
    if (ta && ta.value.trim()) {
        try { faqData = JSON.parse(ta.value.trim()); } catch(e) { faqData = []; }
    }
    renderFaqs();
})();
function eh(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function renderFaqs() {
    var c = document.getElementById('faqRows');
    if (!c) return;
    c.innerHTML = '';
    faqData.forEach(function(item, i) {
        var row = document.createElement('div');
        row.style.cssText = 'background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:12px;';
        row.innerHTML = '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">'
            + '<span style="font-size:12px;font-weight:600;color:#6b7280;">FAQ #' + (i+1) + '</span>'
            + (i > 0 ? '<button type="button" onclick="faqMove(' + i + ',-1)" class="ad-btn ad-btn--sm" style="font-size:10px;padding:2px 6px;margin-right:2px;" title="Move up">\u2191</button>' : '')
            + (i < faqData.length - 1 ? '<button type="button" onclick="faqMove(' + i + ',1)" class="ad-btn ad-btn--sm" style="font-size:10px;padding:2px 6px;margin-right:4px;" title="Move down">\u2193</button>' : '')
            + '<button type="button" onclick="faqRemove(' + i + ')" class="ad-btn ad-btn--danger ad-btn--sm" style="font-size:10px;padding:2px 8px;">Remove</button>'
            + '</div>'
            + '<div style="margin-bottom:6px;">'
            + '<label style="font-size:11px;font-weight:500;color:#374151;display:block;margin-bottom:3px;">Question</label>'
            + '<input type="text" class="ad-form-control" style="font-size:13px;" value="' + eh(item.q||'') + '"'
            + ' oninput="faqUpdate(' + i + ',\'q\',this.value)" placeholder="e.g. What areas does the agent cover?">'
            + '</div><div>'
            + '<label style="font-size:11px;font-weight:500;color:#374151;display:block;margin-bottom:3px;">Answer</label>'
            + '<textarea class="ad-form-control" rows="3" style="font-size:13px;resize:vertical;"'
            + ' oninput="faqUpdate(' + i + ',\'a\',this.value)">' + eh(item.a||'') + '</textarea>'
            + '</div>';
        c.appendChild(row);
    });
    syncFaq();
}
function faqAddRow() { faqData.push({q:'',a:''}); renderFaqs(); }
function faqRemove(i) { faqData.splice(i,1); renderFaqs(); }
function faqMove(i, dir) {
    var j = i + dir;
    if (j < 0 || j >= faqData.length) return;
    var tmp = faqData[i]; faqData[i] = faqData[j]; faqData[j] = tmp;
    renderFaqs();
}
function faqUpdate(i,k,v) { if(faqData[i]) faqData[i][k]=v; syncFaq(); }
function syncFaq() {
    var t = document.getElementById('faqsJsonTextarea');
    if (t) t.value = JSON.stringify(faqData);
}
</script>
@endpush

@endsection
