<?php

namespace App\Modules\LeaseMoveOuts\Presenters;

use App\Models\Asset;
use App\Models\LeaseMoveOut;

final class LeaseMoveOutRowPresenter
{
    /** @return array<string, mixed> */
    public function present(LeaseMoveOut $moveOut, ?Asset $property): array
    {
        $lease = $moveOut->lease;
        $asset = $lease?->leaseable instanceof Asset ? $lease->leaseable : null;
        $documents = $lease?->documents;
        $notice = $documents?->contains('type', 'termination_notice') ?? false;
        $inspection = $documents?->contains('type', 'move_out_inspection') ?? false;
        $due = (float) ($lease?->getAttribute('move_out_total_due') ?? 0);
        $paid = (float) ($lease?->getAttribute('move_out_total_paid') ?? 0);
        $ready = $moveOut->status === 'planned'
            && $moveOut->move_out_date?->lessThanOrEqualTo(today())
            && $moveOut->keys_returned
            && $moveOut->deposit_disposition !== 'pending'
            && $notice
            && $inspection;

        return [
            'id' => $moveOut->id,
            'lease_id' => $moveOut->lease_id,
            'code' => $lease?->code,
            'lease_status' => $lease?->status,
            'status' => $moveOut->status,
            'state' => $this->state($moveOut, $ready),
            'move_out_date' => $moveOut->move_out_date?->toDateString(),
            'reason' => $moveOut->reason,
            'deposit_disposition' => $moveOut->deposit_disposition,
            'deposit_deduction_amount' => (float) $moveOut->deposit_deduction_amount,
            'keys_returned' => (bool) $moveOut->keys_returned,
            'notice_uploaded' => $notice,
            'inspection_uploaded' => $inspection,
            'ready' => $ready,
            'outstanding_amount' => max(0, $due - $paid),
            'currency' => $lease?->currency ?: 'SAR',
            'is_showcase' => $lease?->getIsShowcaseAttribute() ?? false,
            'tenant' => $lease?->tenantProfile?->user ? [
                'id' => $lease->tenantProfile->id,
                'name' => $lease->tenantProfile->user->name,
                'email' => $lease->tenantProfile->user->email,
            ] : null,
            'asset' => $asset ? $this->asset($asset) : null,
            'property' => $property ? $this->asset($property) : null,
        ];
    }

    private function state(LeaseMoveOut $moveOut, bool $ready): string
    {
        if (in_array($moveOut->status, ['completed', 'cancelled'], true)) {
            return $moveOut->status;
        }

        if ($ready) {
            return 'ready';
        }

        if ($moveOut->move_out_date?->isToday()) {
            return 'due_today';
        }

        if ($moveOut->move_out_date?->isPast()) {
            return 'overdue';
        }

        return 'scheduled';
    }

    /** @return array{id:int,title_en:string,title_ar:string,code:string} */
    private function asset(Asset $asset): array
    {
        return [
            'id' => $asset->id,
            'title_en' => $asset->title_en,
            'title_ar' => $asset->title_ar,
            'code' => $asset->code,
        ];
    }
}
