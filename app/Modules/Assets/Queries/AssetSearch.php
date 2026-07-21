<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Support\AssetAccess;
use App\Modules\Search\Presenters\SearchResultPresenter;
use App\Modules\Search\Support\ModuleSearchSource;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;

class AssetSearch extends ModuleSearchSource
{
    public function __construct(
        private readonly AssetAccess $access,
        private readonly TableQuery $tables,
        private readonly SearchResultPresenter $results,
    ) {}

    public function results(User $actor, string $query): array
    {
        if (! $this->isManager($actor) || ! $this->moduleEnabled($actor, 'assets')) {
            return [];
        }

        $assets = $this->query($actor);
        $this->applySearch($assets, $query);

        return $assets
            ->limit(6)
            ->get()
            ->map(fn (Asset $asset): array => $this->results->result(
                trans('app.nav.assets'),
                $this->results->localized($asset->title_en, $asset->title_ar),
                $this->subtitle($asset),
                $this->results->status($asset->occupancy_status),
                route('assets.show', $asset),
            ))
            ->all();
    }

    public function directUrl(User $actor, string $query): ?string
    {
        if (! $this->isManager($actor) || ! $this->moduleEnabled($actor, 'assets')) {
            return null;
        }

        $asset = $this->query($actor)
            ->where(fn (Builder $assets) => $assets
                ->where('code', $query)
                ->orWhere('meta_json->map->land_number', $query))
            ->first();

        return $asset ? route('assets.show', $asset) : null;
    }

    /** @return Builder<Asset> */
    private function query(User $actor): Builder
    {
        return $this->access->directoryScope(Asset::query(), $actor);
    }

    /** @param Builder<Asset> $assets */
    private function applySearch(Builder $assets, string $query): void
    {
        $this->tables->search($assets, $query, [
            'title_en',
            'title_ar',
            'code',
            'address',
            'address_ar',
            'level_label',
            'unit_label',
            fn (Builder $assets, string $term, string $like) => $assets
                ->orWhere('meta_json->map->zone', 'like', $like)
                ->orWhere('meta_json->map->zone_en', 'like', $like)
                ->orWhere('meta_json->map->zone_ar', 'like', $like)
                ->orWhere('meta_json->map->land_number', 'like', $like),
            fn (Builder $assets, string $term, string $like) => $assets->orWhereHas(
                'parent',
                fn (Builder $parents) => $parents
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like),
            ),
            fn (Builder $assets, string $term, string $like) => $assets->orWhereHas(
                'stakeholders.user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like),
            ),
        ]);
    }

    private function subtitle(Asset $asset): string
    {
        $metadata = $asset->getAttribute('meta_json');
        $map = is_array($metadata) && is_array($metadata['map'] ?? null)
            ? $metadata['map']
            : [];
        $zone = app()->isLocale('ar')
            ? ($map['zone_ar'] ?? $map['zone_en'] ?? $map['zone'] ?? null)
            : ($map['zone_en'] ?? $map['zone_ar'] ?? $map['zone'] ?? null);

        return collect([
            $asset->code,
            $map['land_number'] ?? null,
            $zone,
            $asset->asset_type,
        ])->filter()->join(' · ');
    }
}
