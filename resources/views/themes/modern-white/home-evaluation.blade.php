@extends('themes.modern-white.layout')

@php
  $metaTitle = 'Free Home Evaluation — ' . $agent->name;
@endphp

@section('head')
<meta name="description" content="Get a free, data-backed home evaluation from {{ $agent->name }} in {{ $territories->keys()->implode(', ') }}. Results within 6 hours. No obligation.">
@endsection

@section('w4-headline')What's your home worth?@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $agent->name }}</div>
    <h1 class="page-header__title">Free Home Evaluation</h1>
    <p class="page-header__sub">A data-backed valuation within 6 hours — based on real sold comparables in your building or street. No obligation, no pressure.</p>
  </div>
</div>

<div class="container">
  <section class="section">
    <div class="grid-2" style="gap:64px;align-items:start;">
      <div>
        <p class="eyebrow mb-16">Why accuracy matters</p>
        <h2 class="h2 mb-16">An Estimate You Can Trust</h2>
        <p style="color:var(--muted);line-height:1.9;font-size:15px;margin-bottom:18px;">
          Automated estimates (Zestimate, etc.) are calculated from public data with no knowledge of your building, unit floor, renovation quality, or recent comparable sales. They can be off by 10–20% in either direction.
        </p>
        <p style="color:var(--muted);line-height:1.9;font-size:15px;margin-bottom:18px;">
          {{ explode(' ', $agent->name)[0] }} reviews actual MLS sold data — properties in your specific building or on your street — and provides a realistic price range with a clear explanation.
        </p>
        <div class="info-box info-box--accent mb-24">
          <strong>No obligation. No pressure.</strong><br>
          This is a conversation, not a sales pitch. You'll get real data and advice — then decide on your own timeline.
        </div>

        <div style="margin-bottom:32px;">
          <div style="font-weight:700;font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:16px;">What you receive</div>
          <ul class="guide-checklist">
            @foreach(['Comparable sold properties in your building/street (last 90 days)', 'Current active listings you\'re competing with', 'Recommended list price range with rationale', 'Current market conditions (buyer\'s vs seller\'s)', 'Suggested timing if seasonal factors apply', 'PDF summary you can keep'] as $item)
            <li><span class="guide-checklist__icon">✓</span>{{ $item }}</li>
            @endforeach
          </ul>
        </div>

        @if($agent->photo_path)
        <div style="display:flex;align-items:center;gap:16px;padding:18px;border:1px solid var(--border);border-radius:var(--radius);background:#fff;">
          <div style="width:52px;height:52px;border-radius:50%;overflow:hidden;border:1px solid var(--border);flex-shrink:0;">
            <img src="{{ asset($agent->photo_path) }}" alt="{{ $agent->name }}" style="width:100%;height:100%;object-fit:cover;">
          </div>
          <div>
            <div style="font-family:var(--serif);font-weight:700;font-size:15px;">{{ $agent->name }}</div>
            <div style="font-size:11px;color:var(--muted);letter-spacing:0.5px;text-transform:uppercase;margin-bottom:4px;">{{ $agent->brokerage }}</div>
            @if($agent->phone)
              <a href="tel:{{ $agent->phone }}" style="font-size:13px;color:var(--text);font-weight:600;">{{ $agent->phone }}</a>
            @endif
          </div>
        </div>
        @endif
      </div>

      @include('themes.shared.lead-form-w2', [
        'formHeading' => 'Get My Free Evaluation',
        'formSub' => 'Fill in your property details and ' . explode(' ', $agent->name)[0] . ' will deliver a data-backed valuation within 6 hours.',
        'neighbourhood' => $territories->keys()->first()
      ])
    </div>
  </section>
</div>
@endsection
