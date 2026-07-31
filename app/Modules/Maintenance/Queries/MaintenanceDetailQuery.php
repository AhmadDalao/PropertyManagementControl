<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Maintenance\Data\MaintenanceDetailData;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Portfolios\Support\PortfolioModules;

class MaintenanceDetailQuery
{
    public function __construct(private readonly MaintenanceAccess $access) {}

    public function get(MaintenanceRequest $request, User $actor): MaintenanceDetailData
    {
        $this->access->ensureCanAccess($actor, $request);
        $tenantMode = $actor->hasRole('tenant');
        $expensesEnabled = ! $tenantMode
            && PortfolioModules::enabledForUser($actor, 'expenses');

        $request->loadMissing([
            'portfolio',
            'asset',
            'lease',
            'tenantProfile.user',
            'submittedBy',
            'assignedTo',
            'resolvedBy',
        ]);

        $updates = $request->updates()
            ->with('user:id,name')
            ->when($tenantMode, fn ($query) => $query->where('is_public_comment', true))
            ->latest()
            ->get();
        $expenses = $expensesEnabled
            ? $request->expenses()->latest('incurred_on')->get()
            : collect();
        $attachments = $request->attachments()
            ->with('uploadedBy:id,name')
            ->latest()
            ->get();
        $workOrders = $request->workOrders()
            ->when(
                $tenantMode,
                fn ($query) => $query->where('status', '!=', 'draft'),
            )
            ->with([
                'vendor:id,name',
                'assignedTo:id,name',
            ])
            ->latest()
            ->get();

        return new MaintenanceDetailData(
            request: $request,
            actor: $actor,
            tenantMode: $tenantMode,
            updates: $updates,
            expenses: $expenses,
            attachments: $attachments,
            workOrders: $workOrders,
            postedExpenseTotal: $expensesEnabled
                ? (float) $expenses->where('status', 'posted')->sum('amount')
                : 0,
        );
    }
}
