<?php

namespace App\Modules\Authentication\Actions;

use App\Models\User;
use App\Modules\Authentication\Requests\LoginRequest;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class AuthenticateUser
{
    public function execute(LoginRequest $request): User
    {
        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt($request->credentials(), $request->rememberLogin())) {
            $this->reject($request, 'auth.failed');
        }

        $user = Auth::user();

        if (! $user instanceof User || $user->status !== 'active') {
            Auth::guard('web')->logout();
            $this->reject($request, 'auth.inactive');
        }

        RateLimiter::clear($request->throttleKey());
        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }

    private function ensureIsNotRateLimited(LoginRequest $request): void
    {
        if (! RateLimiter::tooManyAttempts($request->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($request));
        $seconds = RateLimiter::availableIn($request->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function reject(LoginRequest $request, string $message): never
    {
        RateLimiter::hit($request->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans($message),
        ]);
    }
}
