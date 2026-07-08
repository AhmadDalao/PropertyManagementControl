import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency, humanDate } from '@/lib/utils';
import type { SharedProps } from '@/types';

type ExpenseRecord = {
    id: number;
    title: string;
    category: string;
    amount: number;
    currency: string;
    incurred_on: string;
    asset?: { title_en: string };
};

type PageProps = SharedProps & {
    expenses: ExpenseRecord[];
    portfolioOptions: Array<{ id: number; name: string }>;
    assetOptions: Array<{ id: number; title_en: string }>;
    maintenanceOptions: Array<{ id: number; title: string }>;
};

export default function ExpensesPage() {
    const { props } = usePage<PageProps>();
    const form = useForm({
        portfolio_id: String(props.auth.user?.portfolio_id ?? props.portfolioOptions[0]?.id ?? ''),
        asset_id: String(props.assetOptions[0]?.id ?? ''),
        maintenance_request_id: '',
        category: 'maintenance',
        title: '',
        description: '',
        incurred_on: '',
        amount: 0,
        currency: 'SAR',
        vendor_name: '',
        status: 'posted',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/expenses', { preserveScroll: true });
    };

    return (
        <AdminLayout>
            <Head title="Expenses" />
            <PageHeader
                title="Expenses"
                description="Capture operational spend and link it back to assets or maintenance work."
            />

            <div className="row g-4">
                <div className="col-xl-4">
                    <div className="pmc-card p-4">
                        <form className="d-grid gap-3" onSubmit={submit}>
                            <div>
                                <label className="form-label pmc-form-label">Title</label>
                                <input
                                    className="form-control"
                                    value={form.data.title}
                                    onChange={(event) => form.setData('title', event.currentTarget.value)}
                                />
                            </div>
                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">Category</label>
                                    <select
                                        className="form-select"
                                        value={form.data.category}
                                        onChange={(event) => form.setData('category', event.currentTarget.value)}
                                    >
                                        <option value="maintenance">Maintenance</option>
                                        <option value="utilities">Utilities</option>
                                        <option value="supplies">Supplies</option>
                                    </select>
                                </div>
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
                            </div>
                            <div>
                                <label className="form-label pmc-form-label">Asset</label>
                                <select
                                    className="form-select"
                                    value={form.data.asset_id}
                                    onChange={(event) => form.setData('asset_id', event.currentTarget.value)}
                                >
                                    {props.assetOptions.map((asset) => (
                                        <option key={asset.id} value={asset.id}>
                                            {asset.title_en}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="form-label pmc-form-label">Date</label>
                                <input
                                    type="date"
                                    className="form-control"
                                    value={form.data.incurred_on}
                                    onChange={(event) => form.setData('incurred_on', event.currentTarget.value)}
                                />
                            </div>
                            <button className="btn btn-primary" disabled={form.processing}>
                                Record expense
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
                                        <th>Expense</th>
                                        <th>Asset</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {props.expenses.map((expense) => (
                                        <tr key={expense.id}>
                                            <td>
                                                <div className="fw-semibold">{expense.title}</div>
                                                <div className="small text-secondary">{expense.category}</div>
                                            </td>
                                            <td>{expense.asset?.title_en ?? '-'}</td>
                                            <td>{humanDate(expense.incurred_on, props.app.locale)}</td>
                                            <td>{currency(expense.amount, props.app.locale, expense.currency)}</td>
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
