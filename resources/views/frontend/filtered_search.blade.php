@extends('frontend.layouts.default')
@php
use App\Helpers\Helper;

$currentMonth   = date('F Y');
$filterType     = $filterType ?? 'bedroom';
$locationLabel  = $locationLabel ?? 'BC';
$h1             = $h1 ?? 'Condos For Sale';
$stats          = $stats ?? ['count' => 0, 'avg_price' => 0, 'avg_dom' => 0];
$loc            = $loc ?? [];
$canonicalUrl   = $canonicalUrl ?? url()->current();
$summary        = $summary ?? '';

// ---- per-page SEO ----
if ($filterType === 'hub') {
    $metaTitle = "Condos For Sale in {$locationLabel} | {$currentMonth} | Hani & Les";
    $metaDesc  = "Browse " . ($totalActive ?? 0) . " condos and homes for sale in {$locationLabel}."
        . " 1–4 bedroom, townhouses, pet-friendly and more. Updated daily from MLS\u{00AE}.";
} elseif ($filterType === 'bedroom') {
    $metaTitle = "{$h1} | {$currentMonth} | BC Condos And Homes";
    $metaDesc  = "Browse " . number_format($stats['count']) . " {$filterPhrase ?? $h1} in {$locationLabel}."
        . ($stats['avg_price'] ? " Avg asking price \$" . number_format($stats['avg_price']) . "." : '')
        . " Live MLS\u{00AE} listings updated daily.";
} elseif ($filterType === 'type') {
    $metaTitle = "{$h1} | {$currentMonth} | BC Condos And Homes";
    $metaDesc  = "Find " . number_format($stats['count']) . " " . strtolower($typeLabel ?? 'properties')
        . " for sale in {$locationLabel}."
        . ($stats['avg_price'] ? " Average price \$" . number_format($stats['avg_price']) . "." : '')
        . " MLS\u{00AE} data updated daily.";
} elseif ($filterType === 'lifestyle') {
    $metaTitle = "{$h1} | {$currentMonth} | BC Condos And Homes";
    $metaDesc  = "Find " . number_format($stats['count']) . " " . strtolower($filterTitle ?? 'condos')
        . " in {$locationLabel}."
        . ($stats['avg_price'] ? " Avg price \$" . number_format($stats['avg_price']) . "." : '')
        . " MLS\u{00AE} listings updated daily.";
} elseif ($filterType === 'landmark') {
    $metaTitle = "{$h1} | Near {$locationLabel} | BC Condos And Homes";
    $metaDesc  = number_format($stats['count']) . " condos for sale near {$locationLabel}."
        . ($stats['avg_price'] ? " Avg price \$" . number_format($stats['avg_price']) . "." : '')
        . " Live MLS\u{00AE} data.";
} else {
    $metaTitle = "{$h1} | BC Condos And Homes";
    $metaDesc  = "Browse properties for sale in {$locationLabel}. Updated daily.";
}

// Breadcrumb
$breadcrumbs = [['Home', 'https://www.bccondosandhomes.com/']];
if (!empty($loc['city_slug']) && !empty($loc['city'])) {
    $breadcrumbs[] = [ucwords($loc['city']), "https://www.bccondosandhomes.com/" . $loc['city_slug'] . "-condos-for-sale"];
}
$breadcrumbs[] = [$h1, $canonicalUrl];

// FAQ items
$faqItems = [];
if ($filterType !== 'hub') {
    $faqItems[] = [
        'q' => "How many {$filterPhrase ?? $h1} are available?",
        'a' => $stats['count']
            ? "There are currently " . number_format($stats['count']) . " active " . ($filterPhrase ?? $h1)
              . " in {$locationLabel}. Listings are sourced directly from MLS\u{00AE} and updated daily."
            : "There are currently no active listings matching this search in {$locationLabel}.",
    ];
    if (!empty($stats['avg_price'])) {
        $faqItems[] = [
            'q' => "What is the average price for {$filterPhrase ?? $h1} in {$locationLabel}?",
            'a' => "The current average asking price for " . ($filterPhrase ?? 'these properties')
                . " in {$locationLabel} is \$" . number_format($stats['avg_price'])
                . ", based on active MLS\u{00AE} listings.",
        ];
    }
    if (!empty($stats['avg_dom'])) {
        $faqItems[] = [
            'q' => "How long do condos stay on the market in {$locationLabel}?",
            'a' => "On average, listings in {$locationLabel} currently spend {$stats['avg_dom']} days on the market before selling.",
        ];
    }
}
@endphp

@section('title'){{ $metaTitle }}@endsection
@section('meta_description'){{ $metaDesc }}@endsection

@section('meta')
<link rel="canonical" href="{{ $canonicalUrl }}">
@if(!empty($listings) && $listings->currentPage() > 1)
<meta name="robots" content="noindex, follow">
@endif
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "BreadcrumbList",
      "itemListElement": [
        @foreach($breadcrumbs as $i => $bc)
        {"@type":"ListItem","position":{{ $i + 1 }},"name":"{{ e($bc[0]) }}","item":"{{ e($bc[1]) }}"}{{ !$loop->last ? ',' : '' }}
        @endforeach
      ]
    }
    @if(count($faqItems))
    ,{
      "@type": "FAQPage",
      "mainEntity": [
        @foreach($faqItems as $faq)
        {"@type":"Question","name":"{{ e($faq['q']) }}","acceptedAnswer":{"@type":"Answer","text":"{{ e(strip_tags($faq['a'])) }}"}}{{ !$loop->last ? ',' : '' }}
        @endforeach
      ]
    }
    @endif
  ]
}
</script>
@endsection

@section('content')
@include('frontend.includes.header')

{{-- ===================== HERO BAND ===================== --}}
<div class="page-main" style="padding:28px 0 18px;background:linear-gradient(135deg,#1a2a3a 0%,#2c3e50 100%);color:#fff;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:10px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                @foreach($breadcrumbs as $bc)
                    @if($loop->last)
                        <li class="breadcrumb-item active" style="color:#fff;">{{ $bc[0] }}</li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ $bc[1] }}" style="color:rgba(255,255,255,.7);">{{ $bc[0] }}</a></li>
                    @endif
                @endforeach
            </ol>
        </nav>

        <h1 style="font-size:26px;font-weight:700;margin:0 0 10px;color:#fff;">{{ $h1 }}</h1>

        @if($filterType !== 'hub')
        <p style="font-size:14px;color:rgba(255,255,255,.82);max-width:820px;line-height:1.65;margin:0 0 12px;">
            {!! $summary !!}
        </p>
        @endif

        <div style="display:flex;flex-wrap:wrap;gap:9px;font-size:13px;">
            @if(!empty($loc['city_slug']))
            <a href="/search-listings/{{ $loc['city_slug'] }}" style="background:#e74c3c;color:#fff;border-radius:4px;padding:7px 16px;text-decoration:none;font-weight:700;">
                View All {{ $loc['location_label'] ?? $locationLabel }} Listings
            </a>
            @endif
            @if(!empty($loc['city_slug']))
            <a href="/{{ $loc['city_slug'] }}-condos-for-sale" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:4px;padding:7px 14px;text-decoration:none;">
                {{ $loc['location_label'] ?? $locationLabel }} Condos Hub
            </a>
            @endif
            @if(!empty($loc['city_slug']))
            <a href="/market-report/{{ $loc['city_slug'] }}" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:4px;padding:7px 14px;text-decoration:none;">
                Market Reports
            </a>
            @endif
        </div>
    </div>
</div>

<div class="container" style="padding-top:24px;padding-bottom:50px;min-height:60vh;">

{{-- ===================== STAT BAR ===================== --}}
@if($filterType !== 'hub' && $stats['count'])
<div class="row" style="margin-bottom:22px;">
    @foreach([
        ['Active Listings', number_format($stats['count']), 'matching your search'],
        ['Avg Asking Price', $stats['avg_price'] ? '$'.number_format($stats['avg_price']) : '—', 'current active'],
        ['Avg Days on Market', $stats['avg_dom'] ? $stats['avg_dom'].'d' : '—', 'before sold'],
    ] as $tile)
    <div class="col-md-4 col-sm-4" style="margin-bottom:12px;">
        <div style="background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:16px 14px;text-align:center;">
            <div style="font-size:{{ strlen($tile[1]) > 6 ? '18' : '24' }}px;font-weight:700;color:#2c2c2c;">{{ $tile[1] }}</div>
            <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-top:4px;">{{ $tile[0] }}</div>
            <div style="font-size:11px;color:#bbb;margin-top:2px;">{{ $tile[2] }}</div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ===================== HUB PAGE ===================== --}}
@if($filterType === 'hub')
<div style="background:#f9f7f4;border:1px solid #e2dbd2;border-radius:6px;padding:20px 22px;margin-bottom:28px;">
    <p style="font-size:15px;color:#444;line-height:1.75;margin:0;">
        Browse all {{ $totalActive ?? 0 }} active condos and homes for sale in <strong>{{ $locationLabel }}</strong>.
        Use the links below to filter by bedroom count, property type, or lifestyle preference.
        @if(!empty($avgPrice))
        The average asking price is <strong>${{ number_format($avgPrice) }}</strong>.
        @endif
        All data sourced directly from MLS<sup>®</sup>, updated daily.
    </p>
</div>

{{-- Bedroom variants --}}
@if(!empty($bedroomVariants))
<section style="margin-bottom:28px;">
    <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">Browse by Bedroom Count</h2>
    <div class="row">
        @foreach($bedroomVariants as $b => $cnt)
        @php
            $bLabel = ['','One','Two','Three','Four','Five','Six'][$b] ?? $b;
        @endphp
        <div class="col-md-3 col-sm-6" style="margin-bottom:12px;">
            <a href="/{{ $b }}-bedroom-condos-for-sale-{{ $city }}" style="text-decoration:none;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:5px;padding:16px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                    <div style="font-size:22px;font-weight:700;color:#2c6fad;">{{ $b }}</div>
                    <div style="font-size:12px;font-weight:600;color:#333;margin-top:2px;">Bedroom Condos</div>
                    <div style="font-size:11px;color:#888;margin-top:4px;">{{ number_format($cnt) }} active</div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</section>
@endif

{{-- Type variants --}}
@if(!empty($typeVariants))
<section style="margin-bottom:28px;">
    <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">Browse by Property Type</h2>
    <div class="row">
        @foreach($typeVariants as $tSlug => $tInfo)
        <div class="col-md-4 col-sm-6" style="margin-bottom:12px;">
            <a href="/{{ $tSlug }}-for-sale-{{ $city }}" style="text-decoration:none;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:5px;padding:14px 16px;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                    <div style="font-size:15px;font-weight:700;color:#2c2c2c;">{{ $tInfo['label'] }}</div>
                    <div style="font-size:12px;color:#888;margin-top:4px;">{{ number_format($tInfo['count']) }} active listings</div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</section>
@endif

{{-- Lifestyle variants --}}
@if(!empty($lifestyleVariants))
<section style="margin-bottom:28px;">
    <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">Lifestyle Searches</h2>
    <div class="row">
        @foreach($lifestyleVariants as $lSlug => $lInfo)
        <div class="col-md-4 col-sm-6" style="margin-bottom:12px;">
            <a href="{{ $lInfo['url'] }}" style="text-decoration:none;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:5px;padding:14px 16px;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                    <div style="font-size:15px;font-weight:700;color:#2c2c2c;">{{ $lInfo['label'] }}</div>
                    <div style="font-size:12px;color:#888;margin-top:4px;">{{ number_format($lInfo['count']) }} active listings</div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</section>
@endif

{{-- Landmark pages --}}
@if(!empty($allLandmarks))
<section style="margin-bottom:28px;">
    <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">Condos Near Local Landmarks</h2>
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
        @foreach($allLandmarks as $lmk)
        <a href="/condos-near-{{ $lmk['slug'] }}"
           style="background:#fff;border:1px solid #e2dbd2;border-radius:4px;padding:8px 14px;font-size:13px;color:#2c6fad;text-decoration:none;box-shadow:0 1px 3px rgba(0,0,0,.05);">
            Near {{ $lmk['name'] }}
        </a>
        @endforeach
    </div>
</section>
@endif

@else

{{-- ===================== LISTING GRID (non-hub) ===================== --}}
@if(!empty($listings) && $listings->count())
<section style="margin-bottom:28px;">
    <div class="row">
        @foreach($listings as $lst)
        @php
            $photo = $lst->aphoto
                ? 'https://media.pixilinkserver.com/' . str_replace('images', '', $lst->aphoto->directory . $lst->aphoto->name)
                : null;
            $addr = trim(
                ($lst->suite_no ? $lst->suite_no . ' - ' : '')
                . ($lst->street_number ? $lst->street_number . ' ' : '')
                . ($lst->street_name ?? '')
                . ($lst->street_type ? ' ' . $lst->street_type : '')
            );
        @endphp
        <div class="col-md-4 col-sm-6" style="margin-bottom:16px;">
            <a href="/listing/{{ $lst->slug }}" style="text-decoration:none;color:inherit;">
                <div style="border:1px solid #e2dbd2;border-radius:5px;overflow:hidden;background:#fff;box-shadow:0 1px 5px rgba(0,0,0,.06);">
                    @if($photo)
                    <div style="height:150px;background:url('{{ $photo }}') center/cover no-repeat;background-color:#f0ede8;"></div>
                    @else
                    <div style="height:150px;background:#f0ede8;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:12px;">No Photo</div>
                    @endif
                    <div style="padding:10px 12px;">
                        <div style="font-size:15px;font-weight:700;color:#2c6fad;">${{ number_format($lst->listprice_2) }}</div>
                        @if($addr)<div style="font-size:12px;font-weight:600;color:#333;margin-top:3px;">{{ $addr }}</div>@endif
                        <div style="font-size:12px;color:#555;margin-top:2px;">{{ $lst->type }}</div>
                        <div style="font-size:11px;color:#888;margin-top:3px;">
                            @if($lst->bedrooms)<span>{{ $lst->bedrooms }} bd</span>@endif
                            @if($lst->bathstotal) &nbsp;·&nbsp; <span>{{ $lst->bathstotal }} ba</span>@endif
                            @if($lst->livingarea_2) &nbsp;·&nbsp; <span>{{ number_format($lst->livingarea_2) }} sqft</span>@endif
                        </div>
                        @if($lst->subarea)<div style="font-size:11px;color:#aaa;margin-top:2px;">{{ $lst->subarea }}</div>@endif
                        @if(!empty($lst->distance_km))
                        <div style="font-size:11px;color:#888;margin-top:2px;">{{ round($lst->distance_km, 1) }} km away</div>
                        @endif
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    <div style="margin-top:10px;">
        {{ $listings->links() }}
    </div>
</section>
@else
<div style="background:#fafaf8;border:1px solid #e2dbd2;border-radius:5px;padding:18px 22px;font-size:14px;color:#888;margin-bottom:22px;">
    No active listings currently match this search.
    @if(!empty($loc['city_slug']))
    <a href="/search-listings/{{ $loc['city_slug'] }}" style="color:#2c6fad;margin-left:8px;">Browse all {{ $locationLabel }} listings →</a>
    @endif
</div>
@endif

{{-- ===================== RELATED LINKS ===================== --}}
@if(!empty($relatedBedLinks) || !empty($relatedTypeLinks) || !empty($lifestyleLinks) || !empty($nearbyLandmarks))
<section style="margin-bottom:28px;padding-top:18px;border-top:1px solid #eee;">
    <h2 style="font-size:16px;font-weight:700;color:#2c2c2c;margin-bottom:12px;">Related Searches</h2>
    <div style="display:flex;flex-wrap:wrap;gap:9px;font-size:13px;">
        @foreach(array_merge(
            $relatedBedLinks  ?? [],
            $relatedTypeLinks ?? [],
            $lifestyleLinks   ?? [],
            $nearbyLandmarks  ?? []
        ) as $lnk)
        <a href="{{ $lnk['url'] }}"
           style="background:#f2f0ec;border:1px solid #ddd;border-radius:4px;padding:6px 12px;color:#2c6fad;text-decoration:none;">
            {{ $lnk['label'] }}
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- ===================== FAQ ACCORDION ===================== --}}
@if(count($faqItems))
<section class="faq-section" style="margin-bottom:28px;padding-top:16px;border-top:1px solid #eee;">
    <h2 style="font-size:18px;font-weight:700;color:#2c2c2c;margin-bottom:14px;">Frequently Asked Questions</h2>
    @foreach($faqItems as $faq)
    <div class="faq-item" onclick="this.classList.toggle('open')">
        <div class="faq-question">{{ $faq['q'] }}<span class="faq-chevron">&#9660;</span></div>
        <div class="faq-answer"><p>{{ $faq['a'] }}</p></div>
    </div>
    @endforeach
</section>
@endif

@endif

{{-- ===================== INTERNAL LINKS FOOTER ===================== --}}
<div style="padding:14px 0;border-top:1px solid #eee;font-size:13px;color:#666;line-height:2.2;">
    @if(!empty($loc['city_slug']))
    <strong>{{ $locationLabel }} quick links:</strong>
    <a href="/search-listings/{{ $loc['city_slug'] }}" style="margin:0 8px;color:#2c6fad;">All Listings</a>
    <a href="/{{ $loc['city_slug'] }}-condos-for-sale" style="margin:0 8px;color:#2c6fad;">Condos Hub</a>
    <a href="/market-report/{{ $loc['city_slug'] }}" style="margin:0 8px;color:#2c6fad;">Market Reports</a>
    <a href="/market-stats/{{ $loc['city_slug'] }}" style="margin:0 8px;color:#2c6fad;">Market Stats</a>
    <a href="/neighbourhood/{{ $loc['city_slug'] }}/" style="margin:0 8px;color:#2c6fad;">Neighbourhoods</a>
    @if(!empty($bedsInt))
    @foreach([1,2,3,4] as $b)
    @if($b !== $bedsInt)
    <a href="/{{ $b }}-bedroom-condos-for-sale-{{ $location ?? $loc['city_slug'] }}" style="margin:0 8px;color:#2c6fad;">{{ $b }}-Bed Condos</a>
    @endif
    @endforeach
    @endif
    @endif
</div>

{{-- ===================== AGENT CTA ===================== --}}
@include('frontend.includes.hani_bubble')

@endsection
