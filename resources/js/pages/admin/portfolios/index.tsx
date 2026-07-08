import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import type { SharedProps } from '@/types';

type PortfolioRecord = {
    id: number;
    name_en: string;
    name_ar: string;
    code: string;
    status: string;
    contact_email?: string | null;
    contact_phone?: string | null;
    city?: string | null;
    country?: string | null;
    address?: string | null;
    default_currency: string;
    module_settings?: Record<string, boolean> | null;
    users?: Array<{ id: number }>;
};

type PageProps = SharedProps & {
    portfolios: PortfolioRecord[];
    canCreate: boolean;
};

export default function PortfoliosPage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<PortfolioRecord | null>(null);
    const form = useForm({
        name_en: '',
        name_ar: '',
        code: '',
        contact_email: '',
        contact_phone: '',
        city: '',
        country: 'Saudi Arabia',
        address: '',
        default_currency: 'SAR',
        status: 'active',
    });

    useEffect(() => {
        if (!editing) {
            form.reset();
            return;
        }

        form.setData({
            name_en: editing.name_en,
            name_ar: editing.name_ar,
            code: editing.code,
            contact_email: editing.contact_email ?? '',
            contact_phone: editing.contact_phone ?? '',
            city: editing.city ?? '',
            country: editing.country ?? 'Saudi Arabia',
            address: editing.address ?? '',
            default_currency: editing.default_currency ?? 'SAR',
            status: editing.status,
        });
    }, [editing]);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/portfolios/${editing.id}`, {
                preserveScroll: true,
                onSuccess: () => setEditing(null),
            });
            return;
        }

        form.post('/portfolios', {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout>
            <Head title="Portfolios" />
            <PageHeader
                title="Portfolios"
                description="Control portfolio identity, contact details, and enabled modules."
            />

            <div className="row g-4">
                <div className="col-xl-4">
                    <div className="pmc-card p-4">
                        <div className="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">Portfolio form</div>
                                <h2 className="h4 mb-0">
                                    {editing ? `Edit ${editing.name_en}` : 'Create portfolio'}
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
                            <div>
                                <label className="form-label pmc-form-label">English name</label>
                                <input
                                    className="form-control"
                                    value={form.data.name_en}
                                    onChange={(event) => form.setData('name_en', event.currentTarget.value)}
                                />
                            </div>
                            <div>
                                <label className="form-label pmc-form-label">Arabic name</label>
                                <input
                                    className="form-control"
                                    value={form.data.name_ar}
                                    onChange={(event) => form.setData('name_ar', event.currentTarget.value)}
                                />
                            </div>
                            {props.canCreate ? (
                                <div>
                                    <label className="form-label pmc-form-label">Code</label>
                                    <input
                                        className="form-control"
                                        value={form.data.code}
                                        onChange={(event) => form.setData('code', event.currentTarget.value)}
                                    />
                                </div>
                            ) : null}
                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Email</label>
                                    <input
                                        className="form-control"
                                        value={form.data.contact_email}
                                        onChange={(event) =>
                                            form.setData('contact_email', event.currentTarget.value)
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Phone</label>
                                    <input
                                        className="form-control"
                                        value={form.data.contact_phone}
                                        onChange={(event) =>
                                            form.setData('contact_phone', event.currentTarget.value)
                                        }
                                    />
                                </div>
                            </div>
                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">City</label>
                                    <input
                                        className="form-control"
                                        value={form.data.city}
                                        onChange={(event) => form.setData('city', event.currentTarget.value)}
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
                                {editing ? 'Update portfolio' : 'Create portfolio'}
                            </button>
                        </form>
                    </div>
                </div>

                <div className="col-xl-8">
                    <div className="pmc-card p-4">
                        <div className="pmc-kicker mb-2">Portfolio list</div>
                        <div className="table-responsive">
                            <table className="table pmc-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Users</th>
                                        <th>Status</th>
                                        <th />
                                    </tr>
                                </thead>
                                <tbody>
                                    {props.portfolios.map((portfolio) => (
                                        <tr key={portfolio.id}>
                                            <td>
                                                <div className="fw-semibold">{portfolio.name_en}</div>
                                                <div className="small text-secondary">{portfolio.name_ar}</div>
                                            </td>
                                            <td>{portfolio.code}</td>
                                            <td>{portfolio.users?.length ?? 0}</td>
                                            <td>
                                                <span className="pmc-chip pmc-chip--primary">
                                                    {portfolio.status}
                                                </span>
                                            </td>
                                            <td className="text-end">
                                                <button
                                                    type="button"
                                                    className="btn btn-outline-secondary btn-sm"
                                                    onClick={() => setEditing(portfolio)}
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
