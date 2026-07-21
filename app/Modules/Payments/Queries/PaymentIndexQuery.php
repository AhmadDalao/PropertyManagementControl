<?php

namespace App\Modules\Payments\Queries;

use App\Models\Asset;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use App\Modules\Payments\Support\PaymentAccess;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\Shared\MorphTypes;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentIndexQuery
{
    public function __construct(
        private readonly PaymentAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
        private readonly MorphTypes $morphTypes,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $this->access->ensureManager($actor);
        $filters = $this->filters($request);
        $baseQuery = $this->portfolios->apply(Payment::query(), $actor);
        $payments = (clone $baseQuery)
            ->with(['lease.leaseable', 'tenantProfile.user'])
            ->withCount('allocations')
            ->withSum('allocations', 'amount');
        $this->applyFilters($payments, $filters);
        $metricScope = clone $baseQuery;
        $this->tables->exact($metricScope, $filters, 'portfolio_id');

        return [
            'payments' => $this->paginate($payments, $filters),
            'paymentInsights' => $this->insights($metricScope),
            'filters' => $filters,
            'counts' => $this->tables->statusCounts(
                $metricScope,
                PaymentOptions::STATUSES,
                $filters,
            ),
            'portfolioOptions' => $this->portfolios->options($actor),
            'statusOptions' => PaymentOptions::STATUSES,
            'typeOptions' => PaymentOptions::TYPES,
            'methodOptions' => PaymentOptions::METHODS,
        ];
    }

    /** @return Builder<Payment> */
    public function forExport(Request $request, User $actor): Builder
    {
        $this->access->ensureManager($actor);
        $filters = $this->filters($request);
        $payments = $this->portfolios
            ->apply(Payment::query(), $actor)
            ->with(['lease.leaseable', 'tenantProfile.user']);
        $this->applyFilters($payments, $filters);

        return $payments;
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $this->tables->filters($request, [
            'status' => 'all',
            'type' => 'all',
            'method' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);
    }

    /**
     * @param  Builder<Payment>  $payments
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $payments, array $filters): void
    {
        foreach (['portfolio_id', 'status', 'type', 'method'] as $filter) {
            $this->tables->exact($payments, $filters, $filter);
        }

        $this->tables->dateRange($payments, $filters, 'received_on');
        $this->tables->search($payments, (string) $filters['search'], [
            'reference',
            'notes',
            fn ($query, $search, $like) => $query->orWhereHas(
                'lease',
                fn ($leaseQuery) => $leaseQuery->where('code', 'like', $like)
            ),
            fn ($query, $search, $like) => $query->orWhereHas(
                'tenantProfile.user',
                fn ($userQuery) => $userQuery
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
            ),
            fn ($query, $search, $like) => $query->orWhereHas(
                'lease',
                fn ($leaseQuery) => $leaseQuery
                    ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
                    ->whereIn('leaseable_id', Asset::query()
                        ->select('id')
                        ->where('title_en', 'like', $like)
                        ->orWhere('title_ar', 'like', $like)
                        ->orWhere('code', 'like', $like))
            ),
        ]);
    }

    /**
     * @param  Builder<Payment>  $query
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginate(Builder $query, array $filters): LengthAwarePaginator
    {
        return $this->tables->paginate($query, $filters, [
            'created_at',
            'received_on',
            'reference',
            'status',
            'type',
            'method',
            'amount',
        ], 'received_on')->through(fn (Payment $payment) => $this->tableRow($payment));
    }

    /** @return array<string, mixed> */
    private function tableRow(Payment $payment): array
    {
        $payment->loadMissing(['lease.leaseable', 'tenantProfile.user']);
        $allocatedAmount = (float) $payment->getAttribute('allocations_sum_amount');
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
            'allocation_count' => (int) $payment->getAttribute('allocations_count'),
            'receipt_url' => route('payments.receipt', $payment),
            'tenant_profile' => [
                'id' => $payment->tenantProfile?->id,
                'user' => [
                    'name' => $payment->tenantProfile?->user?->name,
                    'email' => $payment->tenantProfile?->user?->email,
                ],
            ],
            'lease' => [
                'id' => $payment->lease?->id,
                'code' => $payment->lease?->code,
                'status' => $payment->lease?->status,
                'leaseable' => [
                    'title_en' => $asset?->title_en,
                    'title_ar' => $asset?->title_ar,
                    'code' => $asset?->code,
                ],
            ],
        ];
    }

    /**
     * @param  Builder<Payment>  $baseQuery
     * @return array<string, int|float>
     */
    private function insights(Builder $baseQuery): array
    {
        $posted = (clone $baseQuery)->where('status', 'posted');
        $postedAmount = (float) (clone $posted)->sum('amount');
        $allocatedAmount = (float) PaymentAllocation::query()
            ->whereIn('payment_id', (clone $posted)->select('payments.id'))
            ->sum('amount');

        return [
            'total' => (clone $baseQuery)->count(),
            'posted_count' => (clone $posted)->count(),
            'pending_count' => (clone $baseQuery)->where('status', 'pending')->count(),
            'void_count' => (clone $baseQuery)->where('status', 'void')->count(),
            'posted_amount' => $postedAmount,
            'pending_amount' => (float) (clone $baseQuery)->where('status', 'pending')->sum('amount'),
            'void_amount' => (float) (clone $baseQuery)->where('status', 'void')->sum('amount'),
            'allocated_amount' => $allocatedAmount,
            'unallocated_amount' => max(0, $postedAmount - $allocatedAmount),
            'received_this_month' => (float) (clone $posted)
                ->whereBetween('received_on', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
        ];
    }
}
