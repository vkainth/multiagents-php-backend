@extends('frontend.layouts.default_mobile')
@section('title')
Favorite Listings | Hani & Les | BC Condos And Homes
@endsection
@push('after-styles')
<link rel="stylesheet" href="{{ asset('frontend/css/bootstrapXL.css')}}">
<style>
.d-flex-wrap{display: flex; flex-wrap: wrap;}
.favorite_listing{max-width: 500px;}
.fvrlst_tracked{position: absolute;top: 40px !important;right: 15px; font-size: 25px;}
.listing-tracked{border: 2px dashed #337ab7;}
.listing-is-tracked,.bcch-blue{color: #337ab7;}
.listing-not-tracked,.bcch-red{color: #df4611;}
</style>
@endpush
@php
$user = Auth::user();
$userIsPixiMember = (!empty($user->email) && in_array( substr(strstr($user->email,'@'),1), ['pixilink.com'/*,'bccondos.net','bccondosandhomes.com'*/]) );
@endphp
@section('content')
@include('frontend.includes.header')
<div id="content" class="content full">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="">
                    <ol class="breadcrumb small" style="margin-bottom:0;" >
                        <li class="breadcrumb-item"><a href="{{url('/')}}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{trim(route('show_favorite_listings'),'-')}}">Favorites</a></li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="" style="background-color:#dddddd55">
            {{-- 
            <div class="col-xs-6"> <h1 class="properties-top-heading">Favorites</h1> </div>
            <div class="col-xs-6 text-right"> <a href="/favorites/tracked" class="">Tracked</a> </div>
            --}}
            <ul class="nav nav-tabs {{-- nav-justified --}} nav-fvtr-bar">
                <li class="nav-fvtr nav-fvtr-fv active"><a href="#">Favorites</a></li>
                <li class="nav-fvtr nav-fvtr-tr "><a href="#">Only Tracked</a></li>
            </ul>
        </div>
        <br>
        
        <div class="listing__items">
            <div class="infinite-scroll d-flex-wrap row">
                @if($favorite_listings && count($favorite_listings) > 0)
                @foreach ($favorite_listings as $favorite_listing)
                @if($favorite_listing->listing)
                @php $listing = $favorite_listing->listing; @endphp
                <div class="col-md-4 col-xl-3 col-xxl-2 col-sm-6 favorite_listing @if($favorite_listing->tracked && $listing->status=='Active') listing-is-tracked @else listing-not-tracked @endif" id="listing-{{$listing->listingid}}">
                    <div class="listing__item">
                        <div class="listing__item--content">
                            <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}" class="listing__item--link" >
                                <div class="listing__image lazy" style="background-image: url('@if(!empty($_photo=$listing->photos()->first())) https://media.pixilinkserver.com/{{str_replace('images','',$_photo->directory.$_photo->name)}}?w=900 @else {{asset('assets/img/no-image.jpg')}} @endif')">
                                    <div class="favor__listing hover-toggle-fv" data-listingid="{{$listing->listingid}}"><i class="fa fa-heart" title="Remove from favorite"></i></div>
                                    
                                    @if($listing->status=='Active')
                                    <div class="fvrlst_tracked hover-toggle-fv @if($favorite_listing->tracked) bcch-blue listing-is-tracked @else bcch-red listing-not-tracked @endif " data-listingid="{{$listing->listingid}}"><i class="fa fa-area-chart" title="Toggle Tracking (Notification via email)"></i></div>
                                    @endif

                                    <div class="icons">
                                        <div class="icon__beds clearfix"><i class="fa fa-bed"></i> <span class="number">{{$listing->bedrooms}}</span></div>
                                        <div class="icon__baths clearfix"><i class="fa fa-bath"></i> <span class="number">{{$listing->full_baths+$listing->half_baths}}</span></div>
                                        <div class="icon__photos clearfix"><i class="fa fa-camera"></i> <span class="number">{{$listing->photos()->count()}}</span></div>
                                    </div>
                                </div>
                                <div class="listing__content">
                                    <div class="listing__icon pull-left">
                                        <img class="{{strtolower($listing->status)}}" src="{{asset('frontend/icons/'.strtolower($listing->getType()).'-selected.svg')}}" />
                                    </div>
                                    <div class="mls_number pull-right">MLS®: {{$listing->listingid}}</div>
                                    <div class="listing__status {{strtolower($listing->status)}}">{{$listing->status}}</div> <!-- can be active or sold - depends on status of listing -->
                                    <div class="listing__price">@if($listing->status == 'Sold'){{Helper::money_format('%.0n', $listing->soldprice_2)}}@else {{$listing->listprice}} @endif</div>
                                    <div class="listing__address">
                                        <span class="big">@if($listing->getType() == 'Apartment' && $listing->suite_no){{$listing->suite_no}} - @endif{{$listing->street_number}} {{$listing->street_name}} {{$listing->street_type}}   </span> <br />
                                        {{$listing->subarea}}, {{$listing->city}}, {{$listing->province}}
                                    </div>
                                    <div class="listing__amenities" style="min-height: 44px">
                                        @if($listing->status == 'Sold' && $listing->getSoldPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getSoldPeriod()}} </span> | @elseif($listing->getListingPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getListingPeriod()}} | </span>@endif @if($listing->days_on_market())<span class="{{strtolower($listing->status)}}">{{$listing->days_on_market()}}</span> {{($listing->days_on_market()>1)?'days':'day'}} on the market |@endif @if($listing->livingarea_2 > 0) SqFt: <span class="{{strtolower($listing->status)}}">{{$listing->livingarea_2}}</span>@endif @if($listing->lotsize > 0)| Lot Size: <span class="{{strtolower($listing->status)}}">{{$listing->lotsize}}</span> SqFt. @endif @if($listing->home_style != '')| {{$listing->home_style}} @endif @if($listing->maintenance && $listing->maintenance > 0)| Strata Fees: <span class="{{strtolower($listing->status)}}">{{Helper::money_format('%.0n', $listing->maintenance)}}</span> @endif @if($listing->yearbuilt && $listing->yearbuilt > 0)| Year Built: <span class="{{strtolower($listing->status)}}">{{ $listing->yearbuilt}}</span> @endif
                                    </div>
                                    <div class="listing__listedBy">Listed by: {{$listing->reoffice}}</div>
                                    <div class="listing__item--detail-link {{strtolower($listing->status)}} visible-sm visible-xs">
                                        <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}"><p>View Details</p></a>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                @endif
                @endforeach
                @endif
                <div class="alert alert-info" id="no_listing_message" @if($favorite_listings && count($favorite_listings) > 0) style="display:none" @endif>
                    no favorite listing available
                </div>
                <form id="remove_favorite" action="" method="post">
                    <input type="hidden" name="id" id="listingid" value="">
                    <input type="hidden" name="add" value="false">
                </form>
            </div>
            {{-- @if($userIsPixiMember) --}}
            <div class="removed-items clearfix" style="clear:both;">
                <h3></h3> 
                <div class="removed-items-container clearfix"></div>
            </div>
            <style type="text/css" id="style-for-fvrt-disp"></style>
            {{-- @endif --}}
        </div>
    </div>
</div>
@include('frontend.includes.footer')
@endsection
@push('after-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.matchHeight/0.7.0/jquery.matchHeight-min.js"></script>
<script>
    jQuery(document).ready(function(){
        $('.infinite-scroll .col-md-4 .listing__item').matchHeight();
        jQuery(".favor__listing").on('click', function(e){
            e.preventDefault();
            var listingid = jQuery(this).data('listingid');
            jQuery("#remove_favorite #listingid").val(listingid);
            jQuery.ajax({
                method: 'post',
                url: '/api/savefavourite',
                data: jQuery("#remove_favorite").serialize(),
                beforeSend: function(request) {
                    request.setRequestHeader("authorization", 'Basic {{$user->uid}}');
                },
            }).done(function(response){

                var removedItem = jQuery("#listing-"+listingid).clone().attr("id","listing-removed-"+listingid);
                removedItem.hide().appendTo('.removed-items-container').fadeIn('slow').find('.favor__listing').toggleClass('favorite_again').on('click',
                    function(e){
                        e.preventDefault();
                        jQuery("#listing-"+listingid).fadeIn("slow"); jQuery("#listing-removed-"+listingid).fadeOut("slow").remove();
                        jQuery.ajax({method:'post',url:'/api/savefavourite',data:{'id':listingid,'add':'true'},
                            beforeSend: function(request) {request.setRequestHeader("authorization", 'Basic {{$user->uid}}'); },
                        }).done(function(resp){});
                    });

                jQuery("#listing-"+listingid).fadeOut("slow");
                if(jQuery(".favorite_listing").length <=0){
                    jQuery("#no_listing_message").show();
                }
            });
        });

        jQuery(document).on('click',".fvrlst_tracked", function(e){
            e.preventDefault();
            var listingid = jQuery(this).data('listingid');
            var trackthis = jQuery(this).hasClass('listing-not-tracked');
            var thisEl = jQuery(this);
            jQuery("#remove_favorite #listingid").val(listingid);
            jQuery.ajax({
                method: 'post',
                url: '/api/savefavourite',
                data:{'id':listingid,'add':'true','track':trackthis},
                beforeSend: function(request) {
                    request.setRequestHeader("authorization", 'Basic {{$user->uid}}');
                },
            }).done(function(response){
                jQuery(thisEl).toggleClass('bcch-blue bcch-red listing-is-tracked listing-not-tracked')
                jQuery(thisEl).closest('.favorite_listing').toggleClass('listing-is-tracked listing-not-tracked')
                if(trackthis){}
                if(jQuery(".favorite_listing").length <=0){
                    jQuery("#no_listing_message").show();
                }
            });
        });

        jQuery(".removed-items-container").on('click', '.fvrlst_tracked', function(e){e.preventDefault(); });
        jQuery(".nav-fvtr").on('click', function(e){
            e.preventDefault();
            var el = jQuery(this);
            jQuery(el).addClass('active');jQuery(el).siblings().removeClass('active');
            if(jQuery(el).text().toLowerCase()=='only tracked'){
                jQuery('style#style-for-fvrt-disp').html('.favorite_listing.listing-not-tracked{display:none;}')
            }else {
                jQuery('style#style-for-fvrt-disp').html('.favorite_listing.listing-not-tracked{}')
            }
        });
    });
</script>
@include('frontend.includes.user_additional_scripts')
@endpush