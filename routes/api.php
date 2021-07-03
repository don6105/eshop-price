<?php

use Illuminate\Support\Facades\Route;
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

Route::prefix('v1')->group(function () {
    Route::post('/register', 'App\Http\Controllers\v1\PassportController@register');
    Route::post('/login',    'App\Http\Controllers\v1\PassportController@login');

    Route::apiResource('game', 'App\Http\Controllers\v1\GameController');
});

Route::prefix('v1')->middleware('auth:api')->group(function () {
    Route::post('logout', 'App\Http\Controllers\v1\PassportController@logout');

    // for debug, it can be deleted.
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});