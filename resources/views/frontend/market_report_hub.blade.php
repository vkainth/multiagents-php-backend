@extends('frontend.layouts.default_mobile')
@php
$canonicalBase = $canonicalBase ?? 'https://www.bccondosandhomes.com';
$isMain = ($mode === 'main');
$isCity = ($mode === 'city');
$placeLabel = $city ?: 'BC';
$metaTitle  = $isMain
    ? "BC Real Estate Market Reports – {$monthLabel} | Hani & Les"
    : "{$city} Real Estate Market Reports – {$monthLabel} | Hani & Les";
$metaDesc = $isMain
    ? "Monthly real estate market reports for Metro Vancouver and Fraser Valley — tracking sold prices, days on market, and market conditions by city."
    : "Monthly real estate market reports for {$city}, BC — browse subareas, property types, and historical monthly data.";
$canonical = $isMain
    ? $canonicalBase . '/market-report'
    : $canonicalBase . '/market-report/' . $citySlug;
@endphp
@php
// Build hub breadcrumb items
$_hubBcItems = [
    ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>$canonicalBase.'/'],
    ['@type'=>'ListItem','position'=>2,'name'=>'Market Reports','item'=>$canonicalBase.'/market-report'],
];
if ($isCity) {
    $_hubBcItems[] = ['@type'=>'ListItem','position'=>3,'name'=>$city,'item'=>$canonical];
}
@endphp
@section('title'){{ $metaTitle }}@endsection
@section('meta_description'){{ $metaDesc }}@endsection
@section('meta')
<link rel="canonical" href="{{ $canonical }}">
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => $_hubBcItems,
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'WebPage',
    'name'        => $metaTitle,
    'description' => $metaDesc,
    'url'         => $canonical,
    'breadcrumb'  => ['@type'=>'BreadcrumbList','itemListElement'=>$_hubBcItems],
    'publisher'   => ['@type'=>'Organization','name'=>'Hani & Les','url'=>$canonicalBase],
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG) !!}
</script>
@endsection
@section('content')
@include('frontend.includes.header')

<div style="margin-top:80px;padding:28px 0 0;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:10px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item @if($isMain)active @endif">
                    @if($isCity)<a href="/market-report">Market Reports</a>@else Market Reports @endif
                </li>
                @if($isCity)<li class="breadcrumb-item active">{{ $city }}</li>@endif
            </ol>
        </nav>

        @if($isMain)
        <h1 style="font-size:24px;font-weight:700;margin-bottom:8px;color:#2c2c2c;">BC Real Estate Market Reports</h1>
        <p style="font-size:15px;color:#555;max-width:800px;margin-bottom:4px;line-height:1.65;">
            Every month we publish market reports for the major Metro Vancouver and Fraser Valley real estate markets — tracking sold prices, days on market, and market conditions by city, neighbourhood, and property type. Data is compiled from MLS® sold records.
        </p>
        @else
        <h1 style="font-size:24px;font-weight:700;margin-bottom:8px;color:#2c2c2c;">{{ $city }} Real Estate Market Reports</h1>
        <p style="font-size:15px;color:#555;max-width:800px;margin-bottom:4px;line-height:1.65;">
            Monthly market reports for <strong>{{ $city }}</strong> — browse by neighbourhood or property type. Each report shows sold prices, days on market, and market conditions for that month.
        </p>
        @endif
    </div>
</div>

<div class="container" style="padding-bottom:40px;">

    {{-- Current month snapshot --}}
    @if(count($snapshot) > 0)
    <div class="row" style="margin-top:22px;">
        <div class="col-md-12">
            <h2 style="font-size:18px;font-weight:700;color:#333;margin-bottom:14px;">
                {{ $monthLabel }} Snapshot — @if($isMain)All Cities @else {{ $city }} Neighbourhoods @endif
            </h2>
            <div style="overflow-x:auto;">
                <table style="width:100%;font-size:13px;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f7f4ef;border-bottom:2px solid #eee;">
                            <th style="padding:9px 14px;text-align:left;font-weight:600;color:#555;">{{ $isMain ? 'City' : 'Neighbourhood' }}</th>
                            <th style="padding:9px 14px;text-align:right;font-weight:600;color:#555;">Units Sold</th>
                            <th style="padding:9px 14px;text-align:right;font-weight:600;color:#555;">Avg Sold Price</th>
                            <th style="padding:9px 14px;text-align:right;font-weight:600;color:#555;">Avg DOM</th>
                            <th style="padding:9px 14px;text-align:center;font-weight:600;color:#555;">Condition</th>
                            <th style="padding:9px 14px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($snapshot as $row)
                        @php
                            $aName   = $isMain ? ($row->city_name ?? '') : ($row->area_name ?? '');
                            $aSlug   = App\Helpers\Helper::enslugPlace($aName);
                            $cntSold  = (int)($row->count_sold      ?? 0);
                            $cntAct   = (int)($row->active_at_start ?? 0);
                            $cntList  = (int)($row->count_listed    ?? 0);
                            $divisor  = $cntAct > 0 ? $cntAct : $cntList;
                            $adom     = (int)($row->avg_dom          ?? 0);
                            $aRate    = ($divisor > 0) ? round($cntSold / $divisor * 100, 1) : 0;
                            if ($aRate > 20 && $adom && $adom < 30) {
                                $aLabel = "Strong Seller's Market"; $aColor = '#c0392b';
                            } elseif ($aRate >= 15 || ($adom && $adom <= 45)) {
                                $aLabel = "Seller's Market";        $aColor = '#e67e22';
                            } elseif ($aRate >= 12 && $adom && $adom <= 60) {
                                $aLabel = "Balanced Market";        $aColor = '#f39c12';
                            } elseif ($aRate > 0) {
                                $aLabel = "Buyer's Market";         $aColor = '#2980b9';
                            } else { $aLabel = ''; $aColor = '#aaa'; }
                        @endphp
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:9px 14px;font-weight:500;">
                                @if($isMain)
                                <a href="/market-report/{{ $aSlug }}" style="color:#2c6fad;">{{ $aName }}</a>
                                @else
                                <a href="/market-report/{{ $citySlug }}/{{ $aSlug }}" style="color:#2c6fad;">{{ $aName }}</a>
                                @endif
                            </td>
                            <td style="padding:9px 14px;text-align:right;">{{ number_format($row->count_sold) }}</td>
                            <td style="padding:9px 14px;text-align:right;">@if($row->avg_sold_price)${{ number_format($row->avg_sold_price) }}@else—@endif</td>
                            <td style="padding:9px 14px;text-align:right;">@if($adom){{ $adom }}d @else—@endif</td>
                            <td style="padding:9px 14px;text-align:center;">
                                @if($aLabel)
                                <span style="font-size:11px;font-weight:700;color:#fff;background:{{ $aColor }};border-radius:3px;padding:2px 7px;white-space:nowrap;">{{ $aLabel }}</span>
                                @else—@endif
                            </td>
                            <td style="padding:9px 14px;text-align:center;">
                                @if($isMain)
                                <a href="/market-report/{{ $aSlug }}" style="font-size:12px;color:#2c6fad;">Reports →</a>
                                @else
                                <a href="/market-report/{{ $citySlug }}/{{ $aSlug }}" style="font-size:12px;color:#2c6fad;">Reports →</a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- City grid or type links --}}
    @if($isCity)
    <div class="row" style="margin-top:28px;">
        <div class="col-md-12">
            <h2 style="font-size:18px;font-weight:700;color:#333;margin-bottom:14px;">Browse {{ $city }} Reports by Type</h2>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="/market-report/{{ $citySlug }}/condos" style="display:inline-block;padding:10px 20px;background:#fff;border:1px solid #ddd;border-radius:6px;font-size:14px;font-weight:600;color:#333;text-decoration:none;">Condos</a>
                <a href="/market-report/{{ $citySlug }}/houses" style="display:inline-block;padding:10px 20px;background:#fff;border:1px solid #ddd;border-radius:6px;font-size:14px;font-weight:600;color:#333;text-decoration:none;">Houses</a>
                <a href="/market-report/{{ $citySlug }}/townhouses" style="display:inline-block;padding:10px 20px;background:#fff;border:1px solid #ddd;border-radius:6px;font-size:14px;font-weight:600;color:#333;text-decoration:none;">Townhouses</a>
            </div>
        </div>
    </div>
    @if(isset($subareas) && count($subareas) > 0)
    <div class="row" style="margin-top:22px;">
        <div class="col-md-12">
            <h2 style="font-size:18px;font-weight:700;color:#333;margin-bottom:14px;">{{ $city }} Neighbourhood Reports</h2>
            <div class="row">
                @foreach($subareas as $sa)
                <div class="col-sm-4 col-xs-6" style="margin-bottom:10px;">
                    <a href="/market-report/{{ $citySlug }}/{{ App\Helpers\Helper::enslugPlace($sa->place) }}" style="display:block;padding:10px 14px;background:#fff;border:1px solid #eee;border-radius:5px;font-size:13px;color:#333;text-decoration:none;">
                        {{ $sa->label ?? $sa->place }}
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    @endif

    @if($isMain && isset($cities) && count($cities) > 0)
    <div class="row" style="margin-top:28px;">
        <div class="col-md-12">
            <h2 style="font-size:18px;font-weight:700;color:#333;margin-bottom:14px;">Browse Reports by City</h2>
            <div class="row">
                @foreach($cities as $c)
                <div class="col-sm-4 col-md-3 col-xs-6" style="margin-bottom:10px;">
                    <a href="/market-report/{{ App\Helpers\Helper::enslugPlace($c->place) }}" style="display:block;padding:10px 14px;background:#fff;border:1px solid #eee;border-radius:5px;font-size:13px;color:#333;text-decoration:none;">
                        {{ $c->label ?? $c->place }}
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Related links --}}
    <div class="row" style="margin-top:22px;">
        <div class="col-md-12">
            <div style="background:#f7f4ef;border-radius:6px;padding:14px 18px;font-size:13px;color:#555;">
                <strong>Related:</strong>
                @if($isCity)
                <a href="/market-stats/{{ $citySlug }}" style="margin-left:12px;color:#2c6fad;">{{ $city }} Live Market Stats →</a>
                <a href="/market-report" style="margin-left:12px;color:#2c6fad;">All BC Market Reports →</a>
                @else
                <a href="/market-stats" style="margin-left:12px;color:#2c6fad;">Live BC Market Stats →</a>
                <a href="/sitemap-stats.xml" style="margin-left:12px;color:#2c6fad;">Stats Sitemap →</a>
                @endif
            </div>
        </div>
    </div>

</div>

<div class="container" style="padding-bottom:10px;">
    @include('frontend.includes.hani_attribution', ['attrCity' => ($isCity ? $city : null), 'attrSubarea' => null])
</div>

<div class="listings-disclaimer">
    <div class="container">
        <p>Last Update: {{ \Carbon\Carbon::now()->format('m/d/Y') }} &nbsp;&nbsp;<strong>Disclaimer:</strong> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy.</p>
    </div>
</div>

<div class="container" style="padding-bottom:16px;">
    @php
        $_mrhCtx  = $city ? ($citySlug ? trim($city) : 'BC') : 'Metro Vancouver';
        $_mrhData = json_encode(array_filter(['cities' => $city ?: null, 'listing_status' => 'Active']));
    @endphp
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => $_mrhCtx,
        'stripHeading'    => 'Get New Listing Alerts & Monthly Market Updates for ' . $_mrhCtx,
        'stripSubtext'    => 'Stay informed — get notified of new listings and receive monthly market reports for ' . $_mrhCtx . '.',
        'stripSearchName' => $_mrhCtx . ' Active Listings',
        'stripSearchData' => $_mrhData,
        'stripCity'       => $city ?: '',
        'stripModalId'    => 'mrhAlert_' . ($citySlug ?? 'hub'),
    ])
</div>
@include('frontend.includes.hani_bubble')
@include('frontend.includes.footer_links')
@include('frontend.includes.footer')

@if($isCity && $citySlug)
@php
$_mrhActive = 0;
$_mrhLabel  = '';
if (count($snapshot) > 0) {
    $row0 = $snapshot[0];
    $cnt0s = (int)($row0->count_sold      ?? 0);
    $cnt0a = (int)($row0->active_at_start ?? 0);
    $cnt0l = (int)($row0->count_listed    ?? 0);
    $div0  = $cnt0a > 0 ? $cnt0a : $cnt0l;
    $ar0   = ($div0 > 0) ? round($cnt0s / $div0 * 100, 1) : 0;
    $dom0  = (int)($row0->avg_dom ?? 0);
    $_mrhActive = $cnt0a;
    if ($ar0 > 20 && $dom0 && $dom0 < 30)      $_mrhLabel = 'strong-sellers';
    elseif ($ar0 >= 15 || ($dom0 && $dom0 <= 45)) $_mrhLabel = 'sellers';
    elseif ($ar0 >= 12 && $dom0 && $dom0 <= 60)  $_mrhLabel = 'balanced';
    elseif ($ar0 > 0)                            $_mrhLabel = 'buyers';
}
@endphp
<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
    data-city="{{ $city }}"
    data-market-type="{{ $_mrhLabel ?: 'balanced' }}"
    data-active-listings="{{ $_mrhActive }}"
></script>
@else
<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
></script>
@endif

@endsection

@push('after-scripts')
<script>
window.BCTrack = window.BCTrack || {};
window.BCTrack.pageType    = "market_report";
window.BCTrack.reportMonth = "{{ date('Y-m') }}";
</script>
@endpush
