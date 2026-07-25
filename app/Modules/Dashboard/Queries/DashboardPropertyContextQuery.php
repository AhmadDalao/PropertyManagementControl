<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Support\AssetHierarchy;
use App\Modules\Dashboard\Support\DashboardPropertyContext;
use App\Modules\Shared\PortfolioScope;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DashboardPropertyContextQuery
{
    public function __construct(
        private PortfolioScope $portfolios,
        private AssetHierarchy $hierarchy,
    ) {}

    public function forUser(User $actor, ?int $propertyId): DashboardPropertyContext
    {
        $titleColumn = app()->isLocale('ar') ? 'title_ar' : 'title_en';
        $properties = $this->portfolios
            ->apply(Asset::query(), $actor)
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy($titleColumn)
            ->get(['id', 'portfolio_id', 'code', 'title_en', 'title_ar']);
        $options = array_values(
            $properties
                ->map(fn (Asset $property): array => $this->option($property))
                ->all(),
        );
        $leaseableTypes = array_values($this->hierarchy->leaseableTypes());

        if ($propertyId === null) {
            return new DashboardPropertyContext(
                selected: null,
                options: $options,
                assetIds: [],
                leaseableTypes: $leaseableTypes,
            );
        }

        $property = $properties->firstWhere('id', $propertyId);

        if (! $property instanceof Asset) {
            throw new NotFoundHttpException;
        }

        return new DashboardPropertyContext(
            selected: $this->option($property),
            options: $options,
            assetIds: array_values(
                $this->hierarchy->descendantIdsIncluding($property),
            ),
            leaseableTypes: $leaseableTypes,
        );
    }

    /** @return array{id:int,code:string,title_en:string,title_ar:?string} */
    private function option(Asset $property): array
    {
        return [
            'id' => $property->id,
            'code' => $property->code,
            'title_en' => $property->title_en,
            'title_ar' => $property->title_ar,
        ];
    }
}
