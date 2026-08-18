@extends('frontend.layouts.default_mobile')
@section('title'){{ $metaTitle }}@endsection
@section('meta_description'){{ $metaDesc }}@endsection
@section('meta')
<link rel="canonical" href="{{ $canonical }}">
<meta name="robots" content="noindex,follow">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:url" content="{{ $canonical }}">
@endsection
@section('content')
@include('frontend.includes.header')

<div class="page-main" style="margin-top:66px;padding:28px 0 0;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:10px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item active">Sold Over Asking in {{ $city }}</li>
            </ol>
        </nav>
        <h1 style="font-size:24px;font-weight:700;margin-bottom:6px;color:#2c2c2c;">
            Sold Over Asking – {{ $city }}, BC
        </h1>
        <p style="font-size:14px;color:#666;max-width:780px;line-height:1.7;margin-bottom:8px;">
            Properties in {{ $city }} that sold above their asking price in the last 90 days.
            @if($isGuest)
            <a href="/login" style="color:#2c6fad;font-weight:600;">Sign in</a> to see sold prices.
            @endif
        </p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
            <a href="/new-listings/{{ $citySlug }}" style="font-size:12px;color:#2c6fad;text-decoration:none;">New This Week →</a>
            <a href="/price-reductions/{{ $citySlug }}" style="font-size:12px;color:#2c6fad;text-decoration:none;">Price Reductions →</a>
            <a href="/market-update/{{ $citySlug }}" style="font-size:12px;color:#2c6fad;text-decoration:none;">Monthly Updates →</a>
        </div>
    </div>
</div>

<div class="container" style="padding-bottom:40px;">

    @if($listings->isEmpty())
    <div class="row" style="margin-top:22px;">
        <div class="col-md-12">
            <div style="background:#f7f4ef;border-left:4px solid #ccc;border-radius:4px;padding:14px 18px;font-size:14px;color:#666;">
                No sold-over-asking listings found in <strong>{{ $city }}</strong> in the last 90 days.
            </div>
        </div>
    </div>
    @else
    <div class="row" style="margin-top:22px;">
        @foreach($listings as $listing)
        @php
        $overAmt = ($listing->soldprice_2 ?? 0) - ($listing->listprice_2 ?? 0);
        $overPct = ($listing->listprice_2 > 0 && $overAmt > 0)
                    ? round($overAmt / $listing->listprice_2 * 100, 1)
                    : 0;
        $domDays = ($listing->dom > 0) ? (int)$listing->dom
                 : ($listing->sold_date && $listing->list_date
                     ? max(0, \Carbon\Carbon::parse($listing->list_date)->diffInDays(\Carbon\Carbon::parse($listing->sold_date)))
                     : null);
        @endphp
        <div class="col-md-4 col-sm-6" style="margin-bottom:22px;">
            <div style="position:relative;">
                @if($overAmt > 0)
                <div style="position:absolute;top:0;left:0;z-index:10;background:#27ae60;color:#fff;font-size:11px;font-weight:700;padding:4px 8px;border-radius:4px 0 4px 0;line-height:1.3;">
                    ▲ {{ $overPct }}% over asking
                </div>
                @endif
                <div style="border-radius:6px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.09);">
                    {{-- Photo + link --}}
                    <a href="{{ route('listing-detail-page2', ['slug'=>$listing->slug]) }}" style="display:block;background:#f0f0f0;">
                        @if($listing->photos->first())
                        <img src="https://media.pixilinkserver.com/{{ str_replace('images','',$listing->photos->first()->directory.$listing->photos->first()->name) }}"
                             alt="{{ $listing->streetaddress }}" loading="lazy"
                             style="width:100%;height:190px;object-fit:cover;display:block;">
                        @else
                        <img src="https://www.bccondosandhomes.com/assets/img/no-image-800-600.png" alt="No image" loading="lazy"
                             style="width:100%;height:190px;object-fit:cover;display:block;">
                        @endif
                    </a>
                    <div style="padding:14px 16px;background:#fff;">
                        <div style="font-size:14px;font-weight:600;">
                            <a href="{{ route('listing-detail-page2', ['slug'=>$listing->slug]) }}" style="color:#222;text-decoration:none;">
                                {{ $listing->streetaddress }}, {{ $listing->city }}
                            </a>
                        </div>
                        <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:12px;font-size:13px;color:#555;">
                            @if($listing->listprice_2)
                            <span>Ask: <strong>${{ number_format($listing->listprice_2) }}</strong></span>
                            @endif
                            @if(!$isGuest && $listing->soldprice_2)
                            <span style="color:#27ae60;">Sold: <strong>${{ number_format($listing->soldprice_2) }}</strong></span>
                            @elseif($isGuest)
                            <span style="color:#2c6fad;"><a href="/login" style="color:#2c6fad;">Sign in to see sold price</a></span>
                            @endif
                        </div>
                        <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:10px;font-size:12px;color:#888;">
                            @if($overAmt > 0)
                            <span style="color:#27ae60;font-weight:600;">+${{ number_format($overAmt) }} ({{ $overPct }}%)</span>
                            @endif
                            @if($domDays !== null)
                            <span>{{ $domDays }} {{ $domDays === 1 ? 'day' : 'days' }} on market</span>
                            @endif
                            @if($listing->sold_date)
                            <span>Sold {{ \Carbon\Carbon::parse($listing->sold_date)->format('M j, Y') }}</span>
                            @endif
                        </div>
                        @if($listing->bedrooms || $listing->bathstotal || $listing->livingarea_2)
                        <div style="margin-top:8px;font-size:12px;color:#888;display:flex;gap:12px;flex-wrap:wrap;">
                            @if($listing->bedrooms)<span>{{ $listing->bedrooms }} bed</span>@endif
                            @if($listing->bathstotal)<span>{{ $listing->bathstotal }} bath</span>@endif
                            @if($listing->livingarea_2)<span>{{ number_format($listing->livingarea_2) }} sqft</span>@endif
                            <span>{{ $listing->type }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($isGuest)
    <div class="row" style="margin-top:16px;">
        <div class="col-md-12">
            <div style="background:#eaf4fb;border-left:4px solid #2c6fad;border-radius:4px;padding:14px 18px;font-size:14px;color:#555;">
                <strong>Want to see sold prices?</strong> <a href="/login" style="color:#2c6fad;font-weight:600;">Create a free account</a> to unlock full sold price history on all listings.
            </div>
        </div>
    </div>
    @endif
    @endif

    {{-- Email alert signup --}}
    <div class="row" style="margin-top:30px;">
        <div class="col-md-8 col-md-offset-2">
            @include('frontend.includes.market_intel_alert_widget', ['city' => $city, 'citySlug' => $citySlug, 'source' => 'sold_over_asking'])
        </div>
    </div>

    {{-- Internal links --}}
    <div class="row" style="margin-top:24px;">
        <div class="col-md-12">
            <div style="background:#f7f4ef;border-radius:6px;padding:14px 18px;font-size:13px;color:#555;">
                <strong>Related:</strong>
                <a href="/search-listings/{{ $citySlug }}" style="margin-left:12px;color:#2c6fad;">All {{ $city }} Listings →</a>
                <a href="/market-report/{{ $citySlug }}" style="margin-left:12px;color:#2c6fad;">Market Reports →</a>
                <a href="/market-stats/{{ $citySlug }}" style="margin-left:12px;color:#2c6fad;">Market Stats →</a>
            </div>
        </div>
    </div>
</div>

<div class="listings-disclaimer">
    <div class="container">
        <p>Last Update: {{ \Carbon\Carbon::now()->format('m/d/Y') }} &nbsp;&nbsp;<strong>Disclaimer:</strong> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy.</p>
    </div>
</div>

@include('frontend.includes.hani_bubble')
@include('frontend.includes.footer_links')
@include('frontend.includes.footer')
@endsection
