<div class="map_view">
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
        <div class="col-sm-12 col-md-6 map_container">
            <div id="map"></div>
        </div>
        <div class="col-sm-12 col-md-6">
    @foreach($listings as $listing)
    <!-- for each listing -->

        
        
            
        
        <div class="col-md-12 col-xl-6 col-xxl-4 col-sm-12">
        <div class="listing__item">
            {{--{{trim(route('listing-detail-page', $listing->slug))}}--}}
            <div class="listing__item--content">
            <a href="{{trim(route('listing-detail-page', $listing->slug))}}" class="listing__item--link" >

               {{-- url({{$listing->mainpicurl}})--}}
            <div class="listing__image lazy" data-src="@if($listing->photos->count() > 0) https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos->first()->directory.$listing->photos->first()->name)}}?h=600&w=400 @else {{asset('assets/img/no-image.jpg')}} @endif">

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
                            <a href="{{trim(route('listing-detail-page', $listing->slug))}}"><p>View Details</p></a>
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
            <a href="javascript:;" onclick="locateOnMap('{{$listing->listingid}}')"><p>View on Map</p>
                </a>
            </div>

            </div>
            </a>

        </div>
    </div>

    @endforeach
</div>
            @else
                <div class="alert alert-info">
                   {{config('constants.no_listing_found')}}
                </div>
    @endif

        <div style="clear:both;"></div>
        {{-- {{$listings->links()}} --}}
    </div>
{{-- </div> --}}
<div id="map_json_content" style="display:none">
    {!!$mapXML!!}
</div>
<style>
        /* Always set the map height explicitly to define the size of the div
         * element that contains the map. */
        #map {
          height: 100%;
        }
        /* Optional: Makes the sample page fill the window. */
        html, body {
          height: 100%;
          margin: 0;
          padding: 0;
        }
       
        .map_container{
           height: 100vh;
            
        }
</style>
@push('after-scripts')
    <script>
        var markers = [];
        function initMap(changeCenter = false) {
          
        var map = new google.maps.Map(document.getElementById('map'), {
          center: new google.maps.LatLng(49.246292, -123.116226),
          zoom: 12
        });
        var infoWindow = new google.maps.InfoWindow;
        var bounds  = new google.maps.LatLngBounds();
        var xml = jQuery("#map_json_content").text();
            //var dataObj = JSON.parse(xml);
            var dataObj = JSON.parse(xml);
            //var markers = xml.documentElement.getElementsByTagName('marker');
            dataObj.forEach(function(markerElem){
                var id = markerElem.id;
                var name = markerElem.name;
                var address = markerElem.address;
                var type = markerElem.type;
                var link = markerElem.link;
                var image = markerElem.image;
                var status = markerElem.status;
                var price = markerElem.price;
                var point = new google.maps.LatLng(
                    parseFloat(markerElem.lat),
                    parseFloat(markerElem.lng));

                var infowincontent = document.createElement('div');
                infowincontent.className = "infoMapListing";
                var anchor = document.createElement('a');
                anchor.href = link;
                //var imagecontent = document.createElement('img');
                //imagecontent.src = image;
                //imagecontent.width = 200;
                var imageBg = document.createElement('div');
                imageBg.className = "infoMap__image"
                imageBg.style.backgroundImage = "url("+image+")" 
                anchor.appendChild(imageBg);
                //anchor.appendChild(imagecontent);
                //anchor.appendChild(document.createElement('br'));
                infowincontent.appendChild(anchor);
                var wrapStrong = document.createElement('div');
                wrapStrong.className = "infoMap__name"
                var strong = document.createElement('strong');
                var anchor = document.createElement('a');
                anchor.href = link;
                strong.textContent = name;
                var text = document.createElement('text');
                text.textContent = address
                wrapStrong.appendChild(strong);
                wrapStrong.appendChild(document.createElement('br'));
                wrapStrong.appendChild(text);
                anchor.appendChild(wrapStrong);
                infowincontent.appendChild(anchor);
                
                var wrapPrice = document.createElement('div');
                wrapPrice.className = "infoMap__price--wrap";
                var statusSpan = document.createElement('span');
                statusSpan.className = "infoMap__status";
                statusSpan.textContent = status+": ";
                var priceSpan = document.createElement('span');
                priceSpan.className = "infoMap__price";
                priceSpan.textContent = price;
                wrapPrice.appendChild(statusSpan);
                wrapPrice.appendChild(priceSpan);
                infowincontent.appendChild(wrapPrice);

                //var pricecontent = document.createElement('div');
                //pricecontent.className = "infoMap__price " + status;
                //pricecontent.textContent = status+": "+price;
                //infowincontent.appendChild(pricecontent);
                //var text = document.createElement('text');
                //text.textContent = address
                //infowincontent.appendChild(text);

                var icon = type || {};
               // $('#markers').append('<a class="marker-link" data-markerid="' + id + '" href="#">' + point + '</a> ');
                var marker = new google.maps.Marker({
                    map: map,
                    position: point,
                    label: icon.label,
                    // icon:"{{asset('frontend/images/fisherly-icon.svg')}}"
                });
                var loc = new google.maps.LatLng(marker.position.lat(), marker.position.lng());
                bounds.extend(loc);
                marker.addListener('click', function() {
                    infoWindow.setContent(infowincontent);
                    infoWindow.open(map, marker);
                });
                markers[id] = marker;
            });
            if(changeCenter){
                map.fitBounds(bounds);  
                map.panToBounds(bounds);
            }
            @php
            if(isset($filters) && count($filters)>0){
                @endphp
                map.fitBounds(bounds);  
                map.panToBounds(bounds);
                @php
            }
            @endphp

    }

    function locateOnMap(id){
        google.maps.event.trigger(markers[id], 'click');
    }
    
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBe_jE1XvuaLT9mHySPF4dLAu3kmQXprB0&callback=initMap">
    </script>
@endpush