<?php

namespace App\Modules\Portfolios\Presenters;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioAccess;

class PortfolioActionPresenter
{
    public function __construct(private readonly PortfolioAccess $access) {}

    /**
     * @param  array<string, bool>  $settings
     * @return array<int, array<string, mixed>>
     */
    public function present(Portfolio $portfolio, User $actor, array $settings): array
    {
        $actions = [];

        if ($this->access->canUpdate($actor, $portfolio)) {
            $actions[] = [
                'label' => trans('app.portfolios.edit_portfolio'),
                'href' => route('portfolios.edit', $portfolio),
                'variant' => 'primary',
            ];
        }

        if ($portfolio->status === 'active' && ($settings['assets'] ?? true)) {
            $actions[] = [
                'label' => trans('app.portfolios.create_asset'),
                'href' => route('assets.create', ['portfolio_id' => $portfolio->id]),
                'variant' => 'secondary',
            ];
        }

        if ($portfolio->status === 'active' && ($settings['users'] ?? true)) {
            $actions[] = [
                'label' => trans('app.portfolios.create_user'),
                'href' => route('users.create', ['portfolio_id' => $portfolio->id]),
                'variant' => 'secondary',
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
