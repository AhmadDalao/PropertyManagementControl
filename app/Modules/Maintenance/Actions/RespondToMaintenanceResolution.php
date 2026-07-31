<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Maintenance\Support\MaintenanceSchedule;
use App\Modules\Notifications\Actions\SendMaintenanceActivityNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RespondToMaintenanceResolution
{
    public function __construct(
        private MaintenanceAccess $access,
        private MaintenanceSchedule $schedule,
        private SendMaintenanceActivityNotification $notifications,
    ) {}

    /** @param array{outcome:string,note?:string|null} $data */
    public function handle(
        User $actor,
        MaintenanceRequest $request,
        array $data,
    ): MaintenanceRequest {
        $this->access->ensureCanAccess($actor, $request);
        abort_unless($actor->hasRole('tenant'), 403);

        $updated = DB::transaction(function () use ($actor, $request, $data): MaintenanceRequest {
            $locked = MaintenanceRequest::query()->lockForUpdate()->findOrFail($request->id);
            $this->access->ensureCanAccess($actor, $locked);

            if ($locked->status !== 'resolved' || $locked->tenant_confirmed_at) {
                throw ValidationException::withMessages([
                    'outcome' => trans('app.errors.maintenance_resolution_response_unavailable'),
                ]);
            }

            $note = filled($data['note'] ?? null) ? trim((string) $data['note']) : null;

            if ($data['outcome'] === 'confirmed') {
                $locked->update([
                    'tenant_confirmed_at' => now(),
                    'tenant_confirmation_note' => $note,
                ]);
                $comment = $note ?: trans('app.maintenance.tenant_confirmed_resolution_update');
            } else {
                $locked->update([
                    'status' => 'open',
                    'due_at' => $this->schedule->dueAtForPriority($locked->priority),
                    'resolved_at' => null,
                    'tenant_confirmed_at' => null,
                    'tenant_confirmation_note' => $note,
                ]);
                $comment = trans('app.maintenance.tenant_reopened_resolution_update', [
                    'note' => $note,
                ]);
            }

            $locked->updates()->create([
                'user_id' => $actor->id,
                'status_from' => 'resolved',
                'status_to' => $locked->status,
                'is_public_comment' => true,
                'comment' => $comment,
            ]);

            return $locked->refresh();
        });

        $this->notifications->resolutionResponse(
            $actor,
            $updated,
            $data['outcome'],
        );

        return $updated;
    }
}
