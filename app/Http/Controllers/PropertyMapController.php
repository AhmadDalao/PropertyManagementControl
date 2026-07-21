<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Assets\Queries\PropertyMapQuery;
use App\Modules\Assets\Requests\PropertyMapRequest;
use Inertia\Inertia;
use Inertia\Response;

class PropertyMapController extends Controller
{
    public function __invoke(PropertyMapRequest $request, PropertyMapQuery $propertyMap): Response
    {
        /** @var User $actor */
        $actor = $request->user();

        return Inertia::render(
            'admin/property-map/index',
            $propertyMap->handle($actor, $request->portfolioId()),
        );
    }
}
