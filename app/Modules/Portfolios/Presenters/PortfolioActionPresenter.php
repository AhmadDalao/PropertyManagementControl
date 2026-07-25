<?php

namespace App\Modules\Portfolios\Presenters;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioAccess;
use App\Modules\Shared\Authorization\AssignedPropertyScope;

class PortfolioActionPresenter
{
    public function __construct(
        private readonly PortfolioAccess $access,
        private readonly AssignedPropertyScope $assignments,
    ) {}

    /**
     * @param  array<string, bool>  $settings
     * @return array<int, array<string, mixed>>
     */
    public function present(Portfolio $portfolio, User $actor, array $settings): array
    {
        $actions = [];
        $canCreateRecords = $this->assignments->hasAssignments($actor);
        $canCreateAsset = $canCreateRecords
            && $portfolio->status === 'active'
            && ($settings['assets'] ?? true);
        $canCreateUser = $canCreateRecords
            && $portfolio->status === 'active'
            && ($settings['users'] ?? true);

        if ($canCreateAsset) {
            $actions[] = [
                'label' => trans('app.portfolios.create_asset'),
                'href' => route('assets.create', ['portfolio_id' => $portfolio->id]),
                'variant' => 'primary',
            ];
        }

        if ($canCreateUser) {
            $actions[] = [
                'label' => trans('app.portfolios.create_user'),
                'href' => route('users.create', ['portfolio_id' => $portfolio->id]),
                'variant' => $actions === [] ? 'primary' : 'secondary',
            ];
        }

        if ($this->access->canUpdate($actor, $portfolio)) {
            $actions[] = [
                'label' => trans('app.portfolios.edit_portfolio'),
                'href' => route('portfolios.edit', $portfolio),
                'variant' => $actions === [] ? 'primary' : 'secondary',
            ];
        }

        if ($this->access->canArchive($actor) && $portfolio->status !== 'archived') {
            $actions[] = [
                'label' => trans('app.portfolios.archive_portfolio'),
                'href' => route('portfolios.destroy', $portfolio),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.portfolios.archive_confirm', ['name' => $portfolio->name_en]),
            ];
        }

        return $actions;
    }
}
