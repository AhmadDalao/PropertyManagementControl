<?php

namespace App\Modules\Maintenance\Presenters;

use App\Modules\Maintenance\Data\MaintenanceDetailData;

final class MaintenanceProgressPresenter
{
    /** @return array<string, mixed> */
    public function present(MaintenanceDetailData $data): array
    {
        $request = $data->request;
        $hasAssignment = $request->assigned_to_user_id !== null
            || $data->workOrders->isNotEmpty()
            || in_array($request->status, ['in_progress', 'resolved'], true);
        $hasService = $data->workOrders->where('status', 'completed')->isNotEmpty()
            || $request->status === 'resolved';
        $isResolved = $request->status === 'resolved';
        $isConfirmed = $request->tenant_confirmed_at !== null;
        $states = [true, $hasAssignment, $hasService, $isResolved, $isConfirmed];
        $completed = collect($states)->filter()->count();

        return [
            'eyebrow' => trans('app.maintenance.lifecycle'),
            'title' => trans('app.maintenance.lifecycle_title'),
            'description' => trans('app.maintenance.lifecycle_help'),
            'summary' => trans('app.maintenance.lifecycle_summary', [
                'completed' => $completed,
                'total' => count($states),
            ]),
            'completed' => $completed,
            'total' => count($states),
            'steps' => [
                $this->step(
                    'submitted',
                    true,
                    false,
                    $request->requested_at?->toDateTimeString(),
                ),
                $this->step(
                    'assigned',
                    $hasAssignment,
                    ! $hasAssignment && $request->status === 'open',
                    $request->assignedTo->name
                        ?? ($hasAssignment
                            ? $this->label('app.maintenance.lifecycle_assigned_complete')
                            : null),
                ),
                $this->step(
                    'service',
                    $hasService,
                    ! $hasService
                        && $hasAssignment
                        && ! in_array($request->status, ['cancelled', 'resolved'], true),
                    $data->workOrders->first()->reference_code
                        ?? ($hasService
                            ? $this->label('app.maintenance.lifecycle_service_complete')
                            : null),
                ),
                $this->step(
                    'resolved',
                    $isResolved,
                    ! $isResolved
                        && $hasService
                        && $request->status !== 'cancelled',
                    $request->resolved_at?->toDateTimeString(),
                    $isResolved
                        ? route('maintenance-requests.service-report', $request)
                        : null,
                    $isResolved
                        ? $this->label('app.maintenance.download_service_report')
                        : null,
                    true,
                ),
                $this->step(
                    'confirmed',
                    $isConfirmed,
                    $isResolved && ! $isConfirmed,
                    $request->tenant_confirmed_at?->toDateTimeString(),
                    $data->tenantMode && $isResolved && ! $isConfirmed
                        ? route('maintenance-requests.resolution-response.create', $request)
                        : null,
                    $data->tenantMode && $isResolved && ! $isConfirmed
                        ? $this->label('app.maintenance.review_resolution')
                        : null,
                ),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function step(
        string $key,
        bool $complete,
        bool $current,
        ?string $detail = null,
        ?string $href = null,
        ?string $actionLabel = null,
        bool $download = false,
    ): array {
        return [
            'title' => trans("app.maintenance.lifecycle_{$key}"),
            'description' => $detail
                ?: trans("app.maintenance.lifecycle_{$key}_help"),
            'state' => $complete ? 'complete' : ($current ? 'current' : 'pending'),
            'icon' => match ($key) {
                'submitted' => 'bi-send',
                'assigned' => 'bi-person-check',
                'service' => 'bi-tools',
                'resolved' => 'bi-check2-circle',
                default => 'bi-clipboard-check',
            },
            'href' => $href,
            'actionLabel' => $actionLabel,
            'download' => $download,
        ];
    }

    private function label(string $key): string
    {
        $translated = trans($key);

        return is_string($translated) ? $translated : $key;
    }
}
