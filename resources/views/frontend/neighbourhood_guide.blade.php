@extends('frontend.layouts.default')
@php
$condoAvg    = $condoCondition['avg_sold_30d']    ?? 0;
$houseAvg    = $houseCondition['avg_sold_30d']    ?? 0;
$twAvg       = $townhouseCondition['avg_sold_30d'] ?? 0;
$allActive   = $allCondition['current_active']    ?? 0;
$allAbsorb   = $allCondition['absorption_rate']   ?? 0;
$allLabel    = $allCondition['label']             ?? null;
$allColor    = $allCondition['color']             ?? '#888';

$metaTitle = "{$subarea} Homes for Sale in {$city}, BC | MLS® Listings & Market Data | Hani & Les";

$_avgPrice = $allCondition['avg_sold_30d'] ?? ($condoAvg ?: $houseAvg);
$_firstSentence = '';
if (!empty($description)) {
    $_plain = strip_tags($description);
    preg_match('/^.*?[.!?](?:\s|$)/u', $_plain, $_m);
    if (isset($_m[0])) {
        $_firstSentence = trim($_m[0]) . ' ';
    } elseif (strlen($_plain) > 0) {
        $_snippet = mb_substr($_plain, 0, 100);
        $_lastSpace = mb_strrpos($_snippet, ' ');
        $_firstSentence = rtrim($_lastSpace !== false ? mb_substr($_snippet, 0, $_lastSpace) : $_snippet, '.!?,') . '. ';
    }
}
$metaDesc  = $_firstSentence . "Browse " . number_format($allActive) . " MLS® homes for sale in {$subarea}, {$city}.";
if ($_avgPrice) $metaDesc .= " Avg price \$" . number_format($_avgPrice) . ".";
if ($allLabel)  $metaDesc .= " Currently a {$allLabel}.";
$metaDesc .= " Updated daily.";
if (mb_strlen($metaDesc) > 155) {
    $_truncated = mb_substr($metaDesc, 0, 155);
    $_lastSpace = mb_strrpos($_truncated, ' ');
    $metaDesc = rtrim($_lastSpace !== false ? mb_substr($_truncated, 0, $_lastSpace) : $_truncated, '.!?,') . '…';
}

$canonicalUrl = "https://www.bccondosandhomes.com/neighbourhood/{$citySlug}/{$subareaSlug}/";

// FAQ schema answer content derived from description and market data
$_descPlain = !empty($description) ? trim(preg_replace('/\s+/', ' ', strip_tags($description))) : '';

// "What is [neighbourhood] like?" — use description up to ~500 chars at sentence boundary
if ($_descPlain) {
    if (mb_strlen($_descPlain) > 500) {
        $_trunc = mb_substr($_descPlain, 0, 500);
        $_periodPos = mb_strrpos($_trunc, '.');
        $_descSummary = $_periodPos !== false ? mb_substr($_descPlain, 0, $_periodPos + 1) : $_trunc . '…';
    } else {
        $_descSummary = $_descPlain;
    }
    $_faqWhatIsLike = $_descSummary;
} else {
    $_faqWhatIsLike = "{$subarea} is a neighbourhood in {$city}, BC with " . number_format($allActive) . " active MLS® listings." . ($allLabel ? " The current market condition is a {$allLabel}." : '');
}

// "Is [neighbourhood] a good place to live?" — combine description snippet with market strength
$_goodPlaceParts = [];
if ($_descPlain) {
    preg_match('/^.*?[.!?](?:\s|$)/u', $_descPlain, $_gm);
    if (!empty($_gm[0])) $_goodPlaceParts[] = trim($_gm[0]);
}
$_marketSentence = "{$subarea} currently has " . number_format($allActive) . " active listings";
if ($condoAvg || $houseAvg) {
    $_marketSentence .= " with average sold prices of ";
    if ($condoAvg && $houseAvg) {
        $_marketSentence .= "\$" . number_format($condoAvg) . " for condos and \$" . number_format($houseAvg) . " for houses";
    } elseif ($condoAvg) {
        $_marketSentence .= "\$" . number_format($condoAvg) . " for condos";
    } else {
        $_marketSentence .= "\$" . number_format($houseAvg) . " for houses";
    }
}
$_marketSentence .= ($allLabel ? ", making it a {$allLabel}" : '') . ".";
$_goodPlaceParts[] = $_marketSentence;
$_goodPlaceParts[] = "It is a sought-after area in the Greater {$city} real estate market.";
$_faqGoodPlace = implode(' ', $_goodPlaceParts);

// "What amenities does [neighbourhood] have?" — only populated if description provides content
$_faqAmenities = $_descPlain
    ? "Based on the neighbourhood profile: {$_faqWhatIsLike} For the latest listings and local market insights, visit the {$subarea} neighbourhood guide on BC Condos And Homes."
    : '';

$sparklineData = [];
$sparklineLabels = [];
if ($priceSeries) {
    $suffixes = ['one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve'];
    $apptRow  = null;
    $houseRow = null;
    foreach ($priceSeries as $row) {
        if ($row->type === 'Apartment') $apptRow  = $row;
        if ($row->type === 'House')     $houseRow = $row;
    }
    $baseRow = $apptRow ?? $houseRow ?? ($priceSeries[0] ?? null);
    if ($baseRow) {
        foreach ($suffixes as $suffix) {
            $sparklineLabels[] = substr($baseRow->{'month_'.$suffix} ?? '', 0, 3);
            $sparklineData['condo'][] = $apptRow  ? (int)($apptRow->{'avg_price_'.$suffix}  ?? 0) : 0;
            $sparklineData['house'][] = $houseRow ? (int)($houseRow->{'avg_price_'.$suffix} ?? 0) : 0;
        }
    }
}
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
        {"@type":"ListItem","position":2,"name":"Neighbourhoods","item":"https://www.bccondosandhomes.com/neighbourhood/"},
        {"@type":"ListItem","position":3,"name":"{{ e($city) }}","item":"https://www.bccondosandhomes.com/neighbourhood/{{ $citySlug }}/"},
        {"@type":"ListItem","position":4,"name":"{{ e($subarea) }}","item":"{{ $canonicalUrl }}"}
      ]
    },
    {
      "@type": "Place",
      "name": "{{ e($subarea) }}, {{ e($city) }}, BC",
      "description": "{{ e($description ?? $metaDesc) }}"
      @if($avgLat && $avgLng)
      ,"geo": {"@type":"GeoCoordinates","latitude":{{ round($avgLat, 5) }},"longitude":{{ round($avgLng, 5) }}}
      @endif
    },
    {
      "@type": "FAQPage",
      "mainEntity": [
        @if($condoAvg)
        {"@type":"Question","name":"What is the average condo price in {{ $subarea }}, {{ $city }}?","acceptedAnswer":{"@type":"Answer","text":"The average condo sold price in {{ $subarea }}, {{ $city }} over the last 30 days is ${{ number_format($condoAvg) }}. {{ $condoCondition['sold_30d'] }} condos sold in the last 30 days."}},
        @endif
        @if($houseAvg)
        {"@type":"Question","name":"What is the average house price in {{ $subarea }}, {{ $city }}?","acceptedAnswer":{"@type":"Answer","text":"The average house sold price in {{ $subarea }}, {{ $city }} over the last 30 days is ${{ number_format($houseAvg) }}."}},
        @endif
        {"@type":"Question","name":"How many homes are for sale in {{ $subarea }}, {{ $city }}?","acceptedAnswer":{"@type":"Answer","text":"There are currently {{ number_format($allActive) }} MLS® homes for sale in {{ $subarea }}, {{ $city }}. Listings are updated daily."}},
        {"@type":"Question","name":"Is {{ $subarea }} a buyer's or seller's market?","acceptedAnswer":{"@type":"Answer","text":"Based on current absorption rate ({{ $allAbsorb }}%) and {{ $allActive }} active listings, {{ $subarea }} is currently {{ $allLabel ?? 'a transitioning market' }}."}},
        {"@type":"Question","name":"What is {{ $subarea }} like?","acceptedAnswer":{"@type":"Answer","text":{!! json_encode($_faqWhatIsLike) !!}}},
        {"@type":"Question","name":"Is {{ $subarea }} a good place to live?","acceptedAnswer":{"@type":"Answer","text":{!! json_encode($_faqGoodPlace) !!}}}
        @if($_faqAmenities)
        ,{"@type":"Question","name":"What amenities does {{ $subarea }} have?","acceptedAnswer":{"@type":"Answer","text":{!! json_encode($_faqAmenities) !!}}}
        @endif
      ]
    }
  ]
}
</script>
@endsection

@section('content')
@include('frontend.includes.header')

<div class="page-main" style="padding:28px 0 12px;background:#f7f4ef;border-bottom:1px solid #e2dbd2;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:10px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item"><a href="/neighbourhood/">Neighbourhoods</a></li>
                <li class="breadcrumb-item"><a href="/neighbourhood/{{ $citySlug }}/">{{ $city }}</a></li>
                <li class="breadcrumb-item active">{{ $subarea }}</li>
            </ol>
        </nav>
        <h1 style="font-size:26px;font-weight:700;margin-bottom:10px;color:#2c2c2c;">{{ $subarea }} Homes for Sale &ndash; {{ $city }}, BC</h1>

        <p style="font-size:15px;color:#444;max-width:860px;line-height:1.7;margin-bottom:8px;">
            {{ $subarea }} is {{ $city }}'s
            @if($allActive > 200) most active @elseif($allActive > 80) highly active @else an active @endif
            real estate market.
            @if($allActive) With {{ number_format($allActive) }} active listings @endif
            @if($allAbsorb) and a {{ $allAbsorb }}% absorption rate, @endif
            @if($allLabel) it is currently a <strong>{{ $allLabel }}</strong>@if($condoAvg) for condos @endif. @endif
            @if($condoAvg) The average sold price for a condo in {{ $subarea }} is <strong>${{ number_format($condoAvg) }}</strong> (last 30 days).@endif
            @if($houseAvg) Houses in {{ $subarea }} average <strong>${{ number_format($houseAvg) }}</strong> sold.@endif
        </p>

        <div style="display:flex;flex-wrap:wrap;gap:10px;font-size:13px;">
            <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}" style="background:#2c6fad;color:#fff;border-radius:4px;padding:6px 14px;text-decoration:none;font-weight:600;">View all{{ $allActive > 0 ? ' '.number_format($allActive) : '' }} homes for sale in {{ $subarea }}</a>
            <a href="/market-stats/{{ $citySlug }}/{{ $subareaSlug }}" style="background:#fff;color:#2c6fad;border:1px solid #2c6fad;border-radius:4px;padding:6px 14px;text-decoration:none;font-weight:600;">Market Stats</a>
            <a href="/market-report/{{ $citySlug }}/{{ $subareaSlug }}" style="background:#fff;color:#555;border:1px solid #ccc;border-radius:4px;padding:6px 14px;text-decoration:none;">Market Reports</a>
            <a href="/new-listings/{{ $citySlug }}" style="background:#fff;color:#555;border:1px solid #ccc;border-radius:4px;padding:6px 14px;text-decoration:none;">New This Week</a>
            <a href="/price-reductions/{{ $citySlug }}" style="background:#fff;color:#555;border:1px solid #ccc;border-radius:4px;padding:6px 14px;text-decoration:none;">Price Reductions</a>
            <a href="/market-update/{{ $citySlug }}" style="background:#fff;color:#555;border:1px solid #ccc;border-radius:4px;padding:6px 14px;text-decoration:none;">Market Update</a>
            <a href="/top-realtor/{{ $citySlug }}/{{ $subareaSlug }}/" style="background:#e5b021;color:#111;border-radius:4px;padding:6px 14px;text-decoration:none;font-weight:700;">Top Realtor in {{ $subarea }} &rsaquo;</a>
            {{-- Alert CTA [Task#535] --}}
            @php $_nghModalId = 'nghAlert_' . \Illuminate\Support\Str::random(5); @endphp
            <button onclick="document.getElementById('{{ $_nghModalId }}').style.display='flex'"
                style="background:#231f20;color:#fff;border:none;border-radius:4px;padding:6px 14px;font-size:13px;font-weight:600;cursor:pointer;">
                🔔 Get Listing Alerts
            </button>
        </div>
        {{-- Neighbourhood alert modal [Task#535] --}}
        @php
            $_nghSearchName = $subarea . ', ' . $city . ' Listings';
            $_nghSearchData = json_encode([
                'cities'   => $city,
                'subareas' => $subarea,
                'status'   => 'Active',
            ]);
        @endphp
        <div id="{{ $_nghModalId }}" style="display:none;position:fixed;inset:0;background:rgba(35,31,32,.72);backdrop-filter:blur(3px);z-index:9998;align-items:center;justify-content:center;padding:16px;">
          <div style="background:#fff;border-radius:12px;max-width:460px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.35);overflow:hidden;">
            <div style="background:#231f20;padding:20px 24px 16px;position:relative;">
              <button onclick="document.getElementById('{{ $_nghModalId }}').style.display='none'" style="position:absolute;top:12px;right:12px;background:rgba(255,255,255,.15);border:none;color:#fff;width:26px;height:26px;border-radius:50%;cursor:pointer;font-size:14px;line-height:1;">✕</button>
              <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">🔔 Listing Alerts</div>
              <h3 style="margin:0;font-size:18px;font-weight:700;color:#fff;">Get alerts for {{ $subarea }}, {{ $city }}</h3>
            </div>
            <div style="padding:22px 24px;" id="{{ $_nghModalId }}_body">
              @auth
                <p style="font-size:14px;color:#555;margin:0 0 18px;line-height:1.6;">Click below to save this search and receive email alerts when new listings become available in <strong>{{ $subarea }}, {{ $city }}</strong>.</p>
                <button onclick="bcAlertSubmitAuth('{{ $_nghModalId }}', 'search', '', {{ json_encode($subarea . ', ' . $city) }}, {{ json_encode($city) }}, '', {{ json_encode($_nghSearchName) }}, {{ json_encode($_nghSearchData) }})"
                  style="width:100%;background:#2c6fad;color:#fff;border:none;border-radius:5px;padding:13px;font-size:15px;font-weight:700;cursor:pointer;">
                  Notify Me of New Listings
                </button>
              @else
                <p style="font-size:14px;color:#555;margin:0 0 16px;line-height:1.6;">Enter your email to receive alerts for new listings in <strong>{{ $subarea }}, {{ $city }}</strong>.</p>
                <form onsubmit="bcAlertSubmitGuest(event, '{{ $_nghModalId }}', 'search', '', {{ json_encode($subarea . ', ' . $city) }}, {{ json_encode($city) }}, '', {{ json_encode($_nghSearchName) }}, {{ json_encode($_nghSearchData) }})">
                  @csrf
                  <input type="email" required placeholder="Your email address" style="width:100%;border:1px solid #ddd;border-radius:5px;padding:11px 14px;font-size:14px;margin-bottom:10px;box-sizing:border-box;">
                  <button type="submit" style="width:100%;background:#2c6fad;color:#fff;border:none;border-radius:5px;padding:13px;font-size:15px;font-weight:700;cursor:pointer;">Notify Me</button>
                </form>
                <p style="font-size:11px;color:#aaa;margin:10px 0 0;text-align:center;">A quick confirmation email will be sent. Unsubscribe any time.</p>
              @endauth
            </div>
          </div>
        </div>
        @include('frontend.includes.alert_modal_scripts')
    </div>
</div>

<div class="container" style="padding-top:26px;padding-bottom:50px;min-height:60vh;">

    {{-- MARKET SNAPSHOT TABLE --}}
    <div style="background:#fff;border:1px solid #e2dbd2;border-radius:6px;padding:20px 22px;margin-bottom:26px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
        <h2 style="font-size:16px;font-weight:700;color:#333;margin:0 0 14px;">Market Snapshot — All Property Types (Last 30 Days)</h2>
        <div class="table-responsive">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f7f4ef;color:#888;text-align:left;">
                        <th style="padding:8px 12px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Type</th>
                        <th style="padding:8px 12px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Avg Sold Price</th>
                        <th style="padding:8px 12px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Sold (30d)</th>
                        <th style="padding:8px 12px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Avg DOM</th>
                        <th style="padding:8px 12px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Active</th>
                        <th style="padding:8px 12px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Condition</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([['Condos', $condoCondition], ['Houses', $houseCondition], ['Townhouses', $townhouseCondition]] as $row)
                    @php [$label, $cond] = $row; @endphp
                    <tr style="border-top:1px solid #f0ede8;">
                        <td style="padding:10px 12px;font-weight:600;color:#333;">{{ $label }}</td>
                        <td style="padding:10px 12px;color:#333;">{{ $cond['avg_sold_30d'] ? '$'.number_format($cond['avg_sold_30d']) : '—' }}</td>
                        <td style="padding:10px 12px;color:#333;">{{ $cond['sold_30d'] ?: '—' }}</td>
                        <td style="padding:10px 12px;color:#333;">{{ $cond['avg_dom'] ? $cond['avg_dom'].'d' : '—' }}</td>
                        <td style="padding:10px 12px;color:#333;">{{ $cond['current_active'] ?: '—' }}</td>
                        <td style="padding:10px 12px;">
                            @if($cond['label'])
                            <span style="background:{{ $cond['color'] }};color:#fff;border-radius:3px;padding:2px 7px;font-size:11px;font-weight:700;">{{ $cond['label'] }}</span>
                            @else
                            <span style="color:#aaa;">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('frontend.includes.hani_attribution', ['attrCity' => $city, 'attrSubarea' => $subarea])

    @if($description ?? false)
    <div style="background:#fff;border:1px solid #e2dbd2;border-radius:6px;padding:22px 24px;margin-bottom:26px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
        <h2 style="font-size:16px;font-weight:700;color:#333;margin:0 0 12px;">About {{ $subarea }}</h2>
        <p style="font-size:14px;color:#444;line-height:1.8;margin:0;">{{ $description }}</p>
    </div>
    @endif

    @php
    $marketTypeSlug = 'balanced';
    if ($allLabel === "Strong Seller's Market")   $marketTypeSlug = 'strong-sellers';
    elseif ($allLabel === "Seller's Market")       $marketTypeSlug = 'sellers';
    elseif ($allLabel === "Buyer's Market")        $marketTypeSlug = 'buyers';
    elseif ($allLabel === "Balanced Market")       $marketTypeSlug = 'balanced';
    $avgPriceFormatted = ($allCondition['avg_sold_30d'] ?? 0) > 0 ? '$'.number_format($allCondition['avg_sold_30d']) : '';
    $avgDomFormatted   = ($allCondition['avg_dom'] ?? 0) > 0 ? $allCondition['avg_dom'].'d' : '';
    $absorptionFormatted = ($allCondition['absorption_rate'] ?? 0) > 0 ? $allCondition['absorption_rate'].'%' : '';
    $buyersEst = (int)(round(max(50, ($allCondition['current_active'] ?? 0) * 15 + ($allCondition['sold_30d'] ?? 0) * 30) / 10) * 10);
    @endphp
    @unless(!$allActive)
    <script src="https://admin.bccondosandhomes.com/widget/insight-bar.js"
        data-placement="main"
        data-neighbourhood="{{ $subarea }}"
        data-city="{{ $city }}"
        data-market-type="{{ $marketTypeSlug }}"
        data-avg-price="{{ $avgPriceFormatted }}"
        data-avg-dom="{{ $avgDomFormatted }}"
        data-active-listings="{{ $allCondition['current_active'] ?? 0 }}"
        data-absorption-rate="{{ $absorptionFormatted }}"
        data-sold-30d="{{ $allCondition['sold_30d'] ?? 0 }}"
        data-buyers="{{ $buyersEst }}"
    ></script>
    @endunless

    {{-- CONDO SECTION --}}
    <section style="margin-bottom:30px;">
        <h2 style="font-size:20px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">{{ $subarea }} Condo Market</h2>

        @if($condoCondition['label'])
        <div class="row">
            <div class="col-md-4">
                <div class="verdict-card {{ $condoCondition['class'] }}" style="border-left:5px solid {{ $condoCondition['color'] }};background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:18px 20px;margin-bottom:16px;">
                    <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;margin-bottom:5px;">Condo Market Verdict</div>
                    <div style="font-size:20px;font-weight:700;color:{{ $condoCondition['color'] }};margin-bottom:8px;">{{ $condoCondition['label'] }}</div>
                    <div style="font-size:13px;color:#555;line-height:1.8;">
                        <div>Absorption: <strong>{{ $condoCondition['absorption_rate'] }}%</strong></div>
                        <div>Avg DOM: <strong>{{ $condoCondition['avg_dom'] ?: '—' }} days</strong></div>
                        @if($condoCondition['price_trend'] != 0)
                        <div>30d vs 90d: <strong style="color:{{ $condoCondition['price_trend'] > 0 ? '#27ae60' : '#c0392b' }}">{{ $condoCondition['price_trend'] > 0 ? '+' : '' }}{{ $condoCondition['price_trend'] }}%</strong></div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="row">
                    <div class="col-sm-3 col-xs-6" style="margin-bottom:12px;">
                        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:14px;text-align:center;">
                            <div style="font-size:22px;font-weight:700;color:#333;">{{ number_format($condoCondition['current_active']) }}</div>
                            <div style="font-size:11px;color:#888;margin-top:3px;">Active</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-xs-6" style="margin-bottom:12px;">
                        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:14px;text-align:center;">
                            <div style="font-size:22px;font-weight:700;color:#333;">{{ number_format($condoCondition['sold_30d']) }}</div>
                            <div style="font-size:11px;color:#888;margin-top:3px;">Sold (30d)</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-xs-6" style="margin-bottom:12px;">
                        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:14px;text-align:center;">
                            <div style="font-size:17px;font-weight:700;color:#333;">{{ $condoCondition['avg_sold_30d'] ? '$'.number_format($condoCondition['avg_sold_30d']) : '—' }}</div>
                            <div style="font-size:11px;color:#888;margin-top:3px;">Avg Price (30d)</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-xs-6" style="margin-bottom:12px;">
                        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:14px;text-align:center;">
                            <div style="font-size:22px;font-weight:700;color:#333;">{{ $condoCondition['avg_dom'] ?: '—' }}</div>
                            <div style="font-size:11px;color:#888;margin-top:3px;">Avg Days on Mkt</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($condoCondition['current_active'] || $condoCondition['sold_30d'])
        <div style="background:#fafaf8;border-left:4px solid {{ $condoCondition['color'] }};border-radius:4px;padding:12px 16px;font-size:13px;color:#444;line-height:1.75;margin-bottom:14px;">
            The <strong>{{ $subarea }}</strong> condo market is currently a
            <strong style="color:{{ $condoCondition['color'] }}">{{ $condoCondition['label'] }}</strong>
            @if($condoCondition['current_active']) with {{ number_format($condoCondition['current_active']) }} active condo listings @endif
            @if($condoCondition['sold_30d']) and {{ number_format($condoCondition['sold_30d']) }} condos sold in the last 30 days @endif
            @if($condoCondition['avg_sold_30d']) at an average price of ${{ number_format($condoCondition['avg_sold_30d']) }}@endif.
            @if($condoCondition['avg_dom']) Properties are selling in {{ $condoCondition['avg_dom'] }} days on average.@endif
        </div>
        @endif
        @endif

        {{-- Top Buildings --}}
        @if($topBuildings && count($topBuildings))
        <h3 style="font-size:15px;font-weight:700;color:#333;margin:16px 0 10px;">Top Condo Buildings in {{ $subarea }}</h3>
        <div class="row">
            @foreach($topBuildings as $bldg)
            <div class="col-md-4 col-sm-6" style="margin-bottom:14px;">
                <a href="/building/{{ $bldg->slug }}" style="text-decoration:none;color:inherit;">
                    <div style="border:1px solid #e2dbd2;border-radius:5px;padding:14px 16px;background:#fff;box-shadow:0 1px 5px rgba(0,0,0,.06);height:100%;">
                        <div style="font-size:14px;font-weight:700;color:#2c2c2c;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $bldg->name }}</div>
                        <div style="font-size:12px;color:#888;">
                            {{ $bldg->street_no }} {{ $bldg->street_name }} {{ $bldg->street_type }}
                        </div>
                        <div style="font-size:12px;color:#777;margin-top:4px;display:flex;gap:12px;flex-wrap:wrap;">
                            @if($bldg->units_in_strata)<span>{{ $bldg->units_in_strata }} units</span>@endif
                            @if($bldg->yearbuilt)<span>Built {{ $bldg->yearbuilt }}</span>@endif
                            @if($bldg->levels && $bldg->levels > 1)<span>{{ $bldg->levels }} floors</span>@endif
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        @endif

        <div style="margin-top:10px;font-size:13px;">
            <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}?type=Apartment" style="margin-right:16px;color:#2c6fad;font-weight:600;">See all {{ $subarea }} condos for sale &rsaquo;</a>
            <a href="/market-stats/{{ $citySlug }}/{{ $subareaSlug }}/condos" style="color:#555;">Full condo market stats &rsaquo;</a>
        </div>
    </section>

    {{-- HOUSE SECTION --}}
    <section style="margin-bottom:30px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:20px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">{{ $subarea }} Houses &amp; Townhouses</h2>

        @if($houseCondition['label'])
        <div class="row">
            <div class="col-md-4">
                <div class="verdict-card {{ $houseCondition['class'] }}" style="border-left:5px solid {{ $houseCondition['color'] }};background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:18px 20px;margin-bottom:16px;">
                    <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;margin-bottom:5px;">House Market Verdict</div>
                    <div style="font-size:20px;font-weight:700;color:{{ $houseCondition['color'] }};margin-bottom:8px;">{{ $houseCondition['label'] }}</div>
                    <div style="font-size:13px;color:#555;line-height:1.8;">
                        <div>Absorption: <strong>{{ $houseCondition['absorption_rate'] }}%</strong></div>
                        <div>Avg DOM: <strong>{{ $houseCondition['avg_dom'] ?: '—' }} days</strong></div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="row">
                    <div class="col-sm-3 col-xs-6" style="margin-bottom:12px;">
                        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:14px;text-align:center;">
                            <div style="font-size:22px;font-weight:700;color:#333;">{{ number_format($houseCondition['current_active']) }}</div>
                            <div style="font-size:11px;color:#888;margin-top:3px;">Active</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-xs-6" style="margin-bottom:12px;">
                        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:14px;text-align:center;">
                            <div style="font-size:22px;font-weight:700;color:#333;">{{ number_format($houseCondition['sold_30d']) }}</div>
                            <div style="font-size:11px;color:#888;margin-top:3px;">Sold (30d)</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-xs-6" style="margin-bottom:12px;">
                        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:14px;text-align:center;">
                            <div style="font-size:17px;font-weight:700;color:#333;">{{ $houseCondition['avg_sold_30d'] ? '$'.number_format($houseCondition['avg_sold_30d']) : '—' }}</div>
                            <div style="font-size:11px;color:#888;margin-top:3px;">Avg Price (30d)</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-xs-6" style="margin-bottom:12px;">
                        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:14px;text-align:center;">
                            <div style="font-size:22px;font-weight:700;color:#333;">{{ $houseCondition['avg_dom'] ?: '—' }}</div>
                            <div style="font-size:11px;color:#888;margin-top:3px;">Avg Days on Mkt</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($houseCondition['current_active'] || $houseCondition['sold_30d'])
        <div style="background:#fafaf8;border-left:4px solid {{ $houseCondition['color'] }};border-radius:4px;padding:12px 16px;font-size:13px;color:#444;line-height:1.75;margin-bottom:14px;">
            The <strong>{{ $subarea }}</strong> house market is currently a
            <strong style="color:{{ $houseCondition['color'] }}">{{ $houseCondition['label'] }}</strong>
            @if($houseCondition['current_active']) with {{ number_format($houseCondition['current_active']) }} active house listings @endif
            @if($houseCondition['sold_30d']) and {{ number_format($houseCondition['sold_30d']) }} houses sold in the last 30 days @endif
            @if($houseCondition['avg_sold_30d']) at an average of ${{ number_format($houseCondition['avg_sold_30d']) }}@endif.
        </div>
        @endif
        @endif

        <div style="margin-top:10px;font-size:13px;">
            <a href="/houses/{{ $citySlug }}/{{ $subareaSlug }}/" style="color:#2c6fad;font-weight:600;">&#8594; {{ $subarea }} House Market Guide &amp; Stats</a>
        </div>

        @if($houseListings && count($houseListings))
        <h3 style="font-size:15px;font-weight:700;color:#333;margin:10px 0 10px;">Recent House &amp; Townhouse Listings in {{ $subarea }}</h3>
        <div class="row">
            @foreach($houseListings as $lst)
            @php $lstPhoto = $lst->mainpicurl ?? null; @endphp
            <div class="col-md-3 col-sm-6" style="margin-bottom:16px;">
                <a href="/listing/{{ $lst->slug }}" style="text-decoration:none;color:inherit;">
                    <div style="border:1px solid #e2dbd2;border-radius:5px;overflow:hidden;background:#fff;box-shadow:0 1px 5px rgba(0,0,0,.06);">
                        @if($lstPhoto)
                        <div style="height:130px;background:url('{{ $lstPhoto }}') center/cover no-repeat;background-color:#f0ede8;"></div>
                        @else
                        <div style="height:130px;background:url('{{asset('assets/img/no-image.jpg')}}') center/cover no-repeat;background-color:#f0ede8;"></div>
                        @endif
                        <div style="padding:10px 12px;">
                            @php
                            $lstAddr = trim(($lst->suite_no ? "#{$lst->suite_no} " : '') . $lst->street_number . ' ' . $lst->street_name . ' ' . $lst->street_type);
                            @endphp
                            <div style="font-size:13px;font-weight:700;color:#2c2c2c;margin-bottom:2px;">{{ $lst->type }}</div>
                            @if($lstAddr)<div style="font-size:11px;color:#555;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $lstAddr }}</div>@endif
                            <div style="font-size:13px;color:#2c6fad;font-weight:700;">${{ number_format($lst->listprice_2) }}</div>
                            <div style="font-size:11px;color:#888;margin-top:3px;">
                                @if($lst->bedrooms){{ $lst->bedrooms }}bd @endif
                                @if($lst->bathrooms){{ $lst->bathrooms }}ba @endif
                                @if($lst->livingarea_2){{ number_format($lst->livingarea_2) }} sqft @endif
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        @endif

        <div style="margin-top:10px;font-size:13px;">
            <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}?type=House" style="margin-right:16px;color:#2c6fad;font-weight:600;">See all {{ $subarea }} houses for sale &rsaquo;</a>
            <a href="/market-stats/{{ $citySlug }}/{{ $subareaSlug }}/houses" style="color:#555;">Full house market stats &rsaquo;</a>
        </div>
    </section>

    {{-- RECENTLY SOLD --}}
    @if(!empty($recentSold) && count($recentSold))
    <section style="margin-bottom:30px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:20px;font-weight:700;color:#2c2c2c;margin-bottom:6px;">Recently Sold in {{ $subarea }}</h2>
        <p style="font-size:13px;color:#888;margin-bottom:14px;">Recent sales in {{ $subarea }} — sign in to view sold prices.</p>
        <div class="row">
            @foreach($recentSold as $sold)
            @php
                $soldPhoto = $sold->mainpicurl ?? null;
                $soldAddr  = trim(($sold->suite_no ? "#{$sold->suite_no} " : '') . $sold->street_number . ' ' . $sold->street_name . ' ' . $sold->street_type);
                $soldDateFmt = $sold->sold_date ? \Carbon\Carbon::parse($sold->sold_date)->format('M j, Y') : null;
            @endphp
            <div class="col-md-3 col-sm-6" style="margin-bottom:16px;">
                <div style="border:1px solid #e2dbd2;border-radius:5px;overflow:hidden;background:#fff;box-shadow:0 1px 5px rgba(0,0,0,.06);">
                    <a href="/listing/{{ $sold->slug }}" style="text-decoration:none;display:block;">
                        @if($soldPhoto)
                        <div style="height:130px;background:url('{{ $soldPhoto }}') center/cover no-repeat;background-color:#f0ede8;"></div>
                        @else
                        <div style="height:130px;background:url('{{asset('assets/img/no-image.jpg')}}') center/cover no-repeat;background-color:#f0ede8;"></div>
                        @endif
                    </a>
                    <div style="padding:10px 12px;">
                        <a href="/listing/{{ $sold->slug }}" style="text-decoration:none;color:inherit;display:block;">
                            <div style="font-size:12px;font-weight:700;color:#2c2c2c;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $soldAddr ?: $sold->type }}</div>
                            <div style="font-size:11px;color:#777;margin-bottom:4px;">{{ $sold->type }}</div>
                            <div style="font-size:11px;color:#888;">
                                @if($sold->bedrooms){{ $sold->bedrooms }}bd @endif
                                @if($sold->bathrooms){{ $sold->bathrooms }}ba @endif
                                @if($sold->livingarea_2){{ number_format($sold->livingarea_2) }} sqft @endif
                            </div>
                            @if($soldDateFmt)<div style="font-size:11px;color:#c0392b;margin-top:4px;font-weight:600;">Sold {{ $soldDateFmt }}</div>@endif
                        </a>
                        <div style="margin-top:8px;">
                            <a href="/listing/{{ $sold->slug }}" style="display:inline-block;font-size:11px;font-weight:600;color:#fff;background:#2c6fad;border-radius:3px;padding:4px 10px;text-decoration:none;">&#128274; View Sold Price</a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- MARKET TRENDS (Sparkline) --}}
    <section style="margin-bottom:30px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:20px;font-weight:700;color:#2c2c2c;margin-bottom:6px;">Market Trends</h2>
        <p style="font-size:13px;color:#888;margin-bottom:14px;">12-month average sold price — Condos vs Houses</p>

        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:20px;">
            <div id="ng-sparkline" style="min-height:160px;"></div>
            @if(!$sparklineData)
            <div style="text-align:center;padding:40px 0;color:#bbb;font-size:13px;">Price trend data not available for this neighbourhood.</div>
            @endif
        </div>

        <div style="margin-top:10px;font-size:13px;color:#666;">
            <a href="/market-report/{{ $citySlug }}/{{ $subareaSlug }}" style="color:#2c6fad;">View monthly market reports for {{ $subarea }} &rsaquo;</a>
        </div>
    </section>

    {{-- ABOUT SECTION --}}
    <section style="margin-bottom:30px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:20px;font-weight:700;color:#2c2c2c;margin-bottom:12px;">About {{ $subarea }}</h2>
        <div style="font-size:14px;color:#444;line-height:1.8;max-width:820px;">
            <p>
                <strong>{{ $subarea }}</strong> is a neighbourhood in <strong>{{ $city }}, BC</strong>.
                @if($buildingCount) The area has <strong>{{ $buildingCount }}</strong> tracked residential buildings in the Hani & Les | BC Condos And Homes database.@endif
                @if($avgYear) The average year of construction is <strong>{{ $avgYear }}</strong>, reflecting the neighbourhood's development timeline.@endif
                @if($condoAvg && $houseAvg)
                Condo prices average <strong>${{ number_format($condoAvg) }}</strong> while detached houses average <strong>${{ number_format($houseAvg) }}</strong> based on sales in the last 30 days.
                @elseif($condoAvg)
                Condo prices average <strong>${{ number_format($condoAvg) }}</strong> based on recent sales.
                @elseif($houseAvg)
                Houses average <strong>${{ number_format($houseAvg) }}</strong> based on recent sales.
                @endif
            </p>
            @if($avgLat && $avgLng)
            <p style="margin-top:10px;">
                <a href="https://www.google.com/maps/search/{{ urlencode($subarea.', '.$city.', BC') }}" target="_blank" rel="noopener" style="color:#2c6fad;">View {{ $subarea }} on Google Maps &rsaquo;</a>
            </p>
            @endif
        </div>
    </section>

    {{-- What's Nearby widget --}}
    @if(!empty($avgLat) && !empty($avgLng))
    <section style="margin-bottom:30px;padding-top:16px;border-top:1px solid #eee;">
        @include('frontend.includes.nearby_amenities', [
            'nearbyLat'       => $avgLat,
            'nearbyLng'       => $avgLng,
            'nearbyRadius'    => 1500,
            'nearbyTitle'     => "What's Nearby in {$subarea}",
            'nearbyCacheSlug' => 'ngh-' . ($citySlug ?? '') . '-' . ($subareaSlug ?? ''),
            'nearbyAddress'   => ($subarea ?? '') . ', ' . ($city ?? '') . ' BC',
        ])
    </section>
    @endif

    {{-- NEARBY NEIGHBOURHOODS --}}
    @if($nearbySubareas && count($nearbySubareas))
    <section style="margin-bottom:30px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:20px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">Nearby Neighbourhoods in {{ $city }}</h2>
        <div class="row">
            @foreach($nearbySubareas as $ns)
            @php
                $nsCond = $nearbyStats[$ns->place] ?? ['label' => null, 'color' => '#888', 'avg_sold_30d' => 0, 'current_active' => 0];
                $nsSlug = App\Helpers\Helper::enslugPlace($ns->place);
            @endphp
            <div class="col-md-4 col-sm-6" style="margin-bottom:14px;">
                <a href="/neighbourhood/{{ $citySlug }}/{{ $nsSlug }}/" style="text-decoration:none;color:inherit;">
                    <div style="border:1px solid #e2dbd2;border-radius:5px;padding:14px 16px;background:#fff;box-shadow:0 1px 5px rgba(0,0,0,.05);display:flex;align-items:center;gap:10px;">
                        <div style="flex:1;">
                            <div style="font-size:14px;font-weight:700;color:#2c2c2c;">{{ $ns->label ?? $ns->place }}</div>
                            @if($nsCond['avg_sold_30d'])<div style="font-size:12px;color:#666;margin-top:2px;">Avg ${{ number_format($nsCond['avg_sold_30d']) }}</div>@endif
                        </div>
                        @if($nsCond['label'])
                        <span style="font-size:10px;font-weight:700;color:#fff;background:{{ $nsCond['color'] }};border-radius:3px;padding:2px 6px;white-space:nowrap;">{{ $nsCond['label'] }}</span>
                        @endif
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- INTERNAL LINKS FOOTER --}}
    <div style="margin-top:20px;padding:18px 0;border-top:1px solid #eee;font-size:13px;color:#666;line-height:2;">
        <strong>{{ $subarea }} links:</strong>
        <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}" style="margin:0 10px;">All Listings</a>
        <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}?type=Apartment" style="margin:0 10px;">Condos</a>
        <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}?type=House" style="margin:0 10px;">Houses</a>
        <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}?type=Townhouse" style="margin:0 10px;">Townhouses</a>
        <a href="/houses/{{ $citySlug }}/{{ $subareaSlug }}/" style="margin:0 10px;">House Market</a>
        <a href="/market-stats/{{ $citySlug }}/{{ $subareaSlug }}" style="margin:0 10px;">Market Stats</a>
        <a href="/market-report/{{ $citySlug }}/{{ $subareaSlug }}" style="margin:0 10px;">Market Reports</a>
        <a href="/buildings/{{ $citySlug }}/{{ $subareaSlug }}" style="margin:0 10px;">Buildings</a>
        <a href="/neighbourhood/{{ $citySlug }}/" style="margin:0 10px;">{{ $city }} Neighbourhoods</a>
        <a href="/neighbourhood/" style="margin:0 10px;">All Neighbourhoods</a>
    </div>
</div>

@include('frontend.includes.hani_bubble')
@include('frontend.includes.footer_links')
@include('frontend.includes.footer')

<script src="{{ asset('widget/home-evaluation.js') }}"
    data-placement="floating"
    data-label="Free Home Evaluation"
    data-city="{{ $city }}"
    data-neighbourhood="{{ $subarea }}">
</script>

@unless(!$allActive)
<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
    data-neighbourhood="{{ $subarea }}"
    data-city="{{ $city }}"
    data-market-type="{{ $marketTypeSlug }}"
    data-avg-price="{{ $avgPriceFormatted }}"
    data-avg-dom="{{ $avgDomFormatted }}"
    data-active-listings="{{ $allCondition['current_active'] ?? 0 }}"
    data-absorption-rate="{{ $absorptionFormatted }}"
    data-sold-30d="{{ $allCondition['sold_30d'] ?? 0 }}"
    data-buyers="{{ $buyersEst }}"
></script>
@endunless

@endsection

@push('after-styles')
<style>
.verdict-card { transition: box-shadow .15s; }
</style>
@endpush

@push('after-scripts')
@if($sparklineData && isset($sparklineData['condo']) && isset($sparklineData['house']))
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
<script>
(function(){
    var el = document.getElementById('ng-sparkline');
    if (!el) return;

    var labels  = @json($sparklineLabels);
    var condos  = @json($sparklineData['condo']);
    var houses  = @json($sparklineData['house']);

    var series = [];
    var hasCondoData  = condos.some(function(v){ return v > 0; });
    var hasHouseData  = houses.some(function(v){ return v > 0; });

    if (hasCondoData)  series.push({ name: 'Condo Avg Price', data: condos });
    if (hasHouseData)  series.push({ name: 'House Avg Price', data: houses });

    if (!series.length) {
        el.innerHTML = '<div style="text-align:center;padding:40px 0;color:#bbb;font-size:13px;">No price data available.</div>';
        return;
    }

    var chart = new ApexCharts(el, {
        chart: { type: 'area', height: 160, toolbar: { show: false }, sparkline: { enabled: false }, zoom: { enabled: false } },
        series: series,
        xaxis: { categories: labels, labels: { style: { fontSize: '11px', colors: '#aaa' } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: {
            labels: {
                style: { fontSize: '11px', colors: '#aaa' },
                formatter: function(v){ return v ? '$' + (v/1000).toFixed(0) + 'K' : ''; }
            }
        },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: .6, opacityFrom: .3, opacityTo: 0 } },
        colors: ['#2c6fad', '#e67e22'],
        dataLabels: { enabled: false },
        legend: { show: true, position: 'top', fontSize: '12px' },
        grid: { strokeDashArray: 3, borderColor: '#f0ede8' },
        tooltip: { y: { formatter: function(v){ return v ? '$'+Number(v).toLocaleString('en-CA') : 'N/A'; } } }
    });
    chart.render();
})();
</script>
@endif
@endpush

@push('after-scripts')
<script>
window.BCTrack = window.BCTrack || {};
window.BCTrack.pageType = "subarea_report";
window.BCTrack.city     = "{{ addslashes($city ?? '') }}";
window.BCTrack.subarea  = "{{ addslashes($subarea ?? '') }}";
</script>
@endpush
