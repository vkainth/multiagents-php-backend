@extends('frontend.layouts.default')
@php
$placeLabel  = $subarea ? "{$subarea}, {$city}" : ($city ?: 'BC');
$typeLabel   = $listingtype === 'Apartment' ? 'Condo' : ($listingtype === 'House' ? 'House' : ($listingtype === 'Townhouse' ? 'Townhouse' : 'Real Estate'));
$condLabel   = $marketCondition['label'] ? ' — ' . $marketCondition['label'] : '';
$currentMonth = date('F Y');
$metaTitle   = "{$currentMonth} {$placeLabel} {$typeLabel} Housing Market Report{$condLabel} | Hani & Les";
$metaDesc    = ($marketCondition['label'] ? $marketCondition['label'] . ' — ' : '') . 'Live ' . $placeLabel . ' real estate market data: ' . $marketCondition['current_active'] . ' active listings, ' . $marketCondition['sold_30d'] . ' sold in the last 30 days';
if ($marketCondition['avg_sold_30d']) $metaDesc .= ', avg $' . number_format($marketCondition['avg_sold_30d']);
$metaDesc .= '. Updated daily from MLS® records.';
$canonicalUrl = 'https://www.bccondosandhomes.com/market-stats'
    . ($citySlug        ? '/'.$citySlug        : '')
    . ($subareaSlug     ? '/'.$subareaSlug     : '')
    . ($listtypeSlug    ? '/'.$listtypeSlug    : '');

$_srchBase   = '/search-listings'
    . ($citySlug    ? '/'.$citySlug    : '')
    . ($subareaSlug ? '/'.$subareaSlug : '')
    . ($listtypeSlug ? '/'.$listtypeSlug : '');
$_activeUrl  = $_srchBase . '?listing_status=Active';
$_soldUrl    = $_srchBase . '?listing_status=Sold';
@endphp
@section('title'){{ $metaTitle }}@endsection
@section('meta_description'){{ $metaDesc }}@endsection
@section('meta')
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDesc }}">
<meta property="article:modified_time" content="{{ date('Y-m-d') }}">
@php
$faqEntities = [];
$mc = $marketCondition;
$mo = date('F Y');
$hasAvg = !empty($mc['avg_sold_30d']);
$hasAvg90 = !empty($mc['avg_sold_90d']);
$typePrefix = $listingtype ? "{$typeLabel} " : '';
$_trendDir = $mc['price_trend'] > 0 ? 'rising' : ($mc['price_trend'] < 0 ? 'falling' : 'flat');
$_trendAbs = abs($mc['price_trend']);
$_condLabel = $mc['label'] ?? 'a mixed market';

// Shared FAQ helper text strings
$_absExplain = "The absorption rate measures how quickly available homes are selling. It is calculated by dividing the number of homes sold in the last 30 days by the number of active listings. An absorption rate above 20% typically indicates a seller's market; below 12% indicates a buyer's market; between 12–20% is balanced.";
$_domExplain = $mc['avg_dom'] ? "Properties in this market are currently selling in an average of {$mc['avg_dom']} days." : "Days on market varies by price range and property type.";

if ($city && $subarea) {
    $place = "{$subarea}, {$city}";
    $typeWord = $listingtype ? $typeLabel : 'real estate';
    $faqEntities[] = ['@type'=>'Question','name'=>"What is the {$subarea}, {$city} {$typePrefix}housing market like in {$mo}?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"In {$mo}, the {$subarea} {$typePrefix}market in {$city} shows {$mc['current_active']} active listings and {$mc['sold_30d']} sold in the last 30 days. Market condition: {$_condLabel}. Absorption rate: {$mc['absorption_rate']}%".($hasAvg ? ', avg sold price $'.number_format($mc['avg_sold_30d']) : '').". Data is updated daily from MLS® records."]];
    if ($hasAvg && $hasAvg90) {
        $faqEntities[] = ['@type'=>'Question','name'=>"What is the average sold price in {$subarea}, {$city}?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"The average sold price in {$subarea}, {$city} over the last 30 days is $".number_format($mc['avg_sold_30d'])." and $".number_format($mc['avg_sold_90d'])." over 90 days. Data is updated daily from MLS® board records."]];
    }
    $faqEntities[] = ['@type'=>'Question','name'=>"Is {$subarea} a buyer's or seller's market?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"Based on current absorption rate ({$mc['absorption_rate']}%) and average days on market ({$mc['avg_dom']} days), {$subarea} is currently {$_condLabel}."]];
    if ($hasAvg) { $faqEntities[] = ['@type'=>'Question','name'=>"What is the average home price in {$subarea}, {$city} in {$mo}?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"In {$mo}, the average sold price in {$subarea}, {$city} is $".number_format($mc['avg_sold_30d'])." based on MLS® data from the last 30 days. There are currently {$mc['current_active']} active listings with an absorption rate of {$mc['absorption_rate']}%, indicating {$_condLabel}."]]; }
    if ($mc['price_trend'] != 0) {
        $faqEntities[] = ['@type'=>'Question','name'=>"Are {$typeWord} prices in {$subarea}, {$city} going up or down?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"The 30-day average sold price in {$subarea} is ".($mc['price_trend'] > 0 ? 'up' : 'down')." {$_trendAbs}% compared to the prior 90-day average".($hasAvg ? ', sitting at $'.number_format($mc['avg_sold_30d']) : '').". Prices are currently {$_trendDir} in this neighbourhood."]];
    }
    if ($mc['avg_dom']) {
        $faqEntities[] = ['@type'=>'Question','name'=>"How long does it take to sell a {$typeWord} in {$subarea}, {$city}?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"In {$subarea}, {$city}, homes are currently selling in an average of {$mc['avg_dom']} days on market. {$_domExplain} Properties that are well-priced in {$_condLabel} conditions tend to sell faster."]];
    }
    $faqEntities[] = ['@type'=>'Question','name'=>"What does the absorption rate mean for {$subarea}?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"The current absorption rate in {$subarea} is {$mc['absorption_rate']}%. {$_absExplain} At {$mc['absorption_rate']}%, {$subarea} is classified as {$_condLabel}."]];
    $faqEntities[] = ['@type'=>'Question','name'=>"Is it a good time to buy a {$typeWord} in {$subarea}, {$city}?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"In {$_condLabel} conditions like {$subarea} currently shows, buyers ".($mc['label'] === "Buyer's Market" ? "have strong negotiating power and can take time to find the right property. Prices may be negotiable and conditions are more common." : ($mc['label'] === "Balanced Market" ? "have reasonable choices and moderate negotiating room. Neither side holds a major advantage." : "should be prepared to act quickly and present competitive offers. Inventory is limited and well-priced properties move fast.")).". Consult a local Realtor for up-to-date guidance on buying in {$subarea}."]];
    $faqEntities[] = ['@type'=>'Question','name'=>"How do I make an offer in the {$subarea} market?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"In {$_condLabel} conditions, ".($mc['label'] === "Buyer's Market" ? "buyers typically have room to negotiate below asking price and include subject conditions (financing, inspection). There is less urgency to move quickly." : ($mc['label'] === "Balanced Market" ? "offers close to asking price with standard conditions are common. Negotiating room exists, but serious buyers should avoid lowballing on well-priced properties." : "buyers often need to offer at or above asking price, minimize conditions, and be prepared for multiple-offer scenarios. Pre-approval and a clear budget are essential before writing an offer.")).". Contact Hani & Les for local expertise on making an offer in {$subarea}."]];
    $faqEntities[] = ['@type'=>'Question','name'=>"How does the {$subarea} market compare to the rest of {$city}?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"The {$subarea} neighbourhood shows an absorption rate of {$mc['absorption_rate']}% and average days on market of {$mc['avg_dom']} days. Compare this with other {$city} neighbourhoods on the {$city} market statistics page to see how {$subarea} ranks across all subareas."]];
} elseif ($city) {
    $marketWord = $listingtype ? $typeLabel : 'real estate';
    $typedPlural = $listingtype ? strtolower($typeLabel).'s' : 'properties';
    $faqEntities[] = ['@type'=>'Question','name'=>"What is the {$city} {$typePrefix}housing market like in {$mo}?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"In {$mo}, the {$city} {$marketWord} market shows {$mc['current_active']} active listings and {$mc['sold_30d']} sold in the last 30 days. Market condition: {$_condLabel}. Absorption rate: {$mc['absorption_rate']}%".($hasAvg ? ', avg sold price $'.number_format($mc['avg_sold_30d']) : '').". Data is updated daily from MLS® records."]];
    $faqEntities[] = ['@type'=>'Question','name'=>"How is the real estate market in {$city}, BC?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"The {$city} real estate market currently shows {$mc['current_active']} active listings and {$mc['sold_30d']} sold in the last 30 days. Market condition: {$_condLabel}. Absorption rate: {$mc['absorption_rate']}%."]];
    if ($hasAvg && $hasAvg90) {
        $faqEntities[] = ['@type'=>'Question','name'=>"What is the average sold price in {$city}?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"The average sold price in {$city} over the last 30 days is $".number_format($mc['avg_sold_30d'])." and $".number_format($mc['avg_sold_90d'])." over 90 days. Historical price trend charts are shown below."]];
    }
    $faqEntities[] = ['@type'=>'Question','name'=>"Is {$city} a buyer's or seller's market?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"Based on current absorption rate ({$mc['absorption_rate']}%) and average days on market ({$mc['avg_dom']} days), {$city} is currently {$_condLabel}."]];
    if ($hasAvg) { $faqEntities[] = ['@type'=>'Question','name'=>"What is the average home price in {$city} in {$mo}?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"In {$mo}, the average sold price in {$city} is $".number_format($mc['avg_sold_30d'])." based on MLS® data from the last 30 days. There are {$mc['current_active']} active listings with an absorption rate of {$mc['absorption_rate']}%, indicating {$_condLabel}."]]; }
    if ($mc['price_trend'] != 0) {
        $faqEntities[] = ['@type'=>'Question','name'=>"Are {$marketWord} prices in {$city} going up or down?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"The 30-day average sold price in {$city} is ".($mc['price_trend'] > 0 ? 'up' : 'down')." {$_trendAbs}% compared to the 90-day average".($hasAvg ? ', at $'.number_format($mc['avg_sold_30d']).' currently' : '').". {$city} {$marketWord} prices are trending {$_trendDir} as of {$mo}."]];
    }
    if ($mc['avg_dom']) {
        $faqEntities[] = ['@type'=>'Question','name'=>"How long does it take to sell a {$marketWord} in {$city}?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"In {$city}, {$typedPlural} are currently selling in an average of {$mc['avg_dom']} days on market as of {$mo}. In {$_condLabel} conditions, well-priced homes sell faster than the average. Compare neighbourhood-level days on market using the subareas table on this page."]];
    }
    $faqEntities[] = ['@type'=>'Question','name'=>"What does the absorption rate mean for {$city}?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"The current absorption rate in {$city} is {$mc['absorption_rate']}%. {$_absExplain} At {$mc['absorption_rate']}%, the {$city} market is classified as {$_condLabel}."]];
    $faqEntities[] = ['@type'=>'Question','name'=>"Is it a good time to buy a {$marketWord} in {$city} right now?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"In {$_condLabel} conditions like {$city} currently shows, buyers ".($mc['label'] === "Buyer's Market" ? "have the advantage — more inventory, more negotiating room, and less competition. Subject conditions on financing and inspection are easier to include." : ($mc['label'] === "Balanced Market" ? "have reasonable options and moderate leverage. Prices are stable and offers near asking are typical." : "face a competitive environment. Acting quickly, having financing pre-approved, and making clean offers are important strategies.")).". Browse current {$city} ".strtolower($typeLabel ?: 'real estate')." listings to assess what's available at your budget."]];
    $faqEntities[] = ['@type'=>'Question','name'=>"How do I negotiate when buying a {$marketWord} in {$city}?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"In {$_condLabel} conditions in {$city}, ".($mc['label'] === "Buyer's Market" ? "buyers have significant room to negotiate. Offering below asking, requesting extended subjects, and negotiating inclusions are all reasonable strategies." : ($mc['label'] === "Balanced Market" ? "negotiations are balanced. Reasonable offers within 3–5% of asking are typical. Subject conditions are generally accepted." : "sellers hold the advantage. Competitive buyers often offer at or above asking with few or no conditions. A local Realtor's guidance on current offer norms is essential.")).". Contact Hani & Les for personalized advice on buying in {$city}."]];
    $faqEntities[] = ['@type'=>'Question','name'=>"Which neighbourhoods in {$city} have the strongest real estate market?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"Absorption rates and days-on-market vary significantly by neighbourhood within {$city}. The neighbourhood breakdown table on this page shows the current absorption rate, average sold price, and market condition for each {$city} subarea — sorted and compared side by side."]];
} else {
    $faqEntities[] = ['@type'=>'Question','name'=>'How is the BC real estate market right now?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Metro Vancouver and Fraser Valley market statistics are available on this page. Select a city above to view absorption rate, average sold price, days on market, and recent sales data for your area.']];
    $faqEntities[] = ['@type'=>'Question','name'=>'Which BC cities have the most real estate activity?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Vancouver, Burnaby, Richmond, Surrey, Coquitlam, and North Vancouver are among the most active markets in BC. Select any city above to view detailed market statistics.']];
    $faqEntities[] = ['@type'=>'Question','name'=>"What is a seller's market vs buyer's market in BC real estate?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"A seller's market in BC real estate occurs when the absorption rate (ratio of sold to active listings) exceeds 15-20% and average days on market drops below 30-45 days. Use the market stats pages on this site to track current conditions city by city."]];
    $faqEntities[] = ['@type'=>'Question','name'=>"What does absorption rate mean in BC real estate?",'acceptedAnswer'=>['@type'=>'Answer','text'=>$_absExplain]];
    $faqEntities[] = ['@type'=>'Question','name'=>"How long does it take to sell a home in Metro Vancouver?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"Days on market varies by city, property type, and price range across Metro Vancouver. Select a specific city on this page to see the current average days on market for that area, updated daily from MLS® records."]];
    $faqEntities[] = ['@type'=>'Question','name'=>"Are BC home prices going up or down?",'acceptedAnswer'=>['@type'=>'Answer','text'=>"Price trends vary by city and property type across BC. Each city and neighbourhood page on this site shows the 30-day vs 90-day average price trend, so you can see whether prices are rising or falling in your area of interest. Updated daily from MLS® records."]];
}
$faqSchema = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>$faqEntities];

// StatisticalReport (NewsArticle subtype) schema
$statReportSchema = [
    '@context'      => 'https://schema.org',
    '@type'         => 'NewsArticle',
    'headline'      => $metaTitle,
    'description'   => $metaDesc,
    'dateModified'  => date('Y-m-d'),
    'datePublished' => date('Y-m-d'),
    'author'        => [['@type'=>'Person','name'=>'Hani Faraj'],['@type'=>'Person','name'=>'Les Twarog']],
    'publisher'     => ['@type'=>'Organization','name'=>'BC Condos And Homes — Re/Max Crest Realty','url'=>'https://www.bccondosandhomes.com'],
    'about'         => array_filter([
        ['@type'=>'Place','name'=>$placeLabel],
        $listingtype ? ['@type'=>'Thing','name'=>$typeLabel . ' Real Estate'] : null,
    ]),
    'keywords'      => implode(', ', array_filter([
        $city ? $city . ' real estate' : 'BC real estate',
        $city && $listingtype ? $city . ' ' . strtolower($typeLabel) . ' market' : null,
        $mc['label'] ? $mc['label'] : null,
        'absorption rate', 'average sold price', 'days on market', 'MLS statistics',
    ])),
    'mainEntityOfPage' => ['@type'=>'WebPage','@id'=>$canonicalUrl],
];
@endphp
<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
<script type="application/ld+json">{!! json_encode($statReportSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
@section('content')

@include('frontend.includes.header')

{{-- Hero / SEO intro --}}
<div class="stats-seo-intro page-main" style="padding:28px 0 0;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:10px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item"><a href="/market-stats">Market Statistics</a></li>
                @if($city)<li class="breadcrumb-item @if(!$subarea && !$listtypeSlug)active @endif">
                    @if($subarea || $listtypeSlug)<a href="/market-stats/{{ $citySlug }}">{{ $city }}</a>@else{{ $city }}@endif
                </li>@endif
                @if($subarea)<li class="breadcrumb-item @if(!$listtypeSlug)active @endif">
                    @if($listtypeSlug)<a href="/market-stats/{{ $citySlug }}/{{ $subareaSlug }}">{{ $subarea }}</a>@else{{ $subarea }}@endif
                </li>@endif
                @if($listtypeSlug)<li class="breadcrumb-item active">{{ ucfirst($listtypeSlug) }}</li>@endif
            </ol>
        </nav>

        <h1 style="font-size:24px;font-weight:700;margin-bottom:8px;color:#2c2c2c;">
            @if($city && $subarea && $listingtype)
                {{ date('F Y') }} {{ $subarea }}, {{ $city }} {{ $typeLabel }} Housing Market Report
            @elseif($city && $subarea)
                {{ date('F Y') }} {{ $subarea }}, {{ $city }} Real Estate Market Statistics
            @elseif($city && $listingtype)
                {{ date('F Y') }} {{ $city }} {{ $typeLabel }} Housing Market Report
            @elseif($city)
                {{ date('F Y') }} {{ $city }} Real Estate Market Statistics
            @else
                {{ date('F Y') }} BC Real Estate Market Statistics
            @endif
        </h1>

        <p style="font-size:15px;color:#555;max-width:820px;margin-bottom:14px;line-height:1.65;">
            @if($city && $subarea)
                Live market data for <strong>{{ $subarea }}</strong>{{ $listingtype ? " {$typeLabel}s" : '' }} in {{ $city }}. Track absorption rate, average sold prices, days on market, and historical sales trends — updated daily from MLS® records.
            @elseif($city)
                Comprehensive real estate market statistics for <strong>{{ $city }}</strong>{{ $listingtype ? " {$typeLabel}s" : '' }}, BC — including absorption rate, average sold price, days on market, and neighbourhood breakdowns. Select a subarea below for neighbourhood-level data.
            @else
                Real-time BC real estate market statistics for Metro Vancouver and the Fraser Valley — tracking <a href="/market-stats/vancouver">Vancouver</a>, <a href="/market-stats/burnaby">Burnaby</a>, <a href="/market-stats/burnaby/metrotown">Metrotown</a>, <a href="/market-stats/north-vancouver">North Vancouver</a>, <a href="/market-stats/richmond">Richmond</a>, <a href="/market-stats/surrey">Surrey</a> and more. Updated daily from MLS® board records.
            @endif
        </p>

        @if($city)
        <p style="font-size:13px;color:#666;margin-bottom:4px;">
            Browse active listings:
            @if($subarea)
                <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}" style="margin-right:12px;">All {{ $subarea }} Listings</a>
                <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}?type=Apartment" style="margin-right:12px;">Condos</a>
                <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}?type=House" style="margin-right:12px;">Houses</a>
                <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}?type=Townhouse">Townhouses</a>
            @else
                <a href="/search-listings/{{ $citySlug }}" style="margin-right:12px;">All {{ $city }} Listings</a>
                <a href="/search-listings/{{ $citySlug }}?type=Apartment" style="margin-right:12px;">Condos</a>
                <a href="/search-listings/{{ $citySlug }}?type=House" style="margin-right:12px;">Houses</a>
                <a href="/search-listings/{{ $citySlug }}?type=Townhouse">Townhouses</a>
            @endif
        </p>
        @endif
    </div>
</div>

<div class="container" style="min-height:50vh;padding-bottom:40px;">

    {{-- Market Area & Type Selector --}}
    <div class="row" style="margin-top:20px;">
        <div class="col-md-12">
            <div style="background:#f7f4ef;border:1px solid #e2dbd2;border-radius:6px;padding:16px 20px;display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
                <div style="flex:0 0 auto;">
                    <label style="font-size:12px;font-weight:600;color:#666;display:block;margin-bottom:4px;">CITY</label>
                    <select id="city-select" onchange="window.location.href=this.value" style="font-size:14px;padding:6px 10px;border:1px solid #ccc;border-radius:4px;background:#fff;min-width:160px;">
                        <option value="/market-stats" @if(!$city) selected @endif>All BC</option>
                        @foreach($cities as $c)
                        <option value="/market-stats/{{ App\Helpers\Helper::enslugPlace($c->place) }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}{{ $listtypeSlug ? '/'.$listtypeSlug : '' }}" @if($city === $c->place) selected @endif>{{ $c->label ?? $c->place }}</option>
                        @endforeach
                    </select>
                </div>
                @if($subareas && count($subareas))
                <div style="flex:0 0 auto;">
                    <label style="font-size:12px;font-weight:600;color:#666;display:block;margin-bottom:4px;">NEIGHBOURHOOD</label>
                    <select id="subarea-select" onchange="window.location.href=this.value" style="font-size:14px;padding:6px 10px;border:1px solid #ccc;border-radius:4px;background:#fff;min-width:160px;">
                        <option value="/market-stats/{{ $citySlug }}{{ $listtypeSlug ? '/'.$listtypeSlug : '' }}" @if(!$subarea) selected @endif>All {{ $city }}</option>
                        @foreach($subareas as $sa)
                        <option value="/market-stats/{{ $citySlug }}/{{ App\Helpers\Helper::enslugPlace($sa->place) }}{{ $listtypeSlug ? '/'.$listtypeSlug : '' }}" @if($subarea === $sa->place) selected @endif>{{ $sa->label ?? $sa->place }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div style="flex:0 0 auto;">
                    <label style="font-size:12px;font-weight:600;color:#666;display:block;margin-bottom:4px;">TYPE</label>
                    <div style="display:flex;gap:6px;">
                        <a href="/market-stats/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}" class="mstab @if(!$listtypeSlug)mstab-active @endif">All</a>
                        <a href="/market-stats/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}/condos" class="mstab @if($listtypeSlug==='condos')mstab-active @endif">Condos</a>
                        <a href="/market-stats/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}/houses" class="mstab @if($listtypeSlug==='houses')mstab-active @endif">Houses</a>
                        <a href="/market-stats/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}/townhouses" class="mstab @if($listtypeSlug==='townhouses')mstab-active @endif">Townhouses</a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- Contextual House Market Guide CTA (shown when viewing house stats for a specific city) --}}
    @if($citySlug && $listtypeSlug === 'houses')
    <div style="background:#f0f4f8;border:1px solid #c9d8e8;border-radius:5px;padding:10px 18px;margin-top:12px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <span style="font-size:13px;color:#555;">Want a full house market analysis?</span>
        @if($subareaSlug)
        <a href="/houses/{{ $citySlug }}/{{ $subareaSlug }}/" style="font-weight:700;color:#2c6fad;font-size:13px;text-decoration:none;">{{ ucwords(str_replace('-',' ',$subareaSlug)) }} House Market Guide &rsaquo;</a>
        <span style="color:#ccc;">|</span>
        @endif
        <a href="/houses/{{ $citySlug }}/" style="font-weight:700;color:#2c6fad;font-size:13px;text-decoration:none;">{{ ucwords(str_replace('-',' ',$citySlug)) }} House Market Guide &rsaquo;</a>
        <a href="/houses/" style="color:#777;font-size:12px;text-decoration:none;">All Metro Vancouver Houses</a>
    </div>
    @endif

    {{-- Verdict Card + 4-tile Summary --}}
    @if($marketCondition['label'])
    <div class="row" style="margin-top:22px;">
        <div class="col-md-4">
            <div class="verdict-card {{ $marketCondition['class'] }}" style="border-left:6px solid {{ $marketCondition['color'] }};background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px 22px;">
                <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;">Market Verdict</div>
                <div style="font-size:22px;font-weight:700;color:{{ $marketCondition['color'] }};margin-bottom:10px;">{{ $marketCondition['label'] }}</div>
                <div style="font-size:13px;color:#555;line-height:1.8;">
                    <div>Absorption Rate: <strong>{{ $marketCondition['absorption_rate'] }}%</strong></div>
                    <div>Avg Days on Market: <strong>{{ $marketCondition['avg_dom'] }} days</strong></div>
                    @if($marketCondition['price_trend'] != 0)
                    <div>30d vs 90d Price Trend: <strong style="color:{{ $marketCondition['price_trend'] > 0 ? '#27ae60' : '#c0392b' }}">{{ $marketCondition['price_trend'] > 0 ? '+' : '' }}{{ $marketCondition['price_trend'] }}%</strong></div>
                    @endif
                </div>
                @php
                $guidanceMap = [
                    "Strong Seller's Market" => "What this means: High demand and low inventory — homes sell quickly and often above list price. Buyers should act fast and expect competition.",
                    "Seller's Market"        => "What this means: More buyers than sellers — prices are firm and listings move quickly. Buyers may need to be flexible on conditions.",
                    "Balanced Market"        => "What this means: Supply and demand are roughly in balance — reasonable negotiating room exists for both buyers and sellers.",
                    "Buyer's Market"         => "What this means: More supply than demand — buyers have greater choice and negotiating power, and prices may be more flexible.",
                ];
                @endphp
                @if(isset($guidanceMap[$marketCondition['label']]))
                <div style="margin-top:10px;font-size:12px;color:#666;line-height:1.6;border-top:1px solid #eee;padding-top:8px;">{{ $guidanceMap[$marketCondition['label']] }}</div>
                @endif
            </div>
        </div>
        <div class="col-md-8">
            <div class="row">
                <div class="col-sm-3 col-xs-6" style="margin-bottom:14px;">
                    <a href="{{ $_activeUrl }}" style="text-decoration:none;display:block;">
                    <div class="stat-tile" style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px;text-align:center;">
                        <div style="font-size:26px;font-weight:700;color:#2c6fad;">{{ number_format($marketCondition['current_active']) }}</div>
                        <div style="font-size:12px;color:#888;margin-top:4px;">Active Listings ↗</div>
                    </div>
                    </a>
                </div>
                <div class="col-sm-3 col-xs-6" style="margin-bottom:14px;">
                    <a href="{{ $_soldUrl }}" style="text-decoration:none;display:block;">
                    <div class="stat-tile" style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px;text-align:center;">
                        <div style="font-size:26px;font-weight:700;color:#2c6fad;">{{ number_format($marketCondition['sold_30d']) }}</div>
                        <div style="font-size:12px;color:#888;margin-top:4px;">Sold (30 Days) ↗</div>
                    </div>
                    </a>
                </div>
                <div class="col-sm-3 col-xs-6" style="margin-bottom:14px;">
                    <div class="stat-tile" style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px;text-align:center;">
                        <div style="font-size:20px;font-weight:700;color:#333;">@if($marketCondition['avg_sold_30d'])${{ number_format($marketCondition['avg_sold_30d']) }}@else—@endif</div>
                        <div style="font-size:12px;color:#888;margin-top:4px;">Avg Price (30d)</div>
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6" style="margin-bottom:14px;">
                    <div class="stat-tile" style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px;text-align:center;">
                        <div style="font-size:26px;font-weight:700;color:#333;">{{ $marketCondition['avg_dom'] ?: '—' }}</div>
                        <div style="font-size:12px;color:#888;margin-top:4px;">Avg Days on Market</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Server-rendered editorial paragraph --}}
    @if($marketCondition['label'])
    <div class="row" style="margin-top:20px;">
        <div class="col-md-12">
            <div style="background:#fafaf8;border-left:4px solid {{ $marketCondition['color'] }};border-radius:4px;padding:14px 18px;font-size:14px;color:#444;line-height:1.75;">
                @php
                $editorialParts = [];
                if ($marketCondition['current_active']) $editorialParts[] = '<strong>' . number_format($marketCondition['current_active']) . ' active listings</strong> currently on the market';
                if ($marketCondition['sold_30d'])       $editorialParts[] = '<strong>' . number_format($marketCondition['sold_30d']) . ' properties sold</strong> in the last 30 days';
                if ($marketCondition['avg_sold_30d'])   $editorialParts[] = 'an average sold price of <strong>$' . number_format($marketCondition['avg_sold_30d']) . '</strong>';
                if ($marketCondition['avg_dom'])        $editorialParts[] = 'properties selling in <strong>' . $marketCondition['avg_dom'] . ' days</strong> on average';
                if ($marketCondition['absorption_rate'])$editorialParts[] = 'an absorption rate of <strong>' . $marketCondition['absorption_rate'] . '%</strong>';
                if ($marketCondition['price_trend'] != 0) {
                    $trendDir = $marketCondition['price_trend'] > 0 ? 'up' : 'down';
                    $editorialParts[] = 'prices trending <strong>' . $trendDir . ' ' . abs($marketCondition['price_trend']) . '%</strong> vs the prior 90-day average';
                }
                $placeStr = $subarea ? "{$subarea}, {$city}" : ($city ?: 'BC');
                $typeStr  = $listingtype ? ($listingtype === 'Apartment' ? 'condo' : strtolower($listingtype)) . ' ' : '';
                @endphp
                The <strong>{{ $placeStr }}</strong> {{ $typeStr }}real estate market is currently a <strong style="color:{{ $marketCondition['color'] }}">{{ $marketCondition['label'] }}</strong>@if(count($editorialParts)), with {!! implode(', ', $editorialParts) !!}@endif. All data is sourced daily from MLS® board records.
            </div>
        </div>
    </div>
    @endif

    {{-- Buyer/Seller Guidance Section --}}
    @if($marketCondition['label'] && $city)
    @php
    $_placeStr2 = $subarea ? "{$subarea}, {$city}" : $city;
    $_typeStr2  = $listingtype ? strtolower($typeLabel) : 'real estate';
    $_typePlural = $listingtype ? strtolower($typeLabel).'s' : 'properties';
    if ($marketCondition['label'] === "Strong Seller's Market") {
        $_buyerCopy = "Buying a {$_typeStr2} in {$_placeStr2} right now means competing in one of the most active markets in the region. Inventory is low and demand is high — homes sell quickly and often above the list price. Buyers should have financing pre-approved before searching, be prepared to move fast when the right property appears, and consider limiting or waiving conditions only with careful legal and financial advice. Multiple-offer situations are common in strong seller's markets, so working with an experienced local Realtor is essential.";
        $_sellerCopy = "Selling a {$_typeStr2} in {$_placeStr2} right now is highly favourable. Demand significantly outpaces supply, which means well-priced listings attract multiple offers and often sell above asking. Days on market are short — currently averaging {$mc['avg_dom']} days. Sellers can be selective on terms and conditions. Pricing slightly below market value to trigger a bidding war is a strategy worth discussing with your Realtor in {$_placeStr2}.";
    } elseif ($marketCondition['label'] === "Seller's Market") {
        $_buyerCopy = "Buying a {$_typeStr2} in {$_placeStr2} right now requires preparation and decisiveness. With more buyers than available inventory, desirable listings move quickly — average days on market is currently {$mc['avg_dom']} days. Getting pre-approved for financing, knowing your must-haves vs nice-to-haves, and being ready to write a clean offer will give you an edge. In a seller's market, negotiations tend to favour the seller, so calibrating your offer strategy with a knowledgeable local Realtor is key.";
        $_sellerCopy = "Selling a {$_typeStr2} in {$_placeStr2} is advantageous right now. Buyer demand is solid and the absorption rate of {$mc['absorption_rate']}% indicates inventory moves steadily. Homes priced accurately for the current market tend to attract multiple showings quickly. While not always resulting in bidding wars, sellers can expect firm offers and reasonable terms. Staging, professional photography, and accurate pricing remain important to maximize your sale price.";
    } elseif ($marketCondition['label'] === "Balanced Market") {
        $_buyerCopy = "Buying a {$_typeStr2} in {$_placeStr2} right now offers reasonable choice and moderate negotiating room. Supply and demand are roughly in balance — the absorption rate is {$mc['absorption_rate']}% and homes average {$mc['avg_dom']} days on market. Buyers can take time to find the right property, include standard subject conditions, and negotiate without extreme pressure. This is a good environment to conduct thorough due diligence before committing to a purchase.";
        $_sellerCopy = "Selling a {$_typeStr2} in {$_placeStr2} in a balanced market requires a disciplined approach to pricing and presentation. Buyers have options, so overpriced listings will sit. Accurate market pricing, strong curb appeal, and professional marketing are what separate homes that sell from those that linger. Expect reasonable negotiations and typical subject conditions — offers near asking price are standard in {$_placeStr2} right now.";
    } else {
        $_buyerCopy = "Buying a {$_typeStr2} in {$_placeStr2} right now puts you in a strong negotiating position. With {$mc['current_active']} active listings and only {$mc['sold_30d']} sold in the last 30 days, buyers have plenty of choice and leverage. You can take time to find the right property, negotiate price, and include financing, inspection, and other subject conditions. In a buyer's market, it's reasonable to offer below asking — especially on properties that have been listed for more than {$mc['avg_dom']} days.";
        $_sellerCopy = "Selling a {$_typeStr2} in {$_placeStr2} in a buyer's market requires realistic pricing and strong presentation. Buyers have more options and are less willing to pay a premium — overpricing will result in extended days on market and eventual price reductions. Focus on making your listing stand out through staging, high-quality photos, and competitive pricing from day one. An experienced Realtor familiar with the current {$_placeStr2} market is essential for setting the right strategy.";
    }
    @endphp
    <div class="row" style="margin-top:22px;">
        <div class="col-md-6" style="margin-bottom:16px;">
            <div style="background:#f0f7f0;border-left:4px solid #27ae60;border-radius:4px;padding:16px 20px;">
                <h2 style="font-size:15px;font-weight:700;color:#1a7a40;margin:0 0 8px;">What This Means for Buyers in {{ $_placeStr2 }}</h2>
                <p style="font-size:13px;color:#444;line-height:1.75;margin:0;">{{ $_buyerCopy }}</p>
                @if($city)
                <a href="/search-listings/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}{{ $listtypeSlug ? '/'.$listtypeSlug : '' }}?listing_status=Active" style="display:inline-block;margin-top:10px;font-size:12px;color:#1a7a40;font-weight:700;">Browse Active {{ $_placeStr2 }} Listings →</a>
                @endif
            </div>
        </div>
        <div class="col-md-6" style="margin-bottom:16px;">
            <div style="background:#fdf6f0;border-left:4px solid #e67e22;border-radius:4px;padding:16px 20px;">
                <h2 style="font-size:15px;font-weight:700;color:#c0580a;margin:0 0 8px;">What This Means for Sellers in {{ $_placeStr2 }}</h2>
                <p style="font-size:13px;color:#444;line-height:1.75;margin:0;">{{ $_sellerCopy }}</p>
                <a href="https://www.bccondosandhomes.com/contact" style="display:inline-block;margin-top:10px;font-size:12px;color:#c0580a;font-weight:700;">Get a Free Home Evaluation →</a>
            </div>
        </div>
    </div>
    @endif

    @include('frontend.includes.hani_attribution', ['attrCity' => $city, 'attrSubarea' => $subarea])

    {{-- Explore Nearby: sibling subarea internal links --}}
    @if($city && $subareas && count($subareas) > 1)
    <div style="margin-top:10px;font-size:13px;color:#666;">
        <strong>Explore nearby:</strong>
        @foreach($subareas->take(8) as $sa)
            <a href="/market-stats/{{ $citySlug }}/{{ App\Helpers\Helper::enslugPlace($sa->place) }}" style="margin-right:10px;color:#2c6fad;">{{ $sa->label ?? $sa->place }}</a>
        @endforeach
    </div>
    @endif

    {{-- Compute market vars for widgets (city-level and subarea pages) --}}
    @if($city)
    @php
    $_msMktType = 'balanced';
    $_msLabel   = $marketCondition['label'] ?? '';
    if ($_msLabel === "Strong Seller's Market")   $_msMktType = 'strong-sellers';
    elseif ($_msLabel === "Seller's Market")       $_msMktType = 'sellers';
    elseif ($_msLabel === "Buyer's Market")        $_msMktType = 'buyers';
    elseif ($_msLabel === "Balanced Market")       $_msMktType = 'balanced';
    $_msBuyers = (int)(round(max(50, ($marketCondition['current_active'] ?? 0) * 15 + ($marketCondition['sold_30d'] ?? 0) * 30) / 10) * 10);
    @endphp
    @endif
    {{-- Data element for JS --}}
    <div id="stats-data" style="display:none;"
         data-city="{{ $city }}"
         data-subarea="{{ $subarea }}"
         data-listingtype="{{ $listingtype }}"
         data-flush="{{ $flush }}"
         data-city-slug="{{ $citySlug }}"
         data-subarea-slug="{{ $subareaSlug }}"
         data-type-slug="{{ $listtypeSlug }}"
         data-has-subarea="{{ $subarea ? '1' : '0' }}"
         data-stats-json-url="{{ route('getStatsJson') }}">
    </div>

    {{-- Price Trend Narrative --}}
    @if($marketCondition['label'] && $city && ($marketCondition['avg_sold_30d'] || $marketCondition['avg_dom'] || $marketCondition['absorption_rate']))
    @php
    $_trendNarParts = [];
    if ($mc['avg_sold_30d'] && $mc['avg_sold_90d'] && $mc['price_trend'] != 0) {
        $_trendNarParts[] = "The average {$_typeStr2 ?? 'home'} price in {$_placeStr2 ?? $city} over the last 30 days is <strong>\$" . number_format($mc['avg_sold_30d']) . "</strong> — " . ($mc['price_trend'] > 0 ? '<strong style="color:#27ae60">+' : '<strong style="color:#c0392b">') . $_trendAbs . "%</strong> compared to the prior 90-day average of \$" . number_format($mc['avg_sold_90d']) . ", indicating prices are <strong>{$_trendDir}</strong> as of {$mo}";
    } elseif ($mc['avg_sold_30d']) {
        $_trendNarParts[] = "The average {$_typeStr2 ?? 'home'} price in {$_placeStr2 ?? $city} over the last 30 days is <strong>\$" . number_format($mc['avg_sold_30d']) . "</strong> as of {$mo}";
    }
    if ($mc['avg_dom']) {
        $_trendNarParts[] = "properties are selling in an average of <strong>{$mc['avg_dom']} days</strong> on market";
    }
    if ($mc['absorption_rate']) {
        $absContext = $mc['absorption_rate'] > 20 ? 'above the 20% seller\'s market threshold' : ($mc['absorption_rate'] < 12 ? 'below the 12% buyer\'s market threshold' : 'within the balanced-market range of 12–20%');
        $_trendNarParts[] = "the absorption rate is <strong>{$mc['absorption_rate']}%</strong> ({$absContext})";
    }
    @endphp
    @if(count($_trendNarParts))
    <div class="row" style="margin-top:22px;">
        <div class="col-md-12">
            <div style="background:#f8f9fb;border:1px solid #dde4ee;border-radius:5px;padding:14px 18px;font-size:14px;color:#444;line-height:1.8;">
                <strong style="color:#333;">{{ date('F Y') }} Price Trend — {{ $placeLabel }}{{ $listingtype ? ' '.$typeLabel.'s' : '' }}:</strong>
                {!! implode('; ', $_trendNarParts) !!}.
                @if($mc['price_trend'] != 0)
                Are {{ $listingtype ? strtolower($typeLabel) : '' }} prices in {{ $city }} rising? {{ $mc['price_trend'] > 0 ? "Yes — the 30-day average is trending up {$_trendAbs}% versus the 90-day baseline." : "Prices are currently trending down {$_trendAbs}% versus the 90-day average." }}
                @endif
                See the full 36-month historical chart below.
            </div>
        </div>
    </div>
    @endif
    @endif

    {{-- Chart: Avg Sold Price Monthly --}}
    <section style="margin-top:28px;">
        <div class="col-md-12" style="padding:0;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px;">
                <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 4px;">Historical Average {{ $typeLabel }} Prices in {{ $placeLabel ?: 'BC' }}</h2>
                <p style="font-size:13px;color:#888;margin:0 0 14px;">36 months — House, Townhouse &amp; Condo</p>
                <div id="chart-avg-price-monthly" data-chart="avg_price_monthly" style="min-height:280px;">
                    <div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Chart: Active Listings vs Sold --}}
    <section style="margin-top:22px;">
        <div class="col-md-12" style="padding:0;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px;">
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:14px;">
                    <div>
                        <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 2px;">{{ $placeLabel ?: 'BC' }} Active Listings vs Sold {{ $listingtype ? $typeLabel.'s' : '' }}</h2>
                        <p style="font-size:13px;color:#888;margin:0;">{{ $city ? 'By neighbourhood' : 'By city' }}</p>
                    </div>
                    <div style="margin-left:auto;display:flex;gap:6px;" class="period-group" data-chart-id="city-active-sold">
                        <button class="period-btn period-active" data-period="days30" data-chart-id="city-active-sold">30d</button>
                        <button class="period-btn" data-period="days60" data-chart-id="city-active-sold">60d</button>
                        <button class="period-btn" data-period="days90" data-chart-id="city-active-sold">90d</button>
                    </div>
                </div>
                <div id="chart-city-active-sold" data-chart="city_active_sold" data-period="days30" style="min-height:280px;">
                    <div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Chart: Type Market Share donut --}}
    <section style="margin-top:22px;">
        <div class="col-md-6 col-xs-12" style="padding:0 8px 0 0;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px;">
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:14px;">
                    <div>
                        <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 2px;">Sales by Property Type</h2>
                        <p style="font-size:13px;color:#888;margin:0;">Market share breakdown</p>
                    </div>
                    <div style="margin-left:auto;display:flex;gap:6px;" class="period-group" data-chart-id="type-active-sold">
                        <button class="period-btn period-active" data-period="days30" data-chart-id="type-active-sold">30d</button>
                        <button class="period-btn" data-period="days60" data-chart-id="type-active-sold">60d</button>
                    </div>
                </div>
                <div id="chart-type-active-sold" data-chart="type_active_sold" data-period="days30" style="min-height:260px;">
                    <div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xs-12" style="padding:0 0 0 8px;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px;">
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:14px;">
                    <div>
                        <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 2px;">{{ $city ? $placeLabel.' '.($listingtype ? $typeLabel : '') : 'BC' }} Sales by Bedroom Count</h2>
                        <p style="font-size:13px;color:#888;margin:0;">Last 30 / 60 / 90 days</p>
                    </div>
                    <div style="margin-left:auto;display:flex;gap:6px;" class="period-group" data-chart-id="sold-beds">
                        <button class="period-btn period-active" data-period="{{ ($city && $subarea) ? 'days60' : 'days30' }}" data-chart-id="sold-beds">{{ ($city && $subarea) ? '60d' : '30d' }}</button>
                        <button class="period-btn" data-period="{{ ($city && $subarea) ? 'days90' : 'days60' }}" data-chart-id="sold-beds">{{ ($city && $subarea) ? '90d' : '60d' }}</button>
                    </div>
                </div>
                <div id="chart-sold-beds" data-chart="sold_beds" data-period="{{ ($city && $subarea) ? 'days60' : 'days30' }}" style="min-height:260px;">
                    <div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Chart: Sold Count Monthly --}}
    <section style="margin-top:22px;">
        <div class="col-md-12" style="padding:0;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px;">
                <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 4px;">Monthly Units Sold{{ $city ? ' in '.$placeLabel : '' }}</h2>
                <p style="font-size:13px;color:#888;margin:0 0 14px;">36 months — House, Townhouse &amp; Condo</p>
                <div id="chart-sold-count-monthly" data-chart="sold_count_monthly" style="min-height:280px;">
                    <div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Chart: Price-to-List Diff Monthly --}}
    <section style="margin-top:22px;">
        <div class="col-md-12" style="padding:0;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px;">
                <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 4px;">Sale Price vs List Price (% Difference)</h2>
                <p style="font-size:13px;color:#888;margin:0 0 14px;">Monthly average — positive means sold over ask</p>
                <div id="chart-avg-diff-monthly" data-chart="avg_diff_monthly" style="min-height:280px;">
                    <div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Insight Bar Widget — subarea pages only --}}
    @if($city && $subarea)
    <script src="https://admin.bccondosandhomes.com/widget/insight-bar.js"
        data-placement="main"
        data-neighbourhood="{{ $subarea ?? '' }}"
        data-city="{{ $city }}"
        data-market-type="{{ $_msMktType }}"
        data-avg-price="{{ ($marketCondition['avg_sold_30d'] ?? 0) > 0 ? '$'.number_format($marketCondition['avg_sold_30d']) : '' }}"
        data-avg-dom="{{ ($marketCondition['avg_dom'] ?? 0) > 0 ? $marketCondition['avg_dom'].'d' : '' }}"
        data-active-listings="{{ $marketCondition['current_active'] ?? 0 }}"
        data-absorption-rate="{{ ($marketCondition['absorption_rate'] ?? 0) > 0 ? $marketCondition['absorption_rate'].'%' : '' }}"
        data-sold-30d="{{ $marketCondition['sold_30d'] ?? 0 }}"
        data-buyers="{{ number_format($_msBuyers ?? 50) }}"
    ></script>
    @endif

    {{-- Chart: Sold by Price Range --}}
    <section style="margin-top:22px;">
        <div class="col-md-6 col-xs-12" style="padding:0 8px 0 0;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px;">
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:14px;">
                    <div>
                        <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 2px;">Sold by Price Range</h2>
                        <p style="font-size:13px;color:#888;margin:0;">Units sold per price bracket</p>
                    </div>
                    <div style="margin-left:auto;display:flex;gap:6px;" class="period-group" data-chart-id="sold-price-range">
                        <button class="period-btn period-active" data-period="{{ ($city && $subarea) ? 'days60' : 'days30' }}" data-chart-id="sold-price-range">{{ ($city && $subarea) ? '60d' : '30d' }}</button>
                        <button class="period-btn" data-period="{{ ($city && $subarea) ? 'days90' : 'days60' }}" data-chart-id="sold-price-range">{{ ($city && $subarea) ? '90d' : '60d' }}</button>
                    </div>
                </div>
                <div id="chart-sold-price-range" data-chart="sold_price_range" data-period="{{ ($city && $subarea) ? 'days60' : 'days30' }}" style="min-height:280px;">
                    <div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xs-12" style="padding:0 0 0 8px;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px;">
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:14px;">
                    <div>
                        <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 2px;">Property Age of Sold Homes</h2>
                        <p style="font-size:13px;color:#888;margin:0;">Units sold by build year range</p>
                    </div>
                    <div style="margin-left:auto;display:flex;gap:6px;" class="period-group" data-chart-id="property-age">
                        <button class="period-btn period-active" data-period="{{ ($city && $subarea) ? 'days60' : 'days30' }}" data-chart-id="property-age">{{ ($city && $subarea) ? '60d' : '30d' }}</button>
                        <button class="period-btn" data-period="{{ ($city && $subarea) ? 'days90' : 'days60' }}" data-chart-id="property-age">{{ ($city && $subarea) ? '90d' : '60d' }}</button>
                    </div>
                </div>
                <div id="chart-property-age" data-chart="property_age_stats" data-period="{{ ($city && $subarea) ? 'days60' : 'days30' }}" style="min-height:280px;">
                    <div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>
                </div>
            </div>
        </div>
    </section>

    @if(!$subarea)
    {{-- Chart: Units Sold 24 Months --}}
    <section style="margin-top:22px;">
        <div class="col-md-12" style="padding:0;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px;">
                <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 4px;">Units Sold — Last 24 Months (Year-over-Year){{ $city ? ' in '.$placeLabel : '' }}</h2>
                <p style="font-size:13px;color:#888;margin:0 0 14px;">Current 12 months vs previous 12 months</p>
                <div id="chart-three-year-sold" data-chart="three_year_sold" style="min-height:280px;">
                    <div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Chart: Avg DOM by area --}}
    @if($listingtype === '' || $listingtype === 'any')
    <section style="margin-top:22px;">
        <div class="col-md-12" style="padding:0;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px;">
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:14px;">
                    <div>
                        <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 2px;">How Long Does It Take to Sell a {{ $listingtype ? $typeLabel : 'Home' }} in {{ $city ?: 'BC' }}?</h2>
                        <p style="font-size:13px;color:#888;margin:0;">Average days on market — House, Townhouse &amp; Condo by area</p>
                    </div>
                    <div style="margin-left:auto;display:flex;gap:6px;" class="period-group" data-chart-id="avg-dom">
                        <button class="period-btn period-active" data-period="days30" data-chart-id="avg-dom">30d</button>
                        <button class="period-btn" data-period="days60" data-chart-id="avg-dom">60d</button>
                        <button class="period-btn" data-period="days90" data-chart-id="avg-dom">90d</button>
                    </div>
                </div>
                <div id="chart-avg-dom" data-chart="avg_dom_data" data-period="days30" style="min-height:280px;">
                    <div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>
                </div>
            </div>
        </div>
    </section>
    @endif
    @endif

    {{-- Historical Stats Table --}}
    <section style="margin-top:22px;">
        <div class="col-md-12" style="padding:0;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px;">
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:14px;">
                    <div>
                        <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 2px;">Historical {{ $placeLabel ?: 'BC' }} {{ $listingtype ? $typeLabel : 'Real Estate' }} Market Data</h2>
                        <p style="font-size:13px;color:#888;margin:0;">Monthly breakdown by area — last 24 months</p>
                    </div>
                    <div style="margin-left:auto;display:flex;gap:6px;" id="yearly-type-toggle">
                        <button class="period-btn period-active" id="yt-units_sold" onclick="loadYearlyTable('units_sold')">Units Sold</button>
                        <button class="period-btn" id="yt-avg_price" onclick="loadYearlyTable('avg_price')">Avg Price</button>
                        <button class="period-btn" id="yt-avg_dom" onclick="loadYearlyTable('avg_dom')">Avg DOM</button>
                    </div>
                </div>
                <div id="yearly-table-wrap" style="overflow-x:auto;">
                    <div class="chart-loader" style="text-align:center;padding:40px 0;color:#aaa;">Loading data…</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Absorption Map --}}
    @if($absorptionMap && count($absorptionMap) > 0)
    <section style="margin-top:28px;" id="absorption-map-section">
        <div class="col-md-12" style="padding:0;">
            <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px;">
                <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 4px;">{{ $city }} Real Estate Activity by Neighbourhood</h2>
                <p style="font-size:13px;color:#888;margin:0 0 14px;">30-day absorption snapshot — click any column header to sort, click any neighbourhood for detailed stats</p>

                {{-- Desktop sortable table --}}
                <div class="absorption-table-wrap hidden-xs" style="overflow-x:auto;">
                    <table id="absorption-table" style="width:100%;font-size:13px;border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:2px solid #eee;text-align:left;background:#f7f4ef;">
                                <th class="sortable" data-col="0" style="padding:8px 12px;font-weight:600;color:#555;cursor:pointer;white-space:nowrap;">Neighbourhood <span class="sort-icon">↕</span></th>
                                <th class="sortable" data-col="1" style="padding:8px 12px;font-weight:600;color:#555;text-align:right;cursor:pointer;white-space:nowrap;">Active <span class="sort-icon">↕</span></th>
                                <th class="sortable" data-col="2" style="padding:8px 12px;font-weight:600;color:#555;text-align:right;cursor:pointer;white-space:nowrap;">Sold (30d) <span class="sort-icon">↕</span></th>
                                <th class="sortable" data-col="3" style="padding:8px 12px;font-weight:600;color:#555;text-align:right;cursor:pointer;white-space:nowrap;">Absorption <span class="sort-icon">↕</span></th>
                                <th style="padding:8px 12px;font-weight:600;color:#555;text-align:center;">Market</th>
                                <th class="sortable" data-col="5" style="padding:8px 12px;font-weight:600;color:#555;text-align:right;cursor:pointer;white-space:nowrap;">Avg Price <span class="sort-icon">↕</span></th>
                                <th class="sortable" data-col="6" style="padding:8px 12px;font-weight:600;color:#555;text-align:right;cursor:pointer;white-space:nowrap;">Avg DOM <span class="sort-icon">↕</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($absorptionMap as $area)
                            @php
                                $areaAbsorption = ($area->current_active > 0 && $area->sold_by_filter > 0)
                                    ? round($area->sold_by_filter / $area->current_active * 100, 1)
                                    : 0;
                                $areaSlug = App\Helpers\Helper::enslugPlace($area->place_name ?? $area->city_name);
                                $areaAvgDom = (int)($area->avg_dom_filter ?? 0);
                                if ($areaAbsorption > 0) {
                                    $_areaCond   = App\Helpers\MarketConditionHelper::classify($areaAbsorption, $areaAvgDom);
                                    $areaVColor  = $_areaCond['color'];
                                    $_shortMap   = ["Strong Seller's Market" => "Strong Seller's", "Seller's Market" => "Seller's", "Balanced Market" => "Balanced", "Buyer's Market" => "Buyer's"];
                                    $areaVerdict = $_shortMap[$_areaCond['label']] ?? $_areaCond['label'];
                                } else {
                                    $areaVerdict = ''; $areaVColor = '#aaa';
                                }
                            @endphp
                            <tr style="border-bottom:1px solid #f0f0f0;" data-abs="{{ $areaAbsorption }}" data-price="{{ $area->avg_sold_price_filter ?? 0 }}" data-dom="{{ $areaAvgDom }}">
                                <td style="padding:8px 12px;">
                                    <a href="/market-stats/{{ $citySlug }}/{{ $areaSlug }}" style="color:#2c6fad;font-weight:500;">{{ $area->city_name }}</a>
                                </td>
                                <td style="padding:8px 12px;text-align:right;"><a href="/search-listings/{{ $citySlug }}/{{ $areaSlug }}?listing_status=Active" style="color:#2c6fad;text-decoration:none;">{{ number_format($area->current_active ?? 0) }}</a></td>
                                <td style="padding:8px 12px;text-align:right;">@if($area->sold_by_filter > 0)<a href="/search-listings/{{ $citySlug }}/{{ $areaSlug }}?listing_status=Sold" style="color:#2c6fad;text-decoration:none;">{{ number_format($area->sold_by_filter) }}</a>@else—@endif</td>
                                <td style="padding:8px 12px;text-align:right;">
                                    @if($areaAbsorption)
                                    <span style="font-weight:600;color:{{ $areaVColor }}">{{ $areaAbsorption }}%</span>
                                    @else—@endif
                                </td>
                                <td style="padding:8px 12px;text-align:center;">
                                    @if($areaVerdict)
                                    <span style="font-size:11px;font-weight:700;color:#fff;background:{{ $areaVColor }};border-radius:3px;padding:2px 6px;white-space:nowrap;">{{ $areaVerdict }}</span>
                                    @else—@endif
                                </td>
                                <td style="padding:8px 12px;text-align:right;">@if($area->avg_sold_price_filter)${{ number_format($area->avg_sold_price_filter) }}@else—@endif</td>
                                <td style="padding:8px 12px;text-align:right;">@if($areaAvgDom){{ $areaAvgDom }}d @else—@endif</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile card layout --}}
                <div class="visible-xs absorption-cards" style="display:none;">
                    @foreach($absorptionMap as $area)
                    @php
                        $areaAbsorption2 = ($area->current_active > 0 && $area->sold_by_filter > 0)
                            ? round($area->sold_by_filter / $area->current_active * 100, 1) : 0;
                        $areaSlug2 = App\Helpers\Helper::enslugPlace($area->place_name ?? $area->city_name);
                        $areaAvgDom2 = (int)($area->avg_dom_filter ?? 0);
                        if ($areaAbsorption2 > 0) {
                            $_areaCond2  = App\Helpers\MarketConditionHelper::classify($areaAbsorption2, $areaAvgDom2);
                            $areaVerdict2 = $_areaCond2['label'];
                            $areaVColor2  = $_areaCond2['color'];
                        } else {
                            $areaVerdict2 = ''; $areaVColor2 = '#aaa';
                        }
                    @endphp
                    <div style="border:1px solid #eee;border-left:4px solid {{ $areaVColor2 }};border-radius:5px;padding:12px 14px;margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <a href="/market-stats/{{ $citySlug }}/{{ $areaSlug2 }}" style="font-size:14px;font-weight:700;color:#2c6fad;">{{ $area->city_name }}</a>
                            @if($areaVerdict2)
                            <span style="font-size:11px;font-weight:700;color:#fff;background:{{ $areaVColor2 }};border-radius:3px;padding:2px 7px;">{{ $areaVerdict2 }}</span>
                            @endif
                        </div>
                        <div style="display:flex;gap:18px;font-size:12px;color:#555;flex-wrap:wrap;">
                            <span>Active: <a href="/search-listings/{{ $citySlug }}/{{ $areaSlug2 }}?listing_status=Active" style="font-weight:700;color:#2c6fad;text-decoration:none;">{{ number_format($area->current_active ?? 0) }}</a></span>
                            <span>Sold 30d: @if($area->sold_by_filter > 0)<a href="/search-listings/{{ $citySlug }}/{{ $areaSlug2 }}?listing_status=Sold" style="font-weight:700;color:#2c6fad;text-decoration:none;">{{ number_format($area->sold_by_filter) }}</a>@else<strong>0</strong>@endif</span>
                            @if($areaAbsorption2)<span>Absorption: <strong style="color:{{ $areaVColor2 }}">{{ $areaAbsorption2 }}%</strong></span>@endif
                            @if($area->avg_sold_price_filter)<span>Avg Price: <strong>${{ number_format($area->avg_sold_price_filter) }}</strong></span>@endif
                            @if($areaAvgDom2)<span>Avg DOM: <strong>{{ $areaAvgDom2 }}d</strong></span>@endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Recent Listings Strip --}}
    @if($recentListings && count($recentListings) > 0)
    @php
    $itemListSchema = [
        '@context' => 'https://schema.org',
        '@type'    => 'ItemList',
        'name'     => 'Recently Listed in ' . ($subarea ?: $city ?: 'BC'),
        'itemListElement' => [],
    ];
    foreach($recentListings as $_ili => $_ilr) {
        $itemListSchema['itemListElement'][] = [
            '@type'    => 'ListItem',
            'position' => $_ili + 1,
            'url'      => trim(route('listing-detail-page2', ['slug' => $_ilr->slug])),
            'name'     => trim($_ilr->street_number . ' ' . $_ilr->street_name . ' ' . $_ilr->street_type)
                          . ' — ' . ($_ilr->listprice_2 ? '$' . number_format($_ilr->listprice_2) : 'Price on request'),
        ];
    }
    @endphp
    <script type="application/ld+json">{!! json_encode($itemListSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    <section style="margin-top:28px;">
        <div class="col-md-12" style="padding:0;">
            <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 14px;">
                Recently Listed in {{ $subarea ?: $city ?: 'BC' }}@if($listingtype) — {{ $typeLabel }}s @endif
            </h2>
            <div class="row">
                @foreach($recentListings as $listing)
                @php
                    $_firstPhoto = $listing->photos->first();
                    $photoUrl = $_firstPhoto
                        ? 'https://media.pixilinkserver.com/' . str_replace('images', '', $_firstPhoto->directory . $_firstPhoto->name) . '?w=400'
                        : asset('assets/img/no-image.jpg');
                    $listUrl = trim(route('listing-detail-page2', ['slug' => $listing->slug]));
                @endphp
                <div class="col-sm-4 col-xs-6" style="margin-bottom:18px;">
                    <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);overflow:hidden;">
                        <a href="{{ $listUrl }}">
                            <div style="height:160px;background:url('{{ $photoUrl }}') center/cover no-repeat;"></div>
                        </a>
                        <div style="padding:12px 14px;">
                            <div style="font-size:16px;font-weight:700;color:#2c6fad;">@if($listing->listprice_2)${{ number_format($listing->listprice_2) }}@else—@endif</div>
                            <div style="font-size:13px;color:#555;margin-top:4px;">{{ $listing->bedrooms ?? 0 }} bd &bull; {{ $listing->bathstotal ?? 0 }} ba &bull; {{ $listing->getType() }}</div>
                            <div style="font-size:12px;color:#888;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $listing->street_number }} {{ $listing->street_name }} {{ $listing->street_type }}</div>
                            <a href="{{ $listUrl }}" style="display:block;margin-top:8px;font-size:12px;color:#2c6fad;">View Listing →</a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Top Buildings --}}
    @if($topBuildings && count($topBuildings) > 0)
    <section style="margin-top:28px;">
        <div class="col-md-12" style="padding:0;">
            <h2 style="font-size:17px;font-weight:700;color:#333;margin:0 0 14px;">Top Condo Buildings in {{ $subarea ? $subarea.', '.$city : $city }}</h2>
            <div class="row">
                @foreach($topBuildings as $bldg)
                <div class="col-sm-4 col-xs-6" style="margin-bottom:14px;">
                    <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:14px 16px;">
                        <div style="font-size:14px;font-weight:700;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <a href="{{ route('building-detail-page', ['slug' => $bldg->slug]) }}" style="color:#333;">{{ $bldg->name }}</a>
                        </div>
                        <div style="font-size:12px;color:#888;margin-top:4px;">
                            {{ $bldg->street_no }} {{ $bldg->street_name }} {{ $bldg->street_type }}@if($bldg->yearbuilt) &bull; Built {{ $bldg->yearbuilt }}@endif
                        </div>
                        @if($bldg->units_in_strata)
                        <div style="font-size:12px;color:#666;margin-top:4px;">{{ $bldg->units_in_strata }} units in strata</div>
                        @endif
                        <a href="{{ route('building-detail-page', ['slug' => $bldg->slug]) }}" style="display:block;margin-top:8px;font-size:12px;color:#2c6fad;">View Building →</a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Compare Nearby Markets --}}
    @if($city && $listtypeSlug)
    @php
    $adjacencyMap = [
        'Vancouver'       => ['Burnaby', 'Richmond', 'North Vancouver'],
        'Burnaby'         => ['Vancouver', 'Coquitlam', 'New Westminster'],
        'Richmond'        => ['Vancouver', 'Delta', 'Burnaby'],
        'North Vancouver' => ['Vancouver', 'West Vancouver', 'Burnaby'],
        'West Vancouver'  => ['North Vancouver', 'Vancouver', 'Squamish'],
        'Surrey'          => ['Burnaby', 'Delta', 'Langley'],
        'Coquitlam'       => ['Burnaby', 'Port Coquitlam', 'Port Moody'],
        'Port Coquitlam'  => ['Coquitlam', 'Port Moody', 'Maple Ridge'],
        'Port Moody'      => ['Coquitlam', 'Burnaby', 'North Vancouver'],
        'New Westminster' => ['Burnaby', 'Coquitlam', 'Surrey'],
        'Delta'           => ['Richmond', 'Surrey', 'Burnaby'],
        'Langley'         => ['Surrey', 'Maple Ridge', 'Abbotsford'],
        'Maple Ridge'     => ['Coquitlam', 'Pitt Meadows', 'Langley'],
        'Abbotsford'      => ['Langley', 'Mission', 'Chilliwack'],
        'Mission'         => ['Abbotsford', 'Maple Ridge', 'Chilliwack'],
        'Chilliwack'      => ['Abbotsford', 'Mission', 'Langley'],
        'Pitt Meadows'    => ['Maple Ridge', 'Coquitlam', 'Port Coquitlam'],
        'Squamish'        => ['West Vancouver', 'North Vancouver', 'Whistler'],
        'Whistler'        => ['Squamish', 'Pemberton'],
        'Pemberton'       => ['Whistler', 'Squamish'],
        'White Rock'      => ['Surrey', 'Delta', 'Langley'],
    ];
    $nearbyMarkets = $adjacencyMap[$city] ?? [];
    @endphp
    @if(count($nearbyMarkets))
    <section style="margin-top:28px;">
        <div class="col-md-12" style="padding:0;">
            <div style="background:#f7f4ef;border:1px solid #e2dbd2;border-radius:6px;padding:20px 22px;">
                <h2 style="font-size:16px;font-weight:700;color:#333;margin:0 0 12px;">Compare Nearby {{ $typeLabel }} Markets</h2>
                <p style="font-size:13px;color:#666;margin:0 0 14px;line-height:1.65;">
                    How does the {{ $city }} {{ strtolower($typeLabel) }} market compare to neighbouring cities? Browse live market stats for similar nearby markets:
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:10px;">
                    @foreach($nearbyMarkets as $_nm)
                    @php $_nmSlug = App\Helpers\Helper::enslugPlace($_nm); @endphp
                    <a href="/market-stats/{{ $_nmSlug }}/{{ $listtypeSlug }}"
                       style="display:inline-block;padding:10px 18px;background:#fff;border:1px solid #d0c9bf;border-radius:5px;text-decoration:none;color:#2c6fad;font-size:13px;font-weight:600;box-shadow:0 1px 3px rgba(0,0,0,.06);">
                        {{ $_nm }} {{ $typeLabel }} Market Stats →
                    </a>
                    @endforeach
                    <a href="/market-stats"
                       style="display:inline-block;padding:10px 18px;background:#fff;border:1px solid #d0c9bf;border-radius:5px;text-decoration:none;color:#777;font-size:13px;">
                        All BC Market Stats →
                    </a>
                </div>
                <p style="font-size:12px;color:#999;margin:12px 0 0;line-height:1.5;">
                    Searching "{{ $city }} vs {{ $nearbyMarkets[0] }} {{ strtolower($typeLabel) }} prices"?
                    Both markets are updated daily. The absorption rate, average sold price, and days on market for each city are shown on their respective pages above.
                </p>
            </div>
        </div>
    </section>
    @endif
    @endif

</div>{{-- /container --}}

{{-- Disclaimer --}}
<div class="listings-disclaimer">
    <div class="container">
        <p>Last Update: {{ \Carbon\Carbon::now()->format('m/d/Y') }} &nbsp;&nbsp;<strong>Disclaimer:</strong> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy. — Hani & Les | BC Condos And Homes — Re/Max Crest Realty, 300 - 1195 W Broadway, Vancouver, BC</p>
    </div>
</div>

{{-- Past Monthly Reports for This Area (only shown when a city context exists) --}}
@if(!empty($city))
@php
$_reportType = match($listtypeSlug ?? '') {
    'condos'     => "AND type = 'Apartment'",
    'houses'     => "AND type = 'House'",
    'townhouses' => "AND type = 'Townhouse'",
    default      => "AND type IN('Apartment','House','Townhouse','Duplex','Fourplex','Triplex')",
};
$_placeWhere = '';
if (!empty($city)) {
    $_placeWhere = "AND city = '" . addslashes($city) . "'";
    if (!empty($subarea)) {
        $_placeWhere .= " AND subarea = '" . addslashes($subarea) . "'";
    }
}
$_availRows = \Illuminate\Support\Facades\DB::connection('mysql_pixi360')->select(
    "SELECT YEAR(sold_date) AS yr, MONTH(sold_date) AS mo FROM boards.listings
     WHERE status='Sold' AND sold_date IS NOT NULL AND sold_date > '2010-01-01'
     {$_placeWhere} {$_reportType}
     GROUP BY yr, mo HAVING COUNT(*) >= 3 ORDER BY yr DESC, mo DESC LIMIT 3"
);
$recentReports = [];
foreach ($_availRows as $_row) {
    $recentReports[] = [
        'label' => date('F Y', mktime(0,0,0,$_row->mo,1,$_row->yr)),
        'slug'  => strtolower(date('F', mktime(0,0,0,$_row->mo,1,$_row->yr))) . '-' . $_row->yr,
    ];
}
$reportBaseUrl = '/market-report'
    . ($citySlug    ? '/'.$citySlug    : '')
    . ($subareaSlug ? '/'.$subareaSlug : '')
    . ($listtypeSlug ? '/'.$listtypeSlug : '');
@endphp
<div style="background:#fff;border-top:1px solid #e8e3da;padding:20px 0 16px;">
    <div class="container">
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;">
            <span style="font-size:13px;font-weight:700;color:#555;">Past Monthly Reports:</span>
            @foreach($recentReports as $rr)
            <a href="{{ $reportBaseUrl }}/{{ $rr['slug'] }}" style="font-size:13px;color:#2c6fad;border:1px solid #cde;border-radius:4px;padding:4px 12px;text-decoration:none;white-space:nowrap;">{{ $rr['label'] }}</a>
            @endforeach
            <a href="{{ $reportBaseUrl }}" style="font-size:13px;color:#888;border:1px solid #ddd;border-radius:4px;padding:4px 12px;text-decoration:none;white-space:nowrap;">All Reports →</a>
        </div>
    </div>
</div>
@endif

{{-- SEO backlinks grid --}}
<div class="stats-search-links" style="background:#f7f4ef;border-top:1px solid #e2dbd2;padding:32px 0 24px;">
    <div class="container">
        <h2 style="font-size:16px;font-weight:700;color:#444;margin-bottom:18px;">Browse Real Estate Market Statistics</h2>
        <div class="row">
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Vancouver</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/market-stats/vancouver">Vancouver Market Stats</a></li>
                    <li><a href="/market-stats/vancouver/condos">Vancouver Condo Stats</a></li>
                    <li><a href="/market-stats/vancouver/houses">Vancouver House Stats</a></li>
                    <li><a href="/search-listings/vancouver?type=Apartment">Vancouver Condos for Sale</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Burnaby / Metrotown</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/market-stats/burnaby">Burnaby Market Stats</a></li>
                    <li><a href="/market-stats/burnaby/metrotown">Metrotown Market Stats</a></li>
                    <li><a href="/market-stats/burnaby/condos">Burnaby Condo Stats</a></li>
                    <li><a href="/search-listings/burnaby?type=Apartment">Burnaby Condos for Sale</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">North Shore</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/market-stats/north-vancouver">North Vancouver Stats</a></li>
                    <li><a href="/market-stats/west-vancouver">West Vancouver Stats</a></li>
                    <li><a href="/market-stats/north-vancouver/condos">North Van Condo Stats</a></li>
                    <li><a href="/search-listings/north-vancouver?type=Apartment">North Vancouver Condos</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Richmond &amp; Delta</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/market-stats/richmond">Richmond Market Stats</a></li>
                    <li><a href="/market-stats/richmond/condos">Richmond Condo Stats</a></li>
                    <li><a href="/market-stats/delta">Delta Market Stats</a></li>
                    <li><a href="/search-listings/richmond?type=Apartment">Richmond Condos for Sale</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Surrey &amp; Langley</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/market-stats/surrey">Surrey Market Stats</a></li>
                    <li><a href="/market-stats/surrey/condos">Surrey Condo Stats</a></li>
                    <li><a href="/market-stats/langley">Langley Market Stats</a></li>
                    <li><a href="/search-listings/surrey?type=Apartment">Surrey Condos for Sale</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Tri-Cities</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/market-stats/coquitlam">Coquitlam Market Stats</a></li>
                    <li><a href="/market-stats/port-coquitlam">Port Coquitlam Stats</a></li>
                    <li><a href="/market-stats/port-moody">Port Moody Stats</a></li>
                    <li><a href="/search-listings/coquitlam?type=Apartment">Coquitlam Condos</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Fraser Valley</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/market-stats/abbotsford">Abbotsford Market Stats</a></li>
                    <li><a href="/market-stats/langley">Langley Market Stats</a></li>
                    <li><a href="/market-stats/mission">Mission Market Stats</a></li>
                    <li><a href="/search-listings/abbotsford?type=House">Abbotsford Houses</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">All Market Stats</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/market-stats">BC Market Overview</a></li>
                    <li><a href="/market-stats/vancouver">Vancouver Statistics</a></li>
                    <li><a href="/market-stats/burnaby">Burnaby Statistics</a></li>
                    <li><a href="/market-stats/west-vancouver">West Vancouver Statistics</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Past Market Reports</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/market-report">All Market Reports</a></li>
                    <li><a href="/market-report/vancouver">Vancouver Reports</a></li>
                    <li><a href="/market-report/burnaby">Burnaby Reports</a></li>
                    <li><a href="/market-report/surrey">Surrey Reports</a></li>
                </ul>
            </div>
        </div>
        <div class="row" style="margin-top:18px;padding-top:18px;border-top:1px solid #e2dbd2;">
            <div class="col-sm-12">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">House Market Guides</h3>
                <div style="font-size:13px;line-height:2;">
                    <a href="/houses/" style="margin-right:14px;color:#2c6fad;">Metro Vancouver House Market Hub</a>
                    <a href="/houses/vancouver/" style="margin-right:14px;">Vancouver Houses</a>
                    <a href="/houses/burnaby/" style="margin-right:14px;">Burnaby Houses</a>
                    <a href="/houses/surrey/" style="margin-right:14px;">Surrey Houses</a>
                    <a href="/houses/richmond/" style="margin-right:14px;">Richmond Houses</a>
                    <a href="/houses/coquitlam/" style="margin-right:14px;">Coquitlam Houses</a>
                    <a href="/houses/north-vancouver/" style="margin-right:14px;">North Vancouver Houses</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding-bottom:16px;">
    @php
        $_msStripCtx  = trim(($subarea ? $subarea . ', ' : '') . ($city ?: 'BC'));
        $_msStripName = $_msStripCtx . ($listingtype ? ' ' . ($listingtype === 'Apartment' ? 'Condo' : $listingtype) : '') . ' Listings';
        $_msStripData = json_encode(array_filter([
            'cities'         => $city ?: null,
            'subareas'       => $subarea ?: null,
            'type'           => $listingtype ?: null,
            'listing_status' => 'Active',
        ]));
    @endphp
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => $_msStripCtx,
        'stripSearchName' => $_msStripName,
        'stripSearchData' => $_msStripData,
        'stripCity'       => $city ?: '',
        'stripModalId'    => 'msAlert_' . md5(($citySlug ?? '') . ($subareaSlug ?? '') . ($listtypeSlug ?? '')),
        'stripHeading'    => 'Get New Listing Alerts for ' . $_msStripCtx,
        'stripSubtext'    => 'Stay ahead of the market — get email alerts when new ' . strtolower($_msStripName) . ' hit the MLS®.',
    ])
</div>

@include('frontend.includes.footer_links')
@include('frontend.includes.footer')

@if($city)
<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
    @if($subarea) data-neighbourhood="{{ $subarea }}" @endif
    data-city="{{ $city }}"
    data-market-type="{{ $_msMktType ?? 'balanced' }}"
    data-avg-price="{{ ($marketCondition['avg_sold_30d'] ?? 0) > 0 ? '$'.number_format($marketCondition['avg_sold_30d']) : '' }}"
    data-avg-dom="{{ ($marketCondition['avg_dom'] ?? 0) > 0 ? $marketCondition['avg_dom'].'d' : '' }}"
    data-active-listings="{{ $marketCondition['current_active'] ?? 0 }}"
    data-absorption-rate="{{ ($marketCondition['absorption_rate'] ?? 0) > 0 ? $marketCondition['absorption_rate'].'%' : '' }}"
    data-sold-30d="{{ $marketCondition['sold_30d'] ?? 0 }}"
    data-buyers="{{ number_format($_msBuyers ?? 50) }}"
></script>
@endif
@endsection

@push('after-styles')
<style>
.mstab {
    display:inline-block;padding:5px 12px;font-size:12px;font-weight:600;border:1px solid #ccc;
    border-radius:4px;color:#555;text-decoration:none;background:#fff;cursor:pointer;
}
.mstab:hover,.mstab-active { background:#2c6fad;color:#fff;border-color:#2c6fad;text-decoration:none; }
.period-btn {
    padding:4px 10px;font-size:11px;font-weight:600;border:1px solid #ccc;
    border-radius:4px;background:#fff;color:#555;cursor:pointer;
}
.period-btn:hover,.period-active { background:#2c6fad;color:#fff;border-color:#2c6fad; }
.no-data-msg { text-align:center;padding:60px 0;color:#bbb;font-size:14px; }
</style>
@endpush

@push('after-scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
<script>
(function(){
'use strict';
var el = document.getElementById('stats-data');
if (!el) return;
var CITY        = el.dataset.city        || '';
var SUBAREA     = el.dataset.subarea     || '';
var LISTINGTYPE = el.dataset.listingtype || '';
var FLUSH       = el.dataset.flush       || '0';
var JSONURL     = el.dataset.statsJsonUrl || '/stats_json';

function apiUrl(type, extra){
    var u = JSONURL + '?type=' + encodeURIComponent(type)
        + '&city='        + encodeURIComponent(CITY)
        + '&subarea='     + encodeURIComponent(SUBAREA)
        + '&listingtype=' + encodeURIComponent(LISTINGTYPE)
        + '&flush='       + FLUSH;
    if (extra) Object.keys(extra).forEach(function(k){ u += '&' + k + '=' + encodeURIComponent(extra[k]); });
    return u;
}
function money(n){ return '$' + Number(n||0).toLocaleString('en-CA',{minimumFractionDigits:0,maximumFractionDigits:0}); }
function setLoader(id, msg){ var d=document.getElementById(id); if(d) d.innerHTML='<div class="no-data-msg">'+(msg||'No data available for this period.')+'</div>'; }

/* ---- Monthly chart key arrays ---- */
var MK37 = [
    'thirdyear_twelve','thirdyear_eleven','thirdyear_ten','thirdyear_nine','thirdyear_eight','thirdyear_seven',
    'thirdyear_six','thirdyear_five','thirdyear_four','thirdyear_three','thirdyear_two','thirdyear_one',
    'minus_thirteen','minus_twelve','minus_eleven','minus_ten','minus_nine','minus_eight','minus_seven',
    'minus_six','minus_five','minus_four','minus_three','minus_two','minus_one',
    'one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve'
];
var MK25 = [
    'minus_thirteen','minus_twelve','minus_eleven','minus_ten','minus_nine','minus_eight','minus_seven',
    'minus_six','minus_five','minus_four','minus_three','minus_two','minus_one',
    'one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve'
];
function buildLabels(row, keys){
    return keys.map(function(k){ return (row['month_'+k]||'') + ' ' + (row['year_'+k]||''); });
}
function buildSeries(results, type, prefix, keys){
    var row = {};
    for (var i=0; i<results.length; i++){ if(results[i].type===type){ row=results[i]; break; } }
    return keys.map(function(k){ return parseFloat(row[prefix+k])||null; });
}

/* ---- Chart: Avg Price Monthly ---- */
var chartAvgPrice = null;
function initAvgPriceMonthly(){
    var id = 'chart-avg-price-monthly';
    fetch(apiUrl('avg_price_monthly')).then(function(r){return r.json();}).then(function(d){
        if (!d.success || !d.data || !d.data.length){ setLoader(id); return; }
        var labels = buildLabels(d.data[0], MK37);
        var opts = {
            chart:{type:'line',height:280,toolbar:{show:false},zoom:{enabled:false}},
            series:[
                {name:'House',    data:buildSeries(d.data,'House',    'avg_price_',MK37)},
                {name:'Townhouse',data:buildSeries(d.data,'Townhouse','avg_price_',MK37)},
                {name:'Condo',    data:buildSeries(d.data,'Apartment','avg_price_',MK37)}
            ],
            colors:['#97BBCD','#F8464A','#DCDCDC'],
            xaxis:{categories:labels,tickAmount:6,labels:{rotate:-35,style:{fontSize:'10px'}}},
            yaxis:{labels:{formatter:money}},
            stroke:{width:2,curve:'smooth'},
            tooltip:{y:{formatter:money}},
            legend:{position:'top'},
            noData:{text:'No data'}
        };
        var div = document.getElementById(id);
        div.innerHTML = '';
        chartAvgPrice = new ApexCharts(div, opts);
        chartAvgPrice.render();
    }).catch(function(){ setLoader(id,'Failed to load chart.'); });
}

/* ---- Chart: Sold Count Monthly ---- */
var chartSoldCount = null;
function initSoldCountMonthly(){
    var id = 'chart-sold-count-monthly';
    fetch(apiUrl('sold_count_monthly')).then(function(r){return r.json();}).then(function(d){
        if (!d.success || !d.data || !d.data.length){ setLoader(id); return; }
        var labels = buildLabels(d.data[0], MK37);
        var opts = {
            chart:{type:'line',height:280,toolbar:{show:false},zoom:{enabled:false}},
            series:[
                {name:'House',    data:buildSeries(d.data,'House',    'sold_count_',MK37)},
                {name:'Townhouse',data:buildSeries(d.data,'Townhouse','sold_count_',MK37)},
                {name:'Condo',    data:buildSeries(d.data,'Apartment','sold_count_',MK37)}
            ],
            colors:['#97BBCD','#F8464A','#DCDCDC'],
            xaxis:{categories:labels,tickAmount:6,labels:{rotate:-35,style:{fontSize:'10px'}}},
            yaxis:{labels:{formatter:function(v){return Math.round(v);}}},
            stroke:{width:2,curve:'smooth'},
            legend:{position:'top'},
            noData:{text:'No data'}
        };
        var div = document.getElementById(id);
        div.innerHTML = '';
        chartSoldCount = new ApexCharts(div, opts);
        chartSoldCount.render();
    }).catch(function(){ setLoader(id,'Failed to load chart.'); });
}

/* ---- Chart: Avg Diff Monthly ---- */
var chartAvgDiff = null;
function initAvgDiffMonthly(){
    var id = 'chart-avg-diff-monthly';
    fetch(apiUrl('avg_diff_monthly')).then(function(r){return r.json();}).then(function(d){
        if (!d.success || !d.data || !d.data.length){ setLoader(id); return; }
        var labels = buildLabels(d.data[0], MK37);
        var opts = {
            chart:{type:'line',height:280,toolbar:{show:false},zoom:{enabled:false}},
            series:[
                {name:'House',    data:buildSeries(d.data,'House',    'avg_diff_',MK37)},
                {name:'Townhouse',data:buildSeries(d.data,'Townhouse','avg_diff_',MK37)},
                {name:'Condo',    data:buildSeries(d.data,'Apartment','avg_diff_',MK37)}
            ],
            colors:['#97BBCD','#F8464A','#DCDCDC'],
            xaxis:{categories:labels,tickAmount:6,labels:{rotate:-35,style:{fontSize:'10px'}}},
            yaxis:{labels:{formatter:function(v){return (v||0).toFixed(1)+'%';}}},
            stroke:{width:2,curve:'smooth'},
            tooltip:{y:{formatter:function(v){return (v||0).toFixed(2)+'%';}}},
            legend:{position:'top'},
            noData:{text:'No data'}
        };
        var div = document.getElementById(id);
        div.innerHTML = '';
        chartAvgDiff = new ApexCharts(div, opts);
        chartAvgDiff.render();
    }).catch(function(){ setLoader(id,'Failed to load chart.'); });
}

/* ---- Chart: City Active Sold ---- */
var chartCityActiveSold = null;
function initCityActiveSold(period){
    var id = 'chart-city-active-sold';
    if (chartCityActiveSold){ chartCityActiveSold.destroy(); chartCityActiveSold=null; }
    document.getElementById(id).innerHTML = '<div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>';
    fetch(apiUrl('city_active_sold',{period:period||'days30'})).then(function(r){return r.json();}).then(function(d){
        if (!d.success || !d.data || !d.data.length){ setLoader(id); return; }
        var labels=[], listed=[], sold=[];
        d.data.forEach(function(row){
            if(row.listed_by_filter>0||row.sold_by_filter>0){
                labels.push(row.city_name); listed.push(+row.listed_by_filter||0); sold.push(+row.sold_by_filter||0);
            }
        });
        var opts={
            chart:{type:'bar',height:280,toolbar:{show:false}},
            series:[{name:'Listed',data:listed},{name:'Sold',data:sold}],
            colors:['#45b7cd','#ED402A'],
            xaxis:{categories:labels,labels:{style:{fontSize:'11px'}}},
            plotOptions:{bar:{columnWidth:'55%'}},
            legend:{position:'top'},
            noData:{text:'No data'}
        };
        var div=document.getElementById(id); div.innerHTML='';
        chartCityActiveSold=new ApexCharts(div,opts); chartCityActiveSold.render();
    }).catch(function(){ setLoader(id,'Failed to load chart.'); });
}

/* ---- Chart: Type Active Sold (donut) ---- */
var chartTypeActiveSold = null;
function initTypeActiveSold(period){
    var id='chart-type-active-sold';
    if(chartTypeActiveSold){chartTypeActiveSold.destroy();chartTypeActiveSold=null;}
    document.getElementById(id).innerHTML='<div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>';
    fetch(apiUrl('type_active_sold',{period:period||'days30'})).then(function(r){return r.json();}).then(function(d){
        if(!d.success||!d.data||!d.data.length){setLoader(id);return;}
        var row=d.data[0];
        var vals=[+row.house_sold||0,+row.townhouse_sold||0,+row.apartment_sold||0];
        if(!vals[0]&&!vals[1]&&!vals[2]){setLoader(id);return;}
        var opts={
            chart:{type:'donut',height:260},
            series:vals,labels:['House','Townhouse','Condo'],
            colors:['#97BBCD','#F8464A','#DCDCDC'],
            legend:{position:'bottom'},
            noData:{text:'No data'}
        };
        var div=document.getElementById(id); div.innerHTML='';
        chartTypeActiveSold=new ApexCharts(div,opts); chartTypeActiveSold.render();
    }).catch(function(){setLoader(id,'Failed to load chart.');});
}

/* ---- Chart: Sold Beds ---- */
var chartSoldBeds = null;
function initSoldBeds(period){
    var id='chart-sold-beds';
    if(chartSoldBeds){chartSoldBeds.destroy();chartSoldBeds=null;}
    document.getElementById(id).innerHTML='<div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>';
    fetch(apiUrl('sold_beds',{period:period||'days30'})).then(function(r){return r.json();}).then(function(d){
        if(!d.success||!d.data||!d.data.length){setLoader(id);return;}
        var labels=[], vals=[];
        d.data.forEach(function(row){ if(+row.listings_sold>0){labels.push(row.bedrooms+' bd');vals.push(+row.listings_sold);} });
        if(!vals.length){setLoader(id);return;}
        var opts={
            chart:{type:'bar',height:260,toolbar:{show:false}},
            series:[{name:'Units Sold',data:vals}],
            colors:['#45b7cd'],
            xaxis:{categories:labels},
            plotOptions:{bar:{borderRadius:4,columnWidth:'55%'}},
            legend:{show:false},
            noData:{text:'No data'}
        };
        var div=document.getElementById(id); div.innerHTML='';
        chartSoldBeds=new ApexCharts(div,opts); chartSoldBeds.render();
    }).catch(function(){setLoader(id,'Failed to load chart.');});
}

/* ---- Chart: Sold Price Range ---- */
var chartSoldPriceRange = null;
function initSoldPriceRange(period){
    var id='chart-sold-price-range';
    if(chartSoldPriceRange){chartSoldPriceRange.destroy();chartSoldPriceRange=null;}
    document.getElementById(id).innerHTML='<div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>';
    fetch(apiUrl('sold_price_range',{period:period||'days30'})).then(function(r){return r.json();}).then(function(d){
        if(!d.data||!d.data.length){setLoader(id);return;}
        var labels=[], vals=[];
        d.data.forEach(function(row){labels.push(row.Range);vals.push(+row.Count||0);});
        var opts={
            chart:{type:'bar',height:280,toolbar:{show:false}},
            series:[{name:'Units Sold',data:vals}],
            colors:['#2980b9'],
            xaxis:{categories:labels,labels:{style:{fontSize:'11px'}}},
            plotOptions:{bar:{horizontal:true,borderRadius:3}},
            legend:{show:false},
            noData:{text:'No data'}
        };
        var div=document.getElementById(id); div.innerHTML='';
        chartSoldPriceRange=new ApexCharts(div,opts); chartSoldPriceRange.render();
    }).catch(function(){setLoader(id,'Failed to load chart.');});
}

/* ---- Chart: Property Age ---- */
var chartPropertyAge = null;
function initPropertyAge(period){
    var id='chart-property-age';
    if(chartPropertyAge){chartPropertyAge.destroy();chartPropertyAge=null;}
    document.getElementById(id).innerHTML='<div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>';
    fetch(apiUrl('property_age_stats',{period:period||'days30'})).then(function(r){return r.json();}).then(function(d){
        if(!d.data||!d.data.length){setLoader(id);return;}
        var labels=[], vals=[];
        d.data.forEach(function(row){labels.push(row.Range);vals.push(+row.Count||0);});
        var opts={
            chart:{type:'bar',height:280,toolbar:{show:false}},
            series:[{name:'Units Sold',data:vals}],
            colors:['#8e44ad'],
            xaxis:{categories:labels,labels:{style:{fontSize:'11px'}}},
            plotOptions:{bar:{horizontal:true,borderRadius:3}},
            legend:{show:false},
            noData:{text:'No data'}
        };
        var div=document.getElementById(id); div.innerHTML='';
        chartPropertyAge=new ApexCharts(div,opts); chartPropertyAge.render();
    }).catch(function(){setLoader(id,'Failed to load chart.');});
}

/* ---- Chart: Three Year Sold ---- */
var chartThreeYearSold = null;
function initThreeYearSold(){
    var id='chart-three-year-sold';
    if (!document.getElementById(id)) return;
    fetch(apiUrl('three_year_sold')).then(function(r){return r.json();}).then(function(d){
        if(!d.success||!d.data||!d.data.length){setLoader(id);return;}
        var labels=[],cur=[],prev=[],curLabel='',prevLabel='';
        d.data.forEach(function(row){
            labels.push(row.city_name); cur.push(+row.current_12_months_sold||0); prev.push(+row.last_12_months_sold||0);
            curLabel=row.current_12_months; prevLabel=row.last_12_months;
        });
        var opts={
            chart:{type:'bar',height:280,toolbar:{show:false}},
            series:[{name:curLabel||'Current 12mo',data:cur},{name:prevLabel||'Prior 12mo',data:prev}],
            colors:['#F8464A','#45BFBD'],
            xaxis:{categories:labels,labels:{style:{fontSize:'11px'}}},
            plotOptions:{bar:{columnWidth:'55%'}},
            legend:{position:'top'},
            noData:{text:'No data'}
        };
        var div=document.getElementById(id); div.innerHTML='';
        chartThreeYearSold=new ApexCharts(div,opts); chartThreeYearSold.render();
    }).catch(function(){setLoader(id,'Failed to load chart.');});
}

/* ---- Chart: Avg DOM ---- */
var chartAvgDom = null;
function initAvgDom(period){
    var id='chart-avg-dom';
    if (!document.getElementById(id)) return;
    if(chartAvgDom){chartAvgDom.destroy();chartAvgDom=null;}
    document.getElementById(id).innerHTML='<div class="chart-loader" style="text-align:center;padding:60px 0;color:#aaa;">Loading chart…</div>';
    fetch(apiUrl('avg_dom_data',{period:period||'days30'})).then(function(r){return r.json();}).then(function(d){
        if(!d.success||!d.data||!d.data.length){setLoader(id);return;}
        var labels=[],houses=[],towns=[],apts=[];
        d.data.forEach(function(row){
            if(+row.avg_dom_house>0||+row.avg_dom_apartment>0||+row.avg_dom_townhouse>0){
                labels.push(row.city_name);
                houses.push(+row.avg_dom_house||0); towns.push(+row.avg_dom_townhouse||0); apts.push(+row.avg_dom_apartment||0);
            }
        });
        var opts={
            chart:{type:'bar',height:280,toolbar:{show:false}},
            series:[{name:'House',data:houses},{name:'Townhouse',data:towns},{name:'Condo',data:apts}],
            colors:['#97BBCD','#F8464A','#DCDCDC'],
            xaxis:{categories:labels,labels:{style:{fontSize:'11px'}}},
            plotOptions:{bar:{columnWidth:'75%'}},
            legend:{position:'top'},
            yaxis:{title:{text:'Days'}},
            noData:{text:'No data'}
        };
        var div=document.getElementById(id); div.innerHTML='';
        chartAvgDom=new ApexCharts(div,opts); chartAvgDom.render();
    }).catch(function(){setLoader(id,'Failed to load chart.');});
}

/* ---- Historical Yearly Table ---- */
var currentYearlyType='units_sold';
function loadYearlyTable(statsType){
    currentYearlyType = statsType||'units_sold';
    ['units_sold','avg_price','avg_dom'].forEach(function(t){
        var b=document.getElementById('yt-'+t);
        if(b){ b.classList.toggle('period-active', t===currentYearlyType); }
    });
    var wrap = document.getElementById('yearly-table-wrap');
    if (!wrap) return;
    wrap.innerHTML='<div class="chart-loader" style="text-align:center;padding:40px 0;color:#aaa;">Loading…</div>';
    fetch(apiUrl('city_stats_yearly',{stats_type:currentYearlyType})).then(function(r){return r.json();}).then(function(d){
        if(!d.success||!d.data){wrap.innerHTML='<div class="no-data-msg">No data available.</div>';return;}
        var titles=d.data.titles; var rows=d.data.data;
        var tCols=['minus_twelve','minus_eleven','minus_ten','minus_nine','minus_eight','minus_seven','minus_six','minus_five','minus_four','minus_three','minus_two','minus_one','one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve'];
        var html='<table style="width:100%;font-size:12px;border-collapse:collapse;"><thead><tr style="background:#f7f4ef;">';
        html+='<th style="padding:6px 10px;text-align:left;border-right:1px solid #eee;min-width:140px;">{{ $city ? 'Neighbourhood' : 'City' }}</th>';
        tCols.forEach(function(k){ html+='<th style="padding:6px 6px;text-align:center;border-right:1px solid #eee;white-space:nowrap;">'+((titles&&titles[k])||k)+'</th>'; });
        html+='</tr></thead><tbody>';
        rows.forEach(function(row,i){
            html+='<tr style="border-bottom:1px solid #f0f0f0;'+(i%2?'background:#fafafa;':'')+'"><td style="padding:6px 10px;border-right:1px solid #eee;font-weight:500;">'+row.area+'</td>';
            tCols.forEach(function(k){ html+='<td style="padding:6px 6px;text-align:center;border-right:1px solid #eee;">'+(row['result_'+k]||'—')+'</td>'; });
            html+='</tr>';
        });
        html+='</tbody></table>';
        wrap.innerHTML=html;
    }).catch(function(){ wrap.innerHTML='<div class="no-data-msg">Failed to load data.</div>'; });
}

/* ---- Period button handlers ---- */
document.querySelectorAll('.period-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
        var chartId = this.dataset.chartId;
        var period  = this.dataset.period;
        document.querySelectorAll('.period-btn[data-chart-id="'+chartId+'"]').forEach(function(b){ b.classList.remove('period-active'); });
        this.classList.add('period-active');
        switch(chartId){
            case 'city-active-sold': initCityActiveSold(period); break;
            case 'type-active-sold': initTypeActiveSold(period); break;
            case 'sold-beds':        initSoldBeds(period);        break;
            case 'sold-price-range': initSoldPriceRange(period);  break;
            case 'property-age':     initPropertyAge(period);     break;
            case 'avg-dom':          initAvgDom(period);          break;
        }
    });
});

/* ---- IntersectionObserver lazy loading ---- */
var initialized = {};
var observer = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
        if(!entry.isIntersecting) return;
        var id   = entry.target.id;
        var type = entry.target.dataset.chart;
        var period = entry.target.dataset.period;
        if(initialized[id]) return;
        initialized[id]=true;
        observer.unobserve(entry.target);
        switch(type){
            case 'avg_price_monthly':  initAvgPriceMonthly();           break;
            case 'sold_count_monthly': initSoldCountMonthly();          break;
            case 'avg_diff_monthly':   initAvgDiffMonthly();            break;
            case 'city_active_sold':   initCityActiveSold(period);      break;
            case 'type_active_sold':   initTypeActiveSold(period);      break;
            case 'sold_beds':          initSoldBeds(period);            break;
            case 'sold_price_range':   initSoldPriceRange(period);      break;
            case 'property_age_stats': initPropertyAge(period);         break;
            case 'three_year_sold':    initThreeYearSold();             break;
            case 'avg_dom_data':       initAvgDom(period);              break;
        }
    });
},{rootMargin:'250px'});

document.querySelectorAll('[data-chart]').forEach(function(div){ observer.observe(div); });

/* ---- Expose yearly table loader globally for onclick handlers ---- */
window.loadYearlyTable = loadYearlyTable;

/* ---- Load yearly table immediately ---- */
loadYearlyTable('units_sold');

/* ---- Absorption table sortable ---- */
(function(){
    var table = document.getElementById('absorption-table');
    if (!table) return;
    var lastCol = -1, lastDir = 1;
    table.querySelectorAll('th.sortable').forEach(function(th){
        th.addEventListener('click', function(){
            var col = parseInt(this.dataset.col);
            var dir = (col === lastCol) ? -lastDir : -1;
            lastCol = col; lastDir = dir;
            table.querySelectorAll('.sort-icon').forEach(function(ic){ ic.textContent='↕'; });
            this.querySelector('.sort-icon').textContent = dir === 1 ? '↑' : '↓';
            var tbody = table.querySelector('tbody');
            var rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort(function(a, b){
                var ac = a.cells[col] ? a.cells[col].textContent.trim() : '';
                var bc = b.cells[col] ? b.cells[col].textContent.trim() : '';
                var an = parseFloat(ac.replace(/[$,%d]/g,'')) || 0;
                var bn = parseFloat(bc.replace(/[$,%d]/g,'')) || 0;
                if (col === 0) return dir * ac.localeCompare(bc);
                return dir * (an - bn);
            });
            rows.forEach(function(r){ tbody.appendChild(r); });
        });
    });
    /* show mobile cards on xs, table on sm+ */
    function checkWidth(){
        var isMobile = window.innerWidth < 768;
        var tableWrap = document.querySelector('.absorption-table-wrap');
        var cards     = document.querySelector('.absorption-cards');
        if (tableWrap) tableWrap.style.display = isMobile ? 'none' : 'block';
        if (cards)     cards.style.display     = isMobile ? 'block' : 'none';
    }
    checkWidth();
    window.addEventListener('resize', checkWidth);
    /* Default sort: descending absorption (col 3) on first render */
    var absHeader = table.querySelector('th.sortable[data-col="3"]');
    if (absHeader) absHeader.click();
})();

})();
</script>

@include('frontend.includes.hani_bubble')
@endpush
