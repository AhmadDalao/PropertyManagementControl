<?php

namespace App\Modules\SystemReadiness\Presenters;

use App\Models\User;
use App\Modules\SystemReadiness\Queries\PortfolioReadinessQuery;
use App\Modules\SystemReadiness\Queries\ReadinessConfirmationQuery;
use App\Modules\SystemReadiness\Queries\SystemHealthQuery;
use App\Modules\SystemReadiness\Support\MailReadiness;
use App\Modules\SystemReadiness\Support\ReadinessAccess;

final class ReadinessPagePresenter
{
    public function __construct(
        private readonly ReadinessAccess $access,
        private readonly SystemHealthQuery $system,
        private readonly ReadinessConfirmationQuery $confirmations,
        private readonly PortfolioReadinessQuery $portfolios,
        private readonly MailReadiness $mail,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, ?int $portfolioId): array
    {
        $this->access->ensureSuperadmin($actor);
        $systemChecks = $this->system->checks();
        $systemConfirmations = $this->confirmations->system();
        $portfolioData = $this->portfolios->handle($portfolioId);
        $selected = $portfolioData['selected'];
        $portfolioConfirmations = $selected
            ? $this->confirmations->portfolio((int) $selected['portfolio']['id'])
            : [];
        $statuses = [
            ...array_column($systemChecks, 'status'),
            ...array_map(fn (array $check): string => $check['is_confirmed'] ? 'ready' : 'attention', $systemConfirmations),
            ...array_column($selected['checks'] ?? [], 'status'),
            ...array_map(fn (array $check): string => $check['is_confirmed'] ? 'ready' : 'attention', $portfolioConfirmations),
        ];

        return [
            'checkedAt' => now()->toIso8601String(),
            'summary' => [
                'total' => count($statuses),
                'ready' => count(array_filter($statuses, fn (string $status): bool => $status === 'ready')),
                'attention' => count(array_filter($statuses, fn (string $status): bool => $status === 'attention')),
                'blocked' => count(array_filter($statuses, fn (string $status): bool => $status === 'blocked')),
            ],
            'systemChecks' => $systemChecks,
            'systemConfirmations' => $systemConfirmations,
            'portfolioOptions' => $portfolioData['options'],
            'portfolioReadiness' => $selected,
            'portfolioConfirmations' => $portfolioConfirmations,
            'mailTest' => [
                'enabled' => $this->mail->configured(),
                'target' => $actor->email,
            ],
        ];
    }
}
