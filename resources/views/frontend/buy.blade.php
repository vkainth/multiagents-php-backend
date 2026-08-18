@extends('frontend.layouts.default')
@section('title')Sell Your Home with BC's Top-Ranked RE/MAX Team | Hani & Les | BC Condos And Homes@endsection
@section('meta_description')Sell with BC's #1 ranked RE/MAX Diamond Club Team. $25K interest-free bridge loan, 3D marketing, and 30+ years of expertise. Get your free valuation today.@endsection
@section('meta')
    <meta property="og:title" content="Sell Your Home with BC's Top-Ranked RE/MAX Team | Hani &amp; Les | BC Condos And Homes">
    <meta property="og:description" content="Sell with BC's #1 ranked RE/MAX Diamond Club Team. $25K interest-free bridge loan, 3D marketing, and 30+ years of expertise. Get your free valuation today.">
    <meta property="og:url" content="https://www.bccondosandhomes.com/buy.html">
    <meta property="og:type" content="website">
    <meta property="og:image" content="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.svg">
    <meta property="og:site_name" content="BC Condos And Homes">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Sell Your Home with BC's Top-Ranked RE/MAX Team | Hani &amp; Les | BC Condos And Homes">
    <meta name="twitter:description" content="Sell with BC's #1 ranked RE/MAX Diamond Club Team. $25K interest-free bridge loan, 3D marketing, and 30+ years of expertise. Get your free valuation today.">
    <meta name="twitter:image" content="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.svg">
    <link rel="canonical" href="https://www.bccondosandhomes.com/buy.html">
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "BreadcrumbList",
          "itemListElement": [
            {"@type":"ListItem","position":1,"name":"Home","item":"https://www.bccondosandhomes.com/"},
            {"@type":"ListItem","position":2,"name":"Buy a Home in BC","item":"https://www.bccondosandhomes.com/buy.html"}
          ]
        },
        {
          "@type": "FAQPage",
          "mainEntity": [
            {
              "@type": "Question",
              "name": "How do I buy a home in BC?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Buying a home in BC typically involves getting pre-approved for a mortgage, working with a licensed realtor to search MLS listings, making an offer, conducting due diligence (home inspection, title search, strata document review), and completing the closing process. Working with an experienced local realtor — like the Hani & Les team — ensures you have expert guidance on pricing, neighbourhoods, and negotiation from start to finish."
              }
            },
            {
              "@type": "Question",
              "name": "Do I need a realtor to buy a home in BC?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "While it is not legally required, working with a licensed realtor is strongly recommended when buying in BC. A buyer's agent represents your interests, provides access to all MLS listings, advises on fair market value, and negotiates on your behalf — typically at no direct cost to you, as the buyer's agent commission is paid by the seller. The Hani & Les team offers dedicated buyer representation across Greater Vancouver and the Fraser Valley."
              }
            },
            {
              "@type": "Question",
              "name": "How much does it cost to buy a condo in Vancouver?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Condo prices in Vancouver vary significantly by neighbourhood and size. As of recent market data, one-bedroom condos in Vancouver typically range from $600,000 to $900,000, while two-bedrooms range from $850,000 to $1.5M+. Suburban areas like Burnaby, Richmond, and Surrey offer lower entry points. The Hani & Les team can provide current MLS data and neighbourhood-specific pricing guidance before you start your search."
              }
            },
            {
              "@type": "Question",
              "name": "What closing costs should I expect when buying in BC?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Buyers in BC should budget for Property Transfer Tax (1% on the first $200K, 2% on $200K–$2M, 3% above $2M — with first-time buyer exemptions available), GST on new construction, legal fees ($1,500–$2,500), home inspection ($400–$600), title insurance, and strata document review fees if applicable. Your realtor and lawyer will walk you through the exact costs based on your purchase price and property type."
              }
            }
          ]
        },
        {
          "@type": "Service",
          "name": "Buyer Representation",
          "description": "Full-service buyer representation across Greater Vancouver and the Fraser Valley. Our dedicated buyer specialists help you find, evaluate, and negotiate the right home at the right price.",
          "provider": {
            "@type": "RealEstateAgent",
            "name": "Hani & Les | BC Condos And Homes",
            "url": "https://www.bccondosandhomes.com",
            "telephone": "+16042657975"
          },
          "areaServed": "British Columbia",
          "serviceType": "Real Estate Buyer Representation"
        }
      ]
    }
    </script>
@endsection
@section('content')
@if(Auth::user())
@include('frontend.includes.header')
@else
@include('frontend.includes.header_realtorpage')
@endif

<div class="main sell__main" role="main">

    {{-- Hero --}}
    <div style="background:linear-gradient(135deg,#1a2a3a 0%,#2c4a6a 100%);padding:70px 0 54px;">
        <div class="container">
            <div class="row" style="align-items:center;">
                <div class="col-md-8 col-sm-7">
                    <div style="display:inline-block;background:#e5b021;color:#111;font-size:11px;font-weight:700;padding:4px 12px;border-radius:3px;text-transform:uppercase;letter-spacing:.8px;margin-bottom:16px;">RE/MAX Diamond Club Team</div>
                    <h1 style="font-size:38px;font-weight:800;color:#fff;margin:0 0 16px;line-height:1.2;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">Sell Your Home for More<br>with BC's Top-Ranked Team</h1>
                    <p style="font-size:16px;color:#b8cfe0;margin:0 0 20px;line-height:1.65;">The RE/MAX Diamond Club team at Hani & Les | BC Condos And Homes combines 30+ years of expertise, a 12-agent specialist network, and a unique <strong style="color:#e5b021;">$25,000 interest-free bridge loan</strong> — so you can move forward with confidence before your home sells.</p>
                    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:4px;">
                        <a href="tel:16042608588" style="background:#e5b021;color:#111;border:none;border-radius:5px;padding:14px 26px;font-size:15px;font-weight:700;cursor:pointer;letter-spacing:.2px;text-decoration:none;display:inline-block;">Get My Free Home Valuation</a>
                        <a href="tel:16042608588" style="background:transparent;color:#fff;border:2px solid rgba(255,255,255,.4);border-radius:5px;padding:14px 26px;font-size:15px;font-weight:600;text-decoration:none;">Call 604-260-8588</a>
                    </div>
                </div>
                <div class="col-md-4 col-sm-5 hidden-xs" style="text-align:center;">
                    <img src="{{ asset('frontend/images/teamagents/les-twarog-headshot.jpg') }}" alt="Les Twarog — Top Realtor BC" style="width:190px;height:190px;object-fit:cover;border-radius:50%;border:4px solid #e5b021;box-shadow:0 10px 36px rgba(0,0,0,.45);">
                    <div style="color:#e5b021;font-weight:700;font-size:14px;margin-top:12px;">Les Twarog</div>
                    <div style="color:#aac4e0;font-size:12px;">Principal &amp; Team Lead</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Hero CTA strip: guest-only listing alert sign-up --}}
    @guest
    <div class="container" style="padding:20px 15px 0;">
        @include('frontend.includes.alert_cta_strip', [
            'stripContext'    => 'Metro Vancouver',
            'stripHeading'    => 'Get Instant Alerts on New Listings',
            'stripSubtext'    => 'New homes sell fast. Be the first to know the moment a matching property hits the market.',
            'stripSearchName' => 'Metro Vancouver Listings',
            'stripSearchData' => json_encode(['listing_status' => 'Active']),
            'stripIcon'       => '🔔',
            'stripBtnText'    => 'Set Up Alerts',
            'stripModalId'    => 'buyHeroAlert',
        ])
    </div>
    @endguest

    {{-- Credentials Bar --}}
    <div style="background:#e5b021;padding:18px 0;">
        <div class="container">
            <div class="row" style="text-align:center;">
                <div class="col-xs-4 col-sm-2" style="padding:8px 10px;">
                    <div style="font-size:22px;font-weight:800;color:#111;">$40M+</div>
                    <div style="font-size:11px;font-weight:700;color:#333;text-transform:uppercase;">Sold Annually</div>
                </div>
                <div class="col-xs-4 col-sm-2" style="padding:8px 10px;">
                    <div style="font-size:22px;font-weight:800;color:#111;">30+</div>
                    <div style="font-size:11px;font-weight:700;color:#333;text-transform:uppercase;">Years Experience</div>
                </div>
                <div class="col-xs-4 col-sm-2" style="padding:8px 10px;">
                    <div style="font-size:22px;font-weight:800;color:#111;">12</div>
                    <div style="font-size:11px;font-weight:700;color:#333;text-transform:uppercase;">Specialist Agents</div>
                </div>
                <div class="col-xs-6 col-sm-3" style="padding:8px 10px;">
                    <div style="font-size:22px;font-weight:800;color:#111;">Top 1–5%</div>
                    <div style="font-size:11px;font-weight:700;color:#333;text-transform:uppercase;">of BC Realtors</div>
                </div>
                <div class="col-xs-6 col-sm-3" style="padding:8px 10px;">
                    <div style="font-size:20px;font-weight:800;color:#111;">Diamond</div>
                    <div style="font-size:11px;font-weight:700;color:#333;text-transform:uppercase;">RE/MAX Club</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Why Sell With Us --}}
    <div style="background:#f9f7f4;padding:70px 0;">
        <div class="container">
            <div class="row" style="margin-bottom:36px;">
                <div class="col-md-12" style="text-align:center;">
                    <h2 style="font-size:30px;font-weight:800;color:#1a2a3a;margin:0 0 10px;">Why Sell With Hani & Les | BC Condos And Homes?</h2>
                    <p style="font-size:15px;color:#666;max-width:600px;margin:0 auto;">We're not just agents — we're BC's most data-rich real estate team, combining technology, market expertise, and a 12-agent network to get you the best outcome.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 col-sm-6" style="margin-bottom:28px;">
                    <div style="background:#fff;border-radius:8px;padding:26px;height:100%;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div style="font-size:32px;margin-bottom:10px;">🤝</div>
                        <h3 style="font-size:16px;font-weight:700;color:#1a2a3a;margin:0 0 8px;">12 Agents for the Price of 1</h3>
                        <p style="font-size:13px;color:#555;margin:0;line-height:1.65;">Our 12 area specialists all work your listing simultaneously — more showings, more offers, and a faster sale at the price you deserve.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6" style="margin-bottom:28px;">
                    <div style="background:#fff;border-radius:8px;padding:26px;height:100%;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div style="font-size:32px;margin-bottom:10px;">💰</div>
                        <h3 style="font-size:16px;font-weight:700;color:#1a2a3a;margin:0 0 8px;">$25,000 Interest-Free Bridge Loan</h3>
                        <p style="font-size:13px;color:#555;margin:0;line-height:1.65;">Need funds before closing? We lend you $25,000 within 3 days of a firm deal — interest-free for 60 days. Use it for a new deposit, taxes, or moving costs.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6" style="margin-bottom:28px;">
                    <div style="background:#fff;border-radius:8px;padding:26px;height:100%;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div style="font-size:32px;margin-bottom:10px;">🌐</div>
                        <h3 style="font-size:16px;font-weight:700;color:#1a2a3a;margin:0 0 8px;">BC's Largest Realtor Platform</h3>
                        <p style="font-size:13px;color:#555;margin:0;line-height:1.65;">Your listing reaches 30,000+ registered buyers on bccondosandhomes.com — BC's most visited independent real estate site. Maximum exposure from day one.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6" style="margin-bottom:28px;">
                    <div style="background:#fff;border-radius:8px;padding:26px;height:100%;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div style="font-size:32px;margin-bottom:10px;">📊</div>
                        <h3 style="font-size:16px;font-weight:700;color:#1a2a3a;margin:0 0 8px;">Data-Driven Pricing Strategy</h3>
                        <p style="font-size:13px;color:#555;margin:0;line-height:1.65;">We use real-time market data from our own analytics platform to price your home competitively — not guesswork. Sellers consistently achieve above-list results.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6" style="margin-bottom:28px;">
                    <div style="background:#fff;border-radius:8px;padding:26px;height:100%;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div style="font-size:32px;margin-bottom:10px;">🏆</div>
                        <h3 style="font-size:16px;font-weight:700;color:#1a2a3a;margin:0 0 8px;">RE/MAX Diamond Club — Top 1%</h3>
                        <p style="font-size:13px;color:#555;margin:0;line-height:1.65;">Fewer than 1% of all RE/MAX agents achieve Diamond Club status. This award reflects consistent, top-tier production — and it's our standard, not a one-off achievement.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6" style="margin-bottom:28px;">
                    <div style="background:#fff;border-radius:8px;padding:26px;height:100%;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div style="font-size:32px;margin-bottom:10px;">📷</div>
                        <h3 style="font-size:16px;font-weight:700;color:#1a2a3a;margin:0 0 8px;">Professional Listing Marketing</h3>
                        <p style="font-size:13px;color:#555;margin:0;line-height:1.65;">HDR photography, 3D Matterport tours, targeted email campaigns, and weekly seller reports — everything needed to attract the most qualified buyers.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Proof / Stats + Testimonials --}}
    <div style="background:#fff;padding:70px 0;">
        <div class="container">
            <div style="text-align:center;margin-bottom:36px;">
                <h2 style="font-size:28px;font-weight:800;color:#1a2a3a;margin:0 0 10px;">Results That Speak for Themselves</h2>
                <p style="font-size:15px;color:#666;">Our track record puts us consistently ahead of the BC average.</p>
            </div>
            {{-- Stat blocks --}}
            <div class="row" style="margin-bottom:50px;">
                <div class="col-md-3 col-sm-6 col-xs-6" style="text-align:center;margin-bottom:24px;">
                    <div style="font-size:38px;font-weight:800;color:#e5b021;">80+</div>
                    <div style="font-size:13px;font-weight:700;color:#333;text-transform:uppercase;letter-spacing:.5px;">Homes Sold / Year</div>
                </div>
                <div class="col-md-3 col-sm-6 col-xs-6" style="text-align:center;margin-bottom:24px;">
                    <div style="font-size:38px;font-weight:800;color:#e5b021;">11d</div>
                    <div style="font-size:13px;font-weight:700;color:#333;text-transform:uppercase;letter-spacing:.5px;">Avg Days on Market</div>
                </div>
                <div class="col-md-3 col-sm-6 col-xs-6" style="text-align:center;margin-bottom:24px;">
                    <div style="font-size:38px;font-weight:800;color:#e5b021;">101%</div>
                    <div style="font-size:13px;font-weight:700;color:#333;text-transform:uppercase;letter-spacing:.5px;">Sale / List Ratio</div>
                </div>
                <div class="col-md-3 col-sm-6 col-xs-6" style="text-align:center;margin-bottom:24px;">
                    <div style="font-size:38px;font-weight:800;color:#e5b021;">17×</div>
                    <div style="font-size:13px;font-weight:700;color:#333;text-transform:uppercase;letter-spacing:.5px;">Industry Avg Volume</div>
                </div>
            </div>
            {{-- Testimonials --}}
            <div class="row">
                <div class="col-md-6" style="margin-bottom:24px;">
                    <div style="background:#f9f7f4;border-left:4px solid #e5b021;border-radius:6px;padding:24px 22px;">
                        <div style="color:#e5b021;font-size:17px;margin-bottom:8px;">★★★★★</div>
                        <p style="font-size:14px;color:#333;line-height:1.7;margin:0 0 12px;">"Les and the team sold our Vancouver condo in 11 days — $22,000 over asking. The marketing was outstanding and communication was excellent throughout. We couldn't be happier."</p>
                        <div style="font-size:13px;font-weight:700;color:#1a2a3a;">Sandra M.</div>
                        <div style="font-size:12px;color:#888;">Vancouver, BC</div>
                    </div>
                </div>
                <div class="col-md-6" style="margin-bottom:24px;">
                    <div style="background:#f9f7f4;border-left:4px solid #e5b021;border-radius:6px;padding:24px 22px;">
                        <div style="color:#e5b021;font-size:17px;margin-bottom:8px;">★★★★★</div>
                        <p style="font-size:14px;color:#333;line-height:1.7;margin:0 0 12px;">"I've used other big-name agents before, but no one matched the level of data and market knowledge Les brought. He priced our Richmond home perfectly and it sold in a week."</p>
                        <div style="font-size:13px;font-weight:700;color:#1a2a3a;">David C.</div>
                        <div style="font-size:12px;color:#888;">Richmond, BC</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Team Section --}}
    <div style="background:#f3f0eb;padding:60px 0;">
        <div class="container">
            <div style="text-align:center;margin-bottom:30px;">
                <h2 style="font-size:26px;font-weight:800;color:#1a2a3a;margin:0 0 8px;">Your Dedicated Specialists</h2>
                <p style="font-size:14px;color:#666;">A full team working for you — at the cost of a single agent.</p>
            </div>
            <div class="row" style="justify-content:center;">
                <div class="col-md-2 col-sm-3 col-xs-4" style="text-align:center;margin-bottom:24px;">
                    <img src="{{ asset('frontend/images/teamagents/les-twarog-headshot.jpg') }}" alt="Les Twarog" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e5b021;margin-bottom:8px;">
                    <div style="font-size:13px;font-weight:700;color:#1a2a3a;">Les Twarog</div>
                    <div style="font-size:11px;color:#888;">Team Lead</div>
                </div>
                <div class="col-md-2 col-sm-3 col-xs-4" style="text-align:center;margin-bottom:24px;">
                    <img src="{{ asset('frontend/images/teamagents/hani_faraj.jpg') }}" alt="Hani Faraj" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e5b021;margin-bottom:8px;">
                    <div style="font-size:13px;font-weight:700;color:#1a2a3a;">Hani Faraj</div>
                    <div style="font-size:11px;color:#888;">Sales Specialist</div>
                </div>
                <div class="col-md-2 col-sm-3 col-xs-4" style="text-align:center;margin-bottom:24px;">
                    <img src="{{ asset('frontend/images/teamagents/frank.jpg') }}" alt="Frank" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e5b021;margin-bottom:8px;">
                    <div style="font-size:13px;font-weight:700;color:#1a2a3a;">Frank</div>
                    <div style="font-size:11px;color:#888;">Senior Sales</div>
                </div>
                <div class="col-md-2 col-sm-3 col-xs-4" style="text-align:center;margin-bottom:24px;">
                    <img src="{{ asset('frontend/images/teamagents/celina.jpg') }}" alt="Celina" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e5b021;margin-bottom:8px;">
                    <div style="font-size:13px;font-weight:700;color:#1a2a3a;">Celina</div>
                    <div style="font-size:11px;color:#888;">RE Specialist</div>
                </div>
                <div class="col-md-2 col-sm-3 col-xs-4" style="text-align:center;margin-bottom:24px;">
                    <img src="{{ asset('frontend/images/teamagents/karolina.jpg') }}" alt="Karolina" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e5b021;margin-bottom:8px;">
                    <div style="font-size:13px;font-weight:700;color:#1a2a3a;">Karolina</div>
                    <div style="font-size:11px;color:#888;">Condo Specialist</div>
                </div>
                <div class="col-md-2 col-sm-3 col-xs-4" style="text-align:center;margin-bottom:24px;">
                    <img src="{{ asset('frontend/images/teamagents/andrew.jpg') }}" alt="Andrew" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e5b021;margin-bottom:8px;">
                    <div style="font-size:13px;font-weight:700;color:#1a2a3a;">Andrew</div>
                    <div style="font-size:11px;color:#888;">Buyer Specialist</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Intercom CTA Banner --}}
    <div class="sell__banner hidden-xs">
        <div class="sell__banner--text">
            <h2>Talk To a Hani & Les | BC Condos And Homes Realtor Now!</h2>
            <a href="tel:16042608588" class="btn btn-default">Call Us Now</a>
        </div>
    </div>

    {{-- Tools Section --}}
    <div class="white__bg text__section--3">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="text">
                        <h2>Cutting-Edge Marketing Tools</h2>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 hidden-xs">
                    <div class="item__list-icon">
                        <div class="icon__img">
                            <img src="{{ asset('frontend/images/sell/matterport.svg') }}" alt="3D Matterport tours" />
                        </div>
                        <h3>3D Matterport</h3>
                        <p>Immersive virtual tours that attract the most qualified buyers and dramatically reduce wasted showings.</p>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="item__list-icon">
                        <div class="icon__img">
                            <img src="{{ asset('frontend/images/sell/photography.svg') }}" alt="HDR Photography" />
                        </div>
                        <h3>HDR Photography</h3>
                        <p>Professional real estate photography that makes your property stand out and generates 3× more inquiries.</p>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="item__list-icon">
                        <div class="icon__img">
                            <img src="{{ asset('frontend/images/sell/marketing.svg') }}" alt="Digital marketing campaigns" />
                        </div>
                        <h3>Digital Marketing Campaigns</h3>
                        <p>Targeted email campaigns to 30,000+ registered buyers plus social, display, and listing network promotion.</p>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="item__list-icon">
                        <div class="icon__img">
                            <img src="{{ asset('frontend/images/sell/weeklystats.svg') }}" alt="Weekly seller reports" />
                        </div>
                        <h3>Weekly Seller Reports</h3>
                        <p>Transparent weekly updates on views, inquiries, and comparable sales — so you're always informed.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- $25K Loan CTA --}}
    <div style="background:linear-gradient(135deg,#1a2a3a 0%,#2c3e50 100%);padding:60px 0;">
        <div class="container">
            <div class="row" style="align-items:center;">
                <div class="col-md-7 col-sm-12" style="margin-bottom:28px;">
                    <div style="display:inline-block;background:#e5b021;color:#111;font-size:11px;font-weight:700;padding:4px 12px;border-radius:3px;text-transform:uppercase;letter-spacing:.8px;margin-bottom:16px;">Exclusive to Hani & Les | BC Condos And Homes Sellers</div>
                    <h2 style="font-size:30px;font-weight:800;color:#fff;margin:0 0 14px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">Get $25,000 Interest-Free</h2>
                    <p style="font-size:15px;color:#aac4e0;margin:0 0 20px;line-height:1.65;">When you list with us, we'll lend you $25,000 within 3 days of a firm deal — completely interest-free for 60 days. Use it however you need: a deposit on your next home, taxes, moving costs, or renovations.</p>
                    <ul style="list-style:none;padding:0;margin:0 0 24px;">
                        <li style="font-size:14px;color:#b8cfe0;margin-bottom:10px;display:flex;align-items:flex-start;gap:10px;"><span style="color:#e5b021;font-weight:700;flex-shrink:0;">✓</span> No obligation — available to all our sellers</li>
                        <li style="font-size:14px;color:#b8cfe0;margin-bottom:10px;display:flex;align-items:flex-start;gap:10px;"><span style="color:#e5b021;font-weight:700;flex-shrink:0;">✓</span> No upfront cost — funded within 3 days of a firm deal</li>
                        <li style="font-size:14px;color:#b8cfe0;margin-bottom:10px;display:flex;align-items:flex-start;gap:10px;"><span style="color:#e5b021;font-weight:700;flex-shrink:0;">✓</span> Paid back at closing — zero stress during your transition</li>
                        <li style="font-size:14px;color:#b8cfe0;margin-bottom:0;display:flex;align-items:flex-start;gap:10px;"><span style="color:#e5b021;font-weight:700;flex-shrink:0;">✓</span> Interest-free for 60 days (1% monthly after day 60)</li>
                    </ul>
                    <a href="tel:16042608588" style="background:#e5b021;color:#111;border:none;border-radius:5px;padding:14px 28px;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;">Ask Us About the $25K Loan</a>
                </div>
                <div class="col-md-5 col-sm-12">
                    <div style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.15);border-radius:10px;padding:30px 24px;text-align:center;">
                        <div style="font-size:54px;font-weight:900;color:#e5b021;line-height:1;">$25K</div>
                        <div style="font-size:16px;font-weight:700;color:#fff;margin:8px 0 4px;">Interest-Free Bridge Loan</div>
                        <div style="font-size:13px;color:#aac4e0;margin-bottom:24px;">Available to all Hani & Les | BC Condos And Homes sellers</div>
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <div style="background:rgba(229,176,33,.12);border:1px solid rgba(229,176,33,.3);border-radius:6px;padding:12px 16px;font-size:13px;color:#e0c870;font-weight:600;">Within 3 days of a firm deal</div>
                            <div style="background:rgba(229,176,33,.12);border:1px solid rgba(229,176,33,.3);border-radius:6px;padding:12px 16px;font-size:13px;color:#e0c870;font-weight:600;">60 days interest-free</div>
                            <div style="background:rgba(229,176,33,.12);border:1px solid rgba(229,176,33,.3);border-radius:6px;padding:12px 16px;font-size:13px;color:#e0c870;font-weight:600;">Repaid at closing</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Home Evaluation Widget --}}
    <div style="background:#1a2a3a;padding:60px 0;">
        <div class="container">
            <div class="row" style="align-items:center;">
                <div class="col-md-5 col-sm-12" style="margin-bottom:28px;">
                    <h2 style="font-size:26px;font-weight:800;color:#fff;margin:0 0 12px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">What Is Your Home Worth?</h2>
                    <p style="font-size:15px;color:#aac4e0;margin:0 0 16px;line-height:1.6;">Get a free, no-obligation home evaluation from Metro Vancouver's RE/MAX Diamond Club team. We'll assess your property against current MLS® data and market trends.</p>
                    <ul style="list-style:none;padding:0;margin:0;font-size:14px;color:#b8cfe0;">
                        <li style="margin-bottom:6px;">✓ Free — no commitment required</li>
                        <li style="margin-bottom:6px;">✓ Based on real MLS® sold data</li>
                        <li style="margin-bottom:6px;">✓ Includes $25,000 Interest-Free bridge loan offer</li>
                        <li style="margin-bottom:6px;">✓ Response within 24 hours</li>
                    </ul>
                </div>
                <div class="col-md-7 col-sm-12">
                    <div style="background:#fff;border-radius:10px;padding:6px;">
                        <div id="bc-home-eval"></div>
                        <script src="{{ asset('widget/home-evaluation.js') }}"
                            data-placement="inline"
                            data-target="#bc-home-eval">
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Buyer's Guide Link --}}
    <div style="background:#f9f7f4;padding:36px 0;border-top:1px solid #e8e3db;">
        <div class="container">
            <div class="row">
                <div class="col-md-10 col-md-offset-1" style="text-align:center;">
                    <p style="font-size:15px;color:#555;margin:0;">New to buying in BC? Read our comprehensive <a href="/buyers-guide" style="color:#1a6baa;font-weight:600;">BC Home Buyer's Guide &rarr;</a> — covering buyer's agents, mortgage pre-approval, strata vs. freehold, making an offer, understanding subjects, and the full closing process.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container" style="padding:0 0 30px;">
        @include('frontend.includes.alert_cta_strip', [
            'stripContext'    => 'Metro Vancouver',
            'stripHeading'    => 'Find Your Next Home First',
            'stripSubtext'    => 'Sign up for instant alerts — get notified the moment a new listing matches your criteria before it sells.',
            'stripSearchName' => 'Metro Vancouver Listings',
            'stripSearchData' => json_encode(['listing_status' => 'Active']),
            'stripIcon'       => '🏡',
            'stripBtnText'    => 'Get Listing Alerts',
            'stripModalId'    => 'buyAlert',
        ])
    </div>

</div>

<footer>
    <div class="container">
        <div class="footer__information">
            <p><a href="/terms-and-conditions" target="_blank">Terms &amp; Conditions</a> &#183; <a href="/privacy-policy" target="_blank">Privacy Policy</a></p>
            <p><img src="{{ asset('frontend/images/pixilink-logo.svg') }}" alt="Hani & Les | BC Condos And Homes Logo Footer" loading="lazy" /></p>
        </div>
    </div>
</footer>

<style>
    .main.sell__main {
        padding: 0;
    }
    .sell__main h1,
    .sell__main h2,
    .sell__main h3 {
        text-transform: none;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-weight: 700;
        margin: 0;
    }
    .white__bg {
        padding: 80px 0;
    }
    .text__section--3 h2 {
        font-size: 28px;
        font-weight: 800;
        color: #1a2a3a;
        margin-bottom: 40px;
    }
    .icon__img {
        margin-bottom: 16px;
    }
    .icon__img img {
        width: 80px;
        height: 80px;
        filter: invert(0%) sepia(0%) saturate(691%) hue-rotate(214deg) brightness(0%) contrast(107%);
    }
    .text__section--3 .col-md-6:nth-child(-n+3) .item__list-icon {
        margin-bottom: 50px;
    }
    .text__section--3 p {
        font-size: 15px;
        color: #555;
        line-height: 1.65;
    }
    .item__list-icon h3 {
        font-size: 16px;
        font-weight: 700;
        color: #1a2a3a;
        margin-bottom: 8px;
    }
    .sell__banner {
        position: relative;
        height: 380px;
        background-image: url({{ asset('frontend/images/sell/banner-01.jpg') }});
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center center;
    }
    .sell__banner--text {
        position: absolute;
        text-align: center;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    .sell__banner--text,
    .sell__banner--text h2 {
        color: #fff;
    }
    .sell__banner--text h2 {
        font-size: 30px;
        font-weight: 800;
        margin-bottom: 20px;
    }
    .footer__information {
        padding: 28px 0;
        text-align: center;
    }
    .footer__information p {
        margin: 6px 0;
        font-size: 13px;
        color: #888;
    }
    .footer__information a {
        color: #888;
        text-decoration: none;
    }
    .footer__information img {
        height: 30px;
        opacity: .5;
    }
    @media (max-width: 767px) {
        .main.sell__main {
            padding: 60px 0 0;
        }
        .white__bg {
            padding: 50px 0;
        }
        .text__section--3 .item__list-icon {
            margin-bottom: 36px !important;
        }
        .text__section--3 .col-md-6:last-child .item__list-icon {
            margin-bottom: 0 !important;
        }
    }
</style>

<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
    data-placement="main"
    data-city="Vancouver"
    data-market-type="sellers"
    data-buyers="500"
></script>

@endsection
@push('after-scripts')
<script>
window.BCTrack = {
  pageType:     "buy",
  city:         "{{ addslashes($place->menu_title ?? '') }}",
  propertyType: "{{ addslashes(request()->input('type', '')) }}",
  searchQuery:  "{{ addslashes(request()->input('q', '')) }}",
};
</script>
@include('frontend.includes.user_additional_scripts')
@endpush
