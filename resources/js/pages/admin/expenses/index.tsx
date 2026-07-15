import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

import { ArchiveAction } from '@/components/archive-action';
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

type ExpenseRecord = {
    id: number;
    portfolio_id: number;
    asset_id?: number | null;
    maintenance_request_id?: number | null;
    title: string;
    description?: string | null;
    category: string;
    status: string;
    vendor_name?: string | null;
    amount: number;
    currency: string;
    incurred_on?: string | null;
    asset?: {
        id?: number | null;
        title_en?: string | null;
        code?: string | null;
    };
    maintenance_request?: {
        id?: number | null;
        title?: string | null;
        status?: string | null;
        priority?: string | null;
    };
};

type AssetOption = {
    id: number;
    portfolio_id: number;
    title_en: string;
    code?: string | null;
};

type MaintenanceOption = {
    id: number;
    asset_id?: number | null;
    title: string;
    status?: string | null;
    priority?: string | null;
    asset?: { title_en?: string | null; code?: string | null };
};

type ExpenseInsights = {
    total: number;
    posted_count: number;
    pending_count: number;
    void_count: number;
    posted_amount: number;
    pending_amount: number;
    void_amount: number;
    maintenance_amount: number;
    linked_to_assets: number;
    linked_to_maintenance: number;
    unlinked_count: number;
    vendors: number;
    posted_this_month: number;
};

type PageProps = SharedProps & {
    expenses: PaginatedData<ExpenseRecord>;
    expenseInsights: ExpenseInsights;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    categoryOptions: string[];
    assetOptions: AssetOption[];
    maintenanceOptions: MaintenanceOption[];
};

export default function ExpensesPage() {
    const { props } = usePage<PageProps>();
    const [editing, setEditing] = useState<ExpenseRecord | null>(null);

    const form = useForm({
        portfolio_id: String(
            props.auth.user?.portfolio_id ??
                props.portfolioOptions[0]?.id ??
                '',
        ),
        asset_id: '',
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

    const selectedMaintenance = props.maintenanceOptions.find(
        (request) =>
            String(request.id) === String(form.data.maintenance_request_id),
    );

    const startEditing = (expense: ExpenseRecord) => {
        form.setData({
            portfolio_id: String(expense.portfolio_id),
            asset_id: expense.asset_id ? String(expense.asset_id) : '',
            maintenance_request_id: expense.maintenance_request_id
                ? String(expense.maintenance_request_id)
                : '',
            category: expense.category,
            title: expense.title,
            description: expense.description ?? '',
            incurred_on: expense.incurred_on ?? '',
            amount: expense.amount,
            currency: expense.currency,
            vendor_name: expense.vendor_name ?? '',
            status: expense.status,
        });
        setEditing(expense);
    };

    const clearEditing = () => {
        setEditing(null);
        form.reset();
    };

    const updateMaintenanceSelection = (requestId: string) => {
        const request = props.maintenanceOptions.find(
            (option) => String(option.id) === requestId,
        );

        form.setData({
            ...form.data,
            maintenance_request_id: requestId,
            asset_id: request?.asset_id
                ? String(request.asset_id)
                : form.data.asset_id,
            category: requestId ? 'maintenance' : form.data.category,
        });
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (editing) {
            form.put(`/expenses/${editing.id}`, {
                preserveScroll: true,
                onSuccess: clearEditing,
            });

            return;
        }

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
                ...props.categoryOptions.map((category) => ({
                    label: humanLabel(category),
                    value: category,
                })),
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

            <section className="pmc-expense-command mb-4">
                <div>
                    <span className="pmc-kicker">Spend control</span>
                    <h1>Keep every cost tied to the asset that caused it.</h1>
                    <p>
                        Posted expenses reduce net revenue. Pending expenses
                        stay visible for review. Maintenance-linked costs roll
                        into the service ticket and owner reports.
                    </p>
                    <div className="pmc-expense-command-meta">
                        <span>
                            <i className="bi bi-tools" />
                            Maintenance cost rollups
                        </span>
                        <span>
                            <i className="bi bi-building-check" />
                            Asset-level spend trail
                        </span>
                        <span>
                            <i className="bi bi-graph-down-arrow" />
                            Net revenue ready
                        </span>
                    </div>
                </div>
                <div className="pmc-expense-command-card">
                    <span>This month posted</span>
                    <strong>
                        {currency(
                            props.expenseInsights.posted_this_month,
                            props.app.locale,
                        )}
                    </strong>
                    <small>
                        {props.expenseInsights.posted_count} posted expense
                        {props.expenseInsights.posted_count === 1 ? '' : 's'}
                    </small>
                </div>
            </section>

            <section className="pmc-expense-insight-grid mb-4">
                <ExpenseInsightCard
                    icon="bi-cash-stack"
                    label="Posted spend"
                    value={currency(
                        props.expenseInsights.posted_amount,
                        props.app.locale,
                    )}
                    detail={`${props.expenseInsights.linked_to_assets} linked to assets`}
                    tone="teal"
                />
                <ExpenseInsightCard
                    icon="bi-hourglass-split"
                    label="Pending review"
                    value={currency(
                        props.expenseInsights.pending_amount,
                        props.app.locale,
                    )}
                    detail={`${props.expenseInsights.pending_count} waiting`}
                    tone="orange"
                />
                <ExpenseInsightCard
                    icon="bi-tools"
                    label="Maintenance cost"
                    value={currency(
                        props.expenseInsights.maintenance_amount,
                        props.app.locale,
                    )}
                    detail={`${props.expenseInsights.linked_to_maintenance} linked tickets`}
                    tone="sand"
                />
                <ExpenseInsightCard
                    icon="bi-exclamation-triangle"
                    label="Unlinked"
                    value={String(props.expenseInsights.unlinked_count)}
                    detail="Costs without asset or ticket context"
                    tone="red"
                />
            </section>

            <div className="row g-4 align-items-start">
                <div className="col-xl-4">
                    <div className="pmc-card p-4 pmc-expense-form-card">
                        <div className="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Expense workspace
                                </div>
                                <h2 className="h4 mb-1">
                                    {editing
                                        ? `Review ${editing.title}`
                                        : 'Record operational spend'}
                                </h2>
                                <p className="text-secondary mb-0">
                                    {editing
                                        ? 'Update the cost record or void it. Posted rows feed owner reports.'
                                        : 'Attach spend to a maintenance request when possible; the asset will follow automatically.'}
                                </p>
                            </div>
                            {editing ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={clearEditing}
                                >
                                    New expense
                                </button>
                            ) : null}
                        </div>

                        {Object.keys(form.errors).length > 0 ? (
                            <div className="alert alert-danger small">
                                {Object.values(form.errors)[0]}
                            </div>
                        ) : null}

                        <div className="pmc-expense-form-guide mb-3">
                            <i className="bi bi-info-circle" />
                            <div>
                                <strong>Posted means reportable.</strong>
                                <span>
                                    Pending costs are tracked but excluded from
                                    net revenue until you confirm them.
                                </span>
                            </div>
                        </div>

                        {selectedMaintenance ? (
                            <div className="pmc-selected-expense-link mb-3">
                                <span>Maintenance link</span>
                                <strong>{selectedMaintenance.title}</strong>
                                <small>
                                    {selectedMaintenance.asset?.title_en ??
                                        'No asset label'}{' '}
                                    · {selectedMaintenance.status ?? 'open'}
                                </small>
                            </div>
                        ) : null}

                        <form className="d-grid gap-3" onSubmit={submit}>
                            <div>
                                <label className="form-label pmc-form-label">
                                    Title
                                </label>
                                <input
                                    className="form-control"
                                    value={form.data.title}
                                    onChange={(event) =>
                                        form.setData(
                                            'title',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            </div>

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Category
                                    </label>
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
                                        {props.categoryOptions.map(
                                            (category) => (
                                                <option
                                                    key={category}
                                                    value={category}
                                                >
                                                    {humanLabel(category)}
                                                </option>
                                            ),
                                        )}
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
                                                event.currentTarget.value,
                                            )
                                        }
                                    >
                                        <option value="posted">Posted</option>
                                        <option value="pending">Pending</option>
                                        {editing ? (
                                            <option value="void">Void</option>
                                        ) : null}
                                    </select>
                                </div>
                            </div>

                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label pmc-form-label">
                                        Amount
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
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
                                        Incurred date
                                    </label>
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
                                </div>
                            </div>

                            <div>
                                <label className="form-label pmc-form-label">
                                    Maintenance request
                                </label>
                                <select
                                    className="form-select"
                                    value={form.data.maintenance_request_id}
                                    onChange={(event) =>
                                        updateMaintenanceSelection(
                                            event.currentTarget.value,
                                        )
                                    }
                                >
                                    <option value="">
                                        No maintenance link
                                    </option>
                                    {props.maintenanceOptions.map((request) => (
                                        <option
                                            key={request.id}
                                            value={request.id}
                                        >
                                            #{request.id} {request.title}
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
                                    <option value="">No asset link</option>
                                    {props.assetOptions.map((asset) => (
                                        <option key={asset.id} value={asset.id}>
                                            {asset.title_en}
                                            {asset.code
                                                ? ` · ${asset.code}`
                                                : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="row g-3">
                                <div className="col-md-7">
                                    <label className="form-label pmc-form-label">
                                        Vendor
                                    </label>
                                    <input
                                        className="form-control"
                                        value={form.data.vendor_name}
                                        onChange={(event) =>
                                            form.setData(
                                                'vendor_name',
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
                                    Description
                                </label>
                                <textarea
                                    className="form-control"
                                    rows={3}
                                    value={form.data.description}
                                    onChange={(event) =>
                                        form.setData(
                                            'description',
                                            event.currentTarget.value,
                                        )
                                    }
                                />
                            </div>

                            <button
                                className="btn btn-primary"
                                disabled={form.processing}
                            >
                                {editing ? 'Update expense' : 'Record expense'}
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
                            createHref="/expenses/create"
                            createLabel="Record expense"
                            rowHref={(expense) => `/expenses/${expense.id}`}
                            exportHref={exportUrl(
                                '/exports/expenses',
                                props.filters,
                            )}
                            filterFields={filterFields}
                            emptyText="No costs recorded yet. Start with maintenance and asset-linked expenses so reports stay useful."
                            columns={[
                                {
                                    key: 'expense',
                                    label: 'Expense',
                                    render: (expense) => (
                                        <>
                                            <div className="fw-semibold">
                                                {expense.title}
                                            </div>
                                            <div className="d-flex gap-2 mt-2 flex-wrap">
                                                <StatusChip
                                                    status={expense.status}
                                                />
                                                <span className="pmc-chip pmc-chip--teal">
                                                    {humanLabel(
                                                        expense.category,
                                                    )}
                                                </span>
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'link',
                                    label: 'Asset / ticket',
                                    render: (expense) => (
                                        <>
                                            <div>
                                                {expense.asset?.title_en ??
                                                    'No asset'}
                                            </div>
                                            <div className="small text-secondary">
                                                {expense.maintenance_request
                                                    ?.title ??
                                                    expense.vendor_name ??
                                                    'No maintenance link'}
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
                                    render: (expense) => (
                                        <>
                                            <div className="fw-semibold">
                                                {currency(
                                                    expense.amount,
                                                    props.app.locale,
                                                    expense.currency,
                                                )}
                                            </div>
                                            <div className="small text-secondary">
                                                {expense.vendor_name ??
                                                    'No vendor'}
                                            </div>
                                        </>
                                    ),
                                },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    className: 'text-end',
                                    render: (expense) => (
                                        <div className="d-flex justify-content-end gap-2 flex-wrap">
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary btn-sm"
                                                onClick={() =>
                                                    startEditing(expense)
                                                }
                                            >
                                                Review
                                            </button>
                                            {expense.status !== 'void' ? (
                                                <ArchiveAction
                                                    href={`/expenses/${expense.id}`}
                                                    label="Void"
                                                    confirmMessage={`Void expense ${expense.title}? Reports will stop counting this cost.`}
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

function ExpenseInsightCard({
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
        <div className={`pmc-expense-insight-card pmc-expense-insight-${tone}`}>
            <div>
                <i className={`bi ${icon}`} />
            </div>
            <span>{label}</span>
            <strong>{value}</strong>
            <small>{detail}</small>
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

function humanLabel(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}
