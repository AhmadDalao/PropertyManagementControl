<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Maintenance\Data\MaintenanceDetailData;
use App\Modules\Maintenance\Support\MaintenanceAccess;

class MaintenanceDetailQuery
{
    public function __construct(private readonly MaintenanceAccess $access) {}

    public function get(MaintenanceRequest $request, User $actor): MaintenanceDetailData
    {
        $this->access->ensureCanAccess($actor, $request);
        $tenantMode = $actor->hasRole('tenant');

        $request->loadMissing([
            'portfolio',
            'asset',
            'lease',
            'tenantProfile.user',
            'submittedBy',
            'assignedTo',
        ]);

        $updates = $request->updates()
            ->with('user:id,name')
            ->when($tenantMode, fn ($query) => $query->where('is_public_comment', true))
            ->latest()
            ->get();
        $expenses = $tenantMode
            ? collect()
            : $request->expenses()->latest('incurred_on')->get();

        return new MaintenanceDetailData(
            request: $request,
            actor: $actor,
            tenantMode: $tenantMode,
            updates: $updates,
            expenses: $expenses,
            postedExpenseTotal: $tenantMode
                ? 0
                : (float) $expenses->where('status', 'posted')->sum('amount'),
        );
    }
}
