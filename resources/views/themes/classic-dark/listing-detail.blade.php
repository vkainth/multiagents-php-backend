@extends('themes.classic-dark.layout')

@php
  $metaTitle = ($listing->streetaddress ?? 'Listing') . ' — ' . $agent->name;
  $price = $listing->listprice_2 ?? 0;
@endphp

@section('head')
<meta name="description" content="{{ $listing->streetaddress }}, {{ $listing->city }}. {{ $listing->bedrooms }} bed / {{ $listing->bathstotal }} bath · ${{ number_format($price) }}. Represented by {{ $agent->name }}.">
@endsection

@section('w4-headline')Book a showing for {{ $listing->streetaddress }}@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $listing->city }}{{ $listing->subarea ? ' · ' . $listing->subarea : '' }} · {{ $listing->type }}</div>
    <h1 class="page-header__title">{{ $listing->streetaddress }}</h1>
    <p class="page-header__sub">
      ${{ number_format($price) }}
      @if($listing->bedrooms) · {{ $listing->bedrooms }} bed @endif
      @if($listing->bathstotal) · {{ $listing->bathstotal }} bath @endif
      @if($listing->livingarea_2) · {{ number_format($listing->livingarea_2) }} sqft @endif
    </p>
  </div>
</div>

<div class="container">
  <section class="section">
    <div class="grid-2" style="gap:48px;align-items:start;">
      <div>
        {{-- Main photo --}}
        @if($listing->mainpicurl)
          <div style="border-radius:var(--radius);overflow:hidden;margin-bottom:20px;">
            <img src="{{ $listing->mainpicurl }}" alt="{{ $listing->streetaddress }}" style="width:100%;height:400px;object-fit:cover;" loading="eager">
          </div>
        @endif

        {{-- Key details --}}
        <div class="info-box mb-24">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:14px;">
            @if($listing->listprice_2) <div><strong>List price:</strong> ${{ number_format($listing->listprice_2) }}</div> @endif
            @if($listing->bedrooms) <div><strong>Bedrooms:</strong> {{ $listing->bedrooms }}</div> @endif
            @if($listing->bathstotal) <div><strong>Bathrooms:</strong> {{ $listing->bathstotal }}</div> @endif
            @if($listing->livingarea_2) <div><strong>Area:</strong> {{ number_format($listing->livingarea_2) }} sqft</div> @endif
            @if($listing->yearbuilt) <div><strong>Year built:</strong> {{ $listing->yearbuilt }}</div> @endif
            @if($listing->maintenance && $listing->maintenance > 0) <div><strong>Strata fee:</strong> ${{ number_format($listing->maintenance) }}/mo</div> @endif
            @if($listing->parking) <div><strong>Parking:</strong> {{ $listing->parking }}</div> @endif
            @if($listing->list_date) <div><strong>Listed:</strong> {{ \Carbon\Carbon::parse($listing->list_date)->format('M j, Y') }}</div> @endif
          </div>
        </div>

        @if($listing->remarks)
          <div style="margin-bottom:24px;">
            <h2 class="h3 mb-12">About This Property</h2>
            <p style="color:var(--muted);line-height:1.9;font-size:15px;">{{ $listing->remarks }}</p>
          </div>
        @endif

        <a href="{{ route('listing-detail-page2', $listing->slug) }}" style="color:var(--accent);font-size:14px;">Full listing details on BCCondosAndHomes.com →</a>
      </div>

      <div>
        @include('themes.shared.lead-form-w1', [
          'formHeading' => 'Book a Showing',
          'formSub'     => explode(' ', $agent->name)[0] . ' can arrange a showing within 24 hours.',
          'listingSlug' => $listing->slug,
        ])

        @if(isset($openHouseEvents) && count($openHouseEvents) > 0)
          <div style="margin-top:24px;">
            @include('themes.shared.open-house-widget', ['openHouses' => $openHouseEvents])
          </div>
        @endif
      </div>
    </div>
  </section>
</div>
@endsection
