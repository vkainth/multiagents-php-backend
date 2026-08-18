@extends('admin.layouts.app')

@section('title', 'Add Agent')
@section('page-title', 'Add Agent')

@section('content')

<div style="max-width:860px;">
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <a href="{{ route('admin.agents.index') }}" class="ad-btn ad-btn--outline ad-btn--sm"><i class="fa-solid fa-arrow-left"></i></a>
    <h2 style="font-size:18px;font-weight:700;">New Agent</h2>
</div>

<form method="POST" action="{{ route('admin.agents.store') }}">
@csrf

<div class="ad-tabs" id="adTabs">
    <button type="button" class="ad-tab active" onclick="switchTab(0)"><i class="fa-solid fa-id-card" style="margin-right:6px;"></i>Identity &amp; Territory</button>
    <button type="button" class="ad-tab" onclick="switchTab(1)"><i class="fa-solid fa-palette" style="margin-right:6px;"></i>Site &amp; Branding</button>
    <button type="button" class="ad-tab" onclick="switchTab(2)"><i class="fa-solid fa-plug" style="margin-right:6px;"></i>Integrations &amp; Routing</button>
</div>

{{-- TAB 1 — Identity & Territory --}}
<div class="ad-tab-panel active" id="tabPanel0">
<div class="ad-card">
    <div class="ad-form-row">
        <div class="ad-form-group">
            <label>Full Name <span style="color:#ef4444">*</span></label>
            <input type="text" name="name" class="ad-form-control @error('name') is-invalid @enderror"
                value="{{ old('name') }}" required placeholder="Randy Dyck">
            @error('name')<div style="color:#ef4444;font-size:12px;margin-top:3px;">{{ $message }}</div>@enderror
        </div>
        <div class="ad-form-group">
            <label>Brokerage</label>
            <input type="text" name="brokerage" class="ad-form-control"
                value="{{ old('brokerage') }}" placeholder="RE/MAX Performance Realty">
        </div>
    </div>
    <div class="ad-form-row">
        <div class="ad-form-group">
            <label>Email <span style="color:#ef4444">*</span></label>
            <input type="email" name="email" class="ad-form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}" required placeholder="agent@example.com">
            @error('email')<div style="color:#ef4444;font-size:12px;margin-top:3px;">{{ $message }}</div>@enderror
            <div class="ad-form-help">A temporary password will be emailed to this address.</div>
        </div>
        <div class="ad-form-group">
            <label>Phone</label>
            <input type="text" name="phone" class="ad-form-control"
                value="{{ old('phone') }}" placeholder="+1 604 555 0100">
        </div>
    </div>
    <div class="ad-form-row">
        <div class="ad-form-group">
            <label>MLS Agent ID</label>
            <input type="text" name="mls_id" class="ad-form-control"
                value="{{ old('mls_id') }}" placeholder="FDYCKRA">
            <div class="ad-form-help">Used to filter their active listings on the site.</div>
        </div>
        <div class="ad-form-group">
            <label>Custom Domain</label>
            <input type="text" name="custom_domain" class="ad-form-control"
                value="{{ old('custom_domain') }}" placeholder="randydyck.com">
            <div class="ad-form-help">Add as addon domain in cPanel after saving.</div>
        </div>
    </div>
    <div class="ad-form-row">
        <div class="ad-form-group">
            <label>Notification Email</label>
            <input type="email" name="notification_email" class="ad-form-control"
                value="{{ old('notification_email') }}" placeholder="Defaults to agent email">
        </div>
        <div class="ad-form-group">
            <label>Notification Phone (SMS)</label>
            <input type="text" name="notification_phone" class="ad-form-control"
                value="{{ old('notification_phone') }}" placeholder="+16045550100">
        </div>
    </div>
    <div class="ad-form-group">
        <label>Territory Cities</label>
        <div style="display:flex;flex-wrap:wrap;gap:8px 16px;margin-top:6px;">
            @foreach($territoryCities as $city)
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:400;cursor:pointer;">
                <input type="checkbox" name="territories[]" value="{{ $city }}"
                    {{ in_array($city, old('territories', [])) ? 'checked' : '' }}
                    style="width:14px;height:14px;accent-color:#2563eb;">
                {{ $city }}
            </label>
            @endforeach
        </div>
    </div>
</div>
</div>

{{-- TAB 2 — Site & Branding --}}
<div class="ad-tab-panel" id="tabPanel1">
<div class="ad-card">
    <div class="ad-form-group">
        <label>Accent Colour</label>
        <div class="ad-color-swatches" id="swatchContainer">
            @foreach($swatches as $hex)
            <div class="ad-swatch {{ old('theme_color', '#c9a96e') === $hex ? 'selected' : '' }}"
                style="background:{{ $hex }};"
                data-color="{{ $hex }}"
                title="{{ $hex }}"
                onclick="selectColor('{{ $hex }}')"></div>
            @endforeach
        </div>
        <input type="hidden" name="theme_color" id="themeColorInput" value="{{ old('theme_color', '#c9a96e') }}">
        <div class="ad-form-help" style="margin-top:6px;">Shown in agent site header, buttons, and highlights.</div>
    </div>

    <div style="margin-top:20px;">
        <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:10px;">Social Links</label>
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
                value="{{ old('social_links.' . $key) }}"
                placeholder="{{ in_array($key, ['instagram','tiktok','twitter']) ? '@handle' : 'profile URL or handle' }}">
        </div>
        @endforeach
    </div>
</div>
</div>

{{-- TAB 3 — Integrations & Routing --}}
<div class="ad-tab-panel" id="tabPanel2">
<div class="ad-card">
    <div class="ad-form-row">
        <div class="ad-form-group">
            <label>Google Analytics 4 ID</label>
            <input type="text" name="ga4_id" class="ad-form-control @error('ga4_id') is-invalid @enderror"
                value="{{ old('ga4_id') }}" placeholder="G-XXXXXXXXXX">
            @error('ga4_id')<div style="color:#ef4444;font-size:12px;margin-top:3px;">{{ $message }}</div>@enderror
            <div class="ad-form-help">Auto-injected into &lt;head&gt; on every page of this agent's site.</div>
        </div>
        <div class="ad-form-group">
            <label>Facebook Pixel ID</label>
            <input type="text" name="fb_pixel_id" class="ad-form-control @error('fb_pixel_id') is-invalid @enderror"
                value="{{ old('fb_pixel_id') }}" placeholder="1234567890123456">
            @error('fb_pixel_id')<div style="color:#ef4444;font-size:12px;margin-top:3px;">{{ $message }}</div>@enderror
            <div class="ad-form-help">Numeric Pixel ID — auto-injected into &lt;head&gt;.</div>
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
                    {{ old('fub_enabled') ? 'checked' : '' }}>
                <div class="ad-toggle__switch"></div>
            </label>
        </div>
        <div class="ad-form-group" style="margin-bottom:0;">
            <label>FUB API Key</label>
            <input type="password" name="fub_api_key" class="ad-form-control"
                value="" autocomplete="new-password"
                placeholder="Stored encrypted">
            <div class="ad-form-help">Leave blank to keep existing key on edits.</div>
        </div>
    </div>

    <div style="margin-top:20px;">
        <div style="font-size:13px;font-weight:600;margin-bottom:4px;">Lead Email Routing</div>
        <div style="font-size:12px;color:#6b7280;margin-bottom:12px;">Override which email receives each lead type. Defaults to notification email.</div>
        @foreach([
            'w1_email' => ['W1 — Showing Request', 'Visitor requested a property showing'],
            'w2_email' => ['W2 — Home Evaluation', 'Visitor requested a market evaluation'],
            'w3_email' => ['W3 — Pre-qualification', 'Visitor requested mortgage pre-qual'],
        ] as $field => [$label, $desc])
        <div class="ad-form-group">
            <label>{{ $label }}</label>
            <input type="email" name="lead_routing[{{ $field }}]" class="ad-form-control"
                value="{{ old('lead_routing.' . $field) }}" placeholder="Defaults to notification email">
            <div class="ad-form-help">{{ $desc }}</div>
        </div>
        @endforeach
    </div>
</div>
</div>

<div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
    <a href="{{ route('admin.agents.index') }}" class="ad-btn ad-btn--outline">Cancel</a>
    <button type="submit" class="ad-btn ad-btn--blue">
        <i class="fa-solid fa-user-plus"></i> Create Agent &amp; Send Welcome Email
    </button>
</div>

</form>
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
// Open the tab that has validation errors
@if($errors->any())
(function(){
    const fields = {
        0: ['name','brokerage','email','phone','mls_id','custom_domain','notification_email','notification_phone','territories'],
        1: ['theme_color','social_links'],
        2: ['ga4_id','fb_pixel_id','fub_api_key','lead_routing'],
    };
    const errNames = @json(array_keys($errors->toArray()));
    for (let tab = 0; tab <= 2; tab++) {
        if (errNames.some(n => fields[tab].some(f => n.startsWith(f)))) {
            switchTab(tab); break;
        }
    }
})();
@endif
</script>
@endpush

@endsection
