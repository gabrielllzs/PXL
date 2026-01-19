<?php

namespace App\Http\Controllers;

use App\Events\PixelPlaced;
use App\Models\Pixel;
use App\Services\PixelCounterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PixelController extends Controller
{

    protected PixelCounterService $pixelCounter;

    public function __construct(PixelCounterService $pixelCounter)
    {
        $this->pixelCounter = $pixelCounter;
    }

    public function index()
    {
        return Pixel::all();
    }

    public function store(Request $request)
    {
        $clientIp = $request->ip();
        $user = auth()->user();
        $isAuthenticated = auth()->check();

        $request->validate([
            'x' => 'required|integer',
            'y' => 'required|integer',
            'color' => 'required|string',
            'visitorId' => 'required|string',
            'captchaToken' => $isAuthenticated
                ? 'nullable|string'
                : ($request->session()->get('captcha_verified', false)
                    ? 'nullable|string'
                    : 'required|string'),
        ]);

        // Only check captcha for visitors
        if (!$isAuthenticated && !$request->session()->get('captcha_verified', false)) {
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

        // Check if authenticated user has verified their email
        if ($isAuthenticated) {
            if (!$user->email_verified_at) {
                return response()->json([
                    'error' => 'email_not_verified',
                    'message' => 'Please verify your email before placing pixels.',
                ], 403);
            }
        }

        $visitorId = $request->input('visitorId');



        // Authenticated users have no cooldown - skip all cooldown checks
        if(!$isAuthenticated) {
            // Visitors have cooldown - check cache and database
            $cacheKey = "cooldown:visitor:{$visitorId}";

            if (Cache::has($cacheKey)) {
                $remaining = Cache::get($cacheKey);
                return response()->json([
                    'error' => 'cooldown',
                    'remaining' => $remaining,
                ], 412);
            }

            $cooldownCheck = $this->cooldown($request);
            $remaining = $cooldownCheck['remaining'];

            if ($remaining > 0) {
                return response()->json([
                    'error' => 'cooldown',
                    'remaining' => $remaining,
                ], 412);
            }
        }

        // Dit zegt dat als er al een pixel is op die coördinaten, die geüpdatet wordt of er een nieuwe wordt gemaakt
        $pixel = Pixel::updateOrCreate(
            [
                'x' => $request->x,
                'y' => $request->y,
            ],
            [
                'color' => $request->color ?? 'black',
                'visitor_id' => $visitorId,
                'user_id' => auth()->id(),
                'risk_score' => 0,
                'ip_address' => $clientIp,
            ]
        );

        $this->pixelCounter->addPixel($user);


        // Zet een cooldown in de cache alleen voor visitors (niet voor geauthenticeerde gebruikers)
        if (!auth()->check()) {
            $cooldownSeconds = 10;
            $cacheKey = "cooldown:visitor:{$visitorId}";
            Cache::put($cacheKey, $cooldownSeconds, $cooldownSeconds);
        } else {
            $cooldownSeconds = 0;
        }

        // Trigger PixelPlaced event voor real-time updates
        event(new PixelPlaced($pixel->x, $pixel->y, $pixel->color));

        return response()->json(array_merge(
            $pixel->toArray(),
            ['cooldownDuration' => $cooldownSeconds]
        ));

    }

    public function cooldown(Request $request)
    {
        $visitorId = $request->input('visitorId');
        $cooldownSeconds = 10;

        $query = Pixel::where('visitor_id', $visitorId);

        $last = $query->latest()->first();

        if (!$last) {
            return [
                'cooldown' => false,
                'remaining' => 0,
                'hasReduction' => auth()->check(),
                'cooldownDuration' => $cooldownSeconds,
            ];
        }

        $elapsed = $last->created_at->diffInSeconds(now());
        $remaining = max(0, $cooldownSeconds - $elapsed);

        return [
            'cooldown' => $remaining > 0,
            'remaining' => $remaining,
            'hasReduction' => auth()->check(),
            'cooldownDuration' => $cooldownSeconds,
            'elapsed' => $elapsed,
        ];
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
