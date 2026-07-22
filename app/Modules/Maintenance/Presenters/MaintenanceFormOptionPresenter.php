<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\Asset;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\ResourcePresenter;
use Illuminate\Support\Collection;

class MaintenanceFormOptionPresenter
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
            trim(($this->resources->localized($asset->title_en, $asset->title_ar) ?? '').' · '.$asset->code, ' ·'),
        ))->all();
    }

    /**
     * @param  Collection<int, TenantProfile>  $tenants
     * @return array<int, array{value:string,label:string}>
     */
    public function tenants(Collection $tenants): array
    {
        return $tenants->map(fn (TenantProfile $tenant): array => $this->option(
            $tenant->id,
            $tenant->user->name ?? trans('app.maintenance.tenant_number', ['id' => $tenant->id]),
        ))->all();
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<int, array{value:string,label:string}>
     */
    public function managers(Collection $users): array
    {
        return $users
            ->map(fn (User $user): array => $this->option($user->id, $user->name))
            ->prepend($this->option('', trans('app.maintenance.unassigned_label')))
            ->values()
            ->all();
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
    public function values(array $values): array
    {
        return collect($values)
            ->map(fn (string $value): array => $this->option(
                $value,
                trans("app.status.{$value}"),
            ))
            ->all();
    }

    /** @return array{value:string,label:string} */
    private function option(int|string $value, string $label): array
    {
        return ['value' => (string) $value, 'label' => $label];
    }
}
