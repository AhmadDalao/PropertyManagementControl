<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\ResourcePresenter;

final class UserDetailOverviewPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<string, mixed> */
    public function present(User $user, User $actor): array
    {
        $assetsEnabled = PortfolioModules::enabledForUser($actor, 'assets');
        $leasesEnabled = PortfolioModules::enabledForUser($actor, 'leases')
            && PortfolioModules::enabledForUser($actor, 'tenants');
        $maintenanceEnabled = PortfolioModules::enabledForUser($actor, 'maintenance');
        $paymentsEnabled = PortfolioModules::enabledForUser($actor, 'payments');
        $roles = $user->roles->pluck('name')
            ->map(fn (string $role): string => trans("app.roles.{$role}"))
            ->join(' / ');
        $portfolioName = $this->resources->localized(
            $user->portfolio?->name_en,
            $user->portfolio?->name_ar,
        );
        $openWork = (int) ($user->getAttribute('open_assignments_count') ?? 0);
        $activeLeases = $leasesEnabled
            ? (int) ($user->tenantProfile?->getAttribute('active_leases_count') ?? 0)
            : 0;
        $decisionCards = [
            [
                'title' => trans('app.users.portal_access'),
                'value' => trans("app.status.{$user->status}"),
                'detail' => $user->email,
                'href' => route('users.portal-access.show', $user),
                'actionLabel' => trans('app.users.manage_portal_access'),
                'tone' => $user->status === 'active' ? 'teal' : 'danger',
                'icon' => 'bi-person-lock',
            ],
            [
                'title' => trans('app.users.role'),
                'value' => $roles,
                'detail' => $portfolioName ?: trans('app.users.global_account'),
                'tone' => 'primary',
                'icon' => 'bi-shield-check',
            ],
        ];

        if ($assetsEnabled) {
            $decisionCards[] = [
                'title' => trans('app.users.asset_assignments'),
                'value' => (int) ($user->getAttribute('current_asset_assignments_count') ?? 0),
                'detail' => trans('app.users.ownership_summary', [
                    'count' => (int) ($user->getAttribute('portfolios_owned_count') ?? 0),
                ]),
                'tone' => 'muted',
                'icon' => 'bi-buildings',
            ];
        }

        if ($maintenanceEnabled) {
            $decisionCards[] = [
                'title' => trans('app.users.open_workload'),
                'value' => $openWork,
                'detail' => $leasesEnabled
                    ? trans('app.users.active_lease_summary', ['count' => $activeLeases])
                    : trans('app.users.assignment_count', ['count' => $openWork]),
                'tone' => $openWork > 0 ? 'danger' : 'muted',
                'icon' => 'bi-tools',
            ];
        }

        return [
            'decisionCards' => $decisionCards,
            'stats' => $this->stats(
                $user,
                $roles,
                $openWork,
                $paymentsEnabled,
                $maintenanceEnabled,
            ),
            'sections' => $this->sections($user, $roles, $portfolioName),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function stats(
        User $user,
        string $roles,
        int $openWork,
        bool $paymentsEnabled,
        bool $maintenanceEnabled,
    ): array {
        $stats = [
            ['label' => trans('app.users.status'), 'value' => trans("app.status.{$user->status}"), 'tone' => $user->status === 'active' ? 'teal' : 'danger'],
            ['label' => trans('app.users.role'), 'value' => $roles, 'tone' => 'primary'],
        ];

        if ($paymentsEnabled) {
            $stats[] = ['label' => trans('app.users.recorded_payments'), 'value' => (int) ($user->getAttribute('recorded_payments_count') ?? 0)];
        }

        if ($maintenanceEnabled) {
            $stats[] = ['label' => trans('app.users.open_workload'), 'value' => $openWork, 'tone' => $openWork > 0 ? 'danger' : 'muted'];
        }

        return $this->resources->detailItems($stats);
    }

    /** @return array<int, array<string, mixed>> */
    private function sections(User $user, string $roles, ?string $portfolioName): array
    {
        return [
            [
                'title' => trans('app.users.account_section'),
                'description' => trans('app.users.account_section_help'),
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.users.email'), 'value' => $user->email],
                    ['label' => trans('app.users.phone'), 'value' => $user->phone],
                    ['label' => trans('app.users.preferred_language'), 'value' => trans('app.users.'.($user->preferred_locale === 'ar' ? 'arabic' : 'english'))],
                    ['label' => trans('app.users.portfolio'), 'value' => $portfolioName, 'href' => $user->portfolio ? route('portfolios.show', $user->portfolio) : null],
                    ['label' => trans('app.users.role'), 'value' => $roles],
                ]),
            ],
            [
                'title' => trans('app.users.security_section'),
                'description' => trans('app.users.security_section_help'),
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.users.temporary_password_state'), 'value' => trans('app.users.'.($user->force_password_reset ? 'yes' : 'no'))],
                    ['label' => trans('app.users.last_login'), 'value' => $user->last_login_at?->toDateTimeString()],
                    ['label' => trans('app.users.created_at'), 'value' => $user->created_at?->toDateTimeString()],
                    ['label' => trans('app.users.updated_at'), 'value' => $user->updated_at?->toDateTimeString()],
                ]),
            ],
        ];
    }
}
