<?php

namespace App\Modules\ShowcaseData\Queries;

use App\Models\Asset;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\ShowcaseDataset;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\ShowcaseData\Support\ShowcaseTargets;

class ShowcaseDatasetMetrics
{
    public function __construct(
        private readonly ShowcaseTargets $targets,
    ) {}

    /** @return array<string, int> */
    public function counts(ShowcaseDataset $dataset): array
    {
        $portfolioIds = $dataset->portfolios()->pluck('id');
        $leaseIds = Lease::query()->whereIn('portfolio_id', $portfolioIds)->pluck('id');

        return [
            'portfolios' => $portfolioIds->count(),
            'buildings' => Asset::query()->whereIn('portfolio_id', $portfolioIds)->where('asset_type', 'building')->count(),
            'floors' => Asset::query()->whereIn('portfolio_id', $portfolioIds)->where('asset_type', 'floor')->count(),
            'units' => Asset::query()->whereIn('portfolio_id', $portfolioIds)->whereIn('asset_type', ['unit', 'space'])->count(),
            'users' => User::query()->where('showcase_dataset_id', $dataset->id)->count(),
            'owners' => User::query()->where('showcase_dataset_id', $dataset->id)->role('owner')->count(),
            'managers' => User::query()->where('showcase_dataset_id', $dataset->id)->role('property_manager')->count(),
            'tenant_accounts' => User::query()->where('showcase_dataset_id', $dataset->id)->role('tenant')->count(),
            'tenants' => TenantProfile::query()->whereIn('portfolio_id', $portfolioIds)->count(),
            'leases' => $leaseIds->count(),
            'installments' => LeaseInstallment::query()->whereIn('lease_id', $leaseIds)->count(),
            'payments' => Payment::query()->whereIn('portfolio_id', $portfolioIds)->count(),
            'maintenance' => MaintenanceRequest::query()->whereIn('portfolio_id', $portfolioIds)->count(),
            'expenses' => ExpenseEntry::query()->whereIn('portfolio_id', $portfolioIds)->count(),
            'documents' => Document::query()->whereIn('portfolio_id', $portfolioIds)->count(),
        ];
    }

    public function generatedBuildings(ShowcaseDataset $dataset): int
    {
        return Asset::query()
            ->whereIn('portfolio_id', $dataset->portfolios()->pluck('id'))
            ->where('asset_type', 'building')
            ->where('code', 'like', "SD{$dataset->id}-B%")
            ->count();
    }

    /** @return list<int> */
    public function missingBuildingIndexes(ShowcaseDataset $dataset): array
    {
        $existingCodes = Asset::query()
            ->whereIn('portfolio_id', $dataset->portfolios()->pluck('id'))
            ->where('asset_type', 'building')
            ->pluck('code')
            ->all();
        $missing = [];

        for ($index = 0; $index < ShowcaseTargets::BUILDINGS; $index++) {
            if (! in_array($this->targets->buildingCode($dataset, $index), $existingCodes, true)) {
                $missing[] = $index;
            }
        }

        return $missing;
    }
}
