@push('after-styles')
<style>
.photo_grid_container {
	height: 100%;
	max-height: 600px;
	display: grid;
	grid-template-columns: 2fr 1fr 1fr;
	grid-template-rows: 1fr 1fr; /*1fr;*/
	gap: 0px 0px;
	grid-auto-flow: row;
	grid-template-areas:
	"photo_grid_5_1 photo_grid_4_1 photo_grid_4_2"
	"photo_grid_5_1 photo_grid_4_3 photo_grid_4_4"
	". . .";
}
.photo_grid_5_1 { grid-area: photo_grid_5_1; }
.photo_grid_4_1 { grid-area: photo_grid_4_1; }
.photo_grid_4_2 { grid-area: photo_grid_4_2; }
.photo_grid_4_3 { grid-area: photo_grid_4_3; }
.photo_grid_4_4 { grid-area: photo_grid_4_4; }
.photo_grid_container .grid-item{overflow: hidden;align-items: center;}
</style>
@endpush
@php
$_imgWHsSearch=['w=568','w=300','w=600','w=700','w=1600','w=283','h=212','h=426'];
$_imgWHsReplace = ['w=12','w=12','w=12','w=12','w=12','w=12','h=12','h=12'];
@endphp
@if( 0 && Auth::user())
{{-- <div class=""> --}}
{{-- <a href="/login?redirect={{url()->current()}}" class="image-effect listing-detail__image" style="display:block; background-image: url({{asset('/frontend/images/greviews_hbig_221101.png')}});height: 100%;min-height: 362px;background-position: center;background-size: contain;background-color: #31c4ff;background-repeat: no-repeat;">&nbsp;</a> --}}
{{-- </div> --}}
{{-- <div class="photo_grid_container">
	<div class="grid-item listing-detail__image image-effect"  style="background-image: url({{asset('/frontend/images/greviews_hbig_221101.png')}});" >&nbsp;</div>
</div> --}}
@elseif( 1 && Auth::user())
<div class="photo_grid_container">
  <div class="grid-item photo_grid_5_1 listing-detail__image image-effect">{{($photo_grid_5_1??'')}}</div>
  <div class="grid-item photo_grid_4_1 listing-detail__image image-effect">{{($photo_grid_4_1??'')}}</div>
  <div class="grid-item photo_grid_4_2 listing-detail__image image-effect">{{($photo_grid_4_2??'')}}</div>
  <div class="grid-item photo_grid_4_3 listing-detail__image image-effect">{{($photo_grid_4_3??'')}}</div>
  <div class="grid-item photo_grid_4_4 listing-detail__image image-effect">{{($photo_grid_4_4??'')}}</div>
</div>
@else 
<div class="photo_grid_container">
  <div class="grid-item photo_grid_5_1 listing-detail__image image-effect">{!!str_replace($_imgWHsSearch,$_imgWHsReplace,($photo_grid_5_1??''))!!}</div>
  <div class="grid-item photo_grid_4_1 listing-detail__image image-effect">{!!str_replace($_imgWHsSearch,$_imgWHsReplace,($photo_grid_4_1??''))!!}</div>
  <div class="grid-item photo_grid_4_2 listing-detail__image image-effect">{!!str_replace($_imgWHsSearch,$_imgWHsReplace,($photo_grid_4_2??''))!!}</div>
  <div class="grid-item photo_grid_4_3 listing-detail__image image-effect">{!!str_replace($_imgWHsSearch,$_imgWHsReplace,($photo_grid_4_3??''))!!}</div>
  <div class="grid-item photo_grid_4_4 listing-detail__image image-effect">{!!str_replace($_imgWHsSearch,$_imgWHsReplace,($photo_grid_4_4??''))!!}</div>
</div>
@endif
<div class="clearfix"></div>
