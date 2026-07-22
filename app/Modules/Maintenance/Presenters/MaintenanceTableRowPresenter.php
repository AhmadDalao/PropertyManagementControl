<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceRequest;

class MaintenanceTableRowPresenter
{
    /** @return array<string, mixed> */
    public function present(MaintenanceRequest $request, bool $includeFinancials): array
    {
        $request->loadMissing(['asset', 'tenantProfile.user', 'assignedTo']);

        return [
            'id' => $request->id,
            'title' => $request->title,
            'status' => $request->status,
            'category' => $request->category,
            'priority' => $request->priority,
            'created_at' => $request->created_at?->toIso8601String(),
            'due_at' => $request->due_at?->toIso8601String(),
            'is_overdue' => $request->due_at
                ? $request->due_at->isPast() && ! in_array($request->status, ['resolved', 'cancelled'], true)
                : false,
            'assigned_to' => $request->assignedTo ? [
                'id' => $request->assignedTo->id,
                'name' => $request->assignedTo->name,
            ] : null,
            'asset' => $request->asset ? [
                'id' => $request->asset->id,
                'title_en' => $request->asset->title_en,
                'title_ar' => $request->asset->title_ar,
                'code' => $request->asset->code,
            ] : null,
            'tenant_profile' => [
                'id' => $request->tenantProfile?->id,
                'user' => ['name' => $request->tenantProfile?->user?->name],
            ],
            'expense_total' => $includeFinancials
                ? (float) ($request->getAttribute('posted_expense_total') ?? 0)
                : 0,
            'expense_count' => $includeFinancials
                ? (int) ($request->getAttribute('expenses_count') ?? 0)
                : 0,
        ];
    }
}
