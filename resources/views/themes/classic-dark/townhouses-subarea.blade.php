@extends('themes.classic-dark.layout')

@section('head')
<link rel="canonical" href="{{ $canonical }}">
@php
$jsonLd = [
    '@@context' => 'https://schema.org',
    '@@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@@type' => 'ListItem', 'position' => 1, 'name' => $agent->name, 'item' => route('agent.home', $agent->slug)],
        ['@@type' => 'ListItem', 'position' => 2, 'name' => 'Townhouses', 'item' => route('agent.townhouses', $agent->slug)],
        ['@@type' => 'ListItem', 'position' => 3, 'name' => $city . ' Townhouses', 'item' => route('agent.townhouses.city', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug])],
        ['@@type' => 'ListItem', 'position' => 4, 'name' => $subarea, 'item' => $canonical],
    ],
];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <nav aria-label="breadcrumb" style="margin-bottom:10px;">
      <ol style="list-style:none;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:6px;font-size:13px;color:rgba(255,255,255,.6);">
        <li><a href="{{ route('agent.home', $agent->slug) }}" style="color:rgba(255,255,255,.6);">{{ $agent->name }}</a></li>
        <li style="margin:0 4px;">›</li>
        <li><a href="{{ route('agent.townhouses', $agent->slug) }}" style="color:rgba(255,255,255,.6);">Townhouses</a></li>
        <li style="margin:0 4px;">›</li>
        <li><a href="{{ route('agent.townhouses.city', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug]) }}" style="color:rgba(255,255,255,.6);">{{ $city }}</a></li>
        <li style="margin:0 4px;">›</li>
        <li style="color:#fff;">{{ $subarea }}</li>
      </ol>
    </nav>
    <div class="page-header__eyebrow">{{ $agent->name }} · {{ $city }}</div>
    <h1 class="page-header__title">Townhouses for Sale in {{ $subarea }}</h1>
    @if(!$cond['insufficient_data'] && $cond['label'])
    <p class="page-header__sub">
      <span style="background:{{ $cond['color'] }};color:#fff;border-radius:3px;padding:2px 8px;font-size:12px;font-weight:700;">{{ $cond['label'] }}</span>
      @if($cond['avg_sold_30d']) &nbsp; avg ${{ number_format($cond['avg_sold_30d']) }} @endif
      @if($cond['sold_30d']) &nbsp;· {{ number_format($cond['sold_30d']) }} sold (30d) @endif
      @if($cond['avg_dom']) &nbsp;· {{ $cond['avg_dom'] }}d avg DOM @endif
    </p>
    @else
    <p class="page-header__sub">Townhouses &amp; duplexes in {{ $subarea }}, {{ $city }}</p>
    @endif
  </div>
</div>

<div class="container">

  {{-- STATS TILES --}}
  @if(!$cond['insufficient_data'] && $cond['label'])
  <section class="section--sm" style="padding-top:36px;">
    @php
      $priceTrendVal = $cond['price_trend'] != 0
        ? (($cond['price_trend'] > 0 ? '+' : '') . $cond['price_trend'] . '%')
        : '—';
      $tiles = [
        ['Avg Sold (30d)', $cond['avg_sold_30d'] ? '$'.number_format($cond['avg_sold_30d']) : '—', '30-day avg'],
        ['Avg Sold (90d)', $cond['avg_sold_90d'] ? '$'.number_format($cond['avg_sold_90d']) : '—', '90-day avg'],
        ['Sold (30d)', number_format($cond['sold_30d']), 'townhouses'],
        ['Sold (90d)', number_format($cond['sold_90d']), 'townhouses'],
        ['Avg DOM', $cond['avg_dom'] ? $cond['avg_dom'].'d' : '—', 'days on market'],
        ['Price Trend', $priceTrendVal, 'vs 90-day avg'],
        ['Active', number_format($cond['current_active']), 'for sale now'],
        ['Absorption', $cond['absorption_rate'].'%', 'sell rate/month'],
      ];
    @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:14px;margin-bottom:32px;">
      @foreach($tiles as $tile)
      <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:16px;text-align:center;">
        <div style="font-size:{{ strlen($tile[1]) > 7 ? '16' : '20' }}px;font-weight:700;color:{{ ($tile[0]==='Price Trend' && $cond['price_trend']>0) ? '#4ade80' : (($tile[0]==='Price Trend' && $cond['price_trend']<0) ? '#f87171' : 'var(--accent)') }};">{{ $tile[1] }}</div>
        <div style="font-size:10px;color:rgba(255,255,255,.5);margin-top:4px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">{{ $tile[0] }}</div>
        <div style="font-size:10px;color:rgba(255,255,255,.3);margin-top:2px;">{{ $tile[2] }}</div>
      </div>
      @endforeach
    </div>

    @if($editorial)
    <div style="background:rgba(255,255,255,.04);border-left:4px solid var(--accent);border-radius:4px;padding:18px 22px;font-size:14px;color:rgba(255,255,255,.75);line-height:1.8;margin-bottom:32px;">
      <div style="font-size:13px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Market Analysis — {{ $subarea }}</div>
      {!! $editorial !!}
    </div>
    @endif
  </section>
  @else
  <section class="section--sm" style="padding-top:36px;">
    <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:6px;padding:20px;font-size:14px;color:rgba(255,255,255,.5);margin-bottom:32px;">
      Not enough recent townhouse sales in {{ $subarea }} to calculate market statistics.
      <a href="{{ route('agent.townhouses.city', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug]) }}" style="color:var(--accent);margin-left:8px;">View {{ $city }} townhouse market →</a>
    </div>
  </section>
  @endif

  {{-- RECENT LISTINGS --}}
  <section class="section" aria-labelledby="listings-heading" style="border-top:1px solid rgba(255,255,255,.07);padding-top:40px;">
    <h2 id="listings-heading" class="h2 mb-32">Townhouses for Sale in {{ $subarea }}</h2>
    @if($recentListings->count() > 0)
    <div class="grid-3">
      @foreach($recentListings as $listing)
        @include('themes.shared.listing-card', ['listing' => $listing])
      @endforeach
    </div>
    <div style="margin-top:24px;">
      <a href="{{ route('agent.search', $agent->slug) }}?city={{ urlencode($city) }}&subarea={{ urlencode($subarea) }}&type=Townhouse" class="btn-cta">See All {{ $subarea }} Townhouses</a>
    </div>
    @else
    <p style="color:var(--muted);">No active townhouse listings found in {{ $subarea }} right now.
      <a href="{{ route('agent.townhouses.city', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug]) }}" style="color:var(--accent);">Browse all {{ $city }} townhouses</a>.
    </p>
    @endif
  </section>

  {{-- NEARBY SUBAREAS --}}
  @if($nearbySubareas->isNotEmpty())
  <section class="section" style="border-top:1px solid rgba(255,255,255,.07);padding-top:40px;">
    <h2 class="h2 mb-32">Nearby Neighbourhoods in {{ $city }}</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
      @foreach($nearbySubareas as $nb)
      @php $nbSlug = \App\Helpers\Helper::enslugPlace($nb->subarea); @endphp
      <a href="{{ route('agent.townhouses.subarea', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug, 'subareaSlug' => $nbSlug]) }}" style="display:block;text-decoration:none;">
        <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:14px 18px;transition:border-color .2s;">
          <div style="font-size:14px;font-weight:600;color:#fff;">{{ $nb->subarea }}</div>
          <div style="font-size:12px;color:var(--accent);margin-top:4px;">View townhouses &rsaquo;</div>
        </div>
      </a>
      @endforeach
    </div>
  </section>
  @endif

</div>
@endsection

@section('w4-headline')Townhouses in {{ $subarea }} — What's Your Home Worth?@endsection
