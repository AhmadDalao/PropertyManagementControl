import type { TableFilterField } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

export function usePortfolioFilterFields(
    statuses: string[],
): TableFilterField[] {
    const { t } = useTranslator();

    return [
        {
            name: 'status',
            label: t('portfolios.status'),
            options: [
                { label: t('portfolios.all_statuses'), value: 'all' },
                ...statuses.map((status) => ({
                    label: t(`status.${status}`),
                    value: status,
                })),
            ],
        },
    ];
}
