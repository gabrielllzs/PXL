<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LightsailMetricsService;

class ServerMetricsController extends Controller
{
    protected $metricsService;

    public function __construct(LightsailMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    public function index()
    {
        return response()->json($this->metricsService->getMetrics());
    }
}
