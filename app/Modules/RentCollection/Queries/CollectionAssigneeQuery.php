<?php

namespace App\Modules\RentCollection\Queries;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Leases\Support\LeaseAccess;
use Illuminate\Database\Eloquent\Builder;

final readonly class CollectionAssigneeQuery
{
    public function __construct(private LeaseAccess $leases) {}

    /** @return list<array{id:int,label:string}> */
    public function options(User $actor, Lease $lease): array
    {
        $candidates = User::query()
            ->with('roles:id,name')
            ->where('status', 'active')
            ->where(function (Builder $users) use ($actor, $lease): void {
                $users->where('portfolio_id', $lease->portfolio_id);

                if ($actor->hasRole('superadmin')) {
                    $users->orWhere('id', $actor->id);
                }
            })
            ->whereHas(
                'roles',
                fn (Builder $roles) => $roles->whereIn(
                    'name',
                    ['superadmin', 'owner', 'property_manager'],
                ),
            )
            ->orderBy('name')
            ->get()
            ->filter(fn (User $candidate): bool => $this->leases->canManage($candidate, $lease));

        $options = [];

        foreach ($candidates as $candidate) {
            $role = $candidate->roles->first()->name;
            $options[] = [
                'id' => $candidate->id,
                'label' => $candidate->name.' · '.(string) trans("app.roles.{$role}"),
            ];
        }

        return $options;
    }

    public function allows(User $actor, Lease $lease, int $userId): bool
    {
        return collect($this->options($actor, $lease))->contains('id', $userId);
    }
}
