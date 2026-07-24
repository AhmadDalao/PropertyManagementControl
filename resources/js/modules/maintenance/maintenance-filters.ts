import { propertyFilterField } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';
import type { Translator } from '@/lib/i18n';
import type { PropertyOption } from '@/types';

type MaintenanceFilterOptions = {
    categories: string[];
    priorities: string[];
    statuses: string[];
    portfolios: Array<{ id: number; name: string }>;
    properties: PropertyOption[];
    includePortfolio: boolean;
    includeProperty: boolean;
};

export function useMaintenanceFilterFields({
    categories,
    priorities,
    statuses,
    portfolios,
    properties,
    includePortfolio,
    includeProperty,
}: MaintenanceFilterOptions): TableFilterField[] {
    const { t } = useTranslator();

    const fields: TableFilterField[] = [
        selectField('status', t('maintenance.status'), statuses, t),
        selectField('category', t('maintenance.category'), categories, t),
        selectField('priority', t('maintenance.priority'), priorities, t),
        { name: 'date_from', label: t('maintenance.from'), type: 'date' },
        { name: 'date_to', label: t('maintenance.to'), type: 'date' },
    ];

    if (includePortfolio) {
        fields.push({
            name: 'portfolio_id',
            label: t('maintenance.portfolio'),
            clears: ['property_id'],
            options: [
                { label: t('maintenance.all'), value: 'all' },
                ...portfolios.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    if (includeProperty) {
        fields.push(propertyFilterField(properties, t));
    }

    return fields;
}

function selectField(
    name: string,
    label: string,
    options: string[],
    t: Translator,
): TableFilterField {
    return {
        name,
        label,
        options: [
            { label: t('maintenance.all'), value: 'all' },
            ...options.map((option) => ({
                label: t(`status.${option}`),
                value: option,
            })),
        ],
    };
}
