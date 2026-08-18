{{--
  Lightweight grid card for the buildings list page.
  Uses precomputed controller data to avoid N+1 queries:
    $building->active_count — from SQL subquery in AgentController::buildings()
    $buildingImages          — strata_no → image URL map, batch-fetched in controller
  No per-card DB calls are made.
--}}
@php
  $city         = $building->city ?? '';
  $activeCount  = (int)($building->active_count ?? 0);
  $agentSlug    = isset($agent) ? $agent->slug : null;
  $buildingHref = $agentSlug
    ? route('agent.building', ['agentSlug' => $agentSlug, 'buildingSlug' => $building->slug])
    : route('building-detail-page', ['slug' => $building->slug]);
  $imgUrl = (!empty($building->strata_no) && isset($buildingImages[$building->strata_no]))
    ? $buildingImages[$building->strata_no]
    : asset('frontend/images/apartment-condo-condominium-275484.jpg');
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
