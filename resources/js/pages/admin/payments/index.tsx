import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency, humanDate } from '@/lib/utils';
import type { SharedProps } from '@/types';

type PaymentRecord = {
    id: number;
    reference?: string | null;
    amount: number;
    currency: string;
    received_on: string;
    status: string;
    method: string;
    tenant_profile?: { user?: { name: string } };
};

type LeaseOption = {
    id: number;
    code: string;
    tenant_profile?: { user?: { name: string } };
};

type PageProps = SharedProps & {
    payments: PaymentRecord[];
    portfolioOptions: Array<{ id: number; name: string }>;
    leaseOptions: LeaseOption[];
    tenantOptions: Array<{ id: number; user?: { name: string } }>;
};

export default function PaymentsPage() {
    const { props } = usePage<PageProps>();
    const form = useForm({
        portfolio_id: String(props.auth.user?.portfolio_id ?? props.portfolioOptions[0]?.id ?? ''),
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
                                <label className="form-label pmc-form-label">Lease</label>
                                <select
                                    className="form-select"
                                    value={form.data.lease_id}
                                    onChange={(event) => form.setData('lease_id', event.currentTarget.value)}
                                >
                                    {props.leaseOptions.map((lease) => (
                                        <option key={lease.id} value={lease.id}>
                                            {lease.code} - {lease.tenant_profile?.user?.name ?? ''}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Amount</label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={form.data.amount}
                                        onChange={(event) =>
                                            form.setData('amount', Number(event.currentTarget.value))
                                        }
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Date</label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={form.data.received_on}
                                        onChange={(event) =>
                                            form.setData('received_on', event.currentTarget.value)
                                        }
                                    />
                                </div>
                            </div>
                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Method</label>
                                    <select
                                        className="form-select"
                                        value={form.data.method}
                                        onChange={(event) => form.setData('method', event.currentTarget.value)}
                                    >
                                        <option value="bank_transfer">Bank transfer</option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                    </select>
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Reference</label>
                                    <input
                                        className="form-control"
                                        value={form.data.reference}
                                        onChange={(event) => form.setData('reference', event.currentTarget.value)}
                                    />
                                </div>
                            </div>
                            <button className="btn btn-primary" disabled={form.processing}>
                                Record payment
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
                                        <th>Reference</th>
                                        <th>Tenant</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th />
                                    </tr>
                                </thead>
                                <tbody>
                                    {props.payments.map((payment) => (
                                        <tr key={payment.id}>
                                            <td>
                                                <div className="fw-semibold">{payment.reference ?? `#${payment.id}`}</div>
                                                <div className="small text-secondary">{payment.method}</div>
                                            </td>
                                            <td>{payment.tenant_profile?.user?.name ?? '-'}</td>
                                            <td>{humanDate(payment.received_on, props.app.locale)}</td>
                                            <td>{currency(payment.amount, props.app.locale, payment.currency)}</td>
                                            <td className="text-end">
                                                <a
                                                    href={`/payments/${payment.id}/receipt`}
                                                    className="btn btn-outline-secondary btn-sm"
                                                >
                                                    Receipt
                                                </a>
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
