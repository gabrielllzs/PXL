<?php

use App\Http\Controllers\PixelController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () { return view('canvas'); });
