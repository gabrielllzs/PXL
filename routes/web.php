<?php

use App\Http\Controllers\PixelController;
use App\Http\Controllers\HandleAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\ServerMetricsController;
use App\Http\Controllers\BanUserController;


Route::get('/', function () { return view('canvas'); });


Route::get('/api/map-data', [PixelController::class, 'index']);
Route::post('/api/pixel', [PixelController::class, 'store']);
Route::get('/api/cooldown', [PixelController::class, 'cooldown']);

/* Login Routes */

Route::get('/login', function () { return view('login'); })->middleware('guest')->name('login');
Route::post('/login', [HandleAuthController::class, 'handleLogin']);


Route::get('/admin/', function () { return view('admin'); })->middleware('auth');

Route::middleware('auth')->get('/api/me', function () {
    return Auth::user();
});


Route::middleware('auth')->group(function () {
    Route::post('/logout', [HandleAuthController::class, 'handleLogout']);
    Route::get('/server/metrics', [ServerMetricsController::class, 'index']);
    Route::get('/visitors', [PixelController::class, 'getVisitors']);
    Route::get('/banned-visitors', [BanUserController::class, 'getBannedVisitors']);
    Route::post('/ban-visitors', [BanUserController::class, 'ban']);
});

