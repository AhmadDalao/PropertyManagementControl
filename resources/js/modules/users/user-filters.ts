import type { TableFilterField } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

export function useUserFilterFields({
    statuses,
    roles,
    portfolios,
    includePortfolio,
}: {
    statuses: string[];
    roles: string[];
    portfolios: Array<{ id: number; name: string }>;
    includePortfolio: boolean;
}): TableFilterField[] {
    const { t } = useTranslator();
    const fields: TableFilterField[] = [
        {
            name: 'status',
            label: t('users.status'),
            options: [
                { label: t('users.all_statuses'), value: 'all' },
                ...statuses.map((status) => ({
                    label: t(`status.${status}`),
                    value: status,
                })),
            ],
        },
        {
            name: 'role',
            label: t('users.role'),
            options: [
                { label: t('users.all_roles'), value: 'all' },
                ...roles.map((role) => ({
                    label: t(`roles.${role}`),
                    value: role,
                })),
            ],
        },
    ];

    if (includePortfolio) {
        fields.push({
            name: 'portfolio_id',
            label: t('users.portfolio'),
            options: [
                { label: t('users.all_portfolios'), value: 'all' },
                ...portfolios.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return fields;
}
