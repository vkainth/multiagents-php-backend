@extends('frontend.layouts.default')
@php
$metaTitle    = "Top Realtor in Metro Vancouver & BC | RE/MAX Diamond Club | Hani & Les | BC Condos And Homes";
$metaDesc     = "Find the top realtor for your city or neighbourhood in BC. Les Twarog's RE/MAX Diamond Club team covers Metro Vancouver and surrounding cities — 30+ years experience, \$40M+ sold annually.";
$canonicalUrl = 'https://www.bccondosandhomes.com/top-realtor/';
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
        {"@type":"ListItem","position":2,"name":"Top Realtor in BC","item":"{{ $canonicalUrl }}"}
      ]
    },
    {
      "@type": "Person",
      "name": "Les Twarog",
      "jobTitle": "Real Estate Agent — RE/MAX Diamond Club",
      "url": "https://www.bccondosandhomes.com/top-realtor/",
      "image": "https://www.bccondosandhomes.com/frontend/images/teamagents/les-twarog-headshot.jpg",
      "telephone": "+16042608588",
      "email": "les@bccondosandhomes.com",
      "worksFor": {
        "@type": "Organization",
        "name": "RE/MAX Real Estate Services",
        "url": "https://www.remax.ca"
      },
      "alumniOf": "RE/MAX Diamond Club",
      "knowsAbout": ["Metro Vancouver Real Estate","Condo Sales","Luxury Homes","Investment Properties","BC Real Estate Market"],
      "areaServed": {
        "@type": "Place",
        "name": "Metro Vancouver, BC, Canada"
      }
    },
    {
      "@type": "RealEstateAgent",
      "name": "Hani & Les | BC Condos And Homes — Les Twarog RE/MAX Team",
      "url": "https://www.bccondosandhomes.com",
      "telephone": "+16042608588",
      "image": "https://www.bccondosandhomes.com/frontend/images/teamagents/les-twarog-headshot.jpg",
      "areaServed": {"@type":"Place","name":"Metro Vancouver, BC, Canada"},
      "description": "{{ e($metaDesc) }}",
      "award": "RE/MAX Diamond Club",
      "memberOf": {"@type":"Organization","name":"RE/MAX Real Estate Services"}
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
                <li style="color:#e0c870;">Top Realtor in BC</li>
            </ol>
        </nav>
        <div class="row" style="align-items:center;">
            <div class="col-md-8">
                <div style="display:inline-block;background:#e5b021;color:#111;font-size:11px;font-weight:700;padding:4px 10px;border-radius:3px;text-transform:uppercase;letter-spacing:.7px;margin-bottom:14px;">RE/MAX Diamond Club Team</div>
                <h1 style="font-size:36px;font-weight:800;color:#fff;margin:0 0 14px;line-height:1.2;">Top Realtor in Metro Vancouver &amp; BC</h1>
                <p style="font-size:16px;color:#b8cfe0;margin:0 0 24px;line-height:1.6;">30+ years of local expertise. $40M+ sold annually. 12 dedicated agents covering every city and neighbourhood across BC.</p>
                <div style="display:flex;flex-wrap:wrap;gap:12px;">
                    <button style="background:#e5b021;color:#111;border:none;border-radius:5px;padding:13px 24px;font-size:15px;font-weight:700;cursor:pointer;">Talk to an Agent Now</button>
                    <a href="tel:16042608588" style="background:transparent;color:#fff;border:2px solid rgba(255,255,255,.4);border-radius:5px;padding:13px 24px;font-size:15px;font-weight:600;text-decoration:none;">Call 604-260-8588</a>
                </div>
            </div>
            <div class="col-md-4 hidden-xs hidden-sm" style="text-align:right;">
                <img src="{{ asset('frontend/images/teamagents/les-twarog-headshot.jpg') }}" alt="Les Twarog — Top Realtor Metro Vancouver BC" style="width:180px;height:180px;object-fit:cover;border-radius:50%;border:4px solid #e5b021;box-shadow:0 8px 30px rgba(0,0,0,.4);">
                <div style="color:#e5b021;font-weight:700;font-size:14px;margin-top:10px;">Les Twarog</div>
                <div style="color:#aac4e0;font-size:12px;">Principal &amp; Team Lead</div>
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
                <div style="font-size:26px;font-weight:800;color:#111;">12</div>
                <div style="font-size:12px;font-weight:600;color:#333;text-transform:uppercase;">Dedicated Agents</div>
            </div>
            <div class="col-sm-3 col-xs-6" style="padding:8px;">
                <div style="font-size:26px;font-weight:800;color:#111;">#1</div>
                <div style="font-size:12px;font-weight:600;color:#333;text-transform:uppercase;">RE/MAX Diamond Club</div>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding-top:44px;padding-bottom:30px;">

    {{-- Cities Grid --}}
    <h2 style="font-size:24px;font-weight:700;color:#2c2c2c;margin-bottom:6px;">Find Your Top Realtor by City</h2>
    <p style="font-size:14px;color:#666;margin-bottom:24px;">Select your city to see local market data and connect with a specialist on our team.</p>
    <div class="row" style="margin-bottom:40px;">
        @foreach($cities as $city)
        @php $cSlug = App\Helpers\Helper::enslugPlace($city->place); @endphp
        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-bottom:18px;">
            <a href="/top-realtor/{{ $cSlug }}/"
               style="display:block;background:#fff;border:1px solid #e2dbd2;border-radius:7px;padding:16px 14px;text-decoration:none;transition:box-shadow .15s;"
               onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'"
               onmouseout="this.style.boxShadow='none'">
                <div style="font-size:15px;font-weight:700;color:#2c2c2c;margin-bottom:3px;">{{ $city->place }}</div>
                <div style="font-size:12px;color:#2c6fad;">Top Realtor in {{ $city->place }} &rsaquo;</div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Why Us --}}
    <div style="background:#f9f7f4;border-radius:10px;padding:32px 26px;margin-bottom:40px;">
        <h2 style="font-size:22px;font-weight:700;color:#2c2c2c;margin-bottom:16px;">Why Hani & Les | BC Condos And Homes is BC's Top Real Estate Team</h2>
        <div class="row">
            <div class="col-md-4 col-sm-6" style="margin-bottom:18px;">
                <div style="font-weight:700;color:#2c2c2c;margin-bottom:5px;">🏆 RE/MAX Diamond Club</div>
                <p style="font-size:13px;color:#555;margin:0;line-height:1.6;">Awarded to fewer than 1% of all RE/MAX agents nationally — consistent, top-tier production every year.</p>
            </div>
            <div class="col-md-4 col-sm-6" style="margin-bottom:18px;">
                <div style="font-weight:700;color:#2c2c2c;margin-bottom:5px;">📊 Data-Driven Pricing</div>
                <p style="font-size:13px;color:#555;margin:0;line-height:1.6;">Real-time MLS® analytics power every pricing decision — sellers consistently achieve above-market results.</p>
            </div>
            <div class="col-md-4 col-sm-6" style="margin-bottom:18px;">
                <div style="font-weight:700;color:#2c2c2c;margin-bottom:5px;">🤝 12 Agents, 1 Team Fee</div>
                <p style="font-size:13px;color:#555;margin:0;line-height:1.6;">12 area specialists working for you without extra cost. More agents means more buyers and faster sales.</p>
            </div>
            <div class="col-md-4 col-sm-6" style="margin-bottom:18px;">
                <div style="font-weight:700;color:#2c2c2c;margin-bottom:5px;">🌐 BC's Largest Realtor Website</div>
                <p style="font-size:13px;color:#555;margin:0;line-height:1.6;">30,000+ registered buyers. Your property gets maximum exposure the moment it lists.</p>
            </div>
            <div class="col-md-4 col-sm-6" style="margin-bottom:18px;">
                <div style="font-weight:700;color:#2c2c2c;margin-bottom:5px;">📷 Professional Marketing</div>
                <p style="font-size:13px;color:#555;margin:0;line-height:1.6;">HDR photography, 3D Matterport tours, targeted digital campaigns, and weekly performance reports.</p>
            </div>
            <div class="col-md-4 col-sm-6" style="margin-bottom:18px;">
                <div style="font-weight:700;color:#2c2c2c;margin-bottom:5px;">⚡ 30+ Years Local Expertise</div>
                <p style="font-size:13px;color:#555;margin:0;line-height:1.6;">Les Twarog has navigated every BC market cycle since the early 1990s — steady, seasoned guidance.</p>
            </div>
        </div>
    </div>

    {{-- Testimonials --}}
    <div style="margin-bottom:40px;">
        <h2 style="font-size:22px;font-weight:700;color:#2c2c2c;margin-bottom:20px;">What Our Clients Say</h2>
        <div class="row">
            <div class="col-md-4" style="margin-bottom:20px;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:8px;padding:22px;height:100%;">
                    <div style="color:#e5b021;font-size:16px;margin-bottom:8px;">★★★★★</div>
                    <p style="font-size:14px;color:#444;line-height:1.65;margin:0 0 12px;">"Les and his team sold our Vancouver condo in 11 days — $22,000 over asking. Their marketing was exceptional and we were kept in the loop the whole time."</p>
                    <div style="font-size:13px;font-weight:700;color:#2c2c2c;">Sandra M.</div>
                    <div style="font-size:12px;color:#888;">Vancouver, BC</div>
                </div>
            </div>
            <div class="col-md-4" style="margin-bottom:20px;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:8px;padding:22px;height:100%;">
                    <div style="color:#e5b021;font-size:16px;margin-bottom:8px;">★★★★★</div>
                    <p style="font-size:14px;color:#444;line-height:1.65;margin:0 0 12px;">"As first-time buyers in Burnaby we were nervous, but the team made it effortless. They knew every building and found us exactly what we wanted, below budget."</p>
                    <div style="font-size:13px;font-weight:700;color:#2c2c2c;">James &amp; Priya K.</div>
                    <div style="font-size:12px;color:#888;">Burnaby, BC</div>
                </div>
            </div>
            <div class="col-md-4" style="margin-bottom:20px;">
                <div style="background:#fff;border:1px solid #e2dbd2;border-radius:8px;padding:22px;height:100%;">
                    <div style="color:#e5b021;font-size:16px;margin-bottom:8px;">★★★★★</div>
                    <p style="font-size:14px;color:#444;line-height:1.65;margin:0 0 12px;">"I've used other big-name agents before, but no one matched the level of data and market knowledge Les brought to pricing our Richmond townhouse. Sold in a week."</p>
                    <div style="font-size:13px;font-weight:700;color:#2c2c2c;">David C.</div>
                    <div style="font-size:12px;color:#888;">Richmond, BC</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Home Eval CTA --}}
    <div style="background:#1a2a3a;border-radius:10px;padding:36px 28px;margin-bottom:36px;">
        <div class="row" style="align-items:center;">
            <div class="col-md-6" style="margin-bottom:20px;">
                <h2 style="font-size:22px;font-weight:700;color:#fff;margin:0 0 10px;">What Is Your Home Worth?</h2>
                <p style="font-size:14px;color:#aac4e0;margin:0 0 16px;line-height:1.6;">Get a free, no-obligation home evaluation from BC's top realtors. We analyze recent sales, current market conditions, and your property's unique features.</p>
                <ul style="list-style:none;padding:0;margin:0;">
                    <li style="font-size:13px;color:#b8cfe0;margin-bottom:6px;">✓ Free — no commitment required</li>
                    <li style="font-size:13px;color:#b8cfe0;margin-bottom:6px;">✓ Based on real MLS® sold data</li>
                    <li style="font-size:13px;color:#b8cfe0;margin-bottom:6px;">✓ Response within 24 hours</li>
                </ul>
            </div>
            <div class="col-md-6">
                <div id="hub-home-eval" style="background:#fff;border-radius:8px;padding:4px;"></div>
                <script src="{{ asset('widget/home-evaluation.js') }}"
                    data-placement="inline"
                    data-target="#hub-home-eval">
                </script>
            </div>
        </div>
    </div>

    {{-- About Les --}}
    <div style="background:#fafaf8;border:1px solid #e2dbd2;border-radius:8px;padding:24px 26px;margin-bottom:20px;">
        <h2 style="font-size:19px;font-weight:700;color:#2c2c2c;margin:0 0 10px;">About Les Twarog — Principal Realtor</h2>
        <p style="font-size:14px;color:#555;line-height:1.75;margin:0 0 10px;">Les Twarog is one of BC's most decorated real estate professionals, with over 30 years of experience and consistent RE/MAX Diamond Club status — awarded to fewer than 1% of all RE/MAX agents. Based in Metro Vancouver, his team specializes in condos, luxury homes, and investment properties across BC.</p>
        <p style="font-size:14px;color:#555;line-height:1.75;margin:0;">Les's approach combines superior market analytics, strategic pricing, and broad digital exposure through bccondosandhomes.com, BC's largest realtor site. His team's 60+ transactions annually represent roughly 17× the industry average.</p>
        <div style="margin-top:14px;">
            <a href="/about-us.html" style="color:#2c6fad;font-size:13px;font-weight:600;">Read the full story &rsaquo;</a>
        </div>
    </div>

</div>

<div class="container" style="padding-bottom:20px;">
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => 'Metro Vancouver',
        'stripHeading'    => 'Find Listings While You Search for a Realtor',
        'stripSubtext'    => 'Get instant email alerts for new MLS® listings in Metro Vancouver — updated daily.',
        'stripSearchName' => 'Metro Vancouver Active Listings',
        'stripSearchData' => json_encode(['listing_status' => 'Active']),
        'stripModalId'    => 'trhAlert',
    ])
</div>

@include('frontend.includes.footer_links')
@include('frontend.includes.footer')

<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
    data-city="Vancouver"
    data-market-type="sellers"
    data-buyers="500"
></script>

@endsection
