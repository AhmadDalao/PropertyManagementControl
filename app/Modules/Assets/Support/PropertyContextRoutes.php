<?php

namespace App\Modules\Assets\Support;

final class PropertyContextRoutes
{
    private const NAMES = [
        'dashboard',
        'action-center.index',
        'action-center.export',
        'property-map.index',
        'assets.index',
        'tenants.index',
        'leases.index',
        'lease-renewals.index',
        'lease-move-outs.index',
        'rent-collection.index',
        'payments.index',
        'maintenance-requests.index',
        'expenses.index',
        'reports.index',
        'reports.export',
        'documents.index',
        'exports.resource',
    ];

    public function includes(?string $routeName): bool
    {
        return in_array($routeName, self::NAMES, true);
    }
}
