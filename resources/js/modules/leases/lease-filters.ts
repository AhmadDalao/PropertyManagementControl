import type { TableFilterField } from '@/components/data-table';
import type { Translator, UiTranslationKey } from '@/lib/i18n';

type LeaseFilterOptions = {
    statuses: string[];
    frequencies: string[];
    portfolios: Array<{ id: number; name: string }>;
    includePortfolio: boolean;
};

export function leaseFilterFields(
    { statuses, frequencies, portfolios, includePortfolio }: LeaseFilterOptions,
    t: Translator,
): TableFilterField[] {
    const fields: TableFilterField[] = [
        selectField('status', t('leases.status'), statuses, 'status', t),
        selectField(
            'payment_frequency',
            t('leases.frequency'),
            frequencies,
            'frequency',
            t,
        ),
        { name: 'date_from', label: t('leases.from'), type: 'date' },
        { name: 'date_to', label: t('leases.to'), type: 'date' },
    ];

    if (includePortfolio) {
        fields.push({
            name: 'portfolio_id',
            label: t('leases.portfolio'),
            options: [
                { label: t('leases.all'), value: 'all' },
                ...portfolios.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return fields;
}

function selectField(
    name: string,
    label: string,
    options: string[],
    namespace: 'status' | 'frequency',
    t: Translator,
): TableFilterField {
    return {
        name,
        label,
        options: [
            { label: t('leases.all'), value: 'all' },
            ...options.map((option) => ({
                label: t(
                    (namespace === 'status'
                        ? `status.${option}`
                        : `leases.frequency_${option}`) as UiTranslationKey,
                ),
                value: option,
            })),
        ],
    };
}
