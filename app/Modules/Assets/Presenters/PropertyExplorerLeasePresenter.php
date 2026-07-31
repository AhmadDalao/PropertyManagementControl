<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Lease;
use App\Models\LeaseInstallment;

final class PropertyExplorerLeasePresenter
{
    /** @return array<string, mixed>|null */
    public function present(?Lease $lease): ?array
    {
        if (! $lease) {
            return null;
        }

        $lease->loadMissing(['tenantProfile.user', 'installments']);

        return [
            'id' => $lease->id,
            'code' => $lease->code,
            'status' => $lease->status,
            'tenant_id' => $lease->tenant_profile_id,
            'tenant_name' => $lease->tenantProfile?->user?->name,
            'tenant_email' => $lease->tenantProfile?->user?->email,
            'tenant_phone' => $lease->tenantProfile?->user?->phone,
            'started_at' => $lease->started_at?->toDateString(),
            'ends_at' => $lease->ends_at?->toDateString(),
            'days_remaining' => $lease->days_remaining,
            'total_due' => $lease->total_due,
            'total_paid' => $lease->total_paid,
            'balance_remaining' => $lease->balance_remaining,
            'arrears' => (float) $lease->installments
                ->filter(fn (LeaseInstallment $installment): bool => $installment->due_date?->isPast() ?? false)
                ->sum(fn (LeaseInstallment $installment): float => $installment->remaining_amount),
            'currency' => $lease->currency,
            'href' => route('leases.show', $lease),
            'tenant_href' => $lease->tenant_profile_id
                ? route('tenants.show', $lease->tenant_profile_id)
                : null,
            'payments_href' => route('payments.index', ['search' => $lease->code]),
        ];
    }
}
