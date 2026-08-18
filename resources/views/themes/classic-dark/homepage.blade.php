@extends('themes.classic-dark.layout')

@section('head')
<meta name="description" content="{{ $agent->name }} — REALTOR® specializing in {{ $territories->keys()->implode(', ') }}. {{ $agent->bio ? \Illuminate\Support\Str::limit($agent->bio, 140) : '' }}">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "RealEstateAgent",
  "name": "{{ $agent->name }}",
  "url": "{{ url()->current() }}",
  "telephone": "{{ $agent->phone }}",
  "email": "{{ $agent->email }}",
  "description": "{{ $agent->bio ? \Illuminate\Support\Str::limit($agent->bio, 200) : $agent->name . ' — REALTOR® in ' . $territories->keys()->implode(', ') }}"
}
</script>
@endsection

@section('content')

{{-- ── HERO ────────────────────────────────────────────────── --}}
<section class="hero" aria-label="Hero">
  <img class="hero__img"
    src="{{ $heroImageUrl ?? 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1440&h=700&fit=crop' }}"
    alt="{{ $territories->keys()->first() }} real estate" loading="eager">
  <div class="hero__overlay"></div>
  <div class="hero__content hero__content--split">
    <div class="hero__left">
      <div class="hero__eyebrow">
        {{ $agent->brokerage }}
        @foreach($territories->keys()->take(3) as $city)
          &nbsp;·&nbsp; {{ $city }}
        @endforeach
      </div>
      <h1 class="hero__title">{{ $agent->name }}</h1>
      <div class="hero__subtitle">
        REALTOR®
        @if($agent->sold_count ?? false) &nbsp;·&nbsp; {{ $agent->sold_count }} homes sold @endif
        @if($agent->experience ?? false) &nbsp;·&nbsp; Since {{ date('Y') - intval($agent->experience) }} @endif
      </div>
      @if($agent->bio)
        <p class="hero__bio">{{ \Illuminate\Support\Str::limit($agent->bio, 220) }}</p>
      @endif

      <form class="hero__search" action="{{ route('agent.search', $agent->slug) }}" method="GET" role="search" aria-label="Search listings">
        <select name="city" aria-label="Area">
          <option value="">Any Area</option>
          @foreach($territories->keys() as $city)
            <option value="{{ $city }}">{{ $city }}</option>
          @endforeach
        </select>
        <select name="type" aria-label="Property type">
          <option value="">Any Type</option>
          <option value="Apartment">Condo</option>
          <option value="Townhouse">Townhouse</option>
          <option value="House">House</option>
        </select>
        <select name="beds" aria-label="Bedrooms">
          <option value="">Any Beds</option>
          <option value="1+">1+</option>
          <option value="2+">2+</option>
          <option value="3+">3+</option>
        </select>
        <button type="submit" class="btn-search">Search</button>
      </form>
    </div>

    @if($agent->photo_path)
    <div class="hero__agent-card" aria-label="Agent profile">
      <div class="hero__agent-photo">
        <img src="{{ asset($agent->photo_path) }}" alt="{{ $agent->name }}" loading="eager">
      </div>
      <div class="hero__agent-info">
        <div class="hero__agent-name">{{ $agent->name }}</div>
        <div class="hero__agent-brokerage">{{ $agent->brokerage }}</div>
        @if($agent->phone)
          <a href="tel:{{ $agent->phone }}" class="hero__agent-phone">{{ $agent->phone }}</a>
        @endif
      </div>
    </div>
    @endif
  </div>
</section>

{{-- ── MARKET TICKER ──────────────────────────────────────── --}}
<div class="market-ticker" aria-label="Market snapshot">
  @if(isset($statsBar))
    <div class="market-ticker__stat">
      <div class="market-ticker__value">{{ number_format($statsBar['active_count'] ?? 0) }}</div>
      <div class="market-ticker__label">Active listings</div>
    </div>
    <div class="market-ticker__stat">
      <div class="market-ticker__value">{{ number_format($statsBar['sold_count'] ?? 0) }}</div>
      <div class="market-ticker__label">Sold this month</div>
    </div>
    @if(($statsBar['avg_sold_price'] ?? 0) > 0)
    <div class="market-ticker__stat">
      <div class="market-ticker__value">${{ $statsBar['avg_sold_price'] >= 1000000 ? number_format($statsBar['avg_sold_price']/1000000,2).'M' : number_format($statsBar['avg_sold_price']/1000,0).'K' }}</div>
      <div class="market-ticker__label">Avg sold price</div>
    </div>
    @endif
    @if(($statsBar['list_to_sale'] ?? 0) > 0)
    <div class="market-ticker__stat">
      <div class="market-ticker__value">{{ number_format($statsBar['list_to_sale'],1) }}%</div>
      <div class="market-ticker__label">List-to-sale ratio</div>
    </div>
    @endif
  @endif
</div>

<div class="container">

  {{-- ── FEATURED LISTINGS ────────────────────────────────── --}}
  @if(isset($featuredListings) && $featuredListings->count() > 0)
  <section class="section" aria-labelledby="featured-heading">
    <div class="flex-between mb-32">
      <h2 id="featured-heading" class="h2">Featured Listings</h2>
      <a href="{{ route('agent.search', $agent->slug) }}" style="color:var(--muted);font-size:14px;border-bottom:1px solid var(--border);padding-bottom:2px;">View all listings</a>
    </div>
    <div class="grid-3">
      @foreach($featuredListings->take(3) as $listing)
        @include('themes.shared.listing-card', ['listing' => $listing])
      @endforeach
    </div>
  </section>
  @endif

  {{-- ── MARKET SNAPSHOT ─────────────────────────────────── --}}
  @if(isset($statsBar) && ($statsBar['active_count'] ?? 0) + ($statsBar['sold_count'] ?? 0) > 0)
  <section class="section" aria-labelledby="market-heading">
    <div style="display:grid;grid-template-columns:1fr 1.85fr;gap:52px;align-items:start;">
      <div>
        <h2 id="market-heading" class="h2" style="margin-bottom:18px;">
          {{ $territories->keys()->first() }}<br>Market, {{ date('F Y') }}
        </h2>
        <p style="color:var(--muted);line-height:1.9;font-size:15px;margin-bottom:16px;">
          @if(($statsBar['sold_count'] ?? 0) > 0)
            {{ number_format($statsBar['sold_count']) }} homes sold last month.
          @endif
          @if(($statsBar['list_to_sale'] ?? 0) > 0)
            Buyers paid an average of {{ number_format($statsBar['list_to_sale'],1) }}% of asking price.
          @endif
          @if(($statsBar['active_count'] ?? 0) > 0)
            There are currently {{ number_format($statsBar['active_count']) }} active listings in {{ explode(' ', $agent->name)[0] }}'s territory.
          @endif
        </p>
        <a href="{{ route('agent.market-stats', $agent->slug) }}" class="btn-outline">Full Market Stats →</a>
      </div>
      <div class="stat-cards">
        @foreach([
          ['Market condition', ($statsBar['market_condition'] ?? 'Active'), ''],
          ['Avg sold price', isset($statsBar['avg_sold_price']) && $statsBar['avg_sold_price'] > 0 ? '$'.($statsBar['avg_sold_price'] >= 1000000 ? number_format($statsBar['avg_sold_price']/1000000,2).'M' : number_format($statsBar['avg_sold_price']/1000,0).'K') : '—', date('F Y')],
          ['Homes sold (30d)', number_format($statsBar['sold_count'] ?? 0), 'Reported sales'],
          ['Avg days on market', round($statsBar['avg_dom'] ?? 0) ?: '—', 'Days to accepted offer'],
          ['List-to-sale ratio', ($statsBar['list_to_sale'] ?? 0) > 0 ? number_format($statsBar['list_to_sale'],1).'%' : '—', 'Price accuracy'],
          ['Active inventory', number_format($statsBar['active_count'] ?? 0), 'Live MLS listings'],
        ] as [$label, $value, $sub])
        <div class="stat-card">
          <div class="stat-card__label">{{ $label }}</div>
          <div class="stat-card__value">{{ $value }}</div>
          @if($sub)<div class="stat-card__sub">{{ $sub }}</div>@endif
        </div>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  {{-- ── HOME EVALUATION ─────────────────────────────────── --}}
  <section class="section" aria-labelledby="eval-heading">
    <div class="grid-2" style="gap:64px;align-items:start;">
      <div>
        <h2 id="eval-heading" class="h2" style="margin-bottom:18px;">What's Your Home Worth?</h2>
        <p style="color:var(--muted);line-height:1.9;margin-bottom:22px;font-size:15px;">
          {{ $territories->keys()->first() }} home values have shifted significantly in recent months.
          {{ explode(' ', $agent->name)[0] }} provides a data-backed evaluation within 6 hours — based on real sold comparables in your building or street, not automated estimates.
        </p>
        <div class="info-box info-box--accent">
          <strong>No obligation. No pressure.</strong> {{ explode(' ', $agent->name)[0] }} educates first — you decide on your own timeline.
        </div>
      </div>
      @include('themes.shared.lead-form-w2', ['neighbourhood' => $territories->keys()->first()])
    </div>
  </section>

  {{-- ── ABOUT ────────────────────────────────────────────── --}}
  <section class="section" aria-labelledby="about-heading">
    <div class="about-bio-grid">
      <div>
        @if($agent->photo_path)
          <div class="about-photo">
            <img src="{{ asset($agent->photo_path) }}" alt="{{ $agent->name }}" loading="lazy">
          </div>
        @endif
        <div class="about-meta">
          <div class="about-meta__name">{{ $agent->name }}</div>
          @if($agent->brokerage)
            <div class="about-meta__brokerage">{{ $agent->brokerage }}</div>
          @endif
          @if($testimonialCount > 0)
            <div class="about-meta__rating">★★★★★ 5.0 &nbsp;·&nbsp; {{ $testimonialCount }} Google Reviews</div>
          @endif
          <div class="about-meta__contact">
            @if($agent->phone)
              <a href="tel:{{ $agent->phone }}">{{ $agent->phone }}</a>
            @endif
            @if($agent->email)
              <a href="mailto:{{ $agent->email }}" style="color:var(--muted);font-size:12px;">{{ $agent->email }}</a>
            @endif
          </div>
        </div>
      </div>
      <div>
        <h2 id="about-heading" class="h2" style="margin-bottom:20px;">
          Focused on one stretch of BC
        </h2>
        @if($agent->bio)
          <p style="color:var(--muted);line-height:1.9;margin-bottom:18px;font-size:15px;">{{ $agent->bio }}</p>
        @endif
        @if(isset($testimonials) && $testimonials->count() > 0)
          @php $t = $testimonials->first(); @endphp
          <div class="about-quote">
            "{{ $t->quote }}"
            <div class="about-quote__author">— {{ $t->reviewer_name }}, {{ $t->location ?? 'Client' }}</div>
          </div>
        @endif
        <div style="margin-top:24px;">
          <a href="{{ route('agent.about', $agent->slug) }}" class="btn-outline">More About {{ explode(' ', $agent->name)[0] }} →</a>
        </div>
      </div>
    </div>
  </section>

  {{-- ── AREAS ────────────────────────────────────────────── --}}
  @if($territories->count() > 0)
  <section class="section" aria-labelledby="areas-heading">
    <div class="flex-between mb-24">
      <h2 id="areas-heading" class="h2">Areas {{ explode(' ', $agent->name)[0] }} Covers</h2>
      @if(isset($statsBar))
        <span style="color:var(--muted);font-size:14px;">{{ number_format($statsBar['active_count'] ?? 0) }} active listings across all areas</span>
      @endif
    </div>
    <div class="area-grid">
      {{-- Large left tile --}}
      @php $firstCity = $territories->keys()->first(); @endphp
      <a href="{{ route('agent.search', $agent->slug) }}?city={{ urlencode($firstCity) }}" class="area-tile">
        <img src="{{ $areaImages[$firstCity] ?? 'https://images.unsplash.com/photo-1486325212027-8081e485255e?w=900&h=580&fit=crop' }}" alt="{{ $firstCity }}" loading="lazy">
        <div class="area-tile__overlay"></div>
        <div class="area-tile__content">
          <div class="area-tile__name">{{ $firstCity }}</div>
          <div class="area-tile__desc">{{ $territories[$firstCity]->where('subarea','!=','')->pluck('subarea')->implode(' · ') }}</div>
          <div class="area-tile__count">{{ $areaCounts[$firstCity] ?? 0 }} active listings</div>
        </div>
      </a>
      {{-- Right column --}}
      <div class="area-grid__right">
        @foreach($territories->keys()->skip(1)->take(2) as $city)
        <a href="{{ route('agent.search', $agent->slug) }}?city={{ urlencode($city) }}" class="area-tile area-tile--sm">
          <img src="{{ $areaImages[$city] ?? 'https://images.unsplash.com/photo-1445991842772-097fea258e7b?w=600&h=300&fit=crop' }}" alt="{{ $city }}" loading="lazy">
          <div class="area-tile__overlay"></div>
          <div class="area-tile__content">
            <div class="area-tile__name">{{ $city }}</div>
            <div class="area-tile__count">{{ $areaCounts[$city] ?? 0 }} active listings</div>
          </div>
        </a>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  {{-- ── RECENT SOLDS ─────────────────────────────────────── --}}
  @if(isset($recentSolds) && $recentSolds->count() > 0)
  <section class="section" aria-labelledby="solds-heading">
    <div class="flex-between mb-32">
      <h2 id="solds-heading" class="h2">Recent Solds</h2>
      <a href="{{ route('agent.sold', $agent->slug) }}" style="color:var(--muted);font-size:14px;border-bottom:1px solid var(--border);padding-bottom:2px;">View all sold</a>
    </div>
    <div class="grid-4">
      @foreach($recentSolds->take(4) as $listing)
        @include('themes.shared.listing-card', ['listing' => $listing])
      @endforeach
    </div>
  </section>
  @endif

  {{-- ── MORTGAGE PRE-QUAL ────────────────────────────────── --}}
  <section class="section" aria-labelledby="prequal-heading">
    <div class="grid-2" style="gap:64px;align-items:start;">
      <div>
        <h2 id="prequal-heading" class="h2" style="margin-bottom:18px;">Know Your Number Before You Shop</h2>
        <p style="color:var(--muted);line-height:1.9;margin-bottom:20px;font-size:15px;">
          Most buyers who've lost out in multiple-offer situations weren't outbid — they weren't prepared.
          {{ explode(' ', $agent->name)[0] }} connects buyers with licensed BC mortgage brokers who can turn around a pre-qualification in 24 hours, with access to 30+ lenders.
        </p>
        <div class="info-box">
          <strong>{{ explode(' ', $agent->name)[0] }}'s mortgage partners</strong><br>
          <span style="color:var(--muted);font-size:13px;">Licensed BC mortgage brokers. No hard credit pull until you're ready.</span>
        </div>
      </div>
      @include('themes.shared.lead-form-w3')
    </div>
  </section>

  {{-- ── TESTIMONIALS ─────────────────────────────────────── --}}
  @if(isset($testimonials) && $testimonials->count() > 0)
  <section class="section section--lg" aria-labelledby="reviews-heading">
    <div class="flex-between mb-32" style="align-items:baseline;">
      <h2 id="reviews-heading" class="h2">What clients say</h2>
      @if($testimonialCount > 0)
        <span style="color:var(--muted);font-size:13px;">5.0 / 5 &nbsp;·&nbsp; {{ $testimonialCount }} Google Reviews</span>
      @endif
    </div>

    @php $featured = $testimonials->first(); $others = $testimonials->skip(1)->take(2); @endphp

    @if($featured)
    <div class="testimonial-pull">
      <p class="testimonial-pull__quote">"{{ $featured->quote }}"</p>
      <div class="testimonial-pull__author">{{ $featured->reviewer_name }}</div>
      <div class="testimonial-pull__source">{{ $featured->location ?? '' }}{{ $featured->source ? ' · ' . ucfirst($featured->source) . ' Review' : '' }} · ★★★★★</div>
    </div>
    @endif

    @if($others->count() > 0)
    <div class="grid-2">
      @foreach($others as $t)
      <div class="testimonial-card">
        <p class="testimonial-card__quote">"{{ $t->quote }}"</p>
        <div class="testimonial-card__author">{{ $t->reviewer_name }}</div>
        <div class="testimonial-card__source">{{ $t->location ?? '' }} · ★★★★★</div>
      </div>
      @endforeach
    </div>
    @endif
  </section>
  @endif

</div>{{-- /.container --}}
@endsection
