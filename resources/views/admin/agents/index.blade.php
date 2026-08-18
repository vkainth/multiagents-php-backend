@extends('admin.layouts.app')

@section('title', 'Agents')
@section('page-title', 'Agents')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <div>
        <h2 style="font-size:20px;font-weight:700;">Agent Network</h2>
        <p style="font-size:13px;color:#6b7280;margin-top:3px;">{{ $agents->count() }} agent{{ $agents->count() !== 1 ? 's' : '' }} total</p>
    </div>
    <a href="{{ route('admin.agents.create') }}" class="ad-btn ad-btn--blue">
        <i class="fa-solid fa-user-plus"></i> Add Agent
    </a>
</div>

<div class="ad-card">
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>Name / Brokerage</th>
                    <th>Status</th>
                    <th>Territory</th>
                    <th>Domain</th>
                    <th>Leads (mo)</th>
                    <th>Views (mo)</th>
                    <th>Last Login</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $agent)
                <tr style="cursor:pointer;" onclick="window.location='{{ route('admin.agents.edit', $agent) }}'">
                    <td>
                        <div style="font-weight:600;">{{ $agent->name }}</div>
                        @if($agent->brokerage)
                            <div style="font-size:12px;color:#6b7280;">{{ $agent->brokerage }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="ad-badge ad-badge--{{ $agent->status }}">
                            {{ ucfirst($agent->status) }}
                        </span>
                    </td>
                    <td style="font-size:12px;color:#374151;max-width:160px;">
                        {{ $agent->territories->pluck('city')->implode(', ') ?: '—' }}
                    </td>
                    <td style="font-size:12px;color:#6b7280;">
                        {{ $agent->settings->custom_domain ?? '—' }}
                    </td>
                    <td style="font-weight:600;text-align:center;">{{ number_format($agent->leads_this_month) }}</td>
                    <td style="font-weight:600;text-align:center;">{{ number_format($agent->views_this_month) }}</td>
                    <td style="font-size:12px;color:#6b7280;white-space:nowrap;">
                        {{ $agent->last_login_at ? $agent->last_login_at->diffForHumans() : 'Never' }}
                    </td>
                    <td>
                        <a href="{{ route('admin.agents.edit', $agent) }}" class="ad-btn ad-btn--outline ad-btn--sm" onclick="event.stopPropagation()">
                            Edit
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px 0;color:#6b7280;font-size:14px;">
                        No agents yet. <a href="{{ route('admin.agents.create') }}" style="color:#2563eb;">Add the first one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
