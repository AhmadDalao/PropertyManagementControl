export type ModuleNavItem = {
    labelKey: `nav.${string}`;
    href: string;
    icon: string;
    roles?: string[];
    module?: string;
};

export type ModuleNavGroup = {
    labelKey: `nav.${string}`;
    items: ModuleNavItem[];
};

export const MODULE_NAV_GROUPS: ModuleNavGroup[] = [
    {
        labelKey: 'nav.group_overview',
        items: [
            {
                labelKey: 'nav.dashboard',
                href: '/dashboard',
                icon: 'bi-grid-1x2',
            },
            {
                labelKey: 'nav.property_map',
                href: '/property-map',
                icon: 'bi-map',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'assets',
            },
            {
                labelKey: 'nav.reports',
                href: '/reports',
                icon: 'bi-graph-up-arrow',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'reports',
            },
        ],
    },
    {
        labelKey: 'nav.group_portfolio',
        items: [
            {
                labelKey: 'nav.portfolios',
                href: '/portfolios',
                icon: 'bi-buildings',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
            {
                labelKey: 'nav.assets',
                href: '/assets',
                icon: 'bi-building',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'assets',
            },
            {
                labelKey: 'nav.tenants',
                href: '/tenants',
                icon: 'bi-person-badge',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'tenants',
            },
            {
                labelKey: 'nav.leases',
                href: '/leases',
                icon: 'bi-file-earmark-text',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'leases',
            },
        ],
    },
    {
        labelKey: 'nav.group_operations',
        items: [
            {
                labelKey: 'nav.payments',
                href: '/payments',
                icon: 'bi-cash-stack',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'payments',
            },
            {
                labelKey: 'nav.expenses',
                href: '/expenses',
                icon: 'bi-receipt',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'expenses',
            },
            {
                labelKey: 'nav.maintenance',
                href: '/maintenance-requests',
                icon: 'bi-tools',
                module: 'maintenance',
            },
            {
                labelKey: 'nav.documents',
                href: '/documents',
                icon: 'bi-folder2-open',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'documents',
            },
        ],
    },
    {
        labelKey: 'nav.group_system',
        items: [
            {
                labelKey: 'nav.users',
                href: '/users',
                icon: 'bi-people',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'users',
            },
            {
                labelKey: 'nav.cms',
                href: '/cms',
                icon: 'bi-layout-wtf',
                roles: ['superadmin'],
            },
            {
                labelKey: 'nav.wording',
                href: '/wording',
                icon: 'bi-translate',
                roles: ['superadmin'],
            },
            {
                labelKey: 'nav.showcase_data',
                href: '/system/showcase-data',
                icon: 'bi-database-gear',
                roles: ['superadmin'],
            },
            {
                labelKey: 'nav.media',
                href: '/media-files',
                icon: 'bi-images',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'media',
            },
            {
                labelKey: 'nav.audit',
                href: '/audit-logs',
                icon: 'bi-clock-history',
                roles: ['superadmin', 'owner', 'property_manager'],
            },
            {
                labelKey: 'nav.documentation',
                href: '/documentation',
                icon: 'bi-journal-richtext',
            },
        ],
    },
];
