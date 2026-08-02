import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import type { WorkOrderRecord } from './types';
import {
    WorkOrderActions,
    WorkOrderCosts,
    WorkOrderIdentity,
    WorkOrderPropertyTenant,
    WorkOrderResponsibility,
    WorkOrderSchedule,
} from './work-order-cells';

export function useWorkOrderTableConfig(): {
    columns: Array<TableColumn<WorkOrderRecord>>;
    mobileCard: MobileTableConfig<WorkOrderRecord>;
} {
    const { t } = useTranslator();
    const identity = (order: WorkOrderRecord) => (
        <WorkOrderIdentity order={order} />
    );
    const property = (order: WorkOrderRecord) => (
        <WorkOrderPropertyTenant order={order} />
    );
    const responsibility = (order: WorkOrderRecord) => (
        <WorkOrderResponsibility order={order} />
    );
    const schedule = (order: WorkOrderRecord) => (
        <WorkOrderSchedule order={order} />
    );
    const costs = (order: WorkOrderRecord) => <WorkOrderCosts order={order} />;
    const actions = (order: WorkOrderRecord) => (
        <WorkOrderActions order={order} />
    );

    return {
        mobileCard: {
            title: identity,
            status: schedule,
            meta: [
                { label: t('work_orders.property_tenant'), value: property },
                {
                    label: t('work_orders.responsibility'),
                    value: responsibility,
                },
                { label: t('work_orders.costs'), value: costs },
            ],
            actions,
        },
        columns: [
            {
                key: 'order',
                label: t('work_orders.work_order'),
                render: identity,
            },
            {
                key: 'property',
                label: t('work_orders.property_tenant'),
                render: property,
            },
            {
                key: 'responsibility',
                label: t('work_orders.responsibility'),
                render: responsibility,
            },
            {
                key: 'schedule',
                label: t('work_orders.schedule_access'),
                render: schedule,
            },
            {
                key: 'costs',
                label: t('work_orders.costs'),
                render: costs,
            },
            {
                key: 'actions',
                label: t('work_orders.actions'),
                className: 'text-end',
                render: actions,
            },
        ],
    };
}
