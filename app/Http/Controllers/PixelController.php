<?php

namespace App\Http\Controllers;

use App\Events\PixelPlaced;
use App\Models\Pixel;
use App\Services\SolanaBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PixelController extends Controller
{
    private SolanaBalanceService $balanceService;

    public function __construct(SolanaBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function index()
    {
        return Pixel::all();
    }

    public function store(Request $request)
    {
        $clientIp = $request->ip();

        $request->validate([
            'x' => 'required|integer',
            'y' => 'required|integer',
            'color' => 'required|string',
            'visitorId' => 'required|string',
            'components' => 'required|array',
            'captchaToken' => $request->session()->get('captcha_verified', false)
                ? 'nullable|string'
                : 'required|string',
            'wallet' => 'nullable|array',
            'wallet.publicKey' => 'nullable|string',
            'wallet.signature' => 'nullable|array',
            'wallet.message' => 'nullable|string',
        ]);

        if (!$request->session()->get('captcha_verified', false)) {
            $token = $request->input('captchaToken');

            if (!$token) {
                return response()->json([
                    'error' => 'captcha_required',
                ], 403);
            }

            $response = Http::asForm()->post('https://hcaptcha.com/siteverify', [
                'secret'   => config('services.captcha.secret'),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

            $result = $response->json();

            if (!($result['success'] ?? false)) {
                return response()->json([
                    'error' => 'captcha_failed',
                ], 403);
            }

            $request->session()->put('captcha_verified', true);
        }

        $visitorId = $request->input('visitorId');
        $components = $request->input('components');

        $riskData = $this->checkFingerprint($components, $clientIp);

        if ($riskData['action'] === 'BLOCK') {
            return response()->json([
                'error' => 'security_block',
                'reason' => $riskData['reason'],
            ], 403);
        }

        $cooldownCheck = $this->cooldown($request);
        $remaining = $cooldownCheck['remaining'];

        if ($remaining > 0) {
            return response()->json([
                'error' => 'cooldown',
                'remaining' => $remaining,
                'hasReduction' => $cooldownCheck['hasReduction'] ?? false,
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
        $clientIp = $request->ip();
        $walletData = $request->input('wallet');

        $hasReduction = false;
        $cooldownSeconds = 180;

        if ($walletData && isset($walletData['publicKey'])) {
            $publicKey = $walletData['publicKey'];
            $hasReduction = $this->balanceService->hasReduction($publicKey);

            if ($hasReduction) {
                $cooldownSeconds = 60;
            }
        }

        if (str_contains($clientIp, ':')) {
            $query = Pixel::where('ip_address', $clientIp);
        } else {
            $visitorId = $request->input('visitorId');
            $query = Pixel::where('visitor_id', $visitorId);
        }

        $last = $query->latest()->first();

        if (!$last) {
            return [
                'cooldown' => false,
                'remaining' => 0,
                'hasReduction' => $hasReduction,
                'cooldownDuration' => $cooldownSeconds,
            ];
        }

        $elapsed = $last->created_at->diffInSeconds(now());
        $remaining = max(0, $cooldownSeconds - $elapsed);

        return [
            'cooldown' => $remaining > 0,
            'remaining' => $remaining,
            'hasReduction' => $hasReduction,
            'cooldownDuration' => $cooldownSeconds,
            'elapsed' => $elapsed,
        ];
    }

    public function checkFingerprint($fingerPrintComponents, $clientIp)
    {
        return ['risk_score' => 0, 'action' => 'ALLOW', 'reason' => 'OK'];
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
}
