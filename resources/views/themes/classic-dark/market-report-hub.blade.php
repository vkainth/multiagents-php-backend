@extends('themes.classic-dark.layout')

@php
  $metaTitle = 'Market Reports — ' . $agent->name . ' · ' . $territories->keys()->first();
@endphp

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $agent->name }}</div>
    <h1 class="page-header__title">Market Reports</h1>
    <p class="page-header__sub">Monthly analysis of the {{ $territories->keys()->implode(', ') }} real estate market — inventory, prices, and buyer/seller balance.</p>
  </div>
</div>

<div class="container">
  <section class="section">
    @if(isset($reports) && count($reports) > 0)
      <div class="grid-3">
        @foreach($reports as $report)
        <a href="{{ $report['url'] }}" class="listing-card" style="display:block;">
          <div class="listing-card__body" style="padding:24px;">
            <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--accent);margin-bottom:8px;">{{ $report['area'] }}</div>
            <div class="h3 mb-8">{{ $report['title'] }}</div>
            <div style="color:var(--muted);font-size:13px;">{{ $report['date'] }}</div>
          </div>
        </a>
        @endforeach
      </div>
    @else
      <p style="color:var(--muted);font-size:15px;margin-bottom:32px;">
        {{ explode(' ', $agent->name)[0] }} publishes monthly market reports for {{ $territories->keys()->implode(', ') }}.
        Reports cover active listings, sold prices, days on market, and buy/sell balance.
        <a href="{{ route('agent.contact', $agent->slug) }}" style="color:var(--accent);">Subscribe to get them by email</a>.
      </p>
      <div style="background:var(--alt);border-radius:var(--radius);padding:32px;max-width:600px;">
        <h3 class="h3 mb-12">Get the monthly report</h3>
        @include('themes.shared.lead-form-w1', ['formHeading' => 'Subscribe to Market Reports', 'formSub' => 'Monthly data for ' . $territories->keys()->first() . '. Unsubscribe any time.'])
      </div>
    @endif
  </section>
</div>
@endsection
