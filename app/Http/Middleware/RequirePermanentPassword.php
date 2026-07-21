<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermanentPassword
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if (
            $user?->force_password_reset
            && ! $request->routeIs('profile.*', 'logout')
        ) {
            return to_route('profile.index')->with(
                'warning',
                trans('app.messages.password_change_required'),
            );
        }

        return $next($request);
    }
}
