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
        return [
            ['key' => 'users', 'label' => 'Users', 'description' => 'Owner, manager, and tenant account management.'],
            ['key' => 'assets', 'label' => 'Assets', 'description' => 'Buildings, floors, units, spaces, valuations, and stakeholders.'],
            ['key' => 'tenants', 'label' => 'Tenants', 'description' => 'Tenant profiles, contacts, documents, and portal identities.'],
            ['key' => 'leases', 'label' => 'Leases', 'description' => 'Contracts, lease status, statements, and signed PDFs.'],
            ['key' => 'payments', 'label' => 'Payments', 'description' => 'Manual rent posting, receipts, balances, and allocation history.'],
            ['key' => 'maintenance', 'label' => 'Maintenance', 'description' => 'Tenant requests, triage, updates, and service workflow.'],
            ['key' => 'expenses', 'label' => 'Expenses', 'description' => 'Maintenance costs, vendor expenses, and net revenue inputs.'],
            ['key' => 'reports', 'label' => 'Reports', 'description' => 'Revenue, arrears, occupancy, lease expiry, and export views.'],
            ['key' => 'documents', 'label' => 'Documents', 'description' => 'Contracts, receipts, statements, uploads, and secure downloads.'],
            ['key' => 'media', 'label' => 'Media', 'description' => 'Images and files used by portfolio content and records.'],
        ];
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
