<?php

namespace App\Modules\Tenants\Support;

final class TenantOptions
{
    /** @var array<int, string> */
    public const PROFILE_TYPES = ['individual', 'company'];

    /** @var array<int, string> */
    public const STATUSES = ['active', 'inactive', 'blocked'];

    /** @var array<int, string> */
    public const LOCALES = ['en', 'ar'];

    public static function userStatus(string $tenantStatus): string
    {
        return match ($tenantStatus) {
            'blocked' => 'suspended',
            'inactive' => 'inactive',
            default => 'active',
        };
    }
}
