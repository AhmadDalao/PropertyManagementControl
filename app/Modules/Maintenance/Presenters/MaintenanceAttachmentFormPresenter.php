<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceRequest;

final class MaintenanceAttachmentFormPresenter
{
    /** @return array<string, mixed> */
    public function present(MaintenanceRequest $request): array
    {
        return [
            'title' => trans('app.maintenance.add_photos'),
            'description' => trans('app.maintenance.add_photos_help', ['id' => $request->id]),
            'backHref' => route('maintenance-requests.show', [$request, 'tab' => 'documents']),
            'backLabel' => trans('app.maintenance.request_detail'),
            'action' => route('maintenance-requests.attachments.store', $request),
            'method' => 'post',
            'submitLabel' => trans('app.maintenance.upload_photos'),
            'fields' => [[
                'name' => 'photos',
                'label' => trans('app.maintenance.photos'),
                'type' => 'file',
                'required' => true,
                'multiple' => true,
                'accept' => '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp',
                'help' => trans('app.maintenance.photo_upload_help'),
            ]],
            'initialValues' => ['photos' => []],
        ];
    }
}
