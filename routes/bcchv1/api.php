<?php

use Illuminate\Http\Request;
use App\Http\Controllers;
use App\Http\Controllers\Frontend as Frontend;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// ---- Alert Subscriptions API [Task#535] ----
Route::prefix('v1')->middleware('alert.api.auth')->group(function () {
    Route::get('/alert-subscriptions', 'App\Http\Controllers\Api\AlertSubscriptionApiController@index');
    Route::post('/alert-sent', 'App\Http\Controllers\Api\AlertSubscriptionApiController@alertSent');
    Route::post('/deactivate-alert', 'App\Http\Controllers\Api\AlertSubscriptionApiController@deactivateAlert');
});
// ---- End Alert Subscriptions API ----

Route::get('/get_user', 'App\Http\Controllers\Frontend\UserController@get_user')->name('api:get_user')->middleware('api.auth');

Route::any('/save_search', 'App\Http\Controllers\Frontend\UserController@save_search')->name('api:save_search')->middleware('api.auth');
Route::any('/get_searches', 'App\Http\Controllers\Frontend\UserController@get_searches')->name('api:get_searches')->middleware('api.auth');
Route::any('/update_search/{id}', 'App\Http\Controllers\Frontend\UserController@update_search')->name('api:update_search')->middleware('api.auth');
Route::any('/delete_search/{id}', 'App\Http\Controllers\Frontend\UserController@delete_search')->name('api:delete_search')->middleware('api.auth');

Route::any('/favorite_listing', 'App\Http\Controllers\Frontend\UserController@favorite_listing')->name('api:favorite_listing')->middleware('api.auth');
Route::any('/delete_favorite/{id}', 'App\Http\Controllers\Frontend\UserController@delete_favorite')->name('api:delete_favorite')->middleware('api.auth');

Route::any('/request-showing','App\Http\Controllers\Frontend\UserController@request_showing')->name('api:request_showing')->middleware('throttle:30,1'); //->throttle:x-requests,in-y-minutes //->middleware('api.auth');
Route::any('/contact-us','App\Http\Controllers\Frontend\UserController@contactus')->name('api:contactus')->middleware('api.auth');
Route::post('/request-showing-api','App\Http\Controllers\Frontend\UserController@request_showing_api')->name('api:request_showing_api');
Route::any('/ask-question','App\Http\Controllers\Frontend\UserController@ask_question')->name('api:ask_question')->middleware('api.auth');

//https://www.bccondosandhomes.com/api2/request-showing-api

// Route::any('/adv-search-properties', 'App\Http\Controllers\Frontend\SearchListingsController@get_api_adv_search_properties_per_city_subarea')->name('api:get_adv_search_filtered_listings_for_sale')/*->middleware('csrf')*//*->middleware('api.auth')*/; /*[Delete after:19-May-2022]*/
Route::any('/search-listings', 'App\Http\Controllers\Frontend\SearchListingsController@get_api_adv_search_listings_per_city_subarea')->name('api:get_adv_search_listings_filtered')/*->middleware('csrf')*//*->middleware('api.auth')*/;

// Alert preview & subarea list — My Account "Create Alert" form [added:2026-05]
Route::get('/alert-preview', 'App\Http\Controllers\Frontend\UserController@alertPreview')->name('api:alert_preview');
Route::get('/subareas', 'App\Http\Controllers\Frontend\UserController@subareaList')->name('api:subarea_list');



// for-tests---
Route::get('/offerlandprice/{ml_no?}', 'App\Http\Controllers\Frontend\OfferlandPriceController@testFunction')->name('api:offerland_test');

// Route::any('/test/{slug}-for-sale-{subarea?}', 'App\Http\Controllers\Frontend\DashboardController@get_api_for_sale')->where('slug', '[A-Za-z0-9_\-]+')->where('subarea', '(.*)')->where('subarea', '[A-Za-z0-9_\-]+')->name('api:get_slug_filtered_listings_for_sale')/*->middleware('api.auth')*/; // [disabled:05-06-2022]
// Route::any('/test/adv-search-properties', 'App\Http\Controllers\Frontend\DashboardController@get_api_adv_search_properties_per_city_subarea')->name('api:get_adv_search_filtered_listings_for_sale__test_deleteOn30Jan2022')/*->middleware('csrf')*//*->middleware('api.auth')*/;
Route::any('/reportrd/{slug}', 'App\Http\Controllers\Frontend\TempDevCtrl2021@handleRequest')->where('slug','(.*)')->where('slug', '[A-Za-z0-9_\-\.]+')->name('api:get_redirection_report_aug2021')/*->middleware('api.auth')*/;
