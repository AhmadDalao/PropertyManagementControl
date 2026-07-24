import type { Translator } from '@/lib/i18n';
import type { PropertyOption } from '@/types';

import type { TableFilterField } from './types';

export function propertyFilterField(
    properties: PropertyOption[],
    t: Translator,
): TableFilterField {
    return {
        name: 'property_id',
        label: t('filters.property'),
        clears: ['portfolio_id'],
        options: [
            { label: t('filters.all_properties'), value: 'all' },
            ...properties.map((property) => ({
                label: property.name,
                value: property.id,
            })),
        ],
    };
}
