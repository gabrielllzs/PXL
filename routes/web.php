<?php

use App\Http\Controllers\PixelController;
use App\Http\Controllers\HandleAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\ServerMetricsController;
use App\Http\Controllers\BanUserController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\FeedbackController;
use Illuminate\Http\Request;


Route::get('/', function () { return view('canvas'); });
Route::get('/feedback', function () { return view('feedback'); });


Route::get('/api/map-data', [PixelController::class, 'index']);
Route::post('/api/pixel', [PixelController::class, 'store']);
Route::get('/api/cooldown', [PixelController::class, 'cooldown']);
Route::post('/api/wallet/check-balance', [WalletController::class, 'checkBalance']);

/* Auth Routes */

Route::post('/login', [HandleAuthController::class, 'handleLogin']);
Route::post('/register', [HandleAuthController::class, 'handleRegister'])->middleware('guest');
Route::post('/api/verify-email', [HandleAuthController::class, 'verifyEmail'])->middleware('auth');
Route::post('/api/resend-verification', [HandleAuthController::class, 'resendVerificationCode'])->middleware('auth');



Route::get('/admin/', function () { return view('admin'); })->middleware(['auth', 'admin']);

// API routes that can work with or without auth - must be before auth middleware routes
Route::get('/api/me', function () {
    return auth()->check() ? Auth::user() : null;
});

Route::post('/api/feedback', [FeedbackController::class, 'store']);

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/server/metrics', [ServerMetricsController::class, 'index']);
    Route::get('/visitors', [PixelController::class, 'getVisitors']);
    Route::get('/banned-visitors', [BanUserController::class, 'getBannedVisitors']);
    Route::post('/ban-visitors', [BanUserController::class, 'ban']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [HandleAuthController::class, 'handleLogout']);
});

