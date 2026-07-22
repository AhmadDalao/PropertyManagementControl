import { DataTable, exportUrl } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { useMaintenanceFilterFields } from './maintenance-filters';
import { useMaintenanceTableConfig } from './maintenance-table-config';
import type { MaintenanceTableProps } from './types';

export function MaintenanceTable(props: MaintenanceTableProps) {
    const { t } = useTranslator();
    const filterFields = useMaintenanceFilterFields({
        categories: props.categoryOptions,
        priorities: props.priorityOptions,
        statuses: props.statusOptions,
    });
    const table = useMaintenanceTableConfig(props);

    return (
        <DataTable
            title={t('maintenance.queue_title')}
            description={t('maintenance.queue_description')}
            data={props.requests}
            filters={props.filters}
            counts={props.counts}
            basePath="/maintenance-requests"
            rowHref={(request) => `/maintenance-requests/${request.id}`}
            exportHref={
                props.mode === 'manager'
                    ? exportUrl('/exports/maintenance-requests', props.filters)
                    : undefined
            }
            filterFields={filterFields}
            emptyText={t('maintenance.empty')}
            createHref="/maintenance-requests/create"
            createLabel={t('maintenance.create_request')}
            mobileCard={table.mobileCard}
            columns={table.columns}
        />
    );
}
