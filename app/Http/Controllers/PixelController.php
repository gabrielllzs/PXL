<?php

namespace App\Http\Controllers;

use App\Events\PixelPlaced;
use App\Models\BannedUser;
use App\Models\Pixel;
use App\Services\PixelCounterService;
use App\Services\LevelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\PixelHistory;
use Illuminate\Support\Facades\Log;

class PixelController extends Controller
{

    protected PixelCounterService $pixelCounter;
    protected LevelService $levelService;

    public function __construct(PixelCounterService $pixelCounter, LevelService $levelService)
    {
        $this->pixelCounter = $pixelCounter;
        $this->levelService = $levelService;
    }

    public function index(Request $request)
    {
        $minX = $request->has('minX') ? (int) $request->query('minX') : null;
        $maxX = $request->has('maxX') ? (int) $request->query('maxX') : null;
        $minY = $request->has('minY') ? (int) $request->query('minY') : null;
        $maxY = $request->has('maxY') ? (int) $request->query('maxY') : null;

        $useBounds = $minX !== null && $maxX !== null && $minY !== null && $maxY !== null;
        if ($useBounds) {
            $extent = 10000;
            [$minX, $maxX] = [max(-$extent, min($minX, $maxX)), min($extent, max($minX, $maxX))];
            [$minY, $maxY] = [max(-$extent, min($minY, $maxY)), min($extent, max($minY, $maxY))];
            return Pixel::select('x', 'y', 'color')
                ->whereBetween('x', [$minX, $maxX])
                ->whereBetween('y', [$minY, $maxY])
                ->get();
        }

        return Pixel::select('x', 'y', 'color')->get();
    }

    public function store(Request $request)
    {
        $clientIp = $request->ip();
        $user = auth()->user();
        $isAuthenticated = auth()->check();

        if ($isAuthenticated && $user) {
            $bannedUser = BannedUser::where('user_id', $user->id)
                ->where('banned', true)
                ->where(function ($query) {
                    $query->where('is_permanent', true)
                        ->orWhere(function ($q) {
                            $q->whereNotNull('banned_until')
                                ->where('banned_until', '>', now());
                        });
                })
                ->first();

            if ($bannedUser) {
                return response()->json([
                    'error' => 'banned',
                    'reason' => $bannedUser->reason ?? 'You have been banned from placing pixels.',
                    'is_permanent' => $bannedUser->is_permanent,
                    'banned_until' => $bannedUser->banned_until ? $bannedUser->banned_until->toIso8601String() : null,
                ], 403);
            }
        }

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

        if (!$isAuthenticated) {
            $visitorId = $request->input('visitorId');
            $bannedVisitor = BannedUser::where('visitor_id', $visitorId)
                ->where('banned', true)
                ->where(function ($query) {
                    $query->where('is_permanent', true)
                        ->orWhere(function ($q) {
                            $q->whereNotNull('banned_until')
                                ->where('banned_until', '>', now());
                        });
                })
                ->first();

            if ($bannedVisitor) {
                return response()->json([
                    'error' => 'banned',
                    'reason' => $bannedVisitor->reason ?? 'You have been banned from placing pixels.',
                    'is_permanent' => $bannedVisitor->is_permanent,
                    'banned_until' => $bannedVisitor->banned_until ? $bannedVisitor->banned_until->toIso8601String() : null,
                ], 403);
            }
        }

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
            if (!$user->email_verified) {
                return response()->json([
                    'error' => 'email_not_verified',
                    'message' => 'Please verify your email before placing pixels.',
                ], 403);
            }

            // Check available pixels for authenticated users
            $this->levelService->updateUserLevel($user);
            $user->refresh();

            if ($user->pixels_available <= 0) {
                $timeUntilNext = $this->levelService->getTimeUntilRegeneration($user);
                return response()->json([
                    'error' => 'no_pixels_available',
                    'message' => 'You have no pixels available. Please wait for them to regenerate.',
                    'time_until_next' => $timeUntilNext,
                    'pixels_available' => $user->pixels_available,
                    'pixel_limit' => $this->levelService->getPixelLimit($user->level),
                ], 429);
            }
        }

        $visitorId = $request->input('visitorId');

        // Non-authenticated users only use the fixed palette
        if (! $isAuthenticated) {
            $allowedColors = config('palette.visitor_allowed', []);

            $inputColor = $request->input('color');
            $normalizedColor = is_string($inputColor) ? strtoupper(trim($inputColor)) : '';

            $allowedColorsNormalized = array_map(
                fn ($allowedColor) => strtoupper(trim($allowedColor)),
                $allowedColors
            );

            if (! in_array($normalizedColor, $allowedColorsNormalized, true)) {
                return response()->json([
                    'error' => 'custom_color_requires_auth',
                    'message' => 'Sign in to use custom colors.',
                ], 403);
            }
        }


        if (! $isAuthenticated) {
            // Abuse-preventie: max 24 pixelplaatsingen per IP per minuut (gelijk IP ≠ gedeelde cooldown)
            $ipLimitKey = 'pixel-guest-ip:' . $clientIp;
            if (RateLimiter::tooManyAttempts($ipLimitKey, 24)) {
                return response()->json([
                    'error' => 'ip_rate_limited',
                    'message' => 'Te veel plaatsingen vanaf dit adres. Probeer het over een minuut opnieuw.',
                    'retry_after' => RateLimiter::availableIn($ipLimitKey),
                ], 429);
            }

            // Per-guest 10s cooldown (visitorId);zelfde IP mag meerdere guests hebben
            $cacheKey = "cooldown:visitor:{$visitorId}";
            if (Cache::has($cacheKey)) {
                $remaining = Cache::get($cacheKey);
                return response()->json([
                    'error' => 'cooldown',
                    'remaining' => $remaining,
                ], 412);
            }

            $cooldownCheck = $this->cooldownByVisitor($request);
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
                'user_id' => $isAuthenticated && $user ? $user->id : null,
                'ip_address' => $clientIp,
            ]
        );

        $pixelHistory = PixelHistory::create([
            'x' => $request->x,
            'y' => $request->y,
            'color' => $request->color ?? 'black',
        ]);

        $this->pixelCounter->addPixel($user, $clientIp);

        $leveledUp = false;
        if ($isAuthenticated && $user) {
            $user->refresh();

            $newPixelCount = max(0, $user->pixels_available - 1);
            $user->update([
                'pixels_available' => $newPixelCount,
                'last_pixel_regeneration_time' => now(),
            ]);

            $user->refresh();
            $result = $this->levelService->updateUserLevel($user, skipRegeneration: true);
            $leveledUp = $result['leveled_up'] ?? false;
            $user->refresh();
        }

        // Cooldown alleen voor guests (per visitorId), niet voor ingelogde gebruikers
        if (! auth()->check()) {
            $cooldownSeconds = 10;
            $cacheKey = "cooldown:visitor:{$visitorId}";
            Cache::put($cacheKey, $cooldownSeconds, $cooldownSeconds);
            RateLimiter::hit('pixel-guest-ip:' . $clientIp, 60);
        } else {
            $cooldownSeconds = 0;
        }

        // Trigger PixelPlaced event voor real-time updates
        event(new PixelPlaced($pixel->x, $pixel->y, $pixel->color));

        $responseData = [
            'x' => $pixel->x,
            'y' => $pixel->y,
            'color' => $pixel->color,
            'cooldownDuration' => $cooldownSeconds,
        ];

        if ($isAuthenticated && $user) {
            $responseData['level'] = $user->level;
            $responseData['pixels_available'] = $user->pixels_available;
            $responseData['pixel_limit'] = $this->levelService->getPixelLimit($user->level);
            $responseData['leveled_up'] = $leveledUp;
            if ($leveledUp) {
                $responseData['old_level'] = $result['old_level'] ?? null;
            }
        }

        return response()->json($responseData);

    }

    public function cooldown(Request $request)
    {
        if (auth()->check()) {
            return response()->json([
                'cooldown' => false,
                'remaining' => 0,
                'hasReduction' => true,
                'cooldownDuration' => 10,
            ]);
        }

        $result = $this->cooldownByVisitor($request);

        return response()->json([
            'cooldown' => $result['cooldown'],
            'remaining' => $result['remaining'],
            'hasReduction' => false,
            'cooldownDuration' => $result['cooldownDuration'],
            'elapsed' => $result['elapsed'] ?? null,
        ]);
    }

    private function cooldownByVisitor(Request $request): array
    {
        $visitorId = $request->input('visitorId');
        $cooldownSeconds = 10;
        $cacheKey = "cooldown:visitor:{$visitorId}";

        if (Cache::has($cacheKey)) {
            $remaining = (int) Cache::get($cacheKey);
            return [
                'cooldown' => $remaining > 0,
                'remaining' => $remaining,
                'cooldownDuration' => $cooldownSeconds,
                'elapsed' => $cooldownSeconds - $remaining,
            ];
        }

        $last = Pixel::where('visitor_id', $visitorId)->latest()->first();

        if (! $last) {
            return [
                'cooldown' => false,
                'remaining' => 0,
                'cooldownDuration' => $cooldownSeconds,
                'elapsed' => null,
            ];
        }

        $elapsed = (int) $last->created_at->diffInSeconds(now());
        $remaining = max(0, $cooldownSeconds - $elapsed);

        return [
            'cooldown' => $remaining > 0,
            'remaining' => $remaining,
            'cooldownDuration' => $cooldownSeconds,
            'elapsed' => $elapsed,
        ];
    }

}
