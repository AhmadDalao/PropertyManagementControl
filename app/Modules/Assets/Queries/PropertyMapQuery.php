<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\PropertyMapPresenter;
use App\Modules\Assets\Support\AssetAccess;
use App\Modules\Shared\PortfolioScope;

class PropertyMapQuery
{
    public function __construct(
        private readonly AssetAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly PropertyMapPresenter $propertyMap,
    ) {}

    /** @return array<string, mixed> */
    public function handle(User $actor, ?int $portfolioId): array
    {
        $assets = $this->access->directoryScope(Asset::query(), $actor);

        if ($portfolioId !== null) {
            $this->portfolios->ensureAccess($actor, $portfolioId);
            $assets->where('portfolio_id', $portfolioId);
        }

        return [
            'propertyMap' => $this->propertyMap->forQuery($assets),
            'portfolioOptions' => $this->portfolios->options($actor),
            'filters' => ['portfolio_id' => $portfolioId],
        ];
    }
}
