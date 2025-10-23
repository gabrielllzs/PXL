<?php

use App\Http\Controllers\PixelController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () { return view('canvas'); });

Route::prefix('api')->middleware('api')->group(function () {
    Route::post('/pixel/claim', [PixelController::class, 'claim']);
    Route::get('/pixels', [PixelController::class, 'index']);
});
