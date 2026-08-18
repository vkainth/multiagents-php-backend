@extends('themes.classic-dark.layout')

@php
  $lifestyleLabel = $lifestyle ?? 'Lifestyle';
  $metaTitle = $lifestyleLabel . ' Homes — ' . $territories->keys()->first() . ' · ' . $agent->name;
@endphp

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $agent->name }}</div>
    <h1 class="page-header__title">{{ $lifestyleLabel }} Homes</h1>
    <p class="page-header__sub">in {{ $territories->keys()->implode(', ') }}</p>
  </div>
</div>

<div class="container">
  @if(isset($listings) && $listings->count() > 0)
  <section class="section">
    <div class="grid-3">
      @foreach($listings as $listing)
        @include('themes.shared.listing-card', ['listing' => $listing])
      @endforeach
    </div>
    <div class="pagination">{{ $listings->appends(request()->query())->links('vendor.pagination.simple-bootstrap-5') }}</div>
  </section>
  @else
  <section class="section">
    <p style="color:var(--muted);font-size:15px;">No {{ strtolower($lifestyleLabel) }} listings found. <a href="{{ route('agent.search', $agent->slug) }}" style="color:var(--accent);">View all listings</a>.</p>
  </section>
  @endif
</div>
@endsection
