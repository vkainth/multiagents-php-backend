<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SavedSearches;
use App\Models\FavoriteListings;
use App\Models\ShowingRequests;
use App\Repository\FirebaseRepository;
use App\Models\Auth\FirebaseUser;
use App\Models\Listings;
use Illuminate\Support\Facades\Session;
use function GuzzleHttp\json_encode;
use App\Models\Agents;
use App\Mail\RequestShowing;
use App\Models\EmailsSent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Helpers\FubAreaHelper;
use App\Mail\RequestShowingUserConfirmation;
use App\Mail\AskQuestion;
use App\Models\UserQuestions;
use App\Models\UserActivities;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
        //

        protected $firebaseRepo;
        protected $app;
        protected $connection = 'mysql';

        public function __construct(FirebaseRepository $firebaseRepo)
        {
                $this->firebaseRepo = $firebaseRepo;
        }

        public function save_search()
        {
                $request = request();
                $success = true;
                $message = "";
                $user = Auth::user();
                if ($user) {
                        $user_id = $user->id;
                        $body_content = json_decode($request->getContent());
                        if ($body_content && property_exists($body_content, 'search_name') && property_exists($body_content, 'data')) {
                                $search_name = $body_content->search_name;
                                $data = $body_content->data;
                                $listing_sql = "";
                                $search_url = "";
                                if (property_exists($body_content, 'listing_sql')) {
                                        $listing_sql = $body_content->listing_sql;
                                }
                                if (property_exists($body_content, 'search_url')) {
                                        $search_url = $body_content->search_url;
                                }
                                if (property_exists($body_content, 'daily_email') && $body_content->daily_email == 1) {
                                        $daily_email = 1;
                                } else {
                                        $daily_email = 0;
                                }
                                $existing = SavedSearches::where('search_name', $search_name)->where('userid', $user_id)->count();
                                if ($existing) {
                                        $success = false;
                                        $message = "Search is already saved with the same name";
                                } else {
                                        $record = SavedSearches::create([
                                                'userid' => $user_id,
                                                'search_name' => $search_name,
                                                'data' => json_encode($data),
                                                'daily_email' => $daily_email,
                                                'listing_sql' => $listing_sql,
                                                'search_url' => $search_url
                                        ]);
                                        if ($record && $daily_email) {
                                                \App\Services\AlertWebhookService::dispatch(
                                                    'subscription.created', 'search', $record->toArray()
                                                );
                                        }
                                }
                        } else {
                                $success = false;
                                $message = "search_name and data is required";
                        }
                } else {
                        $success = false;
                        $message = "Not Authorized";
                }
                $response = [
                        'success' => $success,
                        'message' => $message
                ];
                return response()->json($response);
        }

        public function get_searches()
        {
                $success = true;
                $message = "";
                $user = Auth::user();
                if ($user) {
                        $searches = SavedSearches::where('userid', $user->id)->get()->toArray();
                        $response = [
                                'success' => $success,
                                'searches' => $searches
                        ];
                        return response()->json($response);
                } else {
                        $success = false;
                        $message = "Not Authorized";
                }
                $response = [
                        'success' => $success,
                        'message' => $message
                ];
                return response()->json($response);
        }

        public function update_search($id)
        {
                $request = request();
                $success = true;
                $message = "";
                $user = Auth::user();
                if ($user) {
                        $user_id = $user->id;
                        $body_content = json_decode($request->getContent());
                        if ($id && $id > 0) {
                                $search_id = $id;
                                $searchRecord = SavedSearches::where('userid', $user_id)->where('id', $search_id)->first();
                                if ($searchRecord) {
                                        if (property_exists($body_content, 'search_name')) {
                                                $search_name = $body_content->search_name;
                                                $existing = SavedSearches::where('search_name', $search_name)->where('userid', $user_id)->where('id', '!=', $id)->count();
                                                if ($existing) {
                                                        $success = false;
                                                        $message = "Search is already saved with the same name";
                                                }
                                        }
                                        if (property_exists($body_content, 'data')) {
                                                $searchRecord->data = json_encode($body_content->data);
                                        }
                                        if (property_exists($body_content, 'daily_email') && ($body_content->daily_email == 1) || ($body_content->daily_email == 0)) {
                                                $searchRecord->daily_email = $body_content->daily_email;
                                        }
                                        if (property_exists($body_content, 'listing_sql')) {
                                                $searchRecord->listing_sql = $body_content->listing_sql;
                                        }
                                        if (property_exists($body_content, 'search_url')) {
                                                $searchRecord->search_url = $body_content->search_url;
                                        }
                                        if ($success) {
                                                $searchRecord->save();
                                        }
                                } else {
                                        $success = false;
                                        $message = "No search record found";
                                }
                        } else {
                                $success = false;
                                $message = "id is required";
                        }
                } else {
                        $success = false;
                        $message = "Not Authorized";
                }
                $response = [
                        'success' => $success,
                        'message' => $message
                ];
                return response()->json($response);
        }

        public function delete_search($id)
        {
                $success = true;
                $message = "";
                $user = Auth::user();
                if ($user) {
                        $user_id = $user->id;
                        $searchRecord = SavedSearches::where('userid', $user_id)->where('id', $id)->first();
                        if ($searchRecord) {
                                $recordArr = $searchRecord->toArray();
                                $searchRecord->delete();
                                \App\Services\AlertWebhookService::dispatch('subscription.deleted', 'search', $recordArr);
                        } else {
                                $success = false;
                                $message = "Invalid search id";
                        }
                } else {
                        $success = false;
                        $message = "Not Authorized";
                }
                $response = [
                        'success' => $success,
                        'message' => $message
                ];
                return response()->json($response);
        }

        public function favorite_listing()
        {
                $request = request();
                $success = true;
                $message = "";
                $user = Auth::user();
                if ($user) {
                        $user_id = $user->id;
                        $body_content = json_decode($request->getContent());
                        if ($body_content && property_exists($body_content, 'listingid')) {
                                $listingid = $body_content->listingid;
                                $existing = FavoriteListings::where('listingid', $listingid)->count();
                                if ($existing) {
                                        $success = false;
                                        $message = "Listing is already in favorite list";
                                } else {
                                        $listing = Listings::where('listingid', $listingid)->first();
                                        if ($listing) {
                                                $status = $listing->status;
                                                if ($status == 'Sold') {
                                                        $price = $listing->soldprice_2;
                                                } else {
                                                        $price = $listing->listprice_2;
                                                }
                                                FavoriteListings::create([
                                                        'userid' => $user_id,
                                                        'listingid' => $listingid,
                                                        'status' => $status,
                                                        'price' => $price
                                                ]);
                                        } else {
                                                $success = false;
                                                $message = "Invalid listing id";
                                        }
                                }
                        } else {
                                $success = false;
                                $message = "listingid is required";
                        }
                } else {
                        $success = false;
                        $message = "Not Authorized";
                }
                $response = [
                        'success' => $success,
                        'message' => $message
                ];
                return response()->json($response);
        }

        public function delete_favorite($id)
        {
                $success = true;
                $message = "";
                $user = Auth::user();
                if ($user) {
                        $user_id = $user->id;
                        $favoriteRecord = FavoriteListings::where('userid', $user_id)->where(function ($query) use ($id) {
                                $query->where('id', $id)->orWhere('listingid', $id);
                        })->first();
                        if ($favoriteRecord) {
                                $favoriteRecord->delete();
                        } else {
                                $success = false;
                                $message = "Invalid favorite id";
                        }
                } else {
                        $success = false;
                        $message = "Not Authorized";
                }
                $response = [
                        'success' => $success,
                        'message' => $message
                ];
                return response()->json($response);
        }

        public function contactus(){
                $request = request();
                $user = null;
                $user_email = '';
                if(Auth::user()){
                        $user = Auth::user();
                        $user_email = $user->email;
                }
                $listingid = $request->post('listingid');
                $fullname = $request->post('fullname');
                $emailaddress = $request->post('emailaddress');
                $phonenumber = $request->post('phonenumber');
                $message = $request->post('message');
                $agentcheck = $request->post('agent-check-contactus');
                $listing = Listings::where('listingid', $listingid)->first();

                $raw_data = "Contact Us \n\n";
                $raw_data .= "From:\n";
                $raw_data .= "Name: " . $fullname . "\n";
                $raw_data .= "Email: " . $emailaddress . "\n";
                $raw_data .= "Phone: " . $phonenumber . "\n";
                $raw_data .= "Message: " . $message . "\n";
                $raw_data .= "MLS#: " . $listingid . "\n";
                $raw_data .= "Working with Realtor: " . $agentcheck . "\n";
                $raw_data .= "URL: https://www.bccondosandhomes.com/listing/" . $listing->slug . "\n";

                $data = [
                        'first'=>$fullname,
                        'email'=>$emailaddress,
                        'phone'=>$phonenumber,
                        'notes'=>$message,
                        'mls'=>$listingid,
                        'site'=>'bccondosandhomes.com',
                        'working_with_agent'=>$agentcheck,
                        'created_at'=>Carbon::now(),
                        'updated_at'=>Carbon::now()
                ];

                ShowingRequests::create($data);

                try {
                        $mail = new RequestShowing($raw_data);
                        Mail::to(config('bcch.email'))->queue($mail);
                } catch (\Exception $e) {
                        \Log::error('contactus mail failed: ' . $e->getMessage());
                }

                $response = array(
                        "success"=>true
                );

                return response()->json($response);
           
        }

        /**
         * request_showing used-on-ListngDtlPg--reqstShwing-form 
         * @return json           [description]
         */
        public function request_showing(){
                $request = request();
                $request->validate([
                        'emailaddress' => 'required|email',
                        'phonenumber' => 'regex:/(\+\d{1,2}\s?)?1?\-?\.?\s?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}$/',
                        'agent-check' => 'in:No,Yes,none,1,0,true,false,True,False',
                        'approved-check' => 'in:No,Yes,none,1,0,true,false,True,False',
                        // 'approved-check' => 'No|Yes',
                        'firstname' => 'required_without:lastname|string|max:150',
                        'lastname' => 'required_without:firstname|string|max:150',
                ]);
                $resp_success = false;
                $user = Auth::user();
                // $email = $user->email;
                $listingid = $request->post('listingid');
                $firstname = ucfirst($request->post('firstname',''));
                $lastname = ucfirst($request->post('lastname',''));
                $emailaddress = $request->post('emailaddress');
                $phonenumber = $request->post('phonenumber');
                $agentcheck = $request->post('agent-check');
                $approvedcheck = $request->post('approved-check');
                $dateone = $request->post('dateone');
                $timeone = $request->post('timeone');
                $datetwo = $request->post('datetwo');
                $timetwo = $request->post('timetwo');
                $message = $request->post('message');
                $language = $request->post('language');
                $listing = Listings::where('listingid', $listingid)->first();
                $listing_url = '';
                $listing_address = '';
                if($listing){
                        $listing_url = (!empty($listing->slug) )?(route('listing-detail-page2',['slug'=>$listing->slug])):''; // added:12-01-2021 & modified
                        $listing_address = $listing->streetaddress . ", " . $listing->city ;
                }

                $raw_data = "Request Showing \n\n";
                $raw_data .= "From:\n";
                $raw_data .= "Name: " . $firstname . " " . $lastname . "\n";
                $raw_data .= "Email: " . $emailaddress . "\n";
                $raw_data .= "Phone: " . $phonenumber . "\n\n";
                $raw_data .= "Language Preference: " . $language . "\n\n";
                $raw_data .= "Are you working with an agent? : " . $agentcheck . "\n";
                $raw_data .= "Are you pre-approved for mortgage? : " . $approvedcheck . "\n";
                $raw_data .= "Preferred Date: " . $dateone . "\n";
                $raw_data .= "Preferred Time: " . $timeone . "\n";
                if($timetwo){
                        $raw_data .= "Preferred Date 2: " . $datetwo . "\n";
                        $raw_data .= "Preferred Time 2: " . $timetwo . "\n";
                }
                $raw_data .= "Property:\n";
                $raw_data .= "URL: ".$listing_url."\n";
                $raw_data .= "MLS#: " . $listingid . "\n";
                if ($listing) {
                        $raw_data .= "Address: " . $listing->streetaddress . ", " . $listing->city . "\n";
                }
                $raw_data .= "Message: " . $message . "\n";

                $data = [
                        'first'=>$firstname,
                        'last'=>$lastname,
                        'email'=>$emailaddress,
                        'phone'=>$phonenumber,
                        'language'=>$language,
                        'working_with_agent'=>$agentcheck,
                        'pre_approved_mortgage'=>$approvedcheck,
                        'date1'=>$dateone,
                        'time1'=>$timeone,
                        'date2'=>$timetwo?$datetwo:'',
                        'time2'=>$timetwo,
                        'notes'=>$message,
                        'mls'=>$listingid,
                        'site'=>'bccondosandhomes.com',
                        'created_at'=>Carbon::now(),
                        'updated_at'=>Carbon::now()
                ];

                /**
                 * [Cache-lock--disabled:2022-11-01] (bcoz:tests-failed)
                 */
                // if ( !Cache::lock('blockedForUserReqShowing-listing-'.($listing->slug??'no-slug').($user->email ?? $emailaddress ?? 'no-email-address'), 10)->get() ) {
                if ( false ) {

                        /* blocked the same request for 10-seconds */
                        $resp_success = false;
                        $response_array_message = 'Please Wait few seconds and try again!';

                }
                /*elseif( $request->input('_token','no-match-dafault') != csrf_token() ){

                        $resp_success = false;
                        $response_array_message = 'Invalid request method!';
                
                }*/
                elseif( $listing->status != 'Active'){
                        /* [added:12-10-2022] */
                        $resp_success =false;
                        $response_array_message = 'Sorry, this listing is no more active.';
                }
                elseif($user && strtolower($agentcheck)!='yes'){
                        $resp_success = true;
                }
                elseif( strtolower($agentcheck)=='yes' ){
                        $resp_success = false;
                        $response_array_message = 'As you have a realtor, please contact your realtor to schedule a showing.';
                }
                elseif( !empty($emailaddress) && !in_array($emailaddress, ['sample@email.tst']) ){
                        $resp_success = true;
                }else{
                        $resp_success =false;
                }

                if($resp_success){

                        ShowingRequests::create($data);

                        try {
                                $mail = new RequestShowing($raw_data);
                                Mail::to(config('bcch.email'))->queue($mail);
                        } catch (\Exception $e) {
                                \Log::error('request_showing mail failed: ' . $e->getMessage());
                        }

                        // Best-effort CRM push when an agent context is resolvable.
                        try {
                                $showAgent = null;
                                $showSlug  = $request->input('agent_slug');
                                if ($showSlug) {
                                        $showAgent = \App\Models\Agent::with('settings')
                                                ->where('slug', $showSlug)->where('status', 'active')->first();
                                }
                                if (!$showAgent) {
                                        $showHost = strtolower(trim($request->getHost() ?? ''));
                                        if (str_starts_with($showHost, 'www.')) $showHost = substr($showHost, 4);
                                        $showSettings = DB::table('agent_settings')->where('custom_domain', $showHost)->first();
                                        if ($showSettings) {
                                                $showAgent = \App\Models\Agent::with('settings')
                                                        ->where('id', $showSettings->agent_id)->where('status', 'active')->first();
                                        }
                                }
                                if ($showAgent) {
                                        $showName = trim("{$firstname} {$lastname}");
                                        try {
                                                DB::table('agent_leads')->insert([
                                                        'agent_id'         => $showAgent->id,
                                                        'form_type'        => 'w1',
                                                        'name'             => $showName,
                                                        'email'            => $emailaddress,
                                                        'phone'            => $phonenumber,
                                                        'property_address' => $listing_address ?: null,
                                                        'source_url'       => $listing_url ?: null,
                                                        'ip_hash'          => hash('sha256', $request->ip() ?? ''),
                                                        'created_at'       => now(),
                                                        'updated_at'       => now(),
                                                ]);
                                        } catch (\Throwable $dbErr) {
                                                \Log::error('request_showing agent_leads insert failed: ' . $dbErr->getMessage());
                                        }
                                        $showNotify = $showAgent->settings?->notification_email ?: $showAgent->email ?? null;
                                        if ($showNotify) {
                                                try {
                                                        $showDomain = $showAgent->settings?->custom_domain ?? ($showAgent->slug . '.pixilink.com');
                                                        Mail::raw(
                                                                "New showing request on {$showDomain}\n"
                                                                . str_repeat('-', 44) . "\n"
                                                                . "Name:     {$showName}\n"
                                                                . "Email:    {$emailaddress}\n"
                                                                . "Phone:    {$phonenumber}\n"
                                                                . "Property: " . ($listing_address ?: 'N/A') . "\n"
                                                                . str_repeat('-', 44) . "\n"
                                                                . "View leads: https://website.pixilink.com/admin/agents/{$showAgent->id}/leads\n",
                                                                fn ($m) => $m->to($showNotify)->subject("[W1 Showing] New Lead - {$showName}")
                                                        );
                                                } catch (\Throwable $mailErr) {
                                                        \Log::warning('request_showing agent notify failed: ' . $mailErr->getMessage());
                                                }
                                        }
                                        \App\Services\LeadPipeline::pushToFollowUpBoss($showAgent, [
                                                'name'             => $showName,
                                                'email'            => $emailaddress,
                                                'phone'            => $phonenumber,
                                                'form_type'        => 'w1',
                                                'property_address' => $listing_address ?: null,
                                                'source_url'       => $listing_url ?: null,
                                        ]);
                                        \App\Services\LeadPipeline::pushToGoHighLevel($showAgent, [
                                                'name'             => $showName,
                                                'email'            => $emailaddress,
                                                'phone'            => $phonenumber,
                                                'form_type'        => 'w1',
                                                'property_address' => $listing_address ?: null,
                                        ]);
                                }
                        } catch (\Throwable $crmErr) {
                                \Log::warning('request_showing CRM push failed: ' . $crmErr->getMessage());
                        }

                        $response_array = array(
                                "success"=>true,
                        );

                }else{
                        $response_array = ["success"=>false, 'message'=> ($response_array_message ?? 'Something might not be right!'),]; // email/listing-id etc. checks-fail 
                }
                return response()->json($response_array);
                
        }

        public function request_showing_api(){
                $request = request();
                $body_content = json_decode($request->getContent(), true);
                if ($body_content && array_key_exists('first',$body_content)) {
                        $first = '';
                        $last = '';
                        $email = '';
                        $phone = '';
                        $langugage = '';
                        $working_with_agent = '';
                        $pre_approved_mortgage = '';
                        $date1 = '';
                        $time1 = '';
                        $date2 = '';
                        $time2 = '';
                        $notes = '';
                        $site = '';
                        $mls = '';
                        if(array_key_exists('first', $body_content)){
                                $first = $body_content['first'];
                        }
                        if(array_key_exists('last', $body_content)){
                                $last = $body_content['last'];
                        }
                        if(array_key_exists('email', $body_content)){
                                $email = $body_content['email'];
                        }
                        if(array_key_exists('phone', $body_content)){
                                $phone = $body_content['phone'];
                        }
                        if(array_key_exists('langugage', $body_content)){
                                $langugage = $body_content['langugage'];
                        }
                        if(array_key_exists('working_with_agent', $body_content)){
                                $working_with_agent = $body_content['working_with_agent'];
                        }
                        if(array_key_exists('pre_approved_mortgage', $body_content)){
                                $pre_approved_mortgage = $body_content['pre_approved_mortgage'];
                        }
                        if(array_key_exists('date1', $body_content)){
                                $date1 = $body_content['date1'];
                        }
                        if(array_key_exists('time1', $body_content)){
                                $time1 = $body_content['time1'];
                        }
                        if(array_key_exists('date2', $body_content)){
                                $date2 = $body_content['date2'];
                        }
                        if(array_key_exists('time2', $body_content)){
                                $time2 = $body_content['time2'];
                        }
                        if(array_key_exists('notes', $body_content)){
                                $notes = $body_content['notes'];
                        }
                        if(array_key_exists('site', $body_content)){
                                $site = $body_content['site'];
                        }
                        if(array_key_exists('mls', $body_content)){
                                $mls = $body_content['mls'];
                        }
                        $data = [
                                'first'=>$first,
                                'last'=>$last,
                                'email'=>$email,
                                'phone'=>$phone,
                                'language'=>$langugage,
                                'working_with_agent'=>$working_with_agent,
                                'pre_approved_mortgage'=>$pre_approved_mortgage,
                                'date1'=>$date1,
                                'time1'=>$time1,
                                'date2'=>$date2,
                                'time2'=>$time2,
                                'notes'=>$notes,
                                'site'=>$site,
                                'mls'=>$mls,
                                'created_at'=>Carbon::now(),
                                'updated_at'=>Carbon::now()
                        ];
        
                        ShowingRequests::create($data);
        
                        $response = array(
                                "success"=>true
                        );
        
                        return response()->json($response);
                }
        }

        public function request_showing2()
        {
                $request = request();
                $success = true;
                $message = "";
                $user = Auth::user();
                if ($user) {
                        $user_id = $user->id;
                        $body_content = json_decode($request->getContent());
                        if ($body_content && property_exists($body_content, 'listingid')) {
                                $listingid = $body_content->listingid;
                                if (property_exists($body_content, 'message')) {
                                        $message = $body_content->message;
                                } else {
                                        $message = "";
                                }
                                if (property_exists($body_content, 'showing_date')) {
                                        $showing_date = $body_content->showing_date;
                                } else {
                                        $showing_date = "";
                                }
                                if (property_exists($body_content, 'showing_time')) {
                                        $showing_time = $body_content->showing_time;
                                } else {
                                        $showing_time = "";
                                }
                                $data = [
                                        'userid' => $user_id,
                                        'listingid' => $listingid,
                                        // 'showing_date'=>$showing_date,
                                        // 'showing_time'=>$showing_time,
                                        // 'message'=>$message
                                ];
                                //ShowingRequests::create($data);
                                $country = NULL;
                                if (array_key_exists('HTTP_CF_IPCOUNTRY', $_SERVER)) {
                                        $country = $_SERVER['HTTP_CF_IPCOUNTRY'];
                                }
                                $times = [
                                        'morning' => 'Morning (9am - 12pm)',
                                        'afternoon' => 'Afternoon (12pm - 4pm)',
                                        'evening' => 'Evening (4pm - 8pm)'
                                ];
                                $data['country'] = $country;
                                $data['ip_address'] = $user->get_client_ip();
                                // $data['showing_date_formated'] = Carbon::createFromFormat('Y-m-d', $showing_date)->format('d M, Y');
                                // $data['showing_time_formated'] = $times[$showing_time];
                                $data['user_signup_date'] = Carbon::createFromFormat('Y-m-d H:i:s', $user->created_at)->format('d M, Y');
                                // $mail = new RequestShowing($data);
                                // $agent = Agents::find($agentid);
                                // Mail::to($agent->email)->bcc('varinder@pixilink.com')->queue($mail);
                                // EmailsSent::create([
                                //     'userid' => $agent->agent_id,
                                //     'email' => $agent->email,
                                //     'user_role' => 'AGENT',
                                //     'email_type' => 'request_showing',
                                //     'content' => $mail->render()
                                // ]);
                                // $data_user = [
                                //     'user_name' => $user->first . ' ' . $user->last,
                                //     'agent_id' => $agentid,
                                //     'listingid' => $listingid
                                // ];
                                // $mail = new RequestShowingUserConfirmation($data_user);
                                // Mail::to($user->email)->queue($mail);
                                //if ($agentid == 22) {
                                $listing = Listings::where('listingid', $listingid)->first();
                                $raw_data = "Request Showing \n\n";
                                $raw_data .= "From:\n";
                                $raw_data .= "Name: " . $user->first . " " . $user->last . "\n";
                                $raw_data .= "Email: " . $user->email . "\n";
                                $raw_data .= "Phone: " . ($user->phone_country_code??'').$user->phone . "\n\n";
                                $raw_data .= "Property:\n";
                                $raw_data .= "MLS#: " . $listingid . "\n";
                                if ($listing) {
                                        $raw_data .= "Address: " . $listing->streetaddress . ", " . $listing->city . "\n";
                                }
                        } else {
                                $success = false;
                                $message = "listingid is required";
                        }
                } else {
                        $success = false;
                        $message = "Not Authorized";
                }
                $response = [
                        'success' => $success,
                        'message' => $message
                ];
                return response()->json($response);
        }


        public function ask_question()
        {
                $request = request();
                $success = true;
                $message = "";
                $user = Auth::user();
                $type = "listing";
                if ($request->get('type') && $request->get('type') == 'building') {
                        $type = "building";
                }
                if ($user) {
                        $user_id = $user->id;
                        $body_content = json_decode($request->getContent());
                        if ($body_content && property_exists($body_content, 'listingid')) {
                                $listingid = $body_content->listingid;
                                if (property_exists($body_content, 'question')) {
                                        $message = $body_content->question;
                                } else {
                                        $message = "";
                                }
                                $data = [
                                        'userid' => $user_id,
                                        'listingid' => $listingid,
                                        'type' => $type,
                                        'message' => $message
                                ];
                                UserQuestions::create($data);

                                // Best-effort CRM push when agent context is resolvable.
                                try {
                                        $aqAgent = null;
                                        $aqSlug  = request()->input('agent_slug');
                                        if ($aqSlug) {
                                                $aqAgent = \App\Models\Agent::with('settings')
                                                        ->where('slug', $aqSlug)->where('status', 'active')->first();
                                        }
                                        if (!$aqAgent) {
                                                $aqHost = strtolower(trim(request()->getHost() ?? ''));
                                                if (str_starts_with($aqHost, 'www.')) $aqHost = substr($aqHost, 4);
                                                $aqSettings = DB::table('agent_settings')->where('custom_domain', $aqHost)->first();
                                                if ($aqSettings) {
                                                        $aqAgent = \App\Models\Agent::with('settings')
                                                                ->where('id', $aqSettings->agent_id)->where('status', 'active')->first();
                                                }
                                        }
                                        if ($aqAgent) {
                                                $aqName = trim(($user->first ?? '') . ' ' . ($user->last ?? '')) ?: $user->email;
                                                $aqData = [
                                                        'name'      => $aqName,
                                                        'email'     => $user->email,
                                                        'phone'     => ($user->phone_country_code ?? '') . ($user->phone ?? ''),
                                                        'form_type' => 'ask',
                                                        'message'   => $message,
                                                ];
                                                try {
                                                        DB::table('agent_leads')->insert([
                                                                'agent_id'   => $aqAgent->id,
                                                                'form_type'  => 'ask',
                                                                'name'       => $aqName,
                                                                'email'      => $user->email,
                                                                'phone'      => ($user->phone_country_code ?? '') . ($user->phone ?? ''),
                                                                'message'    => $message,
                                                                'ip_hash'    => hash('sha256', request()->ip() ?? ''),
                                                                'created_at' => now(),
                                                                'updated_at' => now(),
                                                        ]);
                                                } catch (\Throwable $dbErr) {
                                                        \Log::error('ask_question agent_leads insert failed: ' . $dbErr->getMessage());
                                                }
                                                \App\Services\LeadPipeline::pushToFollowUpBoss($aqAgent, $aqData);
                                                \App\Services\LeadPipeline::pushToGoHighLevel($aqAgent, $aqData);
                                        }
                                } catch (\Throwable $crmErr) {
                                        \Log::warning('ask_question CRM push failed: ' . $crmErr->getMessage());
                                }

                                try {
                                        $raw_data = "Question \n\n";
                                        $raw_data .= "From:\n";
                                        $raw_data .= "Name: " . $user->first . " " . $user->last . "\n";
                                        $raw_data .= "Email: " . $user->email . "\n";
                                        $raw_data .= "Phone: " . ($user->phone_country_code??'').$user->phone . "\n\n";
                                        $raw_data .= "Type: " . $type . "\n";
                                        $raw_data .= "MLS#: " . $listingid . "\n";
                                        $raw_data .= "Question:\n" . $message . "\n";
                                        $mail = new AskQuestion($raw_data);
                                        Mail::to(config('bcch.email'))->queue($mail);
                                } catch (\Exception $e) {
                                        \Log::error('ask_question mail failed: ' . $e->getMessage());
                                }

                                $country = NULL;
                                if (array_key_exists('HTTP_CF_IPCOUNTRY', $_SERVER)) {
                                        $country = $_SERVER['HTTP_CF_IPCOUNTRY'];
                                }
                                $data['country'] = $country;
                                $data['ip_address'] = $user->get_client_ip();
                                $data['user_signup_date'] = Carbon::createFromFormat('Y-m-d H:i:s', $user->created_at)->format('d M, Y');
                                // $agent = Agents::find($agentid);
                                // $mail = new AskQuestion($data);
                                // Mail::to($agent->email)->bcc('varinder@pixilink.com')->queue($mail);
                                // EmailsSent::create([
                                //     'userid' => $agent->agent_id,
                                //     'email' => $agent->email,
                                //     'user_role' => 'AGENT',
                                //     'email_type' => 'ask_question',
                                //     'content' => $mail->render()
                                // ]);
                                // if ($agentid == 22) {
                                $listing = Listings::where('listingid', $listingid)->first();
                                $raw_data = "Question \n\n";
                                $raw_data .= "From:\n";
                                $raw_data .= "Name: " . $user->first . " " . $user->last . "\n";
                                $raw_data .= "Email: " . $user->email . "\n";
                                $raw_data .= "Phone: " . ($user->phone_country_code??'').$user->phone . "\n\n";
                                $raw_data .= "Property:\n";
                                $raw_data .= "MLS#: " . $listingid . "\n";
                                if ($listing) {
                                        $raw_data .= "Address: " . $listing->streetaddress . ", " . $listing->city . "\n";
                                }
                                $raw_data .= "Question:\n";
                                $raw_data .=  $message . "\n";
                        } else {
                                $success = false;
                                $message = "listingid is required";
                        }
                } else {
                        $success = false;
                        $message = "Not Authorized";
                }
                $response = [
                        'success' => $success,
                        'message' => $message
                ];
                return response()->json($response);
        }

        public function get_user()
        {
                $request = request();
                $success = true;
                $message = "";
                $response = [];
                $token = $request->get('token');
                if ($token) {
                        try {
                                $user = $this->firebaseRepo->verifyToken($token);
                        } catch (\Exception $e) {
                                $success = false;
                                $message = "Invalid Token";
                                $response = [
                                        'success' => $success,
                                        'message' => $message
                                ];
                                return response()->json($response);
                        }
                        if ($user) {
                                $user = FirebaseUser::where('uid', $user->uid)->select('id', 'uid', 'first', 'last', 'phone', 'email', 'role', 'agreedToTerms', 'agreePrivacyNotice', 'agreeDisclosure', 'activated', 'last_login', 'profile_image', 'signup_country', 'signup_ip', 'created_at', 'updated_at', 'login_with_agent')->first();
                                $response['user'] = $user->toArray();
                                $response['user']['profile_completed'] = $user->isProfileCompleted() ? 'y' : 'n';
                                $response['login_with_agent'] = $user->loginWithAgent_api();
                                $response['all_agents'] = $user->getAllAgents_api()->toArray();
                        } else {
                                $success = false;
                                $message = "Invalid Token";
                        }
                } else {
                        $success = false;
                        $message = "token required";
                }
                $response['success'] = $success;
                $response['message'] = $message;

                return response()->json($response);
        }

        public function get_session()
        {
                $request = request();
                $response = [];
                $success = true;
                $message = "";
                if ($request->get('token')) {
                        $fbUser = $this->firebaseRepo->verifyToken($request->get('token'));
        
                        if ($fbUser) {
                                $user = FirebaseUser::where('uid', $fbUser->uid)->first();

                                // if($user){
                                //      $user_agent = request()->header('user-agent');
                                //      $header = json_encode(request()->header());
                                //      DB::insert("insert into bccondosandhomes.login_logout_logs (action, userid, user_agent, headers) values ('login', ".$user->id.", '".$user_agent."', '".$header."')");
                                // }

                                if (($user && $user->agreedToTerms && $user->agreePrivacyNotice && $user->agreeDisclosure)) {
                                        Auth::login($user);
                                } else {
                                        $success = false;
                                        if ($user) {
                                                $response_code = "INCOMPLETE_PROFILE";
                                        } else {
                                                $response_code = "NEW_USER";
                                        }
                                        $next_url = route('handleAuth') . "?token=" . $request->get('token');
                                        $response['success'] = false;
                                        $response['response_code'] = $response_code;
                                        $response['next_url'] = $next_url;
                                        return response()->json($response);
                                }
                        } 
                        elseif(Auth::user()){
                            $user = Auth::user();
                            $user = FirebaseUser::where('uid', $user->uid)->select('id', 'uid', 'first', 'last', 'phone', 'email', 'role', 'agreedToTerms', 'agreePrivacyNotice', 'agreeDisclosure', 'activated', 'last_login', 'profile_image', 'signup_country', 'signup_ip', 'created_at', 'updated_at')->first();
                        $response['user'] = $user->toArray();
                        $response['show_vow_menu'] = false;
                        $response['user']['profile_completed'] = $user->isProfileCompleted() ? 'y' : 'n';
                        }
                        else {
                            Auth::logout();
                                $response['success'] = false;
                                //$response['response_code'] = "INVALID_TOKEN";
                                $response_code = "INCOMPLETE_PROFILE";
                                $response['response_code'] = $response_code;
                                $response['next_url'] = "https://www.bccondosandhomes.com/login";
                                return response()->json($response);
                        }
                }
                $user = Auth::user();
                if ($user) {
                        $user = FirebaseUser::where('uid', $user->uid)->select('id', 'uid', 'first', 'last', 'phone', 'email', 'role', 'agreedToTerms', 'agreePrivacyNotice', 'agreeDisclosure', 'activated', 'last_login', 'profile_image', 'signup_country', 'signup_ip', 'created_at', 'updated_at')->first();
                        $response['user'] = $user->toArray();
                        $response['show_vow_menu'] = false;
                        $response['user']['profile_completed'] = $user->isProfileCompleted() ? 'y' : 'n';
                } else {
                        $success = false;
                        $message = "Not Authorized";
                }
                $response['success'] = $success;
                $response['message'] = $message;
                $response['sessionId'] = Session::getId();
                return response()->json($response);
        }
        

        public function check_email_verification()
        {
                $success = false;
                $user = Auth::user();
                if ($user) {
                        $firebaseRepo = new FirebaseRepository();
                        $verified =  $firebaseRepo->checkVerification($user->uid);
                        if ($verified) {
                                $success = true;
                                $user->activated = 1;
                                $user->save();
                        }
                }
                return response()->json(['success' => $success]);
        }

        public function openLink()
        {
                $request = request();
                if ($request->get('url')) {
                        $whitelisted_domains = [
                                'google.com',
                                'bccondosandhomes.com',
                                'bccondos.net',
                                'outlook.office.com',
                                'yahoo.com'
                        ];
                        $safeurl = false;
                        $url = $request->get('url');
                        foreach($whitelisted_domains as $domain){
                                if(str_contains($url, $domain)){
                                        $safeurl = true;
                                }
                        }
                        if(!$safeurl){
                                abort( 404 );
                                abort( response('Unauthorized', 401) );
                        }
                        if ($request->get('type') == 'add_to_calendar') {
                                $url = $request->get('url');
                                $params = $request->all();
                                if (isset($params['type'])) {
                                        unset($params['type']);
                                }
                                if (isset($params['ref'])) {
                                        unset($params['ref']);
                                }
                                if (isset($params['url'])) {
                                        unset($params['url']);
                                }
                                $url = $url . '&' . http_build_query($params);
                        } elseif ($request->get('type') && $request->get('type') != 'email' && $request->get('type') != 'phone') {
                                $url = $this->addhttp($url);
                        } else {
                                $url = str_replace('tel:', '', str_replace('mailto:', '', $url));
                        }
                        if (Auth::user()) {
                                $user = Auth::user();
                                $agentid = $user->login_with_agent;
                                $type = $request->get('type');
                                $ref = $request->get('ref');
                                UserActivities::create([
                                        'userid' => $user->id,
                                        'agent_id' => $agentid,
                                        'activity' => 'click',
                                        'link' => $url,
                                        'link_type' => $type,
                                        'ref' => $ref
                                ]);
                        }
                        if ($request->get('ajax')) {
                                $response = ['success' => true];
                                return response()->json($response);
                        } else {

                                return redirect($url);
                        }
                } else {
                        abort(404);
                }
        }

        public function addhttp($url)
        {
                if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
                        $url = "http://" . $url;
                }
                return $url;
        }

        public function show_favorite_listings()
        {
                $user = Auth::user();
                $session_id = Session::getId();
                $favorite_listing_ids = FavoriteListings::where('userid', $user->id)->where('deleted', 0)->get()->pluck('listingid')->toArray();
                $other_listings = Listings::where(function ($query) {
                        $query->where('status', '!=', 'Sold')->Where('status', '!=', 'Active');
                })->whereIn('listingid', $favorite_listing_ids)->select('listingid')->get()->pluck('listingid')->toArray();
                $favorite_listings = FavoriteListings::with('listing')->with('listing.aphoto')->where('userid', $user->id)->where('deleted', 0)->whereNotIn('listingid', $other_listings)->get();
                return view('frontend.user.favorite_listings')->with([
                        'user' => $user,
                        'session_id' => $session_id,
                        'favorite_listings' => $favorite_listings
                ]);
        }

        /**
         * show_favorite_tracked_listings [created:21-05-2022]
         * @return [type]           [description]
         */
        public function show_favorite_tracked_listings(){
                return $this->show_favorite_listings();
        }

        public function start_chat()
        {
                $request = request();
                $user = Auth::user();
                $success = false;
                if ($user) {
                        $sid    = "MG6d1dd1362707c26cad81c7fae060b3bc";
                        $token  = config('services.twilio.token');
                        $listing = null;
                        $message = $request->post('message');
                        $agent = $user->loginWithAgent()->first();
                        $listingid = $request->post('listingid');
                        $twilio = new Client($sid, $token);
                        // if($listingid){
                        //     $listing = Listings::where('listingid', $listingid)->first();
                        // }
                        $newline = '%0a';
                        $completeMessage = "Message From: " . $user->first . " " . $user->last;
                        $completeMessage .= $newline;
                        $completeMessage .= ($user->phone_country_code??'').$user->phone;
                        $completeMessage .= $newline;
                        $completeMessage .= $user->email;
                        $completeMessage .= $newline;
                        $completeMessage .= $message;
                        if ($listingid) {
                                $completeMessage .= "MLS: " . $listingid;
                        }
                }
                return response()->json([
                        'success' => $success,
                        'test' => $completeMessage
                ]);
        }

        public function send_info_to_followupboss()
        {
                $request = request();
                $user = Auth::user();

                // Save city to session before the phone-verification gate so
                // pre-registration browsing is captured for the Registration event.
                $earlyAction = $request->post('action');
                if ($earlyAction === 'listingview') {
                        $earlyListing = Listings::where('listingid', $request->post('listingid'))->first();
                        if ($earlyListing) {
                                FubAreaHelper::saveToSession($earlyListing->city);
                        }
                } elseif ($earlyAction === 'buildingview') {
                        $earlyBuilding = \App\Models\Buildings::find($request->post('buildingid'));
                        if ($earlyBuilding) {
                                FubAreaHelper::saveToSession($earlyBuilding->city);
                        }
                }

                if (!$user || !$user->phone_verified) {
                        return response()->json(['success' => true]);
                }

                $action = $request->post('action');

                $person = [
                        'contacted' => false,
                        'firstName' => $user->first,
                        'lastName'  => $user->last,
                        'stage'     => 'Lead',
                        'source'    => 'website',
                        'emails'    => [['value' => $user->email]],
                        'phones'    => [['value' => ($user->phone_country_code ?? '') . ($user->phone ?? '')]],
                ];
                if ($user->followupboss_people_id) {
                        $person['id'] = $user->followupboss_people_id;
                }

                $payload = [
                        'person' => $person,
                        'source' => 'bccondosandhomes.com',
                        'system' => 'website_api',
                        'type'   => 'Viewed Property',
                ];

                if ($action === 'listingview') {
                        $listingid = $request->post('listingid');
                        $listing = Listings::where('listingid', $listingid)->first();
                        if ($listing) {
                                $payload['person']['sourceUrl'] = route('listing-detail-page2', ['slug' => $listing->slug]);
                                $payload['property'] = [
                                        'street'     => ucwords(strtolower($listing->streetaddress)),
                                        'city'       => $listing->city,
                                        'state'      => $listing->province,
                                        'code'       => $listing->postalcode,
                                        'mlsNumber'  => $listing->listingid,
                                        'price'      => $listing->listprice_2,
                                        'url'        => route('listing-detail-page2', ['slug' => $listing->slug]),
                                        'bedrooms'   => $listing->bedrooms,
                                        'bathrooms'  => $listing->bathstotal,
                                        'area'       => $listing->livingarea_2,
                                ];
                                FubAreaHelper::applyTag($payload['person'], $listing->city);
                                FubAreaHelper::saveToSession($listing->city);
                        }
                } elseif ($action === 'buildingview') {
                        $buildingid = $request->post('buildingid');
                        $building = \App\Models\Buildings::find($buildingid);
                        if ($building) {
                                $payload['person']['sourceUrl'] = route('building-detail-page', $building->slug);
                                $payload['property'] = [
                                        'street' => trim($building->street_no . ' ' . ucfirst(strtolower($building->street_name)) . ' ' . ucfirst(strtolower($building->street_type))),
                                        'city'   => ucfirst(strtolower($building->city)),
                                        'state'  => 'BC',
                                        'code'   => $building->postalcode,
                                        'url'    => route('building-detail-page', $building->slug),
                                ];
                                FubAreaHelper::applyTag($payload['person'], $building->city);
                                FubAreaHelper::saveToSession($building->city);
                        }
                }

                $curl = curl_init();
                curl_setopt_array($curl, [
                        CURLOPT_URL            => 'https://api.followupboss.com/v1/events',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT        => 10,
                        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST  => 'POST',
                        CURLOPT_POSTFIELDS     => json_encode($payload),
                        CURLOPT_HTTPHEADER     => [
                                'accept: application/json',
                                'authorization: Basic ' . config('services.followupboss.api_key'),
                                'content-type: application/json',
                        ],
                ]);

                $response     = curl_exec($curl);
                curl_close($curl);
                $responseData = json_decode($response, true);

                if (!$user->followupboss_people_id && isset($responseData['person']['id'])) {
                        $user->followupboss_people_id = $responseData['person']['id'];
                        $user->save();
                }

                return response()->json(['success' => true]);
        }

        public function watchListing()
        {
                $request = request();
                $user = Auth::user();
                if (!$user) {
                        return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
                }
                $body = json_decode($request->getContent());
                $listingId = isset($body->listing_id) ? $body->listing_id : null;
                if (!$listingId) {
                        return response()->json(['success' => false, 'message' => 'listing_id required']);
                }
                $watchPriceDrop = isset($body->watch_price_drop) ? (bool) $body->watch_price_drop : true;
                $watchSold      = isset($body->watch_sold)       ? (bool) $body->watch_sold       : false;
                $fav = FavoriteListings::where('userid', $user->id)
                        ->where('listingid', $listingId)
                        ->first();
                if ($fav) {
                        $fav->watch_price_drop = $watchPriceDrop;
                        $fav->watch_sold       = $watchSold;
                        $fav->deleted          = 0;
                        $fav->save();
                } else {
                        FavoriteListings::create([
                                'userid'           => $user->id,
                                'listingid'        => $listingId,
                                'watch_price_drop' => $watchPriceDrop,
                                'watch_sold'       => $watchSold,
                                'status'           => 'Active',
                                'deleted'          => 0,
                                'ip'               => $request->ip(),
                        ]);
                }

                $userEmail = $user->email;
                $wpd       = $watchPriceDrop;
                $ws        = $watchSold;
                $lid       = $listingId;
                dispatch(static function () use ($userEmail, $lid, $wpd, $ws) {
                        $listing = \App\Models\Listings::where('listingid', $lid)->first();
                        if (!$listing) {
                                return;
                        }
                        $parts   = array_filter([
                                $listing->suite_no
                                        ? $listing->suite_no . '-' . $listing->street_number
                                        : $listing->street_number,
                                $listing->street_name,
                                $listing->street_type,
                        ]);
                        $address = implode(' ', $parts) ?: $lid;
                        \App\Services\AlertApiService::create(
                                \App\Services\AlertApiService::payloadForListingWatch(
                                        $userEmail,
                                        $address,
                                        $listing->city ?? '',
                                        $wpd,
                                        $ws,
                                        $listing->complex ?? ''
                                )
                        );
                })->afterResponse();

                return response()->json([
                        'success'          => true,
                        'watched'          => true,
                        'watch_price_drop' => $watchPriceDrop,
                        'watch_sold'       => $watchSold,
                ]);
        }

        /**
         * alertPreview — returns count + 6 sample listings matching the given filters.
         * Used by the My Account "Create Alert" form for live listing preview.
         * No auth required (listing data is public).
         * [added: 2026-05]
         */
        public function alertPreview(Request $request)
        {
                $city     = trim($request->get('city', ''));
                $subarea  = trim($request->get('subarea', ''));
                $type     = trim($request->get('type', ''));
                $minBeds  = (int) $request->get('min_beds', 0);
                $minPrice = (int) $request->get('min_price', 0);
                $maxPrice = (int) $request->get('max_price', 0);

                $boards = [
                        'Real Estate Board of Greater Vancouver',
                        'Fraser Valley Real Estate Board',
                        'Chilliwack & District Real Estate Board',
                ];

                $query = \App\Models\Listings::query()
                        ->where('status', 'Active')
                        ->whereIn('board', $boards)
                        ->select([
                                'slug', 'street_number', 'street_name', 'suite_no',
                                'city', 'subarea', 'type', 'bedrooms',
                                'full_baths', 'half_baths', 'livingarea_2',
                                'listprice_2', 'thumbnailurl', 'list_date', 'yearbuilt',
                        ]);

                if ($city)             $query->where('city', $city);
                if ($subarea)          $query->where('subarea', $subarea);
                if ($type)             $query->where('type', $type);
                if ($minBeds  > 0)     $query->where('bedrooms',    '>=', $minBeds);
                if ($minPrice > 0)     $query->where('listprice_2', '>=', $minPrice);
                if ($maxPrice > 0)     $query->where('listprice_2', '<=', $maxPrice);

                $count    = $query->count();
                $listings = (clone $query)->orderBy('list_date', 'desc')->limit(6)->get()->map(function ($l) {
                        $addr = trim(($l->suite_no ? $l->suite_no . ' – ' : '') . $l->street_number . ' ' . $l->street_name);
                        return [
                                'slug'    => $l->slug,
                                'address' => $addr,
                                'city'    => $l->city,
                                'subarea' => $l->subarea,
                                'type'    => $l->type,
                                'beds'    => $l->bedrooms,
                                'baths'   => ($l->full_baths ?? 0) + ($l->half_baths ?? 0),
                                'sqft'    => $l->livingarea_2 ? number_format((int)$l->livingarea_2) : null,
                                'price'      => $l->listprice_2 ? (int) $l->listprice_2 : null,
                                'date'       => $l->list_date ? \Carbon\Carbon::parse($l->list_date)->format('M j, Y') : null,
                                'year_built' => $l->yearbuilt ?: null,
                                'photo'      => $l->thumbnailurl ?: null,
                        ];
                });

                return response()->json(['count' => $count, 'listings' => $listings])
                        ->header('Cache-Control', 'no-store');
        }

        /**
         * subareaList — returns distinct active subareas for a given city.
         * Used by the My Account "Create Alert" form neighbourhood dropdown.
         * No auth required.
         * [added: 2026-05]
         */
        public function subareaList(Request $request)
        {
                $city = trim($request->get('city', ''));
                if (!$city) {
                        return response()->json(['subareas' => []]);
                }
                $boards = [
                        'Real Estate Board of Greater Vancouver',
                        'Fraser Valley Real Estate Board',
                        'Chilliwack & District Real Estate Board',
                ];
                $subareas = \App\Models\Listings::query()
                        ->where('status', 'Active')
                        ->whereIn('board', $boards)
                        ->where('city', $city)
                        ->whereNotNull('subarea')
                        ->where('subarea', '!=', '')
                        ->distinct()
                        ->orderBy('subarea')
                        ->pluck('subarea');
                return response()->json(['subareas' => $subareas]);
        }

        public function recall_history(){
                $request = request();
                $subscription_success = $request->get('subscription_success');
                if($subscription_success){
                        $back_url = Session::get('history_url')."?subscription_success=true";
                        if(!$back_url){
                                $back_url = route('landing', ['subscription_success'=>true]);
                        }
                }
                else{
                        $back_url = Session::get('history_url');
                        if(!$back_url){
                                $back_url = route('landing');
                        }
                }
                return redirect($back_url);
        }
}
