<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;

final class PortalAccessPresenter
{
    public function __construct(
        private readonly UserAccess $access,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, User $target, string $origin): array
    {
        $this->access->ensureCanManage($actor, $target);
        $target->loadMissing(['portfolio', 'roles', 'tenantProfile']);
        $tenantOrigin = $origin === 'tenant' && $target->tenantProfile !== null;
        $portfolio = $this->resources->localized(
            $target->portfolio?->name_en,
            $target->portfolio?->name_ar,
        );

        return [
            'header' => [
                'eyebrow' => trans('app.users.portal_access_eyebrow'),
                'title' => trans('app.users.portal_access_title', ['name' => $target->name]),
                'description' => trans('app.users.portal_access_description'),
                'backHref' => $tenantOrigin
                    ? route('tenants.show', $target->tenantProfile)
                    : route('users.show', $target),
                'backLabel' => $tenantOrigin
                    ? trans('app.tenants.back_to_tenant')
                    : trans('app.users.back_to_user'),
            ],
            'account' => [
                'name' => $target->name,
                'email' => $target->email,
                'status' => $target->status,
                'status_label' => trans("app.status.{$target->status}"),
                'role' => $target->roles
                    ->map(fn ($role): string => trans("app.roles.{$role->name}"))
                    ->join(' / '),
                'portfolio' => $portfolio ?: trans('app.users.global_account'),
                'preferred_locale' => $target->preferred_locale,
                'password_change_required' => (bool) $target->force_password_reset,
            ],
            'endpoint' => route('users.portal-access.store', $target),
            'expiresInMinutes' => max(
                1,
                (int) config('auth.passwords.users.expire', 60),
            ),
            'canGenerate' => $target->status === 'active',
        ];
    }
}
