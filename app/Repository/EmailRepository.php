<?php

namespace App\Repository;

use App\Models\Auth\FirebaseUser;
use Illuminate\Support\Facades\Mail;
use App\Mail\PropertySuggestion;
use App\Models\PropertiesEmailed;
use App\Models\UserAgents;
use App\Models\Agents;
use Carbon\Carbon;
use App\Models\UserPropertyViews;
use App\Models\UserSearches;
use App\Models\InsightsActivity;
use App\Mail\OneTimeStatsEmail;
use App\Models\EmailsSent;
use Illuminate\Support\Facades\Auth;
use App\Mail\WeeklyStatsToAgents;
use App\Models\SavedSearches;
use App\Models\Listings;
use App\Mail\SavedSearchesListingUpdate;
use App\Mail\IncompleteUsers5MinuteUpdate;
use Illuminate\Support\Facades\DB;
use App\Mail\IncompleteUsersDay2Update;
use App\Mail\IncompleteUsersDay3Update;
use App\Mail\SavedSearchesPriceUpdate;
use App\Models\FavoriteListings;
use App\Mail\FavoriteListingPriceUpdate;
use App\Mail\FavoriteListingTrackedPriceUpdate; // [added:01-06-2022]
use App\Mail\FavoriteListingStatusUpdate;
use App\Models\MapSearches;
use App\Mail\WeeklyPropertiesStatsAllUsers;
use App\Mail\ActivateVOWAgentAlert;
use App\Mail\RecentSoldToAdmin;
use App\Mail\TempEmail;

class EmailRepository
{

    public function sendPropertySuggestions()
    {
        FirebaseUser::where('activated', '1')->whereNotIn('id', function ($query) {
            $query->select('userid')->distinct()->from('saved_searches');
        })->chunk(100, function ($users) {
            foreach ($users as $user) {
                if ($user->property_suggestion_emails == 'y') {
                        $suggestions = NULL;
                        $suggestions = $user->get_properties_suggestion();
                        if ($suggestions) {
                            $emailUniqueId = md5(time() . mt_rand(1, 1000000));
                            if (count($suggestions['active']) > 0 || count($suggestions['sold']) > 0) {
                                $mail = new PropertySuggestion($user, $emailUniqueId);
                                Mail::to($user->email)->queue($mail);
                                // echo $mail->render();
                                // exit;
                                //Mail::to("varinder@pixilink.com")->queue($mail);
                                $this->markPropertiesSent($user, $suggestions, $mail->render(), $emailUniqueId);
                            }
                        }
                    
                }
            }
        });
    }

    public function markPropertiesSent($user, $suggestions, $content = NULL, $uniqueId = NULL, $type = NULL, $status = NULL, $photos_count = NULL)
    {
        if (!$type) {
            $status = NULL;
            $listingIdsActive = array();
            $listingIdsSold = array();
            if (count($suggestions['active']) > 0) {
                $listings = $suggestions['active'];
                $listingIdsActive = $listings->pluck('listingid')->toArray();
                $status = "Active";
            }
            if (count($suggestions['sold']) > 0) {
                $listings = $suggestions['sold'];
                $listingIdsSold = $listings->pluck('listingid')->toArray();
                if ($status) {
                    $status .= ", Sold";
                } else {
                    $status = "Sold";
                }
            }
            $listingIds = implode(",", array_merge($listingIdsActive, $listingIdsSold));
            PropertiesEmailed::create([
                'userid' => $user->id,
                'mls' => $listingIds,
                'content' => $content,
                'status' => $status,
                'messageId' => $uniqueId,
                'email' => $user->email
            ]);
        } else {
            PropertiesEmailed::create([
                'userid' => $user->id,
                'mls' => $suggestions,
                'content' => $content,
                'status' => $status,
                'messageId' => $uniqueId,
                'email' => $user->email,
                'alert_type' => $type,
                'photos_count' => $photos_count
            ]);
        }
    }


    public function emailWeeklyStatsToAgents()
    {

        $first_day = Carbon::now()->subWeek()->startOfWeek();
        $last_day = Carbon::now()->subWeek()->endOfWeek();

        $first_day_last_week = Carbon::now()->subWeeks(2)->startOfWeek();
        $last_day_last_week = Carbon::now()->subWeeks(2)->endOfWeek();

        // $insight_views_repeated = InsightsActivity::selectRaw('count(userid) as usercount')->whereIn('userid', function($query) use ($agentId){
        //     $query->select('userid')->from('user_agents')->where('agent_id', $agentId);
        // })->where('created_at','>=',$first_day)->where('created_at','<=',$last_day)->groupBy('userid')->havingRaw('usercount > 1')->get();            
        //$insight_views_repeated_percentage = ($insight_views_repeated->count()/$insight_views)*100;

        Agents::where('vow_active', 'yes')->where('vow_signed', 'y')->where('fisherly_notification_weekly_stat', 'y')->chunk(100, function ($agents) use ($first_day, $last_day, $first_day_last_week, $last_day_last_week) {
            foreach ($agents as $agent) {
                $totalUsers = UserAgents::where('agent_id', $agent->agent_id)->count();
                if ($totalUsers > 0) {
                    $newUser = UserAgents::where('agent_id', $agent->agent_id)->where('created_at', '>=', $first_day)->where('created_at', '<=', $last_day)->count();
                    $newUser_last_week = UserAgents::where('agent_id', $agent->agent_id)->where('created_at', '>=', $first_day_last_week)->where('created_at', '<=', $last_day_last_week)->count();

                    $property_views_active = UserPropertyViews::where('agent_id', $agent->agent_id)->where('status', 'Active')->where('created_at', '>=', $first_day)->where('created_at', '<=', $last_day)->count();
                    $property_views_sold = UserPropertyViews::where('agent_id', $agent->agent_id)->where('status', 'Sold')->where('created_at', '>=', $first_day)->where('created_at', '<=', $last_day)->count();
                    $total_views = $property_views_active + $property_views_sold;
                    $total_views_last_week = UserPropertyViews::where('agent_id', $agent->agent_id)->where('created_at', '>=', $first_day_last_week)->where('created_at', '<=', $last_day_last_week)->count();

                    $property_views_mobile = UserPropertyViews::where('agent_id', $agent->agent_id)->where('created_at', '>=', $first_day)->where('created_at', '<=', $last_day)->where('device', 'Mobile')->count();
                    $property_views_desktop = UserPropertyViews::where('agent_id', $agent->agent_id)->where('created_at', '>=', $first_day)->where('created_at', '<=', $last_day)->where('device', 'Desktop')->count();
                    $property_views_tablet = UserPropertyViews::where('agent_id', $agent->agent_id)->where('created_at', '>=', $first_day)->where('created_at', '<=', $last_day)->where('device', 'Tablet')->count();
                    $total_device_views = $property_views_desktop + $property_views_mobile + $property_views_tablet;

                    $searchesData = UserSearches::where('agent_id', $agent->agent_id)->where('created_at', '>=', $first_day)->where('created_at', '<=', $last_day)->get();
                    $total_searches = $searchesData->count();
                    $total_searches_last_week = UserSearches::where('agent_id', $agent->agent_id)->where('created_at', '>=', $first_day_last_week)->where('created_at', '<=', $last_day_last_week)->count();
                    $mapSearchesData = MapSearches::where('agent_id', $agent->agent_id)->where('created_at', '>=', $first_day)->where('created_at', '<=', $last_day)->get();
                    $mapSearchCount = $mapSearchesData->count();
                    $mapSearches_last_week =  MapSearches::where('agent_id', $agent->agent_id)->where('created_at', '>=', $first_day_last_week)->where('created_at', '<=', $last_day_last_week)->count();
                    $total_searches = $total_searches + $mapSearchCount;
                    $total_searches_last_week = $total_searches_last_week + $mapSearches_last_week;

                    $agentId = $agent->agent_id;
                    $insight_views = InsightsActivity::whereIn('userid', function ($query) use ($agentId) {
                        $query->select('user_id')->from('user_agents')->where('agent_id', $agentId);
                    })->where('created_at', '>=', $first_day)->where('created_at', '<=', $last_day)->count();
                    $insight_views_last_week = InsightsActivity::whereIn('userid', function ($query) use ($agentId) {
                        $query->select('user_id')->from('user_agents')->where('agent_id', $agentId);
                    })->where('created_at', '>=', $first_day_last_week)->where('created_at', '<=', $last_day_last_week)->count();

                    if ($total_views != 0) {
                        $active_percentage = ($property_views_active / $total_views) * 100;
                        $sold_percentage = ($property_views_sold / $total_views) * 100;
                    } else {
                        $active_percentage = 0;
                        $sold_percentage = 0;
                    }

                    $mobile_view = 0;
                    $desktop_view = 0;
                    $tablet_view = 0;

                    if ($total_device_views > 0) {
                        $mobile_view = ($property_views_mobile / $total_device_views) * 100;
                        $desktop_view = ($property_views_desktop / $total_device_views) * 100;
                        $tablet_view = ($property_views_tablet / $total_device_views) * 100;
                    }

                    $favorites = FavoriteListings::whereIn('userid', function ($query) use ($agent) {
                        $query->select('id')->from('users')->where('agent', $agent->agent_id);
                    })->where('deleted', 0)->where('created_at', '>=', $first_day)->where('created_at', '<=', $last_day)->count();

                    $favorites_last_week = FavoriteListings::whereIn('userid', function ($query) use ($agent) {
                        $query->select('id')->from('users')->where('agent', $agent->agent_id);
                    })->where('deleted', 0)->where('created_at', '>=', $first_day_last_week)->where('created_at', '<=', $last_day_last_week)->count();

                    $top_cities = $this->get_top_cities_searched($searchesData, 3, $mapSearchesData);

                    $data = [
                        'first_day' => $first_day->format('d M, Y'),
                        'last_day' => $last_day->format('d M, Y'),
                        'week_number' => $first_day->weekOfYear,
                        'year' => $first_day->format('Y'),
                        'total_users' => $totalUsers,
                        'new_users' => $newUser,
                        'new_user_last_week' => $newUser_last_week,
                        'property_views' => $total_views,
                        'property_views_last_week' => $total_views_last_week,
                        'searches' => $total_searches,
                        'searches_last_week' => $total_searches_last_week,
                        'insight_views' => $insight_views,
                        'insight_views_last_Week' => $insight_views_last_week,
                        'active_percentage' => $active_percentage,
                        'sold_percentage' => $sold_percentage,
                        'top_cities' => $top_cities,
                        'agent_id' => $agentId,
                        'mobile_view' => $mobile_view,
                        'desktop_view' => $desktop_view,
                        'tablet_view' => $tablet_view,
                        'favorites' => $favorites,
                        'favorites_last_week' => $favorites_last_week
                    ];
                    $mail =  new WeeklyStatsToAgents($data);
                    Mail::to($agent->email)->queue($mail);
                    EmailsSent::create([
                        'userid' => $agentId,
                        'email' => $agent->email,
                        'user_role' => 'AGENT',
                        'email_type' => 'weekly_stats',
                        'content' => $mail->render()
                    ]);
                }
            }
        });
    }

    public function get_top_cities_searched($searchData, $n, $mapSearchesData = null)
    {
        $cities = [];
        foreach ($searchData as $search) {
            $data_obj = json_decode($search->data);
            if ($data_obj) {
                $data = (array) $data_obj;
                if (array_key_exists('cities', $data) && $data['cities']) {
                    $new_cities = [];
                    $new_cities = explode(";", $data['cities']);
                    $cities = array_merge($cities, $new_cities);
                }
                if (\array_key_exists('city', $data) && $data['city']) {
                    $cities[] = $data['city'];
                }
            }
        }
        if ($mapSearchesData) {
            foreach ($mapSearchesData as $mapSearch) {
                $cities[] = $mapSearch->city;
            }
        }
        if (count($cities) > 0) {
            $city_counts = array_count_values($cities);
            arsort($city_counts);
            $top_cities = array_slice($city_counts, 0, $n);
            $cities = array_keys($top_cities);
        }
        return $cities;
    }


    public function emailSavedSearchesListingUpdate()
    {
        SavedSearches::where(function ($query) {
            $query->where('just_listed_alert', 'y')->orWhere('just_sold_alert', 'y')->orWhere('price_alert', 'y');
        })->chunk(100, function ($searches) {
 
            foreach ($searches as $search) {
                $listings_from = Carbon::now()->subHours(2);
                $sentActive = 0;
                $sentSold = 0;
                $priceAlert = 0;
                if ($search->just_listed_alert == 'y') {
                    $sentActive = 1;
                }
                if ($search->just_sold_alert == 'y') {
                    $user = FirebaseUser::find($search->userid);
                    $sentSold = 1;
                }
                if ($search->price_alert == 'y') {
                    $priceAlert = 1;
                }

                $whereSqlAll = explode(' and ', \strtolower($search->listing_sql));
                $whereSqlNew = array();

                foreach ($whereSqlAll as $wheresql) {
                    if (strtolower(str_replace(' ', '', $wheresql)) == "status='sold'" || strtolower(str_replace(' ', '', $wheresql)) == "status='active'") {
                        continue;
                    }
                    if ((substr_count(strtolower($wheresql), 'list_date') > 0) || (substr_count(strtolower($wheresql), 'sold_date'))) {
                        continue;
                    }
                    $whereSqlNew[] = $wheresql;
                }

                $wheresql = implode(' AND ', $whereSqlNew);
                
                $listingsActive = array();
                $listingsSold = array();
                $listingsPriceUpdated = array();
                $listingsPriceUpdatedFull = array();

                if ($sentActive) {
                    $listingsActive = Listings::where('list_date', '>=', $listings_from)->where('status', 'Active')
                    ->whereNotIn('listingid', function ($query) use ($search) {
                        $query->select('mls')->from('bccondosandhomes.properties_emailed')->where('alert_type', 'saved_search_alert')->where('userid', $search->userid)->where('created_at', '>', Carbon::now()->subHours(10));
                    })
                    ->whereRaw($wheresql)->withCount('photos')->get();
                }
                
                if ($sentSold) {
                    $listingsSold = Listings::where('updated', '>=', $listings_from)->where('status', 'Sold')->whereNotIn('listingid', function ($query) use ($search) {
                        $query->select('mls')->from('bccondosandhomes.properties_emailed')->where('alert_type', 'saved_search_alert')->where('userid', $search->userid)->where('created_at', '>', Carbon::now()->subHours(10));
                    })->whereRaw($wheresql)->withCount('photos')->get();
                }


                if ($priceAlert) {
                    $listingsUpdated = Listings::where('updated', '>=', $listings_from)->where('status', 'Active')->whereNotIn('listingid', function ($query) use ($search) {
                        $query->select('mls')->from('bccondosandhomes.properties_emailed')->where('alert_type', 'saved_search_price_update')->where('userid', $search->userid)->where('created_at', '>', Carbon::now()->subHours(10));
                    })->whereRaw($wheresql)->get()->pluck('listingid')->toArray();
                    if ($listingsUpdated) {
                        $ids = "'" . implode("','", $listingsUpdated) . "'";
                        $query = "select listingid from boards.price_history where `change` < 0 and time_changed >'" . $listings_from . "' and listingid IN (" . $ids . ")  group by listingid order by time_changed desc";
                        $price_history =  DB::select(/*DB::raw*/($query));
                        foreach ($price_history as $history) {
                            $listingsPriceUpdated[] = $history->listingid;
                        }
                        if (count($listingsPriceUpdated) > 0) {
                            $listingsPriceUpdatedFull = Listings::whereIn('listingid', $listingsPriceUpdated)->withCount('photos')->get();
                        }
                    }
                }

                if (count($listingsActive) > 0 || count($listingsSold) > 0 || count($listingsPriceUpdated)) {
                    $userid = $search->userid;
                    $user = FirebaseUser::find($userid);
                    $search_name = $search->search_name;
                    $search_id = $search->id;
                    if ($sentActive) {
                        foreach ($listingsActive as $active_listing) {
                            $emailUniqueId = md5(time() . mt_rand(1, 1000000));
                            $mail = new SavedSearchesListingUpdate($user, $search_name, $active_listing->listingid, $emailUniqueId, $search_id);
                            Mail::to($user->email)->queue($mail);
                            $this->markPropertiesSent($user, $active_listing->listingid, $mail->render(), $emailUniqueId, 'saved_search_alert', 'Active', $active_listing->photos_count);
                        }
                    }
                    if ($sentSold) {
                        foreach ($listingsSold as $sold_listing) {
                            $emailUniqueId = md5(time() . mt_rand(1, 1000000));
                            $mail = new SavedSearchesListingUpdate($user, $search_name, $sold_listing->listingid, $emailUniqueId, $search_id);
                            Mail::to($user->email)->queue($mail);
                            $this->markPropertiesSent($user, $sold_listing->listingid, $mail->render(), $emailUniqueId, 'saved_search_alert', 'Sold', $sold_listing->photos_count);
                        }
                    }

                    if ($priceAlert) {
                        foreach ($listingsPriceUpdatedFull as $updated_listing) {
                            $emailUniqueId = md5(time() . mt_rand(1, 1000000));
                            $mail = new SavedSearchesPriceUpdate($user, $search_name, $updated_listing->listingid, $emailUniqueId, $search_id);
                            Mail::to($user->email)->queue($mail);
                            $this->markPropertiesSent($user, $updated_listing->listingid, $mail->render(), $emailUniqueId, 'saved_search_price_update', 'Active', $updated_listing->photos_count);
                        }
                    }

                    $search->last_update_sent = Carbon::now();
                    $search->save();
                }
            }
        });
    }

    public function emailFavoriteListingsUpdate()
    {
        // $updateTime = Carbon::now()->subHour(2);
        // $boardUpdateTime = Carbon::now()->subHours(5);
        $updateTime = Carbon::now()->subHour(100);
        $boardUpdateTime = Carbon::now()->subHours(100);
        FavoriteListings::with('listing')->with('user')->where('status', 'Active')->where('deleted', 0)->where(function ($query) {
            $query->where('status_update_notified', '!=', 'y')->orWhereNull('status_update_notified');
        })->whereIn('listingid', function ($query) use ($updateTime, $boardUpdateTime) {
            $query->select('listingid')->from('boards.listings')->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->where('table', 'mlsr_listings')->where('updated', '>=', $updateTime)->where('last_modified', '>=', $boardUpdateTime);
        })->chunk(100, function ($favorites) {
            foreach ($favorites as $favorite) {
                if ($favorite->listing) {
                    $last_update_sent = $favorite->created_at;
                    if ($favorite->last_update_sent) {
                        $last_update_sent = $favorite->last_update_sent;
                    }
                    if ($favorite->listing->updated < $last_update_sent) {
                        continue;
                    }
                    $last_price_sent = $favorite->price;
                    if ($favorite->last_price_sent) {
                        $last_price_sent = $favorite->last_price_sent;
                    }
                    if ($favorite->listing->status == 'Sold' || $favorite->listing->status == 'Terminated' || $favorite->listing->status == 'Expired') {
                        $mail = new FavoriteListingStatusUpdate($favorite->user, $favorite->listingid, $favorite->id);
                       // Mail::to($favorite->user->email)->queue($mail);
                        echo $mail->render();
                        //exit;
                        // $favorite->status_update_notified = 'y';
                        // $favorite->save();
                        // EmailsSent::create([
                        //     'userid' => $favorite->user->id,
                        //     'email' => $favorite->user->email,
                        //     'user_role' => 'USER',
                        //     'email_type' => 'favorite_listing_status_update',
                        //     'content' => $mail->render()
                        // ]);
                        continue;
                    } elseif ($favorite->listing->listprice_2 < $last_price_sent) {
                        $mail = new FavoriteListingPriceUpdate($favorite->user, $favorite->listingid, $favorite->id);
                        //Mail::to($favorite->user->email)->queue($mail);
                        echo $mail->render();
                        exit;
                        // $favorite->last_price_sent = $favorite->listing->listprice_2;
                        // $favorite->save();
                        // EmailsSent::create([
                        //     'userid' => $favorite->user->id,
                        //     'email' => $favorite->user->email,
                        //     'user_role' => 'USER',
                        //     'email_type' => 'favorite_listing_price_update',
                        //     'content' => $mail->render()
                        // ]);
                    }
                }
            }
        });
    }

    public function oneTimeStatEmail()
    {
        // $user = FirebaseUser::where('id',102)->first();
        // $agent = $user->getPrimaryAgent();
        //         if($agent){
        //             $mail = new OneTimeStatsEmail($user);
        //             Mail::to('parvinder@pixilink.com')->bcc("varinder@pixilink.com")->queue($mail);

        //             EmailsSent::create([
        //                 'userid'=>$user->id,
        //                 'email'=>$user->email,
        //                 'user_role'=>'USER',
        //                 'email_type'=>'one_time_stat_users',
        //                 'content'=>$mail->render()
        //             ]);

        //         }
        // FirebaseUser::where('agent', '!=', config('constants.demo_agent_id'))->where('agent','!=','2')->where('new_feature_notifications','y')->chunk(100, function ($users){
        //     foreach ($users as $user) {
        //         $agent = $user->getPrimaryAgent();
        //         if($agent){
        //             $mail = new OneTimeStatsEmail($user);
        //             //Mail::to('parvinder@pixilink.com')->queue($mail);
        //             Mail::to($user->email)->queue($mail);
        //             EmailsSent::create([
        //                 'userid'=>$user->id,
        //                 'email'=>$user->email,
        //                 'user_role'=>'USER',
        //                 'email_type'=>'one_time_stat_users',
        //                 'content'=>$mail->render()
        //             ]);

        //         }
        //     }
        // });
    }

    public function incomplete_users_5_minute_update()
    {

        $users = FirebaseUser::where('incomplete_signup_emails', 'y')->where('agent', '!=', config('constants.demo_agent_id'))->where('agent', '!=', 2)->whereNotNull('agent')->where('agent', '>', 0)->where('role', 'USER')->whereNull('phone')->whereNotIn('id', function ($query) {
            $query->select('userid')->from('emails_sent')->where('user_role', 'USER')->where('email_type', 'incomplete_user_5_minute_update');
        })->where('created_at', '>', DB::raw('DATE_SUB(now(), INTERVAL 2 HOUR)'))->where('created_at', '<', DB::raw('(NOW() - INTERVAL 5 MINUTE)'))->get();
        foreach ($users as $user) {
            $mail = new IncompleteUsers5MinuteUpdate($user);
            Mail::to($user->email)->queue($mail);
            EmailsSent::create([
                'userid' => $user->id,
                'email' => $user->email,
                'user_role' => 'USER',
                'email_type' => 'incomplete_user_5_minute_update',
                'content' => $mail->render()
            ]);
        }
    }

    public function incomplete_users_day_2_update()
    {

        $users = FirebaseUser::where('incomplete_signup_emails', 'y')->where('agent', '!=', config('constants.demo_agent_id'))->where('agent', '!=', 2)->where('role', 'USER')->whereNull('phone')->whereNotIn('id', function ($query) {
            $query->select('userid')->from('emails_sent')->where('user_role', 'USER')->where('email_type', 'incomplete_user_day_2_update');
        })->where('created_at', '>', DB::raw('DATE_SUB(now(), INTERVAL 26 HOUR)'))->where('created_at', '<', DB::raw('(NOW() - INTERVAL 24 HOUR)'))->get();

        foreach ($users as $user) {
            $mail = new IncompleteUsersDay2Update($user);
            Mail::to($user->email)->queue($mail);
            EmailsSent::create([
                'userid' => $user->id,
                'email' => $user->email,
                'user_role' => 'USER',
                'email_type' => 'incomplete_user_day_2_update',
                'content' => $mail->render()
            ]);
        }
    }

    public function incomplete_users_day_3_update()
    {

        $users = FirebaseUser::where('incomplete_signup_emails', 'y')->where('agent', '!=', config('constants.demo_agent_id'))->where('agent', '!=', 2)->where('role', 'USER')->whereNull('phone')->whereNotIn('id', function ($query) {
            $query->select('userid')->from('emails_sent')->where('user_role', 'USER')->where('email_type', 'incomplete_user_day_3_update');
        })->where('created_at', '>', DB::raw('DATE_SUB(now(), INTERVAL 50 HOUR)'))->where('created_at', '<', DB::raw('(NOW() - INTERVAL 48 HOUR)'))->get();

        foreach ($users as $user) {
            $mail = new IncompleteUsersDay3Update($user);
            Mail::to($user->email)->queue($mail);
            EmailsSent::create([
                'userid' => $user->id,
                'email' => $user->email,
                'user_role' => 'USER',
                'email_type' => 'incomplete_user_day_3_update',
                'content' => $mail->render()
            ]);
        }
    }

    public function weekly_properties_stats_to_all_users()
    {
        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);

        $first_day = Carbon::now()->subWeeks(2)->startOfWeek();
        $last_day = Carbon::now()->subWeeks(2)->endOfWeek();

        $first_day_last_week = Carbon::now()->subWeeks(3)->startOfWeek();
        $last_day_last_week = Carbon::now()->subWeeks(3)->endOfWeek();

        $vancouver_listed_current_week = Listings::where('board', 'Real Estate Board of Greater Vancouver')->where('list_date', '>=', $first_day)->where('list_date', '<=', $last_day)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->count();
        $vancouver_listed_current_week_price = Listings::where('board', 'Real Estate Board of Greater Vancouver')->where('list_date', '>=', $first_day)->where('list_date', '<=', $last_day)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->sum('listprice_2');
        $vancouver_sold_current_week =   Listings::where('board', 'Real Estate Board of Greater Vancouver')->where('status', 'Sold')->where('sold_date', '>=', $first_day)->where('sold_date', '<=', $last_day)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->count();
        $vancouver_sold_current_week_price =   Listings::where('board', 'Real Estate Board of Greater Vancouver')->where('status', 'Sold')->where('sold_date', '>=', $first_day)->where('sold_date', '<=', $last_day)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->sum('soldprice_2');
        $vancouver_price_drop_current_week = DB::select(/*DB::raw*/("select count(id) as count from boards.listings where board = 'Real Estate Board of Greater Vancouver' and listingid in (select listingid from boards.price_history where `change` < 0 and time_changed >= '" . $first_day . "' and time_changed <= '" . $last_day . "' )"))[0]->count;
        $vancouver_price_drop_current_week_price = DB::select(/*DB::raw*/("select abs(sum(`change`)) as count from boards.price_history where time_changed >= '" . $first_day . "' and time_changed <= '" . $last_day . "' and listingid in (select listingid from boards.listings where board = 'Real Estate Board of Greater Vancouver' and listingid in (select listingid from boards.price_history where `change` < 0 and time_changed >= '" . $first_day . "' and time_changed <= '" . $last_day . "' ))"))[0]->count;

        $fraser_valley_listed_current_week = Listings::where('board', 'Fraser Valley Real Estate Board')->where('list_date', '>=', $first_day)->where('list_date', '<=', $last_day)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->count();
        $fraser_valley_listed_current_week_price = Listings::where('board', 'Fraser Valley Real Estate Board')->where('list_date', '>=', $first_day)->where('list_date', '<=', $last_day)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->sum('listprice_2');
        $fraser_valley_sold_current_week =  Listings::where('board', 'Fraser Valley Real Estate Board')->where('status', 'Sold')->where('sold_date', '>=', $first_day)->where('sold_date', '<=', $last_day)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->count();
        $fraser_valley_sold_current_week_price =  Listings::where('board', 'Fraser Valley Real Estate Board')->where('status', 'Sold')->where('sold_date', '>=', $first_day)->where('sold_date', '<=', $last_day)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->sum('soldprice_2');
        $fraser_valley_price_dropped_current_week = DB::select(/*DB::raw*/("select count(id) as count from boards.listings where board = 'Fraser Valley Real Estate Board' and listingid in (select listingid from boards.price_history where `change` < 0 and time_changed >= '" . $first_day . "' and time_changed <= '" . $last_day . "' )"))[0]->count;
        $fraser_valley_price_dropped_current_week_price = DB::select(/*DB::raw*/("select abs(sum(`change`)) as count from boards.price_history where time_changed >= '" . $first_day . "' and time_changed <= '" . $last_day . "' and listingid in (select listingid from boards.listings where board = 'Fraser Valley Real Estate Board' and listingid in (select listingid from boards.price_history where `change` < 0 and time_changed >= '" . $first_day . "' and time_changed <= '" . $last_day . "' ))"))[0]->count;

        $chilliwack_listed_current_week = Listings::where('board', 'Chilliwack & District Real Estate Board')->where('list_date', '>=', $first_day)->where('list_date', '<=', $last_day)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->count();
        $chilliwack_listed_current_week_price = Listings::where('board', 'Chilliwack & District Real Estate Board')->where('list_date', '>=', $first_day)->where('list_date', '<=', $last_day)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->sum('listprice_2');
        $chilliwack_sold_current_week = Listings::where('board', 'Chilliwack & District Real Estate Board')->where('status', 'Sold')->where('sold_date', '>=', $first_day)->where('sold_date', '<=', $last_day)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->count();
        $chilliwack_sold_current_week_price = Listings::where('board', 'Chilliwack & District Real Estate Board')->where('status', 'Sold')->where('sold_date', '>=', $first_day)->where('sold_date', '<=', $last_day)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->sum('soldprice_2');
        $chilliwack_price_dropped_current_week = DB::select(/*DB::raw*/("select count(id) as count from boards.listings where board = 'Chilliwack & District Real Estate Board' and listingid in (select listingid from boards.price_history where `change` < 0 and time_changed >= '" . $first_day . "' and time_changed <= '" . $last_day . "' )"))[0]->count;
        $chilliwack_price_dropped_current_week_price = DB::select(/*DB::raw*/("select abs(sum(`change`)) as count from boards.price_history where time_changed >= '" . $first_day . "' and time_changed <= '" . $last_day . "' and listingid in (select listingid from boards.listings where board = 'Chilliwack & District Real Estate Board' and listingid in (select listingid from boards.price_history where `change` < 0 and time_changed >= '" . $first_day . "' and time_changed <= '" . $last_day . "' ))"))[0]->count;

        $vancouver_listed_last_week = Listings::where('board', 'Real Estate Board of Greater Vancouver')->where('list_date', '>=', $first_day_last_week)->where('list_date', '<=', $last_day_last_week)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->count();
        $vancouver_sold_last_week  = Listings::where('board', 'Real Estate Board of Greater Vancouver')->where('status', 'Sold')->where('sold_date', '>=', $first_day_last_week)->where('sold_date', '<=', $last_day_last_week)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->count();
        $vancouver_price_drop_last_week = DB::select(/*DB::raw*/("select count(id) as count from boards.listings where board = 'Real Estate Board of Greater Vancouver' and listingid in (select listingid from boards.price_history where `change` < 0 and time_changed >= '" . $first_day_last_week . "' and time_changed <= '" . $last_day_last_week . "' )"))[0]->count;

        $fraser_valley_listed_last_week = Listings::where('board', 'Fraser Valley Real Estate Board')->where('list_date', '>=', $first_day_last_week)->where('list_date', '<=', $last_day_last_week)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->count();
        $fraser_valley_sold_last_week = Listings::where('board', 'Fraser Valley Real Estate Board')->where('status', 'Sold')->where('sold_date', '>=', $first_day_last_week)->where('sold_date', '<=', $last_day_last_week)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->count();
        $fraser_valley_price_dropped_last_week = DB::select(/*DB::raw*/("select count(id) as count from boards.listings where board = 'Fraser Valley Real Estate Board' and listingid in (select listingid from boards.price_history where `change` < 0 and time_changed >= '" . $first_day_last_week . "' and time_changed <= '" . $last_day_last_week . "' )"))[0]->count;

        $chilliwack_listed_last_week = Listings::where('board', 'Chilliwack & District Real Estate Board')->where('list_date', '>=', $first_day_last_week)->where('list_date', '<=', $last_day_last_week)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->count();
        $chilliwack_sold_last_week =  Listings::where('board', 'Chilliwack & District Real Estate Board')->where('status', 'Sold')->where('sold_date', '>=', $first_day_last_week)->where('sold_date', '<=', $last_day_last_week)->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->count();
        $chilliwack_price_dropped_last_week = DB::select(/*DB::raw*/("select count(id) as count from boards.listings where board = 'Chilliwack & District Real Estate Board' and listingid in (select listingid from boards.price_history where `change` < 0 and time_changed >= '" . $first_day_last_week . "' and time_changed <= '" . $last_day_last_week . "' )"))[0]->count;

        $stats = [
            'vancouver_listed_current_week' => $vancouver_listed_current_week,
            'vancouver_sold_current_week' => $vancouver_sold_current_week,
            'vancouver_price_drop_current_week' => $vancouver_price_drop_current_week,
            'fraser_valley_listed_current_week' => $fraser_valley_listed_current_week,
            'fraser_valley_sold_current_week' => $fraser_valley_sold_current_week,
            'fraser_valley_price_dropped_current_week' => $fraser_valley_price_dropped_current_week,
            'chilliwack_listed_current_week' => $chilliwack_listed_current_week,
            'chilliwack_sold_current_week' => $chilliwack_sold_current_week,
            'chilliwack_price_dropped_current_week' => $chilliwack_price_dropped_current_week,
            'vancouver_listed_last_week' => $vancouver_listed_last_week,
            'vancouver_sold_last_week' => $vancouver_sold_last_week,
            'vancouver_price_drop_last_week' => $vancouver_price_drop_last_week,
            'fraser_valley_listed_last_week' => $fraser_valley_listed_last_week,
            'fraser_valley_sold_last_week' => $fraser_valley_sold_last_week,
            'fraser_valley_price_dropped_last_week' => $fraser_valley_price_dropped_last_week,
            'chilliwack_listed_last_week' => $chilliwack_listed_last_week,
            'chilliwack_sold_last_week' => $chilliwack_sold_last_week,
            'chilliwack_price_dropped_last_week' => $chilliwack_price_dropped_last_week,
            'vancouver_listed_current_week_price' => $this->number_shorten($vancouver_listed_current_week_price),
            'vancouver_sold_current_week_price' => $this->number_shorten($vancouver_sold_current_week_price),
            'fraser_valley_listed_current_week_price' => $this->number_shorten($fraser_valley_listed_current_week_price),
            'fraser_valley_sold_current_week_price' => $this->number_shorten($fraser_valley_sold_current_week_price),
            'chilliwack_listed_current_week_price' => $this->number_shorten($chilliwack_listed_current_week_price),
            'chilliwack_sold_current_week_price' => $this->number_shorten($chilliwack_sold_current_week_price),
            'vancouver_price_drop_current_week_price' => $this->number_shorten($vancouver_price_drop_current_week_price),
            'fraser_valley_price_dropped_current_week_price' => $this->number_shorten($fraser_valley_price_dropped_current_week_price),
            'chilliwack_price_dropped_current_week_price' => $this->number_shorten($chilliwack_price_dropped_current_week_price),
            'week_number' => $first_day->weekOfYear,
            'first_day' => $first_day,
            'last_day' => $last_day
        ];

        FirebaseUser::where(function ($query) {
            $query->where(function ($q) {
                $q->where('agent', '!=', config('constants.demo_agent_id'))->where('agent', '!=', '2')->where('agent', '>', 0);
            })->orWhere('register_as', 'AGENT');
        })->where('weekly_real_estate_stat', 'y')->chunk(100, function ($users) use ($stats) {
            foreach ($users as $user) {
                $mail = new WeeklyPropertiesStatsAllUsers($user, $stats);
                Mail::to($user->email)->queue($mail);
                EmailsSent::create([
                    'userid' => $user->id,
                    'email' => $user->email,
                    'user_role' => 'USER',
                    'email_type' => 'weekly_real_estate_stat',
                    'content' => $mail->render()
                ]);
            }
        });

        // $user = FirebaseUser::find(102);
        // $mail = new WeeklyPropertiesStatsAllUsers($user, $stats);
        // //Mail::to("parvinder@pixilink.com")->queue($mail);
        // echo $mail->render();
    }

    public function number_shorten($number, $precision = 3, $divisors = null)
    {

        // Setup default $divisors if not provided
        if (!isset($divisors)) {
            $divisors = array(
                pow(1000, 0) => '', // 1000^0 == 1
                pow(1000, 1) => 'K', // Thousand
                pow(1000, 2) => 'Million', // Million
                pow(1000, 3) => 'Billion', // Billion
                pow(1000, 4) => 'Trillion', // Trillion
                pow(1000, 5) => 'Quadrillion', // Quadrillion
                pow(1000, 6) => 'Quintillion', // Quintillion
            );
        }

        // Loop through each $divisor and find the
        // lowest amount that matches
        foreach ($divisors as $divisor => $shorthand) {
            if (abs($number) < ($divisor * 1000)) {
                // We found a match!
                break;
            }
        }

        // We found our match, or there were no matches.
        // Either way, use the last defined value for $divisor.
        if ($number == 0) {
            return 0;
        }
        if ($shorthand == 'K') {
            return number_format($number / $divisor, 0) . " " . $shorthand;
        } elseif ($shorthand == 'Million') {
            return number_format($number / $divisor, 2) . " " . $shorthand;
        } else {
            return number_format($number / $divisor, 2) . " " . $shorthand;
        }
    }

    public function emailAgentToActivateVow()
    {
        $sql = 'select map_searches.userid, map_searches.agent_id from map_searches, users where map_searches.created_at > DATE_SUB(NOW(), Interval 10 MINUTE) and agent_id in (select agent_id from pixilink_360.agents where vow_active = "no" and agent_id in (select distinct agent_id from map_searches where created_at > DATE_SUB(NOW(), Interval 10 MINUTE))) and map_searches.userid = users.id group by userid, agent_id order by userid';
        $results = DB::select(/*DB::raw*/($sql));
        foreach ($results as $result) {
            if ($result->userid && $result->agent_id) {
                $agent = Agents::find($result->agent_id);
                if ($agent) {
                    $mail = new ActivateVOWAgentAlert($result->userid, $result->agent_id);
                    Mail::to($agent->email)->queue($mail);
                    EmailsSent::create([
                        'userid' => $agent->agent_id,
                        'email' => $agent->email,
                        'user_role' => 'AGENT',
                        'email_type' => 'activate_vow_agent_alert',
                        'content' => $mail->render()
                    ]);
                }
            }
        }
    }

    public function emailRecentSoldToAdmin()
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $listings = Listings::with('photos')->where('status', 'Sold')->whereRaw('DATE(updated) = "' . $yesterday . '"')->where('soldprice_2', '>', '0')->where('virtualtoururl', 'like', '%pixilink%')->get();

        foreach ($listings as $listing) {
            //$listing = $listings;
            $photos = array();
            foreach ($listing->photos as $photo) {
                $photos[] = "https://media.pixilinkserver.com/" . str_replace('images', '', $photo->directory . $photo->name);
            }
            $decrease = $listing->listprice_2 - $listing->soldprice_2;
            $decrease_per = ($decrease / $listing->listprice_2) * 100;
            $soldprice_of_listingprice = number_format(100 - $decrease_per, 2);
            $property['mls'] = $listing->listingid;
            $property['status'] = $listing->status;
            $property['type'] = $listing->listingtype;
            $property['postalcode'] = $listing->postalcode;
            $property['streetaddress'] = $listing->streetaddress;
            $property['yearbuilt'] = $listing->yearbuilt;
            $property['virtualtour'] = $listing->virtualtoururl;
            $property['list_date'] = $listing->list_date;
            $property['listprice'] = $listing->listprice;
            $property['bedrooms'] = $listing->bedrooms;
            $property['kitchens'] = $listing->kitchens;
            $property['baths'] = $listing->bathstotal;
            $property['agent_name'] = $listing->agent_name;
            $property['city'] = $listing->city;
            $property['sold_date'] = $listing->sold_date;
            $property['soldprice'] = $listing->soldprice;
            $property['daysOnMarket'] = $listing->days_on_market();
            $property['soldprice_of_listingprice'] = $soldprice_of_listingprice;
            $property['photos'] = $photos;

            // select * from `order` where suite='9' and street_no='4388' and street_dir='' and street_name='Bayview' and street_type='Street' and city='Richmond' and postalcode= 'V7E 6S9' order by created desc

            //select * from order_items where order_id in (select id from `order` where suite='9' and street_no='4388' and street_dir='' and street_name='Bayview' and street_type='Street' and city='Richmond' and postalcode= 'V7E 6S9' ) order by inserted desc
            $orders = array();
            $sql = "select order_items.*, suppliers.display as supplier_name from pixilink_accounts.order_items 
            join pixilink_accounts.items on ( items.id = order_items.item_id)
            join pixilink_accounts.suppliers_items on (items.id = suppliers_items.item_id)
            join pixilink_accounts.suppliers on (suppliers_items.supplier_id = suppliers.id)
            where order_items.order_id in (select id from pixilink_accounts.`order` where suite='" . $listing->suite_no . "' and street_no='" . $listing->street_number . "' and street_dir='" . $listing->street_dir . "' and street_name='" . $listing->street_name . "' and street_type='" . $listing->street_type . "' and city='" . $listing->city . "' and postalcode= '" . $listing->postalcode . "' ) order by inserted desc LIMIT 10";
            $recentOrders = DB::select(/*DB::raw*/($sql));
            $i = 0;
            foreach ($recentOrders as $order) {
                $orders[$i]['item'] = $order->item;
                $orders[$i]['description'] = $order->description;
                $orders[$i]['inserted'] = $order->inserted;
                $orders[$i]['supplier'] = $order->supplier_name;
                $i++;
            }
            $property['orders'] = $orders;
            $mail = new RecentSoldToAdmin($property);
            Mail::to('mikezahora@pixilink.com')->cc('varinder@pixilink.com')->queue($mail);
            //echo $mail->render();
        }
    }

    public function tempEmail()
    {
        FirebaseUser::where('id', '>', 4893)->whereNull('agent')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $mail = new TempEmail($user);
                Mail::to($user->email)->queue($mail);
                EmailsSent::create([
                    'userid' => $user->id,
                    'email' => $user->email,
                    'user_role' => 'USER',
                    'email_type' => 'agents_page_link',
                    'content' => $mail->render()
                ]);
            }
        });
    }



    /**
     * emailFavoriteListingsTrackedUpdate [created:01-06-2022]
     * @return [type] [description]
     */
    public function emailFavoriteListingsTrackedUpdate()
    {
        // $updateTime = Carbon::now()->subHour(2);
        // $boardUpdateTime = Carbon::now()->subHours(5);
        $updateTime = Carbon::now()->subHour(100);
        $boardUpdateTime = Carbon::now()->subHours(100);
        FavoriteListings::with('listing')->with('user')
        ->where('status', 'Active')
        ->where('deleted', 0)
        ->whereIn('userid', [12376,8]) /* publish only-after-complete testing [subject -- correction:Price Drop/Status Update, db-update -- last_price_sent + last_update_sent ] */ // 12376:ds@pxlnk.clm, 8: vs@pxlnk.clm
        ->where('tracked', 1)
        ->where(function ($query) {
            $query->where('status_update_notified', '!=', 'y')->orWhereNull('status_update_notified');
        })->whereIn('listingid', function ($query) use ($updateTime, $boardUpdateTime) {
            $query->select('listingid')->from('boards.listings')->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))->whereIn('type', array('House', 'Townhouse', 'Apartment', 'Duplex', 'Fourplex', 'Triplex'))->where('table', 'mlsr_listings')->where('updated', '>=', $updateTime)->where('last_modified', '>=', $boardUpdateTime);
        })->chunk(100, function ($favorites) {
            foreach ($favorites as $favorite) {
                if ($favorite->listing) {
                    $last_update_sent = $favorite->created_at;
                    if ($favorite->last_update_sent) {
                        $last_update_sent = $favorite->last_update_sent;
                    }
                    if ($favorite->listing->updated < $last_update_sent) {
                        continue;
                    }
                    $last_price_sent = $favorite->price;
                    if ($favorite->last_price_sent) {
                        $last_price_sent = $favorite->last_price_sent;
                    }
                    if (in_array($favorite->listing->status, ['Sold','Terminated','Expired'])) {
                    // if (true) {
                        $mail = new FavoriteListingStatusUpdate($favorite->user, $favorite->listingid, $favorite->id);
                       Mail::to($favorite->user->email)->queue($mail);
                        /*echo*/ $mail->render();
                        //exit;
                        $favorite->status_update_notified = 'y';
                        $favorite->save();
                        EmailsSent::create([
                            'userid' => $favorite->user->id,
                            'email' => $favorite->user->email,
                            'user_role' => 'USER',
                            'email_type' => 'favorite_listing_tracked_status_update',
                            'content' => $mail->render()
                        ]);
                        continue;
                    // } elseif ($favorite->listing->listprice_2 < $last_price_sent) {
                    } 
                    if ($favorite->listing->listprice_2 != $last_price_sent) {
                        // $mail = new FavoriteListingTrackedPriceUpdate($favorite->user, $favorite->listingid, $favorite->id);
                        $mail = new \App\Mail\FavoriteListingTrackedPriceUpdate($favorite->user, $favorite->listingid, $favorite->id);
                        Mail::to($favorite->user->email)->queue($mail);
                        /*echo*/ $mail->render();
                        // exit;
                        $favorite->last_price_sent = $favorite->listing->listprice_2;
                        $favorite->save();
                        EmailsSent::create([
                            'userid' => $favorite->user->id,
                            'email' => $favorite->user->email,
                            'user_role' => 'USER',
                            'email_type' => 'favorite_listing_tracked_price_update',
                            'content' => $mail->render()
                        ]);
                    }
                }
            }
        });
    }


}
