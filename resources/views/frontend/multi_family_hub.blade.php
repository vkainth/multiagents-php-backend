@extends('frontend.layouts.default')
@php
$metaTitle = 'Multi-Family Properties for Sale in Metro Vancouver, BC — Duplex, Triplex & Fourplex | MLS® Data';
$metaDesc  = 'Browse MLS® duplex, triplex, and fourplex properties for sale across Metro Vancouver. Compare avg sold prices, days on market, and market conditions by city. Updated daily.';
$canonicalUrl = 'https://www.bccondosandhomes.com/multi-family/';
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
        {"@type":"ListItem","position":2,"name":"Multi-Family Properties","item":"{{ $canonicalUrl }}"}
      ]
    },
    {
      "@type": "FAQPage",
      "mainEntity": [
        {"@type":"Question","name":"What types of multi-family properties are sold in Metro Vancouver?","acceptedAnswer":{"@type":"Answer","text":"Metro Vancouver's multi-family market includes duplexes (two-unit), triplexes (three-unit), and fourplexes (four-unit) — all tracked via MLS® board records from REBGV and FVREB, updated daily."}},
        {"@type":"Question","name":"Is Metro Vancouver a buyer's or seller's market for multi-family properties?","acceptedAnswer":{"@type":"Answer","text":"Market conditions vary by city and neighbourhood. Check individual city pages for the current absorption rate, days on market, and market verdict based on the latest MLS® sales data for duplex, triplex, and fourplex properties."}}
      ]
    }
  ]
}
</script>
@endsection

@section('content')
@include('frontend.includes.header')

<div class="page-main" style="padding:36px 0 20px;background:linear-gradient(135deg,#1a3a2a 0%,#2c4e3c 100%);color:#fff;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:12px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/" style="color:rgba(255,255,255,.7);">Home</a></li>
                <li class="breadcrumb-item active" style="color:#fff;">Multi-Family Properties</li>
            </ol>
        </nav>
        <h1 style="font-size:28px;font-weight:700;margin-bottom:12px;color:#fff;line-height:1.3;">Multi-Family Properties for Sale in Metro Vancouver, BC</h1>
        <p style="font-size:15px;color:rgba(255,255,255,.82);max-width:820px;line-height:1.7;margin-bottom:16px;">
            Browse MLS® duplexes, triplexes, and fourplexes for sale across Metro Vancouver. Compare average sold prices,
            days on market, and buyer's vs seller's market conditions by city — updated daily from live MLS® board records.
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:10px;font-size:13px;">
            <a href="/search-listings?type=Duplex" style="background:#e74c3c;color:#fff;border-radius:4px;padding:7px 16px;text-decoration:none;font-weight:700;">Search Duplexes For Sale</a>
            <a href="/search-listings?type=Triplex" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:4px;padding:7px 14px;text-decoration:none;">Triplexes</a>
            <a href="/search-listings?type=Fourplex" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:4px;padding:7px 14px;text-decoration:none;">Fourplexes</a>
        </div>
    </div>
</div>

<div class="container" style="padding-top:28px;padding-bottom:50px;min-height:60vh;">

    {{-- PROPERTY TYPE INTRO CARDS --}}
    <div class="row" style="margin-bottom:26px;">
        @php
        $typeInfo = [
            'Duplex'   => ['icon'=>'🏠','desc'=>'Two-unit residential property. Ideal for owner-occupancy with rental income, or as a pure investment. Most common multi-family entry point.','bg'=>'#e8f5e9'],
            'Triplex'  => ['icon'=>'🏘','desc'=>'Three-unit residential building. Greater rental income than a duplex with all units under one title and one set of property taxes.','bg'=>'#e3f2fd'],
            'Fourplex' => ['icon'=>'🏢','desc'=>'Four-unit residential building. Strong cash-flow potential and often eligible for residential mortgage financing. Maximum density before commercial rules apply.','bg'=>'#fce4ec'],
        ];
        @endphp
        @foreach($typeInfo as $tName => $tData)
        @php $tSlug = strtolower($tName); @endphp
        <div class="col-md-4" style="margin-bottom:14px;">
            <div style="background:{{ $tData['bg'] }};border:1px solid #e2dbd2;border-radius:6px;padding:18px 20px;height:100%;">
                <div style="font-size:20px;margin-bottom:6px;">{{ $tData['icon'] }}</div>
                <div style="font-size:17px;font-weight:700;color:#1a3a2a;margin-bottom:8px;">{{ $tName }}</div>
                <div style="font-size:13px;color:#555;line-height:1.65;margin-bottom:12px;">{{ $tData['desc'] }}</div>
                <a href="/search-listings?type={{ $tName }}" style="font-size:12px;font-weight:700;color:#1a3a2a;text-decoration:none;border-bottom:2px solid #1a3a2a;">Browse {{ $tName }}s For Sale &rsaquo;</a>
            </div>
        </div>
        @endforeach
    </div>

    {{-- CITY COMPARISON TABLE — filtered to active inventory only --}}
    <h2 style="font-size:20px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">Metro Vancouver Multi-Family Market by City</h2>
    <p style="font-size:13px;color:#888;margin-bottom:14px;">Only cities with active multi-family listings or recent sales are shown. Avg price and DOM are 90-day averages from MLS® data.</p>

    <div style="background:#fff;border:1px solid #e2dbd2;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden;margin-bottom:28px;">
        <div class="table-responsive">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#1a3a2a;color:#fff;text-align:left;">
                        <th style="padding:12px 16px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">City</th>
                        <th style="padding:12px 16px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Active</th>
                        <th style="padding:12px 16px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Avg Sold Price</th>
                        <th style="padding:12px 16px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Sold (30d)</th>
                        <th style="padding:12px 16px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Avg DOM</th>
                        <th style="padding:12px 16px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Market</th>
                        <th style="padding:12px 16px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cities as $city)
                    @php
                        $cSlug = App\Helpers\Helper::enslugPlace($city->place);
                        $cond  = $cityStats[$city->place] ?? ['label'=>null,'color'=>'#888','avg_sold_30d'=>0,'sold_30d'=>0,'avg_dom'=>0,'insufficient_data'=>true,'current_active'=>0];
                    @endphp
                    <tr style="border-top:1px solid #f0ede8;" class="mf-city-row">
                        <td style="padding:12px 16px;font-weight:700;color:#2c2c2c;">
                            <a href="/multi-family/{{ $cSlug }}/" style="color:#2c2c2c;text-decoration:none;">{{ $city->label ?? $city->place }}</a>
                        </td>
                        <td style="padding:12px 16px;font-weight:700;color:#1a3a2a;">
                            {{ $cond['current_active'] ?: '—' }}
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
                            {{ $cond['sold_30d'] }}
                            @else
                            —
                            @endif
                        </td>
                        <td style="padding:12px 16px;color:#555;">{{ $cond['avg_dom'] ? $cond['avg_dom'].'d' : '—' }}</td>
                        <td style="padding:12px 16px;">
                            @if($cond['label'] && !$cond['insufficient_data'])
                            <span style="background:{{ $cond['color'] }};color:#fff;border-radius:3px;padding:3px 8px;font-size:11px;font-weight:700;white-space:nowrap;">{{ $cond['label'] }}</span>
                            @else
                            <span style="color:#bbb;font-size:12px;">—</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            <a href="/multi-family/{{ $cSlug }}/" style="color:#2c6fad;font-size:12px;font-weight:600;white-space:nowrap;">View guide &rsaquo;</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- RECENT LISTINGS STRIP --}}
    @if($recentListings && count($recentListings))
    <section style="margin-bottom:30px;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:16px;">Recently Listed Multi-Family Properties</h2>
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
                            <div style="font-size:11px;color:#555;margin-top:1px;font-weight:600;">{{ $lst->type }}</div>
                            <div style="font-size:11px;color:#777;margin-top:1px;">{{ $lst->city }}</div>
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
    </section>
    @endif

    {{-- ABOUT SECTION --}}
    <section style="margin-bottom:30px;padding-top:20px;border-top:1px solid #eee;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:12px;">About Multi-Family Properties in Metro Vancouver</h2>
        <div style="font-size:14px;color:#444;line-height:1.8;max-width:820px;">
            <p>
                Metro Vancouver's multi-family market — covering duplexes, triplexes, and fourplexes — offers investors and
                owner-occupiers a unique opportunity to generate rental income while building equity. Driven in part by BC's
                Small-Scale Multi-Unit Housing (SSMUH) legislation, new multi-family construction is expanding across the region.
            </p>
            <p style="margin-top:10px;">
                Data is sourced directly from the Real Estate Board of Greater Vancouver (REBGV) and Fraser Valley Real Estate Board
                (FVREB) MLS® feeds, updated daily. Absorption rates, average days on market, and average sold prices are calculated
                from actual sales over the last 30 and 90 days for properties listed with type Duplex, Triplex, or Fourplex.
            </p>
        </div>
    </section>

    {{-- CROSS-LINKS --}}
    <div style="padding:16px 0;border-top:1px solid #eee;font-size:13px;color:#666;line-height:2;">
        <strong>City guides:</strong>
        @foreach($cities as $c)
        <a href="/multi-family/{{ App\Helpers\Helper::enslugPlace($c->place) }}/" style="margin:0 8px;color:#2c6fad;">{{ $c->label ?? $c->place }}</a>
        @endforeach
        <br>
        <strong>Related:</strong>
        <a href="/townhouses/" style="margin:0 8px;">Townhouses</a>
        <a href="/houses/" style="margin:0 8px;">Houses</a>
        <a href="/neighbourhood/" style="margin:0 8px;">Neighbourhood Guides</a>
        <a href="/market-stats" style="margin:0 8px;">Market Stats</a>
    </div>
</div>

<div class="container">
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => 'Metro Vancouver',
        'stripHeading'    => 'Get Multi-Family Property Alerts',
        'stripSubtext'    => 'Be first to know when new duplexes, triplexes, and fourplexes hit the market across Metro Vancouver.',
        'stripSearchName' => 'Metro Vancouver Multi-Family Listings',
        'stripSearchData' => json_encode(['listing_status' => 'Active']),
        'stripModalId'    => 'mfHubAlert',
    ])
</div>

@include('frontend.includes.footer_links')
@include('frontend.includes.footer')
@endsection

@push('after-styles')
<style>
.mf-city-row:hover { background:#fafaf8 !important; }
.mf-city-row:hover td:first-child a { color:#2c6fad !important; }
</style>
@endpush

@push('after-scripts')
<script>
window.BCTrack = window.BCTrack || {};
window.BCTrack.pageType     = "buy";
window.BCTrack.propertyType = "multi_family";
</script>
@endpush
