<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Modules\Assets\Queries\PropertyContextQuery;
use App\Modules\Assets\Support\PropertyContextRoutes;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolvePropertyContext
{
    private const SESSION_KEY = 'property_context_id';

    public function __construct(
        private readonly PropertyContextQuery $properties,
        private readonly PropertyContextRoutes $routes,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();

        if (! $request->isMethod('GET') || ! $actor instanceof User) {
            return $next($request);
        }

        if (! $actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])) {
            $request->session()->forget(self::SESSION_KEY);

            return $next($request);
        }

        if ($request->query->has('property_id')) {
            $this->rememberRequestedProperty($request, $actor);
        } elseif ($this->supports($request)) {
            $this->applyRememberedProperty($request, $actor);
        }

        return $next($request);
    }

    private function rememberRequestedProperty(Request $request, User $actor): void
    {
        $value = $request->query('property_id');

        if (in_array($value, [null, '', 'all'], true)) {
            $request->session()->forget(self::SESSION_KEY);

            return;
        }

        $propertyId = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($propertyId && $this->properties->allows($actor, (int) $propertyId)) {
            $request->session()->put(self::SESSION_KEY, (int) $propertyId);
        }
    }

    private function applyRememberedProperty(Request $request, User $actor): void
    {
        $propertyId = (int) $request->session()->get(self::SESSION_KEY, 0);

        if ($propertyId < 1 || ! $this->properties->allows($actor, $propertyId)) {
            $request->session()->forget(self::SESSION_KEY);

            return;
        }

        $request->query->set('property_id', (string) $propertyId);
    }

    private function supports(Request $request): bool
    {
        return $this->routes->includes($request->route()?->getName());
    }
}
