import type { TableFilterField } from '@/components/data-table';
import { humanLabel } from '@/components/operations';

type LeaseFilterOptions = {
    statuses: string[];
    frequencies: string[];
    portfolios: Array<{ id: number; name: string }>;
    includePortfolio: boolean;
};

export function leaseFilterFields({
    statuses,
    frequencies,
    portfolios,
    includePortfolio,
}: LeaseFilterOptions): TableFilterField[] {
    const fields: TableFilterField[] = [
        selectField('status', 'Status', statuses),
        selectField('payment_frequency', 'Frequency', frequencies),
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
