<?php

namespace App\Http\Controllers;

use App\Events\PixelPlaced;
use App\Models\BannedUser;
use App\Models\Pixel;
use App\Models\User;
use App\Services\PixelCounterService;
use App\Services\LevelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

    public function index()
    {
        return Pixel::select('x', 'y', 'color')->get();
    }

    public function store(Request $request)
    {
        $clientIp = $request->ip();
        $user = auth()->user();
        $isAuthenticated = auth()->check();

        if ($isAuthenticated && $user) {
            $bannedUser = BannedUser::where('user_id', $user->id)
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
        // Authenticated users have no cooldown
        if (auth()->check()) {
            return [
                'cooldown' => false,
                'remaining' => 0,
                'hasReduction' => true,
                'cooldownDuration' => 10,
            ];
        }

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

    public function getUsers()
    {
        $users = User::select('id', 'username', 'email')->get();

        $anonymousVisitors = Pixel::query()
            ->whereNull('user_id')
            ->whereNotIn('visitor_id', function ($q) {
                $q->select('visitor_id')
                    ->from('banned_users')
                    ->where('banned', true);
            })
            ->orderBy('id', 'desc')
            ->get()
            ->unique('visitor_id')
            ->values();

        return [
            'users' => $users,
            'anonymous_visitors' => $anonymousVisitors,
        ];


    }
}
