@extends('agent-portal.layouts.app')

@section('title', 'Leads')
@section('page-title', 'Leads')

@push('styles')
<style>
.filter-bar {
    display:flex;align-items:center;gap:12px;flex-wrap:wrap;
    padding:16px 20px;background:#f9fafb;border-bottom:1px solid var(--border);
}
.filter-select, .filter-date {
    padding:8px 12px;border:1px solid var(--border);border-radius:7px;
    font-size:13px;background:#fff;color:#374151;
}
.filter-select:focus, .filter-date:focus { outline:none;border-color:var(--accent); }
</style>
@endpush

@section('content')

<div class="ap-card" style="padding:0;overflow:hidden;">
    <div class="filter-bar">
        <form method="GET" action="{{ route('agent-portal.leads') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;width:100%;">
            <select name="type" class="filter-select" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="w1" {{ request('type')=='w1'?'selected':'' }}>Showing Request</option>
                <option value="w2" {{ request('type')=='w2'?'selected':'' }}>Home Evaluation</option>
                <option value="w3" {{ request('type')=='w3'?'selected':'' }}>Pre-qualification</option>
            </select>
            <input type="date" name="from" class="filter-date" value="{{ request('from') }}" placeholder="From date">
            <input type="date" name="to"   class="filter-date" value="{{ request('to') }}"   placeholder="To date">
            <button type="submit" class="ap-btn ap-btn--outline ap-btn--sm"><i class="fa-solid fa-filter"></i> Filter</button>
            @if(request()->hasAny(['type','from','to']))
                <a href="{{ route('agent-portal.leads') }}" class="ap-btn ap-btn--outline ap-btn--sm" style="color:#6b7280;">✕ Clear</a>
            @endif
            <div style="margin-left:auto;display:flex;gap:8px;">
                <a href="{{ route('agent-portal.leads.export', request()->query()) }}" class="ap-btn ap-btn--outline ap-btn--sm">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </a>
            </div>
        </form>
    </div>

    @if($leads->isEmpty())
        <div style="text-align:center;padding:56px 24px;">
            <i class="fa-solid fa-users" style="font-size:36px;color:#d1d5db;margin-bottom:16px;display:block;"></i>
            <div style="font-weight:600;font-size:15px;margin-bottom:6px;">No leads yet</div>
            <p style="font-size:13px;color:#6b7280;">When visitors fill in forms on your site, they'll appear here.</p>
        </div>
    @else
        <div class="ap-table-wrap">
            <table class="ap-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Page / Listing</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leads as $lead)
                    <tr id="lead-{{ $lead->id }}">
                        <td style="font-weight:600;">{{ $lead->first_name }} {{ $lead->last_name }}</td>
                        <td>
                            @if($lead->phone)
                                <a href="tel:{{ $lead->phone }}" style="color:inherit;text-decoration:none;">{{ $lead->phone }}</a>
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td>
                            @if($lead->email)
                                <a href="mailto:{{ $lead->email }}" style="color:inherit;font-size:13px;text-decoration:none;">{{ $lead->email }}</a>
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="ap-badge ap-badge--{{ $lead->form_type }}">{{ $lead->formTypeLabel() }}</span>
                        </td>
                        <td style="max-width:160px;font-size:12px;color:#6b7280;">
                            @if($lead->listing_id)
                                <div>{{ $lead->listing_id }}</div>
                            @endif
                            @if($lead->source_url)
                                <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ parse_url($lead->source_url, PHP_URL_PATH) ?? $lead->source_url }}
                                </div>
                            @endif
                            @if(!$lead->listing_id && !$lead->source_url)—@endif
                        </td>
                        <td style="font-size:12px;white-space:nowrap;color:#6b7280;">
                            {{ $lead->created_at->format('M j, Y') }}<br>
                            <span style="color:#9ca3af;">{{ $lead->created_at->format('g:ia') }}</span>
                        </td>
                        <td>
                            @if($lead->contacted_at)
                                <span class="ap-badge ap-badge--contacted">
                                    <i class="fa-solid fa-check" style="font-size:9px;"></i>&nbsp;Contacted
                                </span>
                                <div style="font-size:10px;color:#9ca3af;margin-top:3px;">{{ $lead->contacted_at->diffForHumans() }}</div>
                            @else
                                <span style="font-size:12px;color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            @if(!$lead->contacted_at)
                                <button class="ap-btn ap-btn--sm ap-btn--outline" onclick="markContacted({{ $lead->id }}, this)">
                                    <i class="fa-regular fa-circle-check"></i> Mark contacted
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="padding:16px 20px;">
            {{ $leads->links() }}
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
function markContacted(id, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    fetch(`/agent-portal/leads/${id}/contacted`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        const row = document.getElementById('lead-' + id);
        // Replace status cell
        const cells = row.querySelectorAll('td');
        cells[6].innerHTML = `<span class="ap-badge ap-badge--contacted"><i class="fa-solid fa-check" style="font-size:9px;"></i>&nbsp;Contacted</span><div style="font-size:10px;color:#9ca3af;margin-top:3px;">${data.contacted_at}</div>`;
        cells[7].innerHTML = '';
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-regular fa-circle-check"></i> Mark contacted';
    });
}
</script>
@endpush
