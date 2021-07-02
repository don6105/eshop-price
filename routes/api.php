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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    Route::post('/register', 'App\Http\Controllers\v1\AuthController@register');
    Route::post('/login',    'App\Http\Controllers\v1\AuthController@login');
});


Route::prefix('v1')->group(function () {
    Route::apiResource('game', 'App\Http\Controllers\v1\GameController');
});