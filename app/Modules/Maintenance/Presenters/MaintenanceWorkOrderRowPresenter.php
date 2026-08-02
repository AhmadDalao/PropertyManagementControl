<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceWorkOrder;

final class MaintenanceWorkOrderRowPresenter
{
    /** @return array<string, mixed> */
    public function present(MaintenanceWorkOrder $workOrder): array
    {
        $request = $workOrder->maintenanceRequest;
        $asset = $request?->asset;

        return [
            'id' => $workOrder->id,
            'reference_code' => $workOrder->reference_code,
            'status' => $workOrder->status,
            'scheduled_at' => $workOrder->scheduled_at?->toIso8601String(),
            'completed_at' => $workOrder->completed_at?->toIso8601String(),
            'estimated_amount' => $workOrder->estimated_amount,
            'final_amount' => $workOrder->final_amount,
            'currency' => $workOrder->currency,
            'scope' => $workOrder->scope,
            'tenant_access_required' => $workOrder->tenant_access_required,
            'is_overdue' => in_array($workOrder->status, ['scheduled', 'in_progress'], true)
                && $workOrder->scheduled_at?->isPast() === true,
            'request' => $request ? [
                'id' => $request->id,
                'title' => $request->title,
                'category' => $request->category,
                'priority' => $request->priority,
                'status' => $request->status,
            ] : null,
            'asset' => $asset ? [
                'id' => $asset->id,
                'title_en' => $asset->title_en,
                'title_ar' => $asset->title_ar,
                'code' => $asset->code,
            ] : null,
            'tenant' => $request?->tenantProfile?->user ? [
                'name' => $request->tenantProfile->user->name,
            ] : null,
            'vendor' => [
                'id' => $workOrder->vendor?->id,
                'name' => $workOrder->vendor_name,
            ],
            'assigned_to' => $workOrder->assignedTo ? [
                'id' => $workOrder->assignedTo->id,
                'name' => $workOrder->assignedTo->name,
            ] : null,
        ];
    }
}
