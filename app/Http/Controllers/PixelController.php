<?php

namespace App\Http\Controllers;

use App\Events\PixelPlaced;
use App\Models\Pixel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PixelController extends Controller
{
    private const MAX_FINGERPRINT_RISK_SCORE = 5;


    public function index()
    {
        return Pixel::all();
    }

    public function store(Request $request)
    {

        $request->validate([
            'x' => 'required|integer',
            'y' => 'required|integer',
            'color' => 'required|string',
            'visitorId' => 'required|string',
            'components' => 'required|array',
        ]);

        $visitorId = $request->input('visitorId');
        $components = $request->input('components');
        $clientIp = $request->ip();

        $riskData = $this->checkFingerprint($components, $clientIp);

        if ($riskData['action'] === 'BLOCK') {
            return response()->json([
                'error' => 'security_block',
                'reason' => $riskData['reason'],
            ], 403);
        }

        $remaining = $this->cooldown($request)['remaining'];
        if ($remaining > 0) {
            return response()->json([
                'error' => 'cooldown',
                'remaining' => $remaining,
            ], 412);
        }

        $pixel = Pixel::updateOrCreate(
            [
                'x' => $request->x,
                'y' => $request->y,
            ],
            [
                'color' => $request->color ?? 'black',
                'visitor_id' => $visitorId,
                'fingerprint_components' => json_encode($components),
                'risk_score' => $riskData['risk_score'],
            ]
        );

        event(new PixelPlaced($pixel->x, $pixel->y, $pixel->color));
        return response()->json($pixel);
    }

    public function cooldown(Request $request)
    {
        $visitorId = $request->input('visitorId');
        $cooldownSeconds = 60;

        $last = Pixel::where('visitor_id', $visitorId)
            ->latest()
            ->first();

        if (!$last) {
            return ['cooldown' => 0, 'remaining' => 0];
        }

        $elapsed = $last->created_at->diffInSeconds(now());
        $remaining = max(0, $cooldownSeconds - $elapsed);

        return [
            'cooldown' => $remaining > 0,
            'remaining' => $remaining
        ];
    }


    public function checkFingerprint($fingerPrintComponents, $clientIp)
    {
        $riskScore = 0;
        $reason = 'OK';
        $components = $fingerPrintComponents;

        $userAgent = $components['userAgent']['value'] ?? '';
        $platform = $components['platform']['value'] ?? '';
        $webGlRenderer = $components['webGlBasics']['value']['rendererUnmasked'] ?? '';
        $timezone = $components['timezone']['value'] ?? '';
        $hConcurrency = $components['hardwareConcurrency']['value'] ?? 0;
        $deviceMem = $components['deviceMemory']['value'] ?? 0;
        $screenRes = $components['screenResolution']['value'] ?? [0, 0];
        $screenWidth = $screenRes[0] ?? 0;
        $screenHeight = $screenRes[1] ?? 0;

        $maxRiskScore = 5;

        if (str_contains($webGlRenderer, 'SwiftShader') || str_contains($userAgent, 'HeadlessChrome')) {
            $riskScore += 4;
            $reason = 'Virtual/Headless Browser Detected';
        }

        if ((str_contains($userAgent, 'Windows') && $platform === 'MacIntel') || (str_contains($userAgent, 'Macintosh') && $platform === 'Win32')) {
            $riskScore += 4;
            $reason = 'UA/Platform Mismatch';
        }

        if ($platform === 'Linux' && $hConcurrency <= 4 && $deviceMem === 0) {
            $riskScore += 3;
            $reason = 'Generic Linux/Low Concurrency';
        }

        if ($timezone === 'UTC' ) {
            $riskScore += 2;
            $reason = 'Generic/Suspicious Timezone (UTC)';
        }

        if (($screenWidth === 699 && $screenHeight === 1498) || ($screenWidth > 0 && $screenWidth < 800 && $platform === 'Win32')) {
            $riskScore += 2;
            $reason = 'Unusual Screen Resolution';
        }

        if ($riskScore >= $maxRiskScore) {
            return ['risk_score' => $riskScore, 'action' => 'BLOCK', 'reason' => $reason];
        }

        return ['risk_score' => $riskScore, 'action' => 'ALLOW', 'reason' => 'OK'];
    }
}
