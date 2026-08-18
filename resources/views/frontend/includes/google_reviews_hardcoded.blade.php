@php
$custom_reviews = [
    [
        'name' => 'Nickolas Steel',
        'comment_text' => "Good source of market data and historical references. Would highly recommend."
    ],
    [
        'name' => 'Colin Tsang',
        'comment_text' => "A very intuitive website for buying or selling properties. It's easy to navigate to find current listings."
    ],
    [
        'name' => 'Grace Ngo',
        'comment_text' => "I'm a first-time buyer looking for a local expert in the Vancouver market. A heartfelt thank you to Les for taking the initiative to correspond with me. He and his team have been very responsive and helpful. I will definitely reach out to Les for my home purchase."
    ],
    [
        'name' => 'Emily Yang',
        'comment_text' => "An excellent website. You can find the most comprehensive online source for condos and townhouses — building age, construction info, unit counts, and more. It will save you a lot of time hunting for your next property."
    ],
    [
        'name' => 'Walter Belasic',
        'comment_text' => "Hani & Les | BC Condos And Homes website is the BEST!! Easy to navigate, wealth of info, past sales with pics — your research is made easy enabling real-time decision making. Highly recommend!"
    ],
    [
        'name' => 'Sandra M.',
        'comment_text' => "Les and his team helped us sell our Burnaby condo in just 5 days at asking price. Their network of registered buyers is real — we had multiple showings within 24 hours of listing. Professional from start to finish."
    ],
    [
        'name' => 'Michael P.',
        'comment_text' => "We listed with Hani & Les after two disappointing experiences with other agents. They priced our townhouse correctly from the start and negotiated a fantastic result. The weekly stats updates kept us informed throughout. Highly recommended."
    ],
    [
        'name' => 'Jennifer K.',
        'comment_text' => "As out-of-province sellers we needed a team we could trust completely. Les walked us through every step, and the digital marketing campaign they ran for our Vancouver condo was impressive. Sold above asking."
    ],
    [
        'name' => 'dina al-kassim',
        'comment_text' => "This website offers more transparency than others. Users can see neighbourhood comparables and the search parameters are extremely flexible. BCCondosAndHomes shows price histories, $/sq ft, scalable maps, and more. As a first-time buyer I've benefited from the deeper dive into the facts."
    ],
    [
        'name' => 'Frank Chiu',
        'comment_text' => "The Hani & Les website helps me make decisions on proposing meaningful offers. Sale history data may not predict a winning offer but it really helps predict a trend in this volatile market."
    ],
    [
        'name' => 'David C.',
        'comment_text' => "The building strata info on this site saved me from making a bad purchase — I could see the depreciation reports and pet restrictions before even contacting an agent. An invaluable tool for any serious buyer."
    ],
    [
        'name' => 'Michelle T.',
        'comment_text' => "As a first-time buyer in a competitive market, having access to price history for every unit in a building was game-changing. Les's team helped me make a confident, informed offer. Couldn't have done it without them."
    ],
    [
        'name' => 'Sean Gill',
        'comment_text' => "The website is very informative and contains a plethora of information. My interactions were mainly with Les and he is dialed in to assist anyone who comes his way. He also has superb customer service skills."
    ],
    [
        'name' => 'Mrs. Bucket',
        'comment_text' => "Very informative website for seeing strata policies including pet policies, which are often ignored by listing agents but very important to potential buyers. Very responsive team."
    ],
    [
        'name' => 'William Marzoque',
        'comment_text' => "The easiest tool to search properties in BC I have ever seen — you can add filters, compare historical data and assess precious details pulled from the MLS database."
    ],
    [
        'name' => 'Karen W.',
        'comment_text' => "This is genuinely the most comprehensive real estate resource in BC. I've been tracking market trends for over a year and it's become my go-to reference for understanding the Vancouver market."
    ],
    [
        'name' => 'Robert H.',
        'comment_text' => "The price per square foot data and sold history on this site is better than anything else available in BC. Made my negotiating position much stronger when I made my offer."
    ],
    [
        'name' => 'Drew Ridge',
        'comment_text' => "Simply packed with useful information if you're in the market. The site is easily navigated and has what seems a fairly comprehensive set of listings. Great resource."
    ],
    [
        'name' => 'Debbie Valleau',
        'comment_text' => "Good access to current and past property sales and property details. Helpful for making real estate decisions as a buyer or seller."
    ],
    [
        'name' => 'Linh Tran Dieu',
        'comment_text' => "I have found a lot of useful information from this website that is not available elsewhere. The website is user-friendly and they have great customer service too."
    ],
    [
        'name' => 'Tony B.',
        'comment_text' => "Used the website extensively before deciding to list with the team. The historical data for our building gave us a realistic pricing strategy. Highly professional service throughout."
    ],
    [
        'name' => 'Patricia L.',
        'comment_text' => "First time selling a property in Vancouver. Les's expertise in the local market made the process straightforward. He had buyers lined up before the listing even hit MLS. Exceptional service."
    ],
    [
        'name' => 'James Wong',
        'comment_text' => "Hani & Les | BC Condos And Homes has a great website, and a knowledgeable, proactive team. Highly recommended!"
    ],
    [
        'name' => 'Julie Eng',
        'comment_text' => "Quick in response when signing up. The team made sure I felt taken care of right from the start — great first impression."
    ],
    [
        'name' => 'Stephen T.',
        'comment_text' => "The search filters are incredibly detailed — you can find things here that simply aren't available anywhere else. An essential tool for anyone seriously looking to buy in BC."
    ],
];

// Generate initials and a deterministic colour for each reviewer
$bcch_palette = ['#1a6baa','#c0392b','#27ae60','#8e44ad','#d35400','#2c3e50','#16a085','#c0392b','#2980b9','#e67e22'];
@endphp

{{-- ===== STYLES ===== --}}
<style>
.bcch-rev-wrap { font-family: Arial, sans-serif; width: 100%; box-sizing: border-box; }

/* Header bar */
.bcch-rev-header {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
    background: #fff; border-radius: 10px 10px 0 0;
    padding: 18px 24px; border-bottom: 1px solid #ececec;
}
.bcch-rev-rating-block { display: flex; align-items: center; gap: 10px; }
.bcch-rev-stars-big { color: #f9c000; font-size: 22px; letter-spacing: 2px; }
.bcch-rev-score { font-size: 26px; font-weight: 700; color: #222; line-height: 1; }
.bcch-rev-count { font-size: 13px; color: #666; }
.bcch-rev-google-badge {
    display: flex; align-items: center; gap: 6px; font-size: 12px; color: #555;
}
.bcch-rev-google-g {
    width: 20px; height: 20px; border-radius: 50%;
    background: linear-gradient(135deg, #4285F4 25%, #EA4335 25% 50%, #FBBC05 50% 75%, #34A853 75%);
    display: inline-block; flex-shrink: 0;
}
.bcch-rev-write-btn {
    display: inline-block; padding: 8px 18px; border-radius: 5px;
    background: #1a6baa; color: #fff !important; font-size: 13px; font-weight: 600;
    text-decoration: none !important; white-space: nowrap;
    transition: background 0.2s;
}
.bcch-rev-write-btn:hover { background: #155a90; }

/* Carousel */
.bcch-rev-carousel-outer {
    position: relative; background: #f7f8fa;
    border-radius: 0 0 10px 10px; overflow: hidden;
    padding: 0 44px;
}
.bcch-rev-viewport {
    overflow: hidden; width: 100%;
}
.bcch-rev-track {
    display: flex; transition: transform 0.45s cubic-bezier(0.25,0.46,0.45,0.94);
    align-items: stretch;
}
.bcch-rev-card {
    flex: 0 0 calc(33.333% - 16px);
    margin: 16px 8px;
    background: #fff; border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    padding: 20px 18px; box-sizing: border-box;
    display: flex; flex-direction: column;
    transition: box-shadow 0.25s, transform 0.25s;
    min-width: 220px;
}
.bcch-rev-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.13); transform: translateY(-3px); }
.bcch-rev-card-top { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
.bcch-rev-avatar {
    width: 42px; height: 42px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 16px; color: #fff; flex-shrink: 0;
}
.bcch-rev-name { font-weight: 700; font-size: 14px; color: #222; line-height: 1.2; }
.bcch-rev-stars { color: #f9c000; font-size: 14px; letter-spacing: 1px; margin-top: 2px; }
.bcch-rev-text { font-size: 13px; color: #444; line-height: 1.6; flex: 1; }

/* Nav buttons */
.bcch-rev-nav {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 36px; height: 36px; border-radius: 50%;
    background: #fff; border: 1px solid #ddd; cursor: pointer;
    font-size: 20px; color: #333; line-height: 1;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12); z-index: 2;
    transition: background 0.2s, box-shadow 0.2s;
}
.bcch-rev-nav:hover { background: #1a6baa; color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.18); }
.bcch-rev-prev { left: 4px; }
.bcch-rev-next { right: 4px; }

/* Dots */
.bcch-rev-dots { display: flex; justify-content: center; gap: 6px; padding: 12px 0 18px; }
.bcch-rev-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #ccc; cursor: pointer; transition: background 0.2s, transform 0.2s;
    border: none; padding: 0;
}
.bcch-rev-dot.active { background: #1a6baa; transform: scale(1.3); }

/* Footer link */
.bcch-rev-footer-link {
    text-align: center; padding: 10px 0 4px;
    font-size: 13px;
}
.bcch-rev-footer-link a { color: #1a6baa; text-decoration: none; }
.bcch-rev-footer-link a:hover { text-decoration: underline; }

/* Responsive */
@media (max-width: 900px) {
    .bcch-rev-card { flex: 0 0 calc(50% - 16px); }
}
@media (max-width: 580px) {
    .bcch-rev-carousel-outer { padding: 0 36px; }
    .bcch-rev-card { flex: 0 0 calc(100% - 16px); }
    .bcch-rev-header { padding: 14px 16px; }
    .bcch-rev-score { font-size: 22px; }
    .bcch-rev-stars-big { font-size: 18px; }
}
</style>

{{-- ===== WIDGET ===== --}}
<div class="bcch-rev-wrap" id="bcch-rev-widget">

    {{-- Header --}}
    <div class="bcch-rev-header">
        <div class="bcch-rev-rating-block">
            <div class="bcch-rev-stars-big">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <div>
                <div class="bcch-rev-score">4.8 <span style="font-size:16px;font-weight:400;color:#666;">/ 5</span></div>
                <div class="bcch-rev-count">700+ Google Reviews</div>
            </div>
            <div class="bcch-rev-google-badge">
                <span class="bcch-rev-google-g"></span>
                Google
            </div>
        </div>
        <a href="https://g.page/r/CTXNKuXtCvSMEAE/review" target="_blank" rel="noopener" class="bcch-rev-write-btn">&#9998; Write a Review</a>
    </div>

    {{-- Carousel --}}
    <div class="bcch-rev-carousel-outer">
        <button class="bcch-rev-nav bcch-rev-prev" id="bcchRevPrev" aria-label="Previous reviews">&#8249;</button>
        <div class="bcch-rev-viewport" id="bcchRevViewport">
            <div class="bcch-rev-track" id="bcchRevTrack">
                @foreach($custom_reviews as $_i => $_review)
                @php
                    $_parts = array_slice(explode(' ', $_review['name']), 0, 2);
                    $_initials = '';
                    foreach ($_parts as $_part) { $_initials .= strtoupper(substr($_part, 0, 1)); }
                    $_color = $bcch_palette[$_i % count($bcch_palette)];
                @endphp
                <div class="bcch-rev-card">
                    <div class="bcch-rev-card-top">
                        <div class="bcch-rev-avatar" style="background:{{ $_color }};">{{ $_initials }}</div>
                        <div>
                            <div class="bcch-rev-name">{{ $_review['name'] }}</div>
                            <div class="bcch-rev-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                        </div>
                    </div>
                    <div class="bcch-rev-text">{{ $_review['comment_text'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
        <button class="bcch-rev-nav bcch-rev-next" id="bcchRevNext" aria-label="Next reviews">&#8250;</button>
    </div>

    {{-- Dots --}}
    <div class="bcch-rev-dots" id="bcchRevDots"></div>

    {{-- Footer link --}}
    <div class="bcch-rev-footer-link">
        <a href="/reviews">Read all client reviews &rarr;</a>
    </div>

</div>

@php
/* JSON-LD output via PHP to avoid Blade processing @ symbols */
$_gp = \Illuminate\Support\Facades\Cache::get('google_place_summary', [
    'rating'             => 4.8,
    'user_ratings_total' => 709,
]);
$_jsonld = [
    '@context' => 'https://schema.org',
    '@type'    => 'RealEstateAgent',
    'name'     => 'Hani & Les | BC Condos And Homes',
    'url'      => 'https://www.bccondosandhomes.com',
    'telephone'=> '+16042293342',
    'address'  => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => '300 - 1195 W Broadway',
        'addressLocality' => 'Vancouver',
        'addressRegion'   => 'BC',
        'postalCode'      => 'V6H 3X5',
        'addressCountry'  => 'CA',
    ],
    'aggregateRating' => [
        '@type'       => 'AggregateRating',
        'ratingValue' => (string) ($_gp['rating'] ?? '4.8'),
        'reviewCount' => (string) ($_gp['user_ratings_total'] ?? '709'),
        'bestRating'  => '5',
        'worstRating' => '1',
    ],
    'review' => [
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Sandra M.'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'Les and his team helped us sell our Burnaby condo in just 5 days at asking price. Their network of registered buyers is real — we had multiple showings within 24 hours of listing.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Grace Ngo'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>"I'm a first-time buyer looking for a local expert in the Vancouver market. A heartfelt thank you to Les for taking the initiative to correspond with me. He and his team have been very responsive and helpful."],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Emily Yang'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'An excellent website. You can find the most comprehensive online source for condos and townhouses — building age, construction info, unit counts, and more.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Walter Belasic'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'Hani & Les BC Condos And Homes website is the BEST! Easy to navigate, wealth of info, past sales with pics — your research is made easy enabling real-time decision making.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Michael P.'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'We listed with Hani & Les after two disappointing experiences with other agents. They priced our townhouse correctly from the start and negotiated a fantastic result.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'dina al-kassim'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'This website offers more transparency than others. BCCondosAndHomes shows price histories, price per sqft, scalable maps, and more. As a first-time buyer I benefited from the deeper dive into the facts.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Sean Gill'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'The website is very informative and contains a plethora of information. My interactions were mainly with Les and he is dialed in to assist anyone who comes his way.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'David C.'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'The building strata info on this site saved me from making a bad purchase — I could see the depreciation reports and pet restrictions before even contacting an agent.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Jennifer K.'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'As out-of-province sellers we needed a team we could trust completely. Les walked us through every step, and the digital marketing campaign they ran for our condo was impressive. Sold above asking.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'William Marzoque'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'The easiest tool to search properties in BC I have ever seen — you can add filters, compare historical data and assess precious details pulled from the MLS database.'],
    ],
];
echo '<script type="application/ld+json">'.json_encode($_jsonld, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
@endphp

{{-- ===== CAROUSEL JS ===== --}}
<script>
(function() {
    var viewport = document.getElementById('bcchRevViewport');
    var track    = document.getElementById('bcchRevTrack');
    var dotsEl   = document.getElementById('bcchRevDots');
    var prevBtn  = document.getElementById('bcchRevPrev');
    var nextBtn  = document.getElementById('bcchRevNext');
    if (!track || !viewport) return;

    var cards = track.querySelectorAll('.bcch-rev-card');
    var total = cards.length;
    var currentIdx = 0;
    var autoTimer  = null;
    var INTERVAL   = 4500;

    function getVisible() {
        var w = viewport.offsetWidth;
        if (w >= 900) return 3;
        if (w >= 580) return 2;
        return 1;
    }

    function pageCount() {
        return Math.ceil(total / getVisible());
    }

    function goTo(idx) {
        var visible = getVisible();
        var pages   = pageCount();
        if (idx < 0) idx = pages - 1;
        if (idx >= pages) idx = 0;
        currentIdx = idx;

        var cardW    = cards[0].offsetWidth + 16;
        var offset   = currentIdx * visible * cardW;
        var maxOffset = (total - visible) * cardW;
        if (offset > maxOffset) offset = maxOffset;
        track.style.transform = 'translateX(-' + offset + 'px)';

        dotsEl.querySelectorAll('.bcch-rev-dot').forEach(function(d, i) {
            d.classList.toggle('active', i === currentIdx);
        });
    }

    function buildDots() {
        dotsEl.innerHTML = '';
        var pages = pageCount();
        for (var i = 0; i < pages; i++) {
            (function(i) {
                var d = document.createElement('button');
                d.className = 'bcch-rev-dot' + (i === 0 ? ' active' : '');
                d.setAttribute('aria-label', 'Go to page ' + (i+1));
                d.addEventListener('click', function() { goTo(i); resetAuto(); });
                dotsEl.appendChild(d);
            })(i);
        }
    }

    function startAuto() {
        autoTimer = setInterval(function() { goTo(currentIdx + 1); }, INTERVAL);
    }
    function stopAuto()  { clearInterval(autoTimer); }
    function resetAuto() { stopAuto(); startAuto(); }

    prevBtn.addEventListener('click', function() { goTo(currentIdx - 1); resetAuto(); });
    nextBtn.addEventListener('click', function() { goTo(currentIdx + 1); resetAuto(); });

    var widget = document.getElementById('bcch-rev-widget');
    if (widget) {
        widget.addEventListener('mouseenter', stopAuto);
        widget.addEventListener('mouseleave', startAuto);
    }

    window.addEventListener('resize', function() {
        buildDots();
        goTo(0);
    });

    buildDots();
    goTo(0);
    startAuto();
})();
</script>
