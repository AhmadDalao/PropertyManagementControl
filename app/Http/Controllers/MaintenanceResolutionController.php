<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Modules\Maintenance\Actions\RespondToMaintenanceResolution;
use App\Modules\Maintenance\Presenters\MaintenanceResolutionFormPresenter;
use App\Modules\Maintenance\Requests\RespondToMaintenanceResolutionRequest;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceResolutionController extends Controller
{
    public function __construct(
        private readonly MaintenanceAccess $access,
        private readonly MaintenanceResolutionFormPresenter $forms,
        private readonly RespondToMaintenanceResolution $responses,
    ) {}

    public function create(
        Request $request,
        MaintenanceRequest $maintenanceRequest,
    ): Response {
        $actor = $this->actor($request);
        $this->access->ensureCanAccess($actor, $maintenanceRequest);
        abort_unless($actor->hasRole('tenant'), 403);
        abort_unless(
            $maintenanceRequest->status === 'resolved'
                && ! $maintenanceRequest->tenant_confirmed_at,
            409,
            trans('app.errors.maintenance_resolution_response_unavailable'),
        );

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->forms->present($maintenanceRequest),
        ]);
    }

    public function store(
        RespondToMaintenanceResolutionRequest $request,
        MaintenanceRequest $maintenanceRequest,
    ): RedirectResponse {
        $updated = $this->responses->handle(
            $this->actor($request),
            $maintenanceRequest,
            [
                'outcome' => $request->string('outcome')->toString(),
                'note' => $request->filled('note')
                    ? $request->string('note')->toString()
                    : null,
            ],
        );

        return to_route('maintenance-requests.show', $updated)->with(
            'success',
            trans($updated->status === 'resolved'
                ? 'app.messages.maintenance_resolution_confirmed'
                : 'app.messages.maintenance_reopened_by_tenant'),
        );
    }
}
