import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
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
};

export default function TenantsPage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<TenantRecord | null>(null);
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
            <PageHeader
                title="Tenants"
                description="Create tenant profiles, attach user accounts, and keep emergency details nearby."
            />

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

                        <form className="d-grid gap-3" onSubmit={submit}>
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

                            <input
                                className="form-control"
                                placeholder="Name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData(
                                        'name',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <input
                                disabled={Boolean(editing)}
                                className="form-control"
                                placeholder="Email"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData(
                                        'email',
                                        event.currentTarget.value,
                                    )
                                }
                            />

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <input
                                        className="form-control"
                                        placeholder="Phone"
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
                                    placeholder="Password"
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
                                    <input
                                        className="form-control"
                                        placeholder="National ID"
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

                            <textarea
                                className="form-control"
                                rows={3}
                                placeholder="Address"
                                value={form.data.address}
                                onChange={(event) =>
                                    form.setData(
                                        'address',
                                        event.currentTarget.value,
                                    )
                                }
                            />

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
                            description="Search tenant names, email, phone, company, national ID, or emergency contact."
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
                                        </>
                                    ),
                                },
                                {
                                    key: 'leases',
                                    label: 'Leases',
                                    render: (tenant) =>
                                        tenant.leases?.length ?? 0,
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
                                        <button
                                            type="button"
                                            className="btn btn-outline-secondary btn-sm"
                                            onClick={() => startEditing(tenant)}
                                        >
                                            Edit
                                        </button>
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
