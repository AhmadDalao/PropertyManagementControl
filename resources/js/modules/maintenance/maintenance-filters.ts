import type { TableFilterField } from '@/components/data-table';
import { humanLabel } from '@/components/operations';

type MaintenanceFilterOptions = {
    categories: string[];
    priorities: string[];
    statuses: string[];
};

export function maintenanceFilterFields({
    categories,
    priorities,
    statuses,
}: MaintenanceFilterOptions): TableFilterField[] {
    return [
        selectField('status', 'Status', statuses),
        selectField('category', 'Category', categories),
        selectField('priority', 'Priority', priorities),
        { name: 'date_from', label: 'From', type: 'date' },
        { name: 'date_to', label: 'To', type: 'date' },
    ];
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
