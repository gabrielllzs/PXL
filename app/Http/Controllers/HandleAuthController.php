<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class HandleAuthController extends Controller
{
    public function handleLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (auth()->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if ($request->expectsJson()) {
                return response()->json(['user' => auth()->user()]);
            }

            return redirect()->intended('/');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'The provided credentials do not match our records.',
            ], 401);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function handleRegister(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $country = $validated['country'] ?? $this->detectCountry($request->ip());

        $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(15);

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'country' => $country,
            'email_verification_code' => $verificationCode,
            'email_verification_code_expires_at' => $expiresAt,
        ]);

        try {
            Mail::to($user->email)->send(new EmailVerification($verificationCode));
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email: ' . $e->getMessage());
        }

        auth()->login($user);
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'user' => $user,
                'verification_required' => true,
            ], 201);
        }

        return redirect('/');
    }

    public function handleLogout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Logged out successfully']);
        }

        return redirect('/');
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'You must be logged in to verify your email.',
            ], 401);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'error' => 'Email is already verified.',
            ], 400);
        }

        if (!$user->email_verification_code || !$user->email_verification_code_expires_at) {
            return response()->json([
                'success' => false,
                'error' => 'No verification code found. Please request a new one.',
            ], 400);
        }

        if (now()->isAfter($user->email_verification_code_expires_at)) {
            return response()->json([
                'success' => false,
                'error' => 'Verification code has expired. Please request a new one.',
            ], 400);
        }

        if ($user->email_verification_code !== $request->code) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid verification code.',
            ], 400);
        }

        // Verify the email
        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->email_verification_code_expires_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully!',
        ]);
    }

    public function resendVerificationCode(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'You must be logged in to resend verification code.',
            ], 401);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'error' => 'Email is already verified.',
            ], 400);
        }

        // Generate new 6-digit verification code
        $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(15);

        $user->email_verification_code = $verificationCode;
        $user->email_verification_code_expires_at = $expiresAt;
        $user->save();

        // Send verification email
        try {
            Mail::to($user->email)->send(new EmailVerification($verificationCode));
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to send verification email. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent successfully!',
        ]);
    }

    private function detectCountry(string $ip): ?string
    {
        // Simple IP-based country detection
        // MaxMind GeoIP2
        // For now, return null and let it be set later
        return null;
    }
}
