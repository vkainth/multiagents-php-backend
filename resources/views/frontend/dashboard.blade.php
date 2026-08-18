@extends('frontend.layouts.default')
@section('title')
Listings | Fisherly
@endsection
@push('after-styles')
<link rel="stylesheet" href="{{ asset('frontend/css/bootstrapXL.css')}}">
@endpush
@section('content')
@include('frontend.includes.filter_sidebar')

@php 
$allagents = $user->getAllAgents(); 
$loggedinagent = $user->loginWithAgent()->first();
$totalAgents = count($allagents);
@endphp
@if($user->confirm_agent_addition == 'y' && $loggedinagent->agent_id != config('constants.demo_agent_id'))
<div class="modal fade" id="tandcModal">
<div class="modal-dialog modal-lg">
<div class="modal-content">
  <div class="modal-header">
     <h4 class="modal-title">Confirmation</h4> 
  </div>
  <div class="modal-body">
  <div class="main" role="main" style="text-align: justify">
        <div class="col-md-12">
            
             <p>You are already signed up with the following agent(s):</p>
             <ul>
                 @foreach($allagents as $signedupagent)
                 @if($signedupagent->agent_id == config('constants.demo_agent_id'))
                 <li>Demo</li>
                 @else
             <li>{{$signedupagent->fname}} {{$signedupagent->lname}}</li>
                @endif
                 @endforeach
             </ul>
             <p>Are you sure you want to add <strong>@if($loggedinagent->agent_id != config('constants.demo_agent_id')){{$loggedinagent->fname}} {{$loggedinagent->lname}}@else @if(app('request')->session()->get('name') && app('request')->session()->get('agency')) {{app('request')->session()->get('name') }} @endif @endif</strong> to your agents list?</p>
        </div>
  </div>
</div>
  <div class="clearfix"></div>
  <div class="modal-footer">
  <div class="col-md-8 text-left">
    <button type="button" class="btn btn-primary" id="yesButton" >Yes</button>
    <button type="button" class="btn btn-primary" id="noButton" >No</button>
  </div>
  </div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
@endif
<div class="toggle-button"><i class="fa fa-sliders" aria-hidden="true"></i><span class="hidden-sm hidden-xs">Filter Search</span></div>

<main id="panel" class="panel">
    @include('frontend.includes.header')
    <!--<div class="toggle-button hidden-xs"><i class="fa fa-sliders" aria-hidden="true"></i></div>-->

    <div id="content" class="content full">
        <div class="container-fluid">
            {{-- @include('frontend.includes.filter_topbar') --}}
            {{-- @include('frontend.includes.filter_top') --}}
           
            @php
            $userAgent = $user->loginWithAgent()->first();
            if(!$userAgent){
                $userAgent = $user->agent()->first();
            }
            @endphp
             
            {{-- @if($user->role != 'AGENT' && !$userAgent->isSoldAllowed())
                <div class="alert alert-info">
                    Oops!  Your agents needs to activate you access to Sold Information.  Let us do that for you. <button type="button" class="btn btn-primary">Request Sold Data</button>
                </div>
            @endif --}}
            
            @php
            if(array_key_exists('subareas', $filters) && $filters['subareas'] != ''){
                $selectedSubareas = explode(";",$filters['subareas']);
            }else{
                $selectedSubareas = array();
            }
                
            @endphp
            <div class="city_filters" id="city_filters">
                <div class="quicklinks__city--items clearfix">
                    <div class="swiper-container">
                        <div class="swiper-wrapper">
                            @foreach($subareas as $subarea)
                            <div class="swiper-slide">
                                <div class="quick-swipe__city--name">
                                    <label>
                                    <input type="checkbox" name="subarea[]" value="{{$subarea->place}}" @if(in_array($subarea->place, $selectedSubareas)) checked @endif class="subarea_options">
                                        <span>{{$subarea->label}}</span>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                            @foreach($cities as $city)
                            <div class="swiper-slide">
                                <div class="quick-swipe__city--name">
                                    <label>
                                        <input type="checkbox" name="city[]" value="{{$city->place}}" class="city_options">
                                        <span>{{$city->label}}</span>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <!--<div class="swiper-button-prev"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27 44"><path d="M0,22L22,0l2.1,2.1L4.2,22l19.9,19.9L22,44L0,22L0,22L0,22z"></svg></div>
                        <div class="swiper-button-next"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27 44"><path d="M27,22L27,22L5,44l-2.1-2.1L22.8,22L2.9,2.1L5,0L27,22L27,22z"></svg></div>-->
                    </div>
                    <div class="swiper-button-prev"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27 44"><path d="M0,22L22,0l2.1,2.1L4.2,22l19.9,19.9L22,44L0,22L0,22L0,22z"></svg></div>
                    <div class="swiper-button-next"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27 44"><path d="M27,22L27,22L5,44l-2.1-2.1L22.8,22L2.9,2.1L5,0L27,22L27,22z"></svg></div>
                </div>
            </div>
           
            <div class="row">
                <div class="col-md-12">
                <div class="col-sm-10" id="recordsCountDiv">@if($totalRecords > count($listings) || 1==1)<p>Displaying <span id="listingCount">{{count($listings)}}</span> out of <span id="totalRecords">{{$totalRecords}}</span> properties.</p>@else &nbsp; @endif</div><div class="col-sm-2 text-sm-right" id="clearFilterButton" style="margin-bottom:10px; @if(!isset($filters) || count($filters) == 0 ) display:none; @endif"><a href="{{route('dashboard')}}">Clear Filter</a></div> 
                </div>
            </div>

            <div class="map_view_toggle" style="display:none;">
                <button id="map_view_button">Map View</button>
            </div>
                
            <div class="listing__items">

                    <div class="header__title">

                        <div class="sort__listings"></div>
                    </div>

                    <div class="">
                            <div id="loader" class="loader" style="display:none"><div class="lds-ring"><div></div><div></div><div></div><div></div></div></div>
                    <div class="infinite-scroll">
                            @if(session()->has('message'))
                            <div class="alert alert-info">

                               @if($userAgent->agent_id != config('constants.demo_agent_id')){{$userAgent->fname}} {{$userAgent->lname}} @else @if(app('request')->session()->get('name') && app('request')->session()->get('agency')) {{app('request')->session()->get('name') }} @endif @endif {{ session()->get('message') }}
                               <input id="statusReturned" value="Active" type="hidden">
                               @php session()->forget('message') @endphp
                            </div>
                           @endif
                           @if($user->role == 'AGENT' && $user->email == $userAgent->email && !$userAgent->isSoldAllowed() && $status == 'Sold')
                               <div class="alert alert-info">
                                   Activate your VOW to allow this view to your clients. <a href="https://admin2.pixilink.com/vowactivation" target="_blank">Click here</a> to get started
                               </div>
                           @endif
                               

                            @if(count($listings) > 0)
                        @foreach($listings as $listing)
                        <!-- for each listing -->


                            <div class="col-md-4 col-xl-3 col-xxl-2 col-sm-6">
                            <div class="listing__item">
                                {{-- {{trim(route('listing-detail-page2', ['agentId'=>$loggedinagent->vow_username, 'slug'=>$listing->slug]))}} --}}
                                <div class="listing__item--content">
                                <a href="{{trim(route('listing-detail-page2', ['agentId'=>$loggedinagent->vow_username, 'slug'=>$listing->slug]))}}" class="listing__item--link" >

                                   {{-- url({{$listing->mainpicurl}})--}}
                                <div class="listing__image lazy" data-src="@if($listing->photos->count() > 0) https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos->first()->directory.$listing->photos->first()->name)}}?w=900 @else {{asset('assets/img/no-image.jpg')}} @endif">

                                    {{--<div class="favor__listing"><i class="fa fa-heart"></i></div>--}}


                                    <div class="icons">
                                        <div class="icon__beds clearfix"><i class="fa fa-bed"></i> <span class="number">{{$listing->bedrooms}}</span></div>
                                        <div class="icon__baths clearfix"><i class="fa fa-bath"></i> <span class="number">{{$listing->full_baths+$listing->half_baths}}</span></div>
                                        <div class="icon__photos clearfix"><i class="fa fa-camera"></i> <span class="number">{{$listing->photos->count()}}</span></div>
                                    </div>
                                    {{--<div class="">--}}
                                    {{--<div>Floorplan</div>--}}
                                    {{--<div>Virtual Tour</div>--}}
                                    {{--</div>--}}

                                </div>

                                <div class="listing__content">
                                    <div class="listing__icon pull-left">
                                        <img class="{{strtolower($listing->status)}}" src="{{asset('frontend/icons/'.strtolower($listing->getType()).'-selected.svg')}}" />
                                    </div>
                                    <div class="mls_number pull-right">MLS®: {{$listing->listingid}}</div>
                                    <div class="listing__status {{strtolower($listing->status)}}">{{$listing->status}}</div> <!-- can be active or sold - depends on status of listing -->

                                    {{--{{money_format('%.0n', $listing->soldprice)}}--}}
                                    <div class="listing__price">@if($listing->status == 'Sold'){{money_format('%.0n', $listing->soldprice_2)}}@else {{$listing->listprice}} @endif</div>
                                    <div class="listing__address">
                                        <span class="big">@if($listing->getType() == 'Apartment' && $listing->suite_no){{$listing->suite_no}} - @endif{{$listing->street_number}} {{$listing->street_name}} {{$listing->street_type}}   </span> <br />
                                        {{$listing->subarea}}, {{$listing->city}}, {{$listing->province}}
                                    </div>

                                    <div class="listing__amenities" style="min-height: 44px">
                                        @if($listing->status == 'Sold' && $listing->getSoldPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getSoldPeriod()}} </span> | @elseif($listing->getListingPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getListingPeriod()}} | </span>@endif @if($listing->days_on_market())<span class="{{strtolower($listing->status)}}">{{$listing->days_on_market()}}</span> days on the market |@endif @if($listing->livingarea_2 > 0) SqFt: <span class="{{strtolower($listing->status)}}">{{$listing->livingarea_2}}</span>@endif @if($listing->lotsize > 0)| Lot Size: <span class="{{strtolower($listing->status)}}">{{$listing->lotsize}}</span> SqFt. @endif @if($listing->home_style != '')| {{$listing->home_style}} @endif @if($listing->maintenance && $listing->maintenance > 0)| Strata Fees: <span class="{{strtolower($listing->status)}}">{{money_format('%.0n', $listing->maintenance)}}</span> @endif @if($listing->yearbuilt && $listing->yearbuilt > 0)| Year Built: <span class="{{strtolower($listing->status)}}">{{ $listing->yearbuilt}}</span> @endif
                                    </div>

                                    <div class="listing__listedBy">Listed by: {{$listing->reoffice}}</div>
                                    
                                    {{--<div class="row">--}}
                                    {{--<div class="col-sm-12 col-xs-12">--}}
                                            <div class="listing__item--detail-link {{strtolower($listing->status)}} visible-sm visible-xs">
                                                <a href="{{trim(route('listing-detail-page2', ['agentId'=>$loggedinagent->vow_username, 'slug'=>$listing->slug]))}}"><p>View Details</p></a>
                                               
                                            </div>
                                        {{--</div>--}}
                                        {{--<div class="col-sm-6 col-xs-12">
                                        {{--<div class="listing__item--detail-link {{strtolower($listing->status)}} visible-sm visible-xs">--}}
                                            {{--<a href="{{trim(route('listing-detail-page', $listing->slug))}}"><p>Track Listing</p></a>--}}
                                            {{--</div>--}}
                                        {{--</div>--}}
                                    {{--</div>--}}

                                </div>
                                <div class="listing__item--hover hidden-sm hidden-xs">
                                    <a href="{{trim(route('listing-detail-page2', ['agentId'=>$loggedinagent->vow_username, 'slug'=>$listing->slug]))}}"><p>View Details</p></a>
                                </div>

                                </div>
                                </a>

                            </div>
                        </div>

                        @endforeach
                                @else
                                    <div class="alert alert-info">
                                       {{config('constants.no_listing_found')}}
                                    </div>
                        @endif

                            <div style="clear:both;"></div>
                            {{-- {{$listings->links()}} --}}
                        </div>
                    </div>
                    {{-- <div class="map_view_container hidden_map_view" id="mapView">
                            @include('frontend.includes.map_view')
                    </div>  --}}
                </div> <!-- END LISTINGS -->

            </div> <!-- END ROW -->

        </div> <!-- END CONTAINER -->
        {{-- @php
            $userAgent = Auth::user()->agent()->first();
        @endphp --}}
        <div class="listings-disclaimer">
            <div class="container">
                {{-- <p style="font-size:16px;">{{$userAgent->fname}} {{$userAgent->lname}} - <strong>{{$userAgent->agency}}</strong></p> --}}
                <p><img src="{{asset('frontend/images/fisherly-orange-gray.svg')}}" alt="Fisherly Logo Footer" /></p>
                <p ><strong>Last Update:</strong> {{date( 'd-m-Y', strtotime( $last_update ))}} - <strong>Disclaimer:</strong> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy.</p>
            </div>
        </div>

    </div> <!-- END CONTENT -->
</main> <!-- END MAIN -->
<style>
    .hidden_map_view{
        position: absolute;
        left: -100%;
    }
</style>
@endsection
@push('after-scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.matchHeight/0.7.0/jquery.matchHeight-min.js"></script>
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jscroll/2.4.1/jquery.jscroll.min.js"></script> --}}
    <script type="text/javascript" src="{{asset('frontend/js/jquery.lazy.min.js')}}"></script>

    <script type="text/javascript">
        // $('ul.pagination').hide();
        // $(function() {
        //     $('.infinite-scroll').jscroll({
        //         autoTrigger: true,
        //         loadingHtml: '',
        //         padding: 0,
        //         nextSelector: '.pagination li.page-item:last a',
        //         contentSelector: 'div.infinite-scroll',
        //         callback: function() {
        //             $('ul.pagination').remove();
        //             $('.jscroll-added:last .col-md-4').matchHeight();
        //         }
        //     });
        // });
        $(function() {
            $('.infinite-scroll .col-md-4 .listing__item').matchHeight();
            jQuery('.map_view .listing__item').matchHeight();
            $('.lazy').lazy({
                effect: 'fadeIn',
            });

        });

        jQuery("input.city_options").on('click', function(){
            if(jQuery(this).is(":checked")){
                var city = jQuery(this).val();
                if(!cities.includes(city)) {
                        cities.push(city);
                        regenerateCityButtons();
                        resetCityValues();
                        submitForm();
                        submitClickEvent('city', city);
                }
            }
            else{
                var city = jQuery(this).val();
                removeCity(city.replaceAll(' ','+'));
            }
        });

        jQuery("input.subarea_options").on('click', function(){
            if(jQuery(this).is(":checked")){
                var subarea = jQuery(this).val();
                jQuery(this).parent().parent().addClass("active");
                if(!subareas.includes(subarea)) {
                    subareas.push(subarea);
                    cities = [];
                        regenerateCityButtons();
                        resetCityValues();
                        regenerateSubareaButtons();
                        resetSubareaValues();
                        submitForm();
                        submitClickEvent('subarea', subarea);
                }
            }
            else{
                var subarea = jQuery(this).val();
                removeSubarea(subarea.replaceAll(' ','+'));
                jQuery(this).parent().parent().removeClass("active");
            }
        });

        jQuery("input.subarea_options").each(function(){
            if(jQuery(this).is(":checked")){
                jQuery(this).parent().parent().addClass("active");
            } else {
                jQuery(this).parent().parent().removeClass("active");
            }
        });

        $(document).ready(function(){
            /* Hide and show header on scolling */
            var didScroll;
            var lastScrollTop = 0;
            var delta = 5;
            var navbarHeight = $('header').outerHeight();

            $(window).scroll(function(event) {
                didScroll = true;
            });

            setInterval(function() {
                if (didScroll) {
                    hasScrolled();
                    didScroll = false;
                }
            }, 250);

            function hasScrolled() {
                var st = $(this).scrollTop();
                // Make sure they scroll more than delta
                if (Math.abs(lastScrollTop - st) <= delta)
                return;
                // If they scrolled down and are past the navbar, add class .nav-up.
                // This is necessary so you never see what is "behind" the navbar.
                if (st > lastScrollTop && st > navbarHeight) {
                // Scroll Down
                    $('header').removeClass('nav-down').addClass('nav-up').css('top', -navbarHeight);
                } else {
                    // Scroll Up
                    if (st + $(window).height() < $(document).height()) {
                        $('header').removeClass('nav-up').addClass('nav-down').css('top', '0');
                    }
                }
                lastScrollTop = st;
            }
        });

        @if($user->confirm_agent_addition == 'y')
        jQuery(document).ready(function(){
             jQuery('#tandcModal').modal({backdrop: 'static', keyboard: false});
        });

        jQuery("#yesButton").on('click', function(e){
            e.preventDefault();
            jQuery.ajax({
                method: "POST",
                url: "{{route('add-additional-agent')}}",
                data: { confirm: 'y' ,"_token": "{{ csrf_token() }}",}
            }).done(function(){
                document.location = document.location;
            });
        });

        jQuery("#noButton").on('click', function(e){
            e.preventDefault();
            jQuery.ajax({
                method: "POST",
                url: "{{route('add-additional-agent')}}",
                data: { confirm: 'n' ,"_token": "{{ csrf_token() }}",}
            }).done(function(){
                document.location = document.location;
            });
        });
        @endif
</script>
@php
 $primaryAgent = $user->getPrimaryAgent();   
@endphp
        <script>
            var swiper = new Swiper('.swiper-container', {
                slidesPerView: 'auto',
                spaceBetween: 10,
                slidesPerGroup: 10,
                mousewheel: true,
                // init: false,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },

                mousewheel: {
                    invert: true,
                    forceToAxis: true,
                    releaseOnEdges: true,
                    sensitivity: 10,
                },
      
                breakpoints: {
                    1200: {
                        slidesPerView: 'auto',
                        spaceBetween: 10,
                        slidesPerGroup: 7,
                    },
                    992: {
                        slidesPerView: 'auto',
                        spaceBetween: 10,
                        slidesPerGroup: 1,
                    },
                    768: {
                        slidesPerView: 'auto',
                        spaceBetween: 10,
                        slidesPerGroup: 1,
                    },
                    640: {
                        slidesPerView: 'auto',
                        spaceBetween: 10,
                        slidesPerGroup: 1,
                    },
                    400: {
                        slidesPerView: 'auto',
                        spaceBetween: 10,
                        slidesPerGroup: 1,
                    }
                }
            });

            function chipsReloaded(){
              
                // jQuery("input.city_options").on('click', function(){
                //     if(jQuery(this).is(":checked")){
                //         var city = jQuery(this).val();
                //         if(!cities.includes(city)) {
                //                 cities.push(city);
                //                 regenerateCityButtons();
                //                 resetCityValues();
                //                 submitForm();
                //         }
                //     }
                //     else{
                //         var city = jQuery(this).val();
                //         removeCity(city.replaceAll(' ','+'));
                //     }
                // });

                jQuery("input.city_options").on('click', function(){
                    if(jQuery(this).is(":checked")){
                        var city = jQuery(this).val();
                        if(!cities.includes(city)) {
                                cities.push(city);
                                regenerateCityButtons();
                                resetCityValues();
                                submitForm();
                                submitClickEvent('city', city);
                        }
                    }
                    else{
                        var city = jQuery(this).val();
                        removeCity(city.replaceAll(' ','+'));
                    }
                });

                jQuery("input.subarea_options").on('click', function(){
                    if(jQuery(this).is(":checked")){
                        var subarea = jQuery(this).val();
                        jQuery(this).parent().parent().addClass("active");
                        if(!subareas.includes(subarea)) {
                            subareas.push(subarea);
                            cities = [];
                                regenerateCityButtons();
                                resetCityValues();
                                regenerateSubareaButtons();
                                resetSubareaValues();
                                submitForm();
                                submitClickEvent('subarea', subarea);
                        }
                    }
                    else{
                        var subarea = jQuery(this).val();
                        removeSubarea(subarea.replaceAll(' ','+'));
                        jQuery(this).parent().parent().removeClass("active");
                    }
                });

                jQuery("input.subarea_options").each(function(){
                    if(jQuery(this).is(":checked")){
                        jQuery(this).parent().parent().addClass("active");
                    } else {
                        jQuery(this).parent().parent().removeClass("active");
                    }
                });
                
                var swiper = new Swiper('.swiper-container', {
                    slidesPerView: 'auto',
                    spaceBetween: 10,
                    slidesPerGroup: 10,
                    mousewheel: true,
                    // init: false,
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },

                    mousewheel: {
                        invert: true,
                        forceToAxis: true,
                        releaseOnEdges: true,
                        sensitivity: 10,
                    },
      
                    breakpoints: {
                        1200: {
                            slidesPerView: 'auto',
                            spaceBetween: 10,
                            slidesPerGroup: 7,
                        },
                        992: {
                            slidesPerView: 'auto',
                            spaceBetween: 10,
                            slidesPerGroup: 5,
                        },
                        768: {
                            slidesPerView: 'auto',
                            spaceBetween: 10,
                            slidesPerGroup: 1,
                        },
                        640: {
                            slidesPerView: 'auto',
                            spaceBetween: 10,
                            slidesPerGroup: 1,
                        },
                        400: {
                            slidesPerView: 'auto',
                            spaceBetween: 10,
                            slidesPerGroup: 1,
                        }
                    }
                });      
            }

            var map_view = 0;
            jQuery("#map_view_button").on('click', function(){
                if(map_view == 0){
                    jQuery("#map_view_button").text("Grid View").addClass("noImage");
                    jQuery(".map_view_container").removeClass("hidden_map_view");
                    map_view = 1;
                }
                else{
                    jQuery("#map_view_button").text("Map View").removeClass("noImage");
                    jQuery(".map_view_container").addClass("hidden_map_view");
                    map_view = 0;
                }
                jQuery(".infinite-scroll").toggle();
                jQuery('.lazy').lazy({
                    effect: 'fadeIn',
                });
                
            });

            function hideCountDiv(){
                jQuery("#recordsCountDiv").hide();
            }

            function submitClickEvent(type, value){
                jQuery.ajax({
                    method: "POST",
                    url: "{{route('storeClickEvent')}}",
                    data: { type: type, value: value ,"_token": "{{ csrf_token() }}",}
                }).done(function(){
                   
                });
            }

            //var hideCountTimeout = setTimeout(hideCountDiv, 5000);

            

            $(window).on("popstate", function(e) {
                window.location.reload();
            });
            
        </script>
        <script>
window.BCTrack = window.BCTrack || {};
window.BCTrack.pageType = "dashboard";
        </script>
        @include('frontend.includes.user_additional_scripts')
@endpush
