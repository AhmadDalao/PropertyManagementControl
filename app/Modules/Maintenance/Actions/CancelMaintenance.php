<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use Illuminate\Support\Facades\DB;

class CancelMaintenance
{
    public function __construct(private readonly MaintenanceAccess $access) {}

    public function handle(User $actor, MaintenanceRequest $request): bool
    {
        $this->access->ensureCanAccess($actor, $request);

        return DB::transaction(function () use ($actor, $request): bool {
            $locked = MaintenanceRequest::query()->lockForUpdate()->findOrFail($request->id);

            $this->access->ensureCanAccess($actor, $locked);

            if (! in_array($locked->status, ['open', 'in_progress'], true)) {
                return false;
            }

            $previousStatus = $locked->status;
            $locked->update(['status' => 'cancelled']);
            $locked->updates()->create([
                'user_id' => $actor->id,
                'status_from' => $previousStatus,
                'status_to' => 'cancelled',
                'is_public_comment' => $actor->hasRole('tenant'),
                'comment' => trans($actor->hasRole('tenant')
                    ? 'app.maintenance.cancelled_by_tenant'
                    : 'app.maintenance.cancelled_by_management'),
            ]);

            return true;
        });
    }
}
