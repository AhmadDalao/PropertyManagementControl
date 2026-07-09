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

type ExpenseRecord = {
    id: number;
    title: string;
    category: string;
    status: string;
    vendor_name?: string | null;
    amount: number;
    currency: string;
    incurred_on: string;
    asset?: { title_en: string };
};

type PageProps = SharedProps & {
    expenses: PaginatedData<ExpenseRecord>;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    assetOptions: Array<{ id: number; title_en: string }>;
    maintenanceOptions: Array<{ id: number; title: string }>;
};

export default function ExpensesPage() {
    const { props } = usePage<PageProps>();
    const form = useForm({
        portfolio_id: String(
            props.auth.user?.portfolio_id ??
                props.portfolioOptions[0]?.id ??
                '',
        ),
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
            name: 'category',
            label: 'Category',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Maintenance', value: 'maintenance' },
                { label: 'Utilities', value: 'utilities' },
                { label: 'Supplies', value: 'supplies' },
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
            <Head title="Expenses" />
            <PageHeader
                title="Expenses"
                description="Capture operational spend and link it back to assets or maintenance work."
            />

            <div className="row g-4">
                <div className="col-xl-4">
                    <div className="pmc-card p-4">
                        <form className="d-grid gap-3" onSubmit={submit}>
                            <input
                                className="form-control"
                                placeholder="Title"
                                value={form.data.title}
                                onChange={(event) =>
                                    form.setData(
                                        'title',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <div className="row g-3">
                                <div className="col-md-6">
                                    <select
                                        className="form-select"
                                        value={form.data.category}
                                        onChange={(event) =>
                                            form.setData(
                                                'category',
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="maintenance">
                                            Maintenance
                                        </option>
                                        <option value="utilities">
                                            Utilities
                                        </option>
                                        <option value="supplies">
                                            Supplies
                                        </option>
                                    </select>
                                </div>
                                <div className="col-md-6">
                                    <input
                                        type="number"
                                        className="form-control"
                                        placeholder="Amount"
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
                            </div>
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
                            <input
                                className="form-control"
                                placeholder="Vendor"
                                value={form.data.vendor_name}
                                onChange={(event) =>
                                    form.setData(
                                        'vendor_name',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <input
                                type="date"
                                className="form-control"
                                value={form.data.incurred_on}
                                onChange={(event) =>
                                    form.setData(
                                        'incurred_on',
                                        event.currentTarget.value,
                                    )
                                }
                            />
                            <button
                                className="btn btn-primary"
                                disabled={form.processing}
                            >
                                Record expense
                            </button>
                        </form>
                    </div>
                </div>

                <div className="col-xl-8">
                    <div className="pmc-card p-4">
                        <DataTable
                            title="Expense ledger"
                            description="Search titles, vendors, categories, maintenance work, or linked assets."
                            data={props.expenses}
                            filters={props.filters}
                            counts={props.counts}
                            basePath="/expenses"
                            exportHref={exportUrl(
                                '/exports/expenses',
                                props.filters,
                            )}
                            filterFields={filterFields}
                            columns={[
                                {
                                    key: 'expense',
                                    label: 'Expense',
                                    render: (expense) => (
                                        <>
                                            <div className="fw-semibold">
                                                {expense.title}
                                            </div>
                                            <div className="small text-secondary">
                                                {expense.category}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'asset',
                                    label: 'Asset',
                                    render: (expense) => (
                                        <>
                                            <div>
                                                {expense.asset?.title_en ?? '-'}
                                            </div>
                                            <div className="small text-secondary">
                                                {expense.vendor_name ?? '-'}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'date',
                                    label: 'Date',
                                    render: (expense) =>
                                        humanDate(
                                            expense.incurred_on,
                                            props.app.locale,
                                        ),
                                },
                                {
                                    key: 'amount',
                                    label: 'Amount',
                                    render: (expense) =>
                                        currency(
                                            expense.amount,
                                            props.app.locale,
                                            expense.currency,
                                        ),
                                },
                                {
                                    key: 'status',
                                    label: 'Status',
                                    render: (expense) => (
                                        <span className="pmc-chip pmc-chip--primary">
                                            {expense.status}
                                        </span>
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
