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

use App\Http\Controllers\v1\PassportController as Passport;
use App\Http\Controllers\v1\WikiGameController as WikiGame;
use App\Http\Controllers\v1\SummaryController  as Summary;

Route::prefix('v1')->group(function () {
    Route::post('register', [Passport::class, 'register']);
    Route::post('login',    [Passport::class, 'login']);

    Route::get('wikigame',  [WikiGame::class, 'index']);

    Route::get('summary',           [Summary::class, 'index']);
    Route::get('summary/{groupID}', [Summary::class, 'show']);
});

Route::prefix('v1')->middleware('auth:api')->group(function () {
    Route::post('logout', [Passport::class, 'logout']);
    Route::match(['put', 'patch'], 'summary/{groupID}', [Summary::class, 'update']);
});