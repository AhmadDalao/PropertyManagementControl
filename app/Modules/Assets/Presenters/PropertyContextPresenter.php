<?php

namespace App\Modules\Assets\Presenters;

use App\Models\User;
use App\Modules\Assets\Queries\PropertyContextQuery;
use App\Modules\Assets\Support\PropertyContextRoutes;
use App\Modules\Portfolios\Support\PortfolioModules;
use Illuminate\Http\Request;

final readonly class PropertyContextPresenter
{
    public function __construct(
        private PropertyContextQuery $properties,
        private PropertyContextRoutes $routes,
    ) {}

    /** @return array<string, mixed>|null */
    public function present(Request $request): ?array
    {
        $actor = $request->user();

        if (
            ! $actor instanceof User
            || ! $this->routes->includes($request->route()?->getName())
            || ! PortfolioModules::enabledForUser($actor, 'assets')
        ) {
            return null;
        }

        return $this->properties->present(
            $actor,
            $request->session()->get('property_context_id'),
        );
    }
}
