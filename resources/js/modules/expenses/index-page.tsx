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

type ExpenseRecord = {
    id: number;
    title: string;
    category: string;
    status: string;
    vendor_name?: string | null;
    amount: number;
    currency: string;
    incurred_on?: string | null;
    asset?: { title_en?: string | null; code?: string | null };
    maintenance_request?: {
        id?: number | null;
        title?: string | null;
        status?: string | null;
    };
};

type PageProps = SharedProps & {
    expenses: PaginatedData<ExpenseRecord>;
    expenseInsights: {
        total: number;
        posted_count: number;
        pending_count: number;
        void_count: number;
        posted_amount: number;
        pending_amount: number;
        maintenance_amount: number;
        linked_to_assets: number;
        linked_to_maintenance: number;
        unlinked_count: number;
        vendors: number;
        posted_this_month: number;
    };
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    categoryOptions: string[];
};

export default function ExpensesIndexPage() {
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

            <WorkspaceHeader
                eyebrow="Money & service"
                title="Expenses"
                description="Review every cost, its asset or maintenance link, vendor, posting state, and effect on portfolio revenue."
                actions={[
                    {
                        label: 'Reports',
                        href: '/reports',
                        icon: 'bi-bar-chart-line',
                    },
                    {
                        label: 'Record expense',
                        href: '/expenses/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: 'Posted expenses',
                        value: currency(
                            props.expenseInsights.posted_amount,
                            props.app.locale,
                        ),
                        detail: `${props.expenseInsights.posted_count} posted entries`,
                        icon: 'bi-receipt',
                        tone: 'ink',
                    },
                    {
                        label: 'This month',
                        value: currency(
                            props.expenseInsights.posted_this_month,
                            props.app.locale,
                        ),
                        detail: `${props.expenseInsights.vendors} vendors`,
                        icon: 'bi-calendar3',
                        tone: 'blue',
                    },
                    {
                        label: 'Maintenance cost',
                        value: currency(
                            props.expenseInsights.maintenance_amount,
                            props.app.locale,
                        ),
                        detail: `${props.expenseInsights.linked_to_maintenance} linked tickets`,
                        icon: 'bi-tools',
                        tone: 'teal',
                    },
                    {
                        label: 'Needs review',
                        value:
                            props.expenseInsights.pending_count +
                            props.expenseInsights.unlinked_count,
                        detail: `${currency(props.expenseInsights.pending_amount, props.app.locale)} pending · ${props.expenseInsights.unlinked_count} unlinked`,
                        icon: 'bi-exclamation-circle',
                        tone:
                            props.expenseInsights.pending_count +
                                props.expenseInsights.unlinked_count >
                            0
                                ? 'amber'
                                : 'teal',
                    },
                ]}
            />

            <DataTable
                title="Expense ledger"
                description="Search title, vendor, category, maintenance work, or linked asset."
                data={props.expenses}
                filters={props.filters}
                counts={props.counts}
                basePath="/expenses"
                rowHref={(expense) => `/expenses/${expense.id}`}
                exportHref={exportUrl('/exports/expenses', props.filters)}
                filterFields={filterFields}
                emptyText="No costs recorded yet. Link expenses to assets or maintenance so reports stay useful."
                columns={[
                    {
                        key: 'expense',
                        label: 'Expense',
                        render: (expense) => (
                            <div className="pmc-primary-cell">
                                <strong>{expense.title}</strong>
                                <span>{humanLabel(expense.category)}</span>
                                <StatusBadge value={expense.status} />
                            </div>
                        ),
                    },
                    {
                        key: 'link',
                        label: 'Asset / ticket',
                        render: (expense) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {expense.asset?.title_en ?? 'No asset'}
                                </strong>
                                <span>
                                    {expense.maintenance_request?.title ??
                                        'No maintenance link'}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'vendor',
                        label: 'Vendor',
                        render: (expense) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {expense.vendor_name ?? 'Not recorded'}
                                </strong>
                                <span>{humanLabel(expense.category)}</span>
                            </div>
                        ),
                    },
                    {
                        key: 'date',
                        label: 'Incurred',
                        render: (expense) =>
                            humanDate(expense.incurred_on, props.app.locale),
                    },
                    {
                        key: 'amount',
                        label: 'Amount',
                        render: (expense) => (
                            <strong>
                                {currency(
                                    expense.amount,
                                    props.app.locale,
                                    expense.currency,
                                )}
                            </strong>
                        ),
                    },
                    {
                        key: 'actions',
                        label: 'Actions',
                        className: 'text-end',
                        render: (expense) => (
                            <RecordActions
                                showHref={`/expenses/${expense.id}`}
                                editHref={`/expenses/${expense.id}/edit`}
                            >
                                {expense.status !== 'void' ? (
                                    <ArchiveAction
                                        href={`/expenses/${expense.id}`}
                                        label="Void"
                                        confirmMessage={`Void expense ${expense.title}? Reports will stop counting this cost.`}
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
