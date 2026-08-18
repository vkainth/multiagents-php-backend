@extends('frontend.layouts.default')
@php
$cityName = $cityRecord->label ?? $city;
$currentMonth = date('F Y');
$metaTitle = "{$cityName} Townhouse Market Report | {$currentMonth} | Hani & Les";

$metaDesc = ($overallCond['current_active'] ? "Browse " . number_format($overallCond['current_active']) . " MLS® townhouses for sale in {$cityName}, BC." : "Browse MLS® townhouses for sale in {$cityName}, BC.");
if ($overallCond['avg_sold_30d']) $metaDesc .= " Avg sold price \$" . number_format($overallCond['avg_sold_30d']) . ".";
if ($overallCond['avg_dom']) $metaDesc .= " Avg " . $overallCond['avg_dom'] . " days on market.";
if ($overallCond['label']) $metaDesc .= " Currently a " . $overallCond['label'] . ".";
$metaDesc .= " Updated daily from MLS® records.";

$canonicalUrl = "https://www.bccondosandhomes.com/townhouses/{$citySlug}/";

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
        {"@type":"ListItem","position":3,"name":"{{ e($cityName) }}","item":"{{ $canonicalUrl }}"}
      ]
    },
    {
      "@type": "FAQPage",
      "mainEntity": [
        {"@type":"Question","name":"What is the average townhouse price in {{ e($cityName) }}?","acceptedAnswer":{"@type":"Answer","text":"{{ $overallCond['avg_sold_30d'] ? 'The average townhouse sold price in '.$cityName.' over the last 30 days is $'.number_format($overallCond['avg_sold_30d']).'. Based on '.number_format($overallCond['sold_30d']).' townhouse sales recorded in the MLS®.' : 'Insufficient recent sales data for '.$cityName.'. Please check back or browse current active listings.' }}"}},
        {"@type":"Question","name":"Is {{ e($cityName) }} a buyer's or seller's market for townhouses?","acceptedAnswer":{"@type":"Answer","text":"{{ $overallCond['label'] ? 'Based on current data, '.$cityName.' is a '.$overallCond['label'].' for townhouses. The absorption rate is '.$overallCond['absorption_rate'].'% with '.$overallCond['current_active'].' active listings and '.number_format($overallCond['sold_30d']).' townhouses sold in the last 30 days.' : 'Insufficient data to determine market conditions for '.$cityName.' townhouses at this time.' }}"}},
        {"@type":"Question","name":"How long does it take to sell a townhouse in {{ e($cityName) }}?","acceptedAnswer":{"@type":"Answer","text":"{{ $overallCond['avg_dom'] ? 'Townhouses in '.$cityName.' are taking an average of '.$overallCond['avg_dom'].' days on the market before selling, based on sales in the last 30 days.' : 'Average days on market data is not currently available for '.$cityName.' townhouses.' }}"}},
        {"@type":"Question","name":"How many townhouses sold in {{ e($cityName) }} last month?","acceptedAnswer":{"@type":"Answer","text":"{{ $overallCond['sold_30d'] ? number_format($overallCond['sold_30d']).' townhouses sold in '.$cityName.' in the last 30 days, according to MLS® data.' : 'Recent sales count data is not available for '.$cityName.' townhouses.' }}"}}
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
                <li class="breadcrumb-item active" style="color:#fff;">{{ $cityName }}</li>
            </ol>
        </nav>
        <h1 style="font-size:26px;font-weight:700;margin-bottom:10px;color:#fff;">{{ date('F Y') }} {{ $cityName }} Townhouse Market Report</h1>
        <p style="font-size:14px;color:rgba(255,255,255,.82);max-width:820px;line-height:1.65;margin-bottom:10px;">
            @if($overallCond['label'] && !$overallCond['insufficient_data'])
                Townhouse market in {{ $cityName }}:
                <strong style="color:#fff;">{{ $overallCond['label'] }}</strong>
                @if($overallCond['sold_30d']) · {{ number_format($overallCond['sold_30d']) }} townhouses sold (30d)@endif
                @if($overallCond['avg_sold_30d']) · avg ${{ number_format($overallCond['avg_sold_30d']) }}@endif
                @if($overallCond['avg_dom']) · {{ $overallCond['avg_dom'] }} days on market @endif
            @else
                Browse current townhouse listings in {{ $cityName }} with live MLS® data.
            @endif
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:10px;font-size:13px;">
            <a href="/search-listings/{{ $citySlug }}?type=Townhouse" style="background:#e74c3c;color:#fff;border-radius:4px;padding:7px 16px;text-decoration:none;font-weight:700;">View {{ $cityName }} Townhouses For Sale</a>
            <a href="/market-stats/{{ $citySlug }}/townhouses" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:4px;padding:7px 14px;text-decoration:none;">Townhouse Market Stats</a>
            <a href="/neighbourhood/{{ $citySlug }}/" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:4px;padding:7px 14px;text-decoration:none;">Neighbourhood Guides</a>
        </div>
    </div>
</div>

<div class="container" style="padding-top:24px;padding-bottom:50px;min-height:60vh;">

    {{-- VERDICT CARD + 4 TILES --}}
    @if(!$overallCond['insufficient_data'] && $overallCond['label'])
    <div class="row" style="margin-bottom:22px;">
        <div class="col-md-4">
            <div style="border-left:5px solid {{ $overallCond['color'] }};background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:20px 22px;height:100%;">
                <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px;">The Verdict — {{ $cityName }} Townhouses</div>
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
                    ['Avg Sold Price', $overallCond['avg_sold_30d'] ? '$'.number_format($overallCond['avg_sold_30d']) : '—', '30-day avg'],
                    ['Sold (30d)', number_format($overallCond['sold_30d']), 'townhouses'],
                    ['Avg DOM', $overallCond['avg_dom'] ? $overallCond['avg_dom'].'d' : '—', 'days on market'],
                    ['Active Listings', number_format($overallCond['current_active']), 'townhouses for sale'],
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

    {{-- EDITORIAL --}}
    @if($editorial)
    <div style="background:#fafaf8;border-left:4px solid {{ $overallCond['color'] }};border-radius:4px;padding:16px 20px;font-size:14px;color:#444;line-height:1.8;margin-bottom:22px;">
        <h2 style="font-size:15px;font-weight:700;color:#333;margin:0 0 8px;">The {{ $cityName }} Townhouse Market Explained</h2>
        <p style="margin:0;">{!! $editorial !!}</p>
    </div>
    @endif
    @else
    <div style="background:#fafaf8;border:1px solid #e2dbd2;border-radius:5px;padding:16px 20px;font-size:14px;color:#888;margin-bottom:22px;">
        Insufficient recent townhouse sales data for {{ $cityName }} to determine market conditions.
        <a href="/search-listings/{{ $citySlug }}?type=Townhouse" style="color:#2c6fad;margin-left:8px;">Browse active townhouse listings →</a>
    </div>
    @endif

    {{-- SUBAREA BREAKDOWN TABLE --}}
    @if($subareaStats && count($subareaStats))
    <section style="margin-bottom:28px;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">{{ $cityName }} Townhouse Market by Neighbourhood</h2>
        <div style="background:#fff;border:1px solid #e2dbd2;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;">
            <div class="table-responsive">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:#f7f4ef;text-align:left;">
                            <th style="padding:10px 14px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#888;">Neighbourhood</th>
                            <th style="padding:10px 14px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#888;">Avg Price</th>
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
                                <a href="/townhouses/{{ $citySlug }}/{{ $saSlug }}/" style="color:#2c2c2c;text-decoration:none;">{{ $saLabel }}</a>
                            </td>
                            <td style="padding:10px 14px;">
                                @if($saCond['avg_sold_30d'])
                                @php $p = $saCond['avg_sold_30d']; @endphp
                                ${{ $p >= 1000000 ? number_format($p/1000000, 2).'M' : number_format(round($p/1000)).'K' }}
                                @else<span style="color:#bbb;">—</span>@endif
                            </td>
                            <td style="padding:10px 14px;color:#555;">
                                @if($saCond['sold_30d'])
                                <a href="/search-listings/{{ $citySlug }}/{{ $saSlug }}?type=Townhouse&listing_status=sold" style="color:#555;text-decoration:underline;">{{ $saCond['sold_30d'] }}</a>
                                @else—@endif
                            </td>
                            <td style="padding:10px 14px;color:#555;">{{ $saCond['avg_dom'] ? $saCond['avg_dom'].'d' : '—' }}</td>
                            <td style="padding:10px 14px;">
                                @if($saCond['label'] && !$saCond['insufficient_data'])
                                <span style="background:{{ $saCond['color'] }};color:#fff;border-radius:3px;padding:2px 7px;font-size:10px;font-weight:700;white-space:nowrap;">{{ $saCond['label'] }}</span>
                                @else<span style="color:#ccc;font-size:11px;">—</span>@endif
                            </td>
                            <td style="padding:10px 14px;">
                                <a href="/townhouses/{{ $citySlug }}/{{ $saSlug }}/" style="color:#2c6fad;font-size:12px;">Guide</a>
                                &nbsp;·&nbsp;
                                <a href="/search-listings/{{ $citySlug }}/{{ $saSlug }}?type=Townhouse" style="color:#2c6fad;font-size:12px;">Listings</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    @endif

    {{-- RECENT LISTINGS --}}
    @if($recentListings && count($recentListings))
    <section style="margin-bottom:28px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">Recent {{ $cityName }} Townhouse Listings</h2>
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
                            <div style="font-size:12px;color:#333;margin-top:2px;">Townhouse</div>
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
        <div style="margin-top:4px;font-size:13px;">
            <a href="/search-listings/{{ $citySlug }}?type=Townhouse" style="color:#2c6fad;font-weight:600;">See all {{ $cityName }} townhouses for sale &rsaquo;</a>
        </div>
    </section>
    @endif

    {{-- PRICE TREND CHART --}}
    @if($townhouseRow)
    <section style="margin-bottom:28px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:6px;">{{ $cityName }} Townhouse Price Trend — Last 12 Months</h2>
        <p style="font-size:13px;color:#888;margin-bottom:14px;">Average sold price per month (townhouses)</p>
        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:20px;">
            <div id="townhouse-price-trend" style="min-height:180px;"></div>
        </div>
    </section>
    @endif

    {{-- PRICE RANGE CHART --}}
    @if($priceRange && count($priceRange))
    <section style="margin-bottom:28px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:6px;">{{ $cityName }} Townhouse Sold Price Distribution</h2>
        <p style="font-size:13px;color:#888;margin-bottom:14px;">How many townhouses sold in each price bracket (last 90 days)</p>
        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:20px;">
            <div id="townhouse-price-range" style="min-height:200px;"></div>
        </div>
    </section>
    @endif

    {{-- FAQ ACCORDION --}}
    <section class="faq-section" style="margin-bottom:28px;padding-top:16px;border-top:1px solid #eee;">
        <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">{{ $cityName }} Townhouse Market — Frequently Asked Questions</h2>

        <div class="faq-item" onclick="this.classList.toggle('open')">
            <div class="faq-question">
                What is the average townhouse price in {{ $cityName }}?
                <span class="faq-chevron">&#9660;</span>
            </div>
            <div class="faq-answer">
                @if($overallCond['avg_sold_30d'] && !$overallCond['insufficient_data'])
                <dl>
                    <dt><strong>Average townhouse sold price in {{ $cityName }} (last 30 days):</strong></dt>
                    <dd>${{ number_format($overallCond['avg_sold_30d']) }}
                        @if($overallCond['avg_sold_90d']) &nbsp;·&nbsp; 90-day avg: ${{ number_format($overallCond['avg_sold_90d']) }}@endif
                        <span style="color:#888;font-size:12px;"> — last updated {{ date('F j, Y') }}</span>
                    </dd>
                </dl>
                <p style="margin-top:8px;">Based on {{ number_format($overallCond['sold_30d']) }} townhouse sales recorded in the last 30 days via MLS® board data.</p>
                @else
                <p>Insufficient recent sales data to determine the average townhouse price in {{ $cityName }} at this time. Please check active listings for current asking prices.</p>
                @endif
            </div>
        </div>

        <div class="faq-item" onclick="this.classList.toggle('open')">
            <div class="faq-question">
                Is {{ $cityName }} a buyer's or seller's market for townhouses?
                <span class="faq-chevron">&#9660;</span>
            </div>
            <div class="faq-answer">
                @if($overallCond['label'] && !$overallCond['insufficient_data'])
                <p>Based on current data, <strong>{{ $cityName }} is a {{ $overallCond['label'] }}</strong> for townhouses.</p>
                <p style="margin-top:8px;">The absorption rate is <strong>{{ $overallCond['absorption_rate'] }}%</strong>, with <strong>{{ number_format($overallCond['current_active']) }} active townhouse listings</strong> and <strong>{{ number_format($overallCond['sold_30d']) }} sales</strong> in the last 30 days.</p>
                @if(str_contains($overallCond['label'], 'Seller'))
                <p style="margin-top:8px;">What this means for buyers: Competition is real. Townhouses in {{ $cityName }} are moving within @if($overallCond['avg_dom']) {{ $overallCond['avg_dom'] }} days on average @else a few weeks @endif. Budget for potential bidding situations on well-priced townhomes.</p>
                @elseif(str_contains($overallCond['label'], 'Buyer'))
                <p style="margin-top:8px;">What this means for buyers: You have more negotiating power. There are more townhouses available than buyers, giving you time to find the right home without as much competition pressure.</p>
                @endif
                @else
                <p>Insufficient sales data to determine the current market conditions for townhouses in {{ $cityName }}.</p>
                @endif
            </div>
        </div>

        <div class="faq-item" onclick="this.classList.toggle('open')">
            <div class="faq-question">
                How long does it take to sell a townhouse in {{ $cityName }}?
                <span class="faq-chevron">&#9660;</span>
            </div>
            <div class="faq-answer">
                @if($overallCond['avg_dom'])
                <p>Townhouses in <strong>{{ $cityName }}</strong> are taking an average of <strong>{{ $overallCond['avg_dom'] }} days on the market</strong> before selling, based on sales in the last 30 days.</p>
                @if($overallCond['avg_dom'] < 21)
                <p style="margin-top:6px;">Days on market below 21 typically indicates a fast-moving market where sellers often receive offers near or above the asking price.</p>
                @elseif($overallCond['avg_dom'] > 45)
                <p style="margin-top:6px;">With townhouses averaging over 45 days on market, buyers in {{ $cityName }} have more time to consider their options and negotiate.</p>
                @endif
                @else
                <p>Average days on market data is not currently available for {{ $cityName }} townhouses.</p>
                @endif
            </div>
        </div>

        <div class="faq-item" onclick="this.classList.toggle('open')">
            <div class="faq-question">
                How many townhouses sold in {{ $cityName }} last month?
                <span class="faq-chevron">&#9660;</span>
            </div>
            <div class="faq-answer">
                @if($overallCond['sold_30d'])
                <p><strong>{{ number_format($overallCond['sold_30d']) }} townhouses</strong> sold in {{ $cityName }} in the last 30 days, based on MLS® data.</p>
                @if($overallCond['current_active'])
                <p style="margin-top:6px;">There are currently <strong>{{ number_format($overallCond['current_active']) }} active townhouse listings</strong> in {{ $cityName }}.</p>
                @endif
                @else
                <p>Recent townhouse sales count data is not available for {{ $cityName }} at this time.</p>
                @endif
            </div>
        </div>
    </section>

    {{-- INTERNAL LINKS --}}
    <div style="padding:16px 0;border-top:1px solid #eee;font-size:13px;color:#666;line-height:2;">
        <strong>{{ $cityName }} links:</strong>
        <a href="/search-listings/{{ $citySlug }}?type=Townhouse" style="margin:0 10px;color:#2c6fad;">Townhouses For Sale</a>
        <a href="/market-stats/{{ $citySlug }}/townhouses" style="margin:0 10px;">Townhouse Stats</a>
        <a href="/houses/{{ $citySlug }}/" style="margin:0 10px;">Houses</a>
        <a href="/market-report/{{ $citySlug }}" style="margin:0 10px;">Market Reports</a>
        <a href="/neighbourhood/{{ $citySlug }}/" style="margin:0 10px;">Neighbourhood Guides</a>
        <a href="/townhouses/" style="margin:0 10px;">All Cities</a>
        <a href="/multi-family/{{ $citySlug }}/" style="margin:0 10px;color:#27ae60;font-weight:600;">Multi-Family Market &rsaquo;</a>
    </div>
</div>

<div class="container">
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => $cityName . ' Townhouses',
        'stripSearchName' => $cityName . ' Townhouse Listings',
        'stripSearchData' => json_encode(array_filter(['cities' => $cityName, 'listing_status' => 'Active', 'type' => 'Townhouse'])),
        'stripCity'       => $cityName,
        'stripModalId'    => 'thCityAlert_' . $citySlug,
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
    var el = document.getElementById('townhouse-price-trend');
    if (!el) return;
    new ApexCharts(el, {
        chart: { type: 'area', height: 180, toolbar: { show: false }, zoom: { enabled: false } },
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
@if($priceRange && count($priceRange))
@php if(!isset($priceRangeScript)) { $priceRangeScript = true; } @endphp
<script>
(function(){
    var el = document.getElementById('townhouse-price-range');
    if (!el || typeof ApexCharts === 'undefined') {
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js';
        s.onload = renderTHRange;
        document.head.appendChild(s);
    } else { renderTHRange(); }
    function renderTHRange() {
        @php
            $thChartCats = array_map(fn($r) => preg_replace('/^[A-Z]_/', '', $r->Range), $priceRange);
            $thChartVals = array_map(fn($r) => (int)$r->Count, $priceRange);
        @endphp
        var cats = @json($thChartCats);
        var vals = @json($thChartVals);
        new ApexCharts(el, {
            chart: { type: 'bar', height: 200, toolbar: { show: false } },
            series: [{ name: 'Townhouses Sold', data: vals }],
            xaxis: { categories: cats, labels: { rotate: -35, style: { fontSize: '10px', colors: '#aaa' } } },
            yaxis: { labels: { style: { fontSize: '11px', colors: '#aaa' } } },
            colors: ['#2980b9'],
            dataLabels: { enabled: false },
            plotOptions: { bar: { borderRadius: 3 } },
            grid: { strokeDashArray: 3, borderColor: '#f0ede8' },
            tooltip: { y: { formatter: function(v){ return v + ' townhouses sold'; } } }
        }).render();
    }
})();
</script>
@endif
@endpush
