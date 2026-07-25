<?php

namespace App\Modules\Assets\Support;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;

final readonly class PropertyScope
{
    public function __construct(
        private PortfolioScope $portfolios,
        private AssetHierarchy $hierarchy,
        private AssignedPropertyScope $assignments,
    ) {}

    /**
     * @return array<int, array{id:int,portfolio_id:int,name:string,portfolio_name:string|null}>
     */
    public function options(User $actor): array
    {
        $titleColumn = app()->isLocale('ar') ? 'title_ar' : 'title_en';

        return $this->portfolios
            ->apply(Asset::query(), $actor)
            ->whereNull('parent_id')
            ->when(
                $this->assignments->rootIds($actor) !== null,
                fn ($query) => $query->whereIn('id', $this->assignments->rootIds($actor) ?? []),
            )
            ->with('portfolio:id,name_en,name_ar')
            ->orderBy('portfolio_id')
            ->orderBy($titleColumn)
            ->get(['id', 'portfolio_id', 'title_en', 'title_ar', 'code'])
            ->map(function (Asset $asset) use ($actor): array {
                $portfolioName = $this->portfolios->localized(
                    $asset->portfolio?->name_en,
                    $asset->portfolio?->name_ar,
                );
                $propertyName = implode(' · ', array_filter([
                    $this->portfolios->localized($asset->title_en, $asset->title_ar),
                    $asset->code,
                ]));

                return [
                    'id' => $asset->id,
                    'portfolio_id' => $asset->portfolio_id,
                    'name' => $actor->hasRole('superadmin') && $portfolioName
                        ? $portfolioName.' · '.$propertyName
                        : $propertyName,
                    'portfolio_name' => $portfolioName,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, int>|null
     */
    public function assetIds(User $actor, ?int $portfolioId, mixed $propertyId): ?array
    {
        $property = $this->selected($actor, $portfolioId, $propertyId);

        if (! $property) {
            return null;
        }

        $ids = $this->hierarchy->descendantIdsIncluding($property);
        $assignedIds = $this->assignments->assetIds($actor);

        return $assignedIds === null
            ? $ids
            : array_values(array_intersect($ids, $assignedIds));
    }

    public function label(User $actor, ?int $portfolioId, mixed $propertyId): ?string
    {
        $property = $this->selected($actor, $portfolioId, $propertyId);

        return $property
            ? implode(' · ', array_filter([
                $this->portfolios->localized($property->title_en, $property->title_ar),
                $property->code,
            ]))
            : null;
    }

    /** @return array<int, string> */
    public function leaseableTypes(): array
    {
        return $this->hierarchy->leaseableTypes();
    }

    private function selected(User $actor, ?int $portfolioId, mixed $propertyId): ?Asset
    {
        $propertyId = $this->id($propertyId);

        if ($propertyId === null) {
            return null;
        }

        $property = $this->portfolios
            ->apply(Asset::query(), $actor)
            ->whereKey($propertyId)
            ->whereNull('parent_id')
            ->when(
                $this->assignments->rootIds($actor) !== null,
                fn ($query) => $query->whereIn('id', $this->assignments->rootIds($actor) ?? []),
            )
            ->when(
                $portfolioId !== null,
                fn ($query) => $query->where('portfolio_id', $portfolioId),
            )
            ->first();

        abort_unless(
            $property instanceof Asset,
            403,
            trans('app.errors.property_filter_access_denied'),
        );

        return $property;
    }

    private function id(mixed $value): ?int
    {
        if (! is_scalar($value) || in_array($value, ['', 'all'], true)) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $id === false ? null : (int) $id;
    }
}
