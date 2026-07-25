<?php

namespace App\Modules\SystemReadiness\Presenters;

use App\Models\Portfolio;

final class PortfolioReadinessActionPresenter
{
    /** @return array{href: string, label: string} */
    public function portfolio(Portfolio $portfolio, bool $ready): array
    {
        return $this->action(
            $ready ? route('portfolios.show', $portfolio) : route('portfolios.edit', $portfolio),
            $ready ? 'view_portfolio' : 'configure_portfolio',
        );
    }

    /** @return array{href: string, label: string} */
    public function owner(Portfolio $portfolio, bool $ready): array
    {
        if ($portfolio->owner) {
            return $this->action(
                $ready ? route('users.show', $portfolio->owner) : route('users.edit', $portfolio->owner),
                $ready ? 'open_owner' : 'configure_owner',
            );
        }

        return $this->action(
            route('users.create', ['portfolio_id' => $portfolio->id, 'role' => 'owner']),
            'create_owner',
        );
    }

    /** @return array{href: string, label: string} */
    public function manager(Portfolio $portfolio, bool $ready): array
    {
        return $this->action(
            $ready
                ? route('users.index', ['portfolio_id' => $portfolio->id, 'role' => 'property_manager'])
                : route('users.create', ['portfolio_id' => $portfolio->id, 'role' => 'property_manager']),
            $ready ? 'review_team' : 'create_manager',
        );
    }

    /** @return array{href: string, label: string} */
    public function tenant(Portfolio $portfolio, bool $ready): array
    {
        return $this->action(
            $ready
                ? route('tenants.index', ['portfolio_id' => $portfolio->id])
                : route('tenants.create', ['portfolio_id' => $portfolio->id, 'next' => 'lease']),
            $ready ? 'review_tenants' : 'onboard_tenant',
        );
    }

    /** @return array{href: string, label: string} */
    public function property(Portfolio $portfolio, bool $ready): array
    {
        return $this->action(
            $ready
                ? route('assets.index', ['portfolio_id' => $portfolio->id])
                : route('assets.create', ['portfolio_id' => $portfolio->id]),
            $ready ? 'review_properties' : 'create_property',
        );
    }

    /** @return array{href: string, label: string} */
    public function assignments(Portfolio $portfolio): array
    {
        return $this->action(
            route('assets.index', ['portfolio_id' => $portfolio->id]),
            'review_assignments',
        );
    }

    /** @return array{href: string, label: string} */
    public function leases(Portfolio $portfolio, bool $hasLeases): array
    {
        return $this->action(
            $hasLeases
                ? route('leases.index', ['portfolio_id' => $portfolio->id])
                : route('tenants.create', ['portfolio_id' => $portfolio->id, 'next' => 'lease']),
            $hasLeases ? 'review_leases' : 'onboard_tenant',
        );
    }

    /** @return array{href: string, label: string} */
    public function dataSource(Portfolio $portfolio): array
    {
        return $this->action(
            $portfolio->is_showcase ? route('portfolios.create') : route('portfolios.show', $portfolio),
            $portfolio->is_showcase ? 'create_live_portfolio' : 'view_portfolio',
        );
    }

    /** @return array{href: string, label: string} */
    private function action(string $href, string $key): array
    {
        return [
            'href' => $href,
            'label' => trans("app.readiness.actions.{$key}"),
        ];
    }
}
