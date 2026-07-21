import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import {
    humanLabel,
    RecordActions,
    StatusBadge,
} from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import { useExpenseFilterFields } from './expense-filters';
import type { ExpenseIndexPageProps, ExpenseRecord } from './types';

type ExpenseTableProps = Pick<
    ExpenseIndexPageProps,
    | 'expenses'
    | 'filters'
    | 'counts'
    | 'portfolioOptions'
    | 'categoryOptions'
    | 'statusOptions'
    | 'auth'
    | 'app'
>;

export function ExpenseTable(props: ExpenseTableProps) {
    const { locale, t } = useTranslator();
    const filterFields = useExpenseFilterFields({
        statuses: props.statusOptions,
        categories: props.categoryOptions,
        portfolios: props.portfolioOptions,
        includePortfolio:
            props.auth.user?.roles.includes('superadmin') ?? false,
    });
    const categoryLabel = (expense: ExpenseRecord) =>
        t(
            `expenses.category_${expense.category}`,
            humanLabel(expense.category),
        );
    const assetLabel = (expense: ExpenseRecord) =>
        (locale === 'ar'
            ? expense.asset?.title_ar || expense.asset?.title_en
            : expense.asset?.title_en || expense.asset?.title_ar) ??
        t('expenses.no_asset');
    const expenseCell = (expense: ExpenseRecord) => (
        <div className="pmc-primary-cell">
            <strong>{expense.title}</strong>
            <span>{categoryLabel(expense)}</span>
        </div>
    );
    const linkCell = (expense: ExpenseRecord) => (
        <div className="pmc-stacked-cell">
            <strong>{assetLabel(expense)}</strong>
            <span>
                {expense.maintenance_request?.title ??
                    t('expenses.no_maintenance_link')}
            </span>
        </div>
    );
    const vendorCell = (expense: ExpenseRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {expense.vendor_name ?? t('expenses.vendor_not_recorded')}
            </strong>
            <span>{categoryLabel(expense)}</span>
        </div>
    );
    const amountCell = (expense: ExpenseRecord) => (
        <strong>
            {currency(expense.amount, props.app.locale, expense.currency)}
        </strong>
    );
    const actions = (expense: ExpenseRecord) => (
        <RecordActions
            showHref={`/expenses/${expense.id}`}
            editHref={
                expense.status === 'void'
                    ? undefined
                    : `/expenses/${expense.id}/edit`
            }
        >
            {expense.status !== 'void' ? (
                <ArchiveAction
                    href={`/expenses/${expense.id}`}
                    label={t('expenses.void_expense')}
                    confirmMessage={t('expenses.void_confirm', undefined, {
                        title: expense.title,
                    })}
                />
            ) : null}
        </RecordActions>
    );

    return (
        <DataTable
            title={t('expenses.ledger_title')}
            description={t('expenses.ledger_description')}
            data={props.expenses}
            filters={props.filters}
            counts={props.counts}
            basePath="/expenses"
            rowHref={(expense) => `/expenses/${expense.id}`}
            exportHref={exportUrl('/exports/expenses', props.filters)}
            filterFields={filterFields}
            emptyText={t('expenses.empty')}
            createHref="/expenses/create"
            createLabel={t('expenses.record_expense')}
            mobileCard={{
                title: expenseCell,
                subtitle: (expense) => <StatusBadge value={expense.status} />,
                status: amountCell,
                meta: [
                    { label: t('expenses.asset_ticket'), value: linkCell },
                    { label: t('expenses.vendor'), value: vendorCell },
                    {
                        label: t('expenses.incurred_on'),
                        value: (expense) =>
                            humanDate(expense.incurred_on, props.app.locale),
                    },
                ],
                actions,
            }}
            columns={[
                {
                    key: 'expense',
                    label: t('expenses.expense'),
                    render: expenseCell,
                },
                {
                    key: 'link',
                    label: t('expenses.asset_ticket'),
                    render: linkCell,
                },
                {
                    key: 'vendor',
                    label: t('expenses.vendor'),
                    render: vendorCell,
                },
                {
                    key: 'date',
                    label: t('expenses.incurred'),
                    render: (expense) =>
                        humanDate(expense.incurred_on, props.app.locale),
                },
                {
                    key: 'amount',
                    label: t('expenses.amount'),
                    render: amountCell,
                },
                {
                    key: 'actions',
                    label: t('expenses.actions'),
                    className: 'text-end',
                    render: actions,
                },
            ]}
        />
    );
}
