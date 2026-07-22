<?php

namespace App\Modules\Payments\Presenters;

use App\Models\Asset;
use App\Models\Payment;

final class PaymentTableRowPresenter
{
    /** @return array<string, mixed> */
    public function present(Payment $payment): array
    {
        $allocatedAmount = (float) ($payment->getAttribute('allocations_sum_amount') ?? 0);
        $asset = $payment->lease?->leaseable instanceof Asset
            ? $payment->lease->leaseable
            : null;

        return [
            'id' => $payment->id,
            'reference' => $payment->reference,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'received_on' => $payment->received_on?->toDateString(),
            'status' => $payment->status,
            'type' => $payment->type,
            'method' => $payment->method,
            'allocated_amount' => $allocatedAmount,
            'unallocated_amount' => max(0, (float) $payment->amount - $allocatedAmount),
            'allocation_count' => (int) ($payment->getAttribute('allocations_count') ?? 0),
            'receipt_url' => route('payments.receipt', $payment),
            'tenant_profile' => $payment->tenantProfile ? [
                'user' => ['name' => $payment->tenantProfile->user?->name],
            ] : null,
            'lease' => $payment->lease ? [
                'id' => $payment->lease->id,
                'code' => $payment->lease->code,
                'status' => $payment->lease->status,
                'leaseable' => $asset ? [
                    'title_en' => $asset->title_en,
                    'title_ar' => $asset->title_ar,
                    'code' => $asset->code,
                ] : null,
            ] : null,
        ];
    }
}
