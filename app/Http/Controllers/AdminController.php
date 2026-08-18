<?php

namespace App\Http\Controllers;

use App\Models\Auth\FirebaseUser;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSearches;
use App\Models\UserPropertyViews;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Agents;
use App\Models\Places;
use App\Models\Agent as AgentSite;

class AdminController extends Controller
{
    private function requirePixilinkAdmin()
    {
        $user = Auth::user();
        if (!$user) return redirect(route('login.with.agent'));
        $parts = explode('@', $user->email ?? '');
        if (($parts[1] ?? '') !== 'pixilink.com') return abort(403);
        return null;
    }

    public function places_index()
    {
        if ($r = $this->requirePixilinkAdmin()) return $r;

        $query = Places::query();

        if (request('q')) {
            $query->where('place', 'like', '%' . request('q') . '%');
        }
        if (request('type')) {
            $query->where('type', request('type'));
        }
        if (request('has_desc') === 'yes') {
            $query->whereNotNull('description')->where('description', '!=', '');
        } elseif (request('has_desc') === 'no') {
            $query->where(function($q) {
                $q->whereNull('description')->orWhere('description', '');
            });
        }

        $places = $query->orderBy('type')->orderBy('city')->orderBy('place')->paginate(50);

        return view('admin.places_index', compact('places'));
    }

    public function places_edit($id)
    {
        if ($r = $this->requirePixilinkAdmin()) return $r;

        $place = Places::findOrFail($id);
        return view('admin.places_edit', compact('place'));
    }

    public function places_update(Request $request, $id)
    {
        if ($r = $this->requirePixilinkAdmin()) return $r;

        $request->validate([
            'description' => 'nullable|string|max:2000',
        ]);

        $place = Places::findOrFail($id);
        $place->description = $request->input('description');
        $place->save();

        return redirect()->route('admin.places.index')
            ->with('success', "Description for \"{$place->place}\" saved successfully.");
    }

    //

    public function getAllUsers(){
        $request = request();
        $user = Auth::user();
        $userEmail = $user->email;
        $emailParts = explode('@', $userEmail);
        $suggestions = 0;
        $agent = NULL;
        if($request->get('agent')){
            $agent = $request->get('agent');
        }
        if($request->get('suggestions')){
            $suggestions = 1;
        }
        if($emailParts[1] != "pixilink.com"){
            return redirect(route('dashboard'));
        }
        $fromDate = NULL;
        $toDate = NULL;
        $fromRaw = $request->get('from_date');
        $toRaw = $request->get('to_date');
        if($request->get('from_date')){
            $fromDate = Carbon::createFromFormat('m/d/Y H:i:s', $request->get('from_date')." 00:00:00");
        }
        else{
            $fromDate = Carbon::now()->subWeek();
            $fromRaw = Carbon::now()->subWeek()->format('m/d/Y H:i:s');
        }
        if($request->get('to_date')){
            $toDate = Carbon::createFromFormat('m/d/Y H:i:s', $request->get('to_date')." 23:59:59");
        }else{
            $toDate = Carbon::now();
            $toRaw = Carbon::now()->format('m/d/Y H:i:s');
        }
        if($fromDate && $toDate){
            //where('created_at','>=',$fromDate)->where('created_at','<=', $toDate)


            $allUsers = FirebaseUser::with('agent1')->with(['searches' => function($searches) use ($fromDate, $toDate){
                return $searches->where('created_at','>=',$fromDate)->where('created_at','<=', $toDate);
            }])->with(['property_views' => function ($property_view) use ($fromDate, $toDate){
                return $property_view->where('created_at','>=',$fromDate)->where('created_at','<=', $toDate)->with('property');
            }])->orderby('agent')->get();
            #$allUsers = FirebaseUser::with('agent1')->where('phone', '!=','')->orderby('agent')->get();
            $onlyUsers = FirebaseUser::where('role', 'USER')->where('created_at','>=',$fromDate)->where('created_at','<=', $toDate)->get();
            $onlyAgents = FirebaseUser::where('role','AGENT')->where('created_at','>=',$fromDate)->where('created_at','<=', $toDate)->get();
            $allUserIds = $allUsers->pluck('id')->toArray();
            $userSearches = UserSearches::whereIn('userid', $allUserIds)->where('created_at','>=',$fromDate)->where('created_at','<=', $toDate)->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as searches'))->groupBy(DB::raw('date'))->orderBy('date')->get();
            $userPropertyViews = UserPropertyViews::whereIn('userid', $allUserIds)->where('created_at','>=',$fromDate)->where('created_at','<=', $toDate)->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as property_views'))->groupBy(DB::raw('date'))->orderBy('date')->get();
            $allTimeUsers = FirebaseUser::where('phone', '!=','')->where('role', 'USER');
            $allTimeAgents = FirebaseUser::where('role','AGENT')->where('phone', '!=','');
            $allTimeUsersIds = $allTimeUsers->select('id')->get()->pluck('id')->toArray();
            $allTimeAgentIds = $allTimeAgents->select('id')->get()->pluck('id')->toArray();
            $allTimeAllIds = array_merge($allTimeUsersIds, $allTimeAgentIds);
            $allTimeUserSearchesCount = UserSearches::whereIn('userid', $allTimeAllIds)->count();
            $allTimePropertyViews = UserPropertyViews::whereIn('userid', $allTimeAllIds)->count();
            $allTimeUsersCount = count($allTimeUsersIds);
            $allTimeAgentsCount = count($allTimeAgentIds);
        }
        else{
            #$allUsers = FirebaseUser::with('agent1')->with('searches')->with('property_views')->with('property_views.property')->where('phone', '!=','')->orderby('agent');
            $allUsers = FirebaseUser::with('agent1')->where('phone', '!=','')->orderby('agent');
            $onlyUsers = FirebaseUser::where('role', 'USER')->where('phone', '!=','');
            $onlyAgents = FirebaseUser::where('role','AGENT')->where('phone', '!=','');
            if($agent){
                $allUsers->where('agent',$agent);
                $onlyUsers->where('agent',$agent);
                $onlyAgents->where('agent', $agent);
            }
            $allUsers = $allUsers->get();
            $onlyUsers = $onlyUsers->get();
            $onlyAgents = $onlyAgents->get();
            $allUserIds = $allUsers->pluck('id')->toArray();
            $userSearches = UserSearches::whereIn('userid', $allUserIds)->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as searches'))->groupBy(DB::raw('date'))->orderBy('date')->get();
            $userPropertyViews = UserPropertyViews::whereIn('userid', $allUserIds)->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as property_views'))->groupBy(DB::raw('date'))->orderBy('date')->get();
            
        }
        
        $total_user_searches = 0;
        $total_property_views = 0;

        foreach($userSearches as $searches)
        {
            $total_user_searches = $total_user_searches+$searches->searches;
        }
        
        foreach($userPropertyViews as $propertyViews){
            $total_property_views = $total_property_views+$propertyViews->property_views;
        }

        return view('admin.users')->with([
            'allUsers'=>$allUsers,
            'onlyUsers'=>$onlyUsers,
            'onlyAgents'=>$onlyAgents,
            'noOfSearches'=>$total_user_searches,
            'noOfPropertyViews'=>$total_property_views,
            'userSearches'=>$userSearches,
            'userPropertyViews'=>$userPropertyViews,
            'fromRaw'=>$fromRaw,
            'toRaw'=>$toRaw,
            'suggestions'=>$suggestions,
            'allTimeUserSearchesCount'=>$allTimeUserSearchesCount,
            'allTimePropertyViews'=>$allTimePropertyViews,
            'allTimeUsersCount'=>$allTimeUsersCount,
            'allTimeAgentsCount'=>$allTimeAgentsCount
        ]);
    }

    public function show_agents(){
        $request = request();
        $all_agents = $request->get('all_agents');
        if($all_agents == 1){
            $query = "select agents.fname,agents.lname,agents.agency, vow_username, (select count(id) from pixilinkvow.users where agent=agents.agent_id and phone is not null and role='USER') as user_count, (select email from pixilinkvow.users where role='AGENT' and agent_pixilink_id = agents.agent_id LIMIT 1) as      fisherly_id, (select group_concat(distinct city) from boards.listings where agent_id = agents.mlsID and status = 'Active') as cities from pixilink_360.agents where agents.vow_active = 'yes' order by user_count desc";
        }
        else{
            $query = "select agents.fname,agents.lname,agents.agency, vow_username, (select count(id) from pixilinkvow.users where agent=agents.agent_id and phone is not null and role='USER') as user_count, (select email from pixilinkvow.users where role='AGENT' and agent_pixilink_id = agents.agent_id LIMIT 1) as      fisherly_id, (select group_concat(distinct city) from boards.listings where agent_id = agents.mlsID and status = 'Active') as cities from pixilink_360.agents where agents.vow_active = 'yes' having user_count >= 10 order by user_count desc";
        }
        $agents = DB::select(/*DB::raw*/($query));
        return view('admin.show_agents')->with([
            'agents'=>$agents
        ]);

    }

    public function show_users(){
        $request = request();
        $user = Auth::user();
        $userEmail = $user->email;
        $phone_verified = null;
        $emailParts = explode('@', $userEmail);
        if($emailParts[1] != "pixilink.com"){
            return abort(404);
        }
        $agents = array();
        if($request->get('agents')){
            $agents = $request->get('agents');
        }
        $conditions =[
            'eq'=>'=',
            'lt'=>'<',
            'gt'=>'>',
            'le'=>'<=',
            'ge'=>'>='
        ];
        if($request->get('from_date')){
            $firstday = Carbon::parse($request->get('from_date'));
        }
        else{
            $firstday = Carbon::now()->subDays(7);
        }
        if($request->get('to_date')){
            $lastday = Carbon::createFromFormat('m/d/Y H:i:s', $request->get('to_date')." 23:59:59");
        }
        else{
            $lastday = Carbon::now();
        }
        $users = FirebaseUser::with('agent1')->withCount('property_views')->withCount('saved_searches')->withCount('favorites')->where('role','USER')->whereNotNull('phone')->whereNotNull('agent')->where('agent','>',0)->where('created_at','>=', $firstday)->where('created_at','<=', $lastday);
        if($agents){
            $users = $users->whereIn('agent', $agents);
        }
        if($request->get('property_view_condition') && $request->get('property_views_count') && $request->get('property_views_count') > 0){
            $users = $users->having('property_views_count', $conditions[$request->get('property_view_condition')], $request->get('property_views_count'));
        }
        if(is_numeric($request->get('property_views_count')) && $request->get('property_views_count') == 0){
            $users = $users->having('property_views_count', '=', 0);
        }
        if($request->get('phone_verified')){
            $phone_verified = $request->get('phone_verified');
            if($phone_verified == 'yes'){
                $users = $users->where('phone_verified', 1);
            }
            else{
                $users = $users->where('phone_verified', 0);
            }
        }
        $users = $users->orderBy('created_at', 'desc')->get();
        $allAgents = Agents::whereIn('agent_id', function($query){
            $query->select('agent')->from('pixilinkvow.users')->whereNotNull('agent')->where('agent','>',0);
        })->orderBy('fname')->get();
        return view('admin.show_users')->with([
            'user'=>$user,
            'users'=>$users,
            'all_agents'=>$allAgents,
            'selected_agents'=>$agents,
            'params'=>$request->all(),
            'firstday'=>$firstday,
            'lastday'=>$lastday,
            'phone_verified'=>$phone_verified
        ]);
    }

    public function show_agents_report(){
        $user = Auth::user();
        $userEmail = $user->email;
        $emailParts = explode('@', $userEmail);
        if($emailParts[1] != "pixilink.com"){
            return abort(404);
        }
        $sql = "SELECT users.*, users.agent_pixilink_id as agent_pixi_id,
        (SELECT Count(id) 
         FROM   user_property_views 
         WHERE  userid = users.id)      AS property_view_count, 
        (SELECT Count(id) 
         FROM   user_property_views 
         WHERE  userid = users.id 
                AND device = 'Desktop') AS property_view_count_desktop, 
        (SELECT Count(id) 
         FROM   user_property_views 
         WHERE  userid = users.id 
                AND device = 'Mobile')  AS property_view_count_mobile, 
        (SELECT Count(id) 
         FROM   user_property_views 
         WHERE  userid = users.id 
                AND device = 'Tablet')  AS property_view_count_tablet, 
        (SELECT Count(id) 
         FROM   user_property_views 
         WHERE  userid = users.id 
                AND device IS NULL)     AS property_view_count_unknown,
         (SELECT Count(id) 
         FROM   map_searches
         WHERE  userid = users.id 
                )     AS map_searches,
        (SELECT Count(id) 
         FROM   user_searches
         WHERE  userid = users.id 
                )     AS old_searches,
                (SELECT created_at 
        FROM   user_property_views
        WHERE  userid = users.id order by created_at desc limit 1
               )     AS recent_property_viewed_at,
               (select count(id) from users where agent=agent_pixi_id and phone is not null and role='USER') as total_users,
               (select count(id) from user_property_views where agent_id=agent_pixi_id ) as users_property_views,
               (select count(id) from user_searches where agent_id=agent_pixi_id ) as user_searches_1,
               (select count(id) from map_searches where agent_id=agent_pixi_id ) as user_searches_2 
        FROM   users 
        WHERE  role = 'AGENT' 
                AND agent_pixilink_id IS NOT NULL order by created_at desc
        
        ";

        $agents =  DB::select(/*DB::raw*/($sql));

        return view('admin.show_agents_report')->with([
            'agents'=>$agents
            ]
        );
    }

    public function pending_review_accounts(){
        $user = Auth::user();
        $userEmail = $user->email;
        $emailParts = explode('@', $userEmail);
        if($emailParts[1] != "pixilink.com"){
            return abort(404);
        }
        
        $sql = "select users.first, users.last, users.phone, users.email, users.agent_pixilink_id, users.agent_mlsID, agents.agency, agents.address from pixilinkvow.users 
        left join pixilink_360.agents on (users.agent_pixilink_id = agents.agent_id)
        where users.pending_review = 1 ";

        $agents =  DB::select(/*DB::raw*/($sql));

        return view('admin.pending_review_accounts')->with([
            'agents'=>$agents
            ]
        );
        
    }

    // ── Agent Site Theme Management ──────────────────────────────

    public function agentThemesIndex()
    {
        if ($r = $this->requirePixilinkAdmin()) return $r;

        $agentSites = AgentSite::with('settings')->orderBy('name')->get();
        return view('admin.agent_themes', compact('agentSites'));
    }

    public function agentThemeUpdate(Request $request, int $id)
    {
        if ($r = $this->requirePixilinkAdmin()) return $r;

        $request->validate([
            'theme_slug'  => 'required|string|in:classic-dark,modern-white',
            'theme_color' => 'nullable|string|max:20',
        ]);

        $agentSite = AgentSite::findOrFail($id);
        $agentSite->theme_slug  = $request->input('theme_slug');
        $agentSite->theme_color = $request->input('theme_color', '#c9a96e');
        $agentSite->save();

        return redirect()->route('admin.agent-themes.index')
            ->with('success', "Theme updated for {$agentSite->name}.");
    }
}
