<?php

use App\Http\Controllers\PixelController;
use App\Http\Controllers\HandleAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\ServerMetricsController;
use App\Http\Controllers\BanUserController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CountryController;
use Illuminate\Http\Request;


Route::get('/', function () { return view('canvas'); })->name('login');;
Route::get('/feedback', function () { return view('feedback'); });


Route::get('/api/map-data', [PixelController::class, 'index']);
Route::post('/api/pixel', [PixelController::class, 'store']);
Route::get('/api/cooldown', [PixelController::class, 'cooldown']);

Route::prefix('leaderboard')->group(function () {
    Route::get('/groups', [GroupController::class, 'index']);
    Route::get('/users', [UserController::class, 'getUsersPixels']);
    Route::get('/countries', [CountryController::class, 'index']);
});

Route::post('/login', [HandleAuthController::class, 'handleLogin']);
Route::post('/register', [HandleAuthController::class, 'handleRegister'])->middleware('guest');
Route::post('/api/verify-email', [HandleAuthController::class, 'verifyEmail'])->middleware('auth', 'throttle:5,1');
Route::post('/api/resend-verification', [HandleAuthController::class, 'resendVerificationCode'])->middleware('auth' , 'throttle:3,1');



Route::get('/admin/', function () { return view('admin'); })->middleware(['auth', 'admin']);

// API routes that can work with or without auth - must be before auth middleware routes


Route::post('/feedback', [FeedbackController::class, 'store']);

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/server/metrics', [ServerMetricsController::class, 'index']);
    Route::get('/visitors', [PixelController::class, 'getVisitors']);
    Route::get('/banned-visitors', [BanUserController::class, 'getBannedVisitors']);
    Route::post('/ban-visitors', [BanUserController::class, 'ban']);
    Route::post('/logout', [HandleAuthController::class, 'handleLogout']);
    Route::get('/api/me', function () {return auth()->check() ? Auth::user() : null;});
    Route::get('/group', [GroupController::class, 'showMyGroup']);
});
