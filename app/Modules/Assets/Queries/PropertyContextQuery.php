<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

final readonly class PropertyContextQuery
{
    public function __construct(
        private PortfolioScope $portfolios,
        private AssignedPropertyScope $assignments,
    ) {}

    /**
     * @return array{
     *     selected:array{
     *         id:int,
     *         code:string,
     *         title_en:string,
     *         title_ar:string,
     *         portfolio_code:string|null,
     *         portfolio_name_en:string|null,
     *         portfolio_name_ar:string|null
     *     }|null,
     *     options:list<array{
     *         id:int,
     *         code:string,
     *         title_en:string,
     *         title_ar:string,
     *         portfolio_code:string|null,
     *         portfolio_name_en:string|null,
     *         portfolio_name_ar:string|null
     *     }>,
     *     assignment_restricted:bool
     * }|null
     */
    public function present(User $actor, ?int $selectedId): ?array
    {
        if (! $actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])) {
            return null;
        }

        $options = $this->options($actor);

        return [
            'selected' => $selectedId === null
                ? null
                : Arr::first(
                    $options,
                    fn (array $option): bool => $option['id'] === $selectedId,
                ),
            'options' => $options,
            'assignment_restricted' => $this->assignments->restricts($actor),
        ];
    }

    public function allows(User $actor, int $propertyId): bool
    {
        if (! $actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])) {
            return false;
        }

        return $this->query($actor)->whereKey($propertyId)->exists();
    }

    /**
     * @return list<array{
     *     id:int,
     *     code:string,
     *     title_en:string,
     *     title_ar:string,
     *     portfolio_code:string|null,
     *     portfolio_name_en:string|null,
     *     portfolio_name_ar:string|null
     * }>
     */
    private function options(User $actor): array
    {
        $titleColumn = app()->isLocale('ar') ? 'title_ar' : 'title_en';

        return array_values(
            $this->query($actor)
                ->with('portfolio:id,code,name_en,name_ar')
                ->orderBy($titleColumn)
                ->orderBy('code')
                ->get(['id', 'portfolio_id', 'code', 'title_en', 'title_ar'])
                ->map(fn (Asset $property): array => [
                    'id' => $property->id,
                    'code' => $property->code,
                    'title_en' => $property->title_en,
                    'title_ar' => $property->title_ar,
                    'portfolio_code' => $property->portfolio?->code,
                    'portfolio_name_en' => $property->portfolio?->name_en,
                    'portfolio_name_ar' => $property->portfolio?->name_ar,
                ])
                ->all(),
        );
    }

    /** @return Builder<Asset> */
    private function query(User $actor): Builder
    {
        return $this->portfolios
            ->apply(Asset::query(), $actor)
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->whereHas('portfolio', fn (Builder $query) => $query->where('status', 'active'))
            ->when(
                $this->assignments->rootIds($actor) !== null,
                fn (Builder $query) => $query->whereIn(
                    'id',
                    $this->assignments->rootIds($actor) ?? [],
                ),
            );
    }
}
