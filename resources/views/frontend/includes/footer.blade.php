@can('dev-dj')
{{Debugbar::info($listing??'no-listing-obj',$building??'no-building-obj')}}
{{Debugbar::info('showStaged_swtichStatus: '.((Helper::showStaged()??'not-set')?'true':'false') )}}
<style>div.phpdebugbar {z-index: 2147483105 !important;}</style>
@endcan
@if(request()->input('expid','bad-default')=='239487982t3kjsydgfiuw32476dfsg')
<footer class="">
    <div class="col-sm-8 col-sm-offset-1 col-xs-6 container footer__information">
        <div class="">
            <p><a href="/terms-and-conditions" target="_blank">Terms & Conditions</a> · <a href="/privacy-policy" target="_blank">Privacy Policy</a> </p>
            <p><img src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.svg" title="Hani & Les | BC Condos And Homes" alt="Hani & Les | BC Condos And Homes" loading="lazy"></p>
        </div>
        <div class="footer__contact-info">
            <p class="footer__address">Re/Max Crest Realty<br/>Re/Max Crest Realty<br/>300 - 1195 W Broadway<br>Vancouver, BC V6H 3X5</p>
            <div class="footer__contact">
                Phone: <a href="tel:6042657975">604-265-7975</a><br>
                Email: <a href="mailto:info@bccondosandhomes.com">info@bccondosandhomes.com</a>
            </div>
        </div>
    </div>
    <div class="col-sm-2 col-xs-6 footer__information">
        <p> <a href="https://www.fisherly.com/" target="_blank">Powered By</a> </p>
        <p><img src="https://www.fisherly.com/vow/images/fisherly-orange-gray.svg" title="Fisherly" alt="Fisherly" loading="lazy"></p>
    </div>
</footer>
{{-- @elseif(auth()->user()?->can('dev-dj-approve')) --}}
{{-- [published on:14-09-2022] --}}
@elseif( true )

{{-- Social-proof strip --}}
<div style="background:#1a1a2e;color:#fff;padding:14px 0;text-align:center;font-family:Arial,sans-serif;font-size:14px;">
    <div class="container" style="display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:18px;">
        <span style="color:#f9c000;font-size:18px;letter-spacing:2px;">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
        <span style="font-weight:600;">4.8 / 5 &nbsp;&mdash;&nbsp; 700+ Google Reviews</span>
        <a href="/reviews" style="color:#f9c000;font-weight:600;text-decoration:none;border:1px solid #f9c000;border-radius:4px;padding:4px 14px;font-size:13px;white-space:nowrap;">Read Reviews &rarr;</a>
    </div>
</div>

<footer class="ftr-links-wrap-style" style="border-top: 1px solid #e1d6d6;padding: 100px 0;padding:20px 0 100px;font-size: 13.2px;font-family: Arial;">
    <div class="container" style="padding:0 !important; " >
        <div style="display: flex;padding:0;flex-wrap: wrap;justify-content: space-between;">
            <div class="ftr-col1 footer-flex" style="min-width: 430px; font-size: 10pt; font-family: arial; font-size: 13.3px; ">
                <div style="padding:8px 0;"> <img class="" src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.jpg" title="Hani & Les | BC Condos And Homes" alt="Hani & Les | BC Condos And Homes" loading="lazy" width="" height="" style="width: 328px; "> </div>
                <div class=""><img class="" src="https://www.bccondosandhomes.com/assets/img/REMAX_Residential.svg" title="REMAX" alt="REMAX" loading="lazy" width="" height="" style="width: 200px; "></div><br/>
                <div class="">Re/Max Crest Realty<br/>300 - 1195 W Broadway, Vancouver, BC V6H 3X5</div>
                <div class=""><a href="tel:6042657975" style="font-weight: bold;">+1 604-265-7975</a> | <a href="mailto:info@bccondosandhomes.com">info@bccondosandhomes.com</a>
                </div>
                <div>Powered by <a href="https://pixilink.com">Pixilink</a> / <a href="https://www.fisherly.com">Fisherly</a></div>
            </div>
            @if(false)
            <div class="ftr-col2 footer-flex" style="min-width: 210px;justify-content:flex-start;">
                {{-- <div> <a href="#">Careers</a> </div> --}}
                {{-- <div> <a href="#">Become a REALTOR®</a> </div> --}}
                {{-- <div> <a href="#">Contact</a> </div> --}}
                <div> <a href="/terms-and-conditions">Terms &amp; Conditions</a> </div>
                <div> <a href="/privacy-policy">Privacy Policy</a> </div>
                {{-- <div> <a href="#">Sitemap</a> </div> --}}
            </div>
            @else
            <div class="ftr-col2 footer-flex" style="min-width: 210px;">
                <div> <a href="/terms-and-conditions">Terms &amp; Conditions</a> </div>
                <div> <a href="/privacy-policy">Privacy Policy</a> </div>
                <div> <a href="/statistics">Market Insights</a> </div>
                <div> <a href="/market-report">Market Reports</a> </div>
                <div> <a href="/neighbourhood">Neighbourhood Guides</a> </div>
                <div> <a href="/houses">House Market</a> </div>
                <div> <a href="{{route('sell.html')}}">Team</a> </div>
                <div> <a href="{{route('sellers-guide')}}">Seller's Guide</a> </div>
                <div> <a href="{{route('buyers-guide')}}">Buyer's Guide</a> </div>
                <div> <a href="{{route('featured-listings')}}">Featured</a> </div>
                <div> <a href="{{route('our-solds')}}">Recent Solds</a> </div>
                {{-- <div> <a href="{{route('landing')}}">Search</a> </div> --}}
                {{-- <div> <a href="{{route('show_favorite_listings')}}">Favorites</a> </div> --}}
                {{-- <div> <a href="{{route('news-blog-list')}}">News</a> </div> --}}
                {{-- <div> <a href="https://docs.google.com/forms/d/e/1FAIpQLScfNlRSa8f_aib1e2PqZ4QUBrU-izqVXfP0CBaL6TEQcVgFMw/viewform">Home Evaluation</a> </div> --}}
            </div>
            @endif
            <div class="ftr-col3 footer-flex" style="/*min-width: 530px;*/ max-width: 530px;">
                <div>
                    @if(false)
                    <div class="pull-right  social-icons">
                        <a href="#"><i class="fa fa-facebook-official"></i></a>
                        <a href="#"><i class="fa fa-linkedin"></i></a>
                        <a href="#"><i class="fa fa-instagram"></i></a>
                        <a href="#"><i class="fa fa-youtube-play"></i></a>
                    </div>
                    @endif
                    <div style="font-family: 'Roboto';font-weight: bolder;margin:0;font-size: 24px;margin-bottom: 22px;">How Can We Help?</div>
                </div>
                <p>
                    <a class="btn btn-default bcch-btn bcch-color-cyan" href="https://docs.google.com/forms/d/e/1FAIpQLSfjrPZZdRnUlJcmSQ6qpR6WkkfExSWTeFNpCsNqzS2GPzSoFg/viewform">Buy With Hani & Les | BC Condos And Homes</a> &nbsp;
                    <a class="btn btn-default bcch-btn bcch-color-gold" href="/home-evaluation">What is My Home Worth?</a>
                </p>
                <br>
                <div class="small" style="font-size:xx-small;"><strong>Disclaimer:</strong> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy.</div>
            </div>
        </div>
    </div>
    @if(!empty($__env->yieldPushContent('post-footer-html') ))
    <div class="container" style="">
        @stack('post-footer-html')
    </div>
    @endif
</footer>
<style>
footer .social-icons a{margin:0 0.5em;}
.footer__contact-info, .footer__information{text-align: left;} 
.ftr-links-wrap-style a{color: #333;}
.ftr-lnht15{line-height: 1.5;font-size: 13px;}
.footer-flex {display: inline-flex;flex-direction: column;min-height: 170px;padding: 10px 30px;justify-content: space-between;}
footer a.bcch-btn.btn {padding: 0.5em 24px; font-size: 13.2px; font-family: arial, sans-serif; }

@media (max-width: 480px) {
.ftr-col1.footer-flex{min-width: 360px !important;} 
.ftr-col1.footer-flex img{width: 260px !important;} 
.ftr-col3.footer-flex{max-width: 360px !important;} 
}
</style>
{{-- <style bcch-vars> Moved to header_common </style> --}}
@elseif(false)
{{-- 
<footer class="container-fluid ftr-links-wrap-style" style="border-top: 1px solid #e1d6d6; padding-top: 20px;">
<div class="container" style="padding:0;">
        
    <div class="col-lg-4 col-sm-6 container ftr-lnht15">
        <div class="footer__contact-infoXX small" style="">
            <div> <img class="ftr-bcch-logo" src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.jpg" title="Hani & Les | BC Condos And Homes" alt="Hani & Les | BC Condos And Homes" loading="lazy" width="250" height="70" style="margin-left: -16px; margin-top: -20px; " /> </div>
            <div class="footer__addressXX">300 - 1195 W Broadway, Vancouver, BC V6H 3X5</div>
            <div class="footer__contactXX"><a href="tel:6042657975" style="font-weight: bold;">+1 604-265-7975</a> | <a href="mailto:info@bccondosandhomes.com">info@bccondosandhomes.com</a>
            </div>
            <div>Powered by Pixilink / Fisherly</div>
        </div>

    </div>
    <div class="col-lg-2  text-lg-justified  ftr-lnht15 ftr-links-wrap-style small" style="border: none; ">
        <div> <a href="#">Careers</a> </div>
        <div> <a href="#">Become a REALTOR®</a> </div>
        <div> <a href="#">Contact</a> </div>
        <div> <a href="/terms-and-conditions">Terms &amp; Conditions</a> </div>
        <div> <a href="/privacy-policy">Privacy Policy</a> </div>
        <div> <a href="#">Sitemap</a> </div>
    </div>
    <div class="col-lg-6 col-xs-12" style="border: none; ">
        <p> 
            <div class="pull-right  social-icons">
                <a href="#"><i class="fa fa-facebook-official"></i></a>
                <a href="#"><i class="fa fa-linkedin"></i></a>
                <a href="#"><i class="fa fa-instagram"></i></a>
                <a href="#"><i class="fa fa-youtube-play"></i></a>
            </div>
            <div style="font-weight:bold; margin:0;">How Can We Help?</div>  
        </p>
        <p>
            <a class="btn btn-default bcch-btn bcch-color-cyan">Buy With Hani & Les | BC Condos And Homes</a> &nbsp;
            <a class="btn btn-default bcch-btn bcch-color-gold" href="/home-evaluation">What is My Home Worth?</a>
        </p>
        <br>
        <p class="small" style="font-size:xx-small;"><strong>Disclaimer:</strong> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy.</p>
    </div>
    <!--
    <div class="footer__information col-xs-12" style="border: none;/*padding-left:15px*/ ">
        <div class="col-xs-12"> <a href="https://www.fisherly.com/" target="_blank">Powered By</a> </div>
        <div class="">
            <img src="https://www.bccondosandhomes.com/frontend/images/pixilink-logo.svg" title="Pixilink" alt="Pixilink Solutions" loading="lazy" width="120" height="60" style="">
            <img src="https://www.bccondosandhomes.com/frontend/images/fisherly-orange-gray.svg" title="Fisherly" alt="Fisherly" loading="lazy" width="120" height="60" style="border-left:1px solid #000; padding-left:10px">
        </div>
    </div>
    -->
</div>
</footer>
<style>
footer .social-icons a{margin:0 0.5em;}
.footer__contact-info, .footer__information{text-align: left;} 
.ftr-links-wrap-style a{color: #333;}
.ftr-lnht15{line-height: 1.5em;}
.ftr-bcch-logo{margin-left: -16px; margin-top: -20px;}
</style>
<style>
:root{
--bcch-cyan:#337ab7;
--bcch-gold:#dcac1c;
}
.bcch-btn{border: 1px solid !important; border-radius: 4px; }
.bcch-red{color: #df4611;}

.bcch-color-cyan{color: var(--bcch-cyan) !important; }
.bcch-color-gold{color: var(--bcch-gold) !important; }

.bcch-bg-cyan{background-color: var(--bcch-cyan);}
.bcch-bg-golden{background-color:var(--bcch-gold);}
</style>
 --}}
@else
<footer>
    <div class="container">
        <div class="footer__information">
            <p><a href="/terms-and-conditions" target="_blank">Terms & Conditions</a> &#183; <a href="/privacy-policy" target="_blank">Privacy Policy</a> {{--| a project by &copy; Pixilink Solutions {{date('Y')}}--}}</p>
            <p><img src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.svg" title="Hani & Les | BC Condos And Homes" loading="lazy" alt="Hani & Les | BC Condos And Homes" /></p>
        </div>
        <div class="footer__contact-info">
            <p class="footer__address">Re/Max Crest Realty<br/>300 - 1195 W Broadway<br>Vancouver, BC V6H 3X5</p>
            <div class="footer__contact">
                Phone: <a href="tel:6042657975">604-265-7975</a><br>
                Email: <a href="mailto:info@bccondosandhomes.com">info@bccondosandhomes.com</a>
            </div>
        </div>
    </div>
</footer>
@endif


@can('dev-dj-approve')
<script src="/d1/ext-pixi-team-test.js?tknsdle=98324ljksdf&reqtm={{date('his')}}"></script>
<script src="https://v2.bccondosandhomes.com/d1/ext-recent-visitors.js?tknsdle=s83i64jhg6&reqtm={{date('his')}}"></script>
@endcan