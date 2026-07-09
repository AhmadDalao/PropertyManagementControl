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
    module_settings?: Record<string, boolean> | null;
};

type ModuleDefinition = {
    key: string;
    label: string;
    description: string;
};

type PageProps = SharedProps & {
    portfolios: PaginatedData<PortfolioRecord>;
    filters: TableFilters;
    counts: TableCount[];
    canCreate: boolean;
    canUpdate: boolean;
    moduleDefinitions: ModuleDefinition[];
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
        module_settings: defaultModuleSettings(props.moduleDefinitions),
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
            module_settings: defaultModuleSettings(
                props.moduleDefinitions,
                portfolio.module_settings,
            ),
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
                        {props.canCreate || (editing && props.canUpdate) ? (
                            <form className="d-grid gap-3" onSubmit={submit}>
                                <div>
                                    <label className="form-label pmc-form-label">
                                        English name
                                    </label>
                                    <input
                                        className="form-control"
                                        value={form.data.name_en}
                                        onChange={(event) =>
                                            form.setData(
                                                'name_en',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <label className="form-label pmc-form-label">
                                        Arabic name
                                    </label>
                                    <input
                                        className="form-control"
                                        value={form.data.name_ar}
                                        onChange={(event) =>
                                            form.setData(
                                                'name_ar',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <label className="form-label pmc-form-label">
                                        Code
                                    </label>
                                    <input
                                        className="form-control"
                                        disabled={Boolean(editing)}
                                        value={form.data.code}
                                        onChange={(event) =>
                                            form.setData(
                                                'code',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="row g-3">
                                    <div className="col-md-6">
                                        <label className="form-label pmc-form-label">
                                            City
                                        </label>
                                        <input
                                            className="form-control"
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
                                <div className="row g-3">
                                    <div className="col-md-6">
                                        <label className="form-label pmc-form-label">
                                            Contact email
                                        </label>
                                        <input
                                            className="form-control"
                                            value={form.data.contact_email}
                                            onChange={(event) =>
                                                form.setData(
                                                    'contact_email',
                                                    event.currentTarget.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="col-md-6">
                                        <label className="form-label pmc-form-label">
                                            Contact phone
                                        </label>
                                        <input
                                            className="form-control"
                                            value={form.data.contact_phone}
                                            onChange={(event) =>
                                                form.setData(
                                                    'contact_phone',
                                                    event.currentTarget.value,
                                                )
                                            }
                                        />
                                    </div>
                                </div>
                                <div className="row g-3">
                                    <div className="col-md-7">
                                        <label className="form-label pmc-form-label">
                                            Country
                                        </label>
                                        <input
                                            className="form-control"
                                            value={form.data.country}
                                            onChange={(event) =>
                                                form.setData(
                                                    'country',
                                                    event.currentTarget.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="col-md-5">
                                        <label className="form-label pmc-form-label">
                                            Currency
                                        </label>
                                        <input
                                            className="form-control"
                                            maxLength={3}
                                            value={form.data.default_currency}
                                            onChange={(event) =>
                                                form.setData(
                                                    'default_currency',
                                                    event.currentTarget.value.toUpperCase(),
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
                                        value={form.data.address}
                                        onChange={(event) =>
                                            form.setData(
                                                'address',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>

                                <section className="pmc-module-panel">
                                    <div>
                                        <div className="pmc-kicker mb-2">
                                            Module visibility
                                        </div>
                                        <h3 className="h6 mb-1">
                                            Owner-controlled portal modules
                                        </h3>
                                        <p className="text-secondary small mb-3">
                                            Disabled modules disappear from
                                            navigation and are blocked by the
                                            backend for this portfolio.
                                        </p>
                                    </div>
                                    <div className="pmc-module-grid">
                                        {props.moduleDefinitions.map(
                                            (definition) => {
                                                const enabled = Boolean(
                                                    form.data.module_settings[
                                                        definition.key
                                                    ],
                                                );

                                                return (
                                                    <label
                                                        key={definition.key}
                                                        className={`pmc-module-toggle ${enabled ? 'is-enabled' : ''}`}
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            checked={enabled}
                                                            onChange={(event) =>
                                                                form.setData(
                                                                    'module_settings',
                                                                    {
                                                                        ...form
                                                                            .data
                                                                            .module_settings,
                                                                        [definition.key]:
                                                                            event
                                                                                .currentTarget
                                                                                .checked,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                        <span>
                                                            <strong>
                                                                {
                                                                    definition.label
                                                                }
                                                            </strong>
                                                            <small>
                                                                {
                                                                    definition.description
                                                                }
                                                            </small>
                                                        </span>
                                                        <em>
                                                            {enabled
                                                                ? 'On'
                                                                : 'Off'}
                                                        </em>
                                                    </label>
                                                );
                                            },
                                        )}
                                    </div>
                                </section>

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
                                {props.canUpdate
                                    ? 'Select your portfolio from the table to update its profile and enabled modules.'
                                    : 'Portfolio settings are owner-controlled. Managers can view the portfolio but cannot change module access.'}
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
                                        <>
                                            <div className="d-flex gap-2 flex-wrap">
                                                <span className="pmc-chip pmc-chip--teal">
                                                    {portfolio.assets_count ??
                                                        0}{' '}
                                                    assets
                                                </span>
                                                <span className="pmc-chip">
                                                    {portfolio.users_count ?? 0}{' '}
                                                    users
                                                </span>
                                                <span className="pmc-chip">
                                                    {portfolio.leases_count ??
                                                        0}{' '}
                                                    leases
                                                </span>
                                            </div>
                                            <div className="small text-secondary mt-2">
                                                {enabledModuleCount(
                                                    props.moduleDefinitions,
                                                    portfolio.module_settings,
                                                )}{' '}
                                                of{' '}
                                                {props.moduleDefinitions.length}{' '}
                                                modules enabled
                                            </div>
                                        </>
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
                                            {props.canUpdate ? (
                                                <button
                                                    type="button"
                                                    className="btn btn-outline-secondary btn-sm"
                                                    onClick={() =>
                                                        startEditing(portfolio)
                                                    }
                                                >
                                                    Edit
                                                </button>
                                            ) : null}
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

function defaultModuleSettings(
    definitions: ModuleDefinition[],
    source?: Record<string, boolean> | null,
) {
    return Object.fromEntries(
        definitions.map((definition) => [
            definition.key,
            source?.[definition.key] ?? true,
        ]),
    );
}

function enabledModuleCount(
    definitions: ModuleDefinition[],
    source?: Record<string, boolean> | null,
) {
    return definitions.filter((definition) => source?.[definition.key] ?? true)
        .length;
}
