@extends('agent-portal.layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@push('styles')
<style>
.color-swatch {
    display:inline-block;width:32px;height:32px;border-radius:50%;cursor:pointer;
    border:3px solid transparent;transition:transform .15s,border-color .15s;
}
.color-swatch.active, .color-swatch:hover { transform:scale(1.15);border-color:#1a1a2e; }
.readonly-field {
    background:#f9fafb;border:1px solid var(--border);border-radius:7px;
    padding:9px 12px;font-size:14px;color:#6b7280;cursor:not-allowed;
}
.theme-row { display:flex;align-items:center;gap:10px;flex-wrap:wrap; }

/* Notification preferences grid */
.notif-table { width:100%;border-collapse:collapse;font-size:13.5px; }
.notif-table th {
    text-align:left;padding:9px 14px;background:#f9fafb;font-size:11px;
    font-weight:600;text-transform:uppercase;letter-spacing:.4px;
    color:#6b7280;border-bottom:1px solid var(--border);
}
.notif-table th:not(:first-child) { text-align:center; }
.notif-table td { padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:middle; }
.notif-table tr:last-child td { border-bottom:none; }
.notif-table td:not(:first-child) { text-align:center; }
.notif-table .lead-label { font-weight:500;color:#111827;font-size:13.5px; }
.notif-table .email-override-wrap { margin-top:6px; }
.notif-table .email-override-wrap input {
    width:100%;max-width:260px;padding:5px 9px;border:1px solid var(--border);
    border-radius:6px;font-size:12.5px;color:#374151;background:#fff;
}
.notif-table .email-override-wrap input:focus {
    outline:none;border-color:var(--accent);
    box-shadow:0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent);
}

/* Toggle switch */
.ap-toggle { position:relative;display:inline-block;width:38px;height:22px; }
.ap-toggle input { opacity:0;width:0;height:0; }
.ap-toggle__sw {
    position:absolute;inset:0;border-radius:22px;background:#d1d5db;
    cursor:pointer;transition:background .2s;
}
.ap-toggle__sw::after {
    content:'';position:absolute;left:3px;top:3px;
    width:16px;height:16px;border-radius:50%;background:#fff;
    transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2);
}
.ap-toggle input:checked + .ap-toggle__sw { background:var(--accent); }
.ap-toggle input:checked + .ap-toggle__sw::after { transform:translateX(16px); }
</style>
@endpush

@section('content')

<form method="POST" action="{{ route('agent-portal.settings.update') }}" id="settingsForm">
    @csrf
    @method('PATCH')

    {{-- Global notification contacts --}}
    <div class="ap-card" style="margin-bottom:20px;">
        <div class="ap-card__title">Contact Details for Notifications</div>
        <div class="ap-form-row">
            <div class="ap-form-group">
                <label>Default Notification Email</label>
                <input type="email" name="notification_email" class="ap-form-control"
                    value="{{ old('notification_email', $settings?->notification_email) }}"
                    placeholder="you@example.com">
                <div class="ap-form-help">Used for any lead type without a per-type override below.</div>
            </div>
            <div class="ap-form-group">
                <label>SMS Notification Phone</label>
                <input type="tel" name="notification_phone" class="ap-form-control"
                    value="{{ old('notification_phone', $settings?->notification_phone) }}"
                    placeholder="+1 604 555 0100">
                <div class="ap-form-help">Texts are sent to this number when SMS is enabled per form type.</div>
            </div>
        </div>
    </div>

    {{-- Per-lead-type notification preferences --}}
    <div class="ap-card" style="margin-bottom:20px;">
        <div class="ap-card__title">Lead Notification Preferences</div>
        <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">
            Control how you are notified for each type of lead. Toggle Email and/or Text (SMS) on or off per form.
            Optionally enter a different email address to receive a specific lead type at an alternate inbox.
        </p>

        @php
            $prefs = $settings?->notification_prefs ?? [];
        @endphp

        <div class="ap-table-wrap">
        <table class="notif-table">
            <thead>
                <tr>
                    <th style="width:40%;">Lead Type</th>
                    <th style="width:15%;">Email</th>
                    <th style="width:15%;">Text (SMS)</th>
                    <th>Send Email To (optional override)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leadTypes as $type => $label)
                @php
                    $emailOn  = (bool) ($prefs[$type]['email'] ?? 1);
                    $smsOn    = (bool) ($prefs[$type]['sms']   ?? 0);
                    $override = $prefs[$type]['email_override'] ?? '';
                @endphp
                <tr>
                    <td class="lead-label">{{ $label }}</td>
                    <td>
                        <label class="ap-toggle">
                            <input type="hidden"   name="notification_prefs[{{ $type }}][email]" value="0">
                            <input type="checkbox" name="notification_prefs[{{ $type }}][email]" value="1"
                                {{ $emailOn ? 'checked' : '' }}>
                            <span class="ap-toggle__sw"></span>
                        </label>
                    </td>
                    <td>
                        <label class="ap-toggle">
                            <input type="hidden"   name="notification_prefs[{{ $type }}][sms]" value="0">
                            <input type="checkbox" name="notification_prefs[{{ $type }}][sms]" value="1"
                                {{ $smsOn ? 'checked' : '' }}>
                            <span class="ap-toggle__sw"></span>
                        </label>
                    </td>
                    <td>
                        <div class="email-override-wrap">
                            <input type="email"
                                name="notification_prefs[{{ $type }}][email_override]"
                                value="{{ old('notification_prefs.' . $type . '.email_override', $override) }}"
                                placeholder="Leave blank to use default email">
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    <div class="ap-card" style="margin-bottom:20px;">
        <div class="ap-card__title">Site Accent Colour</div>
        <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">Choose the accent colour used in your site header, buttons, and highlights.</p>

        @php
            $presets = ['#c9a96e','#1a73e8','#e53935','#2e7d32','#6a1b9a','#212121'];
            $current = $agent->theme_color ?? '#c9a96e';
        @endphp

        <div class="theme-row" id="colorSwatches">
            @foreach($presets as $color)
            <span class="color-swatch {{ $current === $color ? 'active' : '' }}"
                style="background:{{ $color }};"
                onclick="selectColor('{{ $color }}')"
                title="{{ $color }}"
                data-color="{{ $color }}">
            </span>
            @endforeach
        </div>
        <input type="hidden" name="theme_color" id="themeColorInput" value="{{ $current }}">
        <div style="margin-top:12px;display:flex;align-items:center;gap:8px;">
            <div id="colorPreview" style="width:24px;height:24px;border-radius:4px;background:{{ $current }};border:1px solid rgba(0,0,0,.1);"></div>
            <span id="colorPreviewText" style="font-size:13px;color:#374151;font-family:monospace;">{{ $current }}</span>
        </div>
    </div>

    <div class="ap-card" style="margin-bottom:20px;">
        <div class="ap-card__title">Social Links</div>
        @php
            $socials = $settings?->social_links ?? [];
        @endphp
        @foreach([
            ['instagram','Instagram','fa-instagram'],
            ['facebook','Facebook','fa-facebook-f'],
            ['linkedin','LinkedIn','fa-linkedin-in'],
            ['youtube','YouTube','fa-youtube'],
            ['tiktok','TikTok','fa-tiktok'],
            ['twitter','X / Twitter','fa-x-twitter'],
        ] as [$key,$label,$icon])
        <div class="ap-form-group" style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
            <div style="width:32px;text-align:center;color:var(--text-muted);font-size:16px;flex-shrink:0;">
                <i class="fa-brands {{ $icon }}"></i>
            </div>
            <div style="flex:1;">
                <input type="url" name="{{ $key }}" class="ap-form-control"
                    value="{{ old($key, $socials[$key] ?? '') }}"
                    placeholder="{{ $label }} profile URL">
            </div>
        </div>
        @endforeach
    </div>

    <div class="ap-card" style="margin-bottom:20px;">
        <div class="ap-card__title">Custom Domain</div>
        <div class="ap-form-group" style="margin-bottom:4px;">
            <label>Your domain</label>
            <div class="readonly-field">{{ $settings?->custom_domain ?? '—' }}</div>
        </div>
        <p style="font-size:12px;color:#9ca3af;">Domain changes are made by BC Condos staff. Contact your admin to update.</p>
    </div>

    <div class="ap-card" style="margin-bottom:20px;">
        <div class="ap-card__title">Territory (View Only)</div>
        @forelse($agent->territories as $territory)
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:14px;">
                <i class="fa-solid fa-location-dot" style="color:var(--accent);font-size:12px;"></i>
                {{ $territory->city }}{{ $territory->subarea ? ' — ' . $territory->subarea : '' }}
            </div>
        @empty
            <p style="font-size:13px;color:#9ca3af;">No territory assigned.</p>
        @endforelse
        <p style="font-size:12px;color:#9ca3af;margin-top:8px;">Territory changes require admin action.</p>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;">
        <button type="submit" class="ap-btn ap-btn--primary">
            <i class="fa-solid fa-floppy-disk"></i> Save Settings
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script>
function selectColor(color) {
    document.getElementById('themeColorInput').value = color;
    document.getElementById('colorPreview').style.background = color;
    document.getElementById('colorPreviewText').textContent = color;

    document.querySelectorAll('.color-swatch').forEach(el => {
        el.classList.toggle('active', el.dataset.color === color);
    });

    document.documentElement.style.setProperty('--accent', color);
}
</script>
@endpush
