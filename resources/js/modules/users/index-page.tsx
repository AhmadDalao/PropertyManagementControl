import { Head, Link, usePage } from '@inertiajs/react';

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

type UserRecord = {
    id: number;
    portfolio_id?: number | null;
    name: string;
    email: string;
    phone?: string | null;
    preferred_locale: 'en' | 'ar';
    status: string;
    force_password_reset?: boolean;
    roles?: Array<{ name: string }>;
    tenant_profile?: { id: number; status: string } | null;
};

type PageProps = SharedProps & {
    users: PaginatedData<UserRecord>;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    roleOptions: string[];
    userInsights: {
        total: number;
        active: number;
        suspended: number;
        temporary_passwords: number;
        tenants_without_profile: number;
    };
};

export default function UsersIndexPage() {
    const { props } = usePage<PageProps>();
    const filterFields: TableFilterField[] = [
        {
            name: 'status',
            label: 'Status',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Active', value: 'active' },
                { label: 'Inactive', value: 'inactive' },
                { label: 'Suspended', value: 'suspended' },
            ],
        },
        {
            name: 'role',
            label: 'Role',
            options: [
                { label: 'All', value: 'all' },
                ...props.roleOptions.map((role) => ({
                    label: humanLabel(role),
                    value: role,
                })),
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
            <Head title="Users & Roles" />

            <WorkspaceHeader
                eyebrow="System"
                title="Users & roles"
                description="Create portal accounts, assign the smallest useful role, and open a user to manage ownership, access, and history."
                actions={[
                    {
                        label: 'Create user',
                        href: '/users/create',
                        icon: 'bi-person-plus',
                        tone: 'primary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: 'Accounts',
                        value: props.userInsights.total,
                        detail: 'All accounts in your scope',
                        icon: 'bi-people',
                        tone: 'ink',
                    },
                    {
                        label: 'Active',
                        value: props.userInsights.active,
                        detail: 'Can access the portal',
                        icon: 'bi-person-check',
                        tone: 'teal',
                    },
                    {
                        label: 'Suspended',
                        value: props.userInsights.suspended,
                        detail: 'Access currently blocked',
                        icon: 'bi-person-slash',
                        tone:
                            props.userInsights.suspended > 0 ? 'red' : 'amber',
                    },
                    {
                        label: 'Temporary passwords',
                        value: props.userInsights.temporary_passwords,
                        detail: `${props.userInsights.tenants_without_profile} tenant profiles missing`,
                        icon: 'bi-key',
                        tone:
                            props.userInsights.temporary_passwords > 0
                                ? 'amber'
                                : 'blue',
                    },
                ]}
            />

            <DataTable
                title="Access directory"
                description="Search name, email, phone, role, or account status."
                data={props.users}
                filters={props.filters}
                counts={props.counts}
                basePath="/users"
                rowHref={(user) => `/users/${user.id}`}
                exportHref={exportUrl('/exports/users', props.filters)}
                filterFields={filterFields}
                columns={[
                    {
                        key: 'name',
                        label: 'User',
                        render: (user) => (
                            <div className="pmc-primary-cell">
                                <strong>{user.name}</strong>
                                <span>{user.email}</span>
                                {user.phone ? (
                                    <small>{user.phone}</small>
                                ) : null}
                            </div>
                        ),
                    },
                    {
                        key: 'role',
                        label: 'Role',
                        render: (user) => (
                            <div className="pmc-badge-stack">
                                {(user.roles ?? []).map((role) => (
                                    <StatusBadge
                                        key={role.name}
                                        value={role.name}
                                        tone={
                                            role.name === 'superadmin'
                                                ? 'danger'
                                                : role.name === 'owner'
                                                  ? 'blue'
                                                  : role.name ===
                                                      'property_manager'
                                                    ? 'warning'
                                                    : 'neutral'
                                        }
                                    />
                                ))}
                            </div>
                        ),
                    },
                    {
                        key: 'locale',
                        label: 'Language',
                        render: (user) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {user.preferred_locale === 'ar'
                                        ? 'Arabic'
                                        : 'English'}
                                </strong>
                                <span>
                                    {user.preferred_locale.toUpperCase()}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'status',
                        label: 'Access',
                        render: (user) => (
                            <div className="pmc-badge-stack">
                                <StatusBadge value={user.status} />
                                {user.force_password_reset ? (
                                    <span>Must change password</span>
                                ) : (
                                    <span>Password confirmed</span>
                                )}
                            </div>
                        ),
                    },
                    {
                        key: 'actions',
                        label: 'Actions',
                        className: 'text-end',
                        render: (user) =>
                            user.id === props.auth.user?.id ? (
                                <div className="pmc-record-actions">
                                    <Link
                                        href="/profile"
                                        className="pmc-record-open"
                                    >
                                        My profile
                                        <i className="bi bi-arrow-up-right" />
                                    </Link>
                                </div>
                            ) : (
                                <RecordActions
                                    showHref={`/users/${user.id}`}
                                    editHref={`/users/${user.id}/edit`}
                                >
                                    {user.status !== 'suspended' ? (
                                        <ArchiveAction
                                            href={`/users/${user.id}`}
                                            confirmMessage={`Archive ${user.name}? They will no longer have an active portal account.`}
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
