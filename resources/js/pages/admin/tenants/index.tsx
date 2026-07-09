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

type TenantRecord = {
    id: number;
    portfolio_id: number;
    profile_type: string;
    national_id?: string | null;
    company_name?: string | null;
    emergency_contact_name?: string | null;
    emergency_contact_phone?: string | null;
    address?: string | null;
    status: string;
    notes?: string | null;
    leases?: Array<{ id: number; code: string; status: string }>;
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

export default function TenantsPage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<TenantRecord | null>(null);
    const tenantReadiness =
        props.tenantInsights.total > 0
            ? Math.max(
                  0,
                  Math.round(
                      ((props.tenantInsights.total -
                          props.tenantInsights.without_active_lease -
                          props.tenantInsights.missing_emergency) /
                          props.tenantInsights.total) *
                          100,
                  ),
              )
            : 0;
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
        password: '',
        profile_type: 'individual',
        national_id: '',
        company_name: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        address: '',
        notes: '',
        status: 'active',
    });

    const startEditing = (tenant: TenantRecord) => {
        form.setData({
            portfolio_id: String(tenant.portfolio_id),
            name: tenant.user?.name ?? '',
            email: tenant.user?.email ?? '',
            phone: tenant.user?.phone ?? '',
            preferred_locale: tenant.user?.preferred_locale ?? 'en',
            password: '',
            profile_type: tenant.profile_type,
            national_id: tenant.national_id ?? '',
            company_name: tenant.company_name ?? '',
            emergency_contact_name: tenant.emergency_contact_name ?? '',
            emergency_contact_phone: tenant.emergency_contact_phone ?? '',
            address: tenant.address ?? '',
            notes: tenant.notes ?? '',
            status: tenant.status,
        });
        setEditing(tenant);
    };

    const clearEditing = () => {
        setEditing(null);
        form.reset();
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/tenants/${editing.id}`, {
                preserveScroll: true,
                onSuccess: clearEditing,
            });

            return;
        }

        form.post('/tenants', { preserveScroll: true });
    };

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

            <section className="pmc-tenant-workspace-hero">
                <div>
                    <div className="pmc-kicker mb-3">Tenant onboarding</div>
                    <h1>
                        Build tenant profiles that are ready for leases and
                        service.
                    </h1>
                    <p>
                        A tenant is more than a login. Capture identity,
                        emergency details, company data, address, notes, and
                        lease readiness before contracts and maintenance start.
                    </p>
                    <div className="pmc-tenant-workspace-meta">
                        <span>
                            <i className="bi bi-person-badge" />
                            Portal account created
                        </span>
                        <span>
                            <i className="bi bi-file-earmark-text" />
                            Lease-ready profile
                        </span>
                        <span>
                            <i className="bi bi-tools" />
                            Maintenance intake ready
                        </span>
                    </div>
                </div>

                <div className="pmc-tenant-readiness-card">
                    <div>
                        <span>Tenant readiness</span>
                        <strong>{tenantReadiness}%</strong>
                    </div>
                    <div className="pmc-tenant-insight-grid">
                        <TenantInsight
                            label="Total"
                            value={props.tenantInsights.total}
                        />
                        <TenantInsight
                            label="Active"
                            value={props.tenantInsights.active}
                            tone="good"
                        />
                        <TenantInsight
                            label="No active lease"
                            value={props.tenantInsights.without_active_lease}
                            tone={
                                props.tenantInsights.without_active_lease > 0
                                    ? 'risk'
                                    : 'good'
                            }
                        />
                        <TenantInsight
                            label="Emergency gaps"
                            value={props.tenantInsights.missing_emergency}
                            tone={
                                props.tenantInsights.missing_emergency > 0
                                    ? 'risk'
                                    : 'good'
                            }
                        />
                    </div>
                </div>
            </section>

            <section className="pmc-tenant-onboarding-grid">
                {tenantWorkflowCards.map((card) => (
                    <Link key={card.title} href={card.href}>
                        <i className={`bi ${card.icon}`} />
                        <strong>{card.title}</strong>
                        <span>{card.body}</span>
                    </Link>
                ))}
            </section>

            <div className="row g-4">
                <div className="col-xl-4">
                    <div className="pmc-card p-4">
                        <div className="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Tenant form
                                </div>
                                <h2 className="h4 mb-0">
                                    {editing
                                        ? `Edit ${editing.user?.name}`
                                        : 'Create tenant'}
                                </h2>
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
                            <div className="pmc-tenant-form-guide">
                                <i className="bi bi-clipboard2-check" />
                                <div>
                                    <strong>
                                        {editing
                                            ? 'Keep profile data useful'
                                            : 'Tenant portal account'}
                                    </strong>
                                    <span>
                                        {editing
                                            ? 'Emergency contacts and address are used by maintenance and owner reports. Do not leave them as mystery meat.'
                                            : 'Creating a tenant here creates the login user and the tenant profile needed for leases, documents, payments, and maintenance.'}
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
                                    Tenant name
                                </label>
                                <input
                                    className="form-control"
                                    placeholder="Full name or primary contact"
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
                                    placeholder="tenant@example.com"
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
                                        placeholder="+966..."
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
                                        Profile type
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.profile_type}
                                        onChange={(event) =>
                                            form.setData(
                                                'profile_type',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="individual">
                                            Individual
                                        </option>
                                        <option value="company">Company</option>
                                    </select>
                                </div>
                            </div>

                            {!editing ? (
                                <input
                                    type="password"
                                    className="form-control"
                                    placeholder="Temporary password"
                                    value={form.data.password}
                                    onChange={(event) =>
                                        form.setData(
                                            'password',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            ) : null}

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        National ID / CR
                                    </label>
                                    <input
                                        className="form-control"
                                        placeholder="ID, Iqama, CR, or passport"
                                        value={form.data.national_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'national_id',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
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
                                        <option value="blocked">Blocked</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">
                                    Company name
                                </label>
                                <input
                                    className="form-control"
                                    placeholder="Optional for commercial tenants"
                                    value={form.data.company_name}
                                    onChange={(event) =>
                                        form.setData(
                                            'company_name',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            </div>

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Emergency contact
                                    </label>
                                    <input
                                        className="form-control"
                                        placeholder="Name"
                                        value={form.data.emergency_contact_name}
                                        onChange={(event) =>
                                            form.setData(
                                                'emergency_contact_name',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Emergency phone
                                    </label>
                                    <input
                                        className="form-control"
                                        placeholder="+966..."
                                        value={
                                            form.data.emergency_contact_phone
                                        }
                                        onChange={(event) =>
                                            form.setData(
                                                'emergency_contact_phone',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">
                                    Address
                                </label>
                                <textarea
                                    className="form-control"
                                    rows={3}
                                    placeholder="Current address or billing address"
                                    value={form.data.address}
                                    onChange={(event) =>
                                        form.setData(
                                            'address',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">
                                    Internal notes
                                </label>
                                <textarea
                                    className="form-control"
                                    rows={3}
                                    placeholder="Owner/manager notes"
                                    value={form.data.notes}
                                    onChange={(event) =>
                                        form.setData(
                                            'notes',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            </div>

                            <button
                                className="btn btn-primary"
                                disabled={form.processing}
                            >
                                {editing ? 'Update tenant' : 'Create tenant'}
                            </button>
                        </form>
                    </div>
                </div>

                <div className="col-xl-8">
                    <div className="pmc-card p-4">
                        <DataTable
                            title="Tenant directory"
                            description="Search tenant names, email, phone, company, national ID, address, or emergency contact."
                            data={props.tenants}
                            filters={props.filters}
                            counts={props.counts}
                            basePath="/tenants"
                            exportHref={exportUrl(
                                '/exports/tenants',
                                props.filters,
                            )}
                            filterFields={filterFields}
                            columns={[
                                {
                                    key: 'tenant',
                                    label: 'Tenant',
                                    render: (tenant) => (
                                        <>
                                            <div className="fw-semibold">
                                                {tenant.user?.name ?? '-'}
                                            </div>
                                            <div className="small text-secondary">
                                                {tenant.user?.email ?? '-'}
                                            </div>
                                            {tenant.user?.phone ? (
                                                <div className="small text-secondary">
                                                    {tenant.user.phone}
                                                </div>
                                            ) : null}
                                        </>
                                    ),
                                },
                                {
                                    key: 'profile',
                                    label: 'Profile',
                                    render: (tenant) => (
                                        <>
                                            <div>{tenant.profile_type}</div>
                                            <div className="small text-secondary">
                                                {tenant.company_name ??
                                                    tenant.national_id ??
                                                    '-'}
                                            </div>
                                            <ProfileCompleteness
                                                tenant={tenant}
                                            />
                                        </>
                                    ),
                                },
                                {
                                    key: 'leases',
                                    label: 'Leases',
                                    render: (tenant) => (
                                        <div className="d-flex gap-2 flex-wrap">
                                            <span className="pmc-chip pmc-chip--teal">
                                                {tenant.active_leases_count ??
                                                    0}{' '}
                                                active
                                            </span>
                                            <span className="pmc-chip">
                                                {tenant.leases_count ??
                                                    tenant.leases?.length ??
                                                    0}{' '}
                                                total
                                            </span>
                                            {(tenant.open_requests_count ?? 0) >
                                            0 ? (
                                                <span className="pmc-chip pmc-chip--primary">
                                                    {tenant.open_requests_count}{' '}
                                                    service
                                                </span>
                                            ) : null}
                                        </div>
                                    ),
                                },
                                {
                                    key: 'status',
                                    label: 'Status',
                                    render: (tenant) => (
                                        <span className="pmc-chip pmc-chip--primary">
                                            {tenant.status}
                                        </span>
                                    ),
                                },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    className: 'text-end',
                                    render: (tenant) => (
                                        <div className="d-flex justify-content-end gap-2 flex-wrap">
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary btn-sm"
                                                onClick={() =>
                                                    startEditing(tenant)
                                                }
                                            >
                                                Edit
                                            </button>
                                            {tenant.status !== 'blocked' ? (
                                                <ArchiveAction
                                                    href={`/tenants/${tenant.id}`}
                                                    confirmMessage={`Archive ${tenant.user?.name ?? 'this tenant'}? Active leases must be terminated first.`}
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

function TenantInsight({
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

function ProfileCompleteness({ tenant }: { tenant: TenantRecord }) {
    const missing = [
        tenant.emergency_contact_name && tenant.emergency_contact_phone
            ? null
            : 'emergency',
        tenant.address ? null : 'address',
        tenant.profile_type === 'company' && !tenant.company_name
            ? 'company'
            : null,
    ].filter(Boolean);

    if (missing.length === 0) {
        return <span className="pmc-chip pmc-chip--teal mt-2">Complete</span>;
    }

    return (
        <span className="pmc-chip pmc-chip--primary mt-2">
            Missing {missing.join(', ')}
        </span>
    );
}

const tenantWorkflowCards = [
    {
        icon: 'bi-person-plus',
        title: 'Create profile',
        body: 'Capture identity, emergency contact, address, and portal login.',
        href: '/tenants',
    },
    {
        icon: 'bi-file-earmark-plus',
        title: 'Create lease',
        body: 'Attach the tenant to a rentable asset and generate installments.',
        href: '/leases',
    },
    {
        icon: 'bi-folder2-open',
        title: 'Attach documents',
        body: 'Keep signed contracts, IDs, and tenant statements traceable.',
        href: '/documents',
    },
    {
        icon: 'bi-tools',
        title: 'Handle service',
        body: 'Let tenants submit maintenance and track owner/manager updates.',
        href: '/maintenance-requests',
    },
];
