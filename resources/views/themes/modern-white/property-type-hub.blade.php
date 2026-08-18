@extends('themes.modern-white.layout')

@php
  $typeLabel = match(strtolower($propertyType ?? '')) {
    'apartment', 'condo' => 'Condos',
    'townhouse' => 'Townhouses',
    'house', 'detached' => 'Houses',
    default => ucfirst($propertyType ?? 'Properties')
  };
  $isHouses = strtolower($type ?? '') === 'houses';
  $isTownhouses = strtolower($type ?? '') === 'townhouses';
  $metaTitle = $typeLabel . ' for Sale — ' . $territories->keys()->first() . ' · ' . $agent->name;
@endphp

@section('head')
<link rel="canonical" href="{{ $hubCanonical }}">
<meta name="description" content="{{ $typeLabel }} for sale in {{ $territories->keys()->implode(', ') }} with {{ $agent->name }}. Browse active MLS listings filtered to your property type.">
@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $agent->name }}</div>
    <h1 class="page-header__title">{{ $typeLabel }} for Sale</h1>
    <p class="page-header__sub">in {{ $territories->keys()->implode(', ') }}</p>
  </div>
</div>

<div class="container">
  @if(isset($statsBar))
  <section class="section--sm" style="padding-top:40px;">
    @include('themes.shared.market-stats-bar')
  </section>
  @endif

  {{-- HOUSES HUB: city market cards --}}
  @if($isHouses && !empty($houseCityCards))
  <section class="section" style="border-top:1px solid var(--border);padding-top:40px;" aria-labelledby="cities-heading">
    <h2 id="cities-heading" class="h2 mb-32">Houses Market by City</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;margin-bottom:40px;">
      @foreach($houseCityCards as $cityName => $card)
      @php
        $cSlug = \App\Helpers\Helper::enslugPlace($cityName);
        $cond  = $card['cond'];
      @endphp
      <a href="{{ route('agent.houses.city', ['agentSlug' => $agent->slug, 'citySlug' => $cSlug]) }}" style="display:block;text-decoration:none;">
        <div style="background:#fff;border:1px solid var(--border);border-radius:10px;padding:22px 20px;box-shadow:0 1px 4px rgba(0,0,0,.05);height:100%;">
          <div style="font-size:17px;font-weight:700;color:var(--text);margin-bottom:10px;">{{ $cityName }}</div>

          @if(!$cond['insufficient_data'] && $cond['label'])
          <div style="margin-bottom:12px;">
            <span style="background:{{ $cond['color'] }};color:#fff;border-radius:3px;padding:2px 8px;font-size:11px;font-weight:700;">{{ $cond['label'] }}</span>
          </div>
          <div style="font-size:13px;color:var(--muted);line-height:1.8;">
            @if($cond['avg_sold_30d'])
            <div>Avg sold: <strong style="color:var(--text);">${{ number_format($cond['avg_sold_30d']) }}</strong></div>
            @endif
            @if($cond['sold_30d'])
            <div>{{ number_format($cond['sold_30d']) }} sold last 30 days</div>
            @endif
            @if($cond['avg_dom'])
            <div>Avg {{ $cond['avg_dom'] }} days on market</div>
            @endif
          </div>
          @elseif($card['active'] > 0)
          <div style="font-size:13px;color:var(--muted);margin-bottom:8px;">{{ number_format($card['active']) }} active listings</div>
          @if($card['avg_list'])
          <div style="font-size:13px;color:var(--muted);">Avg asking ${{ number_format($card['avg_list']) }}</div>
          @endif
          @else
          <div style="font-size:13px;color:var(--muted);">No active listings</div>
          @endif

          <div style="margin-top:14px;font-size:12px;font-weight:600;color:var(--accent);text-decoration:underline;">View market guide &rsaquo;</div>
        </div>
      </a>
      @endforeach
    </div>
  </section>
  @endif

  {{-- TOWNHOUSES HUB: city market cards --}}
  @if($isTownhouses && !empty($townhouseCityCards))
  <section class="section" style="border-top:1px solid var(--border);padding-top:40px;" aria-labelledby="th-cities-heading">
    <h2 id="th-cities-heading" class="h2 mb-32">Townhouse Market by City</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;margin-bottom:40px;">
      @foreach($townhouseCityCards as $cityName => $card)
      @php
        $cSlug = \App\Helpers\Helper::enslugPlace($cityName);
        $cond  = $card['cond'];
      @endphp
      <a href="{{ route('agent.townhouses.city', ['agentSlug' => $agent->slug, 'citySlug' => $cSlug]) }}" style="display:block;text-decoration:none;">
        <div style="background:#fff;border:1px solid var(--border);border-radius:10px;padding:22px 20px;box-shadow:0 1px 4px rgba(0,0,0,.05);height:100%;">
          <div style="font-size:17px;font-weight:700;color:var(--text);margin-bottom:10px;">{{ $cityName }}</div>

          @if(!$cond['insufficient_data'] && $cond['label'])
          <div style="margin-bottom:12px;">
            <span style="background:{{ $cond['color'] }};color:#fff;border-radius:3px;padding:2px 8px;font-size:11px;font-weight:700;">{{ $cond['label'] }}</span>
          </div>
          <div style="font-size:13px;color:var(--muted);line-height:1.8;">
            @if($cond['avg_sold_30d'])
            <div>Avg sold: <strong style="color:var(--text);">${{ number_format($cond['avg_sold_30d']) }}</strong></div>
            @endif
            @if($cond['sold_30d'])
            <div>{{ number_format($cond['sold_30d']) }} sold last 30 days</div>
            @endif
            @if($cond['avg_dom'])
            <div>Avg {{ $cond['avg_dom'] }} days on market</div>
            @endif
          </div>
          @elseif($card['active'] > 0)
          <div style="font-size:13px;color:var(--muted);margin-bottom:8px;">{{ number_format($card['active']) }} active listings</div>
          @if($card['avg_list'])
          <div style="font-size:13px;color:var(--muted);">Avg asking ${{ number_format($card['avg_list']) }}</div>
          @endif
          @else
          <div style="font-size:13px;color:var(--muted);">No active listings</div>
          @endif

          <div style="margin-top:14px;font-size:12px;font-weight:600;color:var(--accent);text-decoration:underline;">View market guide &rsaquo;</div>
        </div>
      </a>
      @endforeach
    </div>
  </section>
  @endif

  <section class="section" aria-labelledby="listings-heading" style="border-top:1px solid var(--border);padding-top:40px;">
    <h2 id="listings-heading" class="h2 mb-32">Active {{ $typeLabel }}</h2>
    @if(isset($listings) && $listings->count() > 0)
      <div class="grid-3">
        @foreach($listings as $listing)
          @include('themes.shared.listing-card', ['listing' => $listing])
        @endforeach
      </div>
      <div class="pagination">{{ $listings->appends(request()->query())->links('vendor.pagination.simple-bootstrap-5') }}</div>
    @else
      <p style="color:var(--muted);">No active {{ strtolower($typeLabel) }} found. <a href="{{ route('agent.search', $agent->slug) }}" style="color:var(--text);text-decoration:underline;">View all listings</a>.</p>
    @endif
  </section>
</div>
@endsection
