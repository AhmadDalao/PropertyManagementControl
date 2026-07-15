import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

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

type PaymentAllocationRecord = {
    id: number;
    amount: number;
    allocation_type: string;
    installment?: {
        id?: number | null;
        label?: string | null;
        due_date?: string | null;
    };
};

type PaymentRecord = {
    id: number;
    lease_id: number;
    tenant_profile_id?: number | null;
    reference?: string | null;
    amount: number;
    currency: string;
    received_on?: string | null;
    status: string;
    type: string;
    method: string;
    notes?: string | null;
    allocated_amount: number;
    unallocated_amount: number;
    allocation_count: number;
    receipt_url: string;
    allocations: PaymentAllocationRecord[];
    tenant_profile?: {
        id?: number | null;
        user?: { name?: string | null; email?: string | null };
    };
    lease?: {
        id?: number | null;
        code?: string | null;
        status?: string | null;
        balance_remaining?: number | null;
        total_due?: number | null;
        total_paid?: number | null;
        leaseable?: { title_en?: string | null; code?: string | null };
    };
};

type LeaseOption = {
    id: number;
    portfolio_id: number;
    tenant_profile_id: number;
    code: string;
    currency: string;
    balance_remaining: number;
    total_due: number;
    total_paid: number;
    tenant_profile?: { user?: { name?: string | null } };
    leaseable?: { title_en?: string | null; code?: string | null };
};

type PaymentInsights = {
    total: number;
    posted_count: number;
    pending_count: number;
    void_count: number;
    posted_amount: number;
    pending_amount: number;
    void_amount: number;
    allocated_amount: number;
    unallocated_amount: number;
    received_this_month: number;
};

type PageProps = SharedProps & {
    payments: PaginatedData<PaymentRecord>;
    paymentInsights: PaymentInsights;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    leaseOptions: LeaseOption[];
    tenantOptions: Array<{ id: number; user?: { name?: string | null } }>;
};

const paymentTypes = [
    { value: 'rent', label: 'Rent' },
    { value: 'deposit', label: 'Deposit' },
    { value: 'fee', label: 'Fee' },
];

const paymentMethods = [
    { value: 'bank_transfer', label: 'Bank transfer' },
    { value: 'cash', label: 'Cash' },
    { value: 'card', label: 'Card' },
];

export default function PaymentsPage() {
    const { props } = usePage<PageProps>();
    const firstLease = props.leaseOptions[0] ?? null;
    const [editing, setEditing] = useState<PaymentRecord | null>(null);

    const form = useForm({
        portfolio_id: String(
            props.auth.user?.portfolio_id ??
                firstLease?.portfolio_id ??
                props.portfolioOptions[0]?.id ??
                '',
        ),
        lease_id: String(firstLease?.id ?? ''),
        tenant_profile_id: String(firstLease?.tenant_profile_id ?? ''),
        type: 'rent',
        method: 'bank_transfer',
        status: 'posted',
        reference: '',
        received_on: '',
        amount: firstLease?.balance_remaining ?? 0,
        currency: firstLease?.currency ?? 'SAR',
        notes: '',
    });

    const selectedLease =
        props.leaseOptions.find(
            (lease) => String(lease.id) === String(form.data.lease_id),
        ) ?? firstLease;

    const startEditing = (payment: PaymentRecord) => {
        form.setData({
            portfolio_id: String(
                props.auth.user?.portfolio_id ??
                    props.portfolioOptions[0]?.id ??
                    '',
            ),
            lease_id: String(payment.lease_id),
            tenant_profile_id: payment.tenant_profile_id
                ? String(payment.tenant_profile_id)
                : '',
            type: payment.type,
            method: payment.method,
            status: payment.status,
            reference: payment.reference ?? '',
            received_on: payment.received_on ?? '',
            amount: payment.amount,
            currency: payment.currency,
            notes: payment.notes ?? '',
        });
        setEditing(payment);
    };

    const clearEditing = () => {
        setEditing(null);
        form.reset();
    };

    const updateLeaseSelection = (leaseId: string) => {
        const lease = props.leaseOptions.find(
            (option) => String(option.id) === leaseId,
        );

        form.setData({
            ...form.data,
            lease_id: leaseId,
            tenant_profile_id: lease ? String(lease.tenant_profile_id) : '',
            portfolio_id: String(
                props.auth.user?.portfolio_id ??
                    lease?.portfolio_id ??
                    props.portfolioOptions[0]?.id ??
                    '',
            ),
            currency: lease?.currency ?? form.data.currency,
            amount: lease?.balance_remaining ?? form.data.amount,
        });
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/payments/${editing.id}`, {
                preserveScroll: true,
                onSuccess: clearEditing,
            });

            return;
        }

        form.post('/payments', { preserveScroll: true });
    };

    const filterFields: TableFilterField[] = [
        {
            name: 'status',
            label: 'Status',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Posted', value: 'posted' },
                { label: 'Pending', value: 'pending' },
                { label: 'Void', value: 'void' },
            ],
        },
        {
            name: 'type',
            label: 'Type',
            options: [{ label: 'All', value: 'all' }, ...paymentTypes],
        },
        {
            name: 'method',
            label: 'Method',
            options: [{ label: 'All', value: 'all' }, ...paymentMethods],
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
            <Head title="Payments" />

            <section className="pmc-payment-command mb-4">
                <div>
                    <span className="pmc-kicker">Rent collection</span>
                    <h1>Control every payment before it hits the balance.</h1>
                    <p>
                        Posted payments allocate to open installments. Pending
                        payments stay visible without touching tenant balances.
                        Voids reverse allocations, not just labels.
                    </p>
                    <CreatePageShortcut
                        href="/payments/create"
                        label="Create payment"
                        icon="bi-cash-stack"
                        description="Open a payment form to choose lease, tenant, method, amount, status, and reference."
                    />
                    <div className="pmc-payment-command-meta">
                        <span>
                            <i className="bi bi-receipt" />
                            Receipt-ready ledger
                        </span>
                        <span>
                            <i className="bi bi-shield-check" />
                            Scoped by portfolio
                        </span>
                        <span>
                            <i className="bi bi-phone" />
                            Mobile-first review
                        </span>
                    </div>
                </div>
                <div className="pmc-payment-command-card">
                    <span>This month collected</span>
                    <strong>
                        {currency(
                            props.paymentInsights.received_this_month,
                            props.app.locale,
                            selectedLease?.currency ?? 'SAR',
                        )}
                    </strong>
                    <small>
                        {props.paymentInsights.posted_count} posted payment
                        {props.paymentInsights.posted_count === 1 ? '' : 's'}
                    </small>
                </div>
            </section>

            <section className="pmc-payment-insight-grid mb-4">
                <PaymentInsight
                    icon="bi-cash-stack"
                    label="Posted money"
                    value={currency(
                        props.paymentInsights.posted_amount,
                        props.app.locale,
                        selectedLease?.currency ?? 'SAR',
                    )}
                    detail={`${props.paymentInsights.allocated_amount.toLocaleString()} allocated into installments`}
                    tone="teal"
                />
                <PaymentInsight
                    icon="bi-hourglass-split"
                    label="Pending review"
                    value={currency(
                        props.paymentInsights.pending_amount,
                        props.app.locale,
                        selectedLease?.currency ?? 'SAR',
                    )}
                    detail={`${props.paymentInsights.pending_count} payment${props.paymentInsights.pending_count === 1 ? '' : 's'} waiting`}
                    tone="orange"
                />
                <PaymentInsight
                    icon="bi-arrow-counterclockwise"
                    label="Voided"
                    value={currency(
                        props.paymentInsights.void_amount,
                        props.app.locale,
                        selectedLease?.currency ?? 'SAR',
                    )}
                    detail={`${props.paymentInsights.void_count} reversed payment${props.paymentInsights.void_count === 1 ? '' : 's'}`}
                    tone="sand"
                />
                <PaymentInsight
                    icon="bi-exclamation-triangle"
                    label="Unallocated posted"
                    value={currency(
                        props.paymentInsights.unallocated_amount,
                        props.app.locale,
                        selectedLease?.currency ?? 'SAR',
                    )}
                    detail="Should stay near zero unless overpaid"
                    tone="red"
                />
            </section>

            <div className="row g-4 align-items-start">
                <div
                    className={`col-xl-4 pmc-index-form-column ${editing ? 'is-editing' : 'is-idle'}`}
                >
                    <div className="pmc-card p-4 pmc-payment-form-card mb-4">
                        <div className="d-flex justify-content-between gap-3 align-items-start mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Payment workspace
                                </div>
                                <h2 className="h4 mb-1">
                                    {editing
                                        ? `Review ${editing.reference ?? `#${editing.id}`}`
                                        : 'Record collected money'}
                                </h2>
                                <p className="text-secondary mb-0">
                                    {editing
                                        ? 'Only status and notes stay editable. Real correction is void plus a clean replacement payment.'
                                        : 'Choose the lease first. The tenant and currency follow the contract to avoid cross-tenant payment mistakes.'}
                                </p>
                            </div>
                            {editing ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={clearEditing}
                                >
                                    New payment
                                </button>
                            ) : null}
                        </div>

                        {Object.keys(form.errors).length > 0 ? (
                            <div className="alert alert-danger small">
                                {Object.values(form.errors)[0]}
                            </div>
                        ) : null}

                        {!editing && selectedLease ? (
                            <SelectedLeaseCard
                                lease={selectedLease}
                                locale={props.app.locale}
                            />
                        ) : null}

                        <form className="d-grid gap-3" onSubmit={submit}>
                            {editing ? (
                                <>
                                    <div className="pmc-payment-edit-lock">
                                        <i className="bi bi-lock" />
                                        <div>
                                            <strong>Locked money record</strong>
                                            <span>
                                                Amount, date, lease, tenant,
                                                method, and reference stay fixed
                                                for audit history.
                                            </span>
                                        </div>
                                    </div>

                                    <div>
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
                                            {editing.status === 'void' ? (
                                                <option value="void">
                                                    Void
                                                </option>
                                            ) : (
                                                <>
                                                    <option value="posted">
                                                        Posted
                                                    </option>
                                                    <option value="pending">
                                                        Pending
                                                    </option>
                                                    <option value="void">
                                                        Void
                                                    </option>
                                                </>
                                            )}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="form-label pmc-form-label">
                                            Notes
                                        </label>
                                        <textarea
                                            className="form-control"
                                            rows={4}
                                            value={form.data.notes}
                                            onChange={(event) =>
                                                form.setData(
                                                    'notes',
                                                    event.currentTarget.value,
                                                )
                                            }
                                        />
                                    </div>
                                </>
                            ) : (
                                <>
                                    <div>
                                        <label className="form-label pmc-form-label">
                                            Lease
                                        </label>
                                        <select
                                            className="form-select"
                                            value={form.data.lease_id}
                                            onChange={(event) =>
                                                updateLeaseSelection(
                                                    event.currentTarget.value,
                                                )
                                            }
                                            disabled={
                                                props.leaseOptions.length === 0
                                            }
                                        >
                                            {props.leaseOptions.length === 0 ? (
                                                <option value="">
                                                    Create an active lease first
                                                </option>
                                            ) : null}
                                            {props.leaseOptions.map((lease) => (
                                                <option
                                                    key={lease.id}
                                                    value={lease.id}
                                                >
                                                    {lease.code} -{' '}
                                                    {lease.tenant_profile?.user
                                                        ?.name ??
                                                        `Tenant #${lease.tenant_profile_id}`}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="row g-3">
                                        <div className="col-md-6">
                                            <label className="form-label pmc-form-label">
                                                Amount
                                            </label>
                                            <input
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                className="form-control"
                                                value={form.data.amount}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'amount',
                                                        Number(
                                                            event.currentTarget
                                                                .value,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="col-md-6">
                                            <label className="form-label pmc-form-label">
                                                Received date
                                            </label>
                                            <input
                                                type="date"
                                                className="form-control"
                                                value={form.data.received_on}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'received_on',
                                                        event.currentTarget
                                                            .value,
                                                    )
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="row g-3">
                                        <div className="col-md-6">
                                            <label className="form-label pmc-form-label">
                                                Type
                                            </label>
                                            <select
                                                className="form-select"
                                                value={form.data.type}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'type',
                                                        event.currentTarget
                                                            .value,
                                                    )
                                                }
                                            >
                                                {paymentTypes.map((type) => (
                                                    <option
                                                        key={type.value}
                                                        value={type.value}
                                                    >
                                                        {type.label}
                                                    </option>
                                                ))}
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
                                                        event.currentTarget
                                                            .value,
                                                    )
                                                }
                                            >
                                                <option value="posted">
                                                    Posted
                                                </option>
                                                <option value="pending">
                                                    Pending review
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div className="row g-3">
                                        <div className="col-md-6">
                                            <label className="form-label pmc-form-label">
                                                Method
                                            </label>
                                            <select
                                                className="form-select"
                                                value={form.data.method}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'method',
                                                        event.currentTarget
                                                            .value,
                                                    )
                                                }
                                            >
                                                {paymentMethods.map(
                                                    (method) => (
                                                        <option
                                                            key={method.value}
                                                            value={method.value}
                                                        >
                                                            {method.label}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                        </div>
                                        <div className="col-md-6">
                                            <label className="form-label pmc-form-label">
                                                Reference
                                            </label>
                                            <input
                                                className="form-control"
                                                value={form.data.reference}
                                                placeholder="Bank ref, receipt no..."
                                                onChange={(event) =>
                                                    form.setData(
                                                        'reference',
                                                        event.currentTarget
                                                            .value,
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
                                </>
                            )}

                            <button
                                className="btn btn-primary"
                                disabled={
                                    form.processing ||
                                    (!editing &&
                                        props.leaseOptions.length === 0)
                                }
                            >
                                {editing ? 'Update payment' : 'Record payment'}
                            </button>
                        </form>
                    </div>

                    <div className="pmc-payment-cycle-card">
                        <div>
                            <i className="bi bi-1-circle" />
                            <span>Pending: visible, no balance impact.</span>
                        </div>
                        <div>
                            <i className="bi bi-2-circle" />
                            <span>Posted: allocates to oldest open dues.</span>
                        </div>
                        <div>
                            <i className="bi bi-3-circle" />
                            <span>Void: reverses allocation trail safely.</span>
                        </div>
                    </div>
                </div>

                <div className="col-xl-8">
                    <div className="pmc-card p-4">
                        <DataTable
                            title="Payment ledger"
                            description="Search references, tenant names, lease codes, or notes."
                            data={props.payments}
                            filters={props.filters}
                            counts={props.counts}
                            basePath="/payments"
                            createHref="/payments/create"
                            createLabel="Create payment"
                            rowHref={(payment) => `/payments/${payment.id}`}
                            exportHref={exportUrl(
                                '/exports/payments',
                                props.filters,
                            )}
                            filterFields={filterFields}
                            emptyText="No payments yet. Create an active lease, then record posted or pending money here."
                            columns={[
                                {
                                    key: 'reference',
                                    label: 'Payment',
                                    render: (payment) => (
                                        <>
                                            <div className="fw-semibold">
                                                {payment.reference ??
                                                    `#${payment.id}`}
                                            </div>
                                            <div className="d-flex gap-2 mt-2 flex-wrap">
                                                <StatusChip
                                                    status={payment.status}
                                                />
                                                <span className="pmc-chip">
                                                    {humanPaymentMethod(
                                                        payment.method,
                                                    )}
                                                </span>
                                                <span className="pmc-chip pmc-chip--teal">
                                                    {payment.type}
                                                </span>
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'tenant',
                                    label: 'Tenant / lease',
                                    render: (payment) => (
                                        <>
                                            <div>
                                                {payment.tenant_profile?.user
                                                    ?.name ?? '-'}
                                            </div>
                                            <div className="small text-secondary">
                                                {payment.lease?.code ?? '-'}
                                            </div>
                                            <div className="small text-secondary">
                                                {payment.lease?.leaseable
                                                    ?.title_en ?? '-'}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'date',
                                    label: 'Date',
                                    render: (payment) =>
                                        humanDate(
                                            payment.received_on,
                                            props.app.locale,
                                        ),
                                },
                                {
                                    key: 'amount',
                                    label: 'Amount',
                                    render: (payment) => (
                                        <>
                                            <div className="fw-semibold">
                                                {currency(
                                                    payment.amount,
                                                    props.app.locale,
                                                    payment.currency,
                                                )}
                                            </div>
                                            <div className="small text-secondary">
                                                {currency(
                                                    payment.allocated_amount,
                                                    props.app.locale,
                                                    payment.currency,
                                                )}{' '}
                                                allocated
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'allocation',
                                    label: 'Allocation',
                                    render: (payment) => (
                                        <AllocationSummary
                                            payment={payment}
                                            locale={props.app.locale}
                                        />
                                    ),
                                },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    className: 'text-end',
                                    render: (payment) => (
                                        <div className="d-flex justify-content-end gap-2 flex-wrap">
                                            {payment.status === 'posted' ? (
                                                <a
                                                    href={payment.receipt_url}
                                                    className="btn btn-outline-secondary btn-sm"
                                                >
                                                    Receipt
                                                </a>
                                            ) : null}
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary btn-sm"
                                                onClick={() =>
                                                    startEditing(payment)
                                                }
                                            >
                                                Review
                                            </button>
                                            {payment.status !== 'void' ? (
                                                <ArchiveAction
                                                    href={`/payments/${payment.id}`}
                                                    label="Void"
                                                    confirmMessage={`Void payment ${payment.reference ?? `#${payment.id}`}? This reverses installment allocations.`}
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

function PaymentInsight({
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
        <div className={`pmc-payment-insight-card pmc-payment-insight-${tone}`}>
            <div>
                <i className={`bi ${icon}`} />
            </div>
            <span>{label}</span>
            <strong>{value}</strong>
            <small>{detail}</small>
        </div>
    );
}

function SelectedLeaseCard({
    lease,
    locale,
}: {
    lease: LeaseOption;
    locale: string;
}) {
    return (
        <div className="pmc-selected-lease-card mb-3">
            <div>
                <span>Selected lease</span>
                <strong>{lease.code}</strong>
                <small>
                    {lease.tenant_profile?.user?.name ?? 'No tenant name'} ·{' '}
                    {lease.leaseable?.title_en ?? 'No asset label'}
                </small>
            </div>
            <div>
                <span>Balance left</span>
                <strong>
                    {currency(lease.balance_remaining, locale, lease.currency)}
                </strong>
                <small>
                    {currency(lease.total_paid, locale, lease.currency)} paid /{' '}
                    {currency(lease.total_due, locale, lease.currency)} due
                </small>
            </div>
        </div>
    );
}

function StatusChip({ status }: { status: string }) {
    const className =
        status === 'posted'
            ? 'pmc-chip pmc-chip--teal'
            : status === 'pending'
              ? 'pmc-chip pmc-chip--orange'
              : 'pmc-chip';

    return <span className={className}>{status}</span>;
}

function AllocationSummary({
    payment,
    locale,
}: {
    payment: PaymentRecord;
    locale: string;
}) {
    if (payment.status === 'pending') {
        return (
            <div className="small text-secondary">
                Pending review. No installment touched.
            </div>
        );
    }

    if (payment.status === 'void') {
        return (
            <div className="small text-secondary">
                Voided. Allocations reversed.
            </div>
        );
    }

    if (payment.allocations.length === 0) {
        return (
            <div className="small text-danger">
                Posted but not allocated. Review lease balance.
            </div>
        );
    }

    return (
        <div className="pmc-payment-allocation-list">
            {payment.allocations.slice(0, 2).map((allocation) => (
                <div key={allocation.id}>
                    <strong>
                        {currency(allocation.amount, locale, payment.currency)}
                    </strong>
                    <span>
                        {allocation.installment?.label ??
                            allocation.allocation_type}
                    </span>
                </div>
            ))}
            {payment.allocations.length > 2 ? (
                <small>+{payment.allocations.length - 2} more</small>
            ) : null}
            {payment.unallocated_amount > 0 ? (
                <small>
                    {currency(
                        payment.unallocated_amount,
                        locale,
                        payment.currency,
                    )}{' '}
                    unallocated
                </small>
            ) : null}
        </div>
    );
}

function humanPaymentMethod(method: string): string {
    return method.replaceAll('_', ' ');
}
