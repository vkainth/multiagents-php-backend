@extends('frontend.layouts.default')
@section('content')
@include('frontend.includes.header')
<div id="content" class="container">
    <iframe id="wmhwForm" src="https://docs.google.com/forms/d/e/1FAIpQLScfNlRSa8f_aib1e2PqZ4QUBrU-izqVXfP0CBaL6TEQcVgFMw/viewform" frameborder="0" style="width: 100%;height: 100%;min-height: 80vh;margin:64px auto;"></iframe>
    @guest
    <div style="text-align:center;padding:12px 0 4px;">
        <button id="wmhwDoneBtn" type="button"
            style="background:transparent;border:none;color:#2c6fad;font-size:13px;cursor:pointer;text-decoration:underline;">
            I just submitted the form — what's next?
        </button>
    </div>
    @endguest
</div>
{{-- CTA strip: guest-only, revealed after user scrolls to the bottom of the form --}}
@guest
<div id="wmhwAlertStrip" style="display:none;">
    <div class="container" style="padding:0 0 36px;">
        @include('frontend.includes.alert_cta_strip', [
            'stripContext'    => 'Metro Vancouver',
            'stripHeading'    => 'Also Looking to Buy? Get Listing Alerts',
            'stripSubtext'    => 'While we prepare your home valuation, set up instant alerts so you never miss a new listing that matches your criteria.',
            'stripSearchName' => 'Metro Vancouver Listings',
            'stripSearchData' => json_encode(['listing_status' => 'Active']),
            'stripBtnText'    => 'Get Listing Alerts',
            'stripModalId'    => 'wmhwAlert',
        ])
    </div>
</div>
@endguest
@include('frontend.includes.footer')
@endsection
@push('after-scripts')
{{-- change-following-before-publishing: --}}
@if(Route::is('test*'))
{{-- not-to-include usr-adtnl-scripts --}}
@else
@include('frontend.includes.user_additional_scripts')
@endif
@guest
<script>
(function(){
    var strip = document.getElementById('wmhwAlertStrip');
    if (!strip) return;
    var shown = false;
    function showStrip() {
        if (shown) return;
        shown = true;
        strip.style.display = 'block';
        strip.style.animation = 'bcSlideUp .3s ease';
        strip.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    // Detect Google Form submission: the iframe fires a second load event
    // when the thank-you page replaces the form (cross-origin, but load fires)
    var iframe = document.getElementById('wmhwForm');
    var loadCount = 0;
    if (iframe) {
        iframe.addEventListener('load', function() {
            loadCount++;
            if (loadCount >= 2) {
                // Second load = form submitted successfully — show CTA
                showStrip();
            }
        });
    }
    // Explicit "I submitted" affordance for users who submitted but iframe load didn't fire
    var doneBtn = document.getElementById('wmhwDoneBtn');
    if (doneBtn) {
        doneBtn.addEventListener('click', function() { showStrip(); });
    }
})();
</script>
@endguest
@endpush
