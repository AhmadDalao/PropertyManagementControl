<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceAttachment;
use Illuminate\Support\Collection;

final class MaintenanceAttachmentPresenter
{
    /**
     * @param  Collection<int, MaintenanceAttachment>  $attachments
     * @return array<int, array<string, mixed>>
     */
    public function present(Collection $attachments): array
    {
        return $attachments->map(function (MaintenanceAttachment $attachment): array {
            $href = route('maintenance-requests.attachments.show', [
                $attachment->maintenance_request_id,
                $attachment,
            ]);

            return [
                'id' => $attachment->id,
                'title' => $attachment->original_name,
                'subtitle' => trans('app.maintenance.photo_metadata', [
                    'name' => $attachment->uploaded_by_user_id !== null
                        ? $attachment->uploadedBy->name
                        : trans('app.maintenance.system'),
                    'date' => $attachment->created_at?->toDateTimeString() ?? '-',
                ]),
                'badge' => trans('app.maintenance.photo'),
                'href' => $href,
                'thumbnail' => $href,
            ];
        })->values()->all();
    }
}
