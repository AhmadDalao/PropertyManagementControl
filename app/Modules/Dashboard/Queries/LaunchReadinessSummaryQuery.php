<?php

namespace App\Modules\Dashboard\Queries;

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

        return [
            'status' => $blocked > 0 ? 'blocked' : (($attention + $evidenceRemaining) > 0 ? 'attention' : 'ready'),
            'automatic_ready' => $ready,
            'automatic_attention' => $attention,
            'automatic_blocked' => $blocked,
            'evidence_remaining' => $evidenceRemaining,
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
