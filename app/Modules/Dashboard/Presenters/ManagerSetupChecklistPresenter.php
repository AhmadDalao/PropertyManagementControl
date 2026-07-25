<?php

namespace App\Modules\Dashboard\Presenters;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;

final class ManagerSetupChecklistPresenter
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly AssignedPropertyScope $assignments,
    ) {}

    /** @param array<string, int|float> $stats */
    public function present(User $user, array $stats): array
    {
        if ($this->assignments->restricts($user) && ! $this->assignments->hasAssignments($user)) {
            return [$this->item(
                'assignments',
                false,
                '/portfolios',
                'bi-building',
                trans('app.dashboard.property_assignment_action_help'),
                trans('app.dashboard.property_assignment_action'),
            )];
        }

        $hasTenants = $this->assignments
            ->tenants($this->portfolios->apply(TenantProfile::query(), $user), $user)
            ->exists();

        return [
            $this->item('property', $stats['totalAssets'] > 0, '/assets/create', 'bi-buildings'),
            $this->item('tenant', $hasTenants, '/tenants/create', 'bi-people'),
            $this->item('lease', $stats['activeLeases'] > 0, '/leases/create', 'bi-file-earmark-check'),
        ];
    }

    /** @return array<string, mixed> */
    private function item(
        string $key,
        bool $done,
        string $href,
        string $icon,
        ?string $description = null,
        ?string $label = null,
    ): array {
        return [
            'key' => $key,
            'label' => $label ?? trans("app.portfolios.setup_steps.{$key}.title"),
            'description' => $description ?? trans("app.portfolios.setup_steps.{$key}.description"),
            'done' => $done,
            'href' => $href,
            'icon' => $icon,
        ];
    }
}
