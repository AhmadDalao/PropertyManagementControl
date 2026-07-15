import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
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
        roles: Array<{ role: string; label: string; count: number }>;
    };
};

export default function UsersPage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<UserRecord | null>(null);
    const canCreateOwners = props.roleOptions.includes('owner');
    const manageableRoleCards = roleCards.filter((role) =>
        props.roleOptions.includes(role.role),
    );
    const form = useForm({
        portfolio_id: String(
            props.auth.user?.portfolio_id ??
                props.portfolioOptions[0]?.id ??
                '',
        ),
        name: '',
        email: '',
        phone: '',
        preferred_locale: props.app.locale,
        status: 'active',
        password: '',
        role: props.roleOptions[0] ?? 'tenant',
    });

    const startEditing = (user: UserRecord) => {
        form.setData({
            portfolio_id: String(
                user.portfolio_id ?? props.auth.user?.portfolio_id ?? '',
            ),
            name: user.name,
            email: user.email,
            phone: user.phone ?? '',
            preferred_locale: user.preferred_locale,
            status: user.status,
            password: '',
            role: user.roles?.[0]?.name ?? props.roleOptions[0] ?? 'tenant',
        });
        setEditing(user);
    };

    const clearEditing = () => {
        setEditing(null);
        form.reset('name', 'email', 'phone', 'password');
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/users/${editing.id}`, {
                preserveScroll: true,
                onSuccess: clearEditing,
            });

            return;
        }

        form.post('/users', { preserveScroll: true });
    };

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
                    label: role,
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
            <Head title="Users" />

            <section className="pmc-user-command">
                <div>
                    <div className="pmc-kicker mb-3">Access control</div>
                    <h1>
                        Create the right account, not another support ticket.
                    </h1>
                    <p>
                        Owners and managers need a direct way to create users,
                        reset access, and keep tenants connected to a proper
                        tenant profile. This workspace controls that cycle.
                    </p>
                    <div className="pmc-user-command-meta">
                        <span>
                            <i className="bi bi-shield-lock" />
                            Role scoped
                        </span>
                        <span>
                            <i className="bi bi-key" />
                            Temporary passwords tracked
                        </span>
                        <span>
                            <i className="bi bi-person-badge" />
                            Tenant profiles auto-created
                        </span>
                    </div>
                </div>

                <div className="pmc-user-insight-card">
                    <div>
                        <span>Total accounts</span>
                        <strong>{props.userInsights.total}</strong>
                    </div>
                    <div className="pmc-user-insight-grid">
                        <UserInsight
                            label="Active"
                            value={props.userInsights.active}
                        />
                        <UserInsight
                            label="Suspended"
                            value={props.userInsights.suspended}
                        />
                        <UserInsight
                            label="Temp passwords"
                            value={props.userInsights.temporary_passwords}
                        />
                        <UserInsight
                            label="Profile gaps"
                            value={props.userInsights.tenants_without_profile}
                            tone={
                                props.userInsights.tenants_without_profile > 0
                                    ? 'risk'
                                    : 'good'
                            }
                        />
                    </div>
                </div>
            </section>

            <section className="pmc-access-grid">
                {manageableRoleCards.map((role) => (
                    <div key={role.role} className="pmc-access-card">
                        <i className={`bi ${role.icon}`} />
                        <div>
                            <strong>{role.title}</strong>
                            <span>{role.description}</span>
                        </div>
                        <em>
                            {props.userInsights.roles.find(
                                (item) => item.role === role.role,
                            )?.count ?? 0}{' '}
                            accounts
                        </em>
                    </div>
                ))}
                {!canCreateOwners ? (
                    <div className="pmc-access-card is-muted">
                        <i className="bi bi-info-circle" />
                        <div>
                            <strong>Owner accounts are system-level</strong>
                            <span>
                                Portfolio owners can create managers and
                                tenants. Superadmin creates owners and global
                                system users.
                            </span>
                        </div>
                    </div>
                ) : null}
            </section>

            <div className="row g-4">
                <div className="col-xl-4">
                    <div className="pmc-card p-4">
                        <div className="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Account form
                                </div>
                                <h2 className="h4 mb-0">
                                    {editing
                                        ? `Edit ${editing.name}`
                                        : 'Create user'}
                                </h2>
                                <p className="text-secondary small mb-0 mt-2">
                                    Passwords entered here are temporary. The
                                    user will be asked to replace it from
                                    Profile.
                                </p>
                            </div>
                            {editing ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={clearEditing}
                                >
                                    Reset
                                </button>
                            ) : null}
                        </div>

                        {Object.keys(form.errors).length > 0 ? (
                            <div className="alert alert-danger py-2 small">
                                {Object.values(form.errors)[0]}
                            </div>
                        ) : null}

                        <form className="d-grid gap-3" onSubmit={submit}>
                            <div className="pmc-user-form-guide">
                                <i className="bi bi-person-check" />
                                <div>
                                    <strong>
                                        {form.data.role === 'tenant'
                                            ? 'Tenant account cycle'
                                            : 'Access rule'}
                                    </strong>
                                    <span>
                                        {form.data.role === 'tenant'
                                            ? 'Creating a tenant here also creates the tenant profile needed for leases, payments, documents, and maintenance.'
                                            : 'Assign the smallest role that can do the job. Managers run operations; tenants only see their own portal.'}
                                    </span>
                                </div>
                            </div>

                            {props.auth.user?.roles.includes('superadmin') ? (
                                <div>
                                    <label className="form-label pmc-form-label">
                                        Portfolio
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.portfolio_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'portfolio_id',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        {props.portfolioOptions.map(
                                            (portfolio) => (
                                                <option
                                                    key={portfolio.id}
                                                    value={portfolio.id}
                                                >
                                                    {portfolio.name}
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </div>
                            ) : null}
                            <div>
                                <label className="form-label pmc-form-label">
                                    Name
                                </label>
                                <input
                                    className="form-control"
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData(
                                            'name',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <label className="form-label pmc-form-label">
                                    Email
                                </label>
                                <input
                                    disabled={Boolean(editing)}
                                    className="form-control"
                                    value={form.data.email}
                                    onChange={(event) =>
                                        form.setData(
                                            'email',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Phone
                                    </label>
                                    <input
                                        className="form-control"
                                        value={form.data.phone}
                                        onChange={(event) =>
                                            form.setData(
                                                'phone',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Role
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.role}
                                        onChange={(event) =>
                                            form.setData(
                                                'role',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        {props.roleOptions.map((role) => (
                                            <option key={role} value={role}>
                                                {roleLabel(role)}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Locale
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.preferred_locale}
                                        onChange={(event) =>
                                            form.setData(
                                                'preferred_locale',
                                                event.currentTarget.value as
                                                    'en' | 'ar',
                                            )
                                        }
                                    >
                                        <option value="en">English</option>
                                        <option value="ar">Arabic</option>
                                    </select>
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Status
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.status}
                                        onChange={(event) =>
                                            form.setData(
                                                'status',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">
                                            Inactive
                                        </option>
                                        <option value="suspended">
                                            Suspended
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label className="form-label pmc-form-label">
                                    {editing
                                        ? 'Temporary password reset (optional)'
                                        : 'Temporary password'}
                                </label>
                                <input
                                    type="password"
                                    className="form-control"
                                    value={form.data.password}
                                    onChange={(event) =>
                                        form.setData(
                                            'password',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                                <div className="form-text">
                                    Leave blank while editing to keep the
                                    current password.
                                </div>
                            </div>
                            <button
                                className="btn btn-primary"
                                disabled={form.processing}
                            >
                                {editing ? 'Update user' : 'Create user'}
                            </button>
                        </form>
                    </div>
                </div>

                <div className="col-xl-8">
                    <div className="pmc-card p-4">
                        <DataTable
                            title="Access directory"
                            description="Search by name, email, phone, role, or status. Archive accounts instead of deleting audit history."
                            data={props.users}
                            filters={props.filters}
                            counts={props.counts}
                            basePath="/users"
                            createHref="/users/create"
                            createLabel="Create user"
                            rowHref={(user) => `/users/${user.id}`}
                            exportHref={exportUrl(
                                '/exports/users',
                                props.filters,
                            )}
                            filterFields={filterFields}
                            columns={[
                                {
                                    key: 'name',
                                    label: 'Name',
                                    render: (user) => (
                                        <>
                                            <div className="fw-semibold">
                                                {user.name}
                                            </div>
                                            <div className="small text-secondary">
                                                {user.email}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'role',
                                    label: 'Role',
                                    render: (user) => (
                                        <div className="d-flex gap-2 flex-wrap">
                                            {user.roles?.length ? (
                                                user.roles.map((role) => (
                                                    <span
                                                        key={role.name}
                                                        className="pmc-role-chip"
                                                    >
                                                        {roleLabel(role.name)}
                                                    </span>
                                                ))
                                            ) : (
                                                <span className="pmc-chip">
                                                    No role
                                                </span>
                                            )}
                                        </div>
                                    ),
                                },
                                {
                                    key: 'locale',
                                    label: 'Locale',
                                    render: (user) =>
                                        user.preferred_locale.toUpperCase(),
                                },
                                {
                                    key: 'status',
                                    label: 'Status',
                                    render: (user) => (
                                        <div className="d-flex gap-2 flex-wrap">
                                            <span className="pmc-chip pmc-chip--teal">
                                                {user.status}
                                            </span>
                                            {user.force_password_reset ? (
                                                <span className="pmc-chip">
                                                    temp password
                                                </span>
                                            ) : null}
                                            {user.roles?.some(
                                                (role) =>
                                                    role.name === 'tenant',
                                            ) && !user.tenant_profile ? (
                                                <span className="pmc-chip pmc-chip--primary">
                                                    profile missing
                                                </span>
                                            ) : null}
                                        </div>
                                    ),
                                },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    className: 'text-end',
                                    render: (user) =>
                                        user.id === props.auth.user?.id ? (
                                            <div className="d-flex justify-content-end">
                                                <Link
                                                    href="/profile"
                                                    className="btn btn-outline-secondary btn-sm"
                                                >
                                                    Profile
                                                </Link>
                                            </div>
                                        ) : (
                                            <div className="d-flex justify-content-end gap-2 flex-wrap">
                                                <button
                                                    type="button"
                                                    className="btn btn-outline-secondary btn-sm"
                                                    onClick={() =>
                                                        startEditing(user)
                                                    }
                                                >
                                                    Edit
                                                </button>
                                                {user.status !== 'suspended' ? (
                                                    <ArchiveAction
                                                        href={`/users/${user.id}`}
                                                        confirmMessage={`Archive ${user.name}? They will no longer have an active portal account.`}
                                                    />
                                                ) : null}
                                            </div>
                                        ),
                                },
                            ]}
                        />
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

function UserInsight({
    label,
    value,
    tone = 'default',
}: {
    label: string;
    value: number;
    tone?: 'default' | 'good' | 'risk';
}) {
    return (
        <div className={`is-${tone}`}>
            <span>{label}</span>
            <strong>{value}</strong>
        </div>
    );
}

const roleCards = [
    {
        role: 'superadmin',
        icon: 'bi-shield-lock',
        title: 'Superadmin',
        description:
            'Controls platform-wide portfolios, website CMS, users, media, and system reporting.',
    },
    {
        role: 'owner',
        icon: 'bi-building-check',
        title: 'Owner',
        description:
            'Controls one portfolio: assets, managers, tenants, leases, payments, reports, and module visibility.',
    },
    {
        role: 'property_manager',
        icon: 'bi-person-gear',
        title: 'Property manager',
        description:
            'Runs daily operations for the assigned portfolio and can manage tenants and service work.',
    },
    {
        role: 'tenant',
        icon: 'bi-person-badge',
        title: 'Tenant',
        description:
            'Uses the portal for lease documents, payment history, balance, days left, and maintenance requests.',
    },
];

function roleLabel(role: string): string {
    return (
        roleCards.find((item) => item.role === role)?.title ??
        role.replaceAll('_', ' ')
    );
}
