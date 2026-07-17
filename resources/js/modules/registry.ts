export type ModuleNavItem = {
    label: string;
    href: string;
    icon: string;
    roles?: string[];
    module?: string;
};

export type ModuleNavGroup = {
    label: string;
    items: ModuleNavItem[];
};

export const MODULE_NAV_GROUPS: ModuleNavGroup[] = [
    {
        label: 'Overview',
        items: [
            { label: 'Dashboard', href: '/dashboard', icon: 'bi-grid-1x2' },
            {
                label: 'Properties Map',
                href: '/property-map',
                icon: 'bi-map',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'assets',
            },
            {
                label: 'Reports',
                href: '/reports',
                icon: 'bi-graph-up-arrow',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'reports',
            },
        ],
    },
    {
        label: 'Portfolio',
        items: [
            {
                label: 'Portfolios',
                href: '/portfolios',
                icon: 'bi-buildings',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
            {
                label: 'Properties & Units',
                href: '/assets',
                icon: 'bi-building',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'assets',
            },
            {
                label: 'Tenants',
                href: '/tenants',
                icon: 'bi-person-badge',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'tenants',
            },
            {
                label: 'Leases',
                href: '/leases',
                icon: 'bi-file-earmark-text',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'leases',
            },
        ],
    },
    {
        label: 'Money & Service',
        items: [
            {
                label: 'Payments',
                href: '/payments',
                icon: 'bi-cash-stack',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'payments',
            },
            {
                label: 'Expenses',
                href: '/expenses',
                icon: 'bi-receipt',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'expenses',
            },
            {
                label: 'Maintenance',
                href: '/maintenance-requests',
                icon: 'bi-tools',
                module: 'maintenance',
            },
            {
                label: 'Documents',
                href: '/documents',
                icon: 'bi-folder2-open',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'documents',
            },
        ],
    },
    {
        label: 'System',
        items: [
            {
                label: 'Users & Roles',
                href: '/users',
                icon: 'bi-people',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'users',
            },
            {
                label: 'Website Control',
                href: '/cms',
                icon: 'bi-layout-wtf',
                roles: ['superadmin'],
            },
            {
                label: 'Media Library',
                href: '/media-files',
                icon: 'bi-images',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'media',
            },
            {
                label: 'Audit History',
                href: '/audit-logs',
                icon: 'bi-clock-history',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
            {
                label: 'Documentation',
                href: '/documentation',
                icon: 'bi-journal-richtext',
            },
        ],
    },
];
