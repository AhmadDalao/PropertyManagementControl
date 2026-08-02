<?php

namespace App\Modules\OpeningData\Actions;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Tenants\Actions\CreateTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class ImportOpeningTenants
{
    public function __construct(private readonly CreateTenant $create) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, TenantProfile>
     */
    public function handle(User $actor, int $portfolioId, array $rows): array
    {
        $emails = collect($rows)->pluck('email')->filter()->unique()->all();
        $tenants = TenantProfile::query()
            ->where('portfolio_id', $portfolioId)
            ->whereHas('user', fn (Builder $query) => $query->whereIn('email', $emails))
            ->with('user:id,email')
            ->get()
            ->keyBy(fn (TenantProfile $tenant): string => mb_strtolower((string) $tenant->user?->email))
            ->all();

        foreach ($rows as $row) {
            $tenant = $this->create->handle($actor, [
                'portfolio_id' => $portfolioId,
                'name' => $row['name'],
                'email' => $row['email'],
                'phone' => $row['phone'] ?? null,
                'preferred_locale' => $row['preferred_locale'],
                'profile_type' => $row['profile_type'],
                'national_id' => $row['national_id'] ?? null,
                'company_name' => $row['company_name'] ?? null,
                'emergency_contact_name' => null,
                'emergency_contact_phone' => null,
                'address' => $row['address'] ?? null,
                'status' => $row['status'],
                'notes' => $row['notes'] ?? null,
                'password' => Str::random(36).'aA9!',
            ]);
            $tenants[(string) $row['email']] = $tenant;
        }

        return $tenants;
    }
}
