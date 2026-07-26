<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceVendor;
use App\Modules\Maintenance\Actions\ManageMaintenanceVendors;
use App\Modules\Maintenance\Presenters\MaintenanceVendorDetailPresenter;
use App\Modules\Maintenance\Presenters\MaintenanceVendorFormPresenter;
use App\Modules\Maintenance\Queries\MaintenanceVendorIndexQuery;
use App\Modules\Maintenance\Requests\StoreMaintenanceVendorRequest;
use App\Modules\Maintenance\Requests\UpdateMaintenanceVendorRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MaintenanceVendorController extends Controller
{
    public function __construct(
        private readonly MaintenanceVendorIndexQuery $indexQuery,
        private readonly MaintenanceVendorFormPresenter $forms,
        private readonly MaintenanceVendorDetailPresenter $details,
        private readonly ManageMaintenanceVendors $vendors,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/maintenance-vendors/index',
            $this->indexQuery->handle($request, $this->actor($request)),
        );
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->forms->present(
                $this->actor($request),
                portfolioId: $request->integer('portfolio_id') ?: null,
            ),
        ]);
    }

    public function store(StoreMaintenanceVendorRequest $request): RedirectResponse
    {
        $vendor = $this->vendors->create($this->actor($request), $request->validated());

        return to_route('maintenance-vendors.show', $vendor)
            ->with('success', trans('app.messages.maintenance_vendor_created'));
    }

    public function show(Request $request, MaintenanceVendor $maintenanceVendor): Response
    {
        return Inertia::render('admin/resource-show', [
            'detailPage' => $this->details->present(
                $maintenanceVendor,
                $this->actor($request),
            ),
        ]);
    }

    public function edit(Request $request, MaintenanceVendor $maintenanceVendor): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->forms->present(
                $this->actor($request),
                $maintenanceVendor,
            ),
        ]);
    }

    public function update(
        UpdateMaintenanceVendorRequest $request,
        MaintenanceVendor $maintenanceVendor,
    ): RedirectResponse {
        $this->vendors->update(
            $this->actor($request),
            $maintenanceVendor,
            $request->validated(),
        );

        return to_route('maintenance-vendors.show', $maintenanceVendor)
            ->with('success', trans('app.messages.maintenance_vendor_updated'));
    }

    public function destroy(Request $request, MaintenanceVendor $maintenanceVendor): RedirectResponse
    {
        $this->vendors->archive($this->actor($request), $maintenanceVendor);

        return to_route('maintenance-vendors.show', $maintenanceVendor)
            ->with('success', trans('app.messages.maintenance_vendor_archived'));
    }
}
