<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceRequest;
use App\Modules\Maintenance\Actions\AddMaintenanceAttachments;
use App\Modules\Maintenance\Presenters\MaintenanceAttachmentFormPresenter;
use App\Modules\Maintenance\Requests\StoreMaintenanceAttachmentsRequest;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Maintenance\Support\MaintenanceAttachmentStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaintenanceAttachmentController extends Controller
{
    public function __construct(
        private readonly MaintenanceAccess $access,
        private readonly MaintenanceAttachmentFormPresenter $forms,
        private readonly AddMaintenanceAttachments $attachments,
        private readonly MaintenanceAttachmentStorage $storage,
    ) {}

    public function create(Request $request, MaintenanceRequest $maintenanceRequest): Response
    {
        $this->access->ensureCanAccess($this->actor($request), $maintenanceRequest);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->forms->present($maintenanceRequest),
        ]);
    }

    public function store(
        StoreMaintenanceAttachmentsRequest $request,
        MaintenanceRequest $maintenanceRequest,
    ): RedirectResponse {
        $actor = $this->actor($request);
        $this->access->ensureCanAccess($actor, $maintenanceRequest);
        $this->attachments->handle($actor, $maintenanceRequest, $request->file('photos', []));

        return to_route('maintenance-requests.show', [$maintenanceRequest, 'tab' => 'documents'])
            ->with('success', trans('app.messages.maintenance_photos_uploaded'));
    }

    public function show(
        Request $request,
        MaintenanceRequest $maintenanceRequest,
        MaintenanceAttachment $maintenanceAttachment,
    ): StreamedResponse {
        $this->access->ensureCanAccess($this->actor($request), $maintenanceRequest);
        abort_unless($maintenanceAttachment->maintenance_request_id === $maintenanceRequest->id, 404);

        return $this->storage->response($maintenanceAttachment);
    }
}
