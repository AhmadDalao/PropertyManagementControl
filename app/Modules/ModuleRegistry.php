<?php

namespace App\Modules;

final class ModuleRegistry
{
    /**
     * @return array<string, array{label:string, area:string}>
     */
    public static function operationalModules(): array
    {
        return [
            'dashboard' => ['label' => 'Dashboard', 'area' => 'command'],
            'profile' => ['label' => 'Profile', 'area' => 'account'],
            'documentation' => ['label' => 'Documentation', 'area' => 'support'],
            'portfolios' => ['label' => 'Portfolios', 'area' => 'portfolio'],
            'users' => ['label' => 'Users', 'area' => 'portfolio'],
            'assets' => ['label' => 'Assets', 'area' => 'portfolio'],
            'tenants' => ['label' => 'Tenants', 'area' => 'portfolio'],
            'leases' => ['label' => 'Leases', 'area' => 'operations'],
            'payments' => ['label' => 'Payments', 'area' => 'operations'],
            'maintenance' => ['label' => 'Maintenance', 'area' => 'operations'],
            'expenses' => ['label' => 'Expenses', 'area' => 'operations'],
            'documents' => ['label' => 'Documents', 'area' => 'operations'],
            'reports' => ['label' => 'Reports', 'area' => 'operations'],
            'audit' => ['label' => 'Audit History', 'area' => 'system'],
            'showcase_data' => ['label' => 'Showcase Data', 'area' => 'system'],
            'cms' => ['label' => 'Website Control', 'area' => 'website'],
            'media' => ['label' => 'Media', 'area' => 'website'],
        ];
    }

    /**
     * Cross-cutting modules coordinate feature modules but never own their data rules.
     *
     * @return array<string, array{label:string, area:string}>
     */
    public static function infrastructureModules(): array
    {
        return [
            'search' => ['label' => 'Global Search', 'area' => 'platform'],
            'exports' => ['label' => 'Resource Exports', 'area' => 'platform'],
        ];
    }
}
