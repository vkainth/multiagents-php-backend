<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Hashids\Hashids;
use App\Models\Agents;
use App\Models\Auth\FirebaseUser;
use App\Models\UserChangesLogs;
use App\Models\SavedSearches;
use function GuzzleHttp\json_encode;
use App\Models\FavoriteListings;

class EmailSubscriptionController extends Controller
{
    //

    public function unsubscribe_emails(){
        $request = request();
        $error = 0;
        $type = $request->get('type');
        $service = $request->get('service');
        $id = null;
        $info = null;
        $prevValue = 'y';
        $newValue = 'n';
        if($type == 'agent' && $service == 'newuser_notifications'){
            $token = $request->get('token');
            if(!$token){
                $error = 1;
            }
            else{
                $hashids = new Hashids(config('constants.email_token_salt'), config('constants.token_length'), config('constants.token_char'));
                $agentId = $hashids->decode($token);
                if(count($agentId) > 0){
                    Agents::where('agent_id', $agentId[0])->update([
                        'fisherly_notification_newuser' => 'n'
                    ]);
                    $id = $agentId[0];
                    
                }
                else{
                    $error = 1;
                }
            }

        }elseif($type == 'agent' && $service == 'weekly_stats'){
            $token = $request->get('token');
            if(!$token){
                $error = 1;
            }
            else{
                $hashids = new Hashids(config('constants.email_token_salt'), config('constants.token_length'), config('constants.token_char'));
                $agentId = $hashids->decode($token);
                if(count($agentId) > 0){
                    Agents::where('agent_id', $agentId[0])->update([
                        'fisherly_notification_weekly_stat' => 'n'
                    ]);
                    $id = $agentId[0];
                   
                }
                else{
                    $error = 1;
                }
            }
        }
        elseif($type=='user' && $service=="property_suggestions"){
            $token = $request->get('token');
            if(!$token){
                $error = 1;
            }
            else{
                $hashids = new Hashids(config('constants.email_token_salt'), config('constants.token_length'), config('constants.token_char'));
                $userId = $hashids->decode($token);
                if(count($userId) > 0){
                    FirebaseUser::where('id', $userId[0])->update([
                        'property_suggestion_emails'=>'n'
                    ]);
                    $id = $userId[0];
                   
                }
                else{
                    $error = 1;
                }
            }
        }elseif($type=='user' && $service=="new_feature_notifications"){
            $token = $request->get('token');
            if(!$token){
                $error = 1;
            }
            else{
                $hashids = new Hashids(config('constants.email_token_salt'), config('constants.token_length'), config('constants.token_char'));
                $userId = $hashids->decode($token);
                if(count($userId) > 0){
                    FirebaseUser::where('id', $userId[0])->update([
                        'new_feature_notifications'=>'n'
                    ]);
                    $id = $userId[0];
                   
                }
                else{
                    $error = 1;
                }
            }
        }elseif($type=='user' && $service=="incomplete_signup_emails"){
            $token = $request->get('token');
            if(!$token){
                $error = 1;
            }
            else{
                $hashids = new Hashids(config('constants.email_token_salt'), config('constants.token_length'), config('constants.token_char'));
                $userId = $hashids->decode($token);
                if(count($userId) > 0){
                    FirebaseUser::where('id', $userId[0])->update([
                        'incomplete_signup_emails'=>'n'
                    ]);
                    $id = $userId[0];
                   
                }
                else{
                    $error = 1;
                }
            }
        }
        elseif($type=='user' && $service=="saved_search_alert"){
            $token = $request->get('token');
            if(!$token){
                $error = 1;
            }
            else{
                $hashids = new Hashids(config('constants.email_token_salt'), config('constants.token_length'), config('constants.token_char'));
                $userId = $hashids->decode($token);
                $status = $request->get('status');
                $search_id = $request->get('search_id');
                if(count($userId) > 0 && $status && $search_id){
                    $search = SavedSearches::where('userid', $userId)->where('id', $search_id)->first();
                    if($search){
                        $old_values = [
                            'just_listed_alert'=>$search->just_listed_alert,
                            'just_sold_alert'=>$search->just_sold_alert,
                            'price_alert'=>$search->price_alert
                        ];
                        $search->just_listed_alert = 'n';
                        $search->just_sold_alert ='n';
                        $search->price_alert = 'n';
                        $search->save();
                        $new_values = [
                            'just_listed_alert'=>$search->just_listed_alert,
                            'just_sold_alert'=>$search->just_sold_alert,
                            'price_alert'=>$search->price_alert
                        ];
                        $prevValue = json_encode($old_values);
                        $newValue = json_encode($new_values);
                        $info = json_encode([
                            'search_id'=>$search->id,
                            'message_id'=>$request->get('message_id')
                        ]);
                    }
                    else{
                        $error = 1;
                    }
                }
                else{
                    $error = 1;
                }
            }
        }
        elseif($type=='user' && $service=="favorite_listing_update"){
            $token = $request->get('token');
            if(!$token){
                $error = 1;
            }
            else{
                $hashids = new Hashids(config('constants.email_token_salt'), config('constants.token_length'), config('constants.token_char'));
                $userId = $hashids->decode($token);
                if(count($userId) > 0){
                    if($request->get('id') && $request->get('id') > 0){
                        $fav_id = $request->get('id');
                        FavoriteListings::where('id', $fav_id)->where('userid', $userId)->update([
                            'deleted'=>'1'
                        ]);
                        $id = $userId[0];
                        $info = $fav_id;
                        $prevValue = 0;
                        $newValue = 1;
                    }
                    else{
                        $error = 1;
                    }
                }
                else{
                    $error = 1;
                }
            }
        }
        elseif($type=='user' && $service=="weekly_real_estate_stat"){
            $token = $request->get('token');
            if(!$token){
                $error = 1;
            }
            else{
                $hashids = new Hashids(config('constants.email_token_salt'), config('constants.token_length'), config('constants.token_char'));
                $userId = $hashids->decode($token);
                if(count($userId) > 0){
                    FirebaseUser::where('id', $userId[0])->update([
                        'weekly_real_estate_stat'=>'n'
                    ]);
                    $id = $userId[0];
                }
                else{
                    $error = 1;
                }
            }

        }
        else{
            $error = 1;
        }

        if($error == 0){
            UserChangesLogs::create([
                'userid'=> $id,
                'role'=> strtoupper($type),
                'activity_type'=>'update',
                'activity'=>$service,
                'prev_value'=>$prevValue,
                'new_value'=>$newValue,
                'info'=>$info
            ]);
        }
        return view('emails.unsubscribe')->with(
            [
                'error'=>$error
            ]
        );
    }
}
