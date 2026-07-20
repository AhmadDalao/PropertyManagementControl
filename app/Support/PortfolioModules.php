<?php

namespace App\Support;

use App\Models\User;

class PortfolioModules
{
    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return [
            'users' => true,
            'assets' => true,
            'tenants' => true,
            'leases' => true,
            'payments' => true,
            'maintenance' => true,
            'expenses' => true,
            'reports' => true,
            'documents' => true,
            'media' => true,
        ];
    }

    /**
     * @return array<int, array{key:string,label:string,description:string}>
     */
    public static function definitions(): array
    {
        return collect(array_keys(self::defaults()))
            ->map(fn (string $key): array => [
                'key' => $key,
                'label' => trans("app.modules.{$key}.label"),
                'description' => trans("app.modules.{$key}.description"),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @return array<string, bool>
     */
    public static function normalize(?array $settings): array
    {
        $normalized = self::defaults();

        foreach ($normalized as $key => $enabled) {
            if (array_key_exists($key, $settings ?? [])) {
                $normalized[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $normalized;
    }

    public static function enabledForUser(User $user, string $module): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        $portfolio = $user->portfolio;

        if (! $portfolio) {
            return false;
        }

        return self::normalize($portfolio->module_settings)[$module] ?? true;
    }

    public static function exportResourceModule(string $resource): ?string
    {
        return match ($resource) {
            'users' => 'users',
            'assets' => 'assets',
            'tenants' => 'tenants',
            'leases' => 'leases',
            'payments' => 'payments',
            'maintenance-requests' => 'maintenance',
            'expenses' => 'expenses',
            'documents' => 'documents',
            'media-files' => 'media',
            default => null,
        };
    }
}
