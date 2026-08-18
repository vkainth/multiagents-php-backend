@extends('themes.classic-dark.layout')

@php $metaTitle = 'Open Houses — ' . $agent->name . ' · ' . $territories->keys()->first(); @endphp

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $agent->name }}</div>
    <h1 class="page-header__title">Open Houses</h1>
    <p class="page-header__sub">Upcoming open houses in {{ $territories->keys()->implode(', ') }}.</p>
  </div>
</div>

<div class="container">
  <section class="section">
    @if(isset($openHouses) && count($openHouses) > 0)
      <div style="max-width:680px;">
        @include('themes.shared.open-house-widget', ['openHouses' => $openHouses])
      </div>
    @else
      <div style="text-align:center;padding:80px 0;color:var(--muted);">
        <div style="font-size:40px;margin-bottom:16px;">🏡</div>
        <h2 class="h3 mb-12">No open houses scheduled right now</h2>
        <p style="font-size:15px;margin-bottom:24px;">Contact {{ explode(' ', $agent->name)[0] }} to arrange a private showing anytime.</p>
        <a href="{{ route('agent.home-evaluation', $agent->slug) }}" class="btn-cta">Request a Showing</a>
      </div>
    @endif
  </section>
</div>
@endsection
