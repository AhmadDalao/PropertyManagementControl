<?php

namespace App\Modules\Assets\Support;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;

final readonly class AssetPropertyContext
{
    public function __construct(
        private PortfolioScope $portfolios,
        private AssetRootMap $roots,
        private AssignedPropertyScope $assignments,
    ) {}

    /**
     * @return array{
     *     0:array<int, int>,
     *     1:array<int, Asset>
     * }
     */
    public function get(User $actor, ?int $portfolioId = null): array
    {
        $assets = $this->assignments
            ->assets($this->portfolios->apply(Asset::query(), $actor), $actor)
            ->when(
                $portfolioId !== null,
                fn (Builder $query) => $query->where('portfolio_id', $portfolioId),
            )
            ->get([
                'id',
                'portfolio_id',
                'parent_id',
                'title_en',
                'title_ar',
                'code',
            ]);

        return [
            $this->roots->build($assets),
            $assets->keyBy('id')->all(),
        ];
    }
}
