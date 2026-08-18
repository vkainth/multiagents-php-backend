@extends('themes.modern-white.layout')

@section('head')
<link rel="canonical" href="{{ $canonical }}">
@php
$jsonLd = [
    '@@context' => 'https://schema.org',
    '@@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@@type' => 'ListItem', 'position' => 1, 'name' => $agent->name, 'item' => route('agent.home', $agent->slug)],
        ['@@type' => 'ListItem', 'position' => 2, 'name' => 'Houses', 'item' => route('agent.houses', $agent->slug)],
        ['@@type' => 'ListItem', 'position' => 3, 'name' => $city . ' Houses', 'item' => route('agent.houses.city', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug])],
        ['@@type' => 'ListItem', 'position' => 4, 'name' => $subarea, 'item' => $canonical],
    ],
];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@php
$saFaqEntries = [];

if ($cond['avg_sold_30d'] && !$cond['insufficient_data']) {
    $sa1 = 'The average house sold price in ' . $subarea . ' over the last 30 days is $' . number_format($cond['avg_sold_30d']);
    if ($cond['avg_sold_90d']) { $sa1 .= ', with a 90-day average of $' . number_format($cond['avg_sold_90d']); }
    $sa1 .= '. Based on ' . number_format($cond['sold_30d']) . ' sales recorded via MLS\u00ae data.';
} else {
    $sa1 = 'Insufficient recent sales data to determine the average house price in ' . $subarea . ' at this time. Please check active listings for current asking prices.';
}
$saFaqEntries[] = ['@type' => 'Question', 'name' => 'What is the average house price in ' . $subarea . '?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $sa1]];

if ($cond['label'] && !$cond['insufficient_data']) {
    $sa2 = $subarea . ' is currently a ' . $cond['label'] . ' for detached homes and houses. The absorption rate is ' . $cond['absorption_rate'] . '%, with ' . number_format($cond['current_active']) . ' active listings and ' . number_format($cond['sold_30d']) . ' sales in the last 30 days.';
} else {
    $sa2 = 'Insufficient sales data to determine the current market conditions for houses in ' . $subarea . '.';
}
$saFaqEntries[] = ['@type' => 'Question', 'name' => 'Is ' . $subarea . ' a buyer\'s or seller\'s market for houses?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $sa2]];

$saFaqJsonLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $saFaqEntries];
@endphp
<script type="application/ld+json">{!! json_encode($saFaqJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<style>
.acd-faq-item{border:1px solid var(--border);border-radius:6px;margin-bottom:8px;overflow:hidden;}
.acd-faq-question{padding:13px 16px;cursor:pointer;font-weight:600;font-size:14px;display:flex;justify-content:space-between;align-items:center;background:#f8f8f6;color:var(--text);user-select:none;}
.acd-faq-question:hover{background:#f0ede8;}
.acd-faq-chevron{font-size:12px;transition:transform .2s;color:var(--muted);}
.acd-faq-answer{display:none;padding:13px 16px;font-size:13.5px;line-height:1.65;color:var(--text);border-top:1px solid var(--border);background:#fff;}
.acd-faq-item.open .acd-faq-answer{display:block;}
.acd-faq-item.open .acd-faq-chevron{transform:rotate(180deg);}
</style>
@endsection

@section('content')
<div class="page-header">
  <div class="container">
    <nav aria-label="breadcrumb" style="margin-bottom:10px;">
      <ol style="list-style:none;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:6px;font-size:13px;color:var(--muted);">
        <li><a href="{{ route('agent.home', $agent->slug) }}" style="color:var(--muted);">{{ $agent->name }}</a></li>
        <li style="margin:0 4px;">›</li>
        <li><a href="{{ route('agent.houses', $agent->slug) }}" style="color:var(--muted);">Houses</a></li>
        <li style="margin:0 4px;">›</li>
        <li><a href="{{ route('agent.houses.city', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug]) }}" style="color:var(--muted);">{{ $city }}</a></li>
        <li style="margin:0 4px;">›</li>
        <li style="color:var(--text);">{{ $subarea }}</li>
      </ol>
    </nav>
    <div class="page-header__eyebrow">{{ $agent->name }} · {{ $city }}</div>
    <h1 class="page-header__title">Houses for Sale in {{ $subarea }}</h1>
    @if(!$cond['insufficient_data'] && $cond['label'])
    <p class="page-header__sub">
      <span style="background:{{ $cond['color'] }};color:#fff;border-radius:3px;padding:2px 8px;font-size:12px;font-weight:700;">{{ $cond['label'] }}</span>
      @if($cond['avg_sold_30d']) &nbsp; avg ${{ number_format($cond['avg_sold_30d']) }} @endif
      @if($cond['sold_30d']) &nbsp;· {{ number_format($cond['sold_30d']) }} sold (30d) @endif
      @if($cond['avg_dom']) &nbsp;· {{ $cond['avg_dom'] }}d avg DOM @endif
    </p>
    @else
    <p class="page-header__sub">Detached homes &amp; houses in {{ $subarea }}, {{ $city }}</p>
    @endif
  </div>
</div>

<div class="container">

  {{-- STATS TILES --}}
  @if(!$cond['insufficient_data'] && $cond['label'])
  <section class="section--sm" style="padding-top:36px;">
    @php
      $priceTrendVal = $cond['price_trend'] != 0
        ? (($cond['price_trend'] > 0 ? '+' : '') . $cond['price_trend'] . '%')
        : '—';
      $tiles = [
        ['Avg Sold (30d)', $cond['avg_sold_30d'] ? '$'.number_format($cond['avg_sold_30d']) : '—', '30-day avg'],
        ['Avg Sold (90d)', $cond['avg_sold_90d'] ? '$'.number_format($cond['avg_sold_90d']) : '—', '90-day avg'],
        ['Sold (30d)', number_format($cond['sold_30d']), 'houses'],
        ['Sold (90d)', number_format($cond['sold_90d']), 'houses'],
        ['Avg DOM', $cond['avg_dom'] ? $cond['avg_dom'].'d' : '—', 'days on market'],
        ['Price Trend', $priceTrendVal, 'vs 90-day avg'],
        ['Active', number_format($cond['current_active']), 'for sale now'],
        ['Absorption', $cond['absorption_rate'].'%', 'sell rate/month'],
      ];
    @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:14px;margin-bottom:32px;">
      @foreach($tiles as $tile)
      @php $isTrend = $tile[0]==='Price Trend'; @endphp
      <div style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:16px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <div style="font-size:{{ strlen($tile[1]) > 7 ? '16' : '20' }}px;font-weight:700;color:{{ ($isTrend && $cond['price_trend']>0) ? '#16a34a' : (($isTrend && $cond['price_trend']<0) ? '#dc2626' : 'var(--text)') }};">{{ $tile[1] }}</div>
        <div style="font-size:10px;color:var(--muted);margin-top:4px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">{{ $tile[0] }}</div>
        <div style="font-size:10px;color:var(--muted);margin-top:2px;opacity:.7;">{{ $tile[2] }}</div>
      </div>
      @endforeach
    </div>

    @if($editorial)
    <div style="background:#f8f8f6;border-left:4px solid var(--accent);border-radius:4px;padding:18px 22px;font-size:14px;color:var(--text);line-height:1.8;margin-bottom:32px;">
      <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Market Analysis — {{ $subarea }}</div>
      {!! $editorial !!}
    </div>
    @endif
  </section>
  @else
  <section class="section--sm" style="padding-top:36px;">
    <div style="background:#f8f8f6;border:1px solid var(--border);border-radius:6px;padding:20px;font-size:14px;color:var(--muted);margin-bottom:32px;">
      Not enough recent house sales in {{ $subarea }} to calculate market statistics.
      <a href="{{ route('agent.houses.city', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug]) }}" style="color:var(--text);text-decoration:underline;margin-left:8px;">View {{ $city }} house market →</a>
    </div>
  </section>
  @endif

  {{-- FAQ SECTION --}}
  <section class="section" aria-labelledby="faq-heading" style="border-top:1px solid var(--border);padding-top:40px;margin-bottom:8px;">
    <h2 id="faq-heading" class="h2 mb-32">{{ $subarea }} House Market — Frequently Asked Questions</h2>

    <div class="acd-faq-item" onclick="this.classList.toggle('open')">
      <div class="acd-faq-question">
        What is the average house price in {{ $subarea }}?
        <span class="acd-faq-chevron">&#9660;</span>
      </div>
      <div class="acd-faq-answer">
        @if($cond['avg_sold_30d'] && !$cond['insufficient_data'])
        <dl>
          <dt><strong>Average house sold price in {{ $subarea }} (last 30 days):</strong></dt>
          <dd>${{ number_format($cond['avg_sold_30d']) }}
            @if($cond['avg_sold_90d']) &nbsp;·&nbsp; 90-day avg: ${{ number_format($cond['avg_sold_90d']) }}@endif
            <span style="color:var(--muted);font-size:12px;"> — last updated {{ date('F j, Y') }}</span>
          </dd>
        </dl>
        <p style="margin-top:8px;">Based on {{ number_format($cond['sold_30d']) }} house and detached home sales recorded in the last 30 days via MLS® board data.</p>
        @else
        <p>Insufficient recent sales data to determine the average house price in {{ $subarea }} at this time. Please check active listings for current asking prices.</p>
        @endif
      </div>
    </div>

    <div class="acd-faq-item" onclick="this.classList.toggle('open')">
      <div class="acd-faq-question">
        Is {{ $subarea }} a buyer's or seller's market for houses?
        <span class="acd-faq-chevron">&#9660;</span>
      </div>
      <div class="acd-faq-answer">
        @if($cond['label'] && !$cond['insufficient_data'])
        <p>Based on current data, <strong>{{ $subarea }} is a {{ $cond['label'] }}</strong> for detached homes and houses.</p>
        <p style="margin-top:8px;">The absorption rate is <strong>{{ $cond['absorption_rate'] }}%</strong>, with <strong>{{ number_format($cond['current_active']) }} active house listings</strong> and <strong>{{ number_format($cond['sold_30d']) }} sales</strong> in the last 30 days.</p>
        @if(str_contains($cond['label'], 'Seller'))
        <p style="margin-top:8px;">What this means for buyers: Competition is real. Houses in {{ $subarea }} are moving within @if($cond['avg_dom']) {{ $cond['avg_dom'] }} days on average @else a few weeks @endif. Budget for potential bidding situations on well-priced detached homes.</p>
        @elseif(str_contains($cond['label'], 'Buyer'))
        <p style="margin-top:8px;">What this means for buyers: You have more negotiating power. There are more houses available than buyers, giving you time to find the right home without as much competition pressure.</p>
        @endif
        @else
        <p>Insufficient sales data to determine the current market conditions for houses in {{ $subarea }}.</p>
        @endif
      </div>
    </div>
  </section>

  {{-- RECENT LISTINGS --}}
  <section class="section" aria-labelledby="listings-heading" style="border-top:1px solid var(--border);padding-top:40px;">
    <h2 id="listings-heading" class="h2 mb-32">Houses for Sale in {{ $subarea }}</h2>
    @if($recentListings->count() > 0)
    <div class="grid-3">
      @foreach($recentListings as $listing)
        @include('themes.shared.listing-card', ['listing' => $listing])
      @endforeach
    </div>
    <div style="margin-top:24px;">
      <a href="{{ route('agent.search', $agent->slug) }}?city={{ urlencode($city) }}&subarea={{ urlencode($subarea) }}&type=House" class="btn-cta">See All {{ $subarea }} Houses</a>
    </div>
    @else
    <p style="color:var(--muted);">No active house listings found in {{ $subarea }} right now.
      <a href="{{ route('agent.houses.city', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug]) }}" style="color:var(--text);text-decoration:underline;">Browse all {{ $city }} houses</a>.
    </p>
    @endif
  </section>

  {{-- NEARBY SUBAREAS --}}
  @if($nearbySubareas->isNotEmpty())
  <section class="section" style="border-top:1px solid var(--border);padding-top:40px;">
    <h2 class="h2 mb-32">Nearby Neighbourhoods in {{ $city }}</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
      @foreach($nearbySubareas as $nb)
      @php $nbSlug = \App\Helpers\Helper::enslugPlace($nb->subarea); @endphp
      <a href="{{ route('agent.houses.subarea', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug, 'subareaSlug' => $nbSlug]) }}" style="display:block;text-decoration:none;">
        <div style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:14px 18px;box-shadow:0 1px 3px rgba(0,0,0,.04);">
          <div style="font-size:14px;font-weight:600;color:var(--text);">{{ $nb->subarea }}</div>
          <div style="font-size:12px;color:var(--accent);margin-top:4px;text-decoration:underline;">View houses &rsaquo;</div>
        </div>
      </a>
      @endforeach
    </div>
  </section>
  @endif

</div>
@endsection

@section('w4-headline')Houses in {{ $subarea }} — What's Your Home Worth?@endsection
