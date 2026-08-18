@extends('themes.classic-dark.layout')

@php
  $metaTitle = 'Market Stats — ' . $territories->keys()->first() . ' · ' . $agent->name;
@endphp

@section('head')
<meta name="description" content="{{ $agent->name }}'s real estate market stats for {{ $territories->keys()->implode(', ') }}. Active listings, sold data, avg prices, and trends.">
@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $agent->name }}</div>
    <h1 class="page-header__title">Market Statistics</h1>
    <p class="page-header__sub">Live MLS data for {{ $territories->keys()->implode(', ') }} — updated daily.</p>
  </div>
</div>

<div class="container">

  @if(isset($statsBar))
  <section class="section--sm" style="padding-top:40px;">
    @include('themes.shared.market-stats-bar')
  </section>
  @endif

  {{-- Per-city stats breakdown --}}
  @if(isset($cityStats) && count($cityStats) > 0)
  <section class="section" aria-labelledby="city-stats-heading">
    <h2 id="city-stats-heading" class="h2 mb-32">Stats by Area</h2>
    @foreach($cityStats as $city => $stats)
    <div style="margin-bottom:40px;">
      <h3 class="h3 mb-16">{{ $city }}</h3>
      <div class="stat-cards">
        <div class="stat-card">
          <div class="stat-card__label">Active Listings</div>
          <div class="stat-card__value">{{ number_format($stats['active'] ?? 0) }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__label">Sold (30 days)</div>
          <div class="stat-card__value">{{ number_format($stats['sold'] ?? 0) }}</div>
        </div>
        @if(($stats['avg_sold_price'] ?? 0) > 0)
        <div class="stat-card">
          <div class="stat-card__label">Avg Sold Price</div>
          <div class="stat-card__value">${{ $stats['avg_sold_price'] >= 1000000 ? number_format($stats['avg_sold_price']/1000000,2).'M' : number_format($stats['avg_sold_price']/1000,0).'K' }}</div>
        </div>
        @endif
        @if(($stats['avg_dom'] ?? 0) > 0)
        <div class="stat-card">
          <div class="stat-card__label">Avg Days on Market</div>
          <div class="stat-card__value">{{ round($stats['avg_dom']) }}</div>
        </div>
        @endif
        @if(($stats['avg_ppsf'] ?? 0) > 0)
        <div class="stat-card">
          <div class="stat-card__label">Avg $/sqft</div>
          <div class="stat-card__value">${{ number_format($stats['avg_ppsf'], 0) }}</div>
        </div>
        @endif
      </div>
    </div>
    @endforeach
  </section>
  @else
  <section class="section">
    <p style="color:var(--muted);font-size:15px;">Detailed statistics by area are being compiled. <a href="{{ route('agent.contact', $agent->slug) }}" style="color:var(--accent);">Contact {{ explode(' ', $agent->name)[0] }}</a> for the latest data.</p>
  </section>
  @endif

  {{-- Monthly History --}}
  @if(isset($monthlyHistory) && count($monthlyHistory) > 0)
  <section class="section" aria-labelledby="monthly-history-heading">
    <h2 id="monthly-history-heading" class="h2 mb-32">Monthly History</h2>
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <thead>
          <tr style="border-bottom:1px solid rgba(255,255,255,0.15);">
            <th style="text-align:left;padding:10px 12px 10px 0;color:var(--muted);font-weight:500;white-space:nowrap;">Month</th>
            <th style="text-align:right;padding:10px 12px;color:var(--muted);font-weight:500;white-space:nowrap;">Sold</th>
            <th style="text-align:right;padding:10px 0 10px 12px;color:var(--muted);font-weight:500;white-space:nowrap;">Avg Price</th>
            <th style="text-align:right;padding:10px 0 10px 12px;color:var(--muted);font-weight:500;white-space:nowrap;"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($monthlyHistory as $row)
          <tr style="border-bottom:1px solid rgba(255,255,255,0.07);">
            <td style="padding:12px 12px 12px 0;white-space:nowrap;">{{ $row['month_label'] }}</td>
            <td style="padding:12px;text-align:right;color:var(--muted);">{{ $row['sold_count'] > 0 ? number_format($row['sold_count']) . ' sold' : '—' }}</td>
            <td style="padding:12px 0 12px 12px;text-align:right;color:var(--muted);">
              @if($row['avg_sold_price'] > 0)
                ${{ $row['avg_sold_price'] >= 1000000 ? number_format($row['avg_sold_price']/1000000,2).'M' : number_format($row['avg_sold_price']/1000,0).'K' }}
              @else
                —
              @endif
            </td>
            <td style="padding:12px 0 12px 12px;text-align:right;">
              <a href="{{ route('agent.market-stats.month', ['agentSlug' => $agent->slug, 'year' => $row['year'], 'month' => $row['month']]) }}" style="color:var(--accent);font-size:13px;white-space:nowrap;">View report →</a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </section>
  @endif

  {{-- CTA --}}
  <section class="section">
    <div style="background:var(--nav-bg);border-radius:var(--radius);padding:40px;text-align:center;">
      <h2 class="h2" style="color:#fff;margin-bottom:12px;">Want a personal market briefing?</h2>
      <p style="color:rgba(255,255,255,0.6);margin-bottom:24px;">{{ explode(' ', $agent->name)[0] }} can walk you through exactly what these numbers mean for buying or selling your specific property type.</p>
      @include('themes.shared.lead-form-w1', ['formHeading' => 'Request a Market Briefing', 'formSub' => ''])
    </div>
  </section>

</div>
@endsection
