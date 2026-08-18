@extends('admin.layouts.app')

@section('title', 'Feature Flags')
@section('page-title', 'Feature Flags')

@section('content')

<p style="font-size:13px;color:#6b7280;margin-bottom:18px;">
    Toggle per-agent features on or off. Changes take effect within 5 minutes.
</p>

<div class="ad-card">
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead>
                <tr>
                    <th style="min-width:160px;">Agent</th>
                    @foreach($features as $key => $label)
                        <th style="text-align:center;min-width:140px;">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $agent)
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:13px;">{{ $agent->name }}</div>
                        <span class="ad-badge ad-badge--{{ $agent->status }}" style="margin-top:3px;">{{ ucfirst($agent->status) }}</span>
                    </td>
                    @foreach($features as $key => $label)
                    @php
                        $feat    = $agent->features->firstWhere('feature_key', $key);
                        $enabled = $feat?->enabled ?? false;
                    @endphp
                    <td style="text-align:center;">
                        <button
                            class="ff-toggle ad-badge {{ $enabled ? 'ad-badge--on' : 'ad-badge--off' }}"
                            style="cursor:pointer;border:none;padding:4px 12px;font-size:12px;"
                            data-agent="{{ $agent->id }}"
                            data-feature="{{ $key }}"
                            data-enabled="{{ $enabled ? '1' : '0' }}"
                            title="{{ $enabled ? 'Click to disable' : 'Click to enable' }}"
                        >
                            {{ $enabled ? 'On' : 'Off' }}
                        </button>
                    </td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($features) + 1 }}" style="text-align:center;padding:40px 0;color:#6b7280;">
                        No agents found. <a href="{{ route('admin.agents.create') }}" style="color:#2563eb;">Add one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.ff-toggle').forEach(btn => {
    btn.addEventListener('click', async function () {
        const agentId = this.dataset.agent;
        const feature = this.dataset.feature;
        this.disabled = true;

        try {
            const res = await fetch(`/admin/agents/${agentId}/features/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({ feature_key: feature }),
            });

            if (!res.ok) throw new Error('Server error');
            const data = await res.json();

            this.dataset.enabled = data.enabled ? '1' : '0';
            this.textContent = data.enabled ? 'On' : 'Off';
            this.className = 'ff-toggle ad-badge ' + (data.enabled ? 'ad-badge--on' : 'ad-badge--off');
            this.title = data.enabled ? 'Click to disable' : 'Click to enable';
        } catch (e) {
            alert('Failed to toggle feature. Please try again.');
        } finally {
            this.disabled = false;
        }
    });
});
</script>
@endpush

@endsection
