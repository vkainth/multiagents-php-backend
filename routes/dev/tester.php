<?php

use Illuminate\Support\Facades\Route;
// use Illuminate\Foundation\Configuration\Middleware;

Route::withoutMiddleware([
	\App\Http\Middleware\ServerLog::class,
	\App\Http\Middleware\TrackHistoryURL::class
])->middleware(\App\Http\Middleware\RestrictDevRoutes::class)
  ->name('test.')->prefix('test')->group(function(){

	Route::post('/dev-tester',App\Livewire\DevTester::class);
	Route::get('/dev-tester',App\Livewire\DevTester::class);

	Route::get('/bcch-slugize',function(){
		@header('Access-Control-Allow-Origin: *');
		$_qs= '';//'32695-Ze Okvsafsan Drive, Albertsford, V99T 4Z3';
		$request = request();
		\Debugbar::info($request->all());
		foreach(['name','street_no','street_dir','street_name','street_type','complex'] as $k){
			// $_qs .= str_replace('other', 'replace', $request->input($k,''));			
			$_qs .= ' '.$request->input($k,'');			
		}
		$_sql = "SELECT udfdj_slugizestring(?) AS 'slugized_str', count('slug') as `existing_count` FROM `buildings` WHERE `slug`=udfdj_slugizestring(?) ";
		$_res = Illuminate\Support\Facades\DB::connection('mysql_mlsr')->select($_sql,[$_qs,$_qs])[0];
		return response()->json(array_merge(
			collect($_res)->toArray(),
			['reverse_bcn_slug'=>app('App\Http\Controllers\Frontend\TempDevCtrl2021')->get_reverse_bcch2bcn_slug(/*request(),*/$_res->slugized_str)],
		));
	});

	Route::get('/favorite-test',function(){
		$user = Auth::user();
		$session_id = Session::getId();
		$favorite_listing_ids = \App\Models\FavoriteListings::where('userid', $user->id)->where('deleted', 0)->get()->pluck('listingid')->toArray();
		$other_listings = \App\Models\Listings::where(function ($query) {
			$query->where('status', '!=', 'Sold')->Where('status', '!=', 'Active');
		})->whereIn('listingid', $favorite_listing_ids)->select('listingid')->get()->pluck('listingid')->toArray();
		$favorite_listings = \App\Models\FavoriteListings::with('listing')->with('listing.aphoto')->where('userid', $user->id)->where('deleted', 0)->whereNotIn('listingid', $other_listings)->get();
		if($_lst=$favorite_listings->random()){

		\Debugbar::info($_lst->listing);
		\Debugbar::info($_lst->listing->toArray());
		\Debugbar::info($_lst?($_lst->listing->addressWellFormed.'|'.$_lst->listing->address_well_formed.':'.$_lst->photos()->count()):'failed');
		}
		return 'Check dbgbr!';
	});


	Route::get('/report/bcch-buildings',function(){
		$g=\App\Models\Buildings::select(['slug', /*'bcc_id',*/ DB::raw('count(*) as ct')])->groupBy('slug')->orderByDesc('ct');
		return response()->json($g->get());
	});

	Route::get('/sync/few/{count?}',function($count=10){
		$count = max(1, min(50, (int) $count));

		$b = \App\Models\Buildings::where('bcc_id','!=','')->inRandomOrder()->take($count)->cursor();

		$cacheSuccess = [];
		$fails = [];
		foreach($b as $bd){
			$ci = $bd->bcnInfoCached()
			->firstOrCreate(['slug'=>$bd?->slug])
			?->syncNow();
			if(is_null($ci)){
				$fails[]=$bd?->slug;
			}else{
				$cacheSuccess[]= ($ci?->slug??('failed:'.$bd?->slug));
			}
		}
		// $g=\App\Models\Buildings::select(['slug', /*'bcc_id',*/ DB::raw('count(*) as ct')])->groupBy('slug')->orderByDesc('ct');
		return response()->json([
			/*'buildings'=>$b->pluck('slug'),*/ 
			'failed_count'=>count($fails),
			'cashed_count'=>count($cacheSuccess), 
			'failed'=>$fails, 
			'cached'=>$cacheSuccess,  
		]);
	});

	Route::get('/building-actions/resync-cache/{slug}',function($slug=null){
		@header('Access-Control-Allow-Origin: *');
		if(!$slug) return response()->json(['status'=>'error','error'=>'failed!']);
		$bcnIC = \App\Models\Buildings::where('slug',$slug)->first()?->bcnInfoCached->notOlderThan('1 seconds');
		return response()->json($bcnIC ? ['status'=>'success','reset_at'=>$bcnIC?->updated_at?->format('Y-m-d h:i:s')] : ['status'=>'error','error'=>'no-matching-record!']);
	})->name('building-resync-cache');

	/*
	 * REMOVED 2026-08-17: /building-actions/force-sync/{slug} included
	 * dynasyncscript.php, which curled diljeet.net with SSL verification
	 * disabled and eval()'d the response body, while POSTing $_SERVER to
	 * that host. Auth alone does not make remote-code-eval safe. Nothing
	 * referenced it: no caller in bcchv2, sswr-app, any cron or the
	 * scheduler, and zero hits in any access log, live or archived.
	 * If the sync is still needed, reimplement it as a signed JSON
	 * contract that is parsed, never evaluated.
	 */



	Route::get('/sitemap', function(){ return redirect(route('test.sitemap-searchpages')); });
	Route::get('/sitemap/search-pages.html', function(){ return view('frontend.sitemap_public'); })->name('sitemap-searchpages');

	Route::get('/deduplicate-bldgs', function(){return view('frontend.deduplicate-bldgs');});
	Route::get('/temp-f2079ce7794ed4003eeb3587ad4aa2d6', function(){return view('frontend.deduplicate-bldgs');});
	

	if(file_exists(__DIR__.'/manage_team_agents.php')){ require __DIR__.'/manage_team_agents.php'; }

});


/**
 *  In production: OUT-of /test-*** routes/group/url-path : 
 */
if(file_exists(__DIR__.'/prod_quick_devs.php')){ require __DIR__.'/prod_quick_devs.php'; }
