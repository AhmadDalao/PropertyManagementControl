<?php

namespace App\Modules\Assets\Data;

use App\Models\Asset;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Collection;

final readonly class AssetOperationsData
{
    /**
     * @param  array<int, int>  $assetIds
     * @param  Collection<int, Asset>  $rentableAssets
     * @param  Collection<int, Lease>  $leases
     * @param  Collection<int, LeaseInstallment>  $collectionQueue
     * @param  Collection<int, MaintenanceRequest>  $maintenance
     * @param  Collection<int, ExpenseEntry>  $expenses
     * @param  Collection<int, Document>  $documents
     * @param  list<array{
     *     currency:string,
     *     monthlyScheduledDue:float,
     *     monthlyScheduledPaid:float,
     *     collectionRate:float,
     *     arrears:float,
     *     monthlyRevenue:float,
     *     monthlyExpenses:float,
     *     monthlyNet:float,
     *     postedExpenseTotal:float
     * }>  $currencyTotals
     */
    public function __construct(
        public Asset $propertyRoot,
        public array $assetIds,
        public Collection $rentableAssets,
        public Collection $leases,
        public Collection $collectionQueue,
        public Collection $maintenance,
        public Collection $expenses,
        public Collection $documents,
        public ?Lease $directActiveLease,
        public int $descendantCount,
        public int $rentableCount,
        public int $occupiedCount,
        public int $vacantCount,
        public int $leaseCount,
        public int $activeLeaseCount,
        public int $expiringLeaseCount,
        public int $openMaintenanceCount,
        public ?float $monthlyScheduledDue,
        public ?float $monthlyScheduledPaid,
        public ?float $arrears,
        public ?float $monthlyRevenue,
        public ?float $monthlyExpenses,
        public ?float $postedExpenseTotal,
        public array $currencyTotals,
    ) {}

    public function occupancyRate(): float
    {
        return $this->rentableCount > 0
            ? round(($this->occupiedCount / $this->rentableCount) * 100, 1)
            : 0.0;
    }

    public function collectionRate(): ?float
    {
        if ($this->monthlyScheduledDue === null) {
            return null;
        }

        return $this->monthlyScheduledDue > 0
            ? round(min(100, ($this->monthlyScheduledPaid / $this->monthlyScheduledDue) * 100), 1)
            : 0.0;
    }

    public function monthlyNet(): ?float
    {
        if ($this->monthlyRevenue === null || $this->monthlyExpenses === null) {
            return null;
        }

        return $this->monthlyRevenue - $this->monthlyExpenses;
    }

    public function hasArrears(): bool
    {
        return collect($this->currencyTotals)->contains(
            fn (array $total): bool => $total['arrears'] > 0,
        );
    }

    public function hasNegativeNet(): bool
    {
        return collect($this->currencyTotals)->contains(
            fn (array $total): bool => $total['monthlyNet'] < 0,
        );
    }
}
