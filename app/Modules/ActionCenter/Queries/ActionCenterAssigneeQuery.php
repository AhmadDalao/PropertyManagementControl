<?php

namespace App\Modules\ActionCenter\Queries;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ActionCenterAssigneeQuery
{
    /**
     * @return list<array{id:int,label:string}>
     */
    public function options(User $actor, ?int $portfolioId): array
    {
        if ($actor->hasRole('superadmin') && $portfolioId === null) {
            return [];
        }

        $scopeId = $actor->hasRole('superadmin')
            ? $portfolioId
            : $actor->portfolio_id;

        if ($scopeId === null) {
            return [];
        }

        return array_values(User::query()
            ->where('portfolio_id', $scopeId)
            ->where('status', 'active')
            ->whereHas(
                'roles',
                fn (Builder $roles) => $roles->whereIn(
                    'name',
                    ['owner', 'property_manager'],
                ),
            )
            ->with('roles:id,name')
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'label' => $user->name.' · '.trans(
                    'app.roles.'.$user->roles->first()->name,
                ),
            ])
            ->all());
    }
}
