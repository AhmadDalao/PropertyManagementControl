<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Modules\Maintenance\Actions\ManageMaintenance;
use App\Modules\Maintenance\Presenters\MaintenanceDetailPresenter;
use App\Modules\Maintenance\Presenters\MaintenanceFormPresenter;
use App\Modules\Maintenance\Queries\MaintenanceIndexQuery;
use App\Modules\Maintenance\Requests\StoreMaintenanceRequest;
use App\Modules\Maintenance\Requests\UpdateMaintenanceRequest;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        private readonly MaintenanceIndexQuery $indexQuery,
        private readonly MaintenanceFormPresenter $formPresenter,
        private readonly MaintenanceDetailPresenter $detailPresenter,
        private readonly ManageMaintenance $maintenance,
        private readonly MaintenanceAccess $access,
    ) {}

    public function index(Request $request): Response
    {
        $actor = $this->actor($request);

        return Inertia::render('admin/maintenance/index', $this->indexQuery->handle($request, $actor));
    }

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present(
                $actor,
                defaults: $request->only(['portfolio_id', 'asset_id', 'tenant_profile_id']),
            ),
        ]);
    }

    public function show(Request $request, MaintenanceRequest $maintenanceRequest): Response
    {
        $actor = $this->actor($request);
        $this->access->ensureCanAccess($actor, $maintenanceRequest);

        return Inertia::render('admin/resource-show', [
            'detailPage' => $this->detailPresenter->present($maintenanceRequest, $actor),
        ]);
    }

    public function edit(Request $request, MaintenanceRequest $maintenanceRequest): Response
    {
        $actor = $this->actor($request);
        $this->access->ensureCanAccess($actor, $maintenanceRequest);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present($actor, $maintenanceRequest),
        ]);
    }

    public function store(StoreMaintenanceRequest $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $maintenanceRequest = $this->maintenance->create($actor, $request->validated());

        return to_route('maintenance-requests.show', $maintenanceRequest)->with(
            'success',
            trans($actor->hasRole('tenant')
                ? 'app.messages.maintenance_submitted'
                : 'app.messages.maintenance_created')
        );
    }

    public function update(
        UpdateMaintenanceRequest $request,
        MaintenanceRequest $maintenanceRequest
    ): RedirectResponse {
        $actor = $this->actor($request);
        $this->maintenance->update($actor, $maintenanceRequest, $request->validated());

        return to_route('maintenance-requests.show', $maintenanceRequest)->with(
            'success',
            trans($actor->hasRole('tenant')
                ? 'app.messages.maintenance_comment_added'
                : 'app.messages.maintenance_updated')
        );
    }

    public function destroy(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $actor = $this->actor($request);

        if (! $this->maintenance->cancel($actor, $maintenanceRequest)) {
            return back()->with('error', trans('app.errors.maintenance_not_open'));
        }

        return to_route('maintenance-requests.index')
            ->with('success', trans('app.messages.maintenance_cancelled'));
    }
}
