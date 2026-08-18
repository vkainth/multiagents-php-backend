@php
$all_featured_listings = Helper::get_featured_listings();
$agent_bccondos_info = NULL;
if(!empty($main_listing) && $main_listing->is_featured()){
    $agent_bccondos_info = $main_listing->agent_bccondos_info();
}
@endphp
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.4/tiny-slider.css">
<div style="background-color: #eee;border: 1px solid #ccc;padding: 20px;margin-bottom: 10px;min-height: 130px;">
    <div>
        <div class="listing-detail__agent-bc-box--image" style="float:left">
            @if($agent_bccondos_info && $agent_bccondos_info->profile_image != '')
            <img src="{{$agent_bccondos_info->profile_image}}" alt="profile-image"/>
            @else
            <img src="https://www.bccondosandhomes.com/frontend/images/teamagents/les.jpg" alt="les"/>
            @endif
        </div>
        <div style="float:left; margin-left:24px;margin-top:13px;"><span style="font-size:14px; font-weight:600">For More Information @if(!empty($main_listing)) On This Property @endif</span>
        <br/>
        @if($agent_bccondos_info && $agent_bccondos_info->profile_image != '')
        <span>{{$agent_bccondos_info->first}} {{$agent_bccondos_info->last}} - {{$listing->reoffice}}</span>
        @else
        <span>Les Twarog - RE/MAX Crest Realty</span>
        @endif
        <br/>
        <span><strong>Contact Now: <a class="" href="tel:+16047061760; return false;" style="">604-706-1760</a></strong></span>
        </div>
        <div style="float: right; margin-top:30px;"><a href="/home-evaluation" class="btn btn-default">Free Home Evaluation</a></div>
    </div>
    <div class="clearfix"></div>
    <div style="margin-top:10px;">
        <div><strong style="font-size:16px;">Featured Listings ({{count($all_featured_listings)}})</strong></div>
        <div id="featuredlisting_loop" style="display:none">
            @foreach($all_featured_listings as $featured_listing)
            @if(!empty($_photo=$featured_listing->aphoto))
            <a href="{{trim(route('listing-detail-page2', ['slug'=>$featured_listing->slug]))}}"><div>
               <img src="https://media.pixilinkserver.com/{{str_replace('images','',$_photo->directory.$_photo->name)}}?w=150&h=250" loading="lazy" style="border: 1px solid #eee; border-radius: 5%;filter:brightness(0.8)" alt="listing-image_{{$_photo?->name?:''}}"/>
                <div class="slide-text-overlay">{{ucwords(strtolower($featured_listing->streetaddress))}}, {{ucwords(strtolower($featured_listing->city))}} <br/><span style="font-weight: 500;">{{$featured_listing->agent_name}}</span></div> 
            </div></a>
            @endif
            @endforeach
        </div>
    </div>
</div>
<style>
    .slide-text-overlay{
        position: absolute;
        bottom: 20px;
        max-width: 130px;
        padding: 0 10px;
        font-weight: 800;
        color: #ffffff;
    }
</style>
@push('after-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.2/min/tiny-slider.js"></script>
<script>
    $(document).ready(function(){
        $("#featuredlisting_loop").show();
        var slider = tns({
            container: '#featuredlisting_loop',
            items: 3,
            rewind: true,
            swipeAngle: false,
            speed: 400,
            controls: false,
            mouseDrag:  true,
            autoWidth: true,
            nav: false,
            gutter: 10
         });
    })
   
</script>
@endpush