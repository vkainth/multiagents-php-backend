@extends('themes.modern-white.layout')

@php
  $buildingName = $building->name ?? $building->complex ?? ($building->street_no . ' ' . $building->street_name);
  $metaTitle = $buildingName . ' — ' . ($building->city ?? '') . ' · ' . $agent->name;
@endphp

@section('head')
<meta name="description" content="{{ $buildingName }} in {{ $building->city }}. {{ $total_active_listings }} active listings. Browse condos and sold data with {{ $agent->name }}.">
@endsection

@section('w4-headline')Get alerts for new listings in {{ $buildingName }}@endsection

@section('content')

{{-- ── PHOTO GALLERY STRIP ──────────────────────────────── --}}
@php
  $galleryImages = [];
  if($active_listings->count() > 0) {
    foreach($active_listings->take(5) as $l) { if($l->mainpicurl) $galleryImages[] = ['url' => $l->mainpicurl, 'alt' => $l->streetaddress]; }
  }
  if(count($galleryImages) === 0 && $sold_listings->count() > 0) {
    foreach($sold_listings->take(5) as $l) { if($l->mainpicurl) $galleryImages[] = ['url' => $l->mainpicurl, 'alt' => $l->streetaddress]; }
  }
  $heroImg = count($galleryImages) > 0 ? $galleryImages[0]['url'] : 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1440&h=600&fit=crop';
@endphp

@if(count($galleryImages) >= 3)
  <div class="photo-strip" style="padding:0 var(--px);margin-bottom:0;" aria-label="{{ $buildingName }} photos">
    @foreach($galleryImages as $img)
      <div class="photo-strip__item">
        <img src="{{ $img['url'] }}" alt="{{ $img['alt'] }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
      </div>
    @endforeach
  </div>
@else
  <div style="height:380px;overflow:hidden;">
    <img src="{{ $heroImg }}" alt="{{ $buildingName }}" style="width:100%;height:100%;object-fit:cover;" loading="eager">
  </div>
@endif

<div class="page-header" style="padding-top:36px;">
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

  {{-- Building info + contact form --}}
  <section class="section" style="border-top:1px solid var(--border);">
    <div class="grid-2" style="gap:52px;align-items:start;">

      {{-- Building data table --}}
      <div>
        <h2 class="h2 mb-24">About {{ $buildingName }}</h2>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
          @foreach([
            ['Address', trim(($building->street_no ?? '') . ' ' . ($building->street_name ?? ''))],
            ['City', $building->city ?? null],
            ['Year Built', $building->yearbuilt ?? null],
            ['Levels', $building->levels ?? null],
            ['Total Units', $building->units_in_development ?? null],
            ['Strata Plan', $building->strata_no ?? null],
            ['Bylaws', $building->bylaw_restrictions ?? null],
            ['Amenities', $building->amenities ?? null],
          ] as [$label, $value])
            @if($value)
            <tr style="border-bottom:1px solid var(--border);">
              <td style="padding:12px 0;font-weight:600;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:0.5px;width:140px;">{{ $label }}</td>
              <td style="padding:12px 0;color:var(--text);">{{ $value }}</td>
            </tr>
            @endif
          @endforeach
        </table>
        <div style="margin-top:20px;">
          <a href="{{ route('building-detail-page', $building->slug) }}" style="color:var(--muted);font-size:13px;border-bottom:1px solid var(--border);">Full building details on BCCondosAndHomes.com →</a>
        </div>
      </div>

      {{-- Sidebar: contact form + open houses --}}
      <div>
        @include('themes.shared.lead-form-w1', [
          'formHeading' => 'Book a Showing',
          'formSub' => 'Interested in ' . $buildingName . '? ' . explode(' ', $agent->name)[0] . ' can arrange a showing within 24 hours.'
        ])

        @if(count($openHouseEvents ?? []) > 0)
          <div style="margin-top:24px;">
            @include('themes.shared.open-house-widget', ['openHouses' => $openHouseEvents])
          </div>
        @endif
      </div>
    </div>
  </section>

  {{-- Sold listings --}}
  @if($sold_listings->count() > 0)
  <section class="section" aria-labelledby="sold-heading" style="border-top:1px solid var(--border);">
    <h2 id="sold-heading" class="h2 mb-32">Recent Solds in {{ $buildingName }}</h2>
    <div class="grid-4">
      @foreach($sold_listings->take(8) as $listing)
        @include('themes.shared.listing-card', ['listing' => $listing])
      @endforeach
    </div>
  </section>
  @endif

</div>
@endsection
