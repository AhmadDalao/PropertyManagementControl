import type { TableFilterField } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';
import type { Translator } from '@/lib/i18n';

type AssetFilterOptions = {
    portfolioOptions: Array<{ id: number; name: string }>;
    includePortfolio: boolean;
};

export function useAssetFilterFields({
    portfolioOptions,
    includePortfolio,
}: AssetFilterOptions): TableFilterField[] {
    const { t } = useTranslator();
    const fields: TableFilterField[] = [
        select(
            'status',
            t('assets.status'),
            ['active', 'inactive', 'archived'],
            t,
        ),
        select(
            'asset_type',
            t('assets.type'),
            ['property', 'building', 'floor', 'unit', 'space'],
            t,
            'assets.types',
        ),
        select(
            'usage_type',
            t('assets.usage'),
            ['residential', 'commercial', 'mixed', 'personal'],
            t,
            'assets.usages',
        ),
        select(
            'occupancy_status',
            t('assets.occupancy'),
            [
                'vacant',
                'occupied',
                'partially_occupied',
                'reserved',
                'maintenance',
            ],
            t,
        ),
        {
            name: 'rentable',
            label: t('assets.rentable'),
            options: [
                { label: t('assets.all'), value: 'all' },
                { label: t('assets.yes'), value: 'yes' },
                { label: t('assets.no'), value: 'no' },
            ],
        },
    ];

    if (includePortfolio) {
        fields.push({
            name: 'portfolio_id',
            label: t('assets.portfolio'),
            options: [
                { label: t('assets.all'), value: 'all' },
                ...portfolioOptions.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return fields;
}

function select(
    name: string,
    label: string,
    values: string[],
    t: Translator,
    group: 'status' | 'assets.types' | 'assets.usages' = 'status',
): TableFilterField {
    return {
        name,
        label,
        options: [
            { label: t('assets.all'), value: 'all' },
            ...values.map((value) => ({
                label: t(`${group}.${value}`),
                value,
            })),
        ],
    };
}
