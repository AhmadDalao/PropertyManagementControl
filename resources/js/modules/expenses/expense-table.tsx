import { DataTable, exportUrl } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { useExpenseFilterFields } from './expense-filters';
import { useExpenseTableConfig } from './expense-table-config';
import type { ExpenseIndexPageProps } from './types';

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
    const { t } = useTranslator();
    const table = useExpenseTableConfig(props.app.locale);
    const filters = useExpenseFilterFields({
        statuses: props.statusOptions,
        categories: props.categoryOptions,
        portfolios: props.portfolioOptions,
        includePortfolio:
            props.auth.user?.roles.includes('superadmin') ?? false,
    });

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
            filterFields={filters}
            columns={table.columns}
            mobileCard={table.mobileCard}
            emptyText={t('expenses.empty')}
            createHref="/expenses/create"
            createLabel={t('expenses.record_expense')}
        />
    );
}
