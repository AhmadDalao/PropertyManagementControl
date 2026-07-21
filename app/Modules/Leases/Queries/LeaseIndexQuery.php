<?php

namespace App\Modules\Leases\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Leases\Support\LeaseAccess;
use App\Modules\Leases\Support\LeaseOptions;
use App\Modules\Shared\MorphTypes;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class LeaseIndexQuery
{
    public function __construct(
        private readonly LeaseAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
        private readonly MorphTypes $morphTypes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Request $request, User $actor): array
    {
        $this->access->ensureManager($actor);
        $filters = $this->tables->filters($request, [
            'status' => 'all',
            'payment_frequency' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);
        $baseQuery = $this->portfolios->apply(Lease::query(), $actor);
        $leases = (clone $baseQuery)->with(['tenantProfile.user', 'leaseable', 'installments']);

        foreach (['portfolio_id', 'status', 'payment_frequency'] as $filter) {
            $this->tables->exact($leases, $filters, $filter);
        }

        $this->tables->dateRange($leases, $filters, 'started_at');
        $this->tables->search($leases, $filters['search'], [
            'code',
            'notes',
            fn ($query, $search, $like) => $query->orWhereHas(
                'tenantProfile.user',
                fn ($userQuery) => $userQuery
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
            ),
            fn ($query, $search, $like) => $query->orWhere(function ($leaseQuery) use ($like): void {
                $leaseQuery
                    ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
                    ->whereIn('leaseable_id', Asset::query()
                        ->select('id')
                        ->where('title_en', 'like', $like)
                        ->orWhere('title_ar', 'like', $like)
                        ->orWhere('code', 'like', $like));
            }),
        ]);
        $metricScope = clone $baseQuery;
        $this->tables->exact($metricScope, $filters, 'portfolio_id');

        return [
            'leases' => $this->paginate($leases, $filters),
            'leaseInsights' => $this->insights($metricScope),
            'filters' => $filters,
            'counts' => $this->tables->statusCounts(
                $metricScope,
                LeaseOptions::STATUSES,
                $filters,
            ),
            'portfolioOptions' => $this->portfolios->options($actor),
            'statusOptions' => LeaseOptions::STATUSES,
            'frequencyOptions' => LeaseOptions::PAYMENT_FREQUENCIES,
        ];
    }

    /**
     * @param  Builder<Lease>  $query
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginate(Builder $query, array $filters): LengthAwarePaginator
    {
        return $this->tables->paginate($query, $filters, [
            'created_at',
            'code',
            'status',
            'payment_frequency',
            'started_at',
            'ends_at',
            'rent_amount',
        ], 'started_at')->through(fn (Lease $lease) => $this->tableRow($lease));
    }

    /**
     * @return array<string, mixed>
     */
    private function tableRow(Lease $lease): array
    {
        $lease->loadMissing(['tenantProfile.user', 'leaseable', 'installments']);
        $installments = $lease->installments;
        $nextInstallment = $installments
            ->filter(fn (LeaseInstallment $installment) => $installment->remaining_amount > 0)
            ->sortBy('due_date')
            ->first();
        $overdueCount = $installments
            ->filter(fn (LeaseInstallment $installment) => $installment->remaining_amount > 0
                && ($installment->due_date?->lessThan(today()) ?? false))
            ->count();
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;

        return [
            'id' => $lease->id,
            'portfolio_id' => $lease->portfolio_id,
            'tenant_profile_id' => $lease->tenant_profile_id,
            'leaseable_id' => $lease->leaseable_id,
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
            'tenant_profile' => [
                'id' => $lease->tenantProfile?->id,
                'user' => [
                    'name' => $lease->tenantProfile?->user?->name,
                    'email' => $lease->tenantProfile?->user?->email,
                ],
            ],
            'leaseable' => [
                'id' => $asset?->id,
                'title_en' => $asset?->title_en,
                'title_ar' => $asset?->title_ar,
                'code' => $asset?->code,
            ],
            'total_due' => (float) $lease->total_due,
            'total_paid' => (float) $lease->total_paid,
            'balance_remaining' => (float) $lease->balance_remaining,
            'days_remaining' => $lease->days_remaining,
            'installment_count' => $installments->count(),
            'overdue_count' => $overdueCount,
            'next_due_date' => $nextInstallment?->due_date?->toDateString(),
            'next_due_amount' => $nextInstallment ? (float) $nextInstallment->remaining_amount : null,
            'open_installment_count' => $installments
                ->filter(fn (LeaseInstallment $installment) => $installment->remaining_amount > 0)
                ->count(),
            'paid_percent' => $lease->total_due > 0
                ? round(min(100, ($lease->total_paid / $lease->total_due) * 100), 1)
                : 0,
        ];
    }

    /**
     * @param  Builder<Lease>  $baseQuery
     * @return array<string, int|float>
     */
    private function insights(Builder $baseQuery): array
    {
        $installments = LeaseInstallment::query()
            ->whereIn('lease_id', (clone $baseQuery)->select('leases.id'));
        $totalDue = (float) (clone $installments)->sum('amount_due');
        $totalPaid = (float) (clone $installments)->sum('amount_paid');

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'unsigned' => (clone $baseQuery)->whereNull('signed_at')->count(),
            'expiring_soon' => (clone $baseQuery)
                ->where('status', 'active')
                ->whereBetween('ends_at', [today(), today()->addDays(60)])
                ->count(),
            'overdue' => (clone $baseQuery)
                ->whereHas('installments', fn (Builder $query) => $query
                    ->whereColumn('amount_paid', '<', 'amount_due')
                    ->whereDate('due_date', '<', today()))
                ->count(),
            'total_due' => $totalDue,
            'total_paid' => $totalPaid,
            'balance_remaining' => max(0, $totalDue - $totalPaid),
        ];
    }
}
