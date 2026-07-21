import type { TableFilterField } from '@/components/data-table';
import { humanLabel } from '@/components/operations';

type PaymentFilterOptions = {
    statuses: string[];
    types: string[];
    methods: string[];
    portfolios: Array<{ id: number; name: string }>;
    includePortfolio: boolean;
};

export function paymentFilterFields({
    statuses,
    types,
    methods,
    portfolios,
    includePortfolio,
}: PaymentFilterOptions): TableFilterField[] {
    const fields: TableFilterField[] = [
        selectField('status', 'Status', statuses),
        selectField('type', 'Type', types),
        selectField('method', 'Method', methods),
        { name: 'date_from', label: 'From', type: 'date' },
        { name: 'date_to', label: 'To', type: 'date' },
    ];

    if (includePortfolio) {
        fields.push({
            name: 'portfolio_id',
            label: 'Portfolio',
            options: [
                { label: 'All', value: 'all' },
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
): TableFilterField {
    return {
        name,
        label,
        options: [
            { label: 'All', value: 'all' },
            ...options.map((option) => ({
                label: humanLabel(option),
                value: option,
            })),
        ],
    };
}
