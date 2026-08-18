@extends('themes.modern-white.layout')

@php
  $metaTitle = ($subarea ?? $city ?? 'Neighbourhoods') . ' Real Estate — ' . $agent->name;
  $displayName = $subarea ?? $city ?? 'Neighbourhoods';
@endphp

@section('head')
<meta name="description" content="{{ $displayName }} real estate with {{ $agent->name }}. Browse active listings, sold data, and market insights for {{ $displayName }}.">
@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $agent->name }} · {{ $city ?? 'Neighbourhood Guide' }}</div>
    <h1 class="page-header__title">{{ $displayName }}</h1>
    @if(isset($subarea) && $city)
      <p class="page-header__sub">{{ $city }}, BC — Real estate market guide and active listings</p>
    @endif
  </div>
</div>

<div class="container">

  {{-- Market stats --}}
  @if(isset($statsBar) && ($statsBar['active_count'] ?? 0) > 0)
  <section class="section--sm" style="padding-top:40px;">
    @include('themes.shared.market-stats-bar')
  </section>
  @endif

  {{-- Active listings --}}
  @if(isset($listings) && $listings->count() > 0)
  <section class="section" aria-labelledby="listings-heading" style="border-top:1px solid var(--border);">
    <div class="flex-between mb-32">
      <h2 id="listings-heading" class="h2">Active Listings in {{ $displayName }}</h2>
      <a href="{{ route('agent.search', $agent->slug) }}?city={{ urlencode($city ?? '') }}&subarea={{ urlencode($subarea ?? '') }}" style="font-size:13px;color:var(--muted);border-bottom:1px solid var(--border);">View all</a>
    </div>
    <div class="grid-3">
      @foreach($listings->take(6) as $listing)
        @include('themes.shared.listing-card', ['listing' => $listing])
      @endforeach
    </div>
  </section>
  @endif

  {{-- Neighbourhood hub --}}
  @if(!isset($city))
  <section class="section" aria-labelledby="nh-heading" style="border-top:1px solid var(--border);">
    <h2 id="nh-heading" class="h2 mb-32">Neighbourhoods {{ $agent->name }} Covers</h2>
    @include('themes.shared.neighbourhood-links', ['territories' => $territories->flatten(1)])
  </section>
  @endif

  {{-- Contact CTA --}}
  <section class="section" style="border-top:1px solid var(--border);">
    <div style="background:var(--alt);border:1px solid var(--border);border-radius:var(--radius);padding:48px;display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center;">
      <div>
        <p class="eyebrow mb-12">Local Expertise</p>
        <h2 class="h2" style="margin-bottom:12px;">Looking in {{ $displayName }}?</h2>
        <p style="color:var(--muted);line-height:1.8;font-size:15px;">
          {{ explode(' ', $agent->name)[0] }} knows this market intimately. Get personalised advice — no pressure, no obligation.
        </p>
      </div>
      <div style="display:flex;gap:14px;flex-wrap:wrap;">
        <a href="{{ route('agent.search', $agent->slug) }}?city={{ urlencode($city ?? '') }}&subarea={{ urlencode($subarea ?? '') }}" class="btn-cta">Browse Listings</a>
        <a href="{{ route('agent.home-evaluation', $agent->slug) }}" class="btn-outline">Free Home Evaluation</a>
      </div>
    </div>
  </section>

</div>
@endsection
