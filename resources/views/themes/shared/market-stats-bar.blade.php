{{--
  Shared market stats bar — summary strip of live territory data.
  Variables:
    $statsBar — array with keys: active_count, sold_count, avg_sold_price, avg_dom, list_to_sale
--}}
@php
  $activeCount  = $statsBar['active_count']   ?? 0;
  $soldCount    = $statsBar['sold_count']      ?? 0;
  $avgSoldPrice = $statsBar['avg_sold_price']  ?? 0;
  $avgDom       = $statsBar['avg_dom']         ?? 0;
  $listToSale   = $statsBar['list_to_sale']    ?? 0;
@endphp

<div class="market-stats-bar">
  <div class="market-stats-bar__item">
    <div class="market-stats-bar__value">{{ number_format($activeCount) }}</div>
    <div class="market-stats-bar__label">Active listings</div>
  </div>
  <div class="market-stats-bar__item">
    <div class="market-stats-bar__value">{{ number_format($soldCount) }}</div>
    <div class="market-stats-bar__label">Sold (30 days)</div>
  </div>
  @if($avgSoldPrice > 0)
  <div class="market-stats-bar__item">
    <div class="market-stats-bar__value">${{ $avgSoldPrice >= 1000000 ? number_format($avgSoldPrice / 1000000, 2) . 'M' : number_format($avgSoldPrice / 1000, 0) . 'K' }}</div>
    <div class="market-stats-bar__label">Avg sold price</div>
  </div>
  @endif
  @if($avgDom > 0)
  <div class="market-stats-bar__item">
    <div class="market-stats-bar__value">{{ round($avgDom) }}</div>
    <div class="market-stats-bar__label">Avg days on market</div>
  </div>
  @endif
  @if($listToSale > 0)
  <div class="market-stats-bar__item">
    <div class="market-stats-bar__value">{{ number_format($listToSale, 1) }}%</div>
    <div class="market-stats-bar__label">List-to-sale ratio</div>
  </div>
  @endif
</div>
