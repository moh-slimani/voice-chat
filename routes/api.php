<?php

use App\Http\Controllers\PersonController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('call/{username}', [PersonController::class, 'call']);
Route::post('answer/{username}', [PersonController::class, 'answer']);
Route::post('candidate/{username}', [PersonController::class, 'candidate']);
Route::post('checkin/{username}', [PersonController::class, 'checkin']);
Route::get('refresh/{username}', [PersonController::class, 'refresh']);
Route::post('hangup/{username}', [PersonController::class, 'hangup']);
