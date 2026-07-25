<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class DashboardSetupTargetQuery
{
    public function forUser(User $user): ?Portfolio
    {
        if ($user->hasRole('owner')) {
            return Portfolio::query()
                ->whereKey($user->portfolio_id ?? 0)
                ->whereNull('showcase_dataset_id')
                ->where('status', '!=', 'archived')
                ->first();
        }

        if (! $user->hasRole('superadmin')) {
            return null;
        }

        $available = Portfolio::query()
            ->whereNull('showcase_dataset_id')
            ->where('status', '!=', 'archived');

        return (clone $available)
            ->where(function (Builder $query): void {
                $query
                    ->where('status', '!=', 'active')
                    ->orWhereDoesntHave(
                        'owner',
                        fn (Builder $owner): Builder => $owner->where('status', 'active'),
                    )
                    ->orWhereDoesntHave(
                        'users',
                        fn (Builder $users): Builder => $this->activeRole($users, 'property_manager'),
                    )
                    ->orWhereDoesntHave(
                        'assets',
                        fn (Builder $assets): Builder => $assets
                            ->whereNull('parent_id')
                            ->where('status', '!=', 'archived'),
                    )
                    ->orWhereDoesntHave(
                        'users',
                        fn (Builder $users): Builder => $this->activeRole($users, 'tenant'),
                    )
                    ->orWhereDoesntHave(
                        'leases',
                        fn (Builder $leases): Builder => $leases->where('status', 'active'),
                    );
            })
            ->oldest()
            ->first()
            ?? $available->latest('updated_at')->first();
    }

    /**
     * @param  Builder<User>  $users
     * @return Builder<User>
     */
    private function activeRole(Builder $users, string $role): Builder
    {
        return $users
            ->where('status', 'active')
            ->whereHas(
                'roles',
                fn (Builder $roles): Builder => $roles->where('name', $role),
            );
    }
}
