<?php

use App\Http\Controllers\PixelController;
use App\Http\Controllers\HandleAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\ServerMetricsController;
use App\Http\Controllers\BanUserController;
use App\Http\Controllers\WalletController;


Route::get('/', function () { return view('canvas'); });
Route::get('/support', function () { return view('support'); });


Route::get('/api/map-data', [PixelController::class, 'index']);
Route::post('/api/pixel', [PixelController::class, 'store']);
Route::get('/api/cooldown', [PixelController::class, 'cooldown']);
Route::post('/api/wallet/check-balance', [WalletController::class, 'checkBalance']);

/* Auth Routes */

Route::get('/login', function () { return view('login'); })->middleware('guest')->name('login');
Route::post('/login', [HandleAuthController::class, 'handleLogin']);
Route::post('/register', [HandleAuthController::class, 'handleRegister'])->middleware('guest');
Route::post('/api/verify-email', [HandleAuthController::class, 'verifyEmail'])->middleware('auth');
Route::post('/api/resend-verification', [HandleAuthController::class, 'resendVerificationCode'])->middleware('auth');



Route::get('/admin/', function () { return view('admin'); })->middleware('auth');

// API routes that can work with or without auth - must be before auth middleware routes
Route::get('/api/me', function () {
    return auth()->check() ? Auth::user() : null;
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [HandleAuthController::class, 'handleLogout']);
    Route::get('/server/metrics', [ServerMetricsController::class, 'index']);
    Route::get('/visitors', [PixelController::class, 'getVisitors']);
    Route::get('/banned-visitors', [BanUserController::class, 'getBannedVisitors']);
    Route::post('/ban-visitors', [BanUserController::class, 'ban']);
});

