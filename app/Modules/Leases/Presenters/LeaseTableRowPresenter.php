<?php

namespace App\Modules\Leases\Presenters;

use App\Models\Asset;
use App\Models\Lease;

final class LeaseTableRowPresenter
{
    /** @return array<string, mixed> */
    public function present(Lease $lease): array
    {
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;
        $totalDue = (float) ($lease->getAttribute('installments_total_due') ?? 0);
        $totalPaid = (float) ($lease->getAttribute('installments_total_paid') ?? 0);

        return [
            'id' => $lease->id,
            'code' => $lease->code,
            'status' => $lease->status,
            'payment_frequency' => $lease->payment_frequency,
            'started_at' => $lease->started_at?->toDateString(),
            'ends_at' => $lease->ends_at?->toDateString(),
            'signed_at' => $lease->signed_at?->toDateString(),
            'rent_amount' => (float) $lease->rent_amount,
            'deposit_amount' => (float) $lease->deposit_amount,
            'tax_amount' => (float) $lease->tax_amount,
            'discount_amount' => (float) $lease->discount_amount,
            'currency' => $lease->currency,
            'billing_day' => $lease->billing_day,
            'tenant_profile' => $lease->tenantProfile ? [
                'user' => ['name' => $lease->tenantProfile->user?->name],
            ] : null,
            'leaseable' => $asset ? [
                'title_en' => $asset->title_en,
                'title_ar' => $asset->title_ar,
                'code' => $asset->code,
            ] : null,
            'total_due' => $totalDue,
            'total_paid' => $totalPaid,
            'balance_remaining' => max(0, $totalDue - $totalPaid),
            'days_remaining' => $lease->days_remaining,
            'installment_count' => (int) ($lease->getAttribute('installments_count') ?? 0),
            'overdue_count' => (int) ($lease->getAttribute('overdue_installments_count') ?? 0),
            'open_installment_count' => (int) ($lease->getAttribute('open_installments_count') ?? 0),
            'next_due_date' => $lease->getAttribute('next_due_date'),
            'next_due_amount' => $lease->getAttribute('next_due_amount') !== null
                ? (float) $lease->getAttribute('next_due_amount')
                : null,
            'paid_percent' => $totalDue > 0 ? round(min(100, ($totalPaid / $totalDue) * 100), 1) : 0,
        ];
    }
}
