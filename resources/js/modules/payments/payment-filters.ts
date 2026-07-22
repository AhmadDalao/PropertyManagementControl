import type { TableFilterField } from '@/components/data-table';
import type { Translator, UiTranslationKey } from '@/lib/i18n';

type PaymentFilterOptions = {
    statuses: string[];
    types: string[];
    methods: string[];
    portfolios: Array<{ id: number; name: string }>;
    includePortfolio: boolean;
};

export function paymentFilterFields(
    {
        statuses,
        types,
        methods,
        portfolios,
        includePortfolio,
    }: PaymentFilterOptions,
    t: Translator,
): TableFilterField[] {
    const fields: TableFilterField[] = [
        selectField('status', t('payments.status'), statuses, 'status', t),
        selectField('type', t('payments.type'), types, 'type', t),
        selectField('method', t('payments.method'), methods, 'method', t),
        { name: 'date_from', label: t('payments.from'), type: 'date' },
        { name: 'date_to', label: t('payments.to'), type: 'date' },
    ];

    if (includePortfolio) {
        fields.push({
            name: 'portfolio_id',
            label: t('payments.portfolio'),
            options: [
                { label: t('payments.all'), value: 'all' },
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
    namespace: 'status' | 'type' | 'method',
    t: Translator,
): TableFilterField {
    return {
        name,
        label,
        options: [
            { label: t('payments.all'), value: 'all' },
            ...options.map((option) => ({
                label: t(
                    (namespace === 'status'
                        ? `status.${option}`
                        : `payments.${namespace}_${option}`) as UiTranslationKey,
                ),
                value: option,
            })),
        ],
    };
}
