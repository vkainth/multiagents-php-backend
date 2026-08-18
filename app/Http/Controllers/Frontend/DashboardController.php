<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repository\ListingRepository;
use App\Models\CityButtonClicks;
use Illuminate\Support\Facades\DB;
use App\Models\Listings;
use Carbon\Carbon;

class DashboardController extends Controller
{
    //

    protected $listingRepo;

    public function __construct(ListingRepository $listingRepo)
    {
        $this->listingRepo = $listingRepo;
    }

    public function seoUrl($string)
    {
        //Lower case everything
        $string = strtolower($string);
        //Make alphanumeric (removes all other characters)
        $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
        //Clean up multiple dashes or whitespaces
        $string = preg_replace("/[\s-]+/", " ", $string);
        //Convert whitespaces and underscore to dash
        $string = preg_replace("/[\s_]/", "-", $string);
        return $string;
    }


    public function get_featured_listings()
    {
        $request = request();

        $per_page = 12;
        $max_results = 100;
        $page = 1;
        $max_pages = ceil($max_results / $per_page);
        $properties_sent = 0;

        if ($request->get('page') > 0) {
            $page = $request->get('page');
            $properties_sent = ($page - 1) * $per_page;
        }

        if ($page > $max_pages) {
            $active_listings = array();
            $sold_listings = array();
            $office_listings = array();
        } else {
            $active_listings = Listings::with('photos')->where(function ($q) {
                $q->where('status', 'Active');
            })->orderBy('list_date', 'DESC')->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))
                ->paginate($per_page);

            $sold_listings = Listings::with('photos')->where(function ($q) {
                $q->where('status', 'Sold');
            })->orderBy('list_date', 'DESC')->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))
                ->paginate($per_page);
        }

        if ($page == $max_pages) {

                $remainig_properties = $max_results - $properties_sent;

                $active_listings_count = count($active_listings);
                for ($i = 0; $i < $active_listings_count; $i++) {
                    if (($i + 1) > $remainig_properties) {
                        unset($active_listings[$i]);
                    }
                }

                $sold_listings_count = count($sold_listings);
                for ($i = 0; $i < $sold_listings_count; $i++) {
                    if (($i + 1) > $remainig_properties) {
                        unset($sold_listings[$i]);
                    }
                }
        }

        $active_count = Listings::with('photos')->where(function ($q) {
            $q->where('status', 'Active');
        })->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))
            ->count();

        $sold_count = Listings::with('photos')->where(function ($q) {
            $q->where('status', 'Sold');
        })->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))
            ->count();

        $lastUpdate = Listings::max('updated');

        /*$active_listings = Listings::with('photos')->where(function ($q) {
                $q->where('status', 'Active');
            })->orderBy('list_date', 'DESC')->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'));*/

        /*$listings = Listings::with('photos')->where('table', 'mlsr_listings')->where(function ($q) {
                    $q->where('status', 'Active')->orWhere('status', 'Sold');*/

        return view('frontend.featured_listings')->with([
            'active_listings' => $active_listings,
            'sold_listings' => $sold_listings,
            'last_update' => $lastUpdate,
            'active_count' => $active_count,
            'sold_count' => $sold_count
        ]);

    }

    function storeClickEvent()
    {
        $request = request();
        $type = NULL;
        $value = NULL;
        $type = $request->post('type');
        $value = $request->post('value');
        $user = Auth::user();
        if ($type && $value) {
            CityButtonClicks::create([
                'userid' => $user->id,
                'type' => $type,
                'value' => $value
            ]);
        }
    }

    public function getPlaces()
    {
        $request = request();
        $term = $request->get('term');
        $places = $this->listingRepo->getPlaces($term);
    }

    public function mapPage()
    {
        $user = Auth::user();
        $agent = null;
        $page_content = file_get_contents(public_path() . "/dev/map/index.html");
        $logoutScript = '<script>
(function(){
  var _auth=firebase.auth();
  var _orig=_auth.signOut.bind(_auth);
  _auth.signOut=function(){
    fetch("/logout",{method:"GET",credentials:"same-origin",redirect:"follow"}).catch(function(){});
    return _orig();
  };
})();
</script>';
        return response(str_replace("<intercom_script></intercom_script>", $logoutScript, $page_content))
            ->header('Content-Type', 'text/html');
    }

}
