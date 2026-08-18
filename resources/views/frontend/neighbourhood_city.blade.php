@extends('frontend.layouts.default')
@php
$cityName  = $cityRecord->label ?? $city;
$metaTitle = $cityName . ' Real Estate Neighbourhood Guide | Hani & Les | BC Condos And Homes';
$metaDesc  = "Explore all " . $cityName . " neighbourhoods — market conditions, average prices, absorption rates and current listings for every area. Updated daily from MLS® records.";
if ($overallCondition['current_active']) {
    $metaDesc = $cityName . ": " . number_format($overallCondition['current_active']) . " active listings";
    if ($overallCondition['avg_sold_30d']) $metaDesc .= ", avg sold \$" . number_format($overallCondition['avg_sold_30d']);
    if ($overallCondition['label']) $metaDesc .= " — " . $overallCondition['label'];
    $metaDesc .= ". Browse all " . $cityName . " neighbourhoods with live MLS® data.";
}
$canonicalUrl = 'https://www.bccondosandhomes.com/neighbourhood/' . $citySlug . '/';
@endphp
@section('title'){{ $metaTitle }}@endsection
@section('meta_description'){{ $metaDesc }}@endsection
@section('meta')
<link rel="canonical" href="{{ $canonicalUrl }}">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "{{ e($cityName) }} Neighbourhood Real Estate Guides",
  "itemListElement": [
    @foreach($subareas as $i => $sa)
    {"@type":"ListItem","position":{{ $i + 1 }},"name":"{{ e($sa->label ?? $sa->place) }}, {{ e($cityName) }}","url":"https://www.bccondosandhomes.com/neighbourhood/{{ $citySlug }}/{{ App\Helpers\Helper::enslugPlace($sa->place) }}/"}@if(!$loop->last),@endif
    @endforeach
  ]
}
</script>
@endsection

@section('content')
@include('frontend.includes.header')

<div style="margin-top:80px;padding:28px 0 12px;background:#f7f4ef;border-bottom:1px solid #e2dbd2;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:10px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item"><a href="/neighbourhood/">Neighbourhoods</a></li>
                <li class="breadcrumb-item active">{{ $cityName }}</li>
            </ol>
        </nav>
        <h1 style="font-size:26px;font-weight:700;margin-bottom:10px;color:#2c2c2c;">{{ $cityName }} Real Estate Neighbourhood Guide</h1>
        <p style="font-size:15px;color:#555;max-width:820px;line-height:1.65;margin-bottom:6px;">
            @if($overallCondition['label'])
                {{ $cityName }} is currently a <strong>{{ $overallCondition['label'] }}</strong>
                @if($overallCondition['current_active']) with {{ number_format($overallCondition['current_active']) }} active listings @endif
                @if($overallCondition['sold_30d']) and {{ number_format($overallCondition['sold_30d']) }} properties sold in the last 30 days @endif.
                Select a neighbourhood below for detailed stats, top buildings, and current listings.
            @else
                Select a neighbourhood below to view detailed market stats, top buildings, and current listings for {{ $cityName }}.
            @endif
        </p>
        <div style="margin-top:10px;font-size:13px;">
            <a href="/market-stats/{{ $citySlug }}" style="margin-right:14px;">{{ $cityName }} Market Stats</a>
            <a href="/search-listings/{{ $citySlug }}" style="margin-right:14px;">All {{ $cityName }} Listings</a>
            <a href="/market-report/{{ $citySlug }}" style="margin-right:14px;">Market Reports</a>
            <a href="/top-realtor/{{ $citySlug }}/" style="margin-right:14px;color:#c0392b;font-weight:700;">Top Realtor in {{ $cityName }} &rsaquo;</a>
        </div>
    </div>
</div>

<div class="container" style="padding-top:24px;padding-bottom:50px;min-height:60vh;">

    @if($overallCondition['label'])
    <div class="row" style="margin-bottom:22px;">
        <div class="col-md-12">
            <div style="background:#fff;border-left:5px solid {{ $overallCondition['color'] }};border-radius:5px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:16px 20px;display:flex;flex-wrap:wrap;gap:24px;align-items:center;">
                <div>
                    <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.7px;margin-bottom:4px;">{{ $cityName }} Overall</div>
                    <div style="font-size:18px;font-weight:700;color:{{ $overallCondition['color'] }};">{{ $overallCondition['label'] }}</div>
                </div>
                @if($overallCondition['current_active'])
                <div style="text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#333;">{{ number_format($overallCondition['current_active']) }}</div>
                    <div style="font-size:11px;color:#888;">Active Listings</div>
                </div>
                @endif
                @if($overallCondition['sold_30d'])
                <div style="text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#333;">{{ number_format($overallCondition['sold_30d']) }}</div>
                    <div style="font-size:11px;color:#888;">Sold (30d)</div>
                </div>
                @endif
                @if($overallCondition['avg_sold_30d'])
                <div style="text-align:center;">
                    <div style="font-size:18px;font-weight:700;color:#333;">${{ number_format($overallCondition['avg_sold_30d']) }}</div>
                    <div style="font-size:11px;color:#888;">Avg Price (30d)</div>
                </div>
                @endif
                @if($overallCondition['absorption_rate'])
                <div style="text-align:center;">
                    <div style="font-size:22px;font-weight:700;color:#333;">{{ $overallCondition['absorption_rate'] }}%</div>
                    <div style="font-size:11px;color:#888;">Absorption Rate</div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if($citySlug === 'surrey')
    <div style="background:#f7f4ef;border:1px solid #e2dbd2;border-radius:6px;padding:18px 22px;margin-bottom:22px;font-size:14px;color:#444;line-height:1.75;">
        Surrey is Metro Vancouver's fastest-growing city and one of the most active markets for Surrey homes for sale in the region. From the family-friendly communities of Fleetwood and Cloverdale to the high-rise developments of Whalley and the luxury estates of South Surrey, the city offers a diverse range of properties at a wide spectrum of price points. With strong transit investment and continued population growth, Surrey real estate remains a compelling option for buyers across Metro Vancouver.
    </div>
    @endif

    <h2 style="font-size:18px;font-weight:700;color:#333;margin-bottom:16px;">{{ $cityName }} Neighbourhoods</h2>

    <div class="row">
        @foreach($subareas as $sa)
        @php
            $saSlug = App\Helpers\Helper::enslugPlace($sa->place);
            $cond   = $subareaStats[$sa->place] ?? ['label' => null, 'color' => '#888', 'absorption_rate' => 0, 'current_active' => 0, 'avg_sold_30d' => 0];
        @endphp
        <div class="col-md-4 col-sm-6" style="margin-bottom:18px;">
            <a href="/neighbourhood/{{ $citySlug }}/{{ $saSlug }}/" style="text-decoration:none;color:inherit;">
                <div class="nhub-sa-card" style="border:1px solid #e2dbd2;border-radius:6px;padding:16px 18px;background:#fff;box-shadow:0 2px 6px rgba(0,0,0,.06);height:100%;transition:box-shadow .15s;">
                    <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:8px;">
                        <div style="flex:1;font-size:15px;font-weight:700;color:#2c2c2c;">{{ $sa->label ?? $sa->place }}</div>
                        @if($cond['label'])
                        <span style="font-size:10px;font-weight:700;color:#fff;background:{{ $cond['color'] }};border-radius:3px;padding:2px 6px;white-space:nowrap;flex-shrink:0;">{{ $cond['label'] }}</span>
                        @endif
                    </div>
                    <div style="font-size:12px;color:#777;line-height:1.9;">
                        @if($cond['current_active'])
                        <span>{{ number_format($cond['current_active']) }} active</span>
                        @endif
                        @if($cond['sold_30d'])
                        <span style="margin-left:8px;">{{ number_format($cond['sold_30d']) }} sold (30d)</span>
                        @endif
                        @if($cond['avg_sold_30d'])
                        <div style="margin-top:2px;">Avg: <strong>${{ number_format($cond['avg_sold_30d']) }}</strong></div>
                        @endif
                        @if($cond['absorption_rate'])
                        <div>Absorption: {{ $cond['absorption_rate'] }}%</div>
                        @endif
                    </div>
                    <div style="margin-top:10px;font-size:12px;color:#2c6fad;font-weight:600;">View guide &rsaquo;</div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    @if(empty($subareas) || count($subareas) === 0)
    <div style="padding:40px 0;text-align:center;color:#aaa;font-size:14px;">No tracked neighbourhoods found for {{ $cityName }}.</div>
    @endif

    {{-- Browse by price section --}}
    @php
    $_nbpTypes = [
        'apartment'  => 'Condos',
        'townhouse'  => 'Townhouses',
        'house'      => 'Houses',
    ];
    $_nbpRanges = [
        'under-500k'  => 'Under $500K',
        'under-800k'  => 'Under $800K',
        'under-1m'    => 'Under $1M',
        '1m-to-2m'   => '$1M – $2M',
        'over-2m'    => 'Over $2M',
    ];
    @endphp
    <div style="margin-top:30px;background:#fafaf8;border:1px solid #e2dbd2;border-radius:6px;padding:22px 26px;margin-bottom:24px;">
        <h2 style="font-size:16px;font-weight:700;color:#333;margin-bottom:16px;">Browse {{ $cityName }} Listings by Price</h2>
        @foreach($_nbpTypes as $_nbpTypeSlug => $_nbpTypeLabel)
        <div style="margin-bottom:14px;">
            <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">{{ $_nbpTypeLabel }}</div>
            <div>
                @foreach($_nbpRanges as $_nbpPriceSlug => $_nbpPriceLabel)
                <a href="{{ route('adv_search_listings_city_type_feature', ['city' => $citySlug, 'type' => $_nbpTypeSlug, 'feature' => $_nbpPriceSlug]) }}"
                   style="display:inline-block;margin:3px 4px;padding:5px 14px;border:1px solid #d1d5db;border-radius:20px;font-size:12px;color:#374151;text-decoration:none;white-space:nowrap;background:#fff;">
                    {{ $_nbpTypeLabel }} {{ $_nbpPriceLabel }}
                </a>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <div style="margin-top:30px;padding:18px 0;border-top:1px solid #eee;font-size:13px;color:#666;line-height:1.8;">
        <strong>Related:</strong>
        <a href="/houses/{{ $citySlug }}/" style="margin:0 10px;">{{ $cityName }} House Market</a>
        <a href="/market-stats/{{ $citySlug }}" style="margin:0 10px;">{{ $cityName }} Market Stats</a>
        <a href="/market-stats/{{ $citySlug }}/condos" style="margin:0 10px;">{{ $cityName }} Condo Stats</a>
        <a href="/market-stats/{{ $citySlug }}/houses" style="margin:0 10px;">{{ $cityName }} House Stats</a>
        <a href="/market-report/{{ $citySlug }}" style="margin:0 10px;">{{ $cityName }} Reports</a>
        <a href="/search-listings/{{ $citySlug }}" style="margin:0 10px;">{{ $cityName }} Listings</a>
        <a href="/neighbourhood/" style="margin:0 10px;">All Neighbourhoods</a>
    </div>
</div>

<div class="container">
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => $cityName,
        'stripSearchName' => $cityName . ' Listings',
        'stripSearchData' => json_encode(array_filter(['cities' => $cityName, 'listing_status' => 'Active'])),
        'stripCity'       => $cityName,
        'stripModalId'    => 'ncityAlert_' . $citySlug,
    ])
</div>
@include('frontend.includes.hani_bubble')
@include('frontend.includes.footer_links')
@include('frontend.includes.footer')

@php
$_ncOverallLabel = $overallCondition['label'] ?? '';
$_ncMktType = 'balanced';
if ($_ncOverallLabel === "Strong Seller's Market")   $_ncMktType = 'strong-sellers';
elseif ($_ncOverallLabel === "Seller's Market")       $_ncMktType = 'sellers';
elseif ($_ncOverallLabel === "Buyer's Market")        $_ncMktType = 'buyers';
elseif ($_ncOverallLabel === "Balanced Market")       $_ncMktType = 'balanced';
$_ncAvgPrice   = ($overallCondition['avg_sold_30d'] ?? 0) > 0 ? '$'.number_format($overallCondition['avg_sold_30d']) : '';
$_ncAvgDom     = ($overallCondition['avg_dom'] ?? 0) > 0 ? $overallCondition['avg_dom'].'d' : '';
$_ncAbsorption = ($overallCondition['absorption_rate'] ?? 0) > 0 ? $overallCondition['absorption_rate'].'%' : '';
$_ncActive     = (int)($overallCondition['current_active'] ?? 0);
$_ncSold       = (int)($overallCondition['sold_30d'] ?? 0);
$_ncBuyers     = (int)(round(max(50, $_ncActive * 15 + $_ncSold * 30) / 10) * 10);
@endphp
@if($_ncActive)
<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
    data-city="{{ $cityName }}"
    data-market-type="{{ $_ncMktType }}"
    data-avg-price="{{ $_ncAvgPrice }}"
    data-avg-dom="{{ $_ncAvgDom }}"
    data-active-listings="{{ $_ncActive }}"
    data-absorption-rate="{{ $_ncAbsorption }}"
    data-sold-30d="{{ $_ncSold }}"
    data-buyers="{{ $_ncBuyers }}"
></script>
@else
<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
></script>
@endif

@endsection

@push('after-styles')
<style>
.nhub-sa-card:hover { box-shadow: 0 4px 14px rgba(0,0,0,.12) !important; }
</style>
@endpush
