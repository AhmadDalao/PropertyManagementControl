export type ModuleNavItem = {
    labelKey: `nav.${string}`;
    href: string;
    icon: string;
    roles?: string[];
    module?: string;
    propertyScoped?: boolean;
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
                propertyScoped: true,
            },
            {
                labelKey: 'nav.notifications',
                href: '/notifications',
                icon: 'bi-envelope',
            },
            {
                labelKey: 'nav.company_control',
                href: '/company-control',
                icon: 'bi-building-check',
                roles: ['superadmin'],
            },
            {
                labelKey: 'nav.portfolio_control',
                href: '/portfolio-control',
                icon: 'bi-buildings',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'assets',
            },
            {
                labelKey: 'nav.action_center',
                href: '/action-center',
                icon: 'bi-collection',
                roles: ['superadmin', 'owner', 'property_manager'],
                propertyScoped: true,
            },
            {
                labelKey: 'nav.property_map',
                href: '/property-map',
                icon: 'bi-map',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'assets',
                propertyScoped: true,
            },
            {
                labelKey: 'nav.reports',
                href: '/reports',
                icon: 'bi-graph-up-arrow',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'reports',
                propertyScoped: true,
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
                labelKey: 'nav.opening_data',
                href: '/opening-data',
                icon: 'bi-file-earmark-spreadsheet',
                roles: ['superadmin', 'owner'],
            },
            {
                labelKey: 'nav.property_explorer',
                href: '/property-explorer',
                icon: 'bi-diagram-3',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'assets',
                propertyScoped: true,
            },
            {
                labelKey: 'nav.tenants',
                href: '/tenants',
                icon: 'bi-person-badge',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'tenants',
                propertyScoped: true,
            },
            {
                labelKey: 'nav.leases',
                href: '/leases',
                icon: 'bi-file-earmark-text',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'leases',
                propertyScoped: true,
            },
            {
                labelKey: 'nav.lease_renewals',
                href: '/lease-renewals',
                icon: 'bi-calendar-event',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'leases',
                propertyScoped: true,
            },
            {
                labelKey: 'nav.lease_move_outs',
                href: '/lease-move-outs',
                icon: 'bi-box-arrow-right',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'leases',
                propertyScoped: true,
            },
        ],
    },
    {
        labelKey: 'nav.group_operations',
        items: [
            {
                labelKey: 'nav.rent_collection',
                href: '/rent-collection',
                icon: 'bi-calendar2-check',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'payments',
                propertyScoped: true,
            },
            {
                labelKey: 'nav.payments',
                href: '/payments',
                icon: 'bi-cash-stack',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'payments',
                propertyScoped: true,
            },
            {
                labelKey: 'nav.expenses',
                href: '/expenses',
                icon: 'bi-receipt',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'expenses',
                propertyScoped: true,
            },
            {
                labelKey: 'nav.maintenance',
                href: '/maintenance-requests',
                icon: 'bi-tools',
                module: 'maintenance',
                propertyScoped: true,
            },
            {
                labelKey: 'nav.documents',
                href: '/documents',
                icon: 'bi-folder2-open',
                roles: ['superadmin', 'owner', 'property_manager'],
                module: 'documents',
                propertyScoped: true,
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
                labelKey: 'nav.system_readiness',
                href: '/system/readiness',
                icon: 'bi-shield-check',
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
