@extends('frontend.layouts.default')
@php
$metaTitle = 'Metro Vancouver Neighbourhood Real Estate Guides | Hani & Les | BC Condos And Homes';
$metaDesc  = 'Explore neighbourhood-level real estate guides for Metro Vancouver and the Fraser Valley — market stats, top buildings, current listings and price trends for every area.';
$canonicalUrl = 'https://www.bccondosandhomes.com/neighbourhood/';
@endphp
@section('title'){{ $metaTitle }}@endsection
@section('meta_description'){{ $metaDesc }}@endsection
@section('meta')
<link rel="canonical" href="{{ $canonicalUrl }}">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "Metro Vancouver Neighbourhood Real Estate Guides",
  "description": "Neighbourhood-level market guides for Metro Vancouver and Fraser Valley real estate.",
  "itemListElement": [
    @foreach($cities as $i => $city)
    {"@type":"ListItem","position":{{ $i + 1 }},"name":"{{ e($city->label ?? $city->place) }} Neighbourhood Guide","url":"https://www.bccondosandhomes.com/neighbourhood/{{ App\Helpers\Helper::enslugPlace($city->place) }}/"}@if(!$loop->last),@endif
    @endforeach
  ]
}
</script>
@endsection

@section('content')
@include('frontend.includes.header')

<div class="nhub-hero" style="margin-top:80px;padding:32px 0 16px;background:#f7f4ef;border-bottom:1px solid #e2dbd2;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:10px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item active">Neighbourhoods</li>
            </ol>
        </nav>
        <h1 style="font-size:26px;font-weight:700;margin-bottom:10px;color:#2c2c2c;">Metro Vancouver Neighbourhood Real Estate Guides</h1>
        <p style="font-size:15px;color:#555;max-width:820px;line-height:1.65;margin-bottom:0;">
            Comprehensive neighbourhood-level real estate guides for Metro Vancouver and the Fraser Valley.
            Each guide covers current market conditions, average prices, top condo buildings, active listings, and 12-month price trends —
            updated daily from MLS® board records.
        </p>
    </div>
</div>

<div class="container" style="padding-top:30px;padding-bottom:50px;min-height:60vh;">

    <div class="row">
        @foreach($cities as $city)
        @php
            $cSlug = App\Helpers\Helper::enslugPlace($city->place);
            $cond  = $cityStats[$city->place] ?? ['label' => null, 'color' => '#888', 'absorption_rate' => 0, 'current_active' => 0, 'avg_sold_30d' => 0];
        @endphp
        <div class="col-md-4 col-sm-6" style="margin-bottom:22px;">
            <a href="/neighbourhood/{{ $cSlug }}/" style="text-decoration:none;color:inherit;">
                <div class="nhub-city-card" style="border:1px solid #e2dbd2;border-radius:6px;padding:18px 20px;background:#fff;box-shadow:0 2px 6px rgba(0,0,0,.06);height:100%;transition:box-shadow .15s;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <div style="flex:1;font-size:17px;font-weight:700;color:#2c2c2c;">{{ $city->label ?? $city->place }}</div>
                        @if($cond['label'])
                        <span class="nbadge" style="font-size:11px;font-weight:700;color:#fff;background:{{ $cond['color'] }};border-radius:3px;padding:2px 7px;white-space:nowrap;">{{ $cond['label'] }}</span>
                        @endif
                    </div>
                    <div style="font-size:13px;color:#666;line-height:1.8;">
                        @if($cond['current_active'])
                        <div>{{ number_format($cond['current_active']) }} active listings</div>
                        @endif
                        @if($cond['avg_sold_30d'])
                        <div>Avg sold: <strong>${{ number_format($cond['avg_sold_30d']) }}</strong> (30d)</div>
                        @endif
                        @if($cond['absorption_rate'])
                        <div>Absorption: {{ $cond['absorption_rate'] }}%</div>
                        @endif
                    </div>
                    <div style="margin-top:12px;font-size:13px;color:#2c6fad;font-weight:600;">View neighbourhood guides &rsaquo;</div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    <div style="margin-top:30px;background:#fafaf8;border:1px solid #e2dbd2;border-radius:6px;padding:22px 26px;">
        <h2 style="font-size:16px;font-weight:700;color:#333;margin-bottom:14px;">Quick Links by Region</h2>
        <div class="row">
            <div class="col-sm-6 col-md-3" style="margin-bottom:14px;">
                <div style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Vancouver</div>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:2;">
                    <li><a href="/neighbourhood/vancouver/downtown/">Downtown Vancouver</a></li>
                    <li><a href="/neighbourhood/vancouver/yaletown/">Yaletown</a></li>
                    <li><a href="/neighbourhood/vancouver/coal-harbour/">Coal Harbour</a></li>
                    <li><a href="/neighbourhood/vancouver/kitsilano/">Kitsilano</a></li>
                    <li><a href="/neighbourhood/vancouver/">All Vancouver →</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:14px;">
                <div style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Burnaby</div>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:2;">
                    <li><a href="/neighbourhood/burnaby/metrotown/">Metrotown</a></li>
                    <li><a href="/neighbourhood/burnaby/brentwood-park/">Brentwood Park</a></li>
                    <li><a href="/neighbourhood/burnaby/highgate/">Highgate</a></li>
                    <li><a href="/neighbourhood/burnaby/">All Burnaby →</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:14px;">
                <div style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">North Shore</div>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:2;">
                    <li><a href="/neighbourhood/north-vancouver/lower-lonsdale/">Lower Lonsdale</a></li>
                    <li><a href="/neighbourhood/north-vancouver/central-lonsdale/">Central Lonsdale</a></li>
                    <li><a href="/neighbourhood/west-vancouver/">West Vancouver →</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:14px;">
                <div style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Surrey</div>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:2;">
                    <li><a href="/neighbourhood/surrey/fleetwood-tynehead/">Fleetwood</a></li>
                    <li><a href="/neighbourhood/surrey/newton/">Newton</a></li>
                    <li><a href="/neighbourhood/surrey/cloverdale/">Cloverdale</a></li>
                    <li><a href="/neighbourhood/surrey/guildford/">Guildford</a></li>
                    <li><a href="/neighbourhood/surrey/south-surrey/">South Surrey</a></li>
                    <li><a href="/neighbourhood/surrey/whalley/">Whalley</a></li>
                    <li><a href="/neighbourhood/surrey/">All Surrey →</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:14px;">
                <div style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Richmond</div>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:2;">
                    <li><a href="/neighbourhood/richmond/brighouse/">Brighouse</a></li>
                    <li><a href="/neighbourhood/richmond/steveston/">Steveston</a></li>
                    <li><a href="/neighbourhood/richmond/">All Richmond →</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div style="margin-top:24px;padding:18px 0;border-top:1px solid #eee;font-size:13px;color:#666;line-height:1.7;">
        Browse market stats by city: 
        @foreach($cities as $c)
        <a href="/market-stats/{{ App\Helpers\Helper::enslugPlace($c->place) }}" style="margin-right:10px;">{{ $c->label ?? $c->place }}</a>
        @endforeach
    </div>

    <div style="background:#1a2a3a;border-radius:8px;padding:22px 28px;margin-top:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
        <div>
            <div style="font-size:16px;font-weight:700;color:#fff;margin-bottom:4px;">Looking for the Top Realtor in Your Area?</div>
            <div style="font-size:13px;color:#aac4e0;">Hani Faraj — RE/MAX Diamond Club &bull; 30+ years BC experience &bull; Top 100 Western Canada</div>
        </div>
        <a href="/top-realtor/" style="background:#e5b021;color:#111;border-radius:5px;padding:10px 22px;font-size:14px;font-weight:700;text-decoration:none;white-space:nowrap;">Find a Top Realtor &rsaquo;</a>
    </div>
</div>

<div class="container">
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => 'Metro Vancouver',
        'stripHeading'    => 'Get New Listing Alerts for Metro Vancouver',
        'stripSubtext'    => 'Be first to know when new condos, houses, and townhouses hit the market across Metro Vancouver.',
        'stripSearchName' => 'Metro Vancouver Listings',
        'stripSearchData' => json_encode(['listing_status' => 'Active']),
        'stripModalId'    => 'nhubAlert',
    ])
</div>
@include('frontend.includes.hani_bubble')
@include('frontend.includes.footer_links')
@include('frontend.includes.footer')

<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
></script>

@endsection

@push('after-styles')
<style>
.nhub-city-card:hover { box-shadow: 0 4px 14px rgba(0,0,0,.12) !important; }
</style>
@endpush
