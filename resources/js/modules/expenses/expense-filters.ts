import type { TableFilterField } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

type ExpenseFilterOptions = {
    statuses: string[];
    categories: string[];
    portfolios: Array<{ id: number; name: string }>;
    includePortfolio: boolean;
};

export function useExpenseFilterFields({
    statuses,
    categories,
    portfolios,
    includePortfolio,
}: ExpenseFilterOptions): TableFilterField[] {
    const { t } = useTranslator();
    const fields: TableFilterField[] = [
        {
            name: 'status',
            label: t('expenses.status'),
            options: [
                { label: t('expenses.all'), value: 'all' },
                ...statuses.map((status) => ({
                    label: t(`status.${status}`),
                    value: status,
                })),
            ],
        },
        {
            name: 'category',
            label: t('expenses.category'),
            options: [
                { label: t('expenses.all'), value: 'all' },
                ...categories.map((category) => ({
                    label: t(`expenses.category_${category}`),
                    value: category,
                })),
            ],
        },
        { name: 'date_from', label: t('expenses.from'), type: 'date' },
        { name: 'date_to', label: t('expenses.to'), type: 'date' },
    ];

    if (includePortfolio) {
        fields.push({
            name: 'portfolio_id',
            label: t('expenses.portfolio'),
            options: [
                { label: t('expenses.all'), value: 'all' },
                ...portfolios.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return fields;
}
