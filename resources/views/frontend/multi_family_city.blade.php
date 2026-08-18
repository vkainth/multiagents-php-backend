@extends('frontend.layouts.default')
@php
$cityName = $cityRecord->label ?? $city;
$currentMonth = date('F Y');
$metaTitle = "{$cityName} Multi-Family Market Report | Duplex, Triplex & Fourplex | {$currentMonth} | Hani & Les";

$metaDesc = ($overallCond['current_active'] ? "Browse " . number_format($overallCond['current_active']) . " MLS® multi-family properties (duplex, triplex, fourplex) for sale in {$cityName}, BC." : "Browse MLS® duplexes, triplexes, and fourplexes for sale in {$cityName}, BC.");
if ($overallCond['avg_sold_30d']) $metaDesc .= " Avg sold price \$" . number_format($overallCond['avg_sold_30d']) . ".";
if ($overallCond['avg_dom']) $metaDesc .= " Avg " . $overallCond['avg_dom'] . " days on market.";
if ($overallCond['label']) $metaDesc .= " Currently a " . $overallCond['label'] . ".";
$metaDesc .= " Updated daily from MLS® records.";

$canonicalUrl = "https://www.bccondosandhomes.com/multi-family/{$citySlug}/";

$sparklineLabels = [];
$sparklineData   = [];
$suffixes = ['one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve'];
if ($multiFamilyRow) {
    foreach ($suffixes as $s) {
        $sparklineLabels[] = substr($multiFamilyRow->{'month_'.$s} ?? '', 0, 3);
        $sparklineData[]   = (int)($multiFamilyRow->{'avg_price_'.$s} ?? 0);
    }
}

$typeColors = ['Duplex' => '#27ae60', 'Triplex' => '#2980b9', 'Fourplex' => '#8e44ad'];
$typeIcons  = ['Duplex' => '🏠', 'Triplex' => '🏘', 'Fourplex' => '🏢'];
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
        {"@type":"ListItem","position":2,"name":"Multi-Family Properties","item":"https://www.bccondosandhomes.com/multi-family/"},
        {"@type":"ListItem","position":3,"name":"{{ e($cityName) }}","item":"{{ $canonicalUrl }}"}
      ]
    },
    {
      "@type": "FAQPage",
      "mainEntity": [
        {"@type":"Question","name":"What is the average duplex or multi-family price in {{ e($cityName) }}?","acceptedAnswer":{"@type":"Answer","text":"{{ $overallCond['avg_sold_30d'] ? 'The average multi-family sold price in '.$cityName.' over the last 30 days is $'.number_format($overallCond['avg_sold_30d']).'. Based on '.number_format($overallCond['sold_30d']).' sales recorded in the MLS®.' : 'Insufficient recent sales data for '.$cityName.'. Please check current active listings.' }}"}},
        {"@type":"Question","name":"Is {{ e($cityName) }} a buyer's or seller's market for multi-family properties?","acceptedAnswer":{"@type":"Answer","text":"{{ $overallCond['label'] ? 'Based on current data, '.$cityName.' is a '.$overallCond['label'].' for multi-family properties. The absorption rate is '.$overallCond['absorption_rate'].'% with '.$overallCond['current_active'].' active listings and '.number_format($overallCond['sold_30d']).' sales in the last 30 days.' : 'Insufficient data to determine market conditions for '.$cityName.' multi-family properties at this time.' }}"}},
        {"@type":"Question","name":"How many duplexes are for sale in {{ e($cityName) }}?","acceptedAnswer":{"@type":"Answer","text":"{{ ($typeStats['Duplex']['current_active'] ?? 0) ? number_format($typeStats['Duplex']['current_active']).' duplexes are currently for sale in '.$cityName.' according to MLS® data.' : 'No active duplex listings found in '.$cityName.' at this time.' }}"}},
        {"@type":"Question","name":"How many fourplexes are for sale in {{ e($cityName) }}?","acceptedAnswer":{"@type":"Answer","text":"{{ ($typeStats['Fourplex']['current_active'] ?? 0) ? number_format($typeStats['Fourplex']['current_active']).' fourplexes are currently for sale in '.$cityName.' according to MLS® data.' : 'No active fourplex listings found in '.$cityName.' at this time.' }}"}}
      ]
    }
  ]
}
</script>
@endsection

@section('content')
@include('frontend.includes.header')

<div class="page-main" style="padding:30px 0 16px;background:linear-gradient(135deg,#1a3a2a 0%,#2c4e3c 100%);color:#fff;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:10px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/" style="color:rgba(255,255,255,.7);">Home</a></li>
                <li class="breadcrumb-item"><a href="/multi-family/" style="color:rgba(255,255,255,.7);">Multi-Family</a></li>
                <li class="breadcrumb-item active" style="color:#fff;">{{ $cityName }}</li>
            </ol>
        </nav>
        <h1 style="font-size:26px;font-weight:700;margin-bottom:10px;color:#fff;">{{ date('F Y') }} {{ $cityName }} Multi-Family Market Report</h1>
        <p style="font-size:14px;color:rgba(255,255,255,.82);max-width:820px;line-height:1.65;margin-bottom:10px;">
            @if($overallCond['label'] && !$overallCond['insufficient_data'])
                Market verdict: <strong style="color:#fff;">{{ $overallCond['label'] }}</strong>
                @if($overallCond['sold_30d']) · {{ number_format($overallCond['sold_30d']) }} sold (30d)@endif
                @if($overallCond['avg_sold_30d']) · avg ${{ number_format($overallCond['avg_sold_30d']) }}@endif
                @if($overallCond['avg_dom']) · {{ $overallCond['avg_dom'] }} days on market @endif
            @else
                Browse current duplex, triplex, and fourplex listings in {{ $cityName }} with live MLS® data.
            @endif
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:10px;font-size:13px;">
            <a href="/search-listings/{{ $citySlug }}?type=Duplex" style="background:#e74c3c;color:#fff;border-radius:4px;padding:7px 14px;text-decoration:none;font-weight:700;">Duplexes For Sale</a>
            <a href="/search-listings/{{ $citySlug }}?type=Triplex" style="background:#e74c3c;color:#fff;border-radius:4px;padding:7px 14px;text-decoration:none;font-weight:700;">Triplexes</a>
            <a href="/search-listings/{{ $citySlug }}?type=Fourplex" style="background:#e74c3c;color:#fff;border-radius:4px;padding:7px 14px;text-decoration:none;font-weight:700;">Fourplexes</a>
            <a href="/market-stats/{{ $citySlug }}" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:4px;padding:7px 14px;text-decoration:none;">Market Stats</a>
            <a href="/neighbourhood/{{ $citySlug }}/" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:4px;padding:7px 14px;text-decoration:none;">Neighbourhoods</a>
        </div>
    </div>
</div>

<div class="container" style="padding-top:24px;padding-bottom:50px;min-height:60vh;">

    {{-- OVERALL VERDICT --}}
    @if(!$overallCond['insufficient_data'] && $overallCond['label'])
    <div class="row" style="margin-bottom:22px;">
        <div class="col-md-4">
            <div style="border-left:5px solid {{ $overallCond['color'] }};background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px 22px;height:100%;">
                <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px;">Overall Verdict — {{ $cityName }} Multi-Family</div>
                <div style="font-size:22px;font-weight:700;color:{{ $overallCond['color'] }};margin-bottom:12px;">{{ $overallCond['label'] }}</div>
                <div style="font-size:13px;color:#444;line-height:2;">
                    @if($overallCond['sold_30d'] && $overallCond['current_active'])
                    <div>• {{ number_format($overallCond['sold_30d']) }} sold vs {{ number_format($overallCond['current_active']) }} active ({{ $overallCond['absorption_rate'] }}% absorption)</div>
                    @endif
                    @if($overallCond['avg_dom'])
                    <div>• Avg days on market: <strong>{{ $overallCond['avg_dom'] }} days</strong></div>
                    @endif
                    @if($overallCond['avg_sold_30d'] && $overallCond['price_trend'] != 0)
                    <div>• Avg price {{ $overallCond['price_trend'] > 0 ? 'up' : 'down' }} <strong style="color:{{ $overallCond['price_trend'] > 0 ? '#c0392b' : '#27ae60' }}">{{ abs($overallCond['price_trend']) }}%</strong> vs 90-day avg</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="row">
                @foreach([
                    ['Active Listings', number_format($overallCond['current_active']), 'duplex/triplex/fourplex'],
                    ['Avg Sold Price', $overallCond['avg_sold_30d'] ? '$'.number_format($overallCond['avg_sold_30d']) : '—', '30-day avg'],
                    ['Sold (30d)', number_format($overallCond['sold_30d']), 'properties'],
                    ['Avg DOM', $overallCond['avg_dom'] ? $overallCond['avg_dom'].'d' : '—', 'days on market'],
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
    <div style="background:#fafaf8;border-left:4px solid {{ $overallCond['color'] }};border-radius:4px;padding:16px 20px;font-size:14px;color:#444;line-height:1.8;margin-bottom:22px;">
        <h2 style="font-size:15px;font-weight:700;color:#333;margin:0 0 8px;">The {{ $cityName }} Multi-Family Market Explained</h2>
        <p style="margin:0;">{!! $editorial !!}</p>
    </div>
    @endif
    @else
    <div style="background:#fafaf8;border:1px solid #e2dbd2;border-radius:5px;padding:16px 20px;font-size:14px;color:#888;margin-bottom:22px;">
        Insufficient recent multi-family sales data for {{ $cityName }} to determine overall market conditions.
        <a href="/search-listings/{{ $citySlug }}?type=Duplex" style="color:#2c6fad;margin-left:8px;">Browse active listings →</a>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- PER-TYPE SECTIONS: DUPLEX / TRIPLEX / FOURPLEX                      --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @foreach(['Duplex', 'Triplex', 'Fourplex'] as $mfType)
    @php
        $tc      = $typeStats[$mfType] ?? ['insufficient_data'=>true,'current_active'=>0,'sold_30d'=>0,'avg_sold_30d'=>0,'avg_dom'=>0,'label'=>null,'color'=>'#888','avg_list_price'=>0,'avg_price_sqft'=>0,'absorption_rate'=>0,'price_trend'=>0];
        $tLst    = $typeListings[$mfType] ?? collect([]);
        $tCol    = $typeColors[$mfType];
        $tIcn    = $typeIcons[$mfType];
        $tSlg    = strtolower($mfType);
        $tPlural = ['Duplex'=>'Duplexes','Triplex'=>'Triplexes','Fourplex'=>'Fourplexes'][$mfType];
    @endphp
    <section style="margin-bottom:16px;padding-top:16px;border-top:3px solid {{ $tCol }};">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <span style="font-size:22px;">{{ $tIcn }}</span>
            <h2 style="font-size:19px;font-weight:700;color:#2c2c2c;margin:0;">{{ $tPlural }} for Sale in {{ $cityName }}</h2>
            @if($tc['current_active'])
            <span style="background:{{ $tCol }};color:#fff;border-radius:4px;padding:3px 10px;font-size:12px;font-weight:700;">{{ $tc['current_active'] }} active</span>
            @endif
        </div>

        {{-- Stats tiles for this type --}}
        @if(!$tc['insufficient_data'] || $tc['current_active'] > 0)
        <div class="row" style="margin-bottom:14px;">
            @foreach([
                ['Avg List Price', $tc['avg_list_price'] ? '$'.number_format($tc['avg_list_price']) : '—', 'active listings'],
                ['Avg Sold Price', $tc['avg_sold_30d']   ? '$'.number_format($tc['avg_sold_30d'])   : '—', '30-day avg'],
                ['$/sqft', $tc['avg_price_sqft'] ? '$'.number_format($tc['avg_price_sqft']) : '—', 'active listings'],
                ['Sold (30d)', $tc['sold_30d'] ? number_format($tc['sold_30d']) : '—', $mfType.'s'],
                ['Avg DOM', $tc['avg_dom'] ? $tc['avg_dom'].'d' : '—', 'days on market'],
                ['Market', $tc['label'] ?? '—', 'condition'],
            ] as $tile)
            <div class="col-sm-2 col-xs-4" style="margin-bottom:10px;">
                <div style="background:#fff;border-radius:5px;box-shadow:0 1px 5px rgba(0,0,0,.07);padding:12px 10px;text-align:center;border-top:3px solid {{ $tCol }};">
                    <div style="font-size:{{ strlen($tile[1]) > 6 ? '13' : '18' }}px;font-weight:700;color:#2c2c2c;">{{ $tile[1] }}</div>
                    <div style="font-size:10px;color:#888;margin-top:2px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">{{ $tile[0] }}</div>
                    <div style="font-size:10px;color:#ccc;margin-top:1px;">{{ $tile[2] }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p style="color:#888;font-size:13px;margin-bottom:12px;">
            No recent {{ $mfType }} sales data for {{ $cityName }}.
            <a href="/search-listings/{{ $citySlug }}?type={{ $mfType }}" style="color:#2c6fad;">Browse active {{ $tPlural }} →</a>
        </p>
        @endif

        {{-- Listing cards for this type --}}
        @if($tLst && count($tLst))
        <div class="row" style="margin-bottom:10px;">
            @foreach($tLst as $lst)
            @php
                $photo = $lst->aphoto ? 'https://media.pixilinkserver.com/' . str_replace('images', '', $lst->aphoto->directory . $lst->aphoto->name) : null;
                $addr  = trim(($lst->street_number ? $lst->street_number.' ' : '') . ($lst->street_name ?? '') . ($lst->street_type ? ' '.$lst->street_type : ''));
            @endphp
            <div class="col-md-4 col-sm-6" style="margin-bottom:12px;">
                <a href="/listing/{{ $lst->slug }}" style="text-decoration:none;color:inherit;">
                    <div style="border:1px solid #e2dbd2;border-radius:5px;overflow:hidden;background:#fff;box-shadow:0 1px 5px rgba(0,0,0,.06);">
                        @if($photo)
                        <div style="height:130px;background:url('{{ $photo }}') center/cover no-repeat;background-color:#f0ede8;"></div>
                        @else
                        <div style="height:130px;background:#f0ede8;display:flex;align-items:center;justify-content:center;color:#bbb;font-size:12px;">No Photo</div>
                        @endif
                        <div style="padding:10px 12px;">
                            <div style="font-size:14px;font-weight:700;color:#2c6fad;">${{ number_format($lst->listprice_2) }}</div>
                            @if($addr)<div style="font-size:12px;font-weight:600;color:#333;margin-top:2px;">{{ $addr }}</div>@endif
                            <div style="font-size:11px;color:#888;margin-top:2px;">
                                @if($lst->bedrooms){{ $lst->bedrooms }}bd @endif
                                @if($lst->bathstotal){{ $lst->bathstotal }}ba @endif
                                @if($lst->livingarea_2){{ number_format($lst->livingarea_2) }} sqft @endif
                            </div>
                            @if($lst->subarea)<div style="font-size:11px;color:#aaa;margin-top:1px;">{{ $lst->subarea }}</div>@endif
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        @endif
        <div style="font-size:13px;">
            <a href="/search-listings/{{ $citySlug }}?type={{ $mfType }}" style="color:{{ $tCol }};font-weight:600;">See all {{ $cityName }} {{ $tPlural }} for sale &rsaquo;</a>
            &nbsp;·&nbsp;
            <a href="/search-listings/{{ $citySlug }}?type={{ $mfType }}&listing_status=sold" style="color:#888;font-size:12px;">Sold {{ $tPlural }}</a>
        </div>
    </section>
    @endforeach

    {{-- SUBAREA BREAKDOWN TABLE --}}
    @if($subareaStats && count($subareaStats))
    <section style="margin-bottom:28px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">{{ $cityName }} Multi-Family Market by Neighbourhood</h2>
        <div style="background:#fff;border:1px solid #e2dbd2;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;">
            <div class="table-responsive">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:#f7f4ef;text-align:left;">
                            <th style="padding:10px 14px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#888;">Neighbourhood</th>
                            <th style="padding:10px 14px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#888;">Active</th>
                            <th style="padding:10px 14px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#888;">Avg Sold Price</th>
                            <th style="padding:10px 14px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#888;">Sold (30d)</th>
                            <th style="padding:10px 14px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#888;">Avg DOM</th>
                            <th style="padding:10px 14px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#888;">Market</th>
                            <th style="padding:10px 14px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subareaStats as $saName => $saCond)
                        @php
                            $saSlug = App\Helpers\Helper::enslugPlace($saName);
                            $saRecord = $subareas[$saName] ?? null;
                            $saLabel = $saRecord ? ($saRecord->label ?? $saName) : $saName;
                        @endphp
                        <tr style="border-top:1px solid #f0ede8;">
                            <td style="padding:10px 14px;font-weight:600;color:#2c2c2c;">
                                <a href="/multi-family/{{ $citySlug }}/{{ $saSlug }}/" style="color:#2c2c2c;text-decoration:none;">{{ $saLabel }}</a>
                            </td>
                            <td style="padding:10px 14px;font-weight:600;color:#1a3a2a;">{{ $saCond['current_active'] ?: '—' }}</td>
                            <td style="padding:10px 14px;">
                                @if($saCond['avg_sold_30d'])
                                @php $p = $saCond['avg_sold_30d']; @endphp
                                ${{ $p >= 1000000 ? number_format($p/1000000, 2).'M' : number_format(round($p/1000)).'K' }}
                                @else<span style="color:#bbb;">—</span>@endif
                            </td>
                            <td style="padding:10px 14px;color:#555;">{{ $saCond['sold_30d'] ?: '—' }}</td>
                            <td style="padding:10px 14px;color:#555;">{{ $saCond['avg_dom'] ? $saCond['avg_dom'].'d' : '—' }}</td>
                            <td style="padding:10px 14px;">
                                @if($saCond['label'] && !$saCond['insufficient_data'])
                                <span style="background:{{ $saCond['color'] }};color:#fff;border-radius:3px;padding:2px 7px;font-size:10px;font-weight:700;white-space:nowrap;">{{ $saCond['label'] }}</span>
                                @else<span style="color:#ccc;font-size:11px;">—</span>@endif
                            </td>
                            <td style="padding:10px 14px;">
                                <a href="/multi-family/{{ $citySlug }}/{{ $saSlug }}/" style="color:#2c6fad;font-size:12px;">Guide</a>
                                &nbsp;·&nbsp;
                                <a href="/search-listings/{{ $citySlug }}/{{ $saSlug }}?type=Duplex" style="color:#2c6fad;font-size:12px;">Listings</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    @endif

    {{-- PRICE TREND CHART --}}
    @if($multiFamilyRow)
    <section style="margin-bottom:28px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:6px;">{{ $cityName }} Multi-Family Price Trend — Last 12 Months</h2>
        <p style="font-size:13px;color:#888;margin-bottom:14px;">Average sold price per month (all multi-family types combined)</p>
        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:20px;">
            <div id="mf-price-trend" style="min-height:180px;"></div>
        </div>
    </section>
    @endif

    {{-- PRICE RANGE CHART --}}
    @if($priceRange && count($priceRange))
    <section style="margin-bottom:28px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:6px;">{{ $cityName }} Multi-Family Sold Price Distribution</h2>
        <p style="font-size:13px;color:#888;margin-bottom:14px;">How many multi-family properties sold in each price bracket (last 90 days)</p>
        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:20px;">
            <div id="mf-price-range" style="min-height:200px;"></div>
        </div>
    </section>
    @endif

    {{-- FAQ --}}
    <section class="faq-section" style="margin-bottom:28px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">{{ $cityName }} Multi-Family Market — FAQ</h2>

        <div class="faq-item" onclick="this.classList.toggle('open')">
            <div class="faq-question">What is the average duplex or multi-family price in {{ $cityName }}? <span class="faq-chevron">&#9660;</span></div>
            <div class="faq-answer">
                @if($overallCond['avg_sold_30d'] && !$overallCond['insufficient_data'])
                <dl>
                    <dt><strong>Average multi-family sold price in {{ $cityName }} (last 30 days):</strong></dt>
                    <dd>${{ number_format($overallCond['avg_sold_30d']) }}
                        @if($overallCond['avg_sold_90d']) &nbsp;·&nbsp; 90-day avg: ${{ number_format($overallCond['avg_sold_90d']) }}@endif
                        <span style="color:#888;font-size:12px;"> — last updated {{ date('F j, Y') }}</span>
                    </dd>
                </dl>
                <ul style="margin-top:10px;font-size:13px;line-height:2;">
                    @foreach(['Duplex','Triplex','Fourplex'] as $pt)
                    @if(!empty($typeStats[$pt]['avg_sold_30d']))
                    <li><strong>{{ $pt }}:</strong> avg ${{ number_format($typeStats[$pt]['avg_sold_30d']) }} sold · {{ $typeStats[$pt]['current_active'] }} active listings</li>
                    @endif
                    @endforeach
                </ul>
                @else
                <p>Insufficient recent sales data to determine the average multi-family price in {{ $cityName }} at this time.</p>
                @endif
            </div>
        </div>

        <div class="faq-item" onclick="this.classList.toggle('open')">
            <div class="faq-question">Is {{ $cityName }} a buyer's or seller's market for multi-family properties? <span class="faq-chevron">&#9660;</span></div>
            <div class="faq-answer">
                @if($overallCond['label'] && !$overallCond['insufficient_data'])
                <p>Based on current data, <strong>{{ $cityName }} is a {{ $overallCond['label'] }}</strong> for multi-family properties.</p>
                <p style="margin-top:8px;">The absorption rate is <strong>{{ $overallCond['absorption_rate'] }}%</strong>, with <strong>{{ number_format($overallCond['current_active']) }} active listings</strong> and <strong>{{ number_format($overallCond['sold_30d']) }} sales</strong> in the last 30 days.</p>
                @else
                <p>Insufficient sales data to determine market conditions for multi-family properties in {{ $cityName }}.</p>
                @endif
            </div>
        </div>

        <div class="faq-item" onclick="this.classList.toggle('open')">
            <div class="faq-question">How long does it take to sell a duplex or triplex in {{ $cityName }}? <span class="faq-chevron">&#9660;</span></div>
            <div class="faq-answer">
                @if($overallCond['avg_dom'])
                <p>Multi-family properties in <strong>{{ $cityName }}</strong> are taking an average of <strong>{{ $overallCond['avg_dom'] }} days on the market</strong> before selling.</p>
                @else
                <p>Average days on market data is not currently available for {{ $cityName }} multi-family properties.</p>
                @endif
            </div>
        </div>
    </section>

    {{-- FURTHER READING --}}
    <div style="background:#f0fff0;border:1px solid #b8dfb8;border-radius:6px;padding:20px 24px;margin-bottom:20px;">
        <h3 style="font-size:15px;font-weight:700;color:#1a3a2a;margin:0 0 10px;">Further Reading: Multi-Family Buyer Guides</h3>
        <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.9;">
            <li><a href="/ssmuh-guide" style="color:#1a6baa;">BC SSMUH Explained — What 4 Units By Right Means for {{ $cityName }} Buyers</a></li>
            <li><a href="/buying-a-duplex-bc" style="color:#1a6baa;">Buying a Duplex in BC: Half-Duplex vs Full Duplex, Suites, Parking, Financing</a></li>
            <li><a href="/buying-a-fourplex-bc" style="color:#1a6baa;">Buying a Fourplex or Triplex in BC: Cap Rates, Due Diligence Checklist, Financing</a></li>
        </ul>
    </div>

    {{-- INTERNAL LINKS --}}
    <div style="padding:16px 0;border-top:1px solid #eee;font-size:13px;color:#666;line-height:2;">
        <strong>{{ $cityName }} links:</strong>
        <a href="/search-listings/{{ $citySlug }}?type=Duplex" style="margin:0 10px;color:#2c6fad;">Duplexes For Sale</a>
        <a href="/search-listings/{{ $citySlug }}?type=Triplex" style="margin:0 10px;color:#2c6fad;">Triplexes For Sale</a>
        <a href="/search-listings/{{ $citySlug }}?type=Fourplex" style="margin:0 10px;color:#2c6fad;">Fourplexes For Sale</a>
        <a href="/townhouses/{{ $citySlug }}/" style="margin:0 10px;">Townhouses</a>
        <a href="/houses/{{ $citySlug }}/" style="margin:0 10px;">Houses</a>
        <a href="/neighbourhood/{{ $citySlug }}/" style="margin:0 10px;">Neighbourhood Guides</a>
        <a href="/multi-family/" style="margin:0 10px;">All Cities</a>
    </div>
</div>

<div class="container">
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => $cityName . ' Multi-Family',
        'stripSearchName' => $cityName . ' Multi-Family Listings',
        'stripSearchData' => json_encode(array_filter(['cities' => $cityName, 'listing_status' => 'Active'])),
        'stripCity'       => $cityName,
        'stripModalId'    => 'mfCityAlert_' . $citySlug,
        'stripHeading'    => 'Get Duplex, Triplex & Fourplex Alerts in ' . $cityName,
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
<script>
window.BCTrack = window.BCTrack || {};
window.BCTrack.pageType     = "buy";
window.BCTrack.city         = "{{ addslashes($cityName ?? '') }}";
window.BCTrack.propertyType = "multi_family";
</script>
@if($multiFamilyRow && count($sparklineData) && array_sum($sparklineData) > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
<script>
(function(){
    var el = document.getElementById('mf-price-trend');
    if (!el) return;
    new ApexCharts(el, {
        chart: { type: 'area', height: 180, toolbar: { show: false }, zoom: { enabled: false } },
        series: [{ name: 'Avg Multi-Family Price', data: @json($sparklineData) }],
        xaxis: { categories: @json($sparklineLabels), labels: { style: { fontSize: '11px', colors: '#aaa' } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { fontSize: '11px', colors: '#aaa' }, formatter: function(v){ return v ? '$'+(v/1000000>=1?(v/1000000).toFixed(2)+'M':(v/1000).toFixed(0)+'K') : ''; } } },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: .25, opacityTo: 0 } },
        colors: ['#27ae60'],
        dataLabels: { enabled: false },
        grid: { strokeDashArray: 3, borderColor: '#f0ede8' },
        tooltip: { y: { formatter: function(v){ return '$'+Number(v).toLocaleString('en-CA'); } } }
    }).render();
})();
</script>
@endif
@if($priceRange && count($priceRange))
@if(!($multiFamilyRow && count($sparklineData) && array_sum($sparklineData) > 0))
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
@endif
<script>
(function(){
    var el2 = document.getElementById('mf-price-range');
    if (!el2) return;
    @php
    $rangeLabels = array_map(fn($r) => preg_replace('/^[A-Z]_/', '', $r->Range), $priceRange);
    $rangeCounts = array_map(fn($r) => (int)$r->Count, $priceRange);
    @endphp
    new ApexCharts(el2, {
        chart: { type: 'bar', height: 200, toolbar: { show: false } },
        series: [{ name: 'Properties Sold', data: @json($rangeCounts) }],
        xaxis: { categories: @json($rangeLabels), labels: { style: { fontSize: '10px', colors: '#aaa' }, rotate: -30 } },
        yaxis: { labels: { style: { fontSize: '10px', colors: '#aaa' } } },
        colors: ['#27ae60'],
        dataLabels: { enabled: false },
        grid: { strokeDashArray: 3, borderColor: '#f0ede8' },
        plotOptions: { bar: { borderRadius: 3 } }
    }).render();
})();
</script>
@endif
@endpush
