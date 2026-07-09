import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

type PortfolioRecord = {
    id: number;
    name_en: string;
    name_ar: string;
    code: string;
    status: string;
    city?: string | null;
    country?: string | null;
    contact_email?: string | null;
    contact_phone?: string | null;
    address?: string | null;
    default_currency?: string | null;
    users_count?: number;
    assets_count?: number;
    leases_count?: number;
};

type PageProps = SharedProps & {
    portfolios: PaginatedData<PortfolioRecord>;
    filters: TableFilters;
    counts: TableCount[];
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

    const startEditing = (portfolio: PortfolioRecord) => {
        form.setData({
            name_en: portfolio.name_en,
            name_ar: portfolio.name_ar,
            code: portfolio.code,
            contact_email: portfolio.contact_email ?? '',
            contact_phone: portfolio.contact_phone ?? '',
            city: portfolio.city ?? '',
            country: portfolio.country ?? 'Saudi Arabia',
            address: portfolio.address ?? '',
            default_currency: portfolio.default_currency ?? 'SAR',
            status: portfolio.status,
        });
        setEditing(portfolio);
    };

    const clearEditing = () => {
        setEditing(null);
        form.reset();
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/portfolios/${editing.id}`, {
                preserveScroll: true,
                onSuccess: clearEditing,
            });

            return;
        }

        form.post('/portfolios', { preserveScroll: true });
    };

    return (
        <AdminLayout>
            <Head title="Portfolios" />
            <PageHeader
                title="Portfolios"
                description="Client account boundaries for owners, managers, assets, leases, and reporting."
            />

            <div className="row g-4">
                <div className="col-xl-4">
                    <div className="pmc-card p-4">
                        <div className="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Portfolio form
                                </div>
                                <h2 className="h4 mb-0">
                                    {editing
                                        ? `Edit ${editing.name_en}`
                                        : 'Create portfolio'}
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
                        {props.canCreate || editing ? (
                            <form className="d-grid gap-3" onSubmit={submit}>
                                <input
                                    className="form-control"
                                    placeholder="English name"
                                    value={form.data.name_en}
                                    onChange={(event) =>
                                        form.setData(
                                            'name_en',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                                <input
                                    className="form-control"
                                    placeholder="Arabic name"
                                    value={form.data.name_ar}
                                    onChange={(event) =>
                                        form.setData(
                                            'name_ar',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                                <input
                                    className="form-control"
                                    placeholder="Code"
                                    disabled={Boolean(editing)}
                                    value={form.data.code}
                                    onChange={(event) =>
                                        form.setData(
                                            'code',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                                <div className="row g-3">
                                    <div className="col-md-6">
                                        <input
                                            className="form-control"
                                            placeholder="City"
                                            value={form.data.city}
                                            onChange={(event) =>
                                                form.setData(
                                                    'city',
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
                                            <option value="active">
                                                Active
                                            </option>
                                            <option value="inactive">
                                                Inactive
                                            </option>
                                            <option value="archived">
                                                Archived
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <input
                                    className="form-control"
                                    placeholder="Contact email"
                                    value={form.data.contact_email}
                                    onChange={(event) =>
                                        form.setData(
                                            'contact_email',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                                <button
                                    className="btn btn-primary"
                                    disabled={form.processing}
                                >
                                    {editing
                                        ? 'Update portfolio'
                                        : 'Create portfolio'}
                                </button>
                            </form>
                        ) : (
                            <p className="text-secondary mb-0">
                                Select your portfolio from the table to update
                                its profile. Creating new portfolios is
                                restricted to the system owner.
                            </p>
                        )}
                    </div>
                </div>

                <div className="col-xl-8">
                    <div className="pmc-card p-4">
                        <DataTable
                            title="All portfolios"
                            description="Search by name, code, contact, city, or country."
                            data={props.portfolios}
                            filters={props.filters}
                            counts={props.counts}
                            basePath="/portfolios"
                            exportHref={exportUrl(
                                '/exports/portfolios',
                                props.filters,
                            )}
                            filterFields={[
                                {
                                    name: 'status',
                                    label: 'Status',
                                    options: [
                                        { label: 'All', value: 'all' },
                                        { label: 'Active', value: 'active' },
                                        {
                                            label: 'Inactive',
                                            value: 'inactive',
                                        },
                                        {
                                            label: 'Archived',
                                            value: 'archived',
                                        },
                                    ],
                                },
                            ]}
                            columns={[
                                {
                                    key: 'portfolio',
                                    label: 'Portfolio',
                                    render: (portfolio) => (
                                        <>
                                            <div className="fw-semibold">
                                                {portfolio.name_en}
                                            </div>
                                            <div className="small text-secondary">
                                                {portfolio.name_ar}
                                            </div>
                                            <span className="pmc-chip mt-2">
                                                {portfolio.code}
                                            </span>
                                        </>
                                    ),
                                },
                                {
                                    key: 'location',
                                    label: 'Location',
                                    render: (portfolio) => (
                                        <>
                                            <div>{portfolio.city ?? '-'}</div>
                                            <div className="small text-secondary">
                                                {portfolio.country ?? '-'}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'activity',
                                    label: 'Activity',
                                    render: (portfolio) => (
                                        <div className="d-flex gap-2 flex-wrap">
                                            <span className="pmc-chip pmc-chip--teal">
                                                {portfolio.assets_count ?? 0}{' '}
                                                assets
                                            </span>
                                            <span className="pmc-chip">
                                                {portfolio.users_count ?? 0}{' '}
                                                users
                                            </span>
                                            <span className="pmc-chip">
                                                {portfolio.leases_count ?? 0}{' '}
                                                leases
                                            </span>
                                        </div>
                                    ),
                                },
                                {
                                    key: 'status',
                                    label: 'Status',
                                    render: (portfolio) => (
                                        <span className="pmc-chip pmc-chip--primary">
                                            {portfolio.status}
                                        </span>
                                    ),
                                },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    className: 'text-end',
                                    render: (portfolio) => (
                                        <div className="d-flex justify-content-end gap-2 flex-wrap">
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary btn-sm"
                                                onClick={() =>
                                                    startEditing(portfolio)
                                                }
                                            >
                                                Edit
                                            </button>
                                            {props.canCreate &&
                                            portfolio.status !== 'archived' ? (
                                                <ArchiveAction
                                                    href={`/portfolios/${portfolio.id}`}
                                                    confirmMessage={`Archive portfolio ${portfolio.name_en}? Users and records stay for reporting.`}
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
