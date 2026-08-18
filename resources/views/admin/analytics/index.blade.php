@extends('admin.layouts.app')

@section('title', 'Analytics')
@section('page-title', 'Analytics — Last 30 Days')

@section('content')

<div style="margin-bottom:16px;font-size:13px;color:#6b7280;">
    {{ \Carbon\Carbon::parse($from)->format('M j') }} – {{ \Carbon\Carbon::parse($to)->format('M j, Y') }}
    &nbsp;&bull;&nbsp;
    Sort by:
    <a href="?sort=views" class="sort-link {{ $sortBy === 'views' ? 'active' : '' }}">Page Views</a> &bull;
    <a href="?sort=leads" class="sort-link {{ $sortBy === 'leads' ? 'active' : '' }}">Leads</a> &bull;
    <a href="?sort=name"  class="sort-link {{ $sortBy === 'name'  ? 'active' : '' }}">Name</a>
</div>

<div class="ad-card">
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Status</th>
                    <th style="text-align:right;">
                        <a href="?sort=views" class="sort-link {{ $sortBy === 'views' ? 'active' : '' }}">Page Views ↕</a>
                    </th>
                    <th style="text-align:right;">
                        <a href="?sort=leads" class="sort-link {{ $sortBy === 'leads' ? 'active' : '' }}">Leads ↕</a>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $agent)
                <tr>
                    <td>
                        <div style="font-weight:600;">{{ $agent->name }}</div>
                        @if($agent->brokerage)
                            <div style="font-size:12px;color:#6b7280;">{{ $agent->brokerage }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="ad-badge ad-badge--{{ $agent->status }}">{{ ucfirst($agent->status) }}</span>
                    </td>
                    <td style="text-align:right;font-weight:700;font-size:16px;">{{ number_format($agent->total_views) }}</td>
                    <td style="text-align:right;font-weight:700;font-size:16px;">{{ number_format($agent->total_leads) }}</td>
                    <td style="text-align:right;">
                        <a href="{{ route('admin.agents.edit', $agent) }}" class="ad-btn ad-btn--outline ad-btn--sm">Edit</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:40px 0;color:#6b7280;">No agents found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
