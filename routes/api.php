<?php

use Illuminate\Http\Request;

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

Route::get('v1/cashboxes', 'CashboxController@index');
Route::get('v1/year_stats/checks', 'CheckController@year_stats');
Route::get('v1/month_stats/checks', 'CheckController@month_stats');
