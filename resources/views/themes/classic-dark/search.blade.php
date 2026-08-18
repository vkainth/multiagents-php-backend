@extends('themes.classic-dark.layout')

@section('head')
<meta name="description" content="Search {{ $agent->name }}'s listings in {{ $territories->keys()->implode(', ') }}. Filter by type, bedrooms, and price.">
@endsection

@php
  $metaTitle = 'Search Listings — ' . $agent->name;
@endphp

@section('w4-headline')
  @if(isset($listing))Book a showing for {{ $listing->streetaddress }}@else Find your next home in {{ $territories->keys()->first() }}@endif
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

<div class="container" style="padding-top:32px;padding-bottom:64px;">

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
  </form>

  {{-- Results grid --}}
  @if($listings->count() > 0)
    <div class="grid-3">
      @foreach($listings as $listing)
        @include('themes.shared.listing-card', ['listing' => $listing])
      @endforeach
    </div>
    {{-- Pagination --}}
    <div class="pagination" role="navigation" aria-label="Listings pagination">
      {{ $listings->appends(request()->query())->links('vendor.pagination.simple-bootstrap-5') }}
    </div>
  @else
    <div style="text-align:center;padding:80px 0;color:var(--muted);">
      <div style="font-size:40px;margin-bottom:16px;">🔍</div>
      <h2 class="h3" style="margin-bottom:12px;">No listings found</h2>
      <p style="font-size:15px;margin-bottom:24px;">Try adjusting your filters or <a href="{{ route('agent.search', $agent->slug) }}" style="color:var(--accent);">view all listings</a>.</p>
    </div>
  @endif

</div>
@endsection
