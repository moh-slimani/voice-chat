<?php

use App\Http\Controllers\PersonController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::post('start-chat', [PersonController::class, 'store'])->name('start-chat');
Route::get('/chat/{username}', [PersonController::class, 'show'])->name('chat');
Route::get('checkout/{username}', [PersonController::class, 'checkout'])->name('checkout');


require __DIR__ . '/auth.php';
