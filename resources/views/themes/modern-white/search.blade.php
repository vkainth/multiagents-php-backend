@extends('themes.modern-white.layout')

@section('head')
<meta name="description" content="Search {{ $agent->name }}'s listings in {{ $territories->keys()->implode(', ') }}. Filter by type, bedrooms, and price.">
@endsection

@php
  $metaTitle = 'Search Listings — ' . $agent->name;
@endphp

@section('w4-headline')
  Find your next home in {{ $territories->keys()->first() }}
@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $agent->name }}</div>
    <h1 class="page-header__title">
      @if(request('status') === 'sold') Sold Listings
      @elseif(request('type')) {{ ucfirst(request('type')) }}s for Sale
      @else Listings for Sale
      @endif
      @if(request('city')) in {{ request('city') }} @endif
    </h1>
    <p class="page-header__sub">
      Showing {{ $listings->total() }} listing{{ $listings->total() !== 1 ? 's' : '' }}
      @if(request('city')) in {{ request('city') }} @endif
      @if(request('subarea')) · {{ request('subarea') }} @endif
    </p>
  </div>
</div>

<div class="container" style="padding-bottom:64px;">

  {{-- Filter bar --}}
  <form class="filter-bar" method="GET" action="{{ route('agent.search', $agent->slug) }}" role="search">
    <div class="filter-bar__group">
      <span class="filter-bar__label">Area</span>
      <select name="city" aria-label="City">
        <option value="">Any Area</option>
        @foreach($territories->keys() as $city)
          <option value="{{ $city }}" {{ request('city') === $city ? 'selected' : '' }}>{{ $city }}</option>
        @endforeach
      </select>
    </div>
    <div class="filter-bar__group">
      <span class="filter-bar__label">Type</span>
      <select name="type" aria-label="Property type">
        <option value="">Any Type</option>
        <option value="Apartment" {{ request('type') === 'Apartment' ? 'selected' : '' }}>Condo</option>
        <option value="Townhouse" {{ request('type') === 'Townhouse' ? 'selected' : '' }}>Townhouse</option>
        <option value="House" {{ request('type') === 'House' ? 'selected' : '' }}>House</option>
      </select>
    </div>
    <div class="filter-bar__group">
      <span class="filter-bar__label">Bedrooms</span>
      <select name="beds" aria-label="Bedrooms">
        <option value="">Any</option>
        @foreach(['1+','2+','3+','4+'] as $b)
          <option value="{{ $b }}" {{ request('beds') === $b ? 'selected' : '' }}>{{ $b }}</option>
        @endforeach
      </select>
    </div>
    <div class="filter-bar__group">
      <span class="filter-bar__label">Min Price</span>
      <select name="pricefrom" aria-label="Min price">
        <option value="">No min</option>
        @foreach([300000,400000,500000,600000,750000,1000000,1500000] as $p)
          <option value="{{ $p }}" {{ request('pricefrom') == $p ? 'selected' : '' }}>${{ number_format($p) }}</option>
        @endforeach
      </select>
    </div>
    <div class="filter-bar__group">
      <span class="filter-bar__label">Max Price</span>
      <select name="priceto" aria-label="Max price">
        <option value="">No max</option>
        @foreach([500000,750000,1000000,1250000,1500000,2000000,3000000] as $p)
          <option value="{{ $p }}" {{ request('priceto') == $p ? 'selected' : '' }}>${{ number_format($p) }}</option>
        @endforeach
      </select>
    </div>
    <div class="filter-bar__group">
      <span class="filter-bar__label">Status</span>
      <select name="status" aria-label="Status">
        <option value="active" {{ request('status','active') === 'active' ? 'selected' : '' }}>For Sale</option>
        <option value="sold" {{ request('status') === 'sold' ? 'selected' : '' }}>Sold</option>
      </select>
    </div>
    <button type="submit" class="filter-bar__btn">Search</button>
    <a href="{{ route('agent.search', $agent->slug) }}" style="align-self:flex-end;font-size:13px;color:var(--muted);padding:9px 0;">Clear</a>

    {{-- View toggle --}}
    <div class="view-toggle" style="align-self:flex-end;margin-left:auto;" id="view-toggle">
      <button type="button" class="view-toggle__btn is-active" id="btn-list" title="List view">≡ List</button>
      <button type="button" class="view-toggle__btn" id="btn-grid" title="Grid view">⊞ Grid</button>
    </div>
  </form>

  {{-- Results --}}
  @if($listings->count() > 0)

    {{-- LIST VIEW (default) --}}
    <div id="view-list">
      <table class="listing-list" role="list">
        <thead>
          <tr>
            <th style="width:80px;"></th>
            <th>Address</th>
            <th>Price</th>
            <th>Beds/Baths</th>
            <th>Sqft</th>
            <th>Type</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($listings as $listing)
          @php
            $isSold = strtolower($listing->status ?? '') === 'sold';
            $price = $isSold ? ($listing->soldprice_2 ?? 0) : ($listing->listprice_2 ?? 0);
            $href = $isSold
              ? route('agent.listing.sold', ['agentSlug' => $agent->slug, 'listingSlug' => $listing->slug])
              : route('agent.listing', ['agentSlug' => $agent->slug, 'listingSlug' => $listing->slug]);
            $isNew = !$isSold && $listing->list_date && \Carbon\Carbon::parse($listing->list_date)->diffInDays() <= 7;
          @endphp
          <tr onclick="window.location='{{ $href }}'" style="cursor:pointer;" role="listitem">
            <td class="col-thumb">
              @if($listing->mainpicurl ?? $listing->thumbnailurl ?? null)
                <img src="{{ $listing->mainpicurl ?? $listing->thumbnailurl }}" alt="{{ $listing->streetaddress }}" class="listing-list__thumb" loading="lazy">
              @else
                <img src="{{ asset('frontend/images/no-listing-photo.svg') }}" alt="No photo available" loading="lazy" class="listing-list__thumb" style="object-fit:cover;">
              @endif
            </td>
            <td>
              <div class="listing-list__address">{{ $listing->streetaddress }}</div>
              <div class="listing-list__sub">{{ $listing->subarea ?? $listing->city }}</div>
            </td>
            <td>
              <div class="listing-list__price">${{ number_format($price) }}</div>
            </td>
            <td style="color:var(--muted);font-size:13px;">
              {{ $listing->bedrooms ?? '—' }} / {{ $listing->bathstotal ?? '—' }}
            </td>
            <td style="color:var(--muted);font-size:13px;">
              {{ $listing->livingarea_2 ? number_format($listing->livingarea_2) : '—' }}
            </td>
            <td style="color:var(--muted);font-size:13px;">
              {{ $listing->type === '1/2 Duplex' ? 'Duplex' : ($listing->type ?? '—') }}
            </td>
            <td>
              @if($isSold)
                <span class="listing-list__badge listing-list__badge--sold">Sold</span>
              @elseif($isNew)
                <span class="listing-list__badge listing-list__badge--new">New</span>
              @else
                <span class="listing-list__badge">Active</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- GRID VIEW (hidden by default) --}}
    <div id="view-grid" style="display:none;">
      <div class="grid-3">
        @foreach($listings as $listing)
          @include('themes.shared.listing-card', ['listing' => $listing])
        @endforeach
      </div>
    </div>

    {{-- Pagination --}}
    <div class="pagination" role="navigation" aria-label="Listings pagination">
      {{ $listings->appends(request()->query())->links('vendor.pagination.simple-bootstrap-5') }}
    </div>

  @else
    <div style="text-align:center;padding:80px 0;color:var(--muted);">
      <div style="font-size:40px;margin-bottom:16px;">🔍</div>
      <h2 class="h3" style="margin-bottom:12px;">No listings found</h2>
      <p style="font-size:15px;margin-bottom:24px;">Try adjusting your filters or <a href="{{ route('agent.search', $agent->slug) }}" style="color:var(--text);text-decoration:underline;">view all listings</a>.</p>
    </div>
  @endif

</div>
@endsection

@section('scripts')
<script>
(function() {
  var btnList = document.getElementById('btn-list');
  var btnGrid = document.getElementById('btn-grid');
  var viewList = document.getElementById('view-list');
  var viewGrid = document.getElementById('view-grid');

  function setView(v) {
    if (v === 'grid') {
      viewList.style.display = 'none';
      viewGrid.style.display = 'block';
      btnGrid.classList.add('is-active');
      btnList.classList.remove('is-active');
      localStorage.setItem('mw_search_view', 'grid');
    } else {
      viewGrid.style.display = 'none';
      viewList.style.display = 'block';
      btnList.classList.add('is-active');
      btnGrid.classList.remove('is-active');
      localStorage.setItem('mw_search_view', 'list');
    }
  }

  var saved = localStorage.getItem('mw_search_view');
  if (saved === 'grid') setView('grid');

  if (btnList) btnList.addEventListener('click', function() { setView('list'); });
  if (btnGrid) btnGrid.addEventListener('click', function() { setView('grid'); });
})();
</script>
@endsection
