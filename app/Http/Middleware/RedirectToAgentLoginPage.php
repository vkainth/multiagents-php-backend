<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Agents;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RedirectToAgentLoginPage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if(!$user){
            $agent = NULL;
            if($request->get('agent')){
                $agent = $request->get('agent');
            }
            else{
                $params = $request->route()->parameters();
                if(array_key_exists('agentId', $params)){
                    $agent = $params['agentId'];
                }
            }
            if($agent){
                $checkAgent = Agents::where('vow_username', $agent)->where(function($query){
                    $query->where(function($q1){
                        $q1->where("agent_id", config('constants.demo_agent_id'));
                    })->orWhere(function($q){
                                $q
                                ->where('activated','Y')->where('suspended', 'n')
                                ->whereNotNull('mlsID')
                                ->where('mlsID', '!=', '')
                                ->whereIn('board', array(1,9,10));
                });
                })->first();

                //$checkAgent = Agents::where('vow_username', $agent)->count();
                if($checkAgent){
                        if($checkAgent->agent_id == config('constants.demo_agent_id') && $request->get('name') && $request->get('agency')){
                            $timeNow = Carbon::now();
                            $deadline = Carbon::parse('2019-04-30 23:59:59');
                            if($timeNow > $deadline){
                                return redirect(route('landing'));
                            }
                            $request->session()->put('name', $request->get('name'));
                            $request->session()->put('agency', $request->get('agency'));
                        }
                        if($request->get('listingid')){
                            $request->session()->put('intented_agent', $agent);
                            return Redirect::guest(route('login.with.agent', ['agentId'=>$agent, 'listingid'=>$request->get('listingid')]));
                        }
                        else{
                            $request->session()->put('intented_agent', $agent);
                            return Redirect::guest(route('login.with.agent', $agent));
                        }
                    
                }
                else{
                    return $next($request);
                }
            }
            else{
                return $next($request);
            }
        }else{
            $params = $request->route()->parameters();
            if(($params && is_array($params) && array_key_exists('agentId', $params) &&  $params['agentId']) || $request->get('agent')){
                if($params && is_array($params) && array_key_exists('agentId', $params) &&  $params['agentId']){
                    $agent = $params['agentId'];
                }
                else{
                    $agent = $request->get('agent');
                }
                
                $agentDetail = Agents::where('vow_username', $agent)->where(function($query){
                    $query->where(function($q1){
                        $q1->where("agent_id", config('constants.demo_agent_id'));
                    })->orWhere(function($q){
                                $q
                                ->where('activated','Y')->where('suspended', 'n')
                                ->whereNotNull('mlsID')
                                ->where('mlsID', '!=', '')
                                ->whereIn('board', array(1,9,10));
                });
                })->first();
                if($agentDetail && $agentDetail->agent_id == $user->login_with_agent){
                    return $next($request);
                }
                else{
                    if($request->get('listingid')){
                        $request->session()->put('intented_agent', $agent);
                        return Redirect::guest(route('login.with.agent', ['agentId'=>$agent, 'listingid'=>$request->get('listingid')]));
                    }
                    else{
                        $request->session()->put('intented_agent', $agent);
                        return Redirect::guest(route('login.with.agent', $agent));
                    }
                }
            }
        }
        return $next($request);
    }
}
