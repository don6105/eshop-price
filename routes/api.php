<?php

use Illuminate\Support\Facades\Route;

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

Route::prefix('v1')->group(function () {
    Route::post('/register', 'App\Http\Controllers\v1\PassportController@register');
    Route::post('/login',    'App\Http\Controllers\v1\PassportController@login');

    Route::apiResource('summary', 'App\Http\Controllers\v1\SummaryController');
});

Route::prefix('v1')->middleware('auth:api')->group(function () {
    Route::post('logout', 'App\Http\Controllers\v1\PassportController@logout');
});