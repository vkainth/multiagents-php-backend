@extends('themes.classic-dark.layout')

@php
  $metaTitle = 'Seller\'s Guide — ' . $agent->name . ' · ' . ($agent->brokerage ?? 'REALTOR®');
@endphp

@section('head')
<meta name="description" content="{{ $agent->name }}'s seller's guide for {{ $territories->keys()->implode(', ') }}. Price right, prepare well, and sell for more.">
@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $agent->name }}</div>
    <h1 class="page-header__title">Seller's Guide</h1>
    <p class="page-header__sub">How to price right, prepare your property, and achieve the best outcome in {{ $territories->keys()->first() }}.</p>
  </div>
</div>

<div class="container">
  <section class="section">
    <div style="display:grid;grid-template-columns:260px 1fr;gap:56px;align-items:start;">

      <div class="guide-toc">
        <div class="guide-toc__title">In This Guide</div>
        <ul class="guide-toc__links">
          @foreach([
            ['1', 'Get a Market Evaluation', 'step-eval'],
            ['2', 'Prepare Your Property', 'step-prepare'],
            ['3', 'Pricing Strategy', 'step-pricing'],
            ['4', 'Marketing', 'step-marketing'],
            ['5', 'Showings & Offers', 'step-offers'],
            ['6', 'Negotiating', 'step-negotiate'],
            ['7', 'Closing', 'step-closing'],
          ] as [$num, $label, $anchor])
          <li><a href="#{{ $anchor }}"><span class="guide-toc__num">{{ $num }}</span>{{ $label }}</a></li>
          @endforeach
        </ul>
      </div>

      <div>
        <div id="step-eval" class="guide-section">
          <h2>1. Get a Market Evaluation</h2>
          <p>Before you decide to sell, know what your home is actually worth — not what you hope, and not what automated estimates say. {{ explode(' ', $agent->name)[0] }} pulls the actual sold comparables: properties in your building or on your street that have sold in the last 90 days.</p>
          <p>An accurate valuation prevents two costly mistakes: overpricing (sitting on market, eventual price reduction) and underpricing (leaving money on the table).</p>
          <div class="guide-tip">
            <strong>Free evaluation:</strong> {{ explode(' ', $agent->name)[0] }} provides a no-obligation market evaluation within 6 hours.
            <a href="{{ route('agent.home-evaluation', $agent->slug) }}" style="color:var(--accent);">Request yours →</a>
          </div>
        </div>

        <div id="step-prepare" class="guide-section">
          <h2>2. Prepare Your Property</h2>
          <p>First impressions happen online, before a buyer ever sets foot inside. Professional photography sells homes for more — period.</p>
          <ul class="guide-checklist">
            @foreach(['Deep clean every room including windows', 'Declutter and depersonalise', 'Touch up scuffs and paint', 'Replace burnt-out bulbs', 'Professional photography', 'Consider staging consultation', 'Clean and tidy building common areas before photos', 'Repair any obvious defects (dripping taps, squeaky doors)'] as $item)
            <li><span class="guide-checklist__icon">✓</span>{{ $item }}</li>
            @endforeach
          </ul>
        </div>

        <div id="step-pricing" class="guide-section">
          <h2>3. Pricing Strategy</h2>
          <p>Pricing is the single most important decision in selling. Too high and you sit on market — buyers assume something's wrong. Too low and you leave money behind.</p>
          <p>{{ explode(' ', $agent->name)[0] }}'s 18+ years in {{ $territories->keys()->first() }} means he knows which buildings command premiums, which streets buyers prefer, and how seasonal timing affects values.</p>
          <div class="guide-tip">
            <strong>The truth about overpricing:</strong> Properties that sit on market for 30+ days typically sell for less than their original list price after a reduction. Getting the price right on day one is always better.
          </div>
        </div>

        <div id="step-marketing" class="guide-section">
          <h2>4. Marketing</h2>
          <p>Your listing appears on MLS and all major real estate portals (Realtor.ca, BCCondosAndHomes.com, Zillow, etc.) within hours of listing. Beyond the platforms, active marketing matters.</p>
          <ul class="guide-checklist">
            @foreach(['Professional HDR photography', 'MLS listing (REBGV / FVREB)', 'Realtor.ca, BCCondosAndHomes.com, and partner sites', 'Social media promotion to buyer audience', 'Email to active buyer watch list', 'Open houses as appropriate', 'Agent-to-agent network marketing', 'Direct mail to building / neighbourhood (for houses)'] as $item)
            <li><span class="guide-checklist__icon">✓</span>{{ $item }}</li>
            @endforeach
          </ul>
        </div>

        <div id="step-offers" class="guide-section">
          <h2>5. Showings & Offers</h2>
          <p>Every showing is a qualified buyer. {{ explode(' ', $agent->name)[0] }} handles booking, follow-up, and feedback after every showing so you know what buyers are thinking.</p>
          <p>Offer review is a structured process. You'll see each offer in full and understand its terms before deciding to accept, counter, or reject.</p>
        </div>

        <div id="step-negotiate" class="guide-section">
          <h2>6. Negotiating</h2>
          <p>Negotiation is more than price. Completion date, possession date, included items (appliances, lighting, window coverings), and subject clauses all have dollar value.</p>
          <div class="guide-tip">
            <strong>{{ explode(' ', $agent->name)[0] }}'s approach:</strong> Data-driven, calm, never emotional. The goal is your best net outcome — not the highest offer number if the terms are unfavorable.
          </div>
        </div>

        <div id="step-closing" class="guide-section">
          <h2>7. Closing</h2>
          <p>Once subjects are removed, the sale is firm. Your lawyer or notary handles the legal transfer. You'll receive the net proceeds on completion day after deducting the mortgage payout and realtor commissions.</p>
          <ul class="guide-checklist">
            @foreach(['Confirm completion and possession dates with your lawyer', 'Cancel utilities effective possession date', 'Cancel home insurance effective possession date', 'Remove personal belongings and clean', 'Leave keys, remotes, and manuals for the buyer'] as $item)
            <li><span class="guide-checklist__icon">✓</span>{{ $item }}</li>
            @endforeach
          </ul>
        </div>

        <div style="background:var(--nav-bg);border-radius:var(--radius);padding:32px;margin-top:40px;text-align:center;">
          <h3 class="h3" style="color:#fff;margin-bottom:12px;">Ready to Find Out What Your Home Is Worth?</h3>
          <p style="color:rgba(255,255,255,0.6);margin-bottom:24px;">Free, no-obligation evaluation within 6 hours. Based on real sold comparables.</p>
          <a href="{{ route('agent.home-evaluation', $agent->slug) }}" class="btn-cta">Get My Free Evaluation</a>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
