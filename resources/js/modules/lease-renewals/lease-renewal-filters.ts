import { propertyFilterField } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import type { Translator, UiTranslationKey } from '@/lib/i18n';
import type { PropertyOption } from '@/types';

type LeaseRenewalFilterOptions = {
    queues: string[];
    horizons: string[];
    leaseStatuses: string[];
    portfolios: Array<{ id: number; name: string }>;
    properties: PropertyOption[];
    includePortfolio: boolean;
};

export function leaseRenewalFilterFields(
    {
        queues,
        horizons,
        leaseStatuses,
        portfolios,
        properties,
        includePortfolio,
    }: LeaseRenewalFilterOptions,
    t: Translator,
): TableFilterField[] {
    const fields: TableFilterField[] = [
        {
            name: 'queue',
            label: t('lease_renewals.queue'),
            options: [
                {
                    label: t('lease_renewals.queue_all'),
                    value: 'all',
                },
                ...queues.map((queue) => ({
                    label: t(
                        `lease_renewals.queue_${queue}` as UiTranslationKey,
                    ),
                    value: queue,
                })),
            ],
        },
        {
            name: 'horizon',
            label: t('lease_renewals.horizon'),
            options: horizons.map((horizon) => ({
                label: t(
                    `lease_renewals.horizon_${horizon}` as UiTranslationKey,
                ),
                value: horizon,
            })),
        },
        {
            name: 'lease_status',
            label: t('lease_renewals.lease_status'),
            options: [
                {
                    label: t('lease_renewals.status_all'),
                    value: 'all',
                },
                ...leaseStatuses.map((status) => ({
                    label: t(`status.${status}` as UiTranslationKey),
                    value: status,
                })),
            ],
        },
    ];

    if (includePortfolio) {
        fields.push({
            name: 'portfolio_id',
            label: t('lease_renewals.portfolio'),
            clears: ['property_id'],
            options: [
                {
                    label: t('lease_renewals.all_portfolios'),
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
