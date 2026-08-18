@extends('themes.classic-dark.layout')

@section('head')
@if($buildings->count() > 0)
@php
$itemListElements = [];
foreach ($buildings->items() as $i => $b) {
    $bName = $b->name ?: $b->complex ?: trim(($b->street_no ?? '') . ' ' . ($b->street_name ?? ''));
    $bUrl  = route('agent.building', ['agentSlug' => $agent->slug, 'buildingSlug' => $b->slug]);
    $itemListElements[] = [
        '@@type'    => 'ListItem',
        'position'  => ($buildings->currentPage() - 1) * $buildings->perPage() + $i + 1,
        'name'      => $bName,
        'url'       => $bUrl,
    ];
}
$jsonLd = [
    '@@context' => 'https://schema.org',
    '@@type'    => 'ItemList',
    'name'      => 'Condos & Buildings in ' . $territories->keys()->implode(', '),
    'numberOfItems' => $buildings->total(),
    'itemListElement' => $itemListElements,
];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $territories->keys()->implode(' · ') }}</div>
    <h1 class="page-header__title">Buildings &amp; Condos</h1>
    <p class="page-header__sub">Browse all buildings {{ $agent->name }} covers</p>
  </div>
</div>

<div class="container">
  <section class="section">
    @if($buildings->count() > 0)
      <div class="grid-3">
        @foreach($buildings as $building)
          @include('themes.shared.building-card', ['building' => $building])
        @endforeach
      </div>

      @if($buildings->hasPages())
        <div style="margin-top:40px;text-align:center;">
          {{ $buildings->links() }}
        </div>
      @endif
    @else
      <p style="color:var(--muted);text-align:center;padding:60px 0;">No buildings found in this area yet.</p>
    @endif
  </section>
</div>
@endsection
