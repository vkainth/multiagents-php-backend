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
        ['@@type' => 'ListItem', 'position' => 3, 'name' => $city . ' Houses', 'item' => $canonical],
    ],
];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@php
$faqEntries = [];

if ($cond['avg_sold_30d'] && !$cond['insufficient_data']) {
    $a1 = 'The average house sold price in ' . $city . ' over the last 30 days is $' . number_format($cond['avg_sold_30d']);
    if ($cond['avg_sold_90d']) { $a1 .= ', with a 90-day average of $' . number_format($cond['avg_sold_90d']); }
    $a1 .= '. Based on ' . number_format($cond['sold_30d']) . ' sales recorded via MLS\u00ae data (includes houses, duplexes, triplexes and fourplexes).';
} else {
    $a1 = 'Insufficient recent sales data to determine the average house price in ' . $city . ' at this time. Please check active listings for current asking prices.';
}
$faqEntries[] = ['@type' => 'Question', 'name' => 'What is the average house price in ' . $city . '?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a1]];

if ($cond['label'] && !$cond['insufficient_data']) {
    $a2 = $city . ' is currently a ' . $cond['label'] . ' for detached homes and houses. The absorption rate is ' . $cond['absorption_rate'] . '%, with ' . number_format($cond['current_active']) . ' active listings and ' . number_format($cond['sold_30d']) . ' sales in the last 30 days.';
} else {
    $a2 = 'Insufficient sales data to determine the current market conditions for houses in ' . $city . '.';
}
$faqEntries[] = ['@type' => 'Question', 'name' => 'Is ' . $city . ' a buyer\'s or seller\'s market for houses?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a2]];

if ($cond['avg_dom']) {
    $a3 = 'Houses in ' . $city . ' are taking an average of ' . $cond['avg_dom'] . ' days on the market before selling, based on MLS\u00ae sales data from the last 30 days.';
} else {
    $a3 = 'Average days on market data is not currently available for ' . $city . ' houses.';
}
$faqEntries[] = ['@type' => 'Question', 'name' => 'How long does it take to sell a house in ' . $city . '?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a3]];

if ($cond['sold_30d']) {
    $a4 = number_format($cond['sold_30d']) . ' houses and detached homes sold in ' . $city . ' in the last 30 days, based on MLS\u00ae data (includes houses, duplexes, triplexes and fourplexes).';
    if ($cond['current_active']) { $a4 .= ' There are currently ' . number_format($cond['current_active']) . ' active house listings in ' . $city . '.'; }
} else {
    $a4 = 'Recent house sales count data is not available for ' . $city . ' at this time.';
}
$faqEntries[] = ['@type' => 'Question', 'name' => 'How many houses sold in ' . $city . ' last month?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a4]];

$faqJsonLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqEntries];
@endphp
<script type="application/ld+json">{!! json_encode($faqJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
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
        <li style="color:var(--text);">{{ $city }}</li>
      </ol>
    </nav>
    <div class="page-header__eyebrow">{{ $agent->name }} · Houses</div>
    <h1 class="page-header__title">Houses for Sale in {{ $city }}</h1>
    @if(!$cond['insufficient_data'] && $cond['label'])
    <p class="page-header__sub">
      <span style="background:{{ $cond['color'] }};color:#fff;border-radius:3px;padding:2px 8px;font-size:12px;font-weight:700;">{{ $cond['label'] }}</span>
      @if($cond['sold_30d']) &nbsp; {{ number_format($cond['sold_30d']) }} sold (30d) @endif
      @if($cond['avg_sold_30d']) &nbsp;· avg ${{ number_format($cond['avg_sold_30d']) }} @endif
      @if($cond['avg_dom']) &nbsp;· {{ $cond['avg_dom'] }}d avg DOM @endif
    </p>
    @else
    <p class="page-header__sub">Active MLS® house listings in {{ $city }}</p>
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
      <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Market Analysis</div>
      {!! $editorial !!}
    </div>
    @endif
  </section>
  @else
  <section class="section--sm" style="padding-top:36px;">
    <div style="background:#f8f8f6;border:1px solid var(--border);border-radius:6px;padding:20px;font-size:14px;color:var(--muted);margin-bottom:32px;">
      Not enough recent house sales in {{ $city }} to calculate market statistics yet.
      <a href="{{ route('agent.search', $agent->slug) }}?city={{ urlencode($city) }}&type=House" style="color:var(--accent);margin-left:8px;">Browse active listings →</a>
    </div>
  </section>
  @endif

  {{-- FAQ SECTION --}}
  <section class="section" aria-labelledby="faq-heading" style="border-top:1px solid var(--border);padding-top:40px;margin-bottom:8px;">
    <h2 id="faq-heading" class="h2 mb-32">{{ $city }} House Market — Frequently Asked Questions</h2>

    <div class="acd-faq-item" onclick="this.classList.toggle('open')">
      <div class="acd-faq-question">
        What is the average house price in {{ $city }}?
        <span class="acd-faq-chevron">&#9660;</span>
      </div>
      <div class="acd-faq-answer">
        @if($cond['avg_sold_30d'] && !$cond['insufficient_data'])
        <dl>
          <dt><strong>Average house sold price in {{ $city }} (last 30 days):</strong></dt>
          <dd>${{ number_format($cond['avg_sold_30d']) }}
            @if($cond['avg_sold_90d']) &nbsp;·&nbsp; 90-day avg: ${{ number_format($cond['avg_sold_90d']) }}@endif
            <span style="color:var(--muted);font-size:12px;"> — last updated {{ date('F j, Y') }}</span>
          </dd>
        </dl>
        <p style="margin-top:8px;">Based on {{ number_format($cond['sold_30d']) }} house and detached home sales recorded in the last 30 days via MLS® board data. This includes houses, duplexes, triplexes and fourplexes.</p>
        @else
        <p>Insufficient recent sales data to determine the average house price in {{ $city }} at this time. Please check active listings for current asking prices.</p>
        @endif
      </div>
    </div>

    <div class="acd-faq-item" onclick="this.classList.toggle('open')">
      <div class="acd-faq-question">
        Is {{ $city }} a buyer's or seller's market for houses?
        <span class="acd-faq-chevron">&#9660;</span>
      </div>
      <div class="acd-faq-answer">
        @if($cond['label'] && !$cond['insufficient_data'])
        <p>Based on current data, <strong>{{ $city }} is a {{ $cond['label'] }}</strong> for detached homes and houses.</p>
        <p style="margin-top:8px;">The absorption rate — the percentage of active listings that sell each month — is <strong>{{ $cond['absorption_rate'] }}%</strong>, with <strong>{{ number_format($cond['current_active']) }} active house listings</strong> and <strong>{{ number_format($cond['sold_30d']) }} sales</strong> in the last 30 days.</p>
        @if(str_contains($cond['label'], 'Seller'))
        <p style="margin-top:8px;">What this means for buyers: Competition is real. Houses in {{ $city }} are moving within @if($cond['avg_dom']) {{ $cond['avg_dom'] }} days on average @else a few weeks @endif. Budget for potential bidding situations on well-priced detached homes.</p>
        @elseif(str_contains($cond['label'], 'Buyer'))
        <p style="margin-top:8px;">What this means for buyers: You have more negotiating power. There are more houses available than buyers, giving you time to find the right single-family home without as much competition pressure.</p>
        @endif
        @else
        <p>Insufficient sales data to determine the current market conditions for houses in {{ $city }}.</p>
        @endif
      </div>
    </div>

    <div class="acd-faq-item" onclick="this.classList.toggle('open')">
      <div class="acd-faq-question">
        How long does it take to sell a house in {{ $city }}?
        <span class="acd-faq-chevron">&#9660;</span>
      </div>
      <div class="acd-faq-answer">
        @if($cond['avg_dom'])
        <p>Houses in <strong>{{ $city }}</strong> are taking an average of <strong>{{ $cond['avg_dom'] }} days on the market</strong> before selling, based on sales in the last 30 days.</p>
        @if($cond['avg_dom'] < 21)
        <p style="margin-top:6px;">Days on market below 21 typically indicates a fast-moving market where sellers often receive offers near or above the asking price.</p>
        @elseif($cond['avg_dom'] > 45)
        <p style="margin-top:6px;">With homes averaging over 45 days on market, buyers in {{ $city }} have more time to consider their options and negotiate.</p>
        @endif
        @else
        <p>Average days on market data is not currently available for {{ $city }} houses. Check individual neighbourhood pages or contact an agent for current insight.</p>
        @endif
      </div>
    </div>

    <div class="acd-faq-item" onclick="this.classList.toggle('open')">
      <div class="acd-faq-question">
        How many houses sold in {{ $city }} last month?
        <span class="acd-faq-chevron">&#9660;</span>
      </div>
      <div class="acd-faq-answer">
        @if($cond['sold_30d'])
        <p><strong>{{ number_format($cond['sold_30d']) }} houses and detached homes</strong> sold in {{ $city }} in the last 30 days, based on MLS® data (includes houses, duplexes, triplexes and fourplexes).</p>
        @if($cond['current_active'])
        <p style="margin-top:6px;">There are currently <strong>{{ number_format($cond['current_active']) }} active house listings</strong> in {{ $city }}.</p>
        @endif
        @else
        <p>Recent house sales count data is not available for {{ $city }} at this time.</p>
        @endif
      </div>
    </div>
  </section>

  {{-- SUBAREA BREAKDOWN --}}
  @if(count($subareaStats) > 0)
  <section class="section" aria-labelledby="subarea-heading" style="border-top:1px solid var(--border);padding-top:40px;">
    <h2 id="subarea-heading" class="h2 mb-32">{{ $city }} House Market by Neighbourhood</h2>
    <div style="overflow:hidden;border:1px solid var(--border);border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:32px;">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr style="background:#f8f8f6;text-align:left;">
            <th style="padding:12px 16px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);">Neighbourhood</th>
            <th style="padding:12px 16px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);">Avg Price</th>
            <th style="padding:12px 16px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);">Sold 30d</th>
            <th style="padding:12px 16px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);">DOM</th>
            <th style="padding:12px 16px;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);">Market</th>
            <th style="padding:12px 16px;"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($subareaStats as $saName => $saCond)
          @php $saSlug = \App\Helpers\Helper::enslugPlace($saName); @endphp
          <tr style="border-top:1px solid var(--border);">
            <td style="padding:12px 16px;font-weight:600;color:var(--text);">
              <a href="{{ route('agent.houses.subarea', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug, 'subareaSlug' => $saSlug]) }}" style="color:var(--text);text-decoration:none;">{{ $saName }}</a>
            </td>
            <td style="padding:12px 16px;color:var(--text);">
              @if($saCond['avg_sold_30d'])
              @php $p = $saCond['avg_sold_30d']; @endphp
              ${{ $p >= 1000000 ? number_format($p/1000000, 2).'M' : number_format(round($p/1000)).'K' }}
              @else<span style="color:var(--muted);">—</span>@endif
            </td>
            <td style="padding:12px 16px;color:var(--muted);">{{ $saCond['sold_30d'] ?: '—' }}</td>
            <td style="padding:12px 16px;color:var(--muted);">{{ $saCond['avg_dom'] ? $saCond['avg_dom'].'d' : '—' }}</td>
            <td style="padding:12px 16px;">
              @if($saCond['label'] && !$saCond['insufficient_data'])
              <span style="background:{{ $saCond['color'] }};color:#fff;border-radius:3px;padding:2px 7px;font-size:10px;font-weight:700;white-space:nowrap;">{{ $saCond['label'] }}</span>
              @else<span style="color:var(--muted);font-size:11px;">—</span>@endif
            </td>
            <td style="padding:12px 16px;">
              <a href="{{ route('agent.houses.subarea', ['agentSlug' => $agent->slug, 'citySlug' => $citySlug, 'subareaSlug' => $saSlug]) }}" style="color:var(--accent);font-size:12px;text-decoration:underline;white-space:nowrap;">View &rsaquo;</a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </section>
  @endif

  {{-- RECENT LISTINGS --}}
  <section class="section" aria-labelledby="listings-heading" style="border-top:1px solid var(--border);padding-top:40px;">
    <h2 id="listings-heading" class="h2 mb-32">Active Houses for Sale in {{ $city }}</h2>
    @if($recentListings->count() > 0)
    <div class="grid-3">
      @foreach($recentListings as $listing)
        @include('themes.shared.listing-card', ['listing' => $listing])
      @endforeach
    </div>
    <div style="margin-top:24px;">
      <a href="{{ route('agent.search', $agent->slug) }}?city={{ urlencode($city) }}&type=House" class="btn-cta">See All {{ $city }} Houses for Sale</a>
    </div>
    @else
    <p style="color:var(--muted);">No active house listings found in {{ $city }} right now. <a href="{{ route('agent.search', $agent->slug) }}" style="color:var(--text);text-decoration:underline;">View all listings</a>.</p>
    @endif
  </section>

</div>
@endsection

@section('w4-headline')Houses in {{ $city }} — What's Your Home Worth?@endsection
