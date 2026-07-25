<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureManagerHasAssignedProperty
{
    public function __construct(
        private readonly AssignedPropertyScope $assignments,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();

        if ($actor instanceof User) {
            $this->assignments->ensureHasAssignments($actor);
        }

        return $next($request);
    }
}
