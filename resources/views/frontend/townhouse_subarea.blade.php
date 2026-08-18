@extends('frontend.layouts.default')
@php
$cityName    = $cityRecord->label    ?? $city;
$subareaName = $subareaRecord->label ?? $subarea;
$currentMonth = date('F Y');
$metaTitle   = "{$subareaName}, {$cityName} Townhouse Market Report | {$currentMonth} | Hani & Les";
$metaDesc    = ($cond['current_active'] ? "Browse " . number_format($cond['current_active']) . " MLS® townhouses for sale in {$subareaName}, {$cityName}." : "Browse MLS® townhouses for sale in {$subareaName}, {$cityName}.");
if ($cond['avg_sold_30d'] && !$cond['insufficient_data']) $metaDesc .= " Avg sold price \$" . number_format($cond['avg_sold_30d']) . ".";
if ($cond['label']) $metaDesc .= " Currently a {$cond['label']}.";
$metaDesc .= " Listings updated daily from MLS® records.";
$canonicalUrl = "https://www.bccondosandhomes.com/townhouses/{$citySlug}/{$subareaSlug}/";

$sparklineLabels = [];
$sparklineData   = [];
$suffixes = ['one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve'];
if ($townhouseRow) {
    foreach ($suffixes as $s) {
        $sparklineLabels[] = substr($townhouseRow->{'month_'.$s} ?? '', 0, 3);
        $sparklineData[]   = (int)($townhouseRow->{'avg_price_'.$s} ?? 0);
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
        {"@type":"ListItem","position":2,"name":"Townhouses","item":"https://www.bccondosandhomes.com/townhouses/"},
        {"@type":"ListItem","position":3,"name":"{{ e($cityName) }} Townhouses","item":"https://www.bccondosandhomes.com/townhouses/{{ $citySlug }}/"},
        {"@type":"ListItem","position":4,"name":"{{ e($subareaName) }}","item":"{{ $canonicalUrl }}"}
      ]
    },
    {
      "@type": "FAQPage",
      "mainEntity": [
        {"@type":"Question","name":"What is the average townhouse price in {{ e($subareaName) }}, {{ e($cityName) }}?","acceptedAnswer":{"@type":"Answer","text":"{{ !$cond['insufficient_data'] && $cond['avg_sold_30d'] ? 'The average townhouse price in '.$subareaName.', '.$cityName.' is $'.number_format($cond['avg_sold_30d']).' based on '.number_format($cond['sold_30d']).' sales in the last 30 days.' : 'Insufficient recent townhouse sales data for '.$subareaName.'. See '.$cityName.' city-level stats for a broader view.' }}"}},
        {"@type":"Question","name":"Is {{ e($subareaName) }} a buyer's or seller's market for townhouses?","acceptedAnswer":{"@type":"Answer","text":"{{ !$cond['insufficient_data'] && $cond['label'] ? $subareaName.' is currently a '.$cond['label'].' for townhouses, with an absorption rate of '.$cond['absorption_rate'].'%.' : 'Insufficient data to determine market conditions for '.$subareaName.' townhouses.' }}"}}
      ]
    }
  ]
}
</script>
@endsection

@section('content')
@include('frontend.includes.header')

<div class="page-main" style="padding:30px 0 16px;background:linear-gradient(135deg,#1a2a3a 0%,#2c3e50 100%);color:#fff;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:10px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/" style="color:rgba(255,255,255,.7);">Home</a></li>
                <li class="breadcrumb-item"><a href="/townhouses/" style="color:rgba(255,255,255,.7);">Townhouses</a></li>
                <li class="breadcrumb-item"><a href="/townhouses/{{ $citySlug }}/" style="color:rgba(255,255,255,.7);">{{ $cityName }}</a></li>
                <li class="breadcrumb-item active" style="color:#fff;">{{ $subareaName }}</li>
            </ol>
        </nav>
        <h1 style="font-size:26px;font-weight:700;margin-bottom:10px;color:#fff;">{{ date('F Y') }} {{ $subareaName }}, {{ $cityName }} Townhouse Market Report</h1>
        <p style="font-size:14px;color:rgba(255,255,255,.82);max-width:820px;line-height:1.65;margin-bottom:10px;">
            @if(!$cond['insufficient_data'] && $cond['label'])
                <strong style="color:#fff;">{{ $cond['label'] }}</strong>
                @if($cond['avg_sold_30d']) · avg townhouse price ${{ number_format($cond['avg_sold_30d']) }}@endif
                @if($cond['sold_30d']) · {{ number_format($cond['sold_30d']) }} sold (30d)@endif
                @if($cond['avg_dom']) · {{ $cond['avg_dom'] }} days on market @endif
            @else
                Townhouses for sale in {{ $subareaName }}, {{ $cityName }}.
            @endif
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:10px;font-size:13px;">
            <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}?type=Townhouse" style="background:#e74c3c;color:#fff;border-radius:4px;padding:7px 16px;text-decoration:none;font-weight:700;">Townhouses For Sale in {{ $subareaName }}</a>
            <a href="/neighbourhood/{{ $citySlug }}/{{ $subareaSlug }}/" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:4px;padding:7px 14px;text-decoration:none;">Neighbourhood Guide</a>
            <a href="/townhouses/{{ $citySlug }}/" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:4px;padding:7px 14px;text-decoration:none;">All {{ $cityName }} Townhouses</a>
        </div>
    </div>
</div>

<div class="container" style="padding-top:24px;padding-bottom:50px;min-height:60vh;">

    {{-- VERDICT / STATS --}}
    @if(!$cond['insufficient_data'] && $cond['label'])
    <div class="row" style="margin-bottom:22px;">
        <div class="col-md-4">
            <div style="border-left:5px solid {{ $cond['color'] }};background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:18px 20px;height:100%;">
                <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px;">The Verdict — {{ $subareaName }}</div>
                <div style="font-size:20px;font-weight:700;color:{{ $cond['color'] }};margin-bottom:10px;">{{ $cond['label'] }}</div>
                <div style="font-size:13px;color:#444;line-height:2;">
                    @if($cond['sold_30d'] && $cond['current_active'])
                    <div>• {{ number_format($cond['sold_30d']) }} sold vs {{ number_format($cond['current_active']) }} active</div>
                    @endif
                    @if($cond['absorption_rate'])<div>• {{ $cond['absorption_rate'] }}% absorption rate</div>@endif
                    @if($cond['avg_dom'])<div>• {{ $cond['avg_dom'] }} avg days on market</div>@endif
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="row">
                @foreach([
                    ['Avg Townhouse Price', $cond['avg_sold_30d'] ? '$'.number_format($cond['avg_sold_30d']) : '—', '30-day avg'],
                    ['Sold (30d)', number_format($cond['sold_30d']), 'townhouses'],
                    ['Avg DOM', $cond['avg_dom'] ? $cond['avg_dom'].'d' : '—', 'days on market'],
                    ['Active', number_format($cond['current_active']), 'for sale now'],
                ] as $tile)
                <div class="col-sm-3 col-xs-6" style="margin-bottom:12px;">
                    <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:14px 12px;text-align:center;">
                        <div style="font-size:{{ strlen($tile[1]) > 5 ? '17' : '22' }}px;font-weight:700;color:#2c2c2c;">{{ $tile[1] }}</div>
                        <div style="font-size:11px;color:#888;margin-top:3px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">{{ $tile[0] }}</div>
                        <div style="font-size:10px;color:#bbb;margin-top:1px;">{{ $tile[2] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    @if($editorial)
    <div style="background:#fafaf8;border-left:4px solid {{ $cond['color'] }};border-radius:4px;padding:16px 20px;font-size:14px;color:#444;line-height:1.8;margin-bottom:22px;">
        <h2 style="font-size:15px;font-weight:700;color:#333;margin:0 0 8px;">About the {{ $subareaName }} Townhouse Market</h2>
        <p style="margin:0;">{!! $editorial !!}</p>
    </div>
    @endif
    @else
    <div style="background:#fafaf8;border:1px solid #e2dbd2;border-radius:5px;padding:16px 20px;font-size:14px;color:#888;margin-bottom:22px;">
        @if($cond['sold_90d'] < 3)
        Fewer than 3 townhouses sold in {{ $subareaName }} in the past 90 days — not enough data to calculate reliable market statistics.
        @else
        Market data is currently unavailable for {{ $subareaName }}.
        @endif
        <a href="/townhouses/{{ $citySlug }}/" style="color:#2c6fad;margin-left:8px;">View {{ $cityName }} townhouse market overview →</a>
    </div>
    @endif

    {{-- RECENT LISTINGS --}}
    @if($recentListings && count($recentListings))
    <section style="margin-bottom:28px;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">Townhouses For Sale in {{ $subareaName }}</h2>
        <div class="row">
            @foreach($recentListings as $lst)
            @php
                $photo = $lst->aphoto ? 'https://media.pixilinkserver.com/' . str_replace('images', '', $lst->aphoto->directory . $lst->aphoto->name) : null;
                $addr  = trim(($lst->street_number ? $lst->street_number.' ' : '') . ($lst->street_name ?? '') . ($lst->street_type ? ' '.$lst->street_type : ''));
            @endphp
            <div class="col-md-4 col-sm-6" style="margin-bottom:14px;">
                <a href="/listing/{{ $lst->slug }}" style="text-decoration:none;color:inherit;">
                    <div style="border:1px solid #e2dbd2;border-radius:5px;overflow:hidden;background:#fff;box-shadow:0 1px 5px rgba(0,0,0,.06);">
                        @if($photo)
                        <div style="height:140px;background:url('{{ $photo }}') center/cover no-repeat;background-color:#f0ede8;"></div>
                        @else
                        <div style="height:140px;background:#f0ede8;display:flex;align-items:center;justify-content:center;color:#bbb;font-size:12px;">No Photo</div>
                        @endif
                        <div style="padding:10px 12px;">
                            <div style="font-size:14px;font-weight:700;color:#2c6fad;">${{ number_format($lst->listprice_2) }}</div>
                            @if($addr)<div style="font-size:12px;font-weight:600;color:#333;margin-top:2px;">{{ $addr }}</div>@endif
                            <div style="font-size:11px;color:#888;margin-top:2px;">
                                @if($lst->bedrooms){{ $lst->bedrooms }}bd @endif
                                @if($lst->bathstotal){{ $lst->bathstotal }}ba @endif
                                @if($lst->livingarea_2){{ number_format($lst->livingarea_2) }} sqft @endif
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        <div style="margin-top:4px;font-size:13px;">
            <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}?type=Townhouse" style="color:#2c6fad;font-weight:600;">See all {{ $subareaName }} townhouses for sale &rsaquo;</a>
        </div>
    </section>
    @else
    <div style="padding:20px;background:#fafaf8;border-radius:5px;margin-bottom:22px;font-size:14px;color:#888;">
        No active townhouse listings found in {{ $subareaName }} right now.
        <a href="/search-listings/{{ $citySlug }}?type=Townhouse" style="color:#2c6fad;margin-left:6px;">Search all {{ $cityName }} townhouses →</a>
    </div>
    @endif

    {{-- PRICE TREND SPARKLINE --}}
    @if($townhouseRow && count($sparklineData) && array_sum($sparklineData) > 0)
    <section style="margin-bottom:28px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:6px;">{{ $subareaName }} Townhouse Price Trend</h2>
        <p style="font-size:13px;color:#888;margin-bottom:14px;">12-month average townhouse sold price</p>
        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:16px;">
            <div id="sa-townhouse-price-trend" style="min-height:160px;"></div>
        </div>
    </section>
    @endif

    {{-- FAQ --}}
    <section class="faq-section" style="margin-bottom:28px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">{{ $subareaName }} Townhouse Market FAQ</h2>

        <div class="faq-item" onclick="this.classList.toggle('open')">
            <div class="faq-question">What is the average townhouse price in {{ $subareaName }}, {{ $cityName }}? <span class="faq-chevron">&#9660;</span></div>
            <div class="faq-answer">
                @if(!$cond['insufficient_data'] && $cond['avg_sold_30d'])
                <dl>
                    <dt><strong>Average townhouse price in {{ $subareaName }} (last 30 days):</strong></dt>
                    <dd>${{ number_format($cond['avg_sold_30d']) }} &nbsp;·&nbsp; based on {{ number_format($cond['sold_30d']) }} sales &nbsp;·&nbsp; <span style="color:#888;font-size:12px;">updated {{ date('F j, Y') }}</span></dd>
                </dl>
                @else
                <p>Insufficient recent townhouse sales in {{ $subareaName }} to calculate a reliable average price. See <a href="/townhouses/{{ $citySlug }}/" style="color:#2c6fad;">{{ $cityName }} townhouse market</a> for city-level data.</p>
                @endif
            </div>
        </div>

        <div class="faq-item" onclick="this.classList.toggle('open')">
            <div class="faq-question">Is {{ $subareaName }} a buyer's or seller's market for townhouses? <span class="faq-chevron">&#9660;</span></div>
            <div class="faq-answer">
                @if(!$cond['insufficient_data'] && $cond['label'])
                <p><strong>{{ $subareaName }} is a {{ $cond['label'] }}</strong> for townhouses (absorption rate: {{ $cond['absorption_rate'] }}%, {{ number_format($cond['sold_30d']) }} sold vs {{ number_format($cond['current_active']) }} active listings in the last 30 days).</p>
                @else
                <p>Not enough recent sales data (minimum 3 sales in 90 days required). For context, check <a href="/townhouses/{{ $citySlug }}/" style="color:#2c6fad;">{{ $cityName }} overall townhouse market conditions</a>.</p>
                @endif
            </div>
        </div>
    </section>

    {{-- NEARBY --}}
    @if($nearbySubareas && count($nearbySubareas))
    <section style="margin-bottom:28px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">Nearby {{ $cityName }} Townhouse Markets</h2>
        <div class="row">
            @foreach($nearbySubareas as $ns)
            @php $nsSlug = App\Helpers\Helper::enslugPlace($ns->place); @endphp
            <div class="col-md-4 col-sm-6" style="margin-bottom:12px;">
                <a href="/townhouses/{{ $citySlug }}/{{ $nsSlug }}/" style="text-decoration:none;color:inherit;">
                    <div style="border:1px solid #e2dbd2;border-radius:5px;padding:12px 14px;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.05);">
                        <div style="font-size:14px;font-weight:700;color:#2c2c2c;">{{ $ns->label ?? $ns->place }}</div>
                        <div style="font-size:12px;color:#2c6fad;margin-top:4px;">View townhouse market &rsaquo;</div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- INTERNAL LINKS --}}
    <div style="padding:16px 0;border-top:1px solid #eee;font-size:13px;color:#666;line-height:2;">
        <strong>Links:</strong>
        <a href="/search-listings/{{ $citySlug }}/{{ $subareaSlug }}?type=Townhouse" style="margin:0 10px;color:#2c6fad;">Townhouses For Sale</a>
        <a href="/neighbourhood/{{ $citySlug }}/{{ $subareaSlug }}/" style="margin:0 10px;">Neighbourhood Guide</a>
        <a href="/market-stats/{{ $citySlug }}/{{ $subareaSlug }}/townhouses" style="margin:0 10px;">Market Stats</a>
        <a href="/market-report/{{ $citySlug }}/{{ $subareaSlug }}" style="margin:0 10px;">Reports</a>
        <a href="/townhouses/{{ $citySlug }}/" style="margin:0 10px;">{{ $cityName }} Townhouse Market</a>
        <a href="/townhouses/" style="margin:0 10px;">All Cities</a>
        <a href="/multi-family/{{ $citySlug }}/{{ $subareaSlug }}/" style="margin:0 10px;color:#27ae60;font-weight:600;">Multi-Family Market &rsaquo;</a>
    </div>
</div>

<div class="container">
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => $subareaName . ', ' . $cityName,
        'stripSearchName' => $subareaName . ' Townhouse Listings',
        'stripSearchData' => json_encode(array_filter(['cities' => $cityName, 'subareas' => $subareaName, 'listing_status' => 'Active', 'type' => 'Townhouse'])),
        'stripCity'       => $cityName,
        'stripModalId'    => 'thSubAlert_' . $subareaSlug,
    ])
</div>

@include('frontend.includes.footer_links')
@include('frontend.includes.footer')
@endsection

@push('after-styles')
<style>
.faq-item{border:1px solid #e2dbd2;border-radius:6px;margin-bottom:8px;overflow:hidden;}
.faq-question{padding:13px 16px;cursor:pointer;font-weight:600;font-size:14px;display:flex;justify-content:space-between;align-items:center;background:#fafaf8;user-select:none;}
.faq-question:hover{background:#f3f0eb;}
.faq-chevron{font-size:12px;transition:transform .2s;color:#888;}
.faq-answer{display:none;padding:13px 16px;font-size:13.5px;line-height:1.65;color:#444;border-top:1px solid #f0ede8;background:#fff;}
.faq-item.open .faq-answer{display:block;}
.faq-item.open .faq-chevron{transform:rotate(180deg);}
</style>
@endpush

@push('after-scripts')
@if($townhouseRow && count($sparklineData) && array_sum($sparklineData) > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
<script>
(function(){
    var el = document.getElementById('sa-townhouse-price-trend');
    if (!el) return;
    new ApexCharts(el, {
        chart: { type: 'area', height: 160, toolbar: { show: false }, zoom: { enabled: false } },
        series: [{ name: 'Avg Townhouse Price', data: @json($sparklineData) }],
        xaxis: { categories: @json($sparklineLabels), labels: { style: { fontSize: '11px', colors: '#aaa' } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { fontSize: '11px', colors: '#aaa' }, formatter: function(v){ return v ? '$'+(v/1000000>=1?(v/1000000).toFixed(2)+'M':(v/1000).toFixed(0)+'K') : ''; } } },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: .25, opacityTo: 0 } },
        colors: ['#2980b9'],
        dataLabels: { enabled: false },
        grid: { strokeDashArray: 3, borderColor: '#f0ede8' },
        tooltip: { y: { formatter: function(v){ return '$'+Number(v).toLocaleString('en-CA'); } } }
    }).render();
})();
</script>
@endif
@endpush
