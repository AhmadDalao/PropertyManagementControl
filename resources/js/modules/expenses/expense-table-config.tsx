import { ArchiveAction } from '@/components/archive-action';
import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import {
    humanLabel,
    RecordActions,
    StatusBadge,
} from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { ExpenseRecord } from './types';

export function useExpenseTableConfig(locale: string): {
    columns: Array<TableColumn<ExpenseRecord>>;
    mobileCard: MobileTableConfig<ExpenseRecord>;
} {
    const { t } = useTranslator();
    const categoryLabel = (expense: ExpenseRecord) =>
        t(
            `expenses.category_${expense.category}` as UiTranslationKey,
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
    const desktopExpenseCell = (expense: ExpenseRecord) => (
        <div className="pmc-primary-cell">
            <strong>{expense.title}</strong>
            <span>{categoryLabel(expense)}</span>
            <StatusBadge
                value={expense.status}
                label={t(`status.${expense.status}` as UiTranslationKey)}
            />
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
        <strong>{currency(expense.amount, locale, expense.currency)}</strong>
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
    const columns: Array<TableColumn<ExpenseRecord>> = [
        {
            key: 'expense',
            label: t('expenses.expense'),
            render: desktopExpenseCell,
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
            render: (expense) => humanDate(expense.incurred_on, locale),
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
    ];

    return {
        columns,
        mobileCard: {
            title: expenseCell,
            subtitle: (expense) => (
                <StatusBadge
                    value={expense.status}
                    label={t(`status.${expense.status}` as UiTranslationKey)}
                />
            ),
            status: amountCell,
            meta: [
                { label: t('expenses.asset_ticket'), value: linkCell },
                { label: t('expenses.vendor'), value: vendorCell },
                {
                    label: t('expenses.incurred_on'),
                    value: (expense) => humanDate(expense.incurred_on, locale),
                },
            ],
            actions,
        },
    };
}
