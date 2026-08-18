@extends('themes.modern-white.layout')

@php
  $metaTitle = 'Sold: ' . ($listing->streetaddress ?? 'Listing') . ' — ' . $agent->name;
  $soldPrice = $listing->soldprice_2 ?? 0;
  $listPrice = $listing->listprice_2 ?? 0;
  $ratio = ($listPrice > 0 && $soldPrice > 0) ? round($soldPrice / $listPrice * 100, 1) : null;
@endphp

@section('head')
<meta name="description" content="Sold: {{ $listing->streetaddress }}, {{ $listing->city }} for ${{ number_format($soldPrice) }} by {{ $agent->name }}.">
@endsection

@section('w4-headline')What's your {{ $listing->city }} home worth?@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $listing->city }}{{ $listing->subarea ? ' · ' . $listing->subarea : '' }} · Sold</div>
    <h1 class="page-header__title">{{ $listing->streetaddress }}</h1>
    <p class="page-header__sub">
      Sold for ${{ number_format($soldPrice) }}
      @if($listing->sold_date) on {{ \Carbon\Carbon::parse($listing->sold_date)->format('M j, Y') }} @endif
      @if($ratio) · {{ $ratio }}% of asking @endif
    </p>
  </div>
</div>

<div class="container">
  <section class="section">
    <div class="grid-2" style="gap:48px;align-items:start;">
      <div>
        @if($listing->mainpicurl)
          <div style="border-radius:var(--radius);overflow:hidden;margin-bottom:20px;border:1px solid var(--border);">
            <img src="{{ $listing->mainpicurl }}" alt="{{ $listing->streetaddress }}" style="width:100%;height:360px;object-fit:cover;" loading="eager">
          </div>
        @endif

        <div class="market-stats-bar mb-24">
          <div class="market-stats-bar__item">
            <div class="market-stats-bar__value">${{ number_format($soldPrice) }}</div>
            <div class="market-stats-bar__label">Sold price</div>
          </div>
          @if($listPrice > 0)
          <div class="market-stats-bar__item">
            <div class="market-stats-bar__value">${{ number_format($listPrice) }}</div>
            <div class="market-stats-bar__label">List price</div>
          </div>
          @endif
          @if($ratio)
          <div class="market-stats-bar__item">
            <div class="market-stats-bar__value">{{ $ratio }}%</div>
            <div class="market-stats-bar__label">Of asking</div>
          </div>
          @endif
          @if($listing->dom)
          <div class="market-stats-bar__item">
            <div class="market-stats-bar__value">{{ $listing->dom }}</div>
            <div class="market-stats-bar__label">Days on market</div>
          </div>
          @endif
        </div>

        <table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:24px;">
          @foreach([
            ['Bedrooms', $listing->bedrooms ?? null],
            ['Bathrooms', $listing->bathstotal ?? null],
            ['Area', $listing->livingarea_2 ? number_format($listing->livingarea_2) . ' sqft' : null],
            ['Year built', $listing->yearbuilt ?? null],
            ['Type', $listing->type ?? null],
          ] as [$label, $value])
            @if($value)
            <tr style="border-bottom:1px solid var(--border);">
              <td style="padding:11px 0;font-weight:600;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:0.5px;width:140px;">{{ $label }}</td>
              <td style="padding:11px 0;color:var(--text);">{{ $value }}</td>
            </tr>
            @endif
          @endforeach
        </table>

        <a href="{{ route('agent.sold', $agent->slug) }}" style="color:var(--muted);font-size:13px;border-bottom:1px solid var(--border);">← View all recent solds</a>
      </div>

      <div>
        <div class="info-box info-box--accent mb-24">
          <strong>Curious what your home would sell for?</strong><br>
          <span style="color:var(--muted);font-size:13px;">{{ explode(' ', $agent->name)[0] }} can pull the sold comparables for your specific property within 6 hours — free, no obligation.</span>
        </div>
        @include('themes.shared.lead-form-w2', [
          'formHeading' => 'What\'s Your Home Worth?',
          'formSub'     => 'Free valuation based on real sold comparables. Results in 6 hours.',
          'neighbourhood' => $listing->city,
        ])
      </div>
    </div>
  </section>
</div>
@endsection
