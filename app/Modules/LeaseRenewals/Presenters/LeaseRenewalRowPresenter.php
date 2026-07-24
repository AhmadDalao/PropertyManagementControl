<?php

namespace App\Modules\LeaseRenewals\Presenters;

use App\Models\Asset;
use App\Models\Lease;
use Carbon\CarbonInterface;

final class LeaseRenewalRowPresenter
{
    /** @return array<string, mixed> */
    public function present(Lease $lease, ?Asset $property): array
    {
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;
        $daysRemaining = $lease->ends_at
            ? (int) today()->diffInDays($lease->ends_at, false)
            : null;
        $contactDueOn = $lease->ends_at?->copy()->subDays(
            (int) $lease->renewal_notice_days,
        );
        $totalDue = (float) ($lease->getAttribute('installments_total_due') ?? 0);
        $totalPaid = (float) ($lease->getAttribute('installments_total_paid') ?? 0);

        return [
            'id' => $lease->id,
            'code' => $lease->code,
            'status' => $lease->status,
            'payment_frequency' => $lease->payment_frequency,
            'started_at' => $lease->started_at?->toDateString(),
            'ends_at' => $lease->ends_at?->toDateString(),
            'days_remaining' => $daysRemaining,
            'renewal_notice_days' => (int) $lease->renewal_notice_days,
            'contact_due_on' => $contactDueOn?->toDateString(),
            'notice_due' => $contactDueOn?->lessThanOrEqualTo(today()) ?? false,
            'renewal_state' => $this->state($lease, $contactDueOn),
            'rent_amount' => (float) $lease->rent_amount,
            'outstanding_amount' => max(0, $totalDue - $totalPaid),
            'overdue_installments_count' => (int) ($lease->getAttribute('overdue_installments_count') ?? 0),
            'currency' => $lease->currency ?: 'SAR',
            'is_showcase' => $lease->getIsShowcaseAttribute(),
            'tenant' => $lease->tenantProfile?->user ? [
                'id' => $lease->tenantProfile->id,
                'name' => $lease->tenantProfile->user->name,
                'email' => $lease->tenantProfile->user->email,
                'phone' => $lease->tenantProfile->user->phone,
            ] : null,
            'asset' => $asset ? [
                'id' => $asset->id,
                'title_en' => $asset->title_en,
                'title_ar' => $asset->title_ar,
                'code' => $asset->code,
            ] : null,
            'property' => $property ? [
                'id' => $property->id,
                'title_en' => $property->title_en,
                'title_ar' => $property->title_ar,
                'code' => $property->code,
            ] : null,
            'renewal' => $lease->renewalLease ? [
                'id' => $lease->renewalLease->id,
                'code' => $lease->renewalLease->code,
                'status' => $lease->renewalLease->status,
                'started_at' => $lease->renewalLease->started_at?->toDateString(),
                'ends_at' => $lease->renewalLease->ends_at?->toDateString(),
            ] : null,
        ];
    }

    private function state(Lease $lease, ?CarbonInterface $contactDueOn): string
    {
        if ($lease->renewalLease) {
            return 'prepared';
        }

        if ($lease->status === 'expired') {
            return 'expired';
        }

        return $contactDueOn?->lessThanOrEqualTo(today()) ? 'attention' : 'upcoming';
    }
}
