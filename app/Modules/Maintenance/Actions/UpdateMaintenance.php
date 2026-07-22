<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Maintenance\Support\MaintenanceReferenceGuard;
use App\Modules\Maintenance\Support\MaintenanceSchedule;
use Illuminate\Support\Facades\DB;

class UpdateMaintenance
{
    public function __construct(
        private readonly MaintenanceAccess $access,
        private readonly MaintenanceSchedule $schedule,
        private readonly MaintenanceReferenceGuard $references,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, MaintenanceRequest $request, array $data): MaintenanceRequest
    {
        $this->access->ensureCanAccess($actor, $request);

        return $actor->hasRole('tenant')
            ? $this->addTenantComment($actor, $request, $data)
            : $this->triage($actor, $request, $data);
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
            $this->references->ensureBelongsToPortfolio($data, $locked->portfolio_id);
            $previousStatus = $locked->status;
            $previousPriority = $locked->priority;
            $previousAssignee = $locked->assigned_to_user_id;
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
                'resolved_at' => $data['status'] === 'resolved' ? now() : null,
            ]);

            if ($this->shouldRecordUpdate($locked, $data, $previousStatus, $previousPriority, $previousAssignee)) {
                $locked->updates()->create([
                    'user_id' => $actor->id,
                    'status_from' => $previousStatus,
                    'status_to' => $locked->status,
                    'is_public_comment' => (bool) ($data['is_public_comment'] ?? false),
                    'comment' => $data['comment'] ?? trans('app.maintenance.request_updated'),
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
    ): bool {
        return ! empty($data['comment'])
            || $previousStatus !== $request->status
            || $previousPriority !== $request->priority
            || (int) $previousAssignee !== (int) $request->assigned_to_user_id;
    }
}
