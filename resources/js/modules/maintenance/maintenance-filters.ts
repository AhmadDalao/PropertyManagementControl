import type { TableFilterField } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';
import type { Translator } from '@/lib/i18n';

type MaintenanceFilterOptions = {
    categories: string[];
    priorities: string[];
    statuses: string[];
};

export function useMaintenanceFilterFields({
    categories,
    priorities,
    statuses,
}: MaintenanceFilterOptions): TableFilterField[] {
    const { t } = useTranslator();

    return [
        selectField('status', t('maintenance.status'), statuses, t),
        selectField('category', t('maintenance.category'), categories, t),
        selectField('priority', t('maintenance.priority'), priorities, t),
        { name: 'date_from', label: t('maintenance.from'), type: 'date' },
        { name: 'date_to', label: t('maintenance.to'), type: 'date' },
    ];
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
