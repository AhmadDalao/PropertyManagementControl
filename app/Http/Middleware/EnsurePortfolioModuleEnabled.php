<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\PortfolioModules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortfolioModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless(
            $user && PortfolioModules::enabledForUser($user, $module),
            403,
            'This module is disabled for this portfolio.'
        );

        return $next($request);
    }
}
