<?php

namespace App\Http\Controllers;

use App\Modules\Assets\Actions\CreateBuildingStructure;
use App\Modules\Assets\Presenters\BuildingStructureFormPresenter;
use App\Modules\Assets\Requests\StoreBuildingStructureRequest;
use App\Modules\Portfolios\Support\PortfolioSetupContinuation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetStructureController extends Controller
{
    public function create(Request $request, BuildingStructureFormPresenter $presenter): Response
    {
        return Inertia::render('admin/assets/structure-create', [
            'buildingSetup' => $presenter->present($this->actor($request), $request->only([
                'portfolio_id', PortfolioSetupContinuation::QUERY_KEY,
            ])),
        ]);
    }

    public function store(
        StoreBuildingStructureRequest $request,
        CreateBuildingStructure $structures,
        PortfolioSetupContinuation $setup,
    ): RedirectResponse {
        $data = $request->validated();
        $actor = $this->actor($request);
        $portfolio = $setup->fromRequest($request, $actor);
        $setup->ensureMatches($portfolio, $data['portfolio_id'] ?? null);
        $building = $structures->handle($actor, $data);

        if ($setup->matches($portfolio, $building->portfolio_id)) {
            return to_route('portfolios.show', $portfolio)
                ->with('success', trans('app.messages.portfolio_setup_continue', [
                    'name' => app()->isLocale('ar') ? $building->title_ar : $building->title_en,
                    'portfolio' => $setup->name($portfolio),
                ]));
        }

        return to_route('assets.show', $building)->with('success', trans('app.assets.builder.created', [
            'floors' => $data['floor_count'],
            'units' => (int) $data['floor_count'] * (int) $data['units_per_floor'],
        ]));
    }
}
