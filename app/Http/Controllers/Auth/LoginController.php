<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Agents;
use App\Repository\FirebaseRepository;
use Illuminate\Http\Request;
use App\Models\Auth\FirebaseUser;
use Illuminate\Support\Facades\Auth;
use JsValidator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cookie;
use App\Helpers\FubAreaHelper;
use App\Mail\AgentsUserSignup;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Twilio\Rest\Client;
use App\Models\UserChangesLogs;
use Browser;
use App\Models\Listings;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    private $firebaseRepository;
    private $completeProfileValidationBackend = [
        'first_name' => 'required|string|max:20',
        'last_name' => 'required|string|max:20',
        'email' => 'required|email|max:50',
        'agreePrivacyNotice' => 'required',
        'agreeDisclosure' => 'required',
    ];

    private $completeProfileValidationBackendDemo = [
        'first_name' => 'required|string|max:20',
        'last_name' => 'required|string|max:20',
        'email' => 'required|email|max:50',
        'agreePrivacyNotice' => 'required',
        'agreeDisclosure' => 'required',
    ];

    private $completeProfileValidationFrontend = [
        'first_name' => 'required|string|max:20',
        'last_name' => 'required|string|max:20',
        'email' => 'required|email|max:50',
        'agreePrivacyNotice' => 'required',
        'agreeDisclosure' => 'required',
        // 'user_detail'=>'required',
        // 'working_with_realtor'=>'required'
    ];

    private $completeProfileValidationFrontendDemo = [
        'first_name' => 'required|string|max:20',
        'last_name' => 'required|string|max:20',
        'email' => 'required|email|max:50',
        'agreePrivacyNotice' => 'required',
        'agreeDisclosure' => 'required',
    ];

    public function __construct(FirebaseRepository $firebaseRepository)
    {
        $this->firebaseRepository = $firebaseRepository;
    }

    public function handle_auth()
    {
        $request = request();
        $token = $request->get('token');
        $ref = NULL;
        $allParams = $request->all();
        if (isset($allParams['token'])) {
            unset($allParams['token']);
        }
        if (isset($allParams['f'])) {
            unset($allParams['f']);
        }
        if ($request->get('ref')) {
            $ref = $request->get('ref');
        }
        $user = $this->firebaseRepository->verifyToken($token);
        if ($user && $user->emailVerified) {
            $dbUser = $this->findOrCreateUser($user, $ref, $allParams);
            Auth::login($dbUser);
            $dbUser->last_login = Carbon::now();
            $dbUser->activated = 1;
            if ($user->photoUrl) {
                $dbUser->profile_image = $user->photoUrl;
            }
            $dbUser->save();
            Cookie::queue(Cookie::make('user_id', $dbUser->id, 2628000));
            Cookie::queue(Cookie::make('bcc_needs_otp', $dbUser->phone_verified ? '0' : '1', 60*24*365, '/', null, false, false));
            Cookie::queue(Cookie::make('bcc_auth', '1', 60*24*365, '/', null, false, false));
            $__bcc_sub = $dbUser->isOnTrial() ? 'trial' : ($dbUser->isPremiumMember() ? 'premium' : 'upgrade');
            Cookie::queue(Cookie::make('bcc_sub', $__bcc_sub, 60*24*365, '/', null, false, false));

            // $user_agent = request()->header('user-agent');
            // $header = json_encode(request()->header());
            // DB::insert("insert into bccondosandhomes.login_logout_logs (action, userid, user_agent, headers) values ('login', ".$dbUser->id.", '".$user_agent."', '".$header."')");
            
            if (is_array($allParams) && array_key_exists('listingid', $allParams)) {
                $listing = Listings::where('listingid', $allParams['listingid'])->first();
                $intendedUrl = $listing
                    ? route('listing-detail-page2', ['slug' => $listing->slug])
                    : redirect()->intended(route('landing', $allParams))->getTargetUrl();
            } elseif (is_array($allParams) && array_key_exists('redirect', $allParams)) {
                $intendedUrl = $allParams['redirect'];
            } else {
                $intendedUrl = redirect()->intended(route('landing', $allParams))->getTargetUrl();
            }
            if (!$dbUser->phone_verified) {
                return $this->jsRedirect(url('/complete-profile') . '?' . http_build_query(['redirect' => $intendedUrl]));
            }
            return $this->jsRedirect($intendedUrl);
        } elseif ($user && !$user->emailVerified) {
            $dbUser = $this->findOrCreateUser($user, $ref, $allParams);
            if ($user->photoUrl) {
                $dbUser->profile_image = $user->photoUrl;
            }
            $dbUser->save();
            Auth::login($dbUser);
            usleep(6000);
            $verifyParams = isset($allParams['redirect']) ? ['redirect' => $allParams['redirect']] : [];
            $this->firebaseRepository->sendVerificationEmail($user->uid, null);
            Cookie::queue(Cookie::make('user_id', $dbUser->id, 2628000));
            // $user_agent = request()->header('user-agent');
            // $header = json_encode(request()->header());
            // DB::insert("insert into bccondosandhomes.login_logout_logs (action, userid, user_agent, headers) values ('login', ".$dbUser->id.", '".$user_agent."', '".$header."')");
            return $this->jsRedirect(route('verify-email', $verifyParams));
        } else {
            return redirect(route('landing'));
        }
    }

    public function handleAuthJson()
    {
        $request = request();
        $data = $request->json()->all();
        $token = $data['token'] ?? null;
        $redirectUrl = $data['redirect'] ?? null;

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Missing token.'], 400)
                ->header('Cache-Control', 'no-store');
        }

        $user = $this->firebaseRepository->verifyToken($token);

        if ($user && $user->emailVerified) {
            $dbUser = $this->findOrCreateUser($user, null, []);
            Auth::login($dbUser);
            $dbUser->last_login = Carbon::now();
            $dbUser->activated = 1;
            if ($user->photoUrl) {
                $dbUser->profile_image = $user->photoUrl;
            }
            $dbUser->save();
            Cookie::queue(Cookie::make('user_id', $dbUser->id, 2628000));
            Cookie::queue(Cookie::make('bcc_needs_otp', $dbUser->phone_verified ? '0' : '1', 60*24*365, '/', null, false, false));
            Cookie::queue(Cookie::make('bcc_auth', '1', 60*24*365, '/', null, false, false));
            $__bcc_sub = $dbUser->isOnTrial() ? 'trial' : ($dbUser->isPremiumMember() ? 'premium' : 'upgrade');
            Cookie::queue(Cookie::make('bcc_sub', $__bcc_sub, 60*24*365, '/', null, false, false));
            session()->save();

            if ($redirectUrl) {
                $finalUrl = $redirectUrl;
            } else {
                $finalUrl = route('landing');
            }

            if (!$dbUser->phone_verified) {
                $finalUrl = url('/complete-profile') . '?' . http_build_query(['redirect' => $finalUrl]);
            }

            return response()->json(['success' => true, 'redirect' => $finalUrl])
                ->header('Cache-Control', 'no-store');

        } elseif ($user && !$user->emailVerified) {
            $dbUser = $this->findOrCreateUser($user, null, []);
            if ($user->photoUrl) {
                $dbUser->profile_image = $user->photoUrl;
            }
            $dbUser->save();
            Auth::login($dbUser);
            usleep(6000);
            $this->firebaseRepository->sendVerificationEmail($user->uid, null);
            Cookie::queue(Cookie::make('user_id', $dbUser->id, 2628000));
            session()->save();

            $verifyParams = $redirectUrl ? ['redirect' => $redirectUrl] : [];
            $finalUrl = route('verify-email', $verifyParams);

            return response()->json(['success' => true, 'redirect' => $finalUrl])
                ->header('Cache-Control', 'no-store');

        } else {
            return response()->json(['success' => false, 'message' => 'Authentication failed.'], 401)
                ->header('Cache-Control', 'no-store');
        }
    }

    private function jsRedirect(string $url): \Illuminate\Http\Response
    {
        session()->save();

        return response()
            ->view('auth.js_redirect', ['url' => $url])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('Pragma', 'no-cache');
    }

    public function findOrCreateUser($user, $ref = NULL, $allParams = null)
    {
        $phone = null;
        $authUser = FirebaseUser::where('uid', $user->uid)->first();

        if ($authUser) {
            return $authUser;
        }

        $splitName = $this->split_name($user->displayName);
        $signup_country = NULL;
        if (array_key_exists('HTTP_CF_IPCOUNTRY', $_SERVER)) {
            $signup_country = $_SERVER['HTTP_CF_IPCOUNTRY'];
        }
        $device = "";
        if (Browser::isMobile()) {
            $device = "Mobile";
        } elseif (Browser::isTablet()) {
            $device = "Tablet";
        } elseif (Browser::isDesktop()) {
            $device = "Desktop";
        }
        $user_agent = request()->header('user-agent');
        $ref_details = null;
        if ($allParams) {
            $ref_details = json_encode($allParams);
        }

        $newUser =  FirebaseUser::create([
            'uid'     => $user->uid,
            'email'    => $user->email ? $user->email : '',
            'first' => $splitName[0],
            'last' => $splitName[1],
            'signup_ip' => $this->get_client_ip(),
            'signup_country' => $signup_country,
            'role' => 'USER',
            'ref' => $ref,
            'user_agent' => $user_agent,
            'device' => $device,
            'ref_details' => $ref_details
        ]);

        return $newUser;
    }

    public function agreeTerms()
    {
        $user = Auth::user();
        $user->agreedToTerms = Carbon::now();
        $user->save();
        echo "success";
    }

    public function get_client_ip()
    {

        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public function split_name($name)
    {
        $name = trim(html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (strpos($name, ' ') === false) {
            return array($name, '');
        }
        $parts = explode(' ', $name);
        $last_name = array_pop($parts);
        $first_name = implode(' ', $parts);
        return array($first_name, $last_name);
    }

    public function step2()
    {
        $user = Auth::user();
        $validator = JsValidator::make($this->completeProfileValidationFrontend);
        return view('frontend.user.complete_profile')->with(
            [
                'user' => $user,
                'validator' => $validator
            ]
        );
    }


    public function verifyEmail()
    {
        $user = Auth::user();
        $redirect = request()->get('redirect') ?: session('url.intended', route('landing'));
        if ($user && $user->phone && $user->phone_verified) {
            $next_url = $redirect;
        } else {
            $next_url = url('/complete-profile') . '?' . http_build_query(['redirect' => $redirect]);
        }
        return view('frontend.user.verify_email')->with(
            [
                'next_url' => $next_url
            ]
        );
    }

    public function resendVerificationEmail()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect(route('login.with.agent'));
        }
        $resentAt = session('verification_resent_at');
        if ($resentAt && (now()->timestamp - $resentAt) < 60) {
            return redirect(route('verify-email', ['redirect' => request()->get('redirect'), 'too_soon' => 1]));
        }
        $redirect = request()->get('redirect');
        $verifyParams = $redirect ? ['redirect' => $redirect] : [];
        $this->firebaseRepository->sendVerificationEmail($user->uid, null);
        session(['verification_resent_at' => now()->timestamp]);
        return redirect(route('verify-email', array_merge($verifyParams, ['resent' => 1])));
    }

    public function agentLandingPage($agentUsername)
    {
        $request = request();
        $agent = null;

        $currentUrl = $request->url();
        $lastChar = substr($currentUrl, -1);
        if ($lastChar == '/' || strpos($currentUrl, '/public') !== false) {
            return redirect(rtrim(str_replace('/public', '', $currentUrl), '/'));
        }

        $agent = Agents::where('vow_username', $agentUsername)->where('fisherly_disable', 0)->where(function ($query) {
            $query->where(function ($q1) {
                $q1->where("agent_id", config('constants.demo_agent_id'));
            })->orWhere(function ($q) {
                $q
                    ->where('activated', 'Y')->where('suspended', 'n')
                    ->whereNotNull('mlsID')
                    ->where('mlsID', '!=', '')
                    ->whereIn('board', array(1, 9, 10));
            });
        })->first();

        if (!$agent) {
            abort(404);
        }

        $agentId = $agent->agent_id;
        $agent_mlsID = $agent->mlsID;
        $is_authenticated = false;
        $user = false;
        $agency = null;

        if (Auth::user()) {
            $user = Auth::user();
            if ($user->role == 'AGENT') {
                $is_authenticated = true;
            } elseif ($user->phone) {
                $is_authenticated = true;
            }
        }

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
            $active_listings = Listings::where(function ($q) use ($agent) {
                $q->where('agent_id', $agent->mlsID);
                $q->orWhere('agent2_id', $agent->mlsID);
                $q->orWhere('agent3_id', $agent->mlsID);
                if ($agent->mlsID2) {
                    $q->orWhere('agent_id', $agent->mlsID2);
                    $q->orWhere('agent2_id', $agent->mlsID2);
                    $q->orWhere('agent3_id', $agent->mlsID2);
                }
                if ($agent->mlsID3) {
                    $q->orWhere('agent_id', $agent->mlsID3);
                    $q->orWhere('agent2_id', $agent->mlsID3);
                    $q->orWhere('agent3_id', $agent->mlsID3);
                }
            })->with('photos')->where(function ($q) {
                $q->where('status', 'Active');
            })->orderBy('list_date', 'DESC')->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))
                ->paginate($per_page);

            $sold_listings = Listings::where(function ($q) use ($agent) {
                $q->where('agent_id', $agent->mlsID);
                $q->orWhere('agent2_id', $agent->mlsID);
                $q->orWhere('agent3_id', $agent->mlsID);
                if ($agent->mlsID2) {
                    $q->orWhere('agent_id', $agent->mlsID2);
                    $q->orWhere('agent2_id', $agent->mlsID2);
                    $q->orWhere('agent3_id', $agent->mlsID2);
                }
                if ($agent->mlsID3) {
                    $q->orWhere('agent_id', $agent->mlsID3);
                    $q->orWhere('agent2_id', $agent->mlsID3);
                    $q->orWhere('agent3_id', $agent->mlsID3);
                }
            })->with('photos')->where(function ($q) {
                $q->where('status', 'Sold');
            })->orderBy('list_date', 'DESC')->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))
                ->paginate($per_page);


            $first_active_listing = Listings::where(function ($q) use ($agent) {
                $q->where('agent_id', $agent->mlsID);
                $q->orWhere('agent2_id', $agent->mlsID);
                $q->orWhere('agent3_id', $agent->mlsID);
                if ($agent->mlsID2) {
                    $q->orWhere('agent_id', $agent->mlsID2);
                    $q->orWhere('agent2_id', $agent->mlsID2);
                    $q->orWhere('agent3_id', $agent->mlsID2);
                }
                if ($agent->mlsID3) {
                    $q->orWhere('agent_id', $agent->mlsID3);
                    $q->orWhere('agent2_id', $agent->mlsID3);
                    $q->orWhere('agent3_id', $agent->mlsID3);
                }
            })->where(function ($q) {
                $q->where('status', 'Active');
            })->orderBy('list_date', 'DESC')->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))
                ->first();
            if ($first_active_listing) {
                $agency = $first_active_listing->reoffice;
            }

            if (!$agency) {
                $first_sold_listing = Listings::where(function ($q) use ($agent) {
                    $q->where('agent_id', $agent->mlsID);
                    if ($agent->mlsID2) {
                        $q->orWhere('agent_id', $agent->mlsID2);
                    }
                    if ($agent->mlsID3) {
                        $q->orWhere('agent_id', $agent->mlsID3);
                    }
                })->where(function ($q) {
                    $q->where('status', 'Sold');
                })->orderBy('list_date', 'DESC')->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))
                    ->first();
                if ($first_sold_listing) {
                    $agency = $first_sold_listing->reoffice;
                }
            }

            if (!$agency) {
                $agency = $agent->agency;
            }

            $office_listings = Listings::where('reoffice', trim($agency))->with('photos')->where(function ($q) {
                $q->where('status', 'Active');
            })->orderBy('list_date', 'DESC')->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))
                ->paginate($per_page);

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

                $office_listings_count = count($office_listings);
                for ($i = 0; $i < $office_listings_count; $i++) {
                    if (($i + 1) > $remainig_properties) {
                        unset($office_listings[$i]);
                    }
                }
            }
        }

        $active_count = Listings::where(function ($q) use ($agent) {
            $q->where('agent_id', $agent->mlsID);
            $q->orWhere('agent2_id', $agent->mlsID);
            $q->orWhere('agent3_id', $agent->mlsID);
            if ($agent->mlsID2) {
                $q->orWhere('agent_id', $agent->mlsID2);
                $q->orWhere('agent2_id', $agent->mlsID2);
                $q->orWhere('agent3_id', $agent->mlsID2);
            }
            if ($agent->mlsID3) {
                $q->orWhere('agent_id', $agent->mlsID3);
                $q->orWhere('agent2_id', $agent->mlsID3);
                $q->orWhere('agent3_id', $agent->mlsID3);
            }
        })->with('photos')->where(function ($q) {
            $q->where('status', 'Active');
        })->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))
            ->count();

        $sold_count = Listings::where(function ($q) use ($agent) {
            $q->where('agent_id', $agent->mlsID);
            $q->orWhere('agent2_id', $agent->mlsID);
            $q->orWhere('agent3_id', $agent->mlsID);
            if ($agent->mlsID2) {
                $q->orWhere('agent_id', $agent->mlsID2);
                $q->orWhere('agent2_id', $agent->mlsID2);
                $q->orWhere('agent3_id', $agent->mlsID2);
            }
            if ($agent->mlsID3) {
                $q->orWhere('agent_id', $agent->mlsID3);
                $q->orWhere('agent2_id', $agent->mlsID3);
                $q->orWhere('agent3_id', $agent->mlsID3);
            }
        })->with('photos')->where(function ($q) {
            $q->where('status', 'Sold');
        })->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))
            ->count();

        $office_count = Listings::where('reoffice', trim($agency))->with('photos')->where(function ($q) {
            $q->where('status', 'Active');
        })->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))
            ->count();

        $lastUpdate = Listings::max('updated');

        return view('frontend.realtorpage')->with(
            [
                'agentId' => $agentId,
                'agent' => $agent,
                'active_listings' => $active_listings,
                'sold_listings' => $sold_listings,
                'office_listings' => $office_listings,
                'is_authenticated' => $is_authenticated,
                'user' => $user,
                'last_update' => $lastUpdate,
                'active_count' => $active_count,
                'sold_count' => $sold_count,
                'office_count' => $office_count
            ]
        );
    }

    public function loginPage()
    {
        $request = request();

        $currentUrl = $request->url();
        $lastChar = substr($currentUrl, -1);
        if ($lastChar == '/' || strpos($currentUrl, '/public') !== false) {
            return redirect(rtrim(str_replace('/public', '', $currentUrl), '/'));
        }


        if (Auth::user()) {
            return redirect()->intended(route('landing'));
        }

        return view('frontend.user.login');
    }

    public function completeProfile()
    {
        $request = request();
        $allParams = $request->all();
        if (isset($allParams['_token'])) {
            unset($allParams['_token']);
        }
        if (isset($allParams['first_name'])) {
            unset($allParams['first_name']);
        }
        if (isset($allParams['last_name'])) {
            unset($allParams['last_name']);
        }
        if (isset($allParams['email'])) {
            unset($allParams['email']);
        }
        if (isset($allParams['phone'])) {
            unset($allParams['phone']);
        }
        if (isset($allParams['agreePrivacyNotice'])) {
            unset($allParams['agreePrivacyNotice']);
        }
        if (isset($allParams['agreeDisclosure'])) {
            unset($allParams['agreeDisclosure']);
        }
        if (isset($allParams['agreeTermsAndConditions'])) {
            unset($allParams['agreeTermsAndConditions']);
        }
        if (isset($allParams['country_code'])) {
            unset($allParams['country_code']);
        }
        if (isset($allParams['phone_verified'])) {
            unset($allParams['phone_verified']);
        }
        if (isset($allParams['token'])) {
            unset($allParams['token']);
        }
        if (isset($allParams['f'])) {
            unset($allParams['f']);
        }
        $user = Auth::user();
        $validatedData = $request->validate($this->completeProfileValidationBackend);

        $user->first = $request->post('first_name');
        $user->last = $request->post('last_name');
        $user->email = $request->post('email');
        $user->phone_country_code = $request->post('country_code');
        $user->phone = $request->post('phone');
        $user->agreePrivacyNotice = Carbon::now();
        $user->agreeDisclosure = Carbon::now();
        $user->agreedToTerms = Carbon::now();

        // if($request->post('user_detail')){
        //     $user->client_type = $request->post('user_detail');
        // }

        // if($request->post('working_with_realtor')){
        //     $user->work_with_realtor = $request->post('working_with_realtor');
        // }

        $user_subscription = Subscription::where('user_stripe_email', $user->email)->first();

        if($user_subscription){
            $user->stripe_id = $user_subscription->user_stripe_id;
            $user_subscription->firebase_user_id = $user->id;
            $user_subscription->save();
        }

        $user->save();

        if (is_array($allParams) && array_key_exists('listingid', $allParams)) {
            $listing = Listings::where('listingid', $allParams['listingid'])->first();
            if ($listing) {
                return redirect(route('listing-detail-page2', ['slug' => $listing->slug]));
            }
        }
        $redirectUrl = $request->input('redirect') ?: $request->query('redirect');
        if ($redirectUrl) {
            $appHost = preg_replace('/^www\./', '', parse_url(config('app.url'), PHP_URL_HOST) ?? '');
            $targetHost = preg_replace('/^www\./', '', parse_url($redirectUrl, PHP_URL_HOST) ?? '');
            $isSameOrigin = $targetHost === '' || $targetHost === $appHost;
            if ($isSameOrigin) {
                return redirect($redirectUrl);
            }
        }
        session()->forget('url.intended');
        return redirect('/mapsearch');
    }

    public function logout()
    {
        $request = request();
        $user = Auth::user();
        Cookie::queue(Cookie::forget('user_id'));
        Cookie::queue(Cookie::forget('bcc_auth'));
        Cookie::queue(Cookie::forget('bcc_sub'));
        Cookie::queue(Cookie::forget('bcc_needs_otp'));

        $landingPage = route('landing');
        $this->firebaseRepository->logout($user);

        if($user){
            // $user_agent = request()->header('user-agent');
            // $header = json_encode(request()->header());
            // DB::insert("insert into bccondosandhomes.login_logout_logs (action, userid, user_agent, headers) values ('logout', ".$user->id.", '".$user_agent."', '".$header."')");
        }

        Auth::logout();

        return redirect($landingPage);
    }


    public function confirmPhoneNumber()
    {
        $user = Auth::user();
        $redirect = request()->get('redirect') ?: session('url.intended', route('landing'));
        if ($user->phone && $user->phone_verified) {
            return redirect($redirect);
        }
        //$validator = JsValidator::make(['phone' => 'required']);
        return view('frontend.user.confirm_phone_number')->with([
            'user' => $user,
           //'validator' => $validator,
            'next_url' => $redirect
        ]);
    }

    public function postConfirmPhoneNumber()
    {
        $request = request();
        $user = Auth::user();
        $action = "";
        $success = false;
        $sid    = config('services.twilio.sid');
        $token  = config('services.twilio.token');
        if ($request->get('action')) {
            $action = $request->get('action');
        }
        \Log::info('[OTP] postConfirmPhoneNumber called, action=' . $action . ', user=' . ($user ? $user->id : 'none'));
        if ($action == 'change_number') {
            $validator = Validator::make($request->all(), [
                'number' => 'required|phone1|min:5|numeric',
                'country_code' => 'required'
            ]);
            if (!$validator->fails()) {
                $number = $request->post('number');
                $country_code = $request->post('country_code');
                if ($number != $user->phone || $country_code != $user->phone_country_code) {
                    $prev_number = $user->phone_country_code . $user->phone;
                    $user->phone = $number;
                    $user->phone_country_code = $country_code;
                    $user->phone_verified = 0;
                    $user->save();
                    UserChangesLogs::create([
                        'userid' => $user->id,
                        'role' => 'USER',
                        'activity_type' => 'update',
                        'activity' => $action,
                        'prev_value' => $prev_number,
                        'new_value' => $country_code . $number
                    ]);
                }
                $phone = $user->phone_country_code . $user->phone;
                $twilio = new Client($sid, $token);
                try {
                    $verification = $twilio->verify->v2->services("VAb40c789d5dacd8e5dd558f1dca6b834c")
                        ->verifications
                        ->create($phone, "sms");
                    if ($verification->sid) {
                        $user->phone_verification_sid = $verification->sid;
                        $user->save();
                        $success = true;
                    }
                    $success = true;
                } catch (\Exception $e) {
                    $success = false;
                }
            }
        } elseif ($action == 'send_verification_code') {
            $validator = Validator::make($request->all(), [
                'number' => 'required|phone1',
                'country_code' => 'required'
            ]);
            if (!$validator->fails()) {
                $number = $request->post('number');
                $country_code = $request->post('country_code');
                //if ($number != $user->phone || $country_code != $user->phone_country_code) {
                $prev_number = $user->phone_country_code . $user->phone;
                $user->phone = $number;
                $user->phone_country_code = $country_code;
                $user->phone_verified = 0;
                $user->save();
                UserChangesLogs::create([
                    'userid' => $user->id,
                    'role' => 'USER',
                    'activity_type' => 'update',
                    'activity' => $action,
                    'prev_value' => $prev_number,
                    'new_value' => $country_code . $number
                ]);
                $phone = $user->phone_country_code . $user->phone;
                $twilio = new Client($sid, $token);
                try {
                    $verification = $twilio->verify->v2->services("VAb40c789d5dacd8e5dd558f1dca6b834c")
                        ->verifications
                        ->create($phone, "sms");
                    if ($verification->sid) {
                        $user->phone_verification_sid = $verification->sid;
                        $user->save();
                        $success = true;
                    }
                } catch (\Exception $e) {
                    $success = false;
                }
                //}
            }
        } elseif ($action == 'verify_code') {
            $twilio = new Client($sid, $token);
            $code = $request->post('code');
            $verificationSid = $user->phone_verification_sid;
            try {
                $verification_check = $twilio->verify->v2->services("VAb40c789d5dacd8e5dd558f1dca6b834c")
                    ->verificationChecks
                    // ->create($code, [
                    //     'verificationSid' => $verificationSid
                    // ]);
                    ->create(
                        [
                         "to" => $user->phone_country_code . $user->phone,
                         "code" => $code,
                        ]
                        );

                if ($verification_check->sid) {
                    if ($verification_check->status == 'approved' && $verification_check->valid) {
                        $success = true;
                        $user->phone_verified = 1;
                        Cookie::queue(Cookie::make('bcc_needs_otp', '0', 60*24*365, '/', null, false, false));

                        // Phone-based trial dedup: one trial per phone number, regardless of email.
                        // If another verified account already used a trial with this phone,
                        // copy the earliest trial_end_date so this account cannot get a fresh trial.
                        if ($user->phone && !$user->trial_start_date) {
                            $phoneOrigUser = \App\Models\Auth\FirebaseUser::where('phone', $user->phone)
                                ->where('phone_verified', '1')
                                ->where('id', '!=', $user->id)
                                ->where('phone', '!=', '')
                                ->whereNotNull('phone')
                                ->whereNotNull('trial_end_date')
                                ->orderBy('trial_end_date', 'asc')
                                ->first();
                            if ($phoneOrigUser) {
                                $user->trial_start_date = $phoneOrigUser->trial_start_date;
                                $user->trial_end_date   = $phoneOrigUser->trial_end_date;
                            }
                        }

                        UserChangesLogs::create([
                            'userid' => $user->id,
                            'role' => 'USER',
                            'activity_type' => 'update',
                            'activity' => 'verify_number',
                            'prev_value' => '0',
                            'new_value' => '1'
                        ]);
                        $user->save();
                        try {
                            $fubPerson = [
                                'contacted'  => false,
                                'firstName'  => $user->first,
                                'lastName'   => $user->last,
                                'stage'      => 'Lead',
                                'source'     => 'website',
                                'sourceUrl'  => url('/'),
                                'emails'     => [['value' => $user->email]],
                                'phones'     => [['value' => ($user->phone_country_code ?? '') . ($user->phone ?? '')]],
                            ];
                            if ($user->followupboss_people_id) {
                                $fubPerson['id'] = $user->followupboss_people_id;
                            }
                            FubAreaHelper::applyTagFromSession($fubPerson);
                            $fubPayload = [
                                'person' => $fubPerson,
                                'source' => 'bccondosandhomes.com',
                                'system' => 'website_api',
                                'type'   => 'Registration',
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
                                CURLOPT_SSL_VERIFYPEER => false,
                                CURLOPT_SSL_VERIFYHOST => false,
                                CURLOPT_HTTPHEADER     => [
                                    'accept: application/json',
                                    'authorization: Basic ' . config('services.followupboss.api_key'),
                                    'content-type: application/json',
                                ],
                            ]);
                            $fubResponse = curl_exec($fubCurl);
                            $fubCurlError = curl_error($fubCurl);
                            curl_close($fubCurl);
                            if ($fubCurlError) {
                                \Log::error('[FUB sign-up] cURL error: ' . $fubCurlError);
                            } else {
                                \Log::info('[FUB sign-up] response: ' . $fubResponse);
                            }
                            $fubData = json_decode($fubResponse, true);
                            if (!$user->followupboss_people_id && isset($fubData['person']['id'])) {
                                $user->followupboss_people_id = $fubData['person']['id'];
                                $user->save();
                            }
                        } catch (\Exception $e) {
                            \Log::error('[FUB sign-up] exception: ' . $e->getMessage());
                        }
                    }
                }
            } catch (\Exception $e) {
                $success = false;
                \Log::error('[OTP verify] Twilio exception: ' . $e->getMessage());
            }
        }
        $response = [
            'success' => $success
        ];
        return response()->json($response);
    }
}
