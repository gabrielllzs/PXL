<?php

use App\Http\Controllers\PixelController;
use App\Http\Controllers\HandleLoginController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\ServerMetricsController;


Route::get('/', function () { return view('canvas'); });


Route::get('api/pixel', [PixelController::class, 'index']);
Route::post('api/pixel', [PixelController::class, 'store']);
Route::get('/api/cooldown', [PixelController::class, 'cooldown']);

/* Login Routes */

Route::get('/login', function () { return view('login'); })->middleware('guest')->name('login');
Route::post('/login', [HandleLoginController::class, 'handleLogin']);
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->middleware('auth')->name('logout');


Route::get('/admin/', function () { return view('admin'); })->middleware('auth');

Route::middleware('auth')->get('/api/me', function () {
    return Auth::user();
});


Route::middleware('auth')->group(function () {
    Route::get('/server/metrics', [ServerMetricsController::class, 'index']);
    Route::get('/server/info', [ServerMetricsController::class, 'info']);
});

