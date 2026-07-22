<?php

namespace App\Modules\Documentation\Support;

use App\Models\User;
use App\Support\PortfolioModules;

class DocumentationAccess
{
    /** @var array<string, string> */
    private const ROUTE_MODULES = [
        '/maintenance-requests' => 'maintenance',
        '/media-files' => 'media',
        '/documents' => 'documents',
        '/payments' => 'payments',
        '/expenses' => 'expenses',
        '/tenants' => 'tenants',
        '/leases' => 'leases',
        '/reports' => 'reports',
        '/assets' => 'assets',
        '/users' => 'users',
    ];

    public function primaryRole(User $actor): string
    {
        return $actor->getRoleNames()->first() ?? 'user';
    }

    /** @param array<string, mixed> $item */
    public function canSee(User $actor, array $item): bool
    {
        $roles = $item['roles'] ?? null;

        if (is_array($roles) && ! $actor->hasAnyRole($roles)) {
            return false;
        }

        $module = $item['module'] ?? $this->moduleForRoute($item['route'] ?? null);

        return ! is_string($module) || PortfolioModules::enabledForUser($actor, $module);
    }

    /** @return list<array{key:string, label:string, description:string, enabled:bool}> */
    public function moduleStatus(User $actor): array
    {
        $status = [];

        foreach (PortfolioModules::definitions() as $module) {
            $status[] = [
                ...$module,
                'enabled' => PortfolioModules::enabledForUser($actor, $module['key']),
            ];
        }

        return $status;
    }

    private function moduleForRoute(mixed $route): ?string
    {
        if (! is_string($route)) {
            return null;
        }

        $path = parse_url($route, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        foreach (self::ROUTE_MODULES as $prefix => $module) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return $module;
            }
        }

        return null;
    }
}
