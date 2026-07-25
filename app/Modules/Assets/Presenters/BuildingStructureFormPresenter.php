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
        private readonly BuildingSetupContinuationPresenter $continuation,
        private readonly BuildingStructureInitialValuesPresenter $initialValues,
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

        $setup = $this->continuation->present($actor, $defaults);
        $data = $this->options->get(
            $actor,
            defaults: $defaults,
            activePortfoliosOnly: true,
        );
        $portfolio = Portfolio::query()
            ->whereKey($data->portfolioId)
            ->firstOrFail();
        abort_if(
            $setup['portfolio']
                && $setup['portfolio']->id !== $portfolio->id,
            404,
        );
        $activeOwners = $data->owners->where('status', 'active')->values();
        $activeManagers = $data->managers->where('status', 'active')->values();
        $ownerId = $portfolio->owner_user_id;
        $defaultOwnerId = $activeOwners->contains('id', $ownerId) ? $ownerId : null;
        $defaultManagerId = $activeManagers->contains('id', $defaultOwnerId)
            ? $defaultOwnerId
            : ($activeManagers->count() === 1 ? $activeManagers->first()?->id : null);

        return [
            'title' => $setup['title'],
            'description' => $setup['description'],
            'backHref' => $setup['backHref'],
            'backLabel' => $setup['backLabel'],
            'action' => $setup['action'],
            'submitLabel' => $setup['submitLabel'],
            'isSetup' => $setup['isSetup'],
            'options' => [
                'portfolios' => $this->optionPresenter->portfolios(
                    $setup['portfolio']
                        ? array_values(array_filter(
                            $data->portfolios,
                            fn (array $option): bool => $option['id'] === $setup['portfolio']->id,
                        ))
                        : $data->portfolios,
                ),
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
            'initialValues' => $this->initialValues->present(
                $data,
                $portfolio,
                $defaultOwnerId,
                $defaultManagerId,
            ),
            'limits' => [
                'floors' => BuildingStructurePlan::MAX_FLOORS,
                'unitsPerFloor' => BuildingStructurePlan::MAX_UNITS_PER_FLOOR,
                'records' => BuildingStructurePlan::MAX_RECORDS,
            ],
        ];
    }
}
