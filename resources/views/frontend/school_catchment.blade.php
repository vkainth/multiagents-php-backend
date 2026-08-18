@extends('frontend.layouts.default')
@php
use App\Helpers\Helper;

$schoolName  = $school->name;
$city        = $school->city ?? '';
$cityLabel   = Helper::properCasePlace($city);
$citySlug    = Helper::enslugPlace($city);
$schoolType  = $school->school_type;
$grades      = $school->facility_type ?? ($schoolType === 'Secondary' ? '8–12' : 'K–7');
$districtName = $school->district_name ?? 'Surrey School District No. 36';
$canonicalUrl = url('/school-catchment/' . $school->slug);

$activeCount = $activeListings->count();
$soldCount   = $soldListings->count();

$metaTitle = "Homes for Sale in {$schoolName} Catchment — {$cityLabel} | BC Condos And Homes";
if ($activeCount > 0) {
    $metaDesc = "{$activeCount} home" . ($activeCount !== 1 ? 's' : '') . " for sale in {$schoolName} catchment, {$cityLabel} BC."
        . ($avgListPrice ? " Avg list price \$" . number_format($avgListPrice) . "." : '')
        . " Browse condos, townhouses and houses — MLS® updated daily.";
} else {
    $metaDesc = "Search homes for sale in {$schoolName} catchment area, {$cityLabel} BC."
        . " View recent sales, market trends, and contact a local agent. MLS® updated daily.";
}
@endphp
@section('title'){{ $metaTitle }}@endsection
@section('meta_description'){{ $metaDesc }}@endsection
@section('meta')
<link rel="canonical" href="{{ $canonicalUrl }}" />
<meta property="og:url" content="{{ $canonicalUrl }}" />
<meta property="og:title" content="{{ $metaTitle }}" />
<meta property="og:description" content="{{ $metaDesc }}" />
<meta property="og:type" content="website" />

{{-- FAQPage JSON-LD --}}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "How many homes are for sale in {{ $schoolName }} catchment?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "There @if($activeCount === 1)is 1 active listing@elseif($activeCount > 1)are {{ $activeCount }} active listings@else are currently no active listings@endif in the {{ $schoolName }} catchment area in {{ $cityLabel }}, BC. Inventory changes daily — check back for the latest MLS® data."
      }
    },
    {
      "@type": "Question",
      "name": "What school does a home in {{ $cityLabel }} feed into?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "School assignment in {{ $cityLabel }} is based on your home address and determined by {{ $districtName }}. {{ $schoolName }} serves grades {{ $grades }} for addresses within its designated catchment boundary. Use our school catchment search to confirm which school a specific address is zoned for."
      }
    },
    {
      "@type": "Question",
      "name": "What is the average home price in {{ $schoolName }} catchment?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "@if($avgListPrice)The average asking price for homes currently listed in the {{ $schoolName }} catchment area is \${{ number_format($avgListPrice) }}.@else Average prices vary — browse the current listings above for up-to-date pricing in the {{ $schoolName }} catchment area.@endif{{ $avgSoldPrice ? " Recent sales (last 6 months) averaged \$" . number_format($avgSoldPrice) . "." : '' }}"
      }
    },
    {
      "@type": "Question",
      "name": "What grades does {{ $schoolName }} serve?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "{{ $schoolName }} is a {{ strtolower($schoolType) }} school serving grades {{ $grades }}, located at {{ $school->address }}, {{ $cityLabel }}, BC{{ $school->postal_code ? ' ' . $school->postal_code : '' }}. It is part of {{ $districtName }}."
      }
    }
  ]
}
</script>

{{-- BreadcrumbList JSON-LD --}}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type":"ListItem","position":1,"name":"Home","item":"https://www.bccondosandhomes.com/"},
    {"@type":"ListItem","position":2,"name":"School Catchments","item":"{{ url('/school-catchments/' . $citySlug) }}"},
    {"@type":"ListItem","position":3,"name":"{{ e($schoolName) }} Catchment","item":"{{ $canonicalUrl }}"}
  ]
}
</script>
@endsection

@push('after-styles')
<style>
.sc-listing-card { border:1px solid #e2dbd2; border-radius:6px; overflow:hidden; background:#fff; box-shadow:0 2px 5px rgba(0,0,0,.05); margin-bottom:18px; transition:box-shadow .15s; }
.sc-listing-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.12); }
.sc-listing-card img { width:100%; height:160px; object-fit:cover; display:block; }
.sc-listing-card .sc-card-body { padding:12px 14px; }
.sc-listing-card .sc-price { font-size:17px; font-weight:700; color:#c0392b; }
.sc-listing-card .sc-price-sold { font-size:17px; font-weight:700; color:#888; }
.sc-listing-card .sc-addr { font-size:13px; color:#333; font-weight:600; margin:4px 0 2px; }
.sc-listing-card .sc-sub { font-size:12px; color:#777; }
.sc-stat-bar { background:#fff; border:1px solid #e2dbd2; border-radius:6px; padding:14px 20px; display:flex; flex-wrap:wrap; gap:24px; margin-bottom:22px; }
.sc-stat { text-align:center; }
.sc-stat .val { font-size:20px; font-weight:700; color:#2c6fad; }
.sc-stat .lbl { font-size:12px; color:#777; margin-top:2px; }
</style>
@endpush

@section('content')
@include('frontend.includes.header')

<div style="margin-top:80px;padding:24px 0 12px;background:#f7f4ef;border-bottom:1px solid #e2dbd2;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:8px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/school-catchments/' . $citySlug) }}">{{ $cityLabel }} Schools</a></li>
                <li class="breadcrumb-item active">{{ $schoolName }}</li>
            </ol>
        </nav>
        <h1 style="font-size:24px;font-weight:700;margin-bottom:6px;color:#2c2c2c;">
            Homes for Sale in {{ $schoolName }} Catchment &mdash; {{ $cityLabel }}
        </h1>
        <p style="font-size:13px;color:#666;margin-bottom:0;">
            {{ $schoolType }} school &middot; Grades {{ $grades }} &middot; {{ $school->address }}, {{ $cityLabel }}, BC &middot; {{ $districtName }}
        </p>
    </div>
</div>

<div class="container" style="padding-top:24px;padding-bottom:60px;">
    <div class="row">

        {{-- Main column --}}
        <div class="col-md-8 col-sm-12">

            {{-- Stats bar --}}
            <div class="sc-stat-bar">
                <div class="sc-stat">
                    <div class="val">{{ $activeCount }}</div>
                    <div class="lbl">Active Listings</div>
                </div>
                @if($avgListPrice)
                <div class="sc-stat">
                    <div class="val">${{ number_format($avgListPrice / 1000) }}K</div>
                    <div class="lbl">Avg List Price</div>
                </div>
                @endif
                @if($avgSoldPrice)
                <div class="sc-stat">
                    <div class="val">${{ number_format($avgSoldPrice / 1000) }}K</div>
                    <div class="lbl">Avg Sold (6 mo)</div>
                </div>
                @endif
                @if($avgSoldPsf)
                <div class="sc-stat">
                    <div class="val">${{ number_format($avgSoldPsf) }}</div>
                    <div class="lbl">Avg $/sqft (sold)</div>
                </div>
                @endif
                <div class="sc-stat">
                    <div class="val">{{ $soldCount }}</div>
                    <div class="lbl">Sold (6 months)</div>
                </div>
            </div>

            {{-- Active listings --}}
            <h2 style="font-size:18px;font-weight:700;margin:0 0 14px;color:#2c2c2c;">
                Active Listings in {{ $schoolName }} Catchment
                @if($activeCount > 0)
                <span style="font-size:14px;font-weight:400;color:#777;">({{ $activeCount }})</span>
                @endif
            </h2>

            @if($activeListings->isNotEmpty())
            <div class="row">
                @foreach($activeListings->take(24) as $listing)
                <div class="col-md-6 col-sm-6">
                    <a href="{{ route('listing-detail-page2', ['slug' => $listing->slug]) }}" class="sc-listing-card" style="display:block;text-decoration:none;color:inherit;">
                        @if($listing->mainpicurl)
                        <img src="{{ $listing->mainpicurl }}" alt="{{ $listing->streetaddress }}" loading="lazy" />
                        @else
                        <div style="height:160px;background:#eee;display:flex;align-items:center;justify-content:center;color:#aaa;font-size:13px;">No Photo</div>
                        @endif
                        <div class="sc-card-body">
                            <div class="sc-price">${{ number_format($listing->listprice_2) }}</div>
                            <div class="sc-addr">{{ $listing->streetaddress }}</div>
                            <div class="sc-sub">
                                {{ $listing->bedrooms }} bed &middot; {{ $listing->bathstotal }} bath
                                @if($listing->livingarea_2) &middot; {{ number_format($listing->livingarea_2) }} sqft @endif
                                &middot; {{ $listing->type }}
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
            @else
            <div style="background:#f9f9f7;border:1px solid #e2dbd2;border-radius:6px;padding:22px;text-align:center;color:#666;font-size:14px;margin-bottom:22px;">
                No active listings currently in the {{ $schoolName }} catchment area.<br>
                <a href="{{ url('/search-listings/' . $citySlug) }}" style="color:#2c6fad;font-weight:600;">Browse all {{ $cityLabel }} listings &rsaquo;</a>
            </div>
            @endif

            {{-- Sold listings --}}
            <h2 style="font-size:18px;font-weight:700;margin:22px 0 14px;color:#2c2c2c;">
                Recently Sold in {{ $schoolName }} Catchment
                <span style="font-size:13px;font-weight:400;color:#777;">— last 6 months</span>
            </h2>

            @if($soldListings->isNotEmpty())
            <div class="table-responsive" style="margin-bottom:22px;">
                <table class="table" style="font-size:13px;margin-bottom:0;">
                    <thead style="background:#f5f2ed;color:#444;">
                        <tr>
                            <th>Address</th>
                            <th>Type</th>
                            <th>Bed/Bath</th>
                            <th>Sqft</th>
                            <th style="text-align:right;">Sold Price</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($soldListings->take(30) as $listing)
                    <tr>
                        <td>
                            <a href="{{ route('listing-detail-page2', ['slug' => $listing->slug]) }}" style="color:#2c6fad;">
                                {{ $listing->streetaddress }}
                            </a>
                        </td>
                        <td>{{ $listing->type }}</td>
                        <td>{{ $listing->bedrooms }}/{{ $listing->bathstotal }}</td>
                        <td>{{ $listing->livingarea_2 ? number_format($listing->livingarea_2) : '—' }}</td>
                        <td style="text-align:right;font-weight:700;">
                            @if(!$isGuest)
                                <span style="color:#c0392b;">${{ number_format($listing->soldprice_2) }}</span>
                            @else
                                <a href="{{ route('login.with.agent') }}" style="color:#2c6fad;font-size:12px;">
                                    Sign in to view
                                </a>
                            @endif
                        </td>
                        <td style="white-space:nowrap;color:#666;">
                            {{ $listing->sold_date ? date('M j, Y', strtotime($listing->sold_date)) : '—' }}
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($isGuest)
            <div style="background:#f0f7ff;border:1px solid #c3d9f5;border-radius:6px;padding:14px 18px;font-size:13px;color:#1a5ea8;margin-bottom:18px;">
                <strong>Sign in free</strong> to view all sold prices in the {{ $schoolName }} catchment area.
                <a href="{{ route('login.with.agent') }}" style="margin-left:10px;background:#2c6fad;color:#fff;padding:4px 14px;border-radius:4px;font-weight:700;text-decoration:none;">Sign In / Register</a>
            </div>
            @endif
            @else
            <div style="background:#f9f9f7;border:1px solid #e2dbd2;border-radius:6px;padding:18px;text-align:center;color:#666;font-size:14px;">
                No sales recorded in the last 6 months for this catchment area.
            </div>
            @endif

            {{-- FAQ section --}}
            <div style="margin-top:32px;">
                <h2 style="font-size:17px;font-weight:700;margin-bottom:14px;color:#2c2c2c;">Frequently Asked Questions — {{ $schoolName }} Catchment</h2>

                <div style="border:1px solid #e2dbd2;border-radius:6px;overflow:hidden;margin-bottom:18px;">
                    @php $faqs = [
                        [
                            'q' => "What homes are for sale in {$schoolName} catchment?",
                            'a' => $activeCount > 0
                                ? "There " . ($activeCount === 1 ? "is 1 active listing" : "are {$activeCount} active listings") . " currently for sale in the {$schoolName} catchment area in {$cityLabel}, BC. Browse all " . ($activeCount === 1 ? "it" : "them") . " above or contact us for a full market overview."
                                : "There are no active listings right now in the {$schoolName} catchment area. New homes are listed daily — sign up for alerts to be notified immediately.",
                        ],
                        [
                            'q' => "What school does {$schoolName} feed into for high school?",
                            'a' => $schoolType === 'Elementary'
                                ? "Elementary students in the {$schoolName} catchment typically transition to a designated secondary school in {$districtName}. Contact the district or visit the SD36 website for the exact secondary school assignment for your address."
                                : "{$schoolName} is itself a {$schoolType} school serving grades {$grades}. Students progress within the {$districtName} system.",
                        ],
                        [
                            'q' => "What is the average home price near {$schoolName}?",
                            'a' => $avgListPrice
                                ? "The average asking price for homes currently listed in the {$schoolName} catchment is \$" . number_format($avgListPrice) . "." . ($avgSoldPrice ? " Homes sold in the last 6 months averaged \$" . number_format($avgSoldPrice) . "." : '') . " Prices vary by property type — condos, townhouses, and detached homes each have different price ranges."
                                : "Average prices in the {$schoolName} catchment vary by property type and current market conditions. Contact our team for a free home valuation or market report for this area.",
                        ],
                        [
                            'q' => "How do I find out if a specific address is in {$schoolName} catchment?",
                            'a' => "The most accurate way to confirm your address falls within the {$schoolName} catchment is to check the official {$districtName} catchment maps at sd36.bc.ca or contact the school directly. All listings shown on this page are within the approximate catchment boundary.",
                        ],
                    ]; @endphp

                    @foreach($faqs as $idx => $faq)
                    <div style="{{ $idx > 0 ? 'border-top:1px solid #e2dbd2;' : '' }}padding:14px 18px;">
                        <div style="font-size:14px;font-weight:700;color:#2c2c2c;margin-bottom:6px;">{{ $faq['q'] }}</div>
                        <div style="font-size:13px;color:#555;line-height:1.65;">{{ $faq['a'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Internal links --}}
            <div style="background:#f9f9f7;border:1px solid #e2dbd2;border-radius:6px;padding:16px 20px;font-size:13px;margin-top:10px;">
                <div style="font-weight:700;color:#333;margin-bottom:8px;">Explore more in {{ $cityLabel }}</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <a href="{{ url('/school-catchments/' . $citySlug) }}" style="color:#2c6fad;">All {{ $cityLabel }} School Catchments</a>
                    <span style="color:#ccc;">|</span>
                    <a href="{{ url('/search-listings/' . $citySlug) }}" style="color:#2c6fad;">All {{ $cityLabel }} Listings</a>
                    <span style="color:#ccc;">|</span>
                    <a href="{{ url('/neighbourhood/' . $citySlug) }}" style="color:#2c6fad;">{{ $cityLabel }} Neighbourhood Guide</a>
                    <span style="color:#ccc;">|</span>
                    <a href="{{ url('/' . $citySlug . '-condos-for-sale') }}" style="color:#2c6fad;">Condos for Sale in {{ $cityLabel }}</a>
                    <span style="color:#ccc;">|</span>
                    <a href="{{ url('/market-stats/' . $citySlug) }}" style="color:#2c6fad;">{{ $cityLabel }} Market Stats</a>
                </div>
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="col-md-4 col-sm-12 hidden-xs" style="padding-left:24px;">

            {{-- School info card --}}
            <div style="border:1px solid #e2dbd2;border-radius:6px;background:#fff;padding:18px;margin-bottom:20px;">
                <div style="font-size:13px;font-weight:700;color:#777;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">School Information</div>
                <div style="font-size:16px;font-weight:700;color:#2c2c2c;margin-bottom:6px;">{{ $schoolName }}</div>
                <table style="width:100%;font-size:13px;line-height:1.7;">
                    <tr><td style="color:#888;width:50%;">Type</td><td style="font-weight:600;">{{ $schoolType }}</td></tr>
                    <tr><td style="color:#888;">Grades</td><td style="font-weight:600;">{{ $grades }}</td></tr>
                    <tr><td style="color:#888;">District</td><td>{{ $districtName }}</td></tr>
                    @if($school->address)
                    <tr><td style="color:#888;">Address</td><td>{{ $school->address }}, {{ $cityLabel }}</td></tr>
                    @endif
                    @if($school->postal_code)
                    <tr><td style="color:#888;">Postal Code</td><td>{{ strtoupper($school->postal_code) }}</td></tr>
                    @endif
                </table>
                <div style="margin-top:12px;">
                    <a href="https://sd36.bc.ca" target="_blank" rel="noopener noreferrer" style="font-size:12px;color:#2c6fad;">SD36 Official Site &rsaquo;</a>
                </div>
            </div>

            {{-- CTA --}}
            <div style="background:linear-gradient(135deg,#1a3a5c 0%,#2c6fad 100%);border-radius:8px;padding:20px;color:#fff;margin-bottom:20px;">
                <div style="font-size:15px;font-weight:700;margin-bottom:8px;">Find Your Perfect Home in {{ $schoolName }} Catchment</div>
                <div style="font-size:13px;opacity:.9;margin-bottom:14px;line-height:1.55;">
                    Work with a local expert who knows {{ $cityLabel }} school catchments inside and out. Free consultation — no obligation.
                </div>
                @include('frontend.includes.contact_form_sidebar')
            </div>

            {{-- Other schools in city --}}
            <div style="border:1px solid #e2dbd2;border-radius:6px;background:#fff;padding:16px;font-size:13px;">
                <div style="font-weight:700;color:#333;margin-bottom:10px;">Other {{ $cityLabel }} Schools</div>
                @php
                    $otherSchools = \App\Models\School::whereRaw('LOWER(city) = LOWER(?)', [$school->city])
                        ->where('id', '!=', $school->id)
                        ->where('is_public', true)
                        ->orderBy('school_type')->orderBy('name')
                        ->limit(8)->get();
                @endphp
                @foreach($otherSchools as $other)
                <div style="padding:4px 0;border-bottom:1px solid #f0ece4;">
                    <a href="{{ url('/school-catchment/' . $other->slug) }}" style="color:#2c6fad;">{{ $other->name }}</a>
                    <span style="font-size:11px;color:#999;float:right;">{{ $other->school_type }}</span>
                </div>
                @endforeach
                <div style="margin-top:10px;">
                    <a href="{{ url('/school-catchments/' . $citySlug) }}" style="color:#c0392b;font-weight:700;font-size:12px;">View all {{ $cityLabel }} schools &rsaquo;</a>
                </div>
            </div>

        </div>

    </div>
</div>

@include('frontend.includes.footer')
@endsection
