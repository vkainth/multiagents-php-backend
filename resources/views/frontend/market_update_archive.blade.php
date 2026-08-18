@extends('frontend.layouts.default_mobile')
@section('title'){{ $metaTitle }}@endsection
@section('meta_description'){{ $metaDesc }}@endsection
@section('meta')
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="website">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type":"ListItem","position":1,"name":"Home","item":"https://www.bccondosandhomes.com/"},
    {"@type":"ListItem","position":2,"name":"Market Updates","item":"https://www.bccondosandhomes.com/market-update/{{ $citySlug }}"},
    {"@type":"ListItem","position":3,"name":"{{ $city }}","item":"{{ $canonical }}"}
  ]
}
</script>
@endsection
@section('content')
@include('frontend.includes.header')

<div class="page-main" style="margin-top:66px;padding:28px 0 0;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:10px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item active">{{ $city }} Market Updates</li>
            </ol>
        </nav>
        <h1 style="font-size:24px;font-weight:700;margin-bottom:6px;color:#2c2c2c;">
            {{ $city }} Real Estate Market Update
        </h1>
        <p style="font-size:14px;color:#666;max-width:780px;line-height:1.7;margin-bottom:20px;">
            Monthly real estate market updates for {{ $city }}, BC — sold counts, average prices, days on market, and market condition based on MLS® data.
        </p>
    </div>
</div>

<div class="container" style="padding-bottom:40px;">

    {{-- Quick links to other market intel pages --}}
    <div class="row" style="margin-bottom:24px;">
        <div class="col-md-12">
            <div style="background:#f7f4ef;border-radius:6px;padding:12px 18px;font-size:13px;display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
                <strong style="color:#555;">{{ $city }} market intel:</strong>
                <a href="/new-listings/{{ $citySlug }}" style="color:#2c6fad;text-decoration:none;font-weight:600;">New Listings This Week</a>
                <a href="/price-reductions/{{ $citySlug }}" style="color:#2c6fad;text-decoration:none;font-weight:600;">Price Reductions</a>
                <a href="/sold-over-asking/{{ $citySlug }}" style="color:#2c6fad;text-decoration:none;font-weight:600;">Sold Over Asking</a>
                <a href="/market-report/{{ $citySlug }}" style="color:#555;text-decoration:none;">Market Reports →</a>
            </div>
        </div>
    </div>

    @if(empty($enriched))
    <div class="row">
        <div class="col-md-12">
            <div style="background:#fff8e1;border-left:4px solid #f39c12;border-radius:4px;padding:14px 18px;font-size:14px;color:#666;">
                No monthly updates are available for <strong>{{ $city }}</strong> yet. Check back soon.
            </div>
        </div>
    </div>
    @else
    <div class="row">
        @foreach($enriched as $m)
        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-bottom:16px;">
            <a href="/market-update/{{ $citySlug }}/{{ $m->yr }}/{{ $m->mo }}"
               style="display:block;background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px;text-decoration:none;color:#333;transition:box-shadow .15s;">
                <div style="font-size:15px;font-weight:700;color:#2c6fad;margin-bottom:6px;">{{ $m->label }}</div>
                @if($m->count_sold)
                <div style="font-size:13px;color:#555;margin-bottom:3px;"><strong>{{ number_format($m->count_sold) }}</strong> units sold</div>
                @endif
                @if($m->avg_price)
                <div style="font-size:13px;color:#555;margin-bottom:3px;">Avg <strong>${{ number_format($m->avg_price) }}</strong></div>
                @endif
                @if($m->avg_dom)
                <div style="font-size:12px;color:#888;">{{ $m->avg_dom }} days on market</div>
                @endif
            </a>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Email alert signup --}}
    <div class="row" style="margin-top:30px;">
        <div class="col-md-8 col-md-offset-2">
            @include('frontend.includes.market_intel_alert_widget', ['city' => $city, 'citySlug' => $citySlug, 'source' => 'market_update_archive'])
        </div>
    </div>

    <div class="row" style="margin-top:24px;">
        <div class="col-md-12">
            <div style="font-size:12px;color:#aaa;">
                Data sourced from MLS® records. Updated monthly. &nbsp;
                <a href="/market-stats/{{ $citySlug }}" style="color:#2c6fad;">Live market stats →</a>
            </div>
        </div>
    </div>
</div>

@include('frontend.includes.footer_links')
@include('frontend.includes.footer')
@endsection
