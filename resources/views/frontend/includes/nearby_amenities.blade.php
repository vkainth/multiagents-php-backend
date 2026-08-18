{{--
    Nearby Amenities Widget ("What's Nearby")
    Usage:
        @include('frontend.includes.nearby_amenities', [
            'nearbyLat'    => $building->latitude,
            'nearbyLng'    => $building->longitude,
            'nearbyRadius' => 1500,               // optional, metres
            'nearbyTitle'  => "What's Nearby",    // optional
            'nearbyCacheSlug' => $building->slug, // optional, for cache keying
            'nearbyAddress'   => '...',           // optional, for Walk Score
        ])
--}}
@php
    $nearbyLat       = $nearbyLat       ?? null;
    $nearbyLng       = $nearbyLng       ?? null;
    $nearbyRadius    = $nearbyRadius    ?? 1500;
    $nearbyTitle     = $nearbyTitle     ?? "What's Nearby";
    $nearbyCacheSlug = $nearbyCacheSlug ?? '';
    $nearbyAddress   = $nearbyAddress   ?? '';
    $_canShowNearby  = $nearbyLat && $nearbyLng
        && (float)$nearbyLat !== 0.0
        && (float)$nearbyLng !== 0.0;
    // Unique suffix prevents DOM ID collision when partial is included multiple times
    $_wid = 'naw' . substr(md5(($nearbyLat ?? '') . ($nearbyLng ?? '') . ($nearbyCacheSlug ?: rand())), 0, 8);
@endphp
@if($_canShowNearby)
<div id="{{ $_wid }}"
     class="nearby-amenities-widget"
     data-lat="{{ $nearbyLat }}"
     data-lng="{{ $nearbyLng }}"
     data-radius="{{ (int)$nearbyRadius }}"
     data-slug="{{ $nearbyCacheSlug }}"
     data-address="{{ htmlspecialchars($nearbyAddress, ENT_QUOTES) }}"
     style="display:none;">

    <div class="building-detail__title" style="margin-bottom:10px;">
        <h2>{{ $nearbyTitle }}</h2>
    </div>

    {{-- Tab navigation --}}
    <div class="nearby-tabs" role="tablist" aria-label="Nearby amenities">
        <button class="nearby-tab-btn active" data-tab="schools"   role="tab" aria-selected="true">
            <span class="nearby-tab-icon" aria-hidden="true">🏫</span> Schools
        </button>
        <button class="nearby-tab-btn" data-tab="parks"     role="tab" aria-selected="false">
            <span class="nearby-tab-icon" aria-hidden="true">🌳</span> Parks &amp; Rec
        </button>
        <button class="nearby-tab-btn" data-tab="transit"   role="tab" aria-selected="false">
            <span class="nearby-tab-icon" aria-hidden="true">🚌</span> Transit
        </button>
        <button class="nearby-tab-btn" data-tab="groceries" role="tab" aria-selected="false">
            <span class="nearby-tab-icon" aria-hidden="true">🛒</span> Groceries &amp; Dining
        </button>
    </div>

    {{-- Loading state --}}
    <div class="nearby-loading" style="padding:20px 0;text-align:center;color:#888;font-size:14px;">
        <span>Loading nearby places…</span>
    </div>

    {{-- Tab panels --}}
    <div class="nearby-panels" style="display:none;">
        @foreach(['schools','parks','transit','groceries'] as $_tab)
        <div class="nearby-panel" data-panel="{{ $_tab }}" role="tabpanel"
             style="{{ $_tab !== 'schools' ? 'display:none;' : '' }}">
            <ul class="nearby-poi-list" data-list="{{ $_tab }}"></ul>
            <p class="nearby-empty" data-empty="{{ $_tab }}" style="display:none;color:#888;font-size:13px;padding:10px 0;">
                No results found nearby.
            </p>
        </div>
        @endforeach
    </div>

    {{-- Walk Score badge — only shown when API key is configured and data is returned --}}
    <div class="nearby-walkscore" style="display:none;margin-top:14px;padding-top:12px;border-top:1px solid #eee;">
        <div class="nearby-walkscore-badges" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;"></div>
        <div style="font-size:11px;color:#aaa;margin-top:6px;">
            Walk Score data powered by <a href="https://www.walkscore.com" target="_blank" rel="noopener" style="color:#337ab7;">Walk Score</a>.
        </div>
    </div>
</div>

@once
@push('after-styles')
<style>
.nearby-amenities-widget { margin-top: 0; }
.nearby-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    border-bottom: 2px solid #e0e0e0;
    margin-bottom: 14px;
}
.nearby-tab-btn {
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 600;
    color: #555;
    cursor: pointer;
    margin-bottom: -2px;
    transition: color .15s, border-color .15s;
    white-space: nowrap;
}
.nearby-tab-btn:hover  { color: #2c6fad; }
.nearby-tab-btn.active { color: #2c6fad; border-bottom-color: #2c6fad; }
.nearby-tab-icon       { font-size: 14px; }
.nearby-poi-list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.nearby-poi-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 4px;
    border-bottom: 1px solid #f2f2f2;
    gap: 8px;
}
.nearby-poi-item:last-child  { border-bottom: none; }
.nearby-poi-item--pinned     { background: #f9f7f2; }
.nearby-poi-name  { font-size: 13px; color: #333; flex: 1; }
.nearby-poi-meta  { font-size: 12px; color: #888; white-space: nowrap; text-align: right; }
.nearby-poi-dist  { font-weight: 600; color: #555; }
.nearby-catchment-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    background: #e8f4fd;
    color: #1a6fa0;
    border: 1px solid #b3d9f5;
    border-radius: 3px;
    padding: 1px 5px;
    margin-right: 4px;
    white-space: nowrap;
}
.nearby-ws-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f7f7f7;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 12px;
    color: #333;
}
.nearby-ws-score {
    font-size: 22px;
    font-weight: 800;
    color: #2c6fad;
    line-height: 1;
}
.nearby-ws-label { font-size: 11px; color: #666; }
@media (max-width: 480px) {
    .nearby-tab-btn  { padding: 7px 10px; font-size: 12px; }
    .nearby-tab-icon { display: none; }
}
</style>
@endpush
@endonce

@push('after-scripts')
<script>
(function () {
    var widget = document.getElementById({{ json_encode($_wid) }});
    if (!widget) return;

    var lat     = parseFloat(widget.dataset.lat     || '0');
    var lng     = parseFloat(widget.dataset.lng     || '0');
    var radius  = parseInt(widget.dataset.radius    || '1500', 10);
    var slug    = widget.dataset.slug    || '';
    var address = widget.dataset.address || '';
    if (!lat || !lng) return;

    var tabBtns    = widget.querySelectorAll('.nearby-tab-btn');
    var panels     = widget.querySelectorAll('.nearby-panel');
    var loading    = widget.querySelector('.nearby-loading');
    var panelsWrap = widget.querySelector('.nearby-panels');

    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tab = btn.dataset.tab;
            tabBtns.forEach(function (b) {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            panels.forEach(function (p) { p.style.display = 'none'; });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');
            var panel = widget.querySelector('[data-panel="' + tab + '"]');
            if (panel) panel.style.display = '';
        });
    });

    function esc(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function formatDist(m) {
        return m >= 1000 ? (m / 1000).toFixed(1) + ' km' : m + ' m';
    }

    function renderTab(tab, items) {
        var list  = widget.querySelector('[data-list="'  + tab + '"]');
        var empty = widget.querySelector('[data-empty="' + tab + '"]');
        if (!list) return;
        if (!items || !items.length) {
            if (empty) empty.style.display = '';
            return;
        }
        var html = '';
        items.forEach(function (poi) {
            var isCatchment = poi.type === 'catchment';
            html += '<li class="nearby-poi-item' + (isCatchment ? ' nearby-poi-item--pinned' : '') + '">'
                  + '<span class="nearby-poi-name">'
                  + (isCatchment ? '<span class="nearby-catchment-badge">Catchment</span>' : '')
                  + esc(poi.name)
                  + '</span>'
                  + '<span class="nearby-poi-meta">'
                  + '<span class="nearby-poi-dist">' + (poi.distance > 0 ? formatDist(poi.distance) : '') + '</span>'
                  + (poi.distance > 0 ? ' &nbsp;·&nbsp; ~' + poi.walk_time + ' min walk' : '')
                  + '</span>'
                  + '</li>';
        });
        list.innerHTML = html;
    }

    function renderWalkScore(ws) {
        if (!ws) return;
        var container = widget.querySelector('.nearby-walkscore');
        var badges    = widget.querySelector('.nearby-walkscore-badges');
        if (!container || !badges) return;

        var html = '';
        if (ws.walk && ws.walk.score !== null) {
            html += '<div class="nearby-ws-badge">'
                  + '<span class="nearby-ws-score">' + ws.walk.score + '</span>'
                  + '<span class="nearby-ws-label">Walk<br>Score</span>'
                  + '</div>';
        }
        if (ws.transit && ws.transit.score !== null) {
            html += '<div class="nearby-ws-badge">'
                  + '<span class="nearby-ws-score">' + ws.transit.score + '</span>'
                  + '<span class="nearby-ws-label">Transit<br>Score</span>'
                  + '</div>';
        }
        if (ws.bike && ws.bike.score !== null) {
            html += '<div class="nearby-ws-badge">'
                  + '<span class="nearby-ws-score">' + ws.bike.score + '</span>'
                  + '<span class="nearby-ws-label">Bike<br>Score</span>'
                  + '</div>';
        }
        if (!html) return;
        badges.innerHTML = html;
        container.style.display = '';
    }

    var params = 'lat=' + lat + '&lng=' + lng + '&radius=' + radius;
    if (slug)    params += '&slug='    + encodeURIComponent(slug);
    if (address) params += '&address=' + encodeURIComponent(address);

    fetch('/nearby-amenities?' + params, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
        .then(function (json) {
            if (!json.ok || !json.has_data) return;
            var data = json.data || {};
            ['schools', 'parks', 'transit', 'groceries'].forEach(function (tab) {
                renderTab(tab, data[tab] || []);
            });
            renderWalkScore(json.walk_score || null);
            if (loading)    loading.style.display    = 'none';
            if (panelsWrap) panelsWrap.style.display = '';
            widget.style.display = '';
        })
        .catch(function () {
            /* silently hide on error — graceful degradation */
        });
}());
</script>
@endpush
@endif
