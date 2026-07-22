<?php

namespace App\Modules\Portfolios\Queries;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Users\Support\UserAccess;
use Illuminate\Database\Eloquent\Builder;

class PortfolioInsightsQuery
{
    public function __construct(private readonly UserAccess $users) {}

    /**
     * @param  Builder<Portfolio>  $baseQuery
     * @return array<string, int|float|string|null>
     */
    public function get(Builder $baseQuery, User $actor): array
    {
        $portfolioSummary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count")
            ->selectRaw("SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count")
            ->selectRaw("SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_count")
            ->first();
        $portfolioIds = (clone $baseQuery)->select('id');
        $assetSummary = Asset::query()
            ->whereIn('portfolio_id', clone $portfolioIds)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status != 'archived' THEN valuation_amount ELSE 0 END) as valuation_total")
            ->first();
        $leaseSummary = Lease::query()
            ->whereIn('portfolio_id', clone $portfolioIds)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count")
            ->first();
        $openMaintenance = MaintenanceRequest::query()
            ->whereIn('portfolio_id', clone $portfolioIds)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();
        $revenue = Payment::query()
            ->whereIn('portfolio_id', clone $portfolioIds)
            ->where('status', 'posted')
            ->sum('amount');
        $expenses = ExpenseEntry::query()
            ->whereIn('portfolio_id', clone $portfolioIds)
            ->where('status', 'posted')
            ->sum('amount');
        $visibleUsers = $this->users->directoryScope(User::query(), $actor)
            ->whereIn('portfolio_id', clone $portfolioIds)
            ->count();
        $currencies = (clone $baseQuery)
            ->whereNotNull('default_currency')
            ->distinct()
            ->orderBy('default_currency')
            ->pluck('default_currency');
        $singleCurrency = $currencies->count() <= 1;
        $valuation = (float) ($assetSummary?->getAttribute('valuation_total') ?? 0);
        $revenueTotal = (float) $revenue;
        $expenseTotal = (float) $expenses;

        return [
            'total' => (int) ($portfolioSummary?->getAttribute('total') ?? 0),
            'active' => (int) ($portfolioSummary?->getAttribute('active_count') ?? 0),
            'inactive' => (int) ($portfolioSummary?->getAttribute('inactive_count') ?? 0),
            'archived' => (int) ($portfolioSummary?->getAttribute('archived_count') ?? 0),
            'assets' => (int) ($assetSummary?->getAttribute('total') ?? 0),
            'users' => $visibleUsers,
            'leases' => (int) ($leaseSummary?->getAttribute('total') ?? 0),
            'active_leases' => (int) ($leaseSummary?->getAttribute('active_count') ?? 0),
            'open_maintenance' => $openMaintenance,
            'valuation_total' => $singleCurrency ? $valuation : null,
            'posted_revenue_total' => $singleCurrency ? $revenueTotal : null,
            'posted_expense_total' => $singleCurrency ? $expenseTotal : null,
            'net_total' => $singleCurrency ? $revenueTotal - $expenseTotal : null,
            'currency' => $currencies->first() ?: 'SAR',
            'currency_count' => $currencies->count(),
        ];
    }
}
