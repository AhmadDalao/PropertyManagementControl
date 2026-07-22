<?php

namespace App\Modules\Portfolios\Queries;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Data\PortfolioDetailData;
use App\Modules\Portfolios\Support\PortfolioAccess;
use App\Modules\Users\Support\UserAccess;
use App\Support\PortfolioModules;

class PortfolioDetailQuery
{
    public function __construct(
        private readonly PortfolioAccess $access,
        private readonly UserAccess $users,
    ) {}

    public function get(Portfolio $portfolio, User $actor): PortfolioDetailData
    {
        $this->access->ensureCanView($actor, $portfolio);
        $portfolio->loadMissing('owner:id,name,status');
        $settings = PortfolioModules::normalize($portfolio->module_settings);
        $peopleQuery = $this->users->directoryScope(User::query(), $actor)
            ->where('portfolio_id', $portfolio->id);

        $assets = $this->moduleVisible($actor, $settings, 'assets')
            ? $portfolio->assets()
                ->latest()
                ->limit(8)
                ->get(['id', 'portfolio_id', 'title_en', 'title_ar', 'code', 'asset_type', 'occupancy_status', 'status'])
            : collect();
        $people = $this->moduleVisible($actor, $settings, 'users')
            ? (clone $peopleQuery)
                ->with('roles:id,name')
                ->latest()
                ->limit(8)
                ->get(['id', 'portfolio_id', 'name', 'email', 'status', 'created_at'])
            : collect();
        $leases = $this->moduleVisible($actor, $settings, 'leases')
            ? $portfolio->leases()
                ->with(['tenantProfile.user:id,name', 'leaseable'])
                ->latest()
                ->limit(8)
                ->get()
            : collect();
        $maintenance = $this->moduleVisible($actor, $settings, 'maintenance')
            ? $portfolio->maintenanceRequests()
                ->with('asset:id,portfolio_id,title_en,title_ar,code')
                ->latest('requested_at')
                ->limit(8)
                ->get()
            : collect();
        $documents = $this->moduleVisible($actor, $settings, 'documents')
            ? $portfolio->documents()->latest()->limit(8)->get()
            : collect();

        $assetSummary = $portfolio->assets()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN occupancy_status = 'vacant' AND rentable = 1 THEN 1 ELSE 0 END) as vacant_count")
            ->selectRaw("SUM(CASE WHEN status != 'archived' THEN valuation_amount ELSE 0 END) as valuation_total")
            ->first();
        $leaseSummary = $portfolio->leases()
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count")
            ->first();

        return new PortfolioDetailData(
            portfolio: $portfolio,
            settings: $settings,
            assets: $assets,
            people: $people,
            leases: $leases,
            maintenance: $maintenance,
            documents: $documents,
            assetTotal: (int) ($assetSummary?->getAttribute('total') ?? 0),
            vacantAssets: (int) ($assetSummary?->getAttribute('vacant_count') ?? 0),
            valuation: (float) ($assetSummary?->getAttribute('valuation_total') ?? 0),
            activeLeases: (int) ($leaseSummary?->getAttribute('active_count') ?? 0),
            openMaintenance: $portfolio->maintenanceRequests()
                ->whereIn('status', ['open', 'in_progress'])
                ->count(),
            postedRevenue: (float) $portfolio->payments()->where('status', 'posted')->sum('amount'),
            postedExpenses: (float) $portfolio->expenseEntries()->where('status', 'posted')->sum('amount'),
            visibleUsers: (clone $peopleQuery)->count(),
        );
    }

    /** @param array<string, bool> $settings */
    private function moduleVisible(User $actor, array $settings, string $module): bool
    {
        return $actor->hasRole('superadmin') || ($settings[$module] ?? true);
    }
}
