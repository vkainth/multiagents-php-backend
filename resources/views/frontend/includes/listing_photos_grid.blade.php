<div class="container">
        <div id="listing-detail__images" class="container-fluid hidden-sm hidden-xs nopadding">
                {{-- listing-detail__images--top --}}
                @component('frontend.components.grid514')
                <div class="col-md-6 nopadding">

                        <div class="listing-detail__image no image-effect">
                                @slot('photo_grid_5_1')
                                @if($matterport_url)
                                {{-- <div class="listing-detail__image--iframe"> --}}
                                        <iframe class="resp-iframe" title="" src="" data-src4lazyload="{{$matterport_url}}"  frameborder="0" allowfullscreen style="position:relative" loading="lazy"></iframe>
                                        {{-- <div class="lazyframe" data-src="{{$matterport_url}}" data-vendor="matterport"></div> --}}
                                {{-- </div> --}}
                                @php $media_displayed = 'matterport';   @endphp
                                @elseif($videotour_url)
                                {{-- <div class="listing-detail__image--iframe"> --}}
                                        <iframe class="resp-iframe" title="" src="" data-src4lazyload="{{$videotour_url}}"  frameborder="0" allowfullscreen style="position:relative" loading="lazy"></iframe>
                                        {{-- <div class="lazyframe" data-src="{{$videotour_url}}" data-vendor="youtube"></div> --}}
                                {{-- </div> --}}
                                @php $media_displayed = 'video';   @endphp
                                @elseif($virtualtour_url)
                                {{-- <div class="listing-detail__image--iframe"> --}}
                                        <iframe class="resp-iframe" title="" src="" data-src4lazyload="{{$virtualtour_url}}"  frameborder="0" allowfullscreen style="position:relative" loading="lazy"></iframe>
                                {{-- </div> --}}
                                @php $media_displayed = 'virtualtour';   @endphp
                                @else
                                @if(isset($listing->photos[$image_index]))
                                @php
                                $attr = 'data-fancybox="gallery" href="https://media.pixilinkserver.com/'.str_replace('images','',$listing->photos[$image_index]->directory.$listing->photos[$image_index]->name).'?w=1600"';
                                if($listing->status != 'Active' && !$is_authenticated){
                                        $attr = 'href="/login?redirect='.route('listing-detail-page2', ['slug'=>$listing->slug]) .'"';
                                }
                                @endphp
                                <a {!! $attr !!}>
                                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos[$image_index]->directory.$listing->photos[$image_index]->name)}}?w=568&h=426" loading="lazy" width="568" height="426" alt="{{$addressAsH1tag}}, {{$listing->cityProperCased}}, {{$listing->province??''}} {{$listing->postalcode??''}} | {{$building->name??''}} Photo {{$listing->photos[$image_index]->name??($printedPhotosCount++??'')}}" class="img-responsive">
                                </a>
                                @php $image_index++;   @endphp
                                @else
                                <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="{{asset('assets/img/no-image-1600-1200.png?w=1600&h=1200')}}" alt="no-image" loading="lazy" width="1600" height="1200" class="img-responsive">
                                @endif
                                @endif
                                @endslot
                        </div>

                </div>
                <div class="col-md-3 nopadding">
                        @slot('photo_grid_4_1')
                        {{-- <div class="listing-detail__image image-effect"> --}}
                                @if(isset($listing->photos[$image_index]))
                                @php
                                $attr = 'data-fancybox="gallery" href="https://media.pixilinkserver.com/'.str_replace('images','',$listing->photos[$image_index]->directory.$listing->photos[$image_index]->name).'?w=1600"';
                                if($listing->status != 'Active' && !$is_authenticated){
                                        $attr = 'href="/login?redirect='.route('listing-detail-page2', ['slug'=>$listing->slug]).'"';
                                }
                                @endphp
                                <a {!! $attr!!} >
                                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos[$image_index]->directory.$listing->photos[$image_index]->name)}}?w=283&h=212" loading="lazy" width="283" height="212" alt="{{$addressAsH1tag}}, {{$listing->cityProperCased}}, {{$listing->province??''}} {{$listing->postalcode??''}} | {{$building->name??''}} Photo {{$listing->photos[$image_index]->name??($printedPhotosCount++??'')}}" class="img-responsive">
                                </a>
                                @php $image_index++;   @endphp
                                @else
                                <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="{{asset('assets/img/no-image-800-600.png?w=800&h=600')}}" alt="no-image" loading="lazy" class="img-responsive">
                                @endif
                        {{-- </div> --}}
                        @endslot
                        @slot('photo_grid_4_2')
                        {{-- <div class="listing-detail__image image-effect"> --}}
                                @if(isset($listing->photos[$image_index]))
                                @php
                                $attr = 'data-fancybox="gallery" href="https://media.pixilinkserver.com/'.str_replace('images','',$listing->photos[$image_index]->directory.$listing->photos[$image_index]->name).'?w=1600"';
                                if($listing->status != 'Active' && !$is_authenticated){
                                        $attr = 'href="/login?redirect='.route('listing-detail-page2', ['slug'=>$listing->slug]).'"';
                                }
                                @endphp
                                <a {!! $attr!!} >
                                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos[$image_index]->directory.$listing->photos[$image_index]->name)}}?w=283&h=212" loading="lazy" width="283" height="212" alt="{{$addressAsH1tag}}, {{$listing->cityProperCased}}, {{$listing->province??''}} {{$listing->postalcode??''}} | {{$building->name??''}} Photo {{$listing->photos[$image_index]->name??($printedPhotosCount++??'')}}" class="img-responsive">
                                </a>
                                @php $image_index++;   @endphp
                                @else
                                <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="{{asset('assets/img/no-image-800-600.png?w=800&h=600')}}" alt="no-image" loading="lazy" width="800" height="600" class="img-responsive">
                                @endif
                        {{-- </div> --}}
                        @endslot
                </div>
                <div class="col-md-3 nopadding">
                        @slot('photo_grid_4_3')
                        {{-- <div class="listing-detail__image image-effect"> --}}
                                @if(isset($listing->photos[$image_index]))
                                @php
                                $attr = 'data-fancybox="gallery" href="https://media.pixilinkserver.com/'.str_replace('images','',$listing->photos[$image_index]->directory.$listing->photos[$image_index]->name).'?w=1600"';
                                if($listing->status != 'Active' && !$is_authenticated){
                                        $attr = 'href="/login?redirect='.route('listing-detail-page2', ['slug'=>$listing->slug]).'"';
                                }
                                @endphp
                                <a {!! $attr!!} >
                                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos[$image_index]->directory.$listing->photos[$image_index]->name)}}?w=283&h=212" loading="lazy" width="283" height="212" alt="{{$addressAsH1tag}}, {{$listing->cityProperCased}}, {{$listing->province??''}} {{$listing->postalcode??''}} | {{$building->name??''}} Photo {{$listing->photos[$image_index]->name??($printedPhotosCount++??'')}}" class="img-responsive">
                                </a>
                                @php $image_index++;   @endphp
                                @else
                                <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="{{asset('assets/img/no-image-800-600.png?w=800&h=600')}}" alt="no-image" loading="lazy" class="img-responsive">
                                @endif
                        {{-- </div> --}}
                        @endslot
                        @slot('photo_grid_4_4')
                        {{-- <div class="listing-detail__image image-effect"> --}}
                                @if(isset($listing->photos[$image_index]))
                                @php
                                $attr = 'data-fancybox="gallery" href="https://media.pixilinkserver.com/'.str_replace('images','',$listing->photos[$image_index]->directory.$listing->photos[$image_index]->name).'?w=1600"';
                                if($listing->status != 'Active' && !$is_authenticated){
                                        $attr = 'href="/login?redirect='.route('listing-detail-page2', ['slug'=>$listing->slug]).'"';
                                }
                                @endphp
                                <a {!! $attr!!} >
                                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos[$image_index]->directory.$listing->photos[$image_index]->name)}}?w=283&h=212" loading="lazy" width="283" height="212" alt="{{$addressAsH1tag}}, {{$listing->cityProperCased}}, {{$listing->province??''}} {{$listing->postalcode??''}} | {{$building->name??''}} Photo {{$listing->photos[$image_index]->name??($printedPhotosCount++??'')}}" class="img-responsive">
                                </a>
                                @php $image_index++;   @endphp
                                @else
                                <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="{{asset('assets/img/no-image-800-600.png?w=800&h=600')}}" alt="no-image" loading="lazy" width="800" height="600" class="img-responsive" alt='no-image'>
                                @endif
                        {{-- </div> --}}
                        @endslot

                        <!-- Extra Images !-->
                        @if(isset($listing->photos[$image_index]) && ($listing->status == 'Active' || $is_authenticated))
                        @for($i = $image_index, $_count_listing_photos = count($listing->photos); $i < $_count_listing_photos; $i++)
                        <a data-fancybox="gallery" href="https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos[$i]->directory.$listing->photos[$i]->name)}}?w=1600" style="display: none;"><img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos[$i]->directory.$listing->photos[$i]->name)}}?w=283&h=212" loading="lazy" width="283" height="212" alt="{{$addressAsH1tag}}, {{$listing->cityProperCased}}, {{$listing->province??''}} {{$listing->postalcode??''}} | {{$building->name??''}} Photo {{($printedPhotosCount++??'')}}" class="img-responsive" ></a>
                        @endfor
                        @endif
                        <!-- End Extra Images !-->
                </div>

                <!-- if there show this>-->
                <div class="listing-detail__img-icons">
                        {{--  @if($floorplan)<div class="listing-detail__img-icon"><a href="#floorplan_area"><i class="fa fa-map"></i> Floorplans</a></div>@endif  --}}
                        <!-- Virtual Tour opens on popup -->
                        {{--  @if($listing->virtualtoururl) <div class="listing-detail__img-icon"><a href="{{$listing->virtualtoururl}}" target="_blank"><i class="fa fa-play"></i> Virtual Tour</a></div>@endif  --}}
                </div>
                @endcomponent
        </div>
</div>

<!-- Slider for mobile devices -->
<!-- Start Slider for mobile devices -->
<div class="container">
    @if($listing->status != 'Terminated' && $listing->status != 'Expired')
        <div class="col-md-12 nopadding hidden-md hidden-lg">
                <div class="tab-content">
                        @if($matterport_url || $videotour_url || $virtualtour_url)
                        <div role="tabpanel" class="tab-pane active" id="home">
                                @if($matterport_url)
                                <div class="listing-detail__image--iframe listing-detail__image--iframe-mobile">
                                        <iframe class="resp-iframe lzyldSrc4mAtrib" title="" src="" data-src4lazyload="{{$matterport_url}}"  frameborder="0" allowfullscreen loading="lazy"></iframe>
                                </div>    
                                @elseif($videotour_url)
                                <div class="listing-detail__image--iframe listing-detail__image--iframe-mobile">
                                        <iframe class="resp-iframe lzyldSrc4mAtrib" title="" src="" data-src4lazyload="{{$videotour_url}}"  frameborder="0" allowfullscreen loading="lazy"></iframe>
                                </div>    
                                @elseif($virtualtour_url)
                                <div class="listing-detail__image--iframe listing-detail__image--iframe-mobile">
                                        <iframe class="resp-iframe lzyldSrc4mAtrib" title="" src="" data-src4lazyload="{{$virtualtour_url}}"  frameborder="0" allowfullscreen loading="lazy"></iframe>
                                </div>
                                @endif
                        </div>
                        <div role="tabpanel" class="tab-pane" id="profile">
                                @endif
                                <div class="listing-detail__item">
                                        <div class="listing-detail__animation">
                                                @if(Browser::isMobile())
                                                @if(!empty($listing->photos[0]) && !empty($listing->photos[0]->name) )
                                                <div id="spliderStarterDive2810kjs" class="listing-detail__image" onclick="initSplide_fxn2810();jQuery('#spliderWrapperDiv2810hnbjd').show();jQuery('#spliderStarterDive2810kjs').remove();">
                                                        <div class="splide__arrows"><button class="splide__arrow splide__arrow--prev" type="button" aria-controls="spliderWrapperDiv2810hnbjd-track" aria-label="Go to last slide"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="40" height="40"><path d="m15.5 0.932-4.3 4.38 14.5 14.6-14.5 14.5 4.3 4.4 14.6-14.6 4.4-4.3-4.4-4.4-14.6-14.6z"></path></svg></button><button class="splide__arrow splide__arrow--next" type="button" aria-controls="spliderWrapperDiv2810hnbjd-track" aria-label="Next slide"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="40" height="40"><path d="m15.5 0.932-4.3 4.38 14.5 14.6-14.5 14.5 4.3 4.4 14.6-14.6 4.4-4.3-4.4-4.4-14.6-14.6z"></path></svg></button></div>
                                                        {{-- @if($userIsPixiMember) --}}{{-- [published:30-09-2022] --}}
                                                        <a data-fancybox="gallery-mobile" href="https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos[0]->directory.$listing->photos[0]->name)}}?w=1600" style="position:absolute;right:0">
                                                                <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="20px" height="20px" viewBox="0 0 35 35" style="enable-background:new 0 0 15 15;opacity:0.5" xml:space="preserve"> <g> <g>
                                                                        <path d="M0,0v35h35V0H0z M33,33H2V2h31V33z"></path>
                                                                        <rect x="14" y="4.5" width="7" height="7"></rect>
                                                                        <rect x="14" y="14" width="7" height="7"></rect>
                                                                        <rect x="14" y="23.5" width="7" height="7"></rect>
                                                                        <rect x="4.5" y="4.5" width="7" height="7"></rect>
                                                                        <rect x="4.5" y="14" width="7" height="7"></rect>
                                                                        <rect x="4.5" y="23.5" width="7" height="7"></rect>
                                                                        <rect x="23.5" y="4.5" width="7" height="7"></rect>
                                                                        <rect x="23.5" y="14" width="7" height="7"></rect>
                                                                        <rect x="23.5" y="23.5" width="7" height="7"></rect>
                                                                </g> </g> </svg>
                                                        </a>
                                                        {{-- @endif --}}
                                                        <img sizes="" src="https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos[0]->directory.$listing->photos[0]->name)}}?w=600&h=406{{-- w=300&h=203 --}}" alt="{{$addressAsH1tag}}, {{$listing->cityProperCased}}, {{$listing->province??''}} {{$listing->postalcode??''}} | {{$building->name??''}} Photo {{($printedPhotosCount++??'')}}" width="300" height="203" />
                                                </div>
                                                @else
                                                <div class="listing-detail__image image-effect">
                                                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="{{asset('assets/img/no-image-800-600.png?w=800&h=600')}}" alt="no-image" loading="lazy" width="800" height="600" class="img-responsive" alt='no-image'>
                                                </div>
                                                @endif
                                                @endif
                                                <div class="splide" id="spliderWrapperDiv2810hnbjd" style="display:none">
                                                        <div class="splide__track">
                                                                <ul class="splide__list">
                                                                        @php $cnt_img = 0; @endphp
                                                                        @foreach($listing->photos as $photo)
                                                                        @if (Browser::isMobile())
                                                                        @if($listing->status == 'Active' || $is_authenticated)
                                                                        <li class="splide__slide">
                                                                                @if($userIsPixiMember)<a data-fancybox="gallery-mobile" href="https://media.pixilinkserver.com/{{str_replace('images','',$photo->directory.$photo->name)}}?w=1600" style="position:absolute;right:0"><svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="20px" height="20px" viewBox="0 0 35 35" style="enable-background:new 0 0 15 15;opacity:0.5" xml:space="preserve"> <g> <g>
                                                                                        <path d="M0,0v35h35V0H0z M33,33H2V2h31V33z"></path>
                                                                                        <rect x="14" y="4.5" width="7" height="7"></rect>
                                                                                        <rect x="14" y="14" width="7" height="7"></rect>
                                                                                        <rect x="14" y="23.5" width="7" height="7"></rect>
                                                                                        <rect x="4.5" y="4.5" width="7" height="7"></rect>
                                                                                        <rect x="4.5" y="14" width="7" height="7"></rect>
                                                                                        <rect x="4.5" y="23.5" width="7" height="7"></rect>
                                                                                        <rect x="23.5" y="4.5" width="7" height="7"></rect>
                                                                                        <rect x="23.5" y="14" width="7" height="7"></rect>
                                                                                        <rect x="23.5" y="23.5" width="7" height="7"></rect>
                                                                                </g> </g> </svg> </a>@endif
                                                                                <img sizes="" src="https://media.pixilinkserver.com/{{str_replace('images','',$photo->directory.$photo->name)}}?w=600&h=406{{-- w=300&h=203 --}}" alt="{{$addressAsH1tag}}, {{$listing->cityProperCased}}, {{$listing->province??''}} {{$listing->postalcode??''}} | {{$building->name??''}} Photo {{($printedPhotosCount++??'')}}" loading="lazy" width="300" height="203" />
                                                                        </li>
                                                                        @else
                                                                        @if($cnt_img == 0)
                                                                        @php $attr = 'href="/login?redirect='.route('listing-detail-page2', ['slug'=>$listing->slug]).'"'; @endphp
                                                                        <li class="splide__slide">
                                                                                <a {!! $attr !!}>
                                                                                        <img sizes="" src="https://media.pixilinkserver.com/{{str_replace('images','',$photo->directory.$photo->name)}}?w=600&h=406{{-- w=300&h=203 --}}" alt="{{$addressAsH1tag}}, {{$listing->cityProperCased}}, {{$listing->province??''}} {{$listing->postalcode??''}} | {{$building->name??''}} Photo {{($printedPhotosCount++??'')}}" loading="lazy" width="300" height="203" />
                                                                                </a>
                                                                        </li>
                                                                        @php $cnt_img++ @endphp
                                                                        @endif
                                                                        @endif
                                                                        @else
                                                                        {{-- browser not-mobile --}}
                                                                        @if($listing->status == 'Active' || $is_authenticated)
                                                                        <li class="splide__slide">
                                                                                <a data-fancybox="gallery-mobile" href="https://media.pixilinkserver.com/{{str_replace('images','',$photo->directory.$photo->name)}}?w=1600">
                                                                                        <img sizes="" src="https://media.pixilinkserver.com/{{str_replace('images','',$photo->directory.$photo->name)}}?h=500&w=700" alt="{{$addressAsH1tag}}, {{$listing->cityProperCased}}, {{$listing->province??''}} {{$listing->postalcode??''}} | {{$building->name??''}} Photo {{($printedPhotosCount++??'')}}" loading="lazy" width="700" height="500" />
                                                                                </a>
                                                                        </li>
                                                                        @else
                                                                        @if($cnt_img == 0)
                                                                        @php $attr = 'href="/login?redirect='.route('listing-detail-page2', ['slug'=>$listing->slug]).'"'; @endphp
                                                                        <li class="splide__slide">
                                                                                <a {!! $attr !!}>
                                                                                        <img sizes="" src="https://media.pixilinkserver.com/{{str_replace('images','',$photo->directory.$photo->name)}}?h=500&w=700" alt="{{$addressAsH1tag}}, {{$listing->cityProperCased}}, {{$listing->province??''}} {{$listing->postalcode??''}} | {{$building->name??''}} Photo {{($printedPhotosCount++??'')}}" loading="lazy" width="700" height="500" />
                                                                                </a>
                                                                        </li>
                                                                        @php $cnt_img++ @endphp
                                                                        @endif
                                                                        @endif
                                                                        @endif
                                                                        @endforeach
                                                                </ul>
                                                        </div>
                                                </div>
                                        </div>
                                </div>
                        </div>
                        @if($matterport_url || $videotour_url || $virtualtour_url)
                </div>
                <ul class="nav nav-tabs" role="tablist">
                        @if($matterport_url)
                        <li role="presentation" class="active"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Matterport</a></li>
                        @elseif($videotour_url)
                        <li role="presentation" class="active"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Video Tour</a></li>
                        @elseif($virtualtour_url)
                        <li role="presentation" class="active"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Virtual Tour</a></li>
                        @endif
                        <li role="presentation"><a href="#profile" aria-controls="profile" role="tab" data-toggle="tab" >Photos</a></li>
                </ul>
                @endif
        </div>
        @endif
</div>
<!-- End Slider -->
<div class="clearfix"></div>
