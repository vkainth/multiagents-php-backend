@php
$all_banners = [
    ['banner'=>'1.png?v=2', 'event'=> 'banner-buyer-for-building'],
    ['banner'=>'2.png?v=2', 'event'=> 'banner-list-where-buyer'],
    ['banner'=>'3.png?v=2', 'event'=> 'banner-insane-traffic'],
    ['banner'=>'4.png?v=3', 'event'=> 'banner-registered-users'],
    ['banner'=>'5.png?v=2', 'event'=> 'banner-your-buyer-here']
];

$random_number = rand(0, (count($all_banners)-1));
if(!empty($main_listing)){
    $random_number = rand(1, (count($all_banners)-1));
}
@endphp
<script>
     var metadata = {};
@php
if(!empty($building_url)){
    @endphp
         metadata = {'building_url': '{{$building_url}}'};
    @php
}
else if(!empty($main_listing)){
    @endphp
         metadata = {'listing_url': 'https://www.bccondosandhomes.com/listing/{{$main_listing->slug}}'};
    @php
}
@endphp
</script>

<div class="row">
    <div class="col-md-12" style="text-align: center">
        <a href="mailto:info@bccondosandhomes.com"><img src="https://www.bccondosandhomes.com/assets/img/banners/<?=$all_banners[$random_number]['banner']?>" alt="banner -side-r" style="max-width: 300px;"></a>
    </div>
</div>