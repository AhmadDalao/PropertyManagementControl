import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import type { SharedProps } from '@/types';

type UserRecord = {
    id: number;
    portfolio_id?: number | null;
    name: string;
    email: string;
    phone?: string | null;
    preferred_locale: 'en' | 'ar';
    status: string;
    roles?: Array<{ name: string }>;
};

type PageProps = SharedProps & {
    users: UserRecord[];
    portfolioOptions: Array<{ id: number; name: string }>;
    roleOptions: string[];
};

export default function UsersPage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<UserRecord | null>(null);
    const form = useForm({
        portfolio_id: String(props.auth.user?.portfolio_id ?? props.portfolioOptions[0]?.id ?? ''),
        name: '',
        email: '',
        phone: '',
        preferred_locale: props.app.locale,
        status: 'active',
        password: '',
        role: props.roleOptions[0] ?? 'tenant',
    });

    useEffect(() => {
        if (!editing) {
            form.reset('name', 'email', 'phone', 'password');
            return;
        }

        form.setData({
            portfolio_id: String(editing.portfolio_id ?? props.auth.user?.portfolio_id ?? ''),
            name: editing.name,
            email: editing.email,
            phone: editing.phone ?? '',
            preferred_locale: editing.preferred_locale,
            status: editing.status,
            password: '',
            role: editing.roles?.[0]?.name ?? props.roleOptions[0] ?? 'tenant',
        });
    }, [editing]);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/users/${editing.id}`, {
                preserveScroll: true,
                onSuccess: () => setEditing(null),
            });
            return;
        }

        form.post('/users', { preserveScroll: true });
    };

    return (
        <AdminLayout>
            <Head title="Users" />
            <PageHeader
                title="Users"
                description="Create owners, managers, and tenant accounts with controlled roles."
            />

            <div className="row g-4">
                <div className="col-xl-4">
                    <div className="pmc-card p-4">
                        <div className="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">Account form</div>
                                <h2 className="h4 mb-0">
                                    {editing ? `Edit ${editing.name}` : 'Create user'}
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
                                    <label className="form-label pmc-form-label">Role</label>
                                    <select
                                        className="form-select"
                                        value={form.data.role}
                                        onChange={(event) => form.setData('role', event.currentTarget.value)}
                                    >
                                        {props.roleOptions.map((role) => (
                                            <option key={role} value={role}>
                                                {role}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Locale</label>
                                    <select
                                        className="form-select"
                                        value={form.data.preferred_locale}
                                        onChange={(event) =>
                                            form.setData('preferred_locale', event.currentTarget.value as 'en' | 'ar')
                                        }
                                    >
                                        <option value="en">English</option>
                                        <option value="ar">Arabic</option>
                                    </select>
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
                                <label className="form-label pmc-form-label">
                                    {editing ? 'New password (optional)' : 'Password'}
                                </label>
                                <input
                                    type="password"
                                    className="form-control"
                                    value={form.data.password}
                                    onChange={(event) => form.setData('password', event.currentTarget.value)}
                                />
                            </div>
                            <button className="btn btn-primary" disabled={form.processing}>
                                {editing ? 'Update user' : 'Create user'}
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
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Locale</th>
                                        <th>Status</th>
                                        <th />
                                    </tr>
                                </thead>
                                <tbody>
                                    {props.users.map((user) => (
                                        <tr key={user.id}>
                                            <td>
                                                <div className="fw-semibold">{user.name}</div>
                                                <div className="small text-secondary">{user.email}</div>
                                            </td>
                                            <td>{user.roles?.[0]?.name ?? '-'}</td>
                                            <td>{user.preferred_locale.toUpperCase()}</td>
                                            <td>
                                                <span className="pmc-chip pmc-chip--teal">{user.status}</span>
                                            </td>
                                            <td className="text-end">
                                                <button
                                                    type="button"
                                                    className="btn btn-outline-secondary btn-sm"
                                                    onClick={() => setEditing(user)}
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
