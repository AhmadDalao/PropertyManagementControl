<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    /**
     * End existing sessions as soon as an administrator disables an account.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->status === 'active') {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson()
            ? $this->jsonResponse()
            : $this->redirectResponse();
    }

    private function jsonResponse(): JsonResponse
    {
        return response()->json(['message' => trans('auth.inactive')], 401);
    }

    private function redirectResponse(): RedirectResponse
    {
        return to_route('login')->withErrors([
            'email' => trans('auth.inactive'),
        ]);
    }
}
