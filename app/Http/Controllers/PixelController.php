<?php

namespace App\Http\Controllers;

use App\Events\PixelPlaced;
use App\Models\Pixel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Exception;
use Illuminate\Support\Facades\Http;
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
                'risk_score' => 0,
                'ip_address' => $clientIp,
            ]
        );

        event(new PixelPlaced($pixel->x, $pixel->y, $pixel->color));
        return response()->json($pixel);
    }

    public function cooldown(Request $request)
    {
        $cooldownSeconds = 180;
        $clientIp = $request->ip();
        $wallet = $request->input('wallet');


        if (str_contains($clientIp, ':')) {
            $query = Pixel::where('ip_address', $clientIp);
        } else {
            // IPv4: Fallback to visitorId.
            // Using IP here would block the whole household (NAT), which we want to avoid.
            $visitorId = $request->input('visitorId');
            $query = Pixel::where('visitor_id', $visitorId);
        }

        $last = $query->latest()->first();

        Log::info('Checking remote balance for wallet: ' . $wallet);


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

    public function getVisitors()
    {
        return Pixel::query()
            ->select('visitor_id', 'ip_address', 'risk_score', 'created_at')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('pixels')
                    ->groupBy('visitor_id');
            })
            ->whereNotIn('visitor_id', function ($query) {
                $query->select('visitor_id')
                    ->from('banned_users')
                    ->where('banned', true);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function getRemoteBalance($wallet)
    {
        // Validate wallet address format (basic check)
        if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $wallet)) {
            return 0; // Invalid address → treat as low balance
        }

        $rpcUrl = 'https://mainnet.helius-rpc.com/?api-key=c6cba14f-6ef1-4fd4-8db6-cd718438f272';

        try {
            $response = Http::timeout(10)->post($rpcUrl, [
                'jsonrpc' => '2.0',
                'id' => 'pixel-cooldown-check',
                'method' => 'getBalance',
                'params' => [$wallet],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['result']['value'])) {
                    $lamports = $data['result']['value'];
                    $sol = $lamports / 1000000000; // 1 SOL = 1_000_000_000 lamports
                    return $sol;
                }
            }

            // Log error if needed: \Log::warning('Solana balance check failed', ['response' => $response->body()]);
        } catch (Exception $e) {
            // Silent fail – don't break the endpoint if RPC is down
            // \Log::error('Solana RPC error', ['exception' => $e]);
        }

        return 0; // On any error, default to low balance (longer cooldown)
    }
}
