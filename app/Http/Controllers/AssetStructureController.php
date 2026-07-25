<?php

namespace App\Http\Controllers;

use App\Modules\Assets\Actions\CreateBuildingStructure;
use App\Modules\Assets\Presenters\BuildingStructureFormPresenter;
use App\Modules\Assets\Requests\StoreBuildingStructureRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetStructureController extends Controller
{
    public function create(Request $request, BuildingStructureFormPresenter $presenter): Response
    {
        return Inertia::render('admin/assets/structure-create', [
            'buildingSetup' => $presenter->present(
                $this->actor($request),
                $request->only('portfolio_id'),
            ),
        ]);
    }

    public function store(
        StoreBuildingStructureRequest $request,
        CreateBuildingStructure $structures,
    ): RedirectResponse {
        $data = $request->validated();
        $building = $structures->handle($this->actor($request), $data);

        return to_route('assets.show', $building)->with('success', trans('app.assets.builder.created', [
            'floors' => $data['floor_count'],
            'units' => (int) $data['floor_count'] * (int) $data['units_per_floor'],
        ]));
    }
}
