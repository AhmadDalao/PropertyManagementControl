import { propertyFilterField } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import type { Translator, UiTranslationKey } from '@/lib/i18n';
import type { PropertyOption } from '@/types';

type RentCollectionFilterOptions = {
    statuses: string[];
    lineTypes: string[];
    portfolios: Array<{ id: number; name: string }>;
    properties: PropertyOption[];
    includePortfolio: boolean;
};

export function rentCollectionFilterFields(
    {
        statuses,
        lineTypes,
        portfolios,
        properties,
        includePortfolio,
    }: RentCollectionFilterOptions,
    t: Translator,
): TableFilterField[] {
    const fields: TableFilterField[] = [
        {
            name: 'status',
            label: t('rent_collection.status'),
            options: [
                {
                    label: t('rent_collection.status_all'),
                    value: 'all',
                },
                ...statuses.map((status) => ({
                    label: t(
                        `rent_collection.status_${status}` as UiTranslationKey,
                    ),
                    value: status,
                })),
            ],
        },
        {
            name: 'line_type',
            label: t('rent_collection.type'),
            options: [
                {
                    label: t('rent_collection.type_all'),
                    value: 'all',
                },
                ...lineTypes.map((type) => ({
                    label: t(
                        `rent_collection.type_${type}` as UiTranslationKey,
                    ),
                    value: type,
                })),
            ],
        },
        {
            name: 'date_from',
            label: t('rent_collection.due_from'),
            type: 'date',
        },
        {
            name: 'date_to',
            label: t('rent_collection.due_to'),
            type: 'date',
        },
    ];

    if (includePortfolio) {
        fields.push({
            name: 'portfolio_id',
            label: t('rent_collection.portfolio'),
            clears: ['property_id'],
            options: [
                {
                    label: t('rent_collection.all_portfolios'),
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
