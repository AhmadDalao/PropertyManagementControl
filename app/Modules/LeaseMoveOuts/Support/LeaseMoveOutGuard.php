<?php

namespace App\Modules\LeaseMoveOuts\Support;

use App\Models\Lease;
use App\Models\LeaseMoveOut;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class LeaseMoveOutGuard
{
    public function ensurePlannable(Lease $lease): void
    {
        if (in_array($lease->status, ['active', 'expired'], true)) {
            return;
        }

        throw ValidationException::withMessages([
            'lease' => trans('app.errors.move_out_lease_not_plannable'),
        ]);
    }

    /** @param array<string, mixed> $data */
    public function validatePlan(Lease $lease, array $data): void
    {
        $errors = $this->planErrors($lease, $data);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function ensureCompletable(Lease $lease, LeaseMoveOut $moveOut): void
    {
        $this->ensurePlannable($lease);
        $errors = $this->planErrors($lease, [
            'move_out_date' => $moveOut->move_out_date?->toDateString(),
            'deposit_disposition' => $moveOut->deposit_disposition,
            'deposit_deduction_amount' => $moveOut->deposit_deduction_amount,
        ]);

        if ($moveOut->status !== 'planned') {
            $errors['move_out'] = trans('app.errors.move_out_not_planned');
        }

        if ($moveOut->move_out_date?->isFuture()) {
            $errors['move_out_date'] = trans('app.errors.move_out_date_future');
        }

        if (! $moveOut->keys_returned) {
            $errors['keys_returned'] = trans('app.errors.move_out_keys_required');
        }

        if ($moveOut->deposit_disposition === 'pending') {
            $errors['deposit_disposition'] = trans('app.errors.move_out_deposit_pending');
        }

        $documentTypes = $lease->documents()
            ->whereIn('type', LeaseMoveOutOptions::REQUIRED_DOCUMENT_TYPES)
            ->pluck('type')
            ->all();

        foreach (LeaseMoveOutOptions::REQUIRED_DOCUMENT_TYPES as $type) {
            if (! in_array($type, $documentTypes, true)) {
                $errors[$type] = trans($type === 'termination_notice'
                    ? 'app.errors.move_out_termination_notice_required'
                    : 'app.errors.move_out_inspection_required');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function planErrors(Lease $lease, array $data): array
    {
        $errors = [];
        $date = CarbonImmutable::parse((string) $data['move_out_date'])->startOfDay();

        if ($lease->started_at && $date->lessThan($lease->started_at->startOfDay())) {
            $errors['move_out_date'] = trans('app.errors.move_out_before_lease_start');
        }

        $deposit = $this->cents($lease->deposit_amount);
        $deduction = $this->cents($data['deposit_deduction_amount'] ?? 0);
        $disposition = (string) $data['deposit_disposition'];

        if ($deduction > $deposit) {
            $errors['deposit_deduction_amount'] = trans('app.errors.move_out_deposit_exceeded');

            return $errors;
        }

        $valid = match ($disposition) {
            'pending' => $deduction === 0,
            'refund_full' => $deposit > 0 && $deduction === 0,
            'refund_partial' => $deposit > 0 && $deduction > 0 && $deduction < $deposit,
            'retained' => $deposit > 0 && $deduction === $deposit,
            'not_applicable' => $deposit === 0 && $deduction === 0,
            default => false,
        };

        if (! $valid) {
            $errors['deposit_disposition'] = trans('app.errors.move_out_deposit_invalid');
        }

        return $errors;
    }

    private function cents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
