@can('dev-dj')
{{-- string-regex-search-sublime img-no-width : <img(?![^>]+width).*> --}}
@php $_staged=request()->input('staged'); config(['app.debug'=>true]) @endphp
@if(request()->input('details','')=='offer')
{{ dd($listing->get_commission_details()) }}
@elseif( request()->input('apimode','')=='true' )
{{ dd($__data) }}
{{ dd($listing) }}
@endif
@endcan
@php
function startsWithNumber($str) {
        return preg_match('/^\d/', $str) === 1;
}
$floorplan = $listing->getFloorPlan();
$floorplate = null;
if($listing->status=='Active' && $listing->getType()=='House' ){
        $building = null;
}else{
        $building = $listing->get_building();
}
if(!(Auth::user())){
$building = null; // [as per last demand no-building for guest-sold-listings]
}
$tours = $listing->get_tours();
$building_name = null;
$building_url= null;
$media_displayed = false;
$matterport_url = false;
$videotour_url = false;
$virtualtour_url = false;
$active_listings = null;
$sold_listings = null;
$building_matterport = null;
if($building){
        $period="2year";
        $interval = "2 YEAR";
        $interval_recentSolds = "6 month"; //[updated:16-03-2022 from "2 YEAR" to "6months"]
        $beds = 'all';
        $maxBeds = 0;
        $isTownhouse = 0;
        $isPenthouse = 0;
        $maxBedsSold = 0;
        $isTownhouseSold = 0;
        $isPenthouseSold = 0;
        $total_listprice = 0;
        $total_area = 0;
        $total_listarea=0;
        $total_price_sqft = 0;
        $total_days_on_market_active = 0;
        $building_matterport = $building->matterport_url();
        $active_listings = $building->active_listings()->get();
        $sold_listings = $building->sold_listings($interval_recentSolds)->get();
        $total_active_listings = count($active_listings);
        $total_soldlistings = count($sold_listings);
        $total_soldprice = 0;
        $total_soldarea = 0;
        $price_per_sqft = 0;
        $total_soldpricesqft = 0;
        $total_days_on_market_sold = 0;
        foreach($active_listings as $_listing){
                $total_listprice = $total_listprice + $_listing->listprice_2;
                $total_area = $total_area+$_listing->livingarea_2;
                $total_listarea = $total_listarea+$_listing->livingarea_2;
                if($_listing->livingarea_2 > 0){
                        $price_per_sqft = $_listing->listprice_2/$_listing->livingarea_2;
                }
                else{
                        $price_per_sqft = 1;
                }
                
                $total_price_sqft = $total_price_sqft+$price_per_sqft;
                if($_listing->bedrooms > $maxBeds){
                        $maxBeds = $_listing->bedrooms;
                }
                if($_listing->type == 'Townhouse'){
                        $isTownhouse = 1;
                }
                if(substr_count($_listing->home_style, 'Penthouse') > 0){
                        $isPenthouse = 1;
                }
                $total_days_on_market_active = $total_days_on_market_active+$listing->active_days_on_market();
        }
        foreach($sold_listings as $_listing){
                        $total_soldprice = $total_soldprice + $_listing->soldprice_2;
                        $total_area = $total_area+$_listing->livingarea_2;
                        $total_soldarea = $total_soldarea+$_listing->livingarea_2;
                        if($_listing->livingarea_2 > 0){
                                $price_per_sqft = $_listing->listprice_2/$_listing->livingarea_2;
                        }
                        else{
                                $price_per_sqft = 1;
                        }
                        //$price_per_sqft = $_listing->soldprice_2/$_listing->livingarea_2;
                        $total_soldpricesqft = $total_soldpricesqft+$price_per_sqft;
                        $total_days_on_market_sold = $total_days_on_market_sold+$_listing->days_on_market();
        }
        $sold_listings2 = $building->sold_listings('2 YEAR');
                foreach($sold_listings2 as $_listing){
                        if($_listing->bedrooms > $maxBedsSold){
                                $maxBedsSold = $_listing->bedrooms;
                        }
                        if($_listing->type == 'Townhouse'){
                                $isTownhouseSold = 1;
                        }
                        if(substr_count($_listing->home_style, 'Penthouse') > 0){
                                $isPenthouseSold = 1;
                        }
                }
        $avgprice_sqlft =0;
        $avg_listing_price = 0;
        $avg_price_sqft = 0;
        $avg_area=0;
        $avg_days_on_market_active = 0;
        $avg_soldprice = 0;
        $avg_soldarea = 0;
        $avg_soldpricesqft = 0;
        $avg_days_on_market_sold = 0;
        $total_price = $total_listprice+$total_soldprice;
        if($total_price>0 && $total_area>0){
                $avgprice_sqlft = $total_price/$total_area;
        }

        if($total_listprice > 0 && $total_active_listings > 0){
                $avg_listing_price = $total_listprice/$total_active_listings;
        }

        if($total_price_sqft > 0 && $total_active_listings > 0){
                $avg_price_sqft = $total_price_sqft/$total_active_listings;
        }
        if($total_listarea > 0 && $total_active_listings > 0){
                $avg_area = $total_listarea/$total_active_listings;
        }
        if($total_days_on_market_active>0 && $total_active_listings > 0){
                $avg_days_on_market_active = $total_days_on_market_active/$total_active_listings;
        }

        if($total_soldprice > 0 && $total_soldlistings > 0){
                $avg_soldprice = $total_soldprice/$total_soldlistings;
        }

        if($total_soldarea>0 && $total_soldlistings > 0){
                $avg_soldarea = $total_soldarea/$total_soldlistings;
        }

        if($total_soldpricesqft > 0 && $total_soldlistings > 0){
                $avg_soldpricesqft = $total_soldpricesqft/$total_soldlistings;
        }

        if($total_days_on_market_sold>0 && $total_soldlistings > 0){
                $avg_days_on_market_sold = $total_days_on_market_sold/$total_soldlistings;
        }
        $buildingPhotos = $building->photos()->get()->toArray();
        $building_additional_information = null;
        $building_additional_info_floorplan = null;
        $presale_listings = $building->pre_sale_listings()->get();
        if ($server_up == 'y' && $building->strata_no) {
        
                try{
                        // $building_additional_information = file_get_contents('https://www.bccondosandhomes.com/api_building/public/index.php?strata=' . $building->strata_no, 0, stream_context_create(["http" => ["timeout" => 2]]));
                        $cachedBldAdtnlInfo = Cache::get( 'buildingBcnApi__'.date("Ymd").'_strata-' .urlencode(trim($building->strata_no)).'_streetnum-'.urlencode(trim($building->street_no?:'')) );
                if(empty($cachedBldAdtnlInfo)){

                    $building_additional_information = file_get_contents('https://www.bccondosandhomes.com/api_building/public/?strata=' .urlencode(trim($building->strata_no)).'&streetnum='.urlencode(trim($building->street_no?:'')).'&refreshtoken='.date("Ymd") /* .date("Ymdhis") [enable-param("Ymdhis") for-fresh request every-second to avoid OS-caching]*/ , 0, stream_context_create(["http" => ["timeout" => 2]]));

                    Cache::put('buildingBcnApi__'.date("Ymd").'_strata-' .urlencode(trim($building->strata_no)).'_streetnum-'.urlencode(trim($building->street_no?:'')), $building_additional_information, 60*24);

                }else{
                    $building_additional_information = $cachedBldAdtnlInfo;
                }
                        $cachedBldFloorplan = Cache::get( 'buildingBcnApiFloorplan__'.date("Ymd").'_strata-' .urlencode(trim($building->strata_no)) );
                        if(empty($cachedBldFloorplan)){
                            $building_additional_info_floorplan = file_get_contents('https://www.bccondosandhomes.com/api_building/public/index.php?strata=' . $building->strata_no.'&task=floorplan', 0, stream_context_create(["http" => ["timeout" => 2]]));
                            Cache::put('buildingBcnApiFloorplan__'.date("Ymd").'_strata-' .urlencode(trim($building->strata_no)), $building_additional_info_floorplan ?: 'null', 60*24);
                        } else {
                            $building_additional_info_floorplan = ($cachedBldFloorplan === 'null') ? false : $cachedBldFloorplan;
                        }
                }
                catch (Exception $e) {}
                
                if($building_additional_information){
                        $building_additional_information = json_decode($building_additional_information, true);
                }
                if($building_additional_info_floorplan){
                        $building_additional_info_floorplan = json_decode($building_additional_info_floorplan, true);
                        if(!$floorplan && $listing->suite_no >0){
                                if($building_additional_info_floorplan && array_key_exists('building', $building_additional_info_floorplan['data']) && array_key_exists('floor_plans', $building_additional_info_floorplan['data']['building'])){
                                        foreach($building_additional_info_floorplan['data']['building']['floor_plans'] as $fp){
                                                if($fp['suite'] == $listing->suite_no){
                                                        $floorplan = $fp['floorplanimages'];
                                                        break;
                                                }
                                        }
                                }
                        }
                        if($building_additional_info_floorplan && array_key_exists('building', $building_additional_info_floorplan['data']) && array_key_exists('floor_plates', $building_additional_info_floorplan['data']['building'])){
                                if($listing->suite_no >0){
                                        $floor_no = substr($listing->suite_no, 0, -2);
                                        foreach($building_additional_info_floorplan['data']['building']['floor_plates'] as $fp){
                                                if(trim($fp['floor']) == "Floor ".$floor_no){
                                                        $floorplate = $fp['floorplateimages'];
                                                        break;
                                                }
                                        }
                                }
                        }
                }
        }
}
$image_index = 0;
$printedPhotosCount = 1;
// matterport - removed for sold on-demand [date:15-11-2021]
if($is_authenticated){ // [Video for non-logged-in users re-enable/disable][enabled:29-08-2022,disabled:25-10-2022]
        if(($listing->status == 'Active') && $tours && array_key_exists('matterport', $tours)){
                $matterport_url = $tours['matterport']['video_url']."&brand=0";
        }
        elseif(($listing->status == 'Active') && strpos($listing->virtualtoururl, 'matterport') !== false){
                $matterport_url = $listing->virtualtoururl."&brand=0";
        }
        elseif(strpos($listing->virtualtoururl, 'youtu') !== false){
                $videotour_url = $listing->getYoutubeEmbedUrl($listing->virtualtoururl);
                // elseif-block added on [15-Apr-2021]
        }

        if($tours && array_key_exists('video', $tours)){
                if(array_key_exists('vimeo_embed_url', $tours['video']) && $tours['video']['vimeo_embed_url']){
                        $videotour_url = $tours['video']['vimeo_embed_url'];
                }
                elseif(array_key_exists('youtube_embed_url', $tours['video']) && $tours['video']['youtube_embed_url']){
                        $videotour_url = $tours['video']['youtube_embed_url'];
                }
                else{
                        $videotour_url = "https://player.pixilink.com/".$tours['video']['tour_id'];
                }
        }

        if(strpos($matterport_url, 'http') !== 0){
                $matterport_url = null;
        }
        if(strpos($videotour_url, 'http') !== 0){
                $videotour_url = null;
        }


        if($tours && array_key_exists('virtual', $tours)){
                $virtualtour_url = "https://player.pixilink.com/".$tours['virtual']['tour_id'];
        }
} // [Video for non-logged-in users re-enable/disable][enabled:29-08-2022,disabled:25-10-2022]
if($building){
        $building_name = $building->name;
        $building_url = route('building-detail-page', $building->slug);
}
$breadcrumb_complex = null;
if (!$building_name && in_array($listing->type, ['Apartment','Townhouse']) && $listing->complex) {
        $breadcrumb_complex = ucwords(strtolower($listing->complex));
}

$addressAsH1tag = ltrim((($listing->suite_no)?$listing->suite_no. ' - ':''). Helper::properCasePlace( $listing->street_number.' '.ucwords(strtolower($listing->street_name)).' '.ucwords(strtolower($listing->street_type)) ), ' - ') ;

function remove_openhouse($description){
        $desc2 = substr($description, 100);
        $ohWords = ["Open House","OpenHouse","openhouse","Openhouse","Open house","open house","OPEN HOUSE","O H"];
        foreach($ohWords as $ohWord){
                if(strpos($desc2, $ohWord)){
                        $description = substr($description, 0, strpos($description, $ohWord));
                }               
        }
        /*
        // [code-reduced updated:09-08-2022] // [Delete if all ok post-Aug-2022]
        if(strpos($desc2, "Open House")){
                $description = substr($description, 0, strpos($description, "Open House"));
        }
        if(strpos($desc2, "OpenHouse")){
                $description = substr($description, 0, strpos($description, "OpenHouse"));
        }
        if(strpos($desc2, "openhouse")){
                $description = substr($description, 0, strpos($description, "openhouse"));
        }
        if(strpos($desc2, "Openhouse")){
                $description = substr($description, 0, strpos($description, "Openhouse"));
        }
        if(strpos($desc2, "Open house")){
                $description = substr($description, 0, strpos($description, "Open house"));
        }
        if(strpos($desc2, "open house")){
                $description = substr($description, 0, strpos($description, "open house"));
        }
        if(strpos($desc2, "OPEN HOUSE")){
                $description = substr($description, 0, strpos($description, "OPEN HOUSE"));
        }
        if(strpos($desc2, "O H")){
                $description = substr($description, 0, strpos($description, "O H"));
        }
        */
        return $description;
}

/**
 * [loginLinkHtml_aHref simple-function to generate login-url, instead of reapeated:route('listing_detail...) ]
 * @return [string] [login-url with-href-to current-listing]
 * Usage: <a href="{{loginLinkHtml_aHref()}}" >Login to View </a> inside this blade
 */
function loginLinkHtml_aHref(){
        global $listing;
        $theSlug = '';
        if (is_array($listing)) {
                $theSlug = $listing['slug'] ?? '';
        } elseif (is_object($listing)) {
                $theSlug = $listing->slug ?? '';
        }
        $rdctUrl = url()->current();
        if(!empty($theSlug)){
                $rdctUrl = route('listing-detail-page2', ['slug'=>$theSlug ]);
                // $rdctUrl = $listing->slug.'#sluggedUrl';
        }
        return '/login?redirect='.urlencode($rdctUrl);
}
/**
 * [loginLinkHtml_a4view simple function to generate html element <a> with href-to-login-url]
 * @param  string $attrsString [string of attributes eg: ' onclick="alert(\'Please Login!\');return false;"  ']
 * @param  string $text        [the text to show on the link, eg: 'Please click here to Login' ]
 * @return string              [the html-element <a href="..generated_url.." ..$attrString.. > $text </a> ]
 * Usage : {!! loginLinkHtml_a4view() !!}  , NOT: {{loginLinkHtml_a4view()}}
 */
function loginLinkHtml_a4view($attrsString='',$text='Login To View'){
        global $listing;
        $theSlug = $listing['slug']; // Because $listing here is non-object, so as an array
        $rdctUrl = url()->current();
        if(!empty($theSlug)){
                $rdctUrl = route('listing-detail-page2', ['slug'=>$theSlug ]);
                // $rdctUrl = $listing->slug.'#sluggedUrl';
        }
        return '<a href="/login?redirect='.urlencode($rdctUrl).'" '.$attrsString.'  >'.$text.'</a>';
}

/**
 * [Added: 31-03-2022] Proper-casing city/subarea of $liting. Advantages:: Avoid-repited-functions(ucfirst/ucwords(strtolower(..))), 
 */
if($listing->city){
        $listing->cityProperCased = Helper::properCasePlace($listing->city); // ucwords(strtolower($listing->city))
        $listing->cityEnsluged = Helper::enslugPlace($listing->city);
}
if($listing->subarea){
        $listing->subareaProperCased = Helper::properCasePlace($listing->subarea); // ucwords(strtolower($listing->subarea))
        $listing->subareaEnsluged = Helper::enslugPlace($listing->subarea);
}

/**
 * $jsonldSchema array for SCHEMA: json_ld
 * @var array
 */
$jsonldSchema =['BreadcrumbList'=>[] ];
if($building_name && $building_url){
        $jsonldSchema['BreadcrumbList']['trail-buildings']=[];

        // $jsonldSchema['BreadcrumbList']['trail-buildings'][]= ['url'=>url('/') , 'text'=>'Home'] ;
        $jsonldSchema['BreadcrumbList']['trail-buildings'][]= ['url'=>trim(route('city_buildings'),'-'), 'text'=>'Buildings'];
        if($listing->city){
                $jsonldSchema['BreadcrumbList']['trail-buildings'][]= ['url'=>trim(route('city_buildings',['city'=>str_replace(' ', '-', strtolower($listing->city))]),'-'), 'text'=> $listing->cityProperCased ];
        }
        if($listing->subarea){
                $jsonldSchema['BreadcrumbList']['trail-buildings'][]= ['url'=>trim(route('city_buildings',['city'=>str_replace(' ', '-', strtolower($listing->city)),'subarea'=>str_replace(' ', '-', strtolower($listing->subarea))]),'-'), 'text'=> $listing->subarea ];
        }
        if($building_name && $building_url) {
                $jsonldSchema['BreadcrumbList']['trail-buildings'][]= ['url'=>$building_url, 'text'=>$building_name." - ".$listing->street_number." ".ucwords(strtolower($listing->street_name))." ".ucfirst(strtolower($listing->street_type))];
        }
        $jsonldSchema['BreadcrumbList']['trail-buildings'][]= ['url'=>url()->current(), 'text'=>$addressAsH1tag];
}

$jsonldSchema['BreadcrumbList']['trail-search_listings']=[];
// $jsonldSchema['BreadcrumbList']['trail-search_listings'][]= ['url'=>url('/') , 'text'=>'Home'] ;
$jsonldSchema['BreadcrumbList']['trail-search_listings'][]= ['url'=>route('adv_search_listings'), 'text'=>'Search Listings'];
if($listing->city){
        $jsonldSchema['BreadcrumbList']['trail-search_listings'][]= ['url'=>trim(route('adv_search_listings',['city'=>(Helper::enslugPlace($listing->city))]),'-'), 'text'=>$listing->cityProperCased];
}
if($subarea_slug){
        // $jsonldSchema['BreadcrumbList']['trail-search_listings'][]= ['url'=>$subarea_slug}}, 'text'=>$listing->subareaProperCased];
}

if($listing->subarea){
        $jsonldSchema['BreadcrumbList']['trail-search_listings'][]= ['url'=>trim(route('adv_search_listings',['city'=>(Helper::enslugPlace($listing->city)),'subarea'=>(Helper::enslugPlace($listing->subarea)),'listing_status'=>strtolower($listing->status??'')]),'-'), 'text'=>$listing->subareaProperCased];
}
if($listing->type){
        $jsonldSchema['BreadcrumbList']['trail-search_listings'][]= ['url'=>route('adv_search_listings',['city'=>(Helper::enslugPlace($listing->city?:'')),'subarea'=>$listing->subarea?(Helper::enslugPlace($listing->subarea)):'','type'=>(Helper::enslugPlace($listing->type)),'listing_status'=>strtolower($listing->status??'')]), 'text'=>ucwords(strtolower($listing->type))];
}
$jsonldSchema['BreadcrumbList']['trail-search_listings'][]= ['url'=>url()->current(), 'text'=>$addressAsH1tag];

$buildingBcnApi = (!empty($building_additional_information['data']['building'])?$building_additional_information['data']['building']:null); // [shortened on:31-03-2022]
@endphp
@php
$firstname = '';
$lastname = '';
$email = '';
$phonenumber = '';
$user = false;
if(Auth::user()){
        $user = Auth::user();
        $firstname = $user->first;
        $lastname = $user->last;
        $email = $user->email;
        $phonenumber = ($user->phone_country_code??'').$user->phone;
}


$userIsPixiMember = (!empty($user->email) && in_array( substr(strstr($user->email,'@'),1), ['pixilink.com','bccondos.net','bccondosandhomes.com']) );

/**
 * $_FAQsCombined [added:05-09-2022]
 * @var array
 */

$_FAQsCombined = [];
// if(auth()->user()?->can('dev-dj-approve') && $listing->getFAQs()){
if($listing->getFAQs()){
        $_FAQsCombined = $listing->getFAQs();
        if($listing->getType()=='House' && ($building->amenity??false)){
                // $_FAQsCombined []= ['q'=>'Is this property air conditioned?', 'ans'=>$addressAsH1tag.' does '.((substr_count(strtoupper($building->amenity), 'AIR COND') > 0)?'':'NOT') .' have an air conditioner'];
        }

}

/**
 * generateTitleSectionString [created:31-03-2022]
 * @param  array/null $listing        [description]
 * @param  array/null $building       [description]
 * @param  array/null $buildingBcnApi [description]
 * @return String                 [description]
 */
function generateTitleSectionString($listing=null,$building=null,$buildingBcnApi=null) {
        global $floorplan /*, $_FAQsCombined*/;
        $titleArray = [''];
        $titleArray []= ucwords(strtolower($listing->streetaddress)).', '.$listing->cityProperCased;
        if($listing->mlsr_listing->bylaw_restrictions ?? false){
                $petsString = '';
                $restriction = strtolower($listing->mlsr_listing->bylaw_restrictions);
                $petsNrentals = false; $pets = 0; $rentals = 0;
                if(substr_count($restriction, 'pets not') > 0){ $petsNrentals = true; $pets -= 1; }
                if(substr_count($restriction, 'rentals not') > 0){ $petsNrentals = true; $rentals -= 1; }
                if(substr_count($restriction, 'pets all') > 0){ $petsNrentals = true; $pets += 1; }
                if(substr_count($restriction, 'rentals all') > 0){ $petsNrentals = true; $rentals += 1; }
                // ucwords(strtolower(str_replace([' Allowed,',','], ', ', $listing->mlsr_listing->bylaw_restrictions) ))
                // Only put in anything that is allowed if both are disallowed - then go No Pets or Rentals
                if($petsNrentals){
                        $t = $pets + $rentals;
                        $titleArray []= '|';
                        $_petsNrentalsAns = ($t<0)?'No Pets or Rentals' : ($t==2?'Pets & Rentals Allowed' : ($pets>0? 'Pets Allowed': ($rentals>0?'Rentals Allowed':'') ) ) ;
                        $titleArray []= $_petsNrentalsAns;
                        // $this->faqs []= ['q'=>'Are pets and rentals allowed', 'ans'=>''.ucfirst(strtolower($_petsNrentalsAns))];
                        // $this->_FAQsCombined []= ['q'=>'Are pets and rentals allowed', 'ans'=>''.ucfirst(strtolower($_petsNrentalsAns))];
                        // if(auth()->user()?->can('dev-dj')){
                        //      $listing->faqs []= ['q'=>'Are pets and rentals allowed', 'ans'=>''.ucfirst(strtolower($_petsNrentalsAns))];
                        // }
                }
        }
        $titleArray []= '|';
        // $titleArray []= ltrim((!empty($buildingBcnApi['construction_info']['levels'])?$buildingBcnApi['construction_info']['levels']:(!empty($building->levels)?$building->levels:'')) .' Level', ' Level' );
        if(!empty($buildingBcnApi['construction_info']['levels'])){
                $titleArray []= $buildingBcnApi['construction_info']['levels'] .' Level' ;
        }elseif(!empty($buildingBcnApi['technical_info']['units_in_development'])){
                $titleArray []= $buildingBcnApi['technical_info']['units_in_development'] .' Unit Development' ;
        }elseif(!empty($building->units_in_development)){
                $titleArray []= $building->units_in_development .' Unit Development' ;
        }
        $titleArray []= ucwords(str_replace(['frame - wood',' - '], ['wood - frame',' '], strtolower(!empty($building->construction)?$building->construction:'')));
        if(!empty($buildingBcnApi['building_condo_info']['title_to_land'])) {
                $titleArray []= ucwords(strtolower($buildingBcnApi['building_condo_info']['title_to_land']));
        }if(!empty($buildingBcnApi['technical_info']['developer_name'])){
                $titleArray []= 'by '.ucwords(strtolower($buildingBcnApi['technical_info']['developer_name']));
        }elseif(!empty($building->mgmt_name)){
                if(strpos(strtolower($building->mgmt_name), 'self') !== false ){
                        $titleArray []= 'Self Managed by Owners';
                }else{
                        // $titleArray []= 'Managed by '.ucwords(strtolower($building->mgmt_name));
                }
        }
        // $titleArray []= '| Hani & Les | BC Condos And Homes';

        $titleString = implode(' ', $titleArray);
        if(substr(trim($titleString),-1) == '|'){
                $titleString = ucwords(strtolower($listing->streetaddress)).', '.$listing->cityProperCased.', BC, '.$listing->postalcode;
                $titleString = ucwords(strtolower($listing->streetaddress));
                if($listing->status=='Active' && $listing->getType()=='House' ){
                        // $titleString .= ' For Sale @'.number_format($listing->listprice_2,0).' $'.$listing->pricePerSQFT().'/sqft in '.$listing->cityProperCased; // [updated to following line on:02-02-2022]
                        $titleString .= ', '.$listing->cityProperCased.', BC, '.$listing->type.' For Sale ';
                }elseif($listing->status=='Active'){
                        $titleString .= ' For Sale | ';
                        if($floorplan ?? $listing->getFloorPlan()){
                                $titleString .= 'View floor plan & comparables ';
                        }else{
                                $titleString .= 'View photos & recent sales in '. $listing->subareaProperCased.', '.$listing->cityProperCased.', BC';
                        }
                }
        }
        // if($listing->mlsr_listing->bylaw_restrictions){
        //      $petsString = '';
        //      $restriction = strtolower($listing->mlsr_listing->bylaw_restrictions);
        //      $petsNrentals = false; $pets = 0; $rentals = 0;
        //      if(substr_count($restriction, 'pets not') > 0){ $petsNrentals = true; $pets -= 1; }
        //      if(substr_count($restriction, 'rentals not') > 0){ $petsNrentals = true; $rentals -= 1; }
        //      if(substr_count($restriction, 'pets all') > 0){ $petsNrentals = true; $pets += 1; }
        //      if(substr_count($restriction, 'rentals all') > 0){ $petsNrentals = true; $rentals += 1; }
        //      // ucwords(strtolower(str_replace([' Allowed,',','], ', ', $listing->mlsr_listing->bylaw_restrictions) ))
        //      // Only put in anything that is allowed if both are disallowed - then go No Pets or Rentals
        //      if($petsNrentals){
        //              $t = $pets + $rentals;
        //              $titleString .= ' | ';
        //              $titleString .= ($t<0)?'No Pets or Rentals' : ($t==2?'Pets & Rentals Allowed' : ($pets>0? 'Pets Allowed': ($rentals>0?'Rentals Allowed':'') ) ) ;
        //      }
        // }
        return  trim($titleString); //.' | Hani & Les | BC Condos And Homes';
}

global $authUser;
global $isUserPremiumMember;
$authUser = auth()->user();
if($authUser){
        $isUserPremiumMember = $authUser->isPremiumMember();
}
else{
        $isUserPremiumMember = false;
}


function LoginToViewText($txt = NULL){
        global $authUser;
        global $isUserPremiumMember;

        if(!$authUser){
                return 'Login To View';
        }
        elseif($authUser && $authUser->stripe_id && !$isUserPremiumMember){
                return $txt?$txt:'Subscribe';
        }
        elseif(!$isUserPremiumMember){
                return $txt?$txt:'Subscribe';
        }

}

function LoginToViewLink($linkhref){
        global $authUser;
        global $isUserPremiumMember;

        if(!$authUser){
                return $linkhref;
        }
        elseif(!$isUserPremiumMember){
                return route('subscription_pricing_table');
        }
}

@endphp
{{-- @extends('frontend.layouts.default') --}}
@extends('frontend.layouts.default_mobile')
{{-- @if(auth()->user()?->can('dev-dj')) {{config(['app.debug'=>true])}} --}}
@if($listing->status == 'Active')
@section('title'){!!generateTitleSectionString($listing,$building,$buildingBcnApi)!!}@endsection
@elseif($listing->status == 'Active')
{{-- [updated:31-03-2022]: [address] | [Restrictions/bylaw] |  [x] level [construction] [property type] by [developer] --}}
@section('title')
{{-- {{ucwords(strtolower($listing->streetaddress))}} --}}{{$addressAsH1tag}} | 
{{ ltrim((!empty($buildingBcnApi['construction_info']['levels'])?$buildingBcnApi['construction_info']['levels']:(!empty($building->levels)?$building->levels:'')) .' Level', ' Level' )}} 
{{ucwords(str_replace(['frame - wood',' - '], ['wood - frame',' '], strtolower(!empty($building->construction)?$building->construction:'')))}} 
@if(!empty($buildingBcnApi['building_condo_info']['title_to_land']))
{{ucwords(strtolower($buildingBcnApi['building_condo_info']['title_to_land']))}} 
@endif
@if(!empty($buildingBcnApi['technical_info']['developer_name']))
by {{ucwords(strtolower($buildingBcnApi['technical_info']['developer_name']))}} 
@endif
{{-- @if($listing->mlsr_listing->bylaw_restrictions)
| {{ucwords(strtolower(str_replace([' Allowed,',','], ', ', $listing->mlsr_listing->bylaw_restrictions) ))}} 
@endif --}}

{{-- @if($building && $building->construction || !empty($building->levels) || !empty($buildingBcnApi['construction_info']['levels']) )
| @if(!empty($buildingBcnApi['construction_info']['levels']) || !empty($building->levels)){{$building->levels?(!empty($buildingBcnApi['construction_info']['levels'])?$buildingBcnApi['construction_info']['levels']:''):''}} Level @endif{{ucwords(strtolower(str_replace(' - ', ' ', $building->construction?:'')))}} 
@endif --}}
{{-- @if(!empty($buildingBcnApi['building_condo_info']['title_to_land']))
| {{ucwords(strtolower($buildingBcnApi['building_condo_info']['title_to_land']))}} 
@endif --}}
{{-- @if($listing->mlsr_listing->bylaw_restrictions)
| {{ucwords(strtolower(str_replace([' Allowed,',','], ', ', $listing->mlsr_listing->bylaw_restrictions) ))}} 
@endif --}}
| Hani & Les | BC Condos And Homes @endsection
@elseif($listing->status == 'Active')
@section('title'){{ucwords(strtolower($listing->streetaddress))}}, {{ucwords($listing->city)}}, BC, {{$listing->postalcode}} - {{(strtolower($listing->getType())!='other'?($listing->getType()=='Apartment'?'Condo':$listing->getType()):'Property')}} For Sale {{'@'.$listing->listprice}} - {{$listing->bedrooms}} Bed, {{$listing->bathstotal}} Bath, {{$listing->livingarea}} | Hani & Les | BC Condos And Homes @endsection
@elseif($listing->status == 'Sold')
@php
$_seo_type = ($listing->getType()=='Apartment') ? 'Condo' : ((strtolower($listing->getType())=='other') ? 'Property' : $listing->getType());
$_seo_title = ucwords(strtolower($listing->streetaddress)).' — Sold '.$_seo_type.' in '.$listing->cityProperCased.', BC';
if($listing->bedrooms) $_seo_title .= ' | '.$listing->bedrooms.' Bed';
if($listing->bathstotal) $_seo_title .= ' · '.$listing->bathstotal.' Bath';
$_seo_title .= ' | Hani & Les | BC Condos And Homes';
@endphp
@section('title'){{ $_seo_title }}@endsection
@else
@section('title'){{ucwords(strtolower($listing->streetaddress))}}, {{$listing->cityProperCased}}, BC, {{$listing->postalcode}}  | Hani & Les | BC Condos And Homes @endsection
@endif
@php
$_seo_beds   = $listing->bedrooms  ? $listing->bedrooms.'-bed'   : '';
$_seo_baths  = $listing->bathstotal ? $listing->bathstotal.'-bath' : '';
$_seo_bedbath = implode(', ', array_filter([$_seo_beds, $_seo_baths]));
$_seo_sqft   = (!empty($listing->mlsr_listing->livingarea_2) && $listing->mlsr_listing->livingarea_2 > 0) ? number_format($listing->mlsr_listing->livingarea_2).' sq ft' : ($listing->livingarea ? $listing->livingarea.' sq ft' : '');
$_seo_year   = $listing->yearbuilt ? 'built '.$listing->yearbuilt : '';
$_seo_extras = implode(', ', array_filter([$_seo_sqft, $_seo_year]));
$_seo_subarea = $listing->subareaProperCased ?? $listing->subarea ?? '';
$_seo_desc = 'View this sold'
    .($_seo_bedbath ? ' '.$_seo_bedbath : '')
    .' '.(isset($_seo_type) ? $_seo_type : (($listing->getType()=='Apartment')?'Condo':(strtolower($listing->getType())=='other'?'Property':$listing->getType())))
    .' at '.ucwords(strtolower($listing->streetaddress))
    .($_seo_subarea ? ', '.$_seo_subarea : '')
    .', '.$listing->cityProperCased.', BC'
    .($_seo_extras ? ' ('.$_seo_extras.')' : '')
    .'. Sign in free to unlock the sold price and compare with recent sales.';
if(strlen($_seo_desc) > 155) $_seo_desc = substr($_seo_desc, 0, 152).'...';
@endphp
@section('meta_description'){{ $_seo_desc }}@endsection
@section('meta')
        <link rel="canonical" href="{{$canonicalUrl}}" />
        <meta property="og:site_name" content="Hani & Les | BC Condos And Homes" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://www.bccondosandhomes.com/listing/{{$listing->slug}}" />
        <meta property="og:title" content="{{trim(str_replace(['  ',"\r","\n"], [' ','',''], View::yieldContent('title', 'Hani & Les | BC Condos And Homes')))}}" />
        <meta property="og:description" content="{{trim(str_replace(['  ',"\r","\n"], [' ','',''], View::yieldContent('meta_description', 'Hani & Les | BC Condos And Homes')))}}" />
        <meta property="og:image" content="{{$listing->mainpicurl ?: 'https://www.bccondosandhomes.com/assets/img/no-image-800-600.png'}}" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="{{trim(str_replace(['  ',"\r","\n"], [' ','',''], View::yieldContent('title', 'Hani & Les | BC Condos And Homes')))}}" />
        <meta name="twitter:description" content="{{trim(str_replace(['  ',"\r","\n"], [' ','',''], View::yieldContent('meta_description', 'Hani & Les | BC Condos And Homes')))}}" />
        <meta name="twitter:image" content="{{$listing->mainpicurl ?: 'https://www.bccondosandhomes.com/assets/img/no-image-800-600.png'}}" />
@endsection
@section('content')
@include('frontend.includes.guest_view_gate')
@include('frontend.includes.header')
@push('before-styles')
<link rel="stylesheet" type="text/css" href="{{asset('frontend/plugins/slick/slick.css')}}" @if (Browser::isMobile()) media="print" onload="this.media='all'" @endif />
<link rel="stylesheet" type="text/css" href="{{asset('frontend/plugins/slick/slick-theme.css')}}" @if (Browser::isMobile()) media="print" onload="this.media='all'" @endif />
<link rel="stylesheet" type="text/css" href="{{asset('frontend/css/bootstrap-datetimepicker.min.css')}}" @if (Browser::isMobile()) media="print" onload="this.media='all'" @endif />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@latest/dist/css/splide.min.css" @if (Browser::isMobile()) media="print" onload="this.media='all'" @endif />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.5.0/css/swiper.min.css" @if (Browser::isMobile()) media="print" onload="this.media='all'" @endif />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" integrity="sha512-H9jrZiiopUdsLpg94A333EfumgUBpO9MdbxStdeITo+KEIMaNfHNvwyjjDJb+ERPaRS6DpyRlKbvPUasNItRyw==" crossorigin="anonymous" @if (Browser::isMobile()) media="print" onload="this.media='all'" @endif />
@endpush
@section('body-classes') ListingDetailPage @endsection

@if (false)
{{-- [disabled:07-03-2023] --}}
<div class="listing__viewing--header hidden-xs">
        <div class="container">
                <div class="row">
                        <div class="clearfix">
                                <div class="col-lg-10 col-md-10 col-sm-9 col-xs-12">
                                        <div class="listing-detail__address listing-detail-page__address">
                                                <div class="listing-detail__address-headline">
                                                        @if($listing->getType() == 'Apartment' && $listing->suite_no){{$listing->suite_no}} - @endif{{$listing->street_number}} {{$listing->street_name}} {{$listing->street_type}}, {{$listing->city}}, {{$listing->province}} 
                                                </div>
                                        </div>
                                        <div class="listing-detail__price">@if($listing->status == 'Sold' && Auth::user())@component('frontend.components.altblur'){{Helper::money_format('%.0n', $listing->soldprice_2)}}@endcomponent  @elseif($listing->status=='Active') {{$listing->listprice}} @endif</div>
                                </div>
                                <div class="col-lg-2 col-md-2 col-sm-3 col-xs-12" style="display: none;">
                                        <div class="listing-detail__request-showing">
                                                @if($listing->status == 'Active')
                                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#viewingModal">Book A Viewing</button>
                                                @endif
                                        </div>
                                </div>
                                <div class="col-lg-2 col-md-2 col-sm-3 col-xs-12" style="">
                                        <div class="listing-detail__request-showing">
                                                @if($listing->status == 'Active')
                                                <a class="btn btn-primary" href="#incformhsmhxs_bookappointment" style="padding:10px 20px;">Schedule A Viewing</a>
                                                @endif
                                        </div>
                                </div>
                        </div>
                </div>
        </div>
</div>
@endif

<div class="main" role="main">

        <div class="container listing__detail--header">
                <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                                <div class="listing-detail__breadcrumb" style="margin-top:0px;">
                                        {{--
                                        Changed-style-to-match-building-style(with-bootstrap): [19-Aug-2021]
                                        {{strtoupper($listing->city)}}
                                        @if($listing->type) > {{$listing->type}}@endif > <a href="/{{$subarea_slug}}">{{$listing->subarea}}</a>@if($building_name) > <a href="{{$building_url}}" class="@if($listing->status == 'Active') active @else sold @endif" rel="popover" data-content="Click here to learn more about this Building." >{{$building_name}}</a>@endif 
                                        --}}
                                        {{-- Commented on 20-01-2022
                                        <div class="">
                                                <ol class="breadcrumb small" style="margin-bottom:0;" >
                                                        <li class="breadcrumb-item"><a href="{{url('/')}}">Home</a></li>
                                                        @if($listing->city)<li class="breadcrumb-item"><a href="{{trim(route('city_buildings',['city'=>str_replace(' ', '-', strtolower($listing->city))]),'-')}}">{{$listing->cityProperCased}}</a></li>@endif
                                                        @if(false && $listing->type)<li class="breadcrumb-item"><a >{{ucwords($listing->type)}}</a></li>@endif
                                                        @if($subarea_slug)<li class="breadcrumb-item"><a href="/{{$subarea_slug}}">{{$listing->subarea}}</a></li>@endif
                                                        @if($building_name && $building_url) 
                                                        <li class="breadcrumb-item active"><a href="{{$building_url}}" class="@if($listing->status == 'Active') active @elseif($listing->status == 'Sold') sold @endif" rel="tooltip" data-placement="bottom" data-content="Click here to learn more about this Building." title="Click here to learn more about this Building."  data-toggle="tooltip"> {{startsWithNumber($building_name)?$building_name:$building_name." - ".$listing->street_number." ".ucwords(strtolower($listing->street_name))}} {{ucfirst(strtolower($listing->street_type))}} </a></li>
                                                        @endif 
                                                </ol>
                                        </div>
                                        --}}
                                        <div class="">
                                                <ol class="breadcrumb small" style="margin-bottom:0;" >
                                                        {{-- <li class="breadcrumb-item"><a href="{{url('/')}}"><svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="24" height="24" viewBox="0 0 24 24" style=" fill:#000000;"><path d="M 12 2.0996094 L 1 12 L 4 12 L 4 21 L 11 21 L 11 15 L 13 15 L 13 21 L 20 21 L 20 12 L 23 12 L 12 2.0996094 z M 12 4.7910156 L 18 10.191406 L 18 11 L 18 19 L 15 19 L 15 13 L 9 13 L 9 19 L 6 19 L 6 10.191406 L 12 4.7910156 z"></path></svg></a></li> --}}
                                                        @if($building_name && $building_url)
                                                        {{-- [disabled:31-03-2023 on demand]
                                                        <li class="breadcrumb-item"><a href="{{url('/')}}">Home</a></li>
                                                        <li class="breadcrumb-item"><a href="{{trim(route('city_buildings'),'-')}}">Buildings</a></li>
                                                        --}}
                                                        @if($listing->city)<li class="breadcrumb-item"><a href="{{trim(route('city_buildings',['city'=>(Helper::enslugPlace($listing->city))]),'-')}}">{{$listing->cityProperCased}}</a></li>@endif
                                                        {{-- @if($listing->type)<li class="breadcrumb-item"><a >{{ucwords($listing->type)}}</a></li>@endif --}}
                                                        {{-- @if($subarea_slug)<li class="breadcrumb-item"><a href="/{{$subarea_slug}}">{{$listing->subarea}}</a></li>@endif --}}
                                                        @if($listing->subarea)<li class="breadcrumb-item"><a href="{{trim(route('city_buildings',['city'=>(Helper::enslugPlace($listing->city)),'subarea'=>(Helper::enslugPlace($listing->subarea))]),'-')}}">{{$listing->subarea}}</a></li>@endif
                                                        @if($building_name && $building_url) 
                                                        {{-- [SPLIT: was one combined building+address crumb; now building is its own crumb, address stays as final crumb below] --}}
                                                        <li class="breadcrumb-item"><a href="{{$building_url}}" class="@if($listing->status == 'Active') active @elseif($listing->status == 'Sold') sold @endif" rel="tooltip" data-placement="bottom" data-content="Click here to learn more about this Building." title="Click here to learn more about this Building."  data-toggle="tooltip"> {{$building_name}} </a></li>
                                                        {{-- @if($userIsPixiMember)
                                                        <li class="breadcrumb-item"><a href="{{route('adv_search_listings',['city'=>(Helper::enslugPlace($listing->city?:'')),'subarea'=>$listing->subarea?(Helper::enslugPlace($listing->subarea)):'','type'=>$listing->type?(Helper::enslugPlace($listing->type)):''])}}"><span class="pixidev-demo-preview">- </span>Listings</a></li>
                                                        <li class="breadcrumb-item"><a href="{{url()->current()}}">#{{$listing->listingid}}</a></li>
                                                        @endif --}}
                                                        @endif
                                                        @elseif($breadcrumb_complex)
                                                        @if($listing->city)<li class="breadcrumb-item"><a href="{{trim(route('city_buildings',['city'=>Helper::enslugPlace($listing->city)]),'-')}}">{{$listing->cityProperCased}}</a></li>@endif
                                                        @if($listing->subarea)<li class="breadcrumb-item"><a href="{{trim(route('city_buildings',['city'=>Helper::enslugPlace($listing->city),'subarea'=>Helper::enslugPlace($listing->subarea)]),'-')}}">{{$listing->subarea}}</a></li>@endif
                                                        <li class="breadcrumb-item active">{{$breadcrumb_complex}}</li>
                                                        @else 
                                                        {{-- [disabled:31-03-2023 on demand]
                                                        <li class="breadcrumb-item"><a href="{{url('/')}}">Home</a></li>
                                                        <li class="breadcrumb-item"><a href="{{route('adv_search_listings')}}">Search Listings</a></li>
                                                        --}}
                                                        @if($listing->city)<li class="breadcrumb-item"><a href="{{trim(route('adv_search_listings',['city'=>(Helper::enslugPlace($listing->city))]),'-')}}">{{$listing->cityProperCased}}</a></li>@endif
                                                        {{-- @if($subarea_slug)<li class="breadcrumb-item"><a href="/{{$subarea_slug}}">{{$listing->subarea}}</a></li>@endif --}}
                                                        @if($listing->subarea)<li class="breadcrumb-item"><a href="{{trim(route('adv_search_listings',['city'=>(Helper::enslugPlace($listing->city)),'subarea'=>(Helper::enslugPlace($listing->subarea))]),'-')}}">{{$listing->subarea}}</a></li>@endif
                                                        @if($listing->type)<li class="breadcrumb-item"><a href="{{route('adv_search_listings',['city'=>(Helper::enslugPlace($listing->city)),'subarea'=>(Helper::enslugPlace($listing->subarea)),'type'=>(Helper::enslugPlace($listing->type))])}}">{{ucwords($listing->type)}}</a></li>@endif
                                                        @endif
                                                        <li class="breadcrumb-item"><a href="{{url()->current()}}" > {{$addressAsH1tag}}</a></li>
                                                </ol>
                                        </div>
                                </div>
                        </div>
                        <div class="col-md-9 col-sm-12 col-xs-12">
                                <div class="listing-detail__address listing-detail-page__address">
                                        <h1 style="font-size:32px">
                                                {{-- {{$addressAsH1tag}} --}}
                                                {{-- @if($listing->suite_no){{$listing->suite_no}} - @endif{{$listing->street_number}} {{ucwords(strtolower($listing->street_name))}} {{ucwords(strtolower($listing->street_type))}}, --}}
                                                {{-- {{Helper::deslugPlace(strtolower($listing->streetaddress))}}, --}}
                                                {{trim($addressAsH1tag.', '.$listing->cityProperCased.($listing->province=='BC'?'':', '.strtoupper($listing->province)).', '.$listing->postalcode, ', ' )}}
                                        </h1>
                                        <h2 class="hidden-xs" style="font-size:22px">
                                                @if($listing->status == 'Active')
                                                <span class="hidden-xs">{{$listing->bedrooms?:''}} Bed, {{$listing->bathstotal?:''}} Bath {{(strtolower($listing->getType())!='other'?($listing->getType()=='Apartment'?'Condo':$listing->getType()):'Property')}} FOR SALE in {{$listing->subarea}} </span>MLS: {{$listing->listingid}}
                                                @else
                                                {{-- {{ucwords(strtolower($listing->streetaddress))}}, {{$listing->cityProperCased}} --}}
                                                <span class="hidden-xs">{{$listing->bedrooms?:''}} Bed, {{$listing->bathstotal?:''}} Bath {{(strtolower($listing->getType())!='other'?$listing->getType():'Property')}} in {{$listing->subarea}} </span>@auth MLS: {{$listing->listingid}}@endauth
                                                @endif
                                        </h2>
                                        <!--<h3>
                                                @if($listing->type){{$listing->type}}&nbsp;&nbsp;&nbsp;@endif
                                                @if($building_name)
                                                <a href="{{$building_url}}" class="@if($listing->status == 'Active') active @else sold @endif" rel="popover" data-content="Click here to learn more about this Building." style="text-decoration:underline;">{{$building_name}}</a> - 
                                                @endif 
                                                @if($subarea_slug)
                                                <a href="/{{$subarea_slug}}">{{$listing->subarea}}</a>
                                                @else
                                                {{$listing->subarea}}
                                                @endif
                                        </h3>-->
                                </div>
                                {{--@if($listing->status == 'Active')--}}
                                <div class="listing-detail__info listing-detail-page__info active hidden-sm hidden-xs">
                                        <form id="toggle_favorite" action="" method="get">
                                                <input type="hidden" name="id" id="listingid" value="{{$listing->listingid}}">
                                                <input type="hidden" name="add" id="favorite_value" value="">
                                        </form>
                                        <div class="text-right share-fav__buttons" style="/*padding:0px 15px 0 0; margin:0*/;">
                                                <div class="toggle__share">
                                                        <div class="share__button" id="shareButton" style="margin-bottom:2px;">
                                                                <a  onclick="openShareOptions()" href="javascript:;" class="">
                                                                        <p {{-- onclick="openShareOptions()" --}} class="share_property_button--img">
                                                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                                                </p> Share
                                                                        </a>
                                                        </div>
                                                        <div class="share__button" id="shareButtonSmsAndroid" style="display:none;margin-bottom:2px">
                                                                <a class="" href="sms:?body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                                                        <p class="share_property_button--img">
                                                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                                        </p> Share
                                                                </a>
                                                        </div>
                                                        <div class="share__button" id="shareButtonSmsiOS" style="display:none;margin-bottom:2px">
                                                                <a class="" href="sms: &body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                                                        <p class="share_property_button--img">
                                                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                                        </p> Share
                                                                </a>
                                                        </div>
                                                </div>
                                                @if(Auth::user())
                                                <div class="toggle__favorite">
                                                        <a class="btn toggle_favorite_heart" onclick="toggle_favorite()" href="javascript:;" >
                                                                @if($favorite)
                                                                {{-- <i class="fa fa-heart color-status-sold" style="font-size:20px;" title="Remove from favorite"></i> --}}
                                                                <i class="fa fa-heart" title="Remove from favorite"></i> Favorite
                                                                @else
                                                                {{-- <i class="fa fa-heart-o fa-beat color-status-sold" style="font-size:20px;" title="Add to favorite"></i> --}}
                                                                <i class="fa fa-heart-o" title="Add to favorite"></i> Favorite
                                                                @endif
                                                        </a>
                                                </div>
                                                @if($listing->status=='Active')
                                                <div class="toggle__favorite">
                                                        <a class="btn toggle_favorite_heart btn-track" onclick="toggle_tracking();" href="javascript:;" >
                                                                @if($favorite && $favorite_tracked)
                                                                <i class="i-fvt-track-stop fa fa-area-chart listing-is-tracked" title="Toggle Tracking"></i> Track
                                                                @else
                                                                <i class="i-fvt-track-stop fa fa-area-chart listing-not-tracked" title="Toggle Tracking"></i> Track
                                                                @endif
                                                                {{-- <i class="pixidev-demo-preview">?</i> --}}
                                                        </a>
                                                        <i class="fa fa-info-circle" data-toggle="tooltip" rel="tooltip" data-content="Now you can track updates of your Favorite listings!" data-placement="right" title="Now you can track prices and other updates of your Favorite listings!"></i>
                                                </div>
                                                @endif


                                                @endif
                                        </div>
                                </div>
                                {{--@endif--}}
                        </div>
                        <div class="col-md-3 col-sm-12 col-xs-12">
                                <div class="row">
                                        <div class="col-md-12 col-sm-8 col-xs-12">
                                                <div class="listing-detail__status-price--box">
                                                        @if($listing->status == 'Sold' && Auth::user() && $isUserPremiumMember)
                                                                <div class="listing-detail__price listing-detail__price--mortgage">
                                                                        @component('frontend.components.altlink',['elText'=>'View Sold Price','elStyle'=>'font-size:30px;font-weight:normal;']){{Helper::money_format('%.0n', $listing->soldprice_2)}}@endcomponent 
                                                                </div>
                                                        @elseif($listing->status == 'Sold' && !$authUser)
                                                        @elseif($listing->status == 'Sold' && $authUser && !$isUserPremiumMember)
                                                        <a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" style="font-size:18px;font-weight:normal">{{LoginToViewText('Upgrade Subscription To View Sold Price')}}</a>
                                                        @elseif($listing->status=='Active')
                                                                <div class="listing-detail__price listing-detail__price--mortgage">
                                                                        {{$listing->listprice}}
                                                                </div>
                                                        @endif
                                                        <div class="listing-detail-status">
                                                                @if(in_array($listing->status, ['Terminated','Expired']))
                                                                <span class="previously-listed"><i class="fa fa-circle"></i> Previously Listed</span>
                                                                @else
                                                                <span class="{{strtolower($listing->status)}}"><i class="fa fa-circle"></i> {{$listing->status}}</span> @component('frontend.components.altlink')@if($listing->days_on_market()) {{$listing->days_on_market()}} {{($listing->days_on_market()>1)?'days':'day'}} on the market @elseif($listing->getListingPeriod()) Listed {{$listing->getListingPeriod()}} @endif @endcomponent
                                                                @endif
                                                                @if(Auth::user()) <span class="listing-detail--dollpersqft" style="display:inline-block" > &#8226; <b>$/sqft.:</b> @component('frontend.components.altlink'){{Helper::money_format('%.0n',$listing->pricePerSQFT())}}@endcomponent</span> @endif
                                                        </div>
                                                        {{-- <div class="listing-detail__listed"><b>Listed By:</b> {{$listing->reoffice}}</div> --}}
                                                </div>
                                        </div>

                                        {{-- [disabled:11-10-2022] [Offerland disbled on-demand] --}} 
                                        {{-- 
                                        <div class="col-md-12 col-sm-4 col-xs-12">
                                                @if($listing->status == 'Active' && $listing->get_commission_details() && $listing->get_commission_details('offer_price') && (!$is_featured) )
                                                <div class="listing-detail__offerland">
                                                        <div class="listing-detail__offerland-logo">
                                                                <a href="#" data-toggle="modal" data-target="#offerlandModal"><img src="{{asset('frontend/images/offerland-logo-01.svg')}}" width="50" height="55" alt="offerland"></a>
                                                        </div>
                                                        <div class="listing-detail__offerland-price">
                                                                <a href="#" data-toggle="modal" data-target="#offerlandModal" style="text-decoration: none;cursor: pointer;">OfferValue:</a><br />
                                                                <p>{{Helper::money_format('%.0n',$listing->get_commission_details('offer_price'))}}</p>
                                                        </div>
                                                </div>
                                                <div class="listing-detail__offerland--small">
                                                        <a href="#" data-toggle="modal" data-target="#offerlandModal">What is offervalue?</a>
                                                </div>
                                                @endif
                                        </div> 
                                        --}}

                                        @if(in_array($listing->status, ['Terminated','Expired']) && !Auth::user())
                                        <div class="col-xs-12" style="margin-top:14px;">
                                                <div style="background:#f0f7ff;border:1px solid #b8d9f5;border-radius:6px;padding:16px 20px;display:flex;align-items:center;flex-wrap:wrap;gap:12px;">
                                                        <div style="flex:1;min-width:200px;">
                                                                <strong style="font-size:15px;">Looking for current listings at this address?</strong><br>
                                                                <span style="font-size:13px;color:#555;">Create a free account to see active listings, price history, and nearby properties.</span>
                                                        </div>
                                                        <a href="{!! loginLinkHtml_aHref() !!}" style="background:#1a73e8;color:#fff;padding:9px 20px;border-radius:4px;text-decoration:none;font-size:14px;font-weight:600;white-space:nowrap;">Sign Up Free</a>
                                                </div>
                                        </div>
                                        @endif

                                </div>
                        </div>
                        {{-- @if($listing->status=='Active') --}}
                        <div class="col-sm-12 col-xs-12 visible-sm visible-xs">
                                <div class="listing-detail__info listing-detail-page__info active visible-sm visible-xs">
                                        <form id="toggle_favorite" action="" method="get">
                                                <input type="hidden" name="id" id="listingid" value="{{$listing->listingid}}">
                                                <input type="hidden" name="add" id="favorite_value" value="">
                                        </form>
                                        <div class="text-right share-fav__buttons" style="/*padding:0px 15px 0 0; margin:0*/;">
                                                <div class="toggle__share">
                                                        <div class="share__button" id="shareButton" style="margin-bottom:2px;">
                                                                <a href="javascript:;" class="">
                                                                        <p onclick="openShareOptions()" class="share_property_button--img">
                                                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                                        </p> Share
                                                                </a>
                                                        </div>
                                                        <div class="share__button" id="shareButtonSmsAndroid" style="display:none;margin-bottom:2px">
                                                                <a class="" href="sms:?body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                                                        <p class="share_property_button--img">
                                                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                                        </p> Share
                                                                </a>
                                                        </div>
                                                        <div class="share__button" id="shareButtonSmsiOS" style="display:none;margin-bottom:2px">
                                                                <a class="" href="sms: &body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                                                        <p class="share_property_button--img">
                                                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                                        </p> Share
                                                                </a>
                                                        </div>
                                                </div>
                                                @if(Auth::user())
                                                <div class="toggle__favorite">
                                                        <a class="btn toggle_favorite_heart" onclick="toggle_favorite()" href="javascript:;" >
                                                                @if($favorite)
                                                                        {{-- <i class="fa fa-heart color-status-sold" style="font-size:20px;" title="Remove from favorite"></i> --}}
                                                                        <i class="fa fa-heart" title="Remove from favorite"></i> Favorite
                                                                @else
                                                                        {{-- <i class="fa fa-heart-o fa-beat color-status-sold" style="font-size:20px;" title="Add to favorite"></i> --}}
                                                                        <i class="fa fa-heart-o" title="Add to favorite"></i> Favorite
                                                                @endif
                                                        </a>

                                                </div>
                                                @if($listing->status=='Active')
                                                <div class="toggle__favorite">
                                                        <a class="btn toggle_favorite_heart btn-track" onclick="toggle_tracking();" href="javascript:;" >
                                                                @if($favorite && $favorite_tracked)
                                                                <i class="i-fvt-track-stop fa fa-area-chart listing-is-tracked" title="Toggle Tracking"></i> Track
                                                                @else
                                                                <i class="i-fvt-track-stop fa fa-area-chart listing-not-tracked" title="Toggle Tracking"></i> Track
                                                                @endif
                                                                {{-- <i class="pixidev-demo-preview">?</i> --}}
                                                        </a>
                                                        <i class="fa fa-info-circle" data-toggle="tooltip" rel="tooltip" data-content="Now you can track updates of your Favorite listings!" data-placement="bottom" title="Now you can track prices and other updates of your Favorite listings!"></i>
                                                </div>
                                                @endif
                                                
                                                @endif
                                        </div>
                                </div>
                        </div>
                        {{-- @endif  --}}
                </div>
        </div>

        @guest
            @if(in_array($listing->status, ['Sold', 'Terminated', 'Expired']))
                {{-- Suppress share button for guest+sold/terminated/expired (no value before sign-in) --}}
                <style>.toggle__share { display: none !important; }</style>
            @else
                @include('frontend.includes.listing_photos_grid')
            @endif
        @else
            @include('frontend.includes.listing_photos_grid')
        @endguest

        <div class="container">
                <div class="listing-detail__item">
                        
                        <div class="listing-detail__content">
                                <div class="row">
                                        <div class="clearfix">
                                                <div class="col-md-8 col-sm-12 col-xs-12">
                                                        {{-- Commented on [28-05-2021] for mobile view as per demand (Price-was visible twice, removed one-instance):
                                                        <div class="listing-detail__status-price--box visible-sm visible-xs">
                                                                <div class="row">
                                                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                                                                <div class="listing-detail__price">
                                                                                        @if($listing->status == 'Sold' && Auth::user())
                                                                                        {{Helper::money_format('%.0n', $listing->soldprice_2)}} 
                                                                                        @elseif($listing->status == 'Sold') 
                                                                                        <a href="/login?redirect={{route('listing-detail-page2',['slug'=>$listing->slug])}}" style="font-size:14px;font-weight:normal">Sign-in required to view sold price as per MLS rules</a>
                                                                                        @elseif($listing->status=='Active')
                                                                                        {{$listing->listprice}}
                                                                                        @endif
                                                                                </div>
                                                                        </div>
                                                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                                                                <div class="listing-detail-status">
                                                                                        <span class="{{strtolower($listing->status)}}"><i class="fa fa-circle"></i> {{$listing->status}}</span> @if($listing->days_on_market()) {{$listing->days_on_market()}} {{($listing->days_on_market()>1)?'days':'day'}} on the market @elseif($listing->getListingPeriod()) Listed {{$listing->getListingPeriod()}} @endif
                                                                                </div>
                                                                        </div>
                                                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                                                                <div class="listing-detail__listed"><b>Listed By:</b> {{$listing->reoffice}}</div>
                                                                        </div>
                                                                </div>
                                                        </div> 
                                                        --}}

                                                        {{-- Commented on [19-05-2021] for mobile view as per demand :
                                                        <div class="listing__mortgage visible-sm visible-xs" id="mortgageCalculator">
                                                                <div class="row">
                                                                        <div class="col-sm-6 col-xs-6 nopadding-left">
                                                                                <div class="listing__mortgage-box listing__mortgage-box-topleft">
                                                                                        <label class="control-label" for="inputRate">Interest Rate % <!--<a href="javascript:;" onclick="Intercom('showNewMessage', 'Looking to acquire the posted mortgage rate.');">Get Rate</a>--></label>
                                                                                        <input type="text" id="inputRate_m" value="1.84" class="form-control">
                                                                                </div>
                                                                        </div>
                                                                        <div class="col-sm-6 col-xs-6 nopadding-right">
                                                                                <div class="listing__mortgage-box listing__mortgage-box-topright">
                                                                                        <label class="control-label" for="inputTerm">Ammortization</label>
                                                                                        <div>
                                                                                           <select id="inputTerm_m" class="form-control">
                                                                                                  <option name="10years" value="10">10 years</option>
                                                                                                  <option name="15years" value="15">15 years</option>
                                                                                                  <option name="20years" value="20">20 years</option>
                                                                                                  <option name="25years" value="25" selected="">25 years</option>
                                                                                                  <option name="30years" value="30">30 years</option>
                                                                                           </select>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        <div class="col-sm-6 col-xs-6 nopadding-left">
                                                                                <div class="listing__mortgage-box listing__mortgage-box-bottomleft">
                                                                                        <label class="control-label" for="inputDownpayment">Down Payment (<span id="downpayment_per_m">20</span>%)</label>
                                                                                        <div class="input-downpayment"><input type="text" min="0" id="inputDownpayment_m" data-val="{{($listing->listprice_2*20)/100}}" value="{{number_format(($listing->listprice_2*20)/100)}}" class="form-control" style="padding-left: 10px;"></div>
                                                                                </div>
                                                                        </div>
                                                                        <div class="col-sm-6 col-xs-6 nopadding-right">
                                                                                <div class="listing__mortgage-box listing__mortgage-box-bottomright">
                                                                                        <label class="control-label" for="rentalIncome">Rental Income</label>
                                                                                        <div class="input-rentalincome"><input type="text" min="0" id="inputRentalincome_m" class="form-control"></div>
                                                                                </div>
                                                                        </div>
                                                                        <div class="col-sm-12 col-xs-12">
                                                                                <div id="mortgageMonthly" class="mortgage__total">
                                                                                        <label class="period">Mortgage</label>
                                                                                        <div id="withoutRental_m">
                                                                                           <span class="amount" id="mortgage_amount_m"></span><span class="period">/mth</span>
                                                                                        </div>
                                                                                        <div id="withRental_m" style="display: none">
                                                                                                <span class="amount" id="mortgage_amount_m1"></span> - <span id="rentalAmount_m"></span> = <span id="finalMortgage_m"></span><span class="period">/mth</span>
                                                                                         </div>
                                                                                </div>
                                                                        </div>
                                                                </div>
                                                        </div> 
                                                        --}}

                                                        @guest
                                                        @if(in_array($listing->status, ['Sold', 'Terminated', 'Expired']))
                                                        {{-- SEO Description Block — visible to guests, no gate --}}
                                                        @php
                                                        $_seo_address   = $addressAsH1tag ?? '';
                                                        $_seo_type      = ucwords(strtolower($listing->type ?? 'Home'));
                                                        $_seo_beds      = $listing->bedrooms ?? null;
                                                        $_seo_baths     = $listing->bathstotal ?? null;
                                                        $_seo_city      = $listing->cityProperCased ?? '';
                                                        $_seo_subarea   = $listing->subareaProperCased ?? '';
                                                        $_seo_street    = ucwords(strtolower($listing->street_name ?? ''));
                                                        $_seo_yearbuilt = $listing->yearbuilt ?? null;
                                                        $_seo_soldmonth = '';
                                                        if(!empty($listing->sold_date)){
                                                            $_seo_soldmonth = \Carbon\Carbon::parse($listing->sold_date)->format('F Y');
                                                        }
                                                        $_seo_bedbath = '';
                                                        if($_seo_beds && $_seo_baths) {
                                                            $_seo_bedbath = $_seo_beds.'-bed, '.$_seo_baths.'-bath ';
                                                        } elseif($_seo_beds) {
                                                            $_seo_bedbath = $_seo_beds.'-bed ';
                                                        }
                                                        @endphp
                                                        <div class="bc-seo-description" style="margin-bottom:18px;padding:14px 0;border-bottom:1px solid #eee;">
                                                            <p class="bc-seo-desc-text">
                                                                <strong>{{ $_seo_address }}</strong> is a {{ $_seo_bedbath }}{{ $_seo_type }}
                                                                @if($_seo_subarea) in {{ $_seo_subarea }}, @else in @endif{{ $_seo_city }}
                                                                @if($_seo_street) on {{ $_seo_street }}@endif
                                                                @if($_seo_yearbuilt && $_seo_yearbuilt > 0) , built in {{ $_seo_yearbuilt }}@endif
                                                                @if($_seo_soldmonth) that sold in <strong>{{ $_seo_soldmonth }}</strong>@endif.
                                                                Sign in to view the sold price, full history, and similar recently sold properties nearby.
                                                            </p>
                                                        </div>

                                                        {{-- Neighbourhood Stats Strip — visible to guests --}}
                                                        @php
                                                        $_ns_sold   = isset($total_soldlistings) ? (int)$total_soldlistings : null;
                                                        $_ns_active = isset($total_active_listings) ? (int)$total_active_listings : null;
                                                        if(is_null($_ns_sold)) {
                                                            try {
                                                                $_ns_sold = \App\Models\Listings::where('status','Sold')
                                                                    ->where('subarea', $listing->subarea)
                                                                    ->where('city', $listing->city)
                                                                    ->where('sold_date', '>=', now()->subMonths(6)->toDateString())
                                                                    ->count();
                                                            } catch(\Exception $_e) {
                                                                $_ns_sold = null;
                                                            }
                                                        }
                                                        if(is_null($_ns_active)) {
                                                            try {
                                                                $_ns_active = \App\Models\Listings::where('status','Active')
                                                                    ->where('subarea', $listing->subarea)
                                                                    ->where('city', $listing->city)
                                                                    ->count();
                                                            } catch(\Exception $_e) {
                                                                $_ns_active = null;
                                                            }
                                                        }
                                                        @endphp
                                                        @if($_ns_sold || $_ns_active)
                                                        <div class="bc-neighbourhood-stats">
                                                            @if($_ns_sold)
                                                            <div class="bc-ns-stat">
                                                                <strong>{{ number_format($_ns_sold) }}</strong>
                                                                {{ Str::plural('home', $_ns_sold) }} sold in {{ $listing->subareaProperCased ?: $listing->cityProperCased }} in the last 6 months
                                                            </div>
                                                            @endif
                                                            @if($_ns_active)
                                                            <div class="bc-ns-stat">
                                                                <strong>{{ number_format($_ns_active) }}</strong> active {{ Str::plural('listing', $_ns_active) }}
                                                            </div>
                                                            @endif
                                                        </div>
                                                        @endif

                                                        @endif
                                                        @endguest

                                                        {{-- .listing-detail__details [STARTS] --}}
                                                        <div class="listing-detail__details">
                                                                <div class="listing-detail__title"><h2>Details</h2></div>
                                                                <div class="listing-detail__details-items row clearfix"><!--row-->

                                                                        @if($listing->getType() == 'House')

                                                                        @if($listing->bedrooms)
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_bed.svg')}}"  width="40" height="40" alt="svg_bed" />
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                   <div class="listing-detail__details-label">Beds</div>
                                                                                                   <div>{{$listing->bedrooms}}</div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                        @if($listing->bathstotal)
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_bathroom.svg')}}" loading="lazy" width="40" height="40" alt="svg_bathroom"/>
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                   <div class="listing-detail__details-label">Bath</div>
                                                                                                   <div>{{$listing->bathstotal}}</div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                        @if($listing->mlsr_listing && $listing->mlsr_listing->kitchens)
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_kitchen.svg')}}" loading="lazy" width="40" height="40" alt="svg_kitchen"/>
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                   <div class="listing-detail__details-label">Kitchens</div>
                                                                                                   <div>@component('frontend.components.altlink'){{$listing->mlsr_listing->kitchens}}@endcomponent</div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                        @if($listing->yearbuilt)
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_built-year.svg')}}" loading="lazy" width="40" height="40" alt="svg_built-year"/>
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                   <div class="listing-detail__details-label">Built</div>
                                                                                                   <div>@component('frontend.components.altlink'){{$listing->yearbuilt}}@endcomponent</div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                        @if($listing->livingarea_2)
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_living-area.svg')}}" loading="lazy" width="40" height="40" alt="svg_living"/>
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                   <div class="listing-detail__details-label">Living Area</div>
                                                                                                   <div>@component('frontend.components.altlink'){{$listing->livingarea_2}} SqFt. @endcomponent</div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                        @if($listing->lotsize)
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_lotsize.svg')}}" loading="lazy" width="40" height="40" alt="svg_lotsize" />
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                   <div class="listing-detail__details-label">Lot Size</div>
                                                                                                   <div>@component('frontend.components.altlink'){{$listing->lotsize}} SqFt. @endcomponent</div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                        @if($listing->frontage && $listing->frontage > 0)
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_frontage.svg')}}" loading="lazy" width="40" height="40" alt="svg_frontage"/>
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                   <div class="listing-detail__details-label">Frontage</div>
                                                                                                   <div>@component('frontend.components.altlink'){{$listing->frontage}} Feet @endcomponent</div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                        @if($listing->depth)
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_depth.svg')}}" loading="lazy"  width="40" height="40" alt="svg_depth" />
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                   <div class="listing-detail__details-label">Depth</div>
                                                                                                   <div>@component('frontend.components.altlink'){{$listing->depth}} Feet @endcomponent</div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif

                                                                        @else

                                                                        @if($listing->bedrooms)
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_bed.svg')}}" loading="lazy"  width="40" height="40" alt="svg_bed"/>
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                   <div class="listing-detail__details-label">Beds</div>
                                                                                                   <div>{{$listing->bedrooms}}</div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                        @if($listing->bathstotal)
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_bathroom.svg')}}" loading="lazy"  width="40" height="40" alt="svg_bathroom"/>
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                   <div class="listing-detail__details-label">Bath</div>
                                                                                                   <div>{{$listing->bathstotal}}</div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                        @if($listing->yearbuilt)
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_built-year.svg')}}" loading="lazy" width="40" height="40" alt="svg_built" />
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                   <div class="listing-detail__details-label">Built</div>
                                                                                                   <div>@component('frontend.components.altlink'){{$listing->yearbuilt}}@endcomponent</div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                        @if($listing->livingarea_2)
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_living-area.svg')}}" loading="lazy" width="40" height="40" alt="svg_living" />
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                   <div class="listing-detail__details-label">Living Area</div>
                                                                                                   <div>@component('frontend.components.altlink'){{$listing->livingarea_2}} SqFt.@endcomponent</div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                        @if($listing->pricePerSQFT())
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_price-sqft.svg')}}" loading="lazy" width="40" height="40" alt="svg_price" />
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                        <div class="listing-detail__details-label">$/SqFt. @if($listing->status=='Sold')(Sold)@endif</div>
                                                                                                        <div>
                                                                                                                @component('frontend.components.altlink')
                                                                                                                {{$listing->pricePerSQFT()}}
                                                                                                                @endcomponent
                                                                                                   </div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                        @if($listing->taxamount && $listing->taxamount > 0)
                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_tax.svg')}}" loading="lazy" width="40" height="40" alt="svg_tax" />
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <h3>
                                                                                                   <div class="listing-detail__details-label">Taxes</div>
                                                                                                   <div>@component('frontend.components.altlink'){{Helper::money_format('%.2n', $listing->taxamount)}}@endcomponent</div>
                                                                                                </h3>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif

                                                                        @endif
                                                                </div>
                                                        </div>
                                                        {{-- .listing-detail__details [ENDS] --}}

                                                        {{-- Previous place holder for offerncommission-view --}}

                                                        @if($house_description)
                                                        <div class="listing-detail__description listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Description</h2></div>
                                                                <p>@component('frontend.components.altlink'){!!$house_description!!}@endcomponent</p>
                                                        </div>
                                                        @else
                                                        @if($listing->remarks)
                                                        <div class="listing-detail__description listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Description</h2></div>
                                                                @component('frontend.components.altlink')
                                                                @if(isset($building) && $building)
                                                                <p>{!!str_ireplace($building->name, "<a href='/building/".$building->slug."'>".$building->name."</a>", remove_openhouse($listing->remarks))!!}</p>
                                                                @else
                                                                <p>{{remove_openhouse($listing->remarks)}}</p>
                                                                @endif
                                                                @endcomponent
                                                        </div>
                                                        @endif
                                                        @endif
                                                
                                                        @if($listing->getType() != 'House' && $listing->mlsr_listing && $listing->mlsr_listing->bylaw_restrictions && strtoupper($listing->mlsr_listing->bylaw_restrictions) != 'NO RESTRICTIONS')
                                                                <div class="listing-detail__details listing-detail--border">
                                                                        <div class="listing-detail__title"><h2>Strata ByLaws</h2></div>
                                                                        <div class="listing-detail__details-items row clearfix"><!--row-->
                                                                                @php
                                                                                        $restrictions = explode(',',$listing->mlsr_listing->bylaw_restrictions);
                                                                                @endphp
                                                                                @foreach($restrictions as $restriction)
                                                                                        @if (substr_count($restriction, 'Pet') > 0)
                                                                                                <div class="col-md-6 col-sm-6 col-xs-12">
                                                                                                        <div class="listing-detail__details-item">
                                                                                                                <div class="listing-detail__details-image">
                                                                                                                        <img src="{{asset('frontend/icons/detailsPage/svg_pet.svg')}}" loading="lazy" width="40" height="40" alt="svg_pet" />
                                                                                                                </div>
                                                                                                                <div class="listing-detail__details-value">
                                                                                                                        <div class="listing-detail__details-label">Animals</div>
                                                                                                                        <div>@component('frontend.components.altlink'){{$restriction}}@endcomponent</div>
                                                                                                                </div>
                                                                                                        </div>
                                                                                                </div>
                                                                                        @endif
                                                                                        @if (substr_count($restriction, 'Rental') > 0)
                                                                                                <div class="col-md-6 col-sm-6 col-xs-12">
                                                                                                        <div class="listing-detail__details-item">
                                                                                                                <div class="listing-detail__details-image">
                                                                                                                        <img src="{{asset('frontend/icons/detailsPage/svg_rental.svg')}}" loading="lazy" width="40" height="40" alt="svg_rental"/>
                                                                                                                </div>
                                                                                                                <div class="listing-detail__details-value">
                                                                                                                        <div class="listing-detail__details-label">Rental</div>
                                                                                                                        <div>@component('frontend.components.altlink'){{$restriction}}@endcomponent</div>
                                                                                                                </div>
                                                                                                        </div>
                                                                                                </div>
                                                                                        @endif
                                                                                @endforeach
                                                                        </div>
                                                                </div>
                                                        @endif
  
                                                        @if($listing->open_house ) {{-- enabbled on 11-03-2022 --}}
                                                        <div class="listing-detail__description listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Open House</h2></div>
                                                                <p>
                                                                        @if(false)
                                                                        {!! str_replace(',', '<br/>', $listing->open_house) !!} 
                                                                        @if($addToCal) <br/> - <a href="{{route('open-hyperlink')}}?type=add_to_calendar&ref=listing_detail&url={{$addToCal}}" target="_blank">Add To Calendar</a> @endif
                                                                        @elseif($listing->open_house)
                                                                        @foreach(explode(',',$listing->open_house) as $_oheIdx => $_openHouseEvent)
                                                                        @php 
                                                                        // $_oheDates = explode('/',explode('&',explode('dates=', $addToCal)[1]?:'')[0]?:'');
                                                                        $_oheStrAr = explode(':',$_openHouseEvent,2);
                                                                        $_oheStrTimes = explode('-',$_oheStrAr[1]??'-',2);
                                                                        $_oheDates = [
                                                                                strtotime($_oheStrAr[0].' '.date('y').' '.$_oheStrTimes[0].(strtotime($_oheStrTimes[0].'pm')>strtotime(($_oheStrTimes[1]??''))?'am':'pm')),
                                                                                strtotime($_oheStrAr[0].' '.date('y').' '.($_oheStrTimes[1]??''))
                                                                        ];
                                                                        @endphp
                                                                        @if($_openHouseEvent) 
                                                                        <div>
                                                                        {{$_openHouseEvent}} - <a href="{{route('open-hyperlink')}}?type=add_to_calendar&ref=listing_detail&url={{\Spatie\CalendarLinks\Link::create('Openhouse: ' . $listing->streetaddress . ', ' . $listing->city,  \Illuminate\Support\Carbon::parse($_oheDates[0]),  \Illuminate\Support\Carbon::parse($_oheDates[1]))->google() }}" target="_blank">Add To Calendar</a> <br/> 
                                                                        </div>
                                                                        @endif
                                                                        
                                                                        @if($_oheIdx>0 && auth()->user()?->can('dev-dj'))
                                                                        <code style="white-space:pre-line;" class="pixidev-demo-preview">
                                                                                <a target="_blank" href="{{route('open-hyperlink')}}?type=add_to_calendar&ref=listing_detail&url={{explode('dates=',$addToCal)[0]}}dates={{str_replace('-','',date('c',$_oheDates[0]))}}/{{str_replace('-','',date('c',$_oheDates[1]))}}&text={{urlencode('Openhouse: '.$addressAsH1tag.', '.$listing->city)}}">Add To Calendar {{date('c',$_oheDates[0])}}/{{date('c',$_oheDates[1])}}</a>
                                                                                <a target="_blank" href="{{route('open-hyperlink')}}?type=add_to_calendar&ref=listing_detail&url={{\Spatie\CalendarLinks\Link::create('Openhouse: ' . $listing->streetaddress . ', ' . $listing->city,  \Illuminate\Support\Carbon::parse($_oheDates[0]),  \Illuminate\Support\Carbon::parse($_oheDates[1]))->google() }}">Add To Calendar {{date('c',$_oheDates[0])}}/{{date('c',$_oheDates[1])}}</a>
                                                                        </code>
                                                                        @endif
                                                                        
                                                                        @endforeach
                                                                        @endif
                                                                </p>
                                                                <p>Come see <strong>{{$addressAsH1tag}}</strong> in person during the following open house times or schedule a private appointment by contacting us.</p>
                                                        </div>
                                                        @endif

                                                        {{-- New Placeholder -for-offerncommission-view --}}

                                                        {{-- Disabled on 6-July-2021 -till-legal-docs --}}
                                                        {{-- Enabled on 5-Aug-2021 -for-@pixilink-users-only --}}
                                                        @if(true && $user && substr($user->email,-12)=='pixilink.com' && $listing->status == 'Active' && $listing->get_commission_details())
                                                        @php
                                                        $commissionDetails = $listing->get_commission_details();
                                                        @endphp
                                                        <div class="listing-detail__offerncommission listing-detail--border pixidev-demo-preview">
                                                                <div class="listing-detail__title listing-detail__title-sub">
                                                                        <h2>Make an offer online and save!</h2>
                                                                        {{-- <h3 style="font-size:14px">Rebate only applicable for offers made online!</h3> --}}
                                                                </div>
                                                                <div class="listing-detail__offer table-responsive">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        <tr>
                                                                                                <td width="65%">List Price</td>
                                                                                                <td width="35%">{{$listing->listprice}}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>*OfferValue Price</td>
                                                                                                <td>{{Helper::money_format('%.0n',$commissionDetails['offer_price']) }}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Commission Offered By Listing Agent</td>
                                                                                                <td>{{Helper::money_format('%.2n',$commissionDetails['total_commission']) }}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Our Rebate</td>
                                                                                                <td>{{Helper::money_format('%.2n',$commissionDetails['our_rebate']) }}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td><strong>Your Price</strong></td>
                                                                                                <td><strong>{{Helper::money_format('%.2n',$commissionDetails['your_price']) }}</strong></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td><strong>Total Savings{{-- Total Savings When You Buy With Us --}}</strong></td>
                                                                                                <td><strong>{{Helper::money_format('%.2n',($commissionDetails['total_savings']>0)?$commissionDetails['total_savings']:$commissionDetails['our_rebate'] ) }}</strong></td>
                                                                                        </tr>
                                                                                </tbody>
                                                                        </table>
                                                                        <div style="margin:1em auto;">
                                                                                <p>* OfferValue is an estimate of this home's market value. The OfferValue incorporates numerous conventional and non-conventional data sources to determine the market value of properties using artificial intelligence. Rebate is calculated based on commission on the OfferValue price. The final value of the rebate will be based on the purchase price of the property. The commission rebate is only applicable for clients that have not engaged in full-service services with our team!
                                                                                </p>
                                                                                <p>
                                                                                        {{-- 28% of homes accept an offer within a week.<br>Make an offer before its gone! --}}
                                                                                        <i style="font-style: italic;">
                                                                                        @if( !empty($commissionDetails['most_recent_sold_listing']) && $commissionDetails['most_recent_sold_listing']->days_on_market()<=30)
                                                                                                 A similar property {{-- [addressin the [subarea] OR [building] --}} 
                                                                                                 {{-- {{$commissionDetails['most_recent_sold_listing']->streetaddress}} --}}
                                                                                                 <a href="{{trim(route('listing-detail-page2', ['slug'=>$commissionDetails['most_recent_sold_listing']->slug]))}}" class="color-status-sold">{{--$listing->streetaddress--}}
                                                                                                        {{$commissionDetails['most_recent_sold_listing']->streetaddress}}
                                                                                                        {{-- {{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}} --}}
                                                                                                        {{-- noCity, {{ucfirst(strtolower($building->city))}} --}}
                                                                                                 </a>
                                                                                                  was only on the market for {{$commissionDetails['most_recent_sold_listing']->days_on_market()}} days. Make an offer online before it's sold.
                                                                                        @elseif(!empty($commissionDetails['similar_ones_avg_dom']) && $commissionDetails['similar_ones_avg_dom']<=30)
                                                                                                Similar homes, on average, are selling within {{!empty($commissionDetails['similar_ones_avg_dom'])?$commissionDetails['similar_ones_avg_dom']:' a very few '}} days from hitting the market. Make an offer online before it's sold.
                                                                                        @endif
                                                                                        </i>
                                                                                        @if(request()->input('expid','bad-default')=='239487982t3kjsydgfiuw32476dfsg')
                                                                                        <div class="container-fluid agent__box">
                                                                                                <h4>Trusted Advise</h4>
                                                                                                <div class="col col-sm-2">
                                                                                                        <div class="listing-detail__agent-bc-box clearfix">
                                                                                                                <div class="listing-detail__agent-bc-box--image">
                                                                                                                        <img loading="lazy" src="https://www.bccondosandhomes.com/frontend/images/teamagents/les.jpg">
                                                                                                                </div>
                                                                                                        </div>
                                                                                                </div>
                                                                                                <div class="col col-sm-10">
                                                                                                        <div class="text-left" ><a href="mailto:les@bccondosandhomes.com">Les Twarog</a></div>
                                                                                                        <div class="text-muted">
                                                                                                                
                                                                                                                <div>Re/Max Select Realty</div>

                                                                                                                @if( !empty($commissionDetails['most_recent_sold_listing']) && $commissionDetails['most_recent_sold_listing']->days_on_market()<=30)
                                                                                                                Last property {{-- [addressin the [subarea] OR [building] --}} 
                                                                                                                {{-- {{$commissionDetails['most_recent_sold_listing']->streetaddress}} --}}
                                                                                                                <a href="{{trim(route('listing-detail-page2', ['slug'=>$commissionDetails['most_recent_sold_listing']->slug]))}}" class="color-status-sold">{{--$listing->streetaddress--}}
                                                                                                                        {{$commissionDetails['most_recent_sold_listing']->streetaddress}}
                                                                                                                        {{-- {{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}} --}}
                                                                                                                        {{-- noCity, {{ucfirst(strtolower($building->city))}} --}}
                                                                                                                </a>
                                                                                                                was only on the market for {{$commissionDetails['most_recent_sold_listing']->days_on_market()}} days. Make an offer online before it's sold.
                                                                                                                @elseif(!empty($commissionDetails['similar_ones_avg_dom']) && $commissionDetails['similar_ones_avg_dom']<=30)
                                                                                                                Similar homes, on average, are selling within {{!empty($commissionDetails['similar_ones_avg_dom'])?$commissionDetails['similar_ones_avg_dom']:' a very few '}} days from hitting the market. Make an offer online before it's sold.
                                                                                                                @endif
                                                                                                                <br>
                                                                                                        </div>
                                                                                                        <br>
                                                                                                        <div class="text-black">Submit your offer online in minutes to the listing agent!</div>
                                                                                                        <br>

                                                                                                        <form class="form-inline" onsubmit="return false;">
                                                                                                                <input type="number" min="10000" step="1000" class="form-control inp-start-offer agent-suggested">
                                                                                                                <button class="btn ">Start offer</button>
                                                                                                        </form>
                                                                                                        
                                                                                                </div>
                                                                                        </div>
                                                                                        @endif
                                                                                </p>
                                                                        </div>
                                                                        <div class="">
                                                                                <div {{--  class="col-sm-12" --}} >
                                                                                        {{-- <button class="listing-detail__offer-button start_an_offer">Start an offer </button> --}}
                                                                                </div>
                                                                        </div>
                                                                        {{-- <div style="margin:1em auto;">* Rebate only applicable for offers made online!</div> --}}
                                                                        {{-- <h3 style="font-size:14px">* Rebate only applicable for offers made online!</h3> --}}
                                                                        {{-- <div class="listing-detail__offer--saved">
                                                                                <strong>Save <span>{{Helper::money_format('%.0n',$commissionDetails['save_on_permonthmortgage']) }}</span>/month in mortgage with us!</strong>
                                                                        </div> --}}
                                                                        {{-- [Disabled on 21-June-2021]
                                                                        <div class="row col-sm-12">
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <button class="listing-detail__offer-button start_an_offer">Start an offer </button>
                                                                                </div>
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        Similar homes, on average, are selling within {{!empty($commissionDetails['similar_ones_avg_dom'])?$commissionDetails['similar_ones_avg_dom']:' a very few '}} days from hitting the market. Make an offer online before it's sold.
                                                                                </div> 
                                                                        </div>
                                                                        --}}
                                                                </div>
                                                        </div>
                                                        @endif

                                                        @if(false)
                                                        {{-- [disabled:02-11-2022] (as last discussion) --}}{{-- @if( $userIsPixiMember ) [published:18-05-2022]--}}
                                                        <div class="listing-detail__offerncommission listing-detail--border {{-- pixidev-demo-preview --}} ">
                                                                <div class="listing-detail__title listing-detail__title-sub">
                                                                        <h2>{{-- Buyer --}} Agent Commission</h2>
                                                                </div>
                                                                <div class="listing-detail__offer table-responsive">
                                                                        @component('frontend.components.altlink'){{strtoupper($listing->commission)}}@endcomponent
                                                                        <br>
                                                                        Listed By: @component('frontend.components.altblur'){{($listing->reoffice)}}@endcomponent
                                                                </div>
                                                        </div>
                                                        {{-- @endif --}}
                                                        @endif


                                                
                                                        @php
                                                        $historyData= $listing->getHistory();
                                                        $priceChanges = $listing->get_price_history();
                                                        @endphp
                                                        @if($listing->status == 'Sold' || $listing->status == 'Terminated' || $listing->status == 'Expired' || count($historyData) >= 1 || count($priceChanges) >=1)
                                                        <!--if History -->
                                                        <div class="listing-detail__history listing-detail--border">
                                                                <div class="listing-detail__title"><h2>History</h2></div>
                                                                <div class="listing-detail__history-table table-responsive">
                                                                        <table class="table">
                                                                                <thead>
                                                                                        <tr>
                                                                                                <th>Date</th>
                                                                                                <th>MLS#&reg;</th>
                                                                                                <th>Status</th>
                                                                                                <th>Asking Price</th>
                                                                                                {{-- <th>Brokerage</th> [brokerage-hidden on-demand:19-01-2022] --}}
                                                                                        </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                        @if($listing->status == 'Sold' || $listing->status == 'Terminated' || $listing->status == 'Expired')
                                                                                        @if($listing->status == 'Sold')
                                                                                        <tr>
                                                                                                <td>{{date('m/d/Y', strtotime($listing->sold_date))}}</td>
                                                                                                <!-- <td>@component('frontend.components.altlink'){{date('m/d/Y', strtotime($listing->sold_date))}}@endcomponent</td> -->
                                                                                                <td id="mls_listing_id">@component('frontend.components.altlink'){{$listing->listingid}}@endcomponent</td>
                                                                                                <td>{{$listing->status}}</td>
                                                                                                <td>
                                                                                                        @component('frontend.components.altlink')
                                                                                                        {{Helper::money_format('%.0n', $listing->soldprice_2)}}
                                                                                                        @endcomponent
                                                                                                </td> 
                                                                                                {{-- <td>{{$listing->reoffice}}</td> [brokerage-hidden on-demand:19-01-2022] --}}
                                                                                        </tr>
                                                                                        @else
                                                                                        <tr>
                                                                                                <td>{{date('m/d/Y', strtotime($listing->last_modified))}}</td>
                                                                                                <td id="mls_listing_id">@component('frontend.components.altlink'){{$listing->listingid}}@endcomponent</td>
                                                                                                <td>@component('frontend.components.altlink')Previously Listed@endcomponent</td>
                                                                                                <td>
                                                                                                        @component('frontend.components.altlink')
                                                                                                        {{Helper::money_format('%.0n', $listing->listprice_2)}}
                                                                                                        @endcomponent
                                                                                                </td>
                                                                                                {{-- <td>{{$listing->reoffice}}</td> [brokerage-hidden on-demand:19-01-2022] --}}
                                                                                        </tr>
                                                                                        @endif
                                                                                        @endif
                                                                                        {{-- 
                                                                                        @if($listing->status == 'Sold' || $listing->status == 'Terminated' || $listing->status == 'Expired')
                                                                                        <tr>
                                                                                                <td>{{date('m/d/Y', strtotime($listing->sold_date))}}</td>
                                                                                                <td id="mls_listing_id">{{$listing->listingid}}</td>
                                                                                                <td>{{$listing->status}}</td>
                                                                                                <td>
                                                                                                   @if((Auth::user() || $listing->status != 'Sold') && $isUserPremiumMember)
                                                                                                   {{Helper::money_format('%.0n', $listing->soldprice_2)}}
                                                                                                   @else
                                                                                                   <a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2', ['slug'=>$listing->slug]))}}">{{LoginToViewText()}}</a>
                                                                                                   <span hidden="hidden"></span> 
                                                                                                   @endif
                                                                                           </td> 
                                                                                                <td>{{$listing->reoffice}}</td>
                                                                                        </tr> --}}
                                                                                        {{-- @if($listing->status == 'Sold')
                                                                                        @else
                                                                                        <tr>
                                                                                                <td>{{date('m/d/Y', strtotime($listing->last_modified))}}</td>
                                                                                                <td id="mls_listing_id">{{$listing->listingid}}</td>
                                                                                                <td>{{$listing->status}}</td>
                                                                                                <td>{{Helper::money_format('%.0n', $listing->listprice_2)}}</td>
                                                                                                <td>{{$listing->reoffice}}</td>
                                                                                                </tr>
                                                                                        @endif --}}

                                                                                        {{-- @endif --}}
                                                                                        
                                                                                        @if($priceChanges)
                                                                                        @php 
                                                                                                $listprice = $listing->listprice_2;
                                                                                        @endphp
                                                                                        @foreach($priceChanges as $priceChange)
                                                                                        <tr>
                                                                                                <td>@component('frontend.components.altlink'){{date('m/d/Y', strtotime($priceChange->time_changed))}}@endcomponent</td>
                                                                                                <td>@component('frontend.components.altlink'){{$listing->listingid}}@endcomponent</td>
                                                                                                <td>Price Updated</td>
                                                                                                <td>
                                                                                                        @component('frontend.components.altlink')
                                                                                                        {{Helper::money_format('%.0n', $priceChange->price)}}
                                                                                                        @endcomponent
                                                                                                </td>
                                                                                                {{-- <td>{{$listing->reoffice}}</td> [brokerage-hidden on-demand:19-01-2022] --}}
                                                                                        </tr>
                                                                                        @php
                                                                                                $listprice = $priceChange->price+abs($priceChange->change);
                                                                                        @endphp
                                                                                        @endforeach
                                                                                        <tr>
                                                                                                <td>@component('frontend.components.altlink'){{date('m/d/Y', strtotime($listing->list_date))}}@endcomponent</td>
                                                                                                <td>@component('frontend.components.altlink'){{$listing->listingid}}@endcomponent</td>
                                                                                                <td>Active</td>
                                                                                                <td>
                                                                                                        @component('frontend.components.altlink')
                                                                                                        {{Helper::money_format('%.0n', $listprice)}}
                                                                                                        @endcomponent
                                                                                                </td>
                                                                                                {{-- <td>{{$listing->reoffice}}</td> [brokerage-hidden on-demand:19-01-2022] --}}
                                                                                        </tr>
                                                                                        @else
                                                                                        <tr>
                                                                                                <td>@component('frontend.components.altlink'){{date('m/d/Y', strtotime($listing->list_date))}}@endcomponent</td>
                                                                                                <td>@component('frontend.components.altlink'){{$listing->listingid}}@endcomponent</td>
                                                                                                <td>Active</td>
                                                                                                <td>
                                                                                                        @component('frontend.components.altlink'){{Helper::money_format('%.0n', $listing->listprice_2)}}@endcomponent
                                                                                                </td>
                                                                                                {{-- <td>{{$listing->reoffice}}</td> [brokerage-hidden on-demand:19-01-2022] --}}
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(count($historyData) >= 1)
                                                                                        @foreach($historyData as $history)
                                                                                        <tr>
                                                                                                <td>
                                                                                                        @component('frontend.components.altlink')
                                                                                                        @if($history->status == 'Sold') {{date('m/d/Y', strtotime($history->sold_date))}} @else {{date('m/d/Y', strtotime($history->last_modified))}} @endif
                                                                                                        @endcomponent
                                                                                                </td>
                                                                                                <td>@component('frontend.components.altlink'){{$history->listingid}}@endcomponent</td>
                                                                                                <td>{{$history->status}}</td>
                                                                                                <td>
                                                                                                        @component('frontend.components.altlink')
                                                                                                        @if($history->status == 'Sold' && Auth::user() && $isUserPremiumMember)
                                                                                                        {{Helper::money_format('%.0n', $history->soldprice_2)}}
                                                                                                        @elseif(Auth::user() || $listing->status != 'Sold')
                                                                                                        {{Helper::money_format('%.0n', $history->listprice_2)}}
                                                                                                        {{-- @elseif(false && $history->status != 'Sold')
                                                                                                        {{Helper::money_format('%.0n', $history->listprice_2)}} --}}
                                                                                                        @else
                                                                                                        <a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}">{{LoginToViewText()}}</a>
                                                                                                        <span hidden="hidden"></span>
                                                                                                        @endif
                                                                                                        @endcomponent
                                                                                                </td>
                                                                                                {{-- <td>{{$history->reoffice}}</td> [brokerage-hidden on-demand:19-01-2022] --}}
                                                                                        </tr>
                                                                                        @endforeach
                                                                                        @endif
                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                        @endif

                                                        @if($listing->status == 'Active')

                                                        <div class="listing-detail__details listing-detail--border hidden-sm hidden-xs">
                                                                <div class="listing-detail__title"><h2>Mortgage Calculator</h2></div>
                                                                <div class="listing__mortgage" id="mortgageCalculator">
                                                                        <div class="row">
                                                                                <div class="col-sm-6 col-xs-6 nopadding-left">
                                                                                        <div class="listing__mortgage-box listing__mortgage-box-topleft">
                                                                                                <label class="control-label" for="inputRate">Interest Rate %<!--<a href="javascript:;" onclick="Intercom('showNewMessage', 'Looking to acquire the posted mortgage rate.');">Get Rate</a>--></label>
                                                                                                <input type="text" id="inputRate" value="1.84" class="form-control">
                                                                                        </div>
                                                                                </div>
                                                                                <div class="col-sm-6 col-xs-6 nopadding-right">
                                                                                        <div class="listing__mortgage-box listing__mortgage-box-topright">
                                                                                                <label class="control-label" for="inputTerm">Ammortization</label>
                                                                                                <div>
                                                                                                        <select id="inputTerm" class="form-control">
                                                                                                                <option name="10years" value="10">10 years</option>
                                                                                                                <option name="15years" value="15">15 years</option>
                                                                                                                <option name="20years" value="20">20 years</option>
                                                                                                                <option name="25years" value="25" selected="">25 years</option>
                                                                                                                <option name="30years" value="30">30 years</option>
                                                                                                        </select>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                                <div class="col-sm-6 col-xs-6 nopadding-left">
                                                                                        <div class="listing__mortgage-box listing__mortgage-box-bottomleft">
                                                                                                <label class="control-label" for="inputDownpayment">Down Payment (<span id="downpayment_per">20</span>%)</label>
                                                                                                <div class="input-downpayment"><input type="text" min="0" id="inputDownpayment" data-val="{{($listing->listprice_2*20)/100}}" value="{{number_format(($listing->listprice_2*20)/100)}}" class="form-control"></div>
                                                                                        </div>
                                                                                </div>
                                                                                <div class="col-sm-6 col-xs-6 nopadding-right">
                                                                                        <div class="listing__mortgage-box listing__mortgage-box-bottomright">
                                                                                                <label class="control-label" for="rentalIncome">Rental Income</label>
                                                                                                <div class="input-rentalincome"><input type="text" min="0" id="inputRentalincome" class="form-control"></div>
                                                                                        </div>
                                                                                </div>
                                                                                <div class="col-sm-12 col-xs-12 nopadding-fullwith">
                                                                                        <div id="mortgageMonthly" class="mortgage__total">
                                                                                                <label class="period">Mortgage</label>
                                                                                                <div id="withoutRental">
                                                                                                        <span class="amount" id="mortgage_amount"></span><span class="period">/mth</span>
                                                                                                </div>
                                                                                                <div id="withRental" style="display: none">
                                                                                                        <span class="amount" id="mortgage_amount1"></span> - <span id="rentalAmount"></span> = <span id="finalMortgage"></span><span class="period">/mth</span>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                </div>
                                                        </div>
                                                        @endif


                                                        @if($videotour_url && $media_displayed != 'video')
                                                        <div id="virtualtour_area"></div>
                                                                <div class="listing-detail__floorplan listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Video Tour</h2></div>
                                                                <div class="resp-container">
                                                                <iframe class="resp-iframe" title="" src="{{$videotour_url}}" frameborder="0" allowfullscreen loading="lazy"></iframe>
                                                                </div>
                                                        </div>
                                                        @endif

                                                        @if($virtualtour_url && $media_displayed != 'virtualtour')
                                                        <div id="virtualtour_area"></div>
                                                                <div class="listing-detail__floorplan listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Virtual Tour</h2></div>
                                                                <div class="resp-container">
                                                                <iframe class="resp-iframe" title="" src="{{$virtualtour_url}}" frameborder="0" allowfullscreen loading="lazy"></iframe>
                                                                </div>
                                                        </div>
                                                        @endif

                                                        @if($listing->amenity && $listing->amenity != '' && strtoupper($listing->amenity) != 'NONE')
                                                        <div class="listing-detail__amenities listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Amenities</h2></div>
                                                                @php
                                                                        $amenities = explode(',',$listing->amenity);
                                                                @endphp
                                                                @component('frontend.components.altlink')
                                                                @foreach($amenities as $amenity)
                                                                        <span>{{$amenity}}</span>
                                                                @endforeach
                                                                @endcomponent
                                                        </div>
                                                        @endif

                                                        @if($listing->features)
                                                        <div class="listing-detail__features listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Features</h2></div>
                                                                @php
                                                                        //$featuresAll = str_replace("/",",",$listing->features)
                                                                        $features = explode(',',$listing->features);
                                                                @endphp
                                                                @component('frontend.components.altlink')
                                                                @foreach($features as $feature)
                                                                        <span>{{$feature}}</span>
                                                                @endforeach
                                                                @endcomponent
                                                        </div>
                                                        @endif

                                                        @if($listing->site_influences)
                                                        <div class="listing-detail__site listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Site Influences</h2></div>
                                                                @php
                                                                        $site_influences = explode(",",$listing->site_influences)
                                                                @endphp
                                                                @component('frontend.components.altlink')
                                                                @foreach($site_influences as $site_influence)
                                                                        <span>{{$site_influence}}</span>
                                                                @endforeach
                                                                @endcomponent
                                                        </div>
                                                        @endif
                                                        
                                                        {{--  <div class="col-md-12 col-sm-12">  --}}
                                                                <div class="listing-detail__technical listing-detail--border">
                                                                   @if($listing->getType() == 'Apartment')
                                                                           {{-- <div class="listing-detail__title"><h2>Unit Information</h2></div> --}}
                                                                           <div class="listing-detail__title"><h2>Property Information</h2></div>
                                                                   @else
                                                                           <div class="listing-detail__title"><h2>Property Information</h2></div>
                                                                           {{-- <div class="listing-detail__title"><h2>Technical Information</h2></div> --}}{{-- [changed to Property Info. on: 15-04-2022] --}}
                                                                   @endif
                                                                   <div class="listing-detail__table">
                                                                           <table class="table table-striped">
                                                                                   <tbody>
                                                                                           <!-- If row is there show tr else not -->
                                                                                           @if($listing->listingid)
                                                                                                <tr>
                                                                                                        <td>MLS® #</td>
                                                                                                        <td>@component('frontend.components.altlink'){{$listing->listingid}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->type)
                                                                                                <tr>
                                                                                                        <td>Property Type</td>
                                                                                                        <td>@component('frontend.components.altlink') {{$listing->type}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->listingtype)
                                                                                                <tr>
                                                                                                        <td>Dwelling Type</td>
                                                                                                        <td>@component('frontend.components.altlink') {{$listing->listingtype}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->home_style)
                                                                                                <tr>
                                                                                                        <td>Home Style</td>
                                                                                                        <td>@component('frontend.components.altlink') {{$listing->home_style}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->kitchens)
                                                                                                <tr>
                                                                                                        <td>Kitchens</td>
                                                                                                        <td>@component('frontend.components.altlink') {{$listing->kitchens}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->yearbuilt)
                                                                                                <tr>
                                                                                                        <td>Year Built</td>
                                                                                                        <td>@component('frontend.components.altlink') {{$listing->yearbuilt}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->roof)
                                                                                                <tr>
                                                                                                        <td>Roof</td>
                                                                                                        <td>@component('frontend.components.altlink') {{$listing->roof}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->heating)
                                                                                                <tr>
                                                                                                        <td>Heating</td>
                                                                                                        <td>@component('frontend.components.altlink') {{$listing->heating}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->parking)
                                                                                                <tr>
                                                                                                        <td>Parking</td>
                                                                                                        <td>@component('frontend.components.altlink') {{$listing->parking}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->taxyear && $listing->taxamount && $listing->taxamount > 0)
                                                                                                <tr>
                                                                                                        <td>Tax</td>
                                                                                                        <td>@component('frontend.components.altlink'){{Helper::money_format('%.0n', $listing->taxamount)}} in {{$listing->taxyear}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->strata_no)
                                                                                                <tr>
                                                                                                        <td>Strata No</td>
                                                                                                        <td> <a href="{{$building_url}}">{{$listing->strata_no}}</a> </td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                {{-- postalcode: - Added on 21-July-2021  --}}
                                                                                                @if($listing->postalcode)
                                                                                                <tr>
                                                                                                        <td>Postal Code</td>
                                                                                                        <td>{{$listing->postalcode}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->complex)
                                                                                                <tr>
                                                                                                        <td>Complex Name</td>
                                                                                                        <td><a href="{{$building_url}}">{{ucwords(strtolower($listing->complex))}}</a></td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->reno_year)
                                                                                                <tr>
                                                                                                        <td>Year Renovated</td>
                                                                                                        <td>@component('frontend.components.altlink') {{$listing->reno_year}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->units_in_development)
                                                                                                <tr>
                                                                                                        <td>Units in Development</td>
                                                                                                        <td>@component('frontend.components.altlink') {{$listing->units_in_development}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->units_in_strata)
                                                                                                <tr>
                                                                                                        <td>Units in Strata</td>
                                                                                                        <td>@component('frontend.components.altlink') {{$listing->units_in_strata}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->maintenance && $listing->maintenance > 0)
                                                                                                <tr>
                                                                                                        <td>Strata Fees</td>
                                                                                                        <td>@component('frontend.components.altlink'){{Helper::money_format('%.0n', $listing->maintenance)}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                {{-- @if(false && $building)
                                                                                                <tr>
                                                                                                        <td>Address</td>
                                                                                                        <td><a href="{{$building_url}}">{{startsWithNumber($building->name)?$building->name:$building->name." ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}</a></td>
                                                                                                </tr>
                                                                                                @endif --}}{{-- [Disabled:15-04-2022] & reaplaced by addr-as-H1-tag --}}
                                                                                                @if($addressAsH1tag)
                                                                                                <tr>
                                                                                                        <td>Address</td>
                                                                                                        <td><a href="https://www.google.com/maps/dir/?api=1&destination={{urlencode(($listing->suite_no?'#':'').$addressAsH1tag.','.$listing->city)}}" target="_blank">{{$addressAsH1tag}}</a></td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->city)
                                                                                                @if($listing->subarea)
                                                                                                <tr>
                                                                                                        <td>Subarea</td>
                                                                                                        <td><a href="{{route('adv_search_listings',['city'=>$listing->cityEnsluged,'subarea'=>$listing->subareaEnsluged])}}">{{$listing->subareaProperCased}}</a></td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                <tr>
                                                                                                        <td>City</td>
                                                                                                        <td><a href="{{route('adv_search_listings',['city'=>$listing->cityEnsluged])}}">{{$listing->cityProperCased}}</a></td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->reoffice)
                                                                                                <tr>
                                                                                                        <td>Listed By</td>
                                                                                                        <td>@component('frontend.components.altlink') {{$listing->reoffice}}@endcomponent</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                        </tbody>
                                                                                </table>
                                                                        </div>
                                                                </div>
                                                        {{--  </div>  --}}
                
                                                        {{--  <div class="col-md-12 col-sm-12">  --}}
                                                                <div class="listing-detail__floor--area listing-detail--border">
                                                                        <div class="listing-detail__title"><h2>Floor Area (sq. ft.)</h2></div>
                                                                        @component('frontend.components.altlink')
                                                                        <div class="listing-detail__table">
                                                                                <table class="table table-striped">
                                                                                        <!--<thead>
                                                                                                <tr>
                                                                                                        <th>Floor</th>
                                                                                                        <th>Ensuite</th>
                                                                                                        <th>Pieces</th>
                                                                                                </tr>
                                                                                        </thead>-->
                                                                                        <tbody>
                                                                                                <!-- If row is there show tr else not -->
                                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->main_floor_area_2)
                                                                                                <tr>
                                                                                                        <td>Main Floor</td>
                                                                                                        <td>{{number_format($listing->mlsr_listing->main_floor_area_2, 0)}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->above_main_area)
                                                                                                <tr>
                                                                                                        <td>Above</td>
                                                                                                        <td>{{number_format($listing->mlsr_listing->above_main_area, 0)}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->below_main_area)
                                                                                                <tr>
                                                                                                        <td>Below</td>
                                                                                                        <td>{{number_format($listing->mlsr_listing->below_main_area, 0)}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->basement_area)
                                                                                                <tr>
                                                                                                        <td>Basement</td>
                                                                                                        <td>{{number_format($listing->mlsr_listing->basement_area, 0)}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->unfinished_area)
                                                                                                <tr>
                                                                                                        <td>Unfinished</td>
                                                                                                        <td>{{number_format($listing->mlsr_listing->unfinished_area, 0)}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->livingarea_2)
                                                                                                <tr>
                                                                                                        <td><strong>Total</strong></td>
                                                                                                        <td><strong>{{number_format($listing->mlsr_listing->livingarea_2, 0)}}</strong></td>
                                                                                                </tr>
                                                                                                @endif
                                                                                        </tbody>
                                                                                </table>
                                                                        </div>
                                                                        @endcomponent
                                                                </div>
                                                        {{--  </div>  --}}

                                                        {{-- [added: 08-09-2022] [BEGINS] --}}
                                                        @can('dev-dj')
                                                        @if($listing->vancouver_detached() || true)
                                                        <div class="listing-detail__more--details listing-detail--border">
                                                                <div class="listing-detail__title"><h2>More Details on {{$addressAsH1tag}} </h2></div>
                                                                <div class="listing-detail__table">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        @foreach([
                                                                                                'lfd_fuelheating_17'=>'Heating Natural Gas',
                                                                                                'lfd_fireplace'=>'Fireplace Natural Gas',
                                                                                                'LM_Int1_2'=>'Number of Fireplaces',
                                                                                                'LFD_features'=>'Features',
                                                                                                'LFD_FloorFinish_19'=>'Floor Finish',
                                                                                                'LFD_ParkingAccess_14'=>'Parking Access',
                                                                                                'Ldf_exeteriorFinish_11'=>'Exterior Finish',
                                                                                                'LFD_Roof_12'=>'Roof',
                                                                                                'LFD_construction_10'=>'Construction Type',
                                                                                                'LM_Char10_18'=>'Foundation',
                                                                                                'LFD_Watersupply_8'=>'Water Supply',
                                                                                                'LFD_ServicesConnected_7'=>'Connected Services',
                                                                                                'LM_Int2_7'=>'Parking Spots',
                                                                                                'LM_Char10_17'=>'Zoning',
                                                                                                'LM_char100_3'=>'View',
                                                                                        ] as $_vancDtK => $_vancDt)
                                                                                        @if($listing->fetched_vancDetached->{$_vancDtK}??false)
                                                                                        <tr>
                                                                                                <td>{{$_vancDt}}</td>
                                                                                                <td>{{$listing->fetched_vancDetached->{$_vancDtK}??false}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @endforeach
                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                        @endif
                                                        @endcan
                                                        {{-- [added: 08-09-2022] [ENDS] --}}

                                                        {{-- Toggled-view-Rooms/Bathrooms [STARTS] --}}

                                                        <div class="placeholder4roomsbt " style="padding-bottom: 2em;">
                                                                <button class="btn" onclick="jQuery('.toggle-view-room_sizes').toggle();">View Room Sizes</button>
                                                                <div class="clearfix"></div>


                                                                <div class="col-md-6 col-sm-12 toggle-view-room_sizes" style="display:none">
                                                                        <div class="listing-detail__rooms xxlisting-detail--border">
                                                                                <div class="listing-detail__title"><h2>Rooms</h2></div>
                                                                                <div class="listing-detail__table">
                                                                                        <table class="table table-striped">
                                                                                                <thead>
                                                                                                        <tr>
                                                                                                                <th>Floor</th>
                                                                                                                <th>Type</th>
                                                                                                                <th>Dimensions</th>
                                                                                                        </tr>
                                                                                                </thead>
                                                                                                <tbody>
                                                                                                        {{-- dj -code-compressed on [13-Apr-2021] --}}
                                                                                                        <!-- If row is there show tr else not -->
                                                                                                        @for($i=1; $i<=28; $i++)
                                                                                                        @if($listing->mlsr_listing && $listing->mlsr_listing->{'room'.$i.'_level'} )
                                                                                                        <tr>
                                                                                                                <td>{{$listing->mlsr_listing->{'room'.$i.'_level'} }}</td>
                                                                                                                <td>@component('frontend.components.altlink'){{$listing->mlsr_listing->{'room'.$i.'_type'} }}@endcomponent</td>
                                                                                                                <td>@component('frontend.components.altlink'){{$listing->mlsr_listing->{'room'.$i.'_dim1'} }} x {{$listing->mlsr_listing->{'room'.$i.'_dim2'} }}@endcomponent</td>
                                                                                                        </tr>
                                                                                                        @endif
                                                                                                        @endfor

                                                                                                </tbody>
                                                                                        </table>
                                                                                </div>
                                                                        </div>
                                                                </div>

                                                                <div class="col-md-6 col-sm-12 toggle-view-room_sizes" style="display:none">
                                                                        <div class="listing-detail__bathrooms xxlisting-detail--border">
                                                                                <div class="listing-detail__title"><h2>Bathrooms</h2></div>
                                                                                <div class="listing-detail__table">
                                                                                        <table class="table table-striped">
                                                                                                <thead>
                                                                                                        <tr>
                                                                                                                <th>Floor</th>
                                                                                                                <th>Ensuite</th>
                                                                                                                <th>Pieces</th>
                                                                                                        </tr>
                                                                                                </thead>
                                                                                                <tbody>
                                                                                                        {{-- dj -code-compressed on [13-Apr-2021] --}}
                                                                                                        <!-- If row is there show tr else not -->
                                                                                                        @for($i=0; $i<=8; $i++)
                                                                                                        @if($listing->mlsr_listing && $listing->mlsr_listing->{'bath'.$i.'_ensuite'}  && $listing->mlsr_listing->{'bath'.$i.'_level'} )
                                                                                                        <tr>
                                                                                                                <td>{{$listing->mlsr_listing->{'bath'.$i.'_level'} }}</td>
                                                                                                                <td>@component('frontend.components.altlink'){{$listing->mlsr_listing->{'bath'.$i.'_ensuite'} }}@endcomponent</td>
                                                                                                                <td>@component('frontend.components.altlink'){{$listing->mlsr_listing->{'bath'.$i.'_pieces'} }}@endcomponent</td>
                                                                                                        </tr>
                                                                                                        @endif
                                                                                                        @endfor
                                                                                                </tbody>
                                                                                        </table>
                                                                                </div>
                                                                        </div>
                                                                </div>

                                                        </div>

                                                        {{-- 
                                                        <div class="placeholder4roomsbt" style="padding-bottom: 2em;">
                                                                <div class="listing-detail__title"><h2>&nbsp;</h2></div>
                                                                <div class="row">                                                                       
                                                                        <div class="col-sm-6 col-xs-12">
                                                                                <button class="btn-toggle-rooms" onclick="jQuery('.listing-detail__bathrooms').hide('fast');jQuery('.listing-detail__rooms').toggle('fast');">Rooms</button>
                                                                        </div>
                                                                        <div class="col-sm-6 col-xs-12">
                                                                                <button class="btn-toggle-rooms" onclick="jQuery('.listing-detail__bathrooms').toggle('fast');jQuery('.listing-detail__rooms').hide('fast');">Bathrooms</button>
                                                                        </div>
                                                                </div>
                                                                <div class="tabs4roomtables"></div>
                                                                <div class="clearfix"></div>
                                                                <script>
                                                                        setTimeout(function(){
                                                                                $('.tabs4roomtables').append($('.listing-detail__rooms')).append($('.listing-detail__bathrooms'));
                                                                                $('.listing-detail__bathrooms,.listing-detail__rooms').hide();
                                                                        },1000)
                                                                </script>
                                                        </div> --}}
                                                        
                                                        {{-- Toggled-view-Rooms/Bathrooms [ENDS] --}}

                                                </div> <!-- END COL-MD-8-->

                                                <div class="col-md-4 col-sm-12 col-xs-12 floating__box {{-- hidden-xs hidden-sm --}}" style="margin-bottom:15px">


                                                        @if($listing->status=='Active')
                                                        {{--<div class="listing-detail__offerland">
                                                                <div class="listing-detail__offerland-logo">
                                                                        <a href="#" data-toggle="modal" data-target="#offerlandModal"><img src="{{asset('frontend/images/offerland-logo-01.svg')}}"></a>
                                                                </div>
                                                                <div class="listing-detail__offerland-price">
                                                                        <a href="#" data-toggle="modal" data-target="#offerlandModal">OfferValue:</a><br />
                                                                        <p>{{Helper::money_format('%.2n',$commissionDetails['your_price']) }}</p>
                                                                </div>
                                                        </div>--}}

                                                        <div id="incformhsmhxs_bookappointment" class="hidden-sm hidden-xs">
                                                                @include('frontend.includes.listing_schedule_tour')
                                                        </div>
                                                        @endif

                                                        <div class="hidden-sm hidden-xs">
                                                                @guest
                                                                @if(in_array($listing->status, ['Sold', 'Terminated', 'Expired']))
                                                                {{-- Sidebar conversion widget for guest+sold/terminated/expired: drives sign-up --}}
                                                                <div style="background:#fff;border-radius:8px;border:1px solid #ddd;box-shadow:rgba(0,0,0,0.12) 0px 2px 8px;overflow:hidden;margin-bottom:20px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;">
                                                                  <div style="padding:20px 20px 18px;">
                                                                    <div style="font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#777;margin-bottom:10px;">&#128274; {{ $listing->status === 'Sold' ? 'Sold Price Locked' : 'Full Details Locked' }}</div>
                                                                    <div style="font-size:26px;font-weight:800;color:#333;letter-spacing:-1px;margin-bottom:12px;">{{ $listing->status === 'Sold' ? '🔒 $•••,•••' : '🔒 Sign In to View' }}</div>
                                                                    <div style="font-size:12px;color:#777;line-height:1.5;margin-bottom:6px;">Free forever · no credit card · cancel anytime</div>
                                                                    <div class="bc-sidebar-viewed"><span class="bc-sidebar-viewed-dot"></span> {{ 20 + abs(crc32(($listing->listingid ?? 'x') . date('Y-m-d')) % 75) }} people viewed this listing today</div>
                                                                    <button type="button" onclick="bcSidebarSignIn('google')" aria-label="Sign in with Google" style="display:flex;align-items:center;gap:9px;width:100%;background:#fff;color:#333;border:1px solid #ddd;border-radius:6px;padding:10px 14px;font-size:13px;font-weight:600;cursor:pointer;margin-bottom:8px;font-family:inherit;box-sizing:border-box;box-shadow:0 1px 3px rgba(0,0,0,.08);">
                                                                      <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;background:rgba(66,133,244,.1);border-radius:3px;font-size:11px;font-weight:800;color:#4285F4;flex-shrink:0;">G</span>
                                                                      <span style="flex:1;text-align:left;">Sign in with Google</span>
                                                                    </button>
                                                                    <button type="button" onclick="bcSidebarSignIn('facebook')" aria-label="Sign in with Facebook" style="display:flex;align-items:center;gap:9px;width:100%;background:#1877F2;color:#fff;border:none;border-radius:6px;padding:10px 14px;font-size:13px;font-weight:600;cursor:pointer;margin-bottom:8px;font-family:inherit;box-sizing:border-box;">
                                                                      <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.15);border-radius:3px;font-size:11px;font-weight:800;color:#fff;flex-shrink:0;">f</span>
                                                                      <span style="flex:1;text-align:left;">Sign in with Facebook</span>
                                                                    </button>
                                                                    <button type="button" onclick="bcSidebarSignIn('email')" aria-label="Sign in with email" style="display:flex;align-items:center;gap:9px;width:100%;background:#ee4223;color:#fff;border:none;border-radius:6px;padding:10px 14px;font-size:13px;font-weight:600;cursor:pointer;margin-bottom:0;font-family:inherit;box-sizing:border-box;">
                                                                      <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.2);border-radius:3px;font-size:11px;font-weight:800;color:#fff;flex-shrink:0;">@</span>
                                                                      <span style="flex:1;text-align:left;">Sign in with email</span>
                                                                    </button>
                                                                    <div style="display:flex;align-items:center;gap:10px;margin-top:16px;padding-top:14px;border-top:1px solid #eee;">
                                                                      <img src="{{ asset('frontend/images/teamagents/hani_faraj.jpg') }}" style="width:42px;height:42px;border-radius:50%;border:2px solid #ddd;object-fit:cover;object-position:top center;flex-shrink:0;" alt="Hani Faraj" />
                                                                      <div>
                                                                        <div style="font-size:12px;font-weight:700;color:#333;margin-bottom:2px;">Hani Faraj</div>
                                                                        <div style="font-size:11px;color:#777;">Questions? <a href="tel:6042293342" style="color:#ee4223;text-decoration:none;font-weight:600;">604-229-3342</a></div>
                                                                      </div>
                                                                    </div>
                                                                    <div style="font-size:10px;color:#888;text-align:center;margin-top:12px;line-height:1.5;">&#9733; 4.9 &nbsp;&#183;&nbsp; 700+ Google reviews<br>157,000+ registered users &nbsp;&#183;&nbsp; RE/MAX Crest Realty</div>
                                                                  </div>
                                                                </div>
                                                                @else
                                                                @include('frontend.includes.team_agents_sidebar')
                                                                @endif
                                                                @else
                                                                @include('frontend.includes.team_agents_sidebar')
                                                                @endguest
                                                        </div>
                                                        
                                                        <div style="margin-bottom: 25px;"></div>
                                                        
                                                        @if($listing->status == 'Active')
                                                                @include('frontend.includes.box_sidebar')
                                                        @endif

                                                        {{-- @if($listing->status == 'Active')
                                                                @include('frontend.includes.contact_form_sidebar')
                                                        @endif --}}

                                                        {{--
                                                        [bcoz already forced-hidden-by-style Disabled after-discussion on:07-10-2021] 
                                                        <div class="listing-detail__request-showing listing-detail__request-showing-scroll  hidden-xs" style="display: none;">
                                                                @if($listing->status == 'Active')
                                                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#viewingModal">Book A Viewing</button>
                                                                @endif
                                                        </div>

                                                        <div class="listing-detail__show--photos" style="display: none">
                                                                <button type="button" class="btn btn-primary showphotos__button" data-toggle="modal" data-target="#seeingPhotoModal" data-backdrop="static" data-keyboard="false">See More Photos</button>
                                                        </div>
                                                        --}}



                                                        <div class="listing__agent {{strtolower($listing->status)}}" style="margin-top:20px;">
                                                                <div class="listing-detail__agent-buttons row" style="margin-bottom:2px;">
                                                                        <div class="listing-detail__agent-contact clearfix">
                                                                                <div class="row">
                                                                                        <div class="col-md-12">
                                                                                                <div class="listing-detail__agent-buttons active row " id="shareButton" style="margin-bottom:2px; display:none">
                                                                                                        <div class="col-sm-12 col-xs-12" style="padding:0"><a href="javascript:;" class=""><p onclick="openShareOptions()" class="share_property_button">Share this Property</p></a></div>
                                                                                                </div>
                                                                                                <div class="listing-detail__agent-buttons active row " id="shareButtonSmsAndroid" style="display:none;margin-bottom:2px">
                                                                                                        <div class="col-sm-12 col-xs-12" style="padding:0"><a class="" href="sms:?body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}"><p class="share_property_button">Share this Property</p></a></div>
                                                                                                </div>
                                                                                                <div class="listing-detail__agent-buttons active row" id="shareButtonSmsiOS" style="display:none;margin-bottom:2px">
                                                                                                        <div class="col-sm-12 col-xs-12" style="padding:0"><a class="" href="sms: &body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}"><p class="share_property_button">Share this Property</p></a></div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                </div>
                                                        </div>
                                                        {{--
                                                        @if($listing->status == "Active" )
                                                                <div class="listing__schedule--tour" style="display: none;/*box-shadow: rgba(0,0,0,.4) 0 0 8px;*/">
                                                                        <h3>Schedule a viewing</h3>
                                                                        <form id="showing_form" class="listing-detail__showing showing_form" autocomplete="off" method="post" action="">
                                                                                <div class="listing__schedule--tour--calendar-wrap clearfix">
                                                        <div class="swiper-container">
                                                        <div class="swiper-wrapper">
                                                                @php
                                                                    $startDay = Carbon\Carbon::now()->addDay();
                                                                    $endDay = Carbon\Carbon::now()->addDays(8);
                                                                @endphp
                                                                @while($startDay <= $endDay)
                                                                        <div class="swiper-slide">
                                                                            <div class="showing__checkbox--day">
                                                                                <label class="checkbox">
                                                                                <input type="radio" name="showing_date" class="showing-day__checked" value="{{$startDay->format('Y-m-d')}}">
                                                                                <div>
                                                                                        <span class="listing__schedule--tour-weekday">{{$startDay->format('l')}}</span>
                                                                                        <span class="listing__schedule--tour-day">{{$startDay->format('d')}}</span>
                                                                                        <span class="listing__schedule--tour-month">{{$startDay->format('M')}}</span>
                                                                                </div>
                                                                                </label>
                                                                        </div>
                                                                        </div>
                                                                        @php
                                                                $startDay->addDay();
                                                                        @endphp
                                                                @endwhile   
                                                        </div>
                                                        </div>  

                                                        <div class="swiper-button-prev" style="display:none"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27 44"><path d="M0,22L22,0l2.1,2.1L4.2,22l19.9,19.9L22,44L0,22L0,22L0,22z"></svg></div>
                                                        <div class="swiper-button-next" style="display:none"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27 44"><path d="M27,22L27,22L5,44l-2.1-2.1L22.8,22L2.9,2.1L5,0L27,22L27,22z"></svg></div>
                                                </div>

                                                <div class="listing__schedule--tour--time--dropdown">
                                                        <select>
                                                                <option value="">Choose a Time...</option>
                                                                <option value="09:00">09:00am</option>
                                                                <option value="09:30">09:30am</option>
                                                                <option value="10:00">10:00am</option>
                                                                <option value="10:30">10:30am</option>
                                                                <option value="11:00">11:00am</option>
                                                                <option value="11:30">11:30am</option>
                                                                <option value="12:00">12:00pm</option>
                                                                <option value="12:30">12:30pm</option>
                                                                <option value="13:00">1:00pm</option>
                                                                <option value="13:30">1:30pm</option>
                                                                <option value="14:00">2:00pm</option>
                                                                <option value="14:30">2:30pm</option>
                                                                <option value="15:00">3:00pm</option>
                                                                <option value="15:30">3:30pm</option>
                                                                <option value="16:00">4:00pm</option>
                                                                <option value="16:30">4:30pm</option>
                                                                <option value="17:00">5:00pm</option>
                                                                <option value="17:30">5:30pm</option>
                                                                <option value="18:00">6:00pm</option>
                                                                <option value="18:30">6:30pm</option>
                                                                <option value="19:00">7:00pm</option>
                                                                <option value="19:30">7:30pm</option>
                                                                <option value="20:00">8:00pm</option>
                                                                <option value="20:30">8:30pm</option>
                                                        </select>
                                                </div>

                                                <div class="listing__schedule--tour--realtor">
                                                                                        <div class="listing__schedule--tour--realtor-header">Are you working with a realtor?</div>
                                                                                        <div class="listing__schedule--tour--radio" id="workWithRealtorReq">
                                                                                                <label>
                                                                        <input type="radio" name="showing_realtor" value="Yes" class="realtorReqCheck"><span>Yes</span>
                                                                </label>
                                                                <label>
                                                                        <input type="radio" name="showing_realtor" value="No" class="realtorReqCheck"><span>No</span>
                                                                </label>
                                                                                        </div>
                                                                                </div>
                                                
                                                <div class="listing__schedule--tour--button">
                                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#scheduleModal">Schedule a tour</button>
                                                </div>

                                                                        </form>
                                                                </div>
                                                        @endif  
                                                        --}}

                                                        {{-- Commented to include-at desired places- @include('frontend.includes.schedule_tour_sidebar') --}}

                        </div>
                                        </div>

                                        {{-- Commented on [20-05-2021] 
                                        <div class="col-md-12 col-sm-12">
                                                <div class="listing-detail__rooms listing-detail--border">
                                                        <div class="listing-detail__title"><h2>Rooms</h2></div>
                                                        <div class="listing-detail__table">
                                                                <table class="table table-striped">
                                                                        <thead>
                                                                                <tr>
                                                                                        <th>Floor</th>
                                                                                        <th>Type</th>
                                                                                        <th>Dimensions</th>
                                                                                </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                                <!-- If row is there show tr else not -->
                                                                                @for($i=1; $i<=28; $i++)
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->{'room'.$i.'_level'} )
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->{'room'.$i.'_level'} }}</td>
                                                                                                <td>{{$listing->mlsr_listing->{'room'.$i.'_type'} }}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->{'room'.$i.'_dim1'} }} x {{$listing->mlsr_listing->{'room'.$i.'_dim2'} }}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @endfor
                                                                                 --}}

                                                                                {{-- Commented on [13-Apr-2021] -for-code-compression --}}
                                                                                {{--
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room1_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room1_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room1_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room1_dim1}} x {{$listing->mlsr_listing->room1_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room2_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room2_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room2_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room2_dim1}} x {{$listing->mlsr_listing->room2_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room3_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room3_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room3_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room3_dim1}} x {{$listing->mlsr_listing->room3_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room4_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room4_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room4_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room4_dim1}} x {{$listing->mlsr_listing->room4_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room5_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room5_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room5_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room5_dim1}} x {{$listing->mlsr_listing->room5_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room6_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room6_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room6_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room6_dim1}} x {{$listing->mlsr_listing->room6_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room7_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room7_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room7_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room7_dim1}} x {{$listing->mlsr_listing->room7_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room8_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room8_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room8_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room8_dim1}} x {{$listing->mlsr_listing->room8_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room9_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room9_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room9_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room9_dim1}} x {{$listing->mlsr_listing->room9_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room10_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room10_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room10_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room10_dim1}} x {{$listing->mlsr_listing->room10_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room11_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room11_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room11_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room11_dim1}} x {{$listing->mlsr_listing->room11_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room12_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room12_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room12_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room12_dim1}} x {{$listing->mlsr_listing->room12_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room13_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room13_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room13_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room13_dim1}} x {{$listing->mlsr_listing->room13_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif @if($listing->mlsr_listing && $listing->mlsr_listing->room14_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room14_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room14_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room14_dim1}} x {{$listing->mlsr_listing->room14_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room15_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room15_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room15_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room15_dim1}} x {{$listing->mlsr_listing->room15_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif @if($listing->mlsr_listing && $listing->mlsr_listing->room16_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room16_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room16_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room16_dim1}} x {{$listing->mlsr_listing->room16_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room17_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room17_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room17_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room17_dim1}} x {{$listing->mlsr_listing->room17_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif @if($listing->mlsr_listing && $listing->mlsr_listing->room18_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room18_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room18_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room18_dim1}} x {{$listing->mlsr_listing->room18_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room19_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room19_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room19_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room19_dim1}} x {{$listing->mlsr_listing->room19_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif @if($listing->mlsr_listing && $listing->mlsr_listing->room20_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room20_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room20_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room20_dim1}} x {{$listing->mlsr_listing->room20_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room21_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room21_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room21_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room21_dim1}} x {{$listing->mlsr_listing->room21_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room22_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room22_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room22_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room22_dim1}} x {{$listing->mlsr_listing->room22_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room23_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room23_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room23_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room23_dim1}} x {{$listing->mlsr_listing->room23_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room24_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room24_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room24_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room24_dim1}} x {{$listing->mlsr_listing->room24_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room25_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room25_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room25_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room25_dim1}} x {{$listing->mlsr_listing->room25_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif @if($listing->mlsr_listing && $listing->mlsr_listing->room26_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room26_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room26_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room26_dim1}} x {{$listing->mlsr_listing->room26_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room27_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room27_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room27_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room27_dim1}} x {{$listing->mlsr_listing->room27_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room28_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room28_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room28_type}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->room28_dim1}} x {{$listing->mlsr_listing->room28_dim2}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                --}}
                                                                        {{-- Commented on [20-05-2021]  
                                                                        </tbody>
                                                                </table>
                                                        </div>
                                                </div>
                                        </div> 
                                        --}}

                                        {{-- Commented on [20-05-2021]-bathrooms
                                        <div class="col-md-12 col-sm-12">
                                                <div class="listing-detail__bathrooms listing-detail--border">
                                                        <div class="listing-detail__title"><h2>Bathrooms</h2></div>
                                                        <div class="listing-detail__table">
                                                                <table class="table table-striped">
                                                                        <thead>
                                                                                <tr>
                                                                                        <th>Floor</th>
                                                                                        <th>Ensuite</th>
                                                                                        <th>Pieces</th>
                                                                                </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                                <!-- If row is there show tr else not -->
                                                                                @for($i=0; $i<=8; $i++)
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->{'bath'.$i.'_ensuite'}  && $listing->mlsr_listing->{'bath'.$i.'_level'} )
                                                                                <tr>
                                                                                        <td>{{$listing->mlsr_listing->{'bath'.$i.'_level'} }}</td>
                                                                                        <td>{{$listing->mlsr_listing->{'bath'.$i.'_ensuite'} }}</td>
                                                                                        <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->{'bath'.$i.'_pieces'} }}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                </tr>
                                                                                @endif
                                                                                @endfor

                                                                                --}}
                                                                                {{-- Commented on [13-Apr-2021] -for-code-compression --}}
                                                                                {{--
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath1_ensuite && $listing->mlsr_listing->bath1_level)
                                                                                <tr>
                                                                                        <td>{{$listing->mlsr_listing->bath1_level}}</td>
                                                                                        <td>{{$listing->mlsr_listing->bath1_ensuite}}</td>
                                                                                        <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->bath1_pieces}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath2_ensuite && $listing->mlsr_listing->bath2_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath2_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath2_ensuite}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->bath2_pieces}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath3_ensuite && $listing->mlsr_listing->bath3_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath3_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath3_ensuite}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->bath3_pieces}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath4_ensuite && $listing->mlsr_listing->bath4_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath4_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath4_ensuite}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->bath4_pieces}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath5_ensuite && $listing->mlsr_listing->bath5_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath5_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath5_ensuite}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->bath5_pieces}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath6_ensuite && $listing->mlsr_listing->bath6_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath6_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath6_ensuite}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->bath6_pieces}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath7_ensuite && $listing->mlsr_listing->bath7_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath7_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath7_ensuite}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->bath7_pieces}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath8_ensuite && $listing->mlsr_listing->bath8_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath8_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath8_ensuite}}</td>
                                                                                                <td>@if(Auth::user() && $isUserPremiumMember){{$listing->mlsr_listing->bath8_pieces}}@else<a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >{{LoginToViewText()}}</a>@endif</td>
                                                                                        </tr>
                                                                                @endif 
                                                                                --}}
                                        {{-- Commented on [20-05-2021]
                                                                        </tbody>
                                                                </table>
                                                        </div>
                                                </div>
                                        </div>
                                         --}}


                                        @if($floorplan)
                                        <div class="col-md-12 col-sm-12">
                                                <div id="floorplan_area"></div>
                                                <div class="listing-detail__floorplan listing-detail--border">
                                                        <div class="listing-detail__title"><h2>Floorplan</h2></div>
                                                        @if($authUser && $isUserPremiumMember)
                                                        <a href="{{$floorplan}}" data-fancybox="floorplan"> <img src="{{$floorplan}}" class="img-responsive col-md-10 col-md-offset-1 col-sm-12 " title="{{$listing->streetaddress}}, {{$listing->city}}, {{$listing->province}} Floor Plan" alt="{{$listing->streetaddress}}, {{$listing->city}}, {{$listing->province}} Floor Plan" loading="lazy" width="380" height="380"></a>
                                                        @else
                                                        <a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >
                                                                @auth
                                                                <span style="position: absolute;left: calc(30% - 2em);top: 33%;display: inline-block;z-index: 2;font-size: 2em; " >{{LoginToViewText('Upgrade Subscription To View Floor Plan')}}</span>
                                                                @else
                                                                <span style="position: absolute;left: calc(50% - 2em);top: 33%;display: inline-block;z-index: 2;font-size: 2em; " >{{LoginToViewText()}}</span>
                                                                @endauth
                                                                <img src="{{$floorplan}}" class="img-responsive col-md-10 col-md-offset-1 col-sm-12 bcch-blur" title="{{$listing->streetaddress}}, {{$listing->city}}, {{$listing->province}} Floor Plan" alt="{{$listing->streetaddress}}, {{$listing->city}}, {{$listing->province}} Floor Plan" loading="lazy" width="380" height="380">
                                                        </a>
                                                        @endif
                                                        <br>
                                                </div>
                                        </div>
                                        @endif
                                        
                                        @if($floorplate)
                                        <div class="col-md-12 col-sm-12">
                                                <div id="floorplan_area"></div>
                                                <div class="listing-detail__floorplan listing-detail--border">
                                                        <div class="listing-detail__title"><h2>Floor Plate</h2></div>
                                                        @if($authUser && $isUserPremiumMember)
                                                        <a href="{{$floorplate}}" data-fancybox="floorplate"> <img src="{{$floorplate}}" class="img-responsive col-md-10 col-md-offset-1 col-sm-12" title="{{$listing->streetaddress}}, {{$listing->city}}, {{$listing->province}} Floor Plate" alt="{{$listing->streetaddress}}, {{$listing->city}}, {{$listing->province}} Floor Plate" loading="lazy" width="380" height="380"></a>
                                                        @else
                                                        <a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}" >
                                                                @auth
                                                                <span style="position: absolute;left: calc(30% - 2em);top: 33%;display: inline-block;z-index: 2;font-size: 2em;" >{{LoginToViewText('Upgrade Subscription To View Floor Plate')}}</span>
                                                                @else
                                                                <span style="position: absolute;left: calc(50% - 2em);top: 33%;display: inline-block;z-index: 2;font-size: 2em;" >{{LoginToViewText()}}</span>
                                                                @endauth
                                                                <img src="{{$floorplate}}" class="img-responsive col-md-10 col-md-offset-1 col-sm-12 bcch-blur" title="{{$listing->streetaddress}}, {{$listing->city}}, {{$listing->province}} Floor Plate" alt="{{$listing->streetaddress}}, {{$listing->city}}, {{$listing->province}} Floor Plate" loading="lazy" width="380" height="380">
                                                        </a>
                                                        @endif
                                                        <br>
                                                </div>
                                        </div>
                                        @endif

                                        @if($building)
                                        @if($building->amenities && $building->amenities != '' && $building->amenities !='NONE')
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="building-detail__amenities building-detail--border">
                                                                @if($building->name == 'Oscar')
                                                                        <div class="building-detail__title"><h2>Buildings Amenities</h2></div>
                                                                @else
                                                                   <div class="building-detail__title"><h2>{{html_entity_decode(ucwords(strtolower($building->name)))}} Buildings Amenities</h2></div>
                                                                @endif
                                                                <div class="listing-detail__details-items row clearfix"><!--row-->
                                                                
                                                                @php $amenities = explode(',', $building->amenities) @endphp
                                                                @foreach ($amenities as $amenity)
                                                                @php $amenity = ucwords(strtolower(str_replace(';','/ ',str_replace('/', '/ ',$amenity)))) @endphp
                                                                        @if (substr_count($amenity, 'AIR COND') > 0 || substr_count($amenity, 'Air Cond') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/aircon2.svg')}}" loading="lazy" width="40" height="40" alt="aircon2"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'EXERCISE') > 0 || substr_count($amenity, 'Exercise') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/exercise.svg')}}" loading="lazy" width="40" height="40" alt="exercise" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'LAUNDRY') > 0 || substr_count($amenity, 'Laundry') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/in-suite-laundry.svg')}}" loading="lazy" width="40" height="40" alt="in-suite-laundry"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'HOT TUB') > 0 || substr_count($amenity, 'Hot Tub') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/hottub.svg')}}" loading="lazy" width="40" height="40" alt="hottub"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'POOL') > 0 || substr_count($amenity, 'Pool') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/swimming-pool.svg')}}" loading="lazy" width="40" height="40" alt="swimming-pool" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>                            
                                                                        @elseif (substr_count($amenity, 'SAUNA') > 0 || substr_count($amenity, 'Sauna') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/sauna.svg')}}" loading="lazy" width="40" height="40" alt="sauna" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'ELEVATOR') > 0 || substr_count($amenity, 'Elevator') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/elevator.svg')}}" loading="lazy" width="40" height="40" alt="elevator" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'TENNIS COURT') > 0 || substr_count($amenity, 'Tennis Court') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/tennis-court.svg')}}" loading="lazy" width="40" height="40" alt="tennis-court" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'BIKE ROOM') > 0 || substr_count($amenity, 'Bike Room') > 0 || substr_count($amenity, 'BIKE STORAGE') > 0 || substr_count($amenity, 'Bike Storage') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/bike-room.svg')}}" loading="lazy" width="40" height="40" alt="bike-room" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'STORAGE') > 0 || substr_count($amenity, 'Storage') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/storage-locker.svg')}}" loading="lazy" width="40" height="40" alt="storage"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'WHEELCHAIR ACCESS') > 0 || substr_count($amenity, 'Wheelchair') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/wheelchair.svg')}}" alt="wheelchair"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'BARN') > 0 || substr_count($amenity, 'Barn') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/barn.svg')}}" loading="lazy" width="40" height="40" alt="barn"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'EXTERIOR LIGHTING') > 0 || substr_count($amenity, 'Exterior Lighting') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/exterior-lighting.svg')}}" loading="lazy" width="40" height="40" alt="lighting"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'GREEN HOUSE') > 0 || substr_count($amenity, 'Green House') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/greenhouse.svg')}}" loading="lazy" width="40" height="40" alt="greenhouse"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'GUEST SUITE') > 0 || substr_count($amenity, 'Guest Suite') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/guest-suite.svg')}}" loading="lazy" width="40" height="40" alt="guest-suite"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'IRRIGATION') > 0 || substr_count($amenity, 'Irrigation') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/irrigation.svg')}}" loading="lazy" width="40" height="40" alt="irrigation"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'PLAYHOUSE') > 0 || substr_count($amenity, 'Playhouse') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/playhouse.svg')}}" loading="lazy" width="40" height="40" alt="playhouse"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'ROOFTOP DECK') > 0 || substr_count($amenity, 'Rooftop Deck') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/rooftop-deck.svg')}}" loading="lazy" width="40" height="40" alt="rooftop"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'SATELLITE DISH') > 0 || substr_count($amenity, 'Satellite Dish') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/satellite-dish.svg')}}" loading="lazy" width="40" height="40" alt="satellite-dish" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'STREET LIGHTING') > 0 || substr_count($amenity, 'Street Lighting') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/street-lighting.svg')}}" loading="lazy" width="40" height="40" alt="street-lighting"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'WORKSHOP ATTACHED') > 0 || substr_count($amenity, 'Workshop Attached') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/workshop-attached.svg')}}" loading="lazy" width="40" height="40" alt="workshop-attached" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'HOBBY/WORK') > 0 || substr_count($amenity, 'Hobby') > 0 || substr_count($amenity, 'Work') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/hobby-work-room.svg')}}" loading="lazy" width="40" height="40" alt="hobby-work-room" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'GARDEN') > 0 || substr_count($amenity, 'Garden') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/garden.svg')}}" loading="lazy" width="40" height="40" alt="garden" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'RESTAURANT') > 0 || substr_count($amenity, 'Restaurant') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/restaurant.svg')}}" loading="lazy" width="40" height="40" alt="restaurant" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'GARBAGE REMOVAL') > 0 || substr_count($amenity, 'Garbage Removal') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/garbage-removal.svg')}}" loading="lazy" width="40" height="40" alt="garbage-removal"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'SHARED BBQ') > 0 || substr_count($amenity, 'Shared Bbq') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/shared-bbq.svg')}}" loading="lazy" width="40" height="40" alt="shared-bbq"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'GEOTHERMAL') > 0 || substr_count($amenity, 'Geothermal') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/geothermal.svg')}}" loading="lazy" width="40" height="40" alt="geothermal"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'PEST CONTROL') > 0 || substr_count($amenity, 'Pest Control') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/pest-control.svg')}}" loading="lazy" width="40" height="40" alt="pest-control"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'CLUB HOUSE') > 0 || substr_count($amenity, 'Club House') > 0 || substr_count($amenity, 'Clubhouse') > 0 || substr_count($amenity, 'CLUBHOUSE') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/clubhouse.svg')}}" loading="lazy" width="40" height="40" alt="clubhouse"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'PLAYGROUND') > 0 || substr_count($amenity, 'Playground') > 0 || substr_count($amenity, 'Play Ground') > 0 || substr_count($amenity, 'PLAY GROUND') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/playhouse.svg')}}" loading="lazy" width="40" height="40" alt="playhouse"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'RECREATION CENTER') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/recreation-center.svg')}}" loading="lazy" width="40" height="40" alt="ecreation-center"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'REC ROOM') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/rec-room.svg')}}" loading="lazy" width="40" height="40" alt="rec-room"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'DAY CARE') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/day-care.svg')}}" loading="lazy" width="40" height="40" alt="daycare"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'BUILDING COMMON COSTS') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/building-common-costs.svg')}}" loading="lazy" width="40" height="40" alt="commoncost"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'PROPERTY MANAGEMENT') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/property-management.svg')}}" loading="lazy" width="40" height="40" alt="management"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'RECYCLING PROGRAM') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/recycling-program.svg')}}" loading="lazy" width="40" height="40" alt="recycling"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'ROOF TOP PATIO') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/rooftop-patio.svg')}}" loading="lazy" width="40" height="40" alt="rooftop"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'INDEPENDENT LIVING') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/independent-living.svg')}}" loading="lazy" width="40" height="40" alt="independent-living" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'ASSISTED LIVING') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/assisted-living.svg')}}" loading="lazy" width="40" height="40" alt="assisted-living" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'COMMUNITY MEALS') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/community-meals.svg')}}" loading="lazy" width="40" height="40" alt="community-meals"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'WEEKLY HOUSEKEEPING') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/weekly-housekeeping.svg')}}" loading="lazy" width="40" height="40" alt="weekly-housekeeping" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'MEETING ROOM') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/meeting-room.svg')}}" loading="lazy" width="40" height="40" alt="meeting-room" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'LANDLORD INSURANCE') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/landlord-insurance.svg')}}" loading="lazy" width="40" height="40" alt="rooftop" alt="landlord-insurance" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'PROPERTY TAXES') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/property-tax.svg')}}" loading="lazy" width="40" height="40" alt="propery-tax"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'DAYCARE RM') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/day-care.svg')}}" loading="lazy" width="40" height="40" alt="day-care"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'NONE') > 0 || substr_count($amenity, 'None') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/none.svg')}}" loading="lazy" width="40" height="40" alt="none"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'OTHER') > 0 || substr_count($amenity, 'Other') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/other.svg')}}" loading="lazy" width="40" height="40" alt="other"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>                           
                                                                        @else
                                                                        <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/other.svg')}}" loading="lazy" width="40" height="40" alt="other"/>
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <div>{{$amenity}}</div>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                @endforeach
                                                                </div>
                                                        </div>
                                                </div>
                                        @endif
                                        @endif

                                        @if($building && count($buildingPhotos) > 0)
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="building-detail__photos building-detail--border" id="building-photos" style="display: none">
                                                                @if($building->name == 'Oscar')
                                                                <div class="building-detail__title"><h2>Building Photos</h2></div>
                                                                @else
                                                                <div class="building-detail__title"><h2>{{$building->name}} Building Photos</h2></div>
                                                                @endif
                                                                <div class="listing-detail__details-items clearfix">
                                                                        <div class="listing-detail__item">
                                                                                <div class="listing-detail__animation">
                                                                                        <div class="building-detail__images">
                                                                                                @foreach($buildingPhotos as $photo)
                                                                                                @if (Browser::isMobile())
                                                                                                <div class="listing-detail__image"><img sizes="" src="https://media.pixilinkserver.com/upload/house/images/{{$photo['image_name']}}?w=450&h=300" loading="lazy" width="450" height="300" alt="{{startsWithNumber($building->name)?$building->name:$building->name." ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}, {{ucfirst(strtolower($building->city))}}"></div>
                                                                                                @else
                                                                                                <div class="listing-detail__image"><img sizes="" src="https://media.pixilinkserver.com/upload/house/images/{{$photo['image_name']}}?w=800&h=600" loading="lazy" width="800" height="600" alt="{{startsWithNumber($building->name)?$building->name:$building->name." ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}, {{ucfirst(strtolower($building->city))}}"></div>
                                                                                                @endif
                                                                                                @endforeach
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                </div>
                                                        </div>
                                                </div>
                                                <div class="clearfix"></div>
                                        @endif
                                
                                        @if($building_matterport)
                                                <div class="col-md-12 col-sm-12">
                                                        <div id="matterport_area"></div>
                                                        <div class="listing-detail__floorplan listing-detail--border">
                                                                <div class="listing-detail__title"><h2>{{html_entity_decode(ucwords(strtolower($building->name)))}} Amenities 3D Tour</h2></div>
                                                                <div class="resp-container">
                                                                        <iframe class="resp-iframe" title="" src="{{$building_matterport}}" frameborder="0" loading="lazy" allowfullscreen></iframe>
                                                                </div>
                                                        </div>
                                                </div>
                                        @endif
                                        
                                        {{-- [Re-enabled on:07-09-2022] [BEGINS] --}}
                                        {{-- Commented to remove-on-demand on:[19-02-2022]
                                        @if(Browser::isMobile())
                                        @else
                                        --}}
                                        <div class="col-md-12 col-sm-12">
                                                <div class="listing-detail__details listing-detail--border">
                                                        <div class="listing-detail__title"><h2>Location</h2></div>
                                                        <div class="listing-detail__map">
                                                                <iframe width="100%" height="350" frameborder="0" style="border:0"  marginwidth="0" data-src4lazyloadXX="" src="https://www.google.com/maps/embed/v1/place?q={{urlencode($listing->streetaddress.','.$listing->city)}}&key=AIzaSyBe_jE1XvuaLT9mHySPF4dLAu3kmQXprB0" loading="lazy" allowfullscreen></iframe>
                                                        </div>
                                                </div>
                                        </div>
                                        {{-- @endif  --}}
                                        {{-- [Re-enabled on:07-09-2022] [ENDS] --}}

                                        @if($building)
                                        @if($building_additional_information && array_key_exists('restrictions', $buildingBcnApi) && array_key_exists('pets', $buildingBcnApi['restrictions']) && ($buildingBcnApi['restrictions']['pets']['dogs'] || $buildingBcnApi['restrictions']['pets']['cats']))
                                        <div class="col-md-12 col-sm-12">
                                                <div class="listing-detail__technical listing-detail--border">
                                                        @if($building->name == 'Oscar')
                                                                <div class="listing-detail__title"><h2>Building Pets Restrictions</h2></div>
                                                        @else
                                                        <div class="listing-detail__title"><h2>{{html_entity_decode(ucwords(strtolower($building->name)))}} Building Pets Restrictions</h2></div>
                                                        @endif
                                                        <div class="listing-detail__table">
                                                                <table class="table table-striped">
                                                                        <tbody>
                                                                                @if(array_key_exists('no_pets', $buildingBcnApi['restrictions']['pets']) && $buildingBcnApi['restrictions']['pets']['no_pets'])
                                                                                <tr>
                                                                                        <td style="width: 40%">Pets Allowed:</td>
                                                                                        <td>{{ucwords(strtolower($buildingBcnApi['restrictions']['pets']['no_pets']))}}</td>
                                                                                </tr>
                                                                                @endif
                                                                                @if(array_key_exists('dogs', $buildingBcnApi['restrictions']['pets']) && $buildingBcnApi['restrictions']['pets']['dogs'])
                                                                                <tr>
                                                                                        <td style="width: 40%">Dogs Allowed:</td>
                                                                                        <td>{{ucwords(strtolower($buildingBcnApi['restrictions']['pets']['dogs']))}}</td>
                                                                                </tr>
                                                                                @endif
                                                                                @if(array_key_exists('cats', $buildingBcnApi['restrictions']['pets']) && $buildingBcnApi['restrictions']['pets']['cats'])
                                                                                <tr>
                                                                                        <td style="width: 40%">Cats Allowed:</td>
                                                                                        <td>{{ucwords(strtolower($buildingBcnApi['restrictions']['pets']['cats']))}}</td>
                                                                                </tr>
                                                                                @endif
                                                                        </tbody>
                                                                </table>
                                                        </div>
                                                </div>
                                        </div>
                                        @endif
                                        @endif

                                        @if($building)
                                        @if(count($active_listings) > 0)
                                        <div class="col-md-12 col-sm-12">
                                                <div class="building-detail__details building-detail--border">
                                                        <!--<div class="building-detail__title--thin">Active Listings-->
                                                        <div class="building-detail__title">
                                                                <!--<h2>Other Listings in this Building</h2>-->
                                                                {{-- <h2>Other Units For Sale in {{$listing->street_number}} {{ucwords(strtolower($listing->street_name))}} {{ucwords(strtolower($listing->street_type))}}, {{$listing->cityProperCased}}</h2> --}}
                                                                <h2>Other {{(ucwords($listing->getType())!='other'?($listing->getType()=='Apartment'?'Condos':$listing->getType().'s'):'Units')}} For Sale in {{$listing->street_number}} {{ucwords(strtolower($listing->street_name))}} {{ucwords(strtolower($listing->street_type))}}, {{$listing->cityProperCased}}</h2>
                                                                <div class="pull-right" style="font-size:15px; margin-top:5px">
                                                                        <div class="choose__time" id="active_beds">
                                                                                {{-- <a href="javascript:;" class="@if($beds== 'all') active @endif" data-val="all">All</a> @if($maxBeds > 0) | <a href="javascript:;" class="@if($beds== 'beds1') active @endif" data-val="beds1">1 Bed</a>@endif @if($maxBeds > 1)| <a href="javascript:;" class="@if($beds== 'beds2') active @endif" data-val="beds2">2 Bed</a> @endif @if($maxBeds > 2) | <a href="javascript:;" class="@if($beds== 'beds3') active @endif" data-val="beds3">3 Bed</a> @endif @if($maxBeds > 3)| <a href="javascript:;" class="@if($beds== 'beds3p') active @endif" data-val="beds3p">4+ Beds</a> @endif  --}}
                                                                                
                                                                                <label for="active_beds_options">Type:</label>
                                                                                <select name="active_beds_options" id="active_beds_options" class="stats__time">
                                                                                        <option value="all">All</option>
                                                                                        @if($maxBeds > 0) <option value="beds1">1 Bed</option> @endif
                                                                                        @if($maxBeds > 1)<option value="beds2">2 Bed</option> @endif
                                                                                        @if($maxBeds > 2)<option value="beds3">3 Bed</option> @endif
                                                                                        @if($maxBeds > 3)<option value="beds3p">4+ Bed</option> @endif
                                                                                        @if($isTownhouse)<option value="TH">Townhouse</option>@endif
                                                                                        @if($isPenthouse)<option value="PH">Penthouse</option>@endif
                                                                                </select>  
                                                                        </div>
                                                                </div>   
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="building-detail__table table-responsive">
                                                                <div class="listing-detail__activeListings-table table-responsive">
                                                                <table class="table" id="active_table">
                                                                        <thead>
                                                                                <tr>
                                                                                        <th>Date</th>
                                                                                        <th>Address</th>
                                                                                        <th>Status</th>
                                                                                        <th>Bed</th>
                                                                                        <th>Bath</th>
                                                                                        <th>Asking Price</th>
                                                                                        <!-- <th>Est. Sold Price</th> -->
                                                                                        <th>Sqft</th>
                                                                                        <th>$/Sqft</th>
                                                                                        <th title="Days On Market">DOM</th>
                                                                                        <th>Brokerage</th>
                                                                                </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                                @include('frontend.components.active_listings_table_tbody', compact('active_listings','building'))
                                                                        </tbody>
                                                                </table>
                                                                </div>
                                                                <p style="display:none" id="no_active_listing_available">
                                                                        <span>No listing available for the selected option.</span>
                                                                </p>
                                                        </div>
                                                </div>
                                        </div>
                                        @endif
                                        @endif


                        @if (Browser::isMobile())
                        {{-- <div class="banner--wrapper banner--wrapper-2709">
                            <div class="listing-detail__banner text-center" style="margin-bottom:2em;">
                                <a href="https://calendly.com/d/n2xx-xg68/meeting-with-bc-condos-and-homes-team?month=2021-06" target="_blank">
                                    <img src="{{asset('frontend/images/listing-banner_080921.jpg')}}" width="350" height="200" style="width: 100%; height:auto;" alt="" loading="lazy" />
                                </a>
                            </div>
                        </div> --}}
                        @else
                        {{--  Commented to remove-on-demand on:[19-02-2022]
                        <div class="banner--wrapper banner--wrapper-2709">
                            <div class="listing-detail__banner text-center" style="margin-bottom:2em;">
                                <a href="https://calendly.com/d/n2xx-xg68/meeting-with-bc-condos-and-homes-team?month=2021-06" target="_blank">
                                    <img src="{{asset('frontend/images/listing-banner_080921.jpg')}}" width="700" height="200" style="width: 100%; height:auto;" alt="" loading="lazy" />
                                </a>
                            </div>
                        </div>
                        --}}
                        @endif



                                        @if($building)
                                        <div class="col-md-12 col-sm-12">
                                                <div class="building-detail__details building-detail--border{{-- -dis-13sep21 --}} hidden-xs" id="sold-history">
                                                        <!--<div class="building-detail__title--thin">Sold Listings-->
                                                        <div class="building-detail__title">
                                                                <!--<h2>Recent Solds in this Building</h2>-->
                                                                <h2>Recent Solds in {{$listing->street_number}} {{ucwords(strtolower($listing->street_name))}} {{ucwords(strtolower($listing->street_type))}}, {{$listing->cityProperCased}}</h2>
                                                                <div class="pull-right sold__listings" style="font-size:15px; margin-top:5px">
                                                                        <div id="sold_period">
                                                                                {{--  <a href="javascript:;" class="@if($period== '30day') active @endif" data-val="30day">30 Days</a> | <a href="javascript:;" class="@if($period== '90day') active @endif" data-val="90day">90 Days</a> | <a href="javascript:;" class="@if($period== '6month') active @endif" data-val="6month">6 Months</a> | <a href="javascript:;" class="@if($period== '1year') active @endif" data-val="1year">1 Year</a> | <a href="javascript:;" class="@if($period== '2year') active @endif" data-val="2year">2 Years</a>  --}}
                                                                                <div class="building-select-dropdown choose__time">
                                                                                        <label for="soldPeriod">Term:</label> 
                                                                                        <select name="period" id="soldPeriod" class="stats__time">
                                                                                                <option value="30day" @if($interval_recentSolds== '30 day') selected='selected' @endif>30 Days</option>
                                                                                                <option value="90day" @if($interval_recentSolds== '90 day') selected='selected' @endif>90 Days</option>
                                                                                                <option value="6month" @if($interval_recentSolds== '6 month') selected='selected' @endif>6 Months</option>
                                                                                                <option value="1year" @if($interval_recentSolds== '1 year') selected='selected' @endif>1 Year</option>
                                                                                                <option value="2year" @if($interval_recentSolds== '2 year') selected='selected' @endif>2 Years</option>
                                                                                        </select>
                                                                                </div>
                                                                                <div class="building-select-dropdown choose__time">
                                                                                        <label for="soldBeds">Type:</label> 
                                                                                        <select name="soldBeds" id="soldBeds" class="stats__time">
                                                                                                <option value="all">All</option>
                                                                                                @if($maxBedsSold > 0)<option value="beds1">1 Bed</option> @endif
                                                                                                @if($maxBedsSold > 1)<option value="beds2">2 Bed</option>@endif
                                                                                                @if($maxBedsSold > 2)<option value="beds3">3 Bed</option>@endif
                                                                                                @if($maxBedsSold > 3)<option value="beds3p">4+ Bed</option>@endif
                                                                                                @if($isTownhouseSold)<option value="TH">Townhouse</option>@endif
                                                                                                @if($isPenthouseSold)<option value="PH">Penthouse</option>@endif
                                                                                        </select>
                                                                                </div>
                                                                        </div>
                                                                </div>   
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="building-detail__table table-responsive">
                                                                <table class="table table-sold" id="sold_table">
                                                                        <thead @if(count($sold_listings)==0) style="display:none" @endif>
                                                                                <tr>
                                                                                        <th>Date</th>
                                                                                        <th>Address</th>
                                                                                        <th>Bed</th>
                                                                                        <th>Bath</th>
                                                                                        <th>Asking Price</th>
                                                                                        <th>Sold Price</th>
                                                                                        <th>Sqft</th>
                                                                                        <th>$/Sqft</th>
                                                                                        <th title="Days On Market">DOM</th>
                                                                                        <th>Brokerage</th>
                                                                                </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                                @include('frontend.components.recent_sold_table_tbody_tr',compact('sold_listings','building'))
                                                                        </tbody>
                                                                </table>
                                                                <p @if(count($sold_listings) > 0) style="display:none" @endif id="no_sold_listing_available">
                                                                        <span>No Sold listing available during the selected period.</span>
                                                                </p>
                                                        </div>  
                                                </div>
                                        </div>
                                        @endif
                                        
                                        @if($building)
                                        @if(count($presale_listings))
                                        <div class="col-md-12 col-sm-12">
                                                <div class="building-detail__details building-detail--border" id="presale-listings">
                                                        <div class="building-detail__title">
                                                                <h2>Pre-Sales in {{$listing->street_number}} {{ucwords(strtolower($listing->street_name))}} {{ucwords(strtolower($listing->street_type))}}</h2>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="building-detail__table table-responsive">
                                                                <table class="table" id="table_presale_active">
                                                                        <thead>
                                                                                <tr>
                                                                                        <th>Date</th>
                                                                                        <th>Unit</th>
                                                                                        <th>Bed</th>
                                                                                        <th>Bath</th>
                                                                                        <th>Asking Price</th>
                                                                                        <th>Est. Sold Price</th>
                                                                                        <th>Sqft</th>
                                                                                        <th>$/Sqft</th>
                                                                                        <th title="Days On Market">DOM</th>
                                                                                        <th>Brokerage</th>
                                                                                </tr>
                                                                        </thead>
                                                                        <tbody>           
                                                                                @foreach ($presale_listings as $_listing)
                                                                                <tr>           
                                                                                        <td>{{date("m/d/Y", strtotime($_listing->list_date))}}</td>
                                                                                        <td class="active__listing"><a href="{{trim(route('listing-detail-page2', ['slug'=>$_listing->slug]))}}" >{{--$_listing->streetaddress--}}@if($_listing->type=='Apartment'){{$_listing->suite_no}}@else {{-- <span class='hidden'>TH </span> --}}{{$_listing->suite_no}} @endif</a></td>          
                                                                                        <td>{{$_listing->bedrooms}}</td>
                                                                                        <td>{{$_listing->bathstotal}}</td>
                                                                                        <td>{{$_listing->listprice}}</td>
                                                                                        <td>{{$_listing->soldprice}}</td>
                                                                                        <td>{{$_listing->livingarea_2}}</td>
                                                                                        <td>{{Helper::money_format('%.0n', $_listing->listprice_2/$_listing->livingarea_2)}}</td>
                                                                                        <td align="center">{{$_listing->active_days_on_market()}}</td>
                                                                                        <td>{{$_listing->reoffice}}</td>
                                                                                   </tr>                                       
                                                                                @endforeach
                                                                        </tbody>
                                                                </table>
                                                           </div>  
                                                </div>
                                        </div>
                                        @endif
                                        @endif

                                        @if($building)

                                        @if($building_additional_information)
                                        @if(array_key_exists('description_2',$buildingBcnApi['building_condo_info']) && $buildingBcnApi['building_condo_info']['description_2'])
                                        <div class="col-md-12 col-sm-12">
                                        <div class="building-detail__details listing-detail--border">
                                                <div class="building-detail__title"><h2>Building Overview</h2></div>
                                                {!!$buildingBcnApi['building_condo_info']['description_2']!!}
                                        </div>
                                        </div>
                                        @endif
                                        @endif
                                        
                                        {{-- Tables for Technical Info, Rooms and Bathrooms --}}
                                        @if($building_additional_information && array_key_exists('name', $buildingBcnApi['building_condo_info']))
                                        {{-- <div class="row"> --}}
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="listing-detail__technical listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Building Information</h2></div>
                                                                <div class="listing-detail__table">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        <tr>
                                                                                                <td>Building Name:</td>
                                                                                                <td><a href="{{$building_url}}">{{ucwords($buildingBcnApi['building_condo_info']['name'])}}</a></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Building Address:</td>
                                                                                                <td><a href="{{$building_url}}">{{$building->street_no}} {{ucwords(strtolower($building->street_name))}} {{ucwords(strtolower($building->street_type))}}, {{ucwords(strtolower($building->city))}}, {{$building->postalcode}}</a></td>
                                                                                        </tr>
                                                                                        @if(array_key_exists('levels', $buildingBcnApi['building_condo_info']) && $buildingBcnApi['building_condo_info']['levels'])
                                                                                        <tr>
                                                                                                <td>Levels:</td>
                                                                                                <td>{{$buildingBcnApi['building_condo_info']['levels']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('suites', $buildingBcnApi['building_condo_info']) && $buildingBcnApi['building_condo_info']['suites'])
                                                                                        <tr>
                                                                                                <td>Suites:</td>
                                                                                                <td>{{$buildingBcnApi['building_condo_info']['suites']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('status', $buildingBcnApi['building_condo_info']) && $buildingBcnApi['building_condo_info']['status'])
                                                                                        <tr>
                                                                                                <td>Status:</td>
                                                                                                <td>{{ucwords(strtolower($buildingBcnApi['building_condo_info']['status']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('built', $buildingBcnApi['building_condo_info']) && $buildingBcnApi['building_condo_info']['built'])
                                                                                        <tr>
                                                                                                <td>Built:</td>
                                                                                                <td>{{$buildingBcnApi['building_condo_info']['built']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('title_to_land', $buildingBcnApi['building_condo_info']) && $buildingBcnApi['building_condo_info']['title_to_land'])
                                                                                        <tr>
                                                                                                <td>Title To Land:</td>
                                                                                                <td><a href="{{route('city_buildings', ['city'=>$listing->cityEnsluged,'subarea'=>null, 'filter_titletoland'=>urlencode($buildingBcnApi['building_condo_info']['title_to_land'])])}}">{{ucwords(strtolower($buildingBcnApi['building_condo_info']['title_to_land']))}}</a></td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('building_type', $buildingBcnApi['building_condo_info']) && $buildingBcnApi['building_condo_info']['building_type'])
                                                                                        <tr>
                                                                                                <td>Building Type:</td>
                                                                                                <td>{{ucwords(strtolower($buildingBcnApi['building_condo_info']['building_type']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('strata_plan', $buildingBcnApi['building_condo_info']) && $buildingBcnApi['building_condo_info']['strata_plan'])
                                                                                        <tr>
                                                                                                <td>Strata Plan:</td>
                                                                                                <td>{{$buildingBcnApi['building_condo_info']['strata_plan']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('subarea', $buildingBcnApi['building_condo_info']) && $buildingBcnApi['building_condo_info']['subarea'])
                                                                                        <tr>
                                                                                                <td>Subarea:</td>
                                                                                                <td><a href="{{route('city_buildings',['city'=>$listing->cityEnsluged,'subarea'=>Helper::enslugPlace($buildingBcnApi['building_condo_info']['subarea'])])}}">{{ucwords(strtolower($buildingBcnApi['building_condo_info']['subarea']))}}</a></td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('area', $buildingBcnApi['building_condo_info']) && $buildingBcnApi['building_condo_info']['area'])
                                                                                        <tr>
                                                                                                <td>Area:</td>
                                                                                                <td>{{ucwords(strtolower($buildingBcnApi['building_condo_info']['area']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('board_name', $buildingBcnApi['building_condo_info']) && $buildingBcnApi['building_condo_info']['board_name'])
                                                                                        <tr>
                                                                                                <td>Board Name:</td>
                                                                                                <td>{{ucwords(strtolower($buildingBcnApi['building_condo_info']['board_name']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('management_company', $buildingBcnApi['building_condo_info']) && $buildingBcnApi['building_condo_info']['management_company'])
                                                                                        <tr>
                                                                                                <td>Management:</td>
                                                                                                <td>{{ucwords(strtolower($buildingBcnApi['building_condo_info']['management_company']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('management_company_phone', $buildingBcnApi['building_condo_info']) && $buildingBcnApi['building_condo_info']['management_company_phone'])
                                                                                        <tr>
                                                                                                <td>Management Phone:</td>
                                                                                                <td>{{$buildingBcnApi['building_condo_info']['management_company_phone']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('units_in_development', $buildingBcnApi['technical_info']) && $buildingBcnApi['technical_info']['units_in_development'])
                                                                                        <tr>
                                                                                                <td>Units in Development:</td>
                                                                                                <td>{{$buildingBcnApi['technical_info']['units_in_development']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('units_in_strata', $buildingBcnApi['technical_info']) && $buildingBcnApi['technical_info']['units_in_strata'])
                                                                                        <tr>
                                                                                                <td>Units in Strata:</td>
                                                                                                <td>{{$buildingBcnApi['technical_info']['units_in_strata']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('subcategories', $buildingBcnApi['technical_info']) && $buildingBcnApi['technical_info']['subcategories'])
                                                                                        <tr>
                                                                                                <td>Subcategories:</td>
                                                                                                <td>{{ucwords(strtolower($buildingBcnApi['technical_info']['subcategories']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('property_types', $buildingBcnApi['technical_info']) && $buildingBcnApi['technical_info']['property_types'])
                                                                                        <tr>
                                                                                                <td>Property Types:</td>
                                                                                                <td>{{ucwords(strtolower($buildingBcnApi['technical_info']['property_types']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('developer_name', $buildingBcnApi['technical_info']) && $buildingBcnApi['technical_info']['developer_name'])
                                                                                        <tr>
                                                                                                <td>Developer Name:</td>
                                                                                                <td>{{ucwords(strtolower($buildingBcnApi['technical_info']['developer_name']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('architect_email', $buildingBcnApi['technical_info']) && $buildingBcnApi['technical_info']['architect_email'])
                                                                                        <tr>
                                                                                                <td>Architect Email:</td>
                                                                                                <td>{{$buildingBcnApi['technical_info']['architect_email']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('architect_phone', $buildingBcnApi['technical_info']) && $buildingBcnApi['technical_info']['architect_phone'])
                                                                                        <tr>
                                                                                                <td>Architect Phone:</td>
                                                                                                <td>{{$buildingBcnApi['technical_info']['architect_phone']}}</td>
                                                                                        </tr>
                                                                                        @endif

                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                </div>
                                        {{-- </div> --}}
                                        @else
                                        {{-- <div class="row"> --}}
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="listing-detail__technical listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Building Information</h2></div>
                                                                <div class="listing-detail__table">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        <tr>
                                                                                                <td>Building Name:</td>
                                                                                                <td><a href="{{$building_url}}">{{ucwords($building->name)}}</a></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Building Address:</td>
                                                                                                <td><a href="{{$building_url}}">{{$building->street_no}} {{ucwords(strtolower($building->street_name))}} {{ucwords(strtolower($building->street_type))}}, {{ucwords(strtolower($building->city))}}, {{$building->postalcode}}</a></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Subarea:</td>
                                                                                                <td><a href="{{route('city_buildings',['city'=>$listing->cityEnsluged,'subarea'=>$listing->subareaEnsluged])}}">{{ucwords(strtolower($building->subarea))}}</a></td>
                                                                                        </tr>
                                                                                        @if($building->levels && $building->levels > 1)
                                                                                        <tr>
                                                                                                <td>Levels:</td>
                                                                                                <td>{{$building->levels}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->construction)
                                                                                        <tr>
                                                                                                <td>Construction:</td>
                                                                                                <td>{{ucwords(strtolower($building->construction))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->roof)
                                                                                        <tr>
                                                                                                <td>Roof:</td>
                                                                                                <td>{{ucwords(strtolower($building->roof))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->foundation)
                                                                                        <tr>
                                                                                                <td>Foundation:</td>
                                                                                                <td>{{ucwords(strtolower($building->foundation))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->exterior_finish)
                                                                                        <tr>
                                                                                                <td>Exterior Finish:</td>
                                                                                                <td>{{ucwords(strtolower($building->exterior_finish))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->maint_fees_inc)
                                                                                        <tr>
                                                                                                <td>Maintenance Fees Inc. </td>
                                                                                                <td>{{ucwords(strtolower(str_replace(",",", ",$building->maint_fees_inc)))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->title_to_land)
                                                                                        <tr>
                                                                                                <td>Title to Land:</td>
                                                                                                <td>{{ucwords(strtolower($building->title_to_land))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->units_in_development)
                                                                                        <tr>
                                                                                                <td>Units in Development</td>
                                                                                                <td>{{$building->units_in_development}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->strata_no)
                                                                                        <tr>
                                                                                                <td>Strata Plan:</td>
                                                                                                <td>{{$building->strata_no}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->mgmt_name)
                                                                                        <tr>
                                                                                                <td>Management Company:</td>
                                                                                                <td>{{ucwords(strtolower($building->mgmt_name))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                </div>
                                        {{-- </div> --}}
                                        @endif

                                        @if($building_additional_information && array_key_exists('construction', $buildingBcnApi['construction_info']) && $buildingBcnApi['construction_info']['construction'])
                                        {{-- <div class="row"> --}}
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="listing-detail__technical listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Building Construction Info</h2></div>
                                                                <div class="listing-detail__table">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        @if(array_key_exists('year_built', $buildingBcnApi['construction_info']) && $buildingBcnApi['construction_info']['year_built'])
                                                                                        <tr>
                                                                                                <td>Year Built:</td>
                                                                                                <td>{{$buildingBcnApi['construction_info']['year_built']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('levels', $buildingBcnApi['construction_info']) && $buildingBcnApi['construction_info']['levels'])
                                                                                        <tr>
                                                                                                <td>Levels:</td>
                                                                                                <td>{{$buildingBcnApi['construction_info']['levels']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('construction', $buildingBcnApi['construction_info']) && $buildingBcnApi['construction_info']['construction'])
                                                                                        <tr>
                                                                                                <td>Construction:</td>
                                                                                                <td>{{ucwords(strtolower($buildingBcnApi['construction_info']['construction']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('rain_screen', $buildingBcnApi['construction_info']) && $buildingBcnApi['construction_info']['rain_screen'])
                                                                                        <tr>
                                                                                                <td>Rain Screen:</td>
                                                                                                <td>{{ucwords(strtolower($buildingBcnApi['construction_info']['rain_screen']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('roof', $buildingBcnApi['construction_info']) && $buildingBcnApi['construction_info']['roof'])
                                                                                        <tr>
                                                                                                <td>Roof:</td>
                                                                                                <td>{{ucwords(strtolower($buildingBcnApi['construction_info']['roof']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('foundation', $buildingBcnApi['construction_info']) && $buildingBcnApi['construction_info']['foundation'])
                                                                                        <tr>
                                                                                                <td>Foundation:</td>
                                                                                                <td>{{ucwords(strtolower($buildingBcnApi['construction_info']['foundation']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('exterior_finish', $buildingBcnApi['construction_info']) && $buildingBcnApi['construction_info']['exterior_finish'])
                                                                                        <tr>
                                                                                                <td>Exterior Finish:</td>
                                                                                                <td>{{ucwords(strtolower($buildingBcnApi['construction_info']['exterior_finish']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                </div>
                                        {{-- </div> --}}
                                        @endif

                                        @if($building_additional_information && array_key_exists('maintenance', $buildingBcnApi) && count($buildingBcnApi['maintenance']) && array_key_exists('includes', $buildingBcnApi['maintenance']) &&  count($buildingBcnApi['maintenance']['includes']))
                                        {{--  <div class="row">  --}}
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="listing-detail__technical listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Maintenance Fee Includes</h2></div>
                                                                <div class="listing-detail__table">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        @foreach ($buildingBcnApi['maintenance']['includes'] as $includes)
                                                                                        <tr>
                                                                                                <td>{!!ucwords(strtolower($includes))!!}</td>
                                                                                        </tr>
                                                                                        @endforeach
                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                </div>
                                        {{--  </div>  --}}
                                        @endif


                                        @if($building_additional_information && array_key_exists('features', $buildingBcnApi) && count($buildingBcnApi['features']))
                                        {{-- <div class="row"> --}}
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="listing-detail__technical listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Building Features</h2></div>
                                                                <div class="listing-detail__table">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        @foreach ($buildingBcnApi['features'] as $feature)
                                                                                        <tr>
                                                                                                <td>{!!ucwords(strtolower($feature))!!}</td>
                                                                                        </tr>
                                                                                        @endforeach
                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                </div>
                                        {{-- </div> --}}
                                        @endif 


                                        {{--  <!-- Tables for Technical Info, Rooms and Bathrooms -->
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="listing-detail__technical listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Building Information</h2></div>
                                                                <div class="listing-detail__table">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        <tr>
                                                                                                <td>Building Name:</td>
                                                                                                <td>{{$building->name}}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Building Address:</td>
                                                                                                <td>{{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}, {{ucfirst(strtolower($building->city))}}, {{$building->postalcode}}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Subarea:</td>
                                                                                                <td>{{ucfirst(strtolower($building->subarea))}}</td>
                                                                                        </tr>
                                                                                        @if($building->levels && $building->levels > 1)
                                                                                        <tr>
                                                                                                <td>Levels:</td>
                                                                                                <td>{{$building->levels}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->construction)
                                                                                        <tr>
                                                                                                <td>Construction:</td>
                                                                                                <td>{{ucwords(strtolower($building->construction))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->roof)
                                                                                        <tr>
                                                                                                <td>Roof:</td>
                                                                                                <td>{{ucwords(strtolower($building->roof))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->exterior_finish)
                                                                                        <tr>
                                                                                                <td>Exterior Finish:</td>
                                                                                                <td>{{ucwords(strtolower($building->exterior_finish))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->maint_fees_inc)
                                                                                        <tr>
                                                                                                <td>Maintenance Fees Inc. </td>
                                                                                                <td>{{ucwords(strtolower(str_replace(",",", ",$building->maint_fees_inc)))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->title_to_land)
                                                                                        <tr>
                                                                                                <td>Title to Land:</td>
                                                                                                <td>{{ucwords(strtolower($building->title_to_land))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->units_in_development)
                                                                                        <tr>
                                                                                                <td>Units in Development</td>
                                                                                                <td>{{$building->units_in_development}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->strata_no)
                                                                                        <tr>
                                                                                                <td>Strata Plan:</td>
                                                                                                <td>{{$building->strata_no}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->mgmt_name)
                                                                                        <tr>
                                                                                                <td>Management Company:</td>
                                                                                                <td>{{ucwords(strtolower($building->mgmt_name))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                </div>  --}}
                                        @endif

                                        {{-- 
                                        [Contact-form-disabled after-discussion on:07-10-2021]
                                        <div class="col-md-12 col-sm-12">
                                                <div class="listing-detail__contact listing-detail--border">
                                                        <form id="listing-detail--conact-form" class="listing-detail__contactForm listing-detail__askaquestionform" autocomplete="off" method="post" action="">
                                                                <input type="hidden" name="listingid" value="{{$listing->listingid}}">
                                                                <div class="row askQuestion__userDetailsRow" style="display:none" >
                                                                        <div class="col-xs-6">
                                                                                <input type="text" name="firstname" placeholder="First Name" value="{{$firstname}}" class="askQuestion__firstname" @if(Auth::user()) xxreadonly @endif >
                                                                        </div>
                                                                        <div class="col-xs-6">
                                                                                <input type="text" name="lastname" placeholder="Last Name" value="{{$lastname}}" class="askQuestion__lastname" @if(Auth::user()) xxreadonly @endif >
                                                                        </div>
                                                                        <div class="col-xs-6">
                                                                                <input type="text" name="emailaddress" placeholder="Email Address" value="{{$email}}" class="askQuestion__emailaddress" @if(Auth::user()) xxreadonly @endif >
                                                                        </div>
                                                                        <div class="col-xs-6">
                                                                                <input type="text" name="phonenumber" placeholder="Phone number" value="{{$phonenumber}}" class="askQuestion__phonenumber" @if(Auth::user()) xxreadonly @endif >
                                                                        </div>
                                                                </div>
                                                                <div class="row">
                                                                </div>
                                                                <div class="row">           
                                                                        <div class="col-sm-8 col-xs-12">
                                                                                <textarea cols="40" rows="1" name="message" placeholder="Ask a Question" class="askQuestion__message"></textarea> 
                                                                        </div>
                                                                        <div class="col-sm-4 col-xs-12">
                                                                                <button class="listing__send--question" type="submit">Submit</button>
                                                                        </div>
                                                                </div>                
                                                        </form>
                                                </div>
                                        </div>
                                         --}}
                                        @guest
                                        @if($listing->status == 'Sold')
                                        {{-- Internal Link Cluster — guest only, above similar listings --}}
                                        <div class="col-md-12 col-sm-12" style="margin-bottom:24px;">
                                            <div style="border-top:1px solid #eee;padding:14px 0 4px;">
                                                <h3 style="font-size:13px;font-weight:700;color:#777;text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;">Explore more</h3>
                                                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:8px 20px;">
                                                    {{-- "Browse [Type]s for sale in [Neighbourhood]" --}}
                                                    @if($listing->type && $listing->subareaEnsluged && $listing->cityEnsluged)
                                                    <li>
                                                        <a href="{{ route('adv_search_listings', ['city'=>$listing->cityEnsluged, 'subarea'=>$listing->subareaEnsluged, 'type'=>Helper::enslugPlace($listing->type)]) }}" style="color:#337ab7;text-decoration:underline;">
                                                            Browse {{ $listing->type }}s for sale in {{ $listing->subareaProperCased ?: $listing->subarea }}
                                                        </a>
                                                    </li>
                                                    @elseif($listing->type && $listing->cityEnsluged)
                                                    <li>
                                                        <a href="{{ route('adv_search_listings', ['city'=>$listing->cityEnsluged, 'type'=>Helper::enslugPlace($listing->type)]) }}" style="color:#337ab7;text-decoration:underline;">
                                                            Browse {{ $listing->type }}s for sale in {{ $listing->cityProperCased }}
                                                        </a>
                                                    </li>
                                                    @endif
                                                    {{-- "Sold [Type]s in [Neighbourhood]" (city+subarea scope) --}}
                                                    @if($listing->type && $listing->subareaEnsluged && $listing->cityEnsluged)
                                                    <li>
                                                        <a href="{{ route('adv_search_listings', ['city'=>$listing->cityEnsluged, 'subarea'=>$listing->subareaEnsluged, 'type'=>Helper::enslugPlace($listing->type), 'listing_status'=>'sold']) }}" style="color:#337ab7;text-decoration:underline;">
                                                            Sold {{ $listing->type }}s in {{ $listing->subareaProperCased ?: $listing->subarea }}
                                                        </a>
                                                    </li>
                                                    @endif
                                                    {{-- "Sold [Type]s in [City]" (city-wide scope) --}}
                                                    @if($listing->type && $listing->cityEnsluged)
                                                    <li>
                                                        <a href="{{ route('adv_search_listings', ['city'=>$listing->cityEnsluged, 'type'=>Helper::enslugPlace($listing->type), 'listing_status'=>'sold']) }}" style="color:#337ab7;text-decoration:underline;">
                                                            Sold {{ $listing->type }}s in {{ $listing->cityProperCased }}
                                                        </a>
                                                    </li>
                                                    @endif
                                                    {{-- "All sold listings on [Street Name]" --}}
                                                    @if($listing->street_name && $listing->cityEnsluged)
                                                    <li>
                                                        <a href="{{ route('adv_search_listings', ['city'=>$listing->cityEnsluged, 'listing_status'=>'sold', 'street'=>urlencode($listing->street_name)]) }}" style="color:#337ab7;text-decoration:underline;">
                                                            All sold listings on {{ ucwords(strtolower($listing->street_name)) }} {{ ucwords(strtolower($listing->street_type ?? '')) }}
                                                        </a>
                                                    </li>
                                                    @endif
                                                    {{-- "View [City] market stats" --}}
                                                    <li>
                                                        <a href="{{ route('getWeeklyStats') }}" style="color:#337ab7;text-decoration:underline;">
                                                            View {{ $listing->cityProperCased }} market stats
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        @endif
                                        @endguest

                                        @if(count($similar_active))
                                        <div class="col-md-12 col-sm-12 ">
                                                <div class="listing-detail__title">
                                                        <h2><a href="{{route('adv_search_listings',['city'=>$listing->cityEnsluged,'subarea'=>$listing->subareaEnsluged,'type'=>in_array($listing->type,['House','Townhouse','Apartment'])?Helper::enslugPlace($listing->type):null ])}}" style="color: #4a4a4a; text-decoration:underline">Similar {{$listing->type."s"}} {{'For Sale in '}}{{$listing->subarea}}, {{$listing->city}}</a></h2></h2>
                                                        {{-- <h2>Similar @if($subarea_slug)<a href="/{{$subarea_slug}}" style="color: #4a4a4a; text-decoration:underline">@endif{{$listing->type."s"}} {{'For Sale in '}}{{$listing->subarea}}, {{$listing->city}}@if($subarea_slug)</a>@endif</h2> --}}
                                                </div>
                                                <div class="listing-detail__similarProperty-table table-responsive">
                                                        <table class="table" id="">
                                                                <thead>
                                                                        <tr>
                                                                                <th>Date</th>
                                                                                <th>Address</th>
                                                                                <th>Bed</th>
                                                                                <th>Bath</th>
                                                                                <th>Kitchen</th>
                                                                                <th>Asking Price</th>
                                                                                <th>$/Sqft</th>
                                                                                <th title="Days On Market">DOM</th>
                                                                                <th>Levels</th>
                                                                                <th>Built</th>
                                                                                <th>Living Area</th>
                                                                                <th>Lot Size</th>
                                                                        </tr>
                                                                </thead>
                                                                <tbody>
                                                                        @if($listing->status == "Active")
                                                                        <tr>           
                                                                                <td>{{date("m/d/Y", strtotime($listing->list_date))}}</td>  
                                                                                <td><span style="color:#337ab7" >This Property</span> </td>         
                                                                                <td>{{$listing->bedrooms}}</td>
                                                                                <td>{{$listing->bathstotal}}</td>
                                                                                <td>{{$listing->kitchens}}</td>
                                                                                <td>{{$listing->listprice}}</td>
                                                                                @if($listing->livingarea_2 > 0)
                                                                                <td>
                                                                                        @if(Auth::user() && $isUserPremiumMember)
                                                                                        {{Helper::money_format('%.0n', ($listing->livingarea_2>0)?$listing->listprice_2/$listing->livingarea_2:'')}}
                                                                                        @else
                                                                                        <a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}">{{LoginToViewText()}}</a>
                                                                                        @endif
                                                                                </td>
                                                                                @else
                                                                                <td></td>
                                                                                @endif
                                                                                <td align="center">{{$listing->active_days_on_market()}}</td>
                                                                                <td>{{$listing->finished_levels}}</td>
                                                                                <td>{{$listing->yearbuilt}}</td>
                                                                                <td>{{$listing->livingarea}}</td>
                                                                                <td>{{$listing->lotsize>0?number_format($listing->lotsize).' sqft':'N/A'}} </td>
                                                                        </tr>   
                                                                        @endif
                                                                        @foreach ($similar_active as $act_listing)
                                                                        <tr>           
                                                                                <td>{{date("m/d/Y", strtotime($act_listing->list_date))}}</td>  
                                                                                <td><h3><a href="/listing/{{$act_listing->slug}}">{{ucwords(strtolower($act_listing->streetaddress))}}{{-- noCity, {{$act_listing->city}} --}}</a></h3></td>         
                                                                                <td>{{$act_listing->bedrooms}}</td>
                                                                                <td>{{$act_listing->bathstotal}}</td>
                                                                                <td>{{$act_listing->kitchens}}</td>
                                                                                <td>{{$act_listing->listprice}}</td>
                                                                                @if($act_listing->livingarea_2 > 0)
                                                                                <td>
                                                                                        @if(Auth::user() && $isUserPremiumMember)
                                                                                        {{Helper::money_format('%.0n', $act_listing->listprice_2/$act_listing->livingarea_2)}}
                                                                                        @else
                                                                                        <a href="{{LoginToViewLink('/login?redirect='.route('listing-detail-page2',['slug'=>$listing->slug]))}}">{{LoginToViewText()}}</a>
                                                                                        @endif
                                                                                </td>
                                                                                @else
                                                                                <td></td>
                                                                                @endif
                                                                                <td align="center">{{$act_listing->active_days_on_market()}}</td>
                                                                                <td>{{$act_listing->finished_levels}}</td>
                                                                                <td>{{$act_listing->yearbuilt}}</td>
                                                                                <td>{{$act_listing->livingarea}}</td>
                                                                                <td>{{$act_listing->lotsize>0?number_format($act_listing->lotsize).' sqft':'N/A'}} </td>
                                                                        </tr>   
                                                                        @endforeach

                                                                </tbody>
                                                        </table>
                                                        {{--  <div class="col-md-4 col-xl-3 col-xxl-2 col-sm-6 favorite_listing" id="listing-{{$act_listing->listingid}}">
                                                                <div class="listing__item">
                                                                        <div class="listing__item--content">
                                                                                <a href="{{trim(route('listing-detail-page2', ['slug'=>$act_listing->slug]))}}" class="listing__item--link" >
                                                                                        <div class="listing__image lazy" style="background-image: url('@if($act_listing->photos->count() > 0) https://media.pixilinkserver.com/{{str_replace('images','',$act_listing->photos->first()->directory.$act_listing->photos->first()->name)}}?w=900 @else {{asset('assets/img/no-image.jpg')}} @endif')">
                                                                                                <div class="icons">
                                                                                                        <div class="icon__beds clearfix"><i class="fa fa-bed"></i> <span class="number">{{$act_listing->bedrooms}}</span></div>
                                                                                                        <div class="icon__baths clearfix"><i class="fa fa-bath"></i> <span class="number">{{$act_listing->full_baths+$act_listing->half_baths}}</span></div>
                                                                                                        <div class="icon__photos clearfix"><i class="fa fa-camera"></i> <span class="number">{{$act_listing->photos->count()}}</span></div>
                                                                                                </div>
                                                                                        </div>
                                                                                        <div class="listing__content">
                                                                                                <div class="listing__icon pull-left">
                                                                                                        <img class="{{strtolower($act_listing->status)}}" src="{{asset('frontend/icons/'.strtolower($act_listing->getType()).'-selected.svg')}}" />
                                                                                                </div>
                                                                                                <div class="mls_number pull-right">MLS®: {{$act_listing->listingid}}</div>
                                                                                                <div class="listing__status {{strtolower($act_listing->status)}}">{{$act_listing->status}}</div> <!-- can be active or sold - depends on status of listing -->
                                                                                                <div class="listing__price">@if($act_listing->status == 'Sold') @if(Auth::user()) <span style="color:#df4611">{{Helper::money_format('%.0n', $act_listing->soldprice_2)}}</span> @else<a href="/login?redirect={{Request::url()}}" style="color:#df4611">Login to View</a>@endif @else {{$act_listing->listprice}} @endif</div>
                                                                                                <div class="listing__address">
                                                                                                        <span class="big">@if($act_listing->getType() == 'Apartment' && $act_listing->suite_no){{$act_listing->suite_no}} - @endif{{$act_listing->street_number}} {{$act_listing->street_name}} {{$act_listing->street_type}}   </span> <br />
                                                                                                        {{$act_listing->subarea}}, {{$act_listing->city}}, {{$act_listing->province}}
                                                                                                </div>
                                                                                                <div class="listing__amenities" style="min-height: 44px">
                                                                                                        @if($act_listing->status == 'Sold' && $act_listing->getSoldPeriod()) <span class="{{strtolower($act_listing->status)}}">{{$act_listing->getSoldPeriod()}} </span> | @elseif($act_listing->getListingPeriod()) <span class="{{strtolower($act_listing->status)}}">{{$act_listing->getListingPeriod()}} | </span>@endif @if($act_listing->days_on_market())<span class="{{strtolower($act_listing->status)}}">{{$act_listing->days_on_market()}}</span> days on the market |@endif @if($act_listing->livingarea_2 > 0) SqFt: <span class="{{strtolower($act_listing->status)}}">{{$act_listing->livingarea_2}}</span>@endif @if($act_listing->lotsize > 0)| Lot Size: <span class="{{strtolower($act_listing->status)}}">{{$act_listing->lotsize}}</span> SqFt. @endif @if($act_listing->home_style != '')| {{$act_listing->home_style}} @endif @if($act_listing->maintenance && $act_listing->maintenance > 0)| Strata Fees: <span class="{{strtolower($act_listing->status)}}">{{Helper::money_format('%.0n', $act_listing->maintenance)}}</span> @endif @if($act_listing->yearbuilt && $act_listing->yearbuilt > 0)| Year Built: <span class="{{strtolower($act_listing->status)}}">{{ $act_listing->yearbuilt}}</span> @endif
                                                                                                </div>
                                                                                                <div class="listing__listedBy">Listed by: {{$act_listing->reoffice}}</div>
                                                                                                <div class="listing__item--detail-link {{strtolower($act_listing->status)}} visible-sm visible-xs">
                                                                                                        <a href="{{trim(route('listing-detail-page2', ['slug'=>$act_listing->slug]))}}"><p>View Details</p></a>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        </a>
                                                                </div>
                                                        </div>  --}}
                                                        {{--  @endforeach  --}}
                                                </div>
                                        </div>
                                        @endif

{{--
                                        </div>
                                        </div></div></div>
                                        <div class="listing-detail__banner">
                                                <a href="https://calendly.com/d/n2xx-xg68/meeting-with-bc-condos-and-homes-team?month=2021-06" target="_blank">
                                                        <img src="{{asset('frontend/images/listing-banner_new.jpg')}}" style="width: 100%" loading="lazy" />
                                                </a>
                                        </div>

                                        <div class="container">
                                                <div class="listing-detail__item">
                                                        <div class="listing-detail__content">
                                                                <div class="row">
 --}}
                                        @if(count($similar_sold))
                                        <div class="col-md-12 col-sm-12">
                                                {{-- <div class="listing-detail__title"><h2>Recently Sold Properties In {{$listing->subarea}}, {{$listing->city}}</h2></div> --}}
                                                <div class="listing-detail__title">
                                                        <h2><a href="{{route('adv_search_listings',['city'=>$listing->cityEnsluged,'subarea'=>$listing->subareaEnsluged,'type'=>'','listing_status'=>'sold'])}}" style="color: #4a4a4a; text-decoration:underline">Recently Sold @if($listing->getType()=='Apartment'){{'Condos'}}@elseif($listing->getType()=='Other'){{'Properties'}}@else{{$listing->getType().'s'}}@endif In {{$listing->subarea}}, {{$listing->city}}</a></h2>
                                                </div>
                                                <div class="listing-detail__recentSold-table table-responsive">
                                                        <table class="table" id="">
                                                                <thead>
                                                                        <tr>
                                                                                <th>Date</th>
                                                                                <th>Address</th>
                                                                                <th>Bed</th>
                                                                                <th>Bath</th>
                                                                                <th>Kitchen</th>
                                                                                <th>Asking Price</th>
                                                                                <th>Sold Price</th>
                                                                                <th>$/Sqft</th>
                                                                                <th title="Days On Market">DOM</th>
                                                                                <th>Levels</th>
                                                                                <th>Built</th>
                                                                                <th>Living Area</th>
                                                                                <th>Lot Size</th>
                                                                        </tr>
                                                                </thead>
                                                                <tbody>
                                                                @if($listing->status == "Sold")
                                                                        <tr>           
                                                                                <td>{{date("m/d/Y", strtotime($listing->sold_date))}}</td> 
                                                                                <!-- <td>@component('frontend.components.altblur'){{date("m/d/Y", strtotime($listing->sold_date))}}@endcomponent</td>  -->
                                                                                <td><span class="color-status-sold" >This Property</span> </td>
                                                                                <td>@component('frontend.components.altblur'){{$listing->bedrooms}}@endcomponent</td>
                                                                                <td>@component('frontend.components.altblur'){{$listing->bathstotal}}@endcomponent</td>
                                                                                <td>@component('frontend.components.altblur'){{$listing->kitchens}}@endcomponent</td>
                                                                                <td>@component('frontend.components.altlink'){{Helper::money_format('%.0n', $listing->listprice_2)}}@endcomponent</td>
                                                                                <td>
                                                                                        <span class="{{($listing->soldprice_2 >= $listing->listprice_2)?'color-status-sold':''}}">
                                                                                                @component('frontend.components.altlink'){{Helper::money_format('%.0n', $listing->soldprice_2)}}@endcomponent
                                                                                                <span class="profPrc7b82">@component('frontend.components.altblur')(<i class="fa {{$listing->soldprice_2 == $listing->listprice_2 ?'fa-minus':($listing->soldprice_2 > $listing->listprice_2 ?'fa-arrow-up':'fa-arrow-down')}}"></i>{{number_format(($listing->soldprice_2-$listing->listprice_2)*100/$listing->listprice_2,1)}}%)@endcomponent</span> 
                                                                                        </span>
                                                                                </td>
                                                                                <td>@component('frontend.components.altlink'){{Helper::money_format('%.0n', ($listing->livingarea_2>0)?$listing->soldprice_2/$listing->livingarea_2:'')}}@endcomponent</td>
                                                                                <td align="center">
                                                                                        {{-- {{$listing->days_on_market()}}  --}}
                                                                                        @component('frontend.components.altblur')
                                                                                        @if($listing->days_on_market()) {{$listing->days_on_market()}} 
                                                                                        @elseif($listing->getListingPeriod()) Listed {{$listing->getListingPeriod()}} 
                                                                                        @endif
                                                                                        @endcomponent
                                                                                </td>
                                                                                <td>@component('frontend.components.altblur'){{$listing->finished_levels}}@endcomponent</td>
                                                                                <td>@component('frontend.components.altblur'){{$listing->yearbuilt}}@endcomponent</td>
                                                                                <td>@component('frontend.components.altblur'){{$listing->livingarea}}@endcomponent</td>
                                                                                <td>@component('frontend.components.altblur'){{$listing->lotsize>0?number_format($listing->lotsize).' sqft':'N/A'}}@endcomponent </td>
                                                                        </tr>  
                                                                @endif
                                                                @foreach ($similar_sold as $act_listing)
                                                                        @php
                                                                                $profitPrcnt = number_format(($act_listing->soldprice_2 - $act_listing->listprice_2)*100/$act_listing->listprice_2,1);
                                                                        @endphp
                                                                        <tr>           
                                                                                <td>@component('frontend.components.altblur'){{date("m/d/Y", strtotime($act_listing->sold_date))}}@endcomponent</td> 
                                                                                <td><h3><a href="/listing/{{$act_listing->slug}}" class="color-status-sold">{{ucwords(strtolower($act_listing->streetaddress))}}{{-- noCity, {{ucfirst(strtolower($act_listing->city))}} --}}</a></h3></td>
                                                                                <td>@component('frontend.components.altblur'){{$act_listing->bedrooms}}@endcomponent</td>
                                                                                <td>@component('frontend.components.altblur'){{$act_listing->bathstotal}}@endcomponent</td>
                                                                                <td>@component('frontend.components.altblur'){{$act_listing->kitchens}}@endcomponent</td>
                                                                                <td>@component('frontend.components.altlink'){{Helper::money_format('%.0n', $act_listing->listprice_2)}}@endcomponent</td>
                                                                                <td>
                                                                                        <span class="{{$profitPrcnt>=0?'color-status-sold':''}}">
                                                                                                @component('frontend.components.altlink'){{Helper::money_format('%.0n', $act_listing->soldprice_2)}}@endcomponent
                                                                                                @auth<span class="profPrc7b82">(<i class="fa {{$profitPrcnt==0?'fa-minus':($profitPrcnt>0?'fa-arrow-up':'fa-arrow-down')}}"></i>{{$profitPrcnt}}%)</span>@endauth
                                                                                        </span>
                                                                                </td>

                                                                                @if(Auth::user())
                                                                                @if(!empty($act_listing->soldprice_2) && !empty($act_listing->livingarea_2))
                                                                                <td>@component('frontend.components.altblur'){{Helper::money_format('%.0n', $act_listing->soldprice_2/$act_listing->livingarea_2)}}@endcomponent</td>
                                                                                @else
                                                                                <td>&nbsp;</td>
                                                                                @endif

                                                                                @else <td>-</td>
                                                                                @endif
                                                                                <td align="center">
                                                                                        @if($act_listing->days_on_market()) @component('frontend.components.altblur'){{$act_listing->days_on_market()}}@endcomponent @endif
                                                                                </td>
                                                                                <td>@component('frontend.components.altblur'){{$act_listing->finished_levels}}@endcomponent</td>
                                                                                <td>@component('frontend.components.altblur'){{$act_listing->yearbuilt}}@endcomponent</td>
                                                                                <td>@component('frontend.components.altblur'){{$act_listing->livingarea}}@endcomponent</td>
                                                                                <td>@component('frontend.components.altblur'){{$act_listing->lotsize>0?number_format($act_listing->lotsize).' sqft':'N/A'}}@endcomponent </td>
                                                                        </tr>   
                                                                @endforeach
                                                                </tbody>
                                                        </table>
                                                </div>
                                        </div>
                                        @endif

                                        @if(count($samecity_latest_active))
                                        <div class="col-md-12 col-sm-12 ">
                                                <div class="listing-detail__title"><h2><a href="{{route('adv_search_listings',['city'=>$listing->cityEnsluged,'subarea'=>$listing->subareaEnsluged])}}" style="color: #4a4a4a; text-decoration:underline">Just Listed For Sale In {{$listing->subarea}}, {{$listing->city}}</a></h2></div>
                                                <div class="listing-detail__similarProperty-table table-responsive">
                                                        <table class="table" id="">
                                                                <thead>
                                                                        <tr>
                                                                                <th>Date</th>
                                                                                <th>Address</th>
                                                                                <th>Bed</th>
                                                                                <th>Bath</th>
                                                                                <th>Kitchen</th>
                                                                                <th>Asking Price</th>
                                                                                <th>$/Sqft</th>
                                                                                <th title="Days On Market">DOM</th>
                                                                                <th>Levels</th>
                                                                                <th>Built</th>
                                                                                <th>Living Area</th>
                                                                                <th>Lot Size</th>
                                                                        </tr>
                                                                </thead>
                                                                <tbody>
                                                                        @foreach ($samecity_latest_active as $_citylatest)
                                                                        <tr>           
                                                                                <td>{{-- {{date("m/d/Y", strtotime($_citylatest->list_date))}} --}} {{\Carbon\Carbon::parse($_citylatest->inserted)->diffForHumans()}}</td>  
                                                                                <td><h3><a href="/listing/{{$_citylatest->slug}}" onclick="event.stopPropagation();return true;">{{ucwords(strtolower($_citylatest->streetaddress))}}{{-- noCity, {{$_citylatest->city}} --}}</a></h3></td>         
                                                                                <td>{{$_citylatest->bedrooms}}</td>
                                                                                <td>{{$_citylatest->bathstotal}}</td>
                                                                                <td>{{$_citylatest->kitchens}}</td>
                                                                                <td>{{$_citylatest->listprice}}</td>
                                                                                @if($_citylatest->livingarea_2 > 0)
                                                                                <td>
                                                                                        @if(Auth::user())
                                                                                        {{Helper::money_format('%.0n', $_citylatest->listprice_2/$_citylatest->livingarea_2)}}
                                                                                        @else
                                                                                        <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$_citylatest->slug])}}">Login to View</a>
                                                                                        @endif
                                                                                </td>
                                                                                @else
                                                                                <td></td>
                                                                                @endif
                                                                                <td align="center">{{$_citylatest->active_days_on_market()}}</td>
                                                                                <td>{{$_citylatest->finished_levels}}</td>
                                                                                <td>{{$_citylatest->yearbuilt}}</td>
                                                                                <td>{{$_citylatest->livingarea}}</td>
                                                                                <td>{{$_citylatest->lotsize>0?number_format($_citylatest->lotsize).' sqft':'N/A'}} </td>
                                                                        </tr>   
                                                                        @endforeach

                                                                </tbody>
                                                        </table>
                                                </div>
                                        </div>
                                        @endif

                                        @if($_FAQsCombined)
                                        <div class="col-md-12 col-sm-12" id="FAQs">
                                                <div class="listing-detail--border ">
                                                        <div class="listing-detail__title ">
                                                                <h2 class="">{{-- <a href="#FAQs" style="color: #4a4a4a; text-decoration:underline"></a> --}}Frequently Asked Questions About {{$addressAsH1tag}}</h2>
                                                        </div>
                                                        <div class="listing-detail__content">
                                                                <div class="row ">
                                                                        @foreach($_FAQsCombined as $_faqIdx => $_faq)
                                                                        <div class="col-md-6">
                                                                                <div class="panel panel-default ">
                                                                                        <div class="panel-heading">
                                                                                                <h4 class="panel-title">
                                                                                                        <a data-togglex="collapse" hrefx="#faq_clpsPnl_{{$_faqIdx}}" onclick="jQuery(jQuery(jQuery(this).attr('hrefx')).toggle());return false;" style="cursor:pointer;">{{$_faq['q']}}</a>
                                                                                                </h4>
                                                                                        </div>
                                                                                        <div id="faq_clpsPnl_{{$_faqIdx}}" class="panel-collapse collapse">
                                                                                                <div class="panel-body">{{$_faq['ans']}}</div>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endforeach
                                                                </div>
                                                        </div>
                                                </div> 
                                        </div>
                                        @endif


                                        <div class="col-dm-12 col-sm-12">
                                                <div class="listing-detail__calendarly listing-detail--border">
                                                        <div class="row">
                                                                <div class="col-md-6 col-sm-12 col-xs-12">
                                                                        <div class="listing-detail__calendarly--title-button">
                                                                                <!-- Calendly inline widget begin -->
                                                                                {{-- <div class="calendly-inline-widget" data-url="https://calendly.com/varinder/schedule-a-showing" style="min-width:320px;height:630px;"></div> --}}
                                                                                {{-- <script type="text/javascript" src="https://assets.calendly.com/assets/external/widget.js"></script> --}}
                                                                                <!-- Calendly inline widget end -->
                                                                                <!--<h3>List With #1 Realtor® Website in BC</h3>-->
                                                                                {{--  <h3>Up to 100k of interest FREE financing included with every listing</h3>  --}}
                                                                                <!--<div class="listing-detail__calendarly--button">-->
                                                                                <!-- Calendly link widget begin -->
                                                                                <!--<link href="https://assets.calendly.com/assets/external/widget.css" rel="stylesheet">
                                                                                <script src="https://assets.calendly.com/assets/external/widget.js" type="text/javascript"></script>
                                                                                <button type="button" onclick="Calendly.initPopupWidget({url: 'https://calendly.com/bc-condos-and-homes/call'});return false;">Schedule A Call With Les</button>-->
                                                                                <!-- Calendly link widget end -->
                                                                                <!--<button>Schedule A Call With Les</button>-->
                                                                                <!--</div>
                                                                                <div class="listing-detail__calendarly--button">
                                                                                        <button type="button" class="btn btn-primary" onclick="window.open('https://drive.google.com/file/d/1Txbn-x9Zoqy9qso5a6bKdNlbgo5qHog5/view','_blank')">View Sellers Guide</button>
                                                                                </div>-->
                                                                        </div>
                                                                </div>
                                                                <!--<div class="col-md-6 col-sm-12 col-xs-12"><p>Hani & Les | BC Condos And Homes is the go-to website for Buyers and Sellers.  Looking to sell your home and/or purchase your next home, the Hani & Les | BC Condos And Homes sites get more phone, online info requests and showing requests than any other site we know of. List with our Team and you will be impressed. <a href="javascript:;" onclick="Calendly.initPopupWidget({url: 'https://calendly.com/bc-condos-and-homes/call'});return false;" >Click Here</a> to schedule a call with Les Twarog - Re/max Crest Westside, 1428 W 7th Ave, Vancouver, BC V6H 1C1.</p></div>-->
                                                                <div class="col-md-6 col-sm-12 col-xs-12 border__vertical">
                                                                        {{--  <p>When you sell with Hani & Les | BC Condos And Homes, we will lend you up to $100,000 upon a firm deal, interest free for up to 60 days that you can use towards purchasing your next home or any other expense.</p>  --}}
                                                                        {{--  <div class="listing-detail__calendarly--button" style="margin-right: 0px; display: block;">
                                                                                <button type="button" class="btn btn-primary" onclick="window.open('https://www.bccondosandhomes.com/sell.html','_blank')">Learn More</button>
                                                                        </div>  --}}
                                                                </div>
                                                        </div>
                                                </div>
                                        </div>

                                        @if($listing->status == 'Active')
                                        <div id="incformvsmvxs_bookappointment" class="col-xs-12 col-sm-12 visible-sm visible-xs">
                                                {{--[disabled-and-replaced with lising_schedule_tour on: 07-10-2021] @include('frontend.includes.contact_form_sidebar') --}}
                                                @include('frontend.includes.listing_schedule_tour')
                                        </div>
                                        @endif

                                        <div class="col-xs-12 col-sm-12 visible-sm visible-xs">
                                                @include('frontend.includes.team_agents_sidebar')
                                        </div>

                                        <div class="clearfix"></div>
                                        <!-- DISCLAIMER -->
                                        <div class="col-md-12 col-sm-12">
                                                <div class="listing-detail__disclaimer">
                                                        <p><b>Disclaimer:</b> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy. - The advertising on this website is provided on behalf of the Hani & Les | BC Condos And Homes - Re/Max Crest Realty, 300 - 1195 W Broadway, Vancouver, BC</p>
                                                </div>
                                        </div>

                                {{-- Closing-divs[4]: [STARTS] --}}
                                </div>
                        </div>
                </div>
        </div>
        {{-- Closing-divs[4]: .conainter > .listin-detail__item > .listing-detail__content > .row  [ENDS] --}}
        {{-- 
        @if(auth()->user()?->can('dev-dj-approve'))
        <div class="container">
                <div class="listing-detail__item listing-detail--border ">
                        <div class="listing-detail__title container">
                                <h2 class="pixidev-demo-preview">Frequently asked Questions about {{$addressAsH1tag}}</h2>
                        </div>
                        <div class="listing-detail__content">
                                <div class="row ">
                                        @foreach($_FAQsCombined as $_faqIdx => $_faq)
                                        <div class="col-md-6">
                                                <div class="panel panel-default ">
                                                        <div class="panel-heading">
                                                                <h4 class="panel-title">
                                                                        <a data-toggle="collapse" href="#faq_clpsPnl_{{$_faqIdx}}">{{$_faq['q']}}</a>
                                                                </h4>
                                                        </div>
                                                        <div id="faq_clpsPnl_{{$_faqIdx}}" class="panel-collapse collapse">
                                                                <div class="panel-body">{{$_faq['ans']}}</div>
                                                        </div>
                                                </div>
                                        </div>
                                        @endforeach
                                </div>
                        </div>
                </div> 
        </div>
        @endif --}}

        @include('frontend.includes.footer_links')
        @include('frontend.includes.footer')
        {{-- footer-blade included [on:15-09-2022] --}}
        {{-- <footer @if($listing->status == "Active")class="footer__active" @endif>
                <div class="container">
                        <div class="footer__information">
                                <p><a href="/terms-and-conditions" target="_blank">Terms & Conditions</a> &#183; <a href="/privacy-policy" target="_blank">privacy policy</a> </p>
                                <!--<p><span>powered by</span><img src="{{asset('frontend/images/pixilink-logo.svg')}}" alt="Hani & Les | BC Condos And Homes Logo Footer" loading="lazy" alt="Hani & Les | BC Condos And Homes" /></p>-->
                                <p><!--<span>powered by</span>--><img src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.jpg" alt="Hani & Les | BC Condos And Homes Logo Footer" loading="lazy" alt="Hani & Les | BC Condos And Homes" width="250" height="42" style="width: 250px;height: auto; padding: 10px 0;" /></p>
                        </div>
                        <div class="footer__contact-info">
                                <!--<p class="footer__address">Les Twarog<br/>Re/Max Crest Realty</p>-->
                                <p class="footer__address" style="margin:0px;">Re/Max Crest Realty<br/>300 - 1195 W Broadway<br>Vancouver, BC V6H 3X5</p>
                                <div class="footer__contact">
                                        Phone: <a href="tel:6042657975">604-265-7975</a><br>
                                        Email: <a href="mailto:info@bccondosandhomes.com">Info@bccondosandhomes.com</a>
                                </div>
                        </div>
                </div>
        </footer> --}}

{{-- 
<div class="visible-xs">
        <div class="realtor__action__buttons">
                <div class="realtor__action__buttons--wrap">
                        <div class="button__share" id="shareButton" style="display:none;">
                                <a href="javascript:;" class="">
                                        <div onclick="openShareOptions()" class="share_property_button--img">
                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                <div>Share</div>
                                        </div>
                                </a>
                        </div>
                        <div class="button__share" id="shareButtonSmsAndroid" style="display:none;">
                                <a class="" href="sms:?body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                        <div class="share_property_button--img">
                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                <div>Share</div>
                                        </div>
                                </a>
                        </div>
                        <div class="button__share" id="shareButtonSmsiOS" style="display:none;">
                                <a class="" href="sms: &body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                        <div class="share_property_button--img">
                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                <div>Share</div>
                                        </div>
                                </a>
                        </div>
                        @if(Auth::user())
                        <div class="button__favorite">
                                <form id="toggle_favorite" action="" method="get">
                                        <input type="hidden" name="id" id="listingid" value="{{$listing->listingid}}">
                                        <input type="hidden" name="add" id="favorite_value" value="">
                                </form>
                                <div class="toggle__favorite">
                                        <div class="favorite__button">
                                                <a class="btn toggle_favorite_heart" onclick="toggle_favorite()" href="javascript:;" >
                                                        @if($favorite)
                                                                <i class="fa fa-heart color-status-sold" style="" title="Remove from favorite"></i> Favorite
                                                        @else
                                                                <i class="fa fa-heart-o fa-beat color-status-sold" style="" title="Add to favorite"></i> Favorite
                                                        @endif
                                                </a>
                                        </div>
                                </div>
                        </div>
                        @endif

                        @if($listing->status == 'Active')
                        <div class="listing-detail__request-showing" style="">
                                <!-- <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#viewingModal">Book A Viewing</button> -->
                                <a class="btn btn-primary hidden-md" href="#incformvsmvxs_bookappointment" style="padding:8px 16px;margin-left:5px">Schedule A Viewing</a>
                                <a class="btn btn-primary visible-md" href="#incformhsmhxs_bookappointment" style="padding:10px 20px;margin-top:5px">Schedule A Viewing</a>
                        </div>
                        @endif
                </div>
        </div>
</div>
--}}
 

<!-- Modal OfferLand-->
<div class="modal fade" id="offerlandModal" tabindex="-1" role="dialog" aria-labelledby="offerlandModalLabel">
        <div class="modal-dialog" role="document">
                <div class="modal-content">
                        <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                                <h3>What is an OfferValue?</h3>
                                <p>The offerValue is <a href="http://www.offerland.ca" target="_blank">Offerland's estimate</a> of this home's market value. It is not an <u>appraisal</u> and it should be used as a starting point.</p>
                                <p>The OfferValue incorporates numerous conventional and non-conventional data sources to determine the market value of properties using Artificial Intelligence.</p>
                        </div>
                        <div class="modal-footer"></div>
                </div>
        </div>
</div>
@if($user)
@if($wwr_popup)

<div class="modal fade" id="wwrPopupModal" tabindex="-1" role="dialog" aria-labelledby="wwrPopupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                        <div class="modal-header">
                        </div>
                        
                        <div class="modal-body">
                                <div class="row flexbox__row" style="display: flex; flex-wrap: wrap; margin:0">
                                        <div class="col-md-6 col-sm-6 hidden-xs flexbox__col" style="background-image: url(https://www.bccondosandhomes.com/frontend/images/sell/main-banner-01.jpg); background-size:cover">
                                                
                                        </div>
                                        <div class="col-md-6 col-sm-6 col-xs-12 flexbox__col">
                                                <form id="show-photos_form" class="listing-detail__showphotosForm" autocomplete="off" method="post" action="">
                                                        <div class="row">
                                                                <div class="col-md-12">
                                                                        {{-- <h2 class="modal-title">Reached Maximum Number of Property Views</h2> --}}
                                                                        {{-- <p>Continue your access by verifying yourself.</p> --}}
                                                                </div>
                                                        </div>

                                                        <div class="row hide-to-verify" id="whatDescribeYou">
                                                                <div class="col-md-12 col-sm-12 col-xs-12 label--head">What describes you best?</div>
                                                                <div class="col-md-12 col-sm-12 col-xs-12">
                                                                        <select name="client-check" id="client-check-dropdown-wwr" class="form-control" style="height:45px;">
                                                                                <option value="">Choose</option>
                                                                                <option value="Buyer">Buyer</option>
                                                                                <option value="Seller">Seller</option>
                                                                                <option value="Both">Both</option>
                                                                                {{-- <option value="Other">Other</option> --}}
                                                                        </select>
                                                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                                                                <span id="describe-error-wwr" class="help-block error-help-block"></span>
                                                                        </div>
                                                                </div>
                                                        </div>

                                                        <div class="row hide-to-verify" id="workWithRealtor" style="margin-bottom: 15px;">
                                                                <div class="col-md-12 col-sm-12 col-xs-12 label--head">Are you working with a Realtor?</div>
                                                                <div class="col-md-12 col-sm-12 col-xs-12">
                                                                        <select name="realtor-check" id="realtor-check-dropdown-wwr" class="form-control" style="height:45px;">
                                                                                <option value="">Choose</option>
                                                                                <option value="Yes">Yes</option>
                                                                                <option value="No">No</option>
                                                                        </select>
                                                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                                                                <span id="realtor-check-dropdown-error-wwr" class="help-block error-help-block"></span>
                                                                        </div>
                                                                </div>
                                                        </div>
                
                                                        <div class="row show-to-verify" style="" id="wwrSaveSection">
                                                                <div class="col-sm-12 col-xs-12">
                                                                        <button class="listing__show-photos__button" type="button" id="wwr_save">Update</button>
                                                                </div>
                                                        </div>

                                                </form>
                                        </div>
                                </div>
                        </div>
                        <div class="modal-footer"></div>
                </div>
        </div>
</div>

@endif

@endif
<!-- NEW SCHEDULE A VIEWING MODAL -->
<div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog" aria-labelledby="schedulegModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                        <div class="modal-header">
                                <!--<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>-->
                                <h2 class="modal-title">Please confirm your details</h2>
                        </div>
                        
                        <div class="modal-body">
                                <div class="scheduleApp">
                                        <div class="schedule__date"></div>
                                        <div class="schedule__time"></div>
                                </div>
                                <form id="showingReq_form" class="listing-detail__showingReq showingReq_form" autocomplete="off" method="post" action="">
                                        <input type="hidden" name="listingid" value="{{$listing->listingid}}">
                                        <input type="hidden" nameXX="scheduleDate" name="dateone" value="" id="scheduleDate">
                                        <input type="hidden" nameXX="scheduleTime" name="timeone" value="" id="scheduleTime">
                                        <input type="hidden" nameXX="scheduleRealtor" name="agent-check" value="" id="scheduleRealtor">
                                        <input type="hidden" nameXX="schedulePreApprovedMortgage" name="approved-check" value="" id="schedulePreApprovedMortgage">
                                        <div class="row">
                                                <div class="col-xs-12">
                                                        <input type="text" name="firstname" placeholder="Name" value="{{trim($firstname.' '.$lastname)}}" id="name">
                                                </div>
                                                <div class="col-xs-12">
                                                        <input type="text" name="emailaddress" placeholder="Email Address" value="{{$email}}" id="emailaddress">
                                                </div>
                                                <div class="col-xs-12">
                                                        <input type="text" name="phonenumber" placeholder="Phone number" value="{{$phonenumber}}" id="phonenumber">
                                                </div>
                                        </div>

                                        <div class="row">
                                                <div class="col-xs-12">
                                                        <textarea cols="40" rows="3" name="message" id="showingmessage" placeholder="Notes..."></textarea> 
                                                </div>
                                        </div>

                    <div class="lds-ellipsis" id="viewingRequestLoader" style="position:absolute; @if( !empty($user->role) && $user->role == "AGENT") bottom:100px; @else bottom:56px; @endif right:46px;display:none">
                                                <div></div>
                                                <div></div>
                                                <div></div>
                                                <div></div>
                    </div>
                    <button class="listing__schedule--tour--send" id="sendViewingReq" type="submit">Book Viewing</button>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">Close</button>

                                </form>
                        </div>
                        
                        <div class="modal-footer"></div>
                </div>
        </div>
</div>

<!-- Modal "Book a Viewing" -->
<div class="modal fade" id="viewingModal" tabindex="-1" role="dialog" aria-labelledby="viewingModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
                <div class="modal-content">
                        <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h2 class="modal-title">Book A Viewing</h2>
                                <p id="showingmodeltitle">Enter your details below and one of our associates will contact you.</p>
                        </div>
                        
                        <div class="modal-body">
                                <form id="request_showing_form" class="listing-detail__requestingForm" autocomplete="off" method="post" action="{{route('api:request_showing')}}">
                                        <input type="hidden" name="listingid" value="{{$listing->listingid}}">

                                        <div class="row">
                                                <div class="col-xs-6">
                                                        <input type="text" name="firstname" placeholder="First Name" value="{{$firstname}}" id="firstname">
                                                </div>
                                                <div class="col-xs-6">
                                                        <input type="text" name="lastname" placeholder="Last Name" value="{{$lastname}}" id="lastname">
                                                </div>
                                                <div class="col-xs-12">
                                                        <input type="text" name="emailaddress" placeholder="Email Address" value="{{$email}}" id="emailaddress">
                                                </div>
                                                <div class="col-xs-12">
                                                        <input type="text" name="phonenumber" placeholder="Phone number" value="{{$phonenumber}}" id="phonenumber">
                                                </div>
                                        </div>
                                        
                                        <div class="row">
                                                <div class="col-xs-12">
                                                        <label>Language Preference</label>
                                                        <select name="language" class="form-control" id="language">
                                                                <option value="any">Any</option>
                                                                <option value="English">English</option>
                                                                <option value="Punjabi">Punjabi</option>
                                                                <option value="Cantonese">Cantonese</option>
                                                                <option value="Mandarin">Mandarin</option>
                                                                <option value="Hindi">Hindi</option>
                                                                <option value="Bengali">Bengali</option>
                                                                <option value="Urdu">Urdu</option>
                                                                <option value="Polish">Polish</option>
                                                                <option value="German">German</option>
                                                        </select>
                                                </div>
                                        </div>

                                        <div class="listing-detail__requestingForm--agent listing-detail__requestingForm--agent-first">
                                                <div class="row">
                                                        <div class="col-md-5 col-sm-5 col-xs-12 label--head">Are you working with an agent?</div>
                                                        <div class="col-md-2 col-sm-2 col-xs-2">
                                                                <label>Yes</label>
                                                                <input type="radio" name="agent-check" value="Yes" id="agentcheck1">
                                                        </div>
                                                        <div class="col-md-2 col-sm-2 col-xs-2">
                                                                <label>No</label>
                                                                <input type="radio" name="agent-check" value="No" id="agentcheck2">
                                                        </div>
                                                </div>
                                        </div>

                                        <div class="listing-detail__requestingForm--agent">
                                                <div class="row">
                                                        <div class="col-md-5 col-sm-5 col-xs-12 label--head">Are you pre-approved for mortgage?</div>
                                                        <div class="col-md-2 col-sm-2 col-xs-2">
                                                                <label>Yes</label>
                                                                <input type="radio" name="approved-check" value="Yes" id="approved-check1">
                                                        </div>
                                                        <div class="col-md-2 col-sm-2 col-xs-2">
                                                                <label>No</label>
                                                                <input type="radio" name="approved-check" value="No" id="approved-check2">
                                                        </div>
                                                </div>
                                        </div>

                                        <div class="listing-detail__requestingForm--date">
                                                <div class="label--head">When would you like to see the place</div>
                                                <div class="row">
                                                        <div class="col-xs-12">
                                                                <label class="date-label">Preference 1:</label>
                                                        </div>
                                                        <div class="col-sm-6 col-xs-12">
                                                                <input type="text" name="dateone" placeholder="Date: yyyy-mm-dd" id="dateone" >
                                                        </div>
                                                        <div class="col-sm-6 col-xs-12">
                                                                <input type="text" name="timeone" placeholder="Time: 5:00 PM" id="timeone">
                                                        </div>
                                                        <div class="col-xs-12">
                                                                <label class="date-label">Preference 2:</label>
                                                        </div>
                                                        <div class="col-sm-6 col-xs-12">
                                                                <input type="text" name="datetwo" placeholder="Date: yyyy-mm-dd" id="datetwo">
                                                        </div>
                                                        <div class="col-sm-6 col-xs-12">
                                                                <input type="text" name="timetwo" placeholder="Time: 5:00 PM" id="timetwo">
                                                        </div>
                                                </div>
                                        </div>

                                        <div class="row">
                                                <div class="col-xs-12">
                                                        <textarea cols="40" rows="3" name="message" id="showingmessage" placeholder="Any Notes..."></textarea> 
                                                </div>
                                        </div>

                                        <button class="listing__request-showing__button" type="submit" id="showingsubmit">Make A Booking</button>

                                </form>
                        </div>
                        
                        <div class="modal-footer"></div>
                </div>
        </div>
</div>

<!-- Button trigger modal -->

<!-- Modal "Ask a Question"-->
<div class="modal fade" id="questionModal" tabindex="-1" role="dialog" aria-labelledby="questionModalLabel">
        <div class="modal-dialog" role="document">
                <div class="modal-content">
                        <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                                <div class="listing-detail__question clearfix" style="margin-bottom:15px;">
                                        <div class="row">
                                                {{--  <div class="col-sm-12 col-xs-12">
                                                        <h3>I have a question</h3>
                                                        <form id="ask_question_form" class="listing-detail__showing" autocomplete="off" method="post" action="">
                                                                <textarea class="form-control" name="question" id="ask__question" placeholder="Notes..."></textarea>
                                                                <div class="alert alert-danger fade in alert-dismissible" style="padding: 5px 15px 5px 15px; margin-top:10px; margin-bottom:0; display:none" id="send_question_error">
                                                                        Question is requied.
                                                                </div>
                                                                <input type="hidden" name="listingid" value="{{$listing->listingid}}">
                                                                <button class="listing__ask-question__button" type="submit" style="margin-top:15px">Send</button>
                                                        </form>
                                                        <div class="alert alert-info fade in alert-dismissible" style="display:none" id="askquestion_success">
                                                                {{--  <a href="#" class="close" aria-label="close" id="close_askquestion_success" title="close">×</a>  --}}
                                                                {{--  Your question has been sent.
                                                        </div>
                                                </div>  --}}  
                                        </div>
                                </div>
                        </div>
                        <div class="modal-footer"></div>
                </div>
        </div>
</div>
@push('after-styles')
<style type="text/css">
{{-- for SEO (CLS,LCP etc..) [STARTS] --}}
.bc-neighbourhood-stats{display:flex;gap:20px;flex-wrap:wrap;margin-bottom:18px;padding:8px 0;}
.bc-ns-stat{font-size:13px;color:#555;}
.bc-ns-stat strong{color:#1a1a1a;}
.bc-seo-desc-text{font-size:15px;color:#444;line-height:1.65;margin:0;}
.bc-sidebar-viewed{font-size:11px;color:#2eaa6e;font-weight:600;margin-bottom:14px;display:flex;align-items:center;gap:5px;}
.bc-sidebar-viewed-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#2eaa6e;flex-shrink:0;}
header.site__header{padding: 10px;width: 100vw ;border-bottom: 1px solid #e4e4e4;}
.ListingDetailPage .main{padding-top: 64px !important;}
.breadcrumb>li {display: inline-block;}.pull-left{float: left}.pull-right{float: right !important;}
#mobile-menu{font-size: 21px; padding: 8px;}.btn-group.dropdown__menu{display: inline-block;}
.listing-detail-page__address h1, .featured__property--item h2, .listing-detail-page__address h1 { font-size: 40px; font-weight: 600!important; line-height: 1em;}
.listing-detail-page__address h1, .listing-detail-page__address h2 {line-height: 1.2em;}

.drawer--right .drawer-nav {position: fixed; width: 256px;right: -256px !important;}
.listing-detail__table .table thead tr th, .listing-detail__table .table tbody tr td {padding: 5px 8px;}
.table>tbody>tr>td {border-top: 0;padding: 5px 8px;font-size: 14px;/* white-space: nowrap;*/}
.listing-detail--border, .building-detail--border {border-top: 1px solid #9b9b9b; padding: 15px 0 30px;}


.listing-detail__info .text-right .toggle__share, .listing-detail__info .text-right .toggle__favorite {display: inline-block;}
.share__button, .toggle__favorite .toggle_favorite_heart { border: 1px solid #d9d9d9; color: #454545; background: #f5f5f5;  border-radius: 10px; font-size: /*12px*/;line-height: 12px; padding: 10px 15px; }
.listing-detail__info .toggle__share img {margin-right: 0;width: 16px; height: 16px; min-width:16px;  }
@media (max-width: 767px){
/*body{line-height: 1.42857143;}*/
#spliderStarterDive2810kjs{min-height: 250px;}
.listing-detail__offerland { display: flex; min-height:55px }
.breadcrumb.small{line-height: 22px;margin-top: 0}
.listing-detail__address, .listing-detail__address h1{font-size: 30px; line-height: 30px; }
.listing-detail-page__address h2{ font-size: 20px; line-height: 20px; margin: 10px 0; font-weight: 400; }
.listing-detail__table.table-responsive,.table>thead>tr>th {border: none;border-bottom: none;}
.splide__arrow { align-items: center; background: #ccc; border: 0; border-radius: 50%; cursor: pointer; display: flex; height: 2em;justify-content: center; opacity: .7; padding: 0;position: absolute;top: 50%;transform: translateY(-50%);width: 2em;}
}
{{-- for SEO (CLS,LCP etc..) [ENDS] --}}

.breadcrumb{background-color: transparent; font-size: 1.5rem; padding: 8px 0px; white-space: nowrap; overflow: auto; {{-- [(font-size-for-mobile) fixed: ;26-July] , [padding+... -fix: 27-09-2021] --}} }
.breadcrumb,.breadcrumb a{color: #848484;}
.breadcrumb>li+li:before {content: "❯\00a0";}
.listing-detail__recentSold-table a{color:#df3011;/*color:#ee4223;*/}
#sold_table a,.table-sold a,.color-status-sold{color:#df3011;/*color:#EE4223;*/}
.lazyframe { margin-bottom: 100px;}
.realtor__action__buttons{z-index: 10;}
.listing-detail__image--iframe{background-color:#444;}
{{-- [added: 01-06-2022] [BEGINS] --}}
.fvrlst_tracked{position: absolute;top: 40px !important;right: 15px; font-size: 25px;}
.listing-tracked{border: 2px dashed #337ab7;}
.listing-is-tracked,.bcch-blue{color: #337ab7;}
.listing-not-tracked,.bcch-red{color: #df4611;}
{{-- [added: 01-06-2022] [ENDS] --}}

.pixidev-demo-preview{background: #ffff001a;}
.pixidev-demo-preview:hover:before {content: 'Currently-demo-view';background: #f001;position: sticky;top: 10%;padding:0.5em;opacity:0.3; }
z-index: -1;}
</style>
{{-- [widgetbe added:23-03-2022, disabled:29-03-2022] --}}
<!-- begin Widget Tracker Code -->
{{-- <script>
(function(w,i,d,g,e,t){w["WidgetTrackerObject"]=g;(w[g]=w[g]||function()
{(w[g].q=w[g].q||[]).push(arguments);}),(w[g].ds=1*new Date());(e="script"),
(t=d.createElement(e)),(e=d.getElementsByTagName(e)[0]);t.async=1;t.src=i;
e.parentNode.insertBefore(t,e);})
(window,"https://widgetbe.com/agent",document,"widgetTracker");
window.widgetTracker("create", "WT-PQAQSPHY");
window.widgetTracker("send", "pageview");
</script> --}}
<!-- end Widget Tracker Code -->
@endpush
{{-- 
**REPLACED with : login_modal_n_scripts-view [22-10-2021]
@if(!Auth::user())
<style>
.p p{font-size: 38px;font-weight: 700;margin: 0px !important;line-height: 1.3;}
.right_div {text-align: center;background-color: #fff;padding: 50px;width: 100%;}
</style>
<div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="padding-top:8%;padding-top:calc(50vh - 270px);">
                <div class="modal-content" style="background-image: url('https://www.bccondosandhomes.com/assets/img/BCCondos_ligin.png'); background-position:center; background-size: cover; width:100%">
                        <div class="modal-body" style="background-color: #ffffff8c">
                                <div class="container-fluid" >
                                        <div class="row" style="padding:30px">
                                                <div class="col-md-6 ml-auto">
                                                        <div class="p">
                                                        <p>Sign In &amp; Get </p>
                                                        <p>Unlimited Access to</p>
                                                        <p> Building</p>
                                                        <p> Information, </p>
                                                        <p>Listings, Rentals</p>
                                                        <p>and Sold History</p>
                                                        </div>
                                                        <h3>&nbsp;</h3>
                                                        <h3 style="line-height: 0.6">Hani & Les | BC Condos And Homes By</h3>
                                                        <h3 style="line-height: 0.6"> Re/Max Crest Realty</h3>
                                                </div>
                                                <div class="col-md-6 ml-auto">
                                                        <div class="right_div">
                                                                <div id="firebaseui-auth-container"></div>
                                                                <div id="loader">Loading...</div>
                                                        </div>
                                                </div>
                                        </div>
                                </div>
                          
                        </div>
                </div>
        </div>
</div>
@endif
<link type="text/css" rel="stylesheet" href="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.css" /> 
--}}
@include('frontend.includes.login_modal_n_scripts')
<style>
/*, .virtual_tour_links .virtual_tour_button_text*/
.share_property_button { font-size: 16px; color: #fff; background-color: #df4611 !important; border: 0 !important; width: 100%; padding: 10px 0 !important; margin-top: 20px; border-radius: 4px; } 
.resp-container { position: relative; overflow: hidden; padding-top: 56.25%; }
.resp-iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
.help-block.error-help-block{ color: red; }
</style>
{{--  --}}
<script type="application/ld+json">
{"@context": "https://schema.org", "@graph": [
{
        "@type": @if($listing->type == 'House')"House"@else "Apartment" @endif,
        "@id": "https://www.bccondosandhomes.com/listing/{{$listing->slug}}#property",
        "name": "{{$listing->streetaddress}}, {{$listing->city}}, {{$listing->province}} {{$listing->postalcode}}",
        "url": "https://www.bccondosandhomes.com/listing/{{$listing->slug}}",
        "description":@if($listing->remarks) @php echo json_encode(str_replace(["\r\n","\r","\n"], ' ', remove_openhouse($listing->remarks))); @endphp,
        @else "Sold property at {{$listing->streetaddress}}, {{$listing->city}}, {{$listing->province}}",
        @endif
        "address": {
          "@type": "PostalAddress",
          "streetAddress": "{{$listing->streetaddress}}",
          "addressLocality": "{{$listing->city}}",
          "addressRegion": "{{$listing->province}}",
          "postalCode": "{{$listing->postalcode}}",
          "addressCountry": "Canada"
        },
        @if($listing->lat && $listing->lng)
        "geo": {
          "@type": "GeoCoordinates",
          "latitude": {{$listing->lat}},
          "longitude": {{$listing->lng}}
        },
        @endif
        @if(!empty($buildingBcnApi['restrictions']['pets']['no_pets']))
        "petsAllowed": "{{ucwords(strtolower($buildingBcnApi['restrictions']['pets']['no_pets']))}}",
        @endif
        @if($listing->bedrooms) "numberOfBedrooms": {{$listing->bedrooms}}, @endif
        @if($listing->bathstotal) "numberOfBathroomsTotal": {{$listing->bathstotal}}, @endif
        @if($listing->yearbuilt) "yearBuilt": {{$listing->yearbuilt}}, @endif
        @if($listing->mlsr_listing && $listing->mlsr_listing->livingarea_2) "floorSize": {"@type": "QuantitativeValue","value": {{$listing->mlsr_listing->livingarea_2}},"unitCode": "FTK"}, @endif
        @if($listing->subarea && $listing->city) "areaServed": {"@id": "https://www.bccondosandhomes.com/#neighbourhood-{{\Illuminate\Support\Str::slug($listing->subarea.'-'.$listing->city)}}"}, @endif
        "dateModified":"{{date('Y-m-d', strtotime($listing->last_modified))}}",
        "photo": {"@type": "ImageObject", "url": "{{$listing->mainpicurl?:'https://www.bccondosandhomes.com/assets/img/no-image-800-600.png'}}"},
        "image": ["{{$listing->mainpicurl?:'https://www.bccondosandhomes.com/assets/img/no-image-800-600.png'}}"]
}
,{
        "@type": "Offer",
        "price": {{$listing->listprice_2}},
        "priceCurrency": "CAD",
        "url": "https://www.bccondosandhomes.com/listing/{{$listing->slug}}",
        "availability": "https://schema.org/SoldOut",
        "itemOffered": {"@id": "https://www.bccondosandhomes.com/listing/{{$listing->slug}}#property"}
}
,{
        "@type": "WebSite",
        "name": "Hani & Les | BC Condos And Homes",
        "alternateName": "Hani & Les | BC Condos And Homes",
        "url": "https://www.bccondosandhomes.com"
}
,{
        "@type": "WebPage",
        "@id": "https://www.bccondosandhomes.com/listing/{{$listing->slug}}",
        "datePublished":"{{date('Y-m-d', strtotime($listing->list_date))}}",
        "dateModified":"{{date('Y-m-d', strtotime($listing->last_modified))}}",
        "isPartOf": {"@type": "WebSite", "url": "https://www.bccondosandhomes.com"},
        "mainEntity": {"@id": "https://www.bccondosandhomes.com/listing/{{$listing->slug}}#property"}
}
,{
        "@type": "RealEstateListing",
        "datePosted":"{{date('Y-m-d', strtotime($listing->list_date))}}",
        "dateModified":"{{date('Y-m-d', strtotime($listing->last_modified))}}",
        "url": "https://www.bccondosandhomes.com/listing/{{$listing->slug}}",
        "about": {"@id": "https://www.bccondosandhomes.com/listing/{{$listing->slug}}#property"}
}
@if($listing->getFAQs())
,{
        "@type": "FAQPage",
        "mainEntity": [@foreach($listing->getFAQs() as $_faqIdx=>$_faq)
        @if($_faqIdx==0) { @else ,{ @endif "@type": "Question", "name": "{{$_faq['q']}}", "acceptedAnswer": {"@type": "Answer", "text": "{{$_faq['ans']}}"}} @endforeach
        ]
}
@endif
@if(!empty($jsonldSchema['BreadcrumbList']))
@foreach($jsonldSchema['BreadcrumbList'] as $_jsonldSchema)
,{
        "@type": "BreadcrumbList",
        "itemListElement": [{ "@type":"ListItem", "position":1, "name":"Home", "item":"{{url('/')}}"
        }@foreach($_jsonldSchema as $_jsonldSchemaIdx => $_jsonldSchemaVal)
        ,{ "@type":"ListItem", "position":{{($_jsonldSchemaIdx+2)}}, "name":"{{$_jsonldSchemaVal['text']}}", "item":"{{$_jsonldSchemaVal['url']}}"
        }@endforeach
        ]
}
@endforeach
@endif
]}
</script>
@endsection
@push('after-scripts')
{{-- ══ Sticky bottom bar — sold mode (guest) ══ --}}
<script src="https://admin.bccondosandhomes.com/widget/sticky-bar.js"
  data-placement="main"
  data-mode="sold"
  data-address="{{ ($addressAsH1tag ?? '') . ($listing->cityProperCased ? ', ' . $listing->cityProperCased : '') }}"
  data-neighbourhood="{{ $listing->subareaProperCased ?? $listing->cityProperCased ?? '' }}"></script>

<script  type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/1.2.1/jquery-migrate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js" integrity="sha512-uURl+ZXMBrF4AwGaWmEetzrd+J5/8NRkWAvJx5sbPSSuOb0bZLqf+tOzniObO00BjHa/dD7gub9oCGMLPQHtQA==" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.5.0/js/swiper.min.js"></script>
{{--  <script  type="text/javascript" src="{{asset('frontend/plugins/slick/slick.min.js')}}"></script>  --}}
<script src="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>

<script  type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.27.0/moment.min.js"></script>
<script  type="text/javascript" src="{{asset('frontend/js/bootstrap-datetimepicker.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@latest/dist/js/splide.min.js"></script>

{{-- 
**REPLACED with : login_modal_n_scripts-view [22-10-2021]
@if(!Auth::user())
<script  src="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.js"></script>
<script  src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script  src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>
<script  src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script  src="https://www.gstatic.com/firebasejs/8.10.1/firebase-firestore.js"></script>

<script>
        // Initialize Firebase
        var config = {
                apiKey: "AIzaSyBpd0W87PGBcJHSmZMfIbUAJrAbjfG64jk",
                authDomain: "bccondos-c41f4.firebaseapp.com",
                databaseURL: "https://bccondos-c41f4.firebaseio.com",
                projectId: "bccondos-c41f4",
                storageBucket: "bccondos-c41f4.appspot.com",
                messagingSenderId: "329329041534",
                appId: "1:329329041534:web:c63a4eba288fe525f5b82f",
                measurementId: "G-EY5YB8F197"
        };
        if (!firebase.apps.length) { firebase.initializeApp(config); }
        var ui = new firebaseui.auth.AuthUI(firebase.auth());
        var uid = null;
        var uiConfig = {
                callbacks: {
                        signInSuccessWithAuthResult: function(authResult, redirectUrl) {
                                jQuery(".box-login--signup h3").html("Logging In<span class='loader__dot'>.</span><span class='loader__dot'>.</span><span class='loader__dot'>.</span>");
                                firebase.auth().currentUser.getIdToken(/* forceRefresh */ true).then(function(idToken) {
                                        console.log(idToken);
                                        document.location = 'https://www.bccondosandhomes.com/handle_auth'+"?token="+idToken+"&f=&redirect="+document.location;
                                }).catch(function(error) {
                                        // Handle error
                                });
                                return false;
                        },
                        uiShown: function() {
                                document.getElementById('loader').style.display = 'none';
                        }
                },
                signInFlow: 'redirect',
                signInSuccessUrl: 'https://www.bccondosandhomes.com/handle_auth',
                credentialHelper: firebaseui.auth.CredentialHelper.NONE,
                signInOptions: [
                        firebase.auth.GoogleAuthProvider.PROVIDER_ID,
                        firebase.auth.EmailAuthProvider.PROVIDER_ID,
                        firebase.auth.FacebookAuthProvider.PROVIDER_ID
                ],
                // Terms of service url.
                tosUrl: '/terms-and-conditions',
                // Privacy policy url.
                privacyPolicyUrl: '/privacy-policy'
        };


        ui.start('#firebaseui-auth-container', uiConfig);
</script>
@endif
 --}}
@guest
<script type="text/javascript">
{{--
@if(!$is_featured)
$(document).ready(function(){
        setTimeout(function() { 
                        // $("#loginModal").modal({backdrop: 'static', keyboard: false});
                        // $("#loginModal").modal('show'); // [disabled on 12-Apr-21 ]
                }, 30000);
});

@endif

@if($listing->status=='Sold')
// [disabled on 12-Apr-21 ]
/*jQuery(document).ready(function(){
        setTimeout(function() { 
                jQuery("#loginModal").modal({backdrop: 'static', keyboard: false});
                jQuery("#loginModal").modal('show');
        }, 4000);
});*/
@endif
--}}

@push('document-ready-javascript') 
try{
        jQuery(document).on('click','a[href^="/login"]',function(event){ 
                event.preventDefault();
                jQuery("#loginModal").modal({backdrop: 'static', keyboard: false});
                jQuery('#loginModal').modal('show');
                return false; 
        });

        jQuery.event.special.touchstart = {
                setup: function( _, ns, handle ) {this.addEventListener("touchstart", handle, { passive: !ns.includes("noPreventDefault") });} {{-- to suppress-passive-event message --}}
        };      
}catch(exPtDcRdy){}
@endpush

{{-- redirected-from-(bcn/bcch) (or reached-from-user-click) [added-on: 09-09-2021, updated:07-10-2021] --}}
{{-- [Disabled: 2-2-2022, on demand] 
@if( strpos(request()->headers->get('referer'), 'bccondos.net') || strpos(request()->headers->get('referer'), 'bccondosandhomes.com') ) 
@push('document-ready-javascript') 
try{

        jQuery("#loginModal").modal({backdrop: 'static', keyboard: false});
        jQuery('#loginModal').modal('show');
}catch(exPtDcRdy){}
@endpush
@endif          
--}}
</script>
@endguest

{{-- <script>
        $(document).ready(function(){
 --}}
 @if($building_url)
 @push('document-ready-javascript') 
        try{

                var building_popover_count = 1;
                try{favorite_popover_count;}catch(exPt){favorite_popover_count=0;}
                if(localStorage.getItem("building_popover_count")){
                        building_popover_count = Number(localStorage.getItem("building_popover_count"))+1;
                }
                if(building_popover_count <= 5){
                        localStorage.setItem("building_popover_count", favorite_popover_count);
                        $(".listing-detail__building--link a").popover('show'); 
                        setTimeout(function(){
                                $(".listing-detail__building--link a").popover('destroy');
                        },30000);
                }
        }catch(exPtDcRdy){}
@endpush
@endif

{{--    });
</script> --}}
@if(Auth::user() && !$favorite && $listing->status == 'Active')
<script>
        var favorite_popover_count = 1;
        if(localStorage.getItem("favorite_popover_count")){
                favorite_popover_count = Number(localStorage.getItem("favorite_popover_count"))+1;
        } 
        if(favorite_popover_count <= 5){
                localStorage.setItem("favorite_popover_count", favorite_popover_count);
                $(".toggle_favorite_heart").popover('show'); 
                setTimeout(function(){
                        $(".toggle_favorite_heart").popover('destroy');
                },10000);
        }
</script>
@endif
<script>
{{-- @if(Auth::user() && $favorite)
var favorite = true;
@else
var favorite = false;
@endif --}}
var favorite = {{(Auth::user() && $favorite)?'true':'false'}};
var favoriteReqAction_wait = 0;
@if(Auth::user())
function toggle_updateFavoriteIcons(){
        favoriteReqAction_wait = 0;
        favorite = !favorite;
        var fvtHrtIcon = jQuery(".toggle_favorite_heart i.fa");
        if(favorite){
                // fvtHrtIcon.removeClass('fa-beat');
                fvtHrtIcon.attr('title', 'Remove from favorite').removeClass('fa-heart-o').addClass('fa-heart');
                // jQuery('.btn-track').show();
        }else{
                //fvtHrtIcon.addClass('fa-beat');
                fvtHrtIcon.attr('title', 'Add to favorite').removeClass('fa-heart').addClass('fa-heart-o');
                jQuery('.btn-track,.btn-track i').addClass('listing-not-tracked').removeClass('listing-is-tracked');
        }
        return favorite;
}

function toggle_favorite(){
        jQuery("#favorite_value").val( (favorite)?'false': 'true');
        if(favoriteReqAction_wait == 0){
                favoriteReqAction_wait = 1;
                jQuery.ajax({
                        method: 'post',
                        url: 'https://www.bccondosandhomes.com/api/savefavourite',
                        data: jQuery("#toggle_favorite").serialize(),
                        beforeSend: function(request) {
                                request.setRequestHeader("authorization", 'Basic {{$user->uid}}');
                        },
                }).done(function(response){
                        toggle_updateFavoriteIcons();
                });
        }
        
}

{{-- @if($userIsPixiMember) --}}
function toggle_tracking(){
        var listingid = jQuery("#listingid").val();
        var trackthis = jQuery('i.i-fvt-track-stop').hasClass('listing-not-tracked');
        jQuery.ajax({
                method: 'post',
                url: 'https://www.bccondosandhomes.com/api/savefavourite',
                data:{'id':listingid,'add':'true','track':trackthis},
                beforeSend: function(request) {request.setRequestHeader("authorization", 'Basic {{$user->uid}}'); },
        }).done(function(response){
                jQuery('i.i-fvt-track-stop').toggleClass('listing-is-tracked listing-not-tracked');
                try{
                        if(response.tracking_status==true){
                                if(favorite==false) {toggle_updateFavoriteIcons();}
                        }
                }catch(expTn){}
        });
}
{{-- @endif --}}

@endif
try{jQuery('[data-toggle=tooltip]').tooltip(); jQuery('[rel=popover]').popover();}catch(expTn){} {{-- [added:08-08-2022] --}}
</script>
<script type="text/javascript">
// $(document).ready(function(){
@push('document-ready-javascript') 
        try{

                $('.building-detail__images').slick({
                        infinite: true, // dots: true, {{-- prevArrow: false, nextArrow: false, [disabled:8-02-2022 on-demand to show arrows] --}}
                });

                $('.listing-detail__images').slick({infinite: true, speed: 500, fade: true, cssEase: 'linear', });
                $('#listing_images').show();
                $("#building-photos").show();
                /* Hide and show header on scolling */
                var didScroll;
                var lastScrollTop = 0;
                var delta = 5;
                var navbarHeight = $('header').outerHeight();
                var stickyTop = navbarHeight+20;
                //var viewingTop = navbarHeight;

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
                                $('.floating__box').css('top', '20px');
                                //$('.listing__viewing--header').css('top', '0px');
                        } else {
                                // Scroll Up
                                if (st + $(window).height() < $(document).height()) {
                                        $('header').removeClass('nav-up').addClass('nav-down').css('top', '0');
                                        $('.floating__box').css('top', +stickyTop);
                                        //$('.listing__viewing--header').css('top', +viewingTop);
                                }
                        }
                        lastScrollTop = st;
                }

                var $calendar = $('.listing__schedule--tour--calendar');
                var $timeOfDay = $('.listing__schedule--tour--time');
                $calendar.click(function () {
                        $calendar.removeClass('selected');
                        $(this).addClass('selected');
                        $('.listing__schedule--tour--time-wrap').show();
                });

                $timeOfDay.click(function(){
                        $timeOfDay.removeClass('selected');
                        $(this).addClass('selected');
                        $('.listing__schedule--tour--text').show();
                });

                if ($('.showing__checkbox--day .showing-day__checked').prop('checked')) {
                        $('.listing__schedule--tour--time-wrap').show();
                }

                var checkboxDay = $('.showing__checkbox--day .showing-day__checked');
                $('.showing__checkbox--day .showing-day__checked').on('click',function () {
                        if (checkboxDay.is(':checked')) {
                                $('.listing__schedule--tour--time-wrap').show();
                                jQuery("#send_showing_error").hide();
                        } else {
                                $('.listing__schedule--tour--time-wrap').hide();
                        }
                });

                var checkboxTime = $('.showing__checkbox--time .showing-time__checked');
                $('.showing__checkbox--time .showing-time__checked').on('click',function () {
                        if (checkboxTime.is(':checked')) {
                                $('.listing__schedule--tour--text').show();
                                jQuery("#send_showing_error").hide();
                        } else {
                                $('.listing__schedule--tour--text').hide();
                        }
                });
        }catch(exPtDcRdy){}
@endpush                
        // });

        @if(Browser::isMobile())
        function initSplide_fxn2810(){
                new Splide('.splide', {
                        type: 'fade',
                        speed: '500',
                        easing: 'linear',
                        rewind: true,
                        pagination: false,
                        start: 1, {{-- because [0] is already shown, has click-event to initiate-this --}}
                }).mount();
                // try{setTimeout(startIntercom, 0);}catch(expTn){} //{{-- startIntercom(); --}}
        }
        // initSplide_fxn2810();
        @else
        @push('document-ready-javascript')
        try{
                new Splide('.splide', {
                        type: 'fade',
                        speed: '500',
                        easing: 'linear',
                        rewind: true,
                        pagination: false
                }).mount();
        }catch(exPtSplide){}
        jQuery('#spliderWrapperDiv2810hnbjd').show();jQuery('#spliderStarterDive2810kjs').remove();
        @endpush
        @endif


        jQuery(".track_link").on('click', function(e){
                var href = jQuery(this).attr('href');
                e.preventDefault();
                var type = jQuery(this).data('type');
                jQuery.ajax({
                        "method": "get",
                        "url": "{{route('open-hyperlink')}}?type="+type+"&ref=listing_detail&url="+href+"&ajax=true"
                });
                window.location.href = href;
        });

        $(document).on('click', 'a[href^="#"]', function (event) {
                event.preventDefault();

                $('html, body').animate({
                        scrollTop: $($.attr(this, 'href')).offset().top
                }, 500);
        });

        function getMobileOperatingSystem() {
                
                var userAgent = navigator.userAgent || navigator.vendor || window.opera;
           
                  if ( userAgent.match( /iPad/i ) || userAgent.match( /iPhone/i ) || userAgent.match( /iPod/i ) ) { 
                  //document.getElementsByTagName('body')[0].className+=' ios';
                  return 'iOS'; 
                }
                          
                  else if ( userAgent.match( /Android/i ) ) { 
                          //document.getElementsByTagName('body')[0].className+=' android';
                  return 'Android'; 
                }
           
                  else { return 'non-mobile or unknown'; }
          }
          
        // jQuery(document).ready(function(){
@push('document-ready-javascript') 
        try{

                if(navigator.share){
                        jQuery("#shareButton").show();
                }
                else{
                        var deviceType = getMobileOperatingSystem();
                   
                        if(deviceType == 'Android'){
                                jQuery("#shareButtonSmsAndroid").show();
                        }
                        else if(deviceType == 'iOS'){
                                jQuery("#shareButtonSmsiOS").show();
                        }
                                
                   
                }
                var is_safari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
                var is_firefox = typeof window.InstallTrigger !== 'undefined';
                if (is_safari){
                        jQuery(".listing-detail__offerland").addClass('safari');
                }
                if (is_firefox){
                        jQuery(".listing-detail__offerland").addClass('firefox');
                }
        }catch(exPtDcRdy){}
@endpush
        // });

        
        function openShareOptions(){
                if (navigator.share) {
                
                        navigator.share({
                                title: '{{$listing->streetaddress}} {{$listing->subarea}}, {{$listing->city}}, {{$listing->province}} | Hani & Les | BC Condos And Homes',
                                text: '{{$listing->streetaddress}} {{$listing->subarea}}, {{$listing->city}}, {{$listing->province}}',
                                url: '{{route("listing-detail-page2", ["slug"=>$listing->slug])}}',
                        })
                          .then(() => console.log('Successful share'))
                          .catch((error) => console.log('Error sharing', error));
                  }
        }

        function getFormData($form){
                var unindexed_array = $form.serializeArray();
                var indexed_array = {};
        
                $.map(unindexed_array, function(n, i){
                        indexed_array[n['name']] = n['value'];
                });
        
                return indexed_array;
        }

        jQuery('.listing-detail__askaquestionform').on('submit',function(evt){
                var thisForm = $(this); // to enable multiple instances of the form in a page.
                var errflag = false;
                
                jQuery(thisForm).addClass('listing-detail__requestingForm')
                
                if(!jQuery('.askQuestion__userDetailsRow',thisForm).is(':visible')){
                        // jQuery('.askQuestion__userDetailsRow',thisForm).hide().removeClass('hidden');
                        jQuery('.askQuestion__userDetailsRow',thisForm).slideToggle('fast');
                        jQuery('.askQuestion__userDetailsRow input',thisForm).attr('required',true);

                        // var ips = jQuery('.askQuestion__userDetailsRow input',thisForm);
                        // for (var i = 0; i < ips.length; i++) {ips[i].setCustomValidity('Required');}

                        errflag = true;
                }

                if(errflag){
                        evt.preventDefault();
                        return false;
                }

                if(jQuery(thisForm).valid()) {
                        evt.preventDefault();
                        var fullname = jQuery('.askQuestion__firstname',thisForm).val()+' '+jQuery('.askQuestion__lastname',thisForm).val().trim();
                        var emailaddress = jQuery('.askQuestion__emailaddress',thisForm).val().trim();
                        var phone = jQuery('.askQuestion__phonenumber',thisForm).val().trim();
                        var message = jQuery('.askQuestion__message',thisForm).val().trim();
                        var metadata = {
                                        fullname: fullname,
                                        emailaddress: emailaddress,
                                        email: emailaddress,
                                        phone: phone,
                                        message: message,
                                        listing_id: '{{$listing->listingid}}',
                                        // working_with_realtor: 
                        };

                        if(metadata.emailaddress=='' || metadata.phone=='' || metadata.message==''){
                                errflag = true;
                        }

                        if(errflag){
                                alert('Please provide all form-data');
                                evt.preventDefault();
                                return false;
                        }

                        var datastring = $(thisForm).serialize();
                        
                        jQuery('.listing__send--question',thisForm).attr('disabled', true);
                        jQuery.ajax({
                                type: "POST",
                                url: "{{route('api:contactus')}}",
                                data: datastring,
                                dataType: "json",
                                success: function(data) {
                                        jQuery('.listing__send--question',thisForm).html("<div>Success! We got your request. One of our representatives will contact you shortly.</div><br/><br/>");
                                },
                                error: function() {
                                        alert('error handling here');
                                },
                                complete: function(){
                                        jQuery('.listing__send--question',thisForm).removeAttr('disabled');
                                        jQuery(thisForm).html('<div class="bg-success" style="padding:1em">Success! We got your request. One of our representatives will contact you shortly.</div><br/><br/>')
                                },
                        });

                }
                return false;
        });

        jQuery('.listing__schedule--tour--button button').click(function(evt){
                var thisForm = $(this).closest('form'); // to enable multiple instances of the form in a page.
                var scheduleDateInput = ''+jQuery("input[name='showing_date']:checked",thisForm).val();
                var scheduleTimeInput = ''+jQuery('.listing__schedule--tour--time--dropdown select option:selected',thisForm).val();
                var scheduleReltorInput = jQuery("input[name='showing_realtor']:checked",thisForm).val();
                var schedulePreApprovedMortgageInput = jQuery("input[name='approved_check']:checked",thisForm).val();
                
                jQuery('.listing__schedule--tour select,.listing__schedule--tour input').on('click check select change',function(){
                        jQuery('.listing__schedule--tour--errors',thisForm).hide();
                });
                
                var date    = new Date(scheduleDateInput+'T'+ scheduleTimeInput+':00' ),
                        year    = date.getFullYear(),
                        month   = date.toLocaleString('default', { month: 'short' }),
                        day     = date.getDate(),
                        scheduleDate = month + ' ' + day + ', ' + year;
                var scheduleTime = date.toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
                var errflag = false;
                
                
                if(isNaN(year) || scheduleTimeInput=='' || !date){
                        jQuery('.listing__schedule--tour--errors',thisForm).show();//.fadeOut(2500, function(){});
                        errflag = true;
                }

                if(!jQuery('input[name="showing_realtor"]').is(':checked')){
                        jQuery('.listing__schedule--tour--errors-realtor',thisForm).show();
                        jQuery('.listing__schedule--tour--radio').on('check select click change','.realtorReqCheck',function(){
                                jQuery('.listing__schedule--tour--errors-realtor',thisForm).hide();
                        });
                        
                        // document.querySelector('input[name="showing_realtor"]').setCustomValidity('Required');
                        errflag = true;
                }

                if(!jQuery('input[name="approved_check"]').is(':checked')){
                        jQuery('.listing__schedule--tour--errors-pre-approved-mortgage',thisForm).show();
                        jQuery('.listing__schedule--tour--radio').on('check select click change','.pre-approved-mortgageReqCheck',function(){
                                jQuery('.listing__schedule--tour--errors-pre-approved-mortgage',thisForm).hide();
                        });
                        
                        // document.querySelector('input[name="showing_realtor"]').setCustomValidity('Required');
                        errflag = true;
                }
                
                if(errflag){
                        evt.preventDefault();
                        jQuery('#scheduleModal').modal('hide');
                return false;
        }


                jQuery('.schedule__date').text(scheduleDate);
                jQuery('.schedule__time').text(scheduleTime);
                jQuery('input#scheduleDate').val(scheduleDateInput);
                jQuery('input#scheduleTime').val(scheduleTime);
                jQuery('input#scheduleRealtor').val(scheduleReltorInput);
                jQuery('input#schedulePreApprovedMortgage').val(schedulePreApprovedMortgageInput);
        });

        jQuery('.showingReq_form').on('submit', function(e){
                e.preventDefault();
                jQuery("#send_showing_error", this).hide();

                var form = $(this);
                var data = getFormData(form);
                jQuery("#sendViewingReq",this).attr("disabled", true).addClass('inactive-red').text('Sending Request...');
                jQuery("#viewingRequestLoader",this).show();
                $.ajax({
                        type: "POST",
                        url: "{{route('api:request_showing')}}",
                        // The key needs to match your method's input parameter (case-sensitive).
                        data: JSON.stringify(data),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        success: function(data){

                                setTimeout( function(){ 
                                        jQuery("#sendViewingReq",form).removeClass('inactive-red');
                                        if(data.success){
                                                jQuery("#sendViewingReq", form).text('Request Sent! A member of our team will contact you');
                                        }else{
                                                if(data.message){
                                                        jQuery("#sendViewingReq", form).text(data.message);
                                                }else{
                                                        jQuery("#sendViewingReq", form).text('Something went wrong!');
                                                }
                                                jQuery("#sendViewingReq,.listing__schedule--tour--send",form).addClass('inactive-red');
                                        }
                                        jQuery("#sendingRequestLoader", form).hide();
                                        jQuery("#viewingRequestLoader", form).hide();
                                  }  , 1000 );
                                //jQuery(".showingReq_form .close").text("Back");
                                jQuery(".showingReq_form .scheduleApp").hide();
                                jQuery(".showingReq_form input").hide();
                                jQuery(".showingReq_form textarea").hide();
                                document.getElementById("showingReq_form").reset();
                        },
                        error: function(errMsg){
                                jQuery("#sendViewingReq", form).text( errMsg.message?errMsg.message:'Request Failed! ');
                                jQuery("#sendingRequestLoader", form).hide(); jQuery("#viewingRequestLoader", form).hide();
                        },
                });
        });

        jQuery('.showing_form').on('submit', function(e){
                e.preventDefault();
                
                jQuery("#send_showing_error", this).hide();
                
                var form = $(this);
                var data = getFormData(form);
                jQuery("#sendShowing",this).attr("disabled", true);
                jQuery("#sendShowing",this).addClass('inactive-red');
                jQuery("#sendShowing",this).text('Sending Request...');
                jQuery("#sendingRequestLoader",this).show();
                $.ajax({
                        type: "POST",
                        url: "{{route('api:request_showing')}}",
                        data: JSON.stringify(data),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        success: function(data){
                                
                                setTimeout( function(){ 
                                        jQuery("#sendShowing", form).text('Request Sent');
                                        jQuery("#sendingRequestLoader", form).hide();
                                        jQuery("#sendShowing",form).removeClass('inactive-red');
                                }  , 3000 );
                                
                                document.getElementById("showing_form").reset();
                        },
                        failure: function(errMsg) {
                                alert(errMsg);
                        }
                });
        });

        jQuery("#close_showing_success").on('click', function(){
                jQuery('.listing__schedule--tour').show();
                jQuery('#request_showing_success').hide();
        });

        jQuery("#ask_question_form").on('submit', function(e){
                e.preventDefault();
                if(!jQuery.trim(jQuery("#ask__question").val()))
                {
                        jQuery("#send_question_error").show();
                        jQuery("#send_question_error").fadeTo(2000, 500).slideUp(500, function(){
                                jQuery("#send_question_error").slideUp(500);
                        });
                }
                else{
                        jQuery(".listing__ask-question__button").attr("disabled", true);
                        jQuery(".listing__ask-question__button").text('Sending...');
                        var $form = $("#ask_question_form");
                        var data = getFormData($form);
                        $.ajax({
                                type: "POST",
                                url: "{{route('api:ask_question')}}",
                                data: JSON.stringify(data),
                                contentType: "application/json; charset=utf-8",
                                dataType: "json",
                                success: function(data){
                                        jQuery(".listing__ask-question__button").attr("disabled", false);
                                        jQuery(".listing__ask-question__button").text('Ask Question');
                                        document.getElementById("ask_question_form").reset();
                                        jQuery("#ask_question_form").hide();
                                        jQuery('#askquestion_success').show();
                                },
                                failure: function(errMsg) {
                                        alert(errMsg);
                                }
                        });
                }
        });

        jQuery("#close_askquestion_success").on('click', function(){
                jQuery("#ask_question_form").show();
                jQuery('#askquestion_success').hide();
        });

        $('#questionModal').on('hidden.bs.modal', function () {
                jQuery("#ask_question_form").show();
                jQuery('#askquestion_success').hide();
          })

          jQuery("#toggleClientView").on('click', function(){
                  jQuery(".listing__agent").toggle();
                  jQuery(".listing-detail__agent").toggle();
                  jQuery(".listing__schedule--tour").toggle();
                  jQuery(".listing__ask-question").toggle();
                  var text = jQuery(this).text();
                  jQuery(this).text(text=="Client View"?"Realtor View":"Client View");
          });

        @if($building)

        jQuery("#statsTimeSelect").on('change', function(){
                var period = jQuery(this).val();
                update_stats(period);
        });

        function update_stats(period){
                jQuery.ajax({
                        method: "GET",
                        url: "{{route('getBuildingStatsJson')}}?id={{$building->import_id}}&period="+period,
                }).done(function(response){
                        jQuery("#stats_avg_sold_price").text(response.avg_sold_price);
                        jQuery("#stats_avg_per_sqft").text(response.avg_per_sqft);
                        jQuery("#stats_avg_dom").text(response.avg_dom);
                        jQuery("#stats_expensive_sold").text(response.expensive_sold);
                        jQuery("#statsTime a.active").removeClass("active");
                        jQuery("#statsTime a[data-val='"+period+"']").addClass("active");
                });
        }

        jQuery("#soldPeriod, #soldBeds").on('change', function(){
                var period = jQuery("#soldPeriod").val();
                var soldBeds = jQuery("#soldBeds").val();
                update_sold_listings(period, soldBeds);
        });


        function update_sold_listings(period, soldBeds){
                jQuery.ajax({
                        method: "GET",
                        url: "{{route('getBuildingSoldListings')}}?id={{$building->import_id}}&period="+period+"&beds="+soldBeds,
                }).done(function(response){
                        if(response){
                                jQuery("#sold_table thead").show();
                                jQuery("#no_sold_listing_available").hide();
                                
                        }
                        else{
                                jQuery("#sold_table thead").hide();
                                jQuery("#no_sold_listing_available").show();
                        }
                        jQuery("#sold_table tbody").html(response);
                        jQuery("#sold_period a.active").removeClass("active");
                        jQuery("#sold_period a[data-val='"+period+"']").addClass("active");
                });
        }

        jQuery("#active_beds_options").on('change', function(){
                var beds = jQuery(this).val();
                update_active_listings(beds);
        });

        function update_active_listings(beds){
                jQuery.ajax({
                        method: "GET",
                        url: "{{route('getBuildingActiveListings')}}?id={{$building->import_id}}&beds="+beds,
                }).done(function(response){
                        if(response){
                                jQuery("#active_table thead").show();
                                jQuery("#no_active_listing_available").hide();
                                
                        }
                        else{
                                jQuery("#active_table thead").hide();
                                jQuery("#no_active_listing_available").show();
                        }
                        jQuery("#active_table tbody").html(response);
                        jQuery("#active_beds a.active").removeClass("active");
                        jQuery("#active_beds a[data-val='"+beds+"']").addClass("active");
                });
        }

        @endif
        var tomorrow = moment().add(1,'days');
        jQuery(document).ready(function(){
                jQuery("#timeone").datetimepicker({
                        format: 'LT'
                });
                jQuery("#timetwo").datetimepicker({
                        format: 'LT'
                });
                jQuery("#dateone").datetimepicker({
                          format: 'YYYY-MM-DD',
                          minDate: tomorrow
                });
                jQuery("#datetwo").datetimepicker({
                          format: 'YYYY-MM-DD',
                          minDate: tomorrow
                });
        });
</script>
<script>
        var swiper = new Swiper('.swiper-container', {
                slidesPerView: 3,
                spaceBetween: 10,
                slidesPerGroup: 1,
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
                        sensitivity: 3,
                },
          
                breakpoints: {
                        1200: {
                                slidesPerView: 3,
                                spaceBetween: 10,
                                slidesPerGroup: 1,
                        },
                        992: {
                                slidesPerView: 3,
                                spaceBetween: 10,
                                slidesPerGroup: 1,
                        },
                        768: {
                                slidesPerView: 3,
                                spaceBetween: 10,
                                slidesPerGroup: 1,
                        },
                        640: {
                                slidesPerView: 3,
                                spaceBetween: 10,
                                slidesPerGroup: 1,
                        },
                        400: {
                                slidesPerView: 3,
                                spaceBetween: 10,
                                slidesPerGroup: 1,
                        }
                }
        });
        
        jQuery('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                jQuery('.listing-detail__images').slick('setPosition');
                jQuery('.building-detail__images').slick('setPosition');
        });

        if ( $('#mortgageCalculator').length > 0 ) {
                var listprice = $(".listing-detail__price--mortgage").text().replace("$","").replace(",","").replace(",","");
                console.log(listprice);
                if(listprice == 0) {
                  listprice = 1;
                }

                function addCommas(nStr) {
                  nStr = nStr.toString();
                  nStr = nStr.replace(new RegExp('[^0-9]+', "g"), '');
                  nStr += '';
                  x = nStr.split('.');
                  x1 = x[0];
                  x2 = x.length > 1 ? '.' + x[1] : '';
                  var rgx = /(\d+)(\d{3})/;
                  while (rgx.test(x1)) {
                         x1 = x1.replace(rgx, '$1' + ',' + '$2');
                  }
                  return x1 + x2;
                }

                function pmt(rate,nper,loan_amount) {
                  return Math.round(rate * -(0-Math.pow((1+rate),nper)*loan_amount) / (-1+Math.pow((1+rate),nper))*100) / 100;
                }

                function getRate(rate,periods_per_year) {
                  return (Math.pow((1+rate/2),(2/periods_per_year)))-1;
                }


                var updatePayment = function(downpayment1 = null) {
                        price = listprice;
                        frequency = 12;

                        if(localStorage.getItem("bcc_downpayment") > 0){
                                downpayment = localStorage.getItem("bcc_downpayment");
                                $('#inputDownpayment').data('val', downpayment);
                                $('#inputDownpayment_m').data('val', downpayment);
                                $('#inputDownpayment').val(addCommas(downpayment));
                                $('#inputDownpayment_m').val(addCommas(downpayment));
                        }
                        else{
                                if(downpayment1 > 0){
                                        downpayment = downpayment1
                                }else{
                                        downpayment = $('#inputDownpayment').data('val');
                                }
                        }
                        
                        var rental = Number($("#inputRentalincome").val());
                        if(rental < 0){
                                rental = 0;
                        }

                        var per = (downpayment/price)*100;
                        $("#downpayment_per").text(per.toFixed(0));
                        $("#downpayment_per_m").text(per.toFixed(0));
                        interest = ($('#inputRate').val()/100)/12;
                        exponent = frequency*$('#inputTerm').val();
                        loan_sum = price - downpayment;

                        exponent_subtotal = Math.pow((1+interest),-exponent);
                        exponent_total = (1-exponent_subtotal)/interest;

                        totalmonth = loan_sum/exponent_total;
                        total = (totalmonth*12)*25;
                        
                        var remaining_mortgage = totalmonth - rental;
                        remaining_mortgage = "$" + remaining_mortgage.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');

                        totalmonth_round = "$" + totalmonth.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                        total_round = "$" + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                        loan_sum_total = "$" + loan_sum.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');

                        //$('#mortgageMonthly .amount').text(totalmonth_round);
                        //$('#mortgageResult .amount').text(loan_sum_total);
                        $('#mortgage_amount').text(totalmonth_round);
                        $('#mortgage_amount_m').text(totalmonth_round);
                        $("#mortgage_amount1").text(totalmonth_round);
                        $("#mortgage_amount_m1").text(totalmonth_round);
                        $("#rentalAmount").text("$"+addCommas(rental));
                        $("#rentalAmount_m").text("$"+addCommas(rental));
                        $("#finalMortgage").text(remaining_mortgage);
                        $("#finalMortgage_m").text(remaining_mortgage);

                        if(rental > 0){
                                $("#withoutRental").hide();
                                $("#withoutRental_m").hide();
                                $("#withRental").show();
                                $("#withRental_m").show();
                        }
                        else{
                                $("#withoutRental").show();
                                $("#withoutRental_m").show();
                                $("#withRental").hide();
                                $("#withRental_m").hide();
                        }
                        
                }

                updatePayment();

                // add change events
                // $('#mortgageModal').bind("mouseenter mouseleave click", function() {
                $('#inputRate, #inputRate_m').change(function() {
                        $('#inputRate').val($(this).val());
                        $('#inputRate_m').val($(this).val());
                        var val = Number($(this).val());
                        if(val <=0){
                                val = 0.1;
                        }
                        if(val > 20){
                                val = 20;
                        }
                        $("#inputRate").val(val);
                        $("#inputRate_m").val(val);
                        updatePayment();
                });
                
                $('#inputTerm, #inputTerm_m').change(function() {
                        $('#inputTerm').val($(this).val());
                        $('#inputTerm_m').val($(this).val());
                        updatePayment();
                });

                $('#inputRentalincome, #inputRentalincome_m').on('change keyup',function(){
                        $('#inputRentalincome').val($(this).val());
                        $('#inputRentalincome_m').val($(this).val());
                        updatePayment();
                });

                var typingTimer;                //timer identifier
                var doneTypingInterval = 5000;  //time in ms, 2 second for example


                $('#inputDownpayment, #inputDownpayment_m').on('change keyup',function() {
                        var newval=Number($(this).val().replace("$","").replace(",","").replace(",",""));
                        if (typeof(Storage) !== "undefined") {
                                localStorage.setItem("bcc_downpayment", newval);
                        }
                        if(newval > listprice){
                                newval = listprice;
                                $(this).data('val', newval);
                                $(this).val(addCommas(newval));
                                $('#inputDownpayment').val($(this).val());
                                $('#inputDownpayment_m').val($(this).val());
                                updatePayment(newval);
                        }
                        var oldval = Number($(this).data('val'));
                         
                        if(newval != oldval){
                                $(this).data('val', newval);
                                $(this).val(addCommas(newval));
                                $('#inputDownpayment').val($(this).val());
                                $('#inputDownpayment_m').val($(this).val());
                                updatePayment(newval);
                        }
                        
                });


        }

        $('.show-item').hide();
        /*
        // disabled because $('.listing-detail__request-showing-scroll')[disabled] -- doesNotExist ->gives error onScroll
        $(window).scroll(function() {
                var hT = $('.listing-detail__request-showing-scroll').offset().top,
                        hH = $('llisting-detail__request-showing-scroll').outerHeight(),
                        wH = $(window).height(),
                        wS = $(this).scrollTop();
                if (wS > (hT+hH-wH) && (hT > wS) && (wS+wH > hT+hH)) {
                        $('.listing__viewing--header').hide();
                } else {
                        $('.listing__viewing--header').show();
                }
        });
        */
</script>
<script  type="text/javascript" src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
{{-- {!! $validator->selector('#request_showing_form') !!} --}}
{{-- {!! $contactus_validator->selector('#contactus_form') !!} --}}
<script>
        jQuery('#request_showing_form').on('submit', function() {
                if(jQuery(this).valid()) {
                // do your ajax stuff here
                jQuery("#showingsubmit").attr('disabled', true);
                var firstname = jQuery('#firstname').val();
                var lastname = jQuery('#lastname').val();
                var emailaddress = jQuery('#emailaddress').val();
                var phone = jQuery('#phonenumber').val();
                var language = jQuery('#language').val();
                var working_with_realtor =  jQuery("#agentcheck1").is(':checked')?'Yes':'No';
                var pre_approved_mortgage =  jQuery("#approved-check1").is(':checked')?'Yes':'No';
                var prefered_date_1 = jQuery('#dateone').val();
                var prefered_time_1 = jQuery('#timeone').val();
                var prefered_date_2 ='';
                var prefered_time_2 = '';
                if(jQuery('#timetwo').val()){
                        var prefered_date_2 = jQuery('#datetwo').val();
                        var prefered_time_2 = jQuery('#timetwo').val();
                }
                var message = jQuery('#showingmessage').val();

                var metadata = {
                        first_name: firstname,
                        last_name: lastname,
                        email: emailaddress,
                        phone: phone,
                        language: language,
                        working_with_realtor: working_with_realtor,
                        pre_approved_mortgage: pre_approved_mortgage,
                        prefered_date_1:prefered_date_1,
                        prefered_time_1: prefered_time_1,
                        prefered_date_2: prefered_date_2,
                        prefered_time_2: prefered_time_2,
                        message: message,
                        listing_id: '{{$listing->listingid}}'
                };

                var datastring = $("#request_showing_form").serialize();
                        $.ajax({
                                type: "POST",
                                url: "{{route('api:request_showing')}}",
                                data: datastring,
                                dataType: "json",
                                success: function(data) {
                                        jQuery("#showingmodeltitle").remove();
                                        jQuery("#request_showing_form").html("<div>Success! We got your request. One of our representatives will contact you shortly.</div><br/><br/>");
                                },
                                error: function() {
                                        alert('error handling here');
                                }
                        });
                }
                return false;
                });



                jQuery('.listing-detail__offer-button.start_an_offer').on('submit click', function(evt) {
                        var $thisButton = jQuery(this);
                        @if(Auth::user() && $listing->status == 'Active')
                        var offerprice = '{{Helper::money_format('%.0n',(!empty($commissionDetails['offer_price'])?$commissionDetails['offer_price']:0))}}'; 
                        {{-- $listing->get_commission_details('offer_price')  --}}
                        var localmetadata = {
                                'offerprice': offerprice,
                                // email: '{{Auth::user()->email}}',
                                // phone: '{{Auth::user()->phone}}',
                                // listing_id: '{{$listing->listingid}}',
                                fullname: '{{Auth::user()->first}} {{Auth::user()->last}}',
                                emailaddress: '{{Auth::user()->email}}',
                                phonenumber: '{{Auth::user()->phone_country_code??''}}{{Auth::user()->phone}}',
                                message: 'Made an offer of '+offerprice+' for listing: '+'{{$listing->listingid}}',
                                listingid: '{{$listing->listingid}}',
                                'agent-check-contactus': '',
                                event:'make-an-offer',
                        };
                        if(metadata!=undefined){
                                localmetadata = jQuery.extend({}, metadata, localmetadata);
                        }
                        console.log(localmetadata)
                        // Intercom('trackEvent', 'make-an-offer', localmetadata);

                        var dataToPost = localmetadata;//jQuery(localmetadata).serialize();

                        jQuery(this).attr('disabled', true);
                        jQuery.ajax({
                                type: "POST",
                                url: "{{route('api:contactus')}}", 
                                {{-- // url: "{{route('api:ask_question')}}", --}}
                                data: dataToPost,
                                dataType: "json",
                                success: function(data) {
                                        if(data.success || data.status=='success'){
                                                jQuery('.listing-detail__offer-button.start_an_offer').closest('div').html('<div class="alert alert-success">Success! We got your request. One of our representatives will contact you shortly.</div>');
                                        }else if(!data.status){
                                                jQuery('.listing-detail__offer-button.start_an_offer').closest('div').html('<div class="alert alert-danger">Error! '+data.message+'</div>');
                                        }else{
                                                jQuery(thisButton).text('Please try again!');
                                        }
                                },
                                error: function() {
                                        alert('error handling here');
                                },
                                complete: function(){
                                        jQuery(this).removeAttr('disabled');
                                        jQuery('.listing-detail__offer-button.start_an_offer').closest('div').html('<div class="alert alert-success">Success! We got your request. One of our representatives will contact you shortly.</div>');
                                },
                        });

                        // jQuery('.listing-detail__offer-button.start_an_offer').closest('div').html('<div class="alert alert-success">Success! We got your request. One of our representatives will contact you shortly.</div>');
                        @elseif($listing->status == 'Sold')
                        return false;
                        @else
                        evt.preventDefault();
                        jQuery(this).text('Please login to Start an offer!');
                        jQuery("#loginModal").modal({backdrop: 'static', keyboard: false});
                        jQuery('#loginModal').modal('show');
                        return false; 
                        @endif
                });



                jQuery('#contactus_form').on('submit', function() {
                        if(jQuery(this).valid()) {
                                jQuery("#contactsubmit").attr('disabled', true);
                                var fullname = jQuery('#full-name-contact').val();
                                var emailaddress = jQuery('#email-address-contact').val();
                                var phone = jQuery('#phone-number-contact').val();
                                var message = jQuery('#contactgmessage').val();
                                var working_with_realtor =  jQuery("#agentcheck1_contactus").is(':checked')?'Yes':'No';
                                var metadata = {
                                                fullname: fullname,
                                                emailaddress: emailaddress,
                                                email: emailaddress,
                                                phone: phone,
                                                message: message,
                                                listing_id: '{{$listing->listingid}}',
                                                working_with_realtor: working_with_realtor
                                };

                                var datastring = $("#contactus_form").serialize();
                                $.ajax({
                                type: "POST",
                                url: "{{route('api:contactus')}}",
                                data: datastring,
                                dataType: "json",
                                success: function(data) {
                                        jQuery("#contactus_form").html("<div>Success! We got your request. One of our representatives will contact you shortly.</div><br/><br/>");
                                },
                                error: function() {
                                        alert('error handling here');
                                }
                        });

                        }
                        return false;
                })

                function imageViewed(){
                        var metadata = {
                                event: 'Listing Image Viewed',
                                address: "{{$listing->streetaddress}}",
                                mls: "{{$listing->listingid}}",
                                city: "{{$listing->city}}",
                                @if($listing->status == 'Sold')price: "{{$listing->soldprice_2}}",
                                @else price: "{{$listing->listprice_2}}",
                                @endif
                                listing_link: "https://www.bccondosandhomes.com/listing/{{$listing->slug}}"
                        };
                }

                @push('document-ready-javascript') 
                try{

                $('[data-fancybox="gallery"]').fancybox({
                        //next: function () {
                        //      a.current && a.jumpto(a.current.index + 1);
                        //      a.trigger("onNext");
                        //},
                        //prev: function () {
                        //      a.current && a.jumpto(a.current.index - 1);
                        //      a.trigger("onPrev");
                        //},
                        //onNext          : function () { console.log('next was called'); },
                        //onPrev          : function () { console.log('prev was called'); },
                        afterLoad: function(current, previous) {
                                $(".fancybox-button--arrow_right").on('click',function(){
                                        imageViewed();
                                });
                                $(".fancybox-button--arrow_left").on('click',function(){
                                        imageViewed();
                                });
                        }
                });

                $('[data-fancybox="gallery-mobile"]').fancybox({
                        //next: function () {
                        //  a.current && a.jumpto(a.current.index + 1);
                        //  a.trigger("onNext");
                        //},
                        //prev: function () {
                        //  a.current && a.jumpto(a.current.index - 1);
                        //  a.trigger("onPrev");
                        //},
                        //onNext          : function () { console.log('next was called'); },
                        //onPrev          : function () { console.log('prev was called'); },
                        afterLoad: function(current, previous) {
                                $(".fancybox-button--arrow_right").on('click',function(){
                                        imageViewed();
                                });
                                $(".fancybox-button--arrow_left").on('click',function(){
                                        imageViewed();
                                });
                        }
                });
                }catch(exPtDcRdy){}
                @endpush

                $(".listing-detail__image a").on('click',function(){imageViewed(); });
                $(".fancybox-button--arrow_right").on('click',function(){imageViewed(); });
                $(".fancybox-button--arrow_left").on('click',function(){imageViewed(); });
                $('#listing_images').on('afterChange', function(event, slick, currentSlide, nextSlide){imageViewed(); });
                @if($user)
@if($wwr_popup)
$('#wwrPopupModal').modal({backdrop: 'static', keyboard: false, show:true});

$("#wwr_save").on('click', function(){
        jQuery("#describe-error-wwr").hide();
        jQuery("#realtor-check-dropdown-error-wwr").hide();

        if($("#client-check-dropdown-wwr").val() == ''){
                jQuery("#describe-error-wwr").text('This is required!');
                jQuery("#describe-error-wwr").show();
        }
        else if($("#realtor-check-dropdown-wwr").val() == ''){
                jQuery("#realtor-check-dropdown-error-wwr").text('This is required!');
                jQuery("#realtor-check-dropdown-error-wwr").show();
        }
        else {
                $('#wwrPopupModal').modal('hide');
                        jQuery.ajax({
                                method: "post",
                                url: "{{route('updatewwr')}}",
                                data: {"_token": "{{ csrf_token() }}"}
                        }).done(function(response){})
        }
});

@endif
@endif


jQuery('.listing__schedule--tour').show().insertAfter( '.listing-detail__status-price--box.hidden-sm' );
jQuery('.floating__box .listing__sidebar-contact').hide();

@if(!empty($user->email) && substr($user->email,-12)=='pixilink.com')
/*---13-Apr-2021 Testing Before-Update --BEGINS--- */
jQuery('.listing-detail__offerncommission').removeClass('hide'); 
//.insertAfter('.listing-detail__description')/*.insertBefore('.listing-detail__amenities')*/;


// document.addEventListener('touchstart', onTouchStart, {passive: true}); // no-function found on-touchstart-event, so disabled
/*---13-Apr-2021 Testing Before-Update --ENDS--- */
@endif

</script>
<script src="https://cdn.jsdelivr.net/npm/lazyframe/dist/lazyframe.min.js"></script>
{{-- <script  type="text/javascript" src="{{ URL::asset('frontend/js/lazyframe.min.js') }}"></script> --}}
<script type="text/javascript">
{{-- 
@if(true || Browser::isMobile())
setTimeout(function(){
@else
(function(){
@endif
 --}}

(function(){
        var els = document.querySelectorAll('[data-src4lazyload]');
        for (var i = 0; i < els.length; i++) {
                if(els[i].getAttribute('src').length<=0){
                        els[i].setAttribute('src', els[i].getAttribute('data-src4lazyload'));
                        els[i].setAttribute('onmouseover','if(!this.src.length)this.setAttribute(\'src\',this.getAttribute(\'data-src4lazyload\'));this.removeAttribute(\'onmouseover\')');
                }else{
                        els[i].removeAttribute('onmouseover')
                }
        }
})();

{{-- 
@if (Browser::isMobile())
},5100); //();
@else
})();
@endif
--}}

lazyframe('.lazyframe');
{{-- 
// lazyframe('[loading="lazy"]');
// lazyframe('.resp-iframe');
// lazyframe('iframe');

/**
 * intercomEvent_whatsOfferValue_clicked Intercom-event
 * @return {void} tracks-intercom-event--whatsOfferValue-clicked
 */
--}}

@if(auth()->user()?->can('dev-dj') && $listing->getFAQs())
// faqs:
@foreach($_FAQsCombined AS $_faq)
// {{$_faq['q']}} | Ans: {{$_faq['ans']}} 
@endforeach
@endif

</script>
<script>
window.BCTrack = {
  pageType:   "sold",
  listingKey: "{{$listing->listingid}}",
  address:    "{{addslashes($listing->streetaddress)}}",
  city:       "{{addslashes($listing->cityProperCased)}}",
  soldPrice:  {{$listing->soldprice_2 ?? 'null'}},
  soldDate:   "{{ $listing->closedate ? date('Y-m', strtotime($listing->closedate)) : '' }}",
};
</script>
@auth
<script>
  window.BCTrack = window.BCTrack || {};
  window.BCTrack.email = "{{ auth()->user()->email ?? '' }}";
  @if(!empty(auth()->user()->phone))
  window.BCTrack.phone = "{{ auth()->user()->phone }}";
  @endif
  @if(!empty(auth()->user()->fub_id))
  window.BCTrack.fubId = "{{ auth()->user()->fub_id }}";
  @endif
</script>
@endauth
@include('frontend.includes.user_additional_scripts')
@endpush
