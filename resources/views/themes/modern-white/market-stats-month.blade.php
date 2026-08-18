@extends('themes.modern-white.layout')

@php
  $metaTitle = $pageTitle ?? ($monthLabel . ' Market Report');
@endphp

@section('head')
<meta name="description" content="{{ $metaDesc ?? '' }}">
<link rel="canonical" href="{{ url()->current() }}">
@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $agent->name }}</div>
    <h1 class="page-header__title">{{ $monthLabel }} Market Report</h1>
    <p class="page-header__sub">{{ $territory }} — sold data by property type.</p>
  </div>
</div>

<div class="container">

  {{-- Breadcrumb --}}
  <nav aria-label="Breadcrumb" style="padding:24px 0 8px;font-size:13px;color:var(--muted);">
    <a href="{{ route('agent.market-stats', $agent->slug) }}" style="color:var(--text);text-decoration:underline;">← Market Statistics</a>
    <span style="margin:0 8px;color:var(--muted);">·</span>
    <span style="color:var(--muted);">{{ $monthLabel }}</span>
  </nav>

  {{-- Property-type sections --}}
  @php
    $typeDescriptions = [
      'Condos'     => 'Apartments &amp; strata units',
      'Townhouses' => 'Townhouses, duplexes &amp; multi-family',
      'Houses'     => 'Detached homes',
    ];
  @endphp

  @foreach(['Condos', 'Townhouses', 'Houses'] as $typeName)
  @php $stats = $typeStats[$typeName] ?? ['sold_count'=>0,'avg_sold_price'=>0,'avg_ppsf'=>0,'avg_dom'=>0]; @endphp
  <section class="section" aria-labelledby="type-{{ strtolower($typeName) }}-heading" style="border-top:1px solid var(--border);padding-top:48px;">
    <div style="display:flex;align-items:baseline;gap:12px;margin-bottom:24px;">
      <h2 id="type-{{ strtolower($typeName) }}-heading" class="h2" style="margin-bottom:0;">{{ $typeName }}</h2>
      <span style="font-size:13px;color:var(--muted);">{!! $typeDescriptions[$typeName] !!}</span>
    </div>

    @if($stats['sold_count'] > 0)
    <div class="stat-cards">
      <div class="stat-card">
        <div class="stat-card__label">Units Sold</div>
        <div class="stat-card__value">{{ number_format($stats['sold_count']) }}</div>
      </div>
      @if($stats['avg_sold_price'] > 0)
      <div class="stat-card">
        <div class="stat-card__label">Avg Sold Price</div>
        <div class="stat-card__value">${{ $stats['avg_sold_price'] >= 1000000 ? number_format($stats['avg_sold_price']/1000000,2).'M' : number_format($stats['avg_sold_price']/1000,0).'K' }}</div>
      </div>
      @endif
      @if($stats['avg_ppsf'] > 0)
      <div class="stat-card">
        <div class="stat-card__label">Avg $/sqft</div>
        <div class="stat-card__value">${{ number_format($stats['avg_ppsf'], 0) }}</div>
      </div>
      @endif
      @if($stats['avg_dom'] > 0)
      <div class="stat-card">
        <div class="stat-card__label">Avg Days on Market</div>
        <div class="stat-card__value">{{ round($stats['avg_dom']) }}</div>
      </div>
      @endif
    </div>
    @else
    <p style="color:var(--muted);font-size:15px;">No sold {{ strtolower($typeName) }} recorded in {{ $territory }} for {{ $monthLabel }}.</p>
    @endif
  </section>
  @endforeach

  {{-- CTA --}}
  <section class="section" style="border-top:1px solid var(--border);">
    <div style="background:var(--alt);border-radius:var(--radius);border:1px solid var(--border);padding:48px;text-align:center;">
      <p class="eyebrow mb-12">Personal Briefing</p>
      <h2 class="h2" style="margin-bottom:12px;">Questions about the {{ $monthLabel }} market?</h2>
      <p style="color:var(--muted);margin-bottom:32px;font-size:15px;max-width:560px;margin-left:auto;margin-right:auto;">{{ explode(' ', $agent->name)[0] }} can explain what these numbers mean for your buying or selling goals.</p>
      <div style="max-width:480px;margin:0 auto;">
        @include('themes.shared.lead-form-w1', ['formHeading' => 'Get a Market Briefing', 'formSub' => ''])
      </div>
    </div>
  </section>

</div>
@endsection
