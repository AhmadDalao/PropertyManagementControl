<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;

final class UserWorkflowActionPresenter
{
    /** @return array<string, mixed> */
    public function edit(User $user): array
    {
        return $this->link(trans('app.users.edit_user'), route('users.edit', $user));
    }

    /** @return array<string, mixed> */
    public function assignments(User $user, string $variant = 'primary'): array
    {
        return $this->link(
            trans('app.users.manage_property_assignments'),
            route('users.property-assignments.edit', $user),
            $variant,
        );
    }

    /** @return array<string, mixed> */
    public function portalAccess(User $user, string $variant): array
    {
        return $this->link(
            trans('app.users.manage_portal_access'),
            route('users.portal-access.show', $user),
            $variant,
        );
    }

    /** @return array<string, mixed> */
    public function tenantProfile(User $user): array
    {
        return $this->link(
            trans('app.users.open_tenant_profile'),
            route('tenants.show', $user->tenantProfile),
        );
    }

    /** @return array<string, mixed> */
    public function portfolio(User $user): array
    {
        return $this->link(
            trans('app.users.open_portfolio'),
            route('portfolios.show', $user->portfolio),
        );
    }

    /** @return array<string, mixed> */
    public function workload(User $user): array
    {
        return $this->link(
            trans('app.users.review_workload'),
            route('maintenance-requests.index', ['search' => $user->email]),
        );
    }

    /** @return array<string, mixed> */
    private function link(string $label, string $href, string $variant = 'primary'): array
    {
        return compact('label', 'href', 'variant');
    }
}
