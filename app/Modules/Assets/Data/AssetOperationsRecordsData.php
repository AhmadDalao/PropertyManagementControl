<?php

namespace App\Modules\Assets\Data;

use App\Models\Asset;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Collection;

final readonly class AssetOperationsRecordsData
{
    /**
     * @param  Collection<int, Asset>  $rentableAssets
     * @param  Collection<int, Lease>  $leases
     * @param  Collection<int, LeaseInstallment>  $collectionQueue
     * @param  Collection<int, MaintenanceRequest>  $maintenance
     * @param  Collection<int, ExpenseEntry>  $expenses
     * @param  Collection<int, Document>  $documents
     */
    public function __construct(
        public Collection $rentableAssets,
        public Collection $leases,
        public Collection $collectionQueue,
        public Collection $maintenance,
        public Collection $expenses,
        public Collection $documents,
        public ?Lease $directActiveLease,
    ) {}
}
