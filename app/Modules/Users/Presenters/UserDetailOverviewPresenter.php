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
        $leasesEnabled = PortfolioModules::enabledForUser($actor, 'tenants')
            && $user->tenantProfile
            && PortfolioModules::enabledForUser($actor, 'leases');
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
            ? (int) ($user->tenantProfile->getAttribute('active_leases_count') ?? 0)
            : 0;

        return [
            'stats' => $this->stats(
                $user,
                $roles,
                $openWork,
                $activeLeases,
                $assetsEnabled,
                $leasesEnabled,
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
        int $activeLeases,
        bool $assetsEnabled,
        bool $leasesEnabled,
        bool $paymentsEnabled,
        bool $maintenanceEnabled,
    ): array {
        $stats = [
            ['label' => trans('app.users.status'), 'value' => trans("app.status.{$user->status}"), 'tone' => $user->status === 'active' ? 'teal' : 'danger'],
            ['label' => trans('app.users.role'), 'value' => $roles, 'tone' => 'primary'],
        ];

        if ($assetsEnabled) {
            $stats[] = [
                'label' => trans('app.users.property_assignments'),
                'value' => (int) ($user->getAttribute('current_asset_assignments_count') ?? 0),
            ];
        }

        if ($leasesEnabled) {
            $stats[] = ['label' => trans('app.users.active_leases'), 'value' => $activeLeases, 'tone' => 'teal'];
        }

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
                'key' => 'identity',
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
                'key' => 'access',
                'title' => trans('app.users.security_section'),
                'description' => trans('app.users.security_section_help'),
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.users.status'), 'value' => trans("app.status.{$user->status}")],
                    ['label' => trans('app.users.temporary_password_state'), 'value' => trans('app.users.'.($user->force_password_reset ? 'yes' : 'no'))],
                    ['label' => trans('app.users.last_login'), 'value' => $user->last_login_at?->toDateTimeString()],
                    ['label' => trans('app.users.created_at'), 'value' => $user->created_at?->toDateTimeString()],
                    ['label' => trans('app.users.updated_at'), 'value' => $user->updated_at?->toDateTimeString()],
                ]),
            ],
        ];
    }
}
