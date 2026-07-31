<?php

namespace App\Modules\Dashboard\Presenters;

use App\Models\User;
use App\Modules\Dashboard\Queries\DashboardSetupTargetQuery;
use App\Modules\Portfolios\Presenters\PortfolioSetupStepsPresenter;
use App\Modules\Portfolios\Support\PortfolioModules;

class SetupChecklistPresenter
{
    public function __construct(
        private readonly DashboardSetupTargetQuery $target,
        private readonly PortfolioSetupStepsPresenter $steps,
        private readonly ManagerSetupChecklistPresenter $manager,
    ) {}

    /**
     * @param  array<string, int|float>  $stats
     * @return array{
     *     target:?array<string,mixed>,
     *     items:list<array{key:string,label:string,description:string,done:bool,href:string,icon:string}>
     * }
     */
    public function present(User $user, array $stats): array
    {
        $portfolio = $this->target->forUser($user);

        if ($portfolio) {
            $steps = $this->steps->present(
                $portfolio,
                $user,
                PortfolioModules::normalize($portfolio->module_settings),
            );
            $completed = count(array_filter($steps, fn (array $step): bool => $step['done']));
            $next = collect($steps)->first(fn (array $step): bool => ! $step['done']);
            $name = app()->isLocale('ar')
                ? ($portfolio->name_ar ?: $portfolio->name_en)
                : ($portfolio->name_en ?: $portfolio->name_ar);

            return [
                'target' => [
                    'id' => $portfolio->id,
                    'code' => $portfolio->code,
                    'name' => $name,
                    'href' => route('portfolios.show', $portfolio),
                    'completed' => $completed,
                    'total' => count($steps),
                    'next' => $next ? [
                        'label' => $next['title'],
                        'description' => $next['description'],
                        'href' => $next['href'] ?? route('portfolios.show', $portfolio),
                        'action_label' => $next['actionLabel'] ?? trans('app.actions.next_step'),
                        'icon' => $next['icon'],
                    ] : null,
                ],
                'items' => array_values(array_map(
                    fn (array $step): array => $this->item(
                        $step['key'],
                        $step['title'],
                        $step['description'],
                        $step['done'],
                        $step['href'] ?? route('portfolios.show', $portfolio),
                        $step['icon'],
                    ),
                    $steps,
                )),
            ];
        }

        if ($user->hasRole('superadmin')) {
            return [
                'target' => null,
                'items' => [$this->item(
                    'live_portfolio',
                    trans('app.readiness.create_live_portfolio'),
                    trans('app.readiness.live_portfolio_description'),
                    false,
                    route('portfolios.create'),
                    'bi-buildings',
                )],
            ];
        }

        return ['target' => null, 'items' => $this->manager->present($user, $stats)];
    }

    /** @return array{key:string,label:string,description:string,done:bool,href:string,icon:string} */
    private function item(
        string $key,
        string $label,
        string $description,
        bool $done,
        string $href,
        string $icon,
    ): array {
        return compact('key', 'label', 'description', 'done', 'href', 'icon');
    }
}
