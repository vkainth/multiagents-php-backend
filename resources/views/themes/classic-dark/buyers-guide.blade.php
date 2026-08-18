@extends('themes.classic-dark.layout')

@php
  $metaTitle = 'Buyer\'s Guide — ' . $agent->name . ' · ' . ($agent->brokerage ?? 'REALTOR®');
@endphp

@section('head')
<meta name="description" content="{{ $agent->name }}'s buyer's guide for {{ $territories->keys()->implode(', ') }} real estate. Step-by-step advice from search to closing.">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "How to Buy a Home in {{ $territories->keys()->first() }}",
  "description": "Step-by-step buyer's guide by {{ $agent->name }}, REALTOR® at {{ $agent->brokerage }}",
  "author": { "@type": "Person", "name": "{{ $agent->name }}" }
}
</script>
@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $agent->name }}</div>
    <h1 class="page-header__title">Buyer's Guide</h1>
    <p class="page-header__sub">Everything you need to know about buying in {{ $territories->keys()->implode(', ') }} — from first search to keys in hand.</p>
  </div>
</div>

<div class="container">
  <section class="section">
    <div style="display:grid;grid-template-columns:260px 1fr;gap:56px;align-items:start;">

      {{-- TOC --}}
      <div class="guide-toc">
        <div class="guide-toc__title">In This Guide</div>
        <ul class="guide-toc__links">
          @foreach([
            ['1', 'Get Pre-Approved First', 'step-preapproval'],
            ['2', 'Define Your Search', 'step-search'],
            ['3', 'Viewing Properties', 'step-viewing'],
            ['4', 'Making an Offer', 'step-offer'],
            ['5', 'Subjects & Due Diligence', 'step-subjects'],
            ['6', 'Closing the Deal', 'step-closing'],
            ['7', 'Moving In', 'step-moving'],
          ] as [$num, $label, $anchor])
          <li><a href="#{{ $anchor }}"><span class="guide-toc__num">{{ $num }}</span>{{ $label }}</a></li>
          @endforeach
        </ul>
      </div>

      {{-- Guide content --}}
      <div>
        <div id="step-preapproval" class="guide-section">
          <h2>1. Get Pre-Approved First</h2>
          <p>Before you view a single property, get a mortgage pre-approval. In {{ $territories->keys()->first() }}, multiple-offer situations are common — arriving without financing in place means you can't compete.</p>
          <p>A pre-approval tells you exactly how much you can spend, locks in your rate for 90–120 days, and sends a clear signal to sellers that you're a serious buyer.</p>
          <div class="guide-tip">
            <strong>{{ explode(' ', $agent->name)[0] }}'s tip:</strong> A pre-qualification letter is not the same as a pre-approval. Insist on a full pre-approval with income and asset verification before you start viewing.
          </div>
        </div>

        <div id="step-search" class="guide-section">
          <h2>2. Define Your Search</h2>
          <p>Know what you need vs. what you want. Needs are non-negotiable (bedrooms for your family size, school catchment, strata pet policy). Wants are trade-offs (view, floor level, building age).</p>
          <p>In {{ $territories->keys()->first() }}, the gap between condo, townhouse, and detached prices is significant. Being flexible on type can unlock dramatically more inventory in your budget.</p>
          <ul class="guide-checklist">
            @foreach(['Bedrooms and bathrooms required', 'Parking requirements (EV charging?)', 'Pet policy (dogs vs cats only)', 'Strata rental restrictions', 'School catchment if children', 'Proximity to transit or highway', 'Building age and depreciation report', 'Maximum strata fee tolerance'] as $item)
            <li><span class="guide-checklist__icon">✓</span>{{ $item }}</li>
            @endforeach
          </ul>
        </div>

        <div id="step-viewing" class="guide-section">
          <h2>3. Viewing Properties</h2>
          <p>View with your eyes open, not your heart. Pay attention to what you can't change — floor, view corridor, traffic noise, suite layout — rather than paint colour and staging.</p>
          <p>For condos, request the strata minutes and financials before viewing if possible. A well-run building is more important than granite countertops.</p>
          <div class="guide-tip">
            <strong>What to look for:</strong> Water stains on ceilings, condition of common hallways, whether the building smells clean, age of plumbing, and any postings on the notice board.
          </div>
        </div>

        <div id="step-offer" class="guide-section">
          <h2>4. Making an Offer</h2>
          <p>Your offer is a legal contract. Key terms include: purchase price, completion date, possession date, and any subjects (conditions).</p>
          <p>{{ explode(' ', $agent->name)[0] }} will pull the sold comparables for the specific address and advise on the right price. Overpaying is avoidable with the right data.</p>
          <div class="guide-tip">
            <strong>Deposit:</strong> Typically 5% of purchase price, due within 24 hours of acceptance. Have it in a liquid account before you make an offer.
          </div>
        </div>

        <div id="step-subjects" class="guide-section">
          <h2>5. Subjects & Due Diligence</h2>
          <p>Standard subjects include financing approval and property inspection. For strata properties, add a subject for review of the strata documents (minutes, depreciation report, financials, bylaws).</p>
          <p>Never waive subjects unless you have complete information. Even in hot markets, protecting yourself from a bad investment is worth the risk of losing to another buyer.</p>
        </div>

        <div id="step-closing" class="guide-section">
          <h2>6. Closing the Deal</h2>
          <p>Once subjects are removed, you have a firm sale. Your lawyer or notary handles the legal transfer. You'll need to arrange home insurance and arrange for the balance of funds (purchase price less deposit) by completion date.</p>
          <ul class="guide-checklist">
            @foreach(['Hire a real estate lawyer or notary', 'Arrange home insurance effective completion date', 'Book movers for possession date', 'Forward mail and update address', 'Transfer utilities effective possession date'] as $item)
            <li><span class="guide-checklist__icon">✓</span>{{ $item }}</li>
            @endforeach
          </ul>
        </div>

        <div id="step-moving" class="guide-section">
          <h2>7. Moving In</h2>
          <p>On possession day, you receive keys from your lawyer or notary. Do a final walkthrough before accepting possession if possible.</p>
          <p>For strata properties, register with the strata manager, get the amenity fob, and review parking and storage assignments.</p>
        </div>

        <div style="background:var(--nav-bg);border-radius:var(--radius);padding:32px;margin-top:40px;text-align:center;">
          <h3 class="h3" style="color:#fff;margin-bottom:12px;">Ready to Start Your Search?</h3>
          <p style="color:rgba(255,255,255,0.6);margin-bottom:24px;">{{ explode(' ', $agent->name)[0] }} has helped hundreds of buyers in {{ $territories->keys()->first() }} — from first-timers to investors.</p>
          <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="{{ route('agent.search', $agent->slug) }}" class="btn-cta">Browse Listings</a>
            <a href="{{ route('agent.home-evaluation', $agent->slug) }}" class="btn-outline" style="color:rgba(255,255,255,0.7);border-color:rgba(255,255,255,0.3);">Get Pre-Qual Help</a>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
