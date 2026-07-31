<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Maintenance\Support\MaintenanceReferenceGuard;
use App\Modules\Maintenance\Support\MaintenanceSchedule;
use App\Modules\Maintenance\Support\MaintenanceTransitionGuard;
use App\Modules\Notifications\Actions\SendMaintenanceActivityNotification;
use Illuminate\Support\Facades\DB;

class UpdateMaintenance
{
    public function __construct(
        private readonly MaintenanceAccess $access,
        private readonly MaintenanceSchedule $schedule,
        private readonly MaintenanceReferenceGuard $references,
        private readonly MaintenanceTransitionGuard $transitions,
        private readonly SendMaintenanceActivityNotification $notifications,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, MaintenanceRequest $request, array $data): MaintenanceRequest
    {
        $this->access->ensureCanAccess($actor, $request);
        $previousStatus = $request->status;

        $updated = $actor->hasRole('tenant')
            ? $this->addTenantComment($actor, $request, $data)
            : $this->triage($actor, $request, $data);

        $this->notifications->updated(
            $actor,
            $updated,
            $previousStatus,
            (bool) ($data['is_public_comment'] ?? false),
        );

        return $updated;
    }

    /** @param array<string, mixed> $data */
    private function addTenantComment(User $actor, MaintenanceRequest $request, array $data): MaintenanceRequest
    {
        return DB::transaction(function () use ($actor, $request, $data): MaintenanceRequest {
            $locked = MaintenanceRequest::query()->lockForUpdate()->findOrFail($request->id);
            $this->access->ensureCanAccess($actor, $locked);
            $locked->updates()->create([
                'user_id' => $actor->id,
                'status_from' => $locked->status,
                'status_to' => $locked->status,
                'is_public_comment' => true,
                'comment' => $data['comment'],
            ]);

            return $locked->refresh();
        });
    }

    /** @param array<string, mixed> $data */
    private function triage(User $actor, MaintenanceRequest $request, array $data): MaintenanceRequest
    {
        return DB::transaction(function () use ($actor, $request, $data): MaintenanceRequest {
            $locked = MaintenanceRequest::query()->lockForUpdate()->findOrFail($request->id);
            $this->access->ensureCanAccess($actor, $locked);
            $this->references->ensureBelongsToPortfolio(
                $actor,
                $data,
                $locked->portfolio_id,
                $locked->asset_id,
            );
            $previousStatus = $locked->status;
            $previousPriority = $locked->priority;
            $previousAssignee = $locked->assigned_to_user_id;
            $previousResolution = $locked->resolution_summary;
            $this->transitions->ensureAllowed($previousStatus, $data['status']);
            $isResolved = $data['status'] === 'resolved';
            $resolutionChanged = $isResolved
                && $previousResolution !== ($data['resolution_summary'] ?? null);
            $locked->update([
                'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
                'priority' => $data['priority'],
                'status' => $data['status'],
                'internal_notes' => $data['internal_notes'] ?? null,
                'due_at' => $this->schedule->nextDueAt(
                    $locked,
                    $data['priority'],
                    $data['status'],
                    $previousPriority,
                ),
                'resolved_at' => $isResolved ? ($locked->resolved_at ?? now()) : null,
                'resolution_summary' => $isResolved
                    ? $data['resolution_summary']
                    : $locked->resolution_summary,
                'resolved_by_user_id' => $isResolved
                    ? ($previousStatus !== 'resolved' || $resolutionChanged
                        ? $actor->id
                        : ($locked->resolved_by_user_id ?? $actor->id))
                    : $locked->resolved_by_user_id,
                'tenant_confirmed_at' => $previousStatus !== $data['status'] || $resolutionChanged
                    ? null
                    : $locked->tenant_confirmed_at,
                'tenant_confirmation_note' => $previousStatus !== $data['status'] || $resolutionChanged
                    ? null
                    : $locked->tenant_confirmation_note,
            ]);

            if ($this->shouldRecordUpdate(
                $locked,
                $data,
                $previousStatus,
                $previousPriority,
                $previousAssignee,
                $previousResolution,
            )) {
                $locked->updates()->create([
                    'user_id' => $actor->id,
                    'status_from' => $previousStatus,
                    'status_to' => $locked->status,
                    'is_public_comment' => $isResolved
                        || (bool) ($data['is_public_comment'] ?? false),
                    'comment' => $data['comment']
                        ?? ($isResolved
                            ? $data['resolution_summary']
                            : trans('app.maintenance.request_updated')),
                ]);
            }

            return $locked->refresh();
        });
    }

    /** @param array<string, mixed> $data */
    private function shouldRecordUpdate(
        MaintenanceRequest $request,
        array $data,
        string $previousStatus,
        string $previousPriority,
        ?int $previousAssignee,
        ?string $previousResolution,
    ): bool {
        return ! empty($data['comment'])
            || $previousStatus !== $request->status
            || $previousPriority !== $request->priority
            || (int) $previousAssignee !== (int) $request->assigned_to_user_id
            || $previousResolution !== $request->resolution_summary;
    }
}
