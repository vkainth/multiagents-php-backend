@extends('frontend.layouts.default_mobile')
@php
$canonicalBase = $canonicalBase ?? 'https://www.bccondosandhomes.com';
$placeLabel = $subarea ? "{$subarea}, {$city}" : ($city ?: 'BC');
$typePart   = ($typeLabel && $typeLabel !== 'Real Estate') ? " {$typeLabel}" : '';
$metaTitle  = "{$placeLabel}{$typePart} Market Report – {$monthLabel} | Hani & Les";
$_rawDesc   = "The {$monthLabel}{$typePart} real estate market report for {$placeLabel}. "
    . ($report && $report->count_sold ? number_format($report->count_sold) . ' units sold' : 'Sold activity')
    . ($report && $report->avg_sold_price ? ', avg price $' . number_format($report->avg_sold_price) : '')
    . ($report && $report->avg_dom ? ', avg ' . $report->avg_dom . ' days on market.' : '.');
if (strlen($_rawDesc) > 160) {
    $_cut = substr($_rawDesc, 0, 157);
    $_cut = substr($_cut, 0, strrpos($_cut, ' '));
    $metaDesc = rtrim($_cut, '.,;:') . '...';
} else {
    $metaDesc = $_rawDesc;
}
$canonical = $canonicalBase . '/market-report'
    . ($citySlug    ? "/{$citySlug}"    : '')
    . ($subareaSlug ? "/{$subareaSlug}" : '')
    . ($typeSlug    ? "/{$typeSlug}"    : '')
    . ($monthSlug   ? "/{$monthSlug}"   : '');

// Breadcrumb items
$_bcItems = [
    ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>$canonicalBase.'/'],
    ['@type'=>'ListItem','position'=>2,'name'=>'Market Reports','item'=>$canonicalBase.'/market-report'],
];
$_bcPos = 2;
if ($citySlug) {
    $_bcItems[] = ['@type'=>'ListItem','position'=>++$_bcPos,'name'=>$city,'item'=>$canonicalBase.'/market-report/'.$citySlug];
}
if ($subareaSlug) {
    $_bcItems[] = ['@type'=>'ListItem','position'=>++$_bcPos,'name'=>$subarea,'item'=>$canonicalBase.'/market-report/'.$citySlug.'/'.$subareaSlug];
}
if ($typeSlug) {
    $_bcItems[] = ['@type'=>'ListItem','position'=>++$_bcPos,'name'=>$typeLabel,'item'=>$canonicalBase.'/market-report/'.$citySlug.($subareaSlug ? '/'.$subareaSlug : '').'/'.$typeSlug];
}
$_bcItems[] = ['@type'=>'ListItem','position'=>++$_bcPos,'name'=>$monthLabel,'item'=>$canonical];

// Condition values
$_condLabel      = $condition['label']      ?? '';
$_condColor      = $condition['color']      ?? '#888';
$_condAbsorption = $condition['absorption'] ?? 0;

// Report values
$_countSold    = $report ? (int)$report->count_sold      : 0;
$_avgPrice     = $report ? (int)$report->avg_sold_price  : 0;
$_avgDom       = $report ? (int)$report->avg_dom         : 0;
$_maxPrice     = $report ? (int)$report->max_sold_price  : 0;
$_minPrice     = $report ? (int)$report->min_sold_price  : 0;
$_activeStart  = $report ? (int)$report->active_at_start : 0;
$_countListed  = $report ? (int)$report->count_listed    : 0;

// Previous month comparison
$_prevCountSold = $prevReport ? (int)$prevReport->count_sold     : 0;
$_prevAvgPrice  = $prevReport ? (int)$prevReport->avg_sold_price : 0;
$_prevAvgDom    = $prevReport ? (int)$prevReport->avg_dom        : 0;

// YoY comparison
$_yoyCountSold = $yoyReport ? (int)$yoyReport->count_sold     : 0;
$_yoyAvgPrice  = $yoyReport ? (int)$yoyReport->avg_sold_price : 0;

// Editorial parts
$_editorialParts = [];
if ($_activeStart)  $_editorialParts[] = '<strong>' . number_format($_activeStart) . ' active listings</strong> at the start of the month';
if ($_countSold)    $_editorialParts[] = '<strong>' . number_format($_countSold) . ' properties sold</strong>';
if ($_avgPrice)     $_editorialParts[] = 'an average sold price of <strong>$' . number_format($_avgPrice) . '</strong>';
if ($_avgDom)       $_editorialParts[] = 'properties averaging <strong>' . $_avgDom . ' days</strong> on market';
if ($_condAbsorption > 0) $_editorialParts[] = 'an absorption rate of <strong>' . $_condAbsorption . '%</strong>';
$_placeStr = $subarea ? "{$subarea}, {$city}" : ($city ?: 'BC');
$_typeStr  = $typeLabel && $typeLabel !== 'Real Estate' ? strtolower($typeLabel) . ' ' : '';

// Market condition for sticky-bar
$_mktType = 'balanced';
if ($_condLabel === "Strong Seller's Market")  $_mktType = 'strong-sellers';
elseif ($_condLabel === "Seller's Market")     $_mktType = 'sellers';
elseif ($_condLabel === "Buyer's Market")      $_mktType = 'buyers';
@endphp
@section('title'){{ $metaTitle }}@endsection
@section('meta_description'){{ $metaDesc }}@endsection
@php
// Agent LocalBusiness schema
$_mrAgentSchema = null;
if (!empty($agent) && $agent) {
    $_mrAgentPhone = $agent->settings?->notification_phone ?? null;
    $_mrAgentSchema = [
        '@context' => 'https://schema.org',
        '@type'    => 'RealEstateAgent',
        'name'     => $agent->name ?? '',
        'url'      => $canonicalBase,
        'areaServed' => array_filter([$subarea ?: null, $city ?: null, 'British Columbia']),
    ];
    if ($_mrAgentPhone) {
        $_mrAgentSchema['telephone'] = $_mrAgentPhone;
    }
}

// FAQ data for JSON-LD and visible accordion
$_faqItems = [];
$_faqPlace = $subarea ? "{$subarea}, {$city}" : ($city ?: 'BC');
$_faqType  = ($typeLabel && $typeLabel !== 'Real Estate') ? strtolower($typeLabel) . ' ' : '';
$_faqCond  = ($condition['label'] ?? '') ?: 'mixed';
$_faqSold  = $_countSold ? number_format($_countSold) : 'N/A';
$_faqPrice = $_avgPrice  ? '$' . number_format($_avgPrice) : 'N/A';
$_faqDom   = $_avgDom    ? $_avgDom . ' days' : 'N/A';
$_faqAbs   = ($_condAbsorption > 0) ? $_condAbsorption . '%' : 'N/A';

// Q1: Is it a good time to buy?
$_buyAdvice = match(true) {
    str_contains($_faqCond, "Buyer's") => "Conditions may favour buyers — there is relatively more supply than demand, giving buyers more choice and negotiating room.",
    str_contains($_faqCond, "Strong Seller") => "It is currently a strong seller's market in {$_faqPlace}, meaning high demand and low supply. Buyers face competition and should be prepared to act quickly.",
    str_contains($_faqCond, "Seller") => "It is currently a seller's market in {$_faqPlace}. While demand is elevated, qualified buyers can still find opportunities.",
    default => "The market in {$_faqPlace} is currently balanced. Both buyers and sellers have reasonable negotiating positions.",
};
$_faqItems[] = [
    'q' => "Is it a good time to buy in {$_faqPlace}?",
    'a' => "In {$monthLabel}, the {$_faqPlace} {$_faqType}real estate market is classified as a <strong>{$_faqCond}</strong> with an absorption rate of {$_faqAbs}. {$_buyAdvice} Always consult a licensed REALTOR® to assess your specific situation.",
];

// Q2: How many homes sold this month?
$_faqItems[] = [
    'q' => "How many homes sold in {$_faqPlace} in {$monthLabel}?",
    'a' => "{$_faqSold} {$_faqType}properties sold in {$_faqPlace} during {$monthLabel} based on MLS® records."
        . ($_activeStart ? " There were " . number_format($_activeStart) . " active listings at the start of the month." : "")
        . ($_countListed ? " A total of " . number_format($_countListed) . " new listings came to market during the month." : ""),
];

// Q3: Average home price
$_faqItems[] = [
    'q' => "What is the average home price in {$_faqPlace}?",
    'a' => "The average {$_faqType}sold price in {$_faqPlace} in {$monthLabel} was {$_faqPrice}."
        . ($_maxPrice && $_minPrice ? " Sold prices ranged from $" . number_format($_minPrice) . " to $" . number_format($_maxPrice) . "." : "")
        . " Data is sourced from MLS® board records.",
];

// Q4: How long are homes sitting on the market?
$_faqItems[] = [
    'q' => "How long are homes sitting on the market in {$_faqPlace}?",
    'a' => "In {$monthLabel}, {$_faqType}properties in {$_faqPlace} averaged {$_faqDom} on the market before selling."
        . ($_condAbsorption > 0 ? " With an absorption rate of {$_faqAbs}, the market is currently a {$_faqCond}." : "")
        . " A lower average days-on-market indicates stronger demand.",
];

// Q5: How does this month compare to last year?
$_yoyAvgPriceMr  = $yoyReport ? (int)$yoyReport->avg_sold_price : 0;
$_yoyCountSoldMr = $yoyReport ? (int)$yoyReport->count_sold     : 0;
$_yoyPriceChgMr  = ($_yoyAvgPriceMr > 0 && $_avgPrice > 0)
    ? round(($_avgPrice - $_yoyAvgPriceMr) / $_yoyAvgPriceMr * 100, 1) : null;
$_yoySoldChgMr   = ($_yoyCountSoldMr > 0 && $_countSold > 0)
    ? round(($_countSold - $_yoyCountSoldMr) / $_yoyCountSoldMr * 100, 1) : null;
$_prevYearLabel  = date('F Y', mktime(0, 0, 0, $month, 1, $year - 1));
if ($_yoyPriceChgMr !== null) {
    $_yoyPriceStr   = ($_yoyPriceChgMr > 0 ? 'up ' : 'down ') . abs($_yoyPriceChgMr) . '% year-over-year';
    $_yoyPrevFmt    = $_yoyAvgPriceMr > 0 ? '$' . number_format($_yoyAvgPriceMr) : 'N/A';
    $_yoyAnswer     = "The average {$_faqType}sold price in {$_faqPlace} in {$monthLabel} was {$_faqPrice}, {$_yoyPriceStr} compared to {$_prevYearLabel} ({$_yoyPrevFmt}).";
    if ($_yoySoldChgMr !== null) {
        $_soldDirStr  = $_yoySoldChgMr > 0 ? 'up' : 'down';
        $_yoyAnswer  .= " Sales volume was {$_soldDirStr} " . abs($_yoySoldChgMr) . "% compared to the same month last year.";
    }
} else {
    $_yoyAnswer = "Year-over-year comparison data is not available for {$_faqPlace} in {$monthLabel}. "
        . "Browse the monthly archive to track price trends over multiple years for this area.";
}
$_faqItems[] = [
    'q' => "How has the {$_faqPlace} market changed compared to last year?",
    'a' => $_yoyAnswer . " Data is sourced from MLS® sold records.",
];

$_faqSchema = [
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => array_map(fn($item) => [
        '@type'          => 'Question',
        'name'           => strip_tags($item['q']),
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($item['a'])],
    ], $_faqItems),
];
@endphp
@section('meta')
<link rel="canonical" href="{{ $canonical }}">
@if($prevSlug)
<link rel="prev" href="{{ $canonicalBase . '/market-report' . ($citySlug ? '/'.$citySlug : '') . ($subareaSlug ? '/'.$subareaSlug : '') . ($typeSlug ? '/'.$typeSlug : '') . '/' . $prevSlug }}">
@endif
@if($nextSlug)
<link rel="next" href="{{ $canonicalBase . '/market-report' . ($citySlug ? '/'.$citySlug : '') . ($subareaSlug ? '/'.$subareaSlug : '') . ($typeSlug ? '/'.$typeSlug : '') . '/' . $nextSlug }}">
@endif
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="article">
<meta property="article:published_time" content="{{ sprintf('%04d-%02d-01', $year, $month) }}">
<meta property="article:author" content="Hani Faraj">
@php
$_ogImg = ($placeImageUrl ?? null) ?: 'https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.jpg';
@endphp
<meta property="og:image" content="{{ $_ogImg }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDesc }}">
<meta name="twitter:image" content="{{ $_ogImg }}">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": {!! json_encode($_bcItems, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG) !!}
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": {{ json_encode($metaTitle, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG) }},
  "description": {{ json_encode($metaDesc, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG) }},
  "url": {{ json_encode($canonical, JSON_UNESCAPED_SLASHES|JSON_HEX_TAG) }},
  "datePublished": {{ json_encode(sprintf('%04d-%02d-01', $year, $month), JSON_HEX_TAG) }},
  "dateModified": {{ json_encode(sprintf('%04d-%02d-01', $year, $month), JSON_HEX_TAG) }},
  "author": {
    "@type": "Person",
    "name": "Hani Faraj",
    "url": "https://www.bccondosandhomes.com"
  },
  "publisher": {
    "@type": "Organization",
    "name": "BC Condos And Homes",
    "url": "https://www.bccondosandhomes.com",
    "logo": {
      "@type": "ImageObject",
      "url": "https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.jpg"
    }
  },
  "image": {{ json_encode($_ogImg, JSON_HEX_TAG) }},
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": {!! json_encode($_bcItems, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG) !!}
  }
}
</script>
<script type="application/ld+json">{!! json_encode($_faqSchema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG) !!}</script>
@if($_mrAgentSchema)
<script type="application/ld+json">{!! json_encode($_mrAgentSchema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG) !!}</script>
@endif
@endsection
@section('content')
@include('frontend.includes.header')

<div class="page-main" style="margin-top:66px;padding:28px 0 0;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:10px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item"><a href="/market-report">Market Reports</a></li>
                @if($city)<li class="breadcrumb-item">
                    @if($subarea || $typeSlug)<a href="/market-report/{{ $citySlug }}">{{ $city }}</a>@else<a href="/market-report/{{ $citySlug }}">{{ $city }}</a>@endif
                </li>@endif
                @if($subarea)<li class="breadcrumb-item">
                    <a href="/market-report/{{ $citySlug }}/{{ $subareaSlug }}{{ $typeSlug ? '/'.$typeSlug : '' }}">{{ $subarea }}{{ $typePart }}</a>
                </li>@endif
                <li class="breadcrumb-item active">{{ $monthLabel }}</li>
            </ol>
        </nav>

        <h1 style="font-size:24px;font-weight:700;margin-bottom:6px;color:#2c2c2c;">
            {{ $placeLabel }}{{ $typePart }} Market Report – {{ $monthLabel }}
        </h1>

        @if($placeDescription)
        <p style="font-size:14px;color:#666;max-width:800px;line-height:1.7;margin-bottom:4px;">{{ $placeDescription }}</p>
        @endif

        {{-- Prev / Next navigation --}}
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;margin-bottom:4px;">
            @if($prevSlug)
            <a href="/market-report/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}{{ $typeSlug ? '/'.$typeSlug : '' }}/{{ $prevSlug }}"
               style="font-size:12px;color:#2c6fad;text-decoration:none;">← {{ $prevLabel }}</a>
            @endif
            @if($nextSlug)
            <a href="/market-report/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}{{ $typeSlug ? '/'.$typeSlug : '' }}/{{ $nextSlug }}"
               style="font-size:12px;color:#2c6fad;text-decoration:none;margin-left:auto;">Next month →</a>
            @endif
        </div>
    </div>
</div>

<div class="container" style="padding-bottom:40px;">

    @if(!$report || !$_countSold)
    {{-- No data notice --}}
    <div class="row" style="margin-top:22px;">
        <div class="col-md-12">
            <div style="background:#fff8e1;border-left:4px solid #f39c12;border-radius:4px;padding:14px 18px;font-size:14px;color:#666;">
                No sold data is available for <strong>{{ $placeLabel }}</strong> in <strong>{{ $monthLabel }}</strong>.
                <a href="/market-report/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}{{ $typeSlug ? '/'.$typeSlug : '' }}" style="color:#2c6fad;">Browse all months →</a>
            </div>
        </div>
    </div>
    @else

    {{-- Key stats tiles --}}
    <div class="row" style="margin-top:22px;">
        <div class="col-sm-3 col-xs-6" style="margin-bottom:14px;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:#2c6fad;">{{ number_format($_countSold) }}</div>
                <div style="font-size:12px;color:#888;margin-top:4px;">Units Sold</div>
            </div>
        </div>
        <div class="col-sm-3 col-xs-6" style="margin-bottom:14px;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px;text-align:center;">
                <div style="font-size:22px;font-weight:700;color:#333;">{{ $_avgPrice ? '$'.number_format($_avgPrice) : '—' }}</div>
                <div style="font-size:12px;color:#888;margin-top:4px;">Avg Sold Price</div>
            </div>
        </div>
        <div class="col-sm-3 col-xs-6" style="margin-bottom:14px;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:#333;">{{ $_avgDom ? $_avgDom.'d' : '—' }}</div>
                <div style="font-size:12px;color:#888;margin-top:4px;">Avg Days on Market</div>
            </div>
        </div>
        <div class="col-sm-3 col-xs-6" style="margin-bottom:14px;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px;text-align:center;">
                @if($_condLabel)
                <div style="font-size:14px;font-weight:700;color:#fff;background:{{ $_condColor }};border-radius:4px;padding:6px 10px;display:inline-block;line-height:1.3;">{{ $_condLabel }}</div>
                @else
                <div style="font-size:28px;font-weight:700;color:#888;">—</div>
                @endif
                <div style="font-size:12px;color:#888;margin-top:6px;">Market Condition</div>
            </div>
        </div>
    </div>

    {{-- Secondary stats --}}
    @if($_activeStart || $_countListed || $_maxPrice || $_minPrice || $_condAbsorption)
    <div class="row" style="margin-top:6px;">
        <div class="col-md-12">
            <div style="background:#f7f4ef;border-radius:6px;padding:12px 18px;font-size:13px;color:#555;display:flex;flex-wrap:wrap;gap:18px;">
                @if($_activeStart)<span><strong>{{ number_format($_activeStart) }}</strong> active at start of month</span>@endif
                @if($_countListed)<span><strong>{{ number_format($_countListed) }}</strong> new listings</span>@endif
                @if($_condAbsorption > 0)<span>Absorption rate: <strong>{{ $_condAbsorption }}%</strong></span>@endif
                @if($_maxPrice)<span>Highest sold: <strong>${{ number_format($_maxPrice) }}</strong></span>@endif
                @if($_minPrice)<span>Lowest sold: <strong>${{ number_format($_minPrice) }}</strong></span>@endif
            </div>
        </div>
    </div>
    @endif

    {{-- Month-over-month and year-over-year comparison --}}
    @if($prevReport || $yoyReport)
    <div class="row" style="margin-top:20px;">
        <div class="col-md-12">
            <h2 style="font-size:16px;font-weight:700;color:#333;margin-bottom:12px;">How Does {{ $monthLabel }} Compare?</h2>
            <div style="overflow-x:auto;">
                <table style="width:100%;font-size:13px;border-collapse:collapse;background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);">
                    <thead>
                        <tr style="background:#f7f4ef;border-bottom:2px solid #eee;">
                            <th style="padding:9px 14px;text-align:left;font-weight:600;color:#555;">Period</th>
                            <th style="padding:9px 14px;text-align:right;font-weight:600;color:#555;">Units Sold</th>
                            <th style="padding:9px 14px;text-align:right;font-weight:600;color:#555;">Avg Sold Price</th>
                            <th style="padding:9px 14px;text-align:right;font-weight:600;color:#555;">Avg DOM</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom:1px solid #f0f0f0;background:#fffdf8;">
                            <td style="padding:9px 14px;font-weight:700;">{{ $monthLabel }} <span style="font-size:11px;font-weight:400;color:#888;">(this report)</span></td>
                            <td style="padding:9px 14px;text-align:right;font-weight:700;">{{ number_format($_countSold) }}</td>
                            <td style="padding:9px 14px;text-align:right;font-weight:700;">{{ $_avgPrice ? '$'.number_format($_avgPrice) : '—' }}</td>
                            <td style="padding:9px 14px;text-align:right;font-weight:700;">{{ $_avgDom ? $_avgDom.'d' : '—' }}</td>
                        </tr>
                        @if($prevReport && $_prevCountSold)
                        @php
                        $_soldChg  = $_prevCountSold  ? round(($_countSold  - $_prevCountSold)  / $_prevCountSold  * 100, 1) : null;
                        $_priceChg = $_prevAvgPrice   ? round(($_avgPrice   - $_prevAvgPrice)   / $_prevAvgPrice   * 100, 1) : null;
                        $_domChg   = $_prevAvgDom     ? round(($_avgDom     - $_prevAvgDom)     / $_prevAvgDom     * 100, 1) : null;
                        @endphp
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:9px 14px;color:#555;">{{ $prevLabel }} <span style="font-size:11px;color:#aaa;">month prior</span></td>
                            <td style="padding:9px 14px;text-align:right;">{{ number_format($_prevCountSold) }}
                                @if($_soldChg !== null)<span style="font-size:11px;color:{{ $_soldChg >= 0 ? '#27ae60' : '#e74c3c' }};">{{ $_soldChg >= 0 ? '+' : '' }}{{ $_soldChg }}%</span>@endif
                            </td>
                            <td style="padding:9px 14px;text-align:right;">@if($_prevAvgPrice)${{ number_format($_prevAvgPrice) }}@if($_priceChg !== null) <span style="font-size:11px;color:{{ $_priceChg >= 0 ? '#27ae60' : '#e74c3c' }};">{{ $_priceChg >= 0 ? '+' : '' }}{{ $_priceChg }}%</span>@endif @else &mdash;@endif</td>
                            <td style="padding:9px 14px;text-align:right;">@if($_prevAvgDom && $_domChg !== null){{ $_prevAvgDom }}d <span style="font-size:11px;color:{{ $_domChg <= 0 ? '#27ae60' : '#e74c3c' }};">{{ $_domChg >= 0 ? '+' : '' }}{{ $_domChg }}%</span> @elseif($_prevAvgDom){{ $_prevAvgDom }}d @else &mdash;@endif</td>
                        </tr>
                        @endif
                        @if($yoyReport && $_yoyCountSold)
                        @php
                        $_yoySoldChg  = $_yoyCountSold ? round(($_countSold - $_yoyCountSold) / $_yoyCountSold * 100, 1) : null;
                        $_yoyPriceChg = $_yoyAvgPrice  ? round(($_avgPrice  - $_yoyAvgPrice)  / $_yoyAvgPrice  * 100, 1) : null;
                        $_yoyYear = date('F Y', mktime(0,0,0,$month,1,$year-1));
                        @endphp
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:9px 14px;color:#555;">{{ $_yoyYear }} <span style="font-size:11px;color:#aaa;">year ago</span></td>
                            <td style="padding:9px 14px;text-align:right;">{{ number_format($_yoyCountSold) }}
                                @if($_yoySoldChg !== null)<span style="font-size:11px;color:{{ $_yoySoldChg >= 0 ? '#27ae60' : '#e74c3c' }};">{{ $_yoySoldChg >= 0 ? '+' : '' }}{{ $_yoySoldChg }}%</span>@endif
                            </td>
                            <td style="padding:9px 14px;text-align:right;">@if($_yoyAvgPrice)${{ number_format($_yoyAvgPrice) }}@if($_yoyPriceChg !== null) <span style="font-size:11px;color:{{ $_yoyPriceChg >= 0 ? '#27ae60' : '#e74c3c' }};">{{ $_yoyPriceChg >= 0 ? '+' : '' }}{{ $_yoyPriceChg }}%</span>@endif @else &mdash;@endif</td>
                            <td style="padding:9px 14px;text-align:right;">—</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- 3-year price trend chart --}}
    @if(count($chartData) > 1)
    <div class="row" style="margin-top:24px;">
        <div class="col-md-12">
            <h2 style="font-size:16px;font-weight:700;color:#333;margin-bottom:12px;">3-Year Price Trend — {{ $placeLabel }}{{ $typePart }}</h2>
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px;">
                <canvas id="mr-price-chart" style="width:100%;max-height:260px;"></canvas>
            </div>
        </div>
    </div>
    @endif

    {{-- Editorial / verdict section --}}
    @if($_condLabel)
    <div class="row" style="margin-top:24px;">
        <div class="col-md-12">
            <div style="background:#fafaf8;border-left:4px solid {{ $_condColor }};border-radius:4px;padding:16px 20px;font-size:14px;color:#444;line-height:1.8;">
                The <strong>{{ $_placeStr }}</strong> {{ $_typeStr }}real estate market was a
                <strong style="color:{{ $_condColor }}">{{ $_condLabel }}</strong>
                in <strong>{{ $monthLabel }}</strong>@if(count($_editorialParts)), with {!! implode(', ', $_editorialParts) !!}@endif.
                All data is sourced from MLS® sold records.
            </div>
        </div>
    </div>
    @endif

    {{-- Recent sold listings --}}
    @if($soldListings && count($soldListings) > 0)
    <div class="row" style="margin-top:28px;">
        <div class="col-md-12">
            <h2 style="font-size:16px;font-weight:700;color:#333;margin-bottom:14px;">Recently Sold in {{ $placeLabel }}</h2>
            <div class="row">
                @foreach($soldListings as $lst)
                @php
                    $_lstPhoto = $lst->photos->first();
                    $_lstPhotoUrl = $_lstPhoto
                        ? 'https://media.pixilinkserver.com/' . str_replace('images', '', $_lstPhoto->directory . $_lstPhoto->name) . '?w=400'
                        : asset('assets/img/no-image.jpg');
                    $_lstAddr = trim(($lst->street_number ? $lst->street_number . ' ' : '') . $lst->street_name . ' ' . $lst->street_type);
                @endphp
                <div class="col-sm-4 col-xs-6" style="margin-bottom:16px;">
                    <a href="/listing/{{ $lst->slug }}" style="display:block;text-decoration:none;color:inherit;">
                        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);overflow:hidden;">
                            <div style="height:160px;background:url('{{ $_lstPhotoUrl }}') center/cover no-repeat;"></div>
                            <div style="padding:12px 14px;">
                                <div style="font-size:13px;font-weight:600;color:#333;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $_lstAddr }}</div>
                                @if($lst->soldprice_2)<div style="font-size:14px;font-weight:700;color:#2c6fad;">Sold ${{ number_format($lst->soldprice_2) }}</div>@endif
                                <div style="font-size:11px;color:#888;margin-top:3px;">{{ $lst->bedrooms ?? 0 }} bd &bull; {{ $lst->bathstotal ?? 0 }} ba &bull; {{ $lst->getType() }}</div>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Active listings --}}
    @if($activeListings && count($activeListings) > 0)
    <div class="row" style="margin-top:28px;">
        <div class="col-md-12">
            <h2 style="font-size:16px;font-weight:700;color:#333;margin-bottom:14px;">Active Listings in {{ $placeLabel }}</h2>
            <div class="row">
                @foreach($activeListings as $lst)
                @php
                    $_actPhoto = $lst->photos->first();
                    $_actPhotoUrl = $_actPhoto
                        ? 'https://media.pixilinkserver.com/' . str_replace('images', '', $_actPhoto->directory . $_actPhoto->name) . '?w=400'
                        : asset('assets/img/no-image.jpg');
                    $_actAddr = trim(($lst->street_number ? $lst->street_number . ' ' : '') . $lst->street_name . ' ' . $lst->street_type);
                @endphp
                <div class="col-sm-4 col-xs-6" style="margin-bottom:16px;">
                    <a href="/listing/{{ $lst->slug }}" style="display:block;text-decoration:none;color:inherit;">
                        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);overflow:hidden;">
                            <div style="height:160px;background:url('{{ $_actPhotoUrl }}') center/cover no-repeat;"></div>
                            <div style="padding:12px 14px;">
                                <div style="font-size:13px;font-weight:600;color:#333;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $_actAddr }}</div>
                                @if($lst->listprice_2)<div style="font-size:14px;font-weight:700;color:#2c6fad;">${{ number_format($lst->listprice_2) }}</div>@endif
                                <div style="font-size:11px;color:#888;margin-top:3px;">{{ $lst->bedrooms ?? 0 }} bd &bull; {{ $lst->bathstotal ?? 0 }} ba &bull; {{ $lst->getType() }}</div>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Top buildings (condos only) --}}
    @if($topBuildings && count($topBuildings) > 0)
    <div class="row" style="margin-top:28px;">
        <div class="col-md-12">
            <h2 style="font-size:16px;font-weight:700;color:#333;margin-bottom:14px;">Top Buildings in {{ $placeLabel }}</h2>
            <div class="row">
                @foreach($topBuildings as $bldg)
                <div class="col-sm-4 col-xs-6" style="margin-bottom:10px;">
                    <a href="/building/{{ $bldg->slug }}" style="display:block;padding:10px 14px;background:#fff;border:1px solid #eee;border-radius:5px;font-size:13px;color:#333;text-decoration:none;">
                        <strong>{{ $bldg->name }}</strong>
                        @if($bldg->units_in_strata)<div style="font-size:11px;color:#888;margin-top:2px;">{{ number_format($bldg->units_in_strata) }} units</div>@endif
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @endif {{-- end if $report && $_countSold --}}

    {{-- Hani attribution — always visible on all monthly report pages --}}
    <div class="row" style="margin-top:28px;">
        <div class="col-md-12">
            @include('frontend.includes.hani_attribution', ['attrCity' => ($city ?: null), 'attrSubarea' => ($subarea ?: null)])
        </div>
    </div>

    {{-- Type filter tabs --}}
    <div class="row" style="margin-top:28px;">
        <div class="col-md-12">
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="/market-report/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}/{{ $monthSlug }}" class="mstab @if(!$typeSlug)mstab-active @endif">All Types</a>
                <a href="/market-report/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}/condos/{{ $monthSlug }}" class="mstab @if($typeSlug==='condos')mstab-active @endif">Condos</a>
                <a href="/market-report/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}/houses/{{ $monthSlug }}" class="mstab @if($typeSlug==='houses')mstab-active @endif">Houses</a>
                <a href="/market-report/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}/townhouses/{{ $monthSlug }}" class="mstab @if($typeSlug==='townhouses')mstab-active @endif">Townhouses</a>
            </div>
        </div>
    </div>

    {{-- Related links --}}
    <div class="row" style="margin-top:16px;">
        <div class="col-md-12">
            <div style="background:#f7f4ef;border-radius:6px;padding:14px 18px;font-size:13px;color:#555;">
                <strong>Related:</strong>
                <a href="/market-report/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}{{ $typeSlug ? '/'.$typeSlug : '' }}" style="margin-left:12px;color:#2c6fad;">{{ $placeLabel }}{{ $typePart }} Archive →</a>
                <a href="/market-stats/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}{{ $typeSlug ? '/'.$typeSlug : '' }}" style="margin-left:12px;color:#2c6fad;">Live Market Stats →</a>
                @if($subarea)<a href="/market-report/{{ $citySlug }}" style="margin-left:12px;color:#2c6fad;">{{ $city }} Reports →</a>@endif
                <a href="/market-report" style="margin-left:12px;color:#2c6fad;">All BC Reports →</a>
            </div>
        </div>
    </div>

    {{-- FAQ Accordion (visible for crawlers; answers auto-populated from report data) --}}
    @if(count($_faqItems))
    <div class="row" style="margin-top:32px;">
        <div class="col-md-12">
            <h2 style="font-size:17px;font-weight:700;color:#1a1a1a;margin-bottom:16px;">Frequently Asked Questions — {{ $_faqPlace }} {{ $monthLabel }}</h2>
            <div class="mr-faq-list">
                @foreach($_faqItems as $_fi => $_fq)
                <div class="mr-faq-item">
                    <button class="mr-faq-btn" onclick="mrFaqToggle(this)" aria-expanded="{{ $_fi === 0 ? 'true' : 'false' }}" type="button">
                        {{ $_fq['q'] }}
                        <span class="mr-faq-icon">{{ $_fi === 0 ? '−' : '+' }}</span>
                    </button>
                    <div class="mr-faq-ans" style="{{ $_fi === 0 ? '' : 'display:none;' }}">
                        <p>{!! $_fq['a'] !!}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>

<div class="listings-disclaimer">
    <div class="container">
        <p>Last Update: {{ \Carbon\Carbon::now()->format('m/d/Y') }} &nbsp;&nbsp;<strong>Disclaimer:</strong> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy.</p>
    </div>
</div>

<div class="container" style="padding-bottom:16px;">
    @php
        $_mrStripCtx  = trim(($subarea ? $subarea . ', ' : '') . ($city ?: 'BC'));
        $_mrTypePart  = ($typeLabel && $typeLabel !== 'Real Estate') ? ' ' . $typeLabel : '';
        $_mrStripName = $_mrStripCtx . $_mrTypePart . ' Listings';
        $_mrStripData = json_encode(array_filter([
            'cities'         => $city ?: null,
            'subareas'       => $subarea ?: null,
            'type'           => $listingtype ?? null,
            'listing_status' => 'Active',
        ]));
    @endphp
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => $_mrStripCtx,
        'stripHeading'    => 'Get Monthly Market Updates & New Listing Alerts for ' . $_mrStripCtx,
        'stripSubtext'    => 'Get notified of new' . strtolower($_mrTypePart) . ' listings and monthly market report updates in ' . $_mrStripCtx . '.',
        'stripSearchName' => $_mrStripName,
        'stripSearchData' => $_mrStripData,
        'stripCity'       => $city ?: '',
        'stripModalId'    => 'mrAlert_' . md5(($citySlug ?? '') . ($subareaSlug ?? '') . ($typeSlug ?? '') . ($monthSlug ?? '')),
    ])
</div>
@include('frontend.includes.hani_bubble')
@include('frontend.includes.footer_links')
@include('frontend.includes.footer')

<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
    @if($city) data-city="{{ $city }}" @endif
    @if($subarea) data-neighbourhood="{{ $subarea }}" @endif
    data-market-type="{{ $_mktType }}"
    @if($_avgPrice) data-avg-price="${{ number_format($_avgPrice) }}" @endif
    @if($_avgDom) data-avg-dom="{{ $_avgDom }}d" @endif
    @if($_activeStart) data-active-listings="{{ $_activeStart }}" @endif
    @if($_condAbsorption) data-absorption-rate="{{ $_condAbsorption }}%" @endif
    @if($_countSold) data-sold-30d="{{ $_countSold }}" @endif
></script>

@endsection

@push('after-styles')
<style>
.mstab { display:inline-block;padding:5px 14px;font-size:12px;font-weight:600;border:1px solid #ccc;border-radius:4px;color:#555;text-decoration:none;background:#fff; }
.mstab:hover,.mstab-active { background:#2c6fad;color:#fff;border-color:#2c6fad;text-decoration:none; }
/* FAQ accordion */
.mr-faq-list { border-top:2px solid #f0ebe3; padding-top:4px; }
.mr-faq-item { border:1px solid #e8e4dd;border-radius:6px;margin-bottom:8px;overflow:hidden; }
.mr-faq-btn  { width:100%;display:flex;justify-content:space-between;align-items:center;background:#fafaf8;border:none;padding:13px 16px;font-size:14px;font-weight:600;color:#1a1a1a;text-align:left;cursor:pointer;gap:12px; }
.mr-faq-btn:hover { background:#f5f0ea; }
.mr-faq-icon { flex-shrink:0;font-size:18px;font-weight:400;color:#888;line-height:1; }
.mr-faq-ans  { padding:0 16px 13px;font-size:14px;color:#555;line-height:1.75; }
.mr-faq-ans p { margin:10px 0 0; }
</style>
@endpush

@push('after-scripts')
<script>
function mrFaqToggle(btn) {
    var ans = btn.nextElementSibling;
    var icon = btn.querySelector('.mr-faq-icon');
    var open = ans.style.display !== 'none';
    ans.style.display = open ? 'none' : 'block';
    icon.textContent  = open ? '+' : '−';
    btn.setAttribute('aria-expanded', open ? 'false' : 'true');
}
</script>
@if(count($chartData) > 1)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function(){
    var chartData = {!! json_encode($chartData, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG) !!};
    var labels = [], prices = [], sold = [];
    chartData.forEach(function(r){
        labels.push(r.label || '');
        prices.push(r.avg_sold_price ? Math.round(r.avg_sold_price) : null);
        sold.push(r.count_sold || 0);
    });
    var ctx = document.getElementById('mr-price-chart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Avg Sold Price',
                    data: prices,
                    borderColor: '#2c6fad',
                    backgroundColor: 'rgba(44,111,173,.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    yAxisID: 'y'
                },
                {
                    label: 'Units Sold',
                    data: sold,
                    borderColor: '#e5b021',
                    backgroundColor: 'rgba(229,176,33,.1)',
                    fill: false,
                    tension: 0.3,
                    pointRadius: 4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: {
                    type: 'linear', position: 'left',
                    ticks: { callback: function(v){ return v ? '$'+v.toLocaleString() : '—'; }, font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,.05)' }
                },
                y1: {
                    type: 'linear', position: 'right',
                    ticks: { font: { size: 11 } },
                    grid: { drawOnChartArea: false }
                },
                x: { ticks: { font: { size: 11 }, maxTicksLimit: 12 } }
            }
        }
    });
})();
</script>
@endif
@endpush
