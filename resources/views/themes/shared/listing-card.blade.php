{{--
  Shared listing card — used across ALL themes.
  Variables:
    $listing  — Listings model
    $agent    — Agent model (optional, from View::share)
--}}
@php
  $isSold   = strtolower($listing->status ?? '') === 'sold';
  $price    = $isSold ? ($listing->soldprice_2 ?? 0) : ($listing->listprice_2 ?? 0);
  $imgUrl   = $listing->mainpicurl ?? $listing->thumbnailurl ?? null;
  $dom      = $isSold ? ($listing->dom ?? 0) : $listing->active_days_on_market();
  $agentSlug = isset($agent) ? $agent->slug : null;

  // Use agent-scoped route when rendering inside an agent theme; fall back to
  // the main-site route for all other contexts.
  $cardHref = $agentSlug
    ? ($isSold
        ? route('agent.listing.sold', ['agentSlug' => $agentSlug, 'listingSlug' => $listing->slug])
        : route('agent.listing', ['agentSlug' => $agentSlug, 'listingSlug' => $listing->slug]))
    : route('listing-detail-page2', ['slug' => $listing->slug]);
@endphp

<article class="listing-card" itemscope itemtype="https://schema.org/Residence">
  <a href="{{ $cardHref }}" aria-label="{{ $listing->streetaddress }}" class="d-block">

    <div class="listing-card__img">
      @if($imgUrl)
        <img src="{{ $imgUrl }}" alt="{{ $listing->streetaddress }}" loading="lazy" itemprop="image">
      @else
        <img src="{{ asset('frontend/images/no-listing-photo.svg') }}" alt="No photo available" loading="lazy" style="width:100%;height:100%;object-fit:cover;">
      @endif

      @if($isSold)
        <span class="listing-card__badge listing-card__badge--sold">Sold</span>
      @elseif($listing->list_date && \Carbon\Carbon::parse($listing->list_date)->diffInDays() <= 7)
        <span class="listing-card__badge">New</span>
      @endif
    </div>

    <div class="listing-card__body">
      <div class="listing-card__price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
        <span itemprop="price" content="{{ $price }}">
          ${{ number_format($price) }}
        </span>
        <meta itemprop="priceCurrency" content="CAD">
      </div>

      <div class="listing-card__address" itemprop="address">{{ $listing->streetaddress }}</div>

      <div class="listing-card__subarea">
        {{ $listing->subarea ?? $listing->city }}
        @if($listing->type)
          &nbsp;·&nbsp; {{ $listing->type === '1/2 Duplex' ? 'Duplex' : $listing->type }}
        @endif
      </div>

      <div class="listing-card__meta">
        @if($listing->bedrooms)
          <span>{{ $listing->bedrooms }} bed{{ $listing->bedrooms != 1 ? 's' : '' }}</span>
        @endif
        @if($listing->bathstotal)
          <span>{{ $listing->bathstotal }} bath{{ $listing->bathstotal != 1 ? 's' : '' }}</span>
        @endif
        @if($listing->livingarea_2)
          <span>{{ number_format($listing->livingarea_2) }} sqft</span>
        @endif
        @if($isSold && $listing->sold_date)
          <span>Sold {{ \Carbon\Carbon::parse($listing->sold_date)->format('M j') }}</span>
        @elseif(!$isSold && $dom > 0)
          <span>{{ $dom }} days on market</span>
        @endif
        @if(!$isSold && $listing->maintenance && $listing->maintenance > 0)
          <span>${{ number_format($listing->maintenance) }}/mo strata</span>
        @endif
      </div>
    </div>

  </a>
</article>
