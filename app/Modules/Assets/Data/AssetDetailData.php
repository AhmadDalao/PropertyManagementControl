<?php

namespace App\Modules\Assets\Data;

use App\Models\Asset;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class AssetDetailData
{
    /**
     * @param  Collection<int, Asset>  $children
     * @param  Collection<int, Lease>  $leases
     * @param  Collection<int, MaintenanceRequest>  $maintenance
     * @param  Collection<int, ExpenseEntry>  $expenses
     * @param  Collection<int, Document>  $documents
     */
    public function __construct(
        public Asset $asset,
        public User $actor,
        public Collection $children,
        public Collection $leases,
        public Collection $maintenance,
        public Collection $expenses,
        public Collection $documents,
        public ?Lease $activeLease,
        public int $childrenCount,
        public int $leaseCount,
        public int $openMaintenanceCount,
        public float $postedExpenseTotal,
    ) {}
}
