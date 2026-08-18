@extends('frontend.layouts.default_mobile')
@section('title')Client Reviews | Hani & Les | BC Condos And Homes @endsection
@section('meta_description')Read what clients say about working with Hani & Les | BC Condos And Homes — BC's top-ranked RE/MAX team. 4.8 stars from 700+ Google reviews. @endsection
@section('meta')
    <meta property="og:title" content="Client Reviews | Hani &amp; Les | BC Condos And Homes">
    <meta property="og:description" content="Read what clients say about working with Hani &amp; Les | BC Condos And Homes — BC's top-ranked RE/MAX team. 4.8 stars from 700+ Google reviews.">
    <meta property="og:url" content="https://www.bccondosandhomes.com/reviews">
    <meta property="og:type" content="website">
    <meta property="og:image" content="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.svg">
    <meta property="og:site_name" content="BC Condos And Homes">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Client Reviews | Hani &amp; Les | BC Condos And Homes">
    <meta name="twitter:description" content="Read what clients say about working with Hani &amp; Les | BC Condos And Homes — BC's top-ranked RE/MAX team. 4.8 stars from 700+ Google reviews.">
    <meta name="twitter:image" content="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.svg">
    <link rel="canonical" href="https://www.bccondosandhomes.com/reviews">
    @php
    $_gp = \Illuminate\Support\Facades\Cache::get('google_place_summary', ['rating' => 4.8, 'user_ratings_total' => 709]);
    @endphp
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "RealEstateAgent",
      "name": "BC Condos And Homes",
      "alternateName": "Hani & Les | BC Condos And Homes",
      "url": "https://www.bccondosandhomes.com",
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "{{ $_gp['rating'] ?? '4.8' }}",
        "reviewCount": "{{ $_gp['user_ratings_total'] ?? '709' }}",
        "bestRating": "5",
        "worstRating": "1"
      }
    }
    </script>
@endsection
@section('content')
    @include('frontend.includes.header')
@php
$user = auth()->user();
@endphp
<style>
.navigation nav a { text-transform: none !important; }
.bcch-reviews-page-wrap { max-width: 1080px; margin: 0 auto; padding: 80px 20px 60px; }
.bcch-reviews-page-wrap h1 { font-size: 30px; font-weight: 700; color: #1a1a2e; margin-bottom: 12px; font-family: Roboto, Arial, sans-serif; }
.bcch-reviews-page-wrap .bcch-reviews-intro { font-size: 16px; color: #555; line-height: 1.7; margin-bottom: 36px; max-width: 720px; }
</style>

<div class="bcch-reviews-page-wrap">
    <h1>What Our Clients Are Saying</h1>
    <p class="bcch-reviews-intro">
        We are proud of our 4.8-star rating from 700+ Google reviews.
        Our clients range from first-time buyers navigating Vancouver's competitive market to experienced sellers trusting us with their most significant asset.
        Below is a selection of their experiences with Hani &amp; Les | BC Condos And Homes.
        <a href="https://g.page/r/CbDjNg3F9MxQEAI/review" target="_blank" rel="noopener" style="color:#1a6baa;font-weight:600;">Share your own experience &rarr;</a>
    </p>

    @include('frontend.includes.google_reviews_hardcoded')
</div>
<br/>
@endsection
