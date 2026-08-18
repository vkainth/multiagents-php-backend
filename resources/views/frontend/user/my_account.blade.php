@extends('frontend.layouts.default_mobile')
@section('title', 'My Account | BC Condos And Homes')
@push('after-styles')
<style>
.myacc-wrap { background:#fff; min-height:80vh; padding:28px 0 60px; margin-top:66px; }
.ca-panel { background:#f8fbff; border:1px solid #d0e4f7; border-radius:10px; padding:22px 24px; margin-bottom:24px; }
.ca-panel h3 { font-size:16px; font-weight:700; color:#1a2a3a; margin:0 0 18px; }
.ca-filters { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:12px; margin-bottom:14px; }
.ca-field label { display:block; font-size:11px; font-weight:700; color:#888; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.ca-field select, .ca-field input[type=text], .ca-field input[type=number] { width:100%; border:1px solid #cdd8e3; border-radius:5px; padding:8px 10px; font-size:13px; color:#231f20; background:#fff; }
.ca-field input[type=number] { -moz-appearance:textfield; }
.ca-name-row { margin-bottom:14px; }
.ca-name-row input { width:100%; border:1px solid #cdd8e3; border-radius:5px; padding:9px 12px; font-size:14px; font-weight:600; color:#231f20; }
.ca-preview { margin-top:20px; border-top:1px solid #d0e4f7; padding-top:16px; }
.ca-count { font-size:13px; color:#555; margin-bottom:12px; font-weight:600; }
.ca-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:12px; }
.ca-card { background:#fff; border:1px solid #e2dbd2; border-radius:7px; overflow:hidden; font-size:12px; text-decoration:none; color:#231f20; display:block; transition:box-shadow .15s; }
.ca-card:hover { box-shadow:0 3px 12px rgba(0,0,0,.12); color:#231f20; text-decoration:none; }
.ca-card__img { width:100%; aspect-ratio:4/3; object-fit:cover; background:#f0ede8; display:block; }
.ca-card__body { padding:8px 10px; }
.ca-card__price { font-size:14px; font-weight:700; margin-bottom:2px; }
.ca-card__addr { color:#666; font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ca-card__meta { color:#999; font-size:11px; margin-top:2px; }
.ca-actions { display:flex; align-items:center; gap:12px; margin-top:16px; flex-wrap:wrap; }
.ca-save-btn { background:#2c6fad; color:#fff; border:none; border-radius:5px; padding:10px 22px; font-size:14px; font-weight:700; cursor:pointer; }
.ca-save-btn:disabled { opacity:.5; cursor:not-allowed; }
.ca-cancel-btn { background:transparent; border:none; color:#888; font-size:13px; cursor:pointer; text-decoration:underline; }
.myacc-tabs { display:flex; gap:0; border-bottom:2px solid #e2dbd2; margin-bottom:28px; overflow-x:auto; }
.myacc-tab { padding:11px 20px; font-size:14px; font-weight:600; color:#666; cursor:pointer; white-space:nowrap; border-bottom:3px solid transparent; margin-bottom:-2px; text-decoration:none; display:block; }
.myacc-tab.active, .myacc-tab:hover { color:#2c6fad; border-bottom-color:#2c6fad; text-decoration:none; }
.myacc-section { display:none; }
.myacc-section.active { display:block; }
.myacc-card { background:#fff; border:1px solid #e2dbd2; border-radius:8px; box-shadow:0 1px 6px rgba(0,0,0,.06); margin-bottom:16px; overflow:hidden; }
.myacc-empty { text-align:center; padding:48px 20px; color:#aaa; font-size:15px; }
.myacc-empty a { color:#2c6fad; }
.badge-active { background:#27ae60; color:#fff; font-size:11px; font-weight:700; padding:2px 7px; border-radius:3px; display:inline-block; }
.badge-sold { background:#c0392b; color:#fff; font-size:11px; font-weight:700; padding:2px 7px; border-radius:3px; display:inline-block; }
.badge-paused { background:#f5c842; color:#333; font-size:11px; font-weight:700; padding:2px 7px; border-radius:3px; display:inline-block; }
.badge-building { background:#2c6fad; color:#fff; font-size:11px; font-weight:700; padding:2px 7px; border-radius:3px; display:inline-block; }
.badge-search { background:#8e44ad; color:#fff; font-size:11px; font-weight:700; padding:2px 7px; border-radius:3px; display:inline-block; }
.myacc-btn { display:inline-block; padding:7px 14px; border-radius:4px; font-size:13px; font-weight:600; border:none; cursor:pointer; text-decoration:none; }
.myacc-btn-blue { background:#2c6fad; color:#fff; }
.myacc-btn-red { background:#c0392b; color:#fff; }
.myacc-btn-outline { background:#fff; color:#2c6fad; border:1px solid #2c6fad; }
.fav-card { display:flex; gap:0; background:#fff; border:1px solid #e2dbd2; border-radius:8px; overflow:hidden; margin-bottom:14px; box-shadow:0 1px 6px rgba(0,0,0,.05); }
.fav-card__img { width:130px; min-width:130px; background:#f0ede8 no-repeat center/cover; position:relative; }
@media (max-width:480px){ .fav-card__img { width:90px; min-width:90px; } }
.fav-card__body { flex:1; padding:14px 16px; min-width:0; }
.fav-card__price { font-size:18px; font-weight:700; color:#231f20; margin-bottom:4px; }
.fav-card__addr { font-size:13px; color:#555; margin-bottom:4px; }
.fav-card__meta { font-size:12px; color:#888; }
.fav-card__actions { display:flex; gap:8px; margin-top:10px; flex-wrap:wrap; }
.toggle-switch { position:relative; display:inline-block; width:42px; height:22px; }
.toggle-switch input { opacity:0; width:0; height:0; }
.toggle-slider { position:absolute; inset:0; background:#ccc; border-radius:22px; cursor:pointer; transition:.3s; }
.toggle-slider:before { content:''; position:absolute; width:18px; height:18px; left:2px; bottom:2px; background:#fff; border-radius:50%; transition:.3s; }
input:checked + .toggle-slider { background:#2c6fad; }
input:checked + .toggle-slider:before { transform:translateX(20px); }
</style>
@endpush
@section('content')
@include('frontend.includes.header')
<div class="myacc-wrap">
  <div class="container">

    <div style="margin-bottom:20px;">
      <h1 style="font-size:22px;font-weight:700;color:#231f20;margin:0 0 4px;">My Account</h1>
      <p style="font-size:13px;color:#888;margin:0;">Welcome back, {{ $user->first ?? $user->email }}</p>
    </div>

    {{-- Tab navigation --}}
    <div class="myacc-tabs">
      <a href="?tab=alerts" class="myacc-tab {{ $tab === 'alerts' ? 'active' : '' }}">🔔 Alerts</a>
      <a href="?tab=favourites" class="myacc-tab {{ $tab === 'favourites' ? 'active' : '' }}">❤ Favourites</a>
      <a href="?tab=history" class="myacc-tab {{ $tab === 'history' ? 'active' : '' }}">📋 Alert History</a>
    </div>

    {{-- ===== ALERTS TAB ===== --}}
    <div class="myacc-section {{ $tab === 'alerts' ? 'active' : '' }}" id="tab-alerts">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <h2 style="font-size:17px;font-weight:700;margin:0;color:#333;">Saved Search Alerts</h2>
        <button onclick="caToggle()" id="ca-toggle-btn" class="myacc-btn myacc-btn-blue">+ Create New Alert</button>
      </div>

      {{-- ─── Create Alert Panel ─── --}}
      <div class="ca-panel" id="ca-panel" style="display:none;">
        <h3>Create New Alert</h3>

        {{-- Alert name --}}
        <div class="ca-name-row">
          <div class="ca-field">
            <label>Alert Name</label>
            <input type="text" id="ca-name" placeholder="e.g. 2-bed Condos in Vancouver" maxlength="120">
          </div>
        </div>

        {{-- Filters --}}
        <div class="ca-filters">
          <div class="ca-field">
            <label>City</label>
            <select id="ca-city" onchange="caLoadSubareas();caAutoName();caPreview();">
              <option value="">Any City</option>
              @foreach(\App\Helpers\Helper::getCityList() as $c)
              <option value="{{ $c }}">{{ $c }}</option>
              @endforeach
            </select>
          </div>
          <div class="ca-field">
            <label>Neighbourhood</label>
            <select id="ca-subarea" onchange="caAutoName();caPreview();" disabled style="opacity:.6;">
              <option value="">Select a city first</option>
            </select>
          </div>
          <div class="ca-field">
            <label>Property Type</label>
            <select id="ca-type" onchange="caAutoName();caPreview();">
              <option value="">Any Type</option>
              <option value="Apartment">Apartment / Condo</option>
              <option value="Townhouse">Townhouse</option>
              <option value="House">House</option>
              <option value="1/2 Duplex">1/2 Duplex</option>
              <option value="Duplex">Duplex</option>
            </select>
          </div>
          <div class="ca-field">
            <label>Min Bedrooms</label>
            <select id="ca-beds" onchange="caAutoName();caPreview();">
              <option value="0">Any</option>
              <option value="1">1+</option>
              <option value="2">2+</option>
              <option value="3">3+</option>
              <option value="4">4+</option>
              <option value="5">5+</option>
            </select>
          </div>
          <div class="ca-field">
            <label>Min Price</label>
            <input type="number" id="ca-min-price" placeholder="e.g. 500000" min="0" step="50000" oninput="caDebouncedPreview()">
          </div>
          <div class="ca-field">
            <label>Max Price</label>
            <input type="number" id="ca-max-price" placeholder="e.g. 1500000" min="0" step="50000" oninput="caDebouncedPreview()">
          </div>
        </div>

        {{-- Email toggle --}}
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
          <label class="toggle-switch" title="Daily email notifications">
            <input type="checkbox" id="ca-email" checked>
            <span class="toggle-slider"></span>
          </label>
          <span style="font-size:13px;color:#555;">Daily email notifications</span>
        </div>

        {{-- Actions --}}
        <div class="ca-actions">
          <button class="ca-save-btn" id="ca-save-btn" onclick="caSave()">Create Alert</button>
          <button class="ca-cancel-btn" onclick="caToggle()">Cancel</button>
          <span id="ca-save-msg" style="font-size:13px;display:none;"></span>
        </div>

        {{-- Live preview --}}
        <div class="ca-preview" id="ca-preview" style="display:none;">
          <div class="ca-count" id="ca-count-label"></div>
          <div class="ca-grid" id="ca-grid"></div>
        </div>
      </div>
      {{-- ─── End Create Alert Panel ─── --}}

      @if($savedSearches->isNotEmpty())
        @foreach($savedSearches as $search)
        @php
          $cdata = json_decode($search->data ?? '{}', true) ?: [];
          $parts = array_filter([
            isset($cdata['cities']) && $cdata['cities'] ? $cdata['cities'] : null,
            isset($cdata['subareas']) && $cdata['subareas'] ? $cdata['subareas'] : null,
            isset($cdata['type']) && $cdata['type'] ? (is_array($cdata['type']) ? implode(', ', $cdata['type']) : $cdata['type']) : null,
            (isset($cdata['min_beds']) && $cdata['min_beds']) ? $cdata['min_beds'].'+ bed' : null,
            (isset($cdata['min_price']) || isset($cdata['max_price']))
              ? ('$'.number_format($cdata['min_price']??0).' – $'.number_format($cdata['max_price']??0))
              : null,
            isset($cdata['status']) && $cdata['status'] ? $cdata['status'] : null,
          ]);
          $summary = implode(' · ', $parts) ?: 'Saved search';
        @endphp
        <div class="myacc-card" id="alert-row-{{ $search->id }}">
          <div style="padding:14px 18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div style="flex:1;min-width:180px;">
              <div style="font-size:14px;font-weight:700;color:#231f20;margin-bottom:3px;">
                {{ $search->search_name }}
                @if(!($search->active ?? 1))
                  <span class="badge-paused" style="margin-left:6px;">Paused</span>
                @endif
              </div>
              <div style="font-size:12px;color:#888;">{{ $summary }}</div>
              @if($search->last_update_sent)
              <div style="font-size:11px;color:#aaa;margin-top:3px;">Last alerted: {{ \Carbon\Carbon::parse($search->last_update_sent)->format('M j, Y') }}</div>
              @endif
            </div>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
              @if(!($search->active ?? 1))
                <button onclick="reactivateAlert('search', {{ $search->id }}, this)" class="myacc-btn myacc-btn-outline">Reactivate</button>
              @else
                <label class="toggle-switch" title="Email alerts on/off">
                  <input type="checkbox" {{ $search->daily_email ? 'checked' : '' }}
                    onchange="toggleSearchAlert({{ $search->id }}, this.checked)">
                  <span class="toggle-slider"></span>
                </label>
                <span style="font-size:12px;color:#888;">Daily email</span>
              @endif
              <button onclick="deleteSearch({{ $search->id }}, this)" class="myacc-btn myacc-btn-red">Delete</button>
            </div>
          </div>
        </div>
        @endforeach
      @else
        <div class="myacc-empty">
          No alerts yet — <a href="/mapsearch">try saving a search</a> to get notified of new listings.
        </div>
      @endif
    </div>

    {{-- ===== FAVOURITES TAB ===== --}}
    <div class="myacc-section {{ $tab === 'favourites' ? 'active' : '' }}" id="tab-favourites">
      <div style="margin-bottom:16px;">
        <h2 style="font-size:17px;font-weight:700;margin:0;color:#333;">Saved Listings</h2>
      </div>

      @if($favorites->isNotEmpty())
        @foreach($favorites as $fav)
        @if($fav->listing)
        @php
          $lst = $fav->listing;
          $photo = $lst->photos()->first();
          $photoUrl = $photo
            ? 'https://media.pixilinkserver.com/' . str_replace('images', '', $photo->directory . $photo->name) . '?w=260'
            : asset('assets/img/no-image.jpg');
          $price = $lst->status === 'Sold'
            ? '$' . number_format($lst->soldprice_2)
            : $lst->listprice;
          $baths = ($lst->full_baths ?? 0) + ($lst->half_baths ?? 0);
          $dom = $lst->days_on_market();
          $addr = trim(($lst->suite_no ? $lst->suite_no . ' – ' : '') . $lst->street_number . ' ' . $lst->street_name . ' ' . $lst->street_type);
        @endphp
        <div class="fav-card" id="fav-row-{{ $fav->id }}">
          <div class="fav-card__img" style="background-image:url('{{ $photoUrl }}');">
            @if($lst->status === 'Sold')
            <span class="badge-sold" style="position:absolute;bottom:6px;left:6px;">Sold</span>
            @else
            <span class="badge-active" style="position:absolute;bottom:6px;left:6px;">Active</span>
            @endif
          </div>
          <div class="fav-card__body">
            <div class="fav-card__price">{{ $price }}</div>
            <div class="fav-card__addr">{{ $addr }}<br><span style="color:#aaa;">{{ $lst->subarea }}, {{ $lst->city }}</span></div>
            <div class="fav-card__meta">
              @if($lst->bedrooms) {{ $lst->bedrooms }} bd @endif
              @if($baths) &middot; {{ $baths }} ba @endif
              @if($lst->livingarea_2 > 0) &middot; {{ number_format($lst->livingarea_2) }} sqft @endif
              @if($dom) &middot; {{ $dom }} days on market @endif
            </div>
            <div class="fav-card__actions">
              <a href="{{ route('listing-detail-page2', ['slug'=>$lst->slug]) }}" class="myacc-btn myacc-btn-blue" style="font-size:12px;padding:6px 12px;">View Listing</a>
              <button onclick="removeFavourite({{ $fav->id }}, this)" class="myacc-btn myacc-btn-red" style="font-size:12px;padding:6px 12px;">Remove</button>
            </div>
          </div>
        </div>
        @endif
        @endforeach
      @else
        <div class="myacc-empty">
          No saved listings yet — heart a listing to save it here.
        </div>
      @endif
    </div>

    {{-- Building Follows tab removed (Task #543) --}}

    {{-- ===== ALERT HISTORY TAB ===== --}}
    <div class="myacc-section {{ $tab === 'history' ? 'active' : '' }}" id="tab-history">
      <div style="margin-bottom:16px;">
        <h2 style="font-size:17px;font-weight:700;margin:0;color:#333;">Alert History</h2>
      </div>

      @if($alertHistory->isNotEmpty())
        <div class="myacc-card">
          <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
              <thead>
                <tr style="background:#f7f4ef;color:#888;">
                  <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;font-weight:700;letter-spacing:.04em;">Date Sent</th>
                  <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;font-weight:700;letter-spacing:.04em;">Type</th>
                  <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;font-weight:700;letter-spacing:.04em;">For</th>
                  <th style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;font-weight:700;letter-spacing:.04em;">Listings</th>
                </tr>
              </thead>
              <tbody>
                @foreach($alertHistory as $h)
                @php
                  if ($h->type === 'search') {
                    $histRecord = \App\Models\SavedSearches::find($h->record_id);
                    $histLabel = $histRecord ? $histRecord->search_name : 'Search #'.$h->record_id;
                  } else {
                    $histRecord = \App\Models\BuildingFollow::find($h->record_id);
                    $histLabel = $histRecord ? ($histRecord->building_name ?: $histRecord->building_slug) : 'Building #'.$h->record_id;
                  }
                @endphp
                <tr style="border-top:1px solid #f0ede8;">
                  <td style="padding:10px 14px;color:#555;">{{ \Carbon\Carbon::parse($h->sent_at ?? $h->created_at)->format('M j, Y') }}</td>
                  <td style="padding:10px 14px;">
                    @if($h->type === 'search')
                      <span class="badge-search">Search</span>
                    @else
                      <span class="badge-building">Building</span>
                    @endif
                  </td>
                  <td style="padding:10px 14px;color:#333;font-weight:500;">{{ $histLabel }}</td>
                  <td style="padding:10px 14px;color:#555;">{{ is_array($h->listing_ids) ? count($h->listing_ids) : 0 }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @if($alertHistory->hasPages())
          <div style="padding:12px 14px;border-top:1px solid #f0ede8;">{{ $alertHistory->appends(['tab'=>'history'])->links() }}</div>
          @endif
        </div>
      @else
        <div class="myacc-empty">No alerts have been sent yet.</div>
      @endif
    </div>

  </div>
</div>
@include('frontend.includes.footer')
@endsection
@push('after-scripts')
<script>
var _csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

function toggleSearchAlert(id, enabled) {
  fetch('/api2/update_search/' + id, {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':_csrf},
    body: JSON.stringify({daily_email: enabled ? 1 : 0})
  });
}

function deleteSearch(id, btn) {
  if (!confirm('Delete this alert?')) return;
  fetch('/api2/delete_search/' + id, {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':_csrf}
  }).then(r=>r.json()).then(d=>{
    if (d.success) {
      var el = document.getElementById('alert-row-' + id);
      if (el) el.remove();
    }
  });
}

function removeFavourite(id, btn) {
  if (!confirm('Remove this listing from favourites?')) return;
  fetch('/api2/delete_favorite/' + id, {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':_csrf}
  }).then(r=>r.json()).then(d=>{
    if (d.success) {
      var el = document.getElementById('fav-row-' + id);
      if (el) el.style.opacity = '0.3';
    }
  });
}

function reactivateAlert(type, id, btn) {
  fetch('/user/reactivate-alert', {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':_csrf},
    body: JSON.stringify({type: type, id: id})
  }).then(r=>r.json()).then(d=>{
    if (d.success) { window.location.reload(); }
  });
}

/* ─── Create Alert inline form ─── */
var _caDebTimer = null;

function caToggle() {
  var panel = document.getElementById('ca-panel');
  var btn   = document.getElementById('ca-toggle-btn');
  if (panel.style.display === 'none') {
    panel.style.display = 'block';
    btn.textContent = '✕ Cancel';
    caPreview();
  } else {
    panel.style.display = 'none';
    btn.textContent = '+ Create New Alert';
  }
}

function caLoadSubareas() {
  var city = document.getElementById('ca-city').value;
  var sel  = document.getElementById('ca-subarea');
  if (!city) {
    sel.innerHTML = '<option value="">Select a city first</option>';
    sel.disabled = true;
    sel.style.opacity = '.6';
    return;
  }
  sel.innerHTML = '<option value="">Loading…</option>';
  sel.disabled = true;
  fetch('/api2/subareas?city=' + encodeURIComponent(city))
    .then(r => r.json())
    .then(d => {
      sel.innerHTML = '<option value="">Any Neighbourhood</option>';
      (d.subareas || []).forEach(function(s) {
        var o = document.createElement('option');
        o.value = s; o.textContent = s;
        sel.appendChild(o);
      });
      sel.disabled = false;
      sel.style.opacity = '1';
    })
    .catch(function() {
      sel.innerHTML = '<option value="">Any Neighbourhood</option>';
      sel.disabled = false;
      sel.style.opacity = '1';
    });
}

function caAutoName() {
  var city    = document.getElementById('ca-city').value;
  var subarea = document.getElementById('ca-subarea').value;
  var type    = document.getElementById('ca-type').value;
  var beds    = parseInt(document.getElementById('ca-beds').value, 10) || 0;
  var parts   = [];
  if (beds > 0) parts.push(beds + '+ bed');
  if (type) {
    var typeLabel = {
      'Apartment': 'Condos', 'Townhouse': 'Townhouses',
      'House': 'Houses', '1/2 Duplex': 'Duplexes', 'Duplex': 'Duplexes'
    }[type] || type;
    parts.push(typeLabel);
  } else {
    parts.push('Listings');
  }
  if (subarea) parts.push('in ' + subarea);
  else if (city) parts.push('in ' + city);
  var nameEl = document.getElementById('ca-name');
  if (!nameEl._userEdited) {
    nameEl.value = parts.join(' ');
  }
}

document.addEventListener('DOMContentLoaded', function() {
  var nameEl = document.getElementById('ca-name');
  if (nameEl) {
    nameEl.addEventListener('input', function() { nameEl._userEdited = true; });
  }
});

function caPreview() {
  var city     = document.getElementById('ca-city').value;
  var subarea  = document.getElementById('ca-subarea').value;
  var type     = document.getElementById('ca-type').value;
  var beds     = document.getElementById('ca-beds').value;
  var minPrice = document.getElementById('ca-min-price').value;
  var maxPrice = document.getElementById('ca-max-price').value;

  var qs = new URLSearchParams();
  if (city)     qs.set('city',      city);
  if (subarea)  qs.set('subarea',   subarea);
  if (type)     qs.set('type',      type);
  if (beds > 0) qs.set('min_beds',  beds);
  if (minPrice) qs.set('min_price', minPrice);
  if (maxPrice) qs.set('max_price', maxPrice);

  var previewEl = document.getElementById('ca-preview');
  var countEl   = document.getElementById('ca-count-label');
  var gridEl    = document.getElementById('ca-grid');

  countEl.textContent = 'Loading…';
  previewEl.style.display = 'block';
  gridEl.innerHTML = '';

  fetch('/api2/alert-preview?' + qs.toString())
    .then(r => r.json())
    .then(function(d) {
      var n = d.count || 0;
      var label = n === 0 ? 'No matching listings right now — alert will notify you when new ones appear.'
                : n + ' matching listing' + (n !== 1 ? 's' : '') + ' right now' + (n > 6 ? ' (showing latest 6)' : '') + ':';
      countEl.textContent = label;
      (d.listings || []).forEach(function(l) {
        var price = l.price ? '$' + parseInt(l.price, 10).toLocaleString() : '';
        var meta  = [];
        if (l.beds)       meta.push(l.beds + ' bd');
        if (l.baths)      meta.push(l.baths + ' ba');
        if (l.sqft)       meta.push(l.sqft + ' sqft');
        if (l.year_built) meta.push('Built ' + l.year_built);
        if (l.date)       meta.push('Listed ' + l.date);
        var img = l.photo
          ? '<img class="ca-card__img" src="' + l.photo + '" alt="" loading="lazy" onerror="this.style.display=\'none\'">'
          : '<div class="ca-card__img" style="background:#e8e4de;"></div>';
        var card = '<a class="ca-card" href="/listing/' + l.slug + '" target="_blank">'
          + img
          + '<div class="ca-card__body">'
          + '<div class="ca-card__price">' + price + '</div>'
          + '<div class="ca-card__addr">' + (l.address || '') + '</div>'
          + (meta.length ? '<div class="ca-card__meta">' + meta.join(' · ') + '</div>' : '')
          + '</div></a>';
        gridEl.insertAdjacentHTML('beforeend', card);
      });
    })
    .catch(function() { countEl.textContent = 'Could not load preview.'; });
}

function caDebouncedPreview() {
  clearTimeout(_caDebTimer);
  _caDebTimer = setTimeout(caPreview, 600);
}

function caSave() {
  var name     = document.getElementById('ca-name').value.trim();
  var city     = document.getElementById('ca-city').value;
  var subarea  = document.getElementById('ca-subarea').value;
  var type     = document.getElementById('ca-type').value;
  var beds     = parseInt(document.getElementById('ca-beds').value, 10) || 0;
  var minPrice = parseInt(document.getElementById('ca-min-price').value, 10) || 0;
  var maxPrice = parseInt(document.getElementById('ca-max-price').value, 10) || 0;
  var email    = document.getElementById('ca-email').checked ? 1 : 0;
  var msgEl    = document.getElementById('ca-save-msg');
  var saveBtn  = document.getElementById('ca-save-btn');

  if (!name) { alert('Please enter an alert name.'); document.getElementById('ca-name').focus(); return; }
  if (!city) { alert('Please select a city to receive useful alerts.'); document.getElementById('ca-city').focus(); return; }

  var searchData = { status: 'Active' };
  if (city)     searchData.city     = city;
  if (subarea)  searchData.subarea  = subarea;
  if (type)     searchData.type     = type;
  if (beds > 0) searchData.beds_min = beds;
  if (minPrice) searchData.price_min = minPrice;
  if (maxPrice) searchData.price_max = maxPrice;

  var searchUrl = '/' + (city ? city.toLowerCase().replace(/\s+/g, '-') : '') + '-for-sale';

  saveBtn.disabled = true;
  saveBtn.textContent = 'Saving…';
  msgEl.style.display = 'none';

  fetch('/api2/save_search', {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':_csrf},
    body: JSON.stringify({
      search_name:  name,
      data:         JSON.stringify(searchData),
      daily_email:  email,
      listing_sql:  '',
      search_url:   searchUrl,
    })
  })
  .then(r => r.json())
  .then(function(d) {
    if (d.success) {
      msgEl.style.display = 'inline';
      msgEl.style.color   = '#2a7a2a';
      msgEl.textContent   = '✓ Alert created!';
      setTimeout(function() { window.location.href = '?tab=alerts'; }, 900);
    } else {
      saveBtn.disabled    = false;
      saveBtn.textContent = 'Create Alert';
      msgEl.style.display = 'inline';
      msgEl.style.color   = '#c00';
      msgEl.textContent   = d.message || 'Could not save. Please try again.';
    }
  })
  .catch(function() {
    saveBtn.disabled    = false;
    saveBtn.textContent = 'Create Alert';
    msgEl.style.display = 'inline';
    msgEl.style.color   = '#c00';
    msgEl.textContent   = 'Network error — please try again.';
  });
}
/* ─── End Create Alert ─── */
</script>
@endpush
