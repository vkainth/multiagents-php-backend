{{--
  Shared building card — used across ALL themes.
  Variables:
    $building — Buildings model
--}}
@php
  $imgUrl = $building->main_image();
  $city   = $building->city ?? '';
  $activeCount = $building->active_listings()->count();
  $agentSlug = isset($agent) ? $agent->slug : null;

  // Use agent-scoped route when rendering inside an agent theme; fall back to
  // the main-site route for all other contexts.
  $buildingHref = $agentSlug
    ? route('agent.building', ['agentSlug' => $agentSlug, 'buildingSlug' => $building->slug])
    : route('building-detail-page', ['slug' => $building->slug]);
@endphp

<article class="building-card" itemscope itemtype="https://schema.org/ApartmentComplex">
  <a href="{{ $buildingHref }}" aria-label="{{ $building->name ?? $building->complex ?? 'Building' }}">
    <img src="{{ $imgUrl }}" alt="{{ $building->name ?? $building->complex }}" loading="lazy" itemprop="image">
    <div class="building-card__overlay"></div>
    <div class="building-card__content">
      <div class="building-card__name" itemprop="name">{{ $building->name ?? $building->complex ?? $building->street_no . ' ' . $building->street_name }}</div>
      <div class="building-card__sub">
        {{ $city }}{{ $building->subarea ? ' · ' . $building->subarea : '' }}
        @if($activeCount > 0)
          &nbsp;·&nbsp; {{ $activeCount }} active
        @endif
      </div>
    </div>
    <meta itemprop="address" content="{{ $building->street_no }} {{ $building->street_name }}, {{ $city }}">
  </a>
</article>
