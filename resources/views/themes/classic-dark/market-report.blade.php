@extends('themes.classic-dark.layout')

@php
  $metaTitle = 'Market Report — ' . ($reportTitle ?? date('F Y')) . ' · ' . $agent->name;
@endphp

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $agent->name }} · Market Report</div>
    <h1 class="page-header__title">{{ $reportTitle ?? date('F Y') . ' Market Report' }}</h1>
    <p class="page-header__sub">{{ $territories->keys()->implode(', ') }} real estate market analysis.</p>
  </div>
</div>

<div class="container">
  <section class="section">
    @if(isset($reportContent))
      {!! $reportContent !!}
    @else
      <div style="max-width:740px;margin:0 auto;">
        <p style="color:var(--muted);line-height:1.9;font-size:15px;margin-bottom:24px;">
          The {{ date('F Y') }} market report for {{ $territories->keys()->implode(' and ') }} is being compiled.
          {{ explode(' ', $agent->name)[0] }} publishes monthly reports covering active inventory, sold prices, days on market, and the buyer/seller balance.
        </p>
        <a href="{{ route('agent.market-report-hub', $agent->slug) }}" class="btn-outline">← All Market Reports</a>
      </div>
    @endif
  </section>
</div>
@endsection
