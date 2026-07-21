<?php

namespace App\Modules\Users\Support;

use App\Models\User;

final class UserOptions
{
    /** @var array<int, string> */
    public const ROLES = ['superadmin', 'owner', 'property_manager', 'tenant'];

    /** @var array<int, string> */
    public const STATUSES = ['active', 'inactive', 'suspended'];

    /** @var array<int, string> */
    public const LOCALES = ['en', 'ar'];

    /** @return array<int, string> */
    public static function assignableRoles(User $actor, ?User $target = null): array
    {
        if ($actor->hasRole('superadmin')) {
            if ($target?->portfolio_id === null && $target !== null) {
                return ['superadmin'];
            }

            if ($target !== null) {
                return ['owner', 'property_manager', 'tenant'];
            }

            return self::ROLES;
        }

        if ($actor->hasRole('owner')) {
            return ['property_manager', 'tenant'];
        }

        return ['tenant'];
    }

    /** @return array<int, string> */
    public static function visibleRoles(User $actor): array
    {
        if ($actor->hasRole('superadmin')) {
            return self::ROLES;
        }

        if ($actor->hasRole('owner')) {
            return ['owner', 'property_manager', 'tenant'];
        }

        return ['property_manager', 'tenant'];
    }

    public static function tenantProfileStatus(string $userStatus): string
    {
        return match ($userStatus) {
            'suspended' => 'blocked',
            'inactive' => 'inactive',
            default => 'active',
        };
    }
}
