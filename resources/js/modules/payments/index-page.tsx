import { Head, usePage } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import {
    MetricGrid,
    RecordActions,
    StatusBadge,
    WorkspaceHeader,
    humanLabel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency, humanDate } from '@/lib/utils';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

type PaymentRecord = {
    id: number;
    reference?: string | null;
    amount: number;
    currency: string;
    received_on?: string | null;
    status: string;
    type: string;
    method: string;
    allocated_amount: number;
    unallocated_amount: number;
    allocation_count: number;
    receipt_url: string;
    tenant_profile?: {
        user?: { name?: string | null; email?: string | null };
    };
    lease?: {
        code?: string | null;
        leaseable?: { title_en?: string | null; code?: string | null };
    };
};

type PageProps = SharedProps & {
    payments: PaginatedData<PaymentRecord>;
    paymentInsights: {
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
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
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

export default function PaymentsIndexPage() {
    const { props } = usePage<PageProps>();
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

            <WorkspaceHeader
                eyebrow="Money & service"
                title="Payments"
                description="Review money received, verify allocation to lease installments, download receipts, and void incorrect entries safely."
                actions={[
                    {
                        label: 'Reports',
                        href: '/reports',
                        icon: 'bi-bar-chart-line',
                    },
                    {
                        label: 'Post payment',
                        href: '/payments/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: 'Posted payments',
                        value: currency(
                            props.paymentInsights.posted_amount,
                            props.app.locale,
                        ),
                        detail: `${props.paymentInsights.posted_count} payment records`,
                        icon: 'bi-cash-stack',
                        tone: 'ink',
                    },
                    {
                        label: 'This month',
                        value: currency(
                            props.paymentInsights.received_this_month,
                            props.app.locale,
                        ),
                        detail: 'Posted collections',
                        icon: 'bi-calendar-check',
                        tone: 'teal',
                    },
                    {
                        label: 'Pending',
                        value: currency(
                            props.paymentInsights.pending_amount,
                            props.app.locale,
                        ),
                        detail: `${props.paymentInsights.pending_count} waiting for posting`,
                        icon: 'bi-hourglass-split',
                        tone:
                            props.paymentInsights.pending_count > 0
                                ? 'amber'
                                : 'blue',
                    },
                    {
                        label: 'Unallocated',
                        value: currency(
                            props.paymentInsights.unallocated_amount,
                            props.app.locale,
                        ),
                        detail: `${currency(props.paymentInsights.allocated_amount, props.app.locale)} allocated`,
                        icon: 'bi-diagram-3',
                        tone:
                            props.paymentInsights.unallocated_amount > 0
                                ? 'red'
                                : 'blue',
                    },
                ]}
            />

            <DataTable
                title="Payment ledger"
                description="Search reference, tenant, lease code, asset, or notes."
                data={props.payments}
                filters={props.filters}
                counts={props.counts}
                basePath="/payments"
                rowHref={(payment) => `/payments/${payment.id}`}
                exportHref={exportUrl('/exports/payments', props.filters)}
                filterFields={filterFields}
                emptyText="No payments yet. Create an active lease, then post money here."
                columns={[
                    {
                        key: 'reference',
                        label: 'Payment',
                        render: (payment) => (
                            <div className="pmc-primary-cell">
                                <strong>
                                    {payment.reference ??
                                        `Payment #${payment.id}`}
                                </strong>
                                <span>
                                    {humanLabel(payment.method)} ·{' '}
                                    {humanLabel(payment.type)}
                                </span>
                                <StatusBadge value={payment.status} />
                            </div>
                        ),
                    },
                    {
                        key: 'tenant',
                        label: 'Tenant / lease',
                        render: (payment) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {payment.tenant_profile?.user?.name ??
                                        'No tenant'}
                                </strong>
                                <span>
                                    {payment.lease?.code ?? 'No lease'} ·{' '}
                                    {payment.lease?.leaseable?.title_en ??
                                        'No asset'}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'date',
                        label: 'Received',
                        render: (payment) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {humanDate(
                                        payment.received_on,
                                        props.app.locale,
                                    )}
                                </strong>
                                <span>{humanLabel(payment.method)}</span>
                            </div>
                        ),
                    },
                    {
                        key: 'amount',
                        label: 'Amount',
                        render: (payment) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {currency(
                                        payment.amount,
                                        props.app.locale,
                                        payment.currency,
                                    )}
                                </strong>
                                <span>
                                    {currency(
                                        payment.allocated_amount,
                                        props.app.locale,
                                        payment.currency,
                                    )}{' '}
                                    allocated
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'allocation',
                        label: 'Allocation',
                        render: (payment) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {payment.allocation_count} installment
                                    {payment.allocation_count === 1 ? '' : 's'}
                                </strong>
                                <span>
                                    {payment.unallocated_amount > 0
                                        ? `${currency(payment.unallocated_amount, props.app.locale, payment.currency)} unallocated`
                                        : 'Fully allocated'}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'actions',
                        label: 'Actions',
                        className: 'text-end',
                        render: (payment) => (
                            <RecordActions
                                showHref={`/payments/${payment.id}`}
                                editHref={`/payments/${payment.id}/edit`}
                            >
                                {payment.status === 'posted' ? (
                                    <a
                                        href={payment.receipt_url}
                                        className="btn btn-outline-secondary btn-sm"
                                    >
                                        <i className="bi bi-receipt" />
                                        <span>Receipt</span>
                                    </a>
                                ) : null}
                                {payment.status !== 'void' ? (
                                    <ArchiveAction
                                        href={`/payments/${payment.id}`}
                                        label="Void"
                                        confirmMessage={`Void payment ${payment.reference ?? `#${payment.id}`}? This reverses installment allocations.`}
                                    />
                                ) : null}
                            </RecordActions>
                        ),
                    },
                ]}
            />
        </AdminLayout>
    );
}
