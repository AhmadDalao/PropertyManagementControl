<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'manage portfolios',
            'manage users',
            'manage assets',
            'manage tenants',
            'manage leases',
            'manage payments',
            'manage maintenance',
            'manage expenses',
            'view reports',
            'manage cms',
            'manage media',
            'download documents',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superadmin = Role::findOrCreate('superadmin', 'web');
        $owner = Role::findOrCreate('owner', 'web');
        $manager = Role::findOrCreate('property_manager', 'web');
        $tenant = Role::findOrCreate('tenant', 'web');

        $superadmin->syncPermissions($permissions);

        $owner->syncPermissions([
            'view dashboard',
            'manage users',
            'manage assets',
            'manage tenants',
            'manage leases',
            'manage payments',
            'manage maintenance',
            'manage expenses',
            'view reports',
            'manage media',
            'download documents',
        ]);

        $manager->syncPermissions([
            'view dashboard',
            'manage assets',
            'manage tenants',
            'manage leases',
            'manage payments',
            'manage maintenance',
            'manage expenses',
            'view reports',
            'download documents',
        ]);

        $tenant->syncPermissions([
            'view dashboard',
            'download documents',
        ]);
    }
}
