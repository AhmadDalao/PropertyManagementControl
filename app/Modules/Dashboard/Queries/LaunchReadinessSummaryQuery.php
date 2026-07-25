<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Asset;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\SystemReadiness\Queries\ReadinessConfirmationQuery;
use App\Modules\SystemReadiness\Queries\SystemHealthQuery;

final class LaunchReadinessSummaryQuery
{
    public function __construct(
        private readonly SystemHealthQuery $systemHealth,
        private readonly ReadinessConfirmationQuery $confirmations,
    ) {}

    /** @return array<string, int|string>|null */
    public function forUser(User $user): ?array
    {
        if (! $user->hasRole('superadmin')) {
            return null;
        }

        $statuses = array_column($this->systemHealth->checks(), 'status');
        $evidenceRemaining = count(array_filter(
            $this->confirmations->system(),
            fn (array $check): bool => ! $check['is_confirmed'],
        ));
        $ready = $this->count($statuses, 'ready');
        $attention = $this->count($statuses, 'attention');
        $blocked = $this->count($statuses, 'blocked');
        $showcasePortfolioIds = Portfolio::query()
            ->whereNotNull('showcase_dataset_id')
            ->select('id');

        return [
            'status' => $blocked > 0 ? 'blocked' : (($attention + $evidenceRemaining) > 0 ? 'attention' : 'ready'),
            'automatic_ready' => $ready,
            'automatic_attention' => $attention,
            'automatic_blocked' => $blocked,
            'evidence_remaining' => $evidenceRemaining,
            'operational_portfolios' => Portfolio::query()
                ->whereNull('showcase_dataset_id')
                ->count(),
            'showcase_portfolios' => (clone $showcasePortfolioIds)->count(),
            'showcase_assets' => Asset::query()
                ->whereIn('portfolio_id', $showcasePortfolioIds)
                ->count(),
            'showcase_users' => User::query()
                ->whereNotNull('showcase_dataset_id')
                ->count(),
        ];
    }

    /** @param list<string> $statuses */
    private function count(array $statuses, string $expected): int
    {
        return count(array_filter(
            $statuses,
            fn (string $status): bool => $status === $expected,
        ));
    }
}
