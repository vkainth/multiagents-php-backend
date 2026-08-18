@extends('frontend.layouts.default')
@section('title')About Hani & Les | BC Condos And Homes @endsection
@section('meta_description')Sell your home, condo or a town house and get a 60 day interest free loan of $25,000 with Hani & Les | BC Condos And Homes @endsection
@section('meta')
    @if(request()->get('og_tags'))
    {!!request()->get('og_tags')!!}
    @endif
@endsection
@section('content')
@if(Auth::user())
@include('frontend.includes.header')
@else
@include('frontend.includes.header_realtorpage')
@endif


<div class="main about__main" role="main">

        <div class="container">
                <div class="row">
                        <div class="col-md-12">
                                <h1>About Us</h1>
                        </div>
                </div>
                <div class="row margin-bottom">
                        <div class="col-md-5 col-sm-6 col-xs-12">
                                <img src="{{asset('frontend/images/team.jpg')}}" style="width: 100%;">
                </div>
                <div class="col-md-7 col-sm-6 col-xs-12">
                        <div class="about__main--realtor">
                                        <h2>Les Twarog</h2>
                                        <p>Phone: <a href="tel:6046717000">604.671.7000</a><br>
                                        Email: <a href="mailto:les@6717000.com">les@6717000.com</a>
                                        </p>
                                </div>
                                <div class="realtor__quote">
                                <p>
                                        <q>Seasoned, experienced, this gentleman makes things happen. 4 days to sell and was very strategic in his methods. Surprisingly received much more than the asking price. Thank you very much Mr. Les Twarog.</q> <br> <strong>Daniel Brix</strong>
                                </p>
                        </div>
                        </div>
                </div>
                <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                                <p>Les Twarog is a top performing residential realtor with over 30 years of experience in the dynamic Vancouver market. Specializing in luxury West Side and downtown real estate, Les has been consistently ranked among the highest 1-5% of Vancouver’s 14,000 realtors and has been in the top 100 realtors of RE/MAX of Western Canada. When it comes to Vancouver real estate, few people are more experienced or have more intimate knowledge of the marketplace than Les Twarog.</p>
                                <p>Supported by an unsurpassed team of results-driven professionals, Les has a proven track record in high-end homes and condos, and in particular, elegant Shaughnessy properties—widely considered to be Vancouver’s most prestigious neighbourhood. </p>
                                <p>Les’ vision and drive has enabled him to create the ultimate online resource for Vancouver real estate—The Twarog Group of Realty Web Sites (lestwarog.com).</p>
                                <p>His eight specialty websites are a one-stop shop for all your real estate needs, providing buyers and sellers with a wealth of information on over 13,000 buildings and properties. As the single most comprehensive source of information on Vancouver real estate, the site includes MLS® listings, floor plans, maps, photos, rentals and detailed sales history. This rich source of up-to-date information is indispensible to the real estate decision-making process. You will not need to go anywhere else.</p>
                                <p>Les will provide you with a comprehensive marketing strategy and broad exposure to meet your real estate needs. His team includes associates who are fluent in Mandarin to support Chinese clients and the web portal features Juwai—an online resource that helps Chinese buyers find international property. This site is visited by thousands of Chinese buyers each day from across China, Taiwan, Hong Kong and Singapore. Les Twarog is an accomplished and highly respected Vancouver realtor with the ability and commitment to get the job done for his clients in a highly competitive environment. Call him today to discuss your real estate needs. </p>
                                <p>When his co-workers heard what he was about to do, they thought he was crazy. His mother Cecilia even pleaded with him repeatedly not to do it because the majority who do ultimately fail within the first three years. Then his boss warned, "In six months you'll be begging me to give you back your job!" But in spite of it all, nothing could deter Les Twarog. His mind was already made up. So, early in 1988, Les gave up his secure position at Canada Safeway Limited to begin a career in real estate sales—a field with no guarantees. Gone were the six-week paid annual holidays, company pension and benefits, and most importantly, those regular pay cheques.</p>
                                <p>Then an interesting thing happened. Six months later, Les returned to the Safeway store where he had been employed, not to ask for his old job back, but simply to say hello and let his former boss and colleagues know how things had worked out for him. You can just imagine the looks on their faces when he stepped out of a large, upscale automobile. Les had become very successful in a short period of time and acquired the car to service his real estate clients in complete safety and comfort. But this was only the beginning.</p>
                                <p>Les achieved his initial success working for a small local company called Multiple Realty. This led to him establishing his own real estate firm specializing in condo conversions—taking existing stratified rental buildings owned by a single person and selling them to individual owners. His experience and drive enabled him to sell an average of 250 units annually for several years. Not to be deterred by the 1993 condo market downturn in Vancouver, Les honed his innovative ideas and creative marketing style which propelled him into the 50 top producing realtors in the Lower Mainland—ranking among the top 1% of all realtors nationally. He reached a point where he was completing over 60 real estate transactions per year—nearly 17 times the industry average. It's an achievement Les has repeated every year since, despite fluctuating market conditions. He attributes his success to superior market knowledge, keen negotiating skills and the support of an outstanding team of professionals. "The strength of my team allows me to focus all of my attention on finding the right properties and qualified buyers for my clients. The result is better service and faster turnarounds.</p>
                                <p>" With Les it doesn't matter whether you're interested in buying or selling a detached home, condominium or an entire building complex, you are guaranteed the same high quality service.</p>
                                <p style="font-style: italic;">"Call him today to discuss your real estate needs."</p>

                                <div class="listing-detail__agent-testimonials">
                                <button class="listing-detail__agent-button" type="button" onclick="window.open('https://rankmyagent.com/lestwarog/','_blank')">View Testimonials</button>
                        </div>

                        </div>
        </div>
        </div>

</div>

<div class="container" style="padding:40px 15px 50px;">
    <div class="row">
        <div class="col-md-8 col-md-offset-2 col-sm-10 col-sm-offset-1">
            <div id="bc-home-eval-about"></div>
            <script src="{{ asset('widget/home-evaluation.js') }}"
                data-placement="inline"
                data-target="#bc-home-eval-about">
            </script>
        </div>
    </div>
</div>

@include('frontend.includes.footer_links')

<footer>
    <div class="container">
        <div class="footer__information">
                <p><a href="/terms-and-conditions" target="_blank">Terms & Conditions</a> &#183; <a href="/privacy-policy" target="_blank">privacy policy</a> {{--| a project by &copy; Pixilink Solutions {{date('Y')}}--}}</p>
            <p><!--<span>powered by</span>--><img src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.svg" alt="Hani & Les | BC Condos And Homes Logo Footer" loading="lazy" alt="Hani & Les | BC Condos And Homes" /></p>
        </div>
        <div class="footer__contact-info">
                <p class="footer__address">300 - 1195 W Broadway<br>Vancouver, BC V6H 3X5</p>
                <div class="footer__contact">
                        Phone: <a href="tel:6042657975">604-265-7975</a><br>
                        Email: <a href="mailto:info@bccondosandhomes.com">Info@bccondosandhomes.com</a>
                </div>
        </div>
    </div>
</footer>

<style>
        .main.about__main {
                padding: 70px 0 0;
                font-size: 17px;
        }
        .main.about__main h1 {
                font-weight: 700;
        }
        .main.about__main h2 {
                margin-top: 30px;
                margin-bottom: 10px;
                font-size: 30px;
        line-height: 1em;
        font-family: Circular,-apple-system,BlinkMacSystemFont,Roboto,Helvetica Neue,sans-serif!important;
        font-weight: 400;
        }
        .main.about__main .margin-bottom {
                margin-bottom: 20px;
        }
        .realtor__quote {
                font-style: italic;
                margin: 60px 0 0;
        }
        .realtor__quote p {
        margin: 0;
        }
        .main.about__main q:before {
        content: '\201C';
        font-size: 35px;
        float: left;
        margin: -15px 0 0 -20px;
        position: absolute;
        color: #b0b0b0;
        }
        .main.about__main q:after{
                margin-top: -10px;
        content: '\201D';
        font-size: 35px;
        position: absolute;
        color: #b0b0b0;
        }
        .listing-detail__agent-testimonials button {
                width: auto;
                margin: 25px 0 0;
        }
        @media(max-width:1199px) and (min-width: 992px) {
                .realtor__quote {
                        margin: 30px 0 0;
                }
                .main.about__main h2 {
                        margin-top: 20px;
                }
        }
        @media(max-width:991px) and (min-width: 767px) {
                .realtor__quote {
                        margin: 5px 0 0;
                }
                .main.about__main h2 {
                        margin-top: 5px;
                }
        }
        @media(max-width:767px) {
                .main.about__main h2 {
                        margin-top: 15px;
                }
                .main.about__main .margin-bottom {
                        margin-bottom: 0px;
                }
                .main.about__main .margin-bottom img {
                        margin-bottom: 20px;
                }
                .realtor__quote {
                        margin: 30px 0 30px;
                }
                .main.about__main q:before {
                margin: -15px 0 0 -15px;
                }
                .listing-detail__agent-testimonials button {
                        width: 100%;
                }
        }
</style>

@endsection
@push('after-scripts')
@include('frontend.includes.user_additional_scripts')
@endpush