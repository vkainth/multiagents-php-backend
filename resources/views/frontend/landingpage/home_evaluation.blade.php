@extends('frontend.layouts.default_mobilefirst')

@section('title')
Free Home Evaluation Vancouver | What Is My Home Worth? | BC Condos And Homes
@endsection

@section('meta_description')
Find out what your home is worth with a free, personal evaluation from Hani — BC's top RE/MAX team. Not an instant estimate. Hani personally reviews your property and responds within 6 hours. Serving Vancouver, Surrey & all of BC.
@endsection

@section('meta')
    <link rel="canonical" href="https://www.bccondosandhomes.com/home-evaluation">
    <meta property="og:title" content="Free Home Evaluation Vancouver | What Is My Home Worth? | BC Condos And Homes">
    <meta property="og:description" content="Not an instant online estimate. Hani personally reviews your property against live MLS® sold data and gets back to you within 6 hours. Free, no obligation.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.bccondosandhomes.com/home-evaluation">
    <meta property="og:image" content="https://www.bccondosandhomes.com/frontend/images/teamagents/hani_faraj.jpg">
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "BreadcrumbList",
          "itemListElement": [
            {"@type":"ListItem","position":1,"name":"Home","item":"https://www.bccondosandhomes.com/"},
            {"@type":"ListItem","position":2,"name":"Free Home Evaluation","item":"https://www.bccondosandhomes.com/home-evaluation"}
          ]
        },
        {
          "@type": "FAQPage",
          "mainEntity": [
            {
              "@type": "Question",
              "name": "What is my condo worth?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Your condo's value depends on recent comparable sales in your building and neighbourhood, current market conditions, suite size, floor level, view, updates, and strata fees. Hani will personally review all of these factors and provide you with a detailed, accurate home evaluation within 6 hours — completely free and with no obligation."
              }
            },
            {
              "@type": "Question",
              "name": "How much can I sell my home for in Vancouver?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "The price you can achieve for your Vancouver home depends on its specific location, property type, current market demand, and recent sold prices for comparable properties. Rather than giving you a generic estimate, Hani will assess your property personally against live MLS® sold data and deliver a tailored home evaluation within 6 hours."
              }
            },
            {
              "@type": "Question",
              "name": "How long does a home evaluation take?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Once you submit your property details, Hani personally reviews your home and delivers a comprehensive evaluation within 6 hours. This is not an automated or instant process — every evaluation is done by hand using current market data."
              }
            },
            {
              "@type": "Question",
              "name": "Is a free home evaluation really free?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Yes. The home evaluation from Hani is completely free with no obligation to list or take any further action. It is a genuine, personal assessment to help you understand your property's market value in BC."
              }
            },
            {
              "@type": "Question",
              "name": "How do I find out my property value in BC?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "BC Assessment provides a government estimate each year, but these figures are often 6–18 months behind the market and can be significantly off. For an accurate, current assessment of what your property is worth, request a free home evaluation from Hani — he will personally review your home and respond within 6 hours based on live MLS® sold data."
              }
            },
            {
              "@type": "Question",
              "name": "What is the difference between a home evaluation and a condo appraisal?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "A condo appraisal is a formal report commissioned by lenders, typically costing $300–$500. A home evaluation from a realtor like Hani is a free, personal market assessment that shows you what your property would realistically sell for right now — based on active listings and recent sold prices, not replacement cost."
              }
            }
          ]
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

<div class="main sell__main" role="main" style="padding-top:64px;">

    {{-- Hero --}}
    <div style="background:linear-gradient(135deg,#1a2a3a 0%,#2c4a6a 100%);padding:70px 0 54px;">
        <div class="container">
            <div class="row" style="align-items:center;">
                <div class="col-md-8 col-sm-7">
                    <div style="display:inline-block;background:#e5b021;color:#111;font-size:11px;font-weight:700;padding:4px 12px;border-radius:3px;text-transform:uppercase;letter-spacing:.8px;margin-bottom:16px;">Free &mdash; No Obligation &mdash; Personal Response Within 6 Hours</div>
                    <h1 style="font-size:38px;font-weight:800;color:#fff;margin:0 0 16px;line-height:1.2;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">What Is My Home Worth in Vancouver?</h1>
                    <p style="font-size:16px;color:#b8cfe0;margin:0 0 10px;line-height:1.65;">Get a <strong style="color:#e5b021;">free, personal home evaluation</strong> from Hani — this is not an online estimate or an AI tool. You fill in your property details, Hani personally studies your home against current MLS&reg; sold data, and contacts you directly with a detailed, honest assessment.</p>
                    <p style="font-size:15px;color:#aac4e0;margin:0 0 20px;line-height:1.6;"><strong style="color:#fff;">What happens after you submit:</strong> Hani reviews your property — floor, view, suite size, building history, comparable sales — and sends you a written evaluation within 6 hours. No automated replies. No instant estimates. A real answer from a real person.</p>
                    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:4px;">
                        <a href="#evaluation-widget" style="background:#e5b021;color:#111;border:none;border-radius:5px;padding:14px 26px;font-size:15px;font-weight:700;cursor:pointer;letter-spacing:.2px;text-decoration:none;display:inline-block;">Submit My Property Details</a>
                        <a href="tel:16042657975" style="background:transparent;color:#fff;border:2px solid rgba(255,255,255,.4);border-radius:5px;padding:14px 26px;font-size:15px;font-weight:600;text-decoration:none;">Call 604-265-7975</a>
                    </div>
                </div>
                <div class="col-md-4 col-sm-5 hidden-xs" style="text-align:center;">
                    <img src="{{ asset('frontend/images/teamagents/hani_faraj.jpg') }}" alt="Hani Faraj — Free Home Evaluation Vancouver" style="width:190px;height:190px;object-fit:cover;border-radius:50%;border:4px solid #e5b021;box-shadow:0 10px 36px rgba(0,0,0,.45);">
                    <div style="color:#e5b021;font-weight:700;font-size:14px;margin-top:12px;">Hani Faraj</div>
                    <div style="color:#aac4e0;font-size:12px;">Your Personal Evaluator — Not a Bot</div>
                </div>
            </div>
        </div>
    </div>

    {{-- How It Works Bar --}}
    <div style="background:#e5b021;padding:22px 0;">
        <div class="container">
            <div class="row" style="text-align:center;align-items:center;">
                <div class="col-xs-12 col-sm-3" style="padding:8px 10px;">
                    <div style="font-size:20px;font-weight:800;color:#111;">Step 1</div>
                    <div style="font-size:12px;font-weight:700;color:#333;text-transform:uppercase;margin-top:3px;">You Submit Your Details</div>
                </div>
                <div class="col-xs-12 col-sm-1 hidden-xs" style="color:#555;font-size:22px;font-weight:300;">&rarr;</div>
                <div class="col-xs-12 col-sm-3" style="padding:8px 10px;">
                    <div style="font-size:20px;font-weight:800;color:#111;">Step 2</div>
                    <div style="font-size:12px;font-weight:700;color:#333;text-transform:uppercase;margin-top:3px;">Hani Personally Reviews</div>
                </div>
                <div class="col-xs-12 col-sm-1 hidden-xs" style="color:#555;font-size:22px;font-weight:300;">&rarr;</div>
                <div class="col-xs-12 col-sm-3" style="padding:8px 10px;">
                    <div style="font-size:20px;font-weight:800;color:#111;">Step 3</div>
                    <div style="font-size:12px;font-weight:700;color:#333;text-transform:uppercase;margin-top:3px;">You Receive Your Evaluation</div>
                </div>
                <div class="col-xs-12 col-sm-1" style="padding:8px 10px;">
                    <div style="font-size:13px;font-weight:700;color:#333;">Within 6 Hours</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Widget Section --}}
    <div id="evaluation-widget" style="background:#f9f7f4;padding:60px 0;">
        <div class="container">
            <div class="row">
                <div class="col-md-10 col-md-offset-1">
                    <div style="text-align:center;margin-bottom:28px;">
                        <h2 style="font-size:28px;font-weight:800;color:#1a2a3a;margin:0 0 10px;">Step 1 — Tell Us About Your Property</h2>
                        <p style="font-size:15px;color:#666;max-width:600px;margin:0 auto;">Fill in your property details below. Once you submit, Hani will personally review your home against current MLS&reg; sold data and contact you with a detailed evaluation within 6 hours.</p>
                        <div style="display:inline-block;background:#fff3cd;border:1px solid #e5b021;border-radius:6px;padding:10px 20px;margin-top:16px;font-size:13px;color:#7a5800;">
                            <strong>Please note:</strong> This is not an instant estimate. After you submit, Hani personally prepares your evaluation and gets back to you directly — within 6 hours during business hours.
                        </div>
                    </div>
                    <script>try { localStorage.removeItem('bcc_pv_count'); } catch(e) {}</script>
                    <script src="https://admin.bccondosandhomes.com/widget/home-evaluation.js" data-placement="main"></script>
                </div>
            </div>
        </div>
    </div>

    {{-- Why Hani Section --}}
    <div style="background:#fff;padding:70px 0;">
        <div class="container">
            <div class="row" style="margin-bottom:36px;">
                <div class="col-md-12" style="text-align:center;">
                    <h2 style="font-size:28px;font-weight:800;color:#1a2a3a;margin:0 0 10px;">Why This Evaluation Is Different</h2>
                    <p style="font-size:15px;color:#666;max-width:620px;margin:0 auto;">Most online tools give you a generic algorithm-based estimate. This is not that. Here is what you actually get when you submit to Hani.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 col-sm-6" style="margin-bottom:28px;">
                    <div style="background:#f9f7f4;border-radius:8px;padding:26px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div style="font-size:32px;margin-bottom:10px;">🧑‍💼</div>
                        <h3 style="font-size:16px;font-weight:700;color:#1a2a3a;margin:0 0 8px;">Reviewed by Hani Personally</h3>
                        <p style="font-size:13px;color:#555;margin:0;line-height:1.65;">Not an algorithm. Not a chatbot. Hani personally reviews your suite's floor, view, size, condition, and comparable MLS&reg; sold prices in your building and neighbourhood before responding.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6" style="margin-bottom:28px;">
                    <div style="background:#f9f7f4;border-radius:8px;padding:26px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div style="font-size:32px;margin-bottom:10px;">⏱️</div>
                        <h3 style="font-size:16px;font-weight:700;color:#1a2a3a;margin:0 0 8px;">Response Within 6 Hours</h3>
                        <p style="font-size:13px;color:#555;margin:0;line-height:1.65;">Submit your details and receive a thorough, written evaluation the same day — no waiting weeks for an appointment. Hani aims to respond within 6 hours during business hours.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6" style="margin-bottom:28px;">
                    <div style="background:#f9f7f4;border-radius:8px;padding:26px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div style="font-size:32px;margin-bottom:10px;">📊</div>
                        <h3 style="font-size:16px;font-weight:700;color:#1a2a3a;margin:0 0 8px;">Based on Real MLS&reg; Sold Data</h3>
                        <p style="font-size:13px;color:#555;margin:0;line-height:1.65;">Your evaluation is grounded in current sold prices from the MLS&reg; — not BC Assessment estimates that can lag the real market by 12–18 months.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6" style="margin-bottom:28px;">
                    <div style="background:#f9f7f4;border-radius:8px;padding:26px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div style="font-size:32px;margin-bottom:10px;">🏆</div>
                        <h3 style="font-size:16px;font-weight:700;color:#1a2a3a;margin:0 0 8px;">Top 1–2% of Vancouver Realtors</h3>
                        <p style="font-size:13px;color:#555;margin:0;line-height:1.65;">Hani is consistently ranked in the top 1–2% of Vancouver's 14,000 realtors and among the top 100 of RE/MAX Western Canada — with 700+ five-star Google reviews.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6" style="margin-bottom:28px;">
                    <div style="background:#f9f7f4;border-radius:8px;padding:26px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div style="font-size:32px;margin-bottom:10px;">🌐</div>
                        <h3 style="font-size:16px;font-weight:700;color:#1a2a3a;margin:0 0 8px;">157,000 Registered Buyers</h3>
                        <p style="font-size:13px;color:#555;margin:0;line-height:1.65;">If you decide to sell, your listing reaches 157,000 registered buyers on BC's largest realtor-owned platform — giving you unmatched market exposure from day one.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6" style="margin-bottom:28px;">
                    <div style="background:#f9f7f4;border-radius:8px;padding:26px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div style="font-size:32px;margin-bottom:10px;">💬</div>
                        <h3 style="font-size:16px;font-weight:700;color:#1a2a3a;margin:0 0 8px;">No Obligation — Ever</h3>
                        <p style="font-size:13px;color:#555;margin:0;line-height:1.65;">Whether you are thinking of selling now, in a year, or just want to know your property value in BC, this evaluation is yours with no strings attached.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Who This Is For --}}
    <div style="background:#1a2a3a;padding:60px 0;">
        <div class="container">
            <div style="text-align:center;margin-bottom:36px;">
                <h2 style="font-size:26px;font-weight:800;color:#fff;margin:0 0 10px;">Who Requests a Free Home Evaluation?</h2>
                <p style="font-size:15px;color:#aac4e0;max-width:560px;margin:0 auto;">Homeowners across Metro Vancouver and BC use this service when they want a real answer — not an algorithm.</p>
            </div>
            <div class="row">
                <div class="col-md-3 col-sm-6" style="text-align:center;margin-bottom:28px;">
                    <div style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:24px 16px;">
                        <div style="font-size:30px;margin-bottom:10px;">🏠</div>
                        <div style="font-size:14px;font-weight:700;color:#fff;margin-bottom:6px;">Homeowners</div>
                        <div style="font-size:12px;color:#aac4e0;line-height:1.55;">Wondering "how much can I sell my home for?" and want an honest answer before deciding to list.</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6" style="text-align:center;margin-bottom:28px;">
                    <div style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:24px 16px;">
                        <div style="font-size:30px;margin-bottom:10px;">📈</div>
                        <div style="font-size:14px;font-weight:700;color:#fff;margin-bottom:6px;">Investors</div>
                        <div style="font-size:12px;color:#aac4e0;line-height:1.55;">Tracking current market value and return on investment across their BC portfolio.</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6" style="text-align:center;margin-bottom:28px;">
                    <div style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:24px 16px;">
                        <div style="font-size:30px;margin-bottom:10px;">✈️</div>
                        <div style="font-size:14px;font-weight:700;color:#fff;margin-bottom:6px;">Out-of-Town Owners</div>
                        <div style="font-size:12px;color:#aac4e0;line-height:1.55;">Managing a rental property or inherited home and needing an accurate property value in BC.</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6" style="text-align:center;margin-bottom:28px;">
                    <div style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:24px 16px;">
                        <div style="font-size:30px;margin-bottom:10px;">🔑</div>
                        <div style="font-size:14px;font-weight:700;color:#fff;margin-bottom:6px;">First-Time Sellers</div>
                        <div style="font-size:12px;color:#aac4e0;line-height:1.55;">Looking for a trustworthy market assessment before choosing an agent to sell with.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FAQ Section --}}
    <div style="background:#f9f7f4;padding:70px 0;">
        <div class="container">
            <div class="row">
                <div class="col-md-10 col-md-offset-1">
                    <div style="text-align:center;margin-bottom:40px;">
                        <h2 style="font-size:28px;font-weight:800;color:#1a2a3a;margin:0 0 10px;">Common Questions About Home Evaluations in BC</h2>
                        <p style="font-size:15px;color:#666;">Everything you need to know before finding out what your property is worth.</p>
                    </div>

                    <div style="margin-bottom:24px;background:#fff;border-radius:8px;padding:24px 28px;box-shadow:0 2px 10px rgba(0,0,0,.05);">
                        <h3 style="font-size:17px;font-weight:700;color:#1a2a3a;margin:0 0 10px;">What is my condo worth?</h3>
                        <p style="font-size:14px;color:#555;margin:0;line-height:1.7;">Your condo's market value is shaped by recent comparable sales in your building and neighbourhood, your unit's size, floor, view, condition, and the building's strata fees and age. Online estimates miss building-specific factors that significantly affect value. Hani reviews all of these personally and gives you a precise, current answer — delivered within 6 hours of your request.</p>
                    </div>

                    <div style="margin-bottom:24px;background:#fff;border-radius:8px;padding:24px 28px;box-shadow:0 2px 10px rgba(0,0,0,.05);">
                        <h3 style="font-size:17px;font-weight:700;color:#1a2a3a;margin:0 0 10px;">How much can I sell my home for in Vancouver?</h3>
                        <p style="font-size:14px;color:#555;margin:0;line-height:1.7;">Vancouver real estate prices vary enormously by neighbourhood, property type, and market timing. Rather than giving you a generic estimate, Hani personally assesses your property against live MLS&reg; sold data for your area and delivers a realistic selling price range — so you can decide whether and when to list with confidence.</p>
                    </div>

                    <div style="margin-bottom:24px;background:#fff;border-radius:8px;padding:24px 28px;box-shadow:0 2px 10px rgba(0,0,0,.05);">
                        <h3 style="font-size:17px;font-weight:700;color:#1a2a3a;margin:0 0 10px;">Is this an instant, automated estimate?</h3>
                        <p style="font-size:14px;color:#555;margin:0;line-height:1.7;">No. This is not an AI estimate or an automated valuation. Once you submit your property details, Hani personally reviews your home — its location, floor, view, comparable sold prices, and building specifics — and sends you a detailed written evaluation. The process takes up to 6 hours during business hours. You will hear directly from Hani, not from a bot.</p>
                    </div>

                    <div style="margin-bottom:24px;background:#fff;border-radius:8px;padding:24px 28px;box-shadow:0 2px 10px rgba(0,0,0,.05);">
                        <h3 style="font-size:17px;font-weight:700;color:#1a2a3a;margin:0 0 10px;">Is a free home evaluation really free?</h3>
                        <p style="font-size:14px;color:#555;margin:0;line-height:1.7;">Yes — completely free, with no obligation whatsoever. You are under no pressure to list, sign anything, or commit to any service. The evaluation is a genuine starting point, whether you are thinking of selling soon or simply want to know your property value in BC.</p>
                    </div>

                    <div style="margin-bottom:24px;background:#fff;border-radius:8px;padding:24px 28px;box-shadow:0 2px 10px rgba(0,0,0,.05);">
                        <h3 style="font-size:17px;font-weight:700;color:#1a2a3a;margin:0 0 10px;">How do I find out my property value in BC?</h3>
                        <p style="font-size:14px;color:#555;margin:0;line-height:1.7;">BC Assessment publishes annual property values, but these figures are based on a snapshot in time and can lag the real market by 12–18 months — sometimes significantly over or underestimating your home's actual selling price. For a current, accurate market value, a personal realtor evaluation using live MLS&reg; sold data is far more reliable. Submit your details above and Hani will personally review your home and respond within 6 hours.</p>
                    </div>

                    <div style="margin-bottom:0;background:#fff;border-radius:8px;padding:24px 28px;box-shadow:0 2px 10px rgba(0,0,0,.05);">
                        <h3 style="font-size:17px;font-weight:700;color:#1a2a3a;margin:0 0 10px;">What is the difference between a home evaluation and a condo appraisal in BC?</h3>
                        <p style="font-size:14px;color:#555;margin:0;line-height:1.7;">A formal condo appraisal is a paid report commissioned by a lender, typically costing $300–$500 and used to verify value for mortgage purposes. A realtor home evaluation from Hani is a free, personal market assessment showing what your property would realistically sell for today. For homeowners exploring their options, the realtor evaluation is more practical, immediately useful, and costs you nothing.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CTA Banner --}}
    <div class="sell__banner">
        <div class="sell__banner--text">
            <h2>Ready to Find Out What Your Home Is Worth?</h2>
            <p>Submit your details above — Hani will personally review your property and get back to you within 6 hours.</p>
            <a href="#evaluation-widget" class="btn btn-default">Submit My Property Details</a>
        </div>
    </div>

</div>

@include('frontend.includes.footer')
@endsection
