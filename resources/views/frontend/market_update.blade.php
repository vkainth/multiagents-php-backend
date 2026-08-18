@extends('frontend.layouts.default_mobile')
@php
$_avgPrice    = (int)($report->avg_sold_price ?? 0);
$_maxPrice    = (int)($report->max_sold_price ?? 0);
$_minPrice    = (int)($report->min_sold_price ?? 0);
$_condLabel   = $condition['label']  ?? '';
$_condColor   = $condition['color']  ?? '#888';
$_condAbsorb  = $condition['absorption'] ?? 0;

$_prevCountSold = $prevReport ? (int)($prevReport->count_sold     ?? 0) : 0;
$_prevAvgPrice  = $prevReport ? (int)($prevReport->avg_sold_price ?? 0) : 0;
$_prevAvgDom    = $prevReport ? (int)($prevReport->avg_dom        ?? 0) : 0;
$_yoyCountSold  = $yoyReport  ? (int)($yoyReport->count_sold      ?? 0) : 0;
$_yoyAvgPrice   = $yoyReport  ? (int)($yoyReport->avg_sold_price  ?? 0) : 0;

$_pctSoldMom = ($_prevCountSold > 0)
    ? round(($countSold - $_prevCountSold) / $_prevCountSold * 100, 1)
    : null;
$_pctPriceMom = ($_prevAvgPrice > 0 && $_avgPrice > 0)
    ? round(($_avgPrice - $_prevAvgPrice) / $_prevAvgPrice * 100, 1)
    : null;
$_pctSoldYoy = ($_yoyCountSold > 0)
    ? round(($countSold - $_yoyCountSold) / $_yoyCountSold * 100, 1)
    : null;
$_pctPriceYoy = ($_yoyAvgPrice > 0 && $_avgPrice > 0)
    ? round(($_avgPrice - $_yoyAvgPrice) / $_yoyAvgPrice * 100, 1)
    : null;

function _miArrow($v): string {
    if ($v === null) return '';
    return $v > 0 ? '<span style="color:#27ae60">▲ ' . abs($v) . '%</span>'
                  : ($v < 0 ? '<span style="color:#e74c3c">▼ ' . abs($v) . '%</span>'
                             : '<span style="color:#888">→ 0%</span>');
}
@endphp
@section('title'){{ $metaTitle }}@endsection
@section('meta_description'){{ $metaDesc }}@endsection
@section('meta')
<link rel="canonical" href="{{ $canonical }}">
@if($prevSlug)<link rel="prev" href="https://www.bccondosandhomes.com/market-update/{{ $citySlug }}/{{ $prevSlug }}">@endif
@if($nextSlug)<link rel="next" href="https://www.bccondosandhomes.com/market-update/{{ $citySlug }}/{{ $nextSlug }}">@endif
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="article">
<meta property="article:published_time" content="{{ sprintf('%04d-%02d-01', $year, $month) }}">
<meta property="article:author" content="Hani Faraj">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": {!! json_encode($metaTitle, JSON_HEX_TAG) !!},
  "description": {!! json_encode($metaDesc, JSON_HEX_TAG) !!},
  "url": {!! json_encode($canonical, JSON_UNESCAPED_SLASHES|JSON_HEX_TAG) !!},
  "datePublished": {{ json_encode(sprintf('%04d-%02d-01', $year, $month)) }},
  "dateModified": {{ json_encode(sprintf('%04d-%02d-01', $year, $month)) }},
  "author": {"@type":"Person","name":"Hani Faraj","url":"https://www.bccondosandhomes.com"},
  "publisher": {
    "@type": "Organization",
    "name": "BC Condos And Homes",
    "url": "https://www.bccondosandhomes.com",
    "logo": {"@type":"ImageObject","url":"https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.jpg"}
  }
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type":"ListItem","position":1,"name":"Home","item":"https://www.bccondosandhomes.com/"},
    {"@type":"ListItem","position":2,"name":"{{ $city }} Market Updates","item":"https://www.bccondosandhomes.com/market-update/{{ $citySlug }}"},
    {"@type":"ListItem","position":3,"name":"{{ $monthLabel }}","item":"{{ $canonical }}"}
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
                <li class="breadcrumb-item"><a href="/market-update/{{ $citySlug }}">{{ $city }} Updates</a></li>
                <li class="breadcrumb-item active">{{ $monthLabel }}</li>
            </ol>
        </nav>

        <h1 style="font-size:24px;font-weight:700;margin-bottom:6px;color:#2c2c2c;">
            {{ $city }} Real Estate Market Update – {{ $monthLabel }}
        </h1>

        @if($_condLabel)
        <div style="margin-bottom:10px;">
            <span style="display:inline-block;font-size:13px;font-weight:700;color:#fff;background:{{ $_condColor }};border-radius:4px;padding:5px 12px;">
                {{ $_condLabel }}
            </span>
            @if($_condAbsorb > 0)
            <span style="font-size:12px;color:#888;margin-left:10px;">Absorption rate: {{ $_condAbsorb }}%</span>
            @endif
        </div>
        @endif

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;margin-bottom:4px;">
            @if($prevSlug)
            <a href="/market-update/{{ $citySlug }}/{{ $prevSlug }}" style="font-size:12px;color:#2c6fad;text-decoration:none;">← {{ $prevLabel }}</a>
            @endif
            @if($nextSlug)
            <a href="/market-update/{{ $citySlug }}/{{ $nextSlug }}" style="font-size:12px;color:#2c6fad;text-decoration:none;margin-left:auto;">Next month →</a>
            @endif
        </div>
    </div>
</div>

<div class="container" style="padding-bottom:40px;">

    {{-- Key stats tiles --}}
    <div class="row" style="margin-top:22px;">
        <div class="col-sm-3 col-xs-6" style="margin-bottom:14px;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:#2c6fad;">{{ number_format($countSold) }}</div>
                <div style="font-size:12px;color:#888;margin-top:4px;">Units Sold</div>
                @if($_pctSoldMom !== null)<div style="font-size:11px;margin-top:2px;">{!! _miArrow($_pctSoldMom) !!} <span style="color:#aaa">vs prev month</span></div>@endif
            </div>
        </div>
        <div class="col-sm-3 col-xs-6" style="margin-bottom:14px;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px;text-align:center;">
                <div style="font-size:22px;font-weight:700;color:#333;">{{ $_avgPrice ? '$'.number_format($_avgPrice) : '—' }}</div>
                <div style="font-size:12px;color:#888;margin-top:4px;">Avg Sold Price</div>
                @if($_pctPriceMom !== null)<div style="font-size:11px;margin-top:2px;">{!! _miArrow($_pctPriceMom) !!} <span style="color:#aaa">vs prev month</span></div>@endif
            </div>
        </div>
        <div class="col-sm-3 col-xs-6" style="margin-bottom:14px;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:#333;">{{ $avgDom ? $avgDom.'d' : '—' }}</div>
                <div style="font-size:12px;color:#888;margin-top:4px;">Avg Days on Market</div>
            </div>
        </div>
        <div class="col-sm-3 col-xs-6" style="margin-bottom:14px;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px;text-align:center;">
                @if($_condLabel)
                <div style="font-size:13px;font-weight:700;color:#fff;background:{{ $_condColor }};border-radius:4px;padding:5px 8px;display:inline-block;line-height:1.3;">{{ $_condLabel }}</div>
                @else
                <div style="font-size:28px;font-weight:700;color:#888;">—</div>
                @endif
                <div style="font-size:12px;color:#888;margin-top:6px;">Market Condition</div>
            </div>
        </div>
    </div>

    {{-- Secondary stats strip --}}
    @if($activeStart || $countListed || $_maxPrice || $_minPrice)
    <div class="row" style="margin-top:6px;">
        <div class="col-md-12">
            <div style="background:#f7f4ef;border-radius:6px;padding:12px 18px;font-size:13px;color:#555;display:flex;flex-wrap:wrap;gap:18px;">
                @if($activeStart)<span><strong>{{ number_format($activeStart) }}</strong> active listings at start of month</span>@endif
                @if($countListed)<span><strong>{{ number_format($countListed) }}</strong> new listings</span>@endif
                @if($_condAbsorb > 0)<span>Absorption: <strong>{{ $_condAbsorb }}%</strong></span>@endif
                @if($_maxPrice)<span>Highest: <strong>${{ number_format($_maxPrice) }}</strong></span>@endif
                @if($_minPrice)<span>Lowest: <strong>${{ number_format($_minPrice) }}</strong></span>@endif
            </div>
        </div>
    </div>
    @endif

    {{-- Month-over-month / Year-over-year comparison --}}
    @if($prevReport || $yoyReport)
    <div class="row" style="margin-top:22px;">
        <div class="col-md-12">
            <h2 style="font-size:16px;font-weight:700;color:#333;margin-bottom:12px;">How Does {{ $monthLabel }} Compare?</h2>
            <div style="overflow-x:auto;">
                <table style="width:100%;font-size:13px;border-collapse:collapse;background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);">
                    <thead>
                        <tr style="background:#f7f4ef;border-bottom:2px solid #eee;">
                            <th style="padding:9px 14px;text-align:left;font-weight:600;color:#555;">Period</th>
                            <th style="padding:9px 14px;text-align:right;font-weight:600;color:#555;">Units Sold</th>
                            <th style="padding:9px 14px;text-align:right;font-weight:600;color:#555;">Avg Price</th>
                            <th style="padding:9px 14px;text-align:right;font-weight:600;color:#555;">Avg DOM</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom:1px solid #f0f0f0;background:#fffdf8;">
                            <td style="padding:9px 14px;font-weight:600;color:#2c6fad;">{{ $monthLabel }}</td>
                            <td style="padding:9px 14px;text-align:right;">{{ number_format($countSold) }}</td>
                            <td style="padding:9px 14px;text-align:right;">{{ $_avgPrice ? '$'.number_format($_avgPrice) : '—' }}</td>
                            <td style="padding:9px 14px;text-align:right;">{{ $avgDom ?: '—' }}</td>
                        </tr>
                        @if($prevReport && $_prevCountSold)
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:9px 14px;color:#666;">{{ $prevLabel }}</td>
                            <td style="padding:9px 14px;text-align:right;">{{ number_format($_prevCountSold) }}
                                @if($_pctSoldMom !== null)<br><small>{!! _miArrow($_pctSoldMom) !!}</small>@endif</td>
                            <td style="padding:9px 14px;text-align:right;">{{ $_prevAvgPrice ? '$'.number_format($_prevAvgPrice) : '—' }}
                                @if($_pctPriceMom !== null)<br><small>{!! _miArrow($_pctPriceMom) !!}</small>@endif</td>
                            <td style="padding:9px 14px;text-align:right;">{{ $_prevAvgDom ?: '—' }}</td>
                        </tr>
                        @endif
                        @if($yoyReport && $_yoyCountSold)
                        <tr>
                            <td style="padding:9px 14px;color:#666;">{{ date('F Y', mktime(0,0,0,$month,1,$year-1)) }} <small style="color:#aaa;">(YoY)</small></td>
                            <td style="padding:9px 14px;text-align:right;">{{ number_format($_yoyCountSold) }}
                                @if($_pctSoldYoy !== null)<br><small>{!! _miArrow($_pctSoldYoy) !!}</small>@endif</td>
                            <td style="padding:9px 14px;text-align:right;">{{ $_yoyAvgPrice ? '$'.number_format($_yoyAvgPrice) : '—' }}
                                @if($_pctPriceYoy !== null)<br><small>{!! _miArrow($_pctPriceYoy) !!}</small>@endif</td>
                            <td style="padding:9px 14px;text-align:right;">—</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Quick links: other market intel pages --}}
    <div class="row" style="margin-top:22px;">
        <div class="col-md-12">
            <div style="background:#f7f4ef;border-radius:6px;padding:14px 18px;font-size:13px;color:#555;">
                <strong>More {{ $city }} market intel:</strong>
                <a href="/new-listings/{{ $citySlug }}" style="margin-left:12px;color:#2c6fad;">New Listings This Week →</a>
                <a href="/price-reductions/{{ $citySlug }}" style="margin-left:12px;color:#2c6fad;">Price Reductions →</a>
                <a href="/sold-over-asking/{{ $citySlug }}" style="margin-left:12px;color:#2c6fad;">Sold Over Asking →</a>
                <a href="/market-update/{{ $citySlug }}" style="margin-left:12px;color:#555;">All Updates →</a>
            </div>
        </div>
    </div>

    {{-- Email alert signup --}}
    <div class="row" style="margin-top:28px;">
        <div class="col-md-8 col-md-offset-2">
            @include('frontend.includes.market_intel_alert_widget', ['city' => $city, 'citySlug' => $citySlug, 'source' => 'market_update'])
        </div>
    </div>

</div>

<div class="listings-disclaimer">
    <div class="container">
        <p>Last Update: {{ \Carbon\Carbon::now()->format('m/d/Y') }} &nbsp;&nbsp;<strong>Disclaimer:</strong> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy.</p>
    </div>
</div>

@include('frontend.includes.hani_bubble')
@include('frontend.includes.footer_links')
@include('frontend.includes.footer')
@endsection
