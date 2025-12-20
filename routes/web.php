<?php

use App\Http\Controllers\PixelController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () { return view('canvas'); });


Route::get('api/pixel', [PixelController::class, 'index']);
Route::post('api/pixel', [PixelController::class, 'store']);
Route::get('/api/cooldown', [PixelController::class, 'cooldown']);

/* Login Routes */

Route::get('/login', function () { return view('login'); });



