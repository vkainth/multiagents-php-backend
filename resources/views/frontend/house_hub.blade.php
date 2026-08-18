@extends('frontend.layouts.default')
@php
$metaTitle = 'Houses for Sale in Metro Vancouver, BC | MLS® Listings & Market Data';
$metaDesc  = 'Browse MLS® houses for sale across Metro Vancouver. Compare avg sold prices, days on market, and buyer\'s vs seller\'s market conditions by city. Updated daily from MLS® records.';
$canonicalUrl = 'https://www.bccondosandhomes.com/houses/';
@endphp
@section('title'){{ $metaTitle }}@endsection
@section('meta_description'){{ $metaDesc }}@endsection
@section('meta')
<link rel="canonical" href="{{ $canonicalUrl }}">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "BreadcrumbList",
      "itemListElement": [
        {"@type":"ListItem","position":1,"name":"Home","item":"https://www.bccondosandhomes.com/"},
        {"@type":"ListItem","position":2,"name":"Houses","item":"{{ $canonicalUrl }}"}
      ]
    },
    {
      "@type": "FAQPage",
      "mainEntity": [
        {"@type":"Question","name":"What is the average house price in Metro Vancouver?","acceptedAnswer":{"@type":"Answer","text":"Average house prices in Metro Vancouver vary significantly by city. Vancouver detached homes typically average $2M+, while more affordable options exist in cities like Surrey, Coquitlam, and Maple Ridge. Browse individual city pages for current 30-day averages from live MLS® data."}},
        {"@type":"Question","name":"Is Metro Vancouver a buyer's or seller's market for houses?","acceptedAnswer":{"@type":"Answer","text":"Market conditions vary by city and neighbourhood. Check each city's house market page for the current absorption rate, days on market, and market verdict based on the latest MLS® sales data."}}
      ]
    }
  ]
}
</script>
@endsection

@section('content')
@include('frontend.includes.header')

<div class="page-main" style="padding:36px 0 20px;background:linear-gradient(135deg,#1a2a3a 0%,#2c3e50 100%);color:#fff;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:12px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/" style="color:rgba(255,255,255,.7);">Home</a></li>
                <li class="breadcrumb-item active" style="color:#fff;">Houses</li>
            </ol>
        </nav>
        <h1 style="font-size:28px;font-weight:700;margin-bottom:12px;color:#fff;line-height:1.3;">Houses for Sale in Metro Vancouver, BC</h1>
        <p style="font-size:15px;color:rgba(255,255,255,.82);max-width:820px;line-height:1.7;margin-bottom:0;">
            Browse MLS® houses for sale across Metro Vancouver. Compare average sold prices, days on market,
            and buyer's vs seller's market conditions by city — updated daily from live MLS® board records.
        </p>
    </div>
</div>

<div class="container" style="padding-top:28px;padding-bottom:50px;min-height:60vh;">

    {{-- CITY COMPARISON TABLE --}}
    <h2 style="font-size:20px;font-weight:700;color:#2c2c2c;margin-bottom:16px;">Metro Vancouver House Market — City Comparison</h2>

    <div style="background:#fff;border:1px solid #e2dbd2;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden;margin-bottom:28px;">
        <div class="table-responsive">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#1a2a3a;color:#fff;text-align:left;">
                        <th style="padding:12px 16px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">City</th>
                        <th style="padding:12px 16px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Avg House Price</th>
                        <th style="padding:12px 16px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Sold (30d)</th>
                        <th style="padding:12px 16px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Avg DOM</th>
                        <th style="padding:12px 16px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Market</th>
                        <th style="padding:12px 16px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cities as $city)
                    @php
                        $cSlug = App\Helpers\Helper::enslugPlace($city->place);
                        $cond  = $cityStats[$city->place] ?? ['label'=>null,'color'=>'#888','avg_sold_30d'=>0,'sold_30d'=>0,'avg_dom'=>0,'insufficient_data'=>true,'current_active'=>0];
                    @endphp
                    <tr style="border-top:1px solid #f0ede8;" class="house-city-row">
                        <td style="padding:12px 16px;font-weight:700;color:#2c2c2c;">
                            <a href="/houses/{{ $cSlug }}/" style="color:#2c2c2c;text-decoration:none;">{{ $city->label ?? $city->place }}</a>
                        </td>
                        <td style="padding:12px 16px;">
                            @if($cond['avg_sold_30d'])
                            @php $p = $cond['avg_sold_30d']; @endphp
                            <strong style="color:#2c2c2c;">${{ $p >= 1000000 ? number_format($p/1000000, 2).'M' : number_format(round($p/1000)).'K' }}</strong>
                            @else
                            <span style="color:#bbb;">—</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;color:#555;">
                            @if($cond['sold_30d'])
                            <a href="/search-listings/{{ $cSlug }}?type=House&listing_status=sold" style="color:#555;text-decoration:underline dotted;">{{ $cond['sold_30d'] }}</a>
                            @else
                            —
                            @endif
                        </td>
                        <td style="padding:12px 16px;color:#555;">{{ $cond['avg_dom'] ? $cond['avg_dom'].'d' : '—' }}</td>
                        <td style="padding:12px 16px;">
                            @if($cond['label'] && !$cond['insufficient_data'])
                            <span style="background:{{ $cond['color'] }};color:#fff;border-radius:3px;padding:3px 8px;font-size:11px;font-weight:700;white-space:nowrap;">{{ $cond['label'] }}</span>
                            @else
                            <span style="color:#bbb;font-size:12px;">Insufficient data</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            <a href="/houses/{{ $cSlug }}/" style="color:#2c6fad;font-size:12px;font-weight:600;white-space:nowrap;">View guide &rsaquo;</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- WHAT CAN YOU AFFORD — price-range filtered links --}}
    <section style="background:#f7f4ef;border:1px solid #e2dbd2;border-radius:6px;padding:22px 26px;margin-bottom:28px;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin:0 0 10px;">Find a House in Your Budget</h2>
        <p style="font-size:14px;color:#555;margin-bottom:16px;line-height:1.65;">
            Browse Metro Vancouver detached homes filtered by price range.
        </p>
        <div class="row">
            @php
            $priceTiers = [
                ['label' => 'Under $1M', 'min' => 50000,   'max' => 1000000],
                ['label' => '$1M – $1.5M', 'min' => 1000000, 'max' => 1500000],
                ['label' => '$1.5M – $2M', 'min' => 1500000, 'max' => 2000000],
                ['label' => '$2M – $3M',   'min' => 2000000, 'max' => 3000000],
                ['label' => '$3M+',        'min' => 3000000, 'max' => 20000000],
            ];
            @endphp
            @foreach($priceTiers as $tier)
            <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:12px;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:5px;overflow:hidden;">
                    <div style="background:#1a2a3a;color:#fff;padding:7px 10px;font-size:11px;font-weight:700;text-align:center;">{{ $tier['label'] }}</div>
                    <div style="padding:8px 6px;display:flex;flex-wrap:wrap;gap:4px;">
                        @foreach($cities->take(4) as $c)
                        @php $cSlug = App\Helpers\Helper::enslugPlace($c->place); @endphp
                        <a href="/search-listings/{{ $cSlug }}?type=House&min_price={{ $tier['min'] }}&max_price={{ $tier['max'] }}" style="font-size:11px;color:#2c6fad;text-decoration:none;white-space:nowrap;">{{ $c->label ?? $c->place }}</a>
                        @if(!$loop->last)<span style="color:#ddd;">·</span>@endif
                        @endforeach
                        @if($cities->count() > 4)
                        <span style="font-size:11px;color:#999;">+{{ $cities->count() - 4 }} more</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div style="margin-top:10px;font-size:13px;color:#666;">
            <a href="/search-listings?type=House" style="color:#2c6fad;font-weight:600;">Browse all Metro Vancouver houses for sale &rsaquo;</a>
            &nbsp;·&nbsp;
            @foreach($cities as $c)
            @php $cSlug = App\Helpers\Helper::enslugPlace($c->place); @endphp
            <a href="/search-listings/{{ $cSlug }}?type=House" style="margin:0 6px;color:#555;font-size:12px;">{{ $c->label ?? $c->place }}</a>
            @endforeach
        </div>
    </section>

    {{-- RECENT LISTINGS STRIP --}}
    @if($recentListings && count($recentListings))
    <section style="margin-bottom:30px;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:16px;">Recently Listed Houses &amp; Detached Homes</h2>
        <div class="row">
            @foreach($recentListings as $lst)
            @php $photo = $lst->aphoto ? 'https://media.pixilinkserver.com/' . str_replace('images', '', $lst->aphoto->directory . $lst->aphoto->name) : null; @endphp
            <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:14px;">
                <a href="/listing/{{ $lst->slug }}" style="text-decoration:none;color:inherit;">
                    <div style="border:1px solid #e2dbd2;border-radius:5px;overflow:hidden;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                        @if($photo)
                        <div style="height:100px;background:url('{{ $photo }}') center/cover no-repeat;background-color:#f0ede8;"></div>
                        @else
                        <div style="height:100px;background:#f0ede8;display:flex;align-items:center;justify-content:center;color:#bbb;font-size:11px;">No Photo</div>
                        @endif
                        <div style="padding:8px 10px;">
                            <div style="font-size:13px;font-weight:700;color:#2c6fad;">${{ number_format($lst->listprice_2) }}</div>
                            <div style="font-size:11px;color:#777;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $lst->city }} @if($lst->subarea)· {{ $lst->subarea }}@endif
                            </div>
                            <div style="font-size:11px;color:#999;">
                                @if($lst->bedrooms){{ $lst->bedrooms }}bd @endif
                                @if($lst->bathstotal){{ $lst->bathstotal }}ba @endif
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        <div style="margin-top:4px;font-size:13px;">
            <a href="/search-listings?type=House" style="color:#2c6fad;font-weight:600;">See all Metro Vancouver houses for sale &rsaquo;</a>
        </div>
    </section>
    @endif

    {{-- ABOUT SECTION --}}
    <section style="margin-bottom:30px;padding-top:20px;border-top:1px solid #eee;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:12px;">About the Metro Vancouver House Market</h2>
        <div style="font-size:14px;color:#444;line-height:1.8;max-width:820px;">
            <p>
                Metro Vancouver's detached home market is diverse — from luxury single-family homes in West Vancouver and Point Grey 
                to more affordable houses in Maple Ridge, Surrey, and Langley. The Hani & Les | BC Condos And Homes house market hub tracks 
                all property types including houses, duplexes, fourplexes and triplexes, giving you a complete picture of the 
                detached home market.
            </p>
            <p style="margin-top:10px;">
                Data is sourced directly from the Real Estate Board of Greater Vancouver (REBGV) and Fraser Valley Real Estate Board 
                (FVREB) MLS® feeds, updated daily. Absorption rates, average days on market, and average sold prices are calculated 
                from actual sales data over the last 30 and 90 days.
            </p>
        </div>
    </section>

    {{-- INTERNAL LINKS --}}
    <div style="padding:16px 0;border-top:1px solid #eee;font-size:13px;color:#666;line-height:2;">
        <strong>Browse by city:</strong>
        @foreach($cities as $c)
        <a href="/houses/{{ App\Helpers\Helper::enslugPlace($c->place) }}/" style="margin:0 8px;color:#2c6fad;">Houses for sale in {{ $c->label ?? $c->place }}</a>
        @endforeach
        <a href="/neighbourhood/" style="margin:0 8px;color:#555;">Neighbourhood Guides</a>
        <a href="/market-stats" style="margin:0 8px;color:#555;">Market Stats</a>
    </div>
</div>

<div class="container">
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => 'Metro Vancouver Houses',
        'stripHeading'    => 'Get Alerts for New House Listings',
        'stripSubtext'    => 'Be first to know when new houses hit the market across Metro Vancouver — updated daily from MLS®.',
        'stripSearchName' => 'Metro Vancouver House Listings',
        'stripSearchData' => json_encode(['listing_status' => 'Active', 'type' => 'House']),
        'stripModalId'    => 'hhubAlert',
    ])
</div>
@include('frontend.includes.footer_links')
@include('frontend.includes.footer')
@endsection

@push('after-styles')
<style>
.house-city-row:hover { background:#fafaf8 !important; }
.house-city-row:hover td:first-child a { color:#2c6fad !important; }
</style>
@endpush

@push('after-scripts')
<script>
window.BCTrack = window.BCTrack || {};
window.BCTrack.pageType     = "buy";
window.BCTrack.propertyType = "house";
</script>
@endpush
