@extends('admin.layouts.app')

@section('title', 'Sitemaps')
@section('page-title', 'Sitemaps & Cache Warm-up')

@push('styles')
<style>
    .warmup-progress-card {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 8px;
        padding: 14px 18px;
        margin-top: 8px;
        display: none;
    }
    .warmup-progress-card.visible { display: block; }
    .warmup-bar-wrap {
        background: #e2e8f0;
        border-radius: 999px;
        height: 10px;
        overflow: hidden;
        margin: 8px 0 6px;
    }
    .warmup-bar {
        height: 100%;
        background: #2563eb;
        border-radius: 999px;
        width: 0%;
        transition: width .4s ease;
    }
    .warmup-status-text { font-size: 12px; color: #374151; }
    .warmup-url-text    { font-size: 11px; color: #6b7280; margin-top: 3px; word-break: break-all; }
    .badge-pending  { background: #fef9c3; color: #854d0e; }
    .badge-running  { background: #dbeafe; color: #1e40af; }
    .badge-done     { background: #d1fae5; color: #065f46; }
    .badge-failed   { background: #fee2e2; color: #b91c1c; }
    .badge-none     { background: #f3f4f6; color: #6b7280; }
</style>
@endpush

@section('content')

<div style="margin-bottom:18px;font-size:13px;color:#6b7280;">
    Each row shows a live agent domain, its sitemap URL count (fetched now), and the last warm-up run.
    Clicking <strong>Warm up cache</strong> crawls every sitemap URL in the background — closing this tab will not stop the process.
</div>

<div class="ad-card">
    <div class="ad-table-wrap">
        <table class="ad-table" id="sitemaps-table">
            <thead>
                <tr>
                    <th>Agent / Domain</th>
                    <th style="text-align:right;">Sitemap URLs</th>
                    <th>Last Run</th>
                    <th>Last Run Time</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $agent)
                @php
                    $run    = $latestRuns[$agent->domain] ?? null;
                    $count  = $urlCounts[$agent->domain]  ?? null;
                    $status = $run ? $run->status : 'none';
                    $runId  = $run ? $run->id : null;
                    $isRunning = in_array($status, ['pending', 'running']);
                @endphp
                <tr data-domain="{{ $agent->domain }}" data-run-id="{{ $runId ?? '' }}">
                    <td>
                        <div style="font-weight:600;">{{ $agent->name }}</div>
                        <div style="font-size:12px;margin-top:2px;">
                            <a href="https://{{ $agent->domain }}" target="_blank" rel="noopener" style="color:#2563eb;">
                                {{ $agent->domain }}
                            </a>
                            &nbsp;·&nbsp;
                            <a href="https://{{ $agent->domain }}/sitemap.xml" target="_blank" rel="noopener" style="color:#6b7280;font-size:11px;">
                                sitemap.xml ↗
                            </a>
                        </div>
                        {{-- Progress card (hidden by default, shown when a run is active) --}}
                        <div class="warmup-progress-card {{ $isRunning ? 'visible' : '' }}" id="progress-{{ $agent->domain }}">
                            <div class="warmup-bar-wrap"><div class="warmup-bar" id="bar-{{ $agent->domain }}"></div></div>
                            <div class="warmup-status-text" id="status-text-{{ $agent->domain }}">Starting…</div>
                            <div class="warmup-url-text"    id="url-text-{{ $agent->domain }}"></div>
                        </div>
                    </td>
                    <td style="text-align:right;font-weight:700;">
                        @if($count !== null)
                            {{ number_format($count) }}
                        @else
                            <span style="color:#6b7280;font-size:12px;">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="ad-badge badge-{{ $status }}">
                            @if($status === 'none') Never run
                            @elseif($status === 'pending') Pending
                            @elseif($status === 'running') Running
                            @elseif($status === 'done')
                                Done
                                @if($run && $run->total_urls > 0)
                                    ({{ number_format($run->warmed_urls) }}/{{ number_format($run->total_urls) }})
                                @endif
                            @elseif($status === 'failed') Failed
                            @else {{ ucfirst($status) }}
                            @endif
                        </span>
                        @if($run && $run->error_count > 0)
                            <span style="font-size:11px;color:#b91c1c;margin-left:4px;">{{ $run->error_count }} errors</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:#6b7280;">
                        @if($run && $run->finished_at)
                            {{ \Carbon\Carbon::parse($run->finished_at)->diffForHumans() }}
                        @elseif($run && $run->started_at)
                            Started {{ \Carbon\Carbon::parse($run->started_at)->diffForHumans() }}
                        @else
                            —
                        @endif
                    </td>
                    <td style="text-align:right;">
                        <button
                            class="ad-btn ad-btn--blue ad-btn--sm warmup-btn"
                            data-domain="{{ $agent->domain }}"
                            {{ $isRunning ? 'disabled' : '' }}
                            style="{{ $isRunning ? 'opacity:.5;cursor:not-allowed;' : '' }}"
                        >
                            @if($isRunning)
                                <i class="fa-solid fa-spinner fa-spin"></i> Running…
                            @else
                                <i class="fa-solid fa-fire-flame-curved"></i> Warm up cache
                            @endif
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:40px 0;color:#6b7280;">
                        No active agents with a custom domain configured.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Start a warm-up run when the button is clicked
    document.querySelectorAll('.warmup-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var domain = btn.dataset.domain;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Starting…';

            fetch('/admin/sitemaps/' + encodeURIComponent(domain) + '/start', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.run_id) {
                    var row = document.querySelector('tr[data-domain="' + domain + '"]');
                    if (row) row.dataset.runId = data.run_id;
                    showProgress(domain);
                    startPolling(domain, data.run_id);
                }
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-fire-flame-curved"></i> Warm up cache';
                alert('Failed to start warm-up. Please try again.');
            });
        });
    });

    // Resume polling for any already-running rows on page load
    document.querySelectorAll('tr[data-run-id]').forEach(function (row) {
        var runId  = row.dataset.runId;
        var domain = row.dataset.domain;
        if (!runId || !domain) return;

        var btn = row.querySelector('.warmup-btn');
        if (btn && btn.disabled) {
            showProgress(domain);
            startPolling(domain, runId);
        }
    });

    function showProgress(domain) {
        var card = document.getElementById('progress-' + domain);
        if (card) card.classList.add('visible');
    }

    function startPolling(domain, runId) {
        var interval = setInterval(function () {
            fetch('/admin/sitemaps/status/' + runId, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                updateProgress(domain, d);
                if (d.status === 'done' || d.status === 'failed') {
                    clearInterval(interval);
                    finishRun(domain, d);
                }
            })
            .catch(function () { /* ignore transient errors — keep polling */ });
        }, 2000);
    }

    function updateProgress(domain, d) {
        var bar        = document.getElementById('bar-' + domain);
        var statusText = document.getElementById('status-text-' + domain);
        var urlText    = document.getElementById('url-text-' + domain);

        var pct = d.total_urls > 0 ? Math.round((d.warmed_urls / d.total_urls) * 100) : 0;
        if (bar)        bar.style.width = pct + '%';
        if (statusText) statusText.textContent = d.warmed_urls + ' / ' + (d.total_urls || '?') + ' URLs warmed (' + pct + '%)';
        if (urlText)    urlText.textContent = d.current_url || '';
    }

    function finishRun(domain, d) {
        var btn = document.querySelector('.warmup-btn[data-domain="' + domain + '"]');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-fire-flame-curved"></i> Warm up again';
            btn.style.opacity = '1';
            btn.style.cursor  = 'pointer';
        }

        var bar        = document.getElementById('bar-' + domain);
        var statusText = document.getElementById('status-text-' + domain);
        var urlText    = document.getElementById('url-text-' + domain);

        if (d.status === 'done') {
            if (bar)        bar.style.background = '#10b981';
            if (statusText) statusText.textContent = 'Done — ' + d.warmed_urls + ' URLs warmed' + (d.error_count > 0 ? ' (' + d.error_count + ' errors)' : '');
        } else {
            if (bar)        bar.style.background = '#ef4444';
            if (statusText) statusText.textContent = 'Failed after ' + d.warmed_urls + ' URLs.';
        }
        if (urlText) urlText.textContent = '';
    }
})();
</script>
@endpush
