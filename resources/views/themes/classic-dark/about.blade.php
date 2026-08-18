@extends('themes.classic-dark.layout')

@php
  $metaTitle = 'About ' . $agent->name . ' — ' . ($agent->brokerage ?? 'REALTOR®');
@endphp

@section('head')
<meta name="description" content="About {{ $agent->name }}, REALTOR® at {{ $agent->brokerage }}. Specializing in {{ $territories->keys()->implode(', ') }}.">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "{{ $agent->name }}",
  "jobTitle": "REALTOR®",
  "worksFor": { "@type": "Organization", "name": "{{ $agent->brokerage }}" },
  "telephone": "{{ $agent->phone }}",
  "email": "{{ $agent->email }}"
}
</script>
@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">REALTOR®</div>
    <h1 class="page-header__title">About {{ $agent->name }}</h1>
    <p class="page-header__sub">{{ $agent->brokerage }}{{ $territories->keys()->count() > 0 ? ' · ' . $territories->keys()->implode(', ') : '' }}</p>
  </div>
</div>

<div class="container">
  <section class="section">
    <div class="about-bio-grid">
      <div>
        @if($agent->photo_path)
          <div class="about-photo">
            <img src="{{ asset($agent->photo_path) }}" alt="{{ $agent->name }}" itemprop="image">
          </div>
        @endif
        <div class="about-meta">
          <div class="about-meta__name">{{ $agent->name }}</div>
          @if($agent->brokerage)
            <div class="about-meta__brokerage">{{ $agent->brokerage }}</div>
          @endif
          @if($agent->license_number)
            <div class="about-meta__license">License #{{ $agent->license_number }}</div>
          @endif
          @if($testimonials->count() > 0)
            <div class="about-meta__rating">★★★★★ 5.0 &nbsp;·&nbsp; {{ $testimonials->count() }} Google Reviews</div>
          @endif
          <div class="about-meta__contact">
            @if($agent->phone)
              <a href="tel:{{ $agent->phone }}">{{ $agent->phone }}</a>
            @endif
            @if($agent->email)
              <a href="mailto:{{ $agent->email }}" style="font-size:12px;color:var(--muted);">{{ $agent->email }}</a>
            @endif
          </div>
        </div>
      </div>
      <div>
        @if($agent->bio)
          @foreach(explode("\n\n", $agent->bio) as $para)
            <p style="color:var(--muted);line-height:1.9;margin-bottom:18px;font-size:15px;">{{ trim($para) }}</p>
          @endforeach
        @endif
        <div style="margin-top:28px;display:flex;gap:14px;flex-wrap:wrap;">
          <a href="{{ route('agent.home-evaluation', $agent->slug) }}" class="btn-cta">Get a Free Home Evaluation</a>
          <a href="{{ route('agent.search', $agent->slug) }}" class="btn-outline">View Listings</a>
        </div>
      </div>
    </div>
  </section>

  {{-- Testimonials --}}
  @if($testimonials->count() > 0)
  <section class="section" aria-labelledby="reviews-heading">
    <h2 id="reviews-heading" class="h2 mb-32">What Clients Say</h2>
    @php $featured = $testimonials->first(); @endphp
    <div class="testimonial-pull mb-32">
      <p class="testimonial-pull__quote">"{{ $featured->quote }}"</p>
      <div class="testimonial-pull__author">{{ $featured->reviewer_name }}</div>
      <div class="testimonial-pull__source">{{ $featured->location ?? '' }} · ★★★★★</div>
    </div>
    <div class="grid-2">
      @foreach($testimonials->skip(1) as $t)
      <div class="testimonial-card">
        <p class="testimonial-card__quote">"{{ $t->quote }}"</p>
        <div class="testimonial-card__author">{{ $t->reviewer_name }}</div>
        <div class="testimonial-card__source">{{ $t->location ?? '' }} · ★★★★★</div>
      </div>
      @endforeach
    </div>
  </section>
  @endif

  {{-- Contact CTA --}}
  <section class="section" aria-labelledby="contact-heading">
    <div class="grid-2" style="gap:64px;align-items:start;">
      <div>
        <h2 id="contact-heading" class="h2 mb-16">Get in Touch</h2>
        <p style="color:var(--muted);line-height:1.9;font-size:15px;margin-bottom:24px;">
          Whether you're buying, selling, or simply curious about the market —
          {{ explode(' ', $agent->name)[0] }} is happy to answer questions. No pressure, no obligation.
        </p>
        <div class="info-box" style="margin-bottom:16px;">
          @if($agent->phone)
            <div><strong>Phone:</strong> <a href="tel:{{ $agent->phone }}" style="color:var(--accent);">{{ $agent->phone }}</a></div>
          @endif
          @if($agent->email)
            <div><strong>Email:</strong> <a href="mailto:{{ $agent->email }}" style="color:var(--accent);">{{ $agent->email }}</a></div>
          @endif
          @if($agent->brokerage)
            <div><strong>Brokerage:</strong> {{ $agent->brokerage }}</div>
          @endif
        </div>
      </div>
      @include('themes.shared.lead-form-w1', ['formHeading' => 'Send a Message', 'formSub' => explode(' ', $agent->name)[0] . ' typically responds within a few hours.'])
    </div>
  </section>
</div>
@endsection
