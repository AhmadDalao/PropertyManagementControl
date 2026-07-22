<?php

namespace App\Modules\ShowcaseData\Actions;

use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Models\User;
use App\Modules\ShowcaseData\Generators\ShowcaseBuildingBuilder;
use App\Modules\ShowcaseData\Generators\ShowcaseDocumentBuilder;
use App\Modules\ShowcaseData\Generators\ShowcaseExpenseBuilder;
use App\Modules\ShowcaseData\Generators\ShowcaseLeaseBuilder;
use App\Modules\ShowcaseData\Generators\ShowcaseMaintenanceBuilder;
use App\Modules\ShowcaseData\Generators\ShowcasePaymentBuilder;
use App\Modules\ShowcaseData\Generators\ShowcaseUnitBuilder;
use App\Modules\ShowcaseData\Support\ShowcaseTargets;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BuildShowcaseProperty
{
    public function __construct(
        private readonly ShowcaseTargets $targets,
        private readonly ShowcaseBuildingBuilder $buildings,
        private readonly ShowcaseUnitBuilder $units,
        private readonly ShowcaseLeaseBuilder $leases,
        private readonly ShowcasePaymentBuilder $payments,
        private readonly ShowcaseMaintenanceBuilder $maintenance,
        private readonly ShowcaseExpenseBuilder $expenses,
        private readonly ShowcaseDocumentBuilder $documents,
        private readonly RefreshShowcaseDataset $progress,
    ) {}

    public function handle(int $datasetId, int $buildingIndex): void
    {
        if (! $this->targets->validBuildingIndex($buildingIndex)) {
            return;
        }

        $built = DB::transaction(function () use ($datasetId, $buildingIndex): bool {
            $dataset = ShowcaseDataset::query()->lockForUpdate()->findOrFail($datasetId);

            if (in_array($dataset->status, ['complete', 'purging', 'purged'], true)) {
                return false;
            }

            $portfolio = $this->portfolio($dataset, $buildingIndex);
            $owner = User::query()->findOrFail((int) $portfolio->owner_user_id);
            $manager = $this->manager($dataset, $portfolio, $buildingIndex);
            $building = $this->buildings->build($dataset, $portfolio, $owner, $manager, $buildingIndex);
            $units = $this->units->build($dataset, $portfolio, $building, $buildingIndex);
            $leases = $this->leases->build($dataset, $portfolio, $manager, $units, $buildingIndex);
            $this->payments->build($dataset, $portfolio, $manager, $leases, $buildingIndex);
            $maintenance = $this->maintenance->build($portfolio, $manager, $leases, $buildingIndex);
            $this->expenses->build($portfolio, $manager, $building, $maintenance, $buildingIndex);
            $this->documents->build($dataset, $portfolio, $manager, $leases);

            return true;
        }, 3);

        if ($built) {
            $this->progress->handle($datasetId);
        }
    }

    private function portfolio(ShowcaseDataset $dataset, int $buildingIndex): Portfolio
    {
        return Portfolio::query()
            ->where('showcase_dataset_id', $dataset->id)
            ->where('code', $this->targets->portfolioCode(
                $dataset,
                $this->targets->portfolioIndex($buildingIndex),
            ))
            ->firstOrFail();
    }

    private function manager(ShowcaseDataset $dataset, Portfolio $portfolio, int $buildingIndex): User
    {
        $manager = User::query()
            ->where('showcase_dataset_id', $dataset->id)
            ->where('portfolio_id', $portfolio->id)
            ->role('property_manager')
            ->orderBy('id')
            ->offset($buildingIndex % ShowcaseTargets::MANAGERS_PER_PORTFOLIO)
            ->first();

        if (! $manager) {
            throw new RuntimeException("No showcase manager for building {$buildingIndex}.");
        }

        return $manager;
    }
}
