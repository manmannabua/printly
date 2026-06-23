<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SetSecurityPinRequest;
use App\Http\Requests\Auth\VerifySecurityPinRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    /**
     * Authenticate user and create session.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        // Find the user — constant-time rejection to prevent timing enumeration
        $user = User::where('email', $credentials['email'])->first();

        // Always run Hash::check even when user not found (constant-time)
        $dummyHash = '$2y$12$K4G/RqFqmTMoJlFSyGYpQuSKz1nSqOgQS5dB3VJeog.mfNFsa3R3e';
        $passwordValid = Hash::check($credentials['password'], $user->password ?? $dummyHash);
        if (!$user || !$passwordValid) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // S10: Return same generic error for deactivated accounts to prevent user enumeration.
        // Deactivated users won't learn their account status via login — they must be notified separately.
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Regenerate session to prevent session fixation
        $request->session()->regenerate();

        // Log the user in
        Auth::login($user);

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        AuditLog::log($user, 'login');

        // Load relationships for response
        $user->load('roles.permissions');

        return $this->success(
            new UserResource($user),
            'Login successful.'
        );
    }

    /**
     * Log out the authenticated user.
     */
    public function logout(Request $request): JsonResponse
    {
        AuditLog::log($request->user(), 'logout');

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success(null, 'Logout successful.');
    }

    /**
     * Get the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('roles.permissions', 'directPermissions');

        return $this->success(new UserResource($user));
    }

    /**
     * Send password reset link to user's email.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        // Silently send — don't reveal whether the email exists (prevents user enumeration)
        Password::sendResetLink($request->only('email'));

        return $this->success(
            null,
            'If that email address is registered, you will receive a password reset link shortly.'
        );
    }

    /**
     * Reset user's password using token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');
        $callback = function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            AuditLog::log($user, 'password_reset');
        };

        // Try the activation broker first (12-hour TTL for account setup links),
        // then fall back to the users broker (60-minute TTL for forgot-password links).
        $status = Password::broker('activation')->reset($credentials, $callback);
        if ($status !== Password::PASSWORD_RESET) {
            $status = Password::broker('users')->reset($credentials, $callback);
        }

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(
                null,
                'Your password has been reset successfully.'
            );
        }

        // S9: Return generic error regardless of failure reason to prevent user enumeration
        throw ValidationException::withMessages([
            'email' => ['The password reset link is invalid or has expired.'],
        ]);
    }

    /**
     * Change the authenticated user's password.
     *
     * POST /auth/change-password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        AuditLog::log($user, 'password_changed');

        return $this->success(null, 'Password changed successfully.');
    }

    /**
     * Set or change the security PIN.
     *
     * POST /auth/security-pin
     */
    public function setSecurityPin(SetSecurityPinRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->security_pin) {
            // Changing existing PIN — verify current PIN
            if (!$request->current_pin || !Hash::check($request->current_pin, $user->security_pin)) {
                throw ValidationException::withMessages([
                    'current_pin' => ['The current PIN is incorrect.'],
                ]);
            }
        } else {
            // First-time setup — verify password
            if (!$request->current_password || !Hash::check($request->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password is incorrect.'],
                ]);
            }
        }

        $user->update([
            'security_pin' => $request->pin,
        ]);

        AuditLog::log($user, 'security_pin_changed');

        $user->refresh();

        return $this->success(['has_security_pin' => $user->has_security_pin], 'Security PIN updated successfully.');
    }

    /**
     * Verify the security PIN.
     *
     * POST /auth/verify-pin
     */
    public function verifyPin(VerifySecurityPinRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->security_pin) {
            return $this->error('No security PIN has been set.', 422);
        }

        if (!Hash::check($request->pin, $user->security_pin)) {
            throw ValidationException::withMessages([
                'pin' => ['The PIN is incorrect.'],
            ]);
        }

        // Stamp a 5-minute reveal window. Resources for sensitive PII (govt
        // IDs, etc.) check this before unmasking — without a fresh PIN
        // verification they only return the last-4 mask.
        // Guard: mobile token-auth requests have no session driver active.
        if ($request->hasSession()) {
            $request->session()->put('pin_verified_at', now()->toIso8601String());

            // Clear any server-side session lock — successful PIN verification
            // is the only way to unlock the screen.
            $request->session()->forget('is_locked');
        }

        return $this->success(null, 'PIN verified successfully.');
    }

    /**
     * Lock the current session. New tabs sharing this session cookie will
     * see the lock screen on load, preventing PIN bypass via URL copy.
     *
     * POST /auth/lock
     */
    public function lock(Request $request): JsonResponse
    {
        // Guard: mobile token-auth requests have no session driver active.
        if ($request->hasSession()) {
            $request->session()->put('is_locked', true);
        }

        return $this->success(null, 'Session locked.');
    }

    /**
     * List the authenticated user's active sessions.
     *
     * GET /auth/sessions
     */
    public function sessions(Request $request): JsonResponse
    {
        $currentSessionId = $request->session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) use ($currentSessionId) {
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'last_activity' => date('c', $session->last_activity),
                    'is_current' => $session->id === $currentSessionId,
                ];
            });

        return $this->success($sessions);
    }

    /**
     * Delete a specific session (cannot delete current session).
     *
     * DELETE /auth/sessions/{id}
     */
    public function destroySession(Request $request, string $id): JsonResponse
    {
        $currentSessionId = $request->session()->getId();

        if ($id === $currentSessionId) {
            return $this->error('Cannot revoke the current session. Use logout instead.', 422);
        }

        $deleted = DB::table('sessions')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        if (!$deleted) {
            return $this->notFound('Session not found.');
        }

        AuditLog::log($request->user(), 'session_destroyed');

        return $this->success(null, 'Session revoked successfully.');
    }

    /**
     * Log out all other devices (delete all sessions except current).
     *
     * POST /auth/logout-other-devices
     */
    public function logoutOtherDevices(Request $request): JsonResponse
    {
        $currentSessionId = $request->session()->getId();

        $deleted = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        AuditLog::log($request->user(), 'other_devices_logged_out');

        return $this->success(
            ['revoked_count' => $deleted],
            'All other sessions have been revoked.'
        );
    }

    // ── Mobile app (token-based auth) ─────────────────────────────────────────

    /**
     * Authenticate via API token — for mobile apps that cannot maintain a
     * Laravel session across process restarts.
     *
     * Uses the same constant-time and deactivation checks as SPA login.
     * Any existing mobile token for this user is revoked first to prevent
     * unbounded accumulation across reinstalls.
     *
     * POST /auth/mobile-login  (public, throttled)
     */
    public function mobileLogin(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Constant-time rejection — same dummy hash as SPA login.
        $dummyHash = '$2y$12$K4G/RqFqmTMoJlFSyGYpQuSKz1nSqOgQS5dB3VJeog.mfNFsa3R3e';
        $passwordValid = Hash::check($request->password, $user->password ?? $dummyHash);

        if (!$user || !$passwordValid || !$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke existing mobile tokens to avoid accumulation on reinstall.
        $user->tokens()->where('name', 'mobile')->delete();

        // Create a long-lived token (365 days).
        $token = $user->createToken('mobile', ['*'], now()->addDays(365))->plainTextToken;

        $user->update(['last_login_at' => now()]);
        AuditLog::log($user, 'mobile_login');

        $user->load('roles.permissions');

        // Return the same shape as SPA login with the token at the top level.
        // UserModel.fromJson() reads json['data'] for user fields and
        // json['token'] for the bearer token — no nested restructuring needed.
        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => new UserResource($user),
            'token'   => $token,
        ]);
    }

    /**
     * Revoke the current mobile Bearer token.
     *
     * POST /auth/mobile-logout  (auth:sanctum)
     */
    public function mobileLogout(Request $request): JsonResponse
    {
        AuditLog::log($request->user(), 'mobile_logout');
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out.');
    }

    /**
     * Authorize a Reverb private channel for the mobile app.
     *
     * This mirrors /broadcasting/auth but lives in the API route group
     * (no CSRF, no web session required) so Bearer-token mobile clients
     * can authenticate private channels without needing an XSRF cookie.
     *
     * POST /api/v1/broadcasting/auth  (auth:sanctum)
     */
    public function mobileBroadcastAuth(Request $request): JsonResponse
    {
        try {
            return response()->json(Broadcast::auth($request));
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }
}
