<?php

namespace App\Modules\Portfolios\Presenters;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Queries\PortfolioSetupQuery;
use App\Modules\Portfolios\Support\PortfolioSetupContinuation;

final class PortfolioSetupStepsPresenter
{
    public function __construct(
        private readonly PortfolioSetupQuery $setup,
        private readonly PortfolioSetupContinuation $continuation,
    ) {}

    /**
     * @param  array<string, bool>  $settings
     * @return array<int, array<string, mixed>>
     */
    public function present(Portfolio $portfolio, User $actor, array $settings): array
    {
        $ready = $this->setup->handle($portfolio);
        $portfolioEdit = route('portfolios.edit', $portfolio);

        return [
            $this->step(
                'portfolio',
                $ready['portfolio'],
                $ready['portfolio'] ? route('portfolios.show', $portfolio) : $portfolioEdit,
                $ready['portfolio'] ? 'view_portfolio' : 'configure_portfolio',
                'bi-building-check',
            ),
            $this->step(
                'owner',
                $ready['owner'],
                $ready['owner']
                    ? route('users.show', $portfolio->owner)
                    : ($actor->hasRole('superadmin')
                        ? route('users.create', $this->continuation->query($portfolio, [
                            'portfolio_id' => $portfolio->id,
                            'role' => 'owner',
                        ]))
                        : null),
                $ready['owner'] ? 'open_owner' : 'create_owner',
                'bi-person-badge',
            ),
            $this->step(
                'manager',
                $ready['manager'],
                $ready['manager']
                    ? route('users.index', ['portfolio_id' => $portfolio->id, 'role' => 'property_manager'])
                    : $this->createRoute(
                        $settings['users'] ?? true,
                        route('users.create', $this->continuation->query($portfolio, [
                            'portfolio_id' => $portfolio->id,
                            'role' => 'property_manager',
                        ])),
                        $portfolioEdit,
                    ),
                $ready['manager']
                    ? 'review_team'
                    : (($settings['users'] ?? true) ? 'create_manager' : 'configure_portfolio'),
                'bi-person-workspace',
            ),
            $this->step(
                'property',
                $ready['property'],
                $ready['property']
                    ? route('assets.index', ['portfolio_id' => $portfolio->id])
                    : $this->createRoute(
                        $settings['assets'] ?? true,
                        route('assets.structure.create', $this->continuation->query($portfolio, [
                            'portfolio_id' => $portfolio->id,
                        ])),
                        $portfolioEdit,
                    ),
                $ready['property']
                    ? 'review_properties'
                    : (($settings['assets'] ?? true) ? 'setup_building' : 'configure_portfolio'),
                'bi-buildings',
            ),
            $this->step(
                'tenant',
                $ready['tenant'],
                $ready['tenant']
                    ? route('tenants.index', ['portfolio_id' => $portfolio->id])
                    : $this->createRoute(
                        $settings['tenants'] ?? true,
                        route('tenants.create', ['portfolio_id' => $portfolio->id, 'next' => 'lease']),
                        $portfolioEdit,
                    ),
                $ready['tenant']
                    ? 'review_tenants'
                    : (($settings['tenants'] ?? true) ? 'onboard_tenant' : 'configure_portfolio'),
                'bi-people',
            ),
            $this->step(
                'lease',
                $ready['lease'],
                $ready['lease']
                    ? route('leases.index', ['portfolio_id' => $portfolio->id])
                    : $this->createRoute(
                        $settings['leases'] ?? true,
                        $ready['tenant']
                            ? route('leases.create', ['portfolio_id' => $portfolio->id])
                            : route('tenants.create', ['portfolio_id' => $portfolio->id, 'next' => 'lease']),
                        $portfolioEdit,
                    ),
                $ready['lease']
                    ? 'review_leases'
                    : (($settings['leases'] ?? true) ? 'create_lease' : 'configure_portfolio'),
                'bi-file-earmark-check',
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function step(string $key, bool $done, ?string $href, string $action, string $icon): array
    {
        return [
            'key' => $key,
            'title' => trans("app.portfolios.setup_steps.{$key}.title"),
            'description' => trans("app.portfolios.setup_steps.{$key}.description"),
            'done' => $done,
            'href' => $href,
            'actionLabel' => $href ? trans("app.portfolios.setup_actions.{$action}") : null,
            'icon' => $icon,
        ];
    }

    private function createRoute(bool $moduleEnabled, string $createRoute, string $portfolioEdit): string
    {
        return $moduleEnabled ? $createRoute : $portfolioEdit;
    }
}
