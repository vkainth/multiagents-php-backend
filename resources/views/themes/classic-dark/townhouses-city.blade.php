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
        ['@@type' => 'ListItem', 'position' => 3, 'name' => $city . ' Townhouses', 'item' => $canonical],
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
        <li style="color:#fff;">{{ $city }}</li>
      </ol>
    </nav>
    <div class="page-header__eyebrow">{{ $agent->name }} · Townhouses</div>
    <h1 class="page-header__title">Townhouses for Sale in {{ $city }}</h1>
    @if(!$cond['insufficient_data'] && $cond['label'])
    <p class="page-header__sub">
      <span style="background:{{ $cond['color'] }};color:#fff;border-radius:3px;padding:2px 8px;font-size:12px;font-weight:700;">{{ $cond['label'] }}</span>
      @if($cond['sold_30d']) &nbsp; {{ number_format($cond['sold_30d']) }} sold (30d) @endif
      @if($cond['avg_sold_30d']) &nbsp;· avg ${{ number_format($cond['avg_sold_30d']) }} @endif
      @if($cond['avg_dom']) &nbsp;· {{ $cond['avg_dom'] }}d avg DOM @endif
    </p>
    @else
    <p class="page-header__sub">Active MLS® townhouse listings in {{ $city }}</p>
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
      <div style="font-size:13px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Market Analysis</div>
      {!! $editorial !!}
    </div>
    @endif
  </section>
  @else
  <section class="section--sm" style="padding-top:36px;">
    <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:6px;padding:20px;font-size:14px;color:rgba(255,255,255,.5);margin-bottom:32px;">
      Not enough recent townhouse sales in {{ $city }} to calculate market statistics yet.
      <a href="{{ route('agent.search', $agent->slug) }}?city={{ urlencode($city) }}&type=Townhouse" style="color:var(--accent);margin-left:8px;">Browse active listings →</a>
    </div>
  </section>
  @endif

  {{-- SUBAREA BREAKDOWN --}}
  @if(count($subareaStats) > 0)
  <section class="section" aria-labelledby="subarea-heading" style="border-top:1px solid rgba(255,255,255,.07);padding-top:40px;">
    <h2 id="subarea-heading" class="h2 mb-32">{{ $city }} Townhouse Market by Neighbourhood</h2>
    <div style="overflow:hidden;border:1px solid rgba(255,255,255,.08);border-radius:8px;margin-bottom:32px;">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr style="background:rgba(255,255,255,.05);text-align:left;">
            <th style="padding:12px 16px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(255,255,255,.4);">Neighbourhood</th>
            <th style="padding:12px 16px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(255,255,255,.4);">Avg Price</th>
            <th style="padding:12px 16px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(255,255,255,.4);">Sold 30d</th>
            <th style="padding:12px 16px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(255,255,255,.4);">DOM</th>
            <th style="padding:12px 16px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(255,255,255,.4);">Market</th>
            <th style="padding:12px 16px;"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($subareaStats as $saName => $saCond)
          @php $saSlug = \App\Helpers\Helper::enslugPlace($saName); @endphp
          <tr style="border-top:1px solid rgba(255,255,255,.05);">
            <td style="padding:12px 16px;font-weight:600;color:#fff;">
              <a href="{{ route('agent.townhouses.subarea', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug, 'subareaSlug' => $saSlug]) }}" style="color:#fff;text-decoration:none;">{{ $saName }}</a>
            </td>
            <td style="padding:12px 16px;color:rgba(255,255,255,.7);">
              @if($saCond['avg_sold_30d'])
              @php $p = $saCond['avg_sold_30d']; @endphp
              ${{ $p >= 1000000 ? number_format($p/1000000, 2).'M' : number_format(round($p/1000)).'K' }}
              @else<span style="color:rgba(255,255,255,.25);">—</span>@endif
            </td>
            <td style="padding:12px 16px;color:rgba(255,255,255,.6);">{{ $saCond['sold_30d'] ?: '—' }}</td>
            <td style="padding:12px 16px;color:rgba(255,255,255,.6);">{{ $saCond['avg_dom'] ? $saCond['avg_dom'].'d' : '—' }}</td>
            <td style="padding:12px 16px;">
              @if($saCond['label'] && !$saCond['insufficient_data'])
              <span style="background:{{ $saCond['color'] }};color:#fff;border-radius:3px;padding:2px 7px;font-size:10px;font-weight:700;white-space:nowrap;">{{ $saCond['label'] }}</span>
              @else<span style="color:rgba(255,255,255,.2);font-size:11px;">—</span>@endif
            </td>
            <td style="padding:12px 16px;">
              <a href="{{ route('agent.townhouses.subarea', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug, 'subareaSlug' => $saSlug]) }}" style="color:var(--accent);font-size:12px;white-space:nowrap;">View &rsaquo;</a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </section>
  @endif

  {{-- RECENT LISTINGS --}}
  <section class="section" aria-labelledby="listings-heading" style="border-top:1px solid rgba(255,255,255,.07);padding-top:40px;">
    <h2 id="listings-heading" class="h2 mb-32">Active Townhouses for Sale in {{ $city }}</h2>
    @if($recentListings->count() > 0)
    <div class="grid-3">
      @foreach($recentListings as $listing)
        @include('themes.shared.listing-card', ['listing' => $listing])
      @endforeach
    </div>
    <div style="margin-top:24px;">
      <a href="{{ route('agent.search', $agent->slug) }}?city={{ urlencode($city) }}&type=Townhouse" class="btn-cta">See All {{ $city }} Townhouses for Sale</a>
    </div>
    @else
    <p style="color:var(--muted);">No active townhouse listings found in {{ $city }} right now. <a href="{{ route('agent.search', $agent->slug) }}" style="color:var(--accent);">View all listings</a>.</p>
    @endif
  </section>

</div>
@endsection

@section('w4-headline')Townhouses in {{ $city }} — What's Your Home Worth?@endsection
