<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use App\Http\Controllers\Frontend;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//public urls

//DsdhBrd@mapPage

/**
 * Agent path-prefix route group — dev/staging only.
 * Production: requests arrive via custom domain (randydyck.com) and are
 * resolved by ResolveAgent middleware without needing this prefix.
 *
 * All agent-facing routes belong here; the {agentSlug} parameter is picked
 * up by ResolveAgent middleware and removed before the inner routes see it.
 */
Route::prefix('agent/{agentSlug}')
    ->name('agent.')
    ->middleware('resolve.agent')
    ->group(function () {

        // ── Core pages ───────────────────────────────────────────────────────
        Route::get('/', [\App\Http\Controllers\Frontend\AgentController::class, 'home'])->name('home');
        Route::get('/search', [\App\Http\Controllers\Frontend\AgentController::class, 'search'])->name('search');
        Route::get('/sold', [\App\Http\Controllers\Frontend\AgentController::class, 'sold'])->name('sold');
        Route::get('/about', [\App\Http\Controllers\Frontend\AgentController::class, 'about'])->name('about');
        Route::get('/contact', [\App\Http\Controllers\Frontend\AgentController::class, 'contact'])->name('contact');

        // ── Listing & Building detail ────────────────────────────────────────
        Route::get('/buildings', [\App\Http\Controllers\Frontend\AgentController::class, 'buildings'])->name('buildings');
        Route::get('/listing/{listingSlug}', [\App\Http\Controllers\Frontend\AgentController::class, 'listingDetail'])->name('listing');
        Route::get('/sold/{listingSlug}', [\App\Http\Controllers\Frontend\AgentController::class, 'listingDetailSold'])->name('listing.sold');
        Route::get('/building/{buildingSlug}', [\App\Http\Controllers\Frontend\AgentController::class, 'buildingDetail'])->name('building');

        // ── Market ───────────────────────────────────────────────────────────
        Route::get('/market-stats', [\App\Http\Controllers\Frontend\AgentController::class, 'marketStats'])->name('market-stats');
        Route::get('/market-stats/{year}/{month}', [\App\Http\Controllers\Frontend\AgentController::class, 'monthlyReport'])->name('market-stats.month')->where('year', '[0-9]{4}')->where('month', '[0-9]{1,2}');
        // Legacy paths — 301 redirect to canonical /market-stats equivalents
        Route::get('/market', function (string $agentSlug) {
            return redirect(route('agent.market-stats', ['agentSlug' => $agentSlug]), 301);
        });
        Route::get('/market/{year}/{month}', function (string $agentSlug, string $year, string $month) {
            return redirect(route('agent.market-stats.month', ['agentSlug' => $agentSlug, 'year' => $year, 'month' => $month]), 301);
        })->where('year', '[0-9]{4}')->where('month', '[0-9]{1,2}');
        Route::get('/market-reports', [\App\Http\Controllers\Frontend\AgentController::class, 'marketReportHub'])->name('market-report-hub');
        Route::get('/market-reports/{period}', [\App\Http\Controllers\Frontend\AgentController::class, 'marketReport'])->name('market-report');

        // ── Neighbourhoods ────────────────────────────────────────────────────
        Route::get('/neighbourhoods', [\App\Http\Controllers\Frontend\AgentController::class, 'neighbourhoodHub'])->name('neighbourhoods');
        Route::get('/neighbourhoods/{citySlug}', [\App\Http\Controllers\Frontend\AgentController::class, 'neighbourhood'])->name('neighbourhood');
        Route::get('/neighbourhoods/{citySlug}/{subareaSlug}', [\App\Http\Controllers\Frontend\AgentController::class, 'neighbourhood'])->name('neighbourhood.subarea');

        // ── Property type hubs ────────────────────────────────────────────────
        Route::get('/condos', [\App\Http\Controllers\Frontend\AgentController::class, 'propertyTypeHub'])->defaults('type', 'condos')->name('condos');
        Route::get('/townhouses', [\App\Http\Controllers\Frontend\AgentController::class, 'propertyTypeHub'])->defaults('type', 'townhouses')->name('townhouses');
        Route::get('/houses', [\App\Http\Controllers\Frontend\AgentController::class, 'propertyTypeHub'])->defaults('type', 'houses')->name('houses');
        Route::get('/property-type/{type}', [\App\Http\Controllers\Frontend\AgentController::class, 'propertyTypeHub'])->name('property-type');

        // ── Houses market pages (city → subarea) ──────────────────────────────
        Route::get('/houses/{citySlug}', [\App\Http\Controllers\Frontend\AgentController::class, 'houseCity'])->name('houses.city');
        Route::get('/houses/{citySlug}/{subareaSlug}', [\App\Http\Controllers\Frontend\AgentController::class, 'houseSubarea'])->name('houses.subarea');

        // ── Townhouses market pages (city → subarea) ──────────────────────────
        Route::get('/townhouses/{citySlug}', [\App\Http\Controllers\Frontend\AgentController::class, 'townhouseCity'])->name('townhouses.city');
        Route::get('/townhouses/{citySlug}/{subareaSlug}', [\App\Http\Controllers\Frontend\AgentController::class, 'townhouseSubarea'])->name('townhouses.subarea');

        // ── Guides ────────────────────────────────────────────────────────────
        Route::get('/buyers-guide', [\App\Http\Controllers\Frontend\AgentController::class, 'buyersGuide'])->name('buyers-guide');
        Route::get('/sellers-guide', [\App\Http\Controllers\Frontend\AgentController::class, 'sellersGuide'])->name('sellers-guide');

        // ── Lead capture ──────────────────────────────────────────────────────
        Route::get('/home-evaluation', [\App\Http\Controllers\Frontend\AgentController::class, 'homeEvaluation'])->name('home-evaluation');
        Route::post('/lead', [\App\Http\Controllers\Frontend\AgentLeadController::class, 'store'])->name('lead.store')->middleware('throttle:30,1');

        // ── Extras ────────────────────────────────────────────────────────────
        Route::get('/open-houses', [\App\Http\Controllers\Frontend\AgentController::class, 'openHouses'])->name('open-houses');
        Route::get('/schools', [\App\Http\Controllers\Frontend\AgentController::class, 'schoolCatchment'])->name('school-catchment');
        Route::get('/schools/{citySlug}', [\App\Http\Controllers\Frontend\AgentController::class, 'schoolCatchment'])->name('school-catchment.city');
        Route::get('/lifestyle/{lifestyle}', [\App\Http\Controllers\Frontend\AgentController::class, 'lifestyleHub'])->name('lifestyle');
    });

// =====================================================================
// Agent Portal — /agent-portal/*
// Agents log in here to manage their white-label site.
// Auth guard: 'agent' (separate session from public site users).
// =====================================================================
Route::prefix('agent-portal')->name('agent-portal.')->group(function () {

    // --- Guest-only (redirect to dashboard if already logged in) ---
    Route::middleware(\App\Http\Middleware\RedirectIfAgentAuthenticated::class)->group(function () {
        Route::get('/login',            [\App\Http\Controllers\AgentPortal\AuthController::class, 'showLogin'])->name('login');
        Route::post('/login',           [\App\Http\Controllers\AgentPortal\AuthController::class, 'login'])->name('login.submit');
        Route::get('/password/reset',   [\App\Http\Controllers\AgentPortal\AuthController::class, 'showForgotPassword'])->name('password.request');
        Route::post('/password/email',  [\App\Http\Controllers\AgentPortal\AuthController::class, 'sendPasswordResetLink'])->name('password.email')->middleware('throttle:5,1');
        Route::get('/password/reset/{token}', [\App\Http\Controllers\AgentPortal\AuthController::class, 'showResetPassword'])->name('password.reset');
        Route::post('/password/reset',  [\App\Http\Controllers\AgentPortal\AuthController::class, 'resetPassword'])->name('password.update');
    });

    // --- Authenticated agent routes ---
    Route::middleware(\App\Http\Middleware\AuthenticateAgent::class)->group(function () {
        Route::post('/logout', [\App\Http\Controllers\AgentPortal\AuthController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/',          [\App\Http\Controllers\AgentPortal\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [\App\Http\Controllers\AgentPortal\DashboardController::class, 'index']);

        // Profile & branding
        Route::get('/profile',   [\App\Http\Controllers\AgentPortal\ProfileController::class, 'index'])->name('profile');
        Route::patch('/profile', [\App\Http\Controllers\AgentPortal\ProfileController::class, 'update'])->name('profile.update');

        // Testimonials
        Route::get('/testimonials',                    [\App\Http\Controllers\AgentPortal\TestimonialsController::class, 'index'])->name('testimonials');
        Route::patch('/testimonials/{testimonial}/toggle', [\App\Http\Controllers\AgentPortal\TestimonialsController::class, 'toggleVisible'])->name('testimonials.toggle');

        // Featured listings
        Route::get('/featured-listings',        [\App\Http\Controllers\AgentPortal\FeaturedListingsController::class, 'index'])->name('featured-listings');
        Route::get('/featured-listings/search', [\App\Http\Controllers\AgentPortal\FeaturedListingsController::class, 'search'])->name('featured-listings.search');
        Route::post('/featured-listings',       [\App\Http\Controllers\AgentPortal\FeaturedListingsController::class, 'save'])->name('featured-listings.save');

        // Leads
        Route::get('/leads',                       [\App\Http\Controllers\AgentPortal\LeadsController::class, 'index'])->name('leads');
        Route::patch('/leads/{lead}/contacted',    [\App\Http\Controllers\AgentPortal\LeadsController::class, 'markContacted'])->name('leads.contacted');
        Route::get('/leads/export',                [\App\Http\Controllers\AgentPortal\LeadsController::class, 'export'])->name('leads.export');

        // Settings
        Route::get('/settings',    [\App\Http\Controllers\AgentPortal\SettingsController::class, 'index'])->name('settings');
        Route::patch('/settings',  [\App\Http\Controllers\AgentPortal\SettingsController::class, 'update'])->name('settings.update');

        // Analytics placeholder
        Route::get('/analytics', function () {
            $portalAgent = \Illuminate\Support\Facades\Auth::guard('agent')->user();
            return view('agent-portal.analytics', compact('portalAgent'));
        })->name('analytics');
    });
});
// =====================================================================

Route::get('/unsubscribe_emails', 'App\Http\Controllers\EmailSubscriptionController@unsubscribe_emails')->name('unsubscribe_emails');
Route::post('/contact', 'App\Http\Controllers\ContactController@submit')->name('contact.submit');
Route::post('/ab-log', 'App\Http\Controllers\Frontend\BannerAbLogController@store')->name('ab-log')->middleware('throttle:60,1');
Route::get('/nearby-amenities', 'App\Http\Controllers\Frontend\NearbyAmenitiesController@fetch')->name('nearby-amenities')->middleware('throttle:30,1');

// ---- My Account & Alerts [Task#535] ----
Route::get('/my-account', 'App\Http\Controllers\Frontend\MyAccountController@index')->name('my-account')->middleware('auth');
Route::post('/user/reactivate-alert', 'App\Http\Controllers\Frontend\MyAccountController@reactivateAlert')->name('user.reactivate-alert')->middleware('auth');
Route::post('/user/link-guest-alerts', 'App\Http\Controllers\Frontend\MyAccountController@linkGuestAlerts')->name('user.link-guest-alerts')->middleware('auth');

// ---- End My Account & Alerts ----

Route::controller('App\Http\Controllers\Frontend\StaticController')->group(function(){
        Route::get('/privacy-policy', 'privacyPolicy')->name('privacy-policy');
        Route::get('/terms-and-conditions', 'termsConditions')->name('terms-and-conditions');
        Route::get('/rebgv-terms-and-conditions', 'rebgvTermsConditions')->name('rebgv-terms-and-conditions');
        Route::get('/about-us.html', 'aboutUs')->name('about-us.html');
        // Route::get('/blog.html', 'blog')->name('blog.html');

        //* ----- new-design (by Mark) [BEGINS] ---------  */
        Route::get('/{tsbPage}.html{idSection?}', 'tsbPages')->where('tsbPage','(team|sell|buy)')->where('idSection','(testimonials)')->name('tsb-pages');
        Route::get('/sell.html', 'sell')->name('sell.html');
        ///* ----- new-design (by Mark) [ENDS] ---------  */

        Route::get('/sellers-guide', 'sellersGuide')->name('sellers-guide');
        Route::get('/buyers-guide', 'buyersGuide')->name('buyers-guide');
        Route::get('/ssmuh-guide', 'ssmuhGuide')->name('ssmuh-guide');
        Route::get('/buying-a-duplex-bc', 'buyingDuplexGuide')->name('buying-duplex-guide');
        Route::get('/buying-a-fourplex-bc', 'buyingFourplexGuide')->name('buying-fourplex-guide');
        Route::get('/reviews', 'showReviews')->name('google-reviews');
});

Route::controller('App\Http\Controllers\Frontend\BuildingController')->group(function(){
        // Route::get('/buildings', 'showAllBuildings')->name('all-buildings'); // [03-02-2022 safe-to-del, merged inside route(city_buildings)]
        
        Route::get('/get_building_doc.pdf', 'get_building_doc')->name('get_building_doc');
        Route::get('/building/{slug}', 'showBuildingDetailPage')->name('building-detail-page')->middleware('google.auth');
        Route::get('/building_sold_listings', 'getSoldListings')->name('getBuildingSoldListings'); // auth-state handled inside controller/view; middleware removed so AJAX redirect-injection bug is fixed
        Route::get('/building_active_listings', 'getActiveListings')->name('getBuildingActiveListings');
        Route::get('/building-page/{strata_no}', 'building_redirect')->name('building_redirect');
        Route::get('/building-url/{strata_no}', 'get_building_url')->name('get_building_url'); /*Used>BCN:tpl/condo_new*/
        // Route::get('/the-building2/{slug}', 'showBuildingDetailPageUsingSlug2')->name('building-detail-page-slug2')->middleware('check.email.verified', /*'check.profile.completion'*/); // [Disabled:2025-05-23]
        // Route::get('/building/{slug}/new2', 'redirectBuildingDetailPageToUseSlug2')->name('redirect-2-building-detail-page-slug2')->middleware('check.email.verified', /*'check.profile.completion'*/); // [Disabled:2025-05-23]
        // Route::get('/building-slg{testnewslugnum}/{slug}', 'showBuildingDetailPageUsingTestNewSlugNum')->name('building-detail-page-testnewslugnum')->middleware('check.email.verified', /*'check.profile.completion'*/); // [Disabled:2025-05-23]
        // Route::get('/building/{slug}/slg{testnewslugnum}', 'redirectBuildingDetailPageToUseTestNewSlugNum')->name('redirect-2-building-detail-page-testnewslugnum')->middleware('check.email.verified', /*'check.profile.completion'*/); // [Disabled:2025-05-23]
});


Route::controller('App\Http\Controllers\Frontend\DashboardController')->group(function(){
        #Route::get('/', 'mapPage')->name('landing')->middleware('check.email.verified' /*,'check.profile.completion'*/);
        Route::get('/', function() {
            return response()->view('frontend.landingpage.landing')
                ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        })->name('homepage');
        Route::get('/home-evaluation', function() {
            return response()->view('frontend.landingpage.home_evaluation')
                ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        })->name('home-evaluation');
        Route::get('/home-evaluation.html', function() {
            return redirect('/home-evaluation', 301);
        });
        Route::get('/mapsearch', 'mapPage')->name('landing')->middleware('check.email.verified', 'check.profile.completion');
        // Route::get('/{beds}-bedroom-{slug}-for-sale', 'get_place_for_sale_with_beds')->where('beds','[A-Za-z0-9_\-]+'/* '[nx0-9\-\+]+'*/)->where('slug', '[A-Za-z0-9_\-]+')->name('for_sale_listings_nbeds');
        Route::get('/places.json', 'getPlaces')->name('getPlaces')->middleware('redirect.authenticated' /*,'check.email.verified',*/ /*'check.profile.completion'*/);
        Route::post('/click-event', 'storeClickEvent')->name('storeClickEvent')->middleware('redirect.authenticated' /*,'check.email.verified',*/ /*'check.profile.completion'*/);
        // Route::get('/{beds}-bedroom-{slug}-for-sale-{subarea?}', 'get_place_for_sale_with_beds')->where('beds','[A-Za-z0-9_\-]+' /*'[nx0-9\-\+]+'*/)->where('slug', '[A-Za-z0-9_\-]+')->where('subarea', '(.*)')->where('subarea', '[A-Za-z0-9_\-]+')->name('for_sale_listings_subarea_nbeds');
        // Route::any('/test/{slug}-for-sale-{subarea?}', 'get_place_for_sale')->where('slug', '[A-Za-z0-9_\-]+')->where('subarea', '(.*)')->where('subarea', '[A-Za-z0-9_\-]+')->name('test_for_sale_listings_subarea');
        // Route::get('/test/{beds}-bedroom-{slug}-for-sale-{subarea?}', 'render_for_sale_slugAndBedsFilteredListings')->where('beds','[A-Za-z0-9_\-]+' /*'[nx0-9\-\+]+(|\+|or-more|plus)'*/)->where('slug', '[A-Za-z0-9_\-]+')->where('subarea', '(.*)')->where('subarea', '[A-Za-z0-9_\-]+')->name('test_for_sale_listings_beds_subarea'); [commented:12-05-2022]
        // Route::any('/test/city-{city}-properties-for-sale', 'render_adv_search_listings')->where('city', '[A-Za-z0-9_\-]+')->name('for_sale_listings_city'); [commented:12-05-2022]
        // Route::any('/test/{city}-properties-for-sale-in-{subarea?}', 'render_adv_search_listings')->where('city', '[A-Za-z0-9_\-]+')->where('subarea', '(.*)')->where('subarea', '[A-Za-z0-9_\-]+')->name('for_sale_listings_city_subarea'); [commented:12-05-2022]
        // Route::any('/test/search-listings/{city?}-{subarea?}', 'render_adv_search_listings')->where('city','(.*)')->where('city', '[A-Za-z0-9_\-]+')->where('subarea','(.*)')->where('subarea', '[A-Za-z0-9_\-]+')->name('adv_search_listings');
        // Route::any('/test/{slug}-for-sale-{subarea?}', 'render_for_sale_slugFilteredListings')->where('slug', '[A-Za-z0-9_\-]+')->where('subarea', '(.*)')->where('subarea', '[A-Za-z0-9_\-]+')->name('test_for_sale_listings_subarea'); [commented:12-05-2022]
        // Route::get('/test-landing/{slug}-for-sale-{subarea}', 'get_place_for_sale_localdb')->where('slug', '[A-Za-z0-9_\-]+')->where('subarea', '[A-Za-z0-9_\-]+')->name('for_sale_listings_subarea_localdb'); [commented:12-05-2022]
});

Route::controller('App\Http\Controllers\Frontend\UserController')->group(function(){
        Route::get('/redirect', 'openLink')->name('open-hyperlink');
        Route::get('/get_session', 'get_session')->name('api:get_session');
        Route::get('/check_email_verification', 'check_email_verification')->name('check_email_verification');
        Route::get('/favorites', 'show_favorite_listings')->name('show_favorite_listings')->middleware('redirect.authenticated', 'check.profile.completion');
        Route::get('/favorites/tracked', 'show_favorite_tracked_listings')->name('show_favorite_tracked_listings')->middleware('redirect.authenticated', 'check.profile.completion');
        Route::get('/recall-history', 'recall_history')->name('recall-history');
        Route::post('/send-info-to-followupboss', 'send_info_to_followupboss')->name('send-info-to-followupboss');
});


Route::controller('App\Http\Controllers\Auth\LoginController')->group(function(){
        Route::get('/handle_auth', 'handle_auth')->name('handleAuth');
        Route::post('/handle_auth-json', 'handleAuthJson')->name('handleAuthJson');
        Route::get('/verify-email', 'verifyEmail')->name('verify-email')->middleware('redirect.authenticated');
        Route::get('/resend-verification', 'resendVerificationEmail')->name('resend-verification')->middleware('redirect.authenticated');
        Route::get('/logout', 'logout')->name('logout');
        Route::post('/agree-terms', 'agreeTerms')->name('agreeTerms')->middleware('redirect.authenticated', 'check.email.verified');
        Route::get('/complete-profile', 'step2')->name('step2')->middleware('redirect.authenticated', 'check.email.verified');
        Route::post('/complete-profile', 'completeProfile')->name('complete-profile')->middleware('redirect.authenticated', /*'check.email.verified',*/'handle.ref');
        Route::get('/confirm-phone-number', 'confirmPhoneNumber')->name('confirm-phone-number')->middleware('redirect.authenticated'/*,'check.email.verified',*/ /*'check.profile.completion'*/);
        Route::post('/confirm-phone-number', 'postConfirmPhoneNumber')->name('post-confirm-phone-number')->middleware('redirect.authenticated' /*,'check.email.verified',*/ /*'check.profile.completion'*/);
        Route::get('/login', 'loginPage')->name('login.with.agent')->middleware('listing.og.tags');
        Route::get('/register', 'loginPage')->name('register')->middleware('listing.og.tags');
});

Route::view('/home-preview', 'frontend.landingpage.landing')->name('home-preview');
Route::view('/new-home', 'frontend.landingpage.landing_v2')->name('home-v2');

// [Task#310] Redirect legacy /search/{...} URLs (missing -listings) to the canonical /search-listings/{...} equivalents
Route::get('/search/{city}/{subarea}/{type}', function ($city, $subarea, $type) {
    $qs = request()->query() ? '?' . http_build_query(request()->query()) : '';
    return redirect('/search-listings/' . $city . '/' . $subarea . '/' . $type . $qs, 301);
})->where('city', '[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+')
  ->where('subarea', '[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+')
  ->where('type', '[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+');

Route::get('/search/{city}/{subarea}', function ($city, $subarea) {
    $qs = request()->query() ? '?' . http_build_query(request()->query()) : '';
    return redirect('/search-listings/' . $city . '/' . $subarea . $qs, 301);
})->where('city', '[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+')
  ->where('subarea', '[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+');

Route::get('/search/{city}', function ($city) {
    $qs = request()->query() ? '?' . http_build_query(request()->query()) : '';
    return redirect('/search-listings/' . $city . $qs, 301);
})->where('city', '[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+');

// ---- FilteredSearch SEO landing pages — MUST come before the SearchListingsController catch-all routes ----
Route::controller('App\Http\Controllers\Frontend\FilteredSearchController')->group(function () {
    // Hub: /{city}-condos-for-sale
    Route::get('/{city}-condos-for-sale', 'hub')
        ->where('city', '[a-z][a-z0-9\-]+')
        ->name('filtered.hub');

    // Bedroom: /{N}-bedroom-condos-for-sale-{location}
    Route::get('/{beds}-bedroom-condos-for-sale-{location}', 'bedroom')
        ->where('beds', '[1-9][0-9]?')
        ->where('location', '[a-z][a-z0-9\-]+')
        ->name('filtered.bedroom');

    // Type: /{type}-for-sale-{city}  (only known types; catches before generic {slug}-for-sale-{subarea})
    Route::get('/{type}-for-sale-{city}', 'typeCity')
        ->where('type', 'townhouses|condos|apartments|detached|houses|duplexes|multi-family')
        ->where('city', '[a-z][a-z0-9\-]+')
        ->name('filtered.type-city');

    // Lifestyle pages
    Route::get('/pet-friendly-condos-{city}',   'petFriendly')->where('city', '[a-z][a-z0-9\-]+')->name('filtered.pet-friendly');
    Route::get('/ev-charging-condos-{city}',     'evCharging') ->where('city', '[a-z][a-z0-9\-]+')->name('filtered.ev-charging');
    Route::get('/rental-allowed-condos-{city}',  'rentalAllowed')->where('city', '[a-z][a-z0-9\-]+')->name('filtered.rental-allowed');

    // Near-landmark pages
    Route::get('/condos-near-{landmark}', 'landmark')
        ->where('landmark', '[a-z][a-z0-9\-]+')
        ->name('filtered.landmark');
});
// ---- End FilteredSearch routes ----

Route::controller('App\Http\Controllers\Frontend\SearchListingsController')->group(function(){
        Route::get('/{beds}-bedroom-{slug}-for-sale-{subarea?}', 'get_place_for_sale_with_beds')->where('beds','[A-Za-z0-9_\-]+' /*'[nx0-9\-\+]+(|\+|or-more|plus)'*/)->where('slug', '[A-Za-z0-9_\-]+')->where('subarea', '(.*)')->where('subarea', '[A-Za-z0-9_\-]+')->name('for_sale_listings_beds_subarea')->middleware(/*'force.subscribe'*/)/*->middleware('lscache:max-age=300;public')*/;
        Route::get('/{slug?}-for-sale', 'get_place_for_sale')->where('slug', '[A-Za-z0-9_\-]+')->name('for_sale_listings')->middleware(/*'force.subscribe'*/);
        Route::get('/{slug?}-for-sale-{subarea}', 'get_place_for_sale')->where('slug', '[A-Za-z0-9_\-]+')->where('subarea', '[A-Za-z0-9_\-]+')->name('for_sale_listings_subarea')->middleware(/*'force.subscribe'*/);
        #Route::get('/search-listings-demo/{city?}/{subarea?}/{type?}', 'render_adv_search_listings_2')->where('city','(.*)')->where('city', '[A-Za-z0-9_~\-]+')->where('subarea','(.*)')->where('subarea', '[A-Za-z0-9_~\-\.]+')->name('adv_search_listings_2');
        // Route::any('/search-listings-{slug?}/{subarea?}', 'render_for_sale_slugFilteredListings')->where('slug', '(.*)')->where('slug', '[A-Za-z0-9_\-]+')->where('subarea', '(.*)')->where('subarea', '[A-Za-z0-9_~\-]+')->name('listings-slugfiltered-subarea')->middleware(/*'force.subscribe'*/); // [Disabled:2025-06-27]
        // Route::get('/search-listings/{city?}/{subarea?}/{type?}', 'render_adv_search_listings')->where('city','(.*)')->where('city', '[A-Za-z0-9_~\-]+')->where('subarea','(.*)')->where('subarea', '[A-Za-z0-9_~\-\.]+')->name('adv_search_listings')->middleware(/*'force.subscribe'*/);
        // Feature/bedroom filtered pages — MUST come before the catch-all below [added:Task#8]
        Route::any('/search-listings/{city}/{subarea}/{type}/{feature}', 'render_adv_search_listings')
            ->where('city','[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+')
            ->where('subarea','[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+')
            ->where('type','[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+')
            ->where('feature','with-suite|with-basement|new-construction|[0-9]+-bedroom|under-[0-9]+[km]|over-[0-9]+[km]|[0-9]+[km]-to-[0-9]+[km]')
            ->name('adv_search_listings_feature');
        // 3-segment city/type/feature route — must come before the catch-all so feature slugs are not misread as subareas [added:Task#224]
        Route::any('/search-listings/{city}/{type}/{feature}', 'render_adv_search_listings')
            ->where('city','[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+')
            ->where('type','[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+')
            ->where('feature','with-suite|with-basement|new-construction|[0-9]+-bedroom|under-[0-9]+[km]|over-[0-9]+[km]|[0-9]+[km]-to-[0-9]+[km]')
            ->name('adv_search_listings_city_type_feature');
        Route::any('/search-listings/{city?}/{subarea?}/{type?}', 'render_adv_search_listings')
            ->where('city', '[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+')
            ->where('subarea', '[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+')
            ->where('type', '(?!with-suite$|with-basement$|new-construction$|\d+-bedroom$|under-\d+[km]$|over-\d+[km]$|\d+[km]-to-\d+[km]$)[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]*')
            ->name('adv_search_listings');
});

Route::controller('App\Http\Controllers\Frontend\ListingController')->group(function(){
        Route::get('/featured-listings', 'get_featured_listings')->name('featured-listings');
        Route::get('/our-solds', 'get_oursolds_listings')->name('our-solds');
        Route::get('/listing/{slug}', 'showListingDetailPage3')->name('listing-detail-page2')->middleware('google.auth')/*->middleware('listing.og.tags', 'check.email.verified', 'handle.ref', 'force.subscribe')*//*->middleware('lscache:max-age=7200;public')*/;
        Route::post('/update-wwr', 'updateWwr')->name('updatewwr');
});

Route::controller('App\Http\Controllers\Frontend\MarketReportController')->group(function(){
        Route::get('/market-report', 'hub')->name('market-report.hub');
        Route::get('/market-report/{citySlug}', 'cityHub')->name('market-report.city');
        Route::get('/market-report/{citySlug}/{subareaOrTypeSlug}', 'areaOrTypeArchive')->name('market-report.area');
        Route::get('/market-report/{citySlug}/{subareaOrTypeSlug}/{typeOrMonthSlug}', 'archiveOrReport')->name('market-report.area-report');
        Route::get('/market-report/{citySlug}/{subareaSlug}/{typeSlug}/{monthSlug}', 'monthlyReport')->name('market-report.report');
});

Route::controller('App\Http\Controllers\Frontend\MarketIntelController')->group(function(){
        Route::get('/market-update/{citySlug}', 'monthlyUpdateArchive')->name('market-update.archive');
        Route::get('/market-update/{citySlug}/{year}/{month}', 'monthlyUpdate')->where('year', '[0-9]{4}')->where('month', '1[0-2]|[1-9]')->name('market-update.report');
        Route::get('/new-listings/{citySlug}', 'newListings')->name('market-intel.new-listings');
        Route::get('/price-reductions/{citySlug}', 'priceReductions')->name('market-intel.price-reductions');
        Route::get('/sold-over-asking/{citySlug}', 'soldOverAsking')->name('market-intel.sold-over-asking');
        Route::post('/listing-alert', 'storeAlert')->name('market-intel.store-alert')->middleware('throttle:30,1');
});

Route::controller('App\Http\Controllers\Frontend\NeighbourhoodController')->group(function(){
        Route::get('/neighbourhood', 'index')->name('neighbourhood.hub');
        Route::get('/neighbourhood/', 'index');
        Route::get('/neighbourhood/{citySlug}', 'cityHub')->name('neighbourhood.city');
        Route::get('/neighbourhood/{citySlug}/', 'cityHub');
        Route::get('/neighbourhood/{citySlug}/{subareaSlug}', 'guide')->name('neighbourhood.guide');
        Route::get('/neighbourhood/{citySlug}/{subareaSlug}/', 'guide');
});

Route::controller('App\Http\Controllers\Frontend\SchoolCatchmentController')->group(function () {
        Route::get('/school-catchments/{citySlug}', 'index')->name('school-catchments.hub');
        Route::get('/school-catchment/{schoolSlug}', 'show')->name('school-catchment.show');
});

Route::controller('App\Http\Controllers\Frontend\TopRealtorController')->group(function(){
        Route::get('/top-realtor', 'hub')->name('top-realtor.hub');
        Route::get('/top-realtor/', 'hub');
        Route::get('/top-realtor/{citySlug}', 'city')->name('top-realtor.city');
        Route::get('/top-realtor/{citySlug}/', 'city');
        Route::get('/top-realtor/{citySlug}/{subareaSlug}', 'subarea')->name('top-realtor.subarea');
        Route::get('/top-realtor/{citySlug}/{subareaSlug}/', 'subarea');
});

Route::controller('App\Http\Controllers\Frontend\HouseMarketController')->group(function(){
        Route::get('/houses', 'hub')->name('houses.hub');
        Route::get('/houses/', 'hub');
        Route::get('/houses/{citySlug}', 'city')->name('houses.city');
        Route::get('/houses/{citySlug}/', 'city');
        Route::get('/houses/{citySlug}/{subareaSlug}', 'subarea')->name('houses.subarea');
        Route::get('/houses/{citySlug}/{subareaSlug}/', 'subarea');
});

Route::controller('App\Http\Controllers\Frontend\TownhouseMarketController')->group(function(){
        Route::get('/townhouses', 'hub')->name('townhouses.hub');
        Route::get('/townhouses/', 'hub');
        Route::get('/townhouses/{citySlug}', 'city')->name('townhouses.city');
        Route::get('/townhouses/{citySlug}/', 'city');
        Route::get('/townhouses/{citySlug}/{subareaSlug}', 'subarea')->name('townhouses.subarea');
        Route::get('/townhouses/{citySlug}/{subareaSlug}/', 'subarea');
});

Route::controller('App\Http\Controllers\Frontend\MultiFamilyMarketController')->group(function(){
        Route::get('/multi-family', 'hub')->name('multi-family.hub');
        Route::get('/multi-family/', 'hub');
        Route::get('/multi-family/{citySlug}', 'city')->name('multi-family.city');
        Route::get('/multi-family/{citySlug}/', 'city');
        Route::get('/multi-family/{citySlug}/{subareaSlug}', 'subarea')->name('multi-family.subarea');
        Route::get('/multi-family/{citySlug}/{subareaSlug}/', 'subarea');
});

Route::controller('App\Http\Controllers\Frontend\StatsController')->group(function(){
        Route::get('/statistics', 'getStats')->name('getWeeklyStats');
        Route::get('/stats_json', 'getStatsJson')->name('getStatsJson');
        Route::get('/building_stats_json', 'getBuildingStatsJson')->name('getBuildingStatsJson')->middleware('google.auth', 'redirect.authenticated');
        Route::get('/market-stats/{citySlug?}/{subareaOrTypeSlug?}/{typeSlug?}', 'getStatsNew')->name('getStatsNew');
});

/*
 * REMOVED 2026-08-17: the OfferlandPriceController routes.
 *
 * /offerlandprice/{ml_no?} answered anonymously with dd() of 50 rows from
 * offerland_prices (~25M rows) -- 312KB of ml_no / offer_value dumped to any
 * caller. dd()'s exit(1) made it look like a broken 500 while it was in fact
 * serving data. It also accepted ?create=<hardcoded literal> to trigger a DB
 * import. /export/data-for-offerland pointed at exportCsvToOfferland(), a
 * method that does not exist on the controller, and only ever 500'd.
 *
 * Removed at the operator's request: Offerland is not in use. Zero hits in
 * any access log, live or archived. The controller itself is left in place;
 * only the public routes are gone.
 */

Route::controller('App\Http\Controllers\SitemapController')->prefix('/sitemap')->name('sitemap.')->group(function(){
        Route::get('/index.xml', 'sitemap_index')->name('index');
        Route::get('/in-{postalarea}-active.xml', 'sitemap_active')->name('postalarea-active');
        Route::get('/in-{postalarea}-sold.xml', 'sitemap_sold')->name('postalarea-sold');
        Route::get('/buildings.xml', 'sitemap_buildings')->name('buildings-old-allin1');

        Route::get('/listings-lastweek-active.xml', 'sitemap_lastweek_active')->name('listings-lastweek-active');
        Route::get('/listings-lastweek-sold.xml', 'sitemap_lastweek_sold')->name('listings-lastweek-sold');
        Route::get('/listings-lastmonth-sold.xml', 'sitemap_lastmonth_sold')->name('listings-lastmonth-sold');
        Route::get('/listings-lastmonth-active.xml', 'sitemap_lastmonth_active')->name('listings-lastmonth-active');
        Route::get('/searchpages.xml', 'sitemap_searchpages')->name('searchpages');
        Route::get('/search-listings/{city?}/{subarea?}', 'sitemap_search_listings')->name('search-listings');
});
Route::get('/sitemap.xml', 'App\Http\Controllers\SitemapController@sitemap_index')->name('sitemap.root');
Route::get('/sitemap-{postalarea}-active.xml', 'App\Http\Controllers\SitemapController@sitemap_active')->name('sitemap.postalarea-active-root');
Route::get('/sitemap-{postalarea}-sold.xml', 'App\Http\Controllers\SitemapController@sitemap_sold')->name('sitemap.postalarea-sold-root');
Route::get('/sitemap-stats.xml', 'App\Http\Controllers\SitemapController@sitemap_stats')->name('sitemap.market-stats');
Route::get('/sitemap-reports.xml', 'App\Http\Controllers\SitemapController@sitemap_reports')->name('sitemap.market-reports');
Route::get('/sitemap-market-updates.xml', 'App\Http\Controllers\SitemapController@sitemap_market_updates')->name('sitemap.market-updates');
Route::get('/sitemap-neighbourhoods.xml', 'App\Http\Controllers\SitemapController@sitemap_neighbourhoods')->name('sitemap.neighbourhoods');
Route::get('/sitemap-top-realtor.xml', 'App\Http\Controllers\SitemapController@sitemap_top_realtor')->name('sitemap.top-realtor');
Route::get('/sitemap-houses.xml', 'App\Http\Controllers\SitemapController@sitemap_houses')->name('sitemap.houses');
Route::get('/sitemap-townhouses.xml', 'App\Http\Controllers\SitemapController@sitemap_townhouses')->name('sitemap.townhouses');
Route::get('/sitemap-multi-family.xml', 'App\Http\Controllers\SitemapController@sitemap_multi_family')->name('sitemap.multi-family');
Route::get('/sitemap-static.xml', 'App\Http\Controllers\SitemapController@sitemap_static')->name('sitemap.static');
Route::get('/sitemap-listings-active-index.xml', 'App\Http\Controllers\SitemapController@sitemap_listings_active_index')->name('sitemap.listings-active-index');
Route::get('/sitemap-listings-sold-index.xml', 'App\Http\Controllers\SitemapController@sitemap_listings_sold_index')->name('sitemap.listings-sold-index');
Route::get('/sitemap-adv-search-listings.xml', 'App\Http\Controllers\SitemapController@sitemap_adv_search_listings_city_type_feature')->name('sitemap.adv-search-listings');
Route::get('/sitemap-adv-search-listings-bedrooms.xml', 'App\Http\Controllers\SitemapController@sitemap_adv_search_listings_city_type_bedroom')->name('sitemap.adv-search-listings-bedrooms');
Route::get('/sitemap-adv-search-listings-subarea-bedrooms.xml', 'App\Http\Controllers\SitemapController@sitemap_adv_search_listings_subarea_type_bedroom')->name('sitemap.adv-search-listings-subarea-bedrooms');
Route::get('/sitemap-search-listings-city-type.xml', 'App\Http\Controllers\SitemapController@sitemap_search_listings_city_type')->name('sitemap.search-listings-city-type');
Route::get('/sitemap-buildings-city-landing.xml', 'App\Http\Controllers\SitemapController@sitemap_buildings_city_landing')->name('sitemap.buildings-city-landing');
Route::get('/sitemap-bedroom-landing-pages.xml', 'App\Http\Controllers\SitemapController@sitemap_bedroom_landing_pages')->name('sitemap.bedroom-landing-pages');
Route::get('/sitemap-filtered-search.xml', 'App\Http\Controllers\SitemapController@sitemap_filtered_search_pages')->name('sitemap.filtered-search');
Route::get('/sitemap-school-catchments.xml', 'App\Http\Controllers\SitemapController@sitemap_school_catchments')->name('sitemap.school-catchments');
Route::controller('App\Http\Controllers\SitemapController')->prefix('/approve/sitemap/')->name('sitemap.')->group(function(){
        Route::get('/buildings.xml', 'sitemap_buildings_city')->name('buildings');
        Route::get('/buildings/{city?}.xml', 'sitemap_buildings_city')->name('buildings-city');
});

/* news-blog section [STARTS]*/
Route::controller('App\Http\Controllers\Frontend\NewsarticleController')->group(function(){ 

        // Route::get('/blog', function(){return redirect()->route('news-blog-list');})->name('blog');
        Route::get('/sitemap/news-sitemaps.xml', 'showSiteMap')->name('news-sitemap');
        Route::get('/sitemap/news/{newsmode?}.xml', 'showSiteMap')->name('news-mode-sitemap-xml');
        Route::get('/sitemap/news/{newsmode?}', 'showSiteMap')->name('news-mode-sitemap');

        /*
        Route::get('/news/{newsmode?}', 'showNews')->where('newsmode','(blog|ca|victoria|mandarin)')->name('news-list');
        Route::get('/news/blog/category/{cat}/', 'showNews')->name('news-blog-cat-list');
        Route::get('/news/blog/{year}-{month}', 'showNews')->name('news-blog-year-month-list');
        Route::get('/news/blog', 'showNews')->name('news-blog-list');
        Route::get('/news/{s1?}/{s2?}/{s3?}', 'customUrlArgs')->name('news-blog-list-s3');
         */
        
        Route::get('/news', 'redirectToShowNews')->name('news-list');
        Route::get('/news/general', 'showNews')->name('news-list-general');
        Route::get('/news/victoria', 'showNewsVictoria')->name('news-list-victoria');
        Route::get('/news/mandarin', 'showNewsMandarin')->name('news-list-mandarin');
        Route::get('/news/blog/archive', 'showNewsBlog')->name('news-list-blog'); // similar to next
        Route::get('/news/{newsmode?}', 'showNews')->where('newsmode','(ca|int|victoria|mandarin)')->name('news-list-mode');

        Route::get('/news/blog/page/{pagenum}/', 'showNewsBlog')->name('news-blog-list-page');
        Route::get('/news/blog/id/{blogid}', 'showNewsBlog')->name('news-blog-id');
        Route::get('/news/blog/archive/{year}/{month}/page/{page}', 'showNewsBlogArchive')->name('news-blog-list-year-month-page');
        Route::get('/news/blog/archive/{year?}/{month?}', 'showNewsBlogArchive')->name('news-blog-list-year-month');
        Route::get('/news/blog/category-{categoryid}/', 'showNewsBlog')->name('news-blog-list-catid');
        Route::get('/news/blog/category/{catslug}/', 'showNewsBlog')->name('news-blog-list-cat');
        Route::get('/news/blog/{post_name}', 'showNewsBlog')->name('news-blog-post_name');
        Route::get('/news/blog', 'showNewsBlog')->name('news-blog-list');

        Route::get('/news/api/{newsmode?}', 'showNewsApi')->where('newsmode','(blog|victoria|mandarin|ca|int|general)')->name('news-api-list-mode');

        // Route::get('/news/{s1?}/{s2?}/{s3?}', 'customUrlArgs')->name('news-blog-list-s3');
});
/* news-blog section [ENDS]*/

Route::controller('App\Http\Controllers\Frontend\TempDevCtrl2021')->group(function(){
        Route::get('/buildings/{city?}/{subarea?}', 'showBuildingsListPage')->where('city', '[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+')->where('subarea', '(.*)')->where('subarea', '[A-Za-z0-9_~\,\-\.\(\)\[\]\%\⁄]+')->name('city_buildings');
        Route::get('/{city}-buildings', 'redirectOldUrlsToNewCityBuildingsPage')->where('city', '[A-Za-z0-9_\-]+')->where('subarea', '(.*)')->where('subarea', '[A-Za-z0-9_\-]+')->name('old_city_buildings_redirect2newmatching');
        Route::get('/{city}-buildings-{subarea?}', 'redirectOldUrlsToNewCityBuildingsPage')->name('old_city_buildings_subarea_redirect2newmatching');
        
        Route::get('/listing-redirect/{listingid}', 'redirectToListingDetailPage')->name('redirect-listingid-to-listing-detail-page'); /*Used>BCN:tpl/headerall.php*/
        Route::get('/bcchlistingurlfor/{listingid}', 'listingDetailPageUrlForListingId')->name('listing-detail-page-link-for-listingid'); /*Used>BCN:tpl/headerall.php*/
        // Route::get('/test/whatsmyhomeworth', 'whatsmyhomeworth')->name('whatsmyhomeworth'); // prefered direct-link [21-09-2022]
        Route::get('/getbuildingreversebcnslg/{slug?}', 'get_reverse_bcch2bcn_slug')->name('temp_building_reverse_bcch2bcn_slug')->middleware(/*'check.email.verified',*/ /*'check.profile.completion'*/);
        
        Route::any('/stdsdfl3-switch8slsdjfw', 'renderShowStgChngSwitch2')->name('test-stagfgd-switch');
        Route::get('/confirm-phone-number', 'confirmPhoneNumber')->name('test-confirm-phone-number')->middleware('google.auth', 'redirect.authenticated', 'check.email.verified', /*'check.profile.completion'*/);

        Route::get('/test/bcn-bcch-maptracer-modes', 'getReqModesJson')->name('test-bcn-bcch-maptracer-modes');
         // /* [Safe to Delete: 2025-07-01+] */ 
        // Route::post('/test-confirm-phone-number', 'testPostConfirmPhoneNumber')->name('test-post-confirm-phone-number')->middleware('redirect.authenticated', 'check.email.verified', /*'check.profile.completion'*/); // [Disabled:2025-05-23]
});

Route::controller('App\Http\Controllers\WebhookController')->group(function(){
        Route::post('/stripe/webhook', 'handleWebhook')->name('stripe-webhook')->middleware('stripe.webhook'); // new-cashier Ctrlr following:
        // Route::post('/stripe/webhook', 'Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook')->name('stripe-webhook')->middleware(/*'stripe.webhook'*/);
});

Route::controller('App\Http\Controllers\SubscriptionController')->group(function(){
        Route::any('/subscription-plans', 'showPricingTable')->name('subscription_pricing_table')->middleware('redirect.authenticated', /*'check.email.verified',*/ /*'check.profile.completion'*/);
        Route::get('/manage-subscription', 'manageSubscriptionPortal')->name('stripe-manage-subscription')->middleware('redirect.authenticated', /*'check.email.verified',*/ /*'check.profile.completion'*/);
        Route::get('/subscription-confirmation', 'subscriptionConfirmation')->name('subscription-confirmation')->middleware('redirect.authenticated', /*'check.email.verified',*/ /*'check.profile.completion'*/);
        Route::get('/pricing', 'showNewPricingPage')->name('new-pricing-page');
});



Route::controller('App\Http\Controllers\Frontend\UserListingController')->group(function () {
    // Step-by-step form
    Route::get('/userlistings/{building_id}/create', 'create')->name('userlistings.create')->middleware('redirect.authenticated');
    Route::post('/userlistings/store', 'store')->name('userlistings.store')->middleware('redirect.authenticated');
    Route::get('/userlistings/step/{step}', 'step')->name('userlistings.step')->middleware('redirect.authenticated');
    Route::post('/userlistings/step/save', 'stepSave')->name('userlistings.stepSave')->middleware('redirect.authenticated');

    // CRUD functionality
    Route::get('/userlistings', 'index')->name('userlistings.index')->middleware('redirect.authenticated');
    Route::get('/userlistings/edit/{id}', 'edit')->name('userlistings.edit')->middleware('redirect.authenticated');
    Route::put('/userlistings/update/{id}', 'update')->name('userlistings.update')->middleware('redirect.authenticated');
    Route::post('/userlistings/publish/{id}', 'publish')->name('userlistings.publish')->middleware('redirect.authenticated');
    Route::delete('/userlistings/delete/{id}', 'destroy')->name('userlistings.destroy')->middleware('redirect.authenticated');

        Route::post('/userlistings/publish/request/{id}', 'requestPublish')->name('userlistings.publish.request');
        Route::any('/userlistings/publish/verify/{id}', 'verifyPublish')->name('userlistings.verify');

        Route::post('/userlistings/active-toggle/{id}', 'toggleActive');


});



// =====================================================================
// Staff Admin Portal — /admin/*
// BC Condos & Homes internal dashboard: manage agents, leads, analytics.
// Auth guard: 'admin' (admins table — separate from agents and public users).
// =====================================================================
Route::prefix('admin')->name('admin.')->group(function () {

    // --- Guest-only ---
    Route::middleware(\App\Http\Middleware\RedirectIfAdminAuthenticated::class)->group(function () {
        Route::get('/login',  [\App\Http\Controllers\Admin\AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [\App\Http\Controllers\Admin\AuthController::class, 'login'])->name('login.submit');
    });

    // --- Authenticated admin routes ---
    Route::middleware(\App\Http\Middleware\AuthenticateAdmin::class)->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Admin\AuthController::class, 'logout'])->name('logout');

        // Redirect /admin → agents list
        Route::get('/', fn () => redirect()->route('admin.agents.index'));

        // Agents CRUD
        Route::get('/agents',                          [\App\Http\Controllers\Admin\AgentsController::class, 'index'])->name('agents.index');
        Route::get('/agents/create',                   [\App\Http\Controllers\Admin\AgentsController::class, 'create'])->name('agents.create');
        Route::post('/agents',                         [\App\Http\Controllers\Admin\AgentsController::class, 'store'])->name('agents.store');
        Route::get('/agents/{agent}/edit',             [\App\Http\Controllers\Admin\AgentsController::class, 'edit'])->name('agents.edit');
        Route::patch('/agents/{agent}',                [\App\Http\Controllers\Admin\AgentsController::class, 'update'])->name('agents.update');
        Route::patch('/agents/{agent}/suspend',        [\App\Http\Controllers\Admin\AgentsController::class, 'suspend'])->name('agents.suspend');
        Route::patch('/agents/{agent}/reactivate',     [\App\Http\Controllers\Admin\AgentsController::class, 'reactivate'])->name('agents.reactivate');
        Route::post('/agents/{agent}/photo',          [\App\Http\Controllers\Admin\AgentsController::class, 'uploadPhoto'])->name('agents.upload-photo');

        // Feature flags
        Route::get('/agents/{agent}/features',         [\App\Http\Controllers\Admin\FeatureFlagsController::class, 'index'])->name('feature-flags.index.agent');
        Route::post('/agents/{agent}/features/toggle', [\App\Http\Controllers\Admin\FeatureFlagsController::class, 'toggle'])->name('agents.features.toggle');

        // Leads overview
        Route::get('/leads',                           [\App\Http\Controllers\Admin\LeadsController::class, 'index'])->name('leads.index');

        // Analytics table
        Route::get('/analytics',                       [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');

        // Feature flags panel (all agents)
        Route::get('/feature-flags',                   [\App\Http\Controllers\Admin\FeatureFlagsController::class, 'index'])->name('feature-flags.index');

        // Sitemaps & cache warm-up
        Route::get('/sitemaps',                                    [\App\Http\Controllers\Admin\SitemapController::class, 'index'])->name('sitemaps.index');
        Route::post('/sitemaps/{domain}/start',                    [\App\Http\Controllers\Admin\SitemapController::class, 'start'])->name('sitemaps.start');
        Route::get('/sitemaps/status/{run}',                       [\App\Http\Controllers\Admin\SitemapController::class, 'status'])->name('sitemaps.status');

        // Places management
        Route::controller(\App\Http\Controllers\AdminController::class)->prefix('places')->name('places.')->group(function () {
            Route::get('/',          'places_index')->name('index');
            Route::get('/{id}/edit', 'places_edit')->name('edit');
            Route::put('/{id}',      'places_update')->name('update');
        });

        // Agent theme overrides
        Route::controller(\App\Http\Controllers\AdminController::class)->prefix('agent-themes')->name('agent-themes.')->group(function () {
            Route::get('/',      'agentThemesIndex')->name('index');
            Route::patch('/{id}', 'agentThemeUpdate')->name('update');
        });

        // Agent blog / news articles
        Route::controller(\App\Http\Controllers\Admin\AgentArticlesController::class)->prefix('articles')->name('articles.')->group(function () {
            Route::get('/',                     'index')->name('index');
            Route::get('/{article}/edit',       'edit')->name('edit');
            Route::put('/{article}',            'update')->name('update');
            Route::delete('/{article}',         'destroy')->name('destroy');
            Route::post('/{article}/publish',   'publish')->name('publish');
            Route::post('/{article}/unpublish', 'unpublish')->name('unpublish');
            Route::post('/generate-pack',       'generatePack')->name('generatePack');
            Route::post('/generate-monthly',    'generateMonthly')->name('generateMonthly');
            Route::post('/generate-topic',      'generateFromTopic')->name('generateFromTopic');
        });

        // Neighbourhood lifestyle + weekly pulse content
        Route::controller(\App\Http\Controllers\Admin\AgentNeighbourhoodContentController::class)->prefix('neighbourhood-content')->name('neighbourhood-content.')->group(function () {
            Route::get('/',                       'index')->name('index');
            Route::post('/generate-lifestyle',    'generateLifestyle')->name('generateLifestyle');
            Route::post('/generate-pulse',        'generatePulse')->name('generatePulse');
        });

        // Billing management (Stripe subscriptions)
        Route::prefix('billing')->name('billing.')->controller(\App\Http\Controllers\Admin\BillingController::class)->group(function () {
            Route::get('/',                               'index')->name('index');
            Route::post('/{agent}/subscribe',             'createSubscription')->name('subscribe')->where('agent', '[0-9]+');
            Route::delete('/{agent}/subscribe',           'cancelSubscription')->name('cancel')->where('agent', '[0-9]+');
            Route::post('/{agent}/portal',                'billingPortal')->name('portal')->where('agent', '[0-9]+');
            Route::post('/{agent}/manual-suspend',        'manualSuspend')->name('manual-suspend')->where('agent', '[0-9]+');
            Route::post('/{agent}/manual-reactivate',     'manualReactivate')->name('manual-reactivate')->where('agent', '[0-9]+');
        });
    });
});
// =====================================================================

Route::get('/map', function(){return redirect('https://www.bccondosandhomes.com/');});
Route::get('/bccondos/map', function(){return redirect('https://www.bccondosandhomes.com/');});
Route::get('/bccondos/{slug}', function($slug){return redirect('https://www.bccondosandhomes.com/' . $slug);});
Route::get('/bccondos/{page}/{slug}', function($page, $slug){return redirect('https://www.bccondosandhomes.com/' . $page . "/" . $slug);});
Route::get('/blog', function(){return redirect()->route('news-blog-list');})->name('blog');

// Route::get('/listing/mobiletest/{slug}', function($slug){return redirect()->route('listing-detail-page2',['slug'=>$slug,'expid'=>'239487982t3kjsydgfiuw32476dfsg','testlayout'=>'true']);})->name('listing-detail-page-mobiletest4seo'); /*only-for testing performance [not to be linked in google-submitted urls][22-10-2021] */ // [Disabled:2025-05-23]
Route::get('/whatsmyhomeworth', function(){return redirect('https://docs.google.com/forms/d/e/1FAIpQLScfNlRSa8f_aib1e2PqZ4QUBrU-izqVXfP0CBaL6TEQcVgFMw/viewform');})->name('external-whatsmyhomeworth'); // prefered direct-link [21-09-2022]




// ------------- OLD-Refer-pattern ------------------------

//BldCtrl@showAllBuildings

/*From approval-pattern published on:20-01-2021 -- [STARTS]*/
//Tmp2021Ctrl@showBuildingsListPage
//Tmp2021Ctrl@showBuildingsListPage

/* Handling old-urls to Permanent-redirect-toNew-ones [STARTS]*/
//Tmp2021Ctrl@redirectOldUrlsToNewCityBuildingsPage
//Tmp2021Ctrl@redirectOldUrlsToNewCityBuildingsPage
//BldCtrl@city_buildings
//BldCtrl@city_buildings
/* Handling old-urls to Permanent-redirect-toNew-ones [ENDS]*/
/*From approval-pattern published on:20-01-2021 -- [ENDS]*/

//UsrCtrl@openLink
//BldCtrl@get_building_doc

/*Testing - 17-Aug-2021 [STARTS] */
//DsdhBrd@get_place_for_sale_with_beds
//SrchLstngCtrl@get_place_for_sale_with_beds

/*Testing - 17-Aug-2021 [ENDS] */

//SrchLstngCtrl@get_place_for_sale
//SrchLstngCtrl@get_place_for_sale
//LstgCtrl@get_featured_listings
//LstgCtrl@get_oursolds_listings

//need authentication
//LoginCtrl@handle_auth
//LoginCtrl@verifyEmail
//LoginCtrl@logout
//UsrCtrl@get_session
//UsrCtrl@check_email_verification


//need authentication and email active
//LoginCtrl@agreeTerms

//need authentication, email active and agent
//LoginCtrl@step2
//LoginCtrl@completeProfile

//need authentication, email active, agent and complete profile
//LoginCtrl@confirmPhoneNumber
//LoginCtrl@postConfirmPhoneNumber


//BldCtrl@showBuildingDetailPage
//BldCtrl@getSoldListings
//BldCtrl@getActiveListings
//DsdhBrd@getPlaces
//DsdhBrd@storeClickEvent
//StatsCtrl@getStats
//StatsCtrl@getStatsJson
//StatsCtrl@getBuildingStatsJson
//EmlSbscrptnCtrl@unsubscribe
//UsrCtrl@show_favorite_listings
/** [created:20-05-2022] */
//UsrCtrl@show_favorite_tracked_listings
// Route::get('/mylistings', 'Agent\AgentController@show_agent_listings')->name('show_agent_listings')->middleware('redirect.authenticated', /*'check.email.verified',*/ /*'check.profile.completion'*/);


//anonyFxnRdrct(path:/map

//anonyFxnRdrct(path:/bccondos/map

//anonyFxnRdrct(path:/bccondos/{slug}

//anonyFxnRdrct(path:/bccondos/{page}/{slug}

//anonyFxnRdrct(path:/blog


//SitmpCtrl@sitemap_index
//SitmpCtrl@sitemap_active
//SitmpCtrl@sitemap_sold
//SitmpCtrl@sitemap_lastweek_active
//SitmpCtrl@sitemap_lastweek_sold

//SitmpCtrl@sitemap_buildings_city
//SitmpCtrl@sitemap_buildings_city

//SitmpCtrl@sitemap_buildings
//SitmpCtrl@sitemap_searchpages
//SitmpCtrl@sitemap_search_listings

//BldCtrl@building_redirect
//BldCtrl@get_building_url

/* Login from agent page */

//LstgCtrl@showListingDetailPage3
//LoginCtrl@loginPage
//LstgCtrl@updateWwr



// Route::get('/testing',function(){
//     $emailrepo = new App\Repository\EmailRepository();
//     $emailrepo->sendPropertySuggestions();
// });
// 



/*  Test/Debug-urls (before-finalizing -and- conflicts-verification) : */


//DsdhBrd@get_place_for_sale_with_beds
//DsdhBrd@get_place_for_sale

//SrchLstngCtrl@render_adv_search_listings_2

//SrchLstngCtrl@render_for_sale_slugFilteredListings
//SrchLstngCtrl@render_for_sale_slugFilteredListings

//SrchLstngCtrl@render_adv_search_listings
//SrchLstngCtrl@render_adv_search_listings


//  ------------ [Testing URL(s) for slug-changes] [Disabled:26-08-2022]
//BldCtrl@showBuildingDetailPageUsingSlug2
//BldCtrl@redirectBuildingDetailPageToUseSlug2

// --- Redirection+GET -handle and test-any-new-slug-scheme [eg: slug3, slug4 ...]
//BldCtrl@showBuildingDetailPageUsingTestNewSlugNum
//BldCtrl@redirectBuildingDetailPageToUseTestNewSlugNum



//DsdhBrd@render_for_sale_slugAndBedsFilteredListings
//DsdhBrd@render_adv_search_listings
//DsdhBrd@render_adv_search_listings

//DsdhBrd@render_adv_search_listings

//DsdhBrd@render_for_sale_slugFilteredListings


//Tmp2021Ctrl@getReqModesJson


//anonyFxnRdrct(path:/lstg/mobiletest/<slg>)
//Tmp2021Ctrl@redirectToListingDetailPage
//Tmp2021Ctrl@listingDetailPageUrlForListingId


// Route::get('/sitemap-buildings-ason-8sep2021.xml', 'SitemapController@sitemap_buildings')->name('sitemap-buildings-ason-8sep2021');

//DsdhBrd@get_place_for_sale_localdb

//Tmp2021Ctrl@showBuildingsListPage
//Tmp2021Ctrl@showBuildingsListPage

//Tmp2021Ctrl@whatsmyhomeworth
//anonyFxnRdrct(path:/whatsmyhomeworth)


//Tmp2021Ctrl@get_reverse_bcch2bcn_slug

//deleted


//Tmp2021Ctrl@testPostConfirmPhoneNumber

//WbhokCtrl@handleWebhook
//Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook

//SbscrptnCtrl@showPricingTable
//SbscrptnCtrl@manageSubscriptionPortal
//SbscrptnCtrl@subscriptionConfirmation

//UsrCtrl@recall_history
 
//Tmp2021Ctrl@renderShowStgChngSwitch2


/* -----[Testing:11-10-2022 6:03PM ] ------ */
//Tmp2021Ctrl@confirmPhoneNumber
