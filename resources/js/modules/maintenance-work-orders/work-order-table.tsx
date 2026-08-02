import { DataTable, exportUrl } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import type { WorkOrderIndexProps } from './types';
import { useWorkOrderFilters } from './work-order-filters';
import { useWorkOrderTableConfig } from './work-order-table-config';

export function WorkOrderTable(props: WorkOrderIndexProps) {
    const { t } = useTranslator();
    const table = useWorkOrderTableConfig();

    return (
        <DataTable
            title={t('work_orders.register_title')}
            description={t('work_orders.register_description')}
            data={props.workOrders}
            filters={props.filters}
            counts={props.counts}
            basePath="/maintenance-work-orders"
            rowHref={(order) => `/maintenance-work-orders/${order.id}`}
            exportHref={exportUrl(
                '/exports/maintenance-work-orders',
                props.filters,
            )}
            filterFields={useWorkOrderFilters(props)}
            emptyText={t('work_orders.empty')}
            mobileCard={table.mobileCard}
            columns={table.columns}
        />
    );
}
