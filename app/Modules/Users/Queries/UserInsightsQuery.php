<?php

namespace App\Modules\Users\Queries;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class UserInsightsQuery
{
    /**
     * @param  Builder<User>  $baseQuery
     * @return array<string, int>
     */
    public function get(Builder $baseQuery): array
    {
        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count")
            ->selectRaw("SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_count")
            ->selectRaw('SUM(CASE WHEN force_password_reset = 1 THEN 1 ELSE 0 END) as temporary_passwords')
            ->first();

        return [
            'total' => (int) ($summary?->getAttribute('total') ?? 0),
            'active' => (int) ($summary?->getAttribute('active_count') ?? 0),
            'suspended' => (int) ($summary?->getAttribute('suspended_count') ?? 0),
            'temporary_passwords' => (int) ($summary?->getAttribute('temporary_passwords') ?? 0),
            'tenants_without_profile' => (clone $baseQuery)
                ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'tenant'))
                ->whereDoesntHave('tenantProfile')
                ->count(),
        ];
    }
}
