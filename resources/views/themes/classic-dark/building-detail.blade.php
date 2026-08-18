@extends('themes.classic-dark.layout')

@php
  $buildingName = $building->name ?? $building->complex ?? ($building->street_no . ' ' . $building->street_name);
  $metaTitle = $buildingName . ' — ' . ($building->city ?? '') . ' · ' . $agent->name;
@endphp

@section('head')
<meta name="description" content="{{ $buildingName }} in {{ $building->city }}. {{ $total_active_listings }} active listings. Browse condos and sold data with {{ $agent->name }}.">
@endsection

@section('w4-headline')Get alerts for new listings in {{ $buildingName }}@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $building->city }}{{ $building->subarea ? ' · ' . $building->subarea : '' }}</div>
    <h1 class="page-header__title">{{ $buildingName }}</h1>
    @if($building->yearbuilt)
      <p class="page-header__sub">Built {{ $building->yearbuilt }} · {{ $building->city }}</p>
    @endif
  </div>
</div>

<div class="container">
  {{-- Stats bar --}}
  <section class="section--sm" style="padding-top:40px;">
    <div class="market-stats-bar">
      <div class="market-stats-bar__item">
        <div class="market-stats-bar__value">{{ $total_active_listings }}</div>
        <div class="market-stats-bar__label">Active listings</div>
      </div>
      @if($avg_listing_price > 0)
      <div class="market-stats-bar__item">
        <div class="market-stats-bar__value">${{ $avg_listing_price >= 1000000 ? number_format($avg_listing_price/1000000,2).'M' : number_format($avg_listing_price/1000,0).'K' }}</div>
        <div class="market-stats-bar__label">Avg list price</div>
      </div>
      @endif
      @if($avg_soldprice > 0)
      <div class="market-stats-bar__item">
        <div class="market-stats-bar__value">${{ $avg_soldprice >= 1000000 ? number_format($avg_soldprice/1000000,2).'M' : number_format($avg_soldprice/1000,0).'K' }}</div>
        <div class="market-stats-bar__label">Avg sold price</div>
      </div>
      @endif
      @if($avg_price_sqft > 0)
      <div class="market-stats-bar__item">
        <div class="market-stats-bar__value">${{ number_format($avg_price_sqft, 0) }}</div>
        <div class="market-stats-bar__label">Avg $/sqft</div>
      </div>
      @endif
      @if($avg_days_on_market_active > 0)
      <div class="market-stats-bar__item">
        <div class="market-stats-bar__value">{{ round($avg_days_on_market_active) }}</div>
        <div class="market-stats-bar__label">Avg days on market</div>
      </div>
      @endif
    </div>
  </section>

  {{-- Active listings --}}
  @if($active_listings->count() > 0)
  <section class="section" aria-labelledby="active-heading">
    <h2 id="active-heading" class="h2 mb-32">Active Listings</h2>
    <div class="grid-3">
      @foreach($active_listings as $listing)
        @include('themes.shared.listing-card', ['listing' => $listing])
      @endforeach
    </div>
  </section>
  @endif

  {{-- Sold listings --}}
  @if($sold_listings->count() > 0)
  <section class="section" aria-labelledby="sold-heading">
    <h2 id="sold-heading" class="h2 mb-32">Recent Solds</h2>
    <div class="grid-4">
      @foreach($sold_listings->take(8) as $listing)
        @include('themes.shared.listing-card', ['listing' => $listing])
      @endforeach
    </div>
  </section>
  @endif

  {{-- Open houses --}}
  @if(count($openHouseEvents ?? []) > 0)
  <section class="section--sm">
    @include('themes.shared.open-house-widget', ['openHouses' => $openHouseEvents])
  </section>
  @endif

  {{-- Building info + lead form --}}
  <section class="section">
    <div class="grid-2" style="gap:48px;align-items:start;">
      <div>
        <h2 class="h2 mb-16">About {{ $buildingName }}</h2>
        <div class="info-box mb-16">
          @if($building->yearbuilt)
            <div><strong>Year built:</strong> {{ $building->yearbuilt }}</div>
          @endif
          @if($building->levels)
            <div><strong>Levels:</strong> {{ $building->levels }}</div>
          @endif
          @if($building->units_in_development)
            <div><strong>Units:</strong> {{ $building->units_in_development }}</div>
          @endif
          @if($building->strata_no)
            <div><strong>Strata plan:</strong> {{ $building->strata_no }}</div>
          @endif
          @if($building->bylaw_restrictions)
            <div><strong>Bylaws:</strong> {{ $building->bylaw_restrictions }}</div>
          @endif
          @if($building->amenities)
            <div><strong>Amenities:</strong> {{ $building->amenities }}</div>
          @endif
        </div>
        <a href="{{ route('building-detail-page', $building->slug) }}" style="color:var(--accent);font-size:14px;">Full building details on BCCondosAndHomes.com →</a>
      </div>
      @include('themes.shared.lead-form-w1', [
        'formHeading' => 'Book a Showing',
        'formSub' => 'Interested in ' . $buildingName . '? ' . explode(' ', $agent->name)[0] . ' can arrange a showing within 24 hours.'
      ])
    </div>
  </section>
</div>
@endsection
