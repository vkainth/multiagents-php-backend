@extends('themes.modern-white.layout')

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
          <div style="border-radius:var(--radius);overflow:hidden;margin-bottom:20px;border:1px solid var(--border);">
            <img src="{{ $listing->mainpicurl }}" alt="{{ $listing->streetaddress }}" style="width:100%;height:400px;object-fit:cover;" loading="eager">
          </div>
        @endif

        {{-- Key details --}}
        <table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:24px;">
          @foreach([
            ['List price', $listing->listprice_2 ? '$' . number_format($listing->listprice_2) : null],
            ['Bedrooms', $listing->bedrooms ?? null],
            ['Bathrooms', $listing->bathstotal ?? null],
            ['Area', $listing->livingarea_2 ? number_format($listing->livingarea_2) . ' sqft' : null],
            ['Year built', $listing->yearbuilt ?? null],
            ['Strata fee', ($listing->maintenance && $listing->maintenance > 0) ? '$' . number_format($listing->maintenance) . '/mo' : null],
            ['Parking', $listing->parking ?? null],
            ['Listed', $listing->list_date ? \Carbon\Carbon::parse($listing->list_date)->format('M j, Y') : null],
          ] as [$label, $value])
            @if($value)
            <tr style="border-bottom:1px solid var(--border);">
              <td style="padding:11px 0;font-weight:600;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:0.5px;width:140px;">{{ $label }}</td>
              <td style="padding:11px 0;color:var(--text);">{{ $value }}</td>
            </tr>
            @endif
          @endforeach
        </table>

        @if($listing->remarks)
          <div style="margin-bottom:24px;">
            <h2 class="h3 mb-12">About This Property</h2>
            <p style="color:var(--muted);line-height:1.9;font-size:15px;">{{ $listing->remarks }}</p>
          </div>
        @endif

        <a href="{{ route('listing-detail-page2', $listing->slug) }}" style="color:var(--muted);font-size:13px;border-bottom:1px solid var(--border);">Full listing details on BCCondosAndHomes.com →</a>
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
