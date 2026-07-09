import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
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
    received_on: string;
    status: string;
    type: string;
    method: string;
    tenant_profile?: { user?: { name: string } };
    lease?: { code: string };
};

type LeaseOption = {
    id: number;
    code: string;
    tenant_profile?: { user?: { name: string } };
};

type PageProps = SharedProps & {
    payments: PaginatedData<PaymentRecord>;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    leaseOptions: LeaseOption[];
    tenantOptions: Array<{ id: number; user?: { name: string } }>;
};

export default function PaymentsPage() {
    const { props } = usePage<PageProps>();
    const form = useForm({
        portfolio_id: String(
            props.auth.user?.portfolio_id ??
                props.portfolioOptions[0]?.id ??
                '',
        ),
        lease_id: String(props.leaseOptions[0]?.id ?? ''),
        tenant_profile_id: String(props.tenantOptions[0]?.id ?? ''),
        type: 'rent',
        method: 'bank_transfer',
        status: 'posted',
        reference: '',
        received_on: '',
        amount: 0,
        currency: 'SAR',
        notes: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
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
            options: [
                { label: 'All', value: 'all' },
                { label: 'Rent', value: 'rent' },
                { label: 'Deposit', value: 'deposit' },
                { label: 'Fee', value: 'fee' },
            ],
        },
        {
            name: 'method',
            label: 'Method',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Bank transfer', value: 'bank_transfer' },
                { label: 'Cash', value: 'cash' },
                { label: 'Card', value: 'card' },
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
            <Head title="Payments" />
            <PageHeader
                title="Payments"
                description="Post rent or deposit payments, auto-allocate them, and generate receipts."
            />

            <div className="row g-4">
                <div className="col-xl-4">
                    <div className="pmc-card p-4">
                        <div className="pmc-kicker mb-2">Payment form</div>
                        <form className="d-grid gap-3" onSubmit={submit}>
                            <div>
                                <label className="form-label pmc-form-label">
                                    Lease
                                </label>
                                <select
                                    className="form-select"
                                    value={form.data.lease_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'lease_id',
                                            event.currentTarget.value,
                                        )
                                    }
                                >
                                    {props.leaseOptions.map((lease) => (
                                        <option key={lease.id} value={lease.id}>
                                            {lease.code} -{' '}
                                            {lease.tenant_profile?.user?.name ??
                                                ''}
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
                                        className="form-control"
                                        value={form.data.amount}
                                        onChange={(event) =>
                                            form.setData(
                                                'amount',
                                                Number(
                                                    event.currentTarget.value,
                                                ),
                                            )
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Date
                                    </label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={form.data.received_on}
                                        onChange={(event) =>
                                            form.setData(
                                                'received_on',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
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
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="bank_transfer">
                                            Bank transfer
                                        </option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                    </select>
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Reference
                                    </label>
                                    <input
                                        className="form-control"
                                        value={form.data.reference}
                                        onChange={(event) =>
                                            form.setData(
                                                'reference',
                                                event.currentTarget.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                            <button
                                className="btn btn-primary"
                                disabled={form.processing}
                            >
                                Record payment
                            </button>
                        </form>
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
                            exportHref={exportUrl(
                                '/exports/payments',
                                props.filters,
                            )}
                            filterFields={filterFields}
                            columns={[
                                {
                                    key: 'reference',
                                    label: 'Reference',
                                    render: (payment) => (
                                        <>
                                            <div className="fw-semibold">
                                                {payment.reference ??
                                                    `#${payment.id}`}
                                            </div>
                                            <div className="small text-secondary">
                                                {payment.method}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'tenant',
                                    label: 'Tenant',
                                    render: (payment) => (
                                        <>
                                            <div>
                                                {payment.tenant_profile?.user
                                                    ?.name ?? '-'}
                                            </div>
                                            <div className="small text-secondary">
                                                {payment.lease?.code ?? '-'}
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
                                    render: (payment) =>
                                        currency(
                                            payment.amount,
                                            props.app.locale,
                                            payment.currency,
                                        ),
                                },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    className: 'text-end',
                                    render: (payment) => (
                                        <a
                                            href={`/payments/${payment.id}/receipt`}
                                            className="btn btn-outline-secondary btn-sm"
                                        >
                                            Receipt
                                        </a>
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
