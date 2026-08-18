@extends('frontend.layouts.default_mobile')
@php
$canonicalBase = $canonicalBase ?? 'https://www.bccondosandhomes.com';
$placeLabel = $subarea ? "{$subarea}, {$city}" : ($city ?: 'BC');
$typePart   = $typeLabel !== 'Real Estate' ? " {$typeLabel}" : '';
$metaTitle  = "{$placeLabel}{$typePart} Market Reports Archive | Hani & Les";
$earliest   = count($months) ? end($months)->label : '';
$_rawDesc   = "Browse monthly{$typePart} market reports for {$placeLabel}. Historical sold prices, days on market, absorption rates and market conditions" . ($earliest ? " going back to {$earliest}" : '') . ".";
if (strlen($_rawDesc) > 160) {
    $_cut = substr($_rawDesc, 0, 157);
    $_cut = substr($_cut, 0, strrpos($_cut, ' '));
    $metaDesc = rtrim($_cut, '.,;:') . '...';
} else {
    $metaDesc = $_rawDesc;
}
$canonical  = $canonicalBase . '/market-report'
    . ($citySlug    ? "/{$citySlug}"    : '')
    . ($subareaSlug ? "/{$subareaSlug}" : '')
    . ($typeSlug    ? "/{$typeSlug}"    : '');
$noDataMonth = $noDataMonth ?? null;
$placeDescription = $placeDescription ?? null;

// Build breadcrumb items for schema
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
    $_bcItems[] = ['@type'=>'ListItem','position'=>++$_bcPos,'name'=>$typeLabel,'item'=>$canonical];
}

// Build ItemList entries from $months
$_listItems = [];
foreach ($months as $_i => $_m) {
    $monthUrl = $canonical . '/' . $_m->slug;
    $monthName = $_m->label . ' – ' . $placeLabel . ($typePart ? $typePart : '') . ' Market Report';
    $_listItems[] = ['@type'=>'ListItem','position'=>$_i+1,'name'=>$monthName,'item'=>$monthUrl];
}

// Latest month summary for hero
$_latest     = count($months) ? $months[0] : null;
$_latestCond = $_latest ? ($_latest->condition['label'] ?? '') : '';
$_latestColor = $_latest ? ($_latest->condition['color'] ?? '#aaa') : '#aaa';

// Condition color to text-class mapping (for hero badge)
$_condBgMap  = [
    "Strong Seller's Market" => '#c62828',
    "Seller's Market"        => '#e65100',
    "Balanced Market"        => '#1565c0',
    "Buyer's Market"         => '#2e7d32',
];
$_heroBadgeBg = $_condBgMap[$_latestCond] ?? '#555';

// OG image — use place-specific image when available, otherwise fall back to default
$_archPlaceImg = $placeImageUrl ?? null;
$_archOgImg = $_archPlaceImg ?: 'https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.jpg';

// FAQ data — use latest month if available
$_archFaqPlace  = $subarea ? "{$subarea}, {$city}" : ($city ?: 'BC');
$_archFaqType   = ($typeLabel && $typeLabel !== 'Real Estate') ? strtolower($typeLabel) . ' ' : '';
$_archFaqPrice  = ($_latest && $_latest->avg_price)  ? '$' . number_format($_latest->avg_price)  : null;
$_archFaqDom    = ($_latest && $_latest->avg_dom)    ? $_latest->avg_dom . ' days'               : null;
$_archFaqCond   = ($_latest) ? ($_latest->condition['label'] ?? '')                              : '';
$_archFaqAbs    = ($_latest && isset($_latest->condition['absorption']) && $_latest->condition['absorption'] > 0)
                    ? $_latest->condition['absorption'] . '%' : null;
$_archFaqSold   = ($_latest && $_latest->count_sold) ? number_format($_latest->count_sold)       : null;
$_archFaqPeriod = $_latest ? ($_latest->label ?? 'recent months') : 'recent months';

$_archFaqItems = [];

// Q1: Average home price
$_archFaqItems[] = [
    'q' => "What is the average home price in {$_archFaqPlace}?",
    'a' => $_archFaqPrice
        ? "Based on the most recent data ({$_archFaqPeriod}), the average {$_archFaqType}sold price in {$_archFaqPlace} was {$_archFaqPrice}. Use the monthly archive above to track how prices have changed over time."
        : "Average sold prices for {$_archFaqPlace} are tracked monthly in the archive above. Each report shows the average sold price, price range, and year-over-year comparison for that month.",
];

// Q2: Buyer's or seller's market
$_archMarketAnswer = '';
if ($_archFaqCond) {
    $isBuyer  = str_contains($_archFaqCond, "Buyer");
    $isSeller = str_contains($_archFaqCond, "Seller");
    if ($isBuyer) {
        $_archMarketAnswer = "As of {$_archFaqPeriod}, {$_archFaqPlace} is a buyer's market — there is more supply than demand, giving buyers more choice and negotiating room.";
    } elseif (str_contains($_archFaqCond, "Strong")) {
        $_archMarketAnswer = "As of {$_archFaqPeriod}, {$_archFaqPlace} is a strong seller's market with high demand and limited supply. Buyers face competition and should be prepared to act quickly.";
    } elseif ($isSeller) {
        $_archMarketAnswer = "As of {$_archFaqPeriod}, {$_archFaqPlace} is a seller's market with elevated demand. Well-priced properties are selling faster than average.";
    } else {
        $_archMarketAnswer = "As of {$_archFaqPeriod}, {$_archFaqPlace} is a balanced market where supply and demand are relatively equal, giving both buyers and sellers reasonable negotiating positions.";
    }
    if ($_archFaqAbs) {
        $_archMarketAnswer .= " The absorption rate is {$_archFaqAbs}.";
    }
} else {
    $_archMarketAnswer = "Market conditions in {$_archFaqPlace} vary month to month. Browse the monthly reports above to see whether buyers or sellers had the advantage in any given period.";
}
$_archFaqItems[] = [
    'q' => "Is {$_archFaqPlace} a buyer's or seller's market?",
    'a' => $_archMarketAnswer,
];

// Q3: Types of homes
$_archHomeTypes = $_archFaqType ? ucfirst(trim($_archFaqType)) . 's' : 'Detached houses, townhomes, and condos';
$_archFaqItems[] = [
    'q' => "What types of homes sell in {$_archFaqPlace}?",
    'a' => ($_archFaqType
        ? "{$_archHomeTypes} are the property type tracked in this archive for {$_archFaqPlace}."
        : "{$_archHomeTypes} all trade in {$_archFaqPlace}.")
        . " Use the type tabs above the archive to filter reports by condos, houses, or townhouses and compare price trends for each property type.",
];

// Q4: How long do homes take to sell
$_archFaqItems[] = [
    'q' => "How long do homes take to sell in {$_archFaqPlace}?",
    'a' => $_archFaqDom
        ? "In {$_archFaqPeriod}, {$_archFaqType}properties in {$_archFaqPlace} averaged {$_archFaqDom} on the market before selling. Click any month in the archive above for the full breakdown."
        : "Days on market (DOM) varies each month. Each monthly report in the archive above includes the average days on market for that period in {$_archFaqPlace}.",
];

// Q5: Absorption rate / sales-to-active ratio
$_archFaqItems[] = [
    'q' => "What is the sales-to-active (absorption) rate in {$_archFaqPlace}?",
    'a' => $_archFaqAbs
        ? "The absorption rate in {$_archFaqPlace} was {$_archFaqAbs} in {$_archFaqPeriod}"
          . ($_archFaqSold ? ", with {$_archFaqSold} properties sold" : '')
          . ". An absorption rate above 20% typically indicates a seller's market; below 12% suggests a buyer's market."
        : "The absorption rate (sold ÷ active listings × 100) is published in each monthly report. A rate above 20% signals a seller's market; below 12% favours buyers. Browse the archive above to track this ratio over time.",
];

// Build FAQPage schema array
$_archFaqSchema = [
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => array_map(fn($item) => [
        '@type'          => 'Question',
        'name'           => strip_tags($item['q']),
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($item['a'])],
    ], $_archFaqItems),
];

// Agent LocalBusiness schema
$_archAgentSchema = null;
if (!empty($agent) && $agent) {
    $_archAgentPhone = $agent->settings?->notification_phone ?? null;
    $_archAgentSchema = [
        '@context' => 'https://schema.org',
        '@type'    => 'RealEstateAgent',
        'name'     => $agent->name ?? '',
        'url'      => $canonicalBase,
        'areaServed' => array_filter([$subarea ?: null, $city ?: null, 'British Columbia']),
    ];
    if ($_archAgentPhone) {
        $_archAgentSchema['telephone'] = $_archAgentPhone;
    }
}
@endphp
@section('title'){{ $metaTitle }}@endsection
@section('meta_description'){{ $metaDesc }}@endsection
@section('meta')
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="website">
<meta property="og:image" content="{{ $_archOgImg }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDesc }}">
<meta name="twitter:image" content="{{ $_archOgImg }}">
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => $_bcItems,
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG) !!}
</script>
@php
$_archPageGraph = [
    [
        '@type'       => 'CollectionPage',
        'name'        => $metaTitle,
        'description' => $metaDesc,
        'url'         => $canonical,
        'image'       => $_archOgImg,
        'breadcrumb'  => ['@type'=>'BreadcrumbList','itemListElement'=>$_bcItems],
    ],
];
if (count($_listItems)) {
    $_archPageGraph[] = [
        '@type'           => 'ItemList',
        'name'            => $placeLabel . $typePart . ' Market Report Archive',
        'url'             => $canonical,
        'numberOfItems'   => count($_listItems),
        'itemListElement' => $_listItems,
    ];
}
@endphp
<script type="application/ld+json">
{!! json_encode(['@context'=>'https://schema.org','@graph'=>$_archPageGraph], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG) !!}
</script>
<script type="application/ld+json">{!! json_encode($_archFaqSchema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG) !!}</script>
@if($_archAgentSchema)
<script type="application/ld+json">{!! json_encode($_archAgentSchema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG) !!}</script>
@endif
@endsection
@section('content')
@include('frontend.includes.header')

{{-- ── HERO ── --}}
<div class="mra-hero">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:12px;">
            <ol class="breadcrumb mra-breadcrumb">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item"><a href="/market-report">Market Reports</a></li>
                @if($city)<li class="breadcrumb-item @if(!$subarea && !$typeSlug)active @endif">
                    @if($subarea || $typeSlug)<a href="/market-report/{{ $citySlug }}">{{ $city }}</a>@else{{ $city }}@endif
                </li>@endif
                @if($subarea)<li class="breadcrumb-item @if(!$typeSlug)active @endif">
                    @if($typeSlug)<a href="/market-report/{{ $citySlug }}/{{ $subareaSlug }}">{{ $subarea }}</a>@else{{ $subarea }}@endif
                </li>@endif
                @if($typeSlug)<li class="breadcrumb-item active">{{ ucfirst($typeSlug) }}</li>@endif
            </ol>
        </nav>

        <h1 class="mra-hero__title">{{ $placeLabel }}{{ $typePart }} Market Reports{{ ($subarea && $city && $subarea !== $city) ? ' — ' . $city . ', BC' : '' }}</h1>
        <p class="mra-hero__sub">
            Monthly{{ $typePart ? strtolower($typePart) : ' real estate' }} market data for {{ $placeLabel }}
            @if($earliest) — going back to {{ $earliest }}@endif.
            Click any month for the full report.
        </p>

        @if($_latest && $_latestCond)
        <div class="mra-hero__stats">
            <span class="mra-cond-badge" style="background:{{ $_heroBadgeBg }};">{{ $_latestCond }}</span>
            <span class="mra-hero__stat-sep">Latest month ({{ $_latest->label }}):</span>
            @if($_latest->count_sold)
            <span class="mra-hero__stat"><strong>{{ number_format($_latest->count_sold) }}</strong> sold</span>
            @endif
            @if($_latest->avg_price)
            <span class="mra-hero__stat"><strong>${{ number_format($_latest->avg_price) }}</strong> avg price</span>
            @endif
            @if($_latest->avg_dom)
            <span class="mra-hero__stat"><strong>{{ $_latest->avg_dom }}d</strong> avg DOM</span>
            @endif
        </div>
        @endif
    </div>
</div>

<div class="container mra-body">

    @if($placeDescription)
    <div class="mra-place-desc">
        <p>{{ $placeDescription }}</p>
    </div>
    @endif

    @if($noDataMonth)
    <div class="mra-notice">
        No sold data found for <strong>{{ $noDataMonth }}</strong> in this area. Browse available months below.
    </div>
    @endif

    {{-- ── TYPE FILTER TABS ── --}}
    <div class="mra-tabs">
        <a href="/market-report/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}"
           class="mra-tab @if(!$typeSlug)mra-tab--active @endif">All Types</a>
        <a href="/market-report/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}/condos"
           class="mra-tab @if($typeSlug==='condos')mra-tab--active @endif">Condos</a>
        <a href="/market-report/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}/houses"
           class="mra-tab @if($typeSlug==='houses')mra-tab--active @endif">Houses</a>
        <a href="/market-report/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}/townhouses"
           class="mra-tab @if($typeSlug==='townhouses')mra-tab--active @endif">Townhouses</a>
    </div>

    {{-- ── ARCHIVE GRID ── --}}
    @if(count($months) > 0)
    <div class="mra-grid">
        @foreach($months as $m)
        @php
        $mUrl   = $canonical . '/' . $m->slug;
        $mCond  = $m->condition['label'] ?? '';
        $mColor = $m->condition['color'] ?? '#aaa';
        $mBadgeBg = $_condBgMap[$mCond] ?? '#888';
        $isLatest = $loop->first;
        @endphp
        <a href="{{ $mUrl }}" class="mra-card @if($isLatest)mra-card--latest @endif">
            @if($isLatest)<div class="mra-card__latest-ribbon">Latest</div>@endif
            <div class="mra-card__month">{{ $m->label }}</div>
            @if($mCond)
            <div class="mra-card__cond" style="background:{{ $mBadgeBg }};">{{ $mCond }}</div>
            @endif
            <div class="mra-card__stats">
                @if($m->count_sold)
                <div class="mra-card__stat">
                    <div class="mra-card__stat-val">{{ number_format($m->count_sold) }}</div>
                    <div class="mra-card__stat-lbl">Sold</div>
                </div>
                @endif
                @if($m->avg_price)
                <div class="mra-card__stat">
                    <div class="mra-card__stat-val">${{ number_format($m->avg_price) }}</div>
                    <div class="mra-card__stat-lbl">Avg Price</div>
                </div>
                @endif
                @if($m->avg_dom)
                <div class="mra-card__stat">
                    <div class="mra-card__stat-val">{{ $m->avg_dom }}d</div>
                    <div class="mra-card__stat-lbl">Avg DOM</div>
                </div>
                @endif
            </div>
            <div class="mra-card__cta">View Report →</div>
        </a>
        @endforeach
    </div>
    @else
    <div class="mra-empty">
        <div style="font-size:36px;margin-bottom:12px;">📊</div>
        <div style="font-size:16px;font-weight:600;color:#444;margin-bottom:6px;">No reports available yet</div>
        <div style="font-size:13px;color:#888;">Monthly reports for {{ $placeLabel }} will appear here as data is published.</div>
    </div>
    @endif

    {{-- ── FAQ ACCORDION ── --}}
    <div class="mra-faq">
        <h2 class="mra-faq__title">Frequently Asked Questions — {{ $_archFaqPlace }} Real Estate</h2>
        @foreach($_archFaqItems as $_faqI => $_faqItem)
        <div class="mra-faq__item">
            <button class="mra-faq__q" onclick="mraFaqToggle(this)" aria-expanded="{{ $_faqI === 0 ? 'true' : 'false' }}" type="button">
                {{ $_faqItem['q'] }}
                <span class="mra-faq__icon">{{ $_faqI === 0 ? '−' : '+' }}</span>
            </button>
            <div class="mra-faq__a" style="{{ $_faqI === 0 ? '' : 'display:none;' }}">
                <p>{!! $_faqItem['a'] !!}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── RELATED LINKS ── --}}
    <div class="mra-related">
        <span class="mra-related__label">Related:</span>
        <a href="/market-stats/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}{{ $typeSlug ? '/'.$typeSlug : '' }}" class="mra-related__link">Live {{ $placeLabel }} Market Stats →</a>
        @if($subareaSlug)<a href="/market-report/{{ $citySlug }}" class="mra-related__link">{{ $city }} Market Reports →</a>@endif
        <a href="/market-report" class="mra-related__link">All BC Market Reports →</a>
    </div>

    @if($city && $subarea && count($months))
    @php
    $_archLatest      = $months[0];
    $_archCond        = $_archLatest->condition ?? [];
    $_archMktType     = 'balanced';
    $_archLbl         = $_archCond['label'] ?? '';
    if ($_archLbl === "Strong Seller's Market")   $_archMktType = 'strong-sellers';
    elseif ($_archLbl === "Seller's Market")       $_archMktType = 'sellers';
    elseif ($_archLbl === "Buyer's Market")        $_archMktType = 'buyers';
    elseif ($_archLbl === "Balanced Market")       $_archMktType = 'balanced';
    $_archAvgPrice    = isset($_archLatest->avg_price) && $_archLatest->avg_price > 0 ? '$'.number_format($_archLatest->avg_price) : '';
    $_archAvgDom      = isset($_archLatest->avg_dom)   && $_archLatest->avg_dom   > 0 ? $_archLatest->avg_dom.'d' : '';
    $_archAbsorption  = isset($_archCond['absorption']) && $_archCond['absorption'] > 0 ? $_archCond['absorption'].'%' : '';
    $_archSold        = (int)($_archLatest->count_sold      ?? 0);
    $_archActive      = (int)($_archLatest->active_at_start ?? 0);
    $_archBuyers      = (int)(round(max(50, $_archActive * 15 + $_archSold * 30) / 10) * 10);
    @endphp
    <script src="https://admin.bccondosandhomes.com/widget/insight-bar.js"
        data-placement="main"
        data-neighbourhood="{{ $subarea }}"
        data-city="{{ $city }}"
        data-market-type="{{ $_archMktType }}"
        data-avg-price="{{ $_archAvgPrice }}"
        data-avg-dom="{{ $_archAvgDom }}"
        data-active-listings="{{ $_archActive }}"
        data-absorption-rate="{{ $_archAbsorption }}"
        data-sold-30d="{{ $_archSold }}"
        data-buyers="{{ number_format($_archBuyers) }}"
    ></script>
    @endif

</div>

<div class="container" style="padding-bottom:10px;">
    @include('frontend.includes.hani_attribution', ['attrCity' => ($city ?: null), 'attrSubarea' => ($subarea ?: null)])
</div>

<div class="listings-disclaimer">
    <div class="container">
        <p>Last Update: {{ \Carbon\Carbon::now()->format('m/d/Y') }} &nbsp;&nbsp;<strong>Disclaimer:</strong> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy.</p>
    </div>
</div>

<div class="container" style="padding-bottom:16px;">
    @php
        $_mraStripCtx  = trim(($subarea ? $subarea . ', ' : '') . ($city ?: 'BC'));
        $_mraTypePart  = ($typeLabel && $typeLabel !== 'Real Estate') ? ' ' . $typeLabel : '';
        $_mraStripName = $_mraStripCtx . $_mraTypePart . ' Listings';
        $_mraStripData = json_encode(array_filter([
            'cities'         => $city ?: null,
            'subareas'       => $subarea ?: null,
            'listing_status' => 'Active',
        ]));
    @endphp
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => $_mraStripCtx,
        'stripHeading'    => 'Get New Listing Alerts for ' . $_mraStripCtx,
        'stripSubtext'    => 'Follow the ' . $_mraStripCtx . ' market — get email alerts when new' . strtolower($_mraTypePart) . ' listings hit the MLS®.',
        'stripSearchName' => $_mraStripName,
        'stripSearchData' => $_mraStripData,
        'stripCity'       => $city ?: '',
        'stripModalId'    => 'mraAlert_' . md5(($citySlug ?? '') . ($subareaSlug ?? '') . ($typeSlug ?? '')),
    ])
</div>

@include('frontend.includes.hani_bubble')
@include('frontend.includes.footer_links')
@include('frontend.includes.footer')

@if($city && $subarea && count($months))
<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
    data-neighbourhood="{{ $subarea }}"
    data-city="{{ $city }}"
    data-market-type="{{ $_archMktType }}"
    data-avg-price="{{ $_archAvgPrice }}"
    data-avg-dom="{{ $_archAvgDom }}"
    data-active-listings="{{ $_archActive }}"
    data-absorption-rate="{{ $_archAbsorption }}"
    data-sold-30d="{{ $_archSold }}"
    data-buyers="{{ number_format($_archBuyers) }}"
></script>
@elseif($city && count($months))
@php
$_arcCityLatest  = $months[0];
$_arcCityLbl     = $_arcCityLatest->condition['label'] ?? '';
$_arcCityMktType = 'balanced';
if ($_arcCityLbl === "Strong Seller's Market")   $_arcCityMktType = 'strong-sellers';
elseif ($_arcCityLbl === "Seller's Market")       $_arcCityMktType = 'sellers';
elseif ($_arcCityLbl === "Buyer's Market")        $_arcCityMktType = 'buyers';
$_arcCityAvgPrice = isset($_arcCityLatest->avg_price) && $_arcCityLatest->avg_price > 0 ? '$'.number_format($_arcCityLatest->avg_price) : '';
$_arcCityAvgDom   = isset($_arcCityLatest->avg_dom)   && $_arcCityLatest->avg_dom   > 0 ? $_arcCityLatest->avg_dom.'d' : '';
$_arcCityActive   = (int)($_arcCityLatest->active_at_start ?? 0);
$_arcCitySold     = (int)($_arcCityLatest->count_sold ?? 0);
@endphp
<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
    data-city="{{ $city }}"
    data-market-type="{{ $_arcCityMktType }}"
    data-avg-price="{{ $_arcCityAvgPrice }}"
    data-avg-dom="{{ $_arcCityAvgDom }}"
    data-active-listings="{{ $_arcCityActive }}"
    data-sold-30d="{{ $_arcCitySold }}"
></script>
@else
<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
></script>
@endif

@endsection

@push('after-styles')
<style>
/* ── Market Report Archive ── */
.mra-hero {
    margin-top: 66px;
    background: #f7f4ef;
    border-bottom: 1px solid #e5e0d8;
    padding: 32px 0 24px;
}
.mra-breadcrumb {
    background: none;
    padding: 0;
    margin: 0 0 14px;
    font-size: 12px;
}
.mra-breadcrumb li + li::before { content: "›"; color: #999; }
.mra-hero__title {
    font-size: 26px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 8px;
    line-height: 1.25;
}
.mra-hero__sub {
    font-size: 14px;
    color: #666;
    margin: 0 0 14px;
    line-height: 1.6;
}
.mra-hero__stats {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 13px;
    color: #555;
}
.mra-hero__stat-sep { color: #888; }
.mra-hero__stat { font-size: 13px; color: #444; }
.mra-hero__stat strong { color: #1a1a1a; }
.mra-cond-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    border-radius: 3px;
    padding: 3px 9px;
    white-space: nowrap;
    letter-spacing: 0.02em;
}

/* ── Body ── */
.mra-body { padding-top: 24px; padding-bottom: 48px; }

.mra-notice {
    background: #fff8e1;
    border-left: 4px solid #f39c12;
    border-radius: 4px;
    padding: 12px 18px;
    font-size: 14px;
    color: #666;
    margin-bottom: 20px;
}

/* ── Type tabs ── */
.mra-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}
.mra-tab {
    display: inline-block;
    padding: 6px 16px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid #ccc;
    border-radius: 20px;
    color: #555;
    text-decoration: none;
    background: #fff;
    transition: all 0.15s;
}
.mra-tab:hover, .mra-tab--active {
    background: #2c6fad;
    color: #fff;
    border-color: #2c6fad;
    text-decoration: none;
}

/* ── Card grid ── */
.mra-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 14px;
    margin-bottom: 28px;
}
.mra-card {
    display: block;
    position: relative;
    background: #fff;
    border: 1px solid #e8e4dd;
    border-radius: 8px;
    padding: 18px 18px 14px;
    text-decoration: none;
    color: inherit;
    transition: box-shadow 0.18s, border-color 0.18s, transform 0.18s;
    overflow: hidden;
}
.mra-card:hover {
    box-shadow: 0 4px 18px rgba(0,0,0,0.10);
    border-color: #2c6fad;
    transform: translateY(-2px);
    text-decoration: none;
    color: inherit;
}
.mra-card--latest {
    border-color: #2c6fad;
    background: #f5f9ff;
}
.mra-card__latest-ribbon {
    position: absolute;
    top: 10px;
    right: -22px;
    background: #2c6fad;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 3px 28px;
    transform: rotate(35deg);
}
.mra-card__month {
    font-size: 15px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
}
.mra-card__cond {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    color: #fff;
    border-radius: 3px;
    padding: 2px 7px;
    margin-bottom: 12px;
    white-space: nowrap;
}
.mra-card__stats {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}
.mra-card__stat { text-align: left; }
.mra-card__stat-val {
    font-size: 13px;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1.2;
}
.mra-card__stat-lbl {
    font-size: 10px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.mra-card__cta {
    font-size: 12px;
    font-weight: 600;
    color: #2c6fad;
    border-top: 1px solid #f0ebe3;
    padding-top: 10px;
    margin-top: auto;
}
.mra-card:hover .mra-card__cta { color: #1a5a99; }

/* ── Empty state ── */
.mra-empty {
    text-align: center;
    padding: 56px 0;
    color: #aaa;
}

/* ── Place description ── */
.mra-place-desc {
    background: #fafaf8;
    border-left: 3px solid #c8b99a;
    border-radius: 4px;
    padding: 14px 18px;
    margin-bottom: 22px;
    font-size: 14px;
    color: #555;
    line-height: 1.75;
}
.mra-place-desc p { margin: 0; }

/* ── FAQ accordion ── */
.mra-faq {
    margin: 32px 0 24px;
    border-top: 2px solid #f0ebe3;
    padding-top: 24px;
}
.mra-faq__title {
    font-size: 17px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 16px;
}
.mra-faq__item {
    border: 1px solid #e8e4dd;
    border-radius: 6px;
    margin-bottom: 8px;
    overflow: hidden;
}
.mra-faq__q {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fafaf8;
    border: none;
    padding: 14px 18px;
    font-size: 14px;
    font-weight: 600;
    color: #1a1a1a;
    text-align: left;
    cursor: pointer;
    gap: 12px;
}
.mra-faq__q:hover { background: #f5f0ea; }
.mra-faq__icon {
    flex-shrink: 0;
    font-size: 18px;
    font-weight: 400;
    color: #888;
    line-height: 1;
}
.mra-faq__a {
    padding: 0 18px 14px;
    font-size: 14px;
    color: #555;
    line-height: 1.75;
}
.mra-faq__a p { margin: 10px 0 0; }

/* ── Related links ── */
.mra-related {
    background: #f7f4ef;
    border-radius: 6px;
    padding: 14px 20px;
    font-size: 13px;
    color: #555;
    margin-bottom: 28px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px 0;
}
.mra-related__label { font-weight: 700; margin-right: 8px; }
.mra-related__link {
    color: #2c6fad;
    margin-left: 14px;
    text-decoration: none;
}
.mra-related__link:hover { text-decoration: underline; }

/* ── Responsive ── */
@media (max-width: 600px) {
    .mra-hero__title { font-size: 20px; }
    .mra-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
    .mra-hero__stats { gap: 6px; }
}
@media (max-width: 400px) {
    .mra-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@push('after-scripts')
<script>
window.BCTrack = window.BCTrack || {};
window.BCTrack.pageType    = "market_report_archive";
window.BCTrack.reportMonth = "{{ date('Y-m') }}";
function mraFaqToggle(btn) {
    var ans  = btn.nextElementSibling;
    var icon = btn.querySelector('.mra-faq__icon');
    var open = ans.style.display !== 'none';
    ans.style.display = open ? 'none' : 'block';
    icon.textContent  = open ? '+' : '−';
    btn.setAttribute('aria-expanded', open ? 'false' : 'true');
}
</script>
@endpush
