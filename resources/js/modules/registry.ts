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
        label: 'Command',
        items: [
            { label: 'Dashboard', href: '/dashboard', icon: 'bi-grid-1x2' },
            {
                label: 'Properties Map',
                href: '/assets',
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
            {
                label: 'Documentation',
                href: '/documentation',
                icon: 'bi-journal-richtext',
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
                label: 'Users & Roles',
                href: '/users',
                icon: 'bi-people',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'users',
            },
            {
                label: 'Tenant Profiles',
                href: '/tenants',
                icon: 'bi-person-badge',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'tenants',
            },
        ],
    },
    {
        label: 'Leasing & Service',
        items: [
            {
                label: 'Leases',
                href: '/leases',
                icon: 'bi-file-earmark-text',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'leases',
            },
            {
                label: 'Payments',
                href: '/payments',
                icon: 'bi-cash-stack',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'payments',
            },
            {
                label: 'Maintenance Requests',
                href: '/maintenance-requests',
                icon: 'bi-tools',
                module: 'maintenance',
            },
            {
                label: 'Expenses',
                href: '/expenses',
                icon: 'bi-receipt',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'expenses',
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
        label: 'Admin',
        items: [
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
        ],
    },
];
