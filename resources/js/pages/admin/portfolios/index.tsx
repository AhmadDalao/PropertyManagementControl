import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

import { ArchiveAction } from '@/components/archive-action';
import { CreatePageShortcut } from '@/components/create-page-shortcut';
import { DataTable, exportUrl } from '@/components/data-table';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency } from '@/lib/utils';
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
    active_leases_count?: number;
    open_maintenance_count?: number;
    valuation_total?: number | null;
    posted_revenue_total?: number | null;
    module_settings?: Record<string, boolean> | null;
};

type ModuleDefinition = {
    key: string;
    label: string;
    description: string;
};

type PortfolioInsights = {
    total: number;
    active: number;
    inactive: number;
    archived: number;
    assets: number;
    users: number;
    leases: number;
    active_leases: number;
    open_maintenance: number;
    valuation_total: number;
    posted_revenue_total: number;
};

type PageProps = SharedProps & {
    portfolios: PaginatedData<PortfolioRecord>;
    portfolioInsights: PortfolioInsights;
    filters: TableFilters;
    counts: TableCount[];
    canCreate: boolean;
    canUpdate: boolean;
    moduleDefinitions: ModuleDefinition[];
    statusOptions: string[];
};

export default function PortfoliosPage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<PortfolioRecord | null>(null);
    const isSuperadmin = props.auth.user?.roles.includes('superadmin') ?? false;
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

    const activeErrors = form.errors;
    const canUseForm = props.canCreate || Boolean(editing && props.canUpdate);

    return (
        <AdminLayout>
            <Head title="Portfolios" />

            <section className="pmc-portfolio-command mb-4">
                <div>
                    <span className="pmc-kicker">Portfolio control</span>
                    <h1>
                        {isSuperadmin
                            ? 'Launch and govern every owner account from one boundary.'
                            : 'Control the account boundary before assets, tenants, and money move.'}
                    </h1>
                    <p>
                        {isSuperadmin
                            ? 'Create client accounts, monitor operational size, archive safely, and control which modules each portfolio can use.'
                            : 'Keep your portfolio profile, enabled modules, contact details, and operational footprint clean for managers and tenants.'}
                    </p>
                    <div className="pmc-portfolio-command-meta">
                        <span>
                            <i className="bi bi-diagram-3" />
                            Client boundary
                        </span>
                        <span>
                            <i className="bi bi-toggles" />
                            Module control
                        </span>
                        <span>
                            <i className="bi bi-shield-check" />
                            Scoped records
                        </span>
                    </div>
                </div>
                <div className="pmc-portfolio-command-card">
                    <span>Managed value</span>
                    <strong>
                        {currency(
                            props.portfolioInsights.valuation_total,
                            props.app.locale,
                        )}
                    </strong>
                    <small>
                        {props.portfolioInsights.active} active portfolio
                        {props.portfolioInsights.active === 1 ? '' : 's'} ·{' '}
                        {props.portfolioInsights.assets} assets
                    </small>
                </div>
            </section>

            {props.canCreate ? (
                <CreatePageShortcut
                    href="/portfolios/create"
                    label="Create portfolio"
                    icon="bi-buildings"
                    description="Open a portfolio form for owner account boundary, contact details, currency, and module controls."
                />
            ) : null}

            <section className="pmc-portfolio-insight-grid mb-4">
                <PortfolioInsight
                    icon="bi-buildings"
                    label="Portfolios"
                    value={props.portfolioInsights.total.toLocaleString()}
                    detail={`${props.portfolioInsights.active} active · ${props.portfolioInsights.archived} archived`}
                    tone="teal"
                />
                <PortfolioInsight
                    icon="bi-people"
                    label="Users"
                    value={props.portfolioInsights.users.toLocaleString()}
                    detail="Owners, managers, tenants, and admins in scope"
                    tone="orange"
                />
                <PortfolioInsight
                    icon="bi-file-earmark-text"
                    label="Active leases"
                    value={props.portfolioInsights.active_leases.toLocaleString()}
                    detail={`${props.portfolioInsights.leases} total lease records`}
                    tone="sand"
                />
                <PortfolioInsight
                    icon="bi-wrench-adjustable"
                    label="Open service"
                    value={props.portfolioInsights.open_maintenance.toLocaleString()}
                    detail={`${currency(props.portfolioInsights.posted_revenue_total, props.app.locale)} posted revenue`}
                    tone="red"
                />
            </section>

            <div className="row g-4 align-items-start">
                <div className="col-xl-4">
                    <div className="pmc-card p-4 pmc-portfolio-form-card">
                        <div className="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Portfolio workspace
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

                        {Object.keys(activeErrors).length > 0 ? (
                            <div className="alert alert-danger small">
                                {Object.values(activeErrors)[0]}
                            </div>
                        ) : null}

                        <div className="pmc-portfolio-form-guide mb-3">
                            <i
                                className={`bi ${canUseForm ? 'bi-compass' : 'bi-eye'}`}
                            />
                            <div>
                                <strong>
                                    {canUseForm
                                        ? editing
                                            ? 'Update the client boundary'
                                            : 'Create the account shell first'
                                        : 'Read-only portfolio view'}
                                </strong>
                                <span>
                                    {canUseForm
                                        ? editing
                                            ? 'Profile changes and module switches affect owner, manager, and tenant navigation immediately.'
                                            : 'After creating a portfolio, add owner users, assets, tenants, leases, and payment records.'
                                        : 'Managers can inspect portfolio details but cannot change owner-level module access.'}
                                </span>
                            </div>
                        </div>

                        {canUseForm ? (
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
                                            {props.statusOptions.map(
                                                (status) => (
                                                    <option
                                                        key={status}
                                                        value={status}
                                                    >
                                                        {humanLabel(status)}
                                                    </option>
                                                ),
                                            )}
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
                            title={
                                isSuperadmin
                                    ? 'All portfolios'
                                    : 'Your portfolio'
                            }
                            description="Search by name, code, contact, city, country, or account owner."
                            data={props.portfolios}
                            filters={props.filters}
                            counts={props.counts}
                            basePath="/portfolios"
                            createHref={
                                props.canCreate
                                    ? '/portfolios/create'
                                    : undefined
                            }
                            createLabel="Create portfolio"
                            rowHref={(portfolio) =>
                                `/portfolios/${portfolio.id}`
                            }
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
                                        ...props.statusOptions.map(
                                            (status) => ({
                                                label: humanLabel(status),
                                                value: status,
                                            }),
                                        ),
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
                                    key: 'financials',
                                    label: 'Value',
                                    render: (portfolio) => (
                                        <>
                                            <div className="fw-semibold">
                                                {currency(
                                                    portfolio.valuation_total ??
                                                        0,
                                                    props.app.locale,
                                                    portfolio.default_currency ??
                                                        'SAR',
                                                )}
                                            </div>
                                            <div className="small text-secondary">
                                                {currency(
                                                    portfolio.posted_revenue_total ??
                                                        0,
                                                    props.app.locale,
                                                    portfolio.default_currency ??
                                                        'SAR',
                                                )}{' '}
                                                posted revenue
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'status',
                                    label: 'Status',
                                    render: (portfolio) => (
                                        <div className="d-grid gap-2 justify-items-start">
                                            <StatusChip
                                                label={humanLabel(
                                                    portfolio.status,
                                                )}
                                                tone={
                                                    portfolio.status ===
                                                    'active'
                                                        ? 'success'
                                                        : portfolio.status ===
                                                            'archived'
                                                          ? 'neutral'
                                                          : 'warning'
                                                }
                                            />
                                            <span className="small text-secondary">
                                                {portfolio.active_leases_count ??
                                                    0}{' '}
                                                active leases ·{' '}
                                                {portfolio.open_maintenance_count ??
                                                    0}{' '}
                                                open service
                                            </span>
                                        </div>
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

function PortfolioInsight({
    icon,
    label,
    value,
    detail,
    tone,
}: {
    icon: string;
    label: string;
    value: ReactNode;
    detail: string;
    tone: 'teal' | 'orange' | 'sand' | 'red';
}) {
    return (
        <div
            className={`pmc-portfolio-insight-card pmc-portfolio-insight-${tone}`}
        >
            <div>
                <i className={`bi ${icon}`} />
            </div>
            <span>{label}</span>
            <strong>{value}</strong>
            <small>{detail}</small>
        </div>
    );
}

function StatusChip({
    label,
    tone,
}: {
    label: string;
    tone: 'success' | 'warning' | 'neutral';
}) {
    return <span className={`pmc-chip pmc-chip--${tone}`}>{label}</span>;
}

function humanLabel(value: string) {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}
