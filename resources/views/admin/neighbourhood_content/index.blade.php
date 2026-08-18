@extends('admin.layouts.app')

@section('title', 'Neighbourhood Content')
@section('page-title', 'Neighbourhood Lifestyle &amp; Pulse')

@section('content')

@if(session('success'))
<div style="background:#ecfdf5;color:#065f46;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:13px;">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#fef2f2;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:13px;">
    {{ session('error') }}
</div>
@endif

<form method="GET" style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
    <label style="font-size:13px;color:#6b7280;">Agent</label>
    <select name="agent_id" onchange="this.form.submit()" style="padding:8px 12px;border-radius:8px;border:1px solid #e5e7eb;font-size:13px;">
        @foreach($agents as $a)
            <option value="{{ $a->id }}" {{ $agent && $agent->id === $a->id ? 'selected' : '' }}>{{ $a->name }} ({{ $a->slug }})</option>
        @endforeach
    </select>
</form>

@if($agent)

<div class="ad-card" style="margin-bottom:18px;padding:14px 16px;">
    <p style="font-size:13px;color:#6b7280;margin:0;line-height:1.6;">
        <strong>Lifestyle narratives</strong> are 2–3 paragraph AI-written descriptions of each subarea's character, housing mix, and lifestyle — grounded in real MLS age-bucket and price data.
        <strong>Weekly pulse blurbs</strong> are 2–3 sentence real-time market commentary, refreshed automatically every Monday at 5 AM. Use the buttons below to regenerate either manually.
    </p>
</div>

@if(empty($subareas))
    <div class="ad-card" style="padding:24px;text-align:center;color:#6b7280;font-size:14px;">
        No territory subareas configured for this agent. Set up agent territories first.
    </div>
@else
<div class="ad-card">
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead>
                <tr>
                    <th style="min-width:180px;">Subarea</th>
                    <th>Lifestyle Narrative</th>
                    <th style="white-space:nowrap;">Lifestyle Updated</th>
                    <th>Weekly Pulse</th>
                    <th style="white-space:nowrap;">Pulse Updated</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subareas as $subarea)
                @php $nc = $content[$subarea] ?? null; @endphp
                <tr>
                    <td style="font-weight:600;color:#111827;">{{ $subarea }}</td>
                    <td style="max-width:260px;">
                        @if($nc && $nc->lifestyle_body)
                            <div style="font-size:12px;color:#374151;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;">
                                {{ $nc->lifestyle_body }}
                            </div>
                        @else
                            <span style="font-size:12px;color:#9ca3af;font-style:italic;">Not generated yet</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:#6b7280;white-space:nowrap;">
                        {{ $nc?->lifestyle_generated_at ? $nc->lifestyle_generated_at->diffForHumans() : '—' }}
                    </td>
                    <td style="max-width:240px;">
                        @if($nc && $nc->pulse_body)
                            <div style="font-size:12px;color:#374151;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                                {{ $nc->pulse_body }}
                            </div>
                        @else
                            <span style="font-size:12px;color:#9ca3af;font-style:italic;">Not generated yet</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:#6b7280;white-space:nowrap;">
                        {{ $nc?->pulse_generated_at ? $nc->pulse_generated_at->diffForHumans() : '—' }}
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        <form method="POST" action="{{ route('admin.neighbourhood-content.generateLifestyle') }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="agent_id" value="{{ $agent->id }}">
                            <input type="hidden" name="subarea" value="{{ $subarea }}">
                            <button type="submit" class="ad-btn ad-btn--outline" style="font-size:11px;padding:5px 10px;" title="Regenerate lifestyle narrative for {{ $subarea }}"
                                onclick="return confirm('Regenerate lifestyle narrative for {{ addslashes($subarea) }}? This takes ~10 seconds.')">
                                <i class="fa-solid fa-map-pin"></i> Lifestyle
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.neighbourhood-content.generatePulse') }}" style="display:inline;margin-left:6px;">
                            @csrf
                            <input type="hidden" name="agent_id" value="{{ $agent->id }}">
                            <input type="hidden" name="subarea" value="{{ $subarea }}">
                            <button type="submit" class="ad-btn ad-btn--blue" style="font-size:11px;padding:5px 10px;" title="Regenerate weekly pulse for {{ $subarea }}"
                                onclick="return confirm('Regenerate weekly pulse for {{ addslashes($subarea) }}? This uses live MLS sold data from the past 7 days.')">
                                <i class="fa-solid fa-bolt"></i> Pulse
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endif

@endsection
