@extends('admin.layouts.app')

@section('title', 'Leads')
@section('page-title', 'Leads Overview')

@section('content')

<div class="ad-filter-bar">
    <form method="GET" action="{{ route('admin.leads.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;width:100%;">
        <div class="ad-form-group" style="min-width:160px;">
            <label style="font-size:12px;font-weight:500;color:#374151;margin-bottom:3px;">Agent</label>
            <select name="agent_id" class="ad-form-control" style="font-size:13px;">
                <option value="">All agents</option>
                @foreach($agents as $a)
                    <option value="{{ $a->id }}" {{ $agentId == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ad-form-group" style="min-width:160px;">
            <label style="font-size:12px;font-weight:500;color:#374151;margin-bottom:3px;">Lead Type</label>
            <select name="form_type" class="ad-form-control" style="font-size:13px;">
                <option value="">All types</option>
                <option value="w1" {{ $formType === 'w1' ? 'selected' : '' }}>W1 — Showing</option>
                <option value="w2" {{ $formType === 'w2' ? 'selected' : '' }}>W2 — Home Eval</option>
                <option value="w3" {{ $formType === 'w3' ? 'selected' : '' }}>W3 — Pre-qual</option>
                <option value="w4" {{ $formType === 'w4' ? 'selected' : '' }}>W4 — Bldg Alert</option>
                <option value="contact" {{ $formType === 'contact' ? 'selected' : '' }}>Contact Form</option>
                <option value="ask" {{ $formType === 'ask' ? 'selected' : '' }}>Ask Agent</option>
            </select>
        </div>
        <div class="ad-form-group">
            <label style="font-size:12px;font-weight:500;color:#374151;margin-bottom:3px;">From</label>
            <input type="date" name="from" class="ad-form-control" value="{{ $from }}" style="font-size:13px;min-width:130px;">
        </div>
        <div class="ad-form-group">
            <label style="font-size:12px;font-weight:500;color:#374151;margin-bottom:3px;">To</label>
            <input type="date" name="to" class="ad-form-control" value="{{ $to }}" style="font-size:13px;min-width:130px;">
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;padding-bottom:0;">
            <button type="submit" class="ad-btn ad-btn--blue ad-btn--sm">Filter</button>
            <a href="{{ route('admin.leads.index') }}" class="ad-btn ad-btn--outline ad-btn--sm">Reset</a>
            <a href="{{ route('admin.leads.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="ad-btn ad-btn--outline ad-btn--sm">
                <i class="fa-solid fa-download"></i> CSV
            </a>
        </div>
    </form>
</div>

@if($totalsByAgent->isNotEmpty())
<div class="ad-card" style="margin-bottom:18px;">
    <div class="ad-card__title">Totals by Agent ({{ \Carbon\Carbon::parse($from)->format('M j') }} – {{ \Carbon\Carbon::parse($to)->format('M j, Y') }})</div>
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
        @foreach($totalsByAgent as $row)
        <div style="padding:8px 16px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;">
            <strong>{{ $row->agent->name ?? '—' }}</strong>
            <span style="margin-left:8px;background:#dbeafe;color:#1e40af;border-radius:999px;padding:1px 8px;font-size:11px;font-weight:700;">{{ number_format($row->total) }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="ad-card">
    <div class="ad-card__title">
        <span>Leads</span>
        <span style="font-size:12px;font-weight:400;color:#6b7280;">{{ $leads->total() }} total</span>
    </div>
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Type</th>
                    <th>Source</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                <tr>
                    <td style="font-size:12px;color:#6b7280;">{{ $lead->agent->name ?? '—' }}</td>
                    <td style="font-weight:600;">{{ $lead->name ?: trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')) }}</td>
                    <td style="font-size:12px;">
                        @if($lead->email)<div>{{ $lead->email }}</div>@endif
                        @if($lead->phone)<div style="color:#6b7280;">{{ $lead->phone }}</div>@endif
                    </td>
                    <td>
                        <span class="ad-badge ad-badge--{{ $lead->form_type }}">{{ $lead->formTypeLabel() }}</span>
                    </td>
                    <td style="font-size:11.5px;color:#6b7280;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $lead->source_url ? basename(parse_url($lead->source_url, PHP_URL_PATH)) : '—' }}
                    </td>
                    <td style="font-size:12px;color:#6b7280;white-space:nowrap;">{{ $lead->created_at->format('M j, g:ia') }}</td>
                    <td>
                        @if($lead->contacted_at)
                            <span class="ad-badge" style="background:#d1fae5;color:#065f46;"><i class="fa-solid fa-check" style="font-size:9px;"></i> Contacted</span>
                        @else
                            <span style="color:#9ca3af;font-size:12px;">New</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px 0;color:#6b7280;">No leads found for this filter.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leads->hasPages())
    <div style="padding:14px 0 0;display:flex;justify-content:center;">
        {{ $leads->links() }}
    </div>
    @endif
</div>

@endsection
