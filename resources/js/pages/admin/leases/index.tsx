import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { ArchiveAction } from '@/components/archive-action';
import { CreatePageShortcut } from '@/components/create-page-shortcut';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency, humanDate } from '@/lib/utils';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

type LeaseRecord = {
    id: number;
    portfolio_id: number;
    tenant_profile_id: number;
    leaseable_id: number;
    code: string;
    status: string;
    payment_frequency: string;
    started_at?: string | null;
    ends_at?: string | null;
    signed_at?: string | null;
    rent_amount: number;
    deposit_amount: number;
    tax_amount: number;
    discount_amount: number;
    currency: string;
    billing_day?: number | null;
    notes?: string | null;
    tenant_profile?: { user?: { name?: string | null; email?: string | null } };
    leaseable?: { title_en?: string | null; code?: string | null };
    total_due: number;
    total_paid: number;
    balance_remaining: number;
    days_remaining?: number | null;
    installment_count: number;
    overdue_count: number;
    open_installment_count: number;
    paid_percent: number;
    next_due_date?: string | null;
    next_due_amount?: number | null;
    installments: LeaseInstallmentRecord[];
    documents: LeaseDocumentRecord[];
};

type LeaseInstallmentRecord = {
    id: number;
    sequence: number;
    line_type: string;
    label: string;
    period_start?: string | null;
    period_end?: string | null;
    due_date?: string | null;
    amount_due: number;
    amount_paid: number;
    remaining_amount: number;
    status: string;
};

type LeaseDocumentRecord = {
    id: number;
    type: string;
    title_en: string;
    original_name?: string | null;
    download_url: string;
};

type PageProps = SharedProps & {
    leases: PaginatedData<LeaseRecord>;
    leaseInsights: {
        total: number;
        active: number;
        draft: number;
        unsigned: number;
        expiring_soon: number;
        overdue: number;
        total_due: number;
        total_paid: number;
        balance_remaining: number;
    };
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    tenantOptions: Array<{ id: number; user?: { name: string } }>;
    assetOptions: Array<{ id: number; title_en: string }>;
    leaseOptions: Array<{ id: number; code: string }>;
};

export default function LeasesPage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<LeaseRecord | null>(null);
    const [selectedLeaseId, setSelectedLeaseId] = useState<number | null>(null);
    const [signedContractLease, setSignedContractLease] = useState<string>('');
    const signedContractForm = useForm<{ signed_contract: File | null }>({
        signed_contract: null,
    });

    const form = useForm({
        portfolio_id: String(
            props.auth.user?.portfolio_id ??
                props.portfolioOptions[0]?.id ??
                '',
        ),
        tenant_profile_id: String(props.tenantOptions[0]?.id ?? ''),
        asset_id: String(props.assetOptions[0]?.id ?? ''),
        status: 'active',
        payment_frequency: 'monthly',
        started_at: '',
        ends_at: '',
        signed_at: '',
        rent_amount: 0,
        deposit_amount: 0,
        tax_amount: 0,
        discount_amount: 0,
        currency: 'SAR',
        billing_day: 1,
        notes: '',
        resync_installments: false,
    });

    const startEditing = (lease: LeaseRecord) => {
        form.setData({
            portfolio_id: String(lease.portfolio_id),
            tenant_profile_id: String(lease.tenant_profile_id),
            asset_id: String(lease.leaseable_id),
            status: lease.status,
            payment_frequency: lease.payment_frequency,
            started_at: lease.started_at ?? '',
            ends_at: lease.ends_at ?? '',
            signed_at: lease.signed_at ?? '',
            rent_amount: lease.rent_amount,
            deposit_amount: lease.deposit_amount,
            tax_amount: lease.tax_amount,
            discount_amount: lease.discount_amount,
            currency: lease.currency,
            billing_day: lease.billing_day ?? 1,
            notes: lease.notes ?? '',
            resync_installments: false,
        });
        setEditing(lease);
    };

    const clearEditing = () => {
        setEditing(null);
        form.reset();
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/leases/${editing.id}`, {
                preserveScroll: true,
                onSuccess: clearEditing,
            });

            return;
        }

        form.post('/leases', { preserveScroll: true });
    };

    const selectedLease =
        props.leases.data.find((lease) => lease.id === selectedLeaseId) ??
        props.leases.data[0] ??
        null;

    const uploadSignedContract = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!signedContractLease) {
            return;
        }

        signedContractForm.post(
            `/leases/${signedContractLease}/signed-contract`,
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    signedContractForm.reset();
                    setSignedContractLease('');
                },
            },
        );
    };

    const filterFields: TableFilterField[] = [
        {
            name: 'status',
            label: 'Status',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Draft', value: 'draft' },
                { label: 'Active', value: 'active' },
                { label: 'Expired', value: 'expired' },
                { label: 'Terminated', value: 'terminated' },
            ],
        },
        {
            name: 'payment_frequency',
            label: 'Frequency',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Monthly', value: 'monthly' },
                { label: 'Quarterly', value: 'quarterly' },
                { label: 'Yearly', value: 'yearly' },
            ],
        },
        { name: 'date_from', label: 'From', type: 'date' },
        { name: 'date_to', label: 'To', type: 'date' },
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
            <Head title="Leases" />

            <section className="pmc-lease-command mb-4">
                <div>
                    <span className="pmc-kicker">Contract control</span>
                    <h1>Build the lease once. Let every balance follow it.</h1>
                    <p>
                        Create contracts against rentable assets, generate the
                        billing schedule by frequency, upload signed PDFs, and
                        keep payments tied to the exact due line.
                    </p>
                    <CreatePageShortcut
                        href="/leases/create"
                        label="Create lease"
                        icon="bi-file-earmark-plus"
                        description="Open a lease form to select tenant, asset, dates, rent, deposit, and payment frequency."
                    />
                    <div className="pmc-lease-command-meta">
                        <span>
                            <i className="bi bi-calendar2-check" />
                            Frequency-aware schedules
                        </span>
                        <span>
                            <i className="bi bi-file-earmark-text" />
                            Contract and statement PDFs
                        </span>
                        <span>
                            <i className="bi bi-cash-coin" />
                            Payment allocation ready
                        </span>
                    </div>
                </div>
                <div className="pmc-lease-command-card">
                    <span>Open lease balance</span>
                    <strong>
                        {currency(
                            props.leaseInsights.balance_remaining,
                            props.app.locale,
                            selectedLease?.currency ?? 'SAR',
                        )}
                    </strong>
                    <small>
                        {props.leaseInsights.active} active contract
                        {props.leaseInsights.active === 1 ? '' : 's'}
                    </small>
                </div>
            </section>

            <section className="pmc-lease-insight-grid mb-4">
                <LeaseInsightCard
                    icon="bi-file-earmark-check"
                    label="Active leases"
                    value={String(props.leaseInsights.active)}
                    detail={`${props.leaseInsights.total} total contracts`}
                    tone="teal"
                />
                <LeaseInsightCard
                    icon="bi-pen"
                    label="Unsigned"
                    value={String(props.leaseInsights.unsigned)}
                    detail="Need signed document follow-up"
                    tone="orange"
                />
                <LeaseInsightCard
                    icon="bi-calendar-event"
                    label="Expiring soon"
                    value={String(props.leaseInsights.expiring_soon)}
                    detail="Ending in the next 60 days"
                    tone="sand"
                />
                <LeaseInsightCard
                    icon="bi-exclamation-triangle"
                    label="Overdue schedules"
                    value={String(props.leaseInsights.overdue)}
                    detail={currency(
                        props.leaseInsights.balance_remaining,
                        props.app.locale,
                        selectedLease?.currency ?? 'SAR',
                    )}
                    tone="red"
                />
            </section>

            <div className="row g-4">
                <div
                    className={`col-xl-4 pmc-index-form-column ${editing ? 'is-editing' : 'is-idle'}`}
                >
                    <div className="pmc-card p-4 mb-4 pmc-lease-form-card">
                        <div className="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Lease form
                                </div>
                                <h2 className="h4 mb-0">
                                    {editing
                                        ? `Edit ${editing.code}`
                                        : 'Create lease'}
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
                            <div className="alert alert-danger small">
                                {Object.values(form.errors)[0]}
                            </div>
                        ) : null}

                        <div className="pmc-lease-form-guide mb-3">
                            <i className="bi bi-info-circle" />
                            <div>
                                <strong>
                                    {editing
                                        ? 'Editing preserves the money trail.'
                                        : 'The schedule is generated from this form.'}
                                </strong>
                                <span>
                                    {editing
                                        ? 'Only status, signature date, notes, and safe resync are editable after creation. If money exists, do not rewrite the billing schedule.'
                                        : 'Frequency controls how many rent lines are created. Billing day controls when each line is due.'}
                                </span>
                            </div>
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

                            <div>
                                <label className="form-label pmc-form-label">
                                    Tenant
                                </label>
                                <select
                                    className="form-select"
                                    value={form.data.tenant_profile_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'tenant_profile_id',
                                            event.currentTarget.value,
                                        )
                                    }
                                >
                                    {props.tenantOptions.map((tenant) => (
                                        <option
                                            key={tenant.id}
                                            value={tenant.id}
                                        >
                                            {tenant.user?.name ??
                                                `Tenant #${tenant.id}`}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">
                                    Asset
                                </label>
                                <select
                                    className="form-select"
                                    value={form.data.asset_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'asset_id',
                                            event.currentTarget.value,
                                        )
                                    }
                                >
                                    {props.assetOptions.map((asset) => (
                                        <option key={asset.id} value={asset.id}>
                                            {asset.title_en}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="row g-3">
                                <div className="col-md-4">
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
                                        <option value="draft">Draft</option>
                                        <option value="active">Active</option>
                                        <option value="expired">Expired</option>
                                        <option value="terminated">
                                            Terminated
                                        </option>
                                    </select>
                                </div>
                                <div className="col-md-4">
                                    <label className="form-label pmc-form-label">
                                        Frequency
                                    </label>
                                    <select
                                        className="form-select"
                                        value={form.data.payment_frequency}
                                        disabled={Boolean(editing)}
                                        onChange={(event) =>
                                            form.setData(
                                                'payment_frequency',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">
                                            Quarterly
                                        </option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                                <div className="col-md-4">
                                    <label className="form-label pmc-form-label">
                                        Billing day
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="31"
                                        className="form-control"
                                        disabled={Boolean(editing)}
                                        value={form.data.billing_day}
                                        onChange={(event) =>
                                            form.setData(
                                                'billing_day',
                                                Number(
                                                    event.currentTarget.value,
                                                ),
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Start
                                    </label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        disabled={Boolean(editing)}
                                        value={form.data.started_at}
                                        onChange={(event) =>
                                            form.setData(
                                                'started_at',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        End
                                    </label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        disabled={Boolean(editing)}
                                        value={form.data.ends_at}
                                        onChange={(event) =>
                                            form.setData(
                                                'ends_at',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Tax per cycle
                                    </label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        disabled={Boolean(editing)}
                                        value={form.data.tax_amount}
                                        onChange={(event) =>
                                            form.setData(
                                                'tax_amount',
                                                Number(
                                                    event.currentTarget.value,
                                                ),
                                            )
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Discount per cycle
                                    </label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        disabled={Boolean(editing)}
                                        value={form.data.discount_amount}
                                        onChange={(event) =>
                                            form.setData(
                                                'discount_amount',
                                                Number(
                                                    event.currentTarget.value,
                                                ),
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Rent
                                    </label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        disabled={Boolean(editing)}
                                        value={form.data.rent_amount}
                                        onChange={(event) =>
                                            form.setData(
                                                'rent_amount',
                                                Number(
                                                    event.currentTarget.value,
                                                ),
                                            )
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Deposit
                                    </label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        disabled={Boolean(editing)}
                                        value={form.data.deposit_amount}
                                        onChange={(event) =>
                                            form.setData(
                                                'deposit_amount',
                                                Number(
                                                    event.currentTarget.value,
                                                ),
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Signed at
                                    </label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={form.data.signed_at}
                                        onChange={(event) =>
                                            form.setData(
                                                'signed_at',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Currency
                                    </label>
                                    <input
                                        className="form-control"
                                        maxLength={3}
                                        disabled={Boolean(editing)}
                                        value={form.data.currency}
                                        onChange={(event) =>
                                            form.setData(
                                                'currency',
                                                event.currentTarget.value.toUpperCase(),
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">
                                    Notes
                                </label>
                                <textarea
                                    className="form-control"
                                    rows={3}
                                    value={form.data.notes}
                                    onChange={(event) =>
                                        form.setData(
                                            'notes',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            </div>

                            {editing ? (
                                <label className="form-check">
                                    <input
                                        type="checkbox"
                                        className="form-check-input"
                                        checked={form.data.resync_installments}
                                        onChange={(event) =>
                                            form.setData(
                                                'resync_installments',
                                                event.currentTarget.checked,
                                            )
                                        }
                                    />
                                    <span className="form-check-label">
                                        Rebuild installments if no payments are
                                        recorded
                                    </span>
                                </label>
                            ) : null}

                            <button
                                className="btn btn-primary"
                                disabled={form.processing}
                            >
                                {editing ? 'Update lease' : 'Create lease'}
                            </button>
                        </form>
                    </div>

                    <div className="pmc-card p-4">
                        <div className="pmc-kicker mb-2">
                            Signed contract upload
                        </div>
                        <form
                            className="d-grid gap-3"
                            onSubmit={uploadSignedContract}
                        >
                            <select
                                className="form-select"
                                value={signedContractLease}
                                onChange={(event) =>
                                    setSignedContractLease(
                                        event.currentTarget.value,
                                    )
                                }
                            >
                                <option value="">Choose lease</option>
                                {props.leaseOptions.map((lease) => (
                                    <option key={lease.id} value={lease.id}>
                                        {lease.code}
                                    </option>
                                ))}
                            </select>
                            <input
                                type="file"
                                className="form-control"
                                accept=".pdf,application/pdf"
                                onChange={(event) =>
                                    signedContractForm.setData(
                                        'signed_contract',
                                        event.currentTarget.files?.[0] ?? null,
                                    )
                                }
                            />
                            <small className="text-secondary">
                                Upload the signed-off contract as a PDF only.
                            </small>
                            <button
                                className="btn btn-outline-secondary"
                                disabled={signedContractForm.processing}
                            >
                                Upload signed contract
                            </button>
                        </form>
                    </div>
                </div>

                <div className="col-xl-8">
                    {selectedLease ? (
                        <LeaseDetailPanel
                            lease={selectedLease}
                            locale={props.app.locale}
                        />
                    ) : null}

                    <div className="pmc-card p-4">
                        <DataTable
                            title="Lease lifecycle"
                            description="Search lease codes, tenants, or rented assets."
                            data={props.leases}
                            filters={props.filters}
                            counts={props.counts}
                            basePath="/leases"
                            createHref="/leases/create"
                            createLabel="Create lease"
                            rowHref={(lease) => `/leases/${lease.id}`}
                            exportHref={exportUrl(
                                '/exports/leases',
                                props.filters,
                            )}
                            filterFields={filterFields}
                            columns={[
                                {
                                    key: 'lease',
                                    label: 'Lease',
                                    render: (lease) => (
                                        <>
                                            <div className="fw-semibold">
                                                {lease.code}
                                            </div>
                                            <div className="d-flex gap-2 mt-2 flex-wrap">
                                                <span className="pmc-chip pmc-chip--primary">
                                                    {lease.status}
                                                </span>
                                                {lease.signed_at ? (
                                                    <span className="pmc-chip pmc-chip--teal">
                                                        signed
                                                    </span>
                                                ) : (
                                                    <span className="pmc-chip">
                                                        unsigned
                                                    </span>
                                                )}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'tenant',
                                    label: 'Tenant',
                                    render: (lease) => (
                                        <>
                                            <div>
                                                {lease.tenant_profile?.user
                                                    ?.name ?? '-'}
                                            </div>
                                            <div className="small text-secondary">
                                                {lease.leaseable?.title_en ??
                                                    '-'}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'period',
                                    label: 'Period',
                                    render: (lease) => (
                                        <>
                                            <div>
                                                {humanDate(
                                                    lease.started_at,
                                                    props.app.locale,
                                                )}
                                            </div>
                                            <div className="small text-secondary">
                                                to{' '}
                                                {humanDate(
                                                    lease.ends_at,
                                                    props.app.locale,
                                                )}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'rent',
                                    label: 'Financials',
                                    render: (lease) => (
                                        <>
                                            <div className="fw-semibold">
                                                {currency(
                                                    lease.balance_remaining,
                                                    props.app.locale,
                                                    lease.currency,
                                                )}{' '}
                                                left
                                            </div>
                                            <div className="small text-secondary">
                                                {currency(
                                                    lease.total_paid,
                                                    props.app.locale,
                                                    lease.currency,
                                                )}{' '}
                                                paid /{' '}
                                                {currency(
                                                    lease.total_due,
                                                    props.app.locale,
                                                    lease.currency,
                                                )}{' '}
                                                due
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'next',
                                    label: 'Next due',
                                    render: (lease) => (
                                        <>
                                            <div>
                                                {humanDate(
                                                    lease.next_due_date,
                                                    props.app.locale,
                                                )}
                                            </div>
                                            <div className="small text-secondary">
                                                {lease.next_due_amount
                                                    ? currency(
                                                          lease.next_due_amount,
                                                          props.app.locale,
                                                          lease.currency,
                                                      )
                                                    : 'No open balance'}
                                            </div>
                                            {lease.overdue_count > 0 ? (
                                                <span className="pmc-chip mt-2">
                                                    {lease.overdue_count}{' '}
                                                    overdue
                                                </span>
                                            ) : null}
                                        </>
                                    ),
                                },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    className: 'text-end',
                                    render: (lease) => (
                                        <div className="d-flex justify-content-end gap-2 flex-wrap">
                                            <a
                                                href={`/leases/${lease.id}/contract`}
                                                className="btn btn-outline-secondary btn-sm"
                                            >
                                                Contract
                                            </a>
                                            <a
                                                href={`/leases/${lease.id}/statement`}
                                                className="btn btn-outline-secondary btn-sm"
                                            >
                                                Statement
                                            </a>
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary btn-sm"
                                                onClick={() =>
                                                    setSelectedLeaseId(lease.id)
                                                }
                                            >
                                                Details
                                            </button>
                                            <button
                                                type="button"
                                                className="btn btn-primary btn-sm"
                                                onClick={() =>
                                                    startEditing(lease)
                                                }
                                            >
                                                Edit
                                            </button>
                                            {lease.status !== 'terminated' ? (
                                                <ArchiveAction
                                                    href={`/leases/${lease.id}`}
                                                    label="Terminate"
                                                    confirmMessage={`Terminate lease ${lease.code}? The asset will become vacant if no other active lease exists.`}
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

function LeaseDetailPanel({
    lease,
    locale,
}: {
    lease: LeaseRecord;
    locale: 'en' | 'ar';
}) {
    const paidPercent =
        lease.total_due > 0
            ? Math.min(100, (lease.total_paid / lease.total_due) * 100)
            : 0;

    return (
        <section className="pmc-card p-4 mb-4 pmc-lease-control-panel">
            <div className="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                <div>
                    <div className="pmc-kicker mb-2">Selected lease</div>
                    <h2 className="h4 mb-1">{lease.code}</h2>
                    <p className="text-secondary mb-0">
                        {lease.tenant_profile?.user?.name ?? 'No tenant'} ·{' '}
                        {lease.leaseable?.title_en ?? 'No asset'}
                    </p>
                </div>
                <div className="d-flex gap-2 flex-wrap">
                    <a
                        href={`/leases/${lease.id}/contract`}
                        className="btn btn-outline-secondary btn-sm"
                    >
                        Contract
                    </a>
                    <a
                        href={`/leases/${lease.id}/statement`}
                        className="btn btn-outline-secondary btn-sm"
                    >
                        Statement
                    </a>
                </div>
            </div>

            <div className="pmc-lease-kpi-grid">
                <LeaseKpi
                    label="Paid"
                    value={currency(lease.total_paid, locale, lease.currency)}
                    detail={`${Math.round(paidPercent)}% collected`}
                />
                <LeaseKpi
                    label="Remaining"
                    value={currency(
                        lease.balance_remaining,
                        locale,
                        lease.currency,
                    )}
                    detail={`${lease.open_installment_count} open / ${lease.installment_count} total`}
                />
                <LeaseKpi
                    label="Schedule"
                    value={humanFrequency(lease.payment_frequency)}
                    detail={`Billing day ${lease.billing_day ?? 'start'}`}
                />
                <LeaseKpi
                    label="Days left"
                    value={String(lease.days_remaining ?? 0)}
                    detail={humanDate(lease.ends_at, locale)}
                />
                <LeaseKpi
                    label="Documents"
                    value={String(lease.documents.length)}
                    detail={lease.signed_at ? 'Signed' : 'Needs signature'}
                />
            </div>

            <div className="pmc-lease-progress mt-4">
                <span style={{ width: `${paidPercent}%` }} />
            </div>

            <div className="row g-4 mt-1">
                <div className="col-lg-7">
                    <div className="pmc-kicker mb-2">Installment schedule</div>
                    <div className="pmc-installment-list">
                        {lease.installments.map((installment) => (
                            <div key={installment.id}>
                                <div>
                                    <strong>{installment.label}</strong>
                                    <span>
                                        Due{' '}
                                        {humanDate(
                                            installment.due_date,
                                            locale,
                                        )}{' '}
                                        · {installment.status}
                                    </span>
                                    <small>
                                        {humanDate(
                                            installment.period_start,
                                            locale,
                                        )}{' '}
                                        to{' '}
                                        {humanDate(
                                            installment.period_end,
                                            locale,
                                        )}
                                    </small>
                                </div>
                                <em>
                                    {currency(
                                        installment.remaining_amount,
                                        locale,
                                        lease.currency,
                                    )}{' '}
                                    left
                                </em>
                            </div>
                        ))}
                    </div>
                </div>
                <div className="col-lg-5">
                    <div className="pmc-kicker mb-2">Documents</div>
                    <div className="pmc-document-list">
                        {lease.documents.length > 0 ? (
                            lease.documents.map((document) => (
                                <a
                                    key={document.id}
                                    href={document.download_url}
                                >
                                    <i className="bi bi-file-earmark-text" />
                                    <strong>{document.title_en}</strong>
                                    <span>{document.type}</span>
                                </a>
                            ))
                        ) : (
                            <div>
                                <i className="bi bi-file-earmark-x" />
                                <strong>No documents yet</strong>
                                <span>
                                    Generate a contract or upload a signed file.
                                </span>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}

function LeaseKpi({
    label,
    value,
    detail,
}: {
    label: string;
    value: string;
    detail: string;
}) {
    return (
        <div>
            <span>{label}</span>
            <strong>{value}</strong>
            <small>{detail}</small>
        </div>
    );
}

function LeaseInsightCard({
    icon,
    label,
    value,
    detail,
    tone,
}: {
    icon: string;
    label: string;
    value: string;
    detail: string;
    tone: 'teal' | 'orange' | 'sand' | 'red';
}) {
    return (
        <div className={`pmc-lease-insight-card pmc-lease-insight-${tone}`}>
            <div>
                <i className={`bi ${icon}`} />
            </div>
            <span>{label}</span>
            <strong>{value}</strong>
            <small>{detail}</small>
        </div>
    );
}

function humanFrequency(frequency: string): string {
    return frequency.replaceAll('_', ' ');
}
