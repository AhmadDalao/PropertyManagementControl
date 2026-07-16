<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Modules\Assets\PropertyMapPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PropertyMapController extends Controller
{
    public function __invoke(Request $request, PropertyMapPresenter $propertyMap): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $portfolioId = $this->nullableInteger($request->query('portfolio_id'));
        $assetQuery = $this->scopeByPortfolio(Asset::query(), $actor);

        if ($portfolioId !== null) {
            $this->ensurePortfolioAccess($actor, $portfolioId);
            $assetQuery->where('portfolio_id', $portfolioId);
        }

        return Inertia::render('admin/property-map/index', [
            'propertyMap' => $propertyMap->forQuery($assetQuery),
            'portfolioOptions' => $this->portfolioOptions($actor),
            'filters' => [
                'portfolio_id' => $portfolioId,
            ],
        ]);
    }
}
