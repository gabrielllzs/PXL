<?php

use App\Http\Controllers\PixelController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () { return view('canvas'); });


Route::get('api/pixels', [PixelController::class, 'index']);
Route::post('api/pixels', [PixelController::class, 'store']);
Route::get('/api/cooldown', [PixelController::class, 'cooldown']);
