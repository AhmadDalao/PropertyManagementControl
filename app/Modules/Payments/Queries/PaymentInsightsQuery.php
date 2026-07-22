<?php

namespace App\Modules\Payments\Queries;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Database\Eloquent\Builder;

final class PaymentInsightsQuery
{
    /**
     * @param  Builder<Payment>  $query
     * @return array<string, int|float|string|bool|null>
     */
    public function get(Builder $query): array
    {
        $summary = (clone $query)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_count")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw("SUM(CASE WHEN status = 'void' THEN 1 ELSE 0 END) as void_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'posted' THEN amount ELSE 0 END), 0) as posted_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'void' THEN amount ELSE 0 END), 0) as void_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'posted' AND received_on BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as received_this_month", [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->first();
        $posted = (clone $query)->where('status', 'posted');
        $allocatedAmount = (float) PaymentAllocation::query()
            ->whereIn('payment_id', (clone $posted)->select('payments.id'))
            ->sum('amount');
        $currencies = (clone $query)
            ->whereIn('status', ['posted', 'pending'])
            ->whereNotNull('currency')
            ->distinct()
            ->limit(2)
            ->pluck('currency');
        $postedAmount = (float) ($summary?->getAttribute('posted_amount') ?? 0);

        return [
            'total' => (int) ($summary?->getAttribute('total') ?? 0),
            'posted_count' => (int) ($summary?->getAttribute('posted_count') ?? 0),
            'pending_count' => (int) ($summary?->getAttribute('pending_count') ?? 0),
            'void_count' => (int) ($summary?->getAttribute('void_count') ?? 0),
            'posted_amount' => $postedAmount,
            'pending_amount' => (float) ($summary?->getAttribute('pending_amount') ?? 0),
            'void_amount' => (float) ($summary?->getAttribute('void_amount') ?? 0),
            'allocated_amount' => $allocatedAmount,
            'unallocated_amount' => max(0, $postedAmount - $allocatedAmount),
            'received_this_month' => (float) ($summary?->getAttribute('received_this_month') ?? 0),
            'currency' => $currencies->count() === 1 ? (string) $currencies->first() : null,
            'mixed_currencies' => $currencies->count() > 1,
        ];
    }
}
