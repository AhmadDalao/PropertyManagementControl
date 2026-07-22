<?php

namespace App\Modules\Users\Support;

use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;

class UserAccess
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    public function ensureManager(User $actor): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']),
            403,
            trans('app.errors.section_access_denied'),
        );
    }

    public function ensureCanManage(User $actor, User $target): void
    {
        abort_unless($this->canManage($actor, $target), 403, trans('app.errors.manage_account_denied'));
    }

    public function canManage(User $actor, User $target): bool
    {
        if (! $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']) || $actor->is($target)) {
            return false;
        }

        if ($actor->hasRole('superadmin')) {
            return true;
        }

        if ($actor->portfolio_id === null || $actor->portfolio_id !== $target->portfolio_id) {
            return false;
        }

        if ($target->hasAnyRole(['superadmin', 'owner'])) {
            return false;
        }

        if ($actor->hasRole('owner')) {
            return $target->hasAnyRole(['property_manager', 'tenant']);
        }

        return $target->hasRole('tenant') && ! $target->hasRole('property_manager');
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function directoryScope(Builder $query, User $actor): Builder
    {
        $this->ensureManager($actor);

        if ($actor->hasRole('superadmin')) {
            return $query;
        }

        $portfolioId = $actor->portfolio_id ?? 0;
        $allowedRoles = $actor->hasRole('owner')
            ? ['property_manager', 'tenant']
            : ['tenant'];
        $blockedRoles = $actor->hasRole('owner')
            ? ['superadmin', 'owner']
            : ['superadmin', 'owner', 'property_manager'];

        return $query->where(function (Builder $users) use ($actor, $portfolioId, $allowedRoles, $blockedRoles): void {
            $users
                ->whereKey($actor->id)
                ->orWhere(function (Builder $manageable) use ($portfolioId, $allowedRoles, $blockedRoles): void {
                    $manageable
                        ->where('portfolio_id', $portfolioId)
                        ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', $allowedRoles))
                        ->whereDoesntHave('roles', fn (Builder $roles) => $roles->whereIn('name', $blockedRoles));
                });
        });
    }

    public function ensurePortfolioAccess(User $actor, ?int $portfolioId): void
    {
        $this->portfolios->ensureAccess($actor, $portfolioId);
    }

    public function recordHref(User $actor, ?User $target): ?string
    {
        if (! $target) {
            return null;
        }

        if ($actor->is($target)) {
            return route('profile.index');
        }

        return $this->canManage($actor, $target)
            ? route('users.show', $target)
            : null;
    }
}
