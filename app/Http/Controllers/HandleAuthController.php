<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerification;
use App\Models\User;
use App\Services\CountryIsoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class HandleAuthController extends Controller
{
    protected CountryIsoService $countryIsoService;

    public function __construct(CountryIsoService $countryIsoService)
    {
        $this->countryIsoService = $countryIsoService;
    }

    public function handleLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (auth()->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if ($request->expectsJson()) {
                return response()->json([
                    'user' => auth()->user(),
                    'csrf_token' => csrf_token(),
                ]);
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
        $clientIp = $request->ip();

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $country = $this->countryIsoService->getCountries($clientIp);

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
            Mail::to($user->email)->send(new EmailVerification($verificationCode, $user->username));
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email: ' . $e->getMessage());
        }

        auth()->login($user);
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'user' => $user,
                'verification_required' => true,
                'csrf_token' => csrf_token(),
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

        if ($user->email_verified) {
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
        $user->email_verified = true;
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

        if ($user->email_verified) {
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
            Mail::to($user->email)->send(new EmailVerification($verificationCode, $user->username));
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

    public function changeEmail(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'You must be logged in to change your email.',
            ], 401);
        }

        $validated = $request->validate([
            'new_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['required'],
        ]);

        if ($validated['new_email'] === $user->email) {
            return response()->json([
                'success' => false,
                'error' => 'This is already your current email address.',
            ], 400);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid password.',
            ], 400);
        }

        $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(15);

        $user->email_verification_code = $verificationCode;
        $user->email_verification_code_expires_at = $expiresAt;
        $user->new_email = $validated['new_email'];
        $user->save();

        try {
            Mail::to($validated['new_email'])->send(new EmailVerification($verificationCode, $user->username));
        } catch (\Exception $e) {
            \Log::error('Failed to send email change verification: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to send verification email. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to your new email address.',
        ]);
    }

    public function verifyEmailChange(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'You must be logged in to verify email change.',
            ], 401);
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (!$user->email_verification_code || !$user->email_verification_code_expires_at || !$user->new_email) {
            return response()->json([
                'success' => false,
                'error' => 'No email change request found.',
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

        $emailExists = User::where('email', $user->new_email)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailExists) {
            return response()->json([
                'success' => false,
                'error' => 'This email address is already in use. Please request a change to a different email.',
            ], 400);
        }

        $user->email = $user->new_email;
        $user->new_email = null;
        $user->email_verified = false;
        $user->email_verification_code = null;
        $user->email_verification_code_expires_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Email changed successfully! Please verify your new email.',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        $resetCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(15);

        $user->email_verification_code = $resetCode;
        $user->email_verification_code_expires_at = $expiresAt;
        $user->save();

        try {
            Mail::to($user->email)->send(new EmailVerification($resetCode, $user->username));
        } catch (\Exception $e) {
            \Log::error('Failed to send password reset email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to send reset code. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset code sent to your email.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user->email_verification_code || !$user->email_verification_code_expires_at) {
            return response()->json([
                'success' => false,
                'error' => 'No reset code found. Please request a new one.',
            ], 400);
        }

        if (now()->isAfter($user->email_verification_code_expires_at)) {
            return response()->json([
                'success' => false,
                'error' => 'Reset code has expired. Please request a new one.',
            ], 400);
        }

        if ($user->email_verification_code !== $validated['code']) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid reset code.',
            ], 400);
        }

        $user->password = Hash::make($validated['password']);
        $user->email_verification_code = null;
        $user->email_verification_code_expires_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully!',
        ]);
    }
}
