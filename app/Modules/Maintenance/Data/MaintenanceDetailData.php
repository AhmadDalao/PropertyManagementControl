<?php

namespace App\Modules\Maintenance\Data;

use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceUpdate;
use Illuminate\Support\Collection;

final readonly class MaintenanceDetailData
{
    /**
     * @param  Collection<int, MaintenanceUpdate>  $updates
     * @param  Collection<int, ExpenseEntry>  $expenses
     */
    public function __construct(
        public MaintenanceRequest $request,
        public bool $tenantMode,
        public Collection $updates,
        public Collection $expenses,
        public float $postedExpenseTotal,
    ) {}
}
