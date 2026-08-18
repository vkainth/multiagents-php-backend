<?php

namespace App\Repository;

use App\Models\Listings;
use App\Models\UserPropertyViews;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Repository\ActivityRepository;
use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Places;
use App\Models\BoardPlaces;
use Carbon\Carbon;
use App\Helpers\FubAreaHelper;

class ListingRepository
{

    protected $activityRepository;
    public function __construct(ActivityRepository $activityRepository)
    {
        $this->activityRepository = $activityRepository;
    }

    public function getListings($request = NULL)
    {

        $status = 'Sold';
        $sold_allowed = 1;
        $user = Auth::user();
        $agent = $user->loginWithAgent()->first();
        if (!$agent) {
            $agent = $user->agent()->first();
        }

        if ($agent && !$agent->isSoldAllowed() && $user->role == "USER") {
            $status = 'Active';
            $sold_allowed = 0;
        }
        if ($sold_allowed == 0 && $request->get('status') == 'Sold') {
            Session::flash('message', config('constants.no_sold_access_message'));
            $user->soldRequested();
        }
        $listings =  Listings::/*with('aphoto')->*/where('table', 'mlsr_listings');
        if ($request) {
            if ($request->get('status') == 'Active') {
                $status = $request->get('status');
            }


            $bbarray = array();
            $bbarray['0'] = array('=', '0');
            $bbarray['0p'] = array('>=', '0');
            $bbarray['1'] = array('=', '1');
            $bbarray['1p'] = array('>=', '1');
            $bbarray['2'] = array('=', '2');
            $bbarray['2p'] = array('>=', '2');
            $bbarray['3'] = array('=', '3');
            $bbarray['3p'] = array('>=', '3');
            $bbarray['4'] = array('=', '4');
            $bbarray['4p'] = array('>=', '4');
            $bbarray['5'] = array('=', '5');
            $bbarray['5p'] = array('>=', '5');
            $bbarray['6'] = array('=', '6');
            $bbarray['6p'] = array('>=', '6');
            $bbarray['7'] = array('=', '7');
            $bbarray['7p'] = array('>=', '7');
            $bbarray['8'] = array('=', '8');
            $bbarray['8p'] = array('>=', '8');

            if ($request->get('beds') != '') {
                if (isset($bbarray[$request->get('beds')])) {
                    $listings = $listings->where("bedrooms", $bbarray[$request->get('beds')][0], $bbarray[$request->get('beds')][1]);
                }
            }

            if ($request->get('baths') != '') {
                if (isset($bbarray[$request->get('baths')])) {
                    $listings = $listings->where("bathstotal", $bbarray[$request->get('baths')][0], $bbarray[$request->get('baths')][1]);
                }
            }

            if ($request->get('min_price') != '') {
                if ($status == 'Sold') {
                    $listings = $listings->where("soldprice_2", ">=", (int) $request->get('min_price'));
                } else {
                    $listings = $listings->where("listprice_2", ">=", (int) $request->get('min_price'));
                }
            }

            if ($request->get('max_price') != '') {
                if ($status == 'Sold') {
                    $listings = $listings->where("soldprice_2", "<=", (int) $request->get('max_price'));
                } else {
                    $listings = $listings->where("listprice_2", "<=", (int) $request->get('max_price'));
                }
            }

            if ($request->get('min_area') != '') {

                $listings = $listings->where("livingarea_2", ">=", $request->get('min_area'));
            }

            if ($request->get('max_area') != '') {

                $listings = $listings->where("livingarea_2", "<=", $request->get('max_area'));
            }

            if ($request->get('type')) {
                $type = $request->get('type');

                $listings = $listings->whereIn('type', $type);
            }

            if ($request->get('min_kitchen')) {
                $min_kitchen = $request->get('min_kitchen');

                $listings = $listings->where('kitchens', ">=", $min_kitchen);
            }

            if ($request->get('max_kitchen')) {
                $max_kitchen = $request->get('max_kitchen');
                if ($request->get('min_kitchen') && $max_kitchen >=  $request->get('min_kitchen')) {
                    $listings = $listings->where('kitchens', "<=", $max_kitchen);
                } else {
                    $listings = $listings->where('kitchens', "<=", $max_kitchen);
                }
            }

            if ($request->get('year_built_from') && $request->get('year_built_to')) {
                $from_year = $request->get('year_built_from');
                $to_year = $request->get('year_built_to');
                if ($from_year <= $to_year) {
                    $listings = $listings->where('yearbuilt', '>=', $from_year)->where('yearbuilt', '<=', $to_year);
                }
            }

            if ($request->get('sold_time') > 0  && $request->get('sold_time_unit') != '0') {
                if ($status == "Sold") {
                    //$sold_time = (int)$request->get('sold_time')+1;
                    $sold_time = (int) $request->get('sold_time');
                    $sold_time_unit = $request->get('sold_time_unit');
                    $listings = $listings->where("sold_date", ">=", DB::raw("DATE_SUB(now(), interval " . $sold_time . " " . strtolower($sold_time_unit) . ")"));
                } else {
                    $listed_time = (int) $request->get('sold_time') + 1;
                    $listed_time_unit = $request->get('sold_time_unit');
                    $listings = $listings->where("list_date", ">=", DB::raw("DATE_SUB(now(), interval " . $listed_time . " " . strtolower($listed_time_unit) . ")"));
                }
            }

            if ($request->get('cities') != '' || $request->get('areas') != '' || $request->get('subareas') != '' || $request->get('postalareas') != '' || $request->get('postalcodes') != '' || $request->get('addresses') != '' || $request->get('listingid') != '' || $request->get('places') != '') {

                $listings = $listings->where(function ($query) use ($request) {

                    if ($request->get('listingid') != '') {
                        $listingids = explode(";", $request->get('listingid'));
                        $query->whereIn('listingid', $listingids, 'or');
                    }

                    if ($request->get('subareas') != '') {
                        $subareas = explode(";", $request->get('subareas'));
                        $query->whereIn('subarea', $subareas, 'or');
                        foreach ($subareas as $subarea) {
                            if ($subarea == "Downtown") {
                                $query->WhereIn('subarea', array('Coal Harbour', 'Downtown VE', 'Downtown VW', 'False Creek North', 'West End VW', 'Yaletown'), 'or');
                            }
                        }
                    }

                    if ($request->get('areas') != '') {
                        $areas = explode(";", $request->get('areas'));
                        $query->whereIn('area', $areas, 'or');
                    }

                    if ($request->get('cities') != '') {
                        $cities = explode(";", $request->get('cities'));
                        $query->whereIn('city', $cities, 'or');
                    }


                    if ($request->get('postalareas') != '') {
                        $postalareas = explode(";", $request->get('postalareas'));
                        $query->whereIn('postalarea', $postalareas, 'or');
                    }

                    if ($request->get('postalcodes') != '') {
                        $postalcodes = explode(";", $request->get('postalcodes'));
                        $query->whereIn('postalcode', $postalcodes, 'or');
                    }

                    if ($request->get('addresses') != '') {
                        $addresses = explode(";", $request->get('addresses'));
                        $query->where(function ($q2) use ($addresses) {
                            foreach ($addresses as $addr) {
                                $q2->where('streetaddress', 'like', '%' . trim($addr) . '%');
                            }
                        });
                    }
                    if ($request->get('places') != '') {
                        $where = NULL;
                        $places = explode(';', stripslashes($request->get('places')));
                        $allplaces = array_unique($places);
                        $searchQueries = BoardPlaces::whereIn('place', $allplaces)->pluck('query')->toArray();
                        if (is_array($searchQueries)) {
                            if (sizeof($searchQueries) > 0) {
                                $where = '(' . implode(' OR ', $searchQueries) . ')';
                            }
                        } else {
                            $where = $searchQueries;
                        }

                        foreach ($allplaces as $place) {
                            if ($place == "Downtown") {
                                $where .= " OR (subarea in ('Coal Harbour','Downtown VE','Downtown VW','False Creek North','West End VW','Yaletown'))";
                            }
                        }

                        if ($where) {
                            $query->orWhereRaw($where);
                        }
                    }
                });
            }
        }
        if ($request->get('inCity') != '') {
            $listings = $listings->where('city', $request->get('inCity'));
        }

        $listings = $listings->where('status', $status)->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'));

        if ($status == 'Sold') {

            $listings = $listings->latest('sold_date')->whereNotNull('soldprice_2')->where('soldprice_2', '!=', '');
        } else {
            $listings = $listings->latest('list_date');
        }

        $totalListings = $listings->count();
        $listings = $listings->take(100)->get();

        if (!$request->get('page')  && ($request->get('status') != '' || ($request->get('beds') != '' && $request->get('beds') != '0p') || ($request->get('baths') != '' && $request->get('baths') != '0p') || ($request->get('min_price') != '' && $request->get('min_price') != '25000') || ($request->get('max_price') != '' && $request->get('max_price') != '40000000') || ($request->get('min_area') != '' && $request->get('min_area') != '0') || ($request->get('max_area') != '' && $request->get('max_area') != '5000') || ($request->get('type') && count($request->get('type')) > 0) || $request->get('sold_time') > 0 || $request->get('cities') != '' || $request->get('areas') != '' || $request->get('subareas') != '' || $request->get('postalareas') != '' || $request->get('postalcodes') != '' || ($request->get('year_built_from') != '1949' && $request->get('year_built_from') != '') || ($request->get('year_built_to') != \date('Y') && $request->get('year_built_to') != ''))) {

            $allRequestData = $request->all();
            $mls = NULL;
            if ($listings) {
                $mls = $listings->pluck('listingid');
            }
            $this->activityRepository->logActivity(config('constants.activity_type.SEARCH'), $status, $allRequestData, $mls);
        }
        $response = [
            'totalRecords' => $totalListings,
            'listings' => $listings
        ];
        return $response;
    }

    function getSubareasOfCity($city)
    {
        return Places::where('type', 'subarea')->where('city', $city)->orderBy('label')->get();
    }

    function getSubareasFromSameCity($subarea)
    {
        if (request()->get('inCity')) {
            $city = request()->get('inCity');
        } else {
            $cityRecord = Places::where('place', $subarea)->where('type', 'subarea')->first();
            $city = $cityRecord->city;
        }
        return $this->getSubareasOfCity($city);
    }

    public function generateXML($listings)
    {
        $xml = NULL;
        $dom = new \DOMDocument("1.0");
        $node = $dom->createElement("markers");
        $parnode = $dom->appendChild($node);
        $json = array();
        $count = 0;
        if ($listings && count($listings) > 0) {
            foreach ($listings as $listing) {
                $name = "";
                if ($listing->getType() == 'Apartment' && $listing->suite_no) {
                    $name .= $listing->suite_no . " - ";
                }
                $name .= $listing->street_number . " " . $listing->street_name . " " . $listing->street_type;
                $node = $dom->createElement("marker");
                $newnode = $parnode->appendChild($node);
                $newnode->setAttribute("id", $listing->listingid);
                $newnode->setAttribute("name", $name);
                $newnode->setAttribute("address", $listing->subarea . ", " . $listing->city . ", " . $listing->province);
                $newnode->setAttribute("lat", $listing->lat);
                $newnode->setAttribute("lng", $listing->lng);
                $newnode->setAttribute("type", $listing->type);

                $json[$count]['id'] = $listing->listingid;
                $json[$count]['name'] = $name;
                $json[$count]['address'] = $listing->subarea . ", " . $listing->city . ", " . $listing->province;
                $json[$count]['lat'] = $listing->lat;
                $json[$count]['lng'] = $listing->lng;
                $json[$count]['type'] = $listing->type;
                $json[$count]['link'] = trim(route('listing-detail-page', $listing->slug));
                $json[$count]['status'] = $listing->status;
                $json[$count]['image'] =  asset('assets/img/no-image.jpg');
                if ($listing->photos()->count() > 0) {
                    $_listingPhoto=$listing->photos()->first();
                    $json[$count]['image'] = "https://media.pixilinkserver.com/" . str_replace('images', '', $_listingPhoto->directory . $_listingPhoto->name) . "?h=200&w=200";
                }
                if ($listing->status == 'Sold') {
                    $price = money_format('%.0n', $listing->soldprice_2);
                } else {
                    $price = $listing->listprice;
                }
                $json[$count]['price'] = $price;
                $count++;
            }
        }
        return json_encode($json);
    }



    public function getListingDetail($slug, $ref=NULL){
        $listing = Listings::with('mlsr_listing')->where('slug', $slug)->whereIn('status', array('Active','Sold'))->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->first();
        if($listing){
            if($listing->status == 'Sold'){

                $price = $listing->soldprice_2;
            } else {
                $price = $listing->listprice_2;
            }
            $listing->photos = $listing->public_photos();
            if (Auth::user()) {
                $this->activityRepository->logActivity(config('constants.activity_type.PROPERTY_VIEW'), $listing->status, $listing->listingid, $listing->listingid, $price, $ref);

                // Send property view event to Follow Up Boss
                \Illuminate\Support\Facades\Log::info('FUB property_view auth', ['mls' => $listing->listingid, 'user' => Auth::user()->email ?? '']);
                try {
                    $fubUser = Auth::user();

                    // Skip internal/test accounts and users without a verified phone number.
                    // Without phone_verified, the phone field is blank and FUB receives "+1" only.
                    $fubEmailDomain = $fubUser->email ? strtolower(substr(strrchr($fubUser->email, '@'), 1)) : '';
                    if ($fubEmailDomain !== 'pixilink.com' && $fubUser->phone_verified) {

                        // Throttle: only send once per user per listing per hour.
                        // logActivity() already inserted a row, so count > 1 means a prior
                        // view was recorded within the last 60 minutes.
                        $recentViewCount = UserPropertyViews::where('userid', $fubUser->id)
                            ->where('mls', $listing->listingid)
                            ->where('created_at', '>=', Carbon::now()->subHour())
                            ->count();

                        \Illuminate\Support\Facades\Log::info('FUB property_view check', [
                            'mls'    => $listing->listingid,
                            'user'   => $fubUser->email,
                            'recent' => $recentViewCount,
                        ]);

                        if ($recentViewCount <= 1) {
                            $fubPerson = [
                                'contacted'  => false,
                                'firstName'  => $fubUser->first ?? '',
                                'lastName'   => $fubUser->last ?? '',
                                'stage'      => 'Lead',
                                'source'     => 'website',
                                'sourceUrl'  => route('listing-detail-page2', ['slug' => $listing->slug]),
                                'emails'     => [['value' => $fubUser->email ?? '']],
                                'phones'     => [['value' => ($fubUser->phone_country_code ?? '') . ($fubUser->phone ?? '')]],
                            ];
                            if ($fubUser->followupboss_people_id) {
                                $fubPerson['id'] = $fubUser->followupboss_people_id;
                            }
                            FubAreaHelper::applyTag($fubPerson, $listing->city ?? '');
                            FubAreaHelper::saveToSession($listing->city ?? '');
                            $fubPayload = [
                                'person'   => $fubPerson,
                                'source'   => 'bccondosandhomes.com',
                                'system'   => 'website_api',
                                'type'     => 'Viewed Property',
                                'property' => [
                                    'mlsNumber' => $listing->listingid ?? '',
                                    'price'     => (int) ($price ?? 0),
                                    'street'    => trim(($listing->street_number ?? '') . ' ' . ($listing->street_name ?? '') . ' ' . ($listing->street_type ?? '')),
                                    'city'      => $listing->city ?? '',
                                    'state'     => 'BC',
                                    'code'      => $listing->postalcode ?? '',
                                    'url'       => route('listing-detail-page2', ['slug' => $listing->slug]),
                                ],
                            ];
                            $fubCurl = curl_init();
                            curl_setopt_array($fubCurl, [
                                CURLOPT_URL            => 'https://api.followupboss.com/v1/events',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_TIMEOUT        => 10,
                                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST  => 'POST',
                                CURLOPT_POSTFIELDS     => json_encode($fubPayload),
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTPHEADER     => [
                                    'accept: application/json',
                                    'authorization: Basic ' . config('services.followupboss.api_key'),
                                    'content-type: application/json',
                                ],
                            ]);
                            $fubResponse = curl_exec($fubCurl);
                            $fubError    = curl_error($fubCurl);
                            curl_close($fubCurl);
                            \Illuminate\Support\Facades\Log::info('FUB property_view', [
                                'mls'      => $listing->listingid,
                                'user'     => $fubUser->email,
                                'response' => $fubResponse,
                                'error'    => $fubError,
                            ]);
                            $fubData = json_decode($fubResponse, true);
                            if (!$fubUser->followupboss_people_id && isset($fubData['person']['id'])) {
                                $fubUser->followupboss_people_id = $fubData['person']['id'];
                                $fubUser->save();
                            }
                        }
                    }
                } catch (\Throwable $fubEx) {
                    \Illuminate\Support\Facades\Log::error('FUB property_view error: ' . $fubEx->getMessage());
                }
            }
        }
        return $listing;
    }


    public function getPlaces($searchterm)
    {
        $places =  array();
        if ($searchterm) {
            $province = 'BC';
            $country = 'CAN';
            $country2 = 'ca';
            $this->response['error'] = false;
            $this->response['success'] = true;
            $this->response['page'] = 0;
            $this->response['pages'] = 0;
            $this->response['records'] = 0;
            $this->response['results'] = array();

            $taken = array();
            $results = array();

            if (stristr($searchterm, ' ' . $province) == false) {
                $searchterm .= ', ' . $province;
            }

            $searchterm = strtolower($searchterm);

            $searchterms = trim(str_replace(array('-', '  ', '  '), array(' ', ' ', ' '), $searchterm));
            $searchterms = explode(' ', $searchterms);

            $numerics = array();

            $previous_searchterms = array();
            $previous_searchterm = '';

            // get numerics - must contain 1 of them
            $re = "/\\b([0-9]+)\\b/";
            preg_match_all($re, $searchterm, $matches);
            if (isset($matches[0])) {
                $numerics = $matches[0];
            }

            // construct the search
            $url = "http://ws1.postescanada-canadapost.ca/AddressComplete/Interactive/Find/v2.10/json.ws?";
            $url .= "&Key=" . urlencode('GF26-YU69-NC69-XF48');
            $url .= "&SearchTerm=" . urlencode($searchterm);
            $url .= "&SearchFor=" . urlencode('Places');
            $url .= "&Country=" . urlencode($country);
            $url .= "&LanguagePreference=" . urlencode('EN');
            $url .= "&MaxSuggestions=" . urlencode('7');
            $url .= "&MaxResults=" . urlencode('100');


            if ($json = $this->processURL($url, 'CP2-' . $searchterm)) {

                $this->response['url_results'] = $json;
                $this->response['numerics'] = $numerics;




                if (sizeof($json) == 1) {
                    if (isset($json[0]['Error'])) {
                        $this->response['success'] = false;
                        $this->response['error'] = $json[0];
                    }
                }

                if ($this->response['success']) {


                    $rejects = array();

                    foreach ($json as $place) {
                        if (stristr($place['Description'], $province)) {
                            // must contain 1 of the search terms
                            if ($this->arrayInString($searchterms, $place['Text'])) {
                                // must contain 1 of the numerics
                                if ($this->arrayInString($numerics, $place['Text'])) {
                                    if ($this->notInBlockedList($place['Text'])) {
                                        // must contain 1 of previous search terms
                                        if ((sizeof($previous_searchterms) == 0) || ($this->arrayInString($previous_searchterms, $place['Text']))) {

                                            list($cpp, $addresses) = explode("-", $place['Description']);
                                            list($city, $prov, $postalcode) = explode(",", trim($cpp));

                                            if (stristr($place['Text'], '-')) {
                                                list($suite, $address) = explode("-", $place['Text']);
                                            } else {
                                                $suite = '';
                                                $address = $place['Text'];
                                            }


                                            $result = array();
                                            $result['id'] = trim($place['Id']);
                                            $result['display'] = trim($place['Text']) . ', ' . trim($city);
                                            $result['type'] = 'search';

                                            $query = array();
                                            $query['address'] = trim($address);
                                            $query['postalcode'] = trim($postalcode);
                                            $query['postalarea'] = substr(trim($postalcode), 0, 3);
                                            $query['city'] = trim($city);
                                            $result['query'] = $query;
                                            print_r($result);

                                            if (!in_array($result['display'], $taken)) {
                                                $results[] = $result;
                                                $taken[] = $result['display'];
                                            }
                                            $places = $results;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $places;
    }

    public function processURL($url, $cacheid)
    {

        $cacheid = trim(strtolower($cacheid));

        $return = false;
        $error = false;
        $data = false;

        // do we need to access MySQL ?
        if (!$data) {

            if ($data = file_get_contents($url)) {

                if ($json = json_decode($data, true)) {

                    if (sizeof($json) == 1) {
                        if (isset($json[0]['Error'])) {
                            $error = true;
                        }
                    }

                    // google
                    if (isset($json['status'])) {
                        if ($json['status'] != 'OK') {
                            $error = true;
                        }
                    }

                    if (!$error) {
                        // save to memcache
                        //                        if ($cacheAvailable == true) {
                        //                            $memcache->set($cacheid, $data, 0, 86400); // 1 day
                        //                        }
                        $return = $json;
                    }
                }
            }
        } else {
            //$this->response['cacheid'] = $cacheid;
            if ($json = json_decode($data, true)) {
                $return = $json;
            }
        }

        //$this->response['data'] = $data;

        return $return;
    }

    public function arrayInString($arr, $text)
    {
        foreach ($arr as $find) {
            $re = "/\\b" . $find . "\\b/";
            if (preg_match($re, $text, $matches)) {
                return true;
            }
        }
        return false;
    }

    public function notInBlockedList($text)
    {
        // filter out blocked addresses
        if (stristr($text, 'po box')) {
            return false;
        } else {
            return true;
        }
    }
}
