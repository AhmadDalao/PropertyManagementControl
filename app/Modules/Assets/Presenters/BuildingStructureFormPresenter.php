<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Assets\Queries\AssetFormOptionsQuery;
use App\Modules\Assets\Support\AssetOptions;
use App\Modules\Assets\Support\BuildingStructurePlan;

final class BuildingStructureFormPresenter
{
    public function __construct(
        private readonly AssetFormOptionsQuery $options,
        private readonly AssetFormOptionPresenter $optionPresenter,
    ) {}

    /** @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, array $defaults = []): array
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner']),
            403,
            trans('app.errors.section_access_denied'),
        );

        $data = $this->options->get(
            $actor,
            defaults: $defaults,
            activePortfoliosOnly: true,
        );
        $portfolio = Portfolio::query()
            ->whereKey($data->portfolioId)
            ->first();
        $activeOwners = $data->owners->where('status', 'active')->values();
        $activeManagers = $data->managers->where('status', 'active')->values();
        $ownerId = $portfolio?->owner_user_id;
        $defaultOwnerId = $activeOwners->contains('id', $ownerId) ? $ownerId : null;
        $defaultManagerId = $activeManagers->contains('id', $defaultOwnerId)
            ? $defaultOwnerId
            : ($activeManagers->count() === 1 ? $activeManagers->first()?->id : null);

        return [
            'title' => trans('app.assets.builder.title'),
            'description' => trans('app.assets.builder.description'),
            'backHref' => route('assets.index'),
            'action' => route('assets.structure.store'),
            'options' => [
                'portfolios' => $this->optionPresenter->portfolios($data->portfolios),
                'usages' => $this->optionPresenter->values(AssetOptions::USAGES, 'assets.usages'),
                'unitTypes' => $this->optionPresenter->values(['unit', 'space'], 'assets.types'),
                'owners' => [
                    $this->optionPresenter->option('', trans('app.assets.builder.choose_owner')),
                    ...$this->optionPresenter->users($activeOwners),
                ],
                'managers' => [
                    $this->optionPresenter->option('', trans('app.assets.builder.choose_manager')),
                    ...$this->optionPresenter->users($activeManagers),
                ],
            ],
            'initialValues' => [
                'portfolio_id' => (string) $data->portfolioId,
                'title_en' => '',
                'title_ar' => '',
                'code_prefix' => '',
                'usage_type' => 'residential',
                'floor_count' => 4,
                'units_per_floor' => 4,
                'floor_start' => 1,
                'unit_type' => 'unit',
                'primary_owner_user_id' => (string) ($defaultOwnerId ?? ''),
                'primary_manager_user_id' => (string) ($defaultManagerId ?? ''),
                'valuation_amount' => '',
                'currency' => $portfolio->default_currency ?? 'SAR',
                'area' => '',
                'unit_area' => '',
                'address' => $portfolio->address ?? '',
                'address_ar' => $portfolio->address_ar ?? '',
                'map_zone_en' => '',
                'map_zone_ar' => '',
                'land_number' => '',
                'latitude' => '',
                'longitude' => '',
            ],
            'limits' => [
                'floors' => BuildingStructurePlan::MAX_FLOORS,
                'unitsPerFloor' => BuildingStructurePlan::MAX_UNITS_PER_FLOOR,
                'records' => BuildingStructurePlan::MAX_RECORDS,
            ],
        ];
    }
}
