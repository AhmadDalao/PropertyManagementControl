<?php

namespace App\Modules\Portfolios\Data;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class PortfolioDetailData
{
    /**
     * @param  array<string, bool>  $settings
     * @param  Collection<int, Asset>  $assets
     * @param  Collection<int, User>  $people
     * @param  Collection<int, Lease>  $leases
     * @param  Collection<int, MaintenanceRequest>  $maintenance
     * @param  Collection<int, Document>  $documents
     */
    public function __construct(
        public Portfolio $portfolio,
        public array $settings,
        public Collection $assets,
        public Collection $people,
        public Collection $leases,
        public Collection $maintenance,
        public Collection $documents,
        public int $assetTotal,
        public int $vacantAssets,
        public float $valuation,
        public int $activeLeases,
        public int $openMaintenance,
        public float $postedRevenue,
        public float $postedExpenses,
        public int $visibleUsers,
    ) {}
}
