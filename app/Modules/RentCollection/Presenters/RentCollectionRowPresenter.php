<?php

namespace App\Modules\RentCollection\Presenters;

use App\Models\Asset;
use App\Models\LeaseInstallment;
use App\Modules\Leases\Presenters\LeaseInstallmentLabelPresenter;

final readonly class RentCollectionRowPresenter
{
    public function __construct(
        private LeaseInstallmentLabelPresenter $labels,
        private CollectionFollowUpPresenter $followUps,
    ) {}

    /** @return array<string, mixed> */
    public function present(LeaseInstallment $installment, ?Asset $property): array
    {
        $lease = $installment->lease;
        $asset = $lease?->leaseable instanceof Asset ? $lease->leaseable : null;
        $status = $this->status($installment);
        $days = $installment->due_date
            ? (int) today()->diffInDays($installment->due_date, false)
            : null;

        return [
            'id' => $installment->id,
            'sequence' => $installment->sequence,
            'line_type' => $installment->line_type,
            'label' => $this->labels->present($installment),
            'period_start' => $installment->period_start?->toDateString(),
            'period_end' => $installment->period_end?->toDateString(),
            'due_date' => $installment->due_date?->toDateString(),
            'amount_due' => (float) $installment->amount_due,
            'amount_paid' => (float) $installment->amount_paid,
            'outstanding_amount' => $installment->remaining_amount,
            'status' => $status,
            'days_overdue' => $days !== null && $days < 0 ? abs($days) : 0,
            'days_until_due' => $days !== null && $days > 0 ? $days : 0,
            'currency' => $lease?->currency ?: 'SAR',
            'is_showcase' => $lease?->getIsShowcaseAttribute() ?? false,
            'follow_up' => $this->followUps->latest($installment),
            'lease' => $lease ? [
                'id' => $lease->id,
                'code' => $lease->code,
                'status' => $lease->status,
            ] : null,
            'tenant' => $lease?->tenantProfile?->user ? [
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
        ];
    }

    public function status(LeaseInstallment $installment): string
    {
        if ($installment->remaining_amount <= 0) {
            return 'paid';
        }

        if ($installment->due_date?->isBefore(today())) {
            return 'overdue';
        }

        if ((float) $installment->amount_paid > 0) {
            return 'partial';
        }

        return 'pending';
    }
}
