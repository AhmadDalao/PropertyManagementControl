import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import type { SharedProps } from '@/types';

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
    user?: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        preferred_locale: 'en' | 'ar';
    } | null;
};

type PageProps = SharedProps & {
    tenants: TenantRecord[];
    portfolioOptions: Array<{ id: number; name: string }>;
};

export default function TenantsPage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<TenantRecord | null>(null);
    const form = useForm({
        portfolio_id: String(props.auth.user?.portfolio_id ?? props.portfolioOptions[0]?.id ?? ''),
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

    useEffect(() => {
        if (!editing) {
            form.reset();
            return;
        }

        form.setData({
            portfolio_id: String(editing.portfolio_id),
            name: editing.user?.name ?? '',
            email: editing.user?.email ?? '',
            phone: editing.user?.phone ?? '',
            preferred_locale: editing.user?.preferred_locale ?? 'en',
            password: '',
            profile_type: editing.profile_type,
            national_id: editing.national_id ?? '',
            company_name: editing.company_name ?? '',
            emergency_contact_name: editing.emergency_contact_name ?? '',
            emergency_contact_phone: editing.emergency_contact_phone ?? '',
            address: editing.address ?? '',
            notes: editing.notes ?? '',
            status: editing.status,
        });
    }, [editing]);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/tenants/${editing.id}`, {
                preserveScroll: true,
                onSuccess: () => setEditing(null),
            });
            return;
        }

        form.post('/tenants', { preserveScroll: true });
    };

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
                                <div className="pmc-kicker mb-2">Tenant form</div>
                                <h2 className="h4 mb-0">
                                    {editing ? `Edit ${editing.user?.name}` : 'Create tenant'}
                                </h2>
                            </div>
                            {editing ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={() => setEditing(null)}
                                >
                                    Reset
                                </button>
                            ) : null}
                        </div>

                        <form className="d-grid gap-3" onSubmit={submit}>
                            {props.auth.user?.roles.includes('superadmin') ? (
                                <div>
                                    <label className="form-label pmc-form-label">Portfolio</label>
                                    <select
                                        className="form-select"
                                        value={form.data.portfolio_id}
                                        onChange={(event) =>
                                            form.setData('portfolio_id', event.currentTarget.value)
                                        }
                                    >
                                        {props.portfolioOptions.map((portfolio) => (
                                            <option key={portfolio.id} value={portfolio.id}>
                                                {portfolio.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            ) : null}

                            <div>
                                <label className="form-label pmc-form-label">Name</label>
                                <input
                                    className="form-control"
                                    value={form.data.name}
                                    onChange={(event) => form.setData('name', event.currentTarget.value)}
                                />
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">Email</label>
                                <input
                                    disabled={Boolean(editing)}
                                    className="form-control"
                                    value={form.data.email}
                                    onChange={(event) => form.setData('email', event.currentTarget.value)}
                                />
                            </div>

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Phone</label>
                                    <input
                                        className="form-control"
                                        value={form.data.phone}
                                        onChange={(event) => form.setData('phone', event.currentTarget.value)}
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Profile type</label>
                                    <select
                                        className="form-select"
                                        value={form.data.profile_type}
                                        onChange={(event) =>
                                            form.setData('profile_type', event.currentTarget.value)
                                        }
                                    >
                                        <option value="individual">Individual</option>
                                        <option value="company">Company</option>
                                    </select>
                                </div>
                            </div>

                            {!editing ? (
                                <div>
                                    <label className="form-label pmc-form-label">Password</label>
                                    <input
                                        type="password"
                                        className="form-control"
                                        value={form.data.password}
                                        onChange={(event) => form.setData('password', event.currentTarget.value)}
                                    />
                                </div>
                            ) : null}

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">National ID</label>
                                    <input
                                        className="form-control"
                                        value={form.data.national_id}
                                        onChange={(event) =>
                                            form.setData('national_id', event.currentTarget.value)
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Status</label>
                                    <select
                                        className="form-select"
                                        value={form.data.status}
                                        onChange={(event) => form.setData('status', event.currentTarget.value)}
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">Address</label>
                                <textarea
                                    className="form-control"
                                    rows={3}
                                    value={form.data.address}
                                    onChange={(event) => form.setData('address', event.currentTarget.value)}
                                />
                            </div>

                            <button className="btn btn-primary" disabled={form.processing}>
                                {editing ? 'Update tenant' : 'Create tenant'}
                            </button>
                        </form>
                    </div>
                </div>

                <div className="col-xl-8">
                    <div className="pmc-card p-4">
                        <div className="table-responsive">
                            <table className="table pmc-table">
                                <thead>
                                    <tr>
                                        <th>Tenant</th>
                                        <th>Profile</th>
                                        <th>Status</th>
                                        <th />
                                    </tr>
                                </thead>
                                <tbody>
                                    {props.tenants.map((tenant) => (
                                        <tr key={tenant.id}>
                                            <td>
                                                <div className="fw-semibold">{tenant.user?.name ?? '-'}</div>
                                                <div className="small text-secondary">{tenant.user?.email}</div>
                                            </td>
                                            <td>{tenant.profile_type}</td>
                                            <td>
                                                <span className="pmc-chip pmc-chip--primary">{tenant.status}</span>
                                            </td>
                                            <td className="text-end">
                                                <button
                                                    type="button"
                                                    className="btn btn-outline-secondary btn-sm"
                                                    onClick={() => setEditing(tenant)}
                                                >
                                                    Edit
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
