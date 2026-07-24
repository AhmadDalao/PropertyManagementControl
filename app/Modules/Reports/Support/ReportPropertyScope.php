<?php

namespace App\Modules\Reports\Support;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Support\AssetHierarchy;
use App\Modules\Shared\PortfolioScope;

final readonly class ReportPropertyScope
{
    public function __construct(
        private PortfolioScope $portfolios,
        private AssetHierarchy $hierarchy,
    ) {}

    /**
     * @return array<int, array{id:int,portfolio_id:int,name:string}>
     */
    public function options(User $actor): array
    {
        $titleColumn = app()->isLocale('ar') ? 'title_ar' : 'title_en';

        return $this->portfolios
            ->apply(Asset::query(), $actor)
            ->whereNull('parent_id')
            ->orderBy('portfolio_id')
            ->orderBy($titleColumn)
            ->get(['id', 'portfolio_id', 'title_en', 'title_ar', 'code'])
            ->map(fn (Asset $asset): array => [
                'id' => $asset->id,
                'portfolio_id' => $asset->portfolio_id,
                'name' => implode(' · ', array_filter([
                    $this->portfolios->localized($asset->title_en, $asset->title_ar),
                    $asset->code,
                ])),
            ])
            ->all();
    }

    /**
     * @return array<int, int>|null
     */
    public function assetIds(
        User $actor,
        ?int $portfolioId,
        ?int $propertyId,
    ): ?array {
        $property = $this->selected($actor, $portfolioId, $propertyId);

        if (! $property) {
            return null;
        }

        return $this->hierarchy->descendantIdsIncluding($property);
    }

    public function label(User $actor, ?int $portfolioId, ?int $propertyId): ?string
    {
        $property = $this->selected($actor, $portfolioId, $propertyId);

        return $property
            ? implode(' · ', array_filter([
                $this->portfolios->localized($property->title_en, $property->title_ar),
                $property->code,
            ]))
            : null;
    }

    private function selected(User $actor, ?int $portfolioId, ?int $propertyId): ?Asset
    {
        if ($propertyId === null) {
            return null;
        }

        $property = $this->portfolios
            ->apply(Asset::query(), $actor)
            ->whereKey($propertyId)
            ->whereNull('parent_id')
            ->when(
                $portfolioId !== null,
                fn ($query) => $query->where('portfolio_id', $portfolioId),
            )
            ->first();

        abort_unless(
            $property instanceof Asset,
            403,
            trans('app.errors.property_report_access_denied'),
        );

        return $property;
    }

    /** @return array<int, string> */
    public function leaseableTypes(): array
    {
        return $this->hierarchy->leaseableTypes();
    }
}
