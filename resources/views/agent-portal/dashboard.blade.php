@extends('agent-portal.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

<div class="ap-stats">
    <div class="ap-stat">
        <div class="ap-stat__icon"><i class="fa-solid fa-users"></i></div>
        <div class="ap-stat__label">Leads This Month</div>
        <div class="ap-stat__value">{{ number_format($leadsThisMonth) }}</div>
    </div>
    <div class="ap-stat">
        <div class="ap-stat__icon"><i class="fa-solid fa-eye"></i></div>
        <div class="ap-stat__label">Page Views This Month</div>
        <div class="ap-stat__value">{{ number_format($pageViewsThisMonth) }}</div>
    </div>
    <div class="ap-stat">
        <div class="ap-stat__icon"><i class="fa-solid fa-house"></i></div>
        <div class="ap-stat__label">Active Listings</div>
        <div class="ap-stat__value">{{ number_format($activeListingsCount) }}</div>
    </div>
    <div class="ap-stat">
        <div class="ap-stat__icon"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="ap-stat__label">Upcoming Open Houses</div>
        <div class="ap-stat__value">{{ number_format($openHouseCount) }}</div>
    </div>
</div>

<div class="ap-card">
    <div class="ap-card__title">Recent Leads</div>

    @if($recentLeads->isEmpty())
        <p style="color:#6b7280;font-size:14px;text-align:center;padding:32px 0;">No leads yet. Once visitors fill out forms on your site, they'll appear here.</p>
    @else
        <div class="ap-table-wrap">
            <table class="ap-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Type</th>
                        <th>Page</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLeads as $lead)
                    <tr>
                        <td style="font-weight:600;">{{ $lead->first_name }} {{ $lead->last_name }}</td>
                        <td>
                            @if($lead->email)<div style="font-size:13px;">{{ $lead->email }}</div>@endif
                            @if($lead->phone)<div style="font-size:12px;color:#6b7280;">{{ $lead->phone }}</div>@endif
                        </td>
                        <td>
                            <span class="ap-badge ap-badge--{{ $lead->form_type }}">{{ $lead->formTypeLabel() }}</span>
                        </td>
                        <td style="font-size:12px;color:#6b7280;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $lead->source_url ? basename(parse_url($lead->source_url, PHP_URL_PATH)) : '—' }}
                        </td>
                        <td style="font-size:13px;color:#6b7280;white-space:nowrap;">{{ $lead->created_at->format('M j, g:ia') }}</td>
                        <td>
                            @if($lead->contacted_at)
                                <span class="ap-badge ap-badge--contacted"><i class="fa-solid fa-check" style="font-size:9px;"></i> Contacted</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:14px;text-align:right;">
            <a href="{{ route('agent-portal.leads') }}" class="ap-btn ap-btn--outline ap-btn--sm">View all leads →</a>
        </div>
    @endif
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;">
    <div class="ap-card" style="display:flex;align-items:center;gap:16px;">
        <div style="width:44px;height:44px;border-radius:10px;background:color-mix(in srgb,var(--accent) 15%,transparent);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:18px;flex-shrink:0;">
            <i class="fa-solid fa-user-pen"></i>
        </div>
        <div style="flex:1;">
            <div style="font-weight:600;font-size:14px;">Update Your Profile</div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px;">Bio, photo, social links</div>
        </div>
        <a href="{{ route('agent-portal.profile') }}" class="ap-btn ap-btn--outline ap-btn--sm" style="flex-shrink:0;">Edit →</a>
    </div>
    <div class="ap-card" style="display:flex;align-items:center;gap:16px;">
        <div style="width:44px;height:44px;border-radius:10px;background:color-mix(in srgb,var(--accent) 15%,transparent);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:18px;flex-shrink:0;">
            <i class="fa-solid fa-house-flag"></i>
        </div>
        <div style="flex:1;">
            <div style="font-weight:600;font-size:14px;">Pin Featured Listings</div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px;">Up to 6 pinned on homepage</div>
        </div>
        <a href="{{ route('agent-portal.featured-listings') }}" class="ap-btn ap-btn--outline ap-btn--sm" style="flex-shrink:0;">Manage →</a>
    </div>
</div>

@endsection
