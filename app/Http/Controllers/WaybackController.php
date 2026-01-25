<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PixelHistory;

class WaybackController extends Controller
{
    public function index()
    {
        return PixelHistory::orderBy('created_at', 'asc')->get();
    }

}
