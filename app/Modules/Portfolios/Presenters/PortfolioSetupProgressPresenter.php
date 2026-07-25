<?php

namespace App\Modules\Portfolios\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Data\PortfolioDetailData;

final class PortfolioSetupProgressPresenter
{
    public function __construct(private readonly PortfolioSetupStepsPresenter $steps) {}

    /** @return array<string, mixed>|null */
    public function present(PortfolioDetailData $data, User $actor): ?array
    {
        $portfolio = $data->portfolio;

        if ($portfolio->is_showcase || ! $actor->hasAnyRole(['superadmin', 'owner'])) {
            return null;
        }

        $steps = $this->steps->present(
            $portfolio,
            $actor,
            $data->settings,
        );
        $completed = count(array_filter($steps, fn (array $step): bool => $step['done']));
        $currentAssigned = false;

        return [
            'eyebrow' => trans('app.portfolios.setup_eyebrow'),
            'title' => trans('app.portfolios.setup_title'),
            'description' => trans('app.portfolios.setup_description'),
            'summary' => trans('app.portfolios.setup_summary', [
                'completed' => $completed,
                'total' => count($steps),
            ]),
            'completed' => $completed,
            'total' => count($steps),
            'steps' => array_map(function (array $step) use (&$currentAssigned): array {
                if ($step['done']) {
                    $state = 'complete';
                } elseif (! $currentAssigned) {
                    $state = 'current';
                    $currentAssigned = true;
                } else {
                    $state = 'pending';
                }

                unset($step['done']);

                return ['state' => $state, ...$step];
            }, $steps),
        ];
    }
}
