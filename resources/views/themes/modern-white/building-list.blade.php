@extends('themes.modern-white.layout')

@section('head')
@if($buildings->count() > 0)
@php
$itemListElements = [];
foreach ($buildings->items() as $i => $b) {
    $bName = $b->name ?: $b->complex ?: trim(($b->street_no ?? '') . ' ' . ($b->street_name ?? ''));
    $bUrl  = route('agent.building', ['agentSlug' => $agent->slug, 'buildingSlug' => $b->slug]);
    $itemListElements[] = [
        '@@type'    => 'ListItem',
        'position'  => ($buildings->currentPage() - 1) * $buildings->perPage() + $i + 1,
        'name'      => $bName,
        'url'       => $bUrl,
    ];
}
$jsonLd = [
    '@@context' => 'https://schema.org',
    '@@type'    => 'ItemList',
    'name'      => 'Condos & Buildings in ' . $territories->keys()->implode(', '),
    'numberOfItems' => $buildings->total(),
    'itemListElement' => $itemListElements,
];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@endsection

@push('styles')
<style>
/* ── Buildings toolbar ── */
.bldg-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}
.bldg-toolbar__view-btns {
  display: flex;
  gap: 4px;
}
.bldg-view-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border: 1px solid var(--border, #e2e8f0);
  background: #fff;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  color: var(--muted, #64748b);
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
  line-height: 1;
}
.bldg-view-btn:hover {
  border-color: var(--accent, #c9a96e);
  color: var(--accent, #c9a96e);
}
.bldg-view-btn.is-active {
  background: var(--accent, #c9a96e);
  border-color: var(--accent, #c9a96e);
  color: #fff;
}
.bldg-toolbar__sort {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--muted, #64748b);
}
.bldg-sort-select {
  padding: 7px 28px 7px 10px;
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 6px;
  font-size: 13px;
  background: #fff;
  color: var(--text, #1e293b);
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 8px center;
}

/* ── View containers ── */
.bldg-view--list,
.bldg-view--grid {
  display: none;
}
.bldg-view--list.is-visible,
.bldg-view--grid.is-visible {
  display: block;
}
.bldg-view--grid.is-visible {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}
@media (min-width: 768px) {
  .bldg-view--grid.is-visible {
    grid-template-columns: repeat(3, 1fr);
  }
}
@media (min-width: 1024px) {
  .bldg-view--grid.is-visible {
    grid-template-columns: repeat(4, 1fr);
  }
}
</style>
@endpush

@section('content')
<div class="page-header" style="padding-top:48px;padding-bottom:32px;">
  <div class="container">
    <p class="eyebrow mb-8">Condo Directory</p>
    <h1 class="page-header__title">Buildings &amp; Condos</h1>
    <p class="page-header__sub" style="color:var(--muted);font-size:15px;margin-top:8px;">
      {{ $buildings->total() }} buildings in {{ $territories->keys()->implode(', ') }}
    </p>
  </div>
</div>

<div class="container">
  <section class="section">
    @if($buildings->count() > 0)

      {{-- Toolbar: view toggle + sort --}}
      <div class="bldg-toolbar">
        <div class="bldg-toolbar__view-btns">
          <button class="bldg-view-btn" id="btn-list" data-view="list" aria-label="List view">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            List
          </button>
          <button class="bldg-view-btn" id="btn-grid" data-view="grid" aria-label="Grid view">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            Grid
          </button>
        </div>
        <div class="bldg-toolbar__sort">
          <label for="bldg-sort" style="white-space:nowrap;">Sort by:</label>
          <select id="bldg-sort" class="bldg-sort-select" onchange="window.location.href=this.value">
            <option value="{{ request()->fullUrlWithQuery(['sort' => 'default', 'page' => null]) }}"
              {{ $currentSort === 'default' ? 'selected' : '' }}>Default (Active first)</option>
            <option value="{{ request()->fullUrlWithQuery(['sort' => 'popular', 'page' => null]) }}"
              {{ $currentSort === 'popular' ? 'selected' : '' }}>Most Popular</option>
          </select>
        </div>
      </div>

      {{-- LIST VIEW --}}
      <div class="bldg-view--list" id="view-list">
        <table class="building-list" role="list">
          <thead>
            <tr>
              <th>Building</th>
              <th>Neighbourhood</th>
              <th>Built</th>
              <th>Active</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($buildings as $building)
            @php
              $bldgName   = $building->name ?: $building->complex ?: trim(($building->street_no ?? '') . ' ' . ($building->street_name ?? ''));
              $bldgHref   = route('agent.building', ['agentSlug' => $agent->slug, 'buildingSlug' => $building->slug]);
              $bldgActive = (int)($building->active_count ?? 0);
            @endphp
            <tr onclick="window.location='{{ $bldgHref }}'" role="listitem" style="cursor:pointer;">
              <td>
                <div class="building-list__name">
                  <a href="{{ $bldgHref }}">{{ $bldgName }}</a>
                </div>
                <div class="building-list__address">
                  {{ trim(($building->street_no ?? '') . ' ' . ($building->street_name ?? '') . ($building->street_type ? ' ' . $building->street_type : '')) }}
                </div>
              </td>
              <td style="color:var(--muted);font-size:13px;">
                {{ $building->subarea ?: $building->city }}
              </td>
              <td style="color:var(--muted);font-size:13px;">
                {{ $building->yearbuilt ?: '—' }}
              </td>
              <td>
                @if($bldgActive > 0)
                  <span class="building-list__badge building-list__badge--active">{{ $bldgActive }} active</span>
                @else
                  <span class="building-list__badge">0 active</span>
                @endif
              </td>
              <td style="text-align:right;">
                <a href="{{ $bldgHref }}" style="font-size:12px;color:var(--muted);">View →</a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- GRID VIEW --}}
      <div class="bldg-view--grid" id="view-grid">
        @foreach($buildings as $building)
          @include('themes.modern-white.building-card-grid')
        @endforeach
      </div>

      @if($buildings->hasPages())
        <div style="margin-top:40px;text-align:center;">
          {{ $buildings->appends(['sort' => $currentSort])->links() }}
        </div>
      @endif

    @else
      <p style="color:var(--muted);text-align:center;padding:60px 0;">No buildings found in this area yet.</p>
    @endif
  </section>
</div>

<script>
(function () {
  var STORAGE_KEY = 'mw_bldg_view';
  var listEl  = document.getElementById('view-list');
  var gridEl  = document.getElementById('view-grid');
  var btnList = document.getElementById('btn-list');
  var btnGrid = document.getElementById('btn-grid');

  if (!listEl || !gridEl) return;

  function setView(view) {
    var isGrid = view === 'grid';
    listEl.classList.toggle('is-visible', !isGrid);
    gridEl.classList.toggle('is-visible',  isGrid);
    btnList.classList.toggle('is-active', !isGrid);
    btnGrid.classList.toggle('is-active',  isGrid);
    try { localStorage.setItem(STORAGE_KEY, view); } catch (e) {}
  }

  var saved = 'list';
  try { saved = localStorage.getItem(STORAGE_KEY) || 'list'; } catch (e) {}
  setView(saved);

  btnList.addEventListener('click', function () { setView('list'); });
  btnGrid.addEventListener('click', function () { setView('grid'); });
})();
</script>
@endsection
