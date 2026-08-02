<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceWorkOrder;
use App\Modules\Maintenance\Actions\ManageMaintenanceWorkOrders;
use App\Modules\Maintenance\Presenters\MaintenanceWorkOrderDetailPresenter;
use App\Modules\Maintenance\Presenters\MaintenanceWorkOrderFormPresenter;
use App\Modules\Maintenance\Queries\MaintenanceWorkOrderIndexQuery;
use App\Modules\Maintenance\Requests\StoreMaintenanceWorkOrderRequest;
use App\Modules\Maintenance\Requests\UpdateMaintenanceWorkOrderRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MaintenanceWorkOrderController extends Controller
{
    public function __construct(
        private readonly MaintenanceWorkOrderIndexQuery $index,
        private readonly MaintenanceWorkOrderFormPresenter $forms,
        private readonly MaintenanceWorkOrderDetailPresenter $details,
        private readonly ManageMaintenanceWorkOrders $workOrders,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/maintenance-work-orders/index',
            $this->index->handle($request, $this->actor($request)),
        );
    }

    public function create(Request $request, MaintenanceRequest $maintenanceRequest): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->forms->forCreate(
                $maintenanceRequest,
                $this->actor($request),
            ),
        ]);
    }

    public function store(
        StoreMaintenanceWorkOrderRequest $request,
        MaintenanceRequest $maintenanceRequest,
    ): RedirectResponse {
        $workOrder = $this->workOrders->create(
            $this->actor($request),
            $maintenanceRequest,
            $request->validated(),
        );

        return to_route('maintenance-work-orders.show', $workOrder)
            ->with('success', trans('app.messages.work_order_created'));
    }

    public function show(
        Request $request,
        MaintenanceWorkOrder $maintenanceWorkOrder,
    ): Response {
        return Inertia::render('admin/resource-show', [
            'detailPage' => $this->details->present(
                $maintenanceWorkOrder,
                $this->actor($request),
            ),
        ]);
    }

    public function edit(
        Request $request,
        MaintenanceWorkOrder $maintenanceWorkOrder,
    ): Response {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->forms->forEdit(
                $maintenanceWorkOrder,
                $this->actor($request),
            ),
        ]);
    }

    public function update(
        UpdateMaintenanceWorkOrderRequest $request,
        MaintenanceWorkOrder $maintenanceWorkOrder,
    ): RedirectResponse {
        $this->workOrders->update(
            $this->actor($request),
            $maintenanceWorkOrder,
            $request->validated(),
        );

        return to_route('maintenance-work-orders.show', $maintenanceWorkOrder)
            ->with('success', trans('app.messages.work_order_updated'));
    }
}
