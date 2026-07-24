<?php

namespace App\Modules\Assets\Presenters;

use App\Modules\Assets\Data\AssetDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;

final readonly class AssetRelatedPresenter
{
    public function __construct(
        private AssetStructureRelatedPresenter $structure,
        private AssetLeaseRelatedPresenter $leases,
        private AssetServiceRelatedPresenter $service,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function present(AssetDetailData $data): array
    {
        return [
            ...$this->structure->present($data),
            ...(PortfolioModules::enabledForUser($data->actor, 'leases')
                ? $this->leases->present($data)
                : []),
            ...$this->service->present($data),
        ];
    }
}
