<?php

namespace App\Modules\Reports\Queries;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Reports\Support\ReportAccess;
use App\Modules\Shared\PortfolioScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PortfolioReportQuery
{
    public function __construct(
        private readonly ReportAccess $access,
        private readonly PortfolioScope $portfolios,
    ) {}

    /**
     * @param  array{date_from:string,date_to:string,portfolio_id:int|null}  $filters
     * @return array<string, mixed>
     */
    public function handle(User $actor, array $filters): array
    {
        $this->access->ensurePortfolioFilter($actor, $filters['portfolio_id']);

        $paymentQuery = $this->scope(Payment::query(), $actor, $filters)
            ->where('status', 'posted')
            ->with(['lease.leaseable', 'tenantProfile.user']);
        $expenseQuery = $this->scope(ExpenseEntry::query(), $actor, $filters)
            ->where('status', 'posted')
            ->with(['asset']);
        $assetQuery = $this->scope(Asset::query(), $actor, $filters);
        $leaseQuery = $this->scope(Lease::query(), $actor, $filters)
            ->with(['installments', 'tenantProfile.user', 'leaseable']);
        $maintenanceQuery = $this->scope(MaintenanceRequest::query(), $actor, $filters)
            ->with(['asset', 'tenantProfile.user', 'assignedTo']);

        $this->dateRange($paymentQuery, $filters, 'received_on');
        $this->dateRange($expenseQuery, $filters, 'incurred_on');
        $this->dateRange($maintenanceQuery, $filters, 'created_at');

        $payments = $paymentQuery->get();
        $expenses = $expenseQuery->get();
        $assets = $assetQuery->get();
        $leases = $leaseQuery->get();
        $maintenanceRequests = $maintenanceQuery->get();
        $revenue = (float) $payments->sum('amount');
        $expensesTotal = (float) $expenses->sum('amount');
        $rentableAssets = $assets->where('rentable', true);
        $occupiedAssets = $rentableAssets->whereIn('occupancy_status', ['occupied', 'partially_occupied']);
        $periodStart = CarbonImmutable::parse($filters['date_from'])->startOfDay();
        $periodEnd = CarbonImmutable::parse($filters['date_to'])->endOfDay();
        $scheduledInstallments = $leases
            ->whereIn('status', ['active', 'expired'])
            ->flatMap(fn (Lease $lease) => $lease->installments)
            ->filter(fn (LeaseInstallment $installment) => $installment->due_date?->betweenIncluded($periodStart, $periodEnd) ?? false);
        $scheduledDue = (float) $scheduledInstallments->sum('amount_due');
        $scheduledPaid = (float) $scheduledInstallments->sum(
            fn (LeaseInstallment $installment) => min((float) $installment->amount_due, (float) $installment->amount_paid),
        );
        $arrearsCutoff = $this->arrearsCutoff($filters['date_to']);
        $arrearsLeases = $leases
            ->whereIn('status', ['active', 'expired'])
            ->map(fn (Lease $lease) => [
                'lease' => $lease,
                'arrears_amount' => $this->arrearsAmount($lease, $arrearsCutoff),
            ])
            ->filter(fn (array $item) => $item['arrears_amount'] > 0)
            ->sortByDesc('arrears_amount')
            ->values();
        $maintenanceBacklog = $maintenanceRequests
            ->whereIn('status', ['open', 'in_progress'])
            ->sortByDesc('created_at')
            ->values();

        return [
            'mode' => $actor->hasRole('superadmin') ? 'superadmin' : 'portfolio',
            'summary' => [
                'revenue' => $revenue,
                'expenses' => $expensesTotal,
                'net' => $revenue - $expensesTotal,
                'scheduledDue' => $scheduledDue,
                'scheduledPaid' => $scheduledPaid,
                'collectionRate' => $scheduledDue > 0
                    ? round(min(100, ($scheduledPaid / $scheduledDue) * 100), 2)
                    : 0,
                'occupancyRate' => $rentableAssets->count() > 0
                    ? round(($occupiedAssets->count() / $rentableAssets->count()) * 100, 2)
                    : 0,
                'arrears' => (float) $arrearsLeases->sum('arrears_amount'),
                'contractBalance' => (float) $leases
                    ->whereIn('status', ['active', 'expired'])
                    ->sum(fn (Lease $lease) => $lease->balance_remaining),
                'activeLeases' => $leases->where('status', 'active')->count(),
                'leasesInArrears' => $arrearsLeases->count(),
                'openRequests' => $maintenanceBacklog->count(),
                'resolvedRequests' => $maintenanceRequests->where('status', 'resolved')->count(),
            ],
            'charts' => [
                'revenueByMonth' => $payments
                    ->groupBy(fn (Payment $payment) => $payment->received_on?->format('Y-m') ?? trans('app.reports.unscheduled'))
                    ->sortKeys()
                    ->map(fn (Collection $group) => (float) $group->sum('amount')),
                'expenseByCategory' => $expenses
                    ->groupBy(fn (ExpenseEntry $expense) => $expense->category ?: 'uncategorized')
                    ->sortKeys()
                    ->map(fn (Collection $group) => (float) $group->sum('amount')),
                'assetMix' => $assets
                    ->groupBy('asset_type')
                    ->sortKeys()
                    ->map(fn (Collection $group) => $group->count()),
                'maintenanceByStatus' => $maintenanceRequests
                    ->groupBy('status')
                    ->sortKeys()
                    ->map(fn (Collection $group) => $group->count()),
            ],
            'arrearsLeases' => $arrearsLeases
                ->take(10)
                ->map(function (array $item): array {
                    $lease = $item['lease'];
                    $asset = $lease->leaseable;

                    return [
                        'id' => $lease->id,
                        'code' => $lease->code,
                        'tenant' => $lease->tenantProfile?->user?->name,
                        'asset' => $asset instanceof Asset
                            ? $this->portfolios->localized($asset->title_en, $asset->title_ar)
                            : null,
                        'ends_at' => $lease->ends_at?->toDateString(),
                        'arrears_amount' => $item['arrears_amount'],
                        'currency' => $lease->currency,
                    ];
                })
                ->all(),
            'topAssets' => $this->topAssetsByRevenue($payments),
            'recentPayments' => $payments
                ->sortByDesc('received_on')
                ->take(8)
                ->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'reference' => $payment->reference ?: '#'.$payment->id,
                    'tenant' => $payment->tenantProfile?->user?->name,
                    'lease' => $payment->lease?->code,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                    'received_on' => $payment->received_on?->toDateString(),
                ])
                ->values()
                ->all(),
            'recentExpenses' => $expenses
                ->sortByDesc('incurred_on')
                ->take(8)
                ->map(fn (ExpenseEntry $expense) => [
                    'id' => $expense->id,
                    'title' => $expense->title,
                    'category' => $expense->category,
                    'asset' => $this->portfolios->localized($expense->asset?->title_en, $expense->asset?->title_ar),
                    'amount' => (float) $expense->amount,
                    'currency' => $expense->currency,
                    'incurred_on' => $expense->incurred_on?->toDateString(),
                ])
                ->values()
                ->all(),
            'maintenanceBacklog' => $maintenanceBacklog
                ->take(8)
                ->map(fn (MaintenanceRequest $maintenanceRequest) => [
                    'id' => $maintenanceRequest->id,
                    'title' => $maintenanceRequest->title,
                    'asset' => $this->portfolios->localized(
                        $maintenanceRequest->asset?->title_en,
                        $maintenanceRequest->asset?->title_ar,
                    ),
                    'tenant' => $maintenanceRequest->tenantProfile?->user?->name,
                    'status' => $maintenanceRequest->status,
                    'priority' => $maintenanceRequest->priority,
                    'created_at' => $maintenanceRequest->created_at?->toDateString(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function arrearsAmount(Lease $lease, CarbonImmutable $cutoff): float
    {
        return (float) $lease->installments
            ->filter(fn (LeaseInstallment $installment) => $installment->due_date?->lessThan($cutoff) ?? false)
            ->sum(fn (LeaseInstallment $installment) => $installment->remaining_amount);
    }

    private function arrearsCutoff(string $dateTo): CarbonImmutable
    {
        $today = CarbonImmutable::today();
        $reportEnd = CarbonImmutable::parse($dateTo)->startOfDay();

        return $reportEnd->lessThan($today) ? $reportEnd->addDay() : $today;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{date_from:string,date_to:string,portfolio_id:int|null}  $filters
     * @return Builder<TModel>
     */
    private function scope(Builder $query, User $actor, array $filters): Builder
    {
        $this->portfolios->apply($query, $actor);

        if ($filters['portfolio_id'] !== null) {
            $query->where('portfolio_id', $filters['portfolio_id']);
        }

        return $query;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{date_from:string,date_to:string,portfolio_id:int|null}  $filters
     * @return Builder<TModel>
     */
    private function dateRange(Builder $query, array $filters, string $column): Builder
    {
        return $query
            ->whereDate($column, '>=', $filters['date_from'])
            ->whereDate($column, '<=', $filters['date_to']);
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @return array<int, array{id:int,asset:string,revenue:float,currency:string,lease_count:int}>
     */
    private function topAssetsByRevenue(Collection $payments): array
    {
        $assets = [];

        foreach ($payments as $payment) {
            $asset = $payment->lease?->leaseable;

            if (! $asset instanceof Asset) {
                continue;
            }

            $assets[$asset->id] ??= [
                'id' => $asset->id,
                'asset' => $this->portfolios->localized($asset->title_en, $asset->title_ar)
                    ?? trans('app.reports.unknown_asset'),
                'revenue' => 0.0,
                'currency' => $payment->currency,
                'lease_ids' => [],
            ];
            $assets[$asset->id]['revenue'] += (float) $payment->amount;
            $assets[$asset->id]['lease_ids'][$payment->lease_id] = true;
        }

        return collect($assets)
            ->map(fn (array $asset): array => [
                'id' => $asset['id'],
                'asset' => $asset['asset'],
                'revenue' => $asset['revenue'],
                'currency' => $asset['currency'],
                'lease_count' => count($asset['lease_ids']),
            ])
            ->sortByDesc('revenue')
            ->take(8)
            ->values()
            ->all();
    }
}
