import type { TableFilterField } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

export function useTenantFilterFields({
    statuses,
    profileTypes,
    portfolios,
    includePortfolio,
}: {
    statuses: string[];
    profileTypes: string[];
    portfolios: Array<{ id: number; name: string }>;
    includePortfolio: boolean;
}): TableFilterField[] {
    const { t } = useTranslator();
    const fields: TableFilterField[] = [
        {
            name: 'status',
            label: t('tenants.status'),
            options: [
                { label: t('tenants.all_statuses'), value: 'all' },
                ...statuses.map((status) => ({
                    label: t(`status.${status}`),
                    value: status,
                })),
            ],
        },
        {
            name: 'profile_type',
            label: t('tenants.profile_type'),
            options: [
                { label: t('tenants.all_profiles'), value: 'all' },
                ...profileTypes.map((profile) => ({
                    label: t(`tenants.${profile}`),
                    value: profile,
                })),
            ],
        },
    ];

    if (includePortfolio) {
        fields.push({
            name: 'portfolio_id',
            label: t('tenants.portfolio'),
            options: [
                { label: t('tenants.all_portfolios'), value: 'all' },
                ...portfolios.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return fields;
}
