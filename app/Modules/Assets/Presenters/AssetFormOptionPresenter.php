<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Shared\ResourcePresenter;
use Illuminate\Support\Collection;

class AssetFormOptionPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /**
     * @param  Collection<int, Asset>  $assets
     * @return array<int, array{value:string,label:string}>
     */
    public function assets(Collection $assets): array
    {
        return $assets->map(fn (Asset $asset): array => $this->option(
            $asset->id,
            trim(($this->resources->localized($asset->title_en, $asset->title_ar) ?? '')
                .' · '.$asset->code.' · '.trans("app.assets.types.{$asset->asset_type}"), ' ·'),
        ))->all();
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<int, array{value:string,label:string}>
     */
    public function users(Collection $users): array
    {
        return $users->map(fn (User $user): array => $this->option(
            $user->id,
            $user->status === 'active'
                ? $user->name
                : trans('app.assets.inactive_user', ['name' => $user->name]),
        ))->all();
    }

    /**
     * @param  array<int, array{id:int,name:string}>  $portfolios
     * @return array<int, array{value:string,label:string}>
     */
    public function portfolios(array $portfolios): array
    {
        return collect($portfolios)
            ->map(fn (array $portfolio): array => $this->option($portfolio['id'], $portfolio['name']))
            ->all();
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, array{value:string,label:string}>
     */
    public function values(array $values, string $group): array
    {
        return collect($values)->map(fn (string $value): array => $this->option(
            $value,
            trans("app.{$group}.{$value}"),
        ))->all();
    }

    /** @return array{value:string,label:string} */
    public function option(int|string $value, string $label): array
    {
        return ['value' => (string) $value, 'label' => $label];
    }
}
