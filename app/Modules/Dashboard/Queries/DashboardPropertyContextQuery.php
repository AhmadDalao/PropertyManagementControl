<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Support\AssetHierarchy;
use App\Modules\Dashboard\Support\DashboardPropertyContext;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DashboardPropertyContextQuery
{
    public function __construct(
        private PortfolioScope $portfolios,
        private AssetHierarchy $hierarchy,
        private AssignedPropertyScope $assignments,
    ) {}

    public function forUser(User $actor, ?int $propertyId): DashboardPropertyContext
    {
        $titleColumn = app()->isLocale('ar') ? 'title_ar' : 'title_en';
        $properties = $this->portfolios
            ->apply(Asset::query(), $actor)
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->when(
                $this->assignments->rootIds($actor) !== null,
                fn ($query) => $query->whereIn('id', $this->assignments->rootIds($actor) ?? []),
            )
            ->orderBy($titleColumn)
            ->get(['id', 'portfolio_id', 'code', 'title_en', 'title_ar']);
        $leaseableTypes = array_values($this->hierarchy->leaseableTypes());

        if ($propertyId === null) {
            return new DashboardPropertyContext(
                selected: null,
                propertyCount: $properties->count(),
                assetIds: $this->assignments->assetIds($actor) ?? [],
                leaseableTypes: $leaseableTypes,
                assignmentRestricted: $this->assignments->restricts($actor),
            );
        }

        $property = $properties->firstWhere('id', $propertyId);

        if (! $property instanceof Asset) {
            throw new NotFoundHttpException;
        }

        return new DashboardPropertyContext(
            selected: $this->option($property),
            propertyCount: $properties->count(),
            assetIds: $this->selectedAssetIds($actor, $property),
            leaseableTypes: $leaseableTypes,
            assignmentRestricted: $this->assignments->restricts($actor),
        );
    }

    /** @return list<int> */
    private function selectedAssetIds(User $actor, Asset $property): array
    {
        $ids = $this->hierarchy->descendantIdsIncluding($property);
        $assignedIds = $this->assignments->assetIds($actor);

        return array_values($assignedIds === null ? $ids : array_intersect($ids, $assignedIds));
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
