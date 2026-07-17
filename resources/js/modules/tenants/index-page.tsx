import { Head, usePage } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import {
    MetricGrid,
    RecordActions,
    StatusBadge,
    WorkspaceHeader,
    humanLabel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

type TenantRecord = {
    id: number;
    profile_type: string;
    national_id?: string | null;
    company_name?: string | null;
    emergency_contact_name?: string | null;
    emergency_contact_phone?: string | null;
    address?: string | null;
    status: string;
    leases_count?: number;
    active_leases_count?: number;
    open_requests_count?: number;
    user?: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        preferred_locale: 'en' | 'ar';
    } | null;
};

type PageProps = SharedProps & {
    tenants: PaginatedData<TenantRecord>;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    tenantInsights: {
        total: number;
        active: number;
        blocked: number;
        companies: number;
        without_active_lease: number;
        missing_emergency: number;
        missing_address: number;
    };
};

export default function TenantsIndexPage() {
    const { props } = usePage<PageProps>();
    const profileGaps =
        props.tenantInsights.missing_emergency +
        props.tenantInsights.missing_address;
    const filterFields: TableFilterField[] = [
        {
            name: 'status',
            label: 'Status',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Active', value: 'active' },
                { label: 'Inactive', value: 'inactive' },
                { label: 'Blocked', value: 'blocked' },
            ],
        },
        {
            name: 'profile_type',
            label: 'Profile',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Individual', value: 'individual' },
                { label: 'Company', value: 'company' },
            ],
        },
    ];

    if (props.auth.user?.roles.includes('superadmin')) {
        filterFields.push({
            name: 'portfolio_id',
            label: 'Portfolio',
            options: [
                { label: 'All', value: 'all' },
                ...props.portfolioOptions.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return (
        <AdminLayout>
            <Head title="Tenants" />

            <WorkspaceHeader
                eyebrow="Portfolio"
                title="Tenants"
                description="Find a tenant, open the profile, then manage leases, balances, documents, maintenance, and portal access."
                actions={[
                    {
                        label: 'Create lease',
                        href: '/leases/create',
                        icon: 'bi-file-earmark-plus',
                    },
                    {
                        label: 'Create tenant',
                        href: '/tenants/create',
                        icon: 'bi-person-plus',
                        tone: 'primary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: 'Tenant profiles',
                        value: props.tenantInsights.total,
                        detail: `${props.tenantInsights.companies} company profiles`,
                        icon: 'bi-people',
                        tone: 'ink',
                    },
                    {
                        label: 'Active',
                        value: props.tenantInsights.active,
                        detail: 'Portal profiles in good standing',
                        icon: 'bi-person-check',
                        tone: 'teal',
                    },
                    {
                        label: 'Without active lease',
                        value: props.tenantInsights.without_active_lease,
                        detail: 'Profiles not connected to a current rental',
                        icon: 'bi-file-earmark-x',
                        tone:
                            props.tenantInsights.without_active_lease > 0
                                ? 'amber'
                                : 'blue',
                    },
                    {
                        label: 'Profile gaps',
                        value: profileGaps,
                        detail: `${props.tenantInsights.missing_emergency} emergency · ${props.tenantInsights.missing_address} address`,
                        icon: 'bi-person-exclamation',
                        tone: profileGaps > 0 ? 'red' : 'amber',
                    },
                ]}
            />

            <DataTable
                title="Tenant directory"
                description="Search name, email, phone, company, national ID, address, or emergency contact."
                data={props.tenants}
                filters={props.filters}
                counts={props.counts}
                basePath="/tenants"
                rowHref={(tenant) => `/tenants/${tenant.id}`}
                exportHref={exportUrl('/exports/tenants', props.filters)}
                filterFields={filterFields}
                columns={[
                    {
                        key: 'tenant',
                        label: 'Tenant',
                        render: (tenant) => (
                            <div className="pmc-primary-cell">
                                <strong>
                                    {tenant.user?.name ??
                                        tenant.company_name ??
                                        `Tenant #${tenant.id}`}
                                </strong>
                                <span>{tenant.user?.email ?? 'No email'}</span>
                                {tenant.user?.phone ? (
                                    <small>{tenant.user.phone}</small>
                                ) : null}
                            </div>
                        ),
                    },
                    {
                        key: 'profile',
                        label: 'Profile',
                        render: (tenant) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {humanLabel(tenant.profile_type)}
                                </strong>
                                <span>
                                    {tenant.company_name ??
                                        tenant.national_id ??
                                        'Identity not recorded'}
                                </span>
                                <ProfileCompleteness tenant={tenant} />
                            </div>
                        ),
                    },
                    {
                        key: 'leases',
                        label: 'Rental activity',
                        render: (tenant) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {tenant.active_leases_count ?? 0} active
                                    lease
                                    {(tenant.active_leases_count ?? 0) === 1
                                        ? ''
                                        : 's'}
                                </strong>
                                <span>
                                    {tenant.leases_count ?? 0} total ·{' '}
                                    {tenant.open_requests_count ?? 0} open
                                    service
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'status',
                        label: 'Status',
                        render: (tenant) => (
                            <StatusBadge value={tenant.status} />
                        ),
                    },
                    {
                        key: 'actions',
                        label: 'Actions',
                        className: 'text-end',
                        render: (tenant) => (
                            <RecordActions
                                showHref={`/tenants/${tenant.id}`}
                                editHref={`/tenants/${tenant.id}/edit`}
                            >
                                {tenant.status !== 'blocked' ? (
                                    <ArchiveAction
                                        href={`/tenants/${tenant.id}`}
                                        confirmMessage={`Archive ${tenant.user?.name ?? 'this tenant'}? Active leases must be terminated first.`}
                                    />
                                ) : null}
                            </RecordActions>
                        ),
                    },
                ]}
            />
        </AdminLayout>
    );
}

function ProfileCompleteness({ tenant }: { tenant: TenantRecord }) {
    const missing = [
        tenant.emergency_contact_name && tenant.emergency_contact_phone
            ? null
            : 'emergency contact',
        tenant.address ? null : 'address',
        tenant.profile_type === 'company' && !tenant.company_name
            ? 'company'
            : null,
    ].filter(Boolean);

    return missing.length === 0 ? (
        <StatusBadge value="Complete" tone="success" />
    ) : (
        <StatusBadge value={`Missing ${missing.join(', ')}`} tone="warning" />
    );
}
