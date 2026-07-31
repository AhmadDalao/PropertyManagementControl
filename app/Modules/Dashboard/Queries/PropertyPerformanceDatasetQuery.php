<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Assets\Support\AssetHierarchy;
use App\Modules\Assets\Support\AssetRootMap;
use App\Modules\Dashboard\Support\DashboardPropertyContext;
use App\Modules\Dashboard\Support\PropertyPerformanceScorer;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Support\Collection;

final readonly class PropertyPerformanceDatasetQuery
{
    public function __construct(
        private PortfolioScope $portfolios,
        private AssetHierarchy $hierarchy,
        private AssetRootMap $rootMap,
        private PropertyPerformanceScorer $scorer,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function forUser(User $actor, DashboardPropertyContext $context): array
    {
        $assets = $context
            ->assets($this->portfolios->apply(Asset::query(), $actor))
            ->with('portfolio:id,code,name_en,name_ar,showcase_dataset_id')
            ->get([
                'id', 'portfolio_id', 'parent_id', 'title_en', 'title_ar',
                'code', 'rentable', 'occupancy_status', 'currency',
            ]);

        if ($assets->isEmpty()) {
            return [];
        }

        $rootByAsset = $this->rootMap->build($assets);
        $rows = $this->initialRows($assets);
        $leases = $this->leases($actor, $context, $assets->pluck('id')->all());
        $rootByLease = [];

        foreach ($assets->where('rentable', true) as $asset) {
            $rootId = $rootByAsset[$asset->id] ?? null;
            if ($rootId === null || ! isset($rows[$rootId])) {
                continue;
            }

            $rows[$rootId]['rentable_units']++;
            if (in_array($asset->occupancy_status, ['occupied', 'partially_occupied'], true)) {
                $rows[$rootId]['occupied_units']++;
            }
        }

        foreach ($leases as $lease) {
            $rootId = $rootByAsset[$lease->leaseable_id] ?? null;
            if ($rootId === null || ! isset($rows[$rootId])) {
                continue;
            }

            $rootByLease[$lease->id] = $rootId;
            $rows[$rootId]['active_leases'] += $lease->status === 'active' ? 1 : 0;
            $rows[$rootId]['expiring_leases'] += $lease->status === 'active'
                && $lease->ends_at?->between(today(), today()->addDays(90)) ? 1 : 0;
        }

        $this->addInstallments($rows, $rootByLease);
        $this->addPayments($rows, $rootByLease, $actor);
        $this->addExpenses($rows, $rootByAsset, $actor);
        $this->addMaintenance($rows, $rootByAsset, $actor);

        return collect($rows)
            ->map(fn (array $row): array => $this->scorer->score($row))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @return array<int, array<string, mixed>>
     */
    private function initialRows(Collection $assets): array
    {
        $rows = [];

        foreach ($assets->whereNull('parent_id') as $asset) {
            $rows[$asset->id] = [
                'id' => $asset->id,
                'portfolio_id' => $asset->portfolio_id,
                'portfolio_code' => $asset->portfolio?->code,
                'portfolio_name_en' => $asset->portfolio?->name_en,
                'portfolio_name_ar' => $asset->portfolio?->name_ar,
                'is_showcase' => $asset->is_showcase,
                'title_en' => $asset->title_en,
                'title_ar' => $asset->title_ar,
                'code' => $asset->code,
                'currency' => $asset->currency ?: 'SAR',
                'rentable_units' => 0,
                'occupied_units' => 0,
                'active_leases' => 0,
                'expiring_leases' => 0,
                'scheduled_due' => 0.0,
                'scheduled_paid' => 0.0,
                'arrears' => 0.0,
                'collected' => 0.0,
                'expenses' => 0.0,
                'open_requests' => 0,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, int>  $assetIds
     * @return Collection<int, Lease>
     */
    private function leases(User $actor, DashboardPropertyContext $context, array $assetIds): Collection
    {
        return $context
            ->leases($this->portfolios->apply(Lease::query(), $actor))
            ->whereIn('leaseable_type', $this->hierarchy->leaseableTypes())
            ->whereIn('leaseable_id', $assetIds)
            ->get(['id', 'leaseable_id', 'status', 'ends_at']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $rootByLease
     */
    private function addInstallments(array &$rows, array $rootByLease): void
    {
        LeaseInstallment::query()
            ->whereIn('lease_id', array_keys($rootByLease))
            ->where(fn ($query) => $query
                ->whereDate('due_date', '>=', now()->startOfMonth())
                ->whereDate('due_date', '<=', now()->endOfMonth())
                ->orWhereDate('due_date', '<', today()))
            ->get(['lease_id', 'due_date', 'amount_due', 'amount_paid'])
            ->each(function (LeaseInstallment $installment) use (&$rows, $rootByLease): void {
                $rootId = $rootByLease[$installment->lease_id];
                if ($installment->due_date?->isCurrentMonth()) {
                    $rows[$rootId]['scheduled_due'] += (float) $installment->amount_due;
                    $rows[$rootId]['scheduled_paid'] += (float) $installment->amount_paid;
                }
                if ($installment->due_date?->isBefore(today())) {
                    $rows[$rootId]['arrears'] += $installment->remaining_amount;
                }
            });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $rootByLease
     */
    private function addPayments(array &$rows, array $rootByLease, User $actor): void
    {
        $this->portfolios->apply(Payment::query(), $actor)
            ->where('status', 'posted')
            ->whereIn('lease_id', array_keys($rootByLease))
            ->whereDate('received_on', '>=', now()->startOfMonth())
            ->whereDate('received_on', '<=', now()->endOfMonth())
            ->get(['lease_id', 'amount'])
            ->each(function (Payment $payment) use (&$rows, $rootByLease): void {
                $rootId = $payment->lease_id !== null
                    ? ($rootByLease[$payment->lease_id] ?? null)
                    : null;

                if ($rootId !== null && isset($rows[$rootId])) {
                    $rows[$rootId]['collected'] += (float) $payment->amount;
                }
            });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $rootByAsset
     */
    private function addExpenses(array &$rows, array $rootByAsset, User $actor): void
    {
        $this->portfolios->apply(ExpenseEntry::query(), $actor)
            ->where('status', 'posted')
            ->whereIn('asset_id', array_keys($rootByAsset))
            ->whereDate('incurred_on', '>=', now()->startOfMonth())
            ->whereDate('incurred_on', '<=', now()->endOfMonth())
            ->get(['asset_id', 'amount'])
            ->each(function (ExpenseEntry $expense) use (&$rows, $rootByAsset): void {
                $rootId = $expense->asset_id !== null
                    ? ($rootByAsset[$expense->asset_id] ?? null)
                    : null;

                if ($rootId !== null && isset($rows[$rootId])) {
                    $rows[$rootId]['expenses'] += (float) $expense->amount;
                }
            });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $rootByAsset
     */
    private function addMaintenance(array &$rows, array $rootByAsset, User $actor): void
    {
        $this->portfolios->apply(MaintenanceRequest::query(), $actor)
            ->whereIn('status', ['open', 'in_progress'])
            ->whereIn('asset_id', array_keys($rootByAsset))
            ->get(['asset_id'])
            ->each(function (MaintenanceRequest $request) use (&$rows, $rootByAsset): void {
                $rootId = $rootByAsset[$request->asset_id] ?? null;

                if ($rootId !== null && isset($rows[$rootId])) {
                    $rows[$rootId]['open_requests']++;
                }
            });
    }
}
