<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Modules\Assets\Actions\ManageAssets;
use App\Modules\Assets\Presenters\AssetDetailPresenter;
use App\Modules\Assets\Presenters\AssetFormPresenter;
use App\Modules\Assets\Queries\AssetIndexQuery;
use App\Modules\Assets\Requests\StoreAssetRequest;
use App\Modules\Assets\Requests\UpdateAssetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function __construct(
        private readonly AssetIndexQuery $indexQuery,
        private readonly AssetFormPresenter $formPresenter,
        private readonly AssetDetailPresenter $detailPresenter,
        private readonly ManageAssets $assets,
    ) {}

    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        return Inertia::render('admin/assets/index', $this->indexQuery->handle($request, $actor));
    }

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present($actor, defaults: $request->only(['portfolio_id', 'parent_id'])),
        ]);
    }

    public function show(Request $request, Asset $asset): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $asset->portfolio_id);

        return Inertia::render('admin/resource-show', [
            'detailPage' => $this->detailPresenter->present($asset, $actor),
        ]);
    }

    public function edit(Request $request, Asset $asset): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $asset->portfolio_id);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present($actor, $asset),
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $asset = $this->assets->create($actor, $request->validated());

        return to_route('assets.show', $asset)->with('success', trans('app.messages.record_created', [
            'resource' => trans('app.nav.assets'),
            'name' => app()->isLocale('ar') ? $asset->title_ar : $asset->title_en,
        ]));
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->ensurePortfolioAccess($actor, $asset->portfolio_id);
        $asset = $this->assets->update($actor, $asset, $request->validated());

        return to_route('assets.show', $asset)->with('success', trans('app.messages.record_updated', [
            'resource' => trans('app.nav.assets'),
            'name' => app()->isLocale('ar') ? $asset->title_ar : $asset->title_en,
        ]));
    }

    public function destroy(Request $request, Asset $asset): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $asset->portfolio_id);

        if (! $this->assets->archive($actor, $asset)) {
            return back()->with('error', trans('app.errors.asset_has_active_lease'));
        }

        return to_route('assets.index')->with('success', trans('app.messages.asset_archived', [
            'name' => app()->isLocale('ar') ? $asset->title_ar : $asset->title_en,
        ]));
    }
}
