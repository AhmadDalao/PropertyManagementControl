import { propertyFilterField } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import type { Translator, UiTranslationKey } from '@/lib/i18n';
import type { PropertyOption } from '@/types';

type MoveOutFilterOptions = {
    queues: string[];
    horizons: string[];
    portfolios: Array<{ id: number; name: string }>;
    properties: PropertyOption[];
    includePortfolio: boolean;
};

export function leaseMoveOutFilterFields(
    {
        queues,
        horizons,
        portfolios,
        properties,
        includePortfolio,
    }: MoveOutFilterOptions,
    t: Translator,
): TableFilterField[] {
    const fields: TableFilterField[] = [
        {
            name: 'queue',
            label: t('lease_move_outs.queue'),
            options: [
                {
                    label: t('lease_move_outs.queue_all'),
                    value: 'all',
                },
                ...queues.map((queue) => ({
                    label: t(
                        `lease_move_outs.queue_${queue}` as UiTranslationKey,
                    ),
                    value: queue,
                })),
            ],
        },
        {
            name: 'horizon',
            label: t('lease_move_outs.horizon'),
            options: horizons.map((horizon) => ({
                label: t(
                    `lease_move_outs.horizon_${horizon}` as UiTranslationKey,
                ),
                value: horizon,
            })),
        },
    ];

    if (includePortfolio) {
        fields.push({
            name: 'portfolio_id',
            label: t('lease_move_outs.portfolio'),
            clears: ['property_id'],
            options: [
                {
                    label: t('lease_move_outs.all_portfolios'),
                    value: 'all',
                },
                ...portfolios.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    fields.push(propertyFilterField(properties, t));

    return fields;
}
