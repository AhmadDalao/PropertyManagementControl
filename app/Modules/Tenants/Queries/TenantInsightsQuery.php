<?php

namespace App\Modules\Tenants\Queries;

use App\Models\TenantProfile;
use Illuminate\Database\Eloquent\Builder;

final class TenantInsightsQuery
{
    /**
     * @param  Builder<TenantProfile>  $query
     * @return array<string, int>
     */
    public function get(Builder $query): array
    {
        $summary = (clone $query)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count")
            ->selectRaw("SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_count")
            ->selectRaw("SUM(CASE WHEN profile_type = 'company' THEN 1 ELSE 0 END) as company_count")
            ->selectRaw("SUM(CASE WHEN emergency_contact_name IS NULL OR emergency_contact_name = '' OR emergency_contact_phone IS NULL OR emergency_contact_phone = '' THEN 1 ELSE 0 END) as missing_emergency_count")
            ->selectRaw("SUM(CASE WHEN address IS NULL OR address = '' THEN 1 ELSE 0 END) as missing_address_count")
            ->first();

        return [
            'total' => (int) ($summary?->getAttribute('total') ?? 0),
            'active' => (int) ($summary?->getAttribute('active_count') ?? 0),
            'blocked' => (int) ($summary?->getAttribute('blocked_count') ?? 0),
            'companies' => (int) ($summary?->getAttribute('company_count') ?? 0),
            'without_active_lease' => (clone $query)
                ->whereDoesntHave('leases', fn (Builder $leases) => $leases->where('status', 'active'))
                ->count(),
            'missing_emergency' => (int) ($summary?->getAttribute('missing_emergency_count') ?? 0),
            'missing_address' => (int) ($summary?->getAttribute('missing_address_count') ?? 0),
        ];
    }
}
