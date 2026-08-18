<?php

/**
 * Pixilink internal API — consumed by the Next.js pixilink-web service.
 * Registered in bootstrap/app.php with prefix "api-internal" and no auth middleware.
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Internal\AgentDataController;
use App\Http\Controllers\Internal\AdminInternalController;
use App\Http\Controllers\Internal\UserAuthController;

Route::get('ping', function() { return response()->json(['ok' => true, 'ts' => time()]); });

Route::get('cities', [AgentDataController::class, 'cities']);

Route::prefix('agent')->group(function () {
    Route::get('by-domain/{domain}', [AgentDataController::class, 'byDomain'])
        ->where('domain', '.+');

    Route::post('{slug}/contact', [AgentDataController::class, 'contact'])
        ->where('slug', '[a-z0-9\-]+');

    Route::get('{slug}/listings', [AgentDataController::class, 'featuredListings'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/own-listings', [AgentDataController::class, 'ownListings'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/listing/{listingSlug}', [AgentDataController::class, 'listingDetail'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('listingSlug', '[a-z0-9A-Z\-]+');
    Route::get('{slug}/listing/{listingSlug}/supplemental', [AgentDataController::class, 'listingSupplemental'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('listingSlug', '[a-z0-9A-Z\-]+');
    Route::get('{slug}/listing/{listingSlug}/building-last-sold', [AgentDataController::class, 'buildingLastSold'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('listingSlug', '[a-z0-9A-Z\-]+');
    Route::get('{slug}/listing/{listingSlug}/building-compelling-sold', [AgentDataController::class, 'buildingCompellingSold'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('listingSlug', '[a-z0-9A-Z\-]+');
    Route::get('{slug}/buildings', [AgentDataController::class, 'featuredBuildings'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/building/{buildingSlug}', [AgentDataController::class, 'buildingDetail'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('buildingSlug', '[a-z0-9\-]+');
    Route::get('{slug}/stats', [AgentDataController::class, 'marketStats'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/market-report', [AgentDataController::class, 'marketReport'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/market-breakdown', [AgentDataController::class, 'marketBreakdown'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/neighbourhoods', [AgentDataController::class, 'neighbourhoods'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/neighbourhood/{subareaSlug}/sold', [AgentDataController::class, 'neighbourhoodSold'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('subareaSlug', '[a-z0-9\-]+');
    Route::get('{slug}/price-story', [AgentDataController::class, 'priceStory'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/neighbourhood/{subareaSlug}/reports', [AgentDataController::class, 'neighbourhoodReports'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('subareaSlug', '[a-z0-9\-]+');
    Route::get('{slug}/neighbourhood/{subareaSlug}', [AgentDataController::class, 'neighbourhoodDetail'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('subareaSlug', '[a-z0-9\-]+');
    Route::get('{slug}/top-realtor', [AgentDataController::class, 'topRealtor'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/sold-stats', [AgentDataController::class, 'soldStats'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/home', [AgentDataController::class, 'home'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/testimonials', [AgentDataController::class, 'testimonials'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/pages', [AgentDataController::class, 'pages'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/page/{pageSlug}', [AgentDataController::class, 'page'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('pageSlug', '[a-z0-9\-]+');
    Route::get('{slug}/awards', [AgentDataController::class, 'awards'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/faqs', [AgentDataController::class, 'faqs'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/territories', [AgentDataController::class, 'territories'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/news', [AgentDataController::class, 'news'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/news/{postSlug}', [AgentDataController::class, 'newsPost'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('postSlug', '[a-z0-9\-]+');
    Route::get('{slug}/schools/{schoolSlug}', [AgentDataController::class, 'schoolCatchmentDetail'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('schoolSlug', '[a-z0-9\-]+');
    Route::get('{slug}/schools', [AgentDataController::class, 'schoolCatchments'])
        ->where('slug', '[a-z0-9\-]+');

    Route::get('{slug}/media', [AgentDataController::class, 'media'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/team', [AgentDataController::class, 'getTeam'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}', [AgentDataController::class, 'bySlug'])
        ->where('slug', '[a-z0-9\-]+');
        Route::get('{slug}/area-intro-content',   [AgentDataController::class, 'areaIntro']);
    Route::get('{slug}/neighbourhood-ai-content', [AgentDataController::class, 'neighbourhoodAiContent']);
    Route::get('{slug}/ai-pages', [AgentDataController::class, 'aiPages'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/ai-pages/{pageSlug}', [AgentDataController::class, 'aiPage'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('pageSlug', '[a-z0-9\-]+');
    Route::get('{slug}/landing-pages', [AgentDataController::class, 'landingPagesList'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/landing-pages/{citySlug}', [AgentDataController::class, 'landingPageByCity'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('citySlug', '[a-z0-9\-]+');
    Route::get('{slug}/landing-pages/{citySlug}/{areaSlug}', [AgentDataController::class, 'landingPageByArea'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('citySlug', '[a-z0-9\-]+')
        ->where('areaSlug', '[a-z0-9\-]+');
    Route::get('{slug}/persona/{persona}', [AgentDataController::class, 'personaListings'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('persona', '[a-z0-9\-]+');
    Route::get('{slug}/area-comparisons', [AgentDataController::class, 'areaComparisonsList'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/area-comparisons/{comparisonSlug}', [AgentDataController::class, 'areaComparisonDetail'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('comparisonSlug', '[a-z0-9\-]+');
    Route::get('{slug}/best-of', [AgentDataController::class, 'bestOfListsList'])
        ->where('slug', '[a-z0-9\-]+');
    Route::get('{slug}/best-of/{listSlug}', [AgentDataController::class, 'bestOfListDetail'])
        ->where('slug', '[a-z0-9\-]+')
        ->where('listSlug', '[a-z0-9\-]+');
});

/**
 * User auth — token-based, consumed by Next.js pixilink-web.
 * Protected endpoints require: Authorization: Bearer {token}
 */
Route::prefix('auth')->group(function () {
    // Public (no token needed)
    Route::post('register-passwordless', [UserAuthController::class, 'registerPasswordless'])->middleware('throttle:20,1');
    Route::post('magic-link/send',       [UserAuthController::class, 'sendMagicLink'])->middleware('throttle:10,1');
    Route::post('magic-link/verify',     [UserAuthController::class, 'verifyMagicLink'])->middleware('throttle:10,1');
    Route::post('register',        [UserAuthController::class, 'register'])->middleware('throttle:20,1');
    Route::post('login',           [UserAuthController::class, 'login'])->middleware('throttle:20,1');
    Route::post('forgot-password', [UserAuthController::class, 'forgotPassword'])->middleware('throttle:10,1');
    Route::post('reset-password',  [UserAuthController::class, 'resetPassword'])->middleware('throttle:10,1');
    Route::get('verify-email',     [UserAuthController::class, 'verifyEmail']); // browser link from email

    // Google OAuth (stateless — browser redirects)
    Route::get('google/redirect',  [UserAuthController::class, 'googleRedirect'])->middleware('throttle:20,1');
    Route::get('google/callback',  [UserAuthController::class, 'googleCallback']);
    Route::post('google/exchange', [UserAuthController::class, 'googleExchange'])->middleware('throttle:20,1');

    Route::get('apple/redirect',  [UserAuthController::class, 'appleRedirect'])->middleware('throttle:20,1');
    Route::get('apple/callback',  [UserAuthController::class, 'appleCallback']);
    Route::post('apple/exchange', [UserAuthController::class, 'appleExchange'])->middleware('throttle:20,1');

    // Protected (Bearer token required)
    Route::post('logout',           [UserAuthController::class, 'logout']);
    Route::get('me',                [UserAuthController::class, 'me']);
    Route::post('email/resend',     [UserAuthController::class, 'emailResend'])->middleware('throttle:5,1');
    Route::get('check-verified',    [UserAuthController::class, 'checkVerified']);
    Route::post('complete-profile', [UserAuthController::class, 'completeProfile']);
    Route::post('phone/save',       [UserAuthController::class, 'phoneSave'])->middleware('throttle:20,1');
    Route::post('phone/send',       [UserAuthController::class, 'phoneSend'])->middleware('throttle:10,1');
    Route::post('phone/verify',     [UserAuthController::class, 'phoneVerify'])->middleware('throttle:10,1');
    Route::post('accept-terms',     [UserAuthController::class, 'acceptTerms'])->middleware('throttle:10,1');
});


/**
 * Favourites — Bearer-token auth, consumed by Next.js pixilink-web.
 */
Route::prefix('favourites')->group(function () {
    Route::get('/',         [AgentDataController::class, 'getFavourites']);
    Route::post('/',        [AgentDataController::class, 'addFavourite']);
    Route::delete('{mls}',  [AgentDataController::class, 'removeFavourite'])
        ->where('mls', '[a-zA-Z0-9]+');


});

Route::get('regions', [AgentDataController::class, 'regions']);

Route::post('sold-gate-event', [AgentDataController::class, 'recordSoldGateEvent']);

/**
 * Property view tracking — Bearer-token auth, consumed by Next.js pixilink-web.
 */
Route::post('user/property-view', [AgentDataController::class, 'recordPropertyView']);

/**
 * Admin API — protected by X-Admin-Secret header.
 * Called by the Next.js super-admin panel (pixilink-web /admin/*).
 */
// No 'throttle:600,1' here: the api middleware group already applies exactly
// that limit, and an unnamed throttle keys on sha1(domain|ip) with an empty
// prefix -- so a second identical instance resolved to the SAME cache key and
// called hit() twice per request, halving the real ceiling to ~300/min. The
// api-group limit still caps this surface; only the double-count is gone.
Route::prefix('admin')->middleware([\App\Http\Middleware\VerifyAdminSecret::class])->group(function () {
    Route::post('auth', [AdminInternalController::class, 'auth']);

    Route::get('agents', [AdminInternalController::class, 'agentsList']);
    Route::post('agents', [AdminInternalController::class, 'agentCreate']);
    Route::get('agents/{id}', [AdminInternalController::class, 'agentGet'])->where('id', '[a-zA-Z0-9_-]+');
    Route::put('agents/{id}', [AdminInternalController::class, 'agentUpdate'])->where('id', '[a-zA-Z0-9_-]+');
    Route::delete('agents/{id}', [AdminInternalController::class, 'agentDelete'])->where('id', '[a-zA-Z0-9_-]+');
        Route::get('leads', [AdminInternalController::class, 'allLeads']);
    Route::get('agents/{id}/leads', [AdminInternalController::class, 'agentLeads'])->where('id', '[a-zA-Z0-9_-]+');
    Route::get('agents/{id}/users', [AgentDataController::class, 'agentUsers'])->where('id', '[0-9]+');
    Route::get('users', [AgentDataController::class, 'adminUsers']);
    Route::get('leads/{userId}/property-views', [AgentDataController::class, 'getLeadPropertyViews'])->where('userId', '[0-9]+');
    Route::put('agents/{id}/features', [AdminInternalController::class, 'agentFeatures'])->where('id', '[a-zA-Z0-9_-]+');
    Route::get('agents/{agentId}/buildings', [AgentDataController::class, 'adminAgentBuildings'])->where('agentId', '[0-9]+');
    // Generation coverage (total / generated / remaining per mode) for the
    // batch-generate page. COUNT-only, safe to poll after each run.
    Route::get('agents/{agentId}/buildings/stats', [AgentDataController::class, 'adminAgentBuildingsStats'])->where('agentId', '[0-9]+');
    Route::get('agents/{agentId}/listings', [AgentDataController::class, 'adminAgentListings'])->where('agentId', '[0-9]+');
    Route::post('agents/{id}/upload-photo', [AgentDataController::class, 'uploadAgentPhoto'])->where('id', '[a-zA-Z0-9_-]+');

    Route::get('territory-cities', [AdminInternalController::class, 'territoryCities']);
    Route::get('buildings', [AgentDataController::class, 'adminBuildingsList']);
    Route::post('buildings/{id}/description', [AgentDataController::class, 'saveBuildingDescription'])->where('id', '[a-zA-Z0-9_-]+');
    Route::post('buildings/{id}/features', [AgentDataController::class, 'saveBuildingFeatures'])->where('id', '[a-zA-Z0-9_-]+');
    Route::get('buildings/{id}/commentary', [AgentDataController::class, 'getBuildingCommentary'])->where('id', '[a-zA-Z0-9_-]+');
    Route::post('buildings/{id}/commentary', [AgentDataController::class, 'saveBuildingCommentary'])->where('id', '[a-zA-Z0-9_-]+');
    Route::post('buildings/{id}/tags', [AgentDataController::class, 'adminSaveBuildingTags'])->where('id', '[a-zA-Z0-9_-]+');
    Route::get('listings', [AgentDataController::class, 'adminListingsList']);
    Route::post('listings/{id}/tags', [AgentDataController::class, 'adminSaveListingTags'])->where('id', '[0-9]+');
    Route::get('sold-gate-stats', [AgentDataController::class, 'soldGateStats']);
    Route::get('sold-gate-stats-by-day', [AgentDataController::class, 'soldGateStatsByDay']);
    Route::get('platform-summary', [AdminInternalController::class, 'platformSummary']);
    Route::post('agents/{id}/ai-pages', [AdminInternalController::class, 'saveAiPages'])->where('id', '[a-zA-Z0-9_-]+');
    Route::get('agents/{id}/ai-pages', [AdminInternalController::class, 'listAiPages'])->where('id', '[a-zA-Z0-9_-]+');

    Route::get('agents/{id}/landing-pages', [AgentDataController::class, 'adminLandingPagesList'])->where('id', '[a-zA-Z0-9_-]+');
    Route::post('agents/{id}/landing-pages', [AgentDataController::class, 'adminLandingPagesCreate'])->where('id', '[a-zA-Z0-9_-]+');
    Route::put('agents/{id}/landing-pages/{pageId}', [AgentDataController::class, 'adminLandingPagesUpdate'])->where('id', '[0-9]+')->where('pageId', '[0-9]+');
    Route::delete('agents/{id}/landing-pages/{pageId}', [AgentDataController::class, 'adminLandingPagesDelete'])->where('id', '[0-9]+')->where('pageId', '[0-9]+');

    Route::get('agents/{id}/area-comparisons', [AgentDataController::class, 'adminAreaComparisonsList'])->where('id', '[a-zA-Z0-9_-]+');
    Route::post('agents/{id}/area-comparisons', [AgentDataController::class, 'adminAreaComparisonsCreate'])->where('id', '[a-zA-Z0-9_-]+');
    Route::put('agents/{id}/area-comparisons/{comparisonId}', [AgentDataController::class, 'adminAreaComparisonsUpdate'])->where('id', '[0-9]+')->where('comparisonId', '[0-9]+');
    Route::delete('agents/{id}/area-comparisons/{comparisonId}', [AgentDataController::class, 'adminAreaComparisonsDelete'])->where('id', '[0-9]+')->where('comparisonId', '[0-9]+');

    Route::get('agents/{id}/best-of-lists', [AgentDataController::class, 'adminBestOfListsList'])->where('id', '[a-zA-Z0-9_-]+');
    Route::post('agents/{id}/best-of-lists', [AgentDataController::class, 'adminBestOfListsCreate'])->where('id', '[a-zA-Z0-9_-]+');
    Route::put('agents/{id}/best-of-lists/{listId}', [AgentDataController::class, 'adminBestOfListsUpdate'])->where('id', '[0-9]+')->where('listId', '[0-9]+');
    Route::delete('agents/{id}/best-of-lists/{listId}', [AgentDataController::class, 'adminBestOfListsDelete'])->where('id', '[0-9]+')->where('listId', '[0-9]+');

    Route::post('test-ghl-push', [AgentDataController::class, 'testGhlPush']);
    Route::get('platform-settings',  [AgentDataController::class, 'getPlatformSettings']);
    Route::post('platform-settings', [AgentDataController::class, 'updatePlatformSettings']);
});

Route::prefix('agent-portal')->middleware([\App\Http\Middleware\VerifyAdminSecret::class, 'throttle:120,1'])->group(function () {
    Route::post('auth',                           [AdminInternalController::class, 'agentPortalAuth']);
    Route::get('{id}/dashboard',                  [AdminInternalController::class, 'agentPortalDashboard'])->where('id', '[0-9]+');
    Route::get('{id}/leads',                      [AdminInternalController::class, 'agentPortalLeads'])->where('id', '[0-9]+');
    Route::get('{id}/profile',                    [AdminInternalController::class, 'agentPortalProfile'])->where('id', '[0-9]+');
    Route::put('{id}/profile',                    [AdminInternalController::class, 'agentPortalProfileUpdate'])->where('id', '[0-9]+');
    Route::get('{id}/team',                       [AdminInternalController::class, 'agentPortalTeam'])->where('id', '[0-9]+');
    Route::get('{id}/featured-listings',          [AdminInternalController::class, 'agentPortalFeaturedListings'])->where('id', '[0-9]+');
    Route::get('{id}/settings',                   [AdminInternalController::class, 'agentPortalSettings'])->where('id', '[0-9]+');
    Route::get('{id}/integrations',               [AdminInternalController::class, 'agentPortalIntegrationsGet'])->where('id', '[0-9]+');
    Route::put('{id}/integrations',               [AdminInternalController::class, 'agentPortalIntegrationsUpdate'])->where('id', '[0-9]+');
});


Route::post('stripe-webhook', [\App\Http\Controllers\Admin\BillingController::class, 'webhook']);

Route::get('market-board-report', [AgentDataController::class, 'boardMarketReport']);
Route::get('market-board-cities', [AgentDataController::class, 'boardCities']);

Route::prefix('residencity')->group(function () {
    Route::get('heatmap',    [AgentDataController::class, 'residencityHeatmap']);
    Route::get('recent-sold',[AgentDataController::class, 'residencityRecentSold']);
    Route::get('overview',   [AgentDataController::class, 'residencityOverview']);
    Route::get('trends',     [AgentDataController::class, 'residencityTrends']);
    Route::post('subscribe', [AgentDataController::class, 'residencitySubscribe']);
});

