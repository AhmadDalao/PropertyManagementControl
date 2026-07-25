<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioSetupContinuation;

final class BuildingSetupContinuationPresenter
{
    public function __construct(
        private readonly PortfolioSetupContinuation $continuation,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array{
     *     portfolio:?Portfolio,
     *     isSetup:bool,
     *     title:string,
     *     description:string,
     *     backHref:string,
     *     backLabel:string,
     *     action:string,
     *     submitLabel:string
     * }
     */
    public function present(User $actor, array $defaults): array
    {
        $portfolio = $this->continuation->resolve(
            $actor,
            $defaults[PortfolioSetupContinuation::QUERY_KEY] ?? null,
        );

        if (! $portfolio) {
            return [
                'portfolio' => null,
                'isSetup' => false,
                'title' => trans('app.assets.builder.title'),
                'description' => trans('app.assets.builder.description'),
                'backHref' => route('assets.index'),
                'backLabel' => trans('app.assets.all_assets'),
                'action' => route('assets.structure.store'),
                'submitLabel' => trans('app.assets.builder.create_button'),
            ];
        }

        abort_unless($portfolio->status === 'active', 404);

        return [
            'portfolio' => $portfolio,
            'isSetup' => true,
            'title' => trans('app.portfolios.setup_building_title', [
                'portfolio' => $this->continuation->name($portfolio),
            ]),
            'description' => trans('app.portfolios.setup_building_description'),
            'backHref' => route('portfolios.show', $portfolio),
            'backLabel' => trans('app.portfolios.back_to_setup'),
            'action' => route(
                'assets.structure.store',
                $this->continuation->query($portfolio),
            ),
            'submitLabel' => trans('app.portfolios.setup_building_submit'),
        ];
    }
}
