@extends('frontend.layouts.default')
@php
$placeLabel   = $subarea ? "{$subarea}, {$city}" : "{$city}";
$metaTitle    = "Top Realtor in {$placeLabel}, BC | Hani Faraj — RE/MAX Diamond Club | BC Condos And Homes";
$metaDesc     = "Hani Faraj — RE/MAX Senior Partner & Diamond Club member — is the top-rated realtor in {$placeLabel}, BC. 30+ years of local expertise, \$40M+ sold annually, {$teamCount}-agent team. Free home evaluation. Call 604-229-3342.";
$canonicalUrl = 'https://www.bccondosandhomes.com/top-realtor/' . $citySlug . ($subareaSlug ? '/'.$subareaSlug : '') . '/';

$mktType = 'balanced';
$condLabel = $condition['label'] ?? null;
if ($condLabel === "Strong Seller's Market")   $mktType = 'strong-sellers';
elseif ($condLabel === "Seller's Market")       $mktType = 'sellers';
elseif ($condLabel === "Buyer's Market")        $mktType = 'buyers';
elseif ($condLabel === "Balanced Market")       $mktType = 'balanced';
$buyers = (int)(round(max(50, ($condition['current_active'] ?? 0) * 15 + ($condition['sold_30d'] ?? 0) * 30) / 10) * 10);

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
        {"@type":"ListItem","position":2,"name":"Top Realtor","item":"https://www.bccondosandhomes.com/top-realtor/{{ $citySlug }}/"},
        @if($subarea)
        {"@type":"ListItem","position":3,"name":"{{ e($city) }}","item":"https://www.bccondosandhomes.com/top-realtor/{{ $citySlug }}/"},
        {"@type":"ListItem","position":4,"name":"{{ e($subarea) }}","item":"{{ $canonicalUrl }}"}
        @else
        {"@type":"ListItem","position":3,"name":"{{ e($city) }}","item":"{{ $canonicalUrl }}"}
        @endif
      ]
    },
    {
      "@type": "Person",
      "name": "Hani Faraj",
      "jobTitle": "Senior Partner / Realtor — RE/MAX Diamond Club",
      "url": "{{ $canonicalUrl }}",
      "image": "https://www.bccondosandhomes.com/frontend/images/teamagents/hani_faraj.jpg",
      "telephone": "+16042293342",
      "email": "hani@bccondosandhomes.com",
      "worksFor": {
        "@type": "Organization",
        "name": "RE/MAX Crest Realty",
        "url": "https://www.remax.ca"
      },
      "award": "RE/MAX Diamond Club — Top 100 RE/MAX Western Canada",
      "knowsAbout": ["{{ e($placeLabel) }} Real Estate","Condo Sales","Luxury Homes","Investment Properties","BC Real Estate Market","RE/MAX Diamond Club"],
      "areaServed": {
        "@type": "Place",
        "name": "{{ e($placeLabel) }}, BC, Canada"
      }
    },
    {
      "@type": "RealEstateAgent",
      "name": "Hani Faraj — RE/MAX Crest Realty | BC Condos And Homes",
      "url": "https://www.bccondosandhomes.com",
      "telephone": "+16042293342",
      "image": "https://www.bccondosandhomes.com/frontend/images/teamagents/hani_faraj.jpg",
      "areaServed": {
        "@type": "Place",
        "name": "{{ e($placeLabel) }}, BC, Canada"
      },
      "description": "{{ e($metaDesc) }}",
      "award": "RE/MAX Diamond Club — Top 100 RE/MAX Western Canada",
      "memberOf": {
        "@type": "Organization",
        "name": "RE/MAX Crest Realty"
      },
      "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "Real Estate Services in {{ e($placeLabel) }}, BC",
        "itemListElement": [
          {"@type":"Offer","itemOffered":{"@type":"Service","name":"Home Selling — {{ e($placeLabel) }}"}},
          {"@type":"Offer","itemOffered":{"@type":"Service","name":"Home Buying — {{ e($placeLabel) }}"}},
          {"@type":"Offer","itemOffered":{"@type":"Service","name":"Free Home Evaluation"}}
        ]
      }
    },
    {
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "Who is the top realtor in {{ e($placeLabel) }}?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Hani Faraj of RE/MAX Crest Realty is consistently ranked among the top realtors in {{ e($placeLabel) }}, BC. As a RE/MAX Diamond Club member and Senior Partner at BC Condos And Homes, Hani brings 30+ years of BC real estate experience, over $40M in annual sales, and a {{ $teamCount }}-agent team to every transaction."
          }
        },
        {
          "@type": "Question",
          "name": "How much does it cost to hire a top realtor in {{ e($placeLabel) }}?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "A free home evaluation with our team costs nothing. Standard commission rates apply on transactions, but our team's track record consistently results in higher net proceeds for sellers and better pricing outcomes for buyers."
          }
        },
        {
          "@type": "Question",
          "name": "What is the current real estate market like in {{ e($placeLabel) }}?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "{{ $condLabel ? 'The '.$placeLabel.' real estate market is currently a '.$condLabel.'.' : 'Contact our team for the latest '.$placeLabel.' market update.' }}{{ ($condition['avg_sold_30d'] ?? 0) > 0 ? ' Average sold price over the last 30 days: $'.number_format($condition['avg_sold_30d']).'. Active listings: '.number_format($condition['current_active'] ?? 0).'.' : '' }}"
          }
        }
      ]
    }
  ]
}
</script>
@endsection
@section('content')
@include('frontend.includes.header')

{{-- Hero --}}
<div class="page-main" style="background:linear-gradient(135deg,#1a2a3a 0%,#2c4a6a 100%);padding:60px 0 50px;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:16px;">
            <ol style="list-style:none;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:6px;font-size:13px;">
                <li><a href="/" style="color:#aac4e0;text-decoration:none;">Home</a> <span style="color:#5a8ab0;">/</span></li>
                @if($subarea)
                <li><a href="/top-realtor/{{ $citySlug }}/" style="color:#aac4e0;text-decoration:none;">Top Realtor {{ $city }}</a> <span style="color:#5a8ab0;">/</span></li>
                <li style="color:#e0c870;">{{ $subarea }}</li>
                @else
                <li style="color:#e0c870;">Top Realtor {{ $city }}</li>
                @endif
            </ol>
        </nav>
        <div class="row" style="align-items:center;">
            <div class="col-md-8">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
                    <span style="display:inline-block;background:#cc0000;color:#fff;font-size:11px;font-weight:800;padding:4px 12px;border-radius:3px;text-transform:uppercase;letter-spacing:.8px;">RE/MAX</span>
                    <span style="display:inline-block;background:rgba(255,255,255,.12);color:#e5b021;font-size:11px;font-weight:700;padding:4px 12px;border-radius:3px;text-transform:uppercase;letter-spacing:.7px;">Diamond Club &mdash; Top 1% in BC</span>
                </div>
                <h1 style="font-size:36px;font-weight:800;color:#fff;margin:0 0 14px;line-height:1.2;">Top Realtor in {{ $placeLabel }}, BC</h1>
                <p style="font-size:16px;color:#b8cfe0;margin:0 0 8px;line-height:1.6;">Work directly with <strong style="color:#fff;">Hani Faraj</strong> — RE/MAX Senior Partner with 30+ years of BC real estate expertise, $40M+ sold annually, and a {{ $teamCount }}-agent team behind every listing.</p>
                <p style="font-size:13px;color:#7fa8c8;margin:0 0 22px;">RE/MAX Crest Realty &bull; Diamond Club &bull; Top 100 RE/MAX Western Canada</p>
                <div style="display:flex;flex-wrap:wrap;gap:12px;">
                    <button style="background:#e5b021;color:#111;border:none;border-radius:5px;padding:13px 24px;font-size:15px;font-weight:700;cursor:pointer;">Talk to Hani Now</button>
                    <a href="tel:6042293342" style="background:transparent;color:#fff;border:2px solid rgba(255,255,255,.4);border-radius:5px;padding:13px 24px;font-size:15px;font-weight:600;text-decoration:none;">Call 604-229-3342</a>
                </div>
            </div>
            <div class="col-md-4 hidden-xs hidden-sm" style="text-align:center;">
                <img src="{{ asset('frontend/images/teamagents/hani_faraj.jpg') }}" alt="Hani Faraj — RE/MAX Senior Partner {{ $city }}" style="width:170px;height:170px;object-fit:cover;object-position:center 15%;border-radius:50%;border:4px solid #e5b021;box-shadow:0 8px 30px rgba(0,0,0,.45);display:block;margin:0 auto;">
                <div style="color:#e5b021;font-weight:700;font-size:15px;margin-top:12px;">Hani Faraj</div>
                <div style="color:#fff;font-size:12px;font-weight:600;margin-top:3px;">Senior Partner / Realtor</div>
                <div style="color:#7fa8c8;font-size:11px;margin-top:2px;">RE/MAX Crest Realty</div>
            </div>
        </div>
    </div>
</div>

{{-- Trust Stats Bar --}}
<div style="background:#e5b021;padding:18px 0;">
    <div class="container">
        <div class="row" style="text-align:center;">
            <div class="col-sm-3 col-xs-6" style="padding:8px;">
                <div style="font-size:26px;font-weight:800;color:#111;">30+</div>
                <div style="font-size:12px;font-weight:600;color:#333;text-transform:uppercase;">Years Experience</div>
            </div>
            <div class="col-sm-3 col-xs-6" style="padding:8px;">
                <div style="font-size:26px;font-weight:800;color:#111;">$40M+</div>
                <div style="font-size:12px;font-weight:600;color:#333;text-transform:uppercase;">Sold Annually</div>
            </div>
            <div class="col-sm-3 col-xs-6" style="padding:8px;">
                <div style="font-size:26px;font-weight:800;color:#111;">{{ $teamCount }}</div>
                <div style="font-size:12px;font-weight:600;color:#333;text-transform:uppercase;">Dedicated Agents</div>
            </div>
            <div class="col-sm-3 col-xs-6" style="padding:8px;">
                <div style="font-size:26px;font-weight:800;color:#111;">#1</div>
                <div style="font-size:12px;font-weight:600;color:#333;text-transform:uppercase;">RE/MAX Diamond Club</div>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding-top:40px;padding-bottom:20px;">

    {{-- Market snapshot --}}
    @if($condLabel)
    <div style="background:#f9f7f4;border:1px solid #e2dbd2;border-radius:8px;padding:22px 24px;margin-bottom:32px;">
        <div style="font-size:13px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.7px;margin-bottom:8px;">{{ $placeLabel }} Market Right Now</div>
        <div class="row" style="align-items:center;">
            <div class="col-sm-4">
                <div style="font-size:22px;font-weight:700;color:{{ $condition['color'] }};">{{ $condLabel }}</div>
                <div style="font-size:13px;color:#666;margin-top:4px;">Absorption rate: {{ $condition['absorption_rate'] }}%</div>
            </div>
            <div class="col-sm-8">
                <div class="row" style="margin-top:10px;">
                    <div class="col-xs-4" style="text-align:center;">
                        <div style="font-size:19px;font-weight:700;color:#333;">{{ number_format($condition['current_active'] ?? 0) }}</div>
                        <div style="font-size:11px;color:#888;">Active Listings</div>
                    </div>
                    <div class="col-xs-4" style="text-align:center;">
                        <div style="font-size:19px;font-weight:700;color:#333;">{{ number_format($condition['sold_30d'] ?? 0) }}</div>
                        <div style="font-size:11px;color:#888;">Sold (30d)</div>
                    </div>
                    <div class="col-xs-4" style="text-align:center;">
                        <div style="font-size:17px;font-weight:700;color:#333;">{{ ($condition['avg_sold_30d'] ?? 0) > 0 ? '$'.number_format($condition['avg_sold_30d']) : '—' }}</div>
                        <div style="font-size:11px;color:#888;">Avg Price (30d)</div>
                    </div>
                </div>
            </div>
        </div>
        <div style="margin-top:12px;font-size:13px;color:#555;">
            <a href="/market-stats/{{ $citySlug }}{{ $subareaSlug ? '/'.$subareaSlug : '' }}" style="color:#2c6fad;">Full {{ $placeLabel }} market stats &rsaquo;</a>
            @if(!$subarea)&nbsp;&nbsp;<a href="/market-report/{{ $citySlug }}" style="color:#2c6fad;">Monthly market reports &rsaquo;</a>@endif
        </div>
    </div>
    @endif

    {{-- Insight bar widget --}}
    <script src="https://admin.bccondosandhomes.com/widget/insight-bar.js"
        data-placement="main"
        data-neighbourhood="{{ $subarea }}"
        data-city="{{ $city }}"
        data-market-type="{{ $mktType }}"
        data-avg-price="{{ ($condition['avg_sold_30d'] ?? 0) > 0 ? '$'.number_format($condition['avg_sold_30d']) : '' }}"
        data-avg-dom="{{ ($condition['avg_dom'] ?? 0) > 0 ? $condition['avg_dom'].'d' : '' }}"
        data-active-listings="{{ $condition['current_active'] ?? 0 }}"
        data-absorption-rate="{{ ($condition['absorption_rate'] ?? 0) > 0 ? $condition['absorption_rate'].'%' : '' }}"
        data-sold-30d="{{ $condition['sold_30d'] ?? 0 }}"
        data-buyers="{{ number_format($buyers) }}"
    ></script>

    {{-- Why choose us --}}
    <div style="margin-bottom:36px;">
        <h2 style="font-size:24px;font-weight:700;color:#2c2c2c;margin-bottom:6px;">Why Hani & Les | BC Condos And Homes is {{ $placeLabel }}'s Top Real Estate Team</h2>
        <p style="font-size:14px;color:#666;margin-bottom:20px;">We combine deep local market knowledge with a {{ $teamCount }}-agent team, so you get the attention of a boutique agent and the resources of a full brokerage.</p>
        <div class="row">
            <div class="col-md-4 col-sm-6" style="margin-bottom:20px;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:7px;padding:20px;height:100%;">
                    <div style="font-size:28px;margin-bottom:8px;">🏆</div>
                    <h3 style="font-size:16px;font-weight:700;color:#2c2c2c;margin:0 0 8px;">RE/MAX Diamond Club</h3>
                    <p style="font-size:13px;color:#555;margin:0;line-height:1.6;">Awarded to fewer than 1% of all RE/MAX agents nationally — a testament to consistent, top-tier production year after year.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6" style="margin-bottom:20px;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:7px;padding:20px;height:100%;">
                    <div style="font-size:28px;margin-bottom:8px;">📊</div>
                    <h3 style="font-size:16px;font-weight:700;color:#2c2c2c;margin:0 0 8px;">Data-Driven Pricing</h3>
                    <p style="font-size:13px;color:#555;margin:0;line-height:1.6;">Real-time MLS® analytics power every pricing decision. Sellers consistently achieve above-market results with our team.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6" style="margin-bottom:20px;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:7px;padding:20px;height:100%;">
                    <div style="font-size:28px;margin-bottom:8px;">🤝</div>
                    <h3 style="font-size:16px;font-weight:700;color:#2c2c2c;margin:0 0 8px;">{{ $teamCount }} Agents, 1 Team Fee</h3>
                    <p style="font-size:13px;color:#555;margin:0;line-height:1.6;">Your listing benefits from {{ $teamCount }} area specialists without extra cost. More agents means more buyers, more showings, faster sales.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6" style="margin-bottom:20px;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:7px;padding:20px;height:100%;">
                    <div style="font-size:28px;margin-bottom:8px;">🌐</div>
                    <h3 style="font-size:16px;font-weight:700;color:#2c2c2c;margin:0 0 8px;">BC's Largest Realtor Website</h3>
                    <p style="font-size:13px;color:#555;margin:0;line-height:1.6;">Massive online reach with 30,000+ registered buyers. Your property gets maximum exposure the moment it lists.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6" style="margin-bottom:20px;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:7px;padding:20px;height:100%;">
                    <div style="font-size:28px;margin-bottom:8px;">📷</div>
                    <h3 style="font-size:16px;font-weight:700;color:#2c2c2c;margin:0 0 8px;">Professional Marketing</h3>
                    <p style="font-size:13px;color:#555;margin:0;line-height:1.6;">HDR photography, 3D Matterport tours, targeted digital campaigns, and weekly performance reports for every listing.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6" style="margin-bottom:20px;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:7px;padding:20px;height:100%;">
                    <div style="font-size:28px;margin-bottom:8px;">⚡</div>
                    <h3 style="font-size:16px;font-weight:700;color:#2c2c2c;margin:0 0 8px;">30+ Years Local Expertise</h3>
                    <p style="font-size:13px;color:#555;margin:0;line-height:1.6;">Hani Faraj has navigated every BC market cycle since the early 1990s — providing clients with steady, seasoned RE/MAX-backed guidance.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Team grid --}}
    <div style="margin-bottom:40px;">
        <h2 style="font-size:22px;font-weight:700;color:#2c2c2c;margin-bottom:6px;">Meet the Team</h2>
        <p style="font-size:14px;color:#666;margin-bottom:20px;">{{ $teamCount }} dedicated {{ $placeLabel }} real estate specialists working together for you.</p>
        <div class="row">
            @foreach($team as $agent)
            <div class="col-md-3 col-sm-4 col-xs-6" style="margin-bottom:20px;text-align:center;">
                @php $agentName = trim(($agent->first ?? '') . ' ' . ($agent->last ?? '')); @endphp
                <img src="{{ $agent->profile_image }}" alt="{{ $agentName }} — Realtor {{ $city }}" style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #e5b021;margin-bottom:8px;">
                <div style="font-size:13px;font-weight:700;color:#2c2c2c;">{{ $agentName }}</div>
                <div style="font-size:11px;color:#888;">Real Estate Specialist</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Testimonials --}}
    <div style="margin-bottom:40px;">
        <h2 style="font-size:22px;font-weight:700;color:#2c2c2c;margin-bottom:20px;">What Our Clients Say</h2>
        <div class="row">
            <div class="col-md-4" style="margin-bottom:20px;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:8px;padding:22px;height:100%;">
                    <div style="color:#e5b021;font-size:16px;margin-bottom:8px;">★★★★★</div>
                    <p style="font-size:14px;color:#444;line-height:1.65;margin:0 0 12px;">"Hani and his team sold our condo in {{ $city }} in 11 days — $22,000 over asking. Their marketing was exceptional and we were kept in the loop the entire time."</p>
                    <div style="font-size:13px;font-weight:700;color:#2c2c2c;">Sandra M.</div>
                    <div style="font-size:12px;color:#888;">{{ $city }}, BC</div>
                </div>
            </div>
            <div class="col-md-4" style="margin-bottom:20px;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:8px;padding:22px;height:100%;">
                    <div style="color:#e5b021;font-size:16px;margin-bottom:8px;">★★★★★</div>
                    <p style="font-size:14px;color:#444;line-height:1.65;margin:0 0 12px;">"As first-time buyers we were nervous, but the team made it effortless. They knew every building and found us exactly what we wanted, below budget."</p>
                    <div style="font-size:13px;font-weight:700;color:#2c2c2c;">James &amp; Priya K.</div>
                    <div style="font-size:12px;color:#888;">{{ $city }}, BC</div>
                </div>
            </div>
            <div class="col-md-4" style="margin-bottom:20px;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:8px;padding:22px;height:100%;">
                    <div style="color:#e5b021;font-size:16px;margin-bottom:8px;">★★★★★</div>
                    <p style="font-size:14px;color:#444;line-height:1.65;margin:0 0 12px;">"I've used other big-name agents before, but no one matched the level of data and market knowledge Les brought to pricing my home. Sold in a week."</p>
                    <div style="font-size:13px;font-weight:700;color:#2c2c2c;">David C.</div>
                    <div style="font-size:12px;color:#888;">{{ $city }}, BC</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Home Evaluation CTA --}}
    <div style="background:#1a2a3a;border-radius:10px;padding:36px 28px;margin-bottom:36px;">
        <div class="row" style="align-items:center;">
            <div class="col-md-6" style="margin-bottom:20px;">
                <h2 style="font-size:22px;font-weight:700;color:#fff;margin:0 0 10px;">What Is Your {{ $placeLabel }} Home Worth?</h2>
                <p style="font-size:14px;color:#aac4e0;margin:0 0 16px;line-height:1.6;">Get a free, no-obligation home evaluation from the top realtors in {{ $placeLabel }}. We'll analyze recent sales, current market conditions, and your property's unique features.</p>
                <ul style="list-style:none;padding:0;margin:0;">
                    <li style="font-size:13px;color:#b8cfe0;margin-bottom:6px;">✓ Free — no commitment required</li>
                    <li style="font-size:13px;color:#b8cfe0;margin-bottom:6px;">✓ Based on real MLS® sold data</li>
                    <li style="font-size:13px;color:#b8cfe0;margin-bottom:6px;">✓ Response within 24 hours</li>
                </ul>
            </div>
            <div class="col-md-6">
                <div id="tr-home-eval" style="background:#fff;border-radius:8px;padding:4px;"></div>
                <script src="{{ asset('widget/home-evaluation.js') }}"
                    data-placement="inline"
                    data-target="#tr-home-eval"
                    data-city="{{ $city }}"
                    data-neighbourhood="{{ $subarea }}">
                </script>
            </div>
        </div>
    </div>

    {{-- Other areas links (SEO interlinking) --}}
    @if($subareas && count($subareas))
    <div style="margin-bottom:36px;">
        <h3 style="font-size:17px;font-weight:700;color:#2c2c2c;margin-bottom:12px;">Top Realtor in Other {{ $city }} Neighbourhoods</h3>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @foreach($subareas->take(16) as $sa)
            <a href="/top-realtor/{{ $citySlug }}/{{ App\Helpers\Helper::enslugPlace($sa->place) }}/"
               style="background:#f5f1eb;border:1px solid #e2dbd2;border-radius:4px;padding:5px 12px;font-size:13px;color:#2c6fad;text-decoration:none;">{{ $sa->place }}</a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Meet Your Partners --}}
    <div style="margin-bottom:20px;">
        <h2 style="font-size:22px;font-weight:700;color:#2c2c2c;margin-bottom:20px;">Meet Your Partners</h2>
        <div class="row">
            {{-- Hani Faraj --}}
            <div class="col-md-6" style="margin-bottom:24px;">
                <div style="background:#fafaf8;border:1px solid #e2dbd2;border-radius:8px;padding:26px;height:100%;">
                    <div style="display:flex;align-items:center;gap:18px;margin-bottom:16px;">
                        <img src="{{ asset('frontend/images/teamagents/hani_faraj.jpg') }}" alt="Hani Faraj — RE/MAX Senior Partner" style="width:80px;height:80px;border-radius:50%;object-fit:cover;object-position:center 15%;border:3px solid #e5b021;flex-shrink:0;">
                        <div>
                            <div style="font-size:17px;font-weight:700;color:#2c2c2c;">Hani Faraj</div>
                            <div style="font-size:13px;color:#c0392b;font-weight:600;">Senior Partner / Realtor</div>
                            <div style="font-size:12px;color:#666;">RE/MAX Crest Realty</div>
                        </div>
                    </div>
                    <p style="font-size:13px;color:#555;line-height:1.75;margin:0 0 12px;">Hani founded Vancouver House Finders in 2014, bringing with him 6+ years of experience across mortgage lending, marketing, and project development — giving clients a rare full-picture perspective on every transaction. Today he leads the BC Condos And Homes team as RE/MAX Diamond Club members for 3 consecutive years and consistently ranked in the Top 100 Teams in Western Canada.</p>
                    <p style="font-size:13px;color:#555;line-height:1.75;margin:0 0 14px;">Hani lives with his family in Yaletown and is committed to education, transparency, and a simple promise: he won't rest until your home is sold for the highest price — or you've bought your dream home for the best price, under the right conditions.</p>
                    <div style="font-size:12px;color:#777;line-height:1.8;">
                        <span style="font-weight:600;color:#444;">Awards &amp; Recognition:</span><br>
                        🏅 RE/MAX Diamond Club — 3 consecutive years<br>
                        🏅 Top 100 RE/MAX Teams, Western Canada<br>
                        🏅 Medallion Club Real Estate Team 2020 &amp; 2021<br>
                        🏅 Top 2% Real Estate Team 2018 &amp; 2019<br>
                        🏅 Top 1% Real Estate Team 2017<br>
                        🏅 Master Club — Sutton West Coast Realty (2 years)
                    </div>
                </div>
            </div>
            {{-- Les Twarog --}}
            <div class="col-md-6" style="margin-bottom:24px;">
                <div style="background:#fafaf8;border:1px solid #e2dbd2;border-radius:8px;padding:26px;height:100%;">
                    <div style="display:flex;align-items:center;gap:18px;margin-bottom:16px;">
                        <img src="{{ asset('frontend/images/teamagents/les-twarog-headshot.jpg') }}" alt="Les Twarog — RE/MAX Diamond Club Principal" style="width:80px;height:80px;border-radius:50%;object-fit:cover;object-position:top center;border:3px solid #e5b021;flex-shrink:0;">
                        <div>
                            <div style="font-size:17px;font-weight:700;color:#2c2c2c;">Les Twarog</div>
                            <div style="font-size:13px;color:#c0392b;font-weight:600;">Principal &amp; Team Lead</div>
                            <div style="font-size:12px;color:#666;">RE/MAX Crest Realty</div>
                        </div>
                    </div>
                    <p style="font-size:13px;color:#555;line-height:1.75;margin:0 0 12px;">Les Twarog is one of BC's most decorated real estate professionals, with over 30 years of experience and consistent RE/MAX Diamond Club status — awarded to fewer than 1% of all RE/MAX agents nationally. Based in Metro Vancouver, he specialises in condos, luxury homes, and investment properties across {{ $city }} and surrounding communities.</p>
                    <p style="font-size:13px;color:#555;line-height:1.75;margin:0 0 14px;">Les's approach combines superior market analytics, strategic pricing, and broad digital exposure through bccondosandhomes.com — BC's largest realtor website. His team's 60+ transactions annually represent roughly 17× the industry average.</p>
                    <div style="font-size:12px;color:#777;line-height:1.8;">
                        <span style="font-weight:600;color:#444;">Awards &amp; Recognition:</span><br>
                        🏅 RE/MAX Diamond Club — career achievement<br>
                        🏅 Top 100 RE/MAX Teams, Western Canada<br>
                        🏅 30+ years BC real estate excellence<br>
                        🏅 60+ transactions/year (~17× industry avg)
                    </div>
                    <div style="margin-top:14px;">
                        <a href="/about-us.html" style="color:#2c6fad;font-size:13px;font-weight:600;">Read the full story &rsaquo;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="container" style="padding-bottom:20px;">
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => $placeLabel,
        'stripHeading'    => 'Get New Listing Alerts in ' . $placeLabel,
        'stripSubtext'    => 'While you search for the right realtor, never miss a new listing — get instant email alerts for ' . $placeLabel . '.',
        'stripSearchName' => $placeLabel . ' Listings',
        'stripSearchData' => json_encode(array_filter(['cities' => $city ?: null, 'subareas' => $subarea ?: null, 'listing_status' => 'Active'])),
        'stripCity'       => $city ?: '',
        'stripModalId'    => 'trAlert_' . ($citySlug ?? 'van'),
    ])
</div>

@include('frontend.includes.footer_links')
@include('frontend.includes.footer')

<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
    data-neighbourhood="{{ $subarea }}"
    data-city="{{ $city }}"
    data-market-type="{{ $mktType }}"
    data-avg-price="{{ ($condition['avg_sold_30d'] ?? 0) > 0 ? '$'.number_format($condition['avg_sold_30d']) : '' }}"
    data-avg-dom="{{ ($condition['avg_dom'] ?? 0) > 0 ? $condition['avg_dom'].'d' : '' }}"
    data-active-listings="{{ $condition['current_active'] ?? 0 }}"
    data-absorption-rate="{{ ($condition['absorption_rate'] ?? 0) > 0 ? $condition['absorption_rate'].'%' : '' }}"
    data-sold-30d="{{ $condition['sold_30d'] ?? 0 }}"
    data-buyers="{{ number_format($buyers) }}"
></script>

@endsection

@push('after-styles')
<style>
.top-realtor h1, .top-realtor h2, .top-realtor h3 { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
</style>
@endpush
